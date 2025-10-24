<?php

/**
 * Citation Builder Class
 *
 * This class builds APA and MLA citations.
 *
 * @author      Demian Katz <demian.katz@villanova.edu>
 */
class CitationBuilder {
	private array $details;

	/**
	 * Load the base data needed to build the citations.
	 * The $details parameter should contain as many of the following keys as possible:
	 *
	 *  authors         => Array of authors in "Last, First, Title, Dates" format.
	 *                     i.e. King, Martin Luther, Jr., 1929-1968.
	 *  title           => The primary title of the work.
	 *  subtitle        => Subtitle of the work.
	 *  edition         => Array of edition statements (i.e. "1st ed.").
	 *  pubPlace        => Place of publication.
	 *  pubName         => Name of publisher.
	 *  pubDate         => Year of publication.
	 *
	 * Unless noted as an array, each field should be a string.
	 *
	 * @param array $details An array of details used to build the citations.
	 *                       See above for a full list of keys to populate.
	 */
	public function __construct(array $details) {
		$this->details = $details;
	}

	public static function getCitationFormats(): array {
		return [
			//'AMA' => 'AMA',
			'APA' => 'APA',
			'ChicagoHumanities' => 'Chicago/Turabian - Humanities',
			'ChicagoAuthDate' => 'Chicago/Turabian - Author Date',
			'Harvard' => 'Harvard',
			'MLA' => 'MLA',
		];
	}

	/**
	 * Get APA citation.
	 *
	 * Assign all the necessary variables and then returns a template
	 * name to display an APA citation.
	 *
	 * @return string Path to a Smarty template to display the citation.
	 */
	public function getAPA(): string {
		global $interface;
		$apa = [
			'title' => $this->getTitle(),
			'authors' => $this->getAPAAuthors(),
			'publisher' => $this->getPublisher(),
			'year' => $this->getYear(),
			'edition' => $this->getEdition(),
		];
		$interface->assign('apaDetails', $apa);
		return 'Citation/apa.tpl';
	}

	/**
	 * Get MLA citation.
	 *
	 * Assign all the necessary variables and then returns a template
	 * name to display an MLA citation.
	 *
	 * @return string Path to a Smarty template to display the citation.
	 */
	public function getMLA(): string {
		global $interface;
		$mla = [
			'title' => $this->getCapitalizedTitle(),
			'authors' => $this->getMLAAuthors(),
			'publisher' => $this->getPublisher(),
			'year' => $this->getYear(),
			'edition' => $this->getEdition(),
		];
		$interface->assign('mlaDetails', $mla);
		return 'Citation/mla.tpl';
	}

	private function getMLAFormat(): string {
		$formats = $this->details['format'];
		if (is_array($formats)) {
			foreach ($formats as $format) {
				if ($format == 'CD') {
					return 'CD';
				} elseif ($format == 'DVD' || $format == 'Blu-ray') {
					return 'DVD';
				} elseif ($format == 'Book' || $format == 'Large Print' || $format == 'Serial' || $format == 'Musical Score' || $format == 'Journal' || $format == 'Manuscript' || $format == 'Newspaper') {
					return 'Print';
				} elseif ($format == 'Internet Link' || $format == 'eBook' || $format == 'EPUB EBook' || $format == 'Kindle Book' || $format == 'Kindle' || $format == 'Plucker' || $format == 'Adobe PDF eBook' || $format == 'overdrive' || $format == 'Adobe PDF') {
					return 'Web';
				}
			}
		} else {
			if ($formats == 'CD') {
				return 'CD';
			} elseif ($formats == 'DVD' || $formats == 'Blu-ray') {
				return 'DVD';
			} elseif ($formats == 'Book' || $formats == 'Large Print' || $formats == 'Serial' || $formats == 'Musical Score' || $formats == 'Journal' || $formats == 'Manuscript' || $formats == 'Newspaper') {
				return 'Print';
			} elseif ($formats == 'Internet Link' || $formats == 'eBook' || $formats == 'EPUB EBook' || $formats == 'Kindle Book' || $formats == 'Kindle' || $formats == 'Plucker' || $formats == 'Adobe PDF eBook' || $formats == 'overdrive' || $formats == 'Adobe PDF') {
				return 'Web';
			}
		}

		return '';
	}

