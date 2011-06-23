<?php

if (!function_exists('GetValue')) {
	/**
	* Return the value from an associative array or an object.
	* Taked from Garden core (for use this functions in other projects).
	* 
	* @note Garden.Core function.
	* @param string $Key The key or property name of the value.
	* @param mixed $Collection The array or object to search.
	* @param mixed $Default The value to return if the key does not exist.
	* @param bool $Remove Whether or not to remove the item from the collection.
	* @return mixed The value from the array or object.
	*/
	function GetValue($Key, &$Collection, $Default = FALSE, $Remove = FALSE) {
		$Result = $Default;
		if (is_array($Collection) && array_key_exists($Key, $Collection)) {
			$Result = $Collection[$Key];
			if ($Remove) unset($Collection[$Key]);
		} elseif (is_object($Collection) && property_exists($Collection, $Key)) {
			$Result = $Collection->$Key;
			if ($Remove) unset($Collection->$Key);
		}
		return $Result;
	}
}

if (!function_exists('RotateArray')) {
	/**
	* This function rotates array values by specified amount of steps. 
	* Positive number will make array rotate forward, negative - backward.
	* 
	* @credit http://webcodingeasy.com/PHP/PHP-array-rotation
	* @param int $Steps.
	* @param array $Array.
	* @return array $Array.
	*/
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

if (!function_exists('IsEmpty')) {
	/**
	* Undocumented 
	* 
	* @credit loota-php_util
	* @param mixed $Array.
	* @return bool $Result.
	*/
	function IsEmpty($Array) {
		if (!is_array($Array)) $Array = (array)$Array;
		return (count(array_filter($Array)) == 0);
	}
}

if (!function_exists('PromoteKey')) {
	/**
	* Promote key for associative array/dataset.
	* 
	* @param array $Collection (or any iterable object).
	* @return array $Result.
	*/
	function PromoteKey($Collection, $PromotedKey) {
		$Result = array();
		foreach ($Collection as $Data) {
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
		$Arrays = func_get_args();
		$Result = Flatten($Arrays);
		$Result = array_values(array_unique($Result));
		return $Result;		
	}
}

if (!function_exists('ObjectValue')) {
	function ObjectValue($Key, $Object, $Default = False) {
		$Result = $Default;
		if (is_object($Object) && property_exists($Object, $Key)) $Result = $Object->$Key;
		return $Result;
	}
}

if (!function_exists('ArrayValue')) {
	function ArrayValue($Key, $Array, $Default = False) {
		$Result = $Default;
		if (is_array($Array) && array_key_exists($Key, $Array)) $Result = $Array[$Key];
		return $Result;
	}
}


if (!function_exists('array_flat')) {
	function array_flat($arr) {
		$result = array();
		foreach ($arr as $value) {
			if (!is_array($value)) $result[]   = $value;
			else array_splice($result, count($result), 0, array_flat($value));
		}
		return  $result;
	}
}

if (!function_exists('Flatten')) {
	/**
	* Undocumented 
	* 
	* @credit http://phunction.sf.net
	* @param array 
	* @return array $Result.
	*/
	function Flatten($Array) {
		$Result = array();
		foreach (new RecursiveIteratorIterator(new RecursiveArrayIterator($Array)) as $Value)
			$Result[] = $Value;
		return $Result;
	}
}

if (!function_exists('RandomValue')) {
	/**
	* Get random value from array $Collection.
	* 
	* @param array $Collection.
	* @param mixed $Default, this value returned if $Collection is not array.
	* @param bool $Remove, if true remove this random value from $Collection.
	* @return mixed $Result.
	*/
	function RandomValue(&$Collection, $Default = False, $Remove = False) {
		$Result = $Default;
		if (is_array($Collection)) {
			$Index = array_rand($Collection);
			$Result = $Collection[$Index];
			if ($Remove) unset($Collection[$Index]);
		}
		return $Result;
	}
}

if (!function_exists('SetNullValues')) {
	function SetNullValues(&$Collection) {
		return ReplaceEmpty($Collection, Null);
	}
}

if (!function_exists('ReplaceEmpty')) {
	function ReplaceEmpty(&$Collection, $R) {
		if (is_object($Collection)) {
			foreach (get_object_vars($Collection) as $Property => $Value) {
				if(StringIsNullOrEmpty($Value)) $Collection->$Property = $R;
			}
		}
		else if (is_array($Collection)) {
			foreach ($Collection as &$Value) {
				if(StringIsNullOrEmpty($Value)) $Value = $R;
			}
		}
		return $Collection;
	}
}

if (!function_exists('CamelizeResult')) {
	function CamelizeResult($Data, $bRemoveUnderscoreKeys = True) {
		$Data = Gdn_Format::ObjectAsArray($Data);
		$Keys = array_keys($Data);
		$CamelizedKeys = array_map('Camelize', $Keys);
		$Keys = array_combine($Keys, $CamelizedKeys);
		foreach ($Keys as $Key => $CamelizedKey) {
			$Data[$CamelizedKey] = $Data[$Key];
			if ($bRemoveUnderscoreKeys) unset($Data[$Key]);
		}
		$Data = Gdn_Format::ArrayAsObject($Data);
		return $Data;
	}
}



