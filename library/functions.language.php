<?php

if (!function_exists('LocaleLanguageCode')) {
	/**
	* Gets current language from defined locale.
	* Example: Locale: en-CA, returns "en".
	* 
	* @return string, default "en". 
	*/
	function LocaleLanguageCode() {
		$T = SplitString(Gdn::Locale()->Current(), '/[_-]/');
		return ArrayValue(0, $T, 'en');
	}
}

if (!function_exists('GoogleTranslate')) {
	function GoogleTranslate($Text, $Options = False) {
		static $LanguageCode;
		if (is_null($LanguageCode)) $LanguageCode = LocaleLanguageCode();
		$ResetCache = ArrayValue('ResetCache', $Options, False);
		$From = ArrayValue('From', $Options, $LanguageCode);
		$To = ArrayValue('To', $Options, $LanguageCode);
		
		$String = rawurlencode($Text);
		$Result = False;

		if (!LoadExtension('curl')) throw new Exception('You need to load/activate the cURL extension (http://www.php.net/cURL).');
		$Resource = curl_init();
		$HTTPS = GetValue('HTTPS', $_SERVER, '');
		$Protocol =  (strlen($HTTPS) || GetValue('SERVER_PORT', $_SERVER) == 443) ? 'https://' : 'http://';
		$Host = GetValue('HTTP_HOST', $_SERVER, 'google.com');
		$Referer = $Protocol.$Host;
		curl_setopt($Resource, CURLOPT_URL, "http://translate.google.com/translate_a/t?client=t&text={$String}&sl={$From}&tl={$To}&ie=UTF-8");
		curl_setopt($Resource, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($Resource, CURLOPT_REFERER, $Referer);
		$Body = curl_exec($Resource);
		// Detect response encoding.
		$ContentType = curl_getinfo($Resource, CURLINFO_CONTENT_TYPE);
		if ($ContentType) {
			preg_match('/charset\=(.+)/', $ContentType, $Match);
			$Charset = $Match[1];
			if ($Charset) $Body = mb_convert_encoding($Body, 'utf-8', $Charset);
		}
		curl_close($Resource);
		$Pos = strpos($Body, ']]');
		if ($Pos !== False) {
			$Body = substr($Body, 1, $Pos + 1);
			$Json = json_decode($Body);
			if ($ErrorMessage = LastJsonErrorMessage()) trigger_error($ErrorMessage);
			$Result = GetValueR('0.0', $Json);
			$Result = html_entity_decode($Result, ENT_QUOTES, 'utf-8');
		}
		return $Result;
	}
}

if (!function_exists('LingvoTranslate')) {
	function LingvoTranslate($Word, $Options = array()) {
		LoadPhpQuery();
		static $Result, $LanguageCode;
		if(is_null($LanguageCode)) $LanguageCode = LocaleLanguageCode();
		$ResetCache = ArrayValue('ResetCache', $Options, False);
		$From = ArrayValue('From', $Options, $LanguageCode);
		$To = ArrayValue('To', $Options, $LanguageCode);

		if (!isset($Result[$Word]) || $ResetCache) {
			$Direction = $From.'-'.$To;
			$Url = 'http://lingvo.abbyyonline.com/en/'.$Direction.'/'.rawurlencode($Word);
			$Doc = PhpQuery::NewDocumentFile($Url);
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
}

if (!function_exists('LocalizedOptions')) {
	/**
	* Translate arguments passed to function.
	* Returns associative array: T(Value) => Value.
	* Usefull for dropdown form menu / enum fields.
	* 
	* @param mixed $Options, array or elements of array.
	* @return array $Options. 
	*/
	function LocalizedOptions($Options) {
		if (is_string($Options)) $Options = func_get_args();
		if (is_array($Options)) $Options = array_combine($Options, array_map('T', $Options));
		return $Options;
	}
}

if (!function_exists('LocalizedMessage')) {
	/**
	* Shortening for translate message with parameters. 
	* Example: sprintf(T('Hello %1$s, today is %2$s!'), 'John', 'Tuesday').
	* Same as: LocalizedMessage('Hello %1$s, today is %2$s', 'John', 'Tuesday').
	* 
	* @param string $Format. 
	* @param mixed $Parameter Parameter1.
	* @param mixed ...
	* @return string Localized string. 
	*/
	function LocalizedMessage($Format, $Parameter = Null) {
		$Args = func_get_args();
		$Args[0] = Gdn::Translate($Args[0]);
		return call_user_func_array('sprintf', $Args);
	}
}