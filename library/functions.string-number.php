<?php

if (!function_exists('StringIsNullOrEmpty')) {
	/** Checks whether or not a string is null or an empty string.
	* Taked from Garden core (for use this functions in other projects).
	*
	* @param string $String The string to check.
	* @return bool
	*/
	function StringIsNullOrEmpty($String) {
		return $String === NULL || $String === '';
	}
}

/**
* Convert full name. First letters uppercased.
*/
if (!function_exists('ToTitleCase')) {
	function ToTitleCase($S) {
		return mb_convert_case($S, MB_CASE_TITLE, 'utf-8');
	}
}

/**
* Removes everything except digits, + and -
*/
if (!function_exists('CleanUpPhone')) {
	function CleanUpPhone(&$String) {
		$String = preg_replace('/[^0-9\+\-]/', '', $String);
		return $String;
	}
}

/**
* Function for drawing text as table like in MySQL client console.
* Example:
$Row1 = array('Yvan', 'kras@mail.com', '1');
$Row2 = array('John', 'dsffffkrok@mail.ag', '0');
echo TextDataGrid(array('FirstName', 'Email', 'OnlineWork'), array($Row1, $Row2, ...));
Result:
+-----------+-------------------------+------------+
| FirstName | Email                   | OnlineWork |
+-----------+-------------------------+------------+
| Yvan      | kras@mail.com           |          1 |
| John      | dsffffkrok@mail.ag      |          0 |
| Dummy     | krokuswww@mail.com      |          1 |
| Andy      | lande@reg.maosss.cow    |          0 |
+-----------+-------------------------+------------+
*/

if (!function_exists('TextDataGrid')) {
	function TextDataGrid($Headers, $DataArray, $Options = False) {
		
		if (!function_exists('_TextDataRow')) {
			function _TextDataSeparator($MaxLengthArray) {
				$Result = '';
				foreach ($MaxLengthArray as $Length) $Result .= '+-' . str_repeat('-', $Length) . '-';
				return $Result.'+';
			}

			function _TextDataRow($Array, $MaxLengthArray) {
				$Result = '';
				foreach(array_values($Array) as $N => $Value) {
					$MaxLengthOfRow = $MaxLengthArray[$N];
					// TODO: How are we going to display null values?
					if (is_numeric($Value))
						$Result .= '| ' . mb_str_pad($Value, $MaxLengthOfRow, ' ', STR_PAD_LEFT) . ' ';
					else 
						$Result .= '| ' . mb_str_pad($Value, $MaxLengthOfRow, ' ', STR_PAD_RIGHT) . ' ';
				}
				return $Result.'|';
			}
		}
		
		$Length = count($Headers);
		$MaxLengthArray = array_fill(0, $Length, 0);
		array_unshift($DataArray, $Headers);
		// 1. Detect max length
		foreach ($DataArray as $Data) {
			$Data = array_values($Data);
			for ($i = 0; $i < $Length; $i++) {
				$LocalLength = mb_strlen($Data[$i], 'utf-8');
				if ($LocalLength > $MaxLengthArray[$i]) $MaxLengthArray[$i] = $LocalLength;
			}
		}
		$Result = '';
		// 2. Draw headers
		$Result .= _TextDataSeparator($MaxLengthArray) . "\n";
		$Result .= _TextDataRow(array_shift($DataArray), $MaxLengthArray) . "\n";	
		$Result .= _TextDataSeparator($MaxLengthArray) . "\n";
		// 3. Draw table rows
		foreach($DataArray as $N => $Data)
			$Result .= _TextDataRow($Data, $MaxLengthArray) . "\n";
		$Result .= _TextDataSeparator($MaxLengthArray);
		
		return $Result;
	}
}

/**
* Check given $String for UTF
*/
if (!function_exists('CheckUtf')) {
	function CheckUtf($String) {
		return (bool) preg_match('~~u', $String);
	}
}

/**
* UTF-8 string padding. str_pad() for multibyte string.
*/
if (!function_exists('mb_str_pad')) {
	function mb_str_pad($String, $PadLength, $PadString = ' ', $PadType = STR_PAD_RIGHT) {
		$Diff = strlen($String) - mb_strlen($String, 'utf-8');
		return str_pad($String, $PadLength + $Diff, $PadString, $PadType);
	}
}


