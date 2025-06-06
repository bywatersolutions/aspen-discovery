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
				    ('adminBrowseCategories','Local Enrichment','Administer Browse Categories','Choose the browse category scope for this role.'),
				    ('admincollectionSpotlights','Local Enrichment','Administer Collection Spotlights','Choose the collection spotlight scope for this role.'),
				    ('adminPlacards','Local Enrichment','Administer Placards','Choose the placards scope for this role.'),
				    ('adminJavaScriptSnippets','Local Enrichment','Administer JavaScript Snippets','Choose JavaScript Snippets administration scope for this role.'),
					('adminSystemMessages','Local Enrichment','Administer System Messages','Choose system messages administration scope for this role.'),
				    ('adminThemes','Theme & Layout','Administer Themes','Choose the theme administration scope for this role.'),
				    ('adminLayoutSettings','Theme & Layout','Administer Layout Settings','Choose the layout settings scope for this role.'),
				    ('adminLibraries','Primary Configuration','Administer Libraries','Choose the library administration scope for this role.'),
				    ('adminLocations','Primary Configuration','Administer Locations','Choose the location administration scope for this role.'),
					('adminHoldsReports','Circulation Reports','View Holds Reports','Choose the holds report view scope for this role.'),
					('adminStudentReports','Circulation Reports','View Student Reports','Choose the student report view scope for this role.'),
					('adminCollectionReports','Circulation Reports','View Collection Reports','Choose collection report view scope for this role.'),
					('adminEcomReports','eCommerce','View eCommerce Reports','Choose the eCommerce report view scope for this role.'),
					('adminDonationsReports','eCommerce','View Donations Reports','Choose the donations report view scope for this role.'),
					('adminEmailTemplates','Email','Administer Email Templates','Choose the email template administration scope for this role.'),
					('adminEventsAdmin','Events','Administer Events','Choose the event administration scope for this role.'),
					('adminPrivateEvents','Events','View Private Events','Choose private events view scope for this role.'),
					('adminEventsReports','Events','View Event Reports','Choose the event report view scope for this role.'),
					('adminGroupedWorkDisplaySettings','Grouped Work Display','Administer Grouped Work Display Settings','Choose grouped work display settings scope for this role.'),
					('adminGroupedWorkFacets','Grouped Work Display','Administer Grouped Work Facets','Choose grouped work facets scope for this role.'),
					('adminFormatSorting','Grouped Work Display','Administer Format Sorting','Choose format sorting scope for this role.'),
					('adminVdxForms','ILL Integration','Administer VDX Forms','Choose VDX forms administration scope for this role.'),
					('adminLocalIllForms','ILL Integration','Administer Local ILL Forms','Choose Local ILL forms administration scope for this role.'),
					('adminSublocations','Primary Configuration - Location Sublocations','Administer Sublocations','Choose sublocations administration scope for this role.'),
					('adminWebBuilderMenus','Web Builder','Administer Menus','Choose menu administration scope for this role.'),
					('adminBasicPages','Web Builder','Administer Basic Pages','Choose basic pages administration scope for this role.'),
					('adminCustomPages','Web Builder','Administer Custom Pages','Choose custom pages administration scope for this role.'),
					('adminCustomForms','Web Builder','Administer Custom Forms','Choose custom forms administration scope for this role.'),
					('adminWebResources','Web Builder','Administer Web Resources','Choose web resources administration scope for this role.'),
					('adminStaffMembers','Web Builder','Administer Staff Members','Choose staff members administration scope for this role.'),
					('adminWebsiteFacets','Website Indexing','Administer Website Facets','Choose website facet administration scope for this role.'),
					('adminYearInReview','Year in Review','Administer Year in Review','Choose Year in Review administration scope for this role.'),
					('adminQuickPolls','Web Builder','Administer Quick Polls','Choose quick polls administration scope for this role.'),
					('adminGrapesPages','Web Builder','Administer Grapes Pages','Choose grapes pages administration scope for this role.'),
					('adminCustomWebResourcePages','Web Builder','Administer Custom Web Resource Pages','Choose custom web resource pages administration scope for this role.')",
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
			],
		], //permission_groups_and_mappings
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
			],
		], //cleanup_mutually_exclusive_permissions

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
