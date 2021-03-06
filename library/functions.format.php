<?php


if (!function_exists('FormatAlphaNumeric')) {
	/**
	* Removes all non-alpha-numeric characters (except for _ and -) from
	*
	* @param string $Mixed A string to be formatted.
	* @return string
	*/
	function FormatAlphaNumeric($Mixed) {
		return preg_replace('/([^\w\d_-])/', '', $Mixed);
	}
}

if (!function_exists('FormatForm')) {
	/**
	 * Gdn_Format::Form()
	 * @param mixed $Mixed
	 */
	function FormatForm($Mixed) {
		return htmlspecialchars($Mixed, ENT_QUOTES, 'utf-8');
	}
}

if (!function_exists('FormatText')) {
	/**
	* Takes a mixed variable, formats it for display on the screen as plain text.
	*
	* @param mixed $Mixed An object, array, or string to be formatted.
	* @return mixed
	*/
	function FormatText($Mixed, $AddBreaks = FALSE) {
		$Charset = 'utf-8';
		$Result = htmlspecialchars(strip_tags(preg_replace('`<br\s?/?>`', "\n", html_entity_decode($Mixed, ENT_QUOTES, $Charset))), ENT_NOQUOTES, $Charset);
		if ($AddBreaks) $Result = nl2br(trim($Result));
		return $Result;
	}
}

if (!function_exists('FormatTimespan')) {
	function FormatTimespan($timespan) {
		//$timespan -= 86400 * ($days = (int) floor($timespan / 86400));
		$timespan -= 3600 * ($hours = (int) floor($timespan / 3600));
		$timespan -= 60 * ($minutes = (int) floor($timespan / 60));
		$seconds = $timespan;

		$Result = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
		return $Result;
	}
}