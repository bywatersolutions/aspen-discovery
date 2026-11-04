<?php

require_once ROOT_DIR . '/sys/BotGuard/TrustCookie.php';
require_once ROOT_DIR . '/sys/BotGuard/ProofOfWork.php';
require_once ROOT_DIR . '/sys/BotGuard/BotGuardCounters.php';
require_once ROOT_DIR . '/sys/Utils/EncryptionUtils.php';

/**
 * Origin-side admission control for anonymous heavy requests (search results, author pages, grouped work and
 * record pages and their AJAX calls). Runs from bootstrap.php before the database is opened, so a refused
 * request costs no connection, session row, analytics write, Solr query or Smarty render.
 *
 * A heavy request without a valid trust cookie gets a small page whose JavaScript fetches /BotGuard/Issue and
 * reloads, after solving a proof of work so that every anonymous identity costs a scraper CPU time. Anonymous
 * cookies get a per-cookie request window and share an in-flight cap; cookies issued at login (kind 'u') are
 * never limited. API requests are never challenged; each gets a window keyed on the
 * credential it carries and the Solr-backed API shares a cap of its own. Configured by the [BotGuard] section
 * of config.ini.
 */
class BotGuard {
	const ISSUE_PATH = '/BotGuard/Issue';

	// The gauge key is never refreshed, so a count leaked by a killed worker clears itself within this many seconds
	const INFLIGHT_TTL = 120;

	// In observe mode every cookie-less heavy request would be challenged, so log one line per this many
	const OBSERVE_CHALLENGE_SAMPLE = 1000;

	const DEFAULT_HEAVY_PATHS = [
		'~^/Search/Results/?$~',
		'~^/Union/Search/?$~',
		'~^/Author/Home/?$~',
		'~^/(GroupedWork|Record)/~',
	];

	// XHR endpoints the pages above and the home page call. They cannot run the challenge, so they only get a window:
	// per cookie when the browser has one, per address when it does not (autosuggest and spotlights fire before any
	// heavy page has minted a cookie). They are checked before the heavy patterns.
	const DEFAULT_AJAX_PATHS = [
		'~^/(Search|Author)/AJAX/?$~',
		'~^/(GroupedWork|Record)/([^/]+/)?AJAX/?$~',
	];

	// Matched against '<path>:<method>' with the method lowercased, the way handleAPIRequestAuto() compares it
	const DEFAULT_API_HEAVY_PATHS = [
		'~^/API/SearchAPI/?:~',
		'~^/API/ItemAPI/?:getdescriptionbytitleandauthor$~',
	];

	private static ?BotGuard $instance = null;

	private bool $observe;
	private int $cookieTtl;
	private int $rateLimit;
	private int $rateWindow;
	private int $maxConcurrent;
	private int $issueLimit;
	private int $powDifficulty;
	private array $exemptIps;
	private array $heavyPaths;
	private array $ajaxPaths;
	private string $crawlerUserAgents;
	private int $apiRateLimit;
	private int $apiRateWindow;
	private int $apiMaxConcurrent;
	private array $apiHeavyPaths;
	private string $crawlerRangesFile;
	private ?array $crawlerRanges = null;
	// Null when the guard is disabled or the site has no passkey, which makes it pass everything
	private ?string $secret;
	private string $keyPrefix;
	private BotGuardCounters $counters;
	private ?Logger $logger;

