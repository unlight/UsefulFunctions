<?php

if(!function_exists('NewArray')) {
	function NewArray() {
		$Arguments = func_get_args();
		while(is_array($Arguments[0])) $Arguments = $Arguments[0];
		$Return = array();
		$Count = count($Arguments);
		if($Count % 2 == 1){
			array_splice($Arguments, -1);
			$Count--;
		}
		for($i = 0; $i < $Count; $i += 2) $Return[$Arguments[$i]] = $Arguments[$i+1];
		return $Return;
	}
}

if(!function_exists('LoadExtension')) {
	function LoadExtension($Ext) {
		$Ext = strtolower($Ext);
		if (extension_loaded($Ext)) return True;
		$Prefix = (PHP_SHLIB_SUFFIX == 'dll') ? 'php_' : '';
		if (!function_exists('dl')) throw new Exception("dl() function is not supported. Trying to load `$Ext` extension.");
		$Loaded = dl($Prefix . $Ext . '.' . PHP_SHLIB_SUFFIX);
		return ($Loaded > 0);
	}
}

if(!function_exists('Pick')) {
	function Pick($Default = False) {
		$Arguments = func_get_args();
		$Default = array_pop($Arguments);
		foreach ($Arguments as $A) {
			if(!empty($A)) return $A;
		}
		return $Default;
	}
}

// TODO: Try to extend method for controllers
if(!function_exists('GetBodyIdentifier')){
	function GetBodyIdentifier(&$Controller){
		$ControllerName = GetShortControllerName($Controller);
		$BodyIdentifier = $Controller->ApplicationFolder.'_'.$ControllerName.'_'.Gdn_Format::AlphaNumeric(strtolower($Controller->RequestMethod));
		return strtolower($BodyIdentifier);
	}
}

if(!function_exists('GetShortControllerName')){
	function GetShortControllerName(&$C){
		if(strtolower(substr($C->ControllerName, -10, 10)) == 'controller')
			return substr($C->ControllerName, 0, -10);
		return $C->ControllerName;
	}
}


if(!function_exists('Kick')){
	function Kick($Password = '', $KickMessage = 'Unauthorized'){
		if($Password == '') return;
		if(array_key_exists('HTTP_AUTHORIZATION', $_SERVER)){ // iis
			$Authorization = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
			$_SERVER['PHP_AUTH_USER'] = ArrayValue(0, $Authorization);
			$_SERVER['PHP_AUTH_PW'] = ArrayValue(1, $Authorization);
		}
		$P = ArrayValue('PHP_AUTH_PW', $_SERVER, ''); // apache
		if($P != '' && $P == $Password) return;

		header('WWW-Authenticate: Basic realm = "Restricted Area"');
		header('Status: 401 Unauthorized');
		header('HTTP/1.1 401 Unauthorized');

		die($KickMessage);
	}
}



#﻿

