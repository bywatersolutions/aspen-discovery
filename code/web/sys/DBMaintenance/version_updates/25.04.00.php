<?php

function getUpdates25_04_00(): array {
	$curTime = time();
	return [
		/*'name' => [
			 'title' => '',
			 'description' => '',
			 'continueOnError' => false,
			 'sql' => [
				 ''
			 ]
		 ], //name*/

		//mark - Grove
		'restrict_local_ill_by_patron_type' => [
			'title' => 'Restrict Local ILL by Patron Type',
			'description' => 'Add an option to restrict local ILL by Patron Type',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE ptype ADD COLUMN allowLocalIll TINYINT DEFAULT  1'
			]
		], //restrict_local_ill_by_patron_type
		'force_regrouping_all_works_25_04' => [
			'title' => 'Force Regrouping All Works 25.04',
			'description' => 'Force Regrouping All Works',
			'sql' => [
				"UPDATE system_variables set regroupAllRecordsDuringNightlyIndex = 1",
			],
		], //force_regrouping_all_works_25_04
		'make_local_ill_form_note_optional' => [
			'title' => 'Make Local ILL Form Note Optional',
			'description' => 'Make Local ILL Form Note Optional',
			'sql' => [
				'ALTER TABLE local_ill_form ADD COLUMN showNote TINYINT DEFAULT  1'
			]
		], //make_local_ill_form_note_optional
		'theme_app_header_options' => [
			'title' => 'Theme - App Header Options',
			'description' => 'Add additional options for configuring the app header',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE themes ADD COLUMN headerLogoAlignmentApp TINYINT(1) DEFAULT 2',
				"ALTER TABLE themes ADD COLUMN headerLogoBackgroundColorApp char(7) DEFAULT '#ffffff'",
				"ALTER TABLE themes ADD COLUMN headerLogoBackgroundColorAppDefault TINYINT(1) DEFAULT 1"
			]
		], //theme_app_header_options
		'increase_series_member_field_lengths' => [
			'title' => 'Increase Series Member Field Lengths',
			'description' => 'Increase Series Member Field Lengths',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE series_member CHANGE COLUMN displayName displayName VARCHAR(750)',
				'ALTER TABLE series_member CHANGE COLUMN volume volume VARCHAR(100)',
			]
		], //increase_series_member_field_lengths

		//katherine - Grove
		'add_location_to_aspen_events_settings' => [
			'title' => 'Add Location to Aspen Events Settings',
			'description' => 'Add location_events_setting table so that settings can be linked to specific locations',
			'sql' => [
				"CREATE TABLE location_events_setting (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					settingId INT NOT NULL,
					locationId INT NOT NULL
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci",
				"ALTER TABLE events_indexing_settings ADD COLUMN name VARCHAR(100)"
			]
		], //add_location_to_aspen_events_settings

		//kirstien - Grove

		//kodi - Grove

		//Yanjun Li - ByWater
		'library_add_palace_project_library_id' => [
			'title' => 'library_add_palace_project_library_id',
			'description' => 'Add a field to store the palace project library id for the library',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE library add column palaceProjectLibraryId VARCHAR(50) DEFAULT NULL",
			]
		], //library_add_palace_project_library_id

		// Leo Stoyanov - BWS
		'remove_palace_project_regroup_flag' => [
			'title' => 'Remove Unused Palace Project Regroup Option',
			'description' => 'Remove regroupAllRecords column from palace_project_settings table as it is never used.',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE palace_project_settings DROP COLUMN IF EXISTS regroupAllRecords'
			]
		], //remove_palace_project_regroup_flag
		'fix_nyt_user_home_location' => [
			'title' => 'Fix NYT User Home Location',
			'description' => 'Set nyt_user home location to -1 to ensure NYT lists are visible in consortia when "Lists from library list publishers Only" is selected.',
			'continueOnError' => true,
			'sql' => [
				"UPDATE user SET homeLocationId = -1 WHERE username = 'nyt_user' AND source = 'admin'",
			],
		], //fix_nyt_user_home_location
		'move_list_images' => [
			'title' => 'Properly Move All List Images',
			'description' => "Move all list images to their own directory so they don't conflict with uploaded records' covers.",
			'sql'=> [
				'moveUploadedListImages'
			]
		], //move_list_images
		'ip_lookup_ipv6_support' => [
			'title' => 'Add Support for IPv6 Addresses',
			'description' => 'Add support for IPv6 addresses in ip_lookup table.',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE ip_lookup MODIFY startIpVal VARCHAR(255) NULL COMMENT 'Numeric value for IPv4 or encoded string for IPv6'",
				"ALTER TABLE ip_lookup MODIFY endIpVal VARCHAR(255) NULL COMMENT 'Numeric value for IPv4 or encoded string for IPv6'"
			],
		], //ip_lookup_ipv6_support
		'custom_form_field_enums_to_text' => [
			'title' => 'Increase Custom Form Field EnumValues Size',
			'description' => 'Changes the enumValues column in web_builder_custom_form_field from VARCHAR(255) to TEXT to allow for longer select lists.',
			'sql' => [
				"ALTER TABLE web_builder_custom_form_field MODIFY COLUMN enumValues TEXT DEFAULT NULL",
			]
		],
		'add_placard_image_max_height_to_themes' => [
			'title' => 'Add Placard Image Max Height to Themes',
			'description' => 'Adds a placardImageMaxHeight column to the themes table to control placard image height.',
			'sql' => [
				"ALTER TABLE themes ADD COLUMN IF NOT EXISTS `placardImageMaxHeight` INT DEFAULT 0",
			]
		], //add_placard_image_max_height_to_themes

		//alexander - PTFS-Europe

		//chloe - PTFS-Europe

		//James Staub - Nashville Public Library

		//Lucas Montoya - Theke Solutions

		//other

	];
}
