<?php


class EventsIndexingLogEntry extends BaseLogEntry {
	public $__table = 'events_indexing_log';
	public $id;
	public $startTime;
	public $endTime;
	public $lastUpdate;
	public $name;
	public $notes;
	public $numEvents;
	public $numErrors;
	public $numAdded;
	public $numDeleted;
	public $numUpdated;
}