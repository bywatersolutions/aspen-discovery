<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MyAccount_OverDriveOptions extends MyAccount {
	function launch() : void {
		global $interface;
		$user = UserAccount::getLoggedInUser();

		if ($user) {
			$interface->assign('profile', $user);
			$patronHomeLibrary = $user->getHomeLibrary();
			if ($patronHomeLibrary == null) {
				$interface->assign('noHomeLibrary', true);
			} else {
				$availableSettings = $patronHomeLibrary->getOverdriveSettings();
				$interface->assign('availableSettings', $availableSettings);
				$qrStatuses = [];
				if (!empty($availableSettings)) {
					foreach ($availableSettings as $setting) {
						if (empty($setting->enableQRCodeAuth)) {
							continue;
						}
						$token = $user->getOverDriveQrToken($setting->id);
						if ($token) {
							$qrStatuses[$setting->id] = [
								'connected' => true,
								'expired' => $token->isExpired(),
								'expiresAt' => $token->expiresAt,
								'updated' => $token->updated,
							];
						} else {
							$qrStatuses[$setting->id] = [
								'connected' => false,
							];
						}
					}
				}
				$interface->assign('qrAuthStatuses', $qrStatuses);
				$interface->assign('qrAuthEnabled', count($qrStatuses) > 0);

				// Save/Update Actions
				global $offlineMode;
				if (isset($_POST['updateScope']) && !$offlineMode) {
					$user->updateOverDriveOptions();

					session_write_close();
					$actionUrl = '/MyAccount/OverDriveOptions';
					header("Location: " . $actionUrl);
					exit();
				} elseif (!$offlineMode) {
					$optionsForSetting = [];
					foreach ($availableSettings as $setting) {
						$optionsForSetting[$setting->id] = $user->getOverDriveOptions($setting->id);
					}
					$interface->assign('optionsBySetting', $optionsForSetting);
					$interface->assign('edit', true);
				} else {
					$interface->assign('edit', false);
				}
			}
		}

		$readerName = new OverDriveDriver();
		$readerName = $readerName->getReaderName();
		$interface->assign('readerName', $readerName);

		$this->display('overDriveOptions.tpl', 'Account Settings');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'OverDrive Options');
		return $breadcrumbs;
	}
}
