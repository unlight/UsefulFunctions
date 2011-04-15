<?php
error_reporting(E_ALL);
ini_set('html_errors', 'Off');
ini_set('display_errors', 'On');
ini_set('track_errors', 'On');

if (!defined('APPLICATION')) define('APPLICATION', 'Garden');
if (!defined('APPLICATION_VERSION')) define('APPLICATION_VERSION', '1.0');
if (!defined('DS')) define('DS', '/');

chdir(dirname(__FILE__).DS.'..'.DS.'..');
if (!defined('PATH_ROOT')) define('PATH_ROOT', realpath('.'));

require_once PATH_ROOT.DS.'bootstrap.php';
if (!defined('USEFULFUNCTIONS_LIBRARY')) define('USEFULFUNCTIONS_LIBRARY', dirname(__FILE__).DS.'library');
require_once USEFULFUNCTIONS_LIBRARY.'/class.console.php';

if(!Console::Check() && C('Plugins.UsefulFunctions.Console.Check')) die('Please, run script from command line!');

if (PHP_SAPI == 'cli') $_GET['DeliveryType'] = 'VIEW';

@ob_end_clean();

$Host = GetValue(1, explode('//', Gdn::Config('Garden.Domain')));
Gdn::Request()->RequestHost($Host);
