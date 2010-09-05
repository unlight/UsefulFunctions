<?php

if(!function_exists('d')) {
	function d(){
		static $bExit = True;
		if(!defined('DPHP_USE_ACCESSIBLE')) require dirname(__FILE__).DS.'vendors'.DS.'class.dumphper.php';
		$Args = func_get_args();
		if(count($Args) == 0 && $bExit) $bExit = False;
		if (PHP_SAPI != 'cli') {
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
			$Encoding = Gdn::Config('Plugins.Console.MessageEnconding');
			$String = preg_replace("/\=\>\n +/s", '=> ', $String);
			if($Encoding && $Encoding != 'utf-8') $String = mb_convert_encoding($String, $Encoding, 'utf-8');
			echo $String;
		}
		// TODO: neeed close database connection
		if($bExit) exit();
	}
}