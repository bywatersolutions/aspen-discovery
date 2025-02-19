package com.turning_leaf_technologies.sideloading;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.file.JarUtil;
import org.aspen_discovery.grouping.RecordGroupingProcessor;
import org.aspen_discovery.grouping.RemoveRecordFromWorkResult;
import org.aspen_discovery.grouping.SideLoadedRecordGrouper;
import com.turning_leaf_technologies.indexing.IlsTitle;
import com.turning_leaf_technologies.indexing.IndexingUtils;
import com.turning_leaf_technologies.indexing.RecordIdentifier;
import com.turning_leaf_technologies.indexing.SideLoadSettings;
import com.turning_leaf_technologies.logging.LoggingUtil;
import org.aspen_discovery.reindexer.GroupedWorkIndexer;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import com.turning_leaf_technologies.util.SystemUtils;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.MarcException;
import org.marc4j.MarcPermissiveStreamReader;
import org.marc4j.MarcReader;

import java.io.File;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.sql.*;
import java.util.*;
import java.util.Date;

public class SideLoadingMain {
	private static Logger logger;
	private static String serverName;

	private static Ini configIni;

	private static Connection aspenConn;

	private static SideLoadLogEntry logEntry;

	private static long startTimeForLogging;

	//Record grouper
	private static GroupedWorkIndexer groupedWorkIndexer;
	private static final HashMap<String, SideLoadedRecordGrouper> recordGroupingProcessors = new HashMap<>();

	public static void main(String[] args) {
		String profileToLoad = "";
		if (args.length == 0) {
			serverName = AspenStringUtils.getInputFromCommandLine("Please enter the server name");
			if (serverName.isEmpty()) {
				System.out.println("You must provide the server name as the first argument.");
				System.exit(1);
			}
			profileToLoad = AspenStringUtils.getInputFromCommandLine("Enter the name or id of the profile to run (empty to run all)");
		} else {
			serverName = args[0];
		}

		String processName = "sideload_processing";
		logger = LoggingUtil.setupLogging(serverName, processName);

		//Get the checksum of the JAR when it was started, so we can stop if it has changed.
		long myChecksumAtStart = JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar");
		long reindexerChecksumAtStart = JarUtil.getChecksumForJar(logger, "reindexer", "../reindexer/reindexer.jar");
		long timeAtStart = new Date().getTime();

		while (true) {
			Date startTime = new Date();
			logger.info(startTime + ": Starting Side Load Export");
			startTimeForLogging = startTime.getTime() / 1000;

			// Read the base INI file to get information about the server (current directory/cron/config.ini)
			configIni = ConfigUtil.loadConfigFile("config.ini", serverName, logger);

			//Connect to the Aspen database
			aspenConn = connectToDatabase();

			//Start a log entry
			createDbLogEntry(startTime, aspenConn);
			logEntry.addNote("Starting Update of Side Loaded eContent");
			logEntry.saveResults();

			//Get a list of side loads
			try {
				PreparedStatement getSideloadsStmt = aspenConn.prepareStatement("SELECT * FROM sideloads ORDER BY name");
				if (!profileToLoad.isEmpty()){
					getSideloadsStmt = aspenConn.prepareStatement("SELECT * FROM sideloads where name = ? OR id = ? ORDER BY name");
					getSideloadsStmt.setString(1, profileToLoad);
					getSideloadsStmt.setString(2, profileToLoad);
				}
				PreparedStatement getFilesForSideloadStmt = aspenConn.prepareStatement("SELECT * from sideload_files where sideLoadId = ?");
				PreparedStatement insertSideloadFileStmt = aspenConn.prepareStatement("INSERT INTO sideload_files (sideLoadId, filename, lastChanged, lastIndexed) VALUES (?, ?, ?, ?)");
				PreparedStatement updateSideloadFileStmt = aspenConn.prepareStatement("UPDATE sideload_files set lastChanged = ?, deletedTime = ?, lastIndexed = ? WHERE id = ?");
				ResultSet getSideloadsRS = getSideloadsStmt.executeQuery();
				while (getSideloadsRS.next()) {
					//Check to see if the jar has changes before processing records, and if so quit
					if (myChecksumAtStart != JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar")){
						IndexingUtils.markNightlyIndexNeeded(aspenConn, logger);
						disconnectDatabase(aspenConn);
						break;
					}
					if (reindexerChecksumAtStart != JarUtil.getChecksumForJar(logger, "reindexer", "../reindexer/reindexer.jar")){
						IndexingUtils.markNightlyIndexNeeded(aspenConn, logger);
						disconnectDatabase(aspenConn);
						break;
					}

					SideLoadSettings settings = new SideLoadSettings(serverName, getSideloadsRS, logEntry);
					processSideLoad(settings, getFilesForSideloadStmt, insertSideloadFileStmt, updateSideloadFileStmt);
				}
				getFilesForSideloadStmt.close();
				insertSideloadFileStmt.close();
				updateSideloadFileStmt.close();
			} catch (SQLException e) {
				logger.error("Error loading sideloads to run", e);
			}

			for (RecordGroupingProcessor recordGroupingProcessor : recordGroupingProcessors.values()){
				recordGroupingProcessor.close();
			}
			recordGroupingProcessors.clear();

			if (groupedWorkIndexer != null) {
				groupedWorkIndexer.finishIndexingFromExtract(logEntry);
				groupedWorkIndexer.close();
				groupedWorkIndexer = null;
			}

			logger.info("Finished exporting data " + new Date());
			long endTime = new Date().getTime();
			long elapsedTime = endTime - startTime.getTime();
			logger.info("Elapsed Minutes " + (elapsedTime / 60000));

			//Mark that indexing has finished
			logEntry.setFinished();

			//Check to see if the jar has changes, and if so quit
			if (myChecksumAtStart != JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar")){
				IndexingUtils.markNightlyIndexNeeded(aspenConn, logger);
				disconnectDatabase(aspenConn);
				break;
			}
			if (reindexerChecksumAtStart != JarUtil.getChecksumForJar(logger, "reindexer", "../reindexer/reindexer.jar")){
				IndexingUtils.markNightlyIndexNeeded(aspenConn, logger);
				disconnectDatabase(aspenConn);
				break;
			}
			//Check to see if it's between midnight and 1 am and the jar has been running more than 15 hours.  If so, restart just to clean up memory.
			GregorianCalendar nowAsCalendar = new GregorianCalendar();
			Date now = new Date();
			nowAsCalendar.setTime(now);
			if (nowAsCalendar.get(Calendar.HOUR_OF_DAY) <=1 && (now.getTime() - timeAtStart) > 15 * 60 * 60 * 1000 ){
				logger.info("Ending because we have been running for more than 15 hours and it's between midnight and one AM");
				disconnectDatabase(aspenConn);
				break;
			}
			//Check memory to see if we should close
			if (SystemUtils.hasLowMemory(configIni, logger)){
				logger.info("Ending because we have low memory available");
				disconnectDatabase(aspenConn);
				break;
			}

			disconnectDatabase(aspenConn);

			if (!profileToLoad.isEmpty()){
				break;
			}

			//Check to see if nightly indexing is running and if so, wait until it is done.
			if (IndexingUtils.isNightlyIndexRunning(configIni, serverName, logger)) {
				//Quit and we will restart after if finishes
				System.exit(0);
			}else {
				//Pause 5 minutes before running the next export
				try {
					System.gc();
					Thread.sleep(1000 * 60 * 5);
				} catch (InterruptedException e) {
					logger.info("Thread was interrupted");
				}
			}
		}

		System.exit(0);
	}

