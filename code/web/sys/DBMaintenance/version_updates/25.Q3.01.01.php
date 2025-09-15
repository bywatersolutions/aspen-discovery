<?php
function getUpdates25_Q3_01_01(): array {
	return [
		/*'name' => [
			 'title' => '',
			 'description' => '',
			 'continueOnError' => false,
			 'sql' => [
				 ''
			 ]
		 ], //name*/

		'remove_request_tracker_settings' => [
			'title' => 'Remove Request Tracker Settings & Permissions',
			'description' => 'Drop the request_tracker_connection table and remove RT permissions.',
			'continueOnError' => true,
			'sql' => [
				'TRUNCATE TABLE request_tracker_connection',
				'DELETE FROM role_permissions WHERE permissionId IN (SELECT id FROM permissions WHERE name = "Submit Ticket")',
				'DELETE FROM role_permissions WHERE permissionId IN (SELECT id FROM permissions WHERE name = "View Active Tickets")',
				'DELETE FROM role_permissions WHERE permissionId IN (SELECT id FROM permissions WHERE name = "Administer Request Tracker Connection")',
				'DELETE FROM role_permissions WHERE permissionId IN (SELECT id FROM permissions WHERE name = "Set Development Priorities")'
			]
		], //remove_request_tracker_settings


	];
}