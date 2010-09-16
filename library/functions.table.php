<?php

/*if (!function_exists('OpenTable')) {
	function OpenTable(){
		$Arguments = func_get_args();
		$Headers = array_filter($Arguments, 'is_string');
		$Attributes = current(array_filter($Arguments, 'is_array'));
		$Return = '';
		foreach($Headers as $Header){
			$Return .= Wrap($Header, 'th');
		}
		return Wrap(Wrap($Return, 'thead'), 'table', $Attributes);
	}
}

if (!function_exists('CloseTable')) {
	return '</table>';
}*/