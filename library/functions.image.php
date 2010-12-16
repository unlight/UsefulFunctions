<?php

/**
* Makes thumbnail image by ImageMagick
*/
if (!function_exists('Thumbnail')) {
	function Thumbnail($Source, $Attributes, &$OutData = Null) {
		static $ImPath;
		if ($ImPath === Null) {
			$ImPath = C('Plugins.CssSprites.ImPath', '/usr/local/bin');
			$ImPath = realpath($ImPath);
			if ($ImPath == False) throw new Exception('ImageMagick not found.');
		}
		
		$OutData = Null;
		$Width = ArrayValue('width', $Attributes);
		$Height = ArrayValue('height', $Attributes);
		$Crop = GetValue('Crop', $Attributes, False, True);
		$Geometry = GetValue('Geometry', $Attributes, False, True);
		$TargetFolder = GetValue('TargetFolder', $Attributes, 'uploads/cached', True);
		
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

		
		if (!is_dir($TargetFolder)) mkdir($TargetFolder, 0777, True);
		$Hash = Crc32Value($Source, $Width, $Height, $Crop, $Geometry);
		$Filename = pathinfo($Source, 8);
		$Extension = pathinfo($Source, 4);
		
		$ResultImage = GenerateCleanTargetName($TargetFolder, $Filename.'-'.$Hash, $Extension, False, True);
		
		if (!file_exists($ResultImage)) {
			
			// If source is URL, putting image to temp directory and then converting it
			if (!is_file($Source)) {
				if (!filter_var($Source, FILTER_VALIDATE_URL)) throw new Exception("`$Source` not found or incorrect.");
				$T = tempnam(realpath(sys_get_temp_dir()), '');
				file_put_contents($T, file_get_contents($Source));
			}
			
			$Out = $ReturnValue = Null;
			$Cmd = "{$ImPath}/convert $Source -thumbnail {$Geometry} $ResultImage";
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
}

if (!function_exists('FancyZoomImage')) {
	function FancyZoomImage($Source, $Attributes = array()) {
		// defaults
		if (!is_array($Attributes)) $Attributes = array();
		$NoHiding = GetValue('NoHiding', $Attributes, '', True);
		$bSaveImage = False;
		$Hash = Crc32Value($Source, $Attributes);
		$Filename = pathinfo($Source, PATHINFO_FILENAME);
		$Extension = pathinfo($Source, PATHINFO_EXTENSION);

		if (!array_key_exists('SmallImage', $Attributes)) {
			// make directory
			$TargetFolder = 'uploads/cached'; // cache directory
			if (!is_dir($TargetFolder)) mkdir($TargetFolder, 0777, True);
			$SmallImage = GenerateCleanTargetName($TargetFolder, $Filename.'-'.$Hash, $Extension, False, True);
			$Attributes['SmallImage'] = $SmallImage;
			if (!file_exists($SmallImage)) $bSaveImage = True;
		}

		// get attributes
		$Width = ArrayValue('width', $Attributes, '');
		$Height = ArrayValue('height', $Attributes, '');
		$Crop = GetValue('Crop', $Attributes, False, True);
		$SmallImage = GetValue('SmallImage', $Attributes, '', True);
		$ZoomAttributes = array('id' => 'p'.$Hash);
		if (!$NoHiding) $ZoomAttributes['style'] = 'display:none';

		//if (!array_key_exists('alt', $Attributes)) $Attributes['alt'] = $Filename;
		TouchValue('alt', $Attributes, $Filename);

		if($bSaveImage) Gdn_UploadImage::SaveImageAs($Source, $SmallImage, $Height, $Width, $Crop);

		$SmallImage = Img($SmallImage, $Attributes);
		$ZoomImage = Img($Source, array('alt' => ArrayValue('alt', $Attributes, '')));
		return "\n" . Wrap($SmallImage, 'a', array('href' => '#p'.$Hash)) . Wrap($ZoomImage, 'div', $ZoomAttributes);
	}
}


// returns <a href="[img]"><img src="[thumb]"/></a>
if (!function_exists('ThumbnailImage')) {
	function ThumbnailImage($Data, $Attributes = False) {

		$Width = ArrayValue('width', $Attributes, '');
		$Height = ArrayValue('height', $Attributes, '');

		if(Is_Array($Data)) {
			// group, todo
			// <ul><li><a></a></li>
		}

		$Prefix = substr($Data, 0, 7);
		//if(In_Array($Prefix, array('http://', 'https:/'))) {}
		//$bLocalImage = False;

		if($Prefix != 'http://') {
			//$bLocalImage = True;
			$IncomingImage = $Data;
			$ImageFindPaths[] = 'uploads'.DS.$Data;
			$ImageFindPaths[] = $Data;
			foreach($ImageFindPaths as $File) {
				if(file_exists($File) && is_file($File)) {
					$IncomingImage = $File;
					break;
				}
			}
		}else{
			$IncomingImage = $Data;
		}

		$CacheDirectory = 'uploads/cached';
		if (!is_writable($CacheDirectory)) {
			mkdir($CacheDirectory, 0777, True);
			if (!is_writable($CacheDirectory)) {
				$ErrorMessage = ErrorMessage(sprintf(T('Directory (%s) is not writable.'), $CacheDirectory), 'PHP', __FUNCTION__);
				trigger_error($ErrorMessage, E_USER_ERROR);
				return '';
			}
		}

		$Name = CleanupString(pathinfo($IncomingImage, PATHINFO_FILENAME) . ' '.$Width.' '.$Height);
		$Extension = FileExtension($IncomingImage);
		$Target = $CacheDirectory. DS . $Name . '.' . $Extension;

		if (!file_exists($Target)) {
			Gdn_UploadImage::SaveImageAs($IncomingImage, $Target, $Height, $Width);
		}

		$Target = str_replace(DS, '/', $Target);
		if (!array_key_exists('alt', $Attributes)) $Attributes['alt'] = pathinfo($Name, PATHINFO_FILENAME);

		List($Width, $Height, $Type) = GetImageSize($IncomingImage);
		$Attributes['alt'] .= sprintf(' (%d×%d)', $Width, $Height);
		$Image = Img($Target, $Attributes);

		return Anchor($Image, Url($IncomingImage), '', '', True);
	}
}