	/**
	 * Get AMA citation.
	 *
	 * Assign all the necessary variables and then returns a template
	 * name to display an AMA citation.
	 *
	 * @return string Path to a Smarty template to display the citation.
	 */
	public function getAMA(): string {
		global $interface;
		$citeDetails = [
			'title' => $this->getCapitalizedTitle(),
			'authors' => $this->getAMAAuthors(),
			'publisher' => $this->getPublisherWithPlace(),
			'year' => $this->getYear(),
			'edition' => $this->getEdition(),
		];
		$interface->assign('citeDetails', $citeDetails);
		return 'Citation/ama.tpl';
	}

	/**
	 * Get Chicago Humanities citation.
	 *
	 * Assign all the necessary variables and then returns a template
	 * name to display a Chicago Humanities citation.
	 *
	 * @return string Path to a Smarty template to display the citation.
	 */
	public function getChicagoHumanities(): string {
		global $interface;
		$citeDetails = [
			'title' => $this->getCapitalizedTitle(),
			'authors' => $this->getChicagoAuthors(),
			'publisher' => $this->getPublisher(),
			'year' => $this->getYear(),
		];
		$interface->assign('citeDetails', $citeDetails);
		return 'Citation/chicago-humanities.tpl';
	}

	/**
	 * Assign all the necessary variables and then returns a template
	 * name to display a UCL Harvard citation.
	 *
	 * @return string Path to a Smarty template to display the citation.
	 */
	public function getHarvard(): string {
		global $interface;
		$harvard = [
			'title' => $this->getHarvardTitle(),
			'authors' => $this->getHarvardAuthors(),
			'publisher' => $this->getPublisherWithPlace(),
			'year' => $this->getHarvardYear(),
			'edition' => $this->getHarvardEdition(),
		];
		$interface->assign('harvardDetails', $harvard);
		return 'Citation/harvard.tpl';
	}

	/**
	 * Get Chicago Auth Date citation.
	 *
	 * Assign all the necessary variables and then returns a template
	 * name to display a Chicago Auth Datemanities citation.
	 *
	 * @return string Path to a Smarty template to display the citation.
	 */
	public function getChicagoAuthDate(): string {
		global $interface;
		$citeDetails = [
			'title' => $this->getCapitalizedTitle(),
			'authors' => $this->getChicagoAuthors(),
			'publisher' => $this->getPublisher(),
			'year' => $this->getYear(),
		];
		$interface->assign('citeDetails', $citeDetails);
		return 'Citation/chicago-authdate.tpl';
	}

	/**
	 * Is the string a valid name suffix?
	 *
	 * @param string $str The string to check.
	 * @return bool True if it's a name suffix.
	 */
	private function isNameSuffix(string $str): bool {
		$str = $this->stripPunctuation($str);

		// Is it a standard suffix?
		$suffixes = [
			'Jr',
			'Sr',
		];
		if (in_array($str, $suffixes)) {
			return true;
		}

		// Is it a roman numeral?  (This check could be smarter, but it's probably
		// good enough as it is).
		if (preg_match('/^[MDCLXVI]+$/', $str)) {
			return true;
		}

		// If we got this far, it's not a suffix.
		return false;
	}

	/**
	 * Is the string a date range?
	 *
	 * @param string $str The string to check.
	 * @return bool True if it's a date range.
	 */
	private function isDateRange(string $str): bool {
		$str = trim($str);
		return preg_match('/^([0-9]+)-([0-9]*)\.?$/', $str);
	}

	/**
	 * Abbreviate a first name.
	 *
	 * @param string $name The name to abbreviate.
	 * @return string The abbreviated name.
	 */
	private function abbreviateName(string $name): string {
		// Remove parenthetical full names before abbreviation.
		$name = preg_replace('/\s*\([^)]*\)/', '', $name);
		$parts = explode(', ', $name);
		$name = $parts[0];

		// Append initials if the second part is not a date range.
		// If a date range is encountered instead, it indicates the end of the name and processing should stop.
		if (isset($parts[1]) && !$this->isDateRange($parts[1])) {
			$fnameParts = explode(' ', $parts[1]);
			for ($i = 0; $i < count($fnameParts); $i++) {
				$fnameParts[$i] = substr($fnameParts[$i], 0, 1) . '.';
			}
			$name .= ', ' . implode(' ', $fnameParts);
			if (isset($parts[2])) {
				if ($this->isNameSuffix($parts[2])) {
					$name = trim($name) . ', ' . $parts[2];
				}
			}
		}

		return trim($name);
	}

