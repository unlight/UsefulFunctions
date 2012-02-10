<?php
// …

if (!function_exists('LoremIpsum')) {
	/**
	* Lorem Ipsum Generator. Using remote service.
	* Undocumented.
	* 
	*/
	function LoremIpsum($Settings = array(), $Trim = False) {
		$Language = C('Plugins.UsefulFunctions.LoremIpsum.Language', 'latin');
		if (!in_array($Language, array('latin', 'noIpsum'))) {
			$OtherValue = $Language;
			$Language = 'other';
		}
		$Defaults = array(
			'language' => $Language, // latin (Standard Lipsum), noIpsum (Don't start with "Lorem Ipsum")
			'other'	=> isset($OtherValue) ? $OtherValue : 'latin',
			'radio'	=> 'limit',
			'limit' => 100, // words
			'num' => 1, // paragraph(s)
			'type' => 'plain',
			'Rhubarb' => 'Generate'
		);
		
		if (is_numeric($Settings)) {
			// Words
			$Options['radio'] = 'limit';
			$Options['limit'] = $Settings;
			if (is_int($Settings) || $Settings == 1) $Trim = '.'; // remove dot for single word
		} elseif (is_string($Settings) && substr($Settings, 0, 1) == 'p') {
			// Paragraphs
			$Paragraphs = substr($Settings, 1);
			$Number = Clamp((int)$Paragraphs, 1, 100);
			$Options['num'] = $Number;
			$Options['radio'] = 'num';
		} else {
			$Options = $Settings;
		}
		$Options = array_merge($Defaults, $Options);

		$Snoopy = Gdn::Factory('Snoopy');
		$Snoopy->Submit('http://generator.lorem-ipsum.info/lorem-ipsum-copy', $Options);
		$Doc = PqDocument($Snoopy->results, array('FixHtml' => False));
		$Result = Pq('#txt')->Text();
		if ($Trim !== False) $Result = trim($Result, $Trim);
		return $Result;
	}
}

if (!function_exists('RemoteHtmlToMarkdown')) {
	function RemoteHtmlToMarkdown($Html, $FormatHtml = False) {
		$Snoopy = Gdn::Factory('Snoopy');
		$Vars = array('html' => $Html, 'submit' => 1);
		$Snoopy->Submit('http://modnerd.com/html2markdown/', $Vars);
		$Doc = PqDocument($Snoopy->results, array('FixHtml' => False));
		$Result = Pq('#markdown')->Val();
		$Result = htmlspecialchars_decode($Result, ENT_QUOTES);
		// I don't have a clue why need do htmlspecialchars_decode second time
		$Result = htmlspecialchars_decode($Result, ENT_QUOTES);
		$Result = str_replace('&#xD;', "\r", $Result);
		$Result = trim($Result);
		return $Result;
	}
}

if (!function_exists('HtmlToMarkdown')) {
	function HtmlToMarkdown($Html, $FormatHtml = False) {
		if (!class_exists('HTML_Parser', False)) {
			define('HTML2MD_HEADER_STYLE', 'ATX');
			define('HTML2MD_SUPPRESS_ERRORS', True);
			define('HTML2MD_NEWLINE', "\n");
			require_once USEFULFUNCTIONS_VENDORS . '/html2markdown.php';
		}
		if ($FormatHtml) {
			$HtmlFormatter = Gdn::Factory('HtmlFormatter');
			if ($HtmlFormatter) $Html = $HtmlFormatter->Format($Html);
		}
		$HtmlParser = new HTML_Parser($Html);
		$Result = $HtmlParser->get_markdown();
		return $Result;
	}
}

if (!function_exists('LoadPhpQuery')) {
	function LoadPhpQuery() {
		if (!function_exists('Pq')) require_once USEFULFUNCTIONS_VENDORS . '/phpQuery.php';
	}
}

/*if (!function_exists('PhpQueryLite')) {
	function PhpQueryLite($Document, $Options = False) {
	}
}*/

if (!function_exists('PqDocument')) {
	/**
	* Creates phpQuery document from string or file.
	* Options: 
	* FixHtml (True|False): Clean content by HtmlFormatter
	* phpQuery is a server-side, chainable, CSS3 selector driven Document Object Model (DOM),
	* API based on jQuery JavaScript Library. 
	* More information: http://code.google.com/p/phpquery/
	* 
	* @param mixed $Document, string, file or url.
	* @return PhpQueryDocument object.
	*/ 
	function PqDocument($Document, $Options = False) {
		if (!function_exists('Pq')) require_once USEFULFUNCTIONS_VENDORS.'/phpQuery.php';
		if (strpos($Document, '<') === False) {
			if (is_file($Document) || (substr($Document, 0, 7) == 'http://')) {
				$Document = file_get_contents($Document);
			}
		}
		if (ArrayValue('ConvertEncoding', $Options)) $Document = ConvertEncoding($Document);
		
		if (ArrayValue('FixHtml', $Options, True)) {
			$HtmlFormatter = Gdn::Factory('HtmlFormatter');
			if ($HtmlFormatter) $Document = $HtmlFormatter->Format($Document);
		}
		return phpQuery::newDocumentXHTML($Document);
	}
}