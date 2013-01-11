<?php
error_reporting(E_ALL & ~E_STRICT);
ini_set('html_errors', 0);
ini_set('display_errors', 1);
ini_set('track_errors', 1);
ini_set('max_execution_time', 0);

if (!defined('APPLICATION')) define('APPLICATION', 'Garden');
if (!defined('APPLICATION_VERSION')) define('APPLICATION_VERSION', '1.0');
if (!defined('DS')) define('DS', '/');

chdir(dirname(__FILE__).'/../..');
if (!defined('PATH_ROOT')) define('PATH_ROOT', realpath('.'));

require_once PATH_ROOT.'/bootstrap.php';
if (!defined('USEFULFUNCTIONS_LIBRARY')) define('USEFULFUNCTIONS_LIBRARY', dirname(__FILE__).'/library');
require_once USEFULFUNCTIONS_LIBRARY.'/class.console.php';

if (!Console::Check() && C('Plugins.UsefulFunctions.Console.Check', True)) {
	trigger_error('Please, run script from command line!', E_USER_ERROR);
	return 1;
}
//Console::InitializeErrorHandler();

if (PHP_SAPI == 'cli') {
	ini_set('memory_limit', C('Plugins.UsefulFunctions.Console.MemoryLimit', '256M'));
	$_GET['DeliveryType'] = 'VIEW';
}

$Domain = explode('//', Gdn::Config('Garden.Domain'));
$Host = GetValue(1, $Domain);
Gdn::Request()->RequestHost($Host);
