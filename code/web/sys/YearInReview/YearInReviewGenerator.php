<?php

function generateYearInReview(User $patron) : void {
	require_once ROOT_DIR . '/sys/YearInReview/UserYearInReview.php';
	require_once ROOT_DIR . '/sys/YearInReview/YearInReviewSetting.php';
	require_once ROOT_DIR . '/sys/LibraryLocation/LibraryYearInReview.php';

	if ($patron->hasIlsConnection()) {
		$userIsStaff = $patron->isStaff();

		global $library;
		$yearInReviewSetting  = new YearInReviewSetting();
		if ($userIsStaff) {
			$yearInReviewSetting->whereAdd('staffStartDate <= ' . time());
		}else{
			$yearInReviewSetting->whereAdd('patronStartDate <= ' . time());
		}
		$yearInReviewSetting->whereAdd('endDate >= ' . time());

		$libraryYearInReview = new LibraryYearInReview();
		$libraryYearInReview->libraryId = $library->libraryId;
		$yearInReviewSetting->joinAdd($libraryYearInReview, 'INNER', 'libraryYearInReview', 'id', 'yearInReviewId');

		if ($yearInReviewSetting->find(true)) {
			//We have valid settings
			$userYearInReview = new UserYearInReview();
			$userYearInReview->userId = $patron->id;
			$userYearInReview->settingId = $yearInReviewSetting->id;
			$reloadData = false;
			if (!$userYearInReview->find(true)) {
				$reloadData = true;
			}else{
				if (isset($_REQUEST['reload'])) {
					$reloadData = true;
					$userYearInReview->delete();
				}
			}

			if ($reloadData) {
				//We have not created year in review data for the user
				$readingHistorySize = $patron->getReadingHistorySizeForYear($yearInReviewSetting->year);

				if ($readingHistorySize >= 5) {
					$readingHistorySummary = $patron->getReadingHistorySummaryForYear($yearInReviewSetting->year);

					$userYearInReview->wrappedActive = true;
					$slidesToShow = [];
					//Calculate information for use in the display of Year in Review
					if ($yearInReviewSetting->year == 2024) {
						$yearInReviewData = new stdClass();
						$yearInReviewData->userData = [];
						$yearInReviewData->activeStyle = $yearInReviewSetting->style;

						/** @noinspection PhpIfWithCommonPartsInspection */
						if ($yearInReviewSetting->style == 0) {
							//Always show the first slide (intro)
							$slidesToShow[] = 1;
						}else {
							//Always show the two slides (intro)
							$slidesToShow[] = 1;
							$slidesToShow[] = 2;
						}

						//Borrower Experience
						$yearInReviewData->userData['totalCheckouts'] = $readingHistorySummary->totalYearlyCheckouts;
						if (!empty($readingHistorySummary->yearlyCostSavings)) {
							$yearInReviewData->userData['yearlyCostSavings'] = $readingHistorySummary->yearlyCostSavings;
							$slidesToShow[] = $yearInReviewSetting->style == 0 ? 2 : 3;
						}else{
							//Show the version with just the number of checkouts
							$slidesToShow[] = $yearInReviewSetting->style == 0 ? 3 : 4;
						}

						//Hot Month / Busy Months
						$isSteadyUser = false;
						if ($readingHistorySummary->maxMonthlyCheckouts - $readingHistorySummary->averageCheckouts > 2) {
							foreach ($readingHistorySummary->monthlyCheckouts as $month => $monthlyCheckoutCount) {
								if ($readingHistorySummary->topMonth != $month) {
									if ($readingHistorySummary->maxMonthlyCheckouts - $monthlyCheckoutCount < 2) {
										$isSteadyUser = true;
									}
								}
							}
						}
						if (!$isSteadyUser) {
							$dateObj   = DateTime::createFromFormat('!m', $readingHistorySummary->topMonth);
							$monthName = $dateObj->format('F');
							$yearInReviewData->userData['topMonth'] = $monthName;
							$slidesToShow[] = $yearInReviewSetting->style == 0 ? 4 : 5;
						}else{
							$yearInReviewData->userData['averageCheckouts'] = $readingHistorySummary->averageCheckouts;
							$slidesToShow[] = $yearInReviewSetting->style == 0 ? 5 : 6;
						}

						//Top formats
						if (!empty($readingHistorySummary->topFormats) && count($readingHistorySummary->topFormats) >= 3) {
							$yearInReviewData->userData['topFormats'] = join("\n", $readingHistorySummary->topFormats);
							$formatNames = array_values($readingHistorySummary->topFormats);
							$yearInReviewData->userData['topFormat1'] = $formatNames[0];
							$yearInReviewData->userData['topFormat2'] = count($formatNames) > 1 ?  $formatNames[1] : '';
							$yearInReviewData->userData['topFormat3'] = count($formatNames) > 2 ?  $formatNames[2] : '';
							$slidesToShow[] = $yearInReviewSetting->style == 0 ? 6 : 7;
						}

						//Top genres
						if (!empty($readingHistorySummary->topGenres)) {
							if (count($readingHistorySummary->topGenres) == 1){
								$yearInReviewData->userData['topGenres'] = $readingHistorySummary->topGenres[0];
							}elseif (count($readingHistorySummary->topGenres) == 2){
								$yearInReviewData->userData['topGenres'] = join("\n", [
									$readingHistorySummary->topGenres[0],
									'and',
									$readingHistorySummary->topGenres[1]
								]);
							}elseif(count($readingHistorySummary->topGenres) == 3){
								$yearInReviewData->userData['topGenres'] = join("\n", [
									$readingHistorySummary->topGenres[0] . ', ',
									$readingHistorySummary->topGenres[1]. ', ',
									'and',
									$readingHistorySummary->topGenres[2]
								]);
							}
							$slidesToShow[] = $yearInReviewSetting->style == 0 ? 7 : 8;
						}

						//Top author
						if (!empty($readingHistorySummary->topAuthor)) {
							$yearInReviewData->userData['topAuthor'] = $readingHistorySummary->topAuthor;
							$slidesToShow[] = $yearInReviewSetting->style == 0 ? 8 : 9;
						}

						//Top series
						if ($readingHistorySummary->topSeries) {
							$yearInReviewData->userData['topSeries'] = join(" and ", $readingHistorySummary->topSeries);
							$slidesToShow[] = $yearInReviewSetting->style == 0 ? 9 : 10;
						}

						//Recommendations
						if ($readingHistorySummary->recommendations) {
							$yearInReviewData->userData['recommendations'] = join("\n\n", $readingHistorySummary->recommendations);
							$slidesToShow[] = $yearInReviewSetting->style == 0 ? 10: 11;
						}

						//Always show the last slide
						$slidesToShow[] = $yearInReviewSetting->style == 0 ?  11 : 12;

						$yearInReviewData->numSlidesToShow = count($slidesToShow);
						$yearInReviewData->slidesToShow = $slidesToShow;
						$userYearInReview->wrappedResults = json_encode($yearInReviewData);
					}
				}else{
					$userYearInReview->wrappedActive = false;
					$userYearInReview->wrappedResults = '';
				}
				$userYearInReview->wrappedViewed = 0;

				$userYearInReview->insert();

				if ($userYearInReview->wrappedActive) {
					//Create a user message for the user
					require_once ROOT_DIR . '/sys/Account/UserMessage.php';
					//First check for and dismiss existing Year in Review messages
					$userMessage = new UserMessage();
					$userMessage->userId = UserAccount::getActiveUserId();
					$userMessage->messageType = 'yearInReview';
					$userMessage->isDismissed = 0;
					$userMessage->find();
					while ($userMessage->fetch()) {
						$userMessage->isDismissed = 1;
						$userMessage->update();
					}
					$userMessage = new UserMessage();
					$userMessage->userId = UserAccount::getActiveUserId();
					$promoMessage = $yearInReviewSetting->getTextBlockTranslation('promoMessage', $patron->interfaceLanguage);
					// Remove paragraph tags if it's not multi-paragraph to improve button wrapping
					if (substr_count($promoMessage, "<p>") == 1) {
						$promoMessage = preg_replace('/(<p>)|(<\/p>)/', '', $promoMessage);
					}
					$userMessage->message = $promoMessage;
					$userMessage->messageType = 'yearInReview';
					$userMessage->relatedObjectId = $userYearInReview->id;
					$userMessage->action1 = "return AspenDiscovery.Account.viewYearInReview(1)";
					$userMessage->action1Title = 'Yes';
					$userMessage->action2Title = 'Dismiss';
					$userMessage->isDismissed = 0;
					$userMessage->insert();
					$userMessage->action2 = "return AspenDiscovery.Account.dismissMessage({$userMessage->id})";
					$userMessage->update();
				}
			}
		}
	}
}
