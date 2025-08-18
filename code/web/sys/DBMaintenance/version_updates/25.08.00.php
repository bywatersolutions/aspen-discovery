<?php

function getUpdates25_08_00(): array {
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
		'library_local_ill_email' => [
			'title' => 'Library - Local ILL Email',
			'description' => 'Add Local ILL Email to Library Settings',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE library ADD COLUMN localIllEmail varchar(255) default ''"
			]
		], //library_local_ill_email
		'materials_request_add_source' => [
			'title' => 'Materials Request - Add Source',
			'description' => 'Add Source to Materials Request to differentiate between Local ILL and standard requests',
			'sql' => [
				"ALTER TABLE materials_request ADD COLUMN source TINYINT DEFAULT 1"
			]
		], //materials_request_add_source
		'manually_run_cron_permission' => [
			'title' => 'Add Manually Run Cron Permission',
			'description' => 'Add permission to manually run cron',
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES 
					 ('System Administration', 'Manually Run Cron Processes', '', 60, 'Allows the user to manually start cron processes.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Manually Run Cron Processes'))",
			]
		], //manually_run_cron_permission
		'add_cron_name' => [
			'title' => 'Add Cron Name',
			'description' => 'Add Name to cron table',
			'sql' => [
				"ALTER TABLE cron_log ADD COLUMN name varchar(255) default 'Primary Cron'"
			]
		], //add_cron_name

		//katherine - Grove
		'sierra_self_reg_enhancement_settings' => [
			'title' => 'Add new settings for Sierra Self Registration',
			'description' => 'Add new settings to Sierra Self Registration',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE self_registration_form_sierra ADD COLUMN selfRegNoDuplicateCheck TINYINT(1) DEFAULT 0;',
				'ALTER TABLE self_registration_form_sierra ADD COLUMN selfRegUseAgency TINYINT(1) DEFAULT 0;',
				'ALTER TABLE self_registration_form_sierra ADD COLUMN selfRegUsePatronIdBarcode TINYINT(1) DEFAULT 0;',
				'ALTER TABLE self_registration_form_sierra ADD COLUMN selfRegNoticePrefOptions VARCHAR(255) DEFAULT "";',
				'ALTER TABLE self_registration_tos ADD COLUMN showTOSFirst TINYINT(1) DEFAULT 0;',
				'ALTER TABLE library ADD COLUMN logSelfRegistrations TINYINT(1) DEFAULT 0;',
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES
					('Cataloging & eContent', 'Manage Self Registration Municipalities', '', 23, 'Allows the user to alter self registration form municipality settings for all libraries');",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES
					('Cataloging & eContent', 'Review Self Registrations for All Libraries', '', 25, 'Allows the user to review and approve self registrations for all libraries');",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES
					('Cataloging & eContent', 'Review Self Registrations for Home Library Only', '', 25, 'Allows the user to review and approve self registrations for their home library');",
				"INSERT INTO `permission_groups` (`groupKey`,`sectionName`,`label`,`description`) VALUES
					('adminReviewRegistrations','Cataloging & eContent','Review Self Registrations','Specify whether the role can review all registrations or only those for its home library.');",
				"INSERT INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Review Self Registrations for Home Library Only','Review Self Registrations for All Libraries') WHERE pg.groupKey = 'adminReviewRegistrations';",
				"CREATE TABLE IF NOT EXISTS self_reg_municipality_values_sierra (
					`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					`selfRegistrationFormId` int(11) NOT NULL,
					`municipality` varchar(255) default '' NOT NULL,
					`municipalityType` varchar(10),
					`selfRegAllowed` tinyint(1) NOT NULL DEFAULT '1',
					`sierraPType` int(11) DEFAULT NULL,
					`sierraPTypeApproved` int(11) DEFAULT NULL,
					`sierraPCode1` varchar(25) DEFAULT NULL,
					`sierraPCode2` varchar(25) DEFAULT NULL,
					`sierraPCode3` int DEFAULT NULL,
					`sierraPCode4` int DEFAULT NULL,					
					`expirationLength` tinyint,
					`expirationPeriod` char DEFAULT 'd'
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
				"CREATE TABLE IF NOT EXISTS self_registration_sierra (
					`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					`patronId` int(11) NOT NULL,
					`dateRegistered` datetime NOT NULL DEFAULT current_timestamp,
					`barcode` varchar(255) NOT NULL,
					`sierraPType` int(11) DEFAULT NULL,
					`sierraPTypeApproved` int(11) DEFAULT NULL,
					`sierraPCode1` varchar(25) DEFAULT NULL,
					`sierraPCode2` varchar(25) DEFAULT NULL,
					`sierraPCode3` int DEFAULT NULL,
					`sierraPCode4` int DEFAULT NULL,
					`libraryId` int(11) DEFAULT NULL,			
					`locationId` int(11) DEFAULT NULL,
					`approved` tinyint(1) NOT NULL DEFAULT '0'
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
			]
		], //sierra_self_reg_enhancements

		//kirstien - Grove

		//kodi - Grove
		'library_you_might_also_like' => [
			'title' => 'Library You Might Also Like Setting',
			'description' => 'Add a setting for libraries for the "You Might Also Like" feature to disable or enable with restrictions.',
			'sql' => [
				"ALTER TABLE library ADD COLUMN showYouMightAlsoLike TINYINT(1) DEFAULT 1;",
				"UPDATE library SET showYouMightAlsoLike =0 WHERE showWhileYouWait=0;"
			],
		], //library_you_might_also_like
		// Myranda - Grove

		//Yanjun Li - ByWater

		// Leo Stoyanov - BWS
		'object_restoration_permission' => [
			'title' => 'Add Object Restoration Permission',
			'description' => 'Add new permission to allow administrators to restore soft-deleted objects.',
			'continueOnError' => true,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('System Administration', 'Administer Object Restoration', '', 13, 'Allows the user to view and restore soft-deleted objects (e.g., User Lists) within Aspen.')",
				"INSERT INTO role_permissions (roleId, permissionId) SELECT (SELECT roleId FROM roles WHERE name='opacAdmin'), (SELECT id FROM permissions WHERE name='Administer Object Restoration') WHERE NOT EXISTS (SELECT 1 FROM role_permissions WHERE roleId = (SELECT roleId FROM roles WHERE name='opacAdmin') AND permissionId = (SELECT id FROM permissions WHERE name='Administer Object Restoration'))",
			],
		],// object_restoration_permission
		'add_soft_delete_columns' => [
			'title' => 'Add Soft-Delete Columns to Supported Tables',
			'description' => 'Ensure tables for soft-deletable objects have deleted and dateDeleted columns.',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE user_list ADD COLUMN IF NOT EXISTS deleted TINYINT(1) DEFAULT 0",
				"ALTER TABLE user_list ADD COLUMN IF NOT EXISTS dateDeleted INT(11) DEFAULT 0",
				"ALTER TABLE user_list ADD COLUMN IF NOT EXISTS deletedBy INT(11) DEFAULT NULL",
				"ALTER TABLE user_list ADD COLUMN IF NOT EXISTS deleteFromIndex TINYINT(1) DEFAULT 0",
				"ALTER TABLE user_list_entry ADD COLUMN IF NOT EXISTS deleted TINYINT(1) DEFAULT 0",
				"ALTER TABLE user_list_entry ADD COLUMN IF NOT EXISTS dateDeleted INT(11) DEFAULT 0",
				"ALTER TABLE user_list_entry ADD COLUMN IF NOT EXISTS deletedBy INT(11) DEFAULT NULL",
				"ALTER TABLE web_builder_basic_page ADD COLUMN IF NOT EXISTS deleted TINYINT(1) DEFAULT 0",
				"ALTER TABLE web_builder_basic_page ADD COLUMN IF NOT EXISTS dateDeleted INT(11) DEFAULT 0",
				"ALTER TABLE web_builder_basic_page ADD COLUMN IF NOT EXISTS deletedBy INT(11) DEFAULT NULL",
				"ALTER TABLE web_builder_portal_page ADD COLUMN IF NOT EXISTS deleted TINYINT(1) DEFAULT 0",
				"ALTER TABLE web_builder_portal_page ADD COLUMN IF NOT EXISTS dateDeleted INT(11) DEFAULT 0",
				"ALTER TABLE web_builder_portal_page ADD COLUMN IF NOT EXISTS deletedBy INT(11) DEFAULT NULL",
				"ALTER TABLE web_builder_custom_form ADD COLUMN IF NOT EXISTS deleted TINYINT(1) DEFAULT 0",
				"ALTER TABLE web_builder_custom_form ADD COLUMN IF NOT EXISTS dateDeleted INT(11) DEFAULT 0",
				"ALTER TABLE web_builder_custom_form ADD COLUMN IF NOT EXISTS deletedBy INT(11) DEFAULT NULL",
				"ALTER TABLE web_builder_custom_web_resource_page ADD COLUMN IF NOT EXISTS deleted TINYINT(1) DEFAULT 0",
				"ALTER TABLE web_builder_custom_web_resource_page ADD COLUMN IF NOT EXISTS dateDeleted INT(11) DEFAULT 0",
				"ALTER TABLE web_builder_custom_web_resource_page ADD COLUMN IF NOT EXISTS deletedBy INT(11) DEFAULT NULL",
				"ALTER TABLE web_builder_resource ADD COLUMN IF NOT EXISTS deleted TINYINT(1) DEFAULT 0",
				"ALTER TABLE web_builder_resource ADD COLUMN IF NOT EXISTS dateDeleted INT(11) DEFAULT 0",
				"ALTER TABLE web_builder_resource ADD COLUMN IF NOT EXISTS deletedBy INT(11) DEFAULT NULL",
				"ALTER TABLE image_uploads ADD COLUMN IF NOT EXISTS deleted TINYINT(1) DEFAULT 0",
				"ALTER TABLE image_uploads ADD COLUMN IF NOT EXISTS dateDeleted INT(11) DEFAULT 0",
				"ALTER TABLE image_uploads ADD COLUMN IF NOT EXISTS deletedBy INT(11) DEFAULT NULL",
				"ALTER TABLE file_uploads ADD COLUMN IF NOT EXISTS deleted TINYINT(1) DEFAULT 0",
				"ALTER TABLE file_uploads ADD COLUMN IF NOT EXISTS dateDeleted INT(11) DEFAULT 0",
				"ALTER TABLE file_uploads ADD COLUMN IF NOT EXISTS deletedBy INT(11) DEFAULT NULL",
				"ALTER TABLE placards ADD COLUMN IF NOT EXISTS deleted TINYINT(1) DEFAULT 0",
				"ALTER TABLE placards ADD COLUMN IF NOT EXISTS dateDeleted INT(11) DEFAULT 0",
				"ALTER TABLE placards ADD COLUMN IF NOT EXISTS deletedBy INT(11) DEFAULT NULL",
			],
		],// add_soft_delete_columns
		'update_browse_category_sort_options' => [
			'title' => 'Update Browse Category Sort Options for Lists Search',
			'description' => 'Add new date sorting options for browse categories when using Lists as search source.',
			'sql' => [
				"ALTER TABLE browse_category MODIFY COLUMN defaultSort ENUM('relevance','popularity','newest_to_oldest','author','title','user_rating','holds','publication_year_desc','publication_year_asc','event_date','oldest_to_newest','newest_updated_to_oldest','oldest_updated_to_newest') DEFAULT 'relevance'"
			]
		], //update_browse_category_sort_options
		'update_collection_spotlight_sort_options' => [
			'title' => 'Update Collection Spotlight Sort Options for Lists Search',
			'description' => 'Add new date sorting options for collection spotlights when using Lists as search source.',
			'sql' => [
				"ALTER TABLE collection_spotlight_lists MODIFY COLUMN defaultSort ENUM('relevance','popularity','newest_to_oldest','author','title','user_rating','holds','publication_year_desc','publication_year_asc','event_date','oldest_to_newest','newest_updated_to_oldest','oldest_updated_to_newest') DEFAULT 'relevance'"
			]
		], //update_collection_spotlight_sort_options
		'add_allow_material_requests_branch_choice_setting' => [
			'title' => 'Add Allow Material Requests Branch Choice Setting',
			'description' => 'Add "Allow Material Requests Branch Choice" setting for the ILS Request System.',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library ADD COLUMN IF NOT EXISTS allowMaterialRequestsBranchChoice tinyint(1) DEFAULT 0;'
			]
		],//add_allow_material_requests_branch_choice_setting
		'update_record_to_include_defaults' => [
			'title' => 'Update RecordToInclude Column Defaults to Match PHP Defaults',
			'description' => 'Update database column defaults for includeHoldableOnly, includeItemsOnOrder, and includeEContent to match the PHP class defaults.',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library_records_to_include CHANGE COLUMN includeHoldableOnly includeHoldableOnly tinyint(1) NOT NULL DEFAULT 0',
				'ALTER TABLE library_records_to_include CHANGE COLUMN includeItemsOnOrder includeItemsOnOrder tinyint(1) NOT NULL DEFAULT 1',
				'ALTER TABLE library_records_to_include CHANGE COLUMN includeEContent includeEContent tinyint(1) NOT NULL DEFAULT 1',
				'ALTER TABLE location_records_to_include CHANGE COLUMN includeHoldableOnly includeHoldableOnly tinyint(1) NOT NULL DEFAULT 0',
				'ALTER TABLE location_records_to_include CHANGE COLUMN includeItemsOnOrder includeItemsOnOrder tinyint(1) NOT NULL DEFAULT 1',
				'ALTER TABLE location_records_to_include CHANGE COLUMN includeEContent includeEContent tinyint(1) NOT NULL DEFAULT 1',
			]
		], //update_record_to_include_defaults
		'add_updating_contact_info_from_ils' => [
			'title' => 'Add the Option to "Automatically Update Contact Information from the ILS"',
			'description' => 'Add the Option to "Automatically Update Contact Information from the ILS" ',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE location ADD COLUMN allowUpdatingContactInfoFromILS TINYINT(1) DEFAULT 0",
			],
		], // add_updating_contact_info_from_ils
		'add_max_hold_cancellation_date_field' => [
			'title' => 'Add Max Hold Cancellation Date Field',
			'description' => 'Add the Max Hold Cancellation Date field for when hold cancellations are enabled.',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE library ADD COLUMN IF NOT EXISTS maxHoldCancellationDate int(11) DEFAULT -1 AFTER defaultNotNeededAfterDays;'
			]
		], //add_max_hold_cancellation_date_field
		'log_frequent_crons_system_variable' => [
			'title' => 'Add Log Frequent Crons System Variable',
			'description' => 'Add system variable to control logging of frequently running cron jobs.',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE system_variables ADD COLUMN logFrequentCrons TINYINT(1) DEFAULT 0"
			]
		], //log_frequent_crons_system_variable

		// Laura Escamilla - ByWater Solutions

		//alexander - Open Fifth
		'control_display_of_user_dropdown_in_community_engagement_admin_view' => [
			'title' => 'Control User Select Type in Admin View',
			'description' => 'Add options for how to select users in the admin view section',
			'sql' => [
				"ALTER TABLE library ADD COLUMN communityEngagementAdminUserSelect VARCHAR(20) DEFAULT 'dropdown'",
			],
		], //control_display_of_user_dropdown_in_community_engagement_admin_view
		'display_only_users_from_current_library_in_user_search_admin_view' => [
			'title' => 'Display Only Users From Current Library in User Search Admin View',
			'description' => 'Add option to display users from all libraries or only the current library location when searching by user in Admin View',
			'sql' => [
				"ALTER TABLE library ADD COLUMN displayOnlyUsersForLocationInUserAdmin TINYINT(1) DEFAULT 0",
			],
		], //display_only_users_from_current_library_in_user_search_admin_view
		'allow_admin_to_enroll_users_via_admin_view' => [
			'title' => 'Allow Admin To Enroll Users Via Admin View',
			'description' => 'Add control over whether admin can enroll users via the admin view page',
			'sql' => [
				"ALTER TABLE library ADD COLUMN allowAdminToEnrollUsersInAdminView TINYINT(1) DEFAULT 0",
			],
		], //allow_admin_to_enroll_users_via_admin_view
		'add_admin_control_over_digital_reward_display' => [
			'title' => 'Add Admin Control Over Digital Reward Display',
			'description' => 'Add the option for libraries to choose whether the digital reward displays all the time or only once awarded',
			'sql' => [
				"ALTER TABLE library ADD COLUMN displayDigitalRewardOnlyWhenAwarded TINYINT(1) DEFAULT 0",
			]
		], //add_admin_control_over_digital_reward_display
		'add_ability_to_upload_placeholder_image' => [
			'title' => 'Add Ability to Upload Placeholder Image',
			'description' => 'Add the ability to upload a placeholder image',
			'sql' => [
				"ALTER TABLE library ADD COLUMN digitalRewardPlaceholderImage VARCHAR(100) DEFAULT ''",
			]
		], //add_ability_to_upload_placeholder_image
		'add_table_for_extra_credit' => [
			'title' => 'Add Table For Extra Credit',
			'description' => 'Add a table to for extra credit activites',
			'sql' => [
				"CREATE TABLE ce_extra_credit (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					name VARCHAR(100) NOT NULL, 
					description VARCHAR(225),
					allowPatronProgressInput TINYINT DEFAULT 0
				)ENGINE = InnoDB"
			],
		], //add_table_for_extra_credit
		'add_extra_credit_to_campaigns' => [
			'title' => 'Add Extra Credit to Campaigns',
			'description' => 'Add the ability to add extra credit activities to campaigns',
			'sql' => [
				"ALTER TABLE ce_campaign ADD COLUMN addExtraCreditActivities TINYINT DEFAULT 0 "
			],
		], //add_extra_credit_to_campaigns
		'add_campaign_extra_credit_activities' => [
			'title' => 'Add Campaign Extra Credit Activities',
			'description' => 'Add a new table to link campaigns and extra credit activities',
			'sql' => [
				"CREATE TABLE ce_campaign_extra_credit (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					weight INT(11) NOT NULL DEFAULT 0,
					campaignId INT NOT NULL,
					extraCreditId INT NOT NULL,
					goal INT DEFAULT 0, 
					reward INT(11) DEFAULT -1
				)ENGINE = InnoDB",
			],
		], //add_campaign_extra_credit_activities
		'add_extra_credit_progress_table' => [
			'title' => 'Add Extra Credit Progress Table',
			'description' => 'Store progress for of extra credit activites for each user',
			'sql' => [
				"CREATE Table ce_campaign_extra_credit_activity_users_progress (
					 id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					 userId INT NOT NULL,
					 campaignId INT NOT NULL,
					 extraCreditId INT NOT NULL,
					 progress INT NOT NULL,
					 rewardGiven TINYINT DEFAULT 0
				)ENGINE = InnoDB",
			],
		], //add_extra_credit_progress_table
		'add_ability_to_highlight_campaigns_open_for_enrollment' => [
			'title' => 'Add Ability to Highlight Campaigns Open For Enrollment',
			'description' => 'Allow libraries to choose whether to display campaigns open for enrollment in the campaign highlights banner',
			'sql' => [
				"ALTER TABLE library ADD COLUMN highlightCommunityEngagementOpenToEnroll TINYINT(1) DEFAULT 0",
			],
		], //add_ability_to_highlight_campaigns_open_for_enrollment
		'create_campaign_location_access' => [
			'title' => 'Create Campaign Location Access',
			'description' => 'Add table for location campaign access',
			'sql' => [
				"CREATE TABLE ce_campaign_location_access (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					campaignId INT NOT NULL,
					locationId INT NOT NULL
				) ENGINE = InnoDB",
			],
		], //create_campaign_location_access

		//chloe - Open Fifth


		//Jacob - Open Fifth

		//Pedro - Open Fifth
		'add_timestamp_to_ce_campaign_milestone_progress_entries' => [
			'title' => 'Update ce_campaign_milestone_progress_entries',
			'description' => 'Add timestamp to ce_campaign_milestone_progress_entries',
			'sql' => [
				"ALTER TABLE ce_campaign_milestone_progress_entries ADD COLUMN `timestamp` datetime NOT NULL DEFAULT (CURRENT_TIME)",
			],
		], //add_timestamp_to_ce_campaign_milestone_progress_entries


		//James Staub - Nashville Public Library
		'librarian_facebook_report_permissions' => [
			'title' => 'View Librarian Facebook report permissions',
			'description' => 'Create permissions for Librarian Facebook report',
			'continueOnError' => true,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES 
					('Circulation Reports', 'View Librarian Facebook', '', 70, 'Allows the user to view the Librarian Facebook.')
				",
			]
		], //librarian_facebook_report_permissions

		//Lucas Montoya - Theke Solutions

		//other

		//Talpa Search
		
	];
}
