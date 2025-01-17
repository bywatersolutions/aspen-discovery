<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MyAccount_MyPrivacySettings extends MyAccount {
	function launch() {
		global $interface;
		global $library;

		if (!UserAccount::isLoggedIn()) {
			$interface->assign('error', 'You must be logged in to access the Administration Interface');
		}
		
		// Handles patrons attempting to access the page directly through the URL when the 'Privacy Settings' link is disabled and does not show
		if (!$library->ilsConsentEnabled && !$library->cookieStorageConsent) {
			header("Location: " . '/MyAccount');
		}
		
		$user = UserAccount::getLoggedInUser();
		$linkedUsers = $user->getLinkedUsers();
		$driver = $user->getCatalogDriver();

		$consentPluginNames = $driver->getPluginNamesByMethodName('patron_consent_type');
		$anyConsentPluginsEnabled = false;

		foreach($consentPluginNames as $pluginName) {
			$pluginStatus = $driver->getPluginStatus($pluginName);
			if ($library->ilsConsentEnabled && !$pluginStatus['enabled']) {
				global $logger;
				$statusDescription = $pluginStatus['installed'] ? 'disabled' : 'not installed';
				$logger->log("Patrons Privacy Settings: Patrons cannot view and update their consents as the $pluginName plugin is $statusDescription", Logger::LOG_ERROR);
	
				$messages = [];
				$messages[] = [
					'message' => 'Other consent options are enabled but cannot be shown here due to a technical issue. Please contact your library.',
					'messageStyle' => 'info',
				];
				$interface->assign('ilsMessages', $messages);
				continue;
			}
			$anyConsentPluginsEnabled = true;
		}

		$interface->assign('ilsConsentEnabled', $library->ilsConsentEnabled && $anyConsentPluginsEnabled);
		$interface->assign('cookieConsentEnabled', $library->cookieStorageConsent);
		
		//Determine which user we are showing/updating settings for
		$patronId = isset($_REQUEST['patronId']) ? $_REQUEST['patronId'] : $user->id;
		/** @var $patron */
		$patronRefferedTo = $user->getUserReferredTo($patronId);
		if (!$patronRefferedTo) {
			return;
		}
		if (empty($interface->getVariable('profile'))) {
			$interface->assign('profile', $patronRefferedTo);
		}

		//Linked Accounts Selection Form set-up
		if (count($linkedUsers) > 0) {
			array_unshift($linkedUsers, $patronRefferedTo);
			$interface->assign('linkedUsers', $linkedUsers);
			$interface->assign('selectedUser', $patronId);
		}

		$action = $this->assignAction();
		$consentTypes = $library->ilsConsentEnabled && $anyConsentPluginsEnabled ? $driver->getFormattedConsentTypes() : null;

		if ($action == 'save') {
			$this->updatePrivacySettings($user, $patronRefferedTo, $driver, $anyConsentPluginsEnabled, $consentTypes);
			session_write_close();
			$actionUrl = '/MyAccount/MyPrivacySettings' . ($patronRefferedTo->id == $user->id ? '': '?patronId=' . $patronId);
			header("Location: " . $actionUrl);
		}

		if (!empty($user->updateMessage)) {
			if ($user->updateMessageIsError) {
				$interface->assign('profileUpdateErrors', $user->updateMessage);
			} else {
				$interface->assign('profileUpdateMessage', $user->updateMessage);
			}
			$user->updateMessage = '';
			$user->updateMessageIsError = 0;
		}
		
		if (!$library->ilsConsentEnabled || !$anyConsentPluginsEnabled) {
			$this->display('myPrivacySettings.tpl', 'My Privacy Settings');
			return;
		}

		// Handles cases where the patron has already given their consent on Koha
		$userConsents = $driver->getPatronConsents($user);
		if (!empty($userConsents)) {
			foreach($userConsents as $userConsent) {
				if ($userConsent['given_on']) {
					$consentTypes[$userConsent['type']]['enabledForUser'] = true;
				}
			}
		}
		$interface->assign('consentTypes', $consentTypes);
		$this->display('myPrivacySettings.tpl', 'My Privacy Settings');
	}

	private function assignAction(): string {
		global $offlineMode;
		global $interface;
		if (isset($_POST['updateScope']) && !$offlineMode) {
			$interface->assign('edit', false);
			return "save";
		}
		if (!$offlineMode) {
			$interface->assign('edit', true);
			return "edit";
		}
		$interface->assign('edit', false);
		return "";
	}

	private function updatePrivacySettings($user, $patronRefferedTo, $driver, $anyConsentPluginsEnabled = null, $consentTypes = null): void {
		if ($_REQUEST['patronId'] != $user->id) {
			$user->updateMessage = translate([
				'text' => 'Wrong account credentials, please try again.',
				'isPublicFacing' => true,
			]);
			$user->updateMessageIsError = true;
			return;
		}
		$result = [];

		if ($_POST['updateScope'] === 'userCookiePreference') {
			require_once ROOT_DIR . '/services/MyAccount/MyCookiePreferences.php';
			$myCookiePreferences = new MyAccount_MyCookiePreferences();
			$result = $myCookiePreferences->updateCookiePreferences($patronRefferedTo);
		}
		
		if ($_POST['updateScope'] === 'userILSIssuedConsent') {
			$consentTypeName = $_REQUEST['updateScopeSection'];
			if (!$anyConsentPluginsEnabled) {
				global $logger;
				$logger->log("Patrons Privacy Settings: Patron with id $patronRefferedTo->id could not update their $consentTypeName consent", Logger::LOG_ERROR);
	
				$user->updateMessage = translate([
					'text' => 'Could not update consent due to a technical issue. Please contact your library',
					'isPublicFacing' => true,
				]);
				$user->updateMessageIsError = true;
			}
			foreach ($consentTypes as $consentType) {
				$result = $driver->updatePatronConsent($patronRefferedTo->unique_ils_id, $consentType['allCapsCode'], isset($_POST['userNewsletter']));
			}
		}
		if (isset($result['message'])) {
			$user->updateMessage .= ' ' . $result['message'];
		}
		$user->updateMessageIsError = $user->updateMessageIsError || $result['success'];
		return;
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'Your Privacy Settings');
		return $breadcrumbs;
	}
}