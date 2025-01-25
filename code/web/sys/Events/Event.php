<?php
require_once ROOT_DIR . '/sys/DB/DataObject.php';
require_once ROOT_DIR . '/sys/Events/EventType.php';
require_once ROOT_DIR . '/sys/Events/EventTypeLibrary.php';
require_once ROOT_DIR . '/sys/Events/EventTypeLocation.php';
require_once ROOT_DIR . '/sys/Events/EventEventField.php';

class Event extends DataObject {
	public $__table = 'event';
	public $id;
	public $eventTypeId;
	public $locationId;
	public $sublocationId;
	public $title;
	public $description;
	public $cover;
	public $eventLength;
	public $visibility;
	public $startDate;
	public $startTime;
	public $endDate;
	public $_typeFields = [];




	public static function getObjectStructure($context = ''): array {
		$eventTypes = EventType::getEventTypeList();
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Libraries') || UserAccount::userHasPermission('Administer Home Library Locations'));
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'locationId' => [
				'property' => 'locationId',
				'type' => 'enum',
				'label' => 'Location',
				'description' => 'Location of the event',
				'values' => $locationList,
				'onchange' => 'return AspenDiscovery.Admin.getEventTypesForLocation(this.value);',
			],
			'eventTypeId' => [
				'property' => 'eventTypeId',
				'type' => 'enum',
				'label' => 'Event Type',
				'description' => 'The type of event',
				'values' => $eventTypes,
				'onchange' => "return AspenDiscovery.Admin.getEventTypeFields(this.value);"
			],
			'title' => [
				'property' => 'title',
				'type' => 'text',
				'label' => 'Title',
				'description' => 'The title for this event',
			],
			'infoSection' => [
				'property' => 'infoSection',
				'type' => 'section',
				'label' => 'Event Information',
				'expandByDefault' => true,
				'properties' => [
					'description' => [
						'property' => 'description',
						'type' => 'textarea',
						'label' => 'Description',
						'description' => 'The description for this event',
					],
					'cover' => [
						'property' => 'cover',
						'type' => 'image',
						'label' => 'Cover',
						'maxWidth' => 280,
						'maxHeight' => 280,
						'description' => 'The cover for this event',
						'hideInLists' => true,
					],
					'eventLength' => [
						'property' => 'eventLength',
						'type' => 'integer',
						'label' => 'Event Length (Hours)',
						'description' => 'How long this event lasts',
					],
					'fieldSetFieldSection' => [
						'property' => 'fieldSetFieldSection',
						'type' => 'section',
						'label' => 'Fields for this Event Type',
						'hideInLists' => true,
						'expandByDefault' => true,
						'properties' => [],
					],
					'private' => [
						'property' => 'private',
						'type' => 'checkbox',
						'label' => 'Private?',
						'default' => false,
						'description' => 'Private events are limited to those with permission to view private events',
					],
				],
			],
			'startDate' => [
				'property' => 'startDate',
				'type' => 'hidden',
			],
			'scheduleSection' => [
				'property' => 'scheduleSection',
				'type' => 'section',
				'label' => 'Event Scheduling',
				'expandByDefault' => true,
				'hiddenByDefault' => true,
				'properties' => [
					'startTime' => [
						'property' => 'startTime',
						'type' => 'hidden',
					],
					'endDate' => [
						'property' => 'endDate',
						'type' => 'hidden',
					],
//				'endTime' => [
//					'property' => 'endTime',
//					'type' => 'hidden',
//				],
					'recurrence' => [
						'property' => 'recurrence',
						'type' => 'hidden',
					]
				],
			]
		];
		// Add empty, hidden, readonly copies of all potential fields so that data can be added if they exist for any selected event type
		$eventFieldList = EventField::getEventFieldList();
		foreach ($eventFieldList as $fieldId => $field) {
			$structure['infoSection']['properties']['fieldSetFieldSection']['properties'][$fieldId] = [
				'property' => $fieldId,
				'label' => $field,
				'readOnly' => true,
				'type' => 'hidden',
			];
		}
		if ($context == 'addNew') {
			$structure['eventTypeId'] = [
				'property' => 'eventTypeId',
				'type' => 'enum',
				'label' => 'Event Type',
				'description' => 'The type of event',
				'placeholder' => 'Choose an event type',
				'values' => $eventTypes,
				'onchange' => "return AspenDiscovery.Admin.getEventTypeFields(this.value);",
				'hiddenByDefault' => true
			];
			$structure['eventTypeId']['hiddenByDefault'] = true;
			$structure['title']['hiddenByDefault'] = true;
			$structure['infoSection']['hiddenByDefault'] = true;
			$structure['infoSection']['properties']['description']['hiddenByDefault'] = true;
			$structure['infoSection']['properties']['cover']['hiddenByDefault'] = true;
			$structure['infoSection']['properties']['eventLength']['hiddenByDefault'] = true;
			$structure['infoSection']['properties']['fieldSetFieldSection']['hiddenByDefault'] = true;
			$structure['scheduleSection']['hiddenByDefault'] = true;
		} else {
			$structure['infoSection']['properties']['eventTypeId'] = [
				'property' => 'eventTypeId',
				'type' => 'enum',
				'label' => 'Event Type',
				'description' => 'The type of event',
				'values' => $eventTypes,
				'readOnly' => true,
			];
			$structure['startDate'] = [
				'property' => 'startDate',
				'type' => 'date',
				'label' => 'Start Date',
				'description' => 'The date this event starts',
				'onchange' => "return AspenDiscovery.Admin.updateRecurrenceOptions(this.value);"
			];
			$structure['scheduleSection']['properties'] = [
				'startTime' => [
					'property' => 'startTime',
					'type' => 'time',
					'label' => 'Start Time',
					'description' => 'The time this event starts',
				],
				'endDate' => [
					'property' => 'endDate',
					'type' => 'date',
					'label' => 'End Date',
					'description' => 'The date this event ends',
				],
//				'endTime' => [
//					'property' => 'endTime',
//					'type' => 'time',
//					'label' => 'End Time',
//					'description' => 'The time this event ends',
//				], // Fill this in based on event length
				'recurrence' => [
					'property' => 'recurrence',
					'type' => 'enum',
					'label' => 'Repeat Options',
					'description' => 'How this event repeats',
					'values' => [
						'1' => 'Does not repeat',
						'2' => 'Daily',
						'3' => 'Weekly on this day', // Update option descriptions based on the start date
						'4' => 'Monthly on the same weekday',
						'5' => 'Annually',
						'6' => 'Every weekday (Monday - Friday)',
						'7' => 'Custom',
					],
					'onchange' => "return AspenDiscovery.Admin.getRecurrenceForm(this.value);"
					]
			];
			$structure['infoSection']['expandByDefault'] = false;
			$structure['scheduleSection']['hiddenByDefault'] = false;
		}
		return $structure;
	}

	public function update($context = '') {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
			$this->saveFields();
		}
		return $ret;
	}

	public function insert($context = '') {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
			$this->saveFields();
		}
		return $ret;
	}

	public function __set($name, $value) {
		if ($name == 'libraries') {
			$this->setLibraries($value);
		} else if ($name == 'locations'){
			$this->setLocations($value);
		} else if (is_numeric($name)) {
			$this->setTypeField($name, $value);
		} else	{
			parent::__set($name, $value);
		}
	}

	public function __get($name) {
		if ($name == 'libraries') {
			return $this->getLibraries();
		} else if ($name == 'locations') {
			return $this->getLocations();
		} else if (is_numeric($name)){
			return $this->getTypeField($name);
		} else {
			return parent::__get($name);
		}
	}

	public function setLibraries($value) {
		$this->_libraries = $value;
	}

	public function setLocations($value) {
		$this->_locations = $value;
	}

	public function getLibraries() {
		if (!isset($this->_libraries) && $this->id) {
			$this->_libraries = [];
			$library = new EventTypeLibrary();
			$library->eventTypeId = $this->id;
			$library->find();
			while ($library->fetch()) {
				$this->_libraries[$library->libraryId] = clone($library);
			}
		}
		return $this->_libraries;
	}

	public function getLocations() {
		if (!isset($this->_locations) && $this->id) {
			$this->_locations = [];
			$location = new EventTypeLocation();
			$location->eventTypeId = $this->id;
			$location->find();
			while ($location->fetch()) {
				$this->_locations[$location->locationId] = clone($location);
			}
		}
		return $this->_locations;
	}

	public function saveLibraries() {
		if (isset($this->_libraries) && is_array($this->_libraries)) {
			$this->clearLibraries();

			foreach ($this->_libraries as $library) {
				$eventTypeLibrary = new EventTypeLibrary();
				$eventTypeLibrary->libraryId = $library;
				$eventTypeLibrary->eventTypeId = $this->id;
				$eventTypeLibrary->update();
			}
			unset($this->_libraries);
		}
	}

	public function saveLocations() {
		if (isset($this->_locations) && is_array($this->_locations)) {
			$this->clearLocations();

			foreach ($this->_locations as $location) {
				$eventTypeLocation = new EventTypeLocation();
				$eventTypeLocation->locationId = $location;
				$eventTypeLocation->eventTypeId = $this->id;
				$eventTypeLocation->update();
			}
			unset($this->_locations);
		}
	}


	private function clearLibraries() {
		//Unset existing library associations
		$eventTypeLibrary = new EventTypeLibrary();
		$eventTypeLibrary->eventTypeId = $this->id;
		$eventTypeLibrary->find();
		while ($eventTypeLibrary->fetch()){
			$eventTypeLibrary->delete(true);;
		}
	}

	private function clearLocations() {
		//Unset existing library associations
		$eventTypeLocation = new EventTypeLocation();
		$eventTypeLocation->eventTypeId = $this->id;
		$eventTypeLocation->find();
		while ($eventTypeLocation->fetch()){
			$eventTypeLocation->delete(true);
		}
	}

	public function setTypeField($fieldId, $value) {
		$this->_typeFields[$fieldId] = $value;
	}

	public function getTypeField($fieldId) {
		if (!isset($this->_typeFields[$fieldId]) && $this->id) {
			$this->_typeFields[$fieldId] = '';
			$field = new EventEventField();
			$field->eventId = $this->id;
			$field->eventFieldId = $fieldId;
			if ($field->find(true)) {
				$this->_typeFields[$fieldId] = $field->value;
			}
		}
		return $this->_typeFields[$fieldId] ?? '';
	}

	public function saveFields() {
		if (isset($this->_typeFields) && is_array($this->_typeFields)) {
			$this->clearFields();

			foreach ($this->_typeFields as $fieldId => $field) {
				$eventField = new EventEventField();
				$eventField->eventFieldId = $fieldId;
				$eventField->eventId = $this->id;
				if ($field == "on") { // Handle checkboxes
					$eventField->value = 1;
				} else {
					$eventField->value = $field;
				}
				$eventField->update();
			}
			unset($this->_typeFields);
		}
	}

	private function clearFields() {
		//Delete existing field associations
		$eventField = new EventEventField();
		$eventField->eventId = $this->id;
		$eventField->find();
		while ($eventField->fetch()){
			$eventField->delete(true);
		}
	}

	public function getEventType() {
		if (isset($this->eventTypeId)) {
			$eventType = new EventType();
			$eventType->id = $this->eventTypeId;
			if ($eventType->find(true)) {
				return $eventType;
			}
		}
	}

	public function updateStructureForEditingObject($structure) : array {
		if ($eventType = $this->getEventType()) {
			if (!empty($this->eventTypeId)) {
				if (empty($this->title)) {
					$this->title = $eventType->title;
				}
				if (!$eventType->titleCustomizable) {
					$this->title = $eventType->title;
					$structure['infoSection']['properties']['title']['readOnly'] = true;
				}
				if (empty($this->description)) {
					$this->description = $eventType->description;
				}
				if (!$eventType->descriptionCustomizable) {
					$this->description = $eventType->description;
					$structure['infoSection']['properties']['description']['readOnly'] = true;
				}
				if (empty($this->cover)) {
					$this->cover = $eventType->cover;
				}
				if (!$eventType->coverCustomizable) {
					$structure['infoSection']['properties']['cover']['readOnly'] = true;
					$this->cover = $eventType->cover;
				}
				if (empty($this->eventLength)) {
					$this->eventLength = $eventType->eventLength;
				}
				if (!$eventType->lengthCustomizable) {
					$structure['infoSection']['properties']['eventLength']['readOnly'] = true;
					$this->eventLength = $eventType->eventLength;
				}
				$structure['infoSection']['properties']['fieldSetFieldSection']['properties'] = $eventType->getFieldSetFields();
			}
		}
		return $structure;
	}
}
