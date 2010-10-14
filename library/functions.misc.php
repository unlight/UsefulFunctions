<?php


/**
* Count something in many-to-many relationship tables
* SQL statement:
update News n set n.TagCount = (
	select count(*) from NewsTags nt
		where nt.NewsID = n.NewsID
)
*/ 

if(!function_exists('CountManyToManyData')) {
	// TODO: FIX ME: No escape values here
	function CountManyToManyData($OuterTableName, $OuterField, $InnerTableName, $Where) {
		$SQL = Gdn::SQL();
		$Sql = $SQL
			->Select('*', 'count', 'RowCount')
			->From($InnerTableName)
			->Where($Where, Null, False, False)
			->GetSelect();
		$SQL->Reset();
		$Result = $SQL
			->Update($OuterTableName)
			->Set($OuterField, "($Sql)", False)
			->Put();
		return $Result;
	}
}

/**
* Saves data to tables wguch are in many-to-many relationship
*/
if(!function_exists('SaveManyToManyData')) {
	function SaveManyToManyData($TableName, $Where, $OtherFieldName, $Values) {
		$SQL = Gdn::SQL();
		$SQL->Where($Where)->Delete($TableName);
		$DataInsert = array();
		foreach ($Values as $Value) $DataInsert[] = array_merge($Where, array($OtherFieldName => $Value));
		return $SQL->Insert($TableName, $DataInsert);
	}
}


/**
* Function saves new tags. Return existing.
*/

if(!function_exists('SaveTags')) {
	function SaveTags($TagString) {
		if(!is_string($TagString)) $TagString = GetValue('Tags', $TagString);
		$TagString = mb_strtolower($TagString, 'utf-8');
		$TagString = preg_replace('/[^ \-0-9a-zа-я]/iu', ' ', $TagString);
		$TagsNames = SplitString($TagString, ' ', array('array_filter', 'array_unique'));
		$ExistingTagData = Gdn::SQL()
			->Select('TagID, Name')
			->From('Tag')
			->WhereIn('Name', $TagsNames)
			->Get();
		$ConsolidatedTags = ConsolidateArrayValuesByKey($ExistingTagData->ResultArray(), 'Name', 'TagID');
		foreach($TagsNames as $TagName) {
			$TagID = GetValue($TagName, $ConsolidatedTags);
			if($TagID === False) {
				$TagID = Gdn::SQL()
					->History(False, True)
					->Insert('Tag', array('Name' => $TagName));
			}
			$ConsolidatedTags[$TagName] = $TagID;
		}
		return $ConsolidatedTags;
	}
}

/**
* Key/value storage.
*/
if(!function_exists('K')) {
	function K($Name, $Value = Null) {
		static $SQL, $Cache, $DataTableCreated;
		
		if (is_null($DataTableCreated)) {
			$DataTableCreated = C('Plugins.PluginUtils.DataTableCreated');
			if ($DataTableCreated === False) {
				Gdn::Structure()
					->Table('Data')
					->Column('Name', 'varchar(200)', False, 'unique')
					->Column('Value', 'text')
					->Set(False, False);
				$DataTableCreated = True;
				SaveToConfig('Plugins.PluginUtils.DataTableCreated', $DataTableCreated);
			}
		}
		
		// Select
		if ($SQL === Null) $SQL = Gdn::SQL();
		if (is_string($Name) && $Value === Null) {
			$Modificator = $Name{0};
			if (in_array($Modificator, array('#', '%', '@'))) $Name = substr($Name, 1);
			
			if (!isset($Cache[$Name])) {
				
				switch($Modificator){
					case '#': $SQL->Where('Name', $Name); break;
					case '%': 
					case '@':
					default: $SQL->Like('Name', $Name, 'right');
				}
				
				$Result = Null;
				$ResultSet = $SQL->Select('Name, Value')->From('Data')->Get();
				if ($ResultSet->NumRows() == 0) return False;
				elseif ($Modificator == '%') {
					foreach($ResultSet as $Data) {
						$S = "['".str_replace('.', "']['", $Data->Name)."']";
						eval("\$Value =& \$Result{$S};"); // eval is evil
						if (is_null($Value) || $Value === '' || is_array($Value)) {
							$Value = $Cache[$Data->Name] = Gdn_Format::Unserialize($Data->Value);
						} else {
							// Lost value. What should we do? Delete? Throw Exception?
						}
					}
				} elseif ($Modificator == '@' || $ResultSet->NumRows() > 1) {
					foreach($ResultSet as $Data) {
						$K = array_pop(explode('.', $Data->Name));
						$Result[$K] = $Cache[$Data->Name] = Gdn_Format::Unserialize($Data->Value);
					}
					// reduce result array
					//if ($Modificator == '@') while(count($Result) == 1) $Result = array_shift($Result);
				} else {
					$Result = $ResultSet->FirstRow()->Value;
					$Result = Gdn_Format::Unserialize($Result);
				}
				$Cache[$Name] = $Result;
			}
			return $Cache[$Name];
		}

		// Delete
		if($Value === False) {
			if (is_array($Name)) return $SQL->WhereIn('Name', $Name)->Delete('Data');
			if (is_string($Name)) return $SQL->Like('Name', $Name, 'right')->Delete('Data');
			trigger_error(sprintf('Incorrect type of Key (%s)', gettype($Name)));
		}

		// Insert/Update
		if (!is_array($Name)) $Name = array($Name => $Value);	
		foreach ($Name as $Key => $Value) {
			$Value = Gdn_Format::Serialize($Value);
			$SQL->Replace('Data', array('Value' => $Value), array('Name' => $Key));
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



#﻿

