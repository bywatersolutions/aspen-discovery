<?php

require_once ROOT_DIR . '/Action.php';

/**
 * Landing page used to clear a Cloudflare challenge that was issued to an AJAX request.
 *
 * Challenges cannot be solved inside an XHR, so when one is detected the browser is sent here as a
 * top-level navigation. Cloudflare is configured to always challenge this path, so the challenge is
 * rendered full-page, the visitor receives a cf_clearance cookie, and this action sends them back
 * to the page they were on.
 */
class Help_Verify extends Action {
	function launch() {
		header('Cache-Control: no-store');
		header('Location: ' . self::getSafeReturnUrl($_REQUEST['returnUrl'] ?? ''));
		exit;
	}

	/**
	 * Only allow relative paths on this site to prevent an open redirect.
	 */
	static function getSafeReturnUrl($returnUrl): string {
		if (!is_string($returnUrl) || $returnUrl === '' || $returnUrl[0] !== '/') {
			return '/';
		}
		// Reject protocol-relative URLs (//host, /\host) and control characters that could split headers.
		if (strlen($returnUrl) > 1 && ($returnUrl[1] === '/' || $returnUrl[1] === '\\')) {
			return '/';
		}
		if (preg_match('/[\x00-\x1F\x7F]/', $returnUrl) === 1) {
			return '/';
		}
		return $returnUrl;
	}

	function getBreadcrumbs(): array {
		return [];
	}
}
