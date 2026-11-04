<?php

/**
 * Stateless proof of work for the BotGuard challenge. The server signs a puzzle '<issued>.<nonce>.<difficulty>.<hmac>'
 * and the browser finds a counter such that sha256('<puzzle>.<counter>') starts with <difficulty> zero bits. The nonce
 * of a solved puzzle becomes the trust cookie's nonce, so one solution buys exactly one identity: replaying it yields
 * a cookie with the same budget, not a fresh one. Pure functions, no globals.
 */
class ProofOfWork {
	// Seconds a puzzle stays solvable: long enough for a slow phone, short enough to bound replays
	const TTL = 300;

	public static function issue(string $secret, int $difficulty, ?int $now = null): string {
		$now = $now ?? time();
		$nonce = bin2hex(random_bytes(8));
		return "$now.$nonce.$difficulty." . self::mac($now, $nonce, $difficulty, $secret);
	}

	/**
	 * The puzzle's nonce when the counter solves a genuine, unexpired puzzle of at least the required difficulty,
	 * otherwise null.
	 */
	public static function verify(mixed $puzzle, mixed $counter, string $secret, int $difficulty, ?int $now = null): ?string {
		if (!is_string($puzzle) || !is_string($counter) || !preg_match('/^(\d{1,12})\.([0-9a-f]{16})\.(\d{1,2})\.([0-9a-f]{64})$/', $puzzle, $matches) || !preg_match('/^\d{1,12}$/', $counter)) {
			return null;
		}
		$now = $now ?? time();
		$issued = (int)$matches[1];
		$nonce = $matches[2];
		$puzzleDifficulty = (int)$matches[3];
		if ($puzzleDifficulty < $difficulty || $issued > $now + TrustCookie::MAX_CLOCK_SKEW || $issued + self::TTL < $now) {
			return null;
		}
		if (!hash_equals(self::mac($issued, $nonce, $puzzleDifficulty, $secret), $matches[4])) {
			return null;
		}
		if (self::leadingZeroBits(hash('sha256', "$puzzle.$counter", true)) < $puzzleDifficulty) {
			return null;
		}
		return $nonce;
	}

	public static function leadingZeroBits(string $binary): int {
		$bits = 0;
		foreach (str_split($binary) as $byte) {
			$value = ord($byte);
			if ($value == 0) {
				$bits += 8;
				continue;
			}
			while (!($value & 0x80)) {
				$value <<= 1;
				$bits++;
			}
			break;
		}
		return $bits;
	}

	/** Brute force a solution; for tests and command line checks, the browser has its own solver. */
	public static function solve(string $puzzle, int $difficulty): int {
		for ($counter = 0; ; $counter++) {
			if (self::leadingZeroBits(hash('sha256', "$puzzle.$counter", true)) >= $difficulty) {
				return $counter;
			}
		}
	}

	private static function mac(int $issued, string $nonce, int $difficulty, string $secret): string {
		return hash_hmac('sha256', "pow.$issued.$nonce.$difficulty", $secret);
	}
}
