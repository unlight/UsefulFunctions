<?php

// loota-php_util
if(!function_exists('IsEmpty')){
	function IsEmpty($Array) {
		if (!is_array($Array)) $Array = (array)$Array;
		return (count(array_filter($Array)) > 0);
	}
}

if(!function_exists('TableDataValues')){
	function TableDataValues($Data, $TableName){
		static $Cache;
		if(!isset($Cache[$TableName])){
			$SQL = Gdn::SQL();
			$Cache[$TableName] = $SQL->FetchTableSchema($TableName);
		}
		$Columns = $Cache[$TableName];
		$Result = array();
		$Data = Gdn_Format::ObjectAsArray($Data);
		foreach ($Data as $Name => $Value) {
			if(is_object($Value) || is_array($Value)) continue;
			
			// TODO: WE CAN PUT THIS INTO CACHE
			$ColumnKeys = array_keys($Columns);
			$ColumnKeys = array_combine(array_map('strtolower', $ColumnKeys), $ColumnKeys);
			$NameLowered = strtolower($Name);

			if(array_key_exists($NameLowered, $ColumnKeys)) {
				$Name = $ColumnKeys[$NameLowered];
				$Field = $Columns[$Name];
				$Float = array('float', 'double');
				$Int = array('int', 'tinyint', 'smallint', 'mediumint', 'bigint');
				if(in_array($Field->Type, $Int)) $Value = intval($Value);
				else if(in_array($Field->Type, $Float)) $Value = floatval($Value);
				$Result[$Name] = strval($Value);
			}
		}
		return $Result;
	}
}

if(!function_exists('ConsolidateDataSetValues')) {
	function ConsolidateDataSetValues($Array, $Options, $ValueKey = Null) {
		$Result = array();
		if (is_string($Options) && substr($Options, 0, 1) == '{') $Options = json_decode($Options);
		if (is_scalar($Options)) $Options = array('Key' => $Options);
		$Key = GetValue('Key', $Options);
		$ValueKey = GetValue('ValueKey', $Options, $ValueKey);

		foreach ($Array as $Index => $Data) {
			$N = GetValue($Key, $Data);
			if($ValueKey == 'full') $Result[$N][] = $Data;
			elseif($ValueKey == 'unique') $Result[$N] = $Data;
			elseif($ValueKey != '') $Result[$N] = GetValue($ValueKey, $Data);
			else $Result[] = $N;
		}
		return $Result;
	}
}


if(!function_exists('GroupArrayByKey')){
	function GroupArrayByKey($Array, $Key, $ValueKey = '', $AssociativeArrayValueKey = '', $DefaultValue = False) {
		$Return = array();

		foreach($Array as $Index => $AssociativeArray){
			if(!array_key_exists($Key, $AssociativeArray)) continue;
			if($ValueKey === '') $Return[] = $AssociativeArray[$Key];
			elseif($ValueKey === 0){
				$K = GetValue($Key, $AssociativeArray);
				// Full Array
				$Return[$K][] = $AssociativeArray;
			}elseif($ValueKey === True){ // unique
				$Return[$AssociativeArray[$Key]] = $AssociativeArray;
			}
			elseif(array_key_exists($ValueKey, $AssociativeArray))
				$Return[$AssociativeArray[$Key]][] = $AssociativeArray[$ValueKey];
			else $Return[$AssociativeArray[$Key]] = $DefaultValue;
		}
		return $Return;
	}
}

if(!function_exists('CombineArrays')) {
	function CombineArrays(){
		$Result = array();
		$Arrays = func_get_args();
		foreach($Arrays as $Array) foreach($Array as $Value) $Result[] = $Value;
		$Result = array_unique($Result);
		$Result = array_values($Result);
		return $Result;
	}
}

// temporary compatibility function
if(!function_exists('ObjectValue')){
	function ObjectValue($Key, $Object, $Default = False) {
		return GetValue($Key, $Object, $Default);
	}
}

if(!function_exists('array_flat')) {
	function array_flat($arr){
		$result = array();
		foreach($arr as $value){
			if(!is_array($value))
				$result[]   = $value;
			else
				array_splice($result, count($result), 0, array_flat($value));
		}
		return  $result;
	}
}

// http://phunction.sf.net/
if(!function_exists('Flatten')) {
	function Flatten($Array){
		$Result = array();
		foreach (new RecursiveIteratorIterator(new RecursiveArrayIterator($Array)) as $Value)
			$Result[] = $Value;
		return $Result;
	}
}

if(!function_exists('RandomValue')) {
	function RandomValue(&$Collection, $Default = False, $Remove = False) {
		if (is_array($Collection)) {
			$Index = array_rand($Collection);
			$Default = $Collection[$Index];
			if($Remove) unset($Collection[$Index]);
		}
		return $Default;
	}
}

if(!function_exists('ReplaceEmpty')) {
	function ReplaceEmpty(&$Collection, $R = '-') {
		if(is_object($Collection)){
			foreach(get_object_vars($Collection) as $Property => $Value){
				if(StringIsNullOrEmpty($Value)) $Collection->$Property = $R;
			}
		}
		else if(is_array($Collection)){
			foreach($Collection as &$Value){
				if(StringIsNullOrEmpty($Value)) $Value = $R;
			}
		}
	}
}

if(!function_exists('CamelizeResult')){
	function CamelizeResult($Data, $bRemoveUnderscoreKeys = True){
		$Data = Gdn_Format::ObjectAsArray($Data);
		$Keys = array_keys($Data);
		$CamelizedKeys = array_map('Camelize', $Keys);
		$Keys = array_combine($Keys, $CamelizedKeys);
		foreach($Keys as $Key => $CamelizedKey){
			$Data[$CamelizedKey] = $Data[$Key];
			if($bRemoveUnderscoreKeys) unset($Data[$Key]);
		}
		$Data = Gdn_Format::ArrayAsObject($Data);
		return $Data;
	}
}