<?php /** @noinspection PhpMissingFieldTypeInspection */

class UserOverDriveQRCodeToken extends DataObject {
	public $__table = 'overdrive_qr_sessions';
	public $id;
	public $userId;
	public $settingId;
	public $accessToken;
	public $refreshToken;
	public $tokenType;
	public $scope;
	public $expiresAt;
	public $created;
	public $updated;

	public function getNumericColumnNames(): array {
		return [
			'id',
			'userId',
			'settingId',
			'expiresAt',
			'created',
			'updated',
		];
	}

	public function getEncryptedFieldNames(): array {
		return [
			'accessToken',
			'refreshToken',
		];
	}

	public function isExpired(int $gracePeriod = 0): bool {
		if (empty($this->expiresAt)) {
			return true;
		}
		return $this->expiresAt <= (time() + $gracePeriod);
	}

	public function applyTokenResponse(stdClass $tokenData): void {
		$this->accessToken = $tokenData->access_token ?? '';
		$this->refreshToken = $tokenData->refresh_token ?? '';
		$this->tokenType = $tokenData->token_type ?? 'Bearer';
		$this->scope = $tokenData->scope ?? '';
		$expiresIn = $tokenData->expires_in ?? 0;
		$this->expiresAt = time() + (int)$expiresIn;
		if (empty($this->created)) {
			$this->created = time();
		}
		$this->updated = time();
	}

	public function toPatronTokenData(): stdClass {
		$data = new stdClass();
		$data->access_token = $this->accessToken;
		$data->refresh_token = $this->refreshToken;
		$data->token_type = $this->tokenType ?? 'Bearer';
		$data->scope = $this->scope ?? '';
		$remaining = max(0, ($this->expiresAt ?? 0) - time());
		$data->expires_in = $remaining;
		return $data;
	}
}
