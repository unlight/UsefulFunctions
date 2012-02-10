<?php

if (!function_exists('GetCachedData')) {
	/**
	* Get cached data. 
	* 
	* @param string $Name. 
	* @param mixed $Function callback.
	* @return mixed $Data.
	*/
	function GetCachedData($Name, $Function, $NoCache = False) {
		if (!is_callable($Function)) throw new Exception("Function ($Function) is not callable.");
		$FilePath = PATH_CACHE . '/' . pathinfo($Name, PATHINFO_FILENAME) . '.php';
		if ($NoCache === True) {
			if (file_exists($FilePath)) unlink($FilePath);
			return call_user_func($Function);
		}
		if (!file_exists($FilePath)) {
			$Data = call_user_func($Function);
			$Contents = "<?php if(!defined('APPLICATION')) exit(); \n\$Data = " . var_export($Data, True) . ';';
			file_put_contents($FilePath, $Contents);
		} else {
			include $FilePath;
		}
		return $Data;
	}
}

if (!function_exists('CompileFile')) {
	/**
	* 
	*/
	function CompileFile($File = Null, $bSave = False) {
		static $RequiredFiles = array();
		if (is_null($File)){
			$Return = $RequiredFiles;
			$RequiredFiles = array();
			return $Return;
		}
		if ($bSave != False) {
			$NewFileContent = '';
			$Files = array_values(CompileFile());

			foreach ($Files as $N => $FilePath) {
				$FileData = array_map('rtrim', file($FilePath));
				$Count = count($FileData);
				for ($i = 0; $i < $Count; $i++) {
					$String = $FileData[$i];
					if (strpos($String, 'require') === 0
						|| in_array($String, array('<?php', '?>'))) unset($FileData[$i]);
				}
				$BaseName = pathinfo($FilePath, PATHINFO_BASENAME);
				$FirstLine = "\n/* " . str_pad(" $BaseName ", 72, '=', STR_PAD_LEFT) . "*/\n";
				$NewFileContent .= $FirstLine;
				$NewFileContent .= implode("\n", $FileData);
			}
			return file_put_contents($File, "<?php\n".$NewFileContent);
		}

		$RealPath = realpath($File);
		if (!$RealPath) throw new Exception('No such file '.$File);

		//if (count($RequiredFiles) == 0) $RequiredFiles[] = $RealPath;
		$Hash = Crc32File($RealPath);
		$RequiredFiles[$Hash] = $RealPath;

		$Content = file_get_contents($RealPath);
		$AllTokens = token_get_all($Content);
		foreach ($AllTokens as $N => $TokenArray) {
			list($TokenID) = $TokenArray;
			$String = ArrayValue(1, $TokenArray);
			if (!is_int($TokenID) || !in_array(token_name($TokenID), array('T_REQUIRE', 'T_REQUIRE_ONCE'))) continue;

			$PrevTokenString = ArrayValue(1, $AllTokens[$N-1]);
			$PrevTokenString = str_replace("\r", '', $PrevTokenString);
			if ($PrevTokenString !== "\n") continue;

			$OtherTokens = array_slice($AllTokens, $N);
			$FileTokens = array();
			foreach ($OtherTokens as $M => $Tk) {
				if (count($Tk) == 1 && $Tk[0] == ';') {
					$FileTokens = array_slice($OtherTokens, 0, $M);
					break;
				}
			}
			if (count($FileTokens) == 0) throw new Exception('FileTokens not found.');
			$TheFile = False;
			foreach(array_reverse($FileTokens) as $Tk){
				if (is_int($Tk[0]) && token_name($Tk[0]) == 'T_CONSTANT_ENCAPSED_STRING') {
					$TheFile = $Tk[1];
					$TheFile = trim($TheFile, '"\'/\\');
					break;
				}
			}
			if (!$TheFile) throw Exception('No string file found.');
			$DirnameFileConstruct = dirname($RealPath);
			$TheFile = $DirnameFileConstruct . DS . $TheFile;
			$RealFile = realpath($TheFile);
			if (!$RealFile) throw new Exception(sprintf('Invalid path `%1$s`.', $TheFile));

			$Hash = Crc32File($RealFile);
			if (!array_key_exists($Hash, $RequiredFiles)) {
				CompileFile($RealFile);
				$RequiredFiles[$Hash] = $RealFile;
			}
		}

	}
}

