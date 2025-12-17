<?php
require_once ROOT_DIR . '/Drivers/marmot_inc/ISBNConverter.php';
require_once ROOT_DIR . '/sys/Syndetics/SyndeticsData.php';

class GoDeeperData {
	static function getGoDeeperOptions($isbn, $upc) {
		global $configArray;
		global $memCache;
		global $timer;
		global $library;
		if (is_array($upc)) {
			$upc = count($upc) > 0 ? reset($upc) : '';
		}
		$validEnrichmentTypes = [];
		//Load the index page from syndetics
		if (!isset($isbn) && !isset($upc)) {
			return $validEnrichmentTypes;
		}

		$goDeeperOptions = $memCache->get("go_deeper_options_{$isbn}_$upc");
		if (!$goDeeperOptions || isset($_REQUEST['reload'])) {

			// Use Syndetics Go-Deeper Data.
			require_once ROOT_DIR . '/sys/Enrichment/SyndeticsSetting.php';
			$syndeticsSettings = new SyndeticsSetting();
			$syndeticsSettings->id = $library->syndeticsSettingId;
			if ($syndeticsSettings->find(true)) {
				if (!$syndeticsSettings->syndeticsUnbound) {
					try {
						if ($syndeticsSettings->hasSummary || $syndeticsSettings->hasAvSummary || $syndeticsSettings->hasToc || $syndeticsSettings->hasExcerpt || $syndeticsSettings->hasFictionProfile || $syndeticsSettings->hasAuthorNotes || $syndeticsSettings->hasVideoClip) {
							$clientKey = $syndeticsSettings->syndeticsKey;
							$requestUrl = "https://syndetics.com/index.aspx?isbn=$isbn/INDEX.XML&client=$clientKey&type=xw10&upc=$upc";

							//Get the XML from the service
							$ctx = stream_context_create([
								'http' => [
									'timeout' => 5,
								],
							]);
							$response = @file_get_contents($requestUrl, 0, $ctx);
							ExternalRequestLogEntry::logRequest('syndetics.getIndex', 'GET', $requestUrl, [], '', 0, $response, []);

							$timer->logTime("Got options from syndetics");
							//echo($response);

							//Parse the XML
							if (!preg_match('/<!DOCTYPE\\sHTML/', $response)) {
								//Got a valid response
								$data = new SimpleXMLElement($response);

								$validEnrichmentTypes = [];
								if ($syndeticsSettings->hasSummary && isset($data->SUMMARY)) {
									$validEnrichmentTypes['summary'] = 'Summary';
									if (!isset($defaultOption)) {
										$defaultOption = 'summary';
									}
								}
								if ($syndeticsSettings->hasAvSummary && isset($data->AVSUMMARY)) {
									//AV Summary is weird since it combines both summary and table of contents for movies and music
									$avSummary = GoDeeperData::getAVSummary($syndeticsSettings, $isbn, $upc);
									if (isset($avSummary['summary'])) {
										$validEnrichmentTypes['summary'] = 'Summary';
										if (!isset($defaultOption)) {
											$defaultOption = 'summary';
										}
									}
									if (isset($avSummary['trackListing'])) {
										$validEnrichmentTypes['tableOfContents'] = 'Table of Contents';
										if (!isset($defaultOption)) {
											$defaultOption = 'tableOfContents';
										}
									}
								}
								if ($syndeticsSettings->hasAvProfile && isset($data->AVPROFILE)) {
									//Profile has similar bands and tags for music.  Not sure how to best use this
									$validEnrichmentTypes['avProfile'] = 'Profile';
								}
								if ($syndeticsSettings->hasToc && isset($data->TOC)) {
									$validEnrichmentTypes['tableOfContents'] = 'Table of Contents';
									if (!isset($defaultOption)) {
										$defaultOption = 'tableOfContents';
									}
								}
								if ($syndeticsSettings->hasExcerpt && isset($data->DBCHAPTER)) {
									$validEnrichmentTypes['excerpt'] = 'Excerpt';
									if (!isset($defaultOption)) {
										$defaultOption = 'excerpt';
									}
								}
								if ($syndeticsSettings->hasFictionProfile && isset($data->FICTION)) {
									$validEnrichmentTypes['fictionProfile'] = 'Character Information';
									if (!isset($defaultOption)) {
										$defaultOption = 'fictionProfile';
									}
								}
								if ($syndeticsSettings->hasAuthorNotes && isset($data->ANOTES)) {
									$validEnrichmentTypes['authorNotes'] = 'Author Notes';
									if (!isset($defaultOption)) {
										$defaultOption = 'authorNotes';
									}
								}
								if ($syndeticsSettings->hasVideoClip && isset($data->VIDEOCLIP)) {
									//Profile has similar bands and tags for music.  Not sure how to best use this
									$validEnrichmentTypes['videoClip'] = 'Video Clip';
									if (!isset($defaultOption)) {
										$defaultOption = 'videoClip';
									}
								}
							}
						}
					} catch (Exception $e) {
						global $logger;
						$logger->log("Error fetching data from Syndetics $e", Logger::LOG_ERROR);
						if (isset($response)) {
							$logger->log($response, Logger::LOG_NOTICE);
						}
					}
				}
				$timer->logTime("Finished processing Syndetics options");
			}

			// Use Content Cafe Data
			require_once ROOT_DIR . '/sys/Enrichment/ContentCafeSetting.php';
			$contentCafeSettings = new ContentCafeSetting();
			if ($contentCafeSettings->find(true)) {
				if ($contentCafeSettings->enabled) {
					$response = self::getContentCafeData($contentCafeSettings, $isbn, $upc);
					if ($response) {
						$availableContent = $response[0]->AvailableContent;
						if ($contentCafeSettings->hasExcerpt && $availableContent->Excerpt) {
							$validEnrichmentTypes['excerpt'] = 'Excerpt';
							if (!isset($defaultOption)) {
								$defaultOption = 'excerpt';
							}
						}
						if ($contentCafeSettings->hasToc && $availableContent->TOC) {
							$validEnrichmentTypes['tableOfContents'] = 'Table of Contents';
							if (!isset($defaultOption)) {
								$defaultOption = 'tableOfContents';
							}
						}
						if ($contentCafeSettings->hasAuthorNotes && $availableContent->Biography) {
							$validEnrichmentTypes['authorNotes'] = 'Author Notes';
							if (!isset($defaultOption)) {
								$defaultOption = 'authorNotes';
							}
						}
						if ($contentCafeSettings->hasSummary && $availableContent->Annotation) {
							$validEnrichmentTypes['summary'] = 'Summary';
							if (!isset($defaultOption)) {
								$defaultOption = 'summary';
							}
						}
						$timer->logTime("Finished processing Content Cafe options");
					}
				}
			}

			// Use Loral Data Data
			if ($library->loralSettingId > 0) {
				require_once ROOT_DIR . '/sys/Enrichment/LoralSetting.php';
				$loralSettings = new LoralSetting();
				$loralSettings->id = $library->loralSettingId;
				if ($loralSettings->find(true)) {
					$enrichmentOptions = self::getLoralEnrichmentOptions($loralSettings, $isbn, $upc);
					if (!empty($enrichmentOptions)) {
						if (in_array('excerpt', $enrichmentOptions)) {
							$validEnrichmentTypes['excerpt'] = 'Excerpt';
							if (!isset($defaultOption)) {
								$defaultOption = 'excerpt';
							}
						}
						if (in_array('tableOfContents', $enrichmentOptions)) {
							$validEnrichmentTypes['tableOfContents'] = 'Table of Contents';
							if (!isset($defaultOption)) {
								$defaultOption = 'tableOfContents';
							}
						}
						if (in_array('authorNotes', $enrichmentOptions)) {
							$validEnrichmentTypes['authorNotes'] = 'Author Notes';
							if (!isset($defaultOption)) {
								$defaultOption = 'authorNotes';
							}
						}
						if (in_array('description', $enrichmentOptions)) {
							$validEnrichmentTypes['summary'] = 'Summary';
							if (!isset($defaultOption)) {
								$defaultOption = 'summary';
							}
						}
						if (in_array('allInOne', $enrichmentOptions)) {
							$validEnrichmentTypes['loralAllInOne'] = 'More about this title';
							if (!isset($defaultOption)) {
								$defaultOption = 'loralAllInOne';
							}
						}
						$timer->logTime("Finished processing Loral options");
					}
				}
			}

			$goDeeperOptions = ['options' => $validEnrichmentTypes];
			if (count($validEnrichmentTypes) > 0 && isset($defaultOption)) {
				$goDeeperOptions['defaultOption'] = $defaultOption;
			}
			$memCache->set("go_deeper_options_{$isbn}_$upc", $goDeeperOptions, $configArray['Caching']['go_deeper_options']);
		}

		return $goDeeperOptions;
	}

