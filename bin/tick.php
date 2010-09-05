#!/usr/local/bin/php
<?php
// */5 * * * * /home/www/htdocs/plugins/PluginUtils/bin/tick.php

require dirname(__FILE__) . DS . '../bootstrap.console.php';
require dirname(__FILE__) . DS . '../library/class.tick.php';

$bLoop = Console::Argument('loop', False) !== False;
$Handler = new Tick();

$Ticks = array(60 => 'Minute', 3600 => 'Hour', 86400 => 'Day');
$Matches = array('Minutes' => 'i', 'Hours' => 'H', 'Day' => 'j', 'Month' => 'n'); // , 'WeekDay' => 'w'

$Events = array();
$LastYearSeconds = 0;
do {
	$YearSeconds = (int)(YearSeconds()/60) * 60; // rewind to begining of the minute
	//d($YearSeconds % 60, $YearSeconds % 3600);

	

	//$YearMicroSeconds = microtime(1) - strtotime('1 Jan');
	//$Fraction = $YearMicroSeconds - $YearSeconds;
	/*$SleepSeconds = 60 - $YearSeconds % 60;
	$CorrectYearSeconds = (int)($YearSeconds/60) * 60;
	d(Gdn_Format::TimeSpan($CorrectYearSeconds), $CorrectYearSeconds, $YearSeconds, $SleepSeconds, $YearSeconds % 60);
	Console::Message('Sleep (%s %s)', $SleepSeconds, Plural($SleepSeconds, 'second', 'seconds'));
	sleep($SleepSeconds);
	$YearSeconds = $YearSeconds + $SleepSeconds;
	//if($YearSeconds % 60 != 0) throw new Exception('Failed');*/
	
	// matches
	$Event = '';
	foreach($Matches as $Name => $Token){
		//$Events[] = 'Any_' . date($Token) . '_' . $Name;
		$Event = PrefixString('Match', $Event . '_' . date($Token) . '_' . $Name);
		Console::Message('Tick: %s', $Event);
		$Handler->FireEvent($Event);
		$Events[] = $Event;
	}
	
	$Range = Flatten(array(
		range(1, 99, 1),
		range(100, 999, 5)
	));
	
	foreach ($Range as $i) {
		foreach($Ticks as $Second => $Name){
			$Suffix = ($i == 1) ? '' : 's';
			if($YearSeconds % $Second == 0 && ($YearSeconds / $Second) % $i == 0){
				$Event = 'Every_'.$i.'_'.$Name.$Suffix;
				Console::Message('Tick: %s', $Event);
				$Handler->FireEvent($Event);
				// TODO: FIX ME
				/*try {
					$Handler->FireEvent($Event);
				} catch(Exception $Exception) {
					$ErrorMessage = $Exception->GetMessage();
					
				}*/
				$Events[] = $Event;
			}
		}
	}
	
	if($bLoop != False){
		$LastYearSeconds = $YearSeconds;
		$SleepSeconds = 60 - YearSeconds() % 60;
		Console::Message('Sleep (%s %s)', $SleepSeconds, Plural($SleepSeconds, 'second', 'seconds'));
		sleep($SleepSeconds);		
	}
	
} while ($bLoop); // prevent tick in second while in loop


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