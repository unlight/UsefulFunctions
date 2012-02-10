<?php // …

if (!function_exists('SearchAnyWhere')) {
	function SearchAnyWhere($Options = False) {
		$SQL = Gdn::SQL();
		if (GetValue('Structure', $Options)) {
			$Database = Gdn::Database();
			$Construct = $Database->Structure();
			$Px = $Database->DatabasePrefix;
			$PxLength = strlen($Px);
			$DatabaseName = C('Database.Name');
			//$GetAllColumnsQuery = "select TABLE_NAME, COLUMN_NAME, DATA_TYPE from information_schema.COLUMNS where TABLE_SCHEMA = '{$Px}{$DatabaseName}'";
			$GetAllColumnsQuery = "select TABLE_NAME, COLUMN_NAME, DATA_TYPE from information_schema.COLUMNS where TABLE_SCHEMA = '{$Px}{$DatabaseName}' and DATA_TYPE in ('char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext')";
			$CurrentUser = $SQL->Query("select current_user() as RowValue")->Value('RowValue');
			$CurrentUser = explode('@', $CurrentUser);
			$Definer = "`{$CurrentUser[0]}`@`{$CurrentUser[1]}`";
			$Code = <<<CODE
create definer=$Definer procedure `SearchAnyWhere`(in `What` varchar(50))
    reads sql data
begin

declare Done int default False;
declare TableName, ColumnName, DataType, Dummy varchar(80);
declare FieldValue varchar(255);
declare GetAllColumns cursor for {$GetAllColumnsQuery};
declare continue handler for not found set Done = True;

set @PxLength = $PxLength;
set @SelectAll = Null;

open GetAllColumns;
ReadLoop: loop
	fetch GetAllColumns into TableName, ColumnName, DataType;
	set TableName = substr(TableName, @PxLength+1);
	if Done then leave ReadLoop;
	end if;
	set @SelectSql = concat('select "', TableName, '" as TableName, "', ColumnName, '" as ColumnName, `', ColumnName, '` as RowValue from ', TableName, ' where `', ColumnName, '` like "%', What, '%"');
	set @SelectAll = concat_ws(' union all ', @SelectAll, @SelectSql);
end loop;
close GetAllColumns;
#select @SelectAll;
prepare St from @SelectAll;
execute St;

end
CODE;
			try {
				$ProcedureCode = $Database->Query("show create procedure SearchAnyWhere")->Value('Create Procedure');
			} catch (Exception $Ex) {
				$ProcedureCode = '';
			}
			if (strtolower($ProcedureCode) != strtolower($Code)) {
				$Construct->Query("drop procedure if exists `SearchAnyWhere`");
				$Construct->Query($Code);
			}
		} else {
			if (strlen($Options) > 0) return $SQL->Query("call SearchAnyWhere('{$Options}')");
		}

	}
}

if (!function_exists('DatabasePrefix')) {
	/**
	* Set or setore database prefix (default empty).
	* Allow use SqlDriver class for building queries for other databases.
	* https://github.com/vanillaforums/Garden/pull/1266/files
	* 
	* @param mixed $Px		New prefix
	* @return NULL.
	*/
	function DatabasePrefix($Px = '') {
		static $ConfigDatabasePrefix;
		static $Count = 0;
		$Count++;
		$Database = Gdn::Database();
		if ($ConfigDatabasePrefix === Null) $ConfigDatabasePrefix = $Database->DatabasePrefix;
		if ($Count & 1) $Database->DatabasePrefix = $Px;
		else $Database->DatabasePrefix = $ConfigDatabasePrefix;
	}
}

if (!function_exists('MaxAutoIncrement')) {
	/**
	* Get max AUTO_INCREMENT from $Tables.
	* $Tables is null means all tables.
	* 
	* @param mixed $Tables
	* @return mixed $Result.
	*/
	function MaxAutoIncrement($Tables = Null, $Options = False) {
		if (is_string($Tables)) $Tables = SplitUpString($Tables, ',', 'trim');
		$SQL = Gdn::SQL();
		if (!is_null($Tables)) {
			$Px = $SQL->Database->DatabasePrefix;
			$Tables = array_map(create_function('$S', "return '$Px'.\$S;"), $Tables);
			$SQL->WhereIn('TABLE_NAME', $Tables);
		}
		DatabasePrefix();
		$Select = $SQL
			->Select('AUTO_INCREMENT', 'max', 'MaxAutoIncrement')
			->From('information_schema.TABLES i')
			->Where('TABLE_SCHEMA', C('Database.Name'))
			->GetSelect();
		DatabasePrefix();
		$Select = $SQL->ApplyParameters($Select);
		$SQL->Reset();
		if (GetValue('GetQuery', $Options)) return $Select;
		$Result = $SQL->Query($Select)->Value('MaxAutoIncrement');
		return $Result;
	}
}

/** 
BEGIN
select group_concat(Name separator ',') from (select distinct Name from GDN_UserMeta) as t INTO @Fields;
set @SqlGroups = 'UserID';
loop1: loop
	set @Column = substring_index(@Fields, ',', 1);
	set @Fields = mid(@Fields, length(@Column)+2);
	set @SqlGroups = concat(@SqlGroups, ', group_concat(if(Name = "', @Column, '", Value, NULL)) as ', @Column);
	if (length(@Fields) = 0) then leave loop1; end if;
end loop loop1;
set @Sql = concat('select ', @SqlGroups, ' from Gdn_UserMeta group by UserID');
                
set @Sql = concat('create or replace view GDN_VwUserMeta as ', @Sql);
prepare St FROM @Sql;
execute St;
END

One line:
CREATE PROCEDURE `CreateViewUserMeta`()  LANGUAGE SQL  NOT DETERMINISTIC  CONTAINS SQL  SQL SECURITY DEFINER  COMMENT '' BEGIN select group_concat(Name separator ',') from (select distinct Name from GDN_UserMeta) as t INTO @Fields; set @SqlGroups = 'UserID'; loop1: loop  set @Column = substring_index(@Fields, ',', 1);  set @Fields = mid(@Fields, length(@Column)+2);  set @SqlGroups = concat(@SqlGroups, ', group_concat(if(Name = "', @Column, '", Value, NULL)) as ', @Column);  if (length(@Fields) = 0) then leave loop1; end if; end loop loop1; set @Sql = concat('select ', @SqlGroups, ' from Gdn_UserMeta group by UserID');          set @Sql = concat('create or replace view GDN_VwUserMeta as ', @Sql); prepare St FROM @Sql; execute St; END;
*/
if (!function_exists('CreateViewUserMeta')) {
	/**
	* Create "flat" view for UserMeta table
	*
	*/
	function CreateViewUserMeta() {
		$SQL = Gdn::SQL();
		$Px = $SQL->Database->DatabasePrefix;
		$ViewFileds = $SQL
			->Distinct()
			->Select('Name')
			->From('UserMeta')
			->Get()
			->Result();
		$FieldNames[] = 'UserID';
		foreach ($ViewFileds as $Name) $FieldNames[] = "group_concat(if(Name = '$Name', Value, NULL)) as $Name";
		$FieldNames = implode(", \n", $FieldNames);
		Gdn::Structure()->View('VwUserMeta', $SQL
			->Select($FieldNames)
			->From('UserMeta')
			->GroupBy('UserID')
		);
	}
}

if (!function_exists('TableDataValues')) {
	/**
	* Undocumented
	*
	*/
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
			if (is_object($Value) || is_array($Value)) continue;
			
			// TODO: WE CAN PUT THIS INTO CACHE
			$ColumnKeys = array_keys($Columns);
			$ColumnKeys = array_combine(array_map('strtolower', $ColumnKeys), $ColumnKeys);
			$NameLowered = strtolower($Name);

			if (array_key_exists($NameLowered, $ColumnKeys)) {
				$Name = $ColumnKeys[$NameLowered];
				$Field = $Columns[$Name];
				$Float = array('float', 'double');
				$Int = array('int', 'tinyint', 'smallint', 'mediumint', 'bigint');
				if (in_array($Field->Type, $Int)) $Value = intval($Value);
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
			if ($TagID === False) {
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
					->Column('Value', 'mediumtext')
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
		if ($Value === False) {
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