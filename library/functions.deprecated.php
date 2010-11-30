<?php

if (!function_exists('BunchCollection')) {
	function BunchCollection($Collection, $Key) {
		if (defined('DEBUG')) 
			trigger_error('BunchCollection() is deprecated. Use GroupByKey().', E_USER_DEPRECATED);
	}
}

if(!function_exists('ConsolidateDataSetValues')) {
	function ConsolidateDataSetValues($Array, $Options, $ValueKey = Null) {
		$Result = array();
		if (is_string($Options) && substr($Options, 0, 1) == '{') $Options = json_decode($Options);
		if (is_scalar($Options)) $Options = array('Key' => $Options);
		$Key = GetValue('Key', $Options);
		$ValueKey = GetValue('ValueKey', $Options, $ValueKey);

		foreach ($Array as $Index => $Data) {
			$N = GetValue($Key, $Data);
			if($ValueKey == 'full') {
				if (defined('DEBUG')) 
					trigger_error('ConsolidateDataSetValues() is deprecated. Use GroupByKey() instead.', E_USER_DEPRECATED);
				$Result[$N][] = $Data;
			}
			elseif($ValueKey == 'unique') {
				if (defined('DEBUG')) 
					trigger_error('ConsolidateDataSetValues() is deprecated. Use PromoteKey() instead.', E_USER_DEPRECATED);
				$Result[$N] = $Data;
			}
			elseif($ValueKey != '') {
				$Result[$N] = GetValue($ValueKey, $Data);
				if (defined('DEBUG')) 
					trigger_error('ConsolidateDataSetValues() is deprecated. Use ConsolidateArrayValuesByKey() instead.');
			} else {
				$Result[] = $N;
				if (defined('DEBUG')) 
					trigger_error('ConsolidateDataSetValues() is deprecated. Use ConsolidateArrayValuesByKey() instead.');
			}

		}
		return $Result;
	}
}

if (!function_exists('GroupArrayByKey')) {
	function GroupArrayByKey($Array, $Key, $ValueKey = '', $AssociativeArrayValueKey = '', $DefaultValue = False) {
		if (defined('DEBUG')) 
			trigger_error('GroupArrayByKey() is deprecated. Use GroupByKey() instead.', E_USER_DEPRECATED);
		$Return = array();
		foreach($Array as $Index => $AssociativeArray){
			if(!array_key_exists($Key, $AssociativeArray)) continue;
			if($ValueKey === '') $Return[] = $AssociativeArray[$Key];
			elseif($ValueKey === 0){
				$K = GetValue($Key, $AssociativeArray);
				// Full Array
				$Return[$K][] = $AssociativeArray;
			}elseif($ValueKey === True){ // unique
				$Return[$AssociativeArray[$Key]] = $AssociativeArray;
			}
			elseif(array_key_exists($ValueKey, $AssociativeArray))
				$Return[$AssociativeArray[$Key]][] = $AssociativeArray[$ValueKey];
			else $Return[$AssociativeArray[$Key]] = $DefaultValue;
		}
		return $Return;
	}
}

