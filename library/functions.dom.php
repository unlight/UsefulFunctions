<?php

function LoadPhpQuery(){
	//if(!class_exists('PhpQuery')) require_once dirname(__FILE__).DS.'vendors' . DS . 'phpQuery.php';
	if (!function_exists('Pq')) require_once PLUGINUTILS_VENDORS . DS . 'phpQuery.php';
}

function PqDocument($Document) {
	if (!function_exists('Pq')) require_once PLUGINUTILS_VENDORS . DS . 'phpQuery.php';
	$HtmlFormatter = Gdn::Factory('HtmlFormatter');
	if (strpos($Document, '<') === False) {
		if (is_file($Document)) $Document = file_get_contents($Document);
	}
	if ($HtmlFormatter) $Document = $HtmlFormatter->Format($Document);
	return phpQuery::newDocumentXHTML($Document);
}
