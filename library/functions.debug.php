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
	
	if(!IsSet($_GET['d'])) return;
	if(in_array($PluginFunction, array('gdn_locale_beforetranslate_handler', 'base_beforetranslate_handler'))) return;
	
	$Args = func_get_args();
	if(!defined('DPLG_STYLE')){
		define('DPLG_STYLE', 1);
		echo "<style type='text/css'>em.d{border:1px dashed black;font-size:13px;font-family:Tahoma;display:inline-block;padding:0 2;margin:1;}</style>";
	}

	printf("\n<em class='d'>%s</em>", $PluginFunction);
	
	//@ob_end_flush();
}


function dplg_(){
	
	if(!IsSet($_GET['d'])) return;
	
	$Args = func_get_args();
	if(!defined('DPLG_STYLE')){
		define('DPLG_STYLE', 1);
		echo "<style type='text/css'>em.d{border:1px dashed black;font-size:13px;font-family:Tahoma;display:inline-block;padding:0 2;margin:1;}</style>";
	}
	$Args[0] = "\n<em class='d'>$Args[0]</em>";
	for($i = 1; $i < Count($Args); $i++){
		$S =& $Args[$i];
		if(Is_String($S)){
			$S = ucfirst($S);
			$LowerKeywords = array('module', 'model', 'add', 'query', 'controller', 'before', 'after', 'plugin', 'render', 'comment', 'count', 'update');
			$UpperKeywords = array_map('ucfirst', $LowerKeywords);
			$S = preg_replace('/(^|_\w)/e', 'strtoupper("$1")', $S);
			$S = str_replace($LowerKeywords, $UpperKeywords, $S);
		}elseif(Is_Array($S)){
			if(Count($S) == 0) $S = "";
			elseif(Count($S) >= 1){
				$S = " (" . implode(', ', Array_Keys($S)) . ")";
			}
		}
	}
	call_user_func_array('printf', $Args);
	
	return;

	$LogMessage = call_user_func_array('sprintf', $Args);
	$LogMessage = strip_tags($LogMessage);
	$Info = "POST @ " . (bool)$_POST;
	error_log("$LogMessage ($Info)", 3, 'dplg.log');
	
}