	public function __construct(array $config, ?string $secret, string $site, BotGuardCounters $counters, ?Logger $logger = null, ?string $crawlerRangesFile = null) {
		$this->observe = ($config['mode'] ?? 'enforce') == 'observe';
		$this->cookieTtl = (int)($config['cookie_ttl'] ?? 2592000);
		$this->rateLimit = (int)($config['rate_limit'] ?? 120);
		$this->rateWindow = (int)($config['rate_window'] ?? 60);
		$this->maxConcurrent = (int)($config['max_concurrent'] ?? 4);
		$this->issueLimit = (int)($config['issue_limit'] ?? 30);
		$this->powDifficulty = (int)($config['pow_difficulty'] ?? 18);
		$this->exemptIps = self::splitList($config['exempt_ips'] ?? '127.0.0.1 ::1');
		$this->heavyPaths = empty($config['heavy_paths']) ? self::DEFAULT_HEAVY_PATHS : self::splitList($config['heavy_paths']);
		$this->ajaxPaths = empty($config['ajax_paths']) ? self::DEFAULT_AJAX_PATHS : self::splitList($config['ajax_paths']);
		$this->crawlerUserAgents = $config['crawler_user_agents'] ?? 'Googlebot|bingbot|Applebot|DuckDuckBot';
		$this->apiRateLimit = (int)($config['api_rate_limit'] ?? 300);
		$this->apiRateWindow = (int)($config['api_rate_window'] ?? 60);
		$this->apiMaxConcurrent = (int)($config['api_max_concurrent'] ?? 4);
		$this->apiHeavyPaths = empty($config['api_heavy_paths']) ? self::DEFAULT_API_HEAVY_PATHS : self::splitList($config['api_heavy_paths']);
		$this->crawlerRangesFile = $crawlerRangesFile ?? ROOT_DIR . '/../../sites/default/conf/verified_crawlers.json';
		$this->secret = empty($config['enabled']) ? null : $secret;
		$this->keyPrefix = "bg:$site:";
		$this->counters = $counters;
		$this->logger = $logger;
		if (!empty($config['enabled']) && $secret === null) {
			$this->warnOncePerMinute('botguard event=disabled reason=no_passkey');
		} elseif ($this->secret !== null && !$counters->available()) {
			$this->warnOncePerMinute('botguard event=limits_off reason=no_apcu');
		}
	}

	/** A misconfiguration holds for every request, so one ERROR line a minute says it without flooding messages.log. */
	private function warnOncePerMinute(string $line): void {
		if ($this->logger === null) {
			return;
		}
		$marker = sys_get_temp_dir() . '/botguard-' . md5($this->keyPrefix . $line);
		if (!file_exists($marker) || filemtime($marker) < time() - 60) {
			touch($marker);
			$this->logger->log($line, Logger::LOG_ERROR);
		}
	}

	public static function instance(): BotGuard {
		if (self::$instance === null) {
			global $configArray;
			global $serverName;
			global $logger;
			$config = $configArray['BotGuard'] ?? [];
			// Derive the cookie key from the site passkey so the raw key never leaves EncryptionUtils
			$secret = empty($config['enabled']) ? null : (EncryptionUtils::hmacSha256('BotGuard trust cookie key') ?: null);
			self::$instance = new BotGuard($config, $secret, (string)$serverName, new BotGuardCounters(), $logger);
		}
		return self::$instance;
	}

	/** Entry point from bootstrap.php. Sends a response and exits when the request is refused. */
	public static function guardRequest(): void {
		if (PHP_SAPI == 'cli' || empty($_SERVER['REQUEST_URI'])) {
			return;
		}
		$server = $_SERVER;
		// Apache does not always expose the Authorization header as a CGI variable, so read it the way AbstractAPI does
		if (!isset($server['HTTP_AUTHORIZATION']) && function_exists('getallheaders')) {
			$server['HTTP_AUTHORIZATION'] = array_change_key_case(getallheaders())['authorization'] ?? null;
		}
		self::instance()->handle($server, $_COOKIE, $_GET, $_POST);
	}

	/** Called from index.php once the login state is known. Logged-in users get the unlimited 'u' cookie. */
	public static function syncLoginCookie(bool $isLoggedIn): void {
		$guard = $isLoggedIn ? self::active() : null;
		if ($guard === null) {
			return;
		}
		$cookie = TrustCookie::verify($_COOKIE[TrustCookie::NAME] ?? null, $guard->secret, $guard->cookieTtl);
		if ($cookie === null || $cookie['kind'] != TrustCookie::KIND_USER) {
			$guard->sendCookie(TrustCookie::issue(TrustCookie::KIND_USER, $guard->secret), $_SERVER);
		}
	}

