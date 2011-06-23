<?php

/**
* Validate email using getmxrr() function.
* 
* @param string $Value, email which need to be validated.
* @return bool $Result.
*/
if (!function_exists('ValidateDnsEmail')) {
	function ValidateDnsEmail($Value, $Field = '') {
		if ($Value === NULL) return True; // + required
		$Result = ValidateEmail($Value, $Field);
		if ($Result !== True) return 'ValidateEmail';
		list($User, $Host) = explode('@', $Value);
		$MxHosts = array();
		$Result = getmxrr($Host, $MxHosts);
		return $Result;
	}
}