<?php

if (!function_exists('GetGeoCoords')) {
	/**
	* Gets GeoCoords by calling the Google Maps geoencoding API. 
	* 
	* @param mixed $Address.
	* @param mixed $Name.
	* @return mixed $Result.
	*/
	function GetGeoCoords($Address, $Name = False) {
		//$Address = utf8_encode($Address);
		// call geoencoding api with param json for output
		$GeoCodeURL = "http://maps.google.com/maps/api/geocode/json?";
		$Data["address"] = $Address;
		$Data["sensor"] = "false";
		$GeoCodeURL .= http_build_query($Data);
		$Result = json_decode(file_get_contents($GeoCodeURL));
		$Status = $Result->status;
		if ($Status == 'OK') $Result = $Result->results[0];
		if ($Name !== False) $Result = GetValueR($Name, $Result);
		else {
			$Result = GetValueR('geometry.location', $Result);
			if ($Result) {
				$Result = $Result->lng . ' ' . $Result->lat;
			}
		}
		return $Result;
	}
}


if (!function_exists('YmapGeoCoordinates')) {
	/**
	 * Returns longtintude and latitude of $Address.
	 * @param string $Address Adress which need to get coordinates.
	 */
	function YmapGeoCoordinates($Address, $Options = FALSE) {
		$Raw = ($Options === TRUE);
		$Data = array();
		$Data['geocode'] = $Address;
		$Url = 'http://geocode-maps.yandex.ru/1.x/?' . http_build_query($Data);
		$Content = simplexml_load_string(file_get_contents($Url));
		$Result = json_decode(json_encode($Content), TRUE);
		if ($Raw) {
			return $Result;
		}
		$Result = GetValueR('GeoObjectCollection.featureMember', $Result);
		$Result = GetValueR('GeoObject.Point.pos', $Result);
		return $Result;
	}
}