	/** Called at logout so a shared computer is back to an anonymous budget without being challenged again. */
	public static function downgradeCookie(): void {
		$guard = self::active();
		if ($guard !== null) {
			$guard->sendCookie(TrustCookie::issue(TrustCookie::KIND_ANONYMOUS, $guard->secret), $_SERVER);
		}
	}

	/** The guard, or null when cookies must be left alone: under the CLI, when disabled, or without a passkey. */
	private static function active(): ?BotGuard {
		if (PHP_SAPI == 'cli') {
			return null;
		}
		$guard = self::instance();
		return $guard->secret === null ? null : $guard;
	}

	/**
	 * Decide what to do with a request without sending anything, so the rules can be unit tested.
	 *
	 * @return array action is one of pass, challenge, limited, capped, api_limited, api_capped, issue (a 204,
	 *               with the new cookie in issued when one was minted), issue_limited, pow_required or
	 *               method_not_allowed;
	 *               inflight_key names the gauge the request was counted toward and must decrement at shutdown;
	 *               observed lists the actions observe mode would have taken; subject is the part of the log
	 *               line that names the cookie or credential.
	 */
	public function decide(array $server, array $cookies, array $get, ?int $now = null, array $post = []): array {
		$now = $now ?? time();
		$path = self::requestPath($server['REQUEST_URI'] ?? '/', $get);
		$result = [
			'action' => 'pass',
			'path' => $path,
			'subject' => ' cid=-',
			'n' => 0,
			'limit' => 0,
			'inflight_key' => null,
			'observed' => [],
			'issued' => null,
		];
		if ($path == self::ISSUE_PATH) {
			return $this->decideIssue($server, $cookies, $get, $now, $result);
		}
		$ip = $server['REMOTE_ADDR'] ?? '';
		if ($this->secret === null || self::ipInRanges($ip, $this->exemptIps)) {
			return $result;
		}
		// index.php routes by the path when one of its patterns matches and by ?module=&action= otherwise, and the
		// guard cannot know which (record modules come from the database), so a request is whatever either says
		$routes = [$path];
		if (!empty($get['module']) && is_string($get['module'])) {
			$action = is_string($get['action'] ?? null) ? $get['action'] : 'Home';
			$routes[] = '/' . preg_replace('/[^\w]/', '', $get['module']) . '/' . preg_replace('/[^\w]/', '', $action);
		}
		$api = $ajax = $heavy = false;
		foreach ($routes as $route) {
			$api = $api || str_starts_with($route, '/API/');
			$ajax = $ajax || self::matchesAny($this->ajaxPaths, $route);
			$heavy = $heavy || self::matchesAny($this->heavyPaths, $route);
		}
		if ($api) {
			return $this->decideApi($server, $get, $post, $result);
		}
		if (!$ajax && !$heavy) {
			return $result;
		}
		$cookie = TrustCookie::verify($cookies[TrustCookie::NAME] ?? null, $this->secret, $this->cookieTtl, $now);
		if ($cookie !== null) {
			if ($cookie['kind'] == TrustCookie::KIND_USER) {
				return $result;
			}
			$result['subject'] = ' cid=' . substr($cookie['nonce'], 0, 8);
		}

		if ($ajax) {
			if ($cookie !== null) {
				if ($this->rateLimit > 0) {
					$this->exceeds($this->keyPrefix . 'rl:' . $cookie['nonce'], $this->rateWindow, $this->rateLimit, 'limited', $result);
				}
			} elseif ($this->apiRateLimit > 0) {
				// No cookie yet, so the address is the identity, with the budget an unauthenticated API call gets
				$result['subject'] = " id=ip:$ip";
				$this->exceeds($this->keyPrefix . 'rl:ip:' . $ip, $this->apiRateWindow, $this->apiRateLimit, 'limited', $result);
			}
			return $result;
		}

		// Feed readers and search engines cannot run the challenge, so they only count toward the in-flight cap
		if ($cookie === null && ($get['view'] ?? '') != 'rss' && !$this->isVerifiedCrawler($server['HTTP_USER_AGENT'] ?? '', $ip)) {
			if (!$this->observe) {
				$result['action'] = 'challenge';
				return $result;
			}
			$result['observed'][] = 'challenge';
		}

		if ($cookie !== null && $this->rateLimit > 0 && $this->exceeds($this->keyPrefix . 'rl:' . $cookie['nonce'], $this->rateWindow, $this->rateLimit, 'limited', $result)) {
			return $result;
		}
		if ($this->maxConcurrent > 0) {
			$this->exceeds($this->keyPrefix . 'inflight', self::INFLIGHT_TTL, $this->maxConcurrent, 'capped', $result, true);
		}
		return $result;
	}

