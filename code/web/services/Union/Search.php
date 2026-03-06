<?php

require_once ROOT_DIR . '/ResultsAction.php';

/**
 * Union Results
 * Provides a way of unifying searching disparate sources either by
 * providing joined results between the sources or by including results from
 * a single source
 */
class Union_Search extends ResultsAction {
	/** @var Action */
	private $searchResultsAction;

	function launch() {

		$searchSource = $_REQUEST['searchSource'] ?? 'local';

		$searchSources = new SearchSources();
		$searches = $searchSources->getSearchSources();

		if (!isset($searches[$searchSource]) && $searchSource === 'marmot') {
			$searchSource = 'local';
		}

		$searchInfo = $searches[$searchSource] ?? [];

		// External redirect
		if (!empty($searchInfo['external'])) {
			$_SESSION['searchSource'] = 'local';

			$type = $_REQUEST['searchIndex'] ?? 'Keyword';
			$lookfor = $_REQUEST['lookfor'] ?? '';

			$link = $searchSources->getExternalLink($searchSource, $type, $lookfor);
			header("Location: $link");
			exit;
		}

		$routes = $this->getRoutes();

		// If we have a defined route
		if (isset($routes[$searchSource])) {
			$this->dispatch($routes[$searchSource]);
			return;
		}

		// Default search behavior
		$searchIndex = $_REQUEST['searchIndex'] ?? 'Keyword';

		if ($searchIndex === 'advanced' || $searchIndex === 'editAdvanced') {
			$this->dispatch([
				'file' => '/services/Search/Advanced.php',
				'module' => 'Search',
				'action' => 'Advanced',
				'class' => 'Search_Advanced',
			]);
		} else {
			$this->dispatch([
				'file' => '/services/Search/Results.php',
				'module' => 'Search',
				'action' => 'Results',
				'class' => 'Search_Results',
			]);
		}
	}

	private function getRoutes(): array {
		return [
			'genealogy' => [
				'file' => '/services/Genealogy/Results.php',
				'module' => 'Genealogy',
				'action' => 'Results',
				'class' => 'Genealogy_Results',
			],
			'open_archives' => [
				'file' => '/services/OpenArchives/Results.php',
				'module' => 'OpenArchives',
				'action' => 'Results',
				'class' => 'OpenArchives_Results',
			],
			'lists' => [
				'file' => '/services/Lists/Results.php',
				'module' => 'Lists',
				'action' => 'Results',
				'class' => 'Lists_Results',
			],
			'course_reserves' => [
				'file' => '/services/CourseReserves/Results.php',
				'module' => 'CourseReserves',
				'action' => 'Results',
				'class' => 'CourseReserves_Results',
			],
			'websites' => [
				'file' => '/services/Websites/Results.php',
				'module' => 'Websites',
				'action' => 'Results',
				'class' => 'Websites_Results',
			],
			'events' => [
				'file' => '/services/Events/Results.php',
				'module' => 'Events',
				'action' => 'Results',
				'class' => 'Events_Results',
			],
			'ebsco_eds' => [
				'file' => '/services/EBSCO/Results.php',
				'module' => 'EBSCO',
				'action' => 'Results',
				'class' => 'EBSCO_Results',
			],
			'ebscohost' => [
				'file' => '/services/EBSCOhost/Results.php',
				'module' => 'EBSCOhost',
				'action' => 'Results',
				'class' => 'EBSCOhost_Results',
			],
			'summon' => [
				'file' => '/services/Summon/Results.php',
				'module' => 'Summon',
				'action' => 'Results',
				'class' => 'Summon_Results',
			],
			'combined' => [
				'file' => '/services/Union/CombinedResults.php',
				'module' => 'Union',
				'action' => 'CombinedResults',
				'class' => 'Union_CombinedResults',
			],
			'talpa' => [
				'file' => '/services/Talpa/Results.php',
				'module' => 'Talpa',
				'action' => 'Results',
				'class' => 'Talpa_Results',
			],
			'series' => [
				'file' => '/services/Series/Results.php',
				'module' => 'Series',
				'action' => 'Results',
				'class' => 'Series_Results',
			],
		];
	}

	private function dispatch(array $route) {
		global $module, $action, $interface;

		require_once(ROOT_DIR . $route['file']);

		$module = $route['module'];
		$action = $route['action'];

		$interface->assign('module', $module);
		$interface->assign('action', $action);

		$class = $route['class'];
		$this->searchResultsAction = new $class();
		$this->searchResultsAction->launch();
	}

	function getBreadcrumbs(): array {
		return $this->searchResultsAction->getBreadcrumbs();
	}
}