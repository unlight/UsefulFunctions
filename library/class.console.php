<?php if (!defined('APPLICATION')) exit();

// …

class Console extends Gdn_Pluggable {
	
	/*public function __construct(){
		parent::__construct();
	}*/
	
	public static function ErrorHandler($Error, $Message = '', $File = '', $Line = ''){
		
		if(error_reporting() == 0) return False;
		
		$Object = 'PHP';
		$Method = 'Function';
		
		if(is_object($Error)){
			$Info = False;
			foreach($Error->GetTrace() as $Info) break;
			$Method = ArrayValue('function', $Info, $Method);
			$Object = ArrayValue('class', $Info, $Object);
			$Message = $Error->GetMessage();
			$File = $Error->GetFile();
			$Line = $Error->GetLine();
			$Error = -1;
		}
		
		$File = str_replace(PATH_ROOT.DS, '', $File);
		
		switch($Error){
			case E_NOTICE: $Code = 'NOTICE'; break;
			case E_WARNING: $Code = 'WARNING'; break;
			case -1: $Code = 'UNCAUGHT EXCEPTION'; break;
			default: $Code = 'ERROR';
		}
		
		$Message = strip_tags($Message);
		self::Message('%s: %s in %s on line %s', $Code, $Message, $File, $Line);
		self::Message($Message);
		LogMessage($File, $Line, $Object, $Method, $Message, $Code);
		
		// send error to email
		$To = Gdn::Config('Plugins.PluginUtils.Console.Errors.EmailToAddress');
		if(self::Check() && $To != False){
			$Text = sprintf(Gdn::Translate('Error in console script %1$s %2$s %3$s %4$s'), $Code, $Message, $File, $Line);
			if(!class_exists('Gdn_Email')) return error_log("Error ($Code)", 1, $To, $Text);
			$Email = new Gdn_Email();
			$Email
				->To($To)
				->Message($Text)
				->Subject("Error ($Code)")
				->Send('ErrorInConsoleScript');
		}
		
		exit();
	}
	
	public static function Message(){
		if(!defined('STDOUT')) return;
		static $Encoding;
		if (is_null($Encoding)) $Encoding = strtolower(C('Plugins.PluginUtils.Console.MessageEnconding', 'utf-8'));
		$Args = func_get_args();
		$Message =& $Args[0];
		$Count = substr_count($Message, '%');
		if($Count != Count($Args) - 1) $Message = str_replace('%', '%%', $Message);
		$Message = call_user_func_array('sprintf', $Args);
		if($Encoding && $Encoding != 'utf-8') $Message = mb_convert_encoding($Message, $Encoding, 'utf-8');
		$S = self::TimeSeconds() . ' -!- ' . $Message;
		if(substr($S, -1, 1) != "\n") $S .= "\n";
		fwrite(STDOUT, $S);
	}
	
	/*protected static function StdOut($S, $bNewLine = True){
		if($bNewLine && substr($S, -1, 1) != "\n") $S .= "\n";
		fwrite(STDOUT, $S);
	}*/
	
	public static function Argument($Name, $Default = False){
		$argv = ArrayValue('argv', $GLOBALS);
		if(!is_array($argv)) return $Default;

		if(is_int($Name)) return ArrayValue($Name, $argv);
		
		$Key = array_search('-'.$Name, $argv);
		if($Key === False) return $Default;
		$Result = ArrayValue($Key + 1, $argv);
		$Result = array_map('trim', explode(',', $Result));
		if(Count($Result) == 1) $Result = $Result[0];
		return $Result;
	}
	
	public static function TimeSeconds(){
		static $Started;
		if(is_null($Started)) $Started = Now();
		return Gdn_Format::Timespan(Now() - $Started);
	}
	
	public static function Check(){
		return (PHP_SAPI == 'cli');
	}
	
	public static function Wait($Seconds = 1, $bDrawDots = True) {
		fwrite(STDOUT, self::TimeSeconds() . ' -!- Waiting.');
		$Seconds = Clamp((int)$Seconds, 1, 3600); // 1 hour max
		for($i = 0; $i < $Seconds; $i++){
			sleep(1);
			if($bDrawDots) fwrite(STDOUT, '.');
		}
		fwrite(STDOUT, "\n");
	}
	
	/*public static function Admin() {
		$Session = Gdn::Session();
		$User = new StdClass();
		$User->Admin = 1;
		$User->UserID = 1;
		TouchValue('User', $User, False);
		TouchValue('UserID', $Session, 0);
	}*/
	
}


















// dummy