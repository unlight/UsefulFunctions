#!/usr/local/bin/php
<?php
# Add this line to cron task file, 'crontab -e'
# 5 * * * * /home/www/htdocs/plugins/PluginUtils/bin/tick.php
# ATTENTION! MAKE SURE THAT NEW LINE IS [LF] only
# Better: */5 * * * *  /usr/local/bin/php -q /home/www/htdocs/plugins/PluginUtils/bin/tick.php
# Then no matter what is new line symbol [LF] or [CRLF]
#

require dirname(__FILE__) . '/../bootstrap.console.php';
$bLoop = Console::Argument('loop', False) !== False;
$Reset = Console::Argument('reset', False) !== False;
if ($Reset) {
	RemoveFromConfig('Plugins.PluginUtils.InTick');
	Console::Message('^3Tick mark removed!');
}
$InTick = C('Plugins.PluginUtils.InTick');

if ($InTick === TRUE) {
	$Message = "InTick ".Gdn_Format::ToDateTime();

	exit();
}
SaveToConfig('Plugins.PluginUtils.InTick', TRUE);

ini_set('memory_limit', '256M');
require PLUGINUTILS_LIBRARY . DS .  'class.tick.php';

$Handler = new Tick();

$Ticks = array(60 => 'Minute', 3600 => 'Hour', 86400 => 'Day');
$Matches = array('Minutes' => 'i', 'Hours' => 'H', 'Day' => 'j', 'Month' => 'n'); // , 'WeekDay' => 'w'

$Events = array();
$LastYearSeconds = 0;

do {
	$YearSeconds = (int)(YearSeconds()/60) * 60; // rewind to begining of the minute
	$Event = '';
	foreach($Matches as $Name => $Token){
		$Event = PrefixString('Match', $Event . '_' . date($Token) . '_' . $Name);
		$Handler->FireEvent($Event);
		Console::Message('Tick: %s', $Event);
		$Events[] = $Event;
	}
	
	$Event = $Events[1].'_'.date('l');
	$Handler->FireEvent($Event);
	Console::Message('Tick: %s', $Event);
	$Events[] = $Event;
	
	$Range = Flatten(array(
		range(1, 99, 1),
		range(100, 999, 5))
	);
	
	foreach ($Range as $i) {
		foreach($Ticks as $Second => $Name){
			$Suffix = ($i == 1) ? '' : 's';
			if($YearSeconds % $Second == 0 && ($YearSeconds / $Second) % $i == 0){
				$Event = 'Every_'.$i.'_'.$Name.$Suffix;
				$Events[] = $Event;
				// TODO: FIX ME, chain break if error, maybe use try/catch here
				$Handler->FireEvent($Event);
				Console::Message('Tick: %s', $Event);
			}
		}
	}
	
	if($bLoop != False){
		$LastYearSeconds = $YearSeconds;
		$SleepSeconds = 60 - YearSeconds() % 60;
		Console::Message('Sleep (%s %s)', $SleepSeconds, Plural($SleepSeconds, 'second', 'seconds'));
		// prevent tick in second while in loop
		sleep($SleepSeconds);
	}
	
} while ($bLoop);


$Database = Gdn::Database();
if($Database != Null) $Database->CloseConnection();
RemoveFromConfig('Plugins.PluginUtils.InTick');
exit();




/* Example:


class ExamplePlugin implements Gdn_IPlugin {

	public function Tick_Every_5_Minutes_Handler(){
		// 
	}

	public function Tick_Every_180_Minutes_Handler(){
		// 
	}

	public function Tick_Match_55_Minutes_15_Hours_Sunday(){
		// 
	}
	
	public function Tick_Every_25_Hours_Handler(){
		//
	}

	public function Tick_Every_1_Hour_Handler() {
		//
	}

	public function Tick_Match_57_Minutes_Handler() {
	}
	
	public function Tick_Match_57_Minutes_10_Hours_Handler(){
	}
	
	public function Tick_Match_57_Minutes_10_Hours_1_Day_Handler(){
	}
	
	public function Tick_Match_57_Minutes_10_Hours_1_Day_7_Month_Handler(){
	}
	
	public static function Tick_Match_50_Minutes_07_Hours_Handler() {
		if(date('D') != 'Mon') return;
		// weekly
	}
	
	public static Match_17_Minutes_11_Hours_Thursday() {
		// Every Thursday on 11:17 am
	}


}


*/
