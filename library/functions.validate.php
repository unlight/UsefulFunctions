<?php

/**
* Validate email using getmxrr() function
*/

if(!function_exists('ValidateDnsEmail')) {
	function ValidateDnsEmail($Value, $Field = ''){
		if($Value === '') return True; // + required
		$Result = ValidateEmail($Value, $Field);
		if($Result !== True) return 'ValidateEmail';
		$EmailArray = SplitString($Value, '@');
		$User = ArrayValue(0, $EmailArray);
		$Host = ArrayValue(1, $EmailArray);
		$MxHosts = array();
		$Result = getmxrr($Host, $MxHosts);
		return $Result;
	}
}