	private function decideApi(array $server, array $get, array $post, array $result): array {
		$method = $get['method'] ?? $post['method'] ?? '';
		$method = is_string($method) ? strtolower($method) : '';
		$identity = self::apiIdentity($server, $post, $get);
		// The kind plus the first 8 hex characters of the credential hash; an address is left whole
		$loggedIdentity = str_starts_with($identity, 'ip:') ? $identity : substr($identity, 0, strpos($identity, ':') + 9);
		$result['subject'] = " id=$loggedIdentity method=$method";
		if ($this->apiRateLimit > 0 && $this->exceeds($this->keyPrefix . 'api:rl:' . $identity, $this->apiRateWindow, $this->apiRateLimit, 'api_limited', $result)) {
			return $result;
		}
		if ($this->apiMaxConcurrent > 0 && self::matchesAny($this->apiHeavyPaths, "{$result['path']}:$method")) {
			$this->exceeds($this->keyPrefix . 'api:inflight', self::INFLIGHT_TTL, $this->apiMaxConcurrent, 'api_capped', $result, true);
		}
		return $result;
	}

	private function decideIssue(array $server, array $cookies, array $get, int $now, array $result): array {
		if (($server['REQUEST_METHOD'] ?? 'GET') != 'GET') {
			$result['action'] = 'method_not_allowed';
			return $result;
		}
		// A stale challenge page must never loop, so a disabled guard still answers 204
		$result['action'] = 'issue';
		if ($this->secret === null) {
			return $result;
		}
		// A cookie keeper cannot reset its budget, and a 'u' cookie is never downgraded here
		if (TrustCookie::verify($cookies[TrustCookie::NAME] ?? null, $this->secret, $this->cookieTtl, $now) !== null) {
			return $result;
		}
		// The solved puzzle's nonce becomes the cookie's nonce, so a replayed solution gets the same budget, not a new one
		$nonce = null;
		if ($this->powDifficulty > 0) {
			$nonce = ProofOfWork::verify($get['pow'] ?? null, $get['counter'] ?? null, $this->secret, $this->powDifficulty, $now);
			if ($nonce === null) {
				$result['action'] = 'pow_required';
				return $result;
			}
		}
		if ($this->issueLimit > 0 && $this->exceeds($this->keyPrefix . 'issue:' . ($server['REMOTE_ADDR'] ?? ''), 60, $this->issueLimit, 'issue_limited', $result)) {
			return $result;
		}
		$result['issued'] = TrustCookie::issue(TrustCookie::KIND_ANONYMOUS, $this->secret, $now, $nonce);
		return $result;
	}

