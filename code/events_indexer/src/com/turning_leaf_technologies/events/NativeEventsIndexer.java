package com.turning_leaf_technologies.events;

import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.apache.logging.log4j.Logger;
import org.apache.solr.client.solrj.SolrServerException;
import org.apache.solr.client.solrj.impl.BaseHttpSolrClient;
import org.apache.solr.client.solrj.impl.ConcurrentUpdateHttp2SolrClient;
import org.apache.solr.common.SolrInputDocument;
import org.json.JSONObject;

import java.io.IOException;
import java.sql.*;
import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.*;
import java.util.Date;
import java.util.zip.CRC32;

import static java.util.Calendar.DAY_OF_YEAR;

public class NativeEventsIndexer {
	private final long settingsId;
	private final int numberOfDaysToIndex;
	private final boolean runFullUpdate;
	private final Connection aspenConn;
	private final EventsIndexerLogEntry logEntry;
	private final HashMap<Long, NativeEvent> eventInstances = new HashMap<>();
	// private final HashSet<String> librariesToShowFor = new HashSet<>();
	private final static CRC32 checksumCalculator = new CRC32();

	private PreparedStatement addEventStmt;
	private PreparedStatement deleteEventStmt;

	private final ConcurrentUpdateHttp2SolrClient solrUpdateServer;

	NativeEventsIndexer(long settingsId, int numberOfDaysToIndex, boolean runFullUpdate, ConcurrentUpdateHttp2SolrClient solrUpdateServer, Connection aspenConn, Logger logger) {
		this.settingsId = settingsId;
		this.aspenConn = aspenConn;
		this.solrUpdateServer = solrUpdateServer;
		this.numberOfDaysToIndex = numberOfDaysToIndex;
		this.runFullUpdate = runFullUpdate;

		logEntry = new EventsIndexerLogEntry("Native Events", aspenConn, logger);

//		try {
//			PreparedStatement getLibraryScopesStmt = aspenConn.prepareStatement("SELECT subdomain from library inner join library_events_setting on library.libraryId = library_events_setting.libraryId WHERE settingSource = 'assabet' AND settingId = ?");
//			getLibraryScopesStmt.setLong(1, settingsId);
//			ResultSet getLibraryScopesRS = getLibraryScopesStmt.executeQuery();
//			while (getLibraryScopesRS.next()){
//				librariesToShowFor.add(getLibraryScopesRS.getString("subdomain").toLowerCase());
//			}
//
//		} catch (Exception e) {
//			logEntry.incErrors("Error setting up statements ", e);
//		}

		loadEvents();
	}

