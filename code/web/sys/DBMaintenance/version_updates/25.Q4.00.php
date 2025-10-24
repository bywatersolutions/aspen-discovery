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

		'add_enable_third_party_sms_notifications_option' => [
			'title' => 'Add "Enable Third Party SMS Notifications" Option',
			'description' => 'Add "Enable Third Party SMS Notifications" option for CarlX to Library System settings.',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE library ADD COLUMN IF NOT EXISTS enableThirdPartySMSNotifications TINYINT(1) DEFAULT 0'
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
				'ALTER TABLE grouped_work_display_settings ADD COLUMN IF NOT EXISTS showCopiesForPeriodicalsWithNoItems TINYINT(1) DEFAULT 0'
			]
		], //add_show_copies_for_periodicals_with_no_iems_setting
		'add_hoopla_configurable_indexing_time' => [
			'title' => 'Add Configurable Hoopla Indexing Time',
			'description' => 'Add Hoopla Indexing Time',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE hoopla_settings ADD COLUMN IF NOT EXISTS indexingTime INT DEFAULT 1',
			]
		], //add_hoopla_configurable_indexing_time
		'remove_request_tracker_tables' => [
			'title' => 'Remove Request Tracker Database Tables',
			'description' => 'Drop all database tables related to the Request Tracker implementation.',
			'continueOnError' => true,
			'sql' => [
				'DROP TABLE IF EXISTS component_ticket_link',
				'DROP TABLE IF EXISTS development_task_ticket_link',
				'DROP TABLE IF EXISTS request_tracker_connection',
				'DROP TABLE IF EXISTS ticket',
				'DROP TABLE IF EXISTS ticket_component_feed',
				'DROP TABLE IF EXISTS ticket_queue_feed',
				'DROP TABLE IF EXISTS ticket_severity_feed',
				'DROP TABLE IF EXISTS ticket_status_feed',
				'DROP TABLE IF EXISTS ticket_trend_bugs_by_severity',
				'DROP TABLE IF EXISTS ticket_trend_by_component',
				'DROP TABLE IF EXISTS ticket_trend_by_partner',
				'DROP TABLE IF EXISTS ticket_trend_by_queue'
			]
		], // remove_request_tracker_tables
		'remove_request_tracker_permissions' => [
			'title' => 'Remove Request Tracker Permissions',
			'description' => 'Remove permissions and role assignments related to the Request Tracker implementation.',
			'continueOnError' => true,
			'sql' => [
				'DELETE FROM role_permissions WHERE permissionId IN (SELECT id FROM permissions WHERE name IN ("Submit Ticket", "Administer Request Tracker Connection", "View Active Tickets", "Set Development Priorities"))',
				'DELETE FROM permissions WHERE name IN ("Submit Ticket", "Administer Request Tracker Connection", "View Active Tickets", "Set Development Priorities")',
				'DROP TABLE IF EXISTS development_priorities'
			]
		], // remove_request_tracker_permissions
		'remove_request_tracker_greenhouse_settings' => [
			'title' => 'Remove Request Tracker Greenhouse Settings',
			'description' => 'Remove Request Tracker fields from greenhouse_settings table.',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE greenhouse_settings DROP COLUMN IF EXISTS requestTrackerBaseUrl',
				'ALTER TABLE greenhouse_settings DROP COLUMN IF EXISTS requestTrackerAuthToken'
			]
		], //remove_request_tracker_greenhouse_settings
		'remove_ticket_email_system_variable' => [
			'title' => 'Remove Ticket Email System Variable',
			'description' => 'Remove ticketEmail column from system_variables table.',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE system_variables DROP COLUMN IF EXISTS ticketEmail'
			]
		], // remove_ticket_email_system_variable
		'themes_show_button_shimmer' => [
			'title' => 'Themes - Show Button Shimmer',
			'description' => 'Add showButtonShimmer setting to themes table to allow libraries to disable shimmer effect on circulation buttons.',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE themes ADD COLUMN IF NOT EXISTS showButtonShimmer TINYINT(1) DEFAULT 1',
			]
		], // themes_show_button_shimmer
		'increase_length_of_library_email_for_custom_forms' => [
			'title' => 'Increase length of library email for custom forms',
			'description' => 'Increase length of library email for custom forms',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library_web_builder_custom_form CHANGE COLUMN emailResultsTo emailResultsTo varchar(250) DEFAULT ""',
				'ALTER TABLE web_builder_custom_form CHANGE COLUMN emailResultsTo emailResultsTo varchar(250) DEFAULT ""'
			]
		], //increase_length_of_library_email_for_custom_forms

	];
}