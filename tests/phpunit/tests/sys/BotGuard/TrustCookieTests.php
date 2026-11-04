<?php
namespace sys\BotGuard;

use PHPUnit\Framework\TestCase;
use TrustCookie;

require_once __DIR__ . '/../../../../../code/web/sys/BotGuard/TrustCookie.php';

class TrustCookieTests extends TestCase {
	const SECRET = 'unit test secret';
	const TTL = 3600;

	public function testIssuedCookieVerifies(): void {
		$now = 1_800_000_000;
		$value = TrustCookie::issue(TrustCookie::KIND_ANONYMOUS, self::SECRET, $now);
		$this->assertMatchesRegularExpression('/^a\.1800000000\.[0-9a-f]{16}\.[0-9a-f]{64}$/', $value);

		$cookie = TrustCookie::verify($value, self::SECRET, self::TTL, $now + 100);
		$this->assertSame('a', $cookie['kind']);
		$this->assertSame($now, $cookie['issued']);
		$this->assertSame(16, strlen($cookie['nonce']));

		$value = TrustCookie::issue(TrustCookie::KIND_USER, self::SECRET, $now);
		$this->assertSame('u', TrustCookie::verify($value, self::SECRET, self::TTL, $now)['kind']);
	}

	public function testNoncesDiffer(): void {
		$first = TrustCookie::issue(TrustCookie::KIND_ANONYMOUS, self::SECRET, 1_800_000_000);
		$second = TrustCookie::issue(TrustCookie::KIND_ANONYMOUS, self::SECRET, 1_800_000_000);
		$this->assertNotSame($first, $second);
	}

	public function testTamperedMacIsRejected(): void {
		$value = TrustCookie::issue(TrustCookie::KIND_ANONYMOUS, self::SECRET, 1_800_000_000);
		$flipped = substr($value, 0, -1) . (substr($value, -1) == '0' ? '1' : '0');
		$this->assertNull(TrustCookie::verify($flipped, self::SECRET, self::TTL, 1_800_000_000));
	}

	public function testKindIsCoveredByTheMac(): void {
		$value = TrustCookie::issue(TrustCookie::KIND_ANONYMOUS, self::SECRET, 1_800_000_000);
		$upgraded = 'u' . substr($value, 1);
		$this->assertNull(TrustCookie::verify($upgraded, self::SECRET, self::TTL, 1_800_000_000));
	}

	public function testWrongSecretIsRejected(): void {
		$value = TrustCookie::issue(TrustCookie::KIND_ANONYMOUS, self::SECRET, 1_800_000_000);
		$this->assertNull(TrustCookie::verify($value, 'another secret', self::TTL, 1_800_000_000));
	}

	public function testExpiredCookieIsRejected(): void {
		$now = 1_800_000_000;
		$value = TrustCookie::issue(TrustCookie::KIND_ANONYMOUS, self::SECRET, $now);
		$this->assertNotNull(TrustCookie::verify($value, self::SECRET, self::TTL, $now + self::TTL));
		$this->assertNull(TrustCookie::verify($value, self::SECRET, self::TTL, $now + self::TTL + 1));
	}

	public function testCookieFromTheFutureIsRejected(): void {
		$now = 1_800_000_000;
		$value = TrustCookie::issue(TrustCookie::KIND_ANONYMOUS, self::SECRET, $now + TrustCookie::MAX_CLOCK_SKEW + 1);
		$this->assertNull(TrustCookie::verify($value, self::SECRET, self::TTL, $now));
		$tolerated = TrustCookie::issue(TrustCookie::KIND_ANONYMOUS, self::SECRET, $now + TrustCookie::MAX_CLOCK_SKEW);
		$this->assertNotNull(TrustCookie::verify($tolerated, self::SECRET, self::TTL, $now));
	}

	public function testMalformedValuesAreRejected(): void {
		foreach ([null, '', 'a', 'a.1.2.3', 'z.1800000000.0123456789abcdef.' . str_repeat('0', 64), 'a.1800000000.0123.' . str_repeat('0', 64)] as $value) {
			$this->assertNull(TrustCookie::verify($value, self::SECRET, self::TTL, 1_800_000_000));
		}
	}

	public function testCookieHeader(): void {
		$this->assertSame('aspen_trust=a.1.2.3; Path=/; Max-Age=60; HttpOnly; SameSite=Lax', TrustCookie::cookieHeader('a.1.2.3', 60, false));
		$this->assertSame('aspen_trust=a.1.2.3; Path=/; Max-Age=60; HttpOnly; SameSite=Lax; Secure', TrustCookie::cookieHeader('a.1.2.3', 60, true));
	}
}
