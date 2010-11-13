<?php if (!defined('APPLICATION')) die(); # …

$PluginInfo['PluginUtils'] = array(
	'Name' => 'Plugin utils',
	'Description' => 'Useful functions for plugin and application developers.',
	'RequiredApplications' => array('Dashboard' => '>=2.0.13'),
	'Version' => '2.0.36',
	'Author' => 'Vanilla Fan'
);

define('PLUGINUTILS_LIBRARY', dirname(__FILE__).DS.'library');
define('PLUGINUTILS_VENDORS', dirname(__FILE__).DS.'vendors');

Gdn::FactoryInstall('Zip', 'PclZip', PATH_LIBRARY.'/vendors/pclzip/pclzip.lib.php', Gdn::FactoryInstance);
Gdn::FactoryInstall('Snoopy', 'Snoopy', PLUGINUTILS_VENDORS.DS.'Snoopy.class.php', Gdn::FactorySingleton);
Gdn::FactoryInstall('Mailbox', 'ImapMailbox', PLUGINUTILS_LIBRARY.DS.'class.imapmailbox.php', Gdn::FactorySingleton);

require PLUGINUTILS_LIBRARY.DS.'functions.render.php';
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
This plugin is used by other plugins and applications, using specific functions which doesn't exists in Garden core.

CONFIG
======
$Configuration['Plugins']['Console']['MessageEnconding'] = 'your code page here';
$Configuration['Plugins']['Console']['Check'] = True; // True/False

TODO
====

*/