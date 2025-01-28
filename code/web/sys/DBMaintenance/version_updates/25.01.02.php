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
				// 1) Create a temporary table with the same structure.
				"CREATE TABLE usage_by_user_agent_temp LIKE usage_by_user_agent",

				// 2) Insert aggregated data into temp table.
				"INSERT INTO usage_by_user_agent_temp (userAgentId, year, month, instance, numRequests, numBlockedRequests)
				 SELECT userAgentId, year, month, instance,
						SUM(numRequests) AS totalRequests,
						SUM(numBlockedRequests) AS totalBlocked
				 FROM usage_by_user_agent
				 GROUP BY userAgentId, year, month, instance",

				// 3) Clear out original table.
				"TRUNCATE TABLE usage_by_user_agent",

				// 4) Re-insert aggregated rows into original table.
				"INSERT INTO usage_by_user_agent
				 SELECT * FROM usage_by_user_agent_temp",

				// 5) Drop the temp table.
				"DROP TABLE usage_by_user_agent_temp",

				// 6) Finally, drop the old index and add the unique index.
				"ALTER TABLE usage_by_user_agent
				 DROP INDEX userAgentId,
				 ADD UNIQUE KEY userAgentId (userAgentId, year, month, instance)"
			],
		], //aggregate_usage_by_user_agent
	];
}
