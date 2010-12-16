<?php

/**
* ConvertImage by ImageMagick
*/
if (!function_exists('ImgThumbnail')) {
	function ImgThumbnail($Source, $Attributes) {
		static $ImPath;
		if ($ImPath === Null) $ImPath = C('Plugins.CssSprites.ImPath', '/usr/local/bin/');
		
		$Width = ArrayValue('width', $Attributes);
		$Height = ArrayValue('height', $Attributes);
		$Crop = GetValue('Crop', $Attributes, False, True);
		$Geometry = GetValue('Geometry', $Attributes, False, True);
		$TargetFolder = GetValue('TargetFolder', $Attributes, 'uploads/cached');
		
		if ($Geometry == False) {
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
			$Geometry = $Width.'x'.$Height;
		}
		
		if (!is_dir($TargetFolder)) mkdir($TargetFolder, 0777, True);
		$Hash = Crc32Value($Source, array($Width, $Height, $Crop, $Geometry));
		$Filename = pathinfo($Source, 8);
		$Extension = pathinfo($Source, 4);
		
		$ResultImage = GenerateCleanTargetName($TargetFolder, $Filename.'-'.$Hash, $Extension, False, True);
		
		if (!file_exists($ResultImage)) {
			$Cmd = "{$ImPath}convert $Source -thumbnail {$Geometry} $ResultImage";
			exec($Cmd, $Out, $Rar);
			//if (count($Rar) > 0) errro
		}
		
		if (GetValue('OutOriginalImageSize', $Attributes, False, True)) {
			$Return = array();
			$Return['ImageSize'] = getimagesize($Source);
			$Return['Result'] = $ResultImage;
			return $Return;
		}
		
		return $ResultImage;
	}
}


if (!function_exists('SmallImage')) {
	function SmallImage($Source, $Attributes = array()) {
		
		$Width = ArrayValue('width', $Attributes, '');
		$Height = ArrayValue('height', $Attributes, '');
		$ImageQuality = GetValue('ImageQuality', $Attributes, 100, True);
		$Crop = GetValue('Crop', $Attributes, False, True);
		
		$Hash = Crc32Value($Source, array($Width, $Height, $ImageQuality, $Crop));
		$TargetFolder = 'uploads/cached'; // cache directory
		if (!is_dir($TargetFolder)) mkdir($TargetFolder, 0777, True);
		$Filename = pathinfo($Source, 8);
		$Extension = pathinfo($Source, 4);
		$SmallImage = GenerateCleanTargetName($TargetFolder, $Filename.'-'.$Hash, $Extension, False, True);
		if (!file_exists($SmallImage)) Gdn_UploadImage::SaveImageAs($Source, $SmallImage, $Height, $Width, $Crop);

		if (GetValue('MakeOnly', $Attributes, False, True)) {
			if (GetValue('OutOriginalImageSize', $Attributes, False, True)) { // TEMP, TODO: FIX ME
				$Return = array();
				$Return['ImageSize'] = getimagesize($Source);
				$Return['Result'] = Url($SmallImage);
				return $Return;
			}

			return Url($SmallImage);
		}
		
		
		TouchValue('alt', $Attributes, $Filename);
		// Fail. ImageSY expects parameter 1 to be resource
		//if (!array_key_exists('height', $Attributes)) TouchValue('height', $Attributes, ImageSY($SmallImage));
		//if (!array_key_exists('width', $Attributes)) TouchValue('width', $Attributes, ImageSX($SmallImage));
		return Img($SmallImage, $Attributes);
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