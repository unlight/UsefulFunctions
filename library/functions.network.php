<?php

/**
* Gets/converts IP-address (numeric format/dot format)
*/
if (!function_exists('RealIpAddress')) {
	function RealIpAddress($Ip = Null) {
		if (is_null($Ip)) {
			foreach(array('HTTP_CLIENT_IP','HTTP_X_FORWARDED_FOR','HTTP_X_FORWARDED','HTTP_X_CLUSTER_CLIENT_IP','HTTP_FORWARDED_FOR','HTTP_FORWARDED','REMOTE_ADDR') as $Key) {
				if (isset($_SERVER[$Key])) {
					list ($Ip) = explode(',', $_SERVER[$Key]);
					break;
				}
			}
		}
		if (!$Ip) return $Ip;
		return (is_numeric($Ip)) ? long2ip($Ip) : ip2long($Ip);
	}
}


if (!function_exists('IsOnline')) {
	function IsOnline() {
		return is_int(ip2long(gethostbyname('google.com')));
	}
}

if (!function_exists('CheckIpMask')) {
	function CheckIpMask($MaskIp, $RemoteAddr = False) {
		if($RemoteAddr === False) $RemoteAddr = $_SERVER['REMOTE_ADDR'];
		list($Ip, $MaskBit) = explode('/', $MaskIp);
		$IpLong = ip2long($Ip) >> (32 - $MaskBit);
        $SelfIpLong = ip2long($RemoteAddr) >> (32 - $MaskBit);
        return ($SelfIpLong == $IpLong);
	}
}


/**
* Get MX records corresponding to a given Internet host name;
* For Windows.
*/ 

if (!function_exists ('getmxrr')) {
	// This script was writed by Setec Astronomy - setec@freemail.it
	function getmxrr($hostname = '', &$mxhosts, &$weight = array()) {
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
		while (list($key, $value) = each($nslookup)) {
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
