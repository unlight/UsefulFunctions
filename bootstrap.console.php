<?php
error_reporting(E_ALL);
ini_set('html_errors', 'Off');
ini_set('display_errors', 'On');
ini_set('track_errors', 'On');

if(!defined('APPLICATION')) define('APPLICATION', 'Garden');
if(!defined('APPLICATION_VERSION')) define('APPLICATION_VERSION', '1.0');
if(!defined('DS')) define('DS', '/');

chdir(dirname(__FILE__).DS.'..'.DS.'..');
if(!defined('PATH_ROOT')) define('PATH_ROOT', realpath('.'));

//$_SERVER['SHELL'] = 'php';

require_once PATH_ROOT.DS.'bootstrap.php';
require_once PLUGINUTILS_LIBRARY.DS.'class.console.php';
if(!Console::Check() && C('Plugins.PluginUtils.Console.Check')) die('Please, run script from command line!');

/*if(Console::Check()){
	$Callback = array('Console', 'ErrorHandler');
	set_error_handler($Callback);
	set_exception_handler($Callback);
}*/

if(PHP_SAPI == 'cli') $_GET['DeliveryType'] = 'VIEW';

@ob_end_clean();
