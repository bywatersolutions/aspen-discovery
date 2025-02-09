<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Events/LibraryEventsSetting.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Events/Event.php';

class Events_Events extends ObjectEditor {
	function getObjectType(): string {
		return 'Event';
	}

	function getModule(): string {
		return 'Events';
	}

	function getToolName(): string {
		return 'Events';
	}

	function getPageTitle(): string {
		return 'Events';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new Event();
		$object->deleted = 0;
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$list = [];
		$object->find();
		while ($object->fetch()) {
			$list[$object->id] = clone $object;
		}
		return $list;
	}

	function getDefaultSort(): string {
		return 'startDate asc';
	}

	function getObjectStructure($context = ''): array {
		return Event::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getInstructions(): string {
		return 'https://help.aspendiscovery.org/help/catalog/events';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#events', 'Events');
		$breadcrumbs[] = new Breadcrumb('/Events/Events', 'Events');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'events';
	}

	function canView(): bool {
		if (SystemVariables::getSystemVariables()->enableAspenEvents) {
			return UserAccount::userHasPermission(['Administer Events for All Locations']);
		}
		return false;
	}

	function canBatchEdit(): bool {
		return UserAccount::userHasPermission(['Administer Events for All Locations']);
	}

	public function hasMultiStepAddNew() : bool {
		return true;
	}
}