	private static void processSideLoad(SideLoadSettings settings, PreparedStatement getFilesForSideloadStmt, PreparedStatement insertSideloadFileStmt, PreparedStatement updateSideloadFileStmt) {
		logger.warn("Processing side load " + settings.getName() + " " + settings.getId());
		File marcDirectory = new File(settings.getMarcPath());
		if (!marcDirectory.exists()) {
			logEntry.incErrors("Marc Directory " + settings.getMarcPath() + " did not exist");
		} else {
			TreeSet<SideLoadFile> filesToProcess = new TreeSet<>();
			try {
				//Get a list of all files that have been indexed previously
				getFilesForSideloadStmt.setLong(1, settings.getId());
				ResultSet filesForSideloadRS = getFilesForSideloadStmt.executeQuery();
				while (filesForSideloadRS.next()){
					SideLoadFile fileForRecordInDatabase = new SideLoadFile(filesForSideloadRS);
					filesToProcess.add(fileForRecordInDatabase);
					logger.warn("Found existing file in database '" + fileForRecordInDatabase.getFilename() + "' - sideLoadId " + fileForRecordInDatabase.getSideLoadId() + " - id " + fileForRecordInDatabase.getId() + " number of files to process is: " + filesToProcess.size());
				}
			}catch (Exception e){
				logEntry.incErrors("Could not load existing files for sideload " + settings.getName(), e);
			}

			//Get a list of all files that are currently on the server
			File[] marcFiles = marcDirectory.listFiles((dir, name) -> name.matches(settings.getFilenamesToInclude()));
			if (marcFiles != null) {
				for (File marcFile : marcFiles) {
					logger.warn("Found file in the filesystem '" + marcFile.getName() + "'");
					//Get the SideLoadFile for the file
					boolean foundFileInDB = false;
					for (SideLoadFile curFile : filesToProcess){
						if (curFile.getFilename().equalsIgnoreCase(marcFile.getName())){
							logger.warn("Matched file on file system '" + marcFile.getName() + " 'to file in database - id " + curFile.getId());
							curFile.setExistingFile(marcFile);
							//Force resorting if needed to make sure the list is sorted based on the last change time so remove it and then re-add
							filesToProcess.remove(curFile);
							filesToProcess.add(curFile);
							logger.warn("There are " + filesToProcess.size() + " files to process after adding and removing " + curFile);
							foundFileInDB = true;
							break;
						}
					}
					if (!foundFileInDB){
						logger.warn("Did not find the file in the database, added a new file to process with id 0 (new)");
						filesToProcess.add(new SideLoadFile(settings.getId(), marcFile));
					}
				}
			}

			//If any files have been deleted or if any files have changed, we will do a full reindex since we don't store which
			//file a record comes from.
			boolean changesMade = false;
			for (SideLoadFile curFile : filesToProcess){
				//We need a reindex if the file was undeleted or modified since we last saw it
				if (curFile.isNeedsReindex()){
					if (curFile.getId() == 0){
						logger.warn("File " + curFile.getFilename() + " - id " + curFile.getId() + " was added");
						logEntry.addNote(curFile.getFilename() + " was added");
					}else{
						logger.warn("File " + curFile.getFilename() + " - id " + curFile.getId() + " was changed");
						logEntry.addNote(curFile.getFilename() + " was changed");
					}
					changesMade = true;
				}else if (curFile.getExistingFile() == null){
					logger.warn("File " + curFile.getFilename() + " - id " + curFile.getId() + " no longer exists");
					//The file no longer exists, so we need to mark it as deleted.
					if (curFile.getDeletedTime() == 0){
						logEntry.addNote(curFile.getFilename() + " was deleted");
						curFile.setDeletedTime(new Date().getTime() / 1000);
					}
					//This file has been deleted
					if (curFile.getDeletedTime() > curFile.getLastIndexed()){
						changesMade = true;
					}
				}
			}
			logEntry.saveResults();

			HashMap<String, IlsTitle> existingRecords;

			if (settings.isRunFullUpdate() || changesMade){
				logEntry.addUpdatedSideLoad(settings.getName());

				SideLoadedRecordGrouper recordGrouper = getRecordGroupingProcessor(settings);
				recordGrouper.loadExistingTitles(logEntry);
				existingRecords = recordGrouper.getExistingRecords();

				for (SideLoadFile curFile : filesToProcess){
					try {
						//When one file changes, we need to make sure that all of them are reprocessed in case a record
						//exists in File A, but not File B. If we didn't process both, the record would be deleted.
						//the other issue would be if a record is deleted from File B we would not necessarily know
						//That it should be removed unless we process both.
						if (curFile.getExistingFile() != null) {
							logEntry.addNote("Processing sideload file " + curFile.getFilename());
							processSideLoadFile(curFile.getExistingFile(), existingRecords, settings);
							curFile.updateDatabase(insertSideloadFileStmt, updateSideloadFileStmt);
							getGroupedWorkIndexer().commitChanges();
						} else {
							if (curFile.getDeletedTime() > curFile.getLastIndexed()) {
								logEntry.addNote("Marking " + curFile.getFilename() + " as deleted");
								curFile.updateDatabase(insertSideloadFileStmt, updateSideloadFileStmt);
							}else{
								logEntry.addNote("Ignoring " + curFile.getFilename() + ", because it was marked as deleted previously");
							}
						}
					}catch (SQLException sqlE){
						logEntry.incErrors("Error processing sideload file", sqlE);
					}
				}

				//Remove any records that no longer exist
				try {
					PreparedStatement deleteFromIlsMarcChecksums = aspenConn.prepareStatement("UPDATE ils_records set deleted = 1, dateDeleted = ? where source = ? and ilsId = ?");
					for (String existingIdentifier : existingRecords.keySet()) {
						IlsTitle title = existingRecords.get(existingIdentifier);
						if (!title.isDeleted()) {
							deleteFromIlsMarcChecksums.setLong(1, new Date().getTime() / 1000);
							deleteFromIlsMarcChecksums.setString(2, settings.getName());
							deleteFromIlsMarcChecksums.setString(3, existingIdentifier);
							deleteFromIlsMarcChecksums.executeUpdate();

							//Delete from ils_marc_checksums
							RemoveRecordFromWorkResult result = recordGrouper.removeRecordFromGroupedWork(settings.getName(), existingIdentifier);
							getGroupedWorkIndexer().markIlsRecordAsDeleted(settings.getName(), existingIdentifier);
							if (result.reindexWork) {
								getGroupedWorkIndexer().processGroupedWork(result.permanentId);
							} else if (result.deleteWork) {
								//Delete the work from solr and the database
								getGroupedWorkIndexer().deleteRecord(result.permanentId, result.groupedWorkId);
							}

							logEntry.incDeleted();
							if (logEntry.getNumDeleted() % 500 == 0) {
								getGroupedWorkIndexer().commitChanges();
							}
						}
					}
					deleteFromIlsMarcChecksums.close();
				} catch (Exception e) {
					logEntry.incErrors("Error deleting records from " + settings.getName(), e);
				}
			}

			processRecordsToReload(settings, logEntry);
			getGroupedWorkIndexer().commitChanges();

			try {
				PreparedStatement updateSideloadStmt;
				if (settings.isRunFullUpdate()) {
					updateSideloadStmt = aspenConn.prepareStatement("UPDATE sideloads set lastUpdateOfAllRecords = ?, runFullUpdate = 0 where id = ?");
				} else {
					updateSideloadStmt = aspenConn.prepareStatement("UPDATE sideloads set lastUpdateOfChangedRecords = ? where id = ?");
				}

				updateSideloadStmt.setLong(1, startTimeForLogging);
				updateSideloadStmt.setLong(2, settings.getId());
				updateSideloadStmt.executeUpdate();
			} catch (Exception e) {
				logEntry.incErrors("Error updating lastUpdateFromMarcExport", e);
			}
		}
	}

