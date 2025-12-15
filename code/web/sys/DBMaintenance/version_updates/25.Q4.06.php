<?php

/** @noinspection PhpUnused */
function getUpdates25_Q4_06(): array {
	return [
		/*'name' => [
			 'title' => '',
			 'description' => '',
			 'continueOnError' => false,
			 'sql' => [
				 ''
			 ]
		 ], //name*/

		// Galen Charlton - Equinox
		'chilifresh_cover_art_settings' => [
			'title' => 'ChiliFresh covert art settings',
			'description' => 'ChiliFresh covert art settings',
			'continueOnError' => false,
			'sql' => [
				"CREATE TABLE IF NOT EXISTS chilifresh_settings (
				id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				enabled TINYINT(1) NOT NULL DEFAULT 1,
				genericArtCode TINYTEXT
				) ENGINE = InnoDB",
			]
		],
		'loral_settings' => [
			'title' => 'Loral Integration',
			'description' => 'Create Settings for Loral Integration',
			'continueOnError' => false,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS loral_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					loralUrl varchar(255),
					loralId varchar(10),
					password varchar(50) NOT NULL,
					enabled tinyint(1) DEFAULT 1
				)',
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Third Party Enrichment', 'Administer Loral', '', 40, 'Allows users to administer Loral content Enrichment.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Loral'))",
			]
		], //loral_settings
		'link_loral_and_libraries' => [
			'title' => 'Link Loral and Libraries',
			'description' => 'Link Loral and libraries so each library can have a different Loral subscription',
			'sql' => [
				"ALTER TABLE library ADD COLUMN loralSettingId INT DEFAULT -1",
				"ALTER TABLE loral_settings ADD COLUMN name TINYTEXT default 'default' UNIQUE",
			]
		], //link_loral_and_libraries
		'encrypt_loral_password' => [
			'title' => 'Encrypt Loral Password',
			'description' => 'Extend password field to store Loral Password encrypted',
			'sql' => [
				'ALTER TABLE loral_settings CHANGE COLUMN password password VARCHAR(250)'
			]
		], //encrypt_loral_password
	];
}
