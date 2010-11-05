<?php

function LoadPhpQuery(){
	//if(!class_exists('PhpQuery')) require_once dirname(__FILE__).DS.'vendors' . DS . 'phpQuery.php';
	if (!function_exists('Pq')) require_once PLUGINUTILS_VENDORS . DS . 'phpQuery.php';
}

function PqDocument($Document, $Options = False) {
	if (!function_exists('Pq')) require_once PLUGINUTILS_VENDORS . DS . 'phpQuery.php';
	if (strpos($Document, '<') === False) {
		if (is_file($Document) || (substr($Document, 0, 4) == 'http')) {
			$Document = file_get_contents($Document);
		}
	}
	if (GetValue('ConvertEncoding', $Options)) $Document = ConvertEncoding($Document);
	
	if (GetValue('FixHtml', $Options, True)) {
		$HtmlFormatter = Gdn::Factory('HtmlFormatter');
		if ($HtmlFormatter) $Document = $HtmlFormatter->Format($Document);
	}
	return phpQuery::newDocumentXHTML($Document);
}
