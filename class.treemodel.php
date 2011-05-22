<?php if (!defined('APPLICATION')) exit();

# Credits: 
# Kuzma Feskov <kuzma[at]russofile[dot]ru>
# http://php.russofile.ru/ru/authors/sql/nestedsets01/
# Rolf Brugger, edutech
# http://www.edutech.ch/contribution/nstrees

class TreeModel extends Gdn_Model {
	
	protected $LeftKey = 'TreeLeft';
	protected $RightKey = 'TreeRight';
	protected $DepthKey = 'Depth';
	protected $ParentKey = 'ParentID';
	
	protected $_CachedResult;
	
	// TODO: 
	// CELKO DB CLASS:
	// $FieldOrder => Field name for table nasted set order field
	// $FieldIgnore => Field name for tablr nested set ignore field
	
	public function GetRoot() {
		$Root = $this
			->SelectNodeFields()
			->From($this->Name)
			->Where($this->PrimaryKey, 1, False, False)
			->Get()
			->FirstRow();
		return $Root;
	}
	
	
	/**
	* Receives left, right and level for unit with number id.
	*/
	public function GetNode($ID, $ResetCache = False) {
		$Result =& $this->_CachedResult[$ID];
		if (!isset($this->_CachedResult[$ID]) || $ResetCache) {
			$Result = $this
				->SelectNodeFields()
				->From($this->Name)
				->Where($this->PrimaryKey, $ID)
				->Get()
				->FirstRow();
		}
		return $Result;
	}
	
	/**
	* Sets initial parameters of a tree and creates root of tree
	* ATTENTION, all previous values in table are destroyed.
	*/
	public function Clear($Set = Null) {
		$this->SQL->Truncate($this->Name);
		//$this->Delete(); // why need this?
		if (is_array($Set)) $this->SQL->Insert($this->Name, $Set);
	}
	
	
	protected function SelectNodeFields() {
		$this->SQL
			->Select($this->LeftKey)
			->Select($this->RightKey)
			->Select($this->DepthKey)
			->Select($this->PrimaryKey);
		return $this->SQL;
	}

	/** Converts from the lists of the adjacency to nested sets
	* @param id
	* @param identifier of the first free value
	* credit: http://phpclub.ru/faq/Tree/DemoAlNs
	*/
	public function AjacencyListToNestedSets($ParentID = 0, $SideID = 0) {
		$TreeDataSet = $this->SQL
			->From($this->Name)
			->Select($this->PrimaryKey)
			->Where($this->ParentKey, $ParentID)
			->Get()
			->Result();
		foreach ($TreeDataSet as $Node) {
			$NodeID = $Node->{$this->PrimaryKey};
			$RightID = $this->AjacencyListToNestedSets($NodeID, $SideID + 1);
			$this->SQL
				->Update($this->Name)
				->Set($this->LeftKey, $SideID)
				->Set($this->RightKey, $RightID)
				->Where($this->PrimaryKey, $NodeID)
				->Put();
			$SideID = $RightID + 1;
		}
		return $SideID;
	}
	
	/** 
	* Set Depth for all nodes. Use after AjacencyListToNestedSets()
	*/
	public function UpdateTreeDepth() {
		$Px = $this->SQL->Database->DatabasePrefix;
		$Sql = $this->SQL
			->Select("n.{$this->PrimaryKey}")
			->Select('*', 'count', 'RowCount')
			->From(array($this->Name.' n', $this->Name.' p'))
			->Where("n.{$this->LeftKey} >= p.{$this->LeftKey}", Null, False, False)
			->Where("n.{$this->LeftKey} <= p.{$this->RightKey}", Null, False, False)
			->GroupBy("n.{$this->PrimaryKey}")
			->GetSelect();
		$this->SQL->Reset();
		$TemporaryTableSql = "select t1.{$this->PrimaryKey}, t1.RowCount from ($Sql) as t1";
		$TemporaryTable = "select (t2.RowCount-1) from ($TemporaryTableSql) as t2 where t2.{$this->PrimaryKey} = tree.{$this->PrimaryKey}";
		$Query = "update {$Px}{$this->Name} tree set tree.Depth = ($TemporaryTable)";
		$this->SQL->Query($Query);
	}
	
