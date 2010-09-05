<?php

// 10. DEBUG FUNCTIONS
if(!function_exists('d')) {
	function d(){
		static $bExit = True;
		if(!defined('DPHP_USE_ACCESSIBLE')) require dirname(__FILE__).DS.'vendors'.DS.'class.dumphper.php';
		$Args = func_get_args();
		if(count($Args) == 0 && $bExit) $bExit = False;
		if (PHP_SAPI != 'cli') {
			foreach($Args as $A) Dumphper::dump($A);
		} else {
			$i = 1;
			ob_start();
			foreach($Args as $A){
				echo str_repeat('*', $i++) . ' ';
				var_dump($A);
			}
			$String = ob_get_contents();
			@ob_end_clean();
			$Encoding = Gdn::Config('Plugins.Console.MessageEnconding');
			$String = preg_replace("/\=\>\n +/s", '=> ', $String);
			if($Encoding && $Encoding != 'utf-8') $String = mb_convert_encoding($String, $Encoding, 'utf-8');
			echo $String;
		}
		// TODO: neeed close database connection
		if($bExit) exit();
	}
}

/* ========================================================= functions.php */


/*if(!function_exists('SetValueR')) {
	function SetValueR($Key, &$Collection, $Value){
		$Value = GetValueR($Key, $Collection, Null);
		if ($Value !== Null) {
			$Path = explode('.', $Key);
			array_pop($Path);
			$Key = implode('.', $Path);
			
		}
		d($Key, @$SetCollection, $Collection);
		//d($Path);
		for($Count = count($Path), $i = 0; $i < $Count; $i++){
			//SetValue
		}
	}
}*/

/*if(!function_exists('SetValueR')) {
	function SetValueR($Key, &$Collection, $Value){
		$Return = $Collection;
		$Path = explode('.', $Key);
		$FirstKey = reset($Path);
		$LastKey = end($Path);
		for($Count = count($Path) - 1, $i = 0; $i < $Count; $i++){
			$SubKey = $Path[$i];
			if(is_array($Return) && array_key_exists($SubKey, $Return)) $Return =& $Return[$SubKey];
			elseif(is_object($Return) && property_exists($Return, $SubKey)) $Return =& $Return->$SubKey;
		}
		SetValue($LastKey, $Return, $Value);
		SetValue($FirstKey, $Collection, $Return);
		return $Collection;
	}
}*/


if(!function_exists('Pick')) {
	function Pick($Default = False) {
		$Arguments = func_get_args();
		$Default = array_pop($Arguments);
		foreach ($Arguments as $A) {
			if(!empty($A)) return $A;
		}
		return $Default;
	}
}


/* =================================================== other-functions.php */
// …




function Crc32Value($Length = -1){
	$Value = func_get_args();
	$Crc = crc32(serialize($Value));
	$Crc = sprintf('%u', $Crc);
	$Hash = base_convert($Crc, 10, 36);
	if($Length > 0) $Hash = substr($Hash, -$Length);
	return $Hash;
}



// 6. VALIDATE FUNCTIONS

if(!function_exists('ValidateDnsEmail')) {
	function ValidateDnsEmail($Value, $Field = ''){
		if($Value == '') return True; // + required
		$Result = ValidateEmail($Value, $Field);
		if($Result !== True) return 'ValidateEmail';
		$EmailArray = SplitString($Value, '@');
		$User = ArrayValue(0, $EmailArray);
		$Host = ArrayValue(1, $EmailArray);
		$MxHosts = array();
		$Result = getmxrr($Host, $MxHosts);
		return $Result;
	}
}


// 8. NETWORK FUNCTIONS
if(!function_exists ('getmxrr')){
	// This script was writed by Setec Astronomy - setec@freemail.it
	function getmxrr($hostname = '', &$mxhosts, &$weight = array()){
		$weight = array();
		$mxhosts = array();
		$result = false;

		$command = 'nslookup -type=mx ' . escapeshellarg($hostname);
		exec($command, $result);
		$i = 0;
		$nslookup = array();
		while(list($key, $value) = each($result)){
			if(strstr($value, 'mail exchanger')){
				$nslookup[$i] = $value;
				$i++;
			}
		}

		$mx = array();
		while(list($key, $value) = each($nslookup)){
			$temp = explode(' ', $value);
			$mx[$key][0] = substr($temp[3], 0, -1);
			$mx[$key][1] = $temp[7];
			$mx[$key][2] = gethostbyname($temp[7]);
		}

		array_multisort($mx);

		foreach($mx as $value){
			$mxhosts[] = $value[1];
			$weight[] = $value[0];
		}

		return count($mxhosts) > 0;
	}
}

