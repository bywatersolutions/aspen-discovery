<?php

require_once ROOT_DIR . '/sys/Recommend/Interface.php';

/**
 * SideFacets Recommendations Module
 *
 * This class provides recommendations displaying facets beside search results
 */
class SideFacets implements RecommendationInterface {
	/** @var  SearchObject_SolrSearcher $searchObject */
	private SearchObject_BaseSearcher $searchObject;
	private array $facetSettings;
	private array $mainFacets;

	/* Constructor
	 *
	 * Establishes base settings for making recommendations.
	 *
	 * @access  public
	 * @param   SearchObject_BaseSearcher  $searchObject   The SearchObject requesting recommendations.
	 * @param   string  $params         Additional settings from the searches.ini.
	 */
	public function __construct(SearchObject_BaseSearcher $searchObject, $params) {
		// Save the passed-in SearchObject:
		$this->searchObject = $searchObject;

		$this->facetSettings = $searchObject->getFacetConfig();
		$this->mainFacets = [];
		if (!empty($this->facetSettings)) {
			foreach ($this->facetSettings as $facetName => $facet) {
				if (!$facet->showAboveResults) {
					$this->mainFacets[$facetName] = $facet->displayName;
				}
			}
		}
	}

	/* init
	 *
	 * Called before the SearchObject performs its main search.  This may be used
	 * to set SearchObject parameters in order to generate recommendations as part
	 * of the search.
	 *
	 * @access  public
	 */
	public function init(): void {}

	/** getEventSettings
	 * 
	 * Helper used for getting appropriate event settings given facet type
	 * 
	 * @access private
	 */
	private function getEventSettings(?LibraryEventsFacetSetting $facetSettings) : DataObject {
		global $library;
		$eventSettings = new LibraryEventsSetting();
		$eventSettings->libraryId = $library->libraryId;
		
		// Load eventSettings
		$eventSettings->find(true);
		switch ($eventSettings->settingSource) {
			case 'communico':
				require_once ROOT_DIR . '/sys/Events/CommunicoSetting.php';
				$eventSettings = new CommunicoSetting;
				break;
			case 'springshare':
				require_once ROOT_DIR . '/sys/Events/SpringshareLibCalSetting.php';
				$eventSettings = new SpringshareLibCalSetting;
				break;
			case 'assabet':
				require_once ROOT_DIR . '/sys/Events/AssabetSetting.php';
				$eventSettings = new AssabetSetting;
				break;
			default:
				require_once ROOT_DIR . '/sys/Events/LMLibraryCalendarSetting.php';
				$eventSettings = new LMLibraryCalendarSetting;
				break;
		}
		
		$eventSettings->id = $facetSettings->settingId;
		
		return $eventSettings;
	}

	/* process
	 *
	 * Called after the SearchObject has performed its main search.  This may be
	 * used to extract necessary information from the SearchObject or to perform
	 * completely unrelated processing.
	 *
	 * @access  public
	 */
	public function process(): void {
		global $interface;
		global $library;

		$interface->assign('hasSearchableFacets', $this->searchObject->hasSearchableFacets());
		$interface->assign('facetFormQueryParams', $_GET);
	
		// Get applied facets
		$filterList = $this->getFilterList();
		$interface->assign('filterList', $filterList);
	
		// Get and prepare side facets
		$sideFacets = $this->searchObject->getFacetList($this->mainFacets);
		$sideFacets = $this->initializeSideFacets($sideFacets);
	
		// Get locked facets
		$lockedFacets = $this->getLockedFacets();
	
		// Process facet counts
		$this->processFacetCounts();
	
		// Process facets based on search object type
		$sideFacets = $this->processFacetsBySearchType($sideFacets, $lockedFacets);
	
		$interface->assign('sideFacetSet', $sideFacets);
		$interface->assign('searchId', $this->searchObject->getSearchId());
	}

	private function getFilterList(): array {
		$filterList = $this->searchObject->getFilterList();

		$isNotTopFacet = fn($facet) => strpos($facet[0]['field'], 'availability_toggle') !== 0;
		
		return array_filter(
			$filterList,
			$isNotTopFacet
		);
	}
	
	private function initializeSideFacets(array $sideFacets): array {	
		$orderedSideFacets = [];
		foreach ($this->facetSettings as $facetKey => $facetSetting) {
			if ($facetSetting->showAboveResults) {
				continue;
			}
	
			if (isset($sideFacets[$facetKey])) {
				$orderedSideFacets[$facetKey] = $sideFacets[$facetKey];
				$orderedSideFacets[$facetKey]['loadedValues'] = true;
				if (!isset($orderedSideFacets[$facetKey]['field'])) {
					$orderedSideFacets[$facetKey]['field'] = $facetKey;
				}
			} else {
				$orderedSideFacets[$facetKey] = $this->createPlaceholderFacet($facetKey, $facetSetting);
			}
		}

		// Preserve any facets not present in configured settings by appending them at the end.
		foreach ($sideFacets as $facetKey => $facet) {
			if (!isset($orderedSideFacets[$facetKey])) {
				$orderedSideFacets[$facetKey] = $facet;
			}
		}

		return $orderedSideFacets;
	}
	
