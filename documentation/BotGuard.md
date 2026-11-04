# BotGuard: origin-side challenge and admission control

BotGuard refuses anonymous heavy requests before Aspen opens its database connection, so a scraper pool
that reaches the origin costs no session row, analytics write, Solr query or page render. It is the Aspen
half of the ByWater bot-resistance program; Koha ships the same design as `Koha::Middleware::BotGuard`
with the same cookie format, so one Cloudflare rule can key on either cookie.

## What it does

- Heavy paths are `/Search/Results`, `/Union/Search`, `/Author/Home` and everything under
  `/GroupedWork/` and `/Record/`. The XHR endpoints those pages and the home page call (`/Search/AJAX`,
  `/Author/AJAX`, `/GroupedWork/.../AJAX`, `/Record/.../AJAX`) are never challenged or capped, because
  autosuggest and spotlights fire before any heavy page has minted a cookie: a browser with a cookie
  spends its `rate_limit` on them, one without spends `api_rate_limit` per address. Everything else
  (home page, `/API/`, account pages, book covers, robots.txt, cron) is untouched. A request is classified
  by its path and by its `?module=&action=` parameters, whichever says heavy, because index.php falls
  back to the parameters when the path matches none of its routes. The one route it cannot see is a
  host's `defaultPath` for `/`, which comes from the database.
- A heavy request without a valid `aspen_trust` cookie gets a small HTTP 200 page (`X-Robots-Tag:
  noindex`, `Cache-Control: no-store`) whose JavaScript waits a moment, solves a proof of work, fetches
  `/BotGuard/Issue` with the solution and reloads the original URL. The cookie is
  `<kind>.<issued>.<nonce>.<hmac>`, signed with a key derived from `sites/<site>/conf/passkey`, 30 days,
  `HttpOnly; SameSite=Lax` (plus `Secure` on https).
- The proof of work is stateless: the page carries a signed puzzle and the browser searches for a counter
  whose SHA-256 with it starts with `pow_difficulty` zero bits. The solved puzzle's nonce becomes the
  cookie's nonce, so one solution buys exactly one identity; replaying it returns a cookie with the same
  budget. A puzzle is valid for five minutes. A headless browser can still solve it, but each anonymous
  identity now costs CPU time, and raising `pow_difficulty` by one doubles that cost. `/BotGuard/Issue`
  without a valid solution answers 400 `{"error":"proof_of_work_required"}`; `pow_difficulty = 0` goes
  back to minting on a plain fetch.
- Kind `a` (anonymous) cookies get `rate_limit` heavy requests per `rate_window` seconds (429 with
  `Retry-After`) and share an in-flight cap of `max_concurrent` (503, `Retry-After: 2`). Kind `u` is set
  for logged-in users on their next page view and is never limited; logout replaces it with a fresh `a`.
- Feed requests (`view=rss`) and verified search-engine crawlers (user agent in `crawler_user_agents`
  **and** address inside `sites/default/conf/verified_crawlers.json`) skip the challenge and the window
  but still count toward the cap. Addresses in `exempt_ips` skip everything.
- Counters live in APCu. Without `php-apcu` the challenge still works and the limits are skipped (fail
  open), logged as `botguard event=limits_off reason=no_apcu` once a minute. Without a passkey the guard
  disables itself and logs `botguard event=disabled reason=no_passkey` once a minute.

## API requests

Nothing under `/API/` is challenged, because LiDA and other API clients cannot run JavaScript. Instead each
API request gets `api_rate_limit` requests per `api_rate_window` seconds keyed on the credential it carries,
first match wins, with the credential hashed so nothing secret reaches APCu or the log:

1. `Authorization: Bearer <token>`: per OAuth2 token.
2. `username` in the request (patron-scoped calls): per patron, across devices.
3. `LiDA-SessionID` header: per LiDA session plus app slug plus client address (before login every install
   of an app shares one session id, so the address keeps that from being one app-wide bucket).
4. HTTP Basic credentials (integrations using app or Greenhouse keys): per key pair.
5. Otherwise the client address (public access, which Aspen already gates with `allowAPIAccess`).

Calls to `/API/SearchAPI` (and `ItemAPI` `getDescriptionByTitleAndAuthor`, which queries Solr with no auth)
also count toward `api_max_concurrent`, a gauge separate from the web cap. Denials are JSON in the shape
the OAuth2 rate limiter already uses: 429 `{"error":"rate_limit_exceeded",...,"retry_after":60}` or 503
`{"error":"server_busy","retry_after":2}`, both with `Retry-After`. `exempt_ips` applies, so the upgrade
play's localhost calls are never limited. The existing OAuth2 rate limiter (100 per hour per client, in the
database) still runs after authentication for OAuth2 calls.

## Enabling

Set the values in `sites/<site>/conf/config.ini` (overrides `sites/default/conf/config.ini`, no restart):

```ini
[BotGuard]
enabled        = true
mode           = observe
```

`mode = observe` never denies anything; it logs what enforce would have done so limits can be sized first.
`mode = enforce` (the default) applies them. Start with `max_concurrent` around a quarter of Apache's
`MaxRequestWorkers` so account pages stay reachable while anonymous search traffic is shed.

Rollback is `enabled = false`; it takes effect on the next request. A stale challenge page still gets a
204 from `/BotGuard/Issue` and reloads normally.

## Watching it

Every deny writes one line to `/var/log/aspen-discovery/<site>/messages.log`:

```
[2026 Oct 23 10:15:02] [NOTICE] botguard event=limited ip=203.0.113.5 path=/Search/Results cid=1f3a9c0e n=61 limit=60
```

Events: `limited` (429 per cookie), `capped` (503 in-flight), `issue_limited` (429 minting cookies),
`api_limited` (429 per credential), `api_capped` (503 SearchAPI in-flight), `observe_challenge` (one line
per 1000 cookie-less heavy requests), `observe_limited`, `observe_capped`, `observe_issue_limited`,
`observe_api_limited` and `observe_api_capped`. `cid` is the first 8 hex characters of the cookie nonce,
so one visitor can be followed across lines; API lines carry `id` (the first 8 hex characters of the
credential hash, prefixed by its kind) and `method` instead.
Challenges themselves are not logged; they show in access.log as 200 responses of the challenge page's size.

```
grep -c 'botguard event=capped' /var/log/aspen-discovery/<site>/messages.log
```

## Refreshing the crawler ranges

```
php code/web/cron/updateVerifiedCrawlerRanges.php
```

rewrites `sites/default/conf/verified_crawlers.json` from the lists Google, Bing, Apple and DuckDuckGo
publish, and refuses to write a file in which any crawler would have no ranges. Commit the result.

## Tests

```
cd tests/phpunit && php phpunit.phar --no-configuration --test-suffix Tests.php tests/sys/BotGuard
```

The tests need no database or APCu; they exercise the decision table with array-backed counters.