	/**
	 * Count the request against a window, or against an in-flight gauge when $gauge is set. Returns true when it
	 * must be refused; observe mode records the refusal in 'observed' instead and returns false. A gauge's key goes
	 * in 'inflight_key' so the request gives its slot back at shutdown, unless it is refused here.
	 */
	private function exceeds(string $key, int $ttl, int $limit, string $action, array &$result, bool $gauge = false): bool {
		$n = $this->counters->bump($key, $ttl);
		if ($n === null) {
			return false;
		}
		if ($gauge) {
			$result['inflight_key'] = $key;
		}
		if ($n <= $limit) {
			return false;
		}
		$result['n'] = $n;
		$result['limit'] = $limit;
		if ($this->observe) {
			$result['observed'][] = $action;
			return false;
		}
		if ($gauge) {
			$this->counters->decrement($key, $ttl);
			$result['inflight_key'] = null;
		}
		$result['action'] = $action;
		return true;
	}

	public function handle(array $server, array $cookies, array $get, array $post = []): void {
		$decision = $this->decide($server, $cookies, $get, null, $post);
		if ($decision['inflight_key'] !== null) {
			register_shutdown_function([$this->counters, 'decrement'], $decision['inflight_key'], self::INFLIGHT_TTL);
		}
		foreach ($decision['observed'] as $observed) {
			$this->logObserved($observed, $decision, $server);
		}
		$action = $decision['action'];
		if ($action == 'pass') {
			return;
		}
		if ($action == 'challenge') {
			$this->sendChallenge();
		} elseif ($action == 'issue') {
			header('Cache-Control: no-store, private');
			if ($decision['issued'] !== null) {
				$this->sendCookie($decision['issued'], $server);
			}
			http_response_code(204);
		} elseif ($action == 'pow_required') {
			header('Content-Type: application/json');
			header('Cache-Control: no-store, private');
			http_response_code(400);
			echo json_encode(['error' => 'proof_of_work_required']);
		} elseif ($action == 'method_not_allowed') {
			header('Allow: GET');
			http_response_code(405);
		} else {
			$denials = [
				'limited' => [429, $this->rateWindow, 'Too many requests', 'Please wait a minute and try your search again.'],
				'capped' => [503, 2, 'The catalog is busy', 'Please try again in a moment.'],
				'issue_limited' => [429, 60, 'Too many requests', 'Please wait a minute and try again.'],
				// API clients get the error codes OAuth2RateLimiter::sendRateLimitResponse() already uses
				'api_limited' => [429, $this->apiRateWindow, 'rate_limit_exceeded', 'Too many requests for this credential. Please wait and try again.'],
				'api_capped' => [503, 2, 'server_busy', 'The search service is busy. Please try again in a moment.'],
			];
			$this->log($action, $decision, $server);
			$this->sendDenied(str_starts_with($action, 'api_'), ...$denials[$action]);
		}
		exit;
	}

	/**
	 * The credential an API request carries, hashed so nothing secret reaches APCu or the log. First match
	 * wins: OAuth2 bearer token, patron username, LiDA session, HTTP Basic key pair, client address.
	 */
	public static function apiIdentity(array $server, array $post, array $get): string {
		if (preg_match('/^\s*Bearer\s+(\S+)/i', $server['HTTP_AUTHORIZATION'] ?? '', $matches)) {
			return 'oauth:' . hash('sha256', $matches[1]);
		}
		$username = $post['username'] ?? $get['username'] ?? '';
		if (is_string($username) && $username !== '') {
			return 'patron:' . hash('sha256', $username);
		}
		if (!empty($server['HTTP_LIDA_SESSIONID'])) {
			// Before login every install of an app shares one session id, so the address keeps this from being one app-wide bucket
			return 'lida:' . hash('sha256', ($server['HTTP_LIDA_SLUG'] ?? '') . '|' . $server['HTTP_LIDA_SESSIONID'] . '|' . ($server['REMOTE_ADDR'] ?? ''));
		}
		if (isset($server['PHP_AUTH_USER'])) {
			return 'token:' . hash('sha256', $server['PHP_AUTH_USER'] . ':' . ($server['PHP_AUTH_PW'] ?? ''));
		}
		return 'ip:' . ($server['REMOTE_ADDR'] ?? '');
	}

