<?php if (!defined('APPLICATION')) exit();

abstract class UsefulModel extends Gdn_Model {

	protected $Alias;

	public function SaveMetaData($RowID, $OtherTable, $Values) {
		$TableName = $this->Name . $OtherTable;
		$OtherField = $OtherTable . 'ID';
		$Where = array($this->PrimaryKey => $RowID);
		$SQL = Gdn::SQL();
		$DataInsert = array();
		foreach ($Values as $Value) {
			$DataInsert[] = array_merge($Where, array($OtherField => $Value));
		}
		$this->SQL->Where($Where)->Delete($TableName);
		return $SQL->Insert($TableName, $DataInsert);
	}

	protected function GetAlias() {
		if ($this->Alias === Null) {
			$ClassName = get_class($this);	
			if (substr($ClassName, -5) == 'Model') {
				$ClassName = substr($ClassName, 0, -5);
			}
			preg_match_all('/[A-Z]/', $ClassName, $Match);
			if (isset($Match[0])) {
				$this->Alias = implode('', $Match[0]);
			} else {
				$this->Alias = substr($ClassName, 0, 1);
			}
			$this->Alias = strtolower($this->Alias);
		}
		return $this->Alias;
	}

	/**
	* Undocumented 
	* 
	*/
	public static function SetNullValues(&$Fields, $Nulls = array('')) {
		if (!is_array($Nulls)) $Nulls = array_slice(func_get_args(), 1);
		foreach ($Fields as &$Value) {
			if (is_scalar($Value) && in_array($Value, $Nulls, True)) $Value = NULL;
		}
		return $Fields;
	}

	// public function Save($Fields, $Settings = False) {
	// 	$Fields = self::SetNullValues($Fields);
	// 	$Result = parent::Save($Fields, $Settings);
	// 	return $Result;
	// }
	

	public function GetNames($Collection, $Options = False) {
		$Field = GetValue('Field', $Options, 'Name');
		$NameField = ArrayValue('NameField', $Options, 'Name');
		$AddNew = GetValue('AddNew', $Options, True);
		$Values = array();
		foreach ($Collection as $Key => $Value) {
			if (!$Value) continue;
			$Values[] = $Value;
		}
		$ConsolidatedResult = array();
		$this->SQL->WhereIn($Field, $Values);
		foreach ($this->Get() as $Row) {
			$ConsolidatedResult[$Row->{$NameField}] = $Row->{$this->PrimaryKey};
		}
		if ($AddNew != False) {
			$Lower = ArrayValue('Lower', $Options, False);
			foreach ($Values as $Name) {
				if ($Lower) {
					$Name = mb_convert_case($Name, MB_CASE_LOWER);
				}
				$RowID = ArrayValue($Name, $ConsolidatedResult);
				if ($RowID === False) {
					$Fields = array(
						$NameField => $Name
					);
					$RowID = $this->SQL
						->History(False, True)
						->Insert($this->Name, $Fields);
				}
				$ConsolidatedResult[$Name] = $RowID;
			}
		}
		return $ConsolidatedResult;
	}

	public function Get($Conditions = False, $Offset = False, $Limit = False, $OrderBy = False, $OrderDirection = False) {
		$bCountQuery = GetValue('bCountQuery', $Conditions, False, True);
		$SelectFields = GetValue('SelectFields', $Conditions, False, True);
		$SQL = $this->SQL;
		$Alias = $this->GetAlias();
		if ($bCountQuery) {
			$this->SQL->Select('*', 'count', 'RowCount');
			$Offset = False;
			$Limit = False;
			$OrderBy = False;
		}

		if ($SelectFields && !$bCountQuery) {
			$this->SQL->Select($SelectFields);
		}
		if (is_array($Conditions)) {
			$SQL->Where($Conditions);
		}
		if ($OrderBy !== False) {
			$OrderBys = SplitUpString($OrderBy, ',', 'trim');
			foreach ($OrderBys as $OrderBy) {
				if (!strpos($OrderBy, ' ')) {
					$OrderBy .= ' ' .  $OrderDirection;
				}
				list($Field, $Direction) = explode(' ', $OrderBy);
				$this->SQL->OrderBy($Field, $Direction);
			}
		}

		$this->EventArguments['bCountQuery'] = $bCountQuery;
		$this->EventArguments['Conditions'] =& $Conditions;
		$this->FireEvent('BeforeGet');

		$Result = $this->SQL
			->From($this->Name . ' ' . $Alias)
			->Limit($Limit, $Offset)
			->Get();
		if ($bCountQuery) {
			$Result = $Result->FirstRow()->RowCount;
		}
		return $Result;
	}
}