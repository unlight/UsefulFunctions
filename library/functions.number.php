<?php

if (!function_exists('Clamp')) {
	/**
	* Returns the first number clamped to the interval from A to B.
	* 
	* @param mixed $A.
	* @param mixed $B
	* @return mixed $V.
	*/
	function Clamp($V, $A, $B) {
		if ($V > $B) return $B;
		else if ($V < $A) return $A;
		else return $V;
	}
}

if (!function_exists('ArraySum')) {
	function ArraySum($Array) {
		$N = 0;
		$Array = array_values($Array);
		for ($Count = Count($Array), $i = 0; $i < $Count; $i++) $N = Summation($N, $Array[$i]);
		return $N;
	}
}

if (!function_exists('Summation')) {
	/**
	* Calculate the sum of integer values (values can be any length)
	*/ 
	function Summation($A) {
		$Arguments = func_get_args();
		if (is_array($A)) $Arguments = Flatten($Arguments);
		$MaxLengthArray = array_map('strlen', $Arguments);
		$MaxLength = max($MaxLengthArray);
		foreach ($Arguments as $Index => $Value) {
			settype($Value, 'string');
			$Arguments[$Index] = str_pad($Value, $MaxLength, '0', STR_PAD_LEFT);
		}
		//$Result = array_fill(0, $MaxLength, 0);
		for ($i = $MaxLength - 1; $i >= 0; $i--) {
			if(!isset($Result[$i])) $Result[$i] = 0;
			foreach ($Arguments as $Value) $Result[$i] += (int)($Value{$i});
			$Sum = strval($Result[$i]);
			$Length = strlen($Sum);
			for ($n = $Length - 1; $n >= 0; $n--) {
				$k = $i - ($Length - $n - 1);
				$Result[$k] = $Sum[$n];
			}
		}
		ksort($Result);
		$Result = implode('', $Result);
		return $Result;
	}
}