<?php

/**
* Return current Unix timestamp with microseconds
*/
if (!function_exists('Now')) {
	function Now() {
		return microtime(True);
	}
}

/**
* Get the number of seconds since the current year
*/
if (!function_exists('YearSeconds')) {
	function YearSeconds() {
		return (time() - strtotime('1 Jan'));
	}
}

/**
* String interval to seconds
* Example: IntervalSeconds('2 minutes') => 120
*/
if (!function_exists('IntervalSeconds')) {
	function IntervalSeconds($StringDate) {
		return strtotime($StringDate, 0);
	}
}
