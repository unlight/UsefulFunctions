<?php

if (!function_exists('LoadPhpQuery')) {
	function LoadPhpQuery() {
		//if(!class_exists('PhpQuery')) require_once dirname(__FILE__).DS.'vendors' . DS . 'phpQuery.php';
		if (!function_exists('Pq')) require_once USEFULFUNCTIONS_VENDORS . DS . 'phpQuery.php';
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
		if (!function_exists('Pq')) require_once USEFULFUNCTIONS_VENDORS . DS . 'phpQuery.php';
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