	/** The path index.php will route, so the heavy patterns see the same thing loadModuleActionId() does. */
	public static function requestPath(string $requestUri, array $get): string {
		if (str_starts_with($requestUri, '//')) {
			$requestUri = substr($requestUri, 1);
		}
		$path = parse_url($requestUri, PHP_URL_PATH) ?: '/';
		// A bare '/' still routes by the module and action query parameters
		if ($path == '/' && !empty($get['module'])) {
			$path = '/' . preg_replace('/[^\w]/', '', $get['module']) . '/' . preg_replace('/[^\w]/', '', $get['action'] ?? 'Home');
		}
		return $path;
	}

	public static function ipInRanges(string $ip, array $ranges): bool {
		$packed = @inet_pton($ip);
		if ($packed === false) {
			return false;
		}
		foreach ($ranges as $range) {
			[$network, $bits] = array_pad(explode('/', $range, 2), 2, null);
			$packedNetwork = @inet_pton($network);
			if ($packedNetwork === false || strlen($packedNetwork) != strlen($packed)) {
				continue;
			}
			$bits = $bits === null ? strlen($packed) * 8 : (int)$bits;
			$wholeBytes = intdiv($bits, 8);
			if (substr($packed, 0, $wholeBytes) != substr($packedNetwork, 0, $wholeBytes)) {
				continue;
			}
			$remainingBits = $bits % 8;
			if ($remainingBits == 0) {
				return true;
			}
			$mask = 0xFF << (8 - $remainingBits) & 0xFF;
			if ((ord($packed[$wholeBytes]) & $mask) == (ord($packedNetwork[$wholeBytes]) & $mask)) {
				return true;
			}
		}
		return false;
	}

	private static function matchesAny(array $patterns, string $subject): bool {
		foreach ($patterns as $pattern) {
			if (preg_match($pattern, $subject)) {
				return true;
			}
		}
		return false;
	}

	private function isVerifiedCrawler(string $userAgent, string $ip): bool {
		if ($this->crawlerUserAgents == '' || !preg_match('~' . $this->crawlerUserAgents . '~', $userAgent)) {
			return false;
		}
		if ($this->crawlerRanges === null) {
			$json = is_readable($this->crawlerRangesFile) ? json_decode(file_get_contents($this->crawlerRangesFile), true) : null;
			$this->crawlerRanges = array_merge(...array_values($json['ranges'] ?? []));
		}
		return self::ipInRanges($ip, $this->crawlerRanges);
	}

	private function sendCookie(string $value, array $server): void {
		header('Set-Cookie: ' . TrustCookie::cookieHeader($value, $this->cookieTtl, self::isHttps($server)), false);
	}

	private static function isHttps(array $server): bool {
		return (!empty($server['HTTPS']) && $server['HTTPS'] != 'off') || ($server['HTTP_X_FORWARDED_PROTO'] ?? '') == 'https';
	}

	private static function splitList(string $value): array {
		return preg_split('/\s+/', trim($value), -1, PREG_SPLIT_NO_EMPTY);
	}

	private function log(string $event, array $decision, array $server): void {
		// NOTICE is normally dropped unless the client is a debugging IP, which needs the database we have not opened
		$this->logger?->log("botguard event=$event ip=" . ($server['REMOTE_ADDR'] ?? '-') . " path={$decision['path']}{$decision['subject']} n={$decision['n']} limit={$decision['limit']}", Logger::LOG_NOTICE, true);
	}

	private function logObserved(string $observed, array $decision, array $server): void {
		if ($observed == 'challenge') {
			$n = $this->counters->bump($this->keyPrefix . 'observe_challenge', 3600);
			if ($n === null || $n % self::OBSERVE_CHALLENGE_SAMPLE != 0) {
				return;
			}
			$decision['n'] = $n;
			$decision['limit'] = 0;
		}
		$this->log("observe_$observed", $decision, $server);
	}

