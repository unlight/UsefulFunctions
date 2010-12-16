<?php

if (!function_exists('MarkdownHtml')) {
	function MarkdownHtml($S) {
		$S = str_replace("\r", '', $S);
		preg_match_all('~\^\^(.+?)(\n\n|$)~s', $S, $Matches);
		if (isset($Matches[1][0])) {
			foreach($Matches[0] as $N => $Garbage){
				$Images = SplitString($Matches[1][$N], "\n");
				$Height = GetValue(0, $Images);
				if (!is_numeric($Height)) {
					$bDefaultSet = False;
					switch($Height) {
						case 'tiny': $Height = 240; break;
						case 'small': $Height = 320; break;
						case 'normal': $Height = 400; break;
						case 'big': $Height = 500; break;
						case 'huge': $Height = 600; break;
						default: 
							$Height = 350;
							$bDefaultSet = True;
					}
					if (!$bDefaultSet) unset($Images[0]);
				} else unset($Images[0]);
				
				$Html = '';
				foreach ($Images as $Image) {
					$Attributes = array('height' => $Height, 'OutOriginalImageSize' => True);
					$SmallImage = Thumbnail($Image, $Attributes, $OutData);
					
					list($OriginalWidth, $OriginalHeight) = GetValue('ImageSize', $OutData);
					
					$AnchorAttributes = array('class' => $OriginalWidth.'x'.$OriginalHeight);
					$Html .= Anchor(Img($SmallImage), $Image, '', $AnchorAttributes, True);
				}
				$Html = Wrap($Html, 'div', array('class' => 'PopupImages'));
				$S = str_replace($Garbage, $Html, $S);
			}
		}
		
		if (!function_exists('Markdown')) require_once(PATH_LIBRARY.DS.'vendors'.DS.'markdown'.DS.'markdown.php');
		$S = Markdown($S);
		$HtmlFormatter = Gdn::Factory('HtmlFormatter');
		$S = $HtmlFormatter->Format($S);
		return $S;
	}
}

// Gdn_Format::To($FileSize, 'Size')
if (!function_exists('Size')) {
	function Size($Bytes, $Precision = 2) {
		return Gdn_Format::Bytes($Bytes, $Precision);
	}
}

/**
* Writes an HTML5 video tag.
*/
if (!function_exists('Video')) {
	function Video($Source, $Attributes = array()) {
		static $DefaultAttributes = array('width' => 640, 'height' => 360, 'poster' => '', 'autoplay' => False, 'controls' => 'controls');
		static $VideoType = array('3gp' => '3gpp', 'ogv' => 'ogg', 'mkv' => 'x-matroska', 'm4v' => 'mp4');
		//static $Codecs = array('mp4' => 'avc1.42E01E, mp4a.40.2', 'webm' => 'vp8, vorbis', 'ogg' => 'theora, vorbis');
		static $Codecs;
		$Source = SplitString($Source);
		$StoreDirectory = trim(GetValue('StoreDirectory', $Attributes, '', True), '/');
		foreach($Source as $Src) {
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
		if($Poster && (GetValue('SizeOfPoster', $Attributes, False, True) || !array_key_exists('width', $Attributes))) {
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

if (!function_exists('AltClass')) {
	function AltClass() {
		static $i = 0;
		$AltClass = $i++ % 2 ? 'Alt' : '';
		return Attribute('class', $AltClass);
	}
}

// echo '<tr'.Attribute('class', Alt('Alt', '')).'>';
if (!function_exists('Alt')) {
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
if (!function_exists('AltAttribute')) {
	function AltAttribute($Name) {
		static $i;
		$Arguments = func_get_args();
		$Name = array_shift($Arguments);
		$NumArguments = count($Arguments);
		if ($NumArguments > 0) {
			$Value = $Arguments[($i++ % $NumArguments)];
			return Attribute($Name, $Value);
		}
		$i = 0;
		return '';
	}
}

/**
* Writes code for flash movie <object>..</object> for IE, <embed>...</embed> for others.
*/
if (!function_exists('FlashHtml')) {
	function FlashHtml($Movie, $Attributes = array(), $Params = array(), $FlashVars = False) {
		// TODO: We can get width/height by GetImageSize()
		static $DefaultAttributes = array('width' => 400, 'height' => 300, 'type' => 'application/x-shockwave-flash');
		static $DefaultParams = array('allowfullscreen' => 'true', 'allowscriptaccess' => 'always', 'quality' => 'best', 'menu' => 'false');
		// BUG: 'wmode' => 'transparent'
		
		$ScriptRender = GetValue('ScriptRender', $Attributes, False, True);

		if (!is_array($Params)) $Params = array();
		$Params = array_merge($DefaultParams, $Params);
		$Movie = Asset($Movie, True);
		
		// check size
		if (!array_key_exists('width', $Attributes) || !array_key_exists('height', $Attributes)) {
			$ImageInfo = GetImageSize($Movie);
			if ($ImageInfo != False) {
				$Attributes['width'] = $ImageInfo[0];
				$Attributes['height'] = $ImageInfo[1];
			}
		}
		
		$Attributes = array_merge($DefaultAttributes, $Attributes);
		
		$FlashVars = GetValue('FlashVars', $Attributes, $FlashVars, True);
		if ($FlashVars != False) {
			$FlashVars = Gdn_Format::ObjectAsArray($FlashVars);
			$Vars = array();
			foreach($FlashVars as $Name => $Value) $Vars[] = $Name.'='.$Value; // encodeuricomponent
			$Params['flashvars'] = implode('&', $Vars);
		}
		$MSIE = (strpos(ArrayValue('HTTP_USER_AGENT', $_SERVER), 'MSIE') > 0);

		if ($MSIE != False) {
			$Mode = GetValue('wmode', $Attributes, False, True);
			
			if ($Mode !== False) $Params['wmode'] = $Mode;
			$Params['movie'] = $Movie;
			$ObjectParams = '';
			foreach($Params as $Name => $Value) $ObjectParams .= '<param name="'.$Name.'" value="'.$Value.'" />';
			// TODO: ADD CLASSID FOR IE
			$Result = '<object'.Attribute($Attributes).'>'.$ObjectParams.'</object>';
		} else {
			$Attributes['src'] = $Movie;
			$Attributes = array_merge($Attributes, $Params);
			$Result = '<embed'.Attribute($Attributes).' />';
		}

		if ($ScriptRender) $Result = JavaScript($Result, True);

		// detect flash version you should manually
		return $Result;
	}
}

if (!function_exists('JavaScript')) {
	function JavaScript($Mixed, $bWrite = True) {
		if (is_string($Mixed)) {
			//"\n" => '',
			$Replace = array("\r" => '', '=' => '\=', '</' => '<\/', "'" => "\'");
			$Mixed = str_replace(array_keys($Replace), array_values($Replace), $Mixed);
			$Mixed = explode("\n", $Mixed);
			$Mixed = array_filter($Mixed);
		}
		$Return[] = '<script type="text/javascript">//<![CDATA[';
		if ($bWrite) foreach($Mixed as $S) $Return[] = sprintf("document.writeln('%s');", $S);
		else foreach ($Mixed as $S) $Return[] = $S.';';
		$Return[] = '//]]></script>';
		return implode("\n", $Return);
	}
}

if (!function_exists('NoIndex')) {
	function NoIndex($String) {
		$String = JavaScript($String, True);
		$String = "<!-- This text shouldn't be indexed //-->\n" . $String;
		return $String;
	}
}


