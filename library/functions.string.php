<?php

if (!function_exists('SplitUpString')) {
	/**
	* SplitString v2, no regular expressions.
	* Split string $String to array.
	* Be default applies to array elements: trim
	* Be default apply to array: array_filter and array_values
	*/
	function SplitUpString($String, $Sep = ',', $Functions = Null) {
		static $DefaultFunctions = 'trim filter values';
		$Array = explode($Sep, $String);
		if ($Functions) {
			$List = $DefaultFunctions;
			if (is_string($Functions)) $List .= ' ' . $Functions;
			$Collection = array_map('trim', explode(' ', trim($List)));
			foreach ($Collection as $Key => $F) {
				if ($F{0} == '-') unset($Collection[$Key]);
			}
			foreach ($Collection as $F) {
				if (substr($F, 0, 1) == '+') substr($F, 1);
				if ($F == 'trim') $Array = array_map('trim', $Array);
				elseif ($F == 'filter') $Array = array_filter($Array);
				elseif ($F == 'values') $Array = array_values($Array);
				elseif ($F == 'strtolower') $Array = array_map('strtolower', $Array);
				elseif ($F == 'unique') $Array = array_unique($Array);
			}
		}
		return $Array;
	}
}

if (!function_exists('StringIsNullOrEmpty')) {
	/** 
	* [Garden]. Checks whether or not a string is null or an empty string.
	* Taked from Garden core (for use this functions in other projects).
	*
	* @param string $String The string to check.
	* @return bool
	*/
	function StringIsNullOrEmpty($String) {
		return $String === NULL || $String === '';
	}
}

if (!function_exists('ToTitleCase')) {
	/**
	* Convert full name. First letters uppercased.
	*/
	function ToTitleCase($S) {
		return mb_convert_case($S, MB_CASE_TITLE, 'utf-8');
	}
}

if (!function_exists('CleanUpPhone')) {
	/**
	* Removes everything except digits, + and -.
	*/
	function CleanUpPhone(&$String) {
		$String = preg_replace('/[^0-9\+\-]/', '', $String);
		return $String;
	}
}

if (!function_exists('TextDataGrid')) {
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
	function TextDataGrid($Headers, $DataArray, $Options = False) {
		// TODO: FIX FOR MULTILINE TEXT
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

if (!function_exists('CheckUtf')) {
	/**
	* Check given $String for UTF
	*/
	function CheckUtf($String) {
		return (bool) preg_match('~~u', $String);
	}
}

if (!function_exists('mb_str_pad')) {
	/**
	* UTF-8 string padding. str_pad() for multibyte string.
	*/
	function mb_str_pad($String, $PadLength, $PadString = ' ', $PadType = STR_PAD_RIGHT) {
		$Diff = strlen($String) - mb_strlen($String, 'utf-8');
		return str_pad($String, $PadLength + $Diff, $PadString, $PadType);
	}
}

if (!function_exists('mb_ucfirst')) {

	/**
	* HP’s ucfirst function is very usefull when you want to change words 
	* first letters to uppercase and other letters to lowercase. Currently on PHP 
	* does not have a multibyte (UTF-8) version of ucfirst function.
	* Credits http://petruha.net/?p=97
	*/
	function mb_ucfirst($String, $Encoding = 'utf-8') {
		if (!is_null($Encoding)) mb_internal_encoding($Encoding);
		return mb_strtoupper(mb_substr($String, 0, 1)) . mb_substr($String, 1);
	}
}


if (!function_exists('NCrypt')) {
	/**
	* Crypt/Decrypt string using password.
	*/
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

if (!function_exists('Crc32Value')) {
	/**
	* Calculates small hash for any data based on crc32()
	* Length of string 6-7 chars of [a-z0-9]
	*/ 
	function Crc32Value() {
		$Value = func_get_args();
		$Crc = crc32(serialize($Value));
		$Crc = sprintf('%u', $Crc);
		$Hash = base_convert($Crc, 10, 36);
		return $Hash;
	}
}

if (!function_exists('CleanupString')) {
	/**
	* CleanupString function from Vanilla I
	*/
	function CleanupString($String) {
		return Gdn_Format::Clean($String);
	}
}

if (!function_exists('ConvertEncoding')) {
	function ConvertEncoding($Value) { // TODO: make windows-1251 as param or config
		if (!mb_check_encoding($Value, 'utf-8')) {
			if(mb_check_encoding($Value, 'windows-1251')) $Value = mb_convert_encoding($Value, 'utf-8', 'windows-1251');
		}
		if (!mb_check_encoding($Value, 'utf-8')) trigger_error('Failed to encode value.');
		return $Value;
	}
}

if (!function_exists('SplitString')) {
	/**
	* Split string to array by a regular expression, calls callback or filter for this array.
	* Useful for tags or something like.
	*/
	function SplitString($String, $RegExpr = False, $FilterFunction = Null) {
		if ($RegExpr == False) $RegExpr = '/\s*,\s*/';
		elseif ($RegExpr{0} != '/') $RegExpr = '/'.$RegExpr.'/';
		$Array = preg_split($RegExpr, $String);
		$Array = array_map('trim', $Array);
		$Type = gettype($FilterFunction);
		switch ($Type) {
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

if (!function_exists('GetSimilarity')) {
	/**
	* Compares the string $String with all elements of the array $DataArray using the similar_text();
	* Returns object holding properties:
	* Percent: Similarity in percent 
	* Value: Value of more similarity of subarray (or object) with key/property = $ValueKey
	* Id: Value of more similarity of subarray (or object) with key/property = $IdKey
	* String: Compared $String
	*/
	function GetSimilarity($String, $DataArray, $IdKey = '', $ValueKey = '') {
		$Percents = array();
		$String = strip_tags($String);
		foreach ($DataArray as $Key => $Array) {
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

if (!function_exists('Camelize')) {
	/*
	* Camelize string.
	* Example: Camelize('my_var_name');
	* Returns: MyVarName
	*/
	function Camelize($String) {
		$String = str_replace('_', ' ', $String);
		$String = ucwords($String);
		$String = str_replace(' ', '', $String);
		return $String;
	}
}