/**
* Calculates the crc32 checksum of a file
*/ 
if (!function_exists('Crc32File')) {
	function Crc32File($File) {
		return crc32( sha1_file($File) );
	}
}

/**
* Generate unique pathname for uploaded file.
* 
* @param string $TargetFolder. 
* @param string $Name name of file (or basename without extension). 
* @param string $Extension. 
* @param string $TempFile uploaded file.
* @param bool $bForceOverwriteExisting force overwrite file.
* @return mixed $TargetFile.
*/
if (!function_exists('GenerateCleanTargetName')) {
	function GenerateCleanTargetName($TargetFolder, $Name, $Extension = '', $TempFile = False, $bForceOverwriteExisting = False) {
		if ($Extension == '') {
			$Extension = pathinfo($Name, 4);
			$Name = pathinfo($Name, 8);
		}
		$Extension = Gdn_Format::Clean($Extension);
		$BaseName = Gdn_Format::Clean($Name);
		// check for file with same name
		$TestName = $BaseName;
		$TargetFile = $TargetFolder . DS . $TestName . '.' . $Extension;
		if (!file_exists($TargetFile)) return $TargetFile;
		$IsSameFile = ($TempFile != False && file_exists($TempFile) && Crc32File($TempFile) == Crc32File($TargetFile));
		if ($IsSameFile || $bForceOverwriteExisting) return $TargetFile;
		$Count = 0;
		$NameSuffix = '';
		do {
			//if (++$Count > 100) $NameSuffix = mt_rand(100, 9999);
			if (++$Count > 250) throw new Exception('Cannot generate unique name for file.');
			// make sure that iteration will end
			$TargetFile = $TargetFolder . '/' . $TestName . $NameSuffix . '.' . $Extension;
			$FileExists = file_exists($TargetFile);
			if ($FileExists && file_exists($TempFile) && md5_file($TargetFile) == md5_file($TempFile)) break;
			$NameSuffix = '-' . $Count;
		} while ($FileExists);
		return $TargetFile;
	}
}

/**
* 
*/
if (!function_exists('UploadFile')) {
	function UploadFile($TargetFolder, $InputName, $Options = False) {
/*		if (is_array($InputName)) {
			$Options = $InputName;
			$InputName = $TargetFolder;
		}*/

		$FileName = ArrayValue('name', ArrayValue($InputName, $_FILES));
		if ($FileName == '') return; // no upload, return null

		// options
		$AllowFileExtension = ArrayValue('AllowFileExtension', $Options);
		// TODO: $Overwrite is not used yet
		$CanOverwrite = ArrayValue('Overwrite', $Options, False);
		$CreateTargetFolder = ArrayValue('CreateTargetFolder', $Options, True);
		$WebTarget = ArrayValue('WebTarget', $Options);

		if ($CreateTargetFolder === True) {
			if (!file_exists($TargetFolder)) mkdir($TargetFolder, 0777, True);
			if (!is_writable($TargetFolder)) throw new Exception(sprintf('Directory (%s) is not writable.', $TargetFolder));
		}

		$Upload = new Gdn_Upload();
		if ($AllowFileExtension != False) {
			if (!is_array($AllowFileExtension)) $AllowFileExtension = SplitString($AllowFileExtension);
			foreach ($AllowFileExtension as $Extension) $Upload->AllowFileExtension($Extension);
		}
		
		$IsMultipleUpload = is_array($FileName);
		$Count = ($IsMultipleUpload) ? count($FileName) : 1;
		$OriginalFiles = $_FILES;
		$Result = array();
		for($i = 0; $i < $Count; $i++){
			if ($IsMultipleUpload != False) {
				$_FILES[$InputName] = array();
				foreach(array('name', 'type', 'tmp_name', 'error', 'size') as $Key) {
					$Value = GetValueR($InputName.'.'.$Key.'.'.$i, $OriginalFiles);
					SetValue($Key, $_FILES[$InputName], $Value);
				}
			} else $FileName = array($FileName);
			$TempFile = $Upload->ValidateUpload($InputName);
			$TargetFile = GenerateCleanTargetName($TargetFolder, $FileName[$i], '', $TempFile, $CanOverwrite);
			
			// 2.0.18 screwed Gdn_Upload::SaveAs()
			//$Upload->SaveAs($TempFile, $TargetFile);
			if (!move_uploaded_file($TempFile, $TargetFile))
				throw new Exception(sprintf(T('Failed to move uploaded file to target destination (%s).'), $TargetFile));
			
			if ($WebTarget != False) $File = str_replace(DS, '/', $TargetFile);
			elseif (array_key_exists('WithTargetFolder', $Options)) $File = $TargetFile;
			else $File = pathinfo($TargetFile, PATHINFO_BASENAME);
			$Result[] = $File;
		}
		$_FILES = $OriginalFiles;
		if ($IsMultipleUpload) return $Result;
		return $File;
	}
}


