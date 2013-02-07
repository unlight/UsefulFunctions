<?php

/**
 * Silex specific functions.
 */

function Application($Name = '') {
	static $Application;
	if (is_null($Application)) {
		foreach ($GLOBALS as $Key => $Value) {
			if (is_object($Value) && $Value instanceof Silex\Application) {
				$Application = $Value;
				break;
			}
		}
	}
	if ($Name) return $Application[$Name];
	return $Application;
}