	protected function _NodeValues($Node) {
		if (!is_object($Node)) {
			$Node = $this->GetNode($Node);
			if ($Node === False) throw new Exception('Empty node.');
		}
		$Result = array_map('intval', array(
			$Node->{$this->LeftKey}, 
			$Node->{$this->RightKey}, 
			$Node->{$this->DepthKey}, 
			$Node->{$this->PrimaryKey}
		));
		return $Result;
	}
	
	/**
	* Receives parent left, right and level for unit with number $id.
	*/
	public function GetParent($ID) {
		list($LeftID, $RightID, $Depth, $NodeID) = $this->_NodeValues($ID);
		if (!$NodeID) return False;
		$Level = $Level - 1;
		//$Result =& $this->_CachedResult[$ID];
		$Result = $this->SQL
			//->SelectNodeFields()
			->Select('*')
			->From($this->Name)
			->Where($this->LeftKey.' <', $LeftID)
			->Where($this->RightKey.' >', $RightID)
			->Where($this->DepthKey, $Level)
			->OrderBy($this->LeftKey)
			->Get()
			->FirstRow();
		return $Result;
	}
	
	
	/**
	* Adds a new child element to the end of the list of child elements
	*/
	public function AppendTo($Parent, $Data) {
		if (is_numeric($Parent)) $Parent = $this->GetNode($Parent);
		list($ParentLeftID, $ParentRightID, $ParentDepth, $ParentID) = $this->_NodeValues($Parent);
		
		SetValue($this->LeftKey, $Data, $ParentRightID);
		SetValue($this->RightKey, $Data, $ParentLeftID + 1);
		SetValue($this->DepthKey, $Data, $ParentDepth + 1);
		SetValue($this->ParentKey, $Data, $ParentID);
		
		$this->Database->BeginTransaction();

		$this->SQL
			->Update($this->Name)
			->Set($this->LeftKey, $this->LeftKey.'+2', False)
			->Where($this->LeftKey . '>', $ParentRightID)
			->Put();
		$this->SQL
			->Update($this->Name)
			->Set($this->RightKey, $this->RightKey.'+2', False)
			->Where($this->RightKey . '>=', $ParentRightID)
			->Put();
		
		$ResultID = $this->SQL->Insert($this->Name, $Data);
		$this->Database->CommitTransaction();
		return $ResultID;

	}
	
	/**
	* Adds a new child element to the top of the list of child elements
	*/
	public function PrependTo($Parent, $Data) {
		if (is_numeric($Parent)) $Parent = $this->GetNode($Parent);
		list($ParentLeftID, $ParentRightID, $ParentDepth, $ParentID) = $this->_NodeValues($Parent);
		
		SetValue($this->LeftKey, $Data, $ParentLeftID + 1);
		SetValue($this->RightKey, $Data, $ParentLeftID + 2);
		SetValue($this->DepthKey, $Data, $ParentDepth + 1);
		SetValue($this->ParentKey, $Data, $ParentID);
		
		$this->Database->BeginTransaction();

		$this->SQL
			->Update($this->Name)
			->Set($this->LeftKey, $this->LeftKey.'+2', False)
			->Where($this->LeftKey . '>', $ParentLeftID)
			->Put();
		$this->SQL
			->Update($this->Name)
			->Set($this->RightKey, $this->RightKey.'+2', False)
			->Where($this->RightKey . '>', $ParentLeftID)
			->Put();
		
		$ResultID = $this->SQL->Insert($this->Name, $Data);
		$this->Database->CommitTransaction();
		return $ResultID;
	}
	
