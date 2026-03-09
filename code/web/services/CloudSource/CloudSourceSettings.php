<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/CloudSource/CloudSourceSetting.php';

class CloudSource_CloudSourceSettings extends ObjectEditor {
	function getObjectType(): string {
		return 'CloudSourceSetting';
	}

	function getToolName(): string {
		return 'CloudSourceSettings';
	}

	function getModule(): string {
		return 'CloudSource';
	}

	function getPageTitle(): string {
		return 'CloudSource OA Settings';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new CloudSourceSetting();
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$this->applyFilters($object);
		$object->orderBy($this->getSort());
		$object->find();
		$objectList = [];
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getDefaultSort(): string {
		return 'name asc';
	}

	function getObjectStructure($context = ''): array {
		return CloudSourceSetting::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getAdditionalObjectActions(?DataObject $existingObject): array {
		return [];
	}

	function getInstructions(): string {
		return '';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#cloudsource', 'CloudSource OA');
		$breadcrumbs[] = new Breadcrumb('/CloudSource/CloudSourceSettings', 'CloudSource OA Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'cloudsource';
	}

	public function getViewPermissions() : array {
		return ['Administer CloudSource OA'];
	}

	public function getRequiredModule(): ?string {
		return 'CloudSource';
	}
}