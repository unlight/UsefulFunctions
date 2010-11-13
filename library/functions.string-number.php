<?php

function mb_str_pad($input, $pad_length, $pad_string= ' ', $pad_type = STR_PAD_RIGHT) {
    $diff = strlen($input) - mb_strlen($input, 'UTF-8');
    return str_pad($input, $pad_length+$diff, $pad_string, $pad_type);
}

function FormatTextAsRow($Array, $MaxLengthArray) {
	$Result = '';
	$Array = array_values($Array);
	$LastNum = count($Array) - 1;
	foreach($Array as $N => $Value) {
		$MaxLengthOfRow = $MaxLengthArray[$N];
		$LocalLength = mb_strlen($Value, 'utf-8');
		$NumOfSpace = $MaxLengthOfRow - $LocalLength + 4;
		$Value = mb_str_pad($Value, $MaxLengthOfRow + 4, ' ');
		$NumOfTabs = floor($NumOfSpace/4);
		if ($NumOfTabs >= 1) {
			$Value = mb_substr($Value, 0, -($NumOfTabs * 4), 'utf-8');
			$Value .= str_repeat("\t", $NumOfTabs);
		}
		if ($LastNum == $N) $Value = trim($Value);
		$Result .= $Value;
	}
	return $Result;
	
}

function FormatTextAsTable($Headers, $DataArray, $Options = False) { // very slooooow
	$bHeaderLength = '';
	$Length = count($Headers);
	$MaxLengthArray = array_fill(0, $Length, 0);
	array_unshift($DataArray, $Headers);
	// 1. Detect max length
	foreach($DataArray as $Data) {
		$Data = array_values($Data);
		for ($i = 0; $i < $Length; $i++) {
			$LocalLength = mb_strlen($Data[$i], 'utf-8');
			if ($LocalLength > $MaxLengthArray[$i]) $MaxLengthArray[$i] = $LocalLength;
		}
	}
	$Result = '';
	// 2. Draw headers / data lines
	foreach($DataArray as $Data) {
		$Result .= FormatTextAsRow($Data, $MaxLengthArray) . "\n";
	}
	return $Result;
}

function NCrypt($String, $Password, $Decrypt) {
	if (!defined('ALPHABET')) {
		define('RALPHABET', pack('H*', '4142434445464748494a4b4c4d4e4f505152535455565758595a6162636465666768696a6b6c6d6e6f707172737475767778797a31323334353637383930205c212c2e3a3b3f7e402324255e262a28295f2b2d3d5d5b7d7b2f3e3c2227607c4142434445464748494a4b4c4d4e4f505152535455565758595a6162636465666768696a6b6c6d6e6f707172737475767778797a31323334353637383930205c212c2e3a3b3f7e402324255e262a28295f2b2d3d5d5b7d7b2f3e3c2227607c'));
		define('ALPHABET', strrev(RALPHABET));
	}
	//if (!$Password) $Password = Gdn::Config()
	$String = (!$Decrypt) ? array_pop(unpack('H*', $String)) : pack('H*', $String);
	$RevAlphabetLength = strlen(RALPHABET);
	$PasswordLength = strlen($Password);
	for ($i = 0; $i < $PasswordLength; $i++) {
		$CurrentPasswordLtr = substr($Password, $i, 1);
		$PosAlphaArray[] = substr(strstr(ALPHABET, $CurrentPasswordLtr), 0, $RevAlphabetLength);
	}
	$n = 0;
	$Result = '';
	for ($Length = strlen($String), $i = 0; $i < $Length; $i++) {
		$Pos = strpos(RALPHABET, substr($String, $i, 1));
   		$Result .= substr($PosAlphaArray[$n], $Pos, 1);
   		if (++$n == $PasswordLength) $n = 0;
  	}
	$Result = (!$Decrypt) ? array_pop(unpack('H*', $Result)) : pack('H*', $Result);
	return $Result;
}

function Encrypt($String, $Password) {
	return NCrypt($String, $Password, False);
}

function Decrypt($String, $Password) {
	return NCrypt($String, $Password, True);
}


if(!function_exists('ArraySum')) {
	function ArraySum($Array){
		$N = 0;
		$Array = array_values($Array);
		for($Count = Count($Array), $i = 0; $i < $Count; $i++) $N = Summation($N, $Array[$i]);
		return $N;
	}
}

