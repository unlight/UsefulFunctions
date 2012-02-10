<?php

if (!function_exists('MontageImage')) {
	/**
	* Use the montage program to create a composite image by combining several separate images.
	* http://www.imagemagick.org/script/montage.php
	*/
	function MontageImage($Sources, $Options) {

		$Source = $Sources[0];
		$Filename = CleanupString(pathinfo($Source, 8)).'-'.Crc32Value($Source, $Options);
		$Extension = CleanupString(pathinfo($Source, 4));
		$ResultImage = 'uploads/cached/'.$Filename.'.'.$Extension;
		
		if (!file_exists($ResultImage)) {
			foreach ($Sources as &$Source) $Source = GetImageSource($Source);
			$ResultSource = implode(' ', $Sources);
			ImageMagick('montage', $ResultSource, $Options, $ResultImage);
		}
		
		return $ResultImage;
	}
}

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
* Geometry:
scale% 	Height and width both scaled by specified percentage.
scale-x%xscale-y% 	Height and width individually scaled by specified percentages. (Only one % symbol needed.)
width 	Width given, height automagically selected to preserve aspect ratio.
xheight 	Height given, width automagically selected to preserve aspect ratio.
widthxheight 	Maximum values of height and width given, aspect ratio preserved.
widthxheight^ 	Minimum values of width and height given, aspect ratio preserved.
widthxheight! 	Width and height emphatically given, original aspect ratio ignored.
widthxheight> 	Change as per widthxheight but only if an image dimension exceeds a specified dimension.
widthxheight< 	Change dimensions only if both image dimensions exceed specified dimensions.
area@ 	Resize image to have specified area in pixels. Aspect ratio is preserved.
*/

if (!function_exists('ConvertImage')) {
	function ConvertImage($Source, $Options = Null) {
	
		if (is_string($Options)) $Options = array('Options' => $Options);
		$TargetFolder = GetValue('TargetFolder', $Options, 'uploads/cached', True);
		$Options = GetValue('Options', $Options);
		
		$Filename = CleanupString(pathinfo($Source, 8)).'-'.Crc32Value($Source, $Options);
		$Extension = CleanupString(pathinfo($Source, 4));
		$ResultImage = $TargetFolder.DS.$Filename.'.'.$Extension;
		
		if (!file_exists($ResultImage)) {
			$Source = GetImageSource($Source);
			ImageMagick('convert', $Source, $Options, $ResultImage);
		}
		
		return $ResultImage;
	}
}
if (!function_exists('GetImageSource')) {
	/**
	* ImageMagick helper function.
	* Checks image. Saves remote file (URL) to directory.
	*/ 
	function GetImageSource($Source) {
		$Source = ltrim($Source, '/');
		if (!is_file($Source)) {
			if (!filter_var($Source, FILTER_VALIDATE_URL)) trigger_error("File '$Source' not found or broken.", E_USER_ERROR);
			$TempFile = tempnam(realpath(sys_get_temp_dir()), '');
			file_put_contents($TempFile, file_get_contents($Source));
			$Source = $TempFile;
		}
		return $Source;
	}
}

if (!function_exists('ImageMagick')) {
	function ImageMagick($Command, $Source, $Options, $ResultImage) {
		static $ImPath;
		if ($ImPath === Null) {
			$ImPath = C('Plugins.UsefulFunctions.ImPath', '/usr/local/bin');
			$ImPath = realpath($ImPath);
			if ($ImPath == False) throw new Exception('ImageMagick not found.');
		}

		$ReturnValue = Null;
		$Out = Null;
		$Cmd = "{$ImPath}/$Command $Source $Options $ResultImage";
		$ExecuteResult = exec($Cmd, $Out, $ReturnValue);
		if ($ReturnValue !== 0) trigger_error(ErrorMessage('Cannot process image.', 'PHP', __FUNCTION__, $Cmd), E_USER_ERROR);

		return $ResultImage;
	}
}


if (!function_exists('Thumbnail')) {
	/**
	* Makes thumbnail image by ImageMagick
	* Use ConvertImage()
	*/
	function Thumbnail($Source, $Attributes, &$OutData = Null) {
		$OutData = Null;
		$Width = ArrayValue('width', $Attributes);
		$Height = ArrayValue('height', $Attributes);
		$Crop = GetValue('Crop', $Attributes, False, True);
		$Geometry = GetValue('Geometry', $Attributes, False, True);
		$TargetFolder = GetValue('TargetFolder', $Attributes, 'uploads/cached', True);
		$ImageQuality = GetValue('ImageQuality', $Attributes, False, True);
		
		// ^ option since 6.3.8-2
		if ($Crop === True) $Geometry = "\"{$Width}x{$Height}^\" -crop {$Width}x{$Height}+0+0 +repage";
		if (!$Geometry) $Geometry = $Width.'x'.$Height;
		if (is_numeric($ImageQuality)) $ImageQuality = '-quality ' . Clamp($ImageQuality, 1, 100);
		
		$Options['Options'] = "-thumbnail {$Geometry} {$ImageQuality}";
		$Options['TargetFolder'] = $TargetFolder;

		if (substr($Source, 0, 9) == '/uploads/') $Source = substr($Source, 1);
		$ResultImage = ConvertImage($Source, $Options);
		
		if (ArrayValue('OutOriginalImageSize', $Attributes, False, True)) {
			$Return = array();
			$OutData['ImageSize'] = GetImageSize($Source);
		}
		
		if (array_key_exists('alt', $Attributes) || GetValue('Img', $Attributes, False, True)) {
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


