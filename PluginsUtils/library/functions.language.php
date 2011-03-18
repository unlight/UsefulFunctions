<?php

/**
* Gets currect locale language;
* Example: Locale: en-CA, returns "en".
*/

function LocaleLanguageCode() {
	$T = SplitString(Gdn::Locale()->Current(), '/[_-]/');
	return ArrayValue(0, $T, 'en');
}



function GoogleTranslate($Text, $Options = False) {
	static $LanguageCode;
	if (is_null($LanguageCode)) $LanguageCode = LocaleLanguageCode();
	$ResetCache = ArrayValue('ResetCache', $Options, False);
	$From = ArrayValue('From', $Options, $LanguageCode);
	$To = ArrayValue('To', $Options, $LanguageCode);
	
	$String = rawurlencode($Text);
	if (!LoadExtension('curl')) throw new Exception('You need to load/activate the cURL extension (http://www.php.net/cURL).');
	$Resource = curl_init();
	$Protocol = (GetValue('HTTPS', $_SERVER) == 'on') ? 'https://' : 'http://';
	$Host = GetValue('HTTP_HOST', $_SERVER, 'google.com');
	$Referer = $Protocol.$Host;
	curl_setopt($Resource, CURLOPT_URL, "http://ajax.googleapis.com/ajax/services/language/translate?v=1.0&q={$String}&langpair={$From}%7C{$To}");
	curl_setopt($Resource, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($Resource, CURLOPT_REFERER, $Referer);
	$Body = curl_exec($Resource);
	curl_close($Resource);
	$TranslatedText = GetValueR('responseData.translatedText', json_decode($Body));
	return $TranslatedText;
}

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

/**
* Translate arguments passed to function;
* Returns associative array: T(Value) => Value;
* Usefull for dropdown form menu / enum fields.
*/

function LocalizedOptions($Options) {
	if (is_string($Options)) $Options = func_get_args();
	if (is_array($Options)) $Options = array_combine($Options, array_map('T', $Options));
	return $Options;
}

/**
* Shortening for translate message with parameters.
* Example: sprintf(T('Hello %1$s, today is %2$s!'), 'John', 'Tuesday');
* Same as: LocalizedMessage('Hello %1$s, today is %2$s', 'John', 'Tuesday');
*/

function LocalizedMessage() {
	$Args = func_get_args();
	$Args[0] = Gdn::Translate($Args[0]);
	return call_user_func_array('sprintf', $Args);
}