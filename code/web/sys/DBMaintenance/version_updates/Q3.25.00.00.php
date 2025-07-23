<?php

function getUpdatesQ3_25_00_00(): array {
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

		//katherine - Grove

		//kirstien - Grove

		//kodi - Grove

		//Mark - Grove

		// Myranda - Grove

		//Yanjun Li - ByWater
		'remove_starRating_from_overdrive_api_product_metadata' => [
			'title' => 'Remove Star Rating from overdrive_api_product_metadata',
			'description' => 'Remove starRating from overdrive_api_product_metadata table.',
			'sql' => [
				"ALTER TABLE overdrive_api_product_metadata DROP COLUMN starRating",
			]
		], //remove_starRating_from_overdrive_api_product_metadata

		'add_comprise_donation_settings' => [
			'title' => 'Add Comprise Donation Settings',
			'description' => 'Add customer name and id for donation in Comprise Settings',
			'sql' => [
				"ALTER TABLE comprise_settings ADD COLUMN customerNameForDonation VARCHAR(50) DEFAULT NULL",
				"ALTER TABLE comprise_settings ADD COLUMN customerIdForDonation INT(11) DEFAULT NULL",
			]
		], //add_comprise_donation_settings

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
		'update_browse_category_sort_options' => [
			'title' => 'Update Browse Category Sort Options for Lists Search',
			'description' => 'Add new date sorting options for browse categories when using Lists as search source.',
			'sql' => [
				"ALTER TABLE browse_category MODIFY COLUMN defaultSort ENUM('relevance','popularity','newest_to_oldest','author','title','user_rating','holds','publication_year_desc','publication_year_asc','event_date','oldest_to_newest','newest_updated_to_oldest','oldest_updated_to_newest') DEFAULT 'relevance'"
			]
		],
		'update_collection_spotlight_sort_options' => [
			'title' => 'Update Collection Spotlight Sort Options for Lists Search',
			'description' => 'Add new date sorting options for collection spotlights when using Lists as search source.',
			'sql' => [
				"ALTER TABLE collection_spotlight_lists MODIFY COLUMN defaultSort ENUM('relevance','popularity','newest_to_oldest','author','title','user_rating','holds','publication_year_desc','publication_year_asc','event_date','oldest_to_newest','newest_updated_to_oldest','oldest_updated_to_newest') DEFAULT 'relevance'"
			]
		],
		'clear_wikipedia_article_cache' => [
			'title' => 'Clear Cached Wikipedia Article Info for Wikipedia Integration Update',
			'description' => 'Clear the cached Wikipedia article information due to the Wikipedia parser rewrite.',
			'sql' => [
				"DELETE FROM cached_values WHERE cacheKey = 'wikipedia_article_%'"
			]
		],

		// Laura Escamilla - ByWater Solutions

		//alexander - Open Fifth

		//chloe - Open Fifth
		'move_heycentric_permission' => [
			 'title' => 'Move HeyCentric Permission',
			 'description' => 'Move the Administrer HeyCentric Settings permission into the existing eCommerce section',
			 'continueOnError' => false,
			 'sql' => [
				"UPDATE permissions SET name='Administer HeyCentric', sectionName='eCommerce', description='Allows the user to administer the integration with HeyCentric <em>This has potential security and cost implications.</em>' WHERE name='Administer HeyCentric Settings' AND sectionName='ecommerce'",
			 ],
			 
		 ], // move_heycentric_permission


		//James Staub - Nashville Public Library

		//Lucas Montoya - Theke Solutions

		//other

	];
}
