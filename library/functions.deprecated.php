<?php

if (!function_exists('Markdownify')) {
	/**
	* Converts HTML to Markdown
	*/ 
	function Markdownify($Html) {
		if (function_exists('Debug') && Debug()) {
			trigger_error(sprintf('%s is deprecated, use HtmlToMarkdown() instead.', __FUNCTION__), E_USER_DEPRECATED);
		}
		$Html = Gdn_Format::To($Html, 'xHtml');
		$Snoopy = Gdn::Factory('Snoopy');
		$Vars = array('input' => $Html, 'keepHTML' => 1);
		$Snoopy->Submit('http://milianw.de/projects/markdownify/demo.php', $Vars);
		$Doc = PqDocument($Snoopy->results);
		$Code = Pq('pre > code:eq(0)')->Text();
		$Result = $Code;
		return $Result;
	}
}

/**
* Get your IP-address
* Credit: http://projects.westhost.com/contest/php/function/getipaddress/213
*/
if (!function_exists('GetIpAddress')) {
	function GetIpAddress($NumericFormat = True) {
		if (function_exists('Debug') && Debug()) trigger_error(sprintf('%s is deprecated, use RealIpAddress() instead.', __FUNCTION__), E_USER_DEPRECATED);
		$Ip = False;
		foreach(array('HTTP_CLIENT_IP','HTTP_X_FORWARDED_FOR','HTTP_X_FORWARDED','HTTP_X_CLUSTER_CLIENT_IP','HTTP_FORWARDED_FOR','HTTP_FORWARDED','REMOTE_ADDR') as $Key) {
			if (isset($_SERVER[$Key])) {
				list ($Ip) = explode(',', $_SERVER[$Key]);
				break;
			}
		}
		if ($NumericFormat) $Ip = sprintf('%u', ip2long($Ip));
		return $Ip;
	}
}

if (!function_exists('GetRealIpAddress')) {
	function GetRealIpAddress($bIPv4Format = False) {
		if (function_exists('Debug') && Debug()) trigger_error(sprintf('%s is deprecated, use RealIpAddress() instead.', __FUNCTION__), E_USER_DEPRECATED);
		// Use GetIpAddress() instead
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) $Ip = $_SERVER['HTTP_CLIENT_IP'];
		elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) $Ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		else $Ip = $_SERVER['REMOTE_ADDR'];
		if ($bIPv4Format) {
			// 2130706433 = 127.0.0.1
			// -1 = Invalid IP
			$Ip = sprintf('%u', ip2long($Ip));
		}
		return $Ip;
	}
}

if (!function_exists('FormatTextAsRow')) {
	function FormatTextAsRow($Array, $MaxLengthArray) {
		if (function_exists('Debug') && Debug()) 
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
		if (function_exists('Debug') && Debug()) trigger_error('FormatTextAsTable() is deprecated. Use TextDataGrid() instead.');
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
		
		if (function_exists('Debug') && Debug()) 
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
		if (function_exists('Debug') && Debug()) 
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
				if (Debug()) 
					trigger_error('ConsolidateDataSetValues() is deprecated. Use GroupByKey() instead.', E_USER_DEPRECATED);
				$Result[$N][] = $Data;
			}
			elseif($ValueKey == 'unique') {
				if (Debug()) 
					trigger_error('ConsolidateDataSetValues() is deprecated. Use PromoteKey() instead.', E_USER_DEPRECATED);
				$Result[$N] = $Data;
			}
			elseif($ValueKey != '') {
				$Result[$N] = GetValue($ValueKey, $Data);
				if (Debug()) 
					trigger_error('ConsolidateDataSetValues() is deprecated. Use ConsolidateArrayValuesByKey() instead.');
			} else {
				$Result[] = $N;
				if (Debug()) 
					trigger_error('ConsolidateDataSetValues() is deprecated. Use ConsolidateArrayValuesByKey() instead.');
			}

		}
		return $Result;
	}
}

if (!function_exists('GroupArrayByKey')) {
	function GroupArrayByKey($Array, $Key, $ValueKey = '', $AssociativeArrayValueKey = '', $DefaultValue = False) {
		if (function_exists('Debug') && Debug()) 
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

// returns <a href="[img]"><img src="[thumb]"/></a>
if (!function_exists('ThumbnailImage')) {
	function ThumbnailImage($Data, $Attributes = False) {
		
		if (function_exists('Debug') && Debug()) Deprecated(__FUNCTION__, 'Thumbnail');

		$Width = ArrayValue('width', $Attributes, '');
		$Height = ArrayValue('height', $Attributes, '');

		if(Is_Array($Data)) {
			// group, todo
			// <ul><li><a></a></li>
		}

		$Prefix = substr($Data, 0, 7);
		//if(In_Array($Prefix, array('http://', 'https:/'))) {}
		//$bLocalImage = False;

		if ($Prefix != 'http://') {
			//$bLocalImage = True;
			$IncomingImage = $Data;
			$ImageFindPaths[] = 'uploads'.DS.$Data;
			$ImageFindPaths[] = $Data;
			foreach($ImageFindPaths as $File) {
				if(file_exists($File) && is_file($File)) {
					$IncomingImage = $File;
					break;
				}
			}
		} else {
			$IncomingImage = $Data;
		}

		$CacheDirectory = 'uploads/cached';
		if (!is_writable($CacheDirectory)) {
			mkdir($CacheDirectory, 0777, True);
			if (!is_writable($CacheDirectory)) {
				$ErrorMessage = ErrorMessage(sprintf(T('Directory (%s) is not writable.'), $CacheDirectory), 'PHP', __FUNCTION__);
				trigger_error($ErrorMessage, E_USER_ERROR);
				return '';
			}
		}

		$Name = CleanupString(pathinfo($IncomingImage, PATHINFO_FILENAME) . ' '.$Width.' '.$Height);
		$Extension = FileExtension($IncomingImage);
		$Target = $CacheDirectory. DS . $Name . '.' . $Extension;

		if (!file_exists($Target)) {
			Gdn_UploadImage::SaveImageAs($IncomingImage, $Target, $Height, $Width);
		}

		$Target = str_replace(DS, '/', $Target);
		if (!array_key_exists('alt', $Attributes)) $Attributes['alt'] = pathinfo($Name, PATHINFO_FILENAME);

		List($Width, $Height, $Type) = GetImageSize($IncomingImage);
		$Attributes['alt'] .= sprintf(' (%d×%d)', $Width, $Height);
		$Image = Img($Target, $Attributes);

		return Anchor($Image, Url($IncomingImage), '', '', True);
	}
}