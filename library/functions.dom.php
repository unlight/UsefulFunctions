<?php

/**
* Parse a selector of the form #foo.bar.baz into constituent ID and classes.
* An array argument will be returned unchanged.
* php-helpers by Jason Frame [jason@onehackoranother.com]
*/

if (!function_exists('SelectorAttribute')) {
	function SelectorAttribute($Selector, $Attributes = False) {
		$Return = array();
		preg_match('/^(#([\w-]+))?((\.[\w-]+)*)$/', $Selector, $Matches);
        if (!empty($Matches[2])) $Return['id'] = $Matches[2];
        if (!empty($Matches[3])) $Return['class'] = trim(str_replace('.', ' ', $Matches[3]));
		if (is_array($Attributes)) $Return = array_merge($Attributes, $Return);
		return $Return;
	}
}


/*function parse_simple_selector($s) {
    if (!is_array($s)) {
        preg_match('/^(#([\w-]+))?((\.[\w-]+)*)$/', $s, $matches);
        $s = array();
        if (!empty($matches[2])) $s['id'] = $matches[2];
        if (!empty($matches[3])) $s['class'] = trim(str_replace('.', ' ', $matches[3]));
    }
    return $s;
}*/

if (!function_exists('LoadPhpQuery')) {
	function LoadPhpQuery() {
		//if(!class_exists('PhpQuery')) require_once dirname(__FILE__).DS.'vendors' . DS . 'phpQuery.php';
		if (!function_exists('Pq')) require_once PLUGINUTILS_VENDORS . DS . 'phpQuery.php';
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
		if (!function_exists('Pq')) require_once PLUGINUTILS_VENDORS . DS . 'phpQuery.php';
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