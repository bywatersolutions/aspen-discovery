<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_06_00(): array {
	$now = time();

	return [
		/*'name' => [
			 'title' => '',
			 'description' => '',
			 'continueOnError' => false,
			 'sql' => [
				 ''
			 ]
		 ], //name*/

		//mark n

		//kirstien

		//kodi

		//yanjun
		'add_overdriveAdvantageId' => [
			'title' => 'Add overdriveAdvantageId column',
			'description' => 'Add overdriveAdvantageId column to library_overdrive_settings',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library_overdrive_settings ADD COLUMN overdriveAdvantageId int(11) DEFAULT 0'
			]
		],//add_overdriveAdvantageId
		'add_overdrive_advantage_products_key_additional' => [
			'title' => 'Add OverDrive Advantage Products Key Additional',
			'description' => 'Add a field for additional advantage collection tokens per library',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library_overdrive_settings ADD COLUMN additionalAdvantageProductsKey varchar(255) DEFAULT \'\''
			]
		], //add_overdrive_advantage_products_key_additional
		'add_overdrive_advantage_products_id_additional' => [
			'title' => 'Add OverDrive Advantage Products ID Additional',
			'description' => 'Add a field for additional advantage collection ID per library',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library_overdrive_settings ADD COLUMN additionalAdvantageId int(11) DEFAULT 0'
			]
		], //add_overdrive_advantage_products_id_additional

		//imani

		//galen

		//chloe

		//pedro

		//mark j

		//lucas

		//tomas

		// stephen

		//other

	];
}
