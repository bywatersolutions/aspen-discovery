<?php

require_once ROOT_DIR . '/services/Admin/IndexingLog.php';
require_once ROOT_DIR . '/sys/Events/EventsIndexingLogEntry.php';

class Events_IndexingLog extends Admin_IndexingLog {
	function getIndexLogEntryObject(): BaseLogEntry {
		return new EventsIndexingLogEntry();
	}

	function getTemplateName(): string {
		return 'eventsIndexLog.tpl';
	}

	function getTitle(): string {
		return 'Events Indexing Log';
	}

	function getModule(): string {
		return 'Events';
	}

	function applyMinProcessedFilter(DataObject $indexingObject, $minProcessed): void {
		if ($indexingObject instanceof EventsIndexingLogEntry) {
			$indexingObject->whereAdd('(numAdded + numDeleted + numUpdated) >= ' . intval($minProcessed));
		}
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#events', 'Events');
		$breadcrumbs[] = new Breadcrumb('', 'Indexing Log');
		return $breadcrumbs;
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'View System Reports',
			'View Indexing Logs',
		]);
	}

	function getActiveAdminSection(): string {
		return 'events';
	}
}