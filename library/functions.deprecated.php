<?php

if (!function_exists('FormatTextAsRow')) {
	function FormatTextAsRow($Array, $MaxLengthArray) {
		if (defined('DEBUG')) 
			trigger_error('FormatTextAsRow() is deprecated. Use TextDataGrid() instead.');
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
}


if (!function_exists('FormatTextAsTable')) {
	function FormatTextAsTable($Headers, $DataArray, $Options = False) { // very slooooow
		if (defined('DEBUG')) trigger_error('FormatTextAsTable() is deprecated. Use TextDataGrid() instead.');
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
		foreach($DataArray as $Data)
			$Result .= FormatTextAsRow($Data, $MaxLengthArray) . "\n";
		return $Result;
	}
}



if (!function_exists('SmallImage')) {
	function SmallImage($Source, $Attributes = array()) {
		
		if (defined('DEBUG')) 
			trigger_error('SmallImage() is deprecated. Use Thumbnail().', E_USER_DEPRECATED);
		
		$Width = ArrayValue('width', $Attributes, '');
		$Height = ArrayValue('height', $Attributes, '');
		$ImageQuality = GetValue('ImageQuality', $Attributes, 100, True);
		$Crop = GetValue('Crop', $Attributes, False, True);
		
		$Hash = Crc32Value($Source, array($Width, $Height, $ImageQuality, $Crop));
		$TargetFolder = 'uploads/cached'; // cache directory
		if (!is_dir($TargetFolder)) mkdir($TargetFolder, 0777, True);
		$Filename = pathinfo($Source, 8);
		$Extension = pathinfo($Source, 4);
		$SmallImage = GenerateCleanTargetName($TargetFolder, $Filename.'-'.$Hash, $Extension, False, True);
		if (!file_exists($SmallImage)) Gdn_UploadImage::SaveImageAs($Source, $SmallImage, $Height, $Width, $Crop);

		if (GetValue('MakeOnly', $Attributes, False, True)) {
			if (GetValue('OutOriginalImageSize', $Attributes, False, True)) { // TEMP, TODO: FIX ME
				$Return = array();
				$Return['ImageSize'] = getimagesize($Source);
				$Return['Result'] = Url($SmallImage);
				return $Return;
			}

			return Url($SmallImage);
		}
		
		
		TouchValue('alt', $Attributes, $Filename);
		// Fail. ImageSY expects parameter 1 to be resource
		//if (!array_key_exists('height', $Attributes)) TouchValue('height', $Attributes, ImageSY($SmallImage));
		//if (!array_key_exists('width', $Attributes)) TouchValue('width', $Attributes, ImageSX($SmallImage));
		return Img($SmallImage, $Attributes);
	}
}

if (!function_exists('BunchCollection')) {
	function BunchCollection($Collection, $Key) {
		if (defined('DEBUG')) 
			trigger_error('BunchCollection() is deprecated. Use GroupByKey().', E_USER_DEPRECATED);
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
			if($ValueKey == 'full') {
				if (defined('DEBUG')) 
					trigger_error('ConsolidateDataSetValues() is deprecated. Use GroupByKey() instead.', E_USER_DEPRECATED);
				$Result[$N][] = $Data;
			}
			elseif($ValueKey == 'unique') {
				if (defined('DEBUG')) 
					trigger_error('ConsolidateDataSetValues() is deprecated. Use PromoteKey() instead.', E_USER_DEPRECATED);
				$Result[$N] = $Data;
			}
			elseif($ValueKey != '') {
				$Result[$N] = GetValue($ValueKey, $Data);
				if (defined('DEBUG')) 
					trigger_error('ConsolidateDataSetValues() is deprecated. Use ConsolidateArrayValuesByKey() instead.');
			} else {
				$Result[] = $N;
				if (defined('DEBUG')) 
					trigger_error('ConsolidateDataSetValues() is deprecated. Use ConsolidateArrayValuesByKey() instead.');
			}

		}
		return $Result;
	}
}

if (!function_exists('GroupArrayByKey')) {
	function GroupArrayByKey($Array, $Key, $ValueKey = '', $AssociativeArrayValueKey = '', $DefaultValue = False) {
		if (defined('DEBUG')) 
			trigger_error('GroupArrayByKey() is deprecated. Use GroupByKey() instead.', E_USER_DEPRECATED);
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

