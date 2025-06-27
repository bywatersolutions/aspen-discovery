<?php

require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignMilestone.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignMilestoneProgressEntry.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/Campaign.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/UserCampaign.php';



/**
 * Check if a date falls within the campaign period
 *
 * @param int $date The date to check (unix timestamp)
 * @param Campaign $campaign The campaign object
 * @return bool True if date is within campaign period, false otherwise
 */
function _isDateWithinCampaignPeriod($date, $campaign) {
	$campaignStartDate = strtotime($campaign->startDate);
	$campaignEndDate = strtotime($campaign->endDate);

	return $date >= $campaignStartDate && $date <= $campaignEndDate;
}

/**
 * Process campaign milestone for a given object and date
 *
 * @param mixed $value The object being processed
 * @param string $objectType The type of object ('user_checkout', 'user_hold', 'user_work_review')
 * @param int $userId The user ID
 * @param int $date The date to check (unix timestamp)
 * @param mixed $groupedId The grouped work/record ID (optional)
 */
function _processCampaignMilestoneProgress($value, $objectType, $userId, $date, $groupedId = null) {
	$campaignMilestone = CampaignMilestone::getCampaignMilestonesToUpdate($value, $objectType, $userId);
	if (!$campaignMilestone) {
		return;
	}

	while ($campaignMilestone->fetch()) {
		$campaign = new Campaign();
		$campaign->id = $campaignMilestone->campaignId;
		if (!$campaign->find(true)) {
			continue;
		}

		if (!_isDateWithinCampaignPeriod($date, $campaign)) {
			continue;
		}

		if (_campaignMilestoneProgressEntryObjectAlreadyExists($value, $campaignMilestone)) {
			continue;
		}

		$campaignMilestone->addCampaignMilestoneProgressEntry($value, $userId, $groupedId);

		$userCampaign = new UserCampaign();
		$userCampaign->userId = $userId;
		$userCampaign->campaignId = $campaignMilestone->campaignId;
		$userCampaign->checkAndHandleCampaignCompletion($userId, $campaignMilestone->campaignId);
	}
}

/**
 * after_checkout_insert
 *
 * React to a new user_checkout being added to the database.
 * Add a new ce_milestone_progress_entry to be processed later if all conditions are met
 *
 * @param $value Checkout() object
 */

add_action('after_object_insert', 'after_checkout_insert', function ($value) {
	_processCampaignMilestoneProgress($value, 'user_checkout', $value->userId, $value->checkoutDate, $value->groupedWorkId);
});

/**;
 * after_hold_insert
 *
 * React to a new user_hold being added to the database.
 * Add a new ce_milestone_progress_entry to be processed later
 *
 * @param $value Hold() object
 */

add_action('after_object_insert', 'after_hold_insert', function ($value) {
	_processCampaignMilestoneProgress($value, 'user_hold', $value->userId, $value->createDate, $value->groupedWorkId);
});

/**
 * after_list_insert
 *
 * React to a new user_list being added to the database.
 * Add a new ce_milestone_progress_entry to be processed later
 *
 * @param $value UserList() object
 */

// add_action('after_object_insert', 'after_list_insert', function ($value) {
//     $campaignMilestone = CampaignMilestone::getCampaignMilestonesToUpdate($value, 'user_list', $value->user_id);
//     if (!$campaignMilestone)
//         return;

//     while ($campaignMilestone->fetch()) {
//         $campaignMilestone->addCampaignMilestoneProgressEntry($value, $value->user_id);
//     }
//     return;
// });

/**
 * after_work_review_insert
 *
 * React to a new user_work_review being added to the database.
 * Add a new ce_milestone_progress_entry to be processed later
 *
 * @param $value UserWorkReview() object
 */

add_action('after_object_insert', 'after_work_review_insert', function ($value) {
	_processCampaignMilestoneProgress($value, 'user_work_review', $value->userId, $value->dateRated, $value->groupedRecordPermanentId);
});

/**
 * Checks if an object entry already exists in the ce_milestone_progress_entries table, for a specific milestone.
 * This check is required because a some objects being added to the database may not actually be a instance.
 * For example, for checkouts and holds, these may be purged from the database and re-fetched from the ILS.
 *
 * @param object $value The object containing the sourceId, recordId, and userId.
 * @param CampaignMilestone $campaignMilestone The milestone object.
 * @return bool Returns true if an entry exists, false otherwise.
 */
function _campaignMilestoneProgressEntryObjectAlreadyExists($value, $campaignMilestone)
{
	$campaignMilestoneProgressEntryCheck = new CampaignMilestoneProgressEntry();
	$campaignMilestoneProgressEntryCheck->initialize($campaignMilestone);
	if ($campaignMilestoneProgressEntryCheck->find()) {
		while ($campaignMilestoneProgressEntryCheck->fetch()) {
			$decoded_object = json_decode($campaignMilestoneProgressEntryCheck->object);
			if (
				$decoded_object->sourceId == $value->sourceId &&
				$decoded_object->recordId == $value->recordId &&
				$decoded_object->userId == $value->userId
			) {
				return true;
			}
		}
	}
	return false;
}



