<?php

if (!function_exists('LastJsonErrorMessage')) {
	function LastJsonErrorMessage() {
		if (!function_exists('json_last_error')) return;
		$Error = json_last_error();
		$Message = '';
		switch ($Error) {
			case JSON_ERROR_NONE: break; // No errors
			case JSON_ERROR_DEPTH: $Message = 'Maximum stack depth exceeded'; break;
			case JSON_ERROR_STATE_MISMATCH: $Message = 'Underflow or the modes mismatch'; break;
			case JSON_ERROR_CTRL_CHAR: $Message = 'Unexpected control character found'; break;
			case JSON_ERROR_SYNTAX: $Message = 'Syntax error, malformed JSON'; break;
			case JSON_ERROR_UTF8: $Message = 'Malformed UTF-8 characters, possibly incorrectly encoded'; break;
			default: $Message = 'Unknown error';
		}
		if ($Message) return $Message;
	}
}

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


if (!function_exists('DebugCheckPoint')) {
	/**
	* Undocumented 
	* 
	* @param string $Message. 
	* @return NULL.
	*/
	function DebugCheckPoint($Message, $Sender = False) {
		static $Count = 0;
		static $StartTime;
		if ($Count == 0) $StartTime = microtime(true);
		++$Count;
		$TimeStamp = sprintf(' (%01.3f)', (microtime(true) - $StartTime));
		
		if (is_int($Sender)) {
			$Trace = debug_backtrace();
			ob_start();
			debug_print_backtrace();
			$Backtrace = ob_get_contents();
			ob_end_clean();
			$Backtrace = preg_split('/#\d+ /', $Backtrace);
			unset($Backtrace[0], $Backtrace[1]);
			$PathRoot = PATH_ROOT;
			$Call = array();
			foreach ($Backtrace as &$Trace) {
				$CalledAtPos = strpos($Trace, 'called at');
				$SenderFileLine = str_replace($PathRoot, '', substr($Trace, $CalledAtPos + strlen('called at')));
				$SenderFileLine = trim(trim(trim($SenderFileLine), '[]'), '/\\');
				$Trace = mb_strimwidth($Trace, 0, 80, '…', 'utf-8');
				$Call[] = "\t" . $Trace . ' @ ' . $SenderFileLine;
				if (--$Sender == 0) break;
			}
			$Sender = ' #' . implode("\n", $Call);
		} elseif ($Sender) {
			$Sender = '# ' . $Sender;
		}
		
		$Message = sprintf('%04d', $Count) . $TimeStamp . ": $Message" . $Sender;
		if (PHP_SAPI != 'cli') $Message = '<div style=\'font-size:12px;font-family:"courier new";line-height:13px;\'>' . $Message . '</div>';
		echo $Message, "\n";
	}
}


/**
* Displays structured information about one or more expressions that includes its type and value.
* 
* @param mixed $Var.
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
		if (!class_exists('Dumphper', False)) {
			define('DPHP_USE_ACCESSIBLE', (version_compare(PHP_VERSION, '5.3.0') >= 0));
			$Path = (defined('USEFULFUNCTIONS_VENDORS')) ? USEFULFUNCTIONS_VENDORS : dirname(__FILE__).'/../vendors';
			require $Path.'/class.dumphper.php';
			Dumphper::$escape_keys = false;
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
			$Encoding = 'cp866';
			if (class_exists('Gdn')) $Encoding = Gdn::Config('Plugins.UsefulFunctions.Console.MessageEnconding');
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
		if ($_GET['d'] == 's') file_put_contents('eventkey.txt', "\n$CamelizedFunction", FILE_APPEND | LOCK_EX);
	}
}
