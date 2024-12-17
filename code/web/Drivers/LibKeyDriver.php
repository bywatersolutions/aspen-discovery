<?php

class LibKeyDriver {
	public function getLibKeyLink($data): string | null {
		require_once ROOT_DIR . '/sys/LibKey/LibKeySetting.php';
		$activeLibrary = Library::getActiveLibrary();
		$settings = new LibKeySetting();
		$settings->whereAdd("id=$activeLibrary->libKeySettingId");
		if (!$settings->find(true)) {
			return null;
		}
		$curlWrapper = new CurlWrapper;
		$doi = $this->getDoi($data);
		if (!$doi) {
			return null;
		}
		$response = $curlWrapper->curlGetPage("https://public-api.thirdiron.com/public/v1/libraries/" . $settings->libraryId  . "/articles/doi/" . $doi . "?access_token=" . $settings->apiKey);
		if (empty($response)) {
			return null;
		}
		$result = json_decode($response, true)["data"]["bestIntegratorLink"]["bestLink"];
		return $result;
	}
	
	private function getDoi($data): string | null {
		if (!is_object($data)) {
			if (!$this->containsDoi($data)) {
				return null;
			}
			return $this->extractDoiFromUrl($data);
		}
		return $this->findDoiInArray((array) $data);
	}

	private function findDoiInArray(array $uiList): string | null {
		foreach ($uiList as $ui) {
			if (!is_array($ui) && !is_object($ui) && $this->containsDoi($ui)) {
				return $ui;
			}
		}
		return null;
	}

	private function extractDoiFromUrl(string $url): string {
		return str_replace(["https://doi.org/", "http://"], "", $url);
	}

	public function containsDoi(string $url): bool {
		return preg_match('/10.\d{4,9}\/[-._;()\/:A-Za-z0-9]/', $url);
	}
}