<?php
/** @noinspection PhpMissingFieldTypeInspection */

class ReadingHistoryEntry extends DataObject {
	public $__table = 'user_reading_history_work';
	public $id;
	public $userId;
	public $groupedWorkPermanentId;
	public $source;
	public $sourceId;
	public $barcode;
	public $callNumber;
	public $volume;
	public $title;
	public $author;
	public $format;
	public $ilsStatus;
	public $checkOutDate;
	public $checkInDate;
	public $editedCheckInDate;
	public $deleted;
	public $isIll;
	public $isManuallyAdded;
	public $costSavings;

	function getNumericColumnNames() : array {
		return ['userId', 'checkOutDate', 'checkInDate', 'editedCheckInDate', 'deleted', 'isIll', 'costSavings', 'isManuallyAdded'];
	}

	function objectHistoryEnabled() : bool {
		return false;
	}

	public function getUniquenessFields(): array {
		return [
			'userId',
			'groupedWorkPermanentId',
			'source',
			'sourceId',
			'isIll',
		];
	}

	public function okToExport(array $selectedFilters): bool {
		$okToExport = parent::okToExport($selectedFilters);
		$user = new User();
		$user->id = $this->userId;
		if ($user->find(true)) {
			if ($user->homeLocationId == 0 || in_array($user->homeLocationId, $selectedFilters['locations'])) {
				$okToExport = true;
			}
		}
		return $okToExport;
	}

	public function toArray($includeRuntimeProperties = true, $encryptFields = false): array {
		$return = parent::toArray($includeRuntimeProperties, $encryptFields);
		unset($return['userId']);
		return $return;
	}

	public function getLinksForJSON(): array {
		$links = parent::getLinksForJSON();
		$user = new User();
		$user->id = $this->userId;
		if ($user->find(true)) {
			$links['user'] = $user->ils_barcode;
		}
		return $links;
	}

	public function loadFromJSON($jsonData, $mappings, string $overrideExisting = 'keepExisting'): bool {
		if (array_key_exists($jsonData['sourceId'], $mappings['bibs'])) {
			$jsonData['sourceId'] = $mappings['bibs'][$this->sourceId];
		}
		return parent::loadFromJSON($jsonData, $mappings, $overrideExisting);
	}

	public function loadEmbeddedLinksFromJSON($jsonData, $mappings, string $overrideExisting = 'keepExisting') : void {
		parent::loadEmbeddedLinksFromJSON($jsonData, $mappings, $overrideExisting);
		if (isset($jsonData['user'])) {
			$username = $jsonData['user'];
			$user = new User();
			$user->ils_barcode = $username;
			if ($user->find(true)) {
				$this->userId = $user->id;
			}
		}
	}

	public function insert($context = false): bool|int {
		$existingEntry = new ReadingHistoryEntry();
		$existingEntry->userId = $this->userId;
		$existingEntry->source = $this->source;
		$existingEntry->sourceId = $this->sourceId;
		$existingEntry->barcode = $this->barcode;
		$existingEntry->format = $this->format;
		$existingEntry->checkOutDate = $this->checkOutDate;
		$existingEntry->deleted = 0;
		if ($existingEntry->find(true)) {
			global $logger;
			$logger->log("Skipping duplicate reading history entry for userId=$this->userId, source=$this->source, sourceId=$this->sourceId, checkOutDate=$this->checkOutDate", Logger::LOG_DEBUG);
			return false;
		}

		return parent::insert($context);
	}
}
