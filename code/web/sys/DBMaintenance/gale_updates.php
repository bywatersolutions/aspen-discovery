<?php
/**@noinspection SqlResolve*/
function getGaleUpdates()
{
	return [
		'gale_module' => [
			'title' => 'Gale Module',
			'description' => 'Create Gale Module',
			'sql' => [
				"INSERT INTO modules (name, indexName, backgroundProcess) VALUES ('Gale', '', '')",
			],
		],
		'create_gale_settings' => [
			'title' => 'Create Gale Settings',
			'description' => 'Add table for gale settings',
			'sql' => [
				"CREATE TABLE gale_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(100) NOT NULL,
					locationId VARCHAR(255) NOT NULL,
					fullTextOnly TINYINT(1) DEFAULT 0
				) ENGINE = InnoDB",
			],
		],
		'add_gale_settings_to_library' => [
			'title' => 'Add Gale Settings to Library',
			'description' => 'Add column to library table to store gale settings',
			'sql' => [
				'ALTER TABLE library ADD COLUMN galeSettingsId INT(11) DEFAULT -1',
			],
		],
		'add_permissions_for_gale_module' => [
			'title' => 'Add Permissions For Gale Module',
			'description' => 'Add permissions for the Gale Module',
			'sql' => [
				"INSERT INTO permissions (name, sectionName, requiredModule, weight, description) VALUES ('Administer Gale', 'Cataloging & eContent', 'Gale', 125, 'Allows the user to administer Gale integration for all libraries.');",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Gale'))",
				"INSERT INTO permissions (name, sectionName, requiredModule, weight, description) VALUES ('Library Gale Options', 'Primary Configuration - Library Fields', '', 49, 'Configure Library fields related to Gale content.');",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Library Gale Options'))",
			],
		],
		'create_gale_product_codes' => [
			'title' => 'Create Gale Product Codes Table',
			'description' => 'Add table for Gale product code display names',
			'sql' => [
				"CREATE TABLE gale_product_codes (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					settingId INT NOT NULL,
					productCode VARCHAR(50) NOT NULL,
					displayName VARCHAR(255) NOT NULL,
					INDEX (settingId)
				) ENGINE = InnoDB",
			],
		],
		'aspen_usage_gale' => [
			'title' => 'Aspen Usage for Gale Searches',
			'description' => 'Add a column to track usage of Gale searches within Aspen',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE aspen_usage ADD COLUMN galeSearches INT(11) DEFAULT 0',
			],
		],
		'track_gale_user_usage4' => [
			'title' => 'Gale Usage by user',
			'description' => 'Add a table to track how often a particular user uses Gale.',
			'sql' => [
				"CREATE TABLE user_gale_usage (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					userId INT(11) NOT NULL,
					instance VARCHAR(100) DEFAULT NULL,
					month INT(2) NOT NULL,
					year INT(4) NOT NULL,
					usageCount INT(11),
					KEY year (year, month, instance, userId)
				) ENGINE = InnoDB",
			],
		],
		'gale_record_usage' => [
			'title' => 'Gale Record Usage',
			'description' => 'Add a table to track how often a particular Gale record is used.',
			'sql' => [
				"CREATE TABLE `gale_usage` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`instance` varchar(100) DEFAULT NULL,
					`galeId` varchar(50) NOT NULL,
					`month` int(11) NOT NULL,
					`year` int(11) NOT NULL,
					`timesViewedInSearch` int(11) NOT NULL,
					`timesUsed` int(11) NOT NULL,
					PRIMARY KEY (`id`),
					KEY `galeId` (`galeId`,`year`,`instance`,`month`)
				) ENGINE=InnoDB",
			],
		],
		'display_explore_more_bar_in_gale' => [
			'title' => 'Display Explore More Bar in Gale',
			'description' => 'Display Explore More Bar in Gale',
			'sql' => [
				'ALTER TABLE library ADD COLUMN displayExploreMoreBarInGale TINYINT(1) DEFAULT 1',
				'ALTER TABLE location ADD COLUMN displayExploreMoreBarInGale TINYINT(1) DEFAULT 1',
			],
		],
	];
}
