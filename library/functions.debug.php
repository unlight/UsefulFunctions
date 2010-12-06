<?php

if(!function_exists('d')) {
	function d(){
		static $bSetStyle = True;
		static $bExit = True;
		if(!defined('DPHP_USE_ACCESSIBLE')) require PLUGINUTILS_VENDORS.DS.'class.dumphper.php';
		$Args = func_get_args();
		if(count($Args) == 0 && $bExit) $bExit = False;
		if (PHP_SAPI != 'cli') {
			if($bSetStyle) {
				$bSetStyle = False;
				echo "<style type='text/css'>.dumphper span{font-size:13px !important;font-family:'Arial' !important;}</style>\n";
			}
			foreach($Args as $A) Dumphper::dump($A);
		} else {
			$i = 1;
			ob_start();
			foreach($Args as $A){
				echo str_repeat('*', $i++) . ' ';
				var_dump($A);
			}
			$String = ob_get_contents();
			@ob_end_clean();
			$Encoding = Gdn::Config('Plugins.PluginUtils.Console.MessageEnconding');
			$String = preg_replace("/\=\>\n +/s", '=> ', $String);
			if($Encoding && $Encoding != 'utf-8') $String = mb_convert_encoding($String, $Encoding, 'utf-8');
			echo $String;
		}
		// TODO: neeed close database connection
		if($bExit) exit();
	}
}

function dplg($PluginFunction){
	if (!isset($_GET['d'])) return;
	if (in_array($PluginFunction, array('gdn_locale_beforetranslate_handler', 'base_beforetranslate_handler'))) return;
	
	$CamelizedFunction = str_replace(' ', '_', ucwords(str_replace('_', ' ', $PluginFunction)));
	
	$Search = array('handler', 'model', 'controller', 'query', 'after', 'before', 'add', 'module',
		'asset', 'collection', 'method', 'fetch', 'master', 'pre', 'user', 'session', 'application', 
		'comment', 'body', 'meta', 'form', 'buttons', 'tabs', 'get', 'render');
	$Replace = array_map('ucfirst', $Search);
	
	$CamelizedFunction = str_replace($Search, $Replace, $CamelizedFunction);

	$Args = func_get_args();
	if(!defined('DPLG_STYLE')){
		define('DPLG_STYLE', 1);
		echo "<style type='text/css'>span.d{border:1px dashed black;font-size:13px;font-family:Tahoma;display:inline-block;padding:0 2;margin:1;}</style>";
	}

	printf("\n<span class='d'>%s</span>", $CamelizedFunction);
	
}

