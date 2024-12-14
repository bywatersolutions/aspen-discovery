<?php

function getUpdates25_01_00(): array {
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
		'add_middle_initial_support' => [
			'title' => 'Add Middle Initial Display Name support',
			'description' => 'Add support for middle names for users when creating display names',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE library CHANGE COLUMN patronNameDisplayStyle patronNameDisplayStyle ENUM('firstinitial_lastname', 'lastinitial_firstname','firstinitial_middleinitial_lastname', 'firstname_middleinitial_lastinitial') DEFAULT 'firstinitial_lastname';",
			]
		], //add_middle_initial_support
		'link_syndetics_and_libraries' => [
			'title' => 'Link Syndetics and Libraries',
			'description' => 'Link syndetics and libraries so each library can have a different Syndetics subscription',
			'sql' => [
				"ALTER TABLE library ADD COLUMN syndeticsSettingId INT DEFAULT -1",
				"ALTER TABLE syndetics_settings ADD COLUMN name TINYTEXT default 'default' UNIQUE",
				"linkSyndeticsToLibraries",
			]
		], //link_syndetics_and_libraries
		'syndetics_add_instance_number' => [
			'title' => 'Add Syndetics Unbound Instance Number',
			'description' => 'Add Syndetics Unbound Instance Number',
			'sql' => [
				'ALTER TABLE syndetics_settings ADD COLUMN unboundInstanceNumber int(11) DEFAULT 0'
			]
		], //syndetics_add_instance_number

		//katherine

		//kirstien

		//kodi

		//alexander - PTFS-Europe

		//chloe - PTFS-Europe

		//James Staub - Nashville Public Library

		//Lucas Montoya - Theke Solutions

		//other

	];
}

function linkSyndeticsToLibraries(&$update) : void {
	require_once ROOT_DIR . '/sys/Enrichment/SyndeticsSetting.php';
	$syndeticsSetting = new SyndeticsSetting();
	if ($syndeticsSetting->find(true)) {
		global $aspen_db;
		$aspen_db->query("UPDATE library set syndeticsSettingId = $syndeticsSetting->id");
	}
}