	/** API denials are JSON and their title is the error code; the rest are a small HTML page. */
	private function sendDenied(bool $json, int $status, int $retryAfter, string $title, string $message): void {
		http_response_code($status);
		header($json ? 'Content-Type: application/json' : 'Content-Type: text/html; charset=utf-8');
		header('Cache-Control: no-store, private');
		header("Retry-After: $retryAfter");
		if ($json) {
			echo json_encode([
				'error' => $title,
				'error_description' => $message,
				'retry_after' => $retryAfter,
			]);
		} else {
			echo "<!DOCTYPE html><html lang=\"en\"><head><meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width\"><title>$title</title></head><body><h1>$title</h1><p>$message</p></body></html>";
		}
	}

	private function sendChallenge(): void {
		header('Content-Type: text/html; charset=utf-8');
		header('Cache-Control: no-store, private');
		header('Vary: Cookie');
		header('X-Robots-Tag: noindex');
		$issuePath = self::ISSUE_PATH;
		$puzzle = $this->powDifficulty > 0 ? ProofOfWork::issue($this->secret, $this->powDifficulty) : '';
		$difficulty = $this->powDifficulty;
		echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width">
<title>Loading the catalog</title>
<style>body{font-family:sans-serif;margin:3em auto;max-width:36em;padding:0 1em;color:#222}</style>
</head>
<body>
<h1>Loading the catalog</h1>
<p id="botguard-status" role="status" aria-live="polite">One moment while your browser is checked.</p>
<noscript><p>This catalog needs JavaScript and cookies enabled. Please enable them and reload the page.</p></noscript>
<script>
(function () {
	var status = document.getElementById('botguard-status');
	var attemptsKey = 'aspenTrustAttempts';
	var now = Date.now();
	var attempts = [];
	try {
		attempts = JSON.parse(sessionStorage.getItem(attemptsKey) || '[]').filter(function (t) { return now - t < 60000; });
	} catch (e) {}
	if (attempts.length >= 3) {
		status.innerHTML = 'This catalog needs cookies and JavaScript enabled. Please enable them and <a href="">try again</a>.';
		return;
	}
	attempts.push(now);
	try { sessionStorage.setItem(attemptsKey, JSON.stringify(attempts)); } catch (e) {}
	var delay = 250;
	if (navigator.webdriver) { delay += 10000; }
	if (!navigator.languages || !navigator.languages.length) { delay += 5000; }
	var puzzle = '$puzzle';
	var difficulty = $difficulty;
	function issue(query) {
		fetch('$issuePath' + query, { cache: 'no-store' })
			.then(function () { location.replace(location.href); })
			.catch(function () { location.replace(location.href); });
	}
	if (!difficulty) {
		setTimeout(function () { issue(''); }, delay);
		return;
	}

	// The search runs in a worker so it is neither throttled by the tab being in the background nor blocking the page
	function solver() {
		// SHA-256 over ASCII, returning the eight state words; the search needs it hundreds of thousands of times
		var K = [0x428a2f98, 0x71374491, 0xb5c0fbcf, 0xe9b5dba5, 0x3956c25b, 0x59f111f1, 0x923f82a4, 0xab1c5ed5, 0xd807aa98, 0x12835b01, 0x243185be, 0x550c7dc3, 0x72be5d74, 0x80deb1fe, 0x9bdc06a7, 0xc19bf174, 0xe49b69c1, 0xefbe4786, 0x0fc19dc6, 0x240ca1cc, 0x2de92c6f, 0x4a7484aa, 0x5cb0a9dc, 0x76f988da, 0x983e5152, 0xa831c66d, 0xb00327c8, 0xbf597fc7, 0xc6e00bf3, 0xd5a79147, 0x06ca6351, 0x14292967, 0x27b70a85, 0x2e1b2138, 0x4d2c6dfc, 0x53380d13, 0x650a7354, 0x766a0abb, 0x81c2c92e, 0x92722c85, 0xa2bfe8a1, 0xa81a664b, 0xc24b8b70, 0xc76c51a3, 0xd192e819, 0xd6990624, 0xf40e3585, 0x106aa070, 0x19a4c116, 0x1e376c08, 0x2748774c, 0x34b0bcb5, 0x391c0cb3, 0x4ed8aa4a, 0x5b9cca4f, 0x682e6ff3, 0x748f82ee, 0x78a5636f, 0x84c87814, 0x8cc70208, 0x90befffa, 0xa4506ceb, 0xbef9a3f7, 0xc67178f2];
		var w = new Uint32Array(64);
		var h = new Uint32Array(8);
		function rotr(x, n) { return (x >>> n) | (x << (32 - n)); }
		function sha256(str) {
			var len = str.length;
			var bytes = new Uint8Array(((len + 9 + 63) >> 6) << 6);
			for (var i = 0; i < len; i++) { bytes[i] = str.charCodeAt(i); }
			bytes[len] = 0x80;
			var view = new DataView(bytes.buffer);
			view.setUint32(bytes.length - 4, len * 8, false);
			h.set([0x6a09e667, 0xbb67ae85, 0x3c6ef372, 0xa54ff53a, 0x510e527f, 0x9b05688c, 0x1f83d9ab, 0x5be0cd19]);
			for (var offset = 0; offset < bytes.length; offset += 64) {
				for (i = 0; i < 16; i++) { w[i] = view.getUint32(offset + i * 4, false); }
				for (i = 16; i < 64; i++) {
					var s0 = rotr(w[i - 15], 7) ^ rotr(w[i - 15], 18) ^ (w[i - 15] >>> 3);
					var s1 = rotr(w[i - 2], 17) ^ rotr(w[i - 2], 19) ^ (w[i - 2] >>> 10);
					w[i] = (w[i - 16] + s0 + w[i - 7] + s1) >>> 0;
				}
				var a = h[0], b = h[1], c = h[2], d = h[3], e = h[4], f = h[5], g = h[6], hh = h[7];
				for (i = 0; i < 64; i++) {
					var t1 = (hh + (rotr(e, 6) ^ rotr(e, 11) ^ rotr(e, 25)) + ((e & f) ^ (~e & g)) + K[i] + w[i]) >>> 0;
					var t2 = ((rotr(a, 2) ^ rotr(a, 13) ^ rotr(a, 22)) + ((a & b) ^ (a & c) ^ (b & c))) >>> 0;
					hh = g; g = f; f = e; e = (d + t1) >>> 0; d = c; c = b; b = a; a = (t1 + t2) >>> 0;
				}
				h[0] += a; h[1] += b; h[2] += c; h[3] += d; h[4] += e; h[5] += f; h[6] += g; h[7] += hh;
			}
			return h;
		}
		function leadingZeroBits(words) {
			var bits = 0;
			for (var i = 0; i < 8; i++) {
				if (words[i] !== 0) { return bits + Math.clz32(words[i]); }
				bits += 32;
			}
			return bits;
		}
		self.onmessage = function (event) {
			for (var counter = 0; ; counter++) {
				if (leadingZeroBits(sha256(event.data.puzzle + '.' + counter)) >= event.data.difficulty) {
					self.postMessage(counter);
					return;
				}
			}
		};
	}
	setTimeout(function () {
		try {
			var worker = new Worker(URL.createObjectURL(new Blob(['(' + solver.toString() + ')()'], { type: 'text/javascript' })));
			worker.onmessage = function (event) { issue('?pow=' + puzzle + '&counter=' + event.data); };
			worker.postMessage({ puzzle: puzzle, difficulty: difficulty });
		} catch (e) {
			status.innerHTML = 'This catalog needs a current browser with JavaScript enabled. Please <a href="">try again</a>.';
		}
	}, delay);
})();
</script>
</body>
</html>
HTML;
	}
}
