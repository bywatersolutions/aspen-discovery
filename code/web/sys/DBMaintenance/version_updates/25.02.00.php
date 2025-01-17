<?php

function getUpdates25_02_00(): array {
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


		//katherine
		'native_events_permissions' => [
			'title' => 'Native Events Permissions',
			'description' => 'Add new permissions for native events',
			'continueOnError' => true,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Events', 'Administer Field Sets', 'Events', 30, 'Allows the user to administer field sets for native events.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Events', 'Administer Event Types', 'Events', 40, 'Allows the user to administer native event types.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Events', 'Administer Events for All Locations', 'Events', 50, 'Allows the user to administer native events for all locations.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Events', 'Administer Events for Home Library Locations', 'Events', 51, 'Allows the user to administer native events for home library locations.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Events', 'Administer Events for Home Locations', 'Events', 52, 'Allows the user to administer native events for home location.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Events', 'View Private Events for All Locations', 'Events', 60, 'Allows the user to view private native events for all locations.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Events', 'View Private Events for Home Library Locations', 'Events', 61, 'Allows the user to view private native events for home library locations.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Events', 'View Private Events for Home Locations', 'Events', 62, 'Allows the user to view private native events for home location.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Events', 'View Event Reports for All Libraries', 'Events', 70, 'Allows the user to view event reports for all libraries.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Events', 'View Event Reports for Home Library', 'Events', 71, 'Allows the user to view event reports for their home library.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Events', 'Print Calendars with Header Images', 'Events', 80, 'Allows the user to print calendars with header images.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Field Sets'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Event Types'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Events for All Locations'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='View Private Events for All Locations'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='View Event Reports for All Libraries'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Print Calendars with Header Images'))",
			],
		], //native_events_permissions
		'native_event_tables' => [
			'title' => 'Native Event Tables',
			'description' => 'Add new tables for native events',
			'continueOnError' => true,
			'sql' => [
				"CREATE TABLE event_field (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(50) NOT NULL UNIQUE,
					description VARCHAR(100) NOT NULL,
					type TINYINT NOT NULL DEFAULT 0,
					allowableValues VARCHAR(150),
					defaultValue VARCHAR(150),
					facetName INT NOT NULL DEFAULT 0
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci",
				"CREATE TABLE event_field_set (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(50) NOT NULL UNIQUE
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci",
				"CREATE TABLE event_field_set_field (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					eventFieldId INT NOT NULL,
					eventFieldSetId INT NOT NULL
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci",
			]
		], //native_events_tables

		//kirstien - Grove

		//kodi

		//alexander - PTFS-Europe

		//chloe - PTFS-Europe

		//James Staub - Nashville Public Library

		//Lucas Montoya - Theke Solutions

		//other

	];
}