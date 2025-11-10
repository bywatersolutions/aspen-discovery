<?php

/**
 * Smarty {timing}{/timing} block plugin
 * Usage: {timing label="blockName" threshold=200} ... {/timing}
 * Logs duration (ms) to Logger if available and duration >= threshold.
 *
 * @param array $params
 * @param string|null $content
 * @param Smarty_Internal_Template $template
 * @param bool $repeat
 * @return string|null
 */
function smarty_block_timing(array $params, ?string $content, Smarty_Internal_Template $template, bool &$repeat): ?string {
	static $timingStack = [];

	if ($repeat) {
		$label = $params['label'] ?? 'unnamed';
		$threshold = isset($params['threshold']) ? (float)$params['threshold'] : 200.0;
		$timingStack[] = [
			'label' => $label,
			'threshold' => $threshold,
			'start' => microtime(true),
		];
		return null;
	}

	if ($content === null) {
		return null;
	}

	$timingInfo = array_pop($timingStack);
	$durationMs = (microtime(true) - $timingInfo['start']) * 1000;

	global $logger;
	if (isset($logger) && $durationMs >= $timingInfo['threshold']) {
		$logger->log(
			"Smarty timing {$timingInfo['label']} duration=" . number_format($durationMs, 2) . "ms",
			Logger::LOG_NOTICE,
			true
		);
	}

	return $content;
}