	/**
	* Add a new element in the tree to element with number $id.
	*
	* @return integer Inserted element id
	*/
	public function InsertNode($ID, $Data) {
		list($LeftID, $RightID, $Depth, $NodeID) = $this->_NodeValues($ID);
		$Data = (array)$Data;
		$Data[$this->LeftKey] = $RightID;
		$Data[$this->RightKey] = $RightID + 1;
		$Data[$this->DepthKey] = $Depth + 1;
		$Data[$this->ParentKey] = $NodeID;
		
		$this->Database->BeginTransaction();
		
		// One query.
		$this->SQL
			->Update($this->Name)
			->Set($this->LeftKey, "case when {$this->LeftKey} > $RightID then {$this->LeftKey}+2 else {$this->LeftKey} end", False, False)
			->Set($this->RightKey, "case when {$this->RightKey} >= $RightID then {$this->RightKey}+2 else {$this->RightKey} end", False, False)
			//->Set($this->RightKey, $this->RightKey.'+2', False)
			//->Set($this->LeftKey, "if({$this->LeftKey} > $RightID, {$this->LeftKey}+2, {$this->LeftKey})", False)
			->Where($this->RightKey . '>=', $RightID, False, False)
			->Put();
	
		$ResultID = $this->SQL->Insert($this->Name, $Data);
		$this->Database->CommitTransaction();
		return $ResultID;
	}
	
	
	
	/**
	* Add a new element in the tree near element with number id.
	*
	* @param integer $ID Number of a parental element
	* @return integer Inserted element id
	*/
	public function InsertNear($ID, $Data) {
		list($LeftID, $RightID, $Depth, $NodeID) = $this->_NodeValues($ID);
		if (!$NodeID) return False;
		$Data = (array)$Data;
		
		$Data[$this->LeftKey] = $RightID + 1;
		$Data[$this->RightKey] = $RightID + 2;
		$Data[$this->DepthKey] = $Depth;
		$Data[$this->ParentKey] = $NodeID;
		
		$this->Database->BeginTransaction();
		
		$this->SQL
			->Update($this->Name)
			->Set($this->LeftKey, "case when {$this->LeftKey} > $RightID then {$this->LeftKey}+2 else {$this->LeftKey} end", False, False)
			->Set($this->RightKey, "case when {$this->RightKey} > $RightID then {$this->RightKey}+2 else {$this->RightKey} end", False, False)
			->Where("{$this->RightKey} > $RightID", Null, False, False);

		$ResultID = $this->SQL->Insert($this->Name, $Data);
		$this->Database->CommitTransaction();
		return $ResultID;
	}
	
	# Part of Nested Set Tree Library by Rolf Brugger
	# http://www.edutech.ch/contribution/nstrees
	
	/* '$src' is the node/subtree, '$to' is its destination l-value */
	function _MoveSubTree ($Source, $DestinationLeftID) {
		$SourceLeftID = GetValue($this->LeftKey, $Source);
		$SourceRightID = GetValue($this->RightKey, $Source);
		$TreeSize = $SourceRightID - $SourceLeftID + 1;
		$this->_ShiftRLValues($TreeSize, $DestinationLeftID);
		if ($SourceLeftID >= $DestinationLeftID) { // src was shifted too?
			$SourceLeftID += $TreeSize;
			$SourceRightID += $TreeSize;
		}
		/* now there's enough room next to target to move the subtree */
		$NewPosition = $this->_ShiftRLRange($DestinationLeftID - $SourceLeftID, $SourceLeftID, $SourceRightID);
		/* correct values after source */
		$this->_ShiftRLValues(-$TreeSize, $SourceRightID + 1);
		if ($SourceLeftID <= $DestinationLeftID) { // dst was shifted too?
			$NewPosition[$this->LeftKey] -= $TreeSize;
			$NewPosition[$this->RightKey] -= $TreeSize;
		}  
		return $NewPosition;
	}
	
	/* Adds '$delta' to all L and R values that are >= '$first'. '$delta' can also be negative. */
	protected function _ShiftRLValues($Delta, $First, $Last = False) {
		$Delta = sprintf('%+d', $Delta);
		if ($Last !== False) $this->SQL->Where($this->LeftKey . '<=', $Last);
		$this->SQL
			->Update($this->Name)
			->Set($this->LeftKey, $this->LeftKey . $Delta, False)
			->Where($this->LeftKey . '>=', $First)
			->Put();
		if ($Last !== False) $this->SQL->Where($this->RightKey . '<=', $Last);
		$this->SQL
			->Update($this->Name)
			->Set($this->RightKey, $this->RightKey . $Delta, False)
			->Where($this->RightKey . '>=', $First)
			->Put();
	}

