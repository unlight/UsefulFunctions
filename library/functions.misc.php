<?php
if (!function_exists('AddToConfig')) {
	/**
	* Undocumented 
	* 
	* @param mixed 
	* @return bool $Result.
	*/
	function AddToConfig($Name, $Value = False) {
		$VarExport = create_function('$Value', 'return var_export(strval($Value), 1);');
		$Keys = array_map($VarExport, explode('.', $Name));
		$Key = implode('][', $Keys);
		$ValueCode = var_export($Value, 1);
		$ValueCode = "\n\$Configuration[$Key] = $ValueCode;";
		$Result = file_put_contents(PATH_CONF . '/config.php', $ValueCode, FILE_APPEND | LOCK_EX);
		return ($Result !== False);
	}
}

if (!function_exists('SetModuleSort')) {
	/**
	* Function for quick modify sorting for modules in configuration file.
	* See library/core/class.controller.php ~ L: 118
	* If $PositionItem is False (default) $ModuleName will be added to the edn of the list.
	* If $PositionItem is integer (positive or negative) ...
	* If $PositionItem is string ...
	* 
	* @param string $ModuleSortContainer, container name.
	* @param string $AssetName, asset name.
	* @param string $ModuleName, module name which need to add to config.
	* @param mixed $PositionItem.
	* @return bool. Return FALSE on failure.
	*/
	function SetModuleSort($ModuleSortContainer, $AssetName, $ModuleName, $PositionItem = False) {
		$ModuleSort = Gdn::Config('Modules');
		$AssetSort = GetValueR("$ModuleSortContainer.$AssetName", $ModuleSort, array());
		if (!is_array($AssetSort)) $AssetSort = array();
		if ($PositionItem !== False) {
			if (!is_numeric($PositionItem)) {
				$Position = substr($PositionItem, 0, 1);
				if (in_array($Position, array('-', '+'))) $PositionItem = substr($PositionItem, 1);
				$PositionItem = array_search($PositionItem, $AssetSort);
				if ($Position == '+') $PositionItem = (int)$PositionItem + 1;
			}
			$PositionItem = (int)$PositionItem;
			array_splice($AssetSort, $PositionItem, 0, array($ModuleName));
		} else array_push($AssetSort, $ModuleName);
		
		$AssetSort = array_unique($AssetSort);
			
		// Make sure that we put in config strings only.
		$VarExport = create_function('$Value', 'return var_export(strval($Value), True);');
		$ModuleList = implode(', ',  array_map($VarExport, $AssetSort));
		$PhpArrayCode = "\n\$Configuration['Modules']['$ModuleSortContainer']['$AssetName'] = array($ModuleList);";
		$ConfigFile = PATH_CONF . '/config.php';
		$Result = file_put_contents($ConfigFile, $PhpArrayCode, FILE_APPEND | LOCK_EX);
		return ($Result !== False);
	}
}

if (!function_exists('SendEmailMessage')) {
	/** 
	* Send email message.
	*/
	function SendEmailMessage($Recipient, $Subject, $Message, $Options = False) {
		$MimeType = ArrayValue('MimeType', $Options, 'text/plain');
		$SenderEmail = ArrayValue('SenderEmail', $Options, '');
		$SenderName = ArrayValue('SenderName', $Options, '');
		$Email = new Gdn_Email();
		$Result = $Email
			->From($SenderEmail, $SenderName)
			->MimeType($MimeType)
			->Subject($Subject)
			->To($Recipient)
			->Message($Message)
			->Send();
		return $Result;
	}
}

/** http://en.wikipedia.org/wiki/Bit_field
* A bit field is a common idiom used in computer programming to compactly store a value as a short series of bits.
*/

if (!function_exists('SetBit')) {
	function SetBit(&$BitMask, $Flag) {
		$BitMask |= $Flag;
		return $BitMask;
	}
}

if (!function_exists('ResetBit')) {
	function ResetBit(&$BitMask, $Flag) {
		$BitMask &= ~$Flag;
		return $BitMask;
	}
}

if (!function_exists('IsSetBit')) {
	function IsSetBit($BitMask, $Flag) {
		return ($BitMask & $Flag) != 0;
	}
}

if (!function_exists('ToggleBit')) {
	function ToggleBit(&$BitMask, $Flag) {
		$BitMask ^= $Flag;
		return $BitMask;
	}
}

if (!function_exists('LoadExtension')) {
	/**
	* Loads a PHP extension at runtime.
	* 
	* @param string $Ext. 
	* @return bool $Result.
	*/
	function LoadExtension($Ext, $bThrowException = False) {
		$Ext = strtolower($Ext);
		if (extension_loaded($Ext)) return True;
		$Prefix = (PHP_SHLIB_SUFFIX == 'dll') ? 'php_' : '';
		if (!function_exists('dl')) throw new Exception("dl() function is not supported. Trying to load '$Ext' extension.");
		$Loaded = dl($Prefix . $Ext . '.' . PHP_SHLIB_SUFFIX);
		if ($Loaded) return True;
		if ($bThrowException) throw new Exception(@$php_errormsg);
		return False;
	}
}

if (!function_exists('Pick')) {
	/**
	* Returns first non-empty argument.
	* Last value in list of arguments is default.
	* 
	* @return mixed $Default.
	*/
	function Pick($Default = False) {
		$Arguments = func_get_args();
		$Default = array_pop($Arguments);
		foreach ($Arguments as $A) {
			if (!empty($A)) return $A;
		}
		return $Default;
	}
}

if (!function_exists('GetBodyIdentifier')) {
	function GetBodyIdentifier(&$Controller) {
		$ControllerName = GetShortControllerName($Controller);
		$BodyIdentifier = $Controller->ApplicationFolder.'_'.$ControllerName.'_'.Gdn_Format::AlphaNumeric(strtolower($Controller->RequestMethod));
		return strtolower($BodyIdentifier);
	}
}

if (!function_exists('GetShortControllerName')) {
	function GetShortControllerName(&$C) {
		if (strtolower(substr($C->ControllerName, -10, 10)) == 'controller')
			return substr($C->ControllerName, 0, -10);
		return $C->ControllerName;
	}
}


if (!function_exists('Kick')) {
	function Kick($Password = '', $KickMessage = 'Unauthorized') {
		if ($Password == '') return;
		if (array_key_exists('HTTP_AUTHORIZATION', $_SERVER)) { // iis
			$Authorization = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
			$_SERVER['PHP_AUTH_USER'] = ArrayValue(0, $Authorization);
			$_SERVER['PHP_AUTH_PW'] = ArrayValue(1, $Authorization);
		}
		$P = ArrayValue('PHP_AUTH_PW', $_SERVER, ''); // apache
		if ($P != '' && $P == $Password) return;

		header('WWW-Authenticate: Basic realm = "Restricted Area"');
		header('Status: 401 Unauthorized');
		header('HTTP/1.1 401 Unauthorized');

		die($KickMessage);
	}
}



#﻿
