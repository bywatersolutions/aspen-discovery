<?php

/** @noinspection PhpUnused */
function getUpdates26_Q1_00(): array {
	return [
		/*'name' => [
			 'title' => '',
			 'description' => '',
			 'continueOnError' => false,
			 'sql' => [
				 ''
			 ]
		 ], //name*/
		//yanjun
		'overdrive_suppress_kindle_format' => [
			'title' => 'OverDrive Suppress Kindle Format',
			'description' => 'Allow OverDrive scopes to suppress Kindle format in the grouped work',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE overdrive_scopes ADD COLUMN suppressKindleFormat TINYINT(1) DEFAULT 0",
			]
		],
	];
}
