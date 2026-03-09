<?php

require_once ROOT_DIR . '/JSON_Action.php';

class Gale_JSON extends JSON_Action {
	/** @noinspection PhpUnused */
	public function trackGaleUsage(): array {
		if (!isset($_REQUEST['id'])) {
			return [
				'success' => false,
				'message' => 'ID was not provided',
			];
		}
		$id = $_REQUEST['id'];

		require_once ROOT_DIR . '/sys/Gale/GaleRecordUsage.php';
		$galeRecordUsage = new GaleRecordUsage();
		global $aspenUsage;
		$galeRecordUsage->instance = $aspenUsage->getInstance();
		$galeRecordUsage->galeId = $id;
		$galeRecordUsage->year = date('Y');
		$galeRecordUsage->month = date('n');
		if ($galeRecordUsage->find(true)) {
			$galeRecordUsage->timesUsed++;
			$ret = $galeRecordUsage->update();
			if ($ret == 0) {
				echo ("Unable to update times used");
			}
		} else {
			$galeRecordUsage->timesViewedInSearch = 0;
			$galeRecordUsage->timesUsed = 1;
			$galeRecordUsage->insert();
		}

		$userId = UserAccount::getActiveUserId();
		if ($userId) {
			$userObj = UserAccount::getActiveUserObj();
			$userGaleTracking = $userObj->userCookiePreferenceLocalAnalytics;
			if ($userGaleTracking) {
				require_once ROOT_DIR . '/sys/Gale/UserGaleUsage.php';
				$userGaleUsage = new UserGaleUsage();
				$userGaleUsage->instance = $aspenUsage->getInstance();
				$userGaleUsage->userId = $userId;
				$userGaleUsage->year = date('Y');
				$userGaleUsage->month = date('n');

				if ($userGaleUsage->find(true)) {
					$userGaleUsage->usageCount++;
					$userGaleUsage->update();
				} else {
					$userGaleUsage->usageCount = 1;
					$userGaleUsage->insert();
				}
			}
		}

		return [
			'success' => true,
			'message' => 'Updated usage for Gale record ' . $id,
		];
	}

	function getTitleAuthor(): array {
		$result = [
			'success' => false,
			'title' => 'Unknown',
			'author' => 'Unknown',
		];
		require_once ROOT_DIR . '/RecordDrivers/GaleRecordDriver.php';
		$id = $_REQUEST['id'];
		if (!empty($id)) {
			$recordDriver = new GaleRecordDriver($id);
			if ($recordDriver->isValid()) {
				$result['success'] = true;
				$result['title'] = $recordDriver->getTitle();
				$result['author'] = $recordDriver->getAuthor();
			}
		}
		return $result;
	}
}
