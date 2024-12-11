<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';

require_once ROOT_DIR . '/sys/Account/UserMessage.php';
require_once ROOT_DIR . '/sys/YearInReview/YearInReviewSetting.php';

$today = strtotime("today");
$tomorrow = strtotime("tomorrow");
$yearInReviewSetting  = new YearInReviewSetting();
$yearInReviewSetting->whereAdd('endDate >= ' . $today);
$yearInReviewSetting->whereAdd('endDate < ' . $tomorrow);
$yearInReviewSetting->find();
if ($yearInReviewSetting->fetch()) {
	$userMessage = new UserMessage();
	$userMessage->messageType = 'yearInReview';
	$userMessage->isDismissed = 0;
	$userMessage->find();
	while ($userMessage->fetch()) {
		$userMessage->isDismissed = 1;
		$userMessage->update();
	}
}

global $aspen_db;
$aspen_db = null;

die();
