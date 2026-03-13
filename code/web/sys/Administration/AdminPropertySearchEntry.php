<?php /** @noinspection PhpMissingFieldTypeInspection */

class AdminPropertySearchEntry extends DataObject {
	public $__table = 'admin_property_search_entries';
	public $id;
	public $module;
	public $action;
	public $toolTitle;
	public $section;
	public $propertyName;
	public $label;
	public $keywords;
	public $requiredModule;
	public $requiredPermissions;
}