// 9. MISC FUNCTIONS
// TODO: Try to extend method for controllers
if(!function_exists('GetBodyIdentifier')){
	function GetBodyIdentifier(&$Controller){
		$ControllerName = GetShortControllerName($Controller);
		$BodyIdentifier = $Controller->ApplicationFolder.'_'.$ControllerName.'_'.Gdn_Format::AlphaNumeric(strtolower($Controller->RequestMethod));
		return strtolower($BodyIdentifier);
	}
}

if(!function_exists('GetShortControllerName')){
	function GetShortControllerName(&$C){
		if(strtolower(substr($C->ControllerName, -10, 10)) == 'controller')
			return substr($C->ControllerName, 0, -10);
		return $C->ControllerName;
	}
}

if(!function_exists('GetRealIpAddress')){
	function GetRealIpAddress($bIPv4Format = False) {
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) $Ip = $_SERVER['HTTP_CLIENT_IP'];
		elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) $Ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		else $Ip = $_SERVER['REMOTE_ADDR'];
		if($bIPv4Format){
			$Ip = ip2long($Ip); // 2130706433 = 127.0.0.1
			if($Ip < 0) $Ip += Pow(2, 32);
		}
		return $Ip;
	}
}

/*
// From modular framework
	static function get_real_ip()
	{
		$ip = false;
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		}
		if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ips = explode(", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
			if ($ip) {
				array_unshift($ips, $ip);
				$ip = false;
			}
			for ($i = 0; $i < count($ips); $i++) {
				if (!preg_match("/^(10|172\.16|192\.168)\./i", $ips[$i])) {
					if (version_compare(phpversion(), "5.0.0", ">=")) {
						if (ip2long($ips[$i]) != false) {
							$ip = $ips[$i];
							break;
						}
					} else {
						if (ip2long($ips[$i]) != - 1) {
							$ip = $ips[$i];
							break;
						}
					}
				}
			}
		}
		return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
	}*/


if(!function_exists('Kick')){
	function Kick($Password = '', $KickMessage = 'Unauthorized'){
		if($Password == '') return;
		if(array_key_exists('HTTP_AUTHORIZATION', $_SERVER)){ // iis
			$Authorization = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
			$_SERVER['PHP_AUTH_USER'] = ArrayValue(0, $Authorization);
			$_SERVER['PHP_AUTH_PW'] = ArrayValue(1, $Authorization);
		}
		$P = ArrayValue('PHP_AUTH_PW', $_SERVER, ''); // apache
		if($P != '' && $P == $Password) return;

		header('WWW-Authenticate: Basic realm = "Restricted Area"');
		header('Status: 401 Unauthorized');
		header('HTTP/1.1 401 Unauthorized');

		die($KickMessage);
	}
}








