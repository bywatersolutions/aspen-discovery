<?php

/**
 * APCu-backed counters for BotGuard. APCu is shared memory across every PHP worker on the host, which is what
 * the per-cookie windows and the in-flight gauge need. Everything degrades to null when APCu is missing so the
 * guard fails open.
 */
class BotGuardCounters {
	public function available(): bool {
		return function_exists('apcu_inc') && apcu_enabled();
	}

	/**
	 * Add one to a counter, creating it with the given TTL on first use. Returns the new value, or null when APCu
	 * is unavailable. The TTL is never refreshed, so a fixed window ends when the key expires.
	 */
	public function bump(string $key, int $ttl): ?int {
		if (!$this->available()) {
			return null;
		}
		$value = apcu_inc($key, 1, $success, $ttl);
		return $value === false ? null : $value;
	}

	/**
	 * Subtract one, never below zero. A gauge key expires on its TTL even while requests are in flight, and each of
	 * those then decrements a key that apcu_dec() recreated at -1; clamping keeps the gauge from running up to
	 * max_concurrent slots in the red, so after an expiry it undercounts for a moment instead of overcounting.
	 */
	public function decrement(string $key, int $ttl): void {
		if (!$this->available()) {
			return;
		}
		$value = apcu_dec($key, 1, $success, $ttl);
		if ($value !== false && $value < 0) {
			apcu_store($key, 0, $ttl);
		}
	}
}
