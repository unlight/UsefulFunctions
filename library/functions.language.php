<?php

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