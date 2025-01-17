<?php

function getCloudLibraryUpdates() {
	return [
		'add_use_alternate_library_card_setting_for_cloud_library' => [
			'title' => 'Add a setting to Cloud Library to use the alternate library card',
			'description' => 'Add a setting to Cloud Library to use the alternate library card.',
			'sql' => [
				'ALTER table cloud_library_settings ADD column useAlternateLibraryCard TINYINT(1) DEFAULT 0',
			],
		],
	];
}