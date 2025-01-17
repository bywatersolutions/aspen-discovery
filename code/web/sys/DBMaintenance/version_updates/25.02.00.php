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

		//kirstien - Grove

		//kodi

		//alexander - PTFS-Europe

		//chloe - PTFS-Europe
		'save_library_ils_consent_feature_toggle_value' => [
			'title' => 'Save Library ILS Consent Feature Toggle Value',
			'description' => 'Allows to record whether a library has enabled the ILS Consent feature or not',
			'continueOnError' => false,
			'sql' => ['ALTER TABLE library ADD COLUMN ilsConsentEnabled tinyint(1) DEFAULT 0'],
		], //'save_library_ils_consent_feature_toggle_value'

		//James Staub - Nashville Public Library

		//Lucas Montoya - Theke Solutions

		//other

	];
}