	private static void processRecordsToReload(SideLoadSettings settings, SideLoadLogEntry logEntry) {
		try {
			PreparedStatement getRecordsToReloadStmt = aspenConn.prepareStatement("SELECT * from record_identifiers_to_reload WHERE processed = 0 and type=?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getRecordsToReloadStmt.setString(1, settings.getName());
			PreparedStatement markRecordToReloadAsProcessedStmt = aspenConn.prepareStatement("UPDATE record_identifiers_to_reload SET processed = 1 where id = ?");
			ResultSet getRecordsToReloadRS = getRecordsToReloadStmt.executeQuery();
			int numRecordsToReloadProcessed = 0;
			SideLoadedRecordGrouper recordGrouper = getRecordGroupingProcessor(settings);
			GroupedWorkIndexer indexer = getGroupedWorkIndexer();
			while (getRecordsToReloadRS.next()) {
				long recordToReloadId = getRecordsToReloadRS.getLong("id");
				String recordIdentifier = getRecordsToReloadRS.getString("identifier");
				//getGroupedWorkIndexer().loadMarcRecordFromDatabase() Ticket 95343
				org.marc4j.marc.Record marcRecord = indexer.loadMarcRecordFromDatabase(settings.getName(), recordIdentifier, logEntry);
				if (marcRecord != null) {
					//Regroup the record
					String groupedWorkId = recordGrouper.processMarcRecord(marcRecord, true, null, getGroupedWorkIndexer());
					//Reindex the record
					getGroupedWorkIndexer().processGroupedWork(groupedWorkId);
				}

				markRecordToReloadAsProcessedStmt.setLong(1, recordToReloadId);
				markRecordToReloadAsProcessedStmt.executeUpdate();
				numRecordsToReloadProcessed++;
			}
			if (numRecordsToReloadProcessed > 0) {
				logEntry.addNote("Regrouped " + numRecordsToReloadProcessed + " records marked for reprocessing");
			}
			getRecordsToReloadRS.close();
		}catch (Exception e){
			logEntry.incErrors("Error processing records to reload ", e);
		}
	}

	private static void processSideLoadFile(File fileToProcess, HashMap<String, IlsTitle> existingRecords, SideLoadSettings settings) {
		try {
			logEntry.addNote("Processing " + fileToProcess.getName());
			SideLoadedRecordGrouper recordGrouper = getRecordGroupingProcessor(settings);
			GroupedWorkIndexer reindexer = getGroupedWorkIndexer();
			MarcReader marcReader = new MarcPermissiveStreamReader(new FileInputStream(fileToProcess), true, true, settings.getMarcEncoding());
			while (marcReader.hasNext()) {
				try {
					org.marc4j.marc.Record marcRecord = marcReader.next();
					RecordIdentifier recordIdentifier = recordGrouper.getPrimaryIdentifierFromMarcRecord(marcRecord, settings);
					if (recordIdentifier != null) {
						existingRecords.remove(recordIdentifier.getIdentifier());
						logEntry.incNumProducts(1);
						boolean deleteRecord = false;
						if (settings.getDeletedIds().contains(recordIdentifier.getIdentifier())){
							deleteRecord = true;
						}else {
							String recordNumber = recordIdentifier.getIdentifier();
							GroupedWorkIndexer.MarcStatus marcStatus = reindexer.saveMarcRecordToDatabase(settings, recordNumber, marcRecord);
							if (marcStatus != GroupedWorkIndexer.MarcStatus.UNCHANGED || settings.isRunFullUpdate()) {
								String permanentId = recordGrouper.processMarcRecord(marcRecord, marcStatus != GroupedWorkIndexer.MarcStatus.UNCHANGED, null, getGroupedWorkIndexer());
								if (permanentId == null) {
									//Delete the record since it is suppressed
									deleteRecord = true;
								} else {
									if (marcStatus == GroupedWorkIndexer.MarcStatus.NEW) {
										logEntry.incAdded();
									} else {
										logEntry.incUpdated();
									}
									getGroupedWorkIndexer().processGroupedWork(permanentId);
								}
							} else {
								logEntry.incSkipped();
							}
						}
						if (deleteRecord) {
							RemoveRecordFromWorkResult result = recordGrouper.removeRecordFromGroupedWork(settings.getName(), recordIdentifier.getIdentifier());
							if (result.reindexWork) {
								getGroupedWorkIndexer().processGroupedWork(result.permanentId);
							} else if (result.deleteWork) {
								//Delete the work from solr and the database
								getGroupedWorkIndexer().deleteRecord(result.permanentId, result.groupedWorkId);
							}
							getGroupedWorkIndexer().markIlsRecordAsDeleted(settings.getName(), recordIdentifier.getIdentifier());
							logEntry.incDeleted();
						}
						if (logEntry.getNumProducts() % 250 == 0) {
							logEntry.saveResults();
						}
					}
				}catch (MarcException e){
					logEntry.incErrors("Error reading MARC file " + fileToProcess, e);
				}
			}
			logEntry.saveResults();
		} catch (FileNotFoundException e) {
			logEntry.incErrors("Could not find file " + fileToProcess.getAbsolutePath());
		} catch (Exception e){
			logEntry.incErrors("Error processing side load file " + fileToProcess, e);
		}
	}

	private static Connection connectToDatabase() {
		Connection aspenConn = null;
		try {
			String databaseConnectionInfo = ConfigUtil.cleanIniValue(configIni.get("Database", "database_aspen_jdbc"));
			if (databaseConnectionInfo != null) {
				aspenConn = DriverManager.getConnection(databaseConnectionInfo);
			} else {
				logger.error("Aspen database connection information was not provided");
				System.exit(1);
			}
		} catch (Exception e) {
			logger.error("Error connecting to Aspen database " + e);
			System.exit(1);
		}
		return aspenConn;
	}

	private static void disconnectDatabase(Connection aspenConn) {
		try {
			aspenConn.close();
		} catch (Exception e) {
			logger.error("Error closing database ", e);
			System.exit(1);
		}
	}

	private static void createDbLogEntry(Date startTime, Connection aspenConn) {
		//Remove log entries older than 45 days
		long earliestLogToKeep = (startTime.getTime() / 1000) - (60 * 60 * 24 * 45);
		try {
			int numDeletions = aspenConn.prepareStatement("DELETE from sideload_log WHERE startTime < " + earliestLogToKeep).executeUpdate();
			logger.info("Deleted " + numDeletions + " old log entries");
		} catch (SQLException e) {
			logger.error("Error deleting old log entries", e);
		}

		logEntry = new SideLoadLogEntry(aspenConn, logger);
	}

	private static GroupedWorkIndexer getGroupedWorkIndexer() {
		if (groupedWorkIndexer == null) {
			groupedWorkIndexer = new GroupedWorkIndexer(serverName, aspenConn, configIni, false, false, logEntry, logger);
		}
		return groupedWorkIndexer;
	}

	private static SideLoadedRecordGrouper getRecordGroupingProcessor(SideLoadSettings settings) {
		SideLoadedRecordGrouper recordGroupingProcessor = recordGroupingProcessors.get(settings.getName());
		if (recordGroupingProcessor == null) {
			recordGroupingProcessor = new SideLoadedRecordGrouper(serverName, aspenConn, settings, logEntry, logger);
			recordGroupingProcessors.put(settings.getName(), recordGroupingProcessor);
		}
		return recordGroupingProcessor;
	}
}
