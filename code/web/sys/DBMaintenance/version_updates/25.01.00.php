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
