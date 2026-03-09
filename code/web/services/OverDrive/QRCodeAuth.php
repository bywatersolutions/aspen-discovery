<?php

use Random\RandomException;

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
require_once ROOT_DIR . '/sys/UserAccount.php';
require_once ROOT_DIR . '/sys/OverDrive/OverDriveSetting.php';
require_once ROOT_DIR . '/sys/LibraryLocation/Library.php';
require_once ROOT_DIR . '/sys/Account/User.php';

class OverDrive_QRCodeAuth extends Action {
	function launch() : void {
		// Check if this is a completion callback from OverDrive (has 'code' parameter)
		// or a disconnect request (has 'disconnect' parameter).
		// Otherwise, start the QR code flow.
		if (isset($_REQUEST['code'])) {
			$this->handleAuthComplete();
		} elseif (isset($_REQUEST['disconnect'])) {
			$this->disconnectSession();
		} else {
			$this->startQRCodeFlow();
		}
	}

	private function startQRCodeFlow(): void {
		$user = UserAccount::getLoggedInUser();
		if (!$user) {
			$this->displayResult(false, translate([
				'text' => 'Please sign in to link your Sora/OverDrive account.',
				'isPublicFacing' => true,
			]));
			return;
		}

		$driver = new OverDriveDriver();
		$availableSettings = $driver->getAvailableSettings();
		if (empty($availableSettings)) {
			$this->displayResult(false, translate([
				'text' => 'No OverDrive collections are configured for this library.',
				'isPublicFacing' => true,
			]));
			return;
		}

		$requestSettingId = isset($_REQUEST['settingId']) ? (int)$_REQUEST['settingId'] : null;
		if ($requestSettingId !== null && isset($availableSettings[$requestSettingId])) {
			$activeSetting = $availableSettings[$requestSettingId];
		} else {
			$activeSetting = reset($availableSettings);
		}

		if (empty($activeSetting->enableQRCodeAuth)) {
			$this->displayResult(false, translate([
				'text' => 'QR code authentication is not enabled for this OverDrive collection.',
				'isPublicFacing' => true,
			]));
			return;
		}

		$homeLibrary = $user->getHomeLibrary();
		if ($homeLibrary == null) {
			$this->displayResult(false, translate([
				'text' => 'Unable to determine your library for OverDrive access.',
				'isPublicFacing' => true,
			]));
			return;
		}

		$librarySettings = $homeLibrary->getLibraryOverdriveSetting($activeSetting->id);
		$credentials = $driver->getClientCredentials($activeSetting, $librarySettings);
		if (empty($credentials['clientKey']) || empty($credentials['clientSecret'])) {
			$this->displayResult(false, translate([
				'text' => 'OverDrive client credentials have not been configured.',
				'isPublicFacing' => true,
			]));
			return;
		}
		if (empty($activeSetting->websiteId)) {
			$this->displayResult(false, translate([
				'text' => 'The OverDrive website ID is missing for this collection.',
				'isPublicFacing' => true,
			]));
			return;
		}

		// Store state in memcache keyed by session ID to avoid session data loss.
		// Session data gets cleared when user re-authenticates during the OAuth flow,
		// but the session ID remains the same, so use it as a stable key.
		$returnUrl = $_REQUEST['returnUrl'] ?? null;
		$recordId = $_REQUEST['recordId'] ?? null;
		$action = $_REQUEST['resumeAction'] ?? null; // 'checkout' or 'hold'

		$stateData = [
			'userId' => $user->id,
			'settingId' => $activeSetting->id,
			'libraryId' => $homeLibrary->libraryId,
			'timestamp' => time(),
			'returnUrl' => $returnUrl,
			'recordId' => $recordId,
			'action' => $action,
		];

		$sessionId = session_id();
		global $memCache;
		if ($memCache) {
			$cacheKey = "overdrive_qr_auth_state_{$sessionId}";
			$memCache->set($cacheKey, $stateData, 300); // 5 minutes
		} else {
			$_SESSION['overdrive_qr_auth_state'] = $stateData;
			session_write_close();
		}

		global $configArray;
		$baseUrl = rtrim($configArray['Site']['url'], '/');
		$params = [
			'redirect_url' => $baseUrl . '/OverDrive/QRCodeAuth',
			'abandon_url' => $baseUrl . '/OverDrive/QRCodeAuthCanceled',
			'error_url' => $baseUrl . '/OverDrive/QRCodeAuthFailed',
			'website_id' => $activeSetting->websiteId,
			'client_id' => $credentials['clientKey'],
		];

		$target = 'https://oauth-patron.overdrive.com/device/initiate?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
		header("Location: $target");
		exit();
	}

