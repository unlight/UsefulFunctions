<?php if (!defined('APPLICATION')) die(); # …

$PluginInfo['UsefulFunctions'] = array(
	'Name' => 'Useful Functions',
	'Description' => 'Useful functions for plugin and application developers (ex- PluginUtils).',
	'RequiredApplications' => array('Dashboard' => '>=2.0.13'),
	'Version' => '3.6.122',
	'Date' => 'Summer 2011',
	'Author' => 'Vanilla Fan'
);

define('USEFULFUNCTIONS_LIBRARY', dirname(__FILE__).'/library');
define('USEFULFUNCTIONS_VENDORS', dirname(__FILE__).'/vendors');

if (class_exists('Gdn')) {
	Gdn::FactoryInstall('Zip', 'PclZip', PATH_LIBRARY.'/vendors/pclzip/pclzip.lib.php', Gdn::FactoryInstance);
	Gdn::FactoryInstall('Snoopy', 'Snoopy', USEFULFUNCTIONS_VENDORS.'/Snoopy.class.php', Gdn::FactorySingleton);
	Gdn::FactoryInstall('Mailbox', 'ImapMailbox', USEFULFUNCTIONS_LIBRARY.'/class.imapmailbox.php', Gdn::FactorySingleton);
	Gdn::FactoryInstall('CssSpriteMap', 'CssSpriteMap', USEFULFUNCTIONS_VENDORS.'/CssSprite.php', Gdn::FactorySingleton);
	
	// Since 2.0.18 PluginNameClass must be defined
	class UsefulFunctionsPlugin implements Gdn_IPlugin {
		
		public function Base_Render_Before($Sender) {
			if ($Sender->DeliveryType() != DELIVERY_TYPE_ALL) return;
			$Options = array('path' => 'plugins/UsefulFunctions/js/noindex.js', 'sort' => 9999);
			$Sender->Head->AddScript('plugins/UsefulFunctions/js/noindex.js', 'text/javascript', $Options);
		}
	
		public function Setup() {
		}
	}
	
}

require USEFULFUNCTIONS_LIBRARY.'/functions.render.php';
require USEFULFUNCTIONS_LIBRARY.'/functions.sql.php';
require USEFULFUNCTIONS_LIBRARY.'/functions.image.php';
require USEFULFUNCTIONS_LIBRARY.'/functions.time.php';
require USEFULFUNCTIONS_LIBRARY.'/functions.text.php';
require USEFULFUNCTIONS_LIBRARY.'/functions.network.php';
require USEFULFUNCTIONS_LIBRARY.'/functions.array-object.php';
require USEFULFUNCTIONS_LIBRARY.'/functions.string.php';
require USEFULFUNCTIONS_LIBRARY.'/functions.number.php';
require USEFULFUNCTIONS_LIBRARY.'/functions.file.php';
require USEFULFUNCTIONS_LIBRARY.'/functions.language.php';
require USEFULFUNCTIONS_LIBRARY.'/functions.validate.php';
require USEFULFUNCTIONS_LIBRARY.'/functions.debug.php';
require USEFULFUNCTIONS_LIBRARY.'/functions.misc.php';
require USEFULFUNCTIONS_LIBRARY.'/functions.geo.php';
require USEFULFUNCTIONS_LIBRARY.'/functions.deprecated.php';


