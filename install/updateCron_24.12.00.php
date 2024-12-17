<?php
if (count($_SERVER['argv']) > 1) {
	$serverName = $_SERVER['argv'][1];
	//Check to see if the update already exists properly.
	$fhnd = fopen("/usr/local/aspen-discovery/sites/$serverName/conf/crontab_settings.txt", 'r');
	if ($fhnd) {
		$lines = [];
		$insertDismissYearInReviewMessages = true;
		$dismissYearInReviewMessagesInserted = false;
		while (($line = fgets($fhnd)) !== false) {
			if (strpos($line, 'dismissYearInReviewMessages') > 0) {
				$insertDismissYearInReviewMessages = false;
			}
			if (strpos($line, 'Debian needs a blank line at the end of cron') > 0) {
				if ($insertDismissYearInReviewMessages) {
					//Add these before the end of the file in debian
					$lines[] = "######################################\n";
					$lines[] = "# Dismiss old Year in Review messages #\n";
					$lines[] = "######################################\n";
					$lines[] = "0 3 * * * root php /usr/local/aspen-discovery/code/web/cron/dismissYearInReviewMessages.php $serverName\n";
					$dismissYearInReviewMessagesInserted = true;
				}
			}
			$lines[] = $line;
		}
		fclose($fhnd);

		if ($insertDismissYearInReviewMessages && !$dismissYearInReviewMessagesInserted) {
			//Add at the end for everything else
			$lines[] = "######################################\n";
			$lines[] = "# Dismiss old Year in Review messages #\n";
			$lines[] = "######################################\n";
			$lines[] = "0 3 * * * root php /usr/local/aspen-discovery/code/web/cron/dismissYearInReviewMessages.php $serverName\n";
		}
		if ($insertDismissYearInReviewMessages) {
			$newContent = implode('', $lines);
			file_put_contents("/usr/local/aspen-discovery/sites/$serverName/conf/crontab_settings.txt", $newContent);
		}
	} else {
		echo("- Could not find cron settings file\n");
	}

} else {
	echo 'Must provide servername as first argument';
	exit();
}
