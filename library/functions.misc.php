<?php

// key/value storage table
if(!function_exists('K')) {
	function K($Name, $Value = Null) {
		static $SQL, $Cache, $StorageTableCreated;
		
		if (is_null($StorageTableCreated)) {
			$StorageTableCreated = Gdn::Config('Plugins.PluginUtils.StorageTableCreated');
			if ($StorageTableCreated === False) {
				Gdn::Structure()
					->Table('Storage')
					->Column('Name', 'varchar(200)', False, 'unique')
					->Column('Value', 'text')
					->Set(False, False);
				$StorageTableCreated = True;
				SaveToConfig('Plugins.PluginUtils.StorageTableCreated', $StorageTableCreated);
			}
		}
		
		if ($SQL === Null) $SQL = Gdn::SQL();
		if (is_string($Name) && $Value === Null) {
			if (!isset($Cache[$Name])) {
				$Result = Null;
				$ResultSet = $SQL
					->Select('Name, Value')
					->Select('Name, ".", 1', 'substring_index', 'Key1')
					//->Select('substring_index(Name, ".", 2), ".", -1', 'substring_index', 'Key2')
					//->Select('substring_index(Name, ".", 3), ".", -1', 'substring_index', 'Key3')
					//->Select('substring_index(Name, ".", 4), ".", -1', 'substring_index', 'Key4')
					->From('Storage')
					->Like('Name', $Name, 'right')
					->Get();
				if ($ResultSet->NumRows() == 0) return False;
				if ($ResultSet->NumRows() == 1) {
					$Result = $ResultSet->FirstRow()->Value;
					$Result = Gdn_Format::Unserialize($Result);
				} else {
					foreach($ResultSet as $Data) {
						$K = array_pop(explode('.', $Data->Name));
						$Result[$K] = $Cache[$Data->Name] = Gdn_Format::Unserialize($Data->Value);
					}
					// reduce result array
					//while(count($Result) == 1) $Result = array_shift($Result);
				}
				$Cache[$Name] = $Result;
			}
			return $Cache[$Name];
		}

		// Delete
		if($Value === False) {
			if (!is_array($Name)) $Name = array($Name);
			return $SQL->WhereIn('Name', $Name)->Delete('Storage');
		}

		// Insert/Update
		if (!is_array($Name)) $Name = array($Name => $Value);	
		foreach ($Name as $Key => $Value) {
			$Value = Gdn_Format::Serialize($Value);
			$SQL->Replace('Storage', array('Value' => $Value), array('Name' => $Key));
		}
	}
}

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





