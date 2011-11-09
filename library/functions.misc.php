<?php
if (!function_exists('AddToConfig')) {
	/**
	* Undocumented 
	* 
	* @param mixed 
	* @return bool $Result.
	*/
	function AddToConfig($Name, $Value = False) {
		$VarExport = create_function('$Value', 'return var_export(strval($Value), True);');
		if (!is_array($Name)) $Name = array($Name => $Value);
		$CodeLines = array('');
		foreach ($Name as $Name => $Value) {
			$Keys = array_map($VarExport, explode('.', $Name));
			$Key = implode('][', $Keys);
			$CodeLines[] = "\$Configuration[$Key] = " . var_export($Value, True) . ';';
		}
		$CodeLines = implode("\n", $CodeLines);
		$Result = file_put_contents(PATH_CONF . '/config.php', $CodeLines, FILE_APPEND | LOCK_EX);
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

if (!function_exists('EmailRecipient')) {
	/**
	* Extract email and name from array, object or string.
	* 
	* @param mixed $Mixed.
	* @return mixed $Result.
	*/
	function EmailRecipient($Mixed, $CoerceArray = False) {
		$Result = array();
		if (is_string($Mixed)) {
			if (preg_match('/[;,\n]/', $Mixed) > 0) {
				$DataArray = array_map('trim', preg_split('/[;,\n]/s', $Mixed));
				foreach ($DataArray as $Mixed) $Result[] = EmailRecipient($Mixed);
				$CoerceArray = False;
			} elseif (preg_match('/(.*)\<(.+)\>/U', $Mixed, $Match) > 0) {
				$Result = array('Email' => $Match[2], 'Name' => trim($Match[1]));
			} else {
				$Result = array('Email' => $Mixed, 'Name' => '');
			}
			if ($CoerceArray) $Result = array($Result);
			return $Result;
		} elseif (is_array($Mixed)) {
			if (array_key_exists('Email', $Mixed)) {
				$Email = $Mixed['Email'];
				$Name = (array_key_exists('Name', $Mixed)) ? $Mixed['Name'] : '';
				$Result = array('Email' => $Email, 'Name' => $Name);
				if ($CoerceArray) $Result = array($Result);
				return $Result;
			} elseif (array_key_exists(0, $Mixed)) {
				foreach ($Mixed as $S) $Result[] = EmailRecipient($S);
				return $Result;
			}
		} elseif (is_object($Mixed)) {
			if ($Mixed instanceof Gdn_DataSet) return EmailRecipient($Mixed->Result());
			$Result['Email'] = $Mixed->Email;
			$Result['Name'] = (property_exists($Mixed, 'Name')) ? $Mixed->Name : '';
			if ($CoerceArray) $Result = array($Result);
			return $Result;
		}
		trigger_error('Invalid parameter.');
	}
}

if (!function_exists('SendEmailMessage')) {
	/** 
	* Send email message.
	*/
	function SendEmailMessage($Options) {
		
		static $Defaults = array (
			'To' => '',
			'Cc' => '',
			'Bcc' => '',
			'SingleTo' => True,
			'Subject' => '',
			'Charset' => 'utf-8',
			'ContentType' => 'text/plain',
			'Encoding' => '8bit',
			'Message' => '',
			'Attachment' => '',
			'Attachments' => array(),
			'FromEmail' => '',
			'FromName' => '',
			'Priority' => 3,
			'Sender' => '',
			'ReplyTo' => '',
			'ErrorsTo' => '',
			'ReturnPath' => '',
			'From' => '',
			'Name' => array(),
			'ConfirmReadingTo'	=> '',
			'Organisation' => '',
			'Date' => '',
			'AbuseContact' => '',
			'ThrowExceptions' => True
		);
		
		$Options = array_merge($Defaults, (array)$Options);
		extract($Options, EXTR_SKIP);
		
		$PhpMailer = new PhpMailer($ThrowExceptions);
		
		$PhpMailer->Priority = $Priority;
		$PhpMailer->ContentType = $ContentType;
		$PhpMailer->CharSet = $Charset;
		$PhpMailer->SingleTo = $SingleTo;
		
		if (!$From) {
			if (!$FromEmail) $FromEmail = C('Garden.Email.SupportAddress', '');
			if (!$FromEmail) $FromEmail = 'noreply@'.Gdn::Request()->Host();
			if (!$FromName) $FromName = C('Garden.Email.SupportName', C('Garden.Title', ''));
			$From = array('Name' => $FromName, 'Email' => $FromEmail);
		}
		$From = EmailRecipient($From);
		$PhpMailer->From = $From['Email'];
		$PhpMailer->FromName = $From['Name'];
		
		if ($Sender) $PhpMailer->Sender = $Sender;
		if ($ConfirmReadingTo) $PhpMailer->ConfirmReadingTo = $ConfirmReadingTo;
		
		$PhpMailer->Subject = $Subject;
		$PhpMailer->Body = $Message;
		
		if ($Attachment) foreach ((array)$Attachment as $File) $PhpMailer->AddAttachment($File);

		foreach (EmailRecipient($To, True) as $Recipient) $PhpMailer->AddAddress($Recipient['Email'], $Recipient['Name']);
		if ($Cc) foreach (EmailRecipient($Cc, True) as $Recipient) $PhpMailer->AddCC($Recipient['Email'], $Recipient['Name']);
		if ($Bcc) foreach (EmailRecipient($Bcc, True) as $Recipient) $PhpMailer->AddBCC($Recipient['Email'], $Recipient['Name']);
		if ($ReplyTo) foreach (EmailRecipient($ReplyTo, True) as $Recipient) $PhpMailer->AddReplyTo($Recipient['Email'], $Recipient['Name']);
		
		$PhpMailer->AltBody = '';
		$PhpMailer->Send();
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
