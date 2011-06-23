<?php

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

/**
* Loads a PHP extension at runtime.
* 
* @param string $Ext. 
* @return bool $Result.
*/
if (!function_exists('LoadExtension')) {
	function LoadExtension($Ext, $bThrowException = False) {
		$Ext = strtolower($Ext);
		if (extension_loaded($Ext)) return True;
		$Prefix = (PHP_SHLIB_SUFFIX == 'dll') ? 'php_' : '';
		if (!function_exists('dl')) throw new Exception("dl() function is not supported. Trying to load '$Ext' extension.");
		$Loaded = dl($Prefix . $Ext . '.' . PHP_SHLIB_SUFFIX);
		if ($Loaded) return True
		if ($bThrowException) throw new Exception(@$php_errormsg);
		return False;
	}
}

/**
* Returns first non-empty argument.
*/ 
if (!function_exists('Pick')) {
	function Pick($Default = False) {
		$Arguments = func_get_args();
		$Default = array_pop($Arguments);
		foreach ($Arguments as $A) {
			if (!empty($A)) return $A;
		}
		return $Default;
	}
}

// TODO: Try to extend method for controllers
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
