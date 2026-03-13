<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
require_once ROOT_DIR . '/sys/UserAccount.php';

class OverDrive_QRCodeAuthFailed extends Action {
	function launch() : void {
		$errorDetail = $_REQUEST['error'] ?? '';

		// Clean up session state data
		$this->cleanupStateData();

		$this->displayResult(false, translate([
			'text' => 'OverDrive returned an error while authorizing the account.%1%',
			'1' => empty($errorDetail) ? '' : ' (' . $errorDetail . ')',
			'isPublicFacing' => true,
		]));
	}

	private function cleanupStateData(): void {
		if (isset($_SESSION['overdrive_qr_auth_state'])) {
			unset($_SESSION['overdrive_qr_auth_state']);
		}
	}

	private function displayResult(bool $success, string $message): void {
		global $interface;
		$readerNameDriver = new OverDriveDriver();
		$interface->assign('readerName', $readerNameDriver->getReaderName());
		$interface->assign('qrResultSuccess', $success);
		$interface->assign('qrResultMessage', $message);
		$pageTitle = translate([
			'text' => 'OverDrive Authentication',
			'isPublicFacing' => true,
		]);
		$interface->assign('qrResultTitle', $pageTitle);
		$this->display('qrCodeAuthResult.tpl', $pageTitle);
	}

	function getBreadcrumbs(): array {
		return [];
	}
}
