<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MyAccount_YearInReviewSummary extends MyAccount {
	function launch(): void {
		global $interface;
		$user = UserAccount::getLoggedInUser();

		if ($user) {
			if (isset($_REQUEST['reload'])){
				require_once ROOT_DIR . '/sys/YearInReview/YearInReviewGenerator.php';
				generateYearInReview($user);
			}
			if (!$user->hasYearInReview()) {
				//User shouldn't get here
				$module = 'Error';
				$action = 'Handle404';
				$interface->assign('module', 'Error');
				$interface->assign('action', 'Handle404');
				require_once ROOT_DIR . "/services/Error/Handle404.php";
				$actionClass = new Error_Handle404();
				$actionClass->launch();
				die();
			}

			$interface->assign('profile', $user);

			$slideNumber = $_REQUEST['slide'] ?? 1;
			if (is_numeric($slideNumber)) {
				$yearInReviewSettings = $user->getYearInReviewSetting();
				$result = $yearInReviewSettings->getSlide($user, (int)$slideNumber);
				$interface->assign('slides', $result['modalBody']);
				$interface->assign('slide_configuration', $result['slideConfiguration']);

				$slideNavigation = '';
				global $configArray;
				$url = $configArray['Site']['url'] . '/MyAccount/YearInReviewSummary';
				if ($slideNumber > 1) {
					$slideNavigation .= '<a class="btn btn-default" href="' . $url . '?slide=' . $slideNumber - 1 . '">' . translate([
							'text' => 'Previous',
							'isPublicFacing' => true,
							'inAttribute' => true,
						]) . '</a>';
				}
				if ($slideNumber < $result['numSlidesToShow']) {
					$slideNavigation .= '<a class="btn btn-primary" href="' . $url . '?slide=' . $slideNumber + 1 . '">' . translate([
							'text' => 'Next',
							'isPublicFacing' => true,
							'inAttribute' => true,
						]) . '</a>';
				}

				$interface->assign('slide_navigation', $slideNavigation);

				$interface->assign('yearInReviewSummary', $user->getYearInReviewResultData()->userData);
				if (!empty($user->getYearInReviewResultData()->userData->recommendationIds)) {
					$recommendations = [];
					foreach ($user->getYearInReviewResultData()->userData->recommendationIds as $id) {
						require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
						$record = new GroupedWorkDriver($id);
						$interface->assign('recordDriver', $record);
						$recommendations[] = $interface->fetch($record->getSearchResult());
					}
					$interface->assign('recommendations', $recommendations);
				}
				$summaryTable = $interface->fetch('MyAccount/' . $yearInReviewSettings->year . '_yearInReviewSummaryData.tpl');
				$interface->assign('summaryTable', $summaryTable);

			} else {
				$module = 'Error';
				$action = 'Handle404';
				$interface->assign('module', 'Error');
				$interface->assign('action', 'Handle404');
				require_once ROOT_DIR . "/services/Error/Handle404.php";
				$actionClass = new Error_Handle404();
				$actionClass->launch();
				die();
			}
		}


		$this->display('yearInReviewSummary.tpl', 'Year In Review');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'Year In Review');
		return $breadcrumbs;
	}
}