	/**
	 * Strip the dates off the end of a name.
	 *
	 * @param string $str The name to reverse.
	 * @return string The reversed name.
	 */
	private function cleanNameDates(string $str): string {
		$arr = explode(', ', $str);
		$name = $arr[0];
		if (isset($arr[1]) && !$this->isDateRange($arr[1])) {
			$name .= ', ' . $arr[1];
			if (isset($arr[2])) {
				if ($this->isNameSuffix($arr[2])) {
					$name .= ', ' . $arr[2];
				}
			}
		}
		return $name;
	}

	/**
	 * Strip unwanted punctuation from the right side of a string.
	 *
	 * @param string|null $text The text to clean up. May be null if 245$a is missing.
	 * @return string The cleaned up text.
	 */
	private function stripPunctuation(?string $text): string {
		if ($text === null) {
			return '';
		}
		$text = trim($text);
		if ((str_ends_with($text, '.')) || (str_ends_with($text, ',')) || (str_ends_with($text, ':')) || (str_ends_with($text, ';')) || (str_ends_with($text, '/'))) {
			$text = substr($text, 0, -1);
		}
		return trim($text);
	}

	/**
	 * Turn a "Last, First" name into a "First Last" name.
	 *
	 * @param string $str The name to reverse.
	 * @return string The reversed name.
	 */
	private function reverseName(string $str): string {
		$arr = explode(', ', $str);

		// If the second chunk is a date range, there is nothing to reverse!
		if (!isset($arr[1]) || $this->isDateRange($arr[1])) {
			return $arr[0];
		}

		$name = $arr[1] . ' ' . $arr[0];
		if (isset($arr[2]) && $this->isNameSuffix($arr[2])) {
			$name .= ', ' . $arr[2];
		}
		return $name;
	}

	/**
	 * Capitalize all words in a title, except for a few common exceptions.
	 *
	 * @param string $str The title to capitalize.
	 * @return string The capitalized title.
	 */
	private function capitalizeTitle(string $str): string {
		$exceptions = [
			'a',
			'an',
			'the',
			'against',
			'between',
			'in',
			'of',
			'to',
			'and',
			'but',
			'for',
			'nor',
			'or',
			'so',
			'yet',
			'to',
		];

		$words = explode(' ', $str);
		$newWords = [];
		$followsColon = false;
		foreach ($words as $word) {
			// Capitalize words unless they are in the exception list...  but even
			// exceptional words get capitalized if they follow a colon.
			if (!in_array($word, $exceptions) || $followsColon) {
				$word = ucfirst($word);
			}
			$newWords[] = $word;

			$followsColon = str_ends_with($word, ':');
		}

		return ucfirst(join(' ', $newWords));
	}

	/**
	 * Convert a string to sentence case.
	 *
	 * @param string $text
	 * @return string
	 */
	private function convertToSentenceCase(string $text): string {

		$abbreviations = [];

		//Regex pattern to match common exceptions e.g. "R&B" and "USA"
		$pattern = '/\b[A-Z&]+\b/';
		//Generate placeholders for abbreviations that match the pattern
		$text = preg_replace_callback($pattern, function($matches) use (&$abbreviations) {
			$placeholder = '[[abbr_' . count($abbreviations) . ']]';
			$abbreviations[$placeholder] = $matches[0];
			//Replace abbreviation with placeholder
			return $placeholder;
		}, $text);

		$text = strtolower($text);
		$text = ucfirst($text);
		//Replace placeholders with their original abbreviations - unchanged by strtolower
		foreach ($abbreviations as $placeholder => $abbr) {
			$text = str_replace($placeholder, $abbr, $text);
		}
		return $text;
	}

	/**
	 * Get the full title for a citation.
	 *
	 * @return  string
	 */
	private function getTitle(): string
	{
		// Create Title
		$title = $this->stripPunctuation($this->details['title']);
		if (isset($this->details['subtitle']) && strlen($this->details['subtitle']) > 0) {
			$title .= ': ' . $this->stripPunctuation($this->details['subtitle']);
		}

		if (!((str_ends_with($title, '?')) || (str_ends_with($title, '!')))) {
			$title .= '.';
		}
		return $title;
	}