	private static function getContentCafeData(ContentCafeSetting $contentCafeSettings, $isbn, $upc, $field = 'AvailableContent') {
		$url = 'https://contentcafe2.btol.com/ContentCafe/ContentCafe.asmx?WSDL';

		$SOAP_options = [
			'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
			// sets how the soap responses will be handled
			'soap_version' => SOAP_1_2,
//				'trace' => 1, // turns on debugging features
		];
		if (IPAddress::showDebuggingInformation()) {
			$SOAP_options['trace'] = true;
		}
		try {
			$defaultSocketTimeout = ini_get('default_socket_timeout');
			ini_set('default_socket_timeout', 3);
			$soapClient = new SoapClient($url, $SOAP_options);

			$params = [
				'userID' => $contentCafeSettings->contentCafeId,
				'password' => $contentCafeSettings->pwd,
				'key' => $isbn ?: $upc,
				'content' => $field,
			];

			/** @noinspection PhpUndefinedMethodInspection */
			$response = $soapClient->Single($params);
			if (IPAddress::showDebuggingInformation()) {
				ExternalRequestLogEntry::logRequest('contentcafe.getData', 'GET', $url, $soapClient->__getLastRequestHeaders(), $soapClient->__getLastRequest(), 0, $soapClient->__getLastResponse(), []);
			}
			ini_set('default_socket_timeout', $defaultSocketTimeout);
			if ($response) {
				if (!isset($response->ContentCafe->Error)) {
					return $response->ContentCafe->RequestItems->RequestItem;
				} else {
					global $logger;
					$logger->log("Content Cafe Error Response for Content Type $field : " . $response->ContentCafe->Error, Logger::LOG_ERROR);
				}
			}
		} catch (Exception $e) {
			global $logger;
			$logger->log('Failed ContentCafe SOAP Request ' . $e->getMessage(), Logger::LOG_ERROR);
		}

		return false;
	}

