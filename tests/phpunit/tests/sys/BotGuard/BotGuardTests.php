<?php
namespace sys\BotGuard;

use BotGuard;
use BotGuardCounters;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ProofOfWork;
use TrustCookie;

if (!defined('ROOT_DIR')) {
	define('ROOT_DIR', realpath(__DIR__ . '/../../../../../code/web'));
}
require_once ROOT_DIR . '/sys/BotGuard/BotGuard.php';

/** Array-backed stand-in for APCu with the same return semantics as BotGuardCounters. */
class FakeCounters extends BotGuardCounters {
	public bool $isAvailable = true;
	public array $values = [];

	public function available(): bool {
		return $this->isAvailable;
	}

	public function bump(string $key, int $ttl): ?int {
		if (!$this->isAvailable) {
			return null;
		}
		$this->values[$key] = ($this->values[$key] ?? 0) + 1;
		return $this->values[$key];
	}

	public function decrement(string $key, int $ttl): void {
		if ($this->isAvailable && isset($this->values[$key])) {
			$this->values[$key]--;
		}
	}
}

class BotGuardTests extends TestCase {
	const SECRET = 'unit test secret';
	const NOW = 1_800_000_000;
	const CRAWLER_IP = '66.249.66.1';

	private FakeCounters $counters;
	private string $rangesFile;

	protected function setUp(): void {
		$this->counters = new FakeCounters();
		$this->rangesFile = tempnam(sys_get_temp_dir(), 'crawlers');
		file_put_contents($this->rangesFile, json_encode(['ranges' => ['googlebot' => ['66.249.64.0/19', '2001:4860:4801:10::/64']]]));
	}

	protected function tearDown(): void {
		unlink($this->rangesFile);
	}

	private function guard(array $config = []): BotGuard {
		// Proof of work is off unless a test turns it on, so the issue endpoint tests mint on a plain fetch
		return new BotGuard($config + ['enabled' => true, 'pow_difficulty' => 0], self::SECRET, 'unit', $this->counters, null, $this->rangesFile);
	}

	private function decide(BotGuard $guard, string $uri, array $server = [], array $cookies = [], array $post = []): array {
		parse_str(parse_url($uri, PHP_URL_QUERY) ?? '', $get);
		$server += [
			'REQUEST_URI' => $uri,
			'REQUEST_METHOD' => 'GET',
			'REMOTE_ADDR' => '203.0.113.5',
			'HTTP_USER_AGENT' => 'Mozilla/5.0',
		];
		return $guard->decide($server, $cookies, $get, self::NOW, $post);
	}

	private function cookie(string $kind): array {
		return [TrustCookie::NAME => TrustCookie::issue($kind, self::SECRET, self::NOW)];
	}

	public static function lightPaths(): array {
		return [
			['/'],
			['/Search/Home'],
			['/Search/Results.php'],
			['/MyAccount/Home'],
			['/bookcover.php?id=1&size=small'],
			['/robots.txt'],
			['/Union/Search/extra'],
		];
	}

	#[DataProvider('lightPaths')]
	public function testLightPathsPassWithoutCookie(string $uri): void {
		$decision = $this->decide($this->guard(), $uri);
		$this->assertSame('pass', $decision['action']);
		$this->assertNull($decision['inflight_key']);
	}

	public static function heavyPaths(): array {
		return [
			['/Search/Results?lookfor=test'],
			['//Search/Results?lookfor=test'],
			['/Search/Results/'],
			['/?module=Search&action=Results&lookfor=test'],
			['/Union/Search?lookfor=test'],
			['/Author/Home?author=Austen'],
			['/GroupedWork/abc-123/Home'],
			['/Record/.b1234567'],
			// index.php falls back to the query parameters when the path matches none of its routes
			['/a/b/c?module=Search&action=Results&lookfor=test'],
			['/x/y/z?module=GroupedWork&action=Home&id=1'],
			['/x/y/z?module=Author&action=Home&author=x'],
		];
	}

