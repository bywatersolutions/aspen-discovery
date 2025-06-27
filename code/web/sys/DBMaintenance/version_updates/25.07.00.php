<?php

function getUpdates25_07_00(): array {
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

		 	// Leo Stoyanov - BWS
		'enable_web_builder_for_libraries_using_it' => [
			'title' => 'Enable Web Builder for Libraries Using Web Builder Features',
			'description' => 'Enable Web Builder only for libraries that have associated Web Builder content (resources, pages, forms, etc.).',
			'continueOnError' => true,
			'sql' => [
				"UPDATE library SET enableWebBuilder = 1 
				WHERE libraryId IN (
					-- Libraries with Web Resources
					SELECT DISTINCT libraryId FROM library_web_builder_resource
					UNION
					-- Libraries with Basic Pages  
					SELECT DISTINCT libraryId FROM library_web_builder_basic_page
					UNION
					-- Libraries with Portal Pages (Custom Pages)
					SELECT DISTINCT libraryId FROM library_web_builder_portal_page
					UNION
					-- Libraries with Custom Web Resource Pages
					SELECT DISTINCT libraryId FROM library_web_builder_custom_web_resource_page
					UNION
					-- Libraries with Custom Forms
					SELECT DISTINCT libraryId FROM library_web_builder_custom_form
					UNION
					-- Libraries with Grapes Pages
					SELECT DISTINCT libraryId FROM library_web_builder_grapes_page
					UNION
					-- Libraries with Quick Polls
					SELECT DISTINCT libraryId FROM library_web_builder_quick_poll
				)",
			],
		],

	];
}