/**
* Crypt/Decrypt string using password.
*/
if (!function_exists('NCrypt')) {
	function NCrypt($String, $Password, $bDecrypt) {
		if (!defined('ALPHABET')) {
			define('RALPHABET', pack('H*', '4142434445464748494a4b4c4d4e4f505152535455565758595a6162636465666768696a6b6c6d6e6f707172737475767778797a31323334353637383930205c212c2e3a3b3f7e402324255e262a28295f2b2d3d5d5b7d7b2f3e3c2227607c4142434445464748494a4b4c4d4e4f505152535455565758595a6162636465666768696a6b6c6d6e6f707172737475767778797a31323334353637383930205c212c2e3a3b3f7e402324255e262a28295f2b2d3d5d5b7d7b2f3e3c2227607c'));
			define('ALPHABET', strrev(RALPHABET));
		}

		$String = (!$bDecrypt) ? array_pop(unpack('H*', $String)) : pack('H*', $String);
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
		$Result = (!$bDecrypt) ? array_pop(unpack('H*', $Result)) : pack('H*', $Result);
		return $Result;
	}

	function Encrypt($String, $Password) {
		return NCrypt($String, $Password, False);
	}

	function Decrypt($String, $Password) {
		return NCrypt($String, $Password, True);
	}
}

if (!function_exists('ArraySum')) {
	function ArraySum($Array){
		$N = 0;
		$Array = array_values($Array);
		for($Count = Count($Array), $i = 0; $i < $Count; $i++) $N = Summation($N, $Array[$i]);
		return $N;
	}
}

/**
* Calculate the sum of integer values (values can be any length)
*/ 
if (!function_exists('Summation')) {
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


/**
* Calculates small hash for any data based on crc32()
* Length of string 6-7 chars of [a-z0-9]
*/ 
if (!function_exists('Crc32Value')) {
	function Crc32Value() {
		$Value = func_get_args();
		$Crc = crc32(serialize($Value));
		$Crc = sprintf('%u', $Crc);
		$Hash = base_convert($Crc, 10, 36);
		return $Hash;
	}
}

/**
* Returns the first number clamped to the interval from A to B.
*/
if (!function_exists('Clamp')) {
	function Clamp($V, $A, $B) {
		if ($V > $B) return $B;
		else if ($V < $A) return $A;
		else return $V;
	}
}

/**
* CleanupString function from Vanilla I
*/
if (!function_exists('CleanupString')) {
	function CleanupString($String) {
		return Gdn_Format::Clean($String);
	}
}

if (!function_exists('ConvertEncoding')) {
	function ConvertEncoding($Value){ // TODO: make windows-1251 as param or config
		if(!mb_check_encoding($Value, 'utf-8')){
			if(mb_check_encoding($Value, 'windows-1251')) $Value = mb_convert_encoding($Value, 'utf-8', 'windows-1251');
		}
		if(!mb_check_encoding($Value, 'utf-8')) trigger_error('Failed to encode value.');
		return $Value;
	}
}

/**
* Split string to array by a regular expression, calls callback or filter for this array.
* Useful for tags or something like.
*/
if (!function_exists('SplitString')) {
	function SplitString($String, $RegExpr = False, $FilterFunction = Null) {
		if ($RegExpr == False) $RegExpr = '/\s*,\s*/';
		elseif ($RegExpr{0} != '/') $RegExpr = '/'.$RegExpr.'/';
		$Array = preg_split($RegExpr, $String);
		$Array = array_map('trim', $Array);
		$Type = gettype($FilterFunction);
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

/**
* Compares the string $String with all elements of the array $DataArray using the similar_text();
* Returns object holding properties:
* Percent: Similarity in percent 
* Value: Value of more similarity of subarray (or object) with key/property = $ValueKey
* Id: Value of more similarity of subarray (or object) with key/property = $IdKey
* String: Compared $String
*/
if (!function_exists('GetSimilarity')) {
	function GetSimilarity($String, $DataArray, $IdKey = '', $ValueKey = '') {
		$Percents = array();
		$String = strip_tags($String);
		foreach($DataArray as $Key => $Array){
			if (is_array($Array)) {
				$TestValue = strip_tags($Array[$ValueKey]);
				$MatcheId = $Array[$IdKey];
			} else {
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

/*
* Camelize string.
* Example: Camelize('my_var_name');
* Returns: MyVarName
*/
if (!function_exists('Camelize')) {
	function Camelize($String) {
		$String = str_replace('_', ' ', $String);
		$String = ucwords($String);
		$String = str_replace(' ', '', $String);
		return $String;
	}
}