	private static function getLoralEnrichmentOptions(LoralSetting $settings, $isbn, $upc, $field = 'AvailableContent') {
		if (empty($isbn) && empty($upc)) {
			$summaryData = 'no_summary';
		}else{
			/** @var Memcache $memCache */
			global $memCache;
			global $configArray;
			$memCacheKey = "loral_enrichment_options_{$isbn}_$upc";
			$enrichmentOptions = $memCache->get($memCacheKey);
			if (!$enrichmentOptions || isset($_REQUEST['reload'])) {
				$enrichmentOptions = [];
				$url = $settings->loralUrl;
				$authentication = base64_encode($settings->loralId . ':' . $settings->password);
				$url .= "/Enrichment/Options?isn=";
				if (!empty($isbn)) {
					$url .= "$isbn";
				}else if (!empty($upc)) {
					$url .= "$upc";
				}
				$headers = "User-Agent: {$configArray['Catalog']['catalogUserAgent']}\r\n";
				$headers .= "Authorization: Basic $authentication\r\n";
				$context = stream_context_create([
					'http' => [
						'header' => $headers,
					],
				]);
				$response = @file_get_contents($url, false, $context);
				if ($response) {
					$jsonResponse = json_decode($response);
					if ($jsonResponse->success) {
						$enrichmentOptions = $jsonResponse->enrichmentOptions;
					}

					$memCache->set($memCacheKey, $enrichmentOptions, $configArray['Caching']['enrichment_data']);
				}
			}
		}

		return $enrichmentOptions;
	}

	static function getSummary(?string $workId, ?string $isbn, ?string $upc) : array {
		global $library;
		$summaryData = [];
		if ($library->syndeticsSettingId > 0) {
			require_once ROOT_DIR . '/sys/Enrichment/SyndeticsSetting.php';
			$syndeticsSettings = new SyndeticsSetting();
			$syndeticsSettings->id = $library->syndeticsSettingId;
			if ($syndeticsSettings->find(true) && !$syndeticsSettings->syndeticsUnbound) {
				$summaryData = self::getSyndeticsSummary($syndeticsSettings, $workId, $isbn, $upc);
			}
		}
		require_once ROOT_DIR . '/sys/Enrichment/ContentCafeSetting.php';
		$contentCafeSettings = new ContentCafeSetting();
		if ($contentCafeSettings->find(true)) {
			if ($contentCafeSettings->enabled) {
				$summaryData = self::getContentCafeSummary($contentCafeSettings, $isbn, $upc);
			}
		}
		if ($library->loralSettingId > 0) {
			require_once ROOT_DIR . '/sys/Enrichment/LoralSetting.php';
			$loralSettings = new LoralSetting();
			$loralSettings->id = $library->loralSettingId;
			if ($loralSettings->find(true)) {
				$summaryData = self::getLoralSummary($loralSettings, $isbn, $upc);
			}
		}

		return $summaryData;
	}

	private static function getContentCafeSummary(ContentCafeSetting $settings, ?string $isbn, ?string $upc) : array {
		global $configArray;
		/** @var Memcache $memCache */ global $memCache;
		$memCacheKey = "contentcafe_summary_{$isbn}_$upc";
		$summaryData = $memCache->get($memCacheKey);
		if (!$summaryData || isset($_REQUEST['reload'])) {
			$summaryData = [];
			$response = self::getContentCafeData($settings, $isbn, $upc, 'AnnotationDetail');
			if ($response) {
				$temp = [];
				if (isset($response[0]->AnnotationItems->AnnotationItem)) {
					foreach ($response[0]->AnnotationItems->AnnotationItem as $summary) {
						//Correct poorly encoded quotes
						$tempAnnotation = str_replace('&amp;&#34;', '"', $summary->Annotation);
						$tempAnnotation = str_replace('&amp;&#39;', "'", $tempAnnotation);
						$temp[strlen($summary->Annotation)] = html_entity_decode($tempAnnotation);
					}
					ksort($temp);
					$summaryData['summary'] = end($temp); // Grab the Longest Summary
				}
				if (!empty($summaryData['summary'])) {
					$memCache->set($memCacheKey, $summaryData, $configArray['Caching']['enrichment_data']);
				} else {
					$memCache->set($memCacheKey, 'no_summary', $configArray['Caching']['enrichment_data']);
				}
			}
		}
		if ($summaryData == 'no_summary') {
			return [];
		} else {
			return $summaryData;
		}

	}

