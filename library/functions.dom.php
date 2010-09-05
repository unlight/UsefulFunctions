<?php

function LoadPhpQuery(){
	//if(!class_exists('PhpQuery')) require_once dirname(__FILE__).DS.'vendors' . DS . 'phpQuery.php';
	if(!function_exists('Pq')) require_once PLUGINUTILS_VENDORS . DS . 'phpQuery.php';
}

function LoadQueryPath($Document = ''){
	static $HTMLPurifier;
	if(!function_exists('qp')) require_once PLUGINUTILS_VENDORS . DS . 'QueryPath.php';
	if($Document == '') return;
	// TODO: check for HTMLPurifierPlugin and throw Exception if not exists
	if(is_null($HTMLPurifier)) $HTMLPurifier = new HTMLPurifierPlugin();
	if(strpos($Document, '>') === False) $Document = file_get_contents($Document);
	$Document = $HTMLPurifier->Format($Document);
	$Document = trim($Document);
	if(!StringBeginsWith($Document, '<?xml')) $Document = '<?xml version="1.0" encoding="utf-8"?>'.$Document;
	return Qp($Document);
}