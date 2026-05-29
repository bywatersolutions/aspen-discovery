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
