<?php

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
		
		$Hash = Crc32Value($Source, array($Width, $Height, $ImageQuality));
		$TargetFolder = 'uploads/cached/' . date('Y'); // cache directory
		if(!is_dir($TargetFolder)) mkdir($TargetFolder, 0777, True);
		$Filename = pathinfo($Source, 8);
		$Extension = pathinfo($Source, 4);
		$SmallImage = GenerateCleanTargetName($TargetFolder, $Filename.'-'.$Hash, $Extension, False, True);
		if(!file_exists($SmallImage)) Gdn_UploadImage::SaveImageAs($Source, $SmallImage, $Height, $Width, False);
		
		if(GetValue('MakeOnly', $Attributes, False, True)) return Url($SmallImage);
		
		
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
