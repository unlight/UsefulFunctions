<?php
// …

if (!function_exists('TableDataValues')) {
	function TableDataValues($Data, $TableName, $Options = False){
		static $Cache;
		if (!isset($Cache[$TableName])) {
			$SQL = Gdn::SQL();
			$Cache[$TableName] = $SQL->FetchTableSchema($TableName);
		}
		//$CoerceString = GetValue('CoerceString', $Options);
		$Columns = $Cache[$TableName];
		$Result = array();
		$Data = Gdn_Format::ObjectAsArray($Data);
		foreach ($Data as $Name => $Value) {
			if(is_object($Value) || is_array($Value)) continue;
			
			// TODO: WE CAN PUT THIS INTO CACHE
			$ColumnKeys = array_keys($Columns);
			$ColumnKeys = array_combine(array_map('strtolower', $ColumnKeys), $ColumnKeys);
			$NameLowered = strtolower($Name);

			if(array_key_exists($NameLowered, $ColumnKeys)) {
				$Name = $ColumnKeys[$NameLowered];
				$Field = $Columns[$Name];
				$Float = array('float', 'double');
				$Int = array('int', 'tinyint', 'smallint', 'mediumint', 'bigint');
				if(in_array($Field->Type, $Int)) $Value = intval($Value);
				else if(in_array($Field->Type, $Float)) $Value = floatval($Value);
				if (!is_null($Value)) $Value = strval($Value);
				$Result[$Name] = $Value;
			}
		}
		return $Result;
	}
}

/**
* Count something in many-to-many relationship tables
* SQL statement:
update News n set n.TagCount = (select count(*) from NewsTags nt where nt.NewsID = n.NewsID)
*/ 
if (!function_exists('CountManyToManyData')) {
	// TODO: FIX ME: No escape values here
	function CountManyToManyData($OuterTableName, $OuterField, $InnerTableName, $Where) {
		$SQL = Gdn::SQL();
		$Sql = $SQL
			->Select('*', 'count', 'RowCount')
			->From($InnerTableName)
			->Where($Where, Null, False, False)
			->GetSelect();
		$SQL->Reset();
		$Result = $SQL
			->Update($OuterTableName)
			->Set($OuterField, "($Sql)", False)
			->Put();
		return $Result;
	}
}

/**
* Saves data to tables which are in many-to-many relationship
*/
if (!function_exists('SaveManyToManyData')) {
	function SaveManyToManyData($TableName, $Where, $OtherFieldName, $Values) {
		$SQL = Gdn::SQL();
		$DataInsert = array();
		foreach ($Values as $Value) $DataInsert[] = array_merge($Where, array($OtherFieldName => $Value));
		$SQL->Where($Where)->Delete($TableName);
		return $SQL->Insert($TableName, $DataInsert);
	}
}


/**
* Function saves new tags. Return existing.
*/
if (!function_exists('SaveTags')) {
	function SaveTags($TagString) {
		if (!is_string($TagString)) $TagString = GetValue('Tags', $TagString);
		$TagString = mb_strtolower($TagString, 'utf-8');
		$TagString = preg_replace('/[^ \-0-9a-zа-я]/iu', ' ', $TagString);
		$TagsNames = SplitString($TagString, ' ', array('array_filter', 'array_unique'));
		$ExistingTagData = Gdn::SQL()
			->Select('TagID, Name')
			->From('Tag')
			->WhereIn('Name', $TagsNames)
			->Get();
		$ConsolidatedTags = ConsolidateArrayValuesByKey($ExistingTagData->ResultArray(), 'Name', 'TagID');
		foreach($TagsNames as $TagName) {
			$TagID = GetValue($TagName, $ConsolidatedTags);
			if($TagID === False) {
				$TagID = Gdn::SQL()
					->History(False, True)
					->Insert('Tag', array('Name' => $TagName));
			}
			$ConsolidatedTags[$TagName] = $TagID;
		}
		return $ConsolidatedTags;
	}
}

/**
* Key/value storage.
*/
if (!function_exists('K')) {
	function K($Name, $Value = Null) {
		static $SQL, $Cache, $DataTableCreated;
		
		if (is_null($DataTableCreated)) {
			$DataTableCreated = C('Plugins.UsefulFunctions.DataTableCreated');
			if ($DataTableCreated === False) {
				Gdn::Structure()
					->Table('Data')
					->Column('Name', 'varchar(200)', False, 'unique')
					->Column('Value', 'text')
					->Set(False, False);
				$DataTableCreated = True;
				SaveToConfig('Plugins.UsefulFunctions.DataTableCreated', $DataTableCreated);
			}
		}
		
		// Select
		if ($SQL === Null) $SQL = Gdn::SQL();
		if (is_string($Name) && $Value === Null) {
			$Modificator = $Name{0};
			if (in_array($Modificator, array('#', '%', '@'))) $Name = substr($Name, 1);
			
			if (!isset($Cache[$Name])) {
				
				switch($Modificator){
					case '#': $SQL->Where('Name', $Name); break;
					case '%': 
					case '@':
					default: $SQL->Like('Name', $Name, 'right');
				}
				
				$Result = Null;
				$ResultSet = $SQL->Select('Name, Value')->From('Data')->Get();
				if ($ResultSet->NumRows() == 0) return False;
				elseif ($Modificator == '%') {
					foreach($ResultSet as $Data) {
						$S = "['".str_replace('.', "']['", $Data->Name)."']";
						eval("\$Value =& \$Result{$S};"); // eval is evil
						if (is_null($Value) || $Value === '' || is_array($Value)) {
							$Value = $Cache[$Data->Name] = Gdn_Format::Unserialize($Data->Value);
						} else {
							// TODO: FIX ME
							// Lost value. What should we do? Delete? Throw Exception?
						}
					}
				} elseif ($Modificator == '@' || $ResultSet->NumRows() > 1) {
					foreach($ResultSet as $Data) {
						$K = array_pop(explode('.', $Data->Name));
						$Result[$K] = $Cache[$Data->Name] = Gdn_Format::Unserialize($Data->Value);
					}
					// reduce result array
					//if ($Modificator == '@') while(count($Result) == 1) $Result = array_shift($Result);
				} else {
					$Result = $ResultSet->FirstRow()->Value;
					$Result = Gdn_Format::Unserialize($Result);
				}
				$Cache[$Name] = $Result;
			}
			return $Cache[$Name];
		}

		// Delete
		if($Value === False) {
			if (is_array($Name)) return $SQL->WhereIn('Name', $Name)->Delete('Data');
			if (is_string($Name)) return $SQL->Like('Name', $Name, 'right')->Delete('Data');
			trigger_error(sprintf('Incorrect type of Key (%s)', gettype($Name)));
		}

		// Insert/Update
		if (!is_array($Name)) $Name = array($Name => $Value);	
		foreach ($Name as $Key => $Value) {
			$Value = Gdn_Format::Serialize($Value);
			$SQL->Replace('Data', array('Value' => $Value), array('Name' => $Key));
		}
	}
}