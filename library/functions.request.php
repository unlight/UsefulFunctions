<?php

/**
 * Modified by S.
 */

if (!function_exists('StaticRequest')) {
	function StaticRequest($Env) {
		static $Request;
		if (is_string($Env)) {
			if (!is_null($Request)) {
				foreach (array('Get'.$Env, $Env) as $Method) {
					if (method_exists($Request, $Method)) {
						return $Request->$Method();
					}
				}
			}
			static $Results;
			$Result =& $Results[$Env];
			if (is_null($Result)) {
				$Args = func_get_args();
				$Env = 'Get' . array_shift($Args);
				if (!function_exists($Env)) {
					if ('GetRequestMethod' == $Env) {
						d(debug_backtrace());
					}
					trigger_error("Function ($Env) does not exsts.");
					return FALSE;
				}
				$Result = call_user_func_array($Env, $Args);
			}
			return $Result;
		} elseif (is_object($Env)) {
			if (is_null($Request)) {
				$Request = $Env;
			}
		}
	}
}

if (!function_exists('IsPostBack')) {
	function IsPostBack() {
		return strcasecmp(StaticRequest('Method'), 'post') == 0;
	}
}

if (!function_exists('GetWebRoot')) {
	function GetWebRoot($WithDomain = FALSE) {
		$Result = StaticRequest('BasePath');
		if ($WithDomain) {
			$Result = StaticRequest('SchemeAndHttpHost') . $Result;
		}
		return $Result;
	}
}

if (!function_exists('GetUrl')) {
	function GetUrl($Path = '', $WithDomain = FALSE) {
		if ($Path == '') $Path = StaticRequest('PathInfo');
		if ($WithDomain) {
			$Result = StaticRequest('UriForPath', $Path);
		} else {
			$Result = StaticRequest('BaseUrl') . $Path;
		}
		return $Result;
	}
}

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


/**
 * Prepares the base path.
 *
 * @return string base path
 */
if (!function_exists('GetBasePath')) {
	function GetBasePath()
	{
		$filename = basename(GetValue('SCRIPT_FILENAME', $_SERVER));
		$baseUrl = GetBaseUrl();
		if (empty($baseUrl)) {
			return '';
		}

		if (basename($baseUrl) === $filename) {
			$basePath = dirname($baseUrl);
		} else {
			$basePath = $baseUrl;
		}

		if ('\\' === DIRECTORY_SEPARATOR) {
			$basePath = str_replace('\\', '/', $basePath);
		}

		return rtrim($basePath, '/');
	}
}

if (!function_exists('GetBaseUrl')) {
	/**
	 * Prepares the base URL.
	 *
	 * @return string
	 */
	function GetBaseUrl()
	{
		$filename = basename(GetValue('SCRIPT_FILENAME', $_SERVER));

		if (basename(GetValue('SCRIPT_NAME', $_SERVER)) === $filename) {
			$baseUrl = GetValue('SCRIPT_NAME', $_SERVER);
		} elseif (basename(GetValue('PHP_SELF', $_SERVER)) === $filename) {
			$baseUrl = GetValue('PHP_SELF', $_SERVER);
		} elseif (basename(GetValue('ORIG_SCRIPT_NAME', $_SERVER)) === $filename) {
			$baseUrl = GetValue('ORIG_SCRIPT_NAME', $_SERVER); // 1and1 shared hosting compatibility
		} else {
			// Backtrack up the script_filename to find the portion matching
			// php_self
			$path    = GetValue('PHP_SELF', $_SERVER, '');
			$file    = GetValue('SCRIPT_FILENAME', $_SERVER, '');
			$segs    = explode('/', trim($file, '/'));
			$segs    = array_reverse($segs);
			$index   = 0;
			$last    = count($segs);
			$baseUrl = '';
			do {
				$seg     = $segs[$index];
				$baseUrl = '/'.$seg.$baseUrl;
				++$index;
			} while (($last > $index) && (false !== ($pos = strpos($path, $baseUrl))) && (0 != $pos));
		}

		// Does the baseUrl have anything in common with the request_uri?
		$requestUri = GetRequestUri();

		if ($baseUrl && false !== $prefix = GetUrlencodedPrefix($requestUri, $baseUrl)) {
			// full $baseUrl matches
			return $prefix;
		}

		if ($baseUrl && false !== $prefix = GetUrlencodedPrefix($requestUri, dirname($baseUrl))) {
			// directory portion of $baseUrl matches
			return rtrim($prefix, '/');
		}

		$truncatedRequestUri = $requestUri;
		if (($pos = strpos($requestUri, '?')) !== false) {
			$truncatedRequestUri = substr($requestUri, 0, $pos);
		}

		$basename = basename($baseUrl);
		if (empty($basename) || !strpos(rawurldecode($truncatedRequestUri), $basename)) {
			// no match whatsoever; set it blank
			return '';
		}

		// If using mod_rewrite or ISAPI_Rewrite strip the script filename
		// out of baseUrl. $pos !== 0 makes sure it is not matching a value
		// from PATH_INFO or QUERY_STRING
		if ((strlen($requestUri) >= strlen($baseUrl)) && ((false !== ($pos = strpos($requestUri, $baseUrl))) && ($pos !== 0))) {
			$baseUrl = substr($requestUri, 0, $pos + strlen($baseUrl));
		}

		return rtrim($baseUrl, '/');
	}
}

