<?php

/**
* Convert image by ImageMagick
* http://www.imagemagick.org/script/command-line-processing.php?#geometry
* http://www.imagemagick.org/script/command-line-options.php#crop
* "{$Width}x{$Height}^" -crop {$Width}x{$Height}+0+0 +repage
* -thumbnail {geometry}
* -thumbnail "{Width}x{Height}^" -crop {Width}x{Height}+0+0 +repage
* http://www.imagemagick.org/script/command-line-options.php#unsharp
* -unsharp radiusxsigma{+threshold}
* -thumbnail {geometry} -unsharp 0x0.75
*/
if (!function_exists('СonvertImage')) {
	function СonvertImage($Source, $Options = Null) {
		static $ImPath;
		if ($ImPath === Null) {
			$ImPath = C('Plugins.CssSprites.ImPath', '/usr/local/bin');
			$ImPath = realpath($ImPath);
			if ($ImPath == False) throw new Exception('ImageMagick not found.');
		}
		
		if (is_string($Options)) $Options = array('Options' => $Options);
		$TargetFolder = GetValue('TargetFolder', $Options, 'uploads/cached', True);
		$Options = GetValue('Options', $Options);
		
		$Hash = Crc32Value($Source, $Options);
		$Filename = pathinfo($Source, 8);
		$Extension = pathinfo($Source, 4);
		$ResultImage = GenerateCleanTargetName($TargetFolder, $Filename.'-'.$Hash, $Extension, False, True);
		
		if (!file_exists($ResultImage)) {
			$Source = ltrim($Source, '/');
			if (!is_file($Source)) {
				if (!filter_var($Source, FILTER_VALIDATE_URL)) trigger_error("'$Source' not found or incorrect.", E_USER_ERROR);
				$TempFile = tempnam(realpath(sys_get_temp_dir()), '');
				file_put_contents($TempFile, file_get_contents($Source));
				$Source = $TempFile;
			}
			
			$Out = $ReturnValue = Null;
			$Cmd = "{$ImPath}/convert $Source $Options $ResultImage";
			$ExecuteResult = exec($Cmd, $Out, $ReturnValue);
			if ($ReturnValue !== 0) trigger_error(ErrorMessage('Cannot convert image.', 'PHP', __FUNCTION__, $Cmd), E_USER_ERROR);
		}
		
		return $ResultImage;		
	}
}


/**
* Makes thumbnail image by ImageMagick
* Use СonvertImage()
*/

if (!function_exists('Thumbnail')) {
	function Thumbnail($Source, $Attributes, &$OutData = Null) {
		$OutData = Null;
		$Width = ArrayValue('width', $Attributes);
		$Height = ArrayValue('height', $Attributes);
		$Crop = ArrayValue('Crop', $Attributes, False, True);
		$Geometry = ArrayValue('Geometry', $Attributes, False, True);
		$TargetFolder = ArrayValue('TargetFolder', $Attributes, 'uploads/cached', True);
		$ImageQuality = ArrayValue('ImageQuality', $Attributes, False, True);
		
		if ($Crop === True) $Geometry = "\"{$Width}x{$Height}^\" -crop {$Width}x{$Height}+0+0 +repage";
		if (!$Geometry) $Geometry = $Width.'x'.$Height;
		if (is_numeric($ImageQuality)) $ImageQuality = '-quality ' . Clamp($ImageQuality, 1, 100);
		
		$Options['Options'] = "-thumbnail {$Geometry} {$ImageQuality}";
		$Options['TargetFolder'] = $TargetFolder;
		
		$ResultImage = СonvertImage($Source, $Options);
		
		if (ArrayValue('OutOriginalImageSize', $Attributes, False, True)) {
			$Return = array();
			$OutData['ImageSize'] = GetImageSize($Source);
		}
		
		if (array_key_exists('alt', $Attributes) || ArrayValue('Img', $Attributes, False, True)) {
			TouchValue('alt', $Attributes, pathinfo($ResultImage, PATHINFO_FILENAME));
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