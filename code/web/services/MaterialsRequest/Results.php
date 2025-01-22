<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequest.php';
require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestStatus.php';

/**
 * MaterialsRequest Submission processing, processes a new request for the user and
 * displays a success/fail message to the user.
 */
class MaterialsRequest_Results extends Action {

	function launch() : void {
		global $interface;
		global $library;

		$maxActiveRequests = $library->maxActiveRequests;
		$maxRequestsPerYear = $library->maxRequestsPerYear;
		$accountPageLink = '/MaterialsRequest/MyRequests';
		$interface->assign('accountPageLink', $accountPageLink);
		$interface->assign('maxActiveRequests', $maxActiveRequests);
		$interface->assign('maxRequestsPerYear', $maxRequestsPerYear);

		if($_REQUEST['success'] && $_REQUEST['id']) {
			$materialsRequest = new MaterialsRequest();
			$materialsRequest->id = $_REQUEST['id'];
			if($materialsRequest->find(true)) {
				$materialsRequestCounts = new MaterialsRequest();
				$materialsRequestCounts->createdBy = UserAccount::getActiveUserId();
				$statusQuery = new MaterialsRequestStatus();
				$homeLibrary = Library::getPatronHomeLibrary();
				if (is_null($homeLibrary)) {
					$homeLibrary = $library;
				}
				$statusQuery->libraryId = $homeLibrary->libraryId;
				$statusQuery->isActive = 1;
				$materialsRequestCounts->joinAdd($statusQuery, 'INNER', 'status', 'status', 'id');
				$openRequests = $materialsRequestCounts->count();

				$materialsRequestCounts = new MaterialsRequest();
				$materialsRequestCounts->createdBy = UserAccount::getActiveUserId();
				if ($homeLibrary->yearlyRequestLimitType == 0) {
					$materialsRequestCounts->whereAdd('dateCreated >= unix_timestamp(now() - interval 1 year)');
				}else{
					$currentYear = date('Y');
					$januaryOne = strtotime("01-01-$currentYear");
					$materialsRequestCounts->whereAdd("dateCreated >= $januaryOne");
				}
				$statusQuery = new MaterialsRequestStatus();
				$statusQuery->whereAdd('isPatronCancel = 0 OR ISNULL(isPatronCancel)');
				$materialsRequestCounts->joinAdd($statusQuery, 'INNER', 'status', 'status', 'id');
				$requestsThisYear = $materialsRequestCounts->count();

				$interface->assign('success', true);
				$interface->assign('materialsRequest', $materialsRequest);
				// Update Request Counts on success
				$interface->assign('requestsThisYear', $requestsThisYear);
				$interface->assign('openRequests', $openRequests);

				require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestUsage.php';
				MaterialsRequestUsage::incrementStat($materialsRequest->status, $homeLibrary->libraryId);

				$materialsRequest->sendStatusChangeEmail();
				$materialsRequest->sendStaffNewMaterialsRequestEmail();
			}
		}

		$sidebar = '';
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getActiveUserObj();
			$enableMaterialsRequest = $user->canSuggestMaterials();
			$interface->assign('enableMaterialsRequest', $enableMaterialsRequest);

			$sidebar = 'Search/home-sidebar.tpl';
		}

		$this->display('submission-result.tpl', 'Submission Result', $sidebar);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('/MaterialsRequest/MyRequests', 'My Materials Requests');
		return $breadcrumbs;
	}
}