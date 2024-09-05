<?php

require_once ROOT_DIR . '/Action.php';

class AJAX_JSON extends Action {

	function launch() {
		//header('Content-type: application/json');
		header('Content-type: application/json');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		$method = $_GET['method'];
		if (method_exists($this, $method)) {
			if ($method == 'getHoursAndLocations') {
				header('Content-type: text/html');
				$output = $this->$method();
			} elseif (in_array($method, [
				'getAutoLogoutPrompt',
				'getReturnToHomePrompt',
				'getPayFinesAfterAction',
				'getTranslationForm',
				'saveTranslation',
				'saveLanguagePreference',
				'saveCookiePreference',
				'deleteTranslationTerm',
				'getDisplaySettingsForm',
				'updateDisplaySettings',
				'manageCookiePreferences',
				'saveCookieManagementPreferences'
			])) {
				$output = json_encode($this->$method());
				// Browser-side handler ajaxLightbox() doesn't use the input format in else block below
			} else {
				$output = json_encode(['result' => $this->$method()]);
			}
		} else {
			$output = json_encode(['error' => 'invalid_method']);
		}

		echo $output;
	}

	/** @noinspection PhpUnused */
	function deleteTranslationTerm() {
		$termId = $_REQUEST['termId'];
		$translation = new Translation();
		$translation->termId = $termId;
		$numDeleted = $translation->delete(true);

		$term = new TranslationTerm();
		$term->id = $termId;
		$numDeleted += $term->delete(true);

		if ($numDeleted == 0) {
			return [
				'success' => false,
				'message' => translate([
					'text' => 'Nothing was deleted, may have been deleted already.',
					'isAdminFacing' => true,
				]),
			];
		} else {
			return [
				'title' => translate([
					'text' => 'Error.',
					'isAdminFacing' => true,
				]),
				'success' => true,
				'message' => translate([
					'text' => 'The term was deleted successfully.',
					'isAdminFacing' => true,
				]),
			];
		}
	}

	/** @noinspection PhpUnused */
	function saveLanguagePreference() {
		if (UserAccount::isLoggedIn()) {
			$userObj = UserAccount::getActiveUserObj();
			$userObj->searchPreferenceLanguage = $_REQUEST['searchPreferenceLanguage'];
			$userObj->update();
			return [
				'success' => true,
				'message' => 'Your preferences were updated.  You can make changes to this setting within your account setting.',
			];
		} else {
			setcookie('searchPreferenceLanguage', $_REQUEST['searchPreferenceLanguage'], 0, '/');
			return [
				'success' => true,
				'message' => '',
			];
		}
	}

	/** @noinspeciton PhpUnused */
	function saveCookiePreference(){
		if (UserAccount::isLoggedIn()) {
			//update user object with cookie preference choice from cookieConsent banner
			$userObj = UserAccount::getActiveUserObj();
			$userObj->userCookiePreferenceEssential = $_REQUEST['cookieEssential'];
			$userObj->userCookiePreferenceAnalytics = $_REQUEST['cookieAnalytics'];
			$userObj->userCookiePreferenceLocalAnalytics = $_REQUEST['cookieUserLocalAnalytics'];
			$userObj->update(); //update user object to DB
			return[
				'success' => true,
				'message' => 'Your preferences were updated.  You can make changes to these preferences within your account settings.',
			];
		} else {
			//if not logged in, still set the cookieConsent cookie with user's choice
			$userCookiePost = [
				'Essential' => 1, //Essential cookies cannot be disabled
				'Analytics' => $_REQUEST['cookieAnalytics'],
				'UserLocalAnalytics' => $_REQUEST['cookieUserLocalAnalytics'],
				];
			setcookie('cookieConsent', json_encode($userCookiePost), 0, '/');
			return [
				'success' => true,
				'message' => ''//does this require a messge?,
			];
		}
	}