// TODO: ADD $C, $D... func_get_args
if(!function_exists('Summation')) {
	function Summation($A, $B) {
		
		settype($A, 'string');
		settype($B, 'string');
		
		$AL = strlen($A);
		$BL = strlen($B);
		$MaxLength = ($AL > $BL) ? $AL : $BL;
		$A = str_pad($A, $MaxLength, '0', STR_PAD_LEFT);
		$B = str_pad($B, $MaxLength, '0', STR_PAD_LEFT);
		
		for($i = $MaxLength - 1; $i >= 0; $i--) {
			if(!isset($C[$i])) $C[$i] = 0;
			$C[$i] += (int)$A[$i] + (int)$B[$i];
			if($C[$i] > 9){
				$C[$i] -= 10;
				$C[$i-1] = 1;
			}
		}
		return implode('', array_reverse($C));
	}
}

if(!function_exists('Crc32Value')) {
	function Crc32Value($Length = -1){
		$Value = func_get_args();
		$Crc = crc32(serialize($Value));
		$Crc = sprintf('%u', $Crc);
		$Hash = base_convert($Crc, 10, 36);
		if($Length > 0) $Hash = substr($Hash, -$Length);
		return $Hash;
	}
}

if(!function_exists('Clamp')) {
	function Clamp($V, $A, $B){
		if($V > $B) return $B;
		else if($V < $A) return $A;
		else return $V;
	}
}

// Fixed CleanupString function from Vanilla I
if(!function_exists('CleanupString')) {
	function CleanupString($String) { // deprecated, use Gdn_Format::Clean()
		return Gdn_Format::Clean($String);
	}
}

if(!function_exists('ConvertEncoding')) {
	function ConvertEncoding($Value){ // TODO: make windows-1251 as param or config
		if(!mb_check_encoding($Value, 'utf-8')){
			if(mb_check_encoding($Value, 'windows-1251')) $Value = mb_convert_encoding($Value, 'utf-8', 'windows-1251');
		}
		if(!mb_check_encoding($Value, 'utf-8')) trigger_error('Failed to encode value.');
		return $Value;
	}
}

if(!function_exists('SplitString')) {
	function SplitString($String, $RegExpr = False, $FilterFunction = Null) {
		if($RegExpr == False) $RegExpr = '/\s*,\s*/';
		if($RegExpr{0} != '/') $RegExpr = '/'.$RegExpr.'/';
		$Array = preg_split($RegExpr, $String);
		$Array = array_map('trim', $Array);
		$Type = gettype($FilterFunction);
		//$Callbacks = array();
		switch($Type){
			case 'NULL': $Array = array_filter($Array); break;
			case 'string': $Array = array_filter($Array, $FilterFunction); break;
			case 'array': {
				foreach($FilterFunction as $Function) $Array = $Function($Array);
			} break;
			default: trigger_error(sprintf('Unexpected type (%s) given', $Type));
		}
		return $Array;
	}
}

if(!function_exists('GetSimilarity')) {
	function GetSimilarity($String, $DataArray, $IdKey = '', $ValueKey = ''){
		$Percents = array();
		$String = strip_tags($String);
		foreach($DataArray as $Key => $Array){
			if(is_array($Array)){
				$TestValue = strip_tags($Array[$ValueKey]);
				$MatcheId = $Array[$IdKey];
			}else {
				$TestValue = $Array;
				$MatcheId = $Key;
			}
			similar_text($String, $TestValue, $Percent);
			$Percents[$Percent] = $MatcheId;
			$IdValues[$MatcheId] = $TestValue;
		}
		krsort($Percents);
		list($Percent, $Id) = each($Percents);

		$Dummy = new StdClass();
		$Dummy->Percent = $Percent;
		$Dummy->Id = $Id;
		$Dummy->Value = $IdValues[$Id];
		$Dummy->String = $String;

		return $Dummy;
	}

}

if(!function_exists('Camelize')) {
	function Camelize($String){
		$String = str_replace('_', ' ', $String);
		$String = ucwords($String);
		$String = str_replace(' ', '', $String);
		return $String;
	}
}