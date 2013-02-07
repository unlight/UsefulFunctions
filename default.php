<?php if (!defined('APPLICATION')) die(); # …


$PluginInfo['UsefulFunctions'] = array(
	'Name' => 'Useful Functions',
	'Description' => 'Useful functions for plugin and application developers (ex- PluginUtils).',
	'RequiredApplications' => array('Dashboard' => '>=2.0.18'),
	'Version' => '3.15.6',
	'Author' => 'Vanilla Fan',
	'SettingsUrl' => '/settings/usefulfunctions'
);

define('USEFULFUNCTIONS_LIBRARY', dirname(__FILE__).'/library');
define('USEFULFUNCTIONS_VENDORS', dirname(__FILE__).'/vendors');

if (class_exists('Gdn', False)) {
	Gdn::FactoryInstall('Snoopy', 'Snoopy', USEFULFUNCTIONS_VENDORS.'/Snoopy.class.php', Gdn::FactorySingleton);
	Gdn::FactoryInstall('Mailbox', 'ImapMailbox', USEFULFUNCTIONS_LIBRARY.'/class.imapmailbox.php', Gdn::FactorySingleton);
	Gdn::FactoryInstall('CssSpriteMap', 'CssSpriteMap', USEFULFUNCTIONS_VENDORS.'/CssSprite.php', Gdn::FactorySingleton);
	
	class UsefulFunctionsPlugin implements Gdn_IPlugin {

		public function PluginController_TimerTick_Create($Sender) {
			// Assume that $Sender->RequestArgs is empty.
			$Token = GetIncomingValue('TimerTickToken', RandomString(5));
			if ($Token == C('Plugins.UsefulFunctions.TimerTick.SecretKey', RandomString(8))) {
				require dirname(__FILE__) . '/bin/tick.php';
			}
		}

		public function SettingsController_UsefulFunctions_Create($Sender) {
			$Sender->Permission('Garden.Plugins.Manage');
			$Sender->AddSideMenu();
			$Sender->Title('UsefulFunctions Settings');
		
			$Validation = new Gdn_Validation();
			$Validation->ApplyRule('Plugins.UsefulFunctions.TimerTick.SecretKey', array('Required'));
			$ConfigurationModel = new Gdn_ConfigurationModel($Validation);
			$Sender->Form->SetModel($ConfigurationModel);
			$ConfigurationModel->SetField(array(
				'Plugins.UsefulFunctions.TimerTick.SecretKey',
			));
		
			if ($Sender->Form->AuthenticatedPostBack()) {
				//$FormValues = $Sender->Form->FormValues();
				$Saved = $Sender->Form->Save();
				if ($Saved) {
					$Sender->InformMessage(T('Saved'), array('Sprite' => 'Check', 'CssClass' => 'Dismissable AutoDismiss'));
				}
			} else {
				$Sender->Form->SetData($ConfigurationModel->Data);
			}

			$Sender->View = dirname(__FILE__) . '/views/settings.php';
			$Sender->Render();
		}


/*		public function Base_Render_Before($Sender) {
			if ($Sender->DeliveryType() != DELIVERY_TYPE_ALL) return;
			//$Sender->Head->AddScript('plugins/UsefulFunctions/js/noindex.js', 'text/javascript', array('path' => 'plugins/UsefulFunctions/js/noindex.js', 'sort' => 9999));
			//if (Debug()) $Sender->Head->AddScript('plugins/UsefulFunctions/js/var_dump.js', 'text/javascript', array('path' => 'plugins/UsefulFunctions/js/var_dump.js'));
		}*/


		
		public function Structure() {
			SearchAnyWhere(array('Structure' => True));
		}
	
		public function Setup() {
			if (!is_dir('uploads/cached')) {
				mkdir('uploads/cached', 0777, True);	
			}
		}
	}
}

require USEFULFUNCTIONS_LIBRARY.'/functions.output.php';
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
require USEFULFUNCTIONS_VENDORS.'/XML2Array.php';
