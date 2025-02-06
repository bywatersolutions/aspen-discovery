<?php

function getUpdates25_01_02(): array {
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
		'aggregate_usage_by_user_agent' => [
			'title' => 'Aggregate usage_by_user_agent & add unique key',
			'description' => 'Combine multiple rows per (userAgentId, year, month, instance) into one row before adding unique key',
			'continueOnError' => true,
			'sql' => [
				// 3) Clear out original table.
				"TRUNCATE TABLE usage_by_user_agent",

				// 6) Finally, drop the old index and add the unique index.
				"ALTER TABLE usage_by_user_agent
				 DROP INDEX userAgentId,
				 ADD UNIQUE KEY userAgentId (userAgentId, year, month, instance)",

				// 5) Drop the temp table.
				"DROP TABLE IF EXISTS usage_by_user_agent_temp"
			],
		], //aggregate_usage_by_user_agent
	];
}
