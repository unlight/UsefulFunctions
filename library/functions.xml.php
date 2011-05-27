<?php
// …

/**
* Converts HTML to Markdown
*/ 
if (!function_exists('Markdownify')) {
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

/**
* Creates phpQuery document from string or file.
* Options: 
* FixHtml (True|False): Clean content by HtmlFormatter
* phpQuery is a server-side, chainable, CSS3 selector driven Document Object Model (DOM),
* API based on jQuery JavaScript Library. 
* More information: http://code.google.com/p/phpquery/
*/ 

if (!function_exists('PqDocument')) {
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