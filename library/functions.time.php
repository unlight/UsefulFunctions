<?php

if(!function_exists('Now')) {
	function Now(){
		return microtime(True);
	}
}

if(!function_exists('YearSeconds')) {
	function YearSeconds(){
		return (time() - strtotime('1 Jan'));
	}
}