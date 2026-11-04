<?php

/**
 * Signed trust cookie for BotGuard, '<kind>.<issued>.<nonce>.<hmac>', where kind is 'a' for a cookie minted
 * by the JavaScript challenge and 'u' for one issued at login. Pure functions so tests and the login code
 * can use it without globals.
 */
class TrustCookie {
	const NAME = 'aspen_trust';
	const KIND_ANONYMOUS = 'a';
	const KIND_USER = 'u';

	// A cookie whose issue time is further in the future than this is treated as forged
	const MAX_CLOCK_SKEW = 300;

	/** A cookie minted for a solved proof of work carries the puzzle's nonce, so the solution buys one identity. */
	public static function issue(string $kind, string $secret, ?int $now = null, ?string $nonce = null): string {
		$now = $now ?? time();
		$nonce = $nonce ?? bin2hex(random_bytes(8));
		return "$kind.$now.$nonce." . self::mac($kind, $now, $nonce, $secret);
	}

	/**
	 * @return array|null ['kind' => 'a'|'u', 'issued' => int, 'nonce' => string], or null when the cookie is
	 *                    missing, malformed, expired or tampered with
	 */
	public static function verify(mixed $value, string $secret, int $ttl, ?int $now = null): ?array {
		// A cookie named aspen_trust[] arrives as an array, which is just not a cookie
		if (!is_string($value) || !preg_match('/^([au])\.(\d{1,12})\.([0-9a-f]{16})\.([0-9a-f]{64})$/', $value, $matches)) {
			return null;
		}
		$now = $now ?? time();
		$kind = $matches[1];
		$issued = (int)$matches[2];
		$nonce = $matches[3];
		if ($issued > $now + self::MAX_CLOCK_SKEW || $issued + $ttl < $now) {
			return null;
		}
		if (!hash_equals(self::mac($kind, $issued, $nonce, $secret), $matches[4])) {
			return null;
		}
		return [
			'kind' => $kind,
			'issued' => $issued,
			'nonce' => $nonce,
		];
	}

	public static function cookieHeader(string $value, int $ttl, bool $secure): string {
		$header = self::NAME . "=$value; Path=/; Max-Age=$ttl; HttpOnly; SameSite=Lax";
		if ($secure) {
			$header .= '; Secure';
		}
		return $header;
	}

	private static function mac(string $kind, int $issued, string $nonce, string $secret): string {
		return hash_hmac('sha256', self::NAME . ".$kind.$issued.$nonce", $secret);
	}
}