# http://php.net/manual/en/function.readdir.php
/**
* 
*/
if (!function_exists('ProcessDirectory')) {
	function ProcessDirectory($Directory, $Options = False){

		$bRecursive = $Options;

		/*if(Is_Bool($Options)) $bRecursive = $Options;
		elseif(Is_Numeric($Options)) $IntDeep = $Options; // 0 - unlim
		elseif(Is_Array($Options)){
			$IntDeep = ArrayValue('Deep', $Options, '0');
			$bRecursive = ArrayValue('Recursive', $Options, False);
		}*/

		if (!is_dir($Directory)) return False;
		$List = array();
		$Handle = opendir($Directory);
		while(False !== ($File = ReadDir($Handle))){
			$Path = $Directory.DS.$File;
			if ($File == '.' || $File == '..' || !file_exists($Path)) continue;
			if (is_dir($Path) && $bRecursive) {
				$NextDirectory = ProcessDirectory($Path, True);
				if (is_array($NextDirectory)) $List = array_merge($List, $NextDirectory);
			} else {
				$Entry = new StdClass();
				$Entry->Filename = $File;
				$Entry->Directory = $Directory;
				$Entry->Modtime = filemtime($Path);
				if (!is_dir($Path)) { // files
					$Entry->Size = FileSize($Path);
				} else { // directories
					$Entry->IsWritable = Is_Writable($Path);
					$Entry->bDirectory = True;
				}
				$List[] = $Entry;
			}
		}
		closedir($Handle);
		return $List;
	}
}

/**
Recursive remove directory that remove non empty dirs recursively.
It enters every directory, removes every file starting from the given path.
Note: Gdn_FileSystem::RemoveFolder()
*/
if (!function_exists('RecursiveRemoveDirectory')) {
	function RecursiveRemoveDirectory($Path){
		// Gdn_FileSysytem::RemoveFolder($Path)
		$Directory = new RecursiveDirectoryIterator($Path);
		// Remove all files
		foreach(new RecursiveIteratorIterator($Directory) as $File) unlink($File);
		// Remove all subdirectories
		foreach($Directory as $SubDirectory){
		// If a subdirectory can't be removed, it's because it has subdirectories, so recursiveRemoveDirectory is called again passing the subdirectory as path
		// @ suppress the warning message
			if (!@rmdir($SubDirectory)) RecursiveRemoveDirectory($SubDirectory);
		}
		// Remove main directory
		return rmdir($Path);
	}
}


/**
* Returns file extension
*/
if (!function_exists('FileExtension')) {
	function FileExtension($Basename) {
		return strtolower(pathinfo($Basename, 4));
	}
}