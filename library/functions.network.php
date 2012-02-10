<?php

if (!function_exists('ClientRequest')) {
	/**
	* Perform client request to server.
	* Options: see here http://www.php.net/manual/en/function.curl-setopt.php
	* Bool options: 
	* 	ReturnTransfer, Post, FollowLocation, Header
	* Integer options: 
	* 	ConnectTimeout, Timeout, Timeout_Ms
	* Other options: 
	* 	Url, Cookie, CookieFile, CustomRequest, PostFields, Referer, UserAgent, UserPwd
	* 
	* @param mixed $Url or array $Options.
	* @return mixed $Result.
	*/
	function ClientRequest($Url, $Options = False) {
		static $Connections = array();
		if (!is_array($Url)) $Options['Url'] = $Url;
		
		$Url = GetValue('Url', $Options, False, True);
		$GetInfo = GetValue('GetInfo', $Options, False, True);
		TouchValue('ReturnTransfer', $Options, True);
		
		if (!array_key_exists($Url, $Connections)) $Connections[$Url] = curl_init($Url); 
		$Connection =& $Connections[$Url];
		
		foreach ($Options as $Option => $Value) {
			$Constant = 'CURLOPT_' . strtoupper($Option);
			if (!defined($Constant)) trigger_error('cURL. Unknown option: ' . $Constant);
			curl_setopt($Connection, constant($Constant), $Value);
		}

		$Result = curl_exec($Connection);
		if ($Result === False) {
			$ErrorMessage = curl_error($Connection);
			trigger_error($ErrorMessage);
			return False;
		}
		if ($GetInfo) {
			$Result = array('Result' => $Result);
			$Result['Info'] = curl_getinfo($Connection);
		}
		return $Result;
	}

}

if (!function_exists('RealIpAddress')) {
	/**
	* Gets/converts IP-address (numeric format/dot format).
	* 
	* @param mixed $Ip.
	* @return mixed $Ip, converted or gotten numeric IP.
	*/
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
		return (is_numeric($Ip)) ? long2ip($Ip) : sprintf('%u', ip2long($Ip));
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

if (!function_exists ('getmxrr')) {
	/**
	* Get MX records corresponding to a given Internet host name for Windows.
	* 
	* @see http://www.php.net/manual/en/function.getmxrr.php
	* @credits This script was writed by Setec Astronomy - setec@freemail.it
	*/
	function getmxrr($hostname = '', &$mxhosts, &$weight = array()) {
		$weight = array();
		$mxhosts = array();
		$result = false;

		$command = 'nslookup -type=mx ' . escapeshellarg($hostname);
		exec($command, $result);
		$i = 0;
		$nslookup = array();
		while (list($key, $value) = each($result)) {
			if (strstr($value, 'mail exchanger')) {
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

		foreach ($mx as $value) {
			$mxhosts[] = $value[1];
			$weight[] = $value[0];
		}

		return count($mxhosts) > 0;
	}
}