	private function createPlaceholderFacet(string $facetKey, $facetSetting): array {
		return [
			'field' => $facetKey,
			'field_name' => $facetKey,
			'label' => $facetSetting->displayName,
			'displayNamePlural' => $facetSetting->displayNamePlural,
			'list' => [],
			'hasApplied' => false,
			'loadedValues' => false,
			'multiSelect' => $facetSetting->multiSelect,
		];
	}

	private function getLockedFacets(): array {
		$lockSection = $this->searchObject->getSearchName();
		
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getActiveUserObj();
			$lockedFacets = !empty($user->lockedFacets) ? json_decode($user->lockedFacets, true) : [];
		} else {
			$lockedFacets = isset($_SESSION['lockedFilters']) ? $_SESSION['lockedFilters'] : [];
		}
		
		return $lockedFacets[$lockSection] ?? [];
	}
	
	private function processFacetCounts(): void {
		global $interface;
		global $library;
		
		$searchSource = $_REQUEST['searchSource'] ?? '';
		
		match ($searchSource) {
			'events' => $this->processFacetCountsForEvents($interface, $library),
			default => $this->processFacetCountsForDefault($interface, $library),
		};
	}
	
	private function processFacetCountsForEvents(object $interface, object $library): void {
		$facetSettings = $library->getEventFacetSettings();
		if ($facetSettings) {
			$interface->assign('facetCountsToShow', $facetSettings->getFacetGroup()->eventFacetCountsToShow);
	
			$eventSettings = $this->getEventSettings($facetSettings);
			if ($eventSettings->find(true)) {
				$interface->assign('maxEventDate', strtotime("+" . $eventSettings->numberOfDaysToIndex . " days"));
			}
		}
	}
	
	private function processFacetCountsForDefault(object $interface, object $library): void {
		global $library;
		$facetCountsToShow = $library->getGroupedWorkDisplaySettings()->facetCountsToShow;
		$interface->assign('facetCountsToShow', $facetCountsToShow);
	}

	private function processFacetsBySearchType(array $sideFacets, array $lockedFacets): array {
		return match (true) {
			$this->searchObject instanceof SearchObject_AbstractGroupedWorkSearcher 
				=> $this->processFacetsForGroupedWork($sideFacets, $lockedFacets),
			
			$this->searchObject instanceof SearchObject_EventsSearcher 
				=> $this->processFacetsForEvents($sideFacets, $lockedFacets),
			
			$this->searchObject instanceof SearchObject_ListsSearcher 
				=> $this->processFacetsForLists($sideFacets, $lockedFacets),
			
			default 
				=> $this->processFacetsForDefault($sideFacets, $lockedFacets),
		};
	}

	private function processFacetsForGroupedWork(array $sideFacets, array $lockedFacets): array {
		foreach ($sideFacets as $facetKey => $facet) {
			$facetSetting = $this->facetSettings[$facetKey];
	
			$sideFacets[$facetKey] = match (true) {
				preg_match('/time_since_added/i', $facetKey) 
					=> $this->updateTimeSinceAddedFacet($facet),
				
				$facetKey === 'rating_facet' 
					=> $this->updateUserRatingsFacet($facet),
				
				default 
					=> $this->applyFacetSettings($facetKey, $sideFacets, $facetSetting, $lockedFacets)[$facetKey] ?? $facet,
			};
	
			$this->applyCommonFacetSettings($sideFacets, $facetKey, $facetSetting, $lockedFacets);
		}
		
		return $sideFacets;
	}
	
	private function processFacetsForEvents(array $sideFacets, array $lockedFacets): array {
		foreach ($sideFacets as $facetKey => $facet) {
			$facetSetting = $this->facetSettings[$facetKey];
	
			if ($facetKey === 'start_date') {
				$startDateFacet = $this->updateStartDateRatingsFacet($facet);
				$sideFacets[$facetKey] = $startDateFacet;
				$sideFacets[$facetKey]['hasApplied'] = isset($startDateFacet['start']) || isset($startDateFacet['end']);
			} else {
				$sideFacets = $this->applyFacetSettings($facetKey, $sideFacets, $facetSetting, $lockedFacets);
			}
	
			$this->applyCommonFacetSettings($sideFacets, $facetKey, $facetSetting, $lockedFacets);
		}
		
		return $sideFacets;
	}
	
	private function processFacetsForLists(array $sideFacets, array $lockedFacets): array {
		foreach ($sideFacets as $facetKey => $facet) {
			if (preg_match('/local_time_since_(added|updated)/i', $facetKey)) {
				$sideFacets[$facetKey] = $this->updateTimeSinceAddedFacet($facet);
			}
		}
		
		return $sideFacets;
	}
	
	private function processFacetsForDefault(array $sideFacets, array $lockedFacets): array {
		foreach ($sideFacets as $facetKey => $facet) {
			$facetSetting = $this->facetSettings[$facetKey];
			$sideFacets = $this->applyFacetSettings($facetKey, $sideFacets, $facetSetting, $lockedFacets);
		}
		
		return $sideFacets;
	}
	
	private function applyCommonFacetSettings(array &$sideFacets, string $facetKey, FacetSetting $facetSetting, array $lockedFacets): void {
		$sideFacets[$facetKey]['collapseByDefault'] = $facetSetting->collapseByDefault;
		$sideFacets[$facetKey]['locked'] = array_key_exists($facetKey, $lockedFacets);
		$sideFacets[$facetKey]['canLock'] = $facetSetting->canLock;
	}
	
	

	public function updateTimeSinceAddedFacet(array $timeSinceAddedFacet): array {
		//See if there is a value selected
		$valueSelected = false;
		foreach ($timeSinceAddedFacet['list'] as $facetValue) {
			if (isset($facetValue['isApplied']) && $facetValue['isApplied']) {
				$valueSelected = true;
			}
		}
		if ($valueSelected) {
			//Get rid of all values except the selected value which will allow the value to be removed
			//We remove the other values because it is confusing to have results both longer and shorter than the current value.
			foreach ($timeSinceAddedFacet['list'] as $facetKey => $facetValue) {
				if (!isset($facetValue['isApplied']) || !$facetValue['isApplied']) {
					unset($timeSinceAddedFacet['list'][$facetKey]);
				}
			}
		} else {
			//Make sure to show all values
			$timeSinceAddedFacet['valuesToShow'] = count($timeSinceAddedFacet['list']);
			//We would like to show, On Order, time period values, and then under consideration
//			$onOrderOption = array_key_exists('On Order', $timeSinceAddedFacet['list']) ? $timeSinceAddedFacet['list']['On Order'] : null;
//			$underConsiderationOption = array_key_exists('Under Consideration', $timeSinceAddedFacet['list']) ? $timeSinceAddedFacet['list']['Under Consideration'] : null;
//			if ($onOrderOption != null) {
//				unset($timeSinceAddedFacet['list']['On Order']);
//			}
//			if ($underConsiderationOption != null) {
//				unset($timeSinceAddedFacet['list']['Under Consideration']);
//			}
			$sortOrder = [
				'On Order' => null,
				'In Processing' => null,
				'Day' => null,
				'Week' => null,
				'Month' => null,
				'2 Months' => null,
				'Quarter' => null,
				'Six Months' => null,
				'Year' => null,
				'Under Consideration' => null,
			];
			$sortedOptions = array_merge($sortOrder, $timeSinceAddedFacet['list']);
//			if ($onOrderOption != null) {
//				$sortedOptions = ['On Order' => $onOrderOption] + $sortedOptions;
//			}
//			if ($underConsiderationOption != null) {
//				$sortedOptions = $sortedOptions + ['Under Consideration' => $underConsiderationOption];
//			}
			foreach ($sortedOptions as $key => $value) {
				if (is_null($value)) {
					unset($sortedOptions[$key]);
				}
			}
			//Reverse the display of the list so Day is first and year is last
			$timeSinceAddedFacet['list'] = $sortedOptions;
		}
		return $timeSinceAddedFacet;
	}

	public function updateUserRatingsFacet(array $userRatingFacet): array {
		global $interface;
		$ratingApplied = false;
		$ratingLabels = [];
		foreach ($userRatingFacet['list'] as $facetValue) {
			if ($facetValue['isApplied']) {
				$ratingApplied = true;
				$ratingLabels = [$facetValue['value']];
			}
		}
		if (!$ratingApplied) {
			$ratingLabels = [
				'fiveStar',
				'fourStar',
				'threeStar',
				'twoStar',
				'oneStar',
				'Unrated',
			];
		}
		$interface->assign('ratingLabels', $ratingLabels);
		return $userRatingFacet;
	}

	private function updateStartDateRatingsFacet(array $startDateFacet): array {
		if (!isset($_REQUEST['filter'])) {
			return $startDateFacet;
		}
		$filters = $_REQUEST['filter'];
		if (!empty($filters) && is_array($filters)) {
			foreach ($filters as $filter) {
				if (str_starts_with($filter, 'start_date')) {
					$filterValue = substr($filter, strpos($filter, '[') + 1);
					$filterValue = substr($filterValue, 0, -2);
					$range = explode(' TO ', $filterValue);
					$utcTimeZone = new DateTimeZone('UTC');
					$defaultTimezone = new DateTimeZone(date_default_timezone_get());
					if ($range[0] != '*') {
						$dt = new DateTime($range[0], $utcTimeZone);
						$dt->setTimezone($defaultTimezone);
						$startDateFacet['start'] = $dt->format("Y-m-d");
					}
					if ($range[1] != '*') {
						$dt = new DateTime($range[1], $utcTimeZone);
						$dt->setTimezone($defaultTimezone);
						$startDateFacet['end'] = $dt->format("Y-m-d");
					}
					break;
				}
			}
		}
		return $startDateFacet;
	}

	/* getTemplate
	 *
	 * This method provides a template name so that recommendations can be displayed
	 * to the end user.  It is the responsibility of the process() method to
	 * populate all necessary template variables.
	 *
	 * @access  public
	 * @return  string      The template to use to display the recommendations.
	 */
	public function getTemplate(): string {
		return 'Search/Recommend/SideFacets.tpl';
	}

	/**
	 * @param $facetKey
	 * @param array $sideFacets
	 * @param FacetSetting $facetSetting
	 * @return array
	 */
	private function applyFacetSettings(string $facetKey, array $sideFacets, FacetSetting $facetSetting, array $lockedFacets): array {
		//Do additional handling of the display
		if ($facetSetting->sortMode == 'alphabetically') {
			asort($sideFacets[$facetKey]['list']);
		}
		$lockedValues = $lockedFacets[$facetKey] ?? [];
		if (!empty($sideFacets[$facetKey]['list'])) {
			$sideFacets[$facetKey]['list'] = $this->reorderFacetValues($sideFacets[$facetKey]['list'], $lockedValues);
		}
		if ($facetSetting->numEntriesToShowByDefault > 0) {
			$sideFacets[$facetKey]['valuesToShow'] = $facetSetting->numEntriesToShowByDefault;
		}
		if ($facetSetting->showAsDropDown) {
			$sideFacets[$facetKey]['showAsDropDown'] = $facetSetting->showAsDropDown;
		}
		if ($facetSetting->multiSelect) {
			$sideFacets[$facetKey]['multiSelect'] = $facetSetting->multiSelect;
		}
		if ($facetSetting->useMoreFacetPopup && count($sideFacets[$facetKey]['list']) > 12) {
			$sideFacets[$facetKey]['showMoreFacetPopup'] = true;
			$facetsList = $sideFacets[$facetKey]['list'];
			if ($facetSetting->multiSelect) {
				$sideFacets[$facetKey]['list'] = array_slice($facetsList, 0, $facetSetting->numEntriesToShowByDefault);
				$sideFacets[$facetKey]['fullUnsortedList'] = $facetsList;
			} else {
				$sideFacets[$facetKey]['list'] = array_slice($facetsList, 0, $facetSetting->numEntriesToShowByDefault);
				$sideFacets[$facetKey]['fullUnsortedList'] = $facetsList;
			}

			$sortedList = [];
			foreach ($facetsList as $key => $value) {
				$sortedList[strtolower($key) . $key] = $value;
			}
			ksort($sortedList);
			$sideFacets[$facetKey]['sortedList'] = $sortedList;
		} else {
			$sideFacets[$facetKey]['showMoreFacetPopup'] = false;
		}
		$sideFacets[$facetKey]['collapseByDefault'] = $facetSetting->collapseByDefault;

		$sideFacets[$facetKey]['locked'] = array_key_exists($facetKey, $lockedFacets);
		$sideFacets[$facetKey]['canLock'] = $facetSetting->canLock;
		$sideFacets[$facetKey]['displayNamePlural'] = empty($facetSetting->displayNamePlural) ? $facetSetting->displayName : $facetSetting->displayNamePlural;
		return $sideFacets;
	}

	/**
	 * Reorder the facet values to alwasy show applied values, then the other values
	 * @param array $facetList
	 * @param array $lockedValues
	 * @return array
	 */
	private function reorderFacetValues(array $facetList, array $lockedValues): array {
		if (empty($facetList)) {
			return $facetList;
		}
		$applied = [];
		$other = [];
		foreach ($facetList as $key => $value) {
			if (!empty($lockedValues) && isset($value['value']) && in_array($value['value'], $lockedValues, true)) {
				$value['isLocked'] = true;
			}
			if (!empty($value['isApplied'])) {
				$applied[$key] = $value;
			} else {
				$other[$key] = $value;
			}
		}
		return $applied + $other;

	}
}
