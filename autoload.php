<?php
// Autoload file for composer.

function LoadFunctions($Name) {
	$File = dirname(__FILE__) . '/library/functions.' . strtolower($Name) . '.php';
	require_once $File;
}