<?php
require_once ROOT_DIR . '/sys/DB/DataObject.php';
require_once ROOT_DIR . '/sys/Events/Event.php';

class EventInstance extends DataObject {
	public $__table = 'event_instance';
	public $id;
	public $eventId;
	public $date;
	public $time;
	public $length;
	public $status;
	public $note;

	public static function getObjectStructure($context = ''): array {
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'eventId' => [
				'property' => 'eventId',
				'type' => 'text',
				'label' => 'Event Name',
				'description' => 'A name for the field',
				'hiddenByDefault' => true,
				'hideInLists' => true,
			],
			'date' => [
				'property' => 'date',
				'type' => 'date',
				'label' => 'Event Date',
				'description' => 'The event date',
			],
			'time' => [
				'property' => 'time',
				'type' => 'time',
				'label' => 'Event Time',
				'description' => 'The event Time',
			],
			'length' => [
				'property' => 'length',
				'type' => 'integer',
				'label' => 'Length (Hours)',
				'description' => 'The event length in hours',
			],
			'note' => [
				'property' => 'note',
				'type' => 'text',
				'label' => 'Note',
				'description' => 'A note for this specific instance',
			],
			'status' => [
				'property' => 'status',
				'type' => 'checkbox',
				'label' => 'Active',
				'default' => 1,
				'description' => 'Whether the event is active or cancelled',
			]
		];
		return $structure;
	}

//	public function update($context = '') {
//		$ret = parent::update();
//		if ($ret !== FALSE) {
//			$this->saveFields();
//		}
//		return $ret;
//	}
//
//	public function insert($context = '') {
//		$ret = parent::insert();
//		if ($ret !== FALSE) {
//			$this->saveFields();
//		}
//		return $ret;
//	}
//
//	public function __set($name, $value) {
//		if ($name == 'eventFields') {
//			$this->setEventFields($value);
//		} else {
//			parent::__set($name, $value);
//		}
//	}
//
//	public function __get($name) {
//		if ($name == 'eventFields') {
//			return $this->getEventFields();
//		} else {
//			return parent::__get($name);
//		}
//	}
//
//	public function setEventFields($value) {
//		$this->_eventFields = $value;
//	}
//
//	public function getEventFields() {
//		if (!isset($this->_eventFields) && $this->id) {
//			$this->_eventFields = [];
//			$field = new EventFieldSetField();
//			$field->eventFieldSetId = $this->id;
//			$field->find();
//			while ($field->fetch()) {
//				$this->_eventFields[$field->eventFieldId] = clone($field);
//			}
//		}
//		return $this->_eventFields;
//	}
//
//	public function saveFields() {
//		if (isset($this->_eventFields) && is_array($this->_eventFields)) {
//			$this->clearFields();
//
//			foreach ($this->_eventFields as $eventField) {
//				$fieldSetFields = new EventFieldSetField();
//				$fieldSetFields->eventFieldId = $eventField;
//				$fieldSetFields->eventFieldSetId = $this->id;
//				$fieldSetFields->update();
//			}
//			unset($this->_eventFields);
//		}
//	}
//
//	private function clearFields() {
//		//Delete existing field/field set associations
//		$fieldSetFields = new EventFieldSetField();
//		$fieldSetFields->eventFieldSetId = $this->id;
//		$fieldSetFields->find();
//		while ($fieldSetFields->fetch()){
//			$fieldSetFields->delete(true);;
//		}
//	}
//
//	public static function getEventFieldSetList(): array {
//		$setList = [];
//		$object = new EventFieldSet();
//		$object->orderBy('name');
//		$object->find();
//		while ($object->fetch()) {
//			$label = $object->name;
//			$setList[$object->id] = $label;
//		}
//		return $setList;
//	}
}