	private static function getSyndeticsSummary(SyndeticsSetting $settings, ?string $workId, ?string $isbn, ?string $upc) : array {
		global $configArray;

		if ($settings->hasSummary) {
			/** @var Memcache $memCache */ global $memCache;
			$key = "syndetics_summary_{$isbn}_$upc";
			$summaryData = $memCache->get($key);

			if (!$summaryData || isset($_REQUEST['reload'])) {
				$syndeticsData = new SyndeticsData();
				$syndeticsData->groupedRecordPermanentId = $workId;
				$syndeticsData->primaryIsbn = $isbn;
				$syndeticsData->primaryUpc = $upc;
				$doReload = false;
				if ($syndeticsData->find(true)) {
					//Reload the summary every 2 weeks (to match covers)
					if ($syndeticsData->lastDescriptionUpdate < time() - 2 * 7 * 24 * 60 * 60) {
						$doReload = true;
					}
				} else {
					$doReload = true;
				}
				if (isset($_REQUEST['reload'])) {
					$doReload = true;
				}
				if ($doReload) {
					try {

						//Load the index page from syndetics
						$requestUrl = "https://syndetics.com/index.aspx?isbn=$isbn/SUMMARY.XML&client=$settings->syndeticsKey&type=xw10&upc=$upc";

						//Get the XML from the service
						$ctx = stream_context_create([
							'http' => [
								'timeout' => 2,
							],
						]);

						$response = @file_get_contents($requestUrl, 0, $ctx);
						ExternalRequestLogEntry::logRequest('syndetics.getSummary', 'GET', $requestUrl, [], '', 0, $response, []);
						if (!preg_match('/Error in Query Selection|The page you are looking for could not be found/', $response)) {
							//Parse the XML
							/** @var stdClass $data */
							$data = new SimpleXMLElement($response);

							$summaryData = [];
							if (isset($data->VarFlds->VarDFlds->Notes->Fld520->a)) {
								$summaryData['summary'] = (string)$data->VarFlds->VarDFlds->Notes->Fld520->a;
							}
						}

						//The summary can also be in the avsummary
						if (!isset($summaryData['summary'])) {
							$avSummary = GoDeeperData::getAVSummary($settings, $isbn, $upc);
							if (isset($avSummary['summary'])) {
								$summaryData['summary'] = $avSummary['summary'];
							}
						}
						if (!$summaryData) {
							$syndeticsData->description = 'no_summary';
						} else {
							$syndeticsData->description = $summaryData['summary'];
						}
						$syndeticsData->lastDescriptionUpdate = time();
						$ret = $syndeticsData->update();
						if (!$ret) {
							global $logger;
							$logger->log("An error occurred updating syndetics", Logger::LOG_WARNING);
						}
					} catch (Exception $e) {
						global $logger;
						$logger->log("Error fetching data from Syndetics $e", Logger::LOG_ERROR);
						$logger->log("Request URL was $requestUrl", Logger::LOG_ERROR);
						$summaryData = [];
					}
				} else {
					if ($syndeticsData->description == 'no_summary') {
						$summaryData = [];
					} else {
						$summaryData['summary'] = $syndeticsData->description;
					}
				}

				if (!$summaryData) {
					$memCache->set($key, 'no_summary', $configArray['Caching']['enrichment_data']);
				} else {
					$memCache->set($key, $summaryData, $configArray['Caching']['enrichment_data']);
				}
			}
			if ($summaryData == 'no_summary') {
				return [];
			} else {
				return $summaryData;
			}
		} else {
			return [];
		}
	}

	static function getLoralSummary(LoralSetting $settings, ?string $isbn, ?string $upc) : array {
		if (empty($isbn) && empty($upc)) {
			$summaryData = 'no_summary';
		}else{
			/** @var Memcache $memCache */
			global $memCache;
			global $configArray;
			$memCacheKey = "loral_summary_{$isbn}_$upc";
			$summaryData = $memCache->get($memCacheKey);
			if (!$summaryData || isset($_REQUEST['reload'])) {
				$summaryData = [];
				$url = $settings->loralUrl;
				$authentication = base64_encode($settings->loralId . ':' . $settings->password);
				$url .= "/Enrichment/Description?isn=";
				if (!empty($isbn)) {
					$url .= "$isbn";
				}else if (!empty($upc)) {
					$url .= "$upc";
				}
				$headers = "User-Agent: {$configArray['Catalog']['catalogUserAgent']}\r\n";
				$headers .= "Authorization: Basic $authentication\r\n";
				$context = stream_context_create([
					'http' => [
						'header' => $headers,
					],
				]);
				$response = @file_get_contents($url, false, $context);
				if ($response) {
					$jsonResponse = json_decode($response);
					if ($jsonResponse->success) {
						$summaryData['summary'] = $jsonResponse->description;
					}else{
						$summaryData = 'no_summary';
					}

					if (!empty($summaryData['summary'])) {
						$memCache->set($memCacheKey, $summaryData, $configArray['Caching']['enrichment_data']);
					} else {
						$memCache->set($memCacheKey, 'no_summary', $configArray['Caching']['enrichment_data']);
					}
				}
			}
		}

		if ($summaryData == 'no_summary') {
			return [];
		} else {
			return $summaryData;
		}
	}

	function getTableOfContents($isbn, $upc) {
		$tocData = [];
		require_once ROOT_DIR . '/sys/Enrichment/SyndeticsSetting.php';
		global $library;
		$syndeticsSettings = new SyndeticsSetting();
		$syndeticsSettings->id = $library->syndeticsSettingId;
		if ($syndeticsSettings->find(true)) {
			$tocData = self::getSyndeticsTableOfContents($syndeticsSettings, $isbn, $upc);
		}
		require_once ROOT_DIR . '/sys/Enrichment/ContentCafeSetting.php';
		$contentCafeSettings = new ContentCafeSetting();
		if ($contentCafeSettings->find(true)) {
			if ($contentCafeSettings->enabled) {
				$tocData = self::getContentCafeTableOfContents($contentCafeSettings, $isbn, $upc);
			}
		}
		return $tocData;
	}

	private static function getContentCafeTableOfContents(ContentCafeSetting $settings, ?string $isbn, ?string $upc) {
		global $configArray;
		/** @var Memcache $memCache */ global $memCache;
		$memCacheKey = "contentcafe_toc_{$isbn}_$upc";
		$tocData = $memCache->get($memCacheKey);
		if (!$tocData || isset($_REQUEST['reload'])) {
			$tocData = [];
			$response = self::getContentCafeData($settings, $isbn, $upc, 'TocDetail');
			if ($response) {
				$tocData['html'] = $response[0]->TocItems->TocItem[0]->Toc;
				if (!empty($tocData['html'])) {
					$memCache->set($memCacheKey, $tocData, $configArray['Caching']['enrichment_data']);
				}
			}

		}
		return $tocData;
	}

	private static function getSyndeticsTableOfContents(SyndeticsSetting $settings, ?string $isbn, ?string $upc) : array {
		global $configArray;
		/** @var Memcache $memCache */ global $memCache;
		$tocData = $memCache->get("syndetics_toc_{$isbn}_$upc");

		if (!$tocData || isset($_REQUEST['reload'])) {
			$clientKey = $settings->syndeticsKey;
			//Load the index page from syndetics
			$requestUrl = "https://syndetics.com/index.aspx?isbn=$isbn/TOC.XML&client=$clientKey&type=xw10&upc=$upc";

			try {
				//Get the XML from the service
				$ctx = stream_context_create([
					'http' => [
						'timeout' => 2,
					],
				]);
				$response = file_get_contents($requestUrl, 0, $ctx);
				$tocData = [];
				ExternalRequestLogEntry::logRequest('syndetics.getTOC', 'GET', $requestUrl, [], '', 0, $response, []);
				if (!preg_match('/Error in Query Selection|The page you are looking for could not be found/', $response)) {
					//Parse the XML
					/** @var stdClass $data */
					$data = new SimpleXMLElement($response);


					if (isset($data->VarFlds->VarDFlds->SSIFlds->Fld970)) {
						foreach ($data->VarFlds->VarDFlds->SSIFlds->Fld970 as $field) {
							$tocData[] = [
								'label' => (string)$field->l,
								'title' => (string)$field->t,
								'page' => (string)$field->p,
							];
						}
					}
				}
				if (count($tocData) == 0) {
					$avSummary = GoDeeperData::getAVSummary($settings, $isbn, $upc);
					if (isset($avSummary['trackListing'])) {
						$tocData = $avSummary['trackListing'];
					}
				}

			} catch (Exception $e) {
				global $logger;
				$logger->log("Error fetching data from Syndetics $e", Logger::LOG_ERROR);
				$tocData = [];
			}
			$memCache->set("syndetics_toc_{$isbn}_$upc", $tocData, $configArray['Caching']['enrichment_data']);
		}
		return $tocData;
	}

	static function getSyndeticsFictionProfile(SyndeticsSetting $settings, ?string $isbn, ?string $upc) : array {
		//Load the index page from syndetics
		global $configArray;
		/** @var Memcache $memCache */ global $memCache;
		$fictionData = $memCache->get("syndetics_fiction_profile_{$isbn}_$upc");

		if (!$fictionData) {
			$clientKey = $settings->syndeticsKey;
			$requestUrl = "https://syndetics.com/index.aspx?isbn=$isbn/FICTION.XML&client=$clientKey&type=xw10&upc=$upc";

			try {
				//Get the XML from the service
				$ctx = stream_context_create([
					'http' => [
						'timeout' => 2,
					],
				]);
				$response = file_get_contents($requestUrl, 0, $ctx);
				ExternalRequestLogEntry::logRequest('syndetics.getFiction', 'GET', $requestUrl, [], '', 0, $response, []);

				//Parse the XML
				/** @var stdClass $data */
				$data = new SimpleXMLElement($response);

				$fictionData = [];
				//Load characters
				if (isset($data->VarFlds->VarDFlds->SSIFlds->Fld920)) {
					foreach ($data->VarFlds->VarDFlds->SSIFlds->Fld920 as $field) {
						$fictionData['characters'][] = [
							'name' => (string)$field->b,
							'gender' => (string)$field->c,
							'age' => (string)$field->d,
							'description' => (string)$field->f,
							'occupation' => (string)$field->g,
						];
					}

				}
				//Load subjects
				if (isset($data->VarFlds->VarDFlds->SSIFlds->Fld950)) {
					foreach ($data->VarFlds->VarDFlds->SSIFlds->Fld950 as $field) {
						$fictionData['topics'][] = (string)$field->a;
					}
				}
				//Load settings
				if (isset($data->VarFlds->VarDFlds->SSIFlds->Fld951)) {
					foreach ($data->VarFlds->VarDFlds->SSIFlds->Fld951 as $field) {
						if (isset($field->c)) {
							$fictionData['settings'][] = $field->a . ' -- ' . $field->c;
						} else {
							$fictionData['settings'][] = (string)$field->a;
						}
					}
				}
				//Load additional settings
				if (isset($data->VarFlds->VarDFlds->SSIFlds->Fld952)) {
					foreach ($data->VarFlds->VarDFlds->SSIFlds->Fld952 as $field) {
						if (isset($field->c)) {
							$fictionData['settings'][] = $field->a . ' -- ' . $field->c;
						} else {
							$fictionData['settings'][] = (string)$field->a;
						}
					}
				}
				//Load genres
				if (isset($data->VarFlds->VarDFlds->SSIFlds->Fld955)) {
					foreach ($data->VarFlds->VarDFlds->SSIFlds->Fld955 as $field) {
						$genre = (string)$field->a;
						$subGenres = [];
						if (isset($field->b)) {
							foreach ($field->b as $subGenre) {
								$subGenres[] = $subGenre;
							}
						}
						$fictionData['genres'][] = [
							'name' => $genre,
							'subGenres' => $subGenres,
						];
					}
				}
				//Load awards
				if (isset($data->VarFlds->VarDFlds->SSIFlds->Fld985)) {
					foreach ($data->VarFlds->VarDFlds->SSIFlds->Fld985 as $field) {
						$fictionData['awards'][] = [
							'name' => (string)$field->a,
							'year' => (string)$field->y,
						];
					}

				}
			} catch (Exception $e) {
				global $logger;
				$logger->log("Error fetching data from Syndetics $e", Logger::LOG_ERROR);
				$fictionData = [];
			}
			$memCache->set("syndetics_fiction_profile_{$isbn}_$upc", $fictionData, $configArray['Caching']['enrichment_data']);
		}
		return $fictionData;
	}

	private static function getContentCafeAuthorNotes(ContentCafeSetting $settings, ?string $isbn, ?string $upc) : string {
		global $configArray;
		/** @var Memcache $memCache */ global $memCache;
		$memCacheKey = "contentcafe_author_notes_{$isbn}_$upc";
		$authorData = $memCache->get($memCacheKey);
		if (!$authorData || isset($_REQUEST['reload'])) {
			$authorData = [];
			$response = self::getContentCafeData($settings, $isbn, $upc, 'BiographyDetail');
			if ($response) {
				$authorData['summary'] = $response[0]->BiographyItems->BiographyItem[0]->Biography;
				if (!empty($authorData['summary'])) {
					$memCache->set($memCacheKey, $authorData, $configArray['Caching']['enrichment_data']);
				}
			}

		}
		return $authorData;
	}

	private static function getSyndeticsAuthorNotes(SyndeticsSetting $settings, ?string $isbn, ?string $upc) : array {
		global $configArray;
		/** @var Memcache $memCache */ global $memCache;
		$summaryData = $memCache->get("syndetics_author_notes_{$isbn}_$upc");

		if (!$summaryData) {
			$clientKey = $settings->syndeticsKey;

			//Load the index page from syndetics
			$requestUrl = "https://syndetics.com/index.aspx?isbn=$isbn/ANOTES.XML&client=$clientKey&type=xw10&upc=$upc";

			try {
				//Get the XML from the service
				$ctx = stream_context_create([
					'http' => [
						'timeout' => 2,
					],
				]);
				$response = file_get_contents($requestUrl, 0, $ctx);
				ExternalRequestLogEntry::logRequest('syndetics.getAuthorNotes', 'GET', $requestUrl, [], '', 0, $response, []);

				//Parse the XML
				/** @var stdClass $data */
				$data = new SimpleXMLElement($response);

				$summaryData = [];
				if (isset($data->VarFlds->VarDFlds->SSIFlds->Fld980->a)) {
					$summaryData['summary'] = (string)$data->VarFlds->VarDFlds->SSIFlds->Fld980->a;
				}

				return $summaryData;
			} catch (Exception $e) {
				global $logger;
				$logger->log("Error fetching data from Syndetics $e", Logger::LOG_ERROR);
				$summaryData = [];
			}
			$memCache->set("syndetics_author_notes_{$isbn}_$upc", $summaryData, $configArray['Caching']['enrichment_data']);
		}
		return $summaryData;
	}

	private static function getLoralAuthorNotes(LoralSetting $settings, ?string $isbn, ?string $upc) : array {
		global $configArray;
		/** @var Memcache $memCache */ global $memCache;
		$memCacheKey = "loral_author_notes_{$isbn}_$upc";
		$summaryData = $memCache->get($memCacheKey);

		if (!$summaryData || isset($_REQUEST['reload'])) {
			$summaryData = [];
			$url = $settings->loralUrl;
			$authentication = base64_encode($settings->loralId . ':' . $settings->password);
			$url .= "/Enrichment/AuthorBio?isn=";
			if (!empty($isbn)) {
				$url .= "$isbn";
			}else if (!empty($upc)) {
				$url .= "$upc";
			}
			$headers = "User-Agent: {$configArray['Catalog']['catalogUserAgent']}\r\n";
			$headers .= "Authorization: Basic $authentication\r\n";
			$context = stream_context_create([
				'http' => [
					'header' => $headers,
				],
			]);
			$response = @file_get_contents($url, false, $context);
			if ($response) {
				$jsonResponse = json_decode($response);
				if ($jsonResponse->success) {
					$summaryData['summary'] = $jsonResponse->biography;
				}
			}
			$memCache->set("loral_author_notes_{$isbn}_$upc", $summaryData, $configArray['Caching']['enrichment_data']);
		}
		return $summaryData;
	}

	private static function getLoralAllInOne(LoralSetting $settings, ?string $isbn, ?string $upc) : array {
		global $configArray;
		/** @var Memcache $memCache */ global $memCache;
		$memCacheKey = "loral_all_in_one_{$isbn}_$upc";
		$summaryData = $memCache->get($memCacheKey);

		if (!$summaryData || isset($_REQUEST['reload'])) {
			$summaryData = [];
			$url = $settings->loralUrl;
			$authentication = base64_encode($settings->loralId . ':' . $settings->password);
			$url .= "/Enrichment/AllInOne?isn=";
			if (!empty($isbn)) {
				$url .= "$isbn";
			}else if (!empty($upc)) {
				$url .= "$upc";
			}
			$headers = "User-Agent: {$configArray['Catalog']['catalogUserAgent']}\r\n";
			$headers .= "Authorization: Basic $authentication\r\n";
			$context = stream_context_create([
				'http' => [
					'header' => $headers,
				],
			]);
			$response = @file_get_contents($url, false, $context);
			if ($response) {
				$jsonResponse = json_decode($response);
				if ($jsonResponse->success) {
					$summaryData['allInOneData'] = $jsonResponse->allInOne;
				}
			}
			$memCache->set("loral_all_in_one_{$isbn}_$upc", $summaryData, $configArray['Caching']['enrichment_data']);
		}
		return $summaryData;
	}

	private static function getSyndeticsExcerpt(SyndeticsSetting $settings, ?string $isbn, ?string $upc) : array {
		global $configArray;
		/** @var Memcache $memCache */ global $memCache;
		$excerptData = $memCache->get("syndetics_excerpt_{$isbn}_$upc");

		if (!$excerptData || isset($_REQUEST['reload'])) {
			$clientKey = $settings->syndeticsKey;

			//Load the index page from syndetics
			$requestUrl = "https://syndetics.com/index.aspx?isbn=$isbn/DBCHAPTER.XML&client=$clientKey&type=xw10&upc=$upc";

			try {
				//Get the XML from the service
				$ctx = stream_context_create([
					'http' => [
						'timeout' => 2,
					],
				]);
				$response = file_get_contents($requestUrl, 0, $ctx);
				ExternalRequestLogEntry::logRequest('syndetics.getExcerpt', 'GET', $requestUrl, [], '', 0, $response, []);

				//Parse the XML
				/** @var stdClass $data */
				$data = new SimpleXMLElement($response);

				$excerptData = [];
				if (isset($data->VarFlds->VarDFlds->Notes->Fld520)) {
					$excerptData['excerpt'] = (string)$data->VarFlds->VarDFlds->Notes->Fld520;
					$excerptData['excerpt'] = '<p>' . str_replace(chr(194) . chr(160), '</p><p>', $excerptData['excerpt']) . '</p>';
				}

				$memCache->set("syndetics_excerpt_{$isbn}_$upc", $excerptData, $configArray['Caching']['enrichment_data']);
			} catch (Exception $e) {
				global $logger;
				$logger->log("Error fetching data from Syndetics $e", Logger::LOG_ERROR);
				$excerptData = [];
			}
		}
		return $excerptData;
	}

	private static function getContentCafeExcerpt(ContentCafeSetting $settings, ?string $isbn, ?string $upc) : array {
		global $configArray;
		/** @var Memcache $memCache */ global $memCache;
		$memCacheKey = "contentcafe_excerpt_{$isbn}_$upc";
		$excerptData = $memCache->get($memCacheKey);

		if (!$excerptData || isset($_REQUEST['reload'])) {
			$excerptData = [];
			$response = self::getContentCafeData($settings, $isbn, $upc, 'ExcerptDetail');
			if ($response) {
				$excerptData['excerpt'] = $response[0]->ExcerptItems->ExcerptItem[0]->Excerpt;
				if (!empty($excerptData['excerpt'])) {
					$memCache->set($memCacheKey, $excerptData, $configArray['Caching']['enrichment_data']);
				}
			}
		}
		return $excerptData;
	}

	private static function getVideoClip(SyndeticsSetting $settings, ?string $isbn, ?string $upc) {
		global $configArray;
		/** @var Memcache $memCache */ global $memCache;
		$summaryData = $memCache->get("syndetics_video_clip_{$isbn}_$upc");

		if (!$summaryData) {
			$clientKey = $settings->syndeticsKey;
			//Load the index page from syndetics
			$requestUrl = "https://syndetics.com/index.aspx?isbn=$isbn/VIDEOCLIP.XML&client=$clientKey&type=xw10&upc=$upc";

			try {
				//Get the XML from the service
				$ctx = stream_context_create([
					'http' => [
						'timeout' => 2,
					],
				]);
				$response = file_get_contents($requestUrl, 0, $ctx);
				ExternalRequestLogEntry::logRequest('syndetics.getVideoClip', 'GET', $requestUrl, [], '', 0, $response, []);

				//Parse the XML
				/** @var stdClass $data */
				$data = new SimpleXMLElement($response);

				$summaryData = [];
				if (isset($data->VarFlds->VarDFlds->VideoLink)) {
					$summaryData['videoClip'] = (string)$data->VarFlds->VarDFlds->VideoLink;
				}
				if (isset($data->VarFlds->VarDFlds->SSIFlds->Fld997)) {
					$summaryData['source'] = (string)$data->VarFlds->VarDFlds->SSIFlds->Fld997;
				}

			} catch (Exception $e) {
				global $logger;
				$logger->log("Error fetching data from Syndetics $e", Logger::LOG_ERROR);
				$summaryData = [];
			}
			$memCache->set("syndetics_video_clip_{$isbn}_$upc", $summaryData, $configArray['Caching']['enrichment_data']);
		}

		return $summaryData;
	}

	static function getAVSummary(SyndeticsSetting $settings, ?string $isbn, ?string $upc) : array {
		global $configArray;
		/** @var Memcache $memCache */
		if (!$settings->hasAvSummary) {
			return [];
		}
		global $memCache;
		$avSummaryData = $memCache->get("syndetics_av_summary_{$isbn}_$upc");

		if (!$avSummaryData || isset($_REQUEST['reload'])) {
			$clientKey = $settings->syndeticsKey;

			//Load the index page from syndetics
			$requestUrl = "https://syndetics.com/index.aspx?isbn=$isbn/AVSUMMARY.XML&client=$clientKey&type=xw10&upc=$upc";

			try {
				//Get the XML from the service
				$ctx = stream_context_create([
					'http' => [
						'timeout' => 2,
					],
				]);
				$response = file_get_contents($requestUrl, 0, $ctx);
				ExternalRequestLogEntry::logRequest('syndetics.getAVSummary', 'GET', $requestUrl, [], '', 0, $response, []);
				$avSummaryData = [];
				if (!preg_match('/Error in Query Selection|The page you are looking for could not be found/', $response)) {
					//Parse the XML
					/** @var stdClass $data */
					$data = new SimpleXMLElement($response);

					if (isset($data->VarFlds->VarDFlds->Notes->Fld520->a)) {
						$avSummaryData['summary'] = (string)$data->VarFlds->VarDFlds->Notes->Fld520->a;
					}
					if (isset($data->VarFlds->VarDFlds->SSIFlds->Fld970)) {
						foreach ($data->VarFlds->VarDFlds->SSIFlds->Fld970 as $field) {
							$avSummaryData['trackListing'][] = [
								'number' => (string)$field->l,
								'name' => (string)$field->t,
							];
						}
					}
				}

				$memCache->set("syndetics_av_summary_{$isbn}_$upc", $avSummaryData, $configArray['Caching']['enrichment_data']);
			} catch (Exception $e) {
				global $logger;
				$logger->log("Error fetching data from Syndetics $e", Logger::LOG_ERROR);
				$avSummaryData = [];
			}
		}
		return $avSummaryData;
	}

