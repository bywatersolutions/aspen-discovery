<?php

/** @noinspection PhpUnused */
function getUpdates26_Q1_02(): array {
	return [
		/*'name' => [
			 'title' => '',
			 'description' => '',
			 'continueOnError' => false,
			 'sql' => [
				 ''
			 ]
		 ], //name*/
         'require_pin_for_palace_project' => [
			'title' => 'Add Require PIN for Palace Project Setting',
			'description' => 'Add Require PIN for Palace Project Setting',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE palace_project_settings ADD COLUMN requirePin TINYINT(1) DEFAULT 1',
			]
		], //allow_require_pin_for_palace_project
	];
}
