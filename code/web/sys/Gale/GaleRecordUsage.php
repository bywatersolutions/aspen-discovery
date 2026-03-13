<?php /** @noinspection PhpMissingFieldTypeInspection */


class GaleRecordUsage extends DataObject {
	public $__table = 'gale_usage';
	public $id;
	public $instance;
	public $galeId;
	public $year;
	public $month;
	public $timesViewedInSearch;
	public $timesUsed;

	public function getUniquenessFields(): array {
		return [
			'instance',
			'galeId',
			'year',
			'month',
		];
	}

	public function okToExport(array $selectedFilters): bool {
		$okToExport = parent::okToExport($selectedFilters);
		if (in_array($this->instance, $selectedFilters['instances'])) {
			$okToExport = true;
		}
		return $okToExport;
	}
}