	/**
	 * Get the full citation for a UCL Harvard Title.
	 *
	 * @return string
	 *
	 */
	private function getHarvardTitle(): string {
		$title = $this->convertToSentenceCase($this->stripPunctuation($this->details['title']));

		if (isset($this->details['subtitle']) && strlen($this->details['subtitle']) > 0){
			$subtitle = $this->convertToSentenceCase($this->stripPunctuation($this->details['subtitle']));
			$subtitle = strtolower(substr($subtitle, 0, 1)) . substr($subtitle, 1);
			$title .= ': ' . $subtitle;
		}

		if (!((str_ends_with($title, '?')) || (str_ends_with($title, '!')))) {
			$title .= '.';
		}
		return $title;
	}


	/**
	 * Get an array of authors for an APA citation.
	 *
	 * @return bool|array|string
	 */
	private function getAPAAuthors(): bool|array|string {
		$authorStr = '';
		if (isset($this->details['authors']) && is_array($this->details['authors'])) {
			$i = 0;
			foreach ($this->details['authors'] as $author) {
				$author = $this->abbreviateName($author);
				if (($i + 1 == count($this->details['authors'])) && ($i > 0)) { // Last
					$authorStr .= ', & ' . $this->stripPunctuation($author) . '.';
				} elseif ($i > 0) {
					$authorStr .= ', ' . $this->stripPunctuation($author) . '.';
				} else {
					$authorStr .= $this->stripPunctuation($author) . '.';
				}
				$i++;
			}
		}
		return (empty($authorStr) ? false : $authorStr);
	}

	/**
	 * Get an array of authors for an APA citation.
	 *
	 * @return bool|array|string
	 */
	private function getChicagoAuthors(): bool|array|string {
		$authorStr = '';
		if (isset($this->details['authors']) && is_array($this->details['authors'])) {
			$i = 0;
			$numAuthors = count($this->details['authors']);
			foreach ($this->details['authors'] as $author) {
				$authorAbr = $this->abbreviateName($author);
				$authorReversed = $this->reverseName($author);
				if ($numAuthors == 1) {
					$authorStr = $this->stripPunctuation($author);
				} elseif ($numAuthors < 4) {
					if ($i == 0) {
						$authorStr .= $this->stripPunctuation($author);
					} elseif (($i + 1 == count($this->details['authors'])) && ($i > 0)) {
						$authorStr .= ' and ' . $this->stripPunctuation($authorReversed);
					} else {
						$authorStr .= ', ' . $this->stripPunctuation($authorReversed);
					}
				} else {
					$authorStr .= $this->stripPunctuation($authorReversed) . ' et al.';
					break;
				}
				$i++;
			}
		}
		return (empty($authorStr) ? false : $authorStr);
	}

	/**
	 * Get an array of authors for an APA citation.
	 *
	 * @return bool|array|string
	 */
	private function getAMAAuthors(): bool|array|string {
		$authorStr = '';
		if (isset($this->details['authors']) && is_array($this->details['authors'])) {
			$i = 0;
			foreach ($this->details['authors'] as $author) {
				$author = $this->abbreviateName($author);
				if (($i + 1 == count($this->details['authors'])) && ($i > 0)) { // Last
					$authorStr .= ', & ' . $this->stripPunctuation($author) . '.';
				} elseif ($i > 0) {
					$authorStr .= ', ' . $this->stripPunctuation($author) . '.';
				} else {
					$authorStr .= $this->stripPunctuation($author) . '.';
				}
				$i++;
			}
		}
		return (empty($authorStr) ? false : $authorStr);
	}

	/**
	 * Get a string of authors for a UCL Harvard citation.
	 *
	 * @return bool|string
	 */
	private function getHarvardAuthors(): bool|string {
		$authorStr = ' ';
		if (isset($this->details['authors']) && is_array($this->details['authors'])) {
			$i = 0;
			$numAuthors = count($this->details['authors']);
			foreach($this->details['authors'] as $author) {
				$author = $this->abbreviateName($author);
				//After listing 8 authors, stop listing and add 'et al.'
				if ($i == 7) {
					$authorStr .= ' et al';
					break;
				}

				//Add "and" for last author
				if (($i +1 == $numAuthors || $i == 6) && ($i > 0)) {
					$authorStr .= ' and ' . $this->stripPunctuation($author) . '.';
				} elseif ($i > 0) {
					$authorStr .= ', ' . $this->stripPunctuation($author) . '.';
				} else {
					$authorStr .= $this->stripPunctuation($author) . '.';
				}
				$i++;
			}
		}
		return (empty($authorStr) ? false: $authorStr);
	}


