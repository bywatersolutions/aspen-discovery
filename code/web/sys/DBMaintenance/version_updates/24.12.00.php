<?php

function getUpdates24_12_00(): array {
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
		'localIllRequestType' => [
			'title' => 'Add localIllRequestType to Library Settings',
			'description' => 'Add localIllRequestType to Library Settings',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library ADD COLUMN localIllRequestType TINYINT DEFAULT 0'
			]
		], //localIllRequestType
		'makeVdxHoldGroupsGeneric' => [
			'title' => 'Make VDX Hold Groups Generic',
			'description' => 'Make VDX Hold Groups more generic so they can be used for Local ILL',
			'continueOnError' => true,
			'sql' => [
				"UPDATE permissions SET name = 'Administer Hold Groups' where name = 'Administer VDX Hold Groups'",
				"RENAME TABLE vdx_hold_groups TO hold_groups",
				"RENAME TABLE vdx_hold_group_location TO hold_group_location",
				"ALTER TABLE hold_group_location CHANGE COLUMN vdxHoldGroupId holdGroupId INT"
			]
		], //makeVdxHoldGroupsGeneric
		'local_ill_forms' => [
			'title' => 'Local ILL Form setup',
			'description' => 'Add the ability to configure Local ILL forms for locations',
			'continueOnError' => true,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS local_ill_form(
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							name VARCHAR(50) NOT NULL UNIQUE,
							introText TEXT,
							showAcceptFee TINYINT(1) DEFAULT 0,
							requireAcceptFee TINYINT(1) DEFAULT 0,
							showMaximumFee TINYINT(1) DEFAULT 0,
							feeInformationText TEXT
						) ENGINE = INNODB;',
				'ALTER TABLE location ADD COLUMN localIllFormId INT DEFAULT -1',
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES
							('ILL Integration', 'Administer All Local ILL Forms', '', 17, 'Allows the user to define administer all Local ILL Forms.'),
							('ILL Integration', 'Administer Library Local ILL Forms', '', 18, 'Allows the user to define administer Local ILL Forms for their library.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer All Local ILL Forms'))",
			],
		], //local_ill_forms
		'local_ill_form_default_max_fee' => [
			'title' => 'Local ILL Form - Default Max Fee',
			'description' => 'Local ILL Form - Default Max Fee',
			'sql' => [
				'ALTER TABLE local_ill_form ADD COLUMN defaultMaxFee VARCHAR(10) DEFAULT 0.00'
			]
		], //local_ill_form_default_max_fee
		'copyVDXFormsToLocalIllForms' => [
			'title' => 'Copy VDX Forms to Local ILL Forms',
			'description' => 'Copy VDX Forms to Local ILL Forms',
			'continueOnError' => true,
			'sql' => [
				"INSERT INTO local_ill_form(id, name, introText, showAcceptFee, requireAcceptFee, showMaximumFee, feeInformationText) SELECT id, name, introText, showAcceptFee, showAcceptFee, showMaximumFee, feeInformationText FROM vdx_form",
				"UPDATE location set localIllFormId = vdxFormId"
			]
		], //copyVDXFormsToLocalIllForms
		'add_hold_out_of_hold_group_message' => [
			'title' => 'Add hold out of hold group message',
			'description' => 'Add hold out of hold group message',
			'sql' => [
				'ALTER TABLE user_hold ADD COLUMN outOfHoldGroupMessage TINYTEXT'
			]
		], //add_hold_out_of_hold_group_message
		'year_in_review_permissions' => [
			'title' => 'Year In Review Permissions',
			'description' => 'Add new permissions for Year In Review functionality',
			'continueOnError' => true,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Year in Review', 'Administer Year in Review for All Libraries', '', 10, 'Allows Year in Review functionality to be configured for all libraries.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Year in Review', 'Administer Year in Review for Home Library', '', 20, 'Allows Year in Review functionality to be configured for the user\'s home library.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Year in Review for All Libraries'))",
			],
		], //year_in_review_permissions
		'year_in_review_settings' => [
			'title' => 'Year In Review Settings',
			'description' => 'Add new settings for Year In Review functionality',
			'continueOnError' => true,
			'sql' => [
				"CREATE TABLE year_in_review_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(50) NOT NULL UNIQUE,
					year int,
					staffStartDate int,
					patronStartDate int
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci",
				'CREATE TABLE library_year_in_review (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					yearInReviewId INT NOT NULL,
					libraryId INT NOT NULL,
					UNIQUE (yearInReviewId, libraryId)
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci',
				'CREATE TABLE user_year_in_review (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					userId INT NOT NULL,
					settingId int NOT NULL,
					wrappedActive TINYINT(1) DEFAULT 0,
					wrappedViewed TINYINT(1) DEFAULT 0,
					wrappedResults TEXT,
					UNIQUE (userId, settingId)
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci',
				'ALTER TABLE ptype ADD COLUMN enableYearInReview TINYINT DEFAULT 1'
			]
		], //year_in_review_settings
		'add_end_date_to_year_in_review_settings' => [
			'title' => 'Add End Date to Year In Review Settings',
			'description' => 'Add end date for Year In Review functionality',
			'sql' => [
				'ALTER TABLE year_in_review_settings ADD COLUMN endDate int',
			]
		], //add_end_date_to_year_in_review_settings
		'add_style_to_year_in_review_settings' => [
			'title' => 'Add Style to Year In Review Settings',
			'description' => 'Add style for Year In Review functionality',
			'sql' => [
				'ALTER TABLE year_in_review_settings ADD COLUMN style TINYINT DEFAULT 0',
			]
		], //add_end_date_to_year_in_review_settings
		'create_2024_default_year_in_review_settings' => [
			'title' => 'Create 2024 Default Year In Review Settings',
			'description' => 'Create 2024 Default Year In Review Settings',
			'sql' => [
				"INSERT INTO year_in_review_settings (name, year, style, staffStartDate, patronStartDate, endDate) VALUE ('2024 Shelved', 2024, 0, 1733036400, 1735714800, 1738393200)"
			]
		], //create_2024_default_year_in_review_settings
		'add_active_status_to_materials_requests' => [
			'title' => 'Add Active Status to Materials Requests',
			'description' => 'Add active status to Materials requests so they can be distinguished from open requests',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE library CHANGE COLUMN maxOpenRequests maxActiveRequests INT(11) DEFAULT 5',
				'ALTER TABLE materials_request_status ADD COLUMN isActive TINYINT DEFAULT 0',
				'UPDATE materials_request_status SET isActive = isOpen',
			]
		], //add_active_status_to_materials_requests
		'remove_old_sso_fields' => [
			'title' => 'Remove Old SSO Fields',
			'description' => 'Remove old unused SSO fields',
			'sql' => [
				'ALTER TABLE library DROP COLUMN ssoAddressAttr',
				'ALTER TABLE library DROP COLUMN ssoCategoryIdAttr',
				'ALTER TABLE library DROP COLUMN ssoCategoryIdFallback',
				'ALTER TABLE library DROP COLUMN ssoCityAttr',
				'ALTER TABLE library DROP COLUMN ssoDisplayNameAttr',
				'ALTER TABLE library DROP COLUMN ssoEmailAttr',
				'ALTER TABLE library DROP COLUMN ssoEntityId',
				'ALTER TABLE library DROP COLUMN ssoFirstnameAttr',
				'ALTER TABLE library DROP COLUMN ssoIdAttr',
				'ALTER TABLE library DROP COLUMN ssoLastnameAttr',
				'ALTER TABLE library DROP COLUMN ssoLibraryIdAttr',
				'ALTER TABLE library DROP COLUMN ssoLibraryIdFallback',
				'ALTER TABLE library DROP COLUMN ssoMetadataFilename',
				'ALTER TABLE library DROP COLUMN ssoName',
				'ALTER TABLE library DROP COLUMN ssoPatronTypeAttr',
				'ALTER TABLE library DROP COLUMN ssoPatronTypeFallback',
				'ALTER TABLE library DROP COLUMN ssoPhoneAttr',
				'ALTER TABLE library DROP COLUMN ssoUniqueAttribute',
				'ALTER TABLE library DROP COLUMN ssoUsernameAttr',
				'ALTER TABLE library DROP COLUMN ssoXmlUrl',
			]
		], //remove_old_sso_fields
		'add_library_yearly_request_limit_type' => [
			'title' => 'Add Library Yearly Request Limit Type',
			'description' => 'Add Yearly Request Limit Type to Library Settings',
			'sql' => [
				'ALTER TABLE library ADD COLUMN yearlyRequestLimitType TINYINT DEFAULT 0',
			]
		], //add_library_yearly_request_limit_type
		'enable_add_to_reading_history' => [
			'title' => 'Enable Add to Reading History',
			'description' => 'Add the ability to enable adding titles to reading history from results',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE library ADD COLUMN enableAddToReadingHistory TINYINT DEFAULT 1',
				'ALTER TABLE user_reading_history_work ADD COLUMN isManuallyAdded TINYINT DEFAULT 0'
			]
		], //enable_add_to_reading_history
		'add_checkout_out_of_hold_group_message' => [
			'title' => 'Add checkout out of hold group message',
			'description' => 'Add checkout out of hold group message',
			'sql' => [
				'ALTER TABLE user_checkout ADD COLUMN outOfHoldGroupMessage TINYTEXT'
			]
		], //add_checkout_out_of_hold_group_message
		'library_allow_renewing_out_of_hold_group_checkouts' => [
			'title' => 'Library - Allow Renewing Out of Hold Group Checkouts',
			'description' => 'Library - Allow Renewing Out of Hold Group Checkouts',
			'sql' => [
				'ALTER TABLE library ADD COLUMN allowRenewingOutOfHoldGroupCheckouts TINYINT DEFAULT 1',
			]
		], //library_allow_renewing_out_of_hold_group_checkouts
		'library_maximum_local_ill_requests' => [
			'title' => 'Library - Maximum Local ILL Requests',
			'description' => 'Add the ability to set a maximum number of Local ILL requests per library',
			'sql' => [
				'ALTER TABLE library ADD COLUMN maximumLocalIllRequests INT DEFAULT 0',
			]
		], //library_maximum_local_ill_requests

		//katherine

		//kirstien

		//kodi


		//alexander - PTFS-Europe
		'add_regular_expression_for_iTypes_to_treat_as_eContent' => [
			'title' => 'Add Regular Expression For iTypes To Treat As Econtent',
			'description' => 'Add treatItemsAsEcontent to give control over iTypes to be treated as eContent',
			'sql' => [
				"ALTER TABLE indexing_profiles ADD COLUMN treatItemsAsEcontent VARCHAR(512) DEFAULT 'ebook|ebk|eaudio|evideo|online|oneclick|eaudiobook|download|eresource|electronic resource'",
			],
		], //add_treatItemsAsEcontent_field
		'optional_show_title_on_grapes_pages' => [
			'title' => 'Optional Show Title on Grapes Pages',
			'description' => 'Make displaying a given title on a grapes pae optional',
			'sql' => [
				'ALTER TABLE grapes_web_builder ADD COLUMN showTitleOnPage TINYINT NOT NULL DEFAULT 1'
			],
		], //optional_show_title_on_grapes_pages

		//chloe - PTFS-Europe
		'create_libkey_permissions' => [
			'title' => 'Create LibKey Permissions',
			'description' => 'Add an LibKey permission section containing the Administer LibKey Settings permission',
			'sql' => [
				"INSERT INTO permissions (name, sectionName, weight, description) VALUES ( 'Administer LibKey Settings','Third Party Enrichment', 0, 'Allows the user to administer the integration with LibKey')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer LibKey Settings'))",
			],
		],// create_libkey_permissions
		'create_libkey_settings_table' => [
			'title' => 'Create LibKey Settings Table',
			'description' => 'Add an LibKey settings table',
			'sql' => [
				"CREATE TABLE libkey_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(255) NOT NULL,
					libraryId VARCHAR(20) NOT NULL,
					apiKey VARCHAR(255) NOT NULL
				)",
				"ALTER TABLE library ADD libKeySettingId INT NOT NULL DEFAULT -1"
			],
		],// create_libkey_permissions


		//James Staub - Nashville Public Library

		//Lucas Montoya - Theke Solutions
		'disable_ip_spammy_control' => [
			 'title' => 'Disable Ips Spammy Control',
			 'description' => 'Prevent Aspen from internally checking and blocking spam IP addresses',
			 'continueOnError' => false,
			 'sql' => [
				 "ALTER TABLE system_variables ADD COLUMN disableIpSpammyControl TINYINT(1) DEFAULT 0 ",
			 ]
		 ], //disable_ip_spammy_control



		//other

	];
}
