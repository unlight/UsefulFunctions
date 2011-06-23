<?php

if (!function_exists('Deprecated')) {
	/**
	* Mark a function deprecated (Garden)
	*
	* @param string $Name The name of the deprecated function.
	* @param string $NewName The name of the new function that should be used instead.
	*/
	function Deprecated($Name, $NewName = FALSE) {
		$Msg = $Name.' is deprecated.';
		if ($NewName) $Msg .= " Use $NewName instead.";
		trigger_error($Msg, E_USER_DEPRECATED);
	}
}

/**
* Displays structured information about one or more expressions that includes its type and value.
* 
* @param mixed $Expression.
* @return string $String.
*/
if (!function_exists('VarDump')) {
	function VarDump($Var) {
		ob_start();
		var_dump($Var);
		$String = ob_get_contents();
		ob_end_clean();
		return $String;
	}
}


/**
* A quick and simple service for getting pictures of kittens for use as placeholders in your designs or code. 
* Just put your image size (width & height) after our URL and you'll get a placeholder.
* @param int $Width 
* @param int $Height 
* @param bool $Colored 
* @return string $Url. 
*/
if (!function_exists('PlaceKitten')) {
	function PlaceKitten($Width, $Height = False, $Colored = False) {
		if ($Height === False) $Height = $Width;
		$ResultUrl = 'http://placekitten.com/';
		if (!$Colored) $ResultUrl .= 'g/';
		$ResultUrl .= $Width . '/' . $Height;
		return $ResultUrl;
	}
}


/**
* Dumps information about arguments passed to functions
* 
*/
if (!function_exists('d')) {
	function d() {
		static $bSetStyle = True;
		static $bExit = True;
		define('DPHP_USE_ACCESSIBLE', (version_compare(PHP_VERSION, '5.3.0') >= 0));
		if (!class_exists('Dumphper', False)) {
			$Path = (defined('USEFULFUNCTIONS_VENDORS')) ? USEFULFUNCTIONS_VENDORS : dirname(__FILE__).'/../vendors';
			require $Path.'/class.dumphper.php';
			Dumphper::$escape_keys = true;
			Dumphper::$max_showw_depth = 8;
			Dumphper::$encoding = 'utf-8';
		}
		$Args = func_get_args();
		if (count($Args) == 0 && $bExit) $bExit = False;
		if (PHP_SAPI != 'cli') {
			if (!headers_sent()) header('Content-Type: text/html; charset=utf-8');
			if ($bSetStyle) {
				$bSetStyle = False;
				echo "<style type='text/css'>.dumphper span{font-size:13px !important;font-family:'Arial' !important;}</style>\n";
			}
			foreach ($Args as $A) {
				if (is_string($A) && defined('CP1251')) $A = ConvertEncoding($A);
				Dumphper::dump($A);
			}
		} else {
			$i = 1;
			ob_start();
			foreach ($Args as $A) {
				echo str_repeat('*', $i++) . ' ';
				var_dump($A);
			}
			$String = ob_get_contents();
			@ob_end_clean();
			$Encoding = Gdn::Config('Plugins.UsefulFunctions.Console.MessageEnconding');
			$String = preg_replace("/\=\>\n +/s", '=> ', $String);
			if ($Encoding && $Encoding != 'utf-8') $String = mb_convert_encoding($String, $Encoding, 'utf-8');
			echo $String;
		}
		if (class_exists('Gdn')) {
			$Database = Gdn::Database();
			if ($Database != Null) $Database->CloseConnection();
		}
		if ($bExit) exit();
	}
}

/**
* Undocumented 
* 
* @param string 
*/
if (!function_exists('dplg')) {
	function dplg($PluginFunction) {
		if (!isset($_GET['d'])) return;
		if (in_array($PluginFunction, array('gdn_locale_beforetranslate_handler', 'base_beforetranslate_handler'))) return;
		
		$CamelizedFunction = str_replace(' ', '_', ucwords(str_replace('_', ' ', $PluginFunction)));
		
		$Search = array('handler', 'model', 'controller', 'query', 'after', 'before', 'add', 'module',
			'asset', 'collection', 'method', 'fetch', 'master', 'pre', 'user', 'session', 'application', 
			'comment', 'body', 'meta', 'form', 'buttons', 'tabs', 'get', 'render');
		$Replace = array_map('ucfirst', $Search);
		
		$CamelizedFunction = str_replace($Search, $Replace, $CamelizedFunction);

		$Args = func_get_args();
		if (!defined('DPLG_STYLE')) {
			define('DPLG_STYLE', 1);
			echo "<style type='text/css'>span.d{border:1px dashed black;font-size:13px;font-family:Tahoma;display:inline-block;padding:0 2;margin:1;}</style>";
		}

		printf("\n<span class='d'>%s</span>", $CamelizedFunction);
		
	}
}
