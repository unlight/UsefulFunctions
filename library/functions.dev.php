<?php


/*if (!function_exists('Thumbnail')) {
	function Thumbnail($Source, $Attributes, &$OutData = Null) {
		static $ImPath;
		if ($ImPath === Null) {
			$ImPath = C('Plugins.CssSprites.ImPath', '/usr/local/bin');
			$ImPath = realpath($ImPath);
			if ($ImPath == False) throw new Exception('ImageMagick not found.');
		}
		
		$OutData = Null;
		
		$Options = '';
		$Width = ArrayValue('width', $Attributes);
		$Height = ArrayValue('height', $Attributes);
		$Crop = GetValue('Crop', $Attributes, False, True);
		$Geometry = GetValue('Geometry', $Attributes, False, True);
		$TargetFolder = GetValue('TargetFolder', $Attributes, 'uploads/cached', True);
		$ImageQuality = GetValue('ImageQuality', $Attributes, False, True);
		//$Unsharp = GetValue('Unsharp', $Attributes, False, True);
		//if ($Unsharp) $Options .= ' -unsharp ' . $Unsharp;
		
		// $Height && $Width required
		if ($Crop === True) $Geometry = "\"{$Width}x{$Height}^\" -crop {$Width}x{$Height}+0+0 +repage";
		if (!$Geometry) $Geometry = $Width.'x'.$Height;
		// scale% 			Height and width both scaled by specified percentage.
		// scale-x%xscale-y% 	Height and width individually scaled by specified percentages. (Only one % symbol needed.)
		// width 			Width given, height automagically selected to preserve aspect ratio.
		// xheight 			Height given, width automagically selected to preserve aspect ratio.
		// widthxheight 	Maximum values of height and width given, aspect ratio preserved.
		// widthxheight^ 	Minimum values of width and height given, aspect ratio preserved.
		// widthxheight! 	Width and height emphatically given, original aspect ratio ignored.
		// widthxheight> 	Change as per widthxheight but only if an image dimension exceeds a specified dimension.
		// widthxheight< 	Change dimensions only if both image dimensions exceed specified dimensions.
		// area@ 			Resize image to have specified area in pixels. Aspect ratio is preserved.
		if (is_numeric($ImageQuality)) $ImageQuality = '-quality ' . Clamp($ImageQuality, 1, 100);
		
		if (!is_dir($TargetFolder)) mkdir($TargetFolder, 0777, True);
		$Hash = Crc32Value($Source, $Width, $Height, $Crop, $Geometry, $ImageQuality);
		$Filename = pathinfo($Source, 8);
		$Extension = pathinfo($Source, 4);
		
		$ResultImage = GenerateCleanTargetName($TargetFolder, $Filename.'-'.$Hash, $Extension, False, True);
		
		if (!file_exists($ResultImage)) {
			$Source = ltrim($Source, '/');
			// If source is URL, putting image to temp directory and then converting it
			if (!is_file($Source)) {
				if (!filter_var($Source, FILTER_VALIDATE_URL)) throw new Exception("`$Source` not found or incorrect.");
				$T = tempnam(realpath(sys_get_temp_dir()), '');
				file_put_contents($T, file_get_contents($Source));
				$Source = $T;
			}
			
			$Out = $ReturnValue = Null;
			$Cmd = "{$ImPath}/convert $Source -thumbnail {$Geometry} {$Options} {$ImageQuality} $ResultImage";
			$ExecuteResult = exec($Cmd, $Out, $ReturnValue);
			if ($ReturnValue !== 0) 
				trigger_error(ErrorMessage('Cannot create thumbnail image.', 'PHP', __FUNCTION__, $Cmd), E_USER_ERROR);
		}
		
		if (GetValue('OutOriginalImageSize', $Attributes, False, True)) {
			$Return = array();
			$OutData['ImageSize'] = GetImageSize($Source);
		}
		if (GetValue('Img', $Attributes, False, True)) {
			TouchValue('alt', $Attributes, $Filename);
			$ResultImage = Img($ResultImage, $Attributes);
		}
		
		return $ResultImage;
	}
}*/

// string
/*	

	
	*/


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

