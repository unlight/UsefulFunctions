<?php
// …

if (!function_exists('LoremIpsum')) {
	/**
	* Lorem Ipsum Generator. Using remote service.
	* Undocumented.
	* 
	*/
	function LoremIpsum($Options = array()) {
		$Snoopy = Gdn::Factory('Snoopy');
		$Defaults = array(
			'language' => 'other',
			'other'	=> 'russian',
			'radio'	=> 'limit',
			'limit' => 1000, // words
			'num' => 1, // paragraph(s) 
			'type' => 'plain',
			//'download' => 'download',
			'Rhubarb' => 'Generate'
		);
		$Trim = False;
		if (GetValue('Name', $Options, False, True)) {
			$Options['radio'] = 'limit';
			$Options[$Options['radio']] = 1;
			$Trim = '.';
		}
		$Options = array_merge($Defaults, $Options);
		$Snoopy->Submit('http://generator.lorem-ipsum.info/lorem-ipsum-copy', $Options);
		$Doc = PqDocument($Snoopy->results, array('FixHtml' => False));
		$Result = Pq('#txt')->Text();
		if ($Trim !== False) $Result = trim($Result, $Trim);
		return $Result;
	}
}

if (!function_exists('Markdownify')) {
	/**
	* Converts HTML to Markdown
	*/ 
	function Markdownify($Html) {
		$Html = Gdn_Format::To($Html, 'xHtml');
		$Snoopy = Gdn::Factory('Snoopy');
		$Vars = array('input' => $Html, 'keepHTML' => 1);
		$Snoopy->Submit('http://milianw.de/projects/markdownify/demo.php', $Vars);
		$Doc = PqDocument($Snoopy->results);
		$Code = Pq('pre > code:eq(0)')->Text();
		$Result = $Code;
		return $Result;
	}
}

if (!function_exists('LoadPhpQuery')) {
	function LoadPhpQuery() {
		if (!function_exists('Pq')) require_once USEFULFUNCTIONS_VENDORS . '/phpQuery.php';
	}
}

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