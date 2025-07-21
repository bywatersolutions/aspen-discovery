<?php

require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequest.php';
require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestStatus.php';
require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MaterialsRequest_IlsRequests extends MyAccount {

	function launch(): void {
		global $interface;

		//Get a list of all materials requests for the user
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getActiveUserObj();
			$linkedUsers = $user->getLinkedUsers();
			$patronId = empty($_REQUEST['patronId']) ? $user->id : $_REQUEST['patronId'];
			$interface->assign('patronId', $patronId);

			$patron = $user->getUserReferredTo($patronId);
			if (count($linkedUsers) > 0) {
				array_unshift($linkedUsers, $user);
				$interface->assign('linkedUsers', $linkedUsers);
			}
			$interface->assign('selectedUser', $patronId);

			$catalogConnection = CatalogFactory::getCatalogConnectionInstance();

			if (isset($_REQUEST['submit'])) {
				$deleteResult = $catalogConnection->deleteMaterialsRequests($patron);
				$interface->assign('deleteResult', $deleteResult);
			}
			$requestTemplate = $catalogConnection->getMaterialsRequestsPage($patron);

			$title = 'My Materials Requests';
			$this->display($requestTemplate, $title);
		} else {
			header('Location: /MyAccount/Home?followupModule=MaterialsRequest&followupAction=MyRequests');
			exit;
		}
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('/MaterialsRequest/IlsRequests', 'My Materials Requests');
		return $breadcrumbs;
	}
}