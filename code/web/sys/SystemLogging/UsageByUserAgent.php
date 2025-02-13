<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class UsageByUserAgent extends DataObject {
	public $__table = 'usage_by_user_agent';
	public $id;
	public $userAgentId;
	public $instance;
	public $year;
	public $month;
	public $numRequests;
	public $numBlockedRequests;

	function objectHistoryEnabled() : bool {
		return false;
	}

	public function incrementNumRequests(): bool {
		return $this->query("UPDATE usage_by_user_agent SET numRequests = numRequests + 1 WHERE id = {$this->id}");
	}

	public function incrementNumBlockedRequests(): bool {
		return $this->query("UPDATE usage_by_user_agent SET numBlockedRequests = numBlockedRequests + 1 WHERE id = {$this->id}");
	}
}