	private final SimpleDateFormat dateFormat = new SimpleDateFormat("yyyy-MM-dd");
	private final SimpleDateFormat eventDayFormatter = new SimpleDateFormat("yyyy-MM-dd");
	private final SimpleDateFormat eventMonthFormatter = new SimpleDateFormat("yyyy-MM");
	private final SimpleDateFormat eventYearFormatter = new SimpleDateFormat("yyyy");
	private void loadEvents() {
		try {
			// Calculate date for numberOfDaysToIndex into the future to add to where statement
			GregorianCalendar lastDateToIndex = new GregorianCalendar();
			lastDateToIndex.setTime(new Date());
			lastDateToIndex.add(DAY_OF_YEAR, this.numberOfDaysToIndex);

			// Get event instance and event info
			PreparedStatement eventsStmt = aspenConn.prepareStatement("SELECT ei.*, e.title, e.description, e.eventTypeId, e.locationId, l.code, e.sublocationId, e.cover, e.private FROM event_instance AS ei LEFT JOIN event as e ON e.id = ei.eventID LEFT JOIN location AS l ON e.locationId = l.locationId WHERE ei.date < ?;");
			// Get libraries for this event type
			PreparedStatement librariesStmt = aspenConn.prepareStatement("SELECT etl.libraryId, l.subdomain FROM event_type_library AS etl LEFT JOIN library as l ON etl.libraryId = l.libraryId WHERE eventTypeId = ?");
			// Get custom fields
			PreparedStatement eventFieldStmt = aspenConn.prepareStatement("SELECT ef.name, ef.allowableValues, ef.type, ef.facetName, eef.value from event_event_field AS eef LEFT JOIN event_field AS ef ON ef.id = eef.eventFieldId WHERE eef.eventId = ?;");

			eventsStmt.setString(1, dateFormat.format(lastDateToIndex.getTime()));
			ResultSet existingEventsRS = eventsStmt.executeQuery();


			while (existingEventsRS.next()) {
				NativeEvent event = new NativeEvent(existingEventsRS);
				librariesStmt.clearParameters();
				librariesStmt.setLong(1, event.getParentEventId());
				ResultSet librariesFieldsRS = librariesStmt.executeQuery();
				while (librariesFieldsRS.next()) {
					event.addLibrary(librariesFieldsRS.getString("subdomain").toLowerCase());
				}
				eventFieldStmt.clearParameters();
				eventFieldStmt.setLong(1, event.getParentEventId());
				ResultSet eventFieldsRS = eventFieldStmt.executeQuery();
				while (eventFieldsRS.next()) {
					String[] allowableValues = eventFieldsRS.getString("allowableValues").split(", ");
					if (allowableValues[0].isEmpty()) {
						allowableValues = new String[0];
					}
					event.addField(eventFieldsRS.getString("name"), eventFieldsRS.getString("value"), allowableValues, eventFieldsRS.getInt("type"), eventFieldsRS.getInt("facetName"));
				}
				eventInstances.put(event.getId(), event);
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error loading event instances for Native Events ", e);
		}
	}


	void indexEvents() {

		if (runFullUpdate) {
			try {
				solrUpdateServer.deleteByQuery("type:event AND source:" + this.settingsId);
			} catch (BaseHttpSolrClient.RemoteSolrException rse) {
				logEntry.incErrors("Solr is not running properly, try restarting " + rse);
				System.exit(-1);
			} catch (Exception e) {
				logEntry.incErrors("Error deleting from index ", e);
			}

			for (NativeEvent eventInfo : eventInstances.values()) {
				//Add the event to solr
				try {
					SolrInputDocument solrDocument = new SolrInputDocument();
					solrDocument.addField("id", "nativeEvent_" + settingsId + "_" + eventInfo.getId());
					solrDocument.addField("identifier", eventInfo.getId());
					solrDocument.addField("type", "event_nativeEvent");
					solrDocument.addField("source", settingsId);

					int boost = 1;
					solrDocument.addField("last_indexed", new Date());
					solrDocument.addField("last_change", null);
					//Make sure the start date exists
					Date startDate = eventInfo.getStartDateTime(logEntry);

					solrDocument.addField("start_date", startDate);
					if (startDate == null) {
						continue;
					}

					solrDocument.addField("start_date_sort", startDate.getTime() / 1000);
					Date endDate = eventInfo.getEndDateTime(logEntry);
					solrDocument.addField("end_date", endDate);

					HashSet<String> eventDays = new HashSet<>();
					HashSet<String> eventMonths = new HashSet<>();
					HashSet<String> eventYears = new HashSet<>();
					Date tmpDate = (Date)startDate.clone();

					if (tmpDate.equals(endDate) || tmpDate.after(endDate)){
						eventDays.add(eventDayFormatter.format(tmpDate));
						eventMonths.add(eventMonthFormatter.format(tmpDate));
						eventYears.add(eventYearFormatter.format(tmpDate));
					}else {
						while (tmpDate.before(endDate)) {
							eventDays.add(eventDayFormatter.format(tmpDate));
							eventMonths.add(eventMonthFormatter.format(tmpDate));
							eventYears.add(eventYearFormatter.format(tmpDate));
							tmpDate.setTime(tmpDate.getTime() + 24 * 60 * 60 * 1000);
						}
					}
					//Boost based on start date, we will give preference to anything in the next 30 days
					Date today = new Date();
					if (startDate.before(today) || startDate.equals(today)){
						boost += 30;
					}else{
						long daysInFuture = (startDate.getTime() - today.getTime()) / (1000 * 60 * 60 * 24);
						if (daysInFuture > 30){
							daysInFuture = 30;
						}
						boost += (int) (30 - daysInFuture);
					}
					solrDocument.addField("event_day", eventDays);
					solrDocument.addField("event_month", eventMonths);
					solrDocument.addField("event_year", eventYears);
					solrDocument.addField("title", eventInfo.getName());

					// Locations
					solrDocument.addField("branch", eventInfo.getLocationCode());
					// Also get sublocation

					// Extra fields
					ArrayList<NativeEvent.EventField> extraFields = eventInfo.getFields();
					for (NativeEvent.EventField field : extraFields) {
						solrDocument.addField(field.getSolrFieldName(), field.getValue()); // Add as a dynamic field
					}

					// need getCoverURL and then
					// solrDocument.addField("image_url", eventInfo.getCoverUrl());

					solrDocument.addField("description", eventInfo.getDescription());

					// Libraries scopes
					solrDocument.addField("library_scopes", eventInfo.getLibraries());

					solrDocument.addField("boost", boost);
					solrUpdateServer.add(solrDocument);

				} catch (SolrServerException | IOException e) {
					logEntry.incErrors("Error adding event to solr ", e);
				}}
		}

	}


}
