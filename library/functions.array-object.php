<?php

/*if(!function_exists('ArrayMap')){
	function ArrayMap($Array, $Filter = Null, $Callbacks = Null) {
		if(is_array($Filter)){
			foreach($Filter as $Function) $Array = array_filter($Array, $Function);
		}
		if(is_array($Callbacks)){
			foreach($Callbacks as $Function) $Array = array_map($Function, $Array);
		}
		return $Array;
	}
}*/


// http://code-snippets.co.cc/PHP/PHP-array-rotation
function RotateArray($Steps, $Array) {
	if ($Steps >= 0) {
		for ($i = 0; $i < $Steps; $i++) {
			$Element = array_shift($Array);
			array_push($Array, $Element);
		}
	} else {
		for($i = 0; $i > $Steps; $i--) {
			$Element = array_pop($Array);
			array_unshift($Array, $Element);
		}
	}
	return $Array;
}



// loota-php_util
if(!function_exists('IsEmpty')){
	function IsEmpty($Array) {
		if (!is_array($Array)) $Array = (array)$Array;
		return (count(array_filter($Array)) == 0);
	}
}


/**
* Promote key for associative array/dataset
*/

if(!function_exists('PromoteKey')) {
	function PromoteKey($Collection, $PromotedKey) {
		$Result = array();
		foreach($Collection as $Data) {
			$K = GetValue($PromotedKey, $Data);
			$Result[$K] = $Data;
		}
		return $Result;
	}
}

if (!function_exists('BunchCollection')) {
	function BunchCollection($Collection, $Key) {
		$Result = array();
		foreach ($DataSet as $Data) {
			$BunchKeyValue = GetValue($Key, $Data);
			$Result[$BunchKeyValue][] = $Data;
		}
		return $Result;
	}
}

if (!function_exists('CombineArrays')) {
	function CombineArrays() {
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

if (!function_exists('SetNullValues')) {
	function SetNullValues(&$Collection) {
		ReplaceEmpty($Collection, Null);
	}
}

if(!function_exists('ReplaceEmpty')) {
	function ReplaceEmpty(&$Collection, $R) {
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



if (!function_exists('GroupArrayByKey')) {
	function GroupArrayByKey($Array, $Key, $ValueKey = '', $AssociativeArrayValueKey = '', $DefaultValue = False) {
		if (defined('DEBUG')) trigger_error('GroupArrayByKey() is deprecated. Use BunchCollection() instead.', E_USER_DEPRECATED);
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

if(!function_exists('ConsolidateDataSetValues')) { // deprecated
	function ConsolidateDataSetValues($Array, $Options, $ValueKey = Null) {
		if (defined('DEBUG')) trigger_error('ConsolidateDataSetValues() is deprecated. Use PromoteKey() / ConsolidateArrayValuesByKey() instead.', E_USER_DEPRECATED);
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