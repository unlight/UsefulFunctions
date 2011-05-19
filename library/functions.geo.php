<?php

/**
* @function     GetGeoCoords
* @description  Gets GeoCoords by calling the Google Maps geoencoding API
*/
if (!function_exists('GetGeoCoords')) {
	function GetGeoCoords($Address, $Name = False) {
		//$Address = utf8_encode($Address);
		// call geoencoding api with param json for output
		$GeoCodeURL = "http://maps.google.com/maps/api/geocode/json?address=".urlencode($Address)."&sensor=false";
		$Result = json_decode(file_get_contents($GeoCodeURL));
		$Status = $Result->status;
		if ($Status == 'OK') $Result = $Result->results[0];
		if ($Name !== False) $Result = GetValueR($Name, $Result);
		return $Result;
	}
}
