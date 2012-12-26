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