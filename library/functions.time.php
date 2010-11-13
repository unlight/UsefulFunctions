<?php

if(!function_exists('Now')) {
	function Now(){
		return microtime(True);
	}
}

/**
* Get the number of seconds since the current year
*/
if(!function_exists('YearSeconds')) {
	function YearSeconds(){
		return (time() - strtotime('1 Jan'));
	}
}