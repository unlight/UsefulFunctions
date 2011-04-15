<?php if (!defined('APPLICATION')) die(); # …

$PluginInfo['UsefulFunctions'] = array(
	'Name' => 'Useful Functions',
	'Description' => 'Useful functions for plugin and application developers (ex- PluginUtils).',
	'RequiredApplications' => array('Dashboard' => '>=2.0.13'),
	'Version' => '3.0.87',
	'Date' => '8 Apr 2011',
	'Author' => 'Vanilla Fan'
);

# INSTAL
# ======
# 1. Unpack and upload files to plugins directory
# 2. Add this string to cron task file ('crontab -e')
# */5 * * * *  /usr/local/bin/php -q /home/www/htdocs/plugins/UsefulFunctions/bin/tick.php
# 3. Change permission of file plugins/UsefulFunctions/bin/tick.php to 700

/**************************
DESCRIPTION
What this plugin do?
Nothing! As standalone.
This plugin is used by other plugins and applications, 
using specific functions which doesn't exists in Garden core.

CONFIG
$Configuration['Plugins']['UsefulFunctions']['Console']['MessageEnconding'] = 'cp866';
$Configuration['Plugins']['UsefulFunctions']['Console']['Check'] = TRUE;
$Configuration['Plugins']['UsefulFunctions']['ImPath'] = '/usr/local/bin/'; # ImageMagick path
$Configuration['Plugins']['UsefulFunctions']['InTick'] = FALSE;
$Configuration['Plugins']['UsefulFunctions']['InTickMessage'] = 'admin@local';

TODO

*/

define('USEFULFUNCTIONS_LIBRARY', dirname(__FILE__).'/library');
define('USEFULFUNCTIONS_VENDORS', dirname(__FILE__).'/vendors');


if (interface_exists('Gdn_IPlugin')) {
	class UsefulFunctionsPlugin implements Gdn_IPlugin {
		public function PluginController_ReEnableUsefulFunctions_Create($Sender) {
			$Sender->Permission('Garden.Admin.Only');
			$Session = Gdn::Session();
			$TransientKey = $Session->TransientKey();
			RemoveFromConfig('EnabledPlugins.UsefulFunctions');
			$OldConfiguration = C('Plugins.PluginUtils');
			if ($OldConfiguration) {
				SaveToConfig('Plugins.UsefulFunctions', $OldConfiguration);
				RemoveFromConfig('Plugins.PluginUtils');
			}
			Redirect('settings/plugins/all/UsefulFunctions/'.$TransientKey);
		}
		
		public function Setup() {
		}
	}
}

if (class_exists('Gdn')) {
	Gdn::FactoryInstall('Zip', 'PclZip', PATH_LIBRARY.'/vendors/pclzip/pclzip.lib.php', Gdn::FactoryInstance);
	Gdn::FactoryInstall('Snoopy', 'Snoopy', USEFULFUNCTIONS_VENDORS.'/Snoopy.class.php', Gdn::FactorySingleton);
	Gdn::FactoryInstall('Mailbox', 'ImapMailbox', USEFULFUNCTIONS_LIBRARY.'/class.imapmailbox.php', Gdn::FactorySingleton);
	Gdn::FactoryInstall('CssSpriteMap', 'CssSpriteMap', USEFULFUNCTIONS_VENDORS.'/CssSprite.php', Gdn::FactorySingleton);
}

require USEFULFUNCTIONS_LIBRARY.'/functions.render.php';
require USEFULFUNCTIONS_LIBRARY.'/functions.image.php';
require USEFULFUNCTIONS_LIBRARY.'/functions.time.php';
require USEFULFUNCTIONS_LIBRARY.'/functions.dom.php';
require USEFULFUNCTIONS_LIBRARY.'/functions.network.php';
require USEFULFUNCTIONS_LIBRARY.'/functions.array-object.php';
require USEFULFUNCTIONS_LIBRARY.'/functions.string-number.php';
require USEFULFUNCTIONS_LIBRARY.'/functions.file.php';
require USEFULFUNCTIONS_LIBRARY.'/functions.language.php';
require USEFULFUNCTIONS_LIBRARY.'/functions.validate.php';
require USEFULFUNCTIONS_LIBRARY.'/functions.debug.php';
require USEFULFUNCTIONS_LIBRARY.'/functions.misc.php';
require USEFULFUNCTIONS_LIBRARY.'/functions.sql.php';
require USEFULFUNCTIONS_LIBRARY.'/functions.deprecated.php';









