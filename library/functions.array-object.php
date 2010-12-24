<?php

// http://code-snippets.co.cc/PHP/PHP-array-rotation
if (!function_exists('RotateArray')) {
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
}


// loota-php_util
if (!function_exists('IsEmpty')) {
	function IsEmpty($Array) {
		if (!is_array($Array)) $Array = (array)$Array;
		return (count(array_filter($Array)) == 0);
	}
}


/**
* Promote key for associative array/dataset
*/
if (!function_exists('PromoteKey')) {
	function PromoteKey($Collection, $PromotedKey) {
		$Result = array();
		foreach($Collection as $Data) {
			$K = GetValue($PromotedKey, $Data);
			$Result[$K] = $Data;
		}
		return $Result;
	}
}

if (!function_exists('GroupByKey')) {
	function GroupByKey($Collection, $Key, $Options = False) {
		// $PromoteKey = GetValue('PromoteKey', $Options);
		// TODO: Add option $Result[$KeyValue][$K] = $Data;
		$Result = array();
		foreach ($Collection as $Data) {
			$KeyValue = GetValue($Key, $Data);
			$Result[$KeyValue][] = $Data;
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

if (!function_exists('ObjectValue')) {
	function ObjectValue($Key, $Object, $Default = False) {
		$Result = $Default;
		if (is_object($Object) && property_exists($Object, $Key) 
			$Result = $Object->$Key;
		return $Result;
	}
}

if (!function_exists('ArrayValue')) {
	function ArrayValue($Key, $Array, $Default = False) {
		$Result = $Default;
		if (is_array($Array) && array_key_exists($Key, $Array))
			$Result = $Array[$Key];
		return $Result;
	}
}


if (!function_exists('array_flat')) {
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
if (!function_exists('Flatten')) {
	function Flatten($Array){
		$Result = array();
		foreach (new RecursiveIteratorIterator(new RecursiveArrayIterator($Array)) as $Value)
			$Result[] = $Value;
		return $Result;
	}
}

if (!function_exists('RandomValue')) {
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
		return ReplaceEmpty($Collection, Null);
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
		return $Collection;
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
			if ($bRemoveUnderscoreKeys) unset($Data[$Key]);
		}
		$Data = Gdn_Format::ArrayAsObject($Data);
		return $Data;
	}
}



