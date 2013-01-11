<?php

/**
 * Silex specific functions.
 */

function Application() {
	static $Application;
	if (is_null($Application)) {
		foreach ($GLOBALS as $Key => $Value) {
			if (is_object($Value) && $Value instanceof Silex\Application) {
				$Application = $Value;
				break;
			}
		}
	}
	return $Application;
}