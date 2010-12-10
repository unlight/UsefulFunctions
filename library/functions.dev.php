<?php


/**
* 
*/

/*if(!function_exists('ArrayMap')){
	function ArrayMap($Array, $Filter = Null, $Callbacks = Null) {
		if(is_array($Filter)){
			foreach($Filter as $Function) $Array = array_filter($Array, $Function);
		}
		if(is_array($Callbacks)){
			foreach($Callbacks as $Function) $Array = array_map($Function, $Array);
		}
		return $Array;
	}
}*/

// by https://github.com/rythie/Useful-PHP-functions
function key_sort(&$array, $attr, $reverse=FALSE, $index_association=TRUE)
{
	//sort an array by a particular array key
	/*
		e.g.
		$in_array = array(array("count" => 4,"value" => "apple"), array("count" => 2,"value" => "orange"), array("count" => 6,"value" => "banana"));
		key_sort($in_array,"count");
		print_r($in_array);

		//outputs...
		Array
		(
		    [1] => Array
				(
					[count] => 2
					[value] => orange
				)

			[0] => Array
				(
					[count] => 4
					[value] => apple
				)

			[2] => Array
				(
					[count] => 6
					[value] => banana
				)
		)

		Optionally $attr can be an array for secondary, third sorting
	*/

	if($reverse)
		$ret_val = 1;
	else
		$ret_val = -1;

	if(!is_array($attr))
		$attr = array($attr); //only one attribute to sort on
	//(when they pass in an array they can do secondary sorts)

	$sort_func = create_function('$a,$b', '
				$keys = unserialize(\'' . serialize($attr) . '\');
				foreach($keys as $key)
				{
					if(isset($a[$key]) && isset($b[$key]))
					{
						if($a[$key] < $b[$key] ) return '.$ret_val.';
						elseif($a[$key] > $b[$key] ) return -('.$ret_val.');
					}
				}
				return 0;
				');

	if(!$index_association)
		usort($array, $sort_func);
	else
		uasort($array, $sort_func);
}

