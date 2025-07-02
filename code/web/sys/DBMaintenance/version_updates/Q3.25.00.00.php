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

		// Leo Stoyanov - BWS
		'rollback_administer_side_loads_name' => [
			'title' => 'Clean Slate: Revert Permission Changes and Drop Permission Group Tables',
			'description' => 'Provides a clean slate by reverting permission name changes and dropping permission group tables if they exist. 
			This is primarily for those testing and allows complete rollback of permission group functionality.',
			'continueOnError' => false,
			'sql' => [
				"UPDATE permissions SET name = 'Administer Side Loads' WHERE name = 'Administer All Side Loads'",
				"DROP TABLE IF EXISTS permission_group_permissions",
				"DROP TABLE IF EXISTS permission_groups",
			],
		], // rollback_administer_side_loads_name
		'permission_groups_and_mappings' => [
			'title' => 'Create Permission Groups and Mappings',
			'description' => 'Create tables for permission groups and seed initial groups and mappings.',
			'continueOnError' => false,
			'sql' => [
				"CREATE TABLE IF NOT EXISTS `permission_groups` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`groupKey` varchar(100) NOT NULL,
					`sectionName` varchar(75) NOT NULL,
					`label` varchar(100) NOT NULL,
					`description` varchar(250) DEFAULT '',
					PRIMARY KEY (`id`),
					UNIQUE KEY `groupKey` (`groupKey`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
				"CREATE TABLE IF NOT EXISTS `permission_group_permissions` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`groupId` int(11) NOT NULL,
					`permissionId` int(11) NOT NULL,
					PRIMARY KEY (`id`),
					KEY `groupId` (`groupId`),
					KEY `permissionId` (`permissionId`),
					CONSTRAINT `fk_permission_group` FOREIGN KEY (`groupId`) REFERENCES `permission_groups` (`id`) ON DELETE CASCADE,
					CONSTRAINT `fk_permission_group_permission` FOREIGN KEY (`permissionId`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
				"INSERT IGNORE INTO `permission_groups` (`groupKey`,`sectionName`,`label`,`description`) VALUES
					('adminBrowseCategories','Local Enrichment','Administer Browse Categories','Specify whether the role can manage all browse categories, only those for its library, or specific category groups.'),
					('admincollectionSpotlights','Local Enrichment','Administer Collection Spotlights','Specify whether the role can manage all collection spotlights or only those for its library.'),
					('adminPlacards','Local Enrichment','Administer Placards','Specify whether the role can manage all placards or only those for its library.'),
					('adminJavaScriptSnippets','Local Enrichment','Administer JavaScript Snippets','Specify whether the role can manage all JavaScript snippets or only those scoped to its library.'),
					('adminSystemMessages','Local Enrichment','Administer System Messages','Specify whether the role can manage all system messages or only those scoped to its library.'),
					('adminThemes','Theme & Layout','Administer Themes','Specify whether the role can manage all themes or only those scoped to its library.'),
					('adminLayoutSettings','Theme & Layout','Administer Layout Settings','Specify whether the role can manage all layout settings or only those scoped to its library.'),
					('adminLibraries','Primary Configuration','Administer Libraries','Specify whether the role can manage all libraries or only its assigned home library.'),
					('adminLocations','Primary Configuration','Administer Locations','Specify whether the role can manage all locations, only its library locations, or only the home location.'),
					('adminHoldsReports','Circulation Reports','View Holds Reports','Specify whether the role can view all holds reports or only those for its library.'),
					('adminStudentReports','Circulation Reports','View Student Reports','Specify whether the role can view all student reports or only those for its library.'),
					('adminCollectionReports','Circulation Reports','View Collection Reports','Specify whether the role can view all collection reports or only those for its library.'),
					('adminEcomReports','eCommerce','View eCommerce Reports','Specify whether the role can view eCommerce reports for all libraries or only those for its library.'),
					('adminDonationsReports','eCommerce','View Donations Reports','Specify whether the role can view donation reports for all libraries or only those for its library.'),
					('adminEmailTemplates','Email','Administer Email Templates','Specify whether the role can manage all email templates or only those for its library.'),
					('adminEventsAdmin','Events','Administer Events','Specify whether the role can manage events across all locations, library locations, or only the home location.'),
					('adminPrivateEvents','Events','View Private Events','Specify whether the role can view private events across all locations, library locations, or only the home location.'),
					('adminEventsReports','Events','View Event Reports','Specify whether the role can view event reports for all libraries or only those for its library.'),
					('adminGroupedWorkDisplaySettings','Grouped Work Display','Administer Grouped Work Display Settings','Specify whether the role can manage grouped work display settings across all libraries or only those for its library.'),
					('adminGroupedWorkFacets','Grouped Work Display','Administer Grouped Work Facets','Specify whether the role can manage grouped work facets across all libraries or only those for its library.'),
					('adminFormatSorting','Grouped Work Display','Administer Format Sorting','Specify whether the role can manage format sorting options across all libraries or only those for its library.'),
					('adminVdxForms','ILL Integration','Administer VDX Forms','Specify whether the role can manage all VDX forms or only those for its library.'),
					('adminLocalIllForms','ILL Integration','Administer Local ILL Forms','Specify whether the role can manage all local ILL forms or only those for its library.'),
					('adminSublocations','Primary Configuration - Location Sublocations','Administer Sublocations','Specify whether the role can manage sublocations for all libraries or only those for its library.'),
					('adminWebBuilderMenus','Web Builder','Administer Menus','Specify whether the role can manage all web builder menus or only those for its library.'),
					('adminBasicPages','Web Builder','Administer Basic Pages','Specify whether the role can manage all basic pages or only those for its library.'),
					('adminCustomPages','Web Builder','Administer Custom Pages','Specify whether the role can manage all custom pages or only those for its library.'),
					('adminCustomForms','Web Builder','Administer Custom Forms','Specify whether the role can manage all custom forms or only those for its library.'),
					('adminWebResources','Web Builder','Administer Web Resources','Specify whether the role can manage all web resources or only those for its library.'),
					('adminStaffMembers','Web Builder','Administer Staff Members','Specify whether the role can manage all staff members or only those for its library.'),
					('adminWebsiteFacets','Website Indexing','Administer Website Facets','Specify whether the role can manage website facet settings across all libraries or only those for its library.'),
					('adminYearInReview','Year in Review','Administer Year in Review','Specify whether the role can manage year-in-review settings for all libraries or only those for its library.'),
					('adminQuickPolls','Web Builder','Administer Quick Polls','Specify whether the role can manage all quick polls or only those for its library.'),
					('adminGrapesPages','Web Builder','Administer Grapes Pages','Specify whether the role can manage all Grapes pages or only those for its library.'),
					('adminCustomWebResourcePages','Web Builder','Administer Custom Web Resource Pages','Specify whether the role can manage all custom web resource pages or only those for its library.'),
					('adminSideLoads','Cataloging & eContent','Administer Side Loads','Specify whether the role can manage side loads globally, for its home library, or manage side load scopes for its home library.'),
					('adminWebContent','Web Builder','Administer Web Content','Specify whether the role can manage all web content (i.e., images and PDFs) or only those for its library.')",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Browse Categories','Administer Library Browse Categories','Administer Selected Browse Category Groups') WHERE pg.groupKey = 'adminBrowseCategories'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Collection Spotlights','Administer Library Collection Spotlights') WHERE pg.groupKey = 'admincollectionSpotlights'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Placards','Administer Library Placards', 'Edit Library Placards') WHERE pg.groupKey = 'adminPlacards'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All JavaScript Snippets','Administer Library JavaScript Snippets') WHERE pg.groupKey = 'adminJavaScriptSnippets'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All System Messages','Administer Library System Messages') WHERE pg.groupKey = 'adminSystemMessages'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Themes','Administer Library Themes') WHERE pg.groupKey = 'adminThemes'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Layout Settings','Administer Library Layout Settings') WHERE pg.groupKey = 'adminLayoutSettings'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Libraries','Administer Home Library') WHERE pg.groupKey = 'adminLibraries'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Locations','Administer Home Library Locations', 'Administer Home Location') WHERE pg.groupKey = 'adminLocations'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('View Location Holds Reports','View All Holds Reports') WHERE pg.groupKey = 'adminHoldsReports'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('View Location Student Reports','View All Student Reports') WHERE pg.groupKey = 'adminStudentReports'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('View Location Collection Reports','View All Collection Reports') WHERE pg.groupKey = 'adminCollectionReports'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('View eCommerce Reports for All Libraries','View eCommerce Reports for Home Library') WHERE pg.groupKey = 'adminEcomReports'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('View Donations Reports for All Libraries','View Donations Reports for Home Library') WHERE pg.groupKey = 'adminDonationsReports'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Email Templates','Administer Library Email Templates') WHERE pg.groupKey = 'adminEmailTemplates'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer Events for All Locations','Administer Events for Home Library Locations','Administer Events for Home Location') WHERE pg.groupKey = 'adminEventsAdmin'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('View Private Events for All Locations','View Private Events for Home Library Locations','View Private Events for Home Location') WHERE pg.groupKey = 'adminPrivateEvents'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('View Event Reports for All Libraries','View Event Reports for Home Library') WHERE pg.groupKey = 'adminEventsReports'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Grouped Work Display Settings','Administer Library Grouped Work Display Settings') WHERE pg.groupKey = 'adminGroupedWorkDisplaySettings'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Grouped Work Facets','Administer Library Grouped Work Facets') WHERE pg.groupKey = 'adminGroupedWorkFacets'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Format Sorting','Administer Library Format Sorting') WHERE pg.groupKey = 'adminFormatSorting'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All VDX Forms','Administer Library VDX Forms') WHERE pg.groupKey = 'adminVdxForms'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Local ILL Forms','Administer Library Local ILL Forms') WHERE pg.groupKey = 'adminLocalIllForms'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer Sublocations for All Libraries','Administer Sublocations for Home Library') WHERE pg.groupKey = 'adminSublocations'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Menus','Administer Library Menus') WHERE pg.groupKey = 'adminWebBuilderMenus'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Basic Pages','Administer Library Basic Pages') WHERE pg.groupKey = 'adminBasicPages'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Custom Pages','Administer Library Custom Pages') WHERE pg.groupKey = 'adminCustomPages'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Custom Forms','Administer Library Custom Forms') WHERE pg.groupKey = 'adminCustomForms'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Web Resources','Administer Library Web Resources') WHERE pg.groupKey = 'adminWebResources'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Staff Members','Administer Library Staff Members') WHERE pg.groupKey = 'adminStaffMembers'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Website Facet Settings','Administer Library Website Facet Settings') WHERE pg.groupKey = 'adminWebsiteFacets'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer Year in Review for All Libraries','Administer Year in Review for Home Library') WHERE pg.groupKey = 'adminYearInReview'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Quick Polls','Administer Library Quick Polls') WHERE pg.groupKey = 'adminQuickPolls'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Grapes Pages','Administer Library Grapes Pages') WHERE pg.groupKey = 'adminGrapesPages'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Custom Web Resource Pages','Administer Library Custom Web Resource Pages') WHERE pg.groupKey = 'adminCustomWebResourcePages'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer Side Loads','Administer Side Loads for Home Library', 'Administer Side Load Scopes for Home Library') WHERE pg.groupKey = 'adminSideLoads'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Web Content','Administer Web Content for Home Library') WHERE pg.groupKey = 'adminWebContent'",
			],
		], // permission_groups_and_mappings
		'cleanup_mutually_exclusive_permissions' => [
			'title' => 'Cleanup Mutually Exclusive Permissions',
			'description' => 'Remove duplicate permissions from roles where multiple permissions from the same permission group are assigned, keeping only the broadest permission.',
			'continueOnError' => true,
			'sql' => [
				// adminBrowseCategories - priority: All > Library > Selected
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name IN ('Administer Library Browse Categories', 'Administer Selected Browse Category Groups')
				 AND p2.name = 'Administer All Browse Categories'",

				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Selected Browse Category Groups'
				 AND p2.name = 'Administer Library Browse Categories'",

				// admincollectionSpotlights - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library Collection Spotlights'
				 AND p2.name = 'Administer All Collection Spotlights'",

				// adminPlacards - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name IN ('Administer Library Placards', 'Edit Library Placards')
				 AND p2.name = 'Administer All Placards'",

				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Edit Library Placards'
				 AND p2.name = 'Administer Library Placards'",

				// adminJavaScriptSnippets - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library JavaScript Snippets'
				 AND p2.name = 'Administer All JavaScript Snippets'",

				// adminSystemMessages - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library System Messages'
				 AND p2.name = 'Administer All System Messages'",

				// adminThemes - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library Themes'
				 AND p2.name = 'Administer All Themes'",

				// adminLayoutSettings - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library Layout Settings'
				 AND p2.name = 'Administer All Layout Settings'",

				// adminLibraries - priority: All > Home Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Home Library'
				 AND p2.name = 'Administer All Libraries'",

				// adminLocations - priority: All > Home Library Locations > Home Location
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name IN ('Administer Home Library Locations', 'Administer Home Location')
				 AND p2.name = 'Administer All Locations'",

				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Home Location'
				 AND p2.name = 'Administer Home Library Locations'",

				// adminHoldsReports - priority: All > Location
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'View Location Holds Reports'
				 AND p2.name = 'View All Holds Reports'",

				// adminStudentReports - priority: All > Location
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'View Location Student Reports'
				 AND p2.name = 'View All Student Reports'",

				// adminCollectionReports - priority: All > Location
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'View Location Collection Reports'
				 AND p2.name = 'View All Collection Reports'",

				// adminEcomReports - priority: All Libraries > Home Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'View eCommerce Reports for Home Library'
				 AND p2.name = 'View eCommerce Reports for All Libraries'",

				// adminDonationsReports - priority: All Libraries > Home Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'View Donations Reports for Home Library'
				 AND p2.name = 'View Donations Reports for All Libraries'",

				// adminEmailTemplates - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library Email Templates'
				 AND p2.name = 'Administer All Email Templates'",

				// adminEventsAdmin - priority: All Locations > Home Library Locations > Home Location
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name IN ('Administer Events for Home Library Locations', 'Administer Events for Home Location')
				 AND p2.name = 'Administer Events for All Locations'",

				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Events for Home Location'
				 AND p2.name = 'Administer Events for Home Library Locations'",

				// adminPrivateEvents - priority: All Locations > Home Library Locations > Home Location
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name IN ('View Private Events for Home Library Locations','View Private Events for Home Location')
				 AND p2.name = 'View Private Events for All Locations'",

				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'View Private Events for Home Location'
				 AND p2.name = 'View Private Events for Home Library Locations'",

				// adminEventsReports - priority: All Libraries > Home Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'View Event Reports for Home Library'
				 AND p2.name = 'View Event Reports for All Libraries'",

				// adminGroupedWorkDisplaySettings - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library Grouped Work Display Settings'
				 AND p2.name = 'Administer All Grouped Work Display Settings'",

				// adminGroupedWorkFacets - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library Grouped Work Facets'
				 AND p2.name = 'Administer All Grouped Work Facets'",

				// adminFormatSorting - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library Format Sorting'
				 AND p2.name = 'Administer All Format Sorting'",

				// adminVdxForms - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library VDX Forms'
				 AND p2.name = 'Administer All VDX Forms'",

				// adminLocalIllForms - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library Local ILL Forms'
				 AND p2.name = 'Administer All Local ILL Forms'",

				// adminSublocations - priority: All Libraries > Home Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Sublocations for Home Library'
				 AND p2.name = 'Administer Sublocations for All Libraries'",

				// adminWebBuilderMenus - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library Menus'
				 AND p2.name = 'Administer All Menus'",

				// adminBasicPages - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library Basic Pages'
				 AND p2.name = 'Administer All Basic Pages'",

				// adminCustomPages - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library Custom Pages'
				 AND p2.name = 'Administer All Custom Pages'",

				// adminCustomForms - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library Custom Forms'
				 AND p2.name = 'Administer All Custom Forms'",

				// adminWebResources - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library Web Resources'
				 AND p2.name = 'Administer All Web Resources'",

				// adminStaffMembers - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library Staff Members'
				 AND p2.name = 'Administer All Staff Members'",

				// adminWebsiteFacets - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library Website Facet Settings'
				 AND p2.name = 'Administer All Website Facet Settings'",

				// adminYearInReview - priority: All Libraries > Home Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Year in Review for Home Library'
				 AND p2.name = 'Administer Year in Review for All Libraries'",

				// adminQuickPolls - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library Quick Polls'
				 AND p2.name = 'Administer All Quick Polls'",

				// adminGrapesPages - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library Grapes Pages'
				 AND p2.name = 'Administer All Grapes Pages'",

				// adminCustomWebResourcePages - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library Custom Web Resource Pages'
				 AND p2.name = 'Administer All Custom Web Resource Pages'",

				// adminSideLoads - priority: All > Library > Library Scopes
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name IN ('Administer Side Loads for Home Library', 'Administer Side Load Scopes for Home Library')
				 AND p2.name = 'Administer Side Loads'",

				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Side Load Scopes for Home Library'
				 AND p2.name = 'Administer Side Loads for Home Library'",

				// adminWebContent - priority: All > Home Library
				// "Administer Web Content for Home Library" is a new permission as of 25.07.00, so this
				// DELETE operation should not be necessary, but best to make any corrections just in case.
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Web Content for Home Library'
				 AND p2.name = 'Administer All Web Content'",
			],
		], // cleanup_mutually_exclusive_permissions
		'update_administer_side_loads_name' => [
			'title' => 'Update "Administer Side Loads" Permission Name to "Administer All Side Loads"',
			'description' => 'Update the "Administer Side Loads" permission name to "Administer All Side Loads" to clarify its broad scope.',
			'continueOnError' => false,
			'sql' => [
				"UPDATE permissions SET name = 'Administer All Side Loads' WHERE name = 'Administer Side Loads'",
			],
		], // update_administer_side_loads_name

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