if (!function_exists('GetRequestUri')) {
	/*
	 * The following methods are derived from code of the Zend Framework (1.10dev - 2010-01-24)
	 *
	 * Code subject to the new BSD license (http://framework.zend.com/license/new-bsd).
	 *
	 * Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
	 */

	function GetRequestUri()
	{
		$requestUri = '';

		if (GetValue('HTTP_X_ORIGINAL_URL', $_SERVER) && false !== stripos(PHP_OS, 'WIN')) {
			// IIS with Microsoft Rewrite Module
			$requestUri = GetValue('HTTP_X_ORIGINAL_URL', $_SERVER);
		} elseif (GetValue('HTTP_X_REWRITE_URL', $_SERVER) && false !== stripos(PHP_OS, 'WIN')) {
			// IIS with ISAPI_Rewrite
			$requestUri = GetValue('HTTP_X_REWRITE_URL', $_SERVER);
		} elseif (GetValue('IIS_WasUrlRewritten', $_SERVER) == '1' && GetValue('UNENCODED_URL', $_SERVER) != '') {
			// IIS7 with URL Rewrite: make sure we get the unencoded url (double slash problem)
			$requestUri = GetValue('UNENCODED_URL', $_SERVER);
		} elseif (GetValue('REQUEST_URI', $_SERVER)) {
			$requestUri = GetValue('REQUEST_URI', $_SERVER);
			// HTTP proxy reqs setup request uri with scheme and host [and port] + the url path, only use url path
			$schemeAndHttpHost = GetSchemeAndHttpHost();
			if (strpos($requestUri, $schemeAndHttpHost) === 0) {
				$requestUri = substr($requestUri, strlen($schemeAndHttpHost));
			}
		} elseif (GetValue('ORIG_PATH_INFO', $_SERVER)) {
			// IIS 5.0, PHP as CGI
			$requestUri = GetValue('ORIG_PATH_INFO', $_SERVER);
			if ('' != GetValue('QUERY_STRING', $_SERVER)) {
				$requestUri .= '?'.GetValue('QUERY_STRING', $_SERVER);
			}
		}

		return $requestUri;
	}
}

if (!function_exists('IsSecure')) {

	/**
	 * Checks whether the request is secure or not.
	 *
	 * This method can read the client port from the "X-Forwarded-Proto" header
	 * when trusted proxies were set via "setTrustedProxies()".
	 *
	 * The "X-Forwarded-Proto" header must contain the protocol: "https" or "http".
	 *
	 * If your reverse proxy uses a different header name than "X-Forwarded-Proto"
	 * ("SSL_HTTPS" for instance), configure it via "setTrustedHeaderName()" with
	 * the "client-proto" key.
	 *
	 * @return Boolean
	 *
	 * @api
	 */
	function IsSecure()
	{
		return 'on' == strtolower(GetValue('HTTPS', $_SERVER)) || 1 == GetValue('HTTPS', $_SERVER);
	}
}

if (!function_exists('GetSchemeAndHttpHost')) {
	/**
	 * Gets the scheme and HTTP host.
	 *
	 * If the URL was called with basic authentication, the user
	 * and the password are not added to the generated string.
	 *
	 * @return string The scheme and HTTP host
	 */
	function GetSchemeAndHttpHost()
	{
		return GetScheme().'://'.GetHttpHost();
	}
}

	
if (!function_exists('GetScheme')) {
	/**
	 * Gets the request's scheme.
	 *
	 * @return string
	 *
	 * @api
	 */
	function GetScheme()
	{
		return IsSecure() ? 'https' : 'http';
	}
}

