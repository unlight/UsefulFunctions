<?php
error_reporting(E_ALL);
ini_set('html_errors', 0);
ini_set('display_errors', 1);
ini_set('track_errors', 1);

if (!defined('APPLICATION')) define('APPLICATION', 'Garden');
if (!defined('APPLICATION_VERSION')) define('APPLICATION_VERSION', '1.0');
if (!defined('DS')) define('DS', '/');

chdir(dirname(__FILE__).'/../..');
if (!defined('PATH_ROOT')) define('PATH_ROOT', realpath('.'));

require_once PATH_ROOT.'/bootstrap.php';
if (!defined('USEFULFUNCTIONS_LIBRARY')) define('USEFULFUNCTIONS_LIBRARY', dirname(__FILE__).'/library');
require_once USEFULFUNCTIONS_LIBRARY.'/class.console.php';

if(!Console::Check() && C('Plugins.UsefulFunctions.Console.Check', True)) die('Please, run script from command line!');
//Console::InitializeErrorHandler();

if (PHP_SAPI == 'cli') {
	ini_set('memory_limit', C('Plugins.UsefulFunctions.Console.MemoryLimit', '256M'));
	$_GET['DeliveryType'] = 'VIEW';
}

$Host = GetValue(1, explode('//', Gdn::Config('Garden.Domain')));
Gdn::Request()->RequestHost($Host);