	/** @noinspection PhpUnused */
	function getTranslationForm() {
		if (UserAccount::userHasPermission('Translate Aspen')) {
			$translationTerm = new TranslationTerm();
			$translationTerm->id = $_REQUEST['termId'];
			if ($translationTerm->find(true)) {
				global $interface;
				global $activeLanguage;
				$interface->assign('translationTerm', $translationTerm);
				$translation = new Translation();
				$translation->termId = $translationTerm->id;
				$translation->languageId = $activeLanguage->id;
				if ($translation->find(true)) {
					$interface->assign('translation', $translation);
				}
				//English is always 1.  If we are not in english mode, show english as well for reference
				if ($activeLanguage->id != 1) {
					$englishTranslation = new Translation();
					$englishTranslation->termId = $translationTerm->id;
					$englishTranslation->languageId = 1;
					if ($englishTranslation->find(true)) {
						$interface->assign('englishTranslation', $englishTranslation);
					}
				}

				$result = [
					'title' => translate([
						'text' => 'Translate a term',
						'isAdminFacing' => true,
					]),
					'modalBody' => $interface->fetch('Translation/termTranslationForm.tpl'),
					'modalButtons' => "<button class='tool btn btn-primary' onclick='AspenDiscovery.saveTranslation()'>" . translate([
							'text' => 'Update Translation',
							'isAdminFacing' => true,
						]) . "</button>",
				];
			} else {
				$result = [
					'success' => false,
					'message' => 'No translation term was found',
				];
			}
		} else {
			$result = [
				'success' => false,
				'message' => 'You do not have permissions for this functionality',
			];
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function saveTranslation() {
		$translationId = strip_tags($_REQUEST['translationId']);
		$newTranslation = $_REQUEST['translation'];
		$translation = new Translation();
		$translation->id = $translationId;
		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown Error',
				'isPublicFacing' => true,
			]),
		];
		if (UserAccount::userHasPermission('Translate Aspen')) {
			if ($translation->find(true)) {
				$translation->setTranslation($newTranslation);
				$result = [
					'success' => true,
					'message' => 'Successfully updated the translation',
				];
			} else {
				$result['message'] = 'Could not find the translation';
			}
		} else {
			$result['message'] = 'You do not have permissions to translate';
		}

