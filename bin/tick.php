#!/usr/local/bin/php
<?php
// */5 * * * * /home/www/htdocs/plugins/PluginUtils/bin/tick.php
ini_set('memory_limit', '256M');

require dirname(__FILE__) . '/../bootstrap.console.php';
require PLUGINUTILS_LIBRARY . DS .  'class.tick.php';

$bLoop = Console::Argument('loop', False) !== False;
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


/* Example:


class ExamplePlugin implements Gdn_IPlugin{
	
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

	public function Tick_Every_1_Hour_Handler(){
		//
	}

	public function Tick_Match_57_Minutes_Handler(){
	}
	
	public function Tick_Match_57_Minutes_10_Hours_Handler(){
	}
	
	public function Tick_Match_57_Minutes_10_Hours_1_Day_Handler(){
	}
	
	public function Tick_Match_57_Minutes_10_Hours_1_Day_7_Month_Handler(){
	}


}

*/