// 11. DEV

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
		if(array_key_exists($Name, $Columns)) {
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
/* ================================================== render-functions.php */
// …

if(!function_exists('Video')) {
	function Video($Source, $Attributes = array()){
		static $DefaultAttributes = array('width' => 640, 'height' => 360, 'poster' => '', 'autoplay' => False, 'controls' => 'controls');
		static $VideoType = array('3gp' => '3gpp', 'ogv' => 'ogg', 'mkv' => 'x-matroska', 'm4v' => 'mp4');
		//static $Codecs = array('mp4' => 'avc1.42E01E, mp4a.40.2', 'webm' => 'vp8, vorbis', 'ogg' => 'theora, vorbis');
		static $Codecs;
		$Source = SplitString($Source);
		$StoreDirectory = trim(GetValue('StoreDirectory', $Attributes, '', True), '/');
		foreach($Source as $Src){
			if($StoreDirectory != '') $Src = $StoreDirectory . '/'. $Src;
			$Extension = strtolower(pathinfo($Src, 4));
			$Type = ArrayValue($Extension, $VideoType, $Extension);
			$Codec = ArrayValue($Extension, $Codecs);
			$Src = Asset($Src);
			if($Codec != False && strpos($Type, ';') === False) $Type = $Type.';'.Attribute('codecs', $Codec);
			$Type = PrefixString('video/', $Type);
			$SourceAttributes = array('src' => $Src, 'type' => $Type);
			$Sources[] = '<source'.Attribute($SourceAttributes).' />';
		}
		$Sources = implode("\n", $Sources);
		$Poster = ArrayValue('poster', $Attributes);
		// TODO: make poster from video
		if($Poster && (GetValue('SizeOfPoster', $Attributes, False, True) || !array_key_exists('width', $Attributes))){
			// TODO: FIX ME (WITHOUT URL)
			$ImagePoster = Asset($Poster, True);
			List($width, $height) = GetImageSize($ImagePoster);
			$Attributes = array_merge($Attributes, compact('width', 'height'), array('poster' => $ImagePoster));
		}
		$Unsupported = sprintf(T('Your browser cant play this video. You can %s instead.'), Anchor(T('download the video'), $Source[0]));
		$Attributes = array_merge($DefaultAttributes, compact('Unsupported'), $Attributes);
		
		$Unsupported = Wrap(GetValue('Unsupported', $Attributes, '', True), 'p');
		return Wrap($Sources.$Unsupported, 'video', $Attributes);
		
		// Flash Fallback.
		/*$Config = new StdClass();
		$Config->clip->url = $Source[0];
		$Config->autoPlay = ArrayValue('autoplay', $Attributes, False);
		$Config->autoBuffering = ArrayValue('autobuffering', $Attributes, True);
		//d(json_encode($Config));
		$FlashOptions = array('width' => $Attributes['width'], 'height' => $Attributes['width'],
			'FlashVars' => array('config' => json_encode($Config))
		);
		$Flash = FlashHtml('http://releases.flowplayer.org/swf/flowplayer-3.2.1.swf', $FlashOptions);
		// TODO: Image Fallback
		//$ImageFallback = Img($Poster, array('alt' => 'Poster', 'title' => T('No video playback capabilities.')));
		*/
	}
}

if(!function_exists('SmallImage')) {
	function SmallImage($Source, $Attributes = array()) {
		
		$Width = ArrayValue('width', $Attributes, '');
		$Height = ArrayValue('height', $Attributes, '');
		$ImageQuality = GetValue('ImageQuality', $Attributes, 85, True);
		
		$Hash = Crc32Value($Source, $Attributes, $ImageQuality);
		$TargetFolder = 'uploads/cached/' . date('Y'); // cache directory
		if(!is_dir($TargetFolder)) mkdir($TargetFolder, 0777, True);
		$Filename = pathinfo($Source, 8);
		$Extension = pathinfo($Source, 4);
		$SmallImage = GenerateCleanTargetName($TargetFolder, $Filename.'-'.$Hash, $Extension, False, True);
		if(!file_exists($SmallImage)) Gdn_UploadImage::SaveImageAs($Source, $SmallImage, $Height, $Width, False);
		
		TouchValue('alt', $Attributes, $Filename);
		// Fail. ImageSY expects parameter 1 to be resource
		//if(!array_key_exists('height', $Attributes)) TouchValue('height', $Attributes, ImageSY($SmallImage));
		//if(!array_key_exists('width', $Attributes)) TouchValue('width', $Attributes, ImageSX($SmallImage));
		return Img($SmallImage, $Attributes);
	}
}


if(!function_exists('FancyZoomImage')) {
	function FancyZoomImage($Source, $Attributes = array()){
		// defaults
		if(!is_array($Attributes)) $Attributes = array();
		$NoHiding = GetValue('NoHiding', $Attributes, '', True);
		$bSaveImage = False;
		$Hash = Crc32Value($Source, $Attributes);
		$Filename = pathinfo($Source, PATHINFO_FILENAME);
		$Extension = pathinfo($Source, PATHINFO_EXTENSION);

		if(!array_key_exists('SmallImage', $Attributes)){
			// make directory
			$TargetFolder = 'uploads/cached/' . date('Y'); // cache directory
			if(!is_dir($TargetFolder)) mkdir($TargetFolder, 0777, True);
			$SmallImage = GenerateCleanTargetName($TargetFolder, $Filename.'-'.$Hash, $Extension, False, True);
			$Attributes['SmallImage'] = $SmallImage;
			if(!file_exists($SmallImage)) $bSaveImage = True;
		}

		// get attributes
		$Width = ArrayValue('width', $Attributes, '');
		$Height = ArrayValue('height', $Attributes, '');
		$Crop = GetValue('Crop', $Attributes, False, True);
		$SmallImage = GetValue('SmallImage', $Attributes, '', True);
		$ZoomAttributes = array('id' => 'p'.$Hash);
		if(!$NoHiding) $ZoomAttributes['style'] = 'display:none';

		//if(!array_key_exists('alt', $Attributes)) $Attributes['alt'] = $Filename;
		TouchValue('alt', $Attributes, $Filename);

		if($bSaveImage) Gdn_UploadImage::SaveImageAs($Source, $SmallImage, $Height, $Width, $Crop);

		$SmallImage = Img($SmallImage, $Attributes);
		$ZoomImage = Img($Source, array('alt' => ArrayValue('alt', $Attributes, '')));
		return "\n" . Wrap($SmallImage, 'a', array('href' => '#p'.$Hash)) . Wrap($ZoomImage, 'div', $ZoomAttributes);
	}
}

if(!function_exists('AltClass')) {
	function AltClass() {
		static $i = 0;
		$AltClass = $i++ % 2 ? 'Alt' : '';
		return Attribute('class', $AltClass);
	}
}

// echo '<tr'.Attribute('class', Alt('Alt', '')).'>';
if(!function_exists('Alt')){
	function Alt() {
		static $i;
		$Arguments = func_get_args();
		$NumArguments = count($Arguments);
		if ($NumArguments > 0) return $Arguments[($i++ % $NumArguments)];
		$i = 0;
		return '';
	}
}

// echo '<tr'.AltAttribute('class', 'Alt', '').'>';
if(!function_exists('AltAttribute')){
	function AltAttribute($Name) {
		static $i;
		$Arguments = func_get_args();
		$Name = array_shift($Arguments);
		$NumArguments = count($Arguments);
		if ($NumArguments > 0){
			$Value = $Arguments[($i++ % $NumArguments)];
			return Attribute($Name, $Value);
		}
		$i = 0;
		return '';
	}
}

if(!function_exists('FlashHtml')){
	function FlashHtml($Movie, $Attributes = array(), $Params = array(), $FlashVars = False) {
		static $DefaultAttributes = array('width' => 400, 'height' => 300, 'type' => 'application/x-shockwave-flash');
		static $DefaultParams = array('allowfullscreen' => 'true', 'allowscriptaccess' => 'always', 'quality' => 'best', 'menu' => 'false');
		// BUG: 'wmode' => 'transparent'

		$ScriptRender = GetValue('ScriptRender', $Attributes, False, True);

		if(!is_array($Params)) $Params = array();
		$Params = array_merge($DefaultParams, $Params);
		$Attributes = array_merge($DefaultAttributes, $Attributes);
		$Movie = Asset($Movie);
		$FlashVars = GetValue('FlashVars', $Attributes, $FlashVars, True);
		if($FlashVars != False){
			$FlashVars = Gdn_Format::ObjectAsArray($FlashVars);
			$Vars = array();
			foreach($FlashVars as $Name => $Value) $Vars[] = $Name.'='.$Value; // encodeuricomponent
			$Params['flashvars'] = implode('&', $Vars);
		}
		$MSIE = (strpos(ArrayValue('HTTP_USER_AGENT', $_SERVER), 'MSIE') > 0);
		if($MSIE != False){
			$Params['movie'] = $Movie;
			$ObjectParams = '';
			foreach($Params as $Name => $Value) $ObjectParams .= '<param name="'.$Name.'" value="'.$Value.'" />';
			$Result = '<object'.Attribute($Attributes).'>'.$ObjectParams.'</object>';
		}else{
			$Attributes['src'] = $Movie;
			$Attributes = array_merge($Attributes, $Params);
			$Result = '<embed'.Attribute($Attributes).' />';
		}

		if($ScriptRender) $Result = JavaScript($Result, True);

		// detect flash version you should manually
		return $Result;
	}
}

if(!function_exists('JavaScript')){
	function JavaScript($Mixed, $bWrite = True){
		if(is_string($Mixed)){
			//"\n" => '',
			$Replace = array("\r" => '', '=' => '\=', '</' => '<\/', "'" => "\'");
			$Mixed = str_replace(array_keys($Replace), array_values($Replace), $Mixed);
			$Mixed = explode("\n", $Mixed);
			$Mixed = array_filter($Mixed);
		}
		$Return[] = '<script type="text/javascript">//<![CDATA[';
		if($bWrite) foreach($Mixed as $S) $Return[] = sprintf("document.writeln('%s');", $S);
		else foreach($Mixed as $S) $Return[] = $S.';';
		$Return[] = '//]]></script>';
		return implode("\n", $Return);
	}
}

if(!function_exists('NoIndex')){
	function NoIndex($String){
		$String = "<!-- This text shouldn\'t be indexed //-->\n" . $String;
		return JavaScript($String, True);
	}
}

// returns <a href="[img]"><img src="[thumb]"/></a>
if(!function_exists('ThumbnailImage')){
	function ThumbnailImage($Data, $Attributes = False){

		$Width = ArrayValue('width', $Attributes, '');
		$Height = ArrayValue('height', $Attributes, '');

		if(Is_Array($Data)){
			// group, todo
			// <ul><li><a></a></li>
		}

		$Prefix = substr($Data, 0, 7);
		//if(In_Array($Prefix, array('http://', 'https:/'))){}
		//$bLocalImage = False;

		if($Prefix != 'http://'){
			//$bLocalImage = True;
			$IncomingImage = $Data;
			$ImageFindPaths[] = 'uploads'.DS.$Data;
			$ImageFindPaths[] = $Data;
			foreach($ImageFindPaths as $File){
				if(file_exists($File) && is_file($File)){
					$IncomingImage = $File;
					break;
				}
			}
		}else{
			$IncomingImage = $Data;
		}

		$CacheDirectory = 'uploads/cached/' . date('Y');
		if(!Is_Writable($CacheDirectory)){
			mkdir($CacheDirectory, 0777, True);
			if(!Is_Writable($CacheDirectory)){
				$ErrorMessage = ErrorMessage(sprintf(T('Directory (%s) is not writable.'), $CacheDirectory), 'PHP', __FUNCTION__);
				trigger_error($ErrorMessage, E_USER_ERROR);
				return '';
			}
		}

		$Name = CleanupString(pathinfo($IncomingImage, PATHINFO_FILENAME) . ' '.$Width.' '.$Height);
		$Extension = FileExtension($IncomingImage);
		$Target = $CacheDirectory. DS . $Name . '.' . $Extension;

		if(!file_exists($Target)){
			Gdn_UploadImage::SaveImageAs($IncomingImage, $Target, $Height, $Width);
		}

		$Target = str_replace(DS, '/', $Target);
		if(!array_key_exists('alt', $Attributes)) $Attributes['alt'] = pathinfo($Name, PATHINFO_FILENAME);

		List($Width, $Height, $Type) = GetImageSize($IncomingImage);
		$Attributes['alt'] .= sprintf(' (%d×%d)', $Width, $Height);
		$Image = Img($Target, $Attributes);

		return Anchor($Image, Url($IncomingImage), '', '', True);
	}
}

// Gdn_Format::To($FileSize, 'Size')
if(!function_exists('Size')) {
	function Size($Bytes, $Precision = 2) {
		return Gdn_Format::Bytes($Bytes, $Precision);
		// DEPRECATED, USE Gdn_Format::Bytes($Bytes)
		/*$Units = array('B', 'KB', 'MB', 'GB', 'TB');

		$Bytes = max($Bytes, 0);
		$Pow = floor(($Bytes ? log($Bytes) : 0) / log(1024));
		$Pow = min($Pow, count($Units) - 1);

		$Bytes /= pow(1024, $Pow);

		return round($Bytes, $Precision) . ' ' . $Units[$Pow];
		*/
	}
}

/* ==================================================== time-functions.php */
// …

// 10. TIME FUNCTIONS

if(!function_exists('Now')) {
	function Now(){
		return microtime(True);
	}
}

if(!function_exists('YearSeconds')) {
	function YearSeconds(){
		return (time() - strtotime('1 Jan'));
	}
}
/* ===================================================== dom-functions.php */
// …

function LoadPhpQuery(){
	//if(!class_exists('PhpQuery')) require_once dirname(__FILE__).DS.'vendors' . DS . 'phpQuery.php';
	if(!function_exists('Pq')) require_once dirname(__FILE__).DS.'vendors' . DS . 'phpQuery.php';
}

function LoadQueryPath($Document = ''){
	static $HTMLPurifier;
	if(!function_exists('qp')) require_once dirname(__FILE__).DS.'vendors' . DS . 'QueryPath.php';
	if($Document == '') return;
	if(is_null($HTMLPurifier)) $HTMLPurifier = new HTMLPurifierPlugin();
	if(strpos($Document, '>') === False) $Document = file_get_contents($Document);
	$Document = $HTMLPurifier->Format($Document);
	$Document = trim($Document);
	if(!StringBeginsWith($Document, '<?xml')) $Document = '<?xml version="1.0" encoding="utf-8"?>'.$Document;
	return Qp($Document);
}


/* ============================================ array-object-functions.php */
// …
// 7. ARRAY/OBJECT FUNCTIONS

// temporary compatibility function
if(!function_exists('ObjectValue')){
	function ObjectValue($Key, $Object, $Default = False) {
		return GetValue($Key, $Object, $Default);
	}
}


function array_flat($arr){
	// devpro.it
	$result = array();
	foreach($arr as $value){
		if(!is_array($value))
			$result[]   = $value;
		else
			array_splice($result, count($result), 0, array_flat($value));
	}
	return  $result;
}

// http://phunction.sf.net/
function Flatten($Array){
	$Result = array();
	foreach (new RecursiveIteratorIterator(new RecursiveArrayIterator($Array)) as $Value)
		$Result[] = $Value;
	return $Result;
}


if(!function_exists('RandomValue')) {
	function RandomValue($Key, $Collection, $Default = False) {
		if(is_array($Collection)) return $Collection[array_rand($Collection)];
		return $Default;
	}
}


if(!function_exists('ReplaceEmpty')) {
	function ReplaceEmpty(&$Collection, $R = ' ') {
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

if(!function_exists('ConsolidateDataSetValues')){
	function ConsolidateDataSetValues($Array, $Options, $ValueKey = Null) {
		if (is_string($Options) && substr($Options, 0, 1) == '{') $Options = json_decode($Options);
		if (is_scalar($Options)) $Options = array('Key' => $Options);
		$Key = GetValue('Key', $Options);
		$ValueKey = GetValue('ValueKey', $Options, $ValueKey);

		$Result = array();
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
/* ================================================== scalar-functions.php */
// …
// 4. STRING/NUMBER FUNCTIONS

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
	function SplitString($String, $RegExpr = False, $FilterFunction = Null){
		if($RegExpr == False) $RegExpr = '/\s*,\s*/';
		if($RegExpr{0} != '/') $RegExpr = '/'.$RegExpr.'/';
		$Array = preg_split($RegExpr, $String);
		$Array = array_map('trim', $Array);
		$Array = (is_callable($FilterFunction)) ? array_filter($Array, $FilterFunction) : array_filter($Array);
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
/* ============================================= file-system-functions.php */
// …

if(!function_exists('FileExtension')) {
	function FileExtension($Basename){ // deprecated
		return strtolower(pathinfo($Basename, 4));
	}
}

if(!function_exists('Crc32File')) {
	function Crc32File($File) {
		return crc32( sha1_file($File) );
	}
}

# http://php.net/manual/en/function.readdir.php
if(!function_exists('ProcessDirectory')) {
	function ProcessDirectory($Directory, $Options = False){

		$bRecursive = $Options;

		/*if(Is_Bool($Options)) $bRecursive = $Options;
		elseif(Is_Numeric($Options)) $IntDeep = $Options; // 0 - unlim
		elseif(Is_Array($Options)){
			$IntDeep = ArrayValue('Deep', $Options, '0');
			$bRecursive = ArrayValue('Recursive', $Options, False);
		}*/

		if(!is_dir($Directory)) return False;
		$List = array();
		$Handle = opendir($Directory);
		while(False !== ($File = ReadDir($Handle))){
			$Path = $Directory.DS.$File;
			if ($File == '.' || $File == '..' || !file_exists($Path)) continue;
			if (is_dir($Path) && $bRecursive) {
				$NextDirectory = ProcessDirectory($Path, True);
				if(is_array($NextDirectory)) $List = array_merge($List, $NextDirectory);
			} else {
				$Entry = new StdClass();
				$Entry->Filename = $File;
				$Entry->Directory = $Directory;
				$Entry->Modtime = filemtime($Path);
				if (!is_dir($Path)) { // files
					$Entry->Size = FileSize($Path);
				} else { // directories
					$Entry->IsWritable = Is_Writable($Path);
					$Entry->bDirectory = True;
				}
				$List[] = $Entry;
			}
		}
		closedir($Handle);
		return $List;
	}
}

if(!function_exists('RecursiveRemoveDirectory')) {
	function RecursiveRemoveDirectory($Path){
		// Gdn_FileSysytem::RemoveFolder($Path)
		$Directory = new RecursiveDirectoryIterator($Path);
		// Remove all files
		foreach(new RecursiveIteratorIterator($Directory) as $File) unlink($File);
		// Remove all subdirectories
		foreach($Directory as $SubDirectory){
		// If a subdirectory can't be removed, it's because it has subdirectories, so recursiveRemoveDirectory is called again passing the subdirectory as path
		// @ suppress the warning message
			if(!@rmdir($SubDirectory)) RecursiveRemoveDirectory($SubDirectory);
		}
		// Remove main directory
		return rmdir($Path);
	}
}

function CompileFile($File = Null, $bSave = False) {
	static $RequiredFiles = array();
	if(is_null($File)){
		$Return = $RequiredFiles;
		$RequiredFiles = array();
		return $Return;
	}
	if($bSave != False) {
		$NewFileContent = '';
		$Files = array_values(CompileFile());

		foreach ($Files as $N => $FilePath) {
			$FileData = array_map('rtrim', file($FilePath));
			$Count = count($FileData);
			for ($i = 0; $i < $Count; $i++) {
				$String = $FileData[$i];
				if(strpos($String, 'require') === 0
					|| in_array($String, array('<?php', '?>'))) unset($FileData[$i]);
			}
			$BaseName = pathinfo($FilePath, PATHINFO_BASENAME);
			$FirstLine = "\n/* " . str_pad(" $BaseName ", 72, '=', STR_PAD_LEFT) . "*/\n";
			$NewFileContent .= $FirstLine;
			$NewFileContent .= implode("\n", $FileData);
		}
		return file_put_contents($File, "<?php\n".$NewFileContent);
	}

	$RealPath = realpath($File);
	if(!$RealPath) throw new Exception('No such file '.$File);

	//if (count($RequiredFiles) == 0) $RequiredFiles[] = $RealPath;
	$Hash = Crc32File($RealPath);
	$RequiredFiles[$Hash] = $RealPath;

	$Content = file_get_contents($RealPath);
	$AllTokens = token_get_all($Content);
	foreach ($AllTokens as $N => $TokenArray) {
		list($TokenID) = $TokenArray;
		$String = ArrayValue(1, $TokenArray);
		if(!is_int($TokenID) || !in_array(token_name($TokenID), array('T_REQUIRE', 'T_REQUIRE_ONCE'))) continue;

		$PrevTokenString = ArrayValue(1, $AllTokens[$N-1]);
		$PrevTokenString = str_replace("\r", '', $PrevTokenString);
		if($PrevTokenString !== "\n") continue;

		$OtherTokens = array_slice($AllTokens, $N);
		$FileTokens = array();
		foreach ($OtherTokens as $M => $Tk) {
			if (count($Tk) == 1 && $Tk[0] == ';') {
				$FileTokens = array_slice($OtherTokens, 0, $M);
				break;
			}
		}
		if (count($FileTokens) == 0) throw new Exception('FileTokens not found.');
		$TheFile = False;
		foreach(array_reverse($FileTokens) as $Tk){
			if(is_int($Tk[0]) && token_name($Tk[0]) == 'T_CONSTANT_ENCAPSED_STRING') {
				$TheFile = $Tk[1];
				$TheFile = trim($TheFile, '"\'/\\');
				break;
			}
		}
		if(!$TheFile) throw Exception('No string file found.');
		$DirnameFileConstruct = dirname($RealPath);
		$TheFile = $DirnameFileConstruct . DS . $TheFile;
		$RealFile = realpath($TheFile);
		if (!$RealFile) throw new Exception(sprintf('Invalid path `%1$s`.', $TheFile));

		$Hash = Crc32File($RealFile);
		if (!array_key_exists($Hash, $RequiredFiles)) {
			CompileFile($RealFile);
			$RequiredFiles[$Hash] = $RealFile;
		}
	}

}

// Fixed Upload::GenerateTargetName (Garden)
if(!function_exists('GenerateCleanTargetName')) {
	function GenerateCleanTargetName($TargetFolder, $Name, $Extension = '', $TempFile = False, $bForceOverwriteExisting = False) {
		if ($Extension == '') {
			$Extension = pathinfo($Name, 4);
			$Name = pathinfo($Name, 8);
		}
		$Extension = Gdn_Format::Clean($Extension);
		$BaseName = Gdn_Format::Clean($Name);

		// check for file with same name
		$TestName = $BaseName;
		$TargetFile = $TargetFolder . DS . $TestName . '.' . $Extension;
		if(!file_exists($TargetFile)) return $TargetFile;
		$IsSameFile = ($TempFile != False && file_exists($TempFile) && Crc32File($TempFile) == Crc32File($TargetFile));
		if($IsSameFile || $bForceOverwriteExisting) return $TargetFile;
		$Count = 0;
		do {
			$TestName = $BaseName.'-'.strtolower(RandomString(rand(1, 5)));
			$TargetFile = $TargetFolder . DS . $TestName . '.' . $Extension;
			// make sure that iteration will end
			if(++$Count > 250) throw new Exception('Cannot generate unique name for file.');
		} while (file_exists($TargetFile));

		return $TargetFile;
	}
}

if(!function_exists('UploadFile')) {
	function UploadFile($TargetFolder, $InputName, $Options = False) {
		// TODO: InputName - may be array

		$FileName = ArrayValue('name', ArrayValue($InputName, $_FILES));
		if($FileName == '') return; // no upload, return null

		// options
		$AllowFileExtension = ArrayValue('AllowFileExtension', $Options);
		// TODO: $Overwrite is not used yet
		$CanOverwrite = ArrayValue('Overwrite', $Options, False);
		$CreateTargetFolder = ArrayValue('CreateTargetFolder', $Options, True);
		$WebTarget = ArrayValue('WebTarget', $Options);

		if ($CreateTargetFolder === True) {
			if (!file_exists($TargetFolder)) mkdir($TargetFolder, 0777, True);
			if (!is_writable($TargetFolder)) throw new Exception(sprintf('Directory (%s) is not writable.', $TargetFolder));
		}

		$Upload = new Gdn_Upload();
		if ($AllowFileExtension != False) {
			if (!is_array($AllowFileExtension)) $AllowFileExtension = SplitString($AllowFileExtension);
			foreach ($AllowFileExtension as $Extension) $Upload->AllowFileExtension($Extension);
		}

		$TempFile = $Upload->ValidateUpload($InputName);
		$TargetFile = GenerateCleanTargetName($TargetFolder, $FileName, '', $TempFile, $CanOverwrite);

		$Upload->SaveAs($TempFile, $TargetFile);
		$File = pathinfo($TargetFile, PATHINFO_BASENAME);
		if($WebTarget != False) $File = str_replace(DS, '/', $TargetFile);
		return $File;
	}
}
/* ================================================ language-functions.php */

// …

function LocaleLanguageCode(){
	$T = SplitString(Gdn::Locale()->Current(), '/[_-]/');
	return ArrayValue(0, $T, 'en');
}

function LingvoTranslate($Word, $Options = array()){
	LoadPhpQuery();
	static $Result, $LanguageCode;
	if(is_null($LanguageCode)) $LanguageCode = LocaleLanguageCode();
	$ResetCache = ArrayValue('ResetCache', $Options, False);
	$From = ArrayValue('From', $Options, $LanguageCode);
	$To = ArrayValue('To', $Options, $LanguageCode);

	if(!isset($Result[$Word]) || $ResetCache){
		$Direction = $From.'-'.$To;
		$Doc = PhpQuery::NewDocumentFile('http://lingvo.abbyyonline.com/en/'.$Direction.'/'.rawurlencode($Word));
		/*$Translation = Pq('div.card-short > span.Bold')->Text();
		if($Translation != ''){
			$Translation = preg_replace('/\n/', '', $Translation);
			if(!preg_match('/^[а-я ]+$/iu', $Translation)){
				$Translation = Pq('span.translation:eq(0)')->Text();
			}
		}*/
		Pq('span.translation:eq(0)')->Children()->Remove();
		$Translation = Pq('span.translation:eq(0)')->Text();
		$Translation = trim($Translation);
		$Result[$Word] = $Translation;
	}
	return $Result[$Word];
}

function LocalizedOptions($Options){
	if(is_string($Options)) $Options = func_get_args();
	if(is_array($Options)) $Options = array_combine($Options, array_map('T', $Options));
	return $Options;
}

function LocalizedMessage(){
	$Args = func_get_args();
	$Args[0] = Gdn::Translate($Args[0]);
	return call_user_func_array('sprintf', $Args);
}
