<?php

function mergeItemSummary(array $localCopies, array $itemSummary) : array {
	foreach ($itemSummary as $key => $item) {
		if (isset($localCopies[$key])) {
			$localCopies[$key]['totalCopies'] += $item['totalCopies'];
			$localCopies[$key]['availableCopies'] += $item['availableCopies'];
			$localCopies[$key]['available'] = $localCopies[$key]['availableCopies'] > 0;
			if ($item['displayByDefault']) {
				$localCopies[$key]['displayByDefault'] = true;
			}
		} else {
			$localCopies[$key] = $item;
		}
	}
	return $localCopies;
}