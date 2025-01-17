<?php
require_once ROOT_DIR . '/sys/DB/DataObject.php';
require_once ROOT_DIR . '/sys/Events/EventField.php';
require_once ROOT_DIR . '/sys/Events/EventFieldSetField.php';

class EventFieldSet extends DataObject {
	public $__table = 'event_field_set';
	public $id;
	public $name;
	private $_eventFields;

	public static function getObjectStructure($context = ''): array {
		$eventFields = EventField::getEventFieldList();
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'A name for the field',
			],
			'eventFields' => [
				'property' => 'eventFields',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Event Fields',
				'description' => 'The event fields that make up the set',
				'values' => $eventFields,
				'canAddNew' => true,
			]
		];
		return $structure;
	}

	public function update($context = '') {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveFields();
		}
		return $ret;
	}

	public function insert($context = '') {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveFields();
		}
		return $ret;
	}

	public function __set($name, $value) {
		if ($name == 'eventFields') {
			$this->setEventFields($value);
		} else {
			parent::__set($name, $value);
		}
	}

	public function __get($name) {
		if ($name == 'eventFields') {
			return $this->getEventFields();
		} else {
			return parent::__get($name);
		}
	}

	public function setEventFields($value) {
		$this->_eventFields = $value;
	}

	public function getEventFields() {
		if (!isset($this->_eventFields) && $this->id) {
			$this->_eventFields = [];
			$field = new EventFieldSetField();
			$field->eventFieldSetId = $this->id;
			$field->find();
			while ($field->fetch()) {
				$this->_eventFields[$field->eventFieldId] = clone($field);
			}
		}
		return $this->_eventFields;
	}

	public function saveFields() {
		if (isset($this->_eventFields) && is_array($this->_eventFields)) {
			$this->clearFields();

			foreach ($this->_eventFields as $eventField) {
				$fieldSetFields = new EventFieldSetField();
				$fieldSetFields->eventFieldId = $eventField;
				$fieldSetFields->eventFieldSetId = $this->id;
				$fieldSetFields->update();
			}
			unset($this->_eventFields);
		}
	}

	private function clearFields() {
		//Delete existing field/field set associations
		$fieldSetFields = new EventFieldSetField();
		$fieldSetFields->eventFieldSetId = $this->id;
		$fieldSetFields->find();
		while ($fieldSetFields->fetch()){
			$fieldSetFields->eventFieldSetId = "0";
			$fieldSetFields->update();
		}
	}
}