if (!function_exists('GetHttpHost')) {
	/**
	 * Returns the HTTP host being requested.
	 *
	 * The port name will be appended to the host if it's non-standard.
	 *
	 * @return string
	 *
	 * @api
	 */
	function GetHttpHost()
	{
		$scheme = GetScheme();
		$port   = GetValue('SERVER_PORT', $_SERVER);

		if (('http' == $scheme && $port == 80) || ('https' == $scheme && $port == 443)) {
			return GetHost();
		}

		return GetHost().':'.$port;
	}
}    
	
if (!function_exists('GetHost')) {
	/**
	 * Returns the host name.
	 *
	 * This method can read the client port from the "X-Forwarded-Host" header
	 * when trusted proxies were set via "setTrustedProxies()".
	 *
	 * The "X-Forwarded-Host" header must contain the client host name.
	 *
	 * If your reverse proxy uses a different header name than "X-Forwarded-Host",
	 * configure it via "setTrustedHeaderName()" with the "client-host" key.
	 *
	 * @return string
	 *
	 * @throws \UnexpectedValueException when the host name is invalid
	 *
	 * @api
	 */
	function GetHost()
	{
		if (!$host = GetValue('HTTP_HOST', $_SERVER)) {
			if (!$host = GetValue('SERVER_NAME', $_SERVER)) {
				$host = GetValue('SERVER_ADDR', $_SERVER, '');
			}
		}

		// trim and remove port number from host
		// host is lowercase as per RFC 952/2181
		$host = strtolower(preg_replace('/:\d+$/', '', trim($host)));

		// as the host can come from the user (HTTP_HOST and depending on the configuration, SERVER_NAME too can come from the user)
		// check that it does not contain forbidden characters (see RFC 952 and RFC 2181)
		if ($host && !preg_match('/^\[?(?:[a-zA-Z0-9-:\]_]+\.?)+$/', $host)) {
			throw new Exception('Invalid Host');
		}

		return $host;
	}
}    
	
if (!function_exists('GetUrlencodedPrefix')) {
	
	/*
	 * Returns the prefix as encoded in the string when the string starts with
	 * the given prefix, false otherwise.
	 *
	 * @param string $string The urlencoded string
	 * @param string $prefix The prefix not encoded
	 *
	 * @return string|false The prefix as it is encoded in $string, or false
	 */
	function GetUrlencodedPrefix($string, $prefix)
	{
		if (0 !== strpos(rawurldecode($string), $prefix)) {
			return false;
		}

		$len = strlen($prefix);

		if (preg_match("#^(%[[:xdigit:]]{2}|.){{$len}}#", $string, $match)) {
			return $match[0];
		}

		return false;
	}
}

if (!function_exists('GetPathInfo')) {
	/**
	 * Prepares the path info.
	 *
	 * @return string path info
	 */
	function GetPathInfo()
	{
		$baseUrl = GetBaseUrl();

		if (null === ($requestUri = GetRequestUri())) {
			return '/';
		}

		$pathInfo = '/';

		// Remove the query string from REQUEST_URI
		if ($pos = strpos($requestUri, '?')) {
			$requestUri = substr($requestUri, 0, $pos);
		}

		if ((null !== $baseUrl) && (false === ($pathInfo = substr($requestUri, strlen($baseUrl))))) {
			// If substr() returns false then PATH_INFO is set to an empty string
			return '/';
		} elseif (null === $baseUrl) {
			return $requestUri;
		}

		return (string) $pathInfo;
	}
}


if (!function_exists('GetMethod')) {
    /**
     * Gets the request "intended" method.
     *
     * If the X-HTTP-Method-Override header is set, and if the method is a POST,
     * then it is used to determine the "real" intended HTTP method.
     *
     * The _method request parameter can also be used to determine the HTTP method,
     * but only if enableHttpMethodParameterOverride() has been called.
     *
     * The method is always an uppercased string.
     *
     * @return string The request method
     *
     * @api
     *
     * @see getRealMethod
     */
    function GetMethod()
    {
        $method = strtoupper(GetValue('REQUEST_METHOD', $_SERVER, 'GET'));

        if ('POST' === $method) {
            if (GetValue('X-HTTP-METHOD-OVERRIDE', $_SERVER)) {
            	$method = GetValue('X-HTTP-METHOD-OVERRIDE', $_SERVER);
                $method = strtoupper($method);
            }
        }

        return $method;
    }
    
}