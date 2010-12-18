<?php if (!defined('APPLICATION')) die(); # …

$PluginInfo['PluginUtils'] = array(
	'Name' => 'Plugin utils',
	'Description' => 'Useful functions for plugin and application developers.',
	'RequiredApplications' => array('Dashboard' => '>=2.0.13'),
	'Version' => '2.2.7.b57',
	'Author' => 'Vanilla Fan'
);

define('PLUGINUTILS_LIBRARY', dirname(__FILE__).DS.'library');
define('PLUGINUTILS_VENDORS', dirname(__FILE__).DS.'vendors');

if (class_exists('Gdn')) {
	Gdn::FactoryInstall('Zip', 'PclZip', PATH_LIBRARY.'/vendors/pclzip/pclzip.lib.php', Gdn::FactoryInstance);
	Gdn::FactoryInstall('Snoopy', 'Snoopy', PLUGINUTILS_VENDORS.DS.'Snoopy.class.php', Gdn::FactorySingleton);
	Gdn::FactoryInstall('Mailbox', 'ImapMailbox', PLUGINUTILS_LIBRARY.DS.'class.imapmailbox.php', Gdn::FactorySingleton);
	Gdn::FactoryInstall('CssSpriteMap', 'CssSpriteMap', PLUGINUTILS_VENDORS.DS.'CssSprite.php', Gdn::FactorySingleton);
}

require PLUGINUTILS_LIBRARY.DS.'functions.render.php';
require PLUGINUTILS_LIBRARY.DS.'functions.image.php';
require PLUGINUTILS_LIBRARY.DS.'functions.time.php';
require PLUGINUTILS_LIBRARY.DS.'functions.dom.php';
require PLUGINUTILS_LIBRARY.DS.'functions.network.php';
require PLUGINUTILS_LIBRARY.DS.'functions.array-object.php';
require PLUGINUTILS_LIBRARY.DS.'functions.string-number.php';
require PLUGINUTILS_LIBRARY.DS.'functions.file.php';
require PLUGINUTILS_LIBRARY.DS.'functions.language.php';
require PLUGINUTILS_LIBRARY.DS.'functions.validate.php';
require PLUGINUTILS_LIBRARY.DS.'functions.debug.php';
require PLUGINUTILS_LIBRARY.DS.'functions.misc.php';
require PLUGINUTILS_LIBRARY.DS.'functions.sql.php';
require PLUGINUTILS_LIBRARY.DS.'functions.deprecated.php';
require PLUGINUTILS_LIBRARY.DS.'functions.dev.php';


# INSTAL
# ======
# 1. Unpack and upload files to plugins directory
# 2. Add this string to cron task file ('crontab -e')
# */5 * * * * /path/to/plugins/PluginUtils/bin/tick.php
# 3. Change permission of file plugins/PluginUtils/bin/tick.php to 700

/*

DESCRIPTION
===========
What this plugin do?
Nothing! As standalone.
This plugin is used by other plugins and applications, 
using specific functions which doesn't exists in Garden core.

CONFIG
======
$Configuration['Plugins']['Console']['MessageEnconding'] = 'your code page here';
$Configuration['Plugins']['Console']['Check'] = True; // True/False

$Configuration['Plugins']['CssSprites']['ImPath'] = PATH_APPLICATIONS.DS.'salesoutlet/bin/ImageMagick/'; // WIN
$Configuration['Plugins']['CssSprites']['OutOutExt'] = '.jpg';
//$Configuration['Plugins']['CssSprites']['ImPath'] = '/usr/local/bin/'; // *NIX (default)

TODO
====

*/