	/* adds '$delta' to all L and R values that are >= '$first' and <= '$last'. '$delta' can also be negative. 
		returns the shifted first/last values as node array.
	*/	
	protected function _ShiftRLRange($Delta, $First, $Last) {
		$this->_ShiftRLValues($Delta, $First, $Last);
		$Result = array($this->LeftKey => $First + $Delta, $this->RightKey => $Last + $Delta);
		return $Result;
	}
	
	/* creates a new root record and returns the node 'l'=1, 'r'=2. */
	protected function _InsertNew($Node, $Other) {
		// DO NOT USE!
		$Fields = array_merge((array)$Node, (array)$Other);
		return $this->Insert($this->Name, $Fields);
	}
	

	/**
	* Assigns a node with all its children to another parent.
	*
	* @param integer $ID node ID
	*/
/*	public function MoveAll($ID, $NewParentID, $Where = '') {
		list($LeftID, $RightID, $Depth) = $this->_NodeValues($ID);
		list($ParentLeftID, $ParentRightID, $ParentDepth) = $this->_NodeValues($NewParentID);
		
		if ($ID == $NewParentID || $LeftID == $ParentLeftID 
			|| ($ParentLeftID >= $LeftID && $ParentLeftID <= $RightID)
			|| ($Depth == $ParentDepth+1 && $LeftID > $ParentLeftID && $RightID < $ParentRightID)) {
			trigger_error('Cannot move tree.', E_USER_ERROR);
			return False;
		}
		//$this->SQL->Where($Where)
		
		$DepthSetValue = sprintf('%+d', -($Depth-1) + $ParentDepth);
		$LeftIDM1 = $LeftID - 1;
		$RightLeft1 = $RightID - $LeftID + 1;
		$LeftRightParentM = $LeftID - $ParentRightID;
		$RightIDP1 = $RightID + 1;
		$ParentRightIDM1 = $ParentRightID - 1;
		$BetweenLR = "$LeftID and $RightID";
		
		if ($ParentLeftID < $LeftID && $ParentRightID > $RightID && $ParentDepth < $Depth - 1) {
			
			$Between1 = $RightIDP1.' and '.$ParentRightIDM1;
			$SetValue1 = ((($ParentRightID-$RightID-$Depth+$ParentDepth)/2)*2+$Depth-$ParentDepth-1);
			
			$this->SQL
				->Set($this->DepthKey, "case 
					when {$this->LeftKey} between $BetweenLR then {$this->DepthKey} $DepthSetValue 
					else {$this->DepthKey} end", False, False)
				->Set($this->RightKey, "case 
					when {$this->RightKey} between $Between1 then {$this->RightKey} - $RightLeft1
					when {$this->LeftKey} between {$LeftID} and {$RightID} then {$this->RightKey} + $SetValue1
					else {$this->RightKey} end", False, False)
				->Set($this->LeftKey, "case
					when {$this->LeftKey} between $Between1 then {$this->LeftKey} - $RightLeft1
					when {$this->LeftKey} between {$LeftID} and {$RightID} then {$this->LeftKey} + $SetValue1
					else {$this->LeftKey} end", False, False)
				->Where($this->LeftKey, "between {$Between1}", False, False);
			
		} elseif ($ParentLeftID < $LeftID) {
			$this->SQL
				->Set($this->DepthKey, "case 
					when {$this->LeftKey} between $BetweenLR then {$this->DepthKey} $DepthSetValue 
					else {$this->DepthKey} end", False, False)
				->Set($this->LeftKey, "case
					when {$this->LeftKey} between $RightID and $LeftIDM1 then {$this->LeftKey} + $RightLeft1
					when {$this->LeftKey} between $Between2 then {$this->LeftKey} - $LeftRightParentM
					else {$this->LeftKey}", False, False)
				->Set($this->RightKey, "case
					when {$this->RightKey} between $ParentRightID and $LeftID then {$this->RightKey} + $RightLeft1
					when {$this->RightKey} between $BetweenLR then {$this->RightKey} - $LeftRightParentM
					else {$this->RightKey} end", False, False)
				->BeginWhereGroup()
				->Where($this->LeftKey, "between $ParentLeftID and $RightID", False, False)
				->OrWhere($this->RightKey, "between $ParentLeftID and $RightID", False, False)
				->EndWhereGroup();
		} else {
			$RightParentM1 = $ParentRightID - 1 - $RightID;
			$this->SQL
				->Set($this->DepthKey, "case 
					when {$this->LeftKey} between $BetweenLR then {$this->DepthKey} $DepthSetValue else $Depth")
				->Set($this->LeftKey, "case
					when {$this->LeftKey} between $RightID and $ParentRightID then {$this->LeftKey} - $RightLeft1
					when {$this->LeftKey} between $BetweenLR then {$this->LeftKey} + $RightParentM1
					else {$this->LeftKey} end", False, False)
				->Set($this->RightKey, "case
					when {$this->RightKey} between $RightIDP1 and $ParentRightIDM1 then {$this->RightKey} - $RightLeft1
					when {$this->RightKey} between $BetweenLR then {$this->RightKey} + $RightParentM1 
					else {$this->RightKey} end", False, False)
				->BeginWhereGroup()
				->Where("{$this->LeftKey} between $LeftID and $ParentRightID", Null, False, False)
				->OrWhere("{$this->RightKey} between $LeftID and $ParentRightID", Null, False, False)
				->EndWhereGroup();
		}
		
		$this->SQL->Update($this->Name)->Put();
		return TRUE;
	}*/
	
	
	/**
	* Change items position.
	*
	* @param integer $id1 first item ID
	* @param integer $id2 second item ID
	*/
/*	public function ChangePosition($ID1, $ID2) {
		
		$Node1 = $this->GetNode($ID1);
		if (!$Node1) return $Node;
		
		$Node2 = $this->GetNode($ID2);
		if (!$Node2) return $Node2;
		
		$this->Database->BeginTransaction();
		
		$this->SQL
			->Update($this->Name)
			->Set($this->LeftKey, $Node2->{$this->LeftKey})
			->Set($this->RightKey, $Node2->{$this->RightKey})
			->Set($this->DepthKey, $Node2->{$this->DepthKey})
			->Where($this->PrimaryKey, (int)$ID1, False, False)
			->Put();
		
		$this->SQL
			->Update($this->Name)
			->Set($this->LeftKey, $Node1->{$this->LeftKey})
			->Set($this->RightKey, $Node1->{$this->RightKey})
			->Set($this->DepthKey, $Node1->{$this->DepthKey})
			->Where($this->PrimaryKey, (int)$ID2, False, False)
			->Put();
		
		$this->Database->CommitTransaction();
		return TRUE;
	}*/
	
	
	/**
	* Swapping nodes within the same level and limits of one parent with all its children: $id1 placed before or after $id2.
	*
	*/
	public function ChangePositionAll($ID1, $ID2, $Position = 'after', $Where = False) {
		list($LeftID1, $RightID1, $Depth1, $NodeID1) = $this->_NodeValues($ID1);
		if (!$NodeID1) return False;
		list($LeftID2, $RightID2, $Depth2, $NodeID2) = $this->_NodeValues($ID2);
		if (!$NodeID2) return False;

		if ($Depth1 != $Depth2) {
			trigger_error('Cannot change position.', E_USER_ERROR);
			return False;
		}
		
		switch (strtolower($Position)) {
			case 'before': $this->_ChangePositionBeforeQuery($LeftID1, $RightID1, $LeftID2); break;
			case 'after': $this->_ChangePositionAfterQuery($LeftID1, $RightID1, $LeftID2, $RightID2); break;
			default: {
				trigger_error('Unknown position.', E_USER_ERROR);
				return False;
			}
		}
		if (is_array($Where)) $this->SQL->Where($Where);
		$Result = $this->SQL->Update($this->Name)->Put();
		return $Result;
	}
	