	private function handleAuthComplete(): void {
		$code = $_REQUEST['code'] ?? '';
		if (empty($code)) {
			$this->displayResult(false, translate([
				'text' => 'Missing authorization code from OverDrive.',
				'isPublicFacing' => true,
			]));
			return;
		}

		$stateData = $this->getStateData();
		if ($stateData === null) {
			$this->displayResult(false, translate([
				'text' => 'Authentication session expired or invalid. Please try again.',
				'isPublicFacing' => true,
			]));
			return;
		}

		$user = UserAccount::getLoggedInUser();
		if (!$user || $user->id != $stateData['userId']) {
			$user = new User();
			$user->id = $stateData['userId'];
			if (!$user->find(true)) {
				$this->displayResult(false, translate([
					'text' => 'Unable to load the user account for this session.',
					'isPublicFacing' => true,
				]));
				return;
			}
		}

		$setting = new OverDriveSetting();
		$setting->id = $stateData['settingId'];
		if (!$setting->find(true)) {
			$this->displayResult(false, translate([
				'text' => 'Unable to load the OverDrive collection for this request.',
				'isPublicFacing' => true,
			]));
			return;
		}

		if (empty($setting->enableQRCodeAuth)) {
			$this->displayResult(false, translate([
				'text' => 'This OverDrive collection does not allow QR code authentication.',
				'isPublicFacing' => true,
			]));
			return;
		}

		$library = new Library();
		$library->libraryId = $stateData['libraryId'];
		if (!$library->find(true)) {
			$library = $user->getHomeLibrary();
			if (!$library) {
				$this->displayResult(false, translate([
					'text' => 'Unable to determine which library should be used for this OverDrive account.',
					'isPublicFacing' => true,
				]));
				return;
			}
		}

		$librarySettings = $library->getLibraryOverdriveSetting($setting->id);
		$driver = OverDriveDriver::getOverDriveDriver($setting->id);
		$tokenData = $driver->exchangeQRCodeAuthCode($setting, $librarySettings, $code);
		if (!$tokenData) {
			$this->displayResult(false, translate([
				'text' => 'OverDrive did not provide an access token. Please try again.',
				'isPublicFacing' => true,
			]));
			return;
		}

		$user->saveOverDriveQrToken($setting->id, $tokenData);

		if (!empty($stateData['returnUrl']) && !empty($stateData['recordId']) && !empty($stateData['action'])) {
			$returnUrl = $stateData['returnUrl'];
			$recordId = $stateData['recordId'];
			$action = $stateData['action'];

			if ($action === 'checkout') {
				global $interface;
				$interface->assign('autoTriggerCheckout', true);
				$interface->assign('recordId', $recordId);
				$interface->assign('returnUrl', $returnUrl);
				$this->display('qrCodeAuthRedirect.tpl', 'Authentication Complete');
				return;
			} elseif ($action === 'hold') {
				// Trigger hold via JavaScript on the return page
				global $interface;
				$interface->assign('autoTriggerHold', true);
				$interface->assign('recordId', $recordId);
				$interface->assign('returnUrl', $returnUrl);
				$this->display('qrCodeAuthRedirect.tpl', 'Authentication Complete');
				return;
			}
		}

		$this->displayResult(true, translate([
			'text' => 'Success! Your OverDrive account is ready for one-click checkouts.',
			'isPublicFacing' => true,
		]));
	}

	private function disconnectSession(): void {
		$user = UserAccount::getLoggedInUser();
		if (!$user) {
			$this->displayResult(false, translate([
				'text' => 'Please sign in to change this setting.',
				'isPublicFacing' => true,
			]));
			return;
		}
		$settingId = isset($_REQUEST['settingId']) ? (int)$_REQUEST['settingId'] : 0;
		if ($settingId <= 0) {
			$this->displayResult(false, translate([
				'text' => 'Missing OverDrive setting identifier.',
				'isPublicFacing' => true,
			]));
			return;
		}
		$homeLibrary = $user->getHomeLibrary();
		$settings = $homeLibrary ? $homeLibrary->getOverdriveSettings() : [];
		if (!isset($settings[$settingId]) || empty($settings[$settingId]->enableQRCodeAuth)) {
			$this->displayResult(false, translate([
				'text' => 'QR code authentication is not available for this collection.',
				'isPublicFacing' => true,
			]));
			return;
		}

		$user->deleteOverDriveQrToken($settingId);
		global $memCache;
		$barcode = $user->getBarcode();
		if ($memCache && !empty($barcode) && $homeLibrary) {
			$cacheKey = "overdrive_patron_token_{$settingId}_{$homeLibrary->libraryId}_{$barcode}";
			$memCache->delete($cacheKey);
		}

		$this->displayResult(true, translate([
			'text' => 'The saved %1% session has been removed.',
			1 => $settings[$settingId]->readerName,
			'isPublicFacing' => true,
		]));
	}

	private function getStateData(): ?array {
		global $logger;
		global $memCache;
		$sessionId = session_id();
		$stateData = null;
		if ($memCache) {
			$cacheKey = "overdrive_qr_auth_state_{$sessionId}";
			$stateData = $memCache->get($cacheKey);
			if ($stateData !== false) {
				$memCache->delete($cacheKey);
			}
		}

		// Fallback to session if memcache wasn't available or didn't have the data.
		if ($stateData === null || $stateData === false) {
			if (isset($_SESSION['overdrive_qr_auth_state'])) {
				$stateData = $_SESSION['overdrive_qr_auth_state'];
				unset($_SESSION['overdrive_qr_auth_state']);
			}
		}

		if ($stateData === null || $stateData === false) {
			return null;
		}

		// Check if expired (5 minutes)
		if (isset($stateData['timestamp']) && (time() - $stateData['timestamp']) > 300) {
			return null;
		}

		return $stateData;
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
