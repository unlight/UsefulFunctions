<?php if (!defined('APPLICATION')) die(); # …

$PluginInfo['UsefulFunctions'] = array(
	'Name' => 'Useful Functions',
	'Description' => 'Useful functions for plugin and application developers (ex- PluginUtils).',
	'RequiredApplications' => array('Dashboard' => '>=2.0.13'),
	'Version' => '3.1.90',
	'Date' => '7 May 2011',
	'Author' => 'Vanilla Fan'
);

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
				// TODO: Incorrect fix me
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









