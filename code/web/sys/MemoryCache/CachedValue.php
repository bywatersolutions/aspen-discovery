<?php /** @noinspection PhpMissingFieldTypeInspection */

class CachedValue extends DataObject {
	public $__table = 'cached_values';
	public $__primaryKey = 'cacheKey';
	public $cacheKey;
	/** @noinspection PhpUnused */
	public $valueType;
	public $value;
	public $expirationTime;

	public static function clearAllCachedValues(): bool {
		try {
			$cachedValue = new CachedValue();
			$cachedValue->deleteAll();
			return true;
		} catch (Exception $e) {
			global $logger;
			$logger->log('Unable to clear cached_values: ' . $e->getMessage(), Logger::LOG_ERROR);
			return false;
		}
	}
}