		return $result;
	}

	function isLoggedIn() {
		return UserAccount::isLoggedIn();
	}

	/** @noinspection PhpUnused */
	function getUserLists() {
		$user = UserAccount::getLoggedInUser();
		$lists = $user->getLists();
		$userLists = [];
		foreach ($lists as $current) {
			$userLists[] = [
				'id' => $current->id,
				'title' => $current->title,
			];
		}
		return $userLists;
	}

	/** @noinspection PhpUnused */
	function loginUser() {
		//Login the user.  Must be called via Post parameters.
		global $interface;
		global $logger;
		$_SESSION['enroll2FA'] = false;
		$_SESSION['has2FA'] = false;
		$_SESSION['codeSent'] = false;
		$_SESSION['passwordExpired'] = false;
		$logger->log("Starting JSON/loginUser session: " . session_id(), Logger::LOG_DEBUG);
		$isLoggedIn = UserAccount::isLoggedIn();
		if (!$isLoggedIn) {
			try {
				$user = UserAccount::login();

				$interface->assign('user', $user); // PLB Assignment Needed before error checking?
				if (!$user || ($user instanceof AspenError)) {
					// Expired Card Notice
					if ($user instanceof ExpiredPasswordError) {
						$interface->assign('token', $user->resetToken);
						$interface->assign('tokenValid', true);
						$interface->assign('userID', $user->userId);
						$interface->assign('showSubmitButton', false);

						$catalog = CatalogFactory::getCatalogConnectionInstance();
						$pinValidationRules = $catalog->getPasswordPinValidationRules();
						$interface->assign('pinValidationRules', $pinValidationRules);

						$_SESSION['enroll2FA'] = false;
						$_SESSION['has2FA'] = false;
						$_SESSION['passwordExpired'] = true;

						return [
							'success' => false,
							'title' => translate([
								'text' => 'Error',
								'isPublicFacing' => true,
							]),
							'body' => $interface->fetch('MyAccount/pinResetWithTokenPopup.tpl'),
							'message' => translate([
								'text' => 'Your PIN has expired.',
								'isPublicFacing' => true,
							]),
							'buttons' => '<button class="btn btn-primary" type="submit" name="submit" onclick="$(\'#resetPin\').submit();">' . translate([
									'text' => 'Reset My PIN',
									'isPublicFacing' => true,
								]) . '</button>',
							'passwordExpired' => true,
							'enroll2FA' => false,
						];
					} else if ($user instanceof TwoFactorAuthenticationError) {
						if ($user->twoFactorAuthStatus == TwoFactorAuthenticationError::MUST_ENROLL) {
							// User needs to enroll into 2FA
							$_SESSION['enroll2FA'] = true;
							$_SESSION['twoFactorStart'] = time();
							$_SESSION['has2FA'] = false;
							$_SESSION['passwordExpired'] = false;

							return [
								'success' => false,
								'enroll2FA' => true,
								'has2FA' => false,
								'passwordExpired' => false,
							];
						} else {
							// User needs to authenticate with 2FA
							$_SESSION['enroll2FA'] = false;
							$_SESSION['has2FA'] = true;
							$_SESSION['twoFactorStart'] = time();
							$_SESSION['passwordExpired'] = false;
							$referer = $_REQUEST['referer'] ?? null;
							$interface->assign('referer', $referer);
							$name = $_REQUEST['name'] ?? null;
							$interface->assign('name', $name);
							$interface->assign('codeSent', !empty($_SESSION['codeSent']));
							return [
								'success' => false,
								'enroll2FA' => false,
								'has2FA' => true,
								'passwordExpired' => false,
								'title' => translate([
									'text' => 'Two-Factor Authentication',
									'isPublicFacing' => true,
								]),
								'body' => $interface->fetch('MyAccount/2fa/login.tpl'),
								'buttons' => "<button class='tool btn btn-primary' onclick='AspenDiscovery.Account.verify2FALogin(); return false;'>" . translate([
										'text' => 'Verify',
										'isPublicFacing' => true,
									]) . "</button>",
							];
						}
					} else if ($user instanceof AspenError && $user->getMessage() == 'Your library card has expired. Please contact your local library to have your library card renewed.') {
						// Expired Card Notice
						return [
							'success' => false,
							'message' => translate([
								'text' => 'Your library card has expired. Please contact your local library to have your library card renewed.',
								'isPublicFacing' => true,
							]),
							'enroll2FA' => false,
							'has2FA' => false,
							'passwordExpired' => false,
						];
					}

					// General Login Error
					/** @var AspenError $error */
					$error = $user;
					$message = ($user instanceof AspenError) ? translate([
						'text' => $error->getMessage(),
						'isPublicFacing' => true,
					]) : translate([
						'text' => "Sorry that login information was not recognized, please try again.",
						'isPublicFacing' => true,
					]);
					return [
						'success' => false,
						'message' => $message,
					];
				} else {
					$logger->log("User was logged in successfully session: " . session_id(), Logger::LOG_DEBUG);
				}
			} catch (UnknownAuthenticationMethodException $e) {
				$logger->log("Error logging user in $e", Logger::LOG_DEBUG);
				return [
					'success' => false,
					'message' => $e->getMessage(),
				];
			}
		} else {
			$logger->log("User is already logged in", Logger::LOG_DEBUG);
			$user = UserAccount::getLoggedInUser();
		}

		$patronHomeBranch = Location::getUserHomeLocation();
		//Check to see if materials request should be activated
		require_once ROOT_DIR . '/sys/MaterialsRequest.php';

		return [
			'success' => true,
			'twoFactor' => false,
			'name' => htmlentities($user->displayName),
			'phone' => htmlentities($user->phone),
			'email' => htmlentities($user->email),
			'homeLocation' => isset($patronHomeBranch) ? $patronHomeBranch->code : '',
			'homeLocationId' => isset($patronHomeBranch) ? $patronHomeBranch->locationId : '',
			'enableMaterialsRequest' => MaterialsRequest::enableAspenMaterialsRequest(true),
		];
	}

	/**
	 * Send output data and exit.
	 *
	 * @param mixed $data The response data
	 * @param string $status Status of the request
	 *
	 * @return void
	 * @access public
	 */
	protected function output($data, $status) {
		header('Content-type: application/javascript');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		$output = [
			'data' => $data,
			'status' => $status,
		];
		echo json_encode($output);
		exit;
	}

	/** @noinspection PhpUnused */
	function getHoursAndLocations() {
		//Get a list of locations for the current library
		global $library;
		global $configArray;
		$tmpLocation = new Location();
		$tmpLocation->libraryId = $library->libraryId;
		$tmpLocation->showInLocationsAndHoursList = 1;
		$tmpLocation->orderBy('isMainBranch DESC, displayName'); // List Main Branches first, then sort by name
		$libraryLocations = [];
		$tmpLocation->find();
		if ($tmpLocation->getNumResults() == 0) {
			//Get all locations
			$tmpLocation = new Location();
			$tmpLocation->showInLocationsAndHoursList = 1;
			$tmpLocation->orderBy('displayName');
			$tmpLocation->find();
		}

		$locationsToProcess = [];
		while ($tmpLocation->fetch()) {
			$locationsToProcess[] = clone $tmpLocation;
		}

		require_once ROOT_DIR . '/sys/Enrichment/GoogleApiSetting.php';
		$googleSettings = new GoogleApiSetting();
		if ($googleSettings->find(true)) {
			$mapsKey = $googleSettings->googleMapsKey;
		} else {
			$mapsKey = null;
		}
		require_once ROOT_DIR . '/sys/Parsedown/AspenParsedown.php';
		$parsedown = AspenParsedown::instance();
		$parsedown->setBreaksEnabled(true);
		foreach ($locationsToProcess as $locationToProcess) {
			$mapAddress = urlencode(preg_replace('/\r\n|\r|\n/', '+', $locationToProcess->address));
			$hours = $locationToProcess->getHours();
			foreach ($hours as $key => $hourObj) {
				if (!$hourObj->closed) {
					$hourString = $hourObj->open;
					[
						$hour,
						$minutes,
					] = explode(':', $hourString);
					if ($hour < 12) {
						if ($hour == 0) {
							$hour += 12;
						}
						$hourObj->open = +$hour . ":$minutes AM"; // remove leading zeros in the hour
					} elseif ($hour == 12 && $minutes == '00') {
						$hourObj->open = 'Noon';
					} elseif ($hour == 24 && $minutes == '00') {
						$hourObj->open = 'Midnight';
					} else {
						if ($hour != 12) {
							$hour -= 12;
						}
						$hourObj->open = "$hour:$minutes PM";
					}
					$hourString = $hourObj->close;
					[
						$hour,
						$minutes,
					] = explode(':', $hourString);
					if ($hour < 12) {
						if ($hour == 0) {
							$hour += 12;
						}
						$hourObj->close = "$hour:$minutes AM";
					} elseif ($hour == 12 && $minutes == '00') {
						$hourObj->close = 'Noon';
					} elseif ($hour == 24 && $minutes == '00') {
						$hourObj->close = 'Midnight';
					} else {
						if ($hour != 12) {
							$hour -= 12;
						}
						$hourObj->close = "$hour:$minutes PM";
					}
				}
				$hours[$key] = $hourObj;
			}
			$libraryLocation = [
				'id' => $locationToProcess->locationId,
				'name' => $locationToProcess->displayName,
				'address' => preg_replace('/\r\n|\r|\n/', '<br>', $locationToProcess->address),
				'phone' => $locationToProcess->phone,
				'tty' => $locationToProcess->tty,
				'email' => $locationToProcess->contactEmail,
				//'map_image' => "http://maps.googleapis.com/maps/api/staticmap?center=$mapAddress&zoom=15&size=200x200&sensor=false&markers=color:red%7C$mapAddress",
				'hours' => $hours,
				'hasValidHours' => $locationToProcess->hasValidHours(),
				'description' => $parsedown->parse($locationToProcess->description),
				'image' => $locationToProcess->locationImage ? $configArray['Site']['url'] . '/files/original/' . $locationToProcess->locationImage : null,
				'longitude' => floatval($locationToProcess->longitude),
				'latitude' => floatval($locationToProcess->latitude),
				'homeLink' => !empty($library->homeLink) ? $library->homeLink : null,
				'hoursMessage' => Location::getLibraryHoursMessage($locationToProcess->locationId, true),
			];

			if (!empty($mapsKey)) {
				$libraryLocation['map_link'] = "http://maps.google.com/maps?f=q&hl=en&geocode=&q=$mapAddress&ie=UTF8&z=15&iwloc=addr&om=1&t=m&key=$mapsKey";
			}
			$libraryLocations[$locationToProcess->locationId] = $libraryLocation;
		}

		global $interface;
		$interface->assign('libraryLocations', $libraryLocations);
		return $interface->fetch('AJAX/libraryHoursAndLocations.tpl');
	}

	/** @noinspection PhpUnused */
	function getAutoLogoutPrompt() {
		global $interface;
		$masqueradeMode = UserAccount::isUserMasquerading();
		return [
			'title' => translate([
				'text' => 'Still There?',
				'isPublicFacing' => true,
			]),
			'modalBody' => $interface->fetch('AJAX/autoLogoutPrompt.tpl'),
			'modalButtons' => "<div id='continueSession' class='btn btn-primary' onclick='continueSession();'>" . translate([
					'text' => 'Continue',
					'isPublicFacing' => true,
				]) . "</div>" . ($masqueradeMode ? "<div id='endSession' class='btn btn-primary' onclick='AspenDiscovery.Account.endMasquerade()'>" . translate([
						'text' => 'End Masquerade',
						'isAdminFacing' => true,
					]) . "</div>" . "<div id='endSession' class='btn btn-warning' onclick='endSession()'>" . translate([
						'text' => 'Logout',
						'isAdminFacing' => true,
					]) . "</div>" : "<div id='endSession' class='btn btn-warning' onclick='endSession()'>" . translate([
						'text' => 'Logout',
						'isAdminFacing' => true,
					]) . "</div>"),
		];
	}

	/** @noinspection PhpUnused */
	function getReturnToHomePrompt() {
		global $interface;
		return [
			'title' => translate([
				'text' => 'Still There?',
				'isPublicFacing' => true,
			]),
			'modalBody' => $interface->fetch('AJAX/autoReturnToHomePrompt.tpl'),
			'modalButtons' => "<a id='continueSession' class='btn btn-primary' onclick='continueSession();'>" . translate([
					'text' => 'Continue',
					'isPublicFacing' => true,
				]) . "</a>",
		];
	}

	/** @noinspection PhpUnused */
	function getPayFinesAfterAction() {
		global $interface;
		return [
			'title' => translate([
				'text' => 'Pay Fines',
				'isPublicFacing' => true,
			]),
			'modalBody' => $interface->fetch('AJAX/refreshFinesAccountInfo.tpl'),
			'modalButtons' => '<a class="btn btn-primary" href="/MyAccount/Fines?reload">Refresh My Fines Information</a>',
		];
	}

	/** @noinspection PhpUnused */
	function formatCurrency() {
		$currencyValue = isset($_REQUEST['currencyValue']) ? $_REQUEST['currencyValue'] : 0;

		global $activeLanguage;

		$currencyCode = 'USD';
		$variables = new SystemVariables();
		if ($variables->find(true)) {
			$currencyCode = $variables->currencyCode;
		}

		$currencyFormatter = new NumberFormatter($activeLanguage->locale . '@currency=' . $currencyCode, NumberFormatter::CURRENCY);

		$formattedCurrency = $currencyFormatter->formatCurrency($currencyValue, $currencyCode);

		return [
			'success' => true,
			'formattedValue' => $formattedCurrency,
		];
	}

	/** @noinspection PhpUnused */
	function getDisplaySettingsForm() {
		global $interface;

		return [
			'title' => translate([
				'text' => 'Display Settings',
				'isPublicFacing' => true,
			]),
			'modalBody' => $interface->fetch('AJAX/displaySettings.tpl'),
			'modalButtons' => "<a id='updateDisplaySettings' class='btn btn-primary' onclick='AspenDiscovery.updateDisplaySettings();'>" . translate([
					'text' => 'Update Settings',
					'isPublicFacing' => true,
				]) . "</a>",
		];
	}

	function updateDisplaySettings() {
		global $interface;
		$userLanguage = UserAccount::getUserInterfaceLanguage();
		if ($userLanguage == '') {
			$language = strip_tags((isset($_SESSION['language'])) ? $_SESSION['language'] : 'en');
		} else {
			$language = $userLanguage;
		}

		$preferredLanguage = strip_tags($_REQUEST['preferredLanguage']);
		if ($language != $preferredLanguage) {
			$language = $preferredLanguage;
			$_SESSION['language'] = $language;
			//Clear the preference cookie
			if (isset($_COOKIE['searchPreferenceLanguage'])) {
				//Clear the cookie when we change languages
				setcookie('searchPreferenceLanguage', $_COOKIE['searchPreferenceLanguage'], time() - 1000, '/');
				unset($_COOKIE['searchPreferenceLanguage']);
			}
		}

		$activeThemeId = $interface->getVariable('activeThemeId');
		if (isset($_REQUEST['preferredTheme'])) {
			$preferredTheme = strip_tags($_REQUEST['preferredTheme']);
			if ($activeThemeId != $preferredTheme) {
				$_SESSION['preferredTheme'] = $preferredTheme;
			}
		}

		return [
			'success' => true,
			'message' => translate([
				'text' => 'Your settings have been updated',
				'isPublicFacing' => true,
			]),
		];
	}

	function manageCookiePreferences(){
		global $interface;
		return [
			'success' => true,
			'modalBody' => $interface->fetch('AJAX/cookieManagement.tpl'),
			'modalButtons' => '<button type="button" class="btn btn-primary" onclick="$(\'#cookieManagementPreferencesForm\').submit();">' . translate([
					'text' => 'Save Preferences',
					'isPublicFacing' => true,
					'inAttribute' => true,
				]) . '</button>',
		];
	}

	function saveCookieManagementPreferences() {
		require_once ROOT_DIR . '/services/MyAccount/MyCookiePreferences.php';
		if (UserAccount::isLoggedIn()) {
			$userObj = UserAccount::getActiveUserObj();
			$userObj->userCookiePreferenceEssential = $_REQUEST['cookieEssential'] == "1" || $_REQUEST['cookieEssential'] == 1 ? 1 : 0;
			$userObj->userCookiePreferenceAnalytics = $_REQUEST['cookieAnalytics'] == "1" || $_REQUEST['cookieAnalytics'] == 1 ? 1 : 0;
			$userObj->userCookiePreferenceLocalAnalytics = $_REQUEST['cookieUserLocalAnalytics'] == "1" || $_REQUEST['cookieUserLocalAnalytics'] == 1 ? 1 : 0;

			$userObj->update();
			
			if ($userObj->userCookiePreferenceLocalAnalytics == 0) {
				$this->removeLocalAnalyticsTrackingForUser($userObj->id);
			}
			return[
				'success' => true,
				'message' => 'Your preferences were updated.  You can make changes to these preferences within your account settings.',
			];
		} else {
			$userCookiePost = [
				'Essential' => 1,
				'Analytics' => $_REQUEST['cookieAnalytics'],
				'UserLocalAnalytics' =>  isset($_POST['cookieUserLocalAnalytics']) ? 1 : 0,
			];
			setcookie('cookieConsent', json_encode($userCookiePost), 0, '/');
			return [
				'success' => true,
				'message' => '',
			];
		}
	}

	public function removeLocalAnalyticsTrackingForUser($userId) {
		require_once ROOT_DIR . '/sys/Summon/UserSummonUsage.php';
		require_once ROOT_DIR . '/sys/Axis360/UserAxis360Usage.php';
		require_once ROOT_DIR . '/sys/CloudLibrary/UserCloudLibraryUsage.php';
		require_once ROOT_DIR . '/sys/Ebsco/UserEbscoEdsUsage.php';
		require_once ROOT_DIR . '/sys/Ebsco/UserEbscohostUsage.php';
		require_once ROOT_DIR . '/sys/Hoopla/UserHooplaUsage.php';
		require_once ROOT_DIR . '/sys/OpenArchives/UserOpenArchivesUsage.php';
		require_once ROOT_DIR . '/sys/OverDrive/UserOverDriveUsage.php';
		require_once ROOT_DIR . '/sys/PalaceProject/UserPalaceProjectUsage.php';
		require_once ROOT_DIR . '/sys/Indexing/UserSideLoadUsage.php';
		require_once ROOT_DIR . '/sys/WebsiteIndexing/UserWebsiteUsage.php';
		require_once ROOT_DIR . '/sys/Events/UserEventsUsage.php';

		$userId = UserAccount::getActiveUserId();

		if ($userId) {
			$userSummonUsage = new UserSummonUsage();
			$userSummonUsage->userId = $userId;
			$userSummonUsage->find();
			while ($userSummonUsage->fetch()) {
				$userSummonUsage->delete();
			}

			$userAxis360Usage = new UserAxis360Usage();
			$userAxis360Usage->userId = $userId;
			$userAxis360Usage->find();
			while ($userAxis360Usage->fetch()) {
				$userAxis360Usage->delete();
			}

			$userCloudLibraryUsage = new UserCloudLibraryUsage();
			$userCloudLibraryUsage->userId = $userId;
			$userCloudLibraryUsage->find();
			while ($userCloudLibraryUsage->fetch()) {
				$userCloudLibraryUsage->delete();
			}

			$userEbscoEdsUsage = new UserEbscoEdsUsage();
			$userEbscoEdsUsage->userId = $userId;
			$userEbscoEdsUsage->find();
			while ($userEbscoEdsUsage->fetch()) {
				$userEbscoEdsUsage->delete();
			}

			$userEbscoHostUsage = new UserEbscohostUsage();
			$userEbscoHostUsage->userId = $userId;
			$userEbscoHostUsage->find();
			while ($userEbscoHostUsage->fetch()) {
				$userEbscoHostUsage->delete();
			}

			$userHooplaUsage = new UserHooplaUsage();
			$userHooplaUsage->userId = $userId;
			$userHooplaUsage->find();
			while ($userHooplaUsage->fetch()) {
				$userHooplaUsage->delete();
			}

			$userOpenArchivesUsage = new UserOpenArchivesUsage();
			$userOpenArchivesUsage->userId = $userId;
			$userOpenArchivesUsage->find();
			while ($userOpenArchivesUsage->fetch()) {
				$userOpenArchivesUsage->delete();
			}

			$userOverDriveUsage = new UserOverDriveUsage();
			$userOverDriveUsage->userId = $userId;
			$userOverDriveUsage->find();
			while ($userOverDriveUsage->fetch()) {
				$userOverDriveUsage->delete();
			}

			$userPalaceProjectUsage = new UserPalaceProjectUsage();
			$userPalaceProjectUsage->userId = $userId;
			$userPalaceProjectUsage->find();
			while ($userPalaceProjectUsage->fetch()) {
				$userPalaceProjectUsage->delete();
			}

			$userSideLoadUsage = new UserSideLoadUsage();
			$userSideLoadUsage->userId = $userId;
			$userSideLoadUsage->find();
			while ($userSideLoadUsage->fetch()) {
				$userSideLoadUsage->delete();
			}

			$userWebsiteUsage = new UserWebsiteUsage();
			$userWebsiteUsage->userId = $userId;
			$userWebsiteUsage->find();
			while ($userWebsiteUsage->fetch()) {
				$userWebsiteUsage->delete();
			}

			$userEventsUsage = new UserEventsUsage();
			$userEventsUsage->userId = $userId;
			$userEventsUsage->find();
			while ($userEventsUsage->fetch()) {
				$userEventsUsage->delete();
			}
		}
	}

	function getBreadcrumbs(): array {
		return [];
	}
}