	private function _ChangePositionAfterQuery($LeftID1, $RightID1, $LeftID2, $RightID2) {
		if ($LeftID1 > $LeftID2) {
			$Value1 = $LeftID1 - $LeftID2 - ($RightID2 - $LeftID2 + 1);
			$SqlValueRight = "case when {$this->LeftKey} between $LeftID1 and $RightID1 then {$this->RightKey} - $Value1
				when {$this->LeftKey} between ($RightID2 + 1) and ($LeftID1 - 1) then {$this->RightKey} + ($RightID1 - $LeftID1 + 1))
				else {$this->RightKey} end";
			$SqlValueLeft = "case when {$this->LeftKey} between $LeftID1 and $RightID1 then {$this->LeftKey} - ($LeftID1 - $LeftID2 - ($RightID2 - $LeftID2 + 1))
				when {$this->LeftKey} between ($RightID2 + 1) and ($LeftID1 - 1) then {$this->LeftKey} + ($RightID1 - $LeftID1 + 1) 
				else {$this->LeftKey} end";
			$this->SQL
				->Set($this->RightKey, $SqlValueRight, False, False)
				->Set($this->LeftKey, $SqlValueLeft, False, False)
				->Where("{$this->LeftKey} between ($RightID2 + 1) and $RightID1", Null, False, False);
		} else {
/*$SqlValueRight = '';
// i'm here
$sql = 'UPDATE ' . $this->table . ' SET '
. {$this->RightKey} . ' = CASE WHEN ' . {$this->LeftKey} . ' BETWEEN ' . $LeftID1 . ' AND ' . $RightID1 . ' THEN ' . {$this->RightKey} . ' + ' . ($RightID2 - $RightID1) . ' '
. 'WHEN ' . {$this->LeftKey} . ' BETWEEN ' . ($RightID1 + 1) . ' AND ' . $RightID2 . ' THEN ' . {$this->RightKey} . ' - ' . (($RightID1 - $LeftID1 + 1)) . ' ELSE ' . {$this->RightKey} . ' END, '
. {$this->LeftKey} . ' = CASE WHEN ' . {$this->LeftKey} . ' BETWEEN ' . $LeftID1 . ' AND ' . $RightID1 . ' THEN ' . {$this->LeftKey} . ' + ' . ($RightID2 - $RightID1) . ' '
. 'WHEN ' . {$this->LeftKey} . ' BETWEEN ' . ($RightID1 + 1) . ' AND ' . $RightID2 . ' THEN ' . {$this->LeftKey} . ' - ' . ($RightID1 - $LeftID1 + 1) . ' ELSE ' . {$this->LeftKey} . ' END '
. 'WHERE ' . {$this->LeftKey} . ' BETWEEN ' . $LeftID1 . ' AND ' . $RightID2;*/
		}
		
/*		if ($LeftID1 > $LeftID2) {
			$Value1 = $LeftID1 - $LeftID2 - ($RightID2 - $LeftID2 + 1); // $RightID1
			$this->SQL->Set($this->LeftKey, "case 
				when {$this->LeftKey} between $Between1 then {$this->LeftKey} - $Value1
				when {$this->LeftKey} between ($RightID2 + 1) and ($LeftID1 - 1) then {$this->LeftKey} + ($RightID1 - $LeftID1 + 1)
				else {$this->LeftKey} end", False, False);
			// INCORRECT. FIX ME
			$this->SQL->Set($this->LeftKey, "case 
				when {$this->LeftKey} between $Between1 then {$this->LeftKey} - $Value1
				when {$this->LeftKey} BETWEEN ($RightID2 + 1) and ($LeftID1 - 1) then {$this->LeftKey} + ($RightID1 - $LeftID1 + 1) 
				else {$this->LeftKey} end", False, False);
			$this->SQL->Where("{$this->LeftKey} between ($RightID2 + 1) and $RightID1", Null, False, False);
		} else {
			$this->SQL->Set($this->RightKey, "case 
				when {$this->LeftKey} between $Between1 then {$this->RightKey} + ($RightID2 - $RightID1)
				when {$this->LeftKey} between ($RightID1 + 1) and $RightID2 then {$this->RightKey} - ($RightID1 - $LeftID1 + 1)
				else {$this->LeftKey} end", False, False);
			$this->SQL->Set($this->LeftKey, "case 
				when {$this->LeftKey} between $Between1 then {$this->LeftKey} + ($RightID2 - $RightID1)
				when {$this->LeftKey} between ($RightID1 + 1) and $RightID2 then {$this->LeftKey} - ($RightID1 - $LeftID1 + 1) 
				else {$this->LeftKey} end", False, False);
			$this->SQL->Where("{$this->LeftKey} between $LeftID1 and $RightID2", Null, False, False);
		}*/
	}
	
	private function _ChangePositionBeforeQuery($LeftID1, $RightID1, $LeftID2) {
		$Between1 = "$LeftID1 and $RightID1";
		$Between2 = $LeftID2 . ' and ' . ($LeftID1 - 1);
		$Between3 = ($RightID1 + 1) . ' and ' . ($LeftID2 - 1);
		$DeltaRight = $RightID1 - $LeftID1 + 1;
		
		if ($LeftID1 > $LeftID2) {
			$DeltaLeft = $LeftID1 - $LeftID2;
			$this->SQL
				->Set($this->RightKey, "case
					when {$this->LeftKey} between $Between1 then {$this->RightKey} - $DeltaLeft
					when {$this->LeftKey} between $Between2 then {$this->RightKey} + $DeltaRight
					else {$this->RightKey} end", False, False)
				->Set($this->LeftKey, "case
					when {$this->LeftKey} between $Between1 then {$this->LeftKey} - $DeltaLeft
					when {$this->LeftKey} between $Between2 then {$this->LeftKey} + $DeltaRight
					else {$this->LeftKey} end", False, False)
				->Where("{$this->LeftKey} between $LeftID2 and $RightID1", Null, False, False);
		} else {
			$DeltaLeft = $LeftID2 - $LeftID1;
			$this->SQL
				->Set($this->RightKey, "case
					when {$this->LeftKey} between $Between1 then {$this->LeftKey} + ($DeltaLeft - $DeltaRight)
					when {$this->LeftKey} between $Between3 then {$this->LeftKey} - $DeltaRight 
					else {$this->LeftKey} end", False, False)
				->Set($this->LeftKey, "case
					WHEN {$this->LeftKey} between $Between1 then {$this->LeftKey} + ($DeltaLeft - $DeltaRight)
					WHEN {$this->LeftKey} between $Between3 then {$this->LeftKey} - $DeltaRight
					ELSE {$this->LeftKey} end", False, False)
				->Where("{$this->LeftKey} between $LeftID1 and ($LeftID2 - 1)", Null, False, False);
		}
	}
	
	/**
	* Delete element with number $id from the tree wihtout deleting it's children.
	*
	*/
/*	public function Delete($ID, $Where = '') {
		$Node = $this->GetNode($ID);
		if (!$Node) return $Node;
		list($LeftID, $RightID) = $this->_NodeValues($Node);
		
		$this->Database->BeginTransaction();
		$this->Delete(array($this->PrimaryKey => $ID));
		
		$this->SQL
			->Set($this->DepthKey, "case
				when {$this->LeftKey} between $LeftID and $RightID then {$this->DepthKey} - 1
				else {$this->DepthKey} end", False, False)
			->Set($this->LeftKey, "case
				when {$this->LeftKey} between $LeftID and $RightID then {$this->LeftKey} - 1
				when {$this->LeftKey} > $RightID then {$this->LeftKey} - 2
				else {$this->LeftKey} end", False, False)
			->Set($this->LeftKey, "case
				when {$this->LeftKey} between $LeftID and $RightID then {$this->LeftKey} - 1
				when {$this->LeftKey} > $RightID then {$this->LeftKey} - 2
				else {$this->LeftKey} end", False, False)
			->Where($this->LeftKey .' >', $LeftID, False, False);
		if (is_array($Where)) $this->Where($Where);

		$Result = $this->SQL->Update($this->Name)->Put();
		$this->Database->CommitTransaction();
		return $Result;
	}*/

	/**
	* Delete element with number $ID from the tree and all it children.
	*
	*/
/*	public function DeleteAll($ID, $Where = '') {
		// TODO:
		$Node = $this->GetNode($ID);
		if (!$Node) return $Node;
		list($LeftID, $RightID) = $this->_NodeValues($Node);
		$this->Database->BeginTransaction();
		$this->SQL
			->Where($this->LeftKey, "between $LeftID and $RightID", False, False)
			->Delete($this->Name);
		
		$DeltaID = (($RightID - $LeftID) + 1);
		
		//$this->SQL->Where($Where);
		$this->SQL
			->Update($this->Name)
			->Set($this->LeftKey, "when {$this->LeftKey} > $LeftID then {$this->LeftKey} - 1 - $DeltaID else {$this->LeftKey} end", False, False)
			->Set($this->RightKey, "when $this->RightKey > $LeftID then $this->RightKey - $DeltaID else {$this->LeftKey}")
			->Where($this->RightKey . ' >', $RightID)
			->Put();
		$this->Database->CommitTransaction();
		return TRUE;
	}*/

	/**
	* Returns all elements of the tree sortet by left.
	*
	*/
	public function Full($Fields = '*', $Where = False) {
		if (is_array($Where)) $this->SQL->Where($Where);
		$Result = $this->SQL
			->Select($Fields)
			->From($this->Name)
			->OrderBy($this->LeftKey)
			->Get();
		return $Result;
	}

	/**
	* Returns all elements of a branch starting from an element with number $ID.
	*
	*/
	public function Branch($NodeID, $Fields = 'a.*', $Where = False) {
		$NodeID = (int)$NodeID;
		if (is_array($Where)) $this->SQL->Where($Where);
		$Result = $this->SQL
			->Select($Fields)
			->From($this->Name . ' a')
			->From($this->Name . ' b')
			->SelectCase("a.{$this->LeftKey}", array("a.{$this->LeftKey} + 1 < a.{$this->RightKey}" => 1, '' => 0), 'N')
			->Where("b.{$this->PrimaryKey} = $NodeID", Null, False, False)
			->Where("a.{$this->LeftKey} >= b.{$this->LeftKey}", Null, False, False)
			->Where("a.{$this->RightKey} <= b.{$this->RightKey}", Null, False, False)
			->OrderBy("a.{$this->LeftKey}")
			->Get();
		return $Result;
	}

	/**
	* Returns all parents of element with number $ID.
	*
	*/
	
	public function Parents($NodeID, $Fields = 'a.*', $Where = False) { // $Where a.
		$NodeID = (int)$NodeID;
		if (is_array($Where)) $this->SQL->Where($Where);
		$Result = $this->SQL
			->Select($Fields)
			->From($this->Name . ' a')
			->From($this->Name . ' b')
			->SelectCase("a.{$this->LeftKey}", array("a.{$this->LeftKey} + 1 < a.{$this->RightKey}" => 1, '' => 0), 'N') // What is N? 
			->Where("b.{$this->PrimaryKey} = $NodeID", Null, False, False)
			->Where("b.{$this->LeftKey} between a.{$this->LeftKey} and a.{$this->RightKey}", Null, False, False)
			->OrderBy("a.{$this->LeftKey}")
			->Get();
		return $Result;
	}

	/**
	* Returns a slightly opened tree from an element with number $ID.
	*
	*/
	public function Ajar($ID, $Where = '') {
		$DataSet = $this->Parents($ID);
		$NumRows = $DataSet->NumRows();
		
		$this->SQL
			->Select('a.*')
			->From($this->Name . ' a')
			->BeginWhereGroup()
			->Where($this->DepthKey, 1, False, False);
		
		$i = 0;
		foreach ($DataSet->ResultArray() as $Row) {
			if ((++$i == $NumRows) && ($Row[$this->LeftKey] + 1) == $Row[$this->RightKey]) break;
			$this->SQL
				->OrOp()
				->BeginWhereGroup()
				->Where($this->DepthKey, $Row[$this->DepthKey] + 1)
				->Where($this->LeftKey.' >', $Row[$this->LeftKey])
				->Where($this->RightKey.' <', $Row[$this->RightKey])
				->EndWhereGroup();
		}
		$Result = $this->SQL
			->EndWhereGroup()
			->OrderBy($this->LeftKey)
			->Get();
		return $Result;
	}
	
	/**
	*
	*/
	protected function GetNodeWidth($NodeID) {
		$Width = $this->SQL
			->Select("{$this->RightKey} - {$this->RightKey} + 1", '', 'Width')
			->From($this->Name)
			->Where($this->PrimaryKey, $NodeID)
			->Get()
			->Value('Width');
		return $Width;
	}
	
	
	/**
	* Get all nodes without children
	*
	*/
	public function GetLeafs($Fields = '*') {
		$Result = $this->SQL
			->Select($Fields)
			->From($this->Name)
			->Where($this->RightKey, $this->LeftKey .'+1', False)
			->Get();
		return $Result;
	}

	
}




