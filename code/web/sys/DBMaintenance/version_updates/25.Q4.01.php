<?php

/** @noinspection PhpUnused */
function getUpdates25_Q4_01(): array {
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
		]
	];
}
