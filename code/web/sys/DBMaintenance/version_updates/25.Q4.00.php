<?php

/** @noinspection PhpUnused */
function getUpdates25_Q4_00(): array {
	return [
		/*'name' => [
			 'title' => '',
			 'description' => '',
			 'continueOnError' => false,
			 'sql' => [
				 ''
			 ]
		 ], //name*/

		// Leo Stoyanov - BWS
		'add_enable_third_party_sms_notifications_option' => [
			'title' => 'Add "Enable Third Party SMS Notifications" Option',
			'description' => 'Add "Enable Third Party SMS Notifications" option for CarlX to Library System settings.',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE library ADD COLUMN enableThirdPartySMSNotifications TINYINT(1) DEFAULT 0'
			],
		], // add_enable_third_party_sms_notifications_option
		'add_indexes_for_more_user_list_sort_options' => [
			'title' => 'Add Indexes For More User List Sort Options',
			'description' => 'Add indexes idx_publicationDateId and idx_callNumberId for faster User List sorting.',
			'continueOnError' => false,
			'sql' => [
				'CREATE INDEX idx_publicationDateId ON grouped_work_records(publicationDateId)',
				'CREATE INDEX idx_callNumberId ON grouped_work_record_items (callNumberId)'
			],
		], // add_indexes_for_more_user_list_sort_options
		'add_show_copies_for_periodicals_with_no_iems_setting' => [
			'title' => 'Add Show Copies for Periodicals with No Items Setting',
			'description' => 'Add a setting to control whether Copies accordion is shown for periodicals with no items.',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE grouped_work_display_settings ADD COLUMN showCopiesForPeriodicalsWithNoItems TINYINT(1) DEFAULT 0'
			]
		], //add_show_copies_for_periodicals_with_no_iems_setting

		//Yanjun Li - ByWater
		'add_hoopla_configurable_indexing_time' => [
			'title' => 'Add Configurable Hoopla Indexing Time',
			'description' => 'Add Hoopla Indexing Time',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE hoopla_settings ADD COLUMN indexingTime INT DEFAULT 1',
			]
		], //add_hoopla_configurable_indexing_time

	];
}