	static function getHtmlData(string $dataType, string $recordType, ?string $isbn, ?string $upc) : string {
		global $interface;
		global $library;

		$interface->assign('recordType', $recordType);
		$id = !empty($_REQUEST['id']) ? $_REQUEST['id'] : $_GET['id'];
		// TODO: request id is not always set here. a quirk of static call
		$interface->assign('id', $id);
		$interface->assign('isbn', $isbn);
		$interface->assign('upc', $upc);

		// Use Syndetics Data
		require_once ROOT_DIR . '/sys/Enrichment/SyndeticsSetting.php';
		$syndeticsSettings = new SyndeticsSetting();
		$syndeticsSettings->id = $library->syndeticsSettingId;
		if ($syndeticsSettings->find(true)) {
			switch (strtolower($dataType)) {
				case 'summary' :
					$data = GoDeeperData::getSyndeticsSummary($syndeticsSettings, $id, $isbn, $upc);
					$interface->assign('summaryData', $data);
					return $interface->fetch('Record/view-syndetics-summary.tpl');
				case 'tableofcontents' :
					$data = GoDeeperData::getSyndeticsTableOfContents($syndeticsSettings, $isbn, $upc);
					$interface->assign('tocData', $data);
					return $interface->fetch('Record/view-syndetics-toc.tpl');
				case 'fictionprofile' :
					$data = GoDeeperData::getSyndeticsFictionProfile($syndeticsSettings, $isbn, $upc);
					$interface->assign('fictionData', $data);
					return $interface->fetch('Record/view-syndetics-fiction.tpl');
				case 'authornotes' :
					$data = GoDeeperData::getSyndeticsAuthorNotes($syndeticsSettings, $isbn, $upc);
					$interface->assign('authorData', $data);
					return $interface->fetch('Record/view-syndetics-author-notes.tpl');
				case 'excerpt' :
					$data = GoDeeperData::getSyndeticsExcerpt($syndeticsSettings, $isbn, $upc);
					$interface->assign('excerptData', $data);
					return $interface->fetch('Record/view-syndetics-excerpt.tpl');
				case 'avsummary' :
					$data = GoDeeperData::getAVSummary($syndeticsSettings, $isbn, $upc);
					$interface->assign('avSummaryData', $data);
					return $interface->fetch('Record/view-syndetics-av-summary.tpl');
				case 'videoclip' :
					$data = GoDeeperData::getVideoClip($syndeticsSettings, $isbn, $upc);
					$interface->assign('videoClipData', $data);
					return $interface->fetch('Record/view-syndetics-video-clip.tpl');
				default :
					return "Loading data for Syndetics $dataType still needs to be handled.";
			}
		}

		// Use Content Cafe Data
		require_once ROOT_DIR . '/sys/Enrichment/ContentCafeSetting.php';
		$contentCafeSettings = new ContentCafeSetting();
		if ($contentCafeSettings->find(true)) {
			if ($contentCafeSettings->enabled) {
				switch (strtolower($dataType)) {
					case 'tableofcontents' :
						$data = GoDeeperData::getContentCafeTableOfContents($contentCafeSettings, $isbn, $upc);
						$interface->assign('tocData', $data);
						return $interface->fetch('Record/view-contentcafe-toc.tpl');
					case 'authornotes' :
						$data = GoDeeperData::getContentCafeAuthorNotes($contentCafeSettings, $isbn, $upc);
						$interface->assign('authorData', $data);
						return $interface->fetch('Record/view-syndetics-author-notes.tpl');
					case 'excerpt' :
						$data = GoDeeperData::getContentCafeExcerpt($contentCafeSettings, $isbn, $upc);
						$interface->assign('excerptData', $data);
						return $interface->fetch('Record/view-syndetics-excerpt.tpl');
					default :
						return "Loading data for Content Cafe $dataType still needs to be handled.";
				}
			}
		}

		// Use Loral Data
		if ($library->loralSettingId > 0) {
			require_once ROOT_DIR . '/sys/Enrichment/LoralSetting.php';
			$loralSettings = new LoralSetting();
			$loralSettings->id = $library->loralSettingId;
			if ($loralSettings->find(true)) {
				switch (strtolower($dataType)) {
					case 'authornotes' :
						$data = GoDeeperData::getLoralAuthorNotes($loralSettings, $isbn, $upc);
						$interface->assign('authorData', $data);
						return $interface->fetch('Record/view-syndetics-author-notes.tpl');
					case 'loralallinone' :
						$data = GoDeeperData::getLoralAllInOne($loralSettings, $isbn, $upc);
						$interface->assign('loralAllInOneData', $data);
						return $interface->fetch('GroupedWork/loralAllInOne.tpl');
//					case 'excerpt' :
//						$data = GoDeeperData::getLoralExcerpt($loralSettings, $isbn, $upc);
//						$interface->assign('excerptData', $data);
//						return $interface->fetch('Record/view-syndetics-excerpt.tpl');
//					case 'tableofcontents' :
//						$data = GoDeeperData::getLoralTableOfContents($loralSettings, $isbn, $upc);
//						$interface->assign('tocData', $data);
//						return $interface->fetch('Record/view-contentcafe-toc.tpl');
					default :
						return "Loading data for Loral $dataType still needs to be handled.";
				}
			}
		}

		return "Unhandled option or incorrectly configured option";
	}
}