	#[DataProvider('heavyPaths')]
	public function testHeavyPathsAreChallengedWithoutCookie(string $uri): void {
		$this->assertSame('challenge', $this->decide($this->guard(), $uri)['action']);
	}

	public function testQueryRouteReachesTheApiAndAjaxLanes(): void {
		$decision = $this->decide($this->guard(), '/a/b/c?module=API&action=SearchAPI&method=search');
		$this->assertSame('pass', $decision['action']);
		$this->assertSame(' id=ip:203.0.113.5 method=search', $decision['subject']);
		$this->assertSame(1, $this->counters->values['bg:unit:api:rl:ip:203.0.113.5']);

		$decision = $this->decide($this->guard(), '/a/b/c?module=Search&action=AJAX&method=getAutoSuggestList');
		$this->assertSame('pass', $decision['action']);
		$this->assertSame(1, $this->counters->values['bg:unit:rl:ip:203.0.113.5']);
	}

	public function testAjaxEndpointsAreNeverChallenged(): void {
		$guard = $this->guard(['api_rate_limit' => 2, 'max_concurrent' => 1]);
		foreach (['/Search/AJAX?method=getAutoSuggestList&searchTerm=x', '/Author/AJAX?method=x'] as $uri) {
			$decision = $this->decide($guard, $uri);
			$this->assertSame('pass', $decision['action']);
			$this->assertNull($decision['inflight_key']);
			$this->assertSame(' id=ip:203.0.113.5', $decision['subject']);
		}
		$decision = $this->decide($guard, '/GroupedWork/abc/AJAX?method=getWorkInfo');
		$this->assertSame('limited', $decision['action']);
		$this->assertSame(3, $decision['n']);
		$this->assertSame(2, $decision['limit']);
	}

	public function testAjaxWithACookieSpendsTheCookieWindowAndIsNotCapped(): void {
		$guard = $this->guard(['rate_limit' => 2, 'max_concurrent' => 1]);
		$cookies = $this->cookie(TrustCookie::KIND_ANONYMOUS);
		$this->counters->values['bg:unit:inflight'] = 1;
		$first = $this->decide($guard, '/GroupedWork/abc/AJAX?method=getWorkInfo', [], $cookies);
		$this->assertSame('pass', $first['action']);
		$this->assertNull($first['inflight_key']);
		$this->assertMatchesRegularExpression('/^ cid=[0-9a-f]{8}$/', $first['subject']);
		$this->assertSame('pass', $this->decide($guard, '/Record/.b1/AJAX?method=getCopies', [], $cookies)['action']);
		$this->assertSame('limited', $this->decide($guard, '/Search/AJAX?method=getAutoSuggestList', [], $cookies)['action']);
		$this->assertSame('pass', $this->decide($guard, '/Search/AJAX?method=getAutoSuggestList', [], $this->cookie(TrustCookie::KIND_USER))['action']);
	}

	public function testArrayValuedCookieAndPuzzleAreJustInvalid(): void {
		$this->assertSame('challenge', $this->decide($this->guard(), '/Search/Results', [], [TrustCookie::NAME => ['x']])['action']);
		$this->assertSame('pow_required', $this->decide($this->guard(['pow_difficulty' => 8]), '/BotGuard/Issue?pow[]=x&counter=1')['action']);
	}

	public function testPostToHeavyPathIsChallenged(): void {
		$this->assertSame('challenge', $this->decide($this->guard(), '/Search/Results', ['REQUEST_METHOD' => 'POST'])['action']);
	}

	public function testHeavyPathsCanBeOverridden(): void {
		$guard = $this->guard(['heavy_paths' => '~^/Hoopla/~']);
		$this->assertSame('challenge', $this->decide($guard, '/Hoopla/123/Home')['action']);
		$this->assertSame('pass', $this->decide($guard, '/Search/Results?lookfor=test')['action']);
	}

	public function testExemptIpPasses(): void {
		$guard = $this->guard(['exempt_ips' => '10.0.0.0/8 127.0.0.1']);
		$this->assertSame('pass', $this->decide($guard, '/Search/Results', ['REMOTE_ADDR' => '10.20.30.40'])['action']);
		$this->assertSame('pass', $this->decide($guard, '/Search/Results', ['REMOTE_ADDR' => '127.0.0.1'])['action']);
		$this->assertSame('challenge', $this->decide($guard, '/Search/Results', ['REMOTE_ADDR' => '11.0.0.1'])['action']);
	}

	public function testFeedViewPassesAndCountsInFlight(): void {
		$decision = $this->decide($this->guard(), '/Search/Results?lookfor=test&view=rss');
		$this->assertSame('pass', $decision['action']);
		$this->assertNotNull($decision['inflight_key']);
		$this->assertSame(1, $this->counters->values['bg:unit:inflight']);
	}

	public function testVerifiedCrawlerPasses(): void {
		$server = ['HTTP_USER_AGENT' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'];
		$decision = $this->decide($this->guard(), '/GroupedWork/abc/Home', $server + ['REMOTE_ADDR' => self::CRAWLER_IP]);
		$this->assertSame('pass', $decision['action']);
		$this->assertNotNull($decision['inflight_key']);
		$decision = $this->decide($this->guard(), '/GroupedWork/abc/Home', $server + ['REMOTE_ADDR' => '2001:4860:4801:10::5']);
		$this->assertSame('pass', $decision['action']);
	}

	public function testCrawlerUserAgentFromAnotherAddressIsChallenged(): void {
		$server = ['HTTP_USER_AGENT' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'];
		$this->assertSame('challenge', $this->decide($this->guard(), '/GroupedWork/abc/Home', $server)['action']);
	}

	public function testMissingRangesFileChallengesCrawlers(): void {
		$guard = new BotGuard(['enabled' => true], self::SECRET, 'unit', $this->counters, null, '/nonexistent/verified_crawlers.json');
		$server = ['HTTP_USER_AGENT' => 'Googlebot/2.1', 'REMOTE_ADDR' => self::CRAWLER_IP];
		$this->assertSame('challenge', $this->decide($guard, '/Search/Results', $server)['action']);
	}

	public function testUserCookiePassesWithoutCounting(): void {
		$guard = $this->guard(['rate_limit' => 1, 'max_concurrent' => 1]);
		for ($i = 0; $i < 5; $i++) {
			$decision = $this->decide($guard, '/Search/Results', [], $this->cookie(TrustCookie::KIND_USER));
			$this->assertSame('pass', $decision['action']);
			$this->assertNull($decision['inflight_key']);
		}
		$this->assertSame([], $this->counters->values);
	}

	public function testAnonymousCookieIsLimitedAfterTheWindowFills(): void {
		$guard = $this->guard(['rate_limit' => 2]);
		$cookies = $this->cookie(TrustCookie::KIND_ANONYMOUS);
		$this->assertSame('pass', $this->decide($guard, '/Search/Results', [], $cookies)['action']);
		$this->assertSame('pass', $this->decide($guard, '/Search/Results', [], $cookies)['action']);
		$decision = $this->decide($guard, '/Search/Results', [], $cookies);
		$this->assertSame('limited', $decision['action']);
		$this->assertSame(3, $decision['n']);
		$this->assertSame(2, $decision['limit']);
		$this->assertMatchesRegularExpression('/^ cid=[0-9a-f]{8}$/', $decision['subject']);
		$this->assertNull($decision['inflight_key']);
	}

	public function testEachCookieHasItsOwnWindow(): void {
		$guard = $this->guard(['rate_limit' => 1]);
		$first = $this->cookie(TrustCookie::KIND_ANONYMOUS);
		$second = $this->cookie(TrustCookie::KIND_ANONYMOUS);
		$this->assertSame('pass', $this->decide($guard, '/Search/Results', [], $first)['action']);
		$this->assertSame('limited', $this->decide($guard, '/Search/Results', [], $first)['action']);
		$this->assertSame('pass', $this->decide($guard, '/Search/Results', [], $second)['action']);
	}

	public function testInFlightCapRefusesAndDoesNotCount(): void {
		$guard = $this->guard(['max_concurrent' => 1]);
		$cookies = $this->cookie(TrustCookie::KIND_ANONYMOUS);
		$first = $this->decide($guard, '/Search/Results', [], $cookies);
		$this->assertSame('pass', $first['action']);
		$this->assertNotNull($first['inflight_key']);
		$second = $this->decide($guard, '/Search/Results', [], $cookies);
		$this->assertSame('capped', $second['action']);
		$this->assertSame(2, $second['n']);
		$this->assertSame(1, $second['limit']);
		$this->assertNull($second['inflight_key']);
		$this->assertSame(1, $this->counters->values['bg:unit:inflight']);
	}

	public function testZeroLimitsDisableThem(): void {
		$guard = $this->guard(['rate_limit' => 0, 'max_concurrent' => 0]);
		$cookies = $this->cookie(TrustCookie::KIND_ANONYMOUS);
		for ($i = 0; $i < 3; $i++) {
			$decision = $this->decide($guard, '/Search/Results', [], $cookies);
			$this->assertSame('pass', $decision['action']);
			$this->assertNull($decision['inflight_key']);
		}
	}

	public function testMissingCountersFailOpen(): void {
		$this->counters->isAvailable = false;
		$guard = $this->guard(['rate_limit' => 1, 'max_concurrent' => 1]);
		$cookies = $this->cookie(TrustCookie::KIND_ANONYMOUS);
		for ($i = 0; $i < 3; $i++) {
			$decision = $this->decide($guard, '/Search/Results', [], $cookies);
			$this->assertSame('pass', $decision['action']);
			$this->assertNull($decision['inflight_key']);
		}
		$this->assertSame('challenge', $this->decide($guard, '/Search/Results')['action']);
	}

	public function testExpiredUserCookieIsChallenged(): void {
		$guard = $this->guard(['cookie_ttl' => 100]);
		$expired = TrustCookie::issue(TrustCookie::KIND_USER, self::SECRET, self::NOW - 101);
		$this->assertSame('challenge', $this->decide($guard, '/Search/Results', [], [TrustCookie::NAME => $expired])['action']);
	}

	public function testObserveModeNeverDenies(): void {
		$guard = $this->guard(['mode' => 'observe', 'rate_limit' => 1, 'max_concurrent' => 1]);

		$decision = $this->decide($guard, '/Search/Results');
		$this->assertSame('pass', $decision['action']);
		$this->assertSame(['challenge'], $decision['observed']);
		$this->assertNotNull($decision['inflight_key']);

		$cookies = $this->cookie(TrustCookie::KIND_ANONYMOUS);
		$this->decide($guard, '/Search/Results', [], $cookies);
		$decision = $this->decide($guard, '/Search/Results', [], $cookies);
		$this->assertSame('pass', $decision['action']);
		$this->assertSame(['limited', 'capped'], $decision['observed']);
		$this->assertNotNull($decision['inflight_key']);
		$this->assertSame(3, $this->counters->values['bg:unit:inflight']);

		// A cookie-less request over the cap records both outcomes, so neither count is lost
		$this->assertSame(['challenge', 'capped'], $this->decide($guard, '/Search/Results')['observed']);
	}

	public function testDisabledGuardPassesEverything(): void {
		$guard = $this->guard(['enabled' => false]);
		$this->assertSame('pass', $this->decide($guard, '/Search/Results')['action']);
		$this->assertSame('issue', $this->decide($guard, '/BotGuard/Issue')['action']);
	}

	public function testMissingSecretPassesEverything(): void {
		$guard = new BotGuard(['enabled' => true], null, 'unit', $this->counters, null, $this->rangesFile);
		$this->assertSame('pass', $this->decide($guard, '/Search/Results')['action']);
		$decision = $this->decide($guard, '/BotGuard/Issue');
		$this->assertSame('issue', $decision['action']);
		$this->assertNull($decision['issued']);
	}

	public function testIssueEndpointMintsAnAnonymousCookie(): void {
		$decision = $this->decide($this->guard(), '/BotGuard/Issue');
		$this->assertSame('issue', $decision['action']);
		$cookie = TrustCookie::verify($decision['issued'], self::SECRET, 2592000, self::NOW);
		$this->assertSame('a', $cookie['kind']);
	}

	public function testIssueEndpointKeepsAnExistingCookie(): void {
		foreach ([TrustCookie::KIND_ANONYMOUS, TrustCookie::KIND_USER] as $kind) {
			$decision = $this->decide($this->guard(), '/BotGuard/Issue', [], $this->cookie($kind));
			$this->assertSame('issue', $decision['action']);
			$this->assertNull($decision['issued']);
		}
	}

	public function testIssueEndpointRejectsOtherMethods(): void {
		$this->assertSame('method_not_allowed', $this->decide($this->guard(), '/BotGuard/Issue', ['REQUEST_METHOD' => 'POST'])['action']);
	}

	public function testIssueEndpointIsLimitedPerAddress(): void {
		$guard = $this->guard(['issue_limit' => 2]);
		$this->assertNotNull($this->decide($guard, '/BotGuard/Issue')['issued']);
		$this->assertNotNull($this->decide($guard, '/BotGuard/Issue')['issued']);
		$decision = $this->decide($guard, '/BotGuard/Issue');
		$this->assertSame('issue_limited', $decision['action']);
		$this->assertSame(3, $decision['n']);
		$this->assertNull($decision['issued']);
		$this->assertNotNull($this->decide($guard, '/BotGuard/Issue', ['REMOTE_ADDR' => '203.0.113.6'])['issued']);
	}

	public function testIssueEndpointRequiresProofOfWork(): void {
		$guard = $this->guard(['pow_difficulty' => 8]);
		$decision = $this->decide($guard, '/BotGuard/Issue');
		$this->assertSame('pow_required', $decision['action']);
		$this->assertNull($decision['issued']);
		$this->assertSame([], $this->counters->values);

		$puzzle = ProofOfWork::issue(self::SECRET, 8, self::NOW);
		$counter = ProofOfWork::solve($puzzle, 8);
		$this->assertSame('pow_required', $this->decide($guard, "/BotGuard/Issue?pow=$puzzle&counter=" . ($counter + 1))['action']);

		$decision = $this->decide($guard, "/BotGuard/Issue?pow=$puzzle&counter=$counter");
		$this->assertSame('issue', $decision['action']);
		$cookie = TrustCookie::verify($decision['issued'], self::SECRET, 2592000, self::NOW);
		$this->assertSame('a', $cookie['kind']);
		$this->assertSame(explode('.', $puzzle)[1], $cookie['nonce']);

		// Replaying the solution buys the same identity, not a new one
		$replayed = TrustCookie::verify($this->decide($guard, "/BotGuard/Issue?pow=$puzzle&counter=$counter")['issued'], self::SECRET, 2592000, self::NOW);
		$this->assertSame($cookie['nonce'], $replayed['nonce']);

		// An easier puzzle than the configuration demands is refused even when solved
		$easy = ProofOfWork::issue(self::SECRET, 4, self::NOW);
		$this->assertSame('pow_required', $this->decide($guard, "/BotGuard/Issue?pow=$easy&counter=" . ProofOfWork::solve($easy, 4))['action']);
	}

	public function testExistingCookieSkipsProofOfWork(): void {
		$guard = $this->guard(['pow_difficulty' => 8]);
		$decision = $this->decide($guard, '/BotGuard/Issue', [], $this->cookie(TrustCookie::KIND_ANONYMOUS));
		$this->assertSame('issue', $decision['action']);
		$this->assertNull($decision['issued']);
	}

	public function testObserveModeNeverLimitsIssuing(): void {
		$guard = $this->guard(['mode' => 'observe', 'issue_limit' => 1]);
		$this->assertNotNull($this->decide($guard, '/BotGuard/Issue')['issued']);
		$decision = $this->decide($guard, '/BotGuard/Issue');
		$this->assertSame('issue', $decision['action']);
		$this->assertNotNull($decision['issued']);
		$this->assertSame(['issue_limited'], $decision['observed']);
	}

	public function testRequestPathMatchesIndexRouting(): void {
		$this->assertSame('/Search/Results', BotGuard::requestPath('/Search/Results?lookfor=x', []));
		$this->assertSame('/Search/Results', BotGuard::requestPath('//Search/Results', []));
		$this->assertSame('/Search/Results', BotGuard::requestPath('/', ['module' => 'Search', 'action' => 'Results']));
		$this->assertSame('/Search/Home', BotGuard::requestPath('/?module=Search', ['module' => 'Search']));
		$this->assertSame('/SearchResults/Home', BotGuard::requestPath('/', ['module' => 'Search/../Results']));
		$this->assertSame('/', BotGuard::requestPath('', []));
	}

	public function testApiIdentityPrecedence(): void {
		$everything = [
			'HTTP_AUTHORIZATION' => 'Bearer tok123',
			'HTTP_LIDA_SLUG' => 'app',
			'HTTP_LIDA_SESSIONID' => 'sess1',
			'PHP_AUTH_USER' => 'k1',
			'PHP_AUTH_PW' => 'k2',
			'REMOTE_ADDR' => '203.0.113.5',
		];
		$post = ['username' => '21234000123'];
		$this->assertSame('oauth:' . hash('sha256', 'tok123'), BotGuard::apiIdentity($everything, $post, []));
		unset($everything['HTTP_AUTHORIZATION']);
		$this->assertSame('patron:' . hash('sha256', '21234000123'), BotGuard::apiIdentity($everything, $post, []));
		$this->assertSame('patron:' . hash('sha256', '21234000123'), BotGuard::apiIdentity($everything, [], ['username' => '21234000123']));
		$this->assertSame('lida:' . hash('sha256', 'app|sess1|203.0.113.5'), BotGuard::apiIdentity($everything, [], []));
		unset($everything['HTTP_LIDA_SESSIONID']);
		$this->assertSame('token:' . hash('sha256', 'k1:k2'), BotGuard::apiIdentity($everything, [], []));
		unset($everything['PHP_AUTH_USER'], $everything['PHP_AUTH_PW']);
		$this->assertSame('ip:203.0.113.5', BotGuard::apiIdentity($everything, [], []));
		$this->assertSame('ip:203.0.113.5', BotGuard::apiIdentity($everything, ['username' => ['x']], []));
	}

	public function testApiIdentityNeverContainsTheCredential(): void {
		$server = ['HTTP_AUTHORIZATION' => 'Bearer secret-token', 'REMOTE_ADDR' => '203.0.113.5'];
		$this->assertStringNotContainsString('secret-token', BotGuard::apiIdentity($server, [], []));
		$this->assertStringNotContainsString('21234', BotGuard::apiIdentity([], ['username' => '21234000123'], []));
	}

	public function testApiPathsAreNeverChallenged(): void {
		$decision = $this->decide($this->guard(), '/API/SearchAPI?method=search&lookfor=test');
		$this->assertSame('pass', $decision['action']);
		$this->assertSame(' id=ip:203.0.113.5 method=search', $decision['subject']);
	}

	public function testApiWindowIsPerCredential(): void {
		$guard = $this->guard(['api_rate_limit' => 2]);
		$bearer = ['HTTP_AUTHORIZATION' => 'Bearer abc'];
		$this->assertSame('pass', $this->decide($guard, '/API/SystemAPI?method=getCurrentVersion', $bearer)['action']);
		$this->assertSame('pass', $this->decide($guard, '/API/SystemAPI?method=getCurrentVersion', $bearer)['action']);
		$decision = $this->decide($guard, '/API/SystemAPI?method=getCurrentVersion', $bearer);
		$this->assertSame('api_limited', $decision['action']);
		$this->assertSame(3, $decision['n']);
		$this->assertSame(2, $decision['limit']);
		$this->assertMatchesRegularExpression('/^ id=oauth:[0-9a-f]{8} method=getcurrentversion$/', $decision['subject']);
		$this->assertSame('pass', $this->decide($guard, '/API/SystemAPI?method=getCurrentVersion', ['HTTP_AUTHORIZATION' => 'Bearer def'])['action']);
		$this->assertSame('pass', $this->decide($guard, '/API/SystemAPI?method=getCurrentVersion')['action']);
	}

	public function testApiWindowFollowsThePatronNotTheSession(): void {
		$guard = $this->guard(['api_rate_limit' => 1]);
		$lida = ['HTTP_USER_AGENT' => 'Aspen LiDA', 'HTTP_LIDA_SLUG' => 'app', 'HTTP_LIDA_SESSIONID' => 'sess1', 'PHP_AUTH_USER' => 'k1', 'PHP_AUTH_PW' => 'k2'];
		$this->assertSame('pass', $this->decide($guard, '/API/UserAPI?method=getPatronProfile', $lida, [], ['username' => 'p1'])['action']);
		$this->assertSame('api_limited', $this->decide($guard, '/API/UserAPI?method=getPatronProfile', $lida, [], ['username' => 'p1'])['action']);
		$this->assertSame('pass', $this->decide($guard, '/API/UserAPI?method=getPatronProfile', $lida, [], ['username' => 'p2'])['action']);
		$this->assertSame('pass', $this->decide($guard, '/API/SearchAPI?method=search', $lida)['action']);
		$this->assertSame('api_limited', $this->decide($guard, '/API/SearchAPI?method=search', $lida)['action']);
		$this->assertSame('pass', $this->decide($guard, '/API/SearchAPI?method=search', ['HTTP_LIDA_SESSIONID' => 'sess2'] + $lida)['action']);
	}

	public function testSearchApiCountsTowardItsOwnCap(): void {
		$guard = $this->guard(['api_max_concurrent' => 1, 'max_concurrent' => 1]);
		$first = $this->decide($guard, '/API/SearchAPI?method=search&lookfor=a', ['HTTP_AUTHORIZATION' => 'Bearer a']);
		$this->assertSame('pass', $first['action']);
		$this->assertSame('bg:unit:api:inflight', $first['inflight_key']);
		$second = $this->decide($guard, '/API/SearchAPI?method=getAppBrowseCategoryResults', ['HTTP_AUTHORIZATION' => 'Bearer b']);
		$this->assertSame('api_capped', $second['action']);
		$this->assertSame(2, $second['n']);
		$this->assertNull($second['inflight_key']);
		$this->assertSame(1, $this->counters->values['bg:unit:api:inflight']);

		$cheap = $this->decide($guard, '/API/SystemAPI?method=getCurrentVersion', ['HTTP_AUTHORIZATION' => 'Bearer c']);
		$this->assertSame('pass', $cheap['action']);
		$this->assertNull($cheap['inflight_key']);

		$this->assertSame('api_capped', $this->decide($guard, '/API/ItemAPI?method=getDescriptionByTitleAndAuthor&title=x')['action']);
		$this->assertSame('pass', $this->decide($guard, '/API/ItemAPI?method=getAppGroupedWork&id=x', ['HTTP_AUTHORIZATION' => 'Bearer d'])['action']);

		// The web gauge is separate: an anonymous browser search still gets its own slot
		$web = $this->decide($guard, '/Search/Results?lookfor=a', [], $this->cookie(TrustCookie::KIND_ANONYMOUS));
		$this->assertSame('pass', $web['action']);
		$this->assertSame('bg:unit:inflight', $web['inflight_key']);
	}

	public function testApiExemptIpPasses(): void {
		$guard = $this->guard(['api_rate_limit' => 1, 'exempt_ips' => '127.0.0.1']);
		for ($i = 0; $i < 3; $i++) {
			$decision = $this->decide($guard, '/API/SystemAPI?method=runPendingDatabaseUpdates', ['REMOTE_ADDR' => '127.0.0.1']);
			$this->assertSame('pass', $decision['action']);
		}
		$this->assertSame([], $this->counters->values);
	}

	public function testApiObserveModeNeverDenies(): void {
		$guard = $this->guard(['mode' => 'observe', 'api_rate_limit' => 1, 'api_max_concurrent' => 1]);
		$this->decide($guard, '/API/SearchAPI?method=search');
		$decision = $this->decide($guard, '/API/SearchAPI?method=search');
		$this->assertSame('pass', $decision['action']);
		$this->assertSame(['api_limited', 'api_capped'], $decision['observed']);
		$this->assertNotNull($decision['inflight_key']);
		$decision = $this->decide($guard, '/API/SystemAPI?method=getCurrentVersion');
		$this->assertSame('pass', $decision['action']);
		$this->assertSame(['api_limited'], $decision['observed']);
	}

	public function testApiLimitsCanBeTurnedOffOrFailOpen(): void {
		$guard = $this->guard(['api_rate_limit' => 0, 'api_max_concurrent' => 0]);
		for ($i = 0; $i < 3; $i++) {
			$decision = $this->decide($guard, '/API/SearchAPI?method=search');
			$this->assertSame('pass', $decision['action']);
			$this->assertNull($decision['inflight_key']);
		}
		$this->counters->isAvailable = false;
		$guard = $this->guard(['api_rate_limit' => 1, 'api_max_concurrent' => 1]);
		for ($i = 0; $i < 3; $i++) {
			$this->assertSame('pass', $this->decide($guard, '/API/SearchAPI?method=search')['action']);
		}
	}

	public function testApiHeavyPathsCanBeOverridden(): void {
		$guard = $this->guard(['api_max_concurrent' => 1, 'api_heavy_paths' => '~^/API/ListAPI/?:getlisttitles$~']);
		$this->decide($guard, '/API/ListAPI?method=getListTitles&id=1', ['HTTP_AUTHORIZATION' => 'Bearer a']);
		$this->assertSame('api_capped', $this->decide($guard, '/API/ListAPI?method=getListTitles&id=2', ['HTTP_AUTHORIZATION' => 'Bearer b'])['action']);
		$this->assertSame('pass', $this->decide($guard, '/API/SearchAPI?method=search', ['HTTP_AUTHORIZATION' => 'Bearer c'])['action']);
	}

	public function testIpInRanges(): void {
		$this->assertTrue(BotGuard::ipInRanges('66.249.66.1', ['66.249.64.0/19']));
		$this->assertFalse(BotGuard::ipInRanges('66.249.96.1', ['66.249.64.0/19']));
		$this->assertTrue(BotGuard::ipInRanges('66.249.66.1', ['10.0.0.0/8', '66.249.66.1']));
		$this->assertTrue(BotGuard::ipInRanges('192.0.2.129', ['192.0.2.128/25']));
		$this->assertFalse(BotGuard::ipInRanges('192.0.2.127', ['192.0.2.128/25']));
		$this->assertTrue(BotGuard::ipInRanges('2001:4860:4801:10::5', ['2001:4860:4801:10::/64']));
		$this->assertFalse(BotGuard::ipInRanges('2001:4860:4801:11::5', ['2001:4860:4801:10::/64']));
		$this->assertFalse(BotGuard::ipInRanges('66.249.66.1', ['2001:4860:4801:10::/64']));
		$this->assertFalse(BotGuard::ipInRanges('not an ip', ['66.249.64.0/19']));
		$this->assertFalse(BotGuard::ipInRanges('66.249.66.1', ['garbage/8', '']));
	}
}
