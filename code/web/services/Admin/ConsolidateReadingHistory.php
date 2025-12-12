<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';

class Admin_ConsolidateReadingHistory extends Admin_Admin {
	function launch(): void {
		global $interface;
		if (isset($_REQUEST['submit'])) {
			$barcode = trim($_REQUEST['barcode']);
			if (!empty($barcode)) {
				$interface->assign('barcode', $barcode);

				require_once ROOT_DIR . '/sys/Utils/SystemUtils.php';
				$additionalParameters = [];
				$additionalParameters[] = $barcode;
				$results = SystemUtils::startBackgroundProcess("consolidateReadingHistory", $additionalParameters);
				$interface->assign('results', $results);
			}else {
				$interface->assign('error', 'Barcode was not entered');
			}
		}

		$this->display('consolidateReadingHistory.tpl', 'Consolidate Reading History', false);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_admin', 'System Administration');
		$breadcrumbs[] = new Breadcrumb('', 'Consolidate Reading History');

		return $breadcrumbs;
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Perform System Maintenance');
	}

	function getActiveAdminSection(): string {
		return 'system_admin';
	}
}