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
		'ptype_account_profile' => [
			'title' => 'Patron Type - Add Account Profile',
			'description' => 'Add Information about which account profile a patron type belongs to',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE ptype ADD COLUMN accountProfileId INT',
				"UPDATE ptype set accountProfileId = (SELECT MIN(id) from account_profiles where ils <> 'na' and name <> 'admin')",
				"ALTER TABLE ptype DROP INDEX ptype",
				"ALTER TABLE ptype ADD UNIQUE INDEX ptype_profile(ptype, accountProfileId)",
			]
		], //ptype_account_profile

		//katherine

		//kirstien - Grove

		//kodi

		//alexander - PTFS-Europe

		//chloe - PTFS-Europe

		//James Staub - Nashville Public Library

		//Lucas Montoya - Theke Solutions

		//other

	];
}
