<?php if (!defined('APPLICATION')) exit();

// …

class Console extends Gdn_Pluggable {
	
	public static function InitializeErrorHandler() {
		set_exception_handler(array('Console', 'ExceptionHandler'));
		set_error_handler(array('Console', 'ErrorHandler'), E_ALL);
	}
	
	public static function ErrorHandler($No, $Message, $File, $Line, $Globals = Null) {
		$Code = 0;
		throw new ErrorException($Message, $Code, $No, $File, $Line);
	}
	
	public static function ExceptionHandler($Exception) {
		$Message = $Exception->GetMessage();
		$File = $Exception->GetFile();
		$Line = $Exception->GetLine();
		echo "Error: $Message\n";
		echo $Exception->GetTraceAsString() . "\n";
		echo "$File\n";
		$FileArray = file($File);
		array_unshift($FileArray, '');
		for ($LengthAfter = $Line + 3, $i = $Line - 2; $i < $LengthAfter; $i++) {
			$FileLine =& $FileArray[$i];
			if ($FileLine !== Null) {
				$Px = '    ';
				if ($i == $Line) {
					$FileLine = substr($FileLine, 0, -1) . " // <-- HERE!\n";
					$Px = '>>> ';
				}
				echo "{$Px}$i: {$FileLine}";
			}
		}
		LogException($Exception);
		die();
	}
	
	public static function CheckColorSupport() {
		static $Result;
		if ($Result === Null) {
			$bWindows = (substr(PHP_OS, 0, 3) == 'WIN');
			if ($bWindows) $Result = (getenv('ANSICON') !== False);
			else $Result = (function_exists('posix_isatty') && posix_isatty(STDOUT));
		}
		return $Result;
	}
	
	static protected $ForegroundColor = array(
		'Black' => 30,
		'Blue' => 34,
		'Green' => 32,
		'Cyan' => 36,
		'Red' => 31,
		'Purple' => 35,
		'Brown' => 33,
		'Yellow' => 33,
		'White' => 37
	);

	public static function GetColorCode($Color = Null) {
		if ($Color === Null || !array_key_exists($Color, self::$ForegroundColor)) return "\033[0m";
		return "\033[" . self::$ForegroundColor[$Color] . 'm';
	}
	
	public static function ColorizeForeground($ColorToken = '') { // ColorToken = ^1
		$N = substr($ColorToken, 1, 1);
		if ($N === '') return self::GetColorCode();
		switch ($N) {
			case 0: return self::GetColorCode('White');
			case 1: return self::GetColorCode('Red');
			case 2: return self::GetColorCode('Green');
			case 3: return self::GetColorCode('Yellow');
			case 4: return self::GetColorCode('Blue');
			case 5: return self::GetColorCode('Turq');
			case 6: return self::GetColorCode('Pink');
			case 7: return self::GetColorCode('LightRed');
			case 8: return self::GetColorCode('Grey');
			case 9: return self::GetColorCode('GreyWhite');
			default: break;
		}
		return self::GetColorCode(); // reset
	}
	
	public static function ColorizeMessage($PaintString) { // ex- Paint
		$bColorSupport = self::CheckColorSupport();
		if (!$bColorSupport) return preg_replace('/\^\d/', '', $PaintString);
		$OutString = '';
		$Pos = strpos($PaintString, '^');
		while ($Pos > -1) {
			if ($Pos > 0) {
				$OutString .= substr($PaintString, 0, $Pos);
				$PaintString = substr($PaintString, $Pos);
				$Pos = 0;
			}
			$ColorToken = substr($PaintString, $Pos, 2);
			$N = strlen($PaintString) - 2;
			if ($N > 0) $PaintString = substr($PaintString, -$N);
			else $PaintString = '';
			$OutString .= self::ColorizeForeground($ColorToken);
			$Pos = strpos($PaintString, '^');
		}
		if ($PaintString != '') $OutString .= $PaintString;
		$OutString .= self::GetColorCode();
		return $OutString;
	}
	
	public static function Message() {
		if (!defined('STDOUT')) return;
		static $Encoding;
		if (is_null($Encoding)) $Encoding = strtolower(C('Plugins.UsefulFunctions.Console.MessageEnconding', 'utf-8'));
		$Args = func_get_args();
		$Message =& $Args[0];
		$Count = substr_count($Message, '%');
		if($Count != Count($Args) - 1) $Message = str_replace('%', '%%', $Message);
		$Message = call_user_func_array('sprintf', $Args);
		if($Encoding && $Encoding != 'utf-8') $Message = mb_convert_encoding($Message, $Encoding, 'utf-8');
		$S = self::TimeSeconds() . ' -!- ' . self::ColorizeMessage($Message);
		if (substr($S, -1, 1) != "\n") $S .= "\n";
		fwrite(STDOUT, $S);
		return $S;
	}
	
	//public static GetOption() {
	//}
	
	public static function Argument($Name, $Default = False) {
		$argv = ArrayValue('argv', $GLOBALS);
		if (!is_array($argv)) return $Default;
		if (is_int($Name)) return ArrayValue($Name, $argv);
		$Key = array_search('-'.$Name, $argv);
		if ($Key === False) return $Default;
		$Result = ArrayValue($Key + 1, $argv);
		$Result = array_map('trim', explode(',', $Result));
		if (count($Result) == 1) $Result = $Result[0];
		return $Result;
	}
	
	public static function TimeSeconds() {
		static $Started;
		if (is_null($Started)) $Started = Now();
		return Gdn_Format::Timespan(Now() - $Started);
	}
	
	public static function Check() {
		return (PHP_SAPI == 'cli');
	}
	
	public static function Wait($Seconds = 1, $bDrawDots = True) {
		fwrite(STDOUT, self::TimeSeconds() . ' -!- Waiting.');
		$Seconds = Clamp((int)$Seconds, 1, 3600); // 1 hour max
		for ($i = 0; $i < $Seconds; $i++) {
			sleep(1);
			if ($bDrawDots) fwrite(STDOUT, '.');
		}
		fwrite(STDOUT, "\n");
	}

}


















// dummy