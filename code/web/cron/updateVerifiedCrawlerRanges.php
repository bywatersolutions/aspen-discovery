<?php
/**
 * Rebuild sites/default/conf/verified_crawlers.json from the IP ranges the search engines publish.
 * BotGuard lets a request whose user agent claims to be one of these crawlers skip the challenge only when
 * its address is inside the matching list.
 *
 * Usage: php updateVerifiedCrawlerRanges.php [output file]
 * Runs standalone, no site bootstrap, so it is safe from any machine with outbound HTTPS.
 */

$sources = [
	'googlebot' => 'https://developers.google.com/static/crawling/ipranges/common-crawlers.json',
	'bingbot' => 'https://www.bing.com/toolbox/bingbot.json',
	'applebot' => 'https://search.developer.apple.com/applebot.json',
	'duckduckbot' => 'https://duckduckgo.com/duckduckbot.json',
];
$outputFile = $argv[1] ?? __DIR__ . '/../../../sites/default/conf/verified_crawlers.json';

$ranges = [];
foreach ($sources as $crawler => $url) {
	$body = file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 30, 'user_agent' => 'Aspen Discovery updateVerifiedCrawlerRanges']]));
	if ($body === false) {
		fwrite(STDERR, "Could not fetch $url\n");
		exit(1);
	}
	$found = [];
	$json = json_decode($body, true);
	foreach ($json['prefixes'] ?? [] as $prefix) {
		$found[] = $prefix['ipv4Prefix'] ?? $prefix['ipv6Prefix'] ?? '';
	}
	$valid = [];
	foreach ($found as $range) {
		[$network] = explode('/', $range, 2);
		if (@inet_pton($network) !== false) {
			$valid[] = $range;
		}
	}
	$valid = array_values(array_unique($valid));
	sort($valid);
	// An empty list would silently start challenging that crawler, so refuse to write one
	if (count($valid) == 0) {
		fwrite(STDERR, "No ranges parsed for $crawler from $url, leaving the file unchanged\n");
		exit(1);
	}
	$ranges[$crawler] = $valid;
	echo "$crawler: " . count($valid) . " ranges\n";
}

$output = [
	'updated' => date('Y-m-d'),
	'sources' => $sources,
	'ranges' => $ranges,
];
file_put_contents($outputFile, json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
echo "Wrote $outputFile\n";
