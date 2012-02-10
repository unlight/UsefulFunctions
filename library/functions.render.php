<?php

if (!function_exists('NoIndex')) {
	/**
	* This text will be hidden from robots.
	* 
	*/
	function NoIndex($String) {
		$Result = '';
		$Result .= '<input type="hidden" value="'.base64_encode($String).'" class="NoIndex" />';
		return $Result;
	}
}

/**
* Clean HtmlFormatter without mentions, linebreak, etc.
* 
* @param string $String Input string. 
* @return string $String Output string.
*/
if (!function_exists('xHtml')) {
	function xHtml($String) {
		$HtmlFormatter = Gdn::Factory('HtmlFormatter');
		if ($HtmlFormatter) $String = $HtmlFormatter->Format($String);
		else $String = Gdn_Format::Text($String);
		return $String;
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


if (!function_exists('Attr')) {
	/**
	* Parse a selector of the form #foo.bar.baz into constituent ID and classes.
	* An array argument will be returned unchanged.
	* php-helpers by Jason Frame [jason@onehackoranother.com]
	* If second argument is array result array merging with it and returning as string.
	*/
	function Attr($Selector, $Attributes = False) {
		$Result = array();
		preg_match('/^(#([\w-]+))?((\.[\w-]+)*)$/', $Selector, $Matches);
        if (!empty($Matches[2])) $Result['id'] = $Matches[2];
        if (!empty($Matches[3])) $Result['class'] = trim(str_replace('.', ' ', $Matches[3]));
		if (is_array($Attributes)) {
			$Result = array_merge($Attributes, $Result);
			$Result = Attribute($Result);
		}
		return $Result;
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
* Writes object code for swf flash movie
* credit: HtmlPurifier
* credit: http://the-echoplex.net/log/swfobject-for-php
*/
if (!function_exists('FlashObject')) {
	function FlashObject($Movie, $Attributes = array(), $FlashVars = False) {
		//static $Defaults = array('allowfullscreen' => 'true', 'allowscriptaccess' => 'always', 'quality' => 'best', 'menu' => 'false');
		//$Attributes = array_merge($Defaults, $Attributes);
		$ScriptRender = GetValue('ScriptRender', $Attributes, False, True);
		$FlashVars = GetValue('FlashVars', $Attributes, $FlashVars, True);
		$Params = GetValue('Params', $Attributes, array(), True);
		$Movie = Asset($Movie, True);
		
		$AltContent = GetValue('AltContent', $Attributes, 
			Anchor(Img('http://wwwimages.adobe.com/www.adobe.com/images/shared/download_buttons/get_flash_player.gif', array('alt' => 'Get Adobe Flash player')), 'http://www.adobe.com/go/getflashplayer', '', array('rel' => 'nofollow'), True), True);
		
		foreach(array('wmode', 'allowfullscreen', 'allowscriptaccess', 'quality', 'menu') as $Name) {
			$Value = GetValue($Name, $Attributes, False, True);
			if ($Value !== False) $Params[$Name] = $Value;
		}
		
		if (!array_key_exists('width', $Attributes) || !array_key_exists('height', $Attributes)) {
			$ImageInfo = GetImageSize($Movie);
			TouchValue('width', $Attributes, $ImageInfo[0]);
			TouchValue('height', $Attributes, $ImageInfo[1]);
		}
		$Attributes['type'] = 'application/x-shockwave-flash';
		$Attributes['data'] = $Movie;
		
		$HtmlParams = Wrap('', 'param', array('name' => 'movie', 'value' => $Movie));
		foreach ($Params as $Name => $Value) $HtmlParams .= Wrap('', 'param', array('name' => $Name, 'value' => $Value));
		if (is_array($FlashVars)) foreach($FlashVars as $Name => $Value)
			$Variables[] = $Name . '=' . urlencode($Value);
		if (isset($Variables)) $HtmlParams .= Wrap('', 'param', array('name' => 'flashvars', 'value' => implode('&', $Variables)));
		
/*		$Agent = ArrayValue('HTTP_USER_AGENT', $_SERVER);
		if ($Agent != False && stripos($Agent, 'MSIE ') > 0) {
			$Attributes['classid'] = 'clsid:d27cdb6e-ae6d-11cf-96b8-444553540000';
		}*/
		
		$Return = Wrap($HtmlParams.$AltContent, 'object', $Attributes);
		//d($Return);
		
		if ($ScriptRender) $Return = JavaScript($Return, True);

		return $Return;
	}
}

/**
* Writes code for flash movie <object>..</object> for IE, <embed>...</embed> for others.
*/
if (!function_exists('FlashHtml')) {
	function FlashHtml($Movie, $Attributes = array(), $Params = array(), $FlashVars = False) {
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