	/**
	 * Get edition statement for inclusion in a citation.
	 *
	 * @return bool|string
	 */
	private function getEdition(): bool|string {
		// Find the first edition statement that isn't "1st ed."
		if (isset($this->details['edition'])) {
			if (is_array($this->details['edition'])) {
				foreach ($this->details['edition'] as $edition) {
					if ($edition !== '1st ed.') {
						return $this->stripPunctuation($edition);
					}
				}
			} else {
				if ($this->details['edition'] !== '1st ed.') {
					return $this->stripPunctuation($this->details['edition']);
				}
			}
		}
		// No edition statement found.
		return false;
	}

	/**
	 * Get edition statement for inclusion in a UCL Harvard citation.
	 *
	 * @return bool|string
	 */
	private function getHarvardEdition(): bool|string {
		if (isset($this->details['edition'])) {
			if (is_array($this->details['edition'])) {
				foreach ($this->details['edition'] as $edition) {
					if ($edition !== '1st ed.'){
						$edition =preg_replace('/\bedition\b/i', 'edn', $edition);
						return $this->stripPunctuation($edition);
					}
				}
			} else {
				if ($this->details['edition'] !== '1st ed.') {
					$edition = preg_replace('/\bedition\b/i', 'edn', $this->details['edition']);
					return $this->stripPunctuation($edition);
				}
			}
		}
		// No edition statement found.
		return false;
	}

	/**
	 * Get the citation's full, title-case (i.e., capitalizing the major words) title.
	 *
	 * @return string
	 */
	private function getCapitalizedTitle(): string {
		// MLA titles are just like APA titles, only capitalized differently.
		return $this->capitalizeTitle($this->getTitle());
	}

	/**
	 * Get an array of authors for an APA citation.
	 *
	 * @return bool|string
	 */
	private function getMLAAuthors(): bool|string {
		$authorStr = '';
		if (isset($this->details['authors']) && is_array($this->details['authors'])) {
			$i = 0;
			if (count($this->details['authors']) > 4) {
				$author = $this->details['authors'][0];
				$authorStr = $this->cleanNameDates($author) . ', et al';
			} else {
				foreach ($this->details['authors'] as $author) {
					if (($i + 1 == count($this->details['authors'])) && ($i > 0)) {
						// Last
						$authorStr .= ', and ' . $this->reverseName($this->stripPunctuation($author));
					} elseif ($i > 0) {
						$authorStr .= ', ' . $this->reverseName($this->stripPunctuation($author));
					} else {
						// First
						$authorStr .= $this->cleanNameDates($author);
					}
					$i++;
				}
			}
		}
		return (empty($authorStr) ? false : $this->stripPunctuation($authorStr));
	}

	/**
	 * Get publisher information (place: name) for inclusion in a citation.
	 *
	 * @return bool|string
	 */
	private function getPublisherWithPlace(): bool|string {
		$parts = [];
		if (isset($this->details['placeOfPublication']) && !empty($this->details['placeOfPublication'])) {
			$parts[] = $this->stripPunctuation($this->details['placeOfPublication']);
		}
		if (isset($this->details['pubName']) && !empty($this->details['pubName'])) {
			$parts[] = $this->details['pubName'];
		}
		if (empty($parts)) {
			return false;
		}
		return $this->stripPunctuation(implode(': ', $parts));
	}

	private function getPublisher(): bool|string {
		if (isset($this->details['pubName']) && !empty($this->details['pubName'])) {
			return $this->stripPunctuation($this->details['pubName']);
		}
		return false;
	}

	/**
	 * Get the year of publication for inclusion in a citation.
	 *
	 * @return bool|string
	 */
	private function getYear(): bool|string {
		if (isset($this->details['pubDate'])) {
			return preg_replace('/[^0-9]/', '', $this->details['pubDate']);
		}
		return false;
	}

	/**
	 * Get the year of publication for inclusion in a UCL Harvard citation.
	 *
	 * @return string
	 */
	private function getHarvardYear(): string {
		if (isset($this->details['pubDate'])) {
			$year = preg_replace('/[^0-9]/', '', $this->details['pubDate']);
			if (strlen($year) === 4) {
				return $year;
			}
		}
		// Return "n.d." for missing or invalid dates.
		return 'n.d.';
	}
}