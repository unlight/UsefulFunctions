<?php if (!defined('APPLICATION')) exit();


/**
* Credits:
* Kuzma Feskov <kuzma[at]russofile[dot]ru>
* http://php.russofile.ru/ru/authors/sql/nestedsets01/
* Rolf Brugger, edutech
* http://www.edutech.ch/contribution/nstrees
* 
*/

class TreeModel extends Gdn_Model {
	
	protected $LeftKey = 'TreeLeft';
	protected $RightKey = 'TreeRight';
	protected $DepthKey = 'Depth';
	protected $ParentKey = 'ParentID';
	protected $CachedNodeResults = array();
	
	/**
	* Takes max value of $ExternalRowID or possible NextID of primary key
	* and sets it as auto_increment value.
	* 
	* @return NULL.
	*/
	public function AutoIncrement($ExternalRowID) {
		$AutoIncrementValue = max($ExternalRowID, $this->GetMaxID() + 1);
		$Px = $this->SQL->Database->DatabasePrefix;
		$this->SQL->Query("ALTER TABLE {$Px}{$this->Name} auto_increment = {$AutoIncrementValue}");
	}
	
	
	/**
	* Undocumented 
	* 
	* @return mixed $Result.
	*/
	public function GetMaxID() {
		$MaxID = $this->SQL
			->Select($this->PrimaryKey, 'max', 'MaxID')
			->From($this->Name)
			->Get()
			->FirstRow()
			->MaxID;
		return $MaxID;
	}
	
	/**
	* Sets initial parameters of a tree and creates root of tree
	* ATTENTION, all previous values in table will be destroyed.
	*/
	public function Clear($Set = Null) {
		$this->SQL->Truncate($this->Name);
		if (is_array($Set)) $this->SQL->Insert($this->Name, $Set);
	}
	
	
	/** Converts from the lists of the adjacency to nested sets
	* @param id
	* @param identifier of the first free value
	* credit: http://phpclub.ru/faq/Tree/DemoAlNs
	*/
	public function AjacencyListToNestedSets($ParentID = 1, $SideID = 1) {
		$TreeDataSet = $this->SQL
			->From($this->Name)
			->Select($this->PrimaryKey)
			->Where($this->ParentKey, $ParentID)
			->OrderBy($this->LeftKey, 'asc')
			->Get()
			->Result();
		foreach ($TreeDataSet as $Node) {
			$NodeID = $Node->{$this->PrimaryKey};
			$RightID = $this->AjacencyListToNestedSets($NodeID, $SideID + 1);
			// TODO: Update Depth here
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
	* Script for building/re-building nested set/tree from adjacency list model. 
	* http://klepa.co/2011/02/16/adjacency-list-nested-set-model-mptt/
	* 
	* @param int $ParentID 
	* @param int $LeftID 
	* @return mixed $SideID.
	*/
	public function RebuildTree($ParentID, $LeftID) {
		// The right value of this node is the left value + 1
		$RightID = $LeftID + 1;
		// Get all children of this node
		$TreeDataSet = $this->SQL
			->From($this->Name)
			->Select($this->PrimaryKey)
			->Where($this->ParentKey, $ParentID)
			->OrderBy($this->LeftKey, 'asc')
			->Get()
			->Result();
		// Recursive execution of this function for each child of this node
		// $RightID is the current right value, which is incremented by the rebuild_tree function
		foreach ($TreeDataSet as $Node) {
			$RightID = $this->RebuildTree($Node->{$this->PrimaryKey}, $RightID);
		}
		// We've got the left value, and now that we've processed the children of this node we also know the right value
		$this->SQL
			->Update($this->Name)
			->Set($this->LeftKey, $LeftID)
			->Set($this->RightKey, $RightID)
			->Where($this->PrimaryKey, $ParentID)
			->Put();
		// Return the right value of this node + 1
		return $RightID + 1;
	}

	
	/**
	* Set Depth for all nodes.
	* Use after AjacencyListToNestedSets().
	* 
	* @return Null.
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
	
	/**
	* Undocumented 
	* 
	* @param string $ValueField 
	* @param DataSet $ValueField 
	* @return array $Result.
	*/
	public function DropDownArray($ValueField, $DataSet) {
		$Result = array();
		foreach ($DataSet as $Node) {
			$Value = $Node->$ValueField;
			if ($Node->Depth - 1 > 0) {
				$Value = str_repeat('  ', $Node->Depth - 1) . $Value;
			}
			$Result[$Node->{$this->PrimaryKey}] = $Value;
		}
		return $Result;
	}
	
	
	/**
	* Undocumented 
	* 
	* @param mixed 
	* @return mixed $Result.
	*/
	protected function SelectNodeFields($TableAlias = '') {
		if ($TableAlias != '') $TableAlias .= '.';
		$this->SQL
			->Select($TableAlias.$this->LeftKey)
			->Select($TableAlias.$this->RightKey)
			->Select($TableAlias.$this->DepthKey)
			->Select($TableAlias.$this->PrimaryKey)
			->Select($TableAlias.$this->ParentKey);
		return $this->SQL;
	}

	/**
	* Undocumented 
	* 
	* @param mixed 
	* @return mixed $Result.
	*/
	protected function NodeValues($Node) {
		if (!is_object($Node)) {
			$Node = $this->GetNode($Node);
			if ($Node === False) {
				trigger_error(ErrorMessage('Empty node.', __CLASS__, __FUNCTION__, VarDump(func_get_arg(0))));
				return False;
			}
		}
		$Result = array_map('intval', array(
			$Node->{$this->LeftKey}, 
			$Node->{$this->RightKey}, 
			$Node->{$this->DepthKey}, 
			$Node->{$this->PrimaryKey},
			$Node->{$this->ParentKey}
		));
		return $Result;
	}
	
	/**
	* Adds a new child element to the top of the list of child elements.
	* 
	* @param mixed $Parent.
	* @param mixed $Data.
	* @return mixed $Result.
	*/
	public function AppendTo($Parent, $Data) {
		if (is_numeric($Parent)) $Parent = $this->GetNode($Parent);
		list($ParentLeftID, $ParentRightID, $ParentDepth, $ParentID) = $this->NodeValues($Parent);
		
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
	* Adds a new child element to the top of the list of child elements.
	* 
	* @param mixed $Parent.
	* @param mixed $Data.
	* @return mixed $Result.
	*/
	public function PrependTo($Parent, $Data) {

		list($ParentLeftID, $ParentRightID, $ParentDepth, $ParentID) = $this->NodeValues($Parent);
		
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
	* Add a new node in the tree to parent $Node.
	*
	*
	* @param $Node 
	* @param $Data
	* @return integer Inserted node id
	*/
	public function InsertNode($Node, $Data) {
		list($LeftID, $RightID, $Depth, $NodeID) = $this->NodeValues($Node);
		if (!$NodeID) return False;
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
		
		// All left and right keys are changed, reset cache.
		$this->CachedNodeResults = array();
		
		return $ResultID;
	}
	
	
	
	/**
	* Add a new node in the tree near $Node.
	*
	* @param integer|object $Node 	Parent node object or ID of parent node.
	* @return integer Inserted node id
	*/
	public function InsertNear($Node, $Data) {
		list($LeftID, $RightID, $Depth, $NodeID) = $this->NodeValues($Node);
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
		$this->CachedNodeResults = array();
		return $ResultID;
	}
	
	/**
	* Assigns a node with all its children to another parent.
	* 
	* @param mixed $Node, id of node or node object.
	* @param mixed $NewParent, parent id of node or parent node object.
	* @return mixed $Result.
	*/
	public function MoveAll($Node, $NewParent, $Where = False) {
		
		list($LeftID, $RightID, $Depth, $NodeID) = $this->NodeValues($Node);
		list($ParentLeftID, $ParentRightID, $ParentDepth, $ParentNodeID) = $this->NodeValues($NewParent);
		
		if ($NodeID == $ParentNodeID 
			|| $LeftID == $ParentLeftID 
			|| ($ParentLeftID >= $LeftID && $ParentLeftID <= $RightID)
			|| ($Depth == $ParentDepth+1 && $LeftID > $ParentLeftID && $RightID < $ParentRightID)) {
			return False;
		}
		
		$DeltaDepth = sprintf('%+d', -($Depth-1) + $ParentDepth);
		$SqlValueDepth = "case 
			when $this->LeftKey between $LeftID and $RightID then $this->DepthKey $DeltaDepth
			else $this->DepthKey end";
		$DeltaRL = $RightID - $LeftID + 1;
		$Sets[$this->DepthKey] = $SqlValueDepth; // ORDER OF SET FIELDS IS IMPORTANT!
		
		if ($ParentLeftID < $LeftID && $ParentRightID > $RightID && $ParentDepth < $Depth - 1) {
			$DeltaTree = (($ParentRightID - $RightID - $Depth + $ParentDepth) / 2) * 2 + $Depth - $ParentDepth - 1;
			$SqlValueRight = "case 
				when $this->RightKey between ($RightID+1) and ($ParentRightID-1) then $this->RightKey - $DeltaRL
				when $this->LeftKey between $LeftID and $RightID then $this->RightKey + $DeltaTree
				else $this->RightKey end";
			$SqlValueLeft = "case 
				when $this->LeftKey between ($RightID+1) and ($ParentRightID-1) then $this->LeftKey - $DeltaRL
				when $this->LeftKey between $LeftID and $RightID then $this->LeftKey + $DeltaTree
				else $this->LeftKey end";
			$Sets += array($this->RightKey => $SqlValueRight, $this->LeftKey => $SqlValueLeft);
			$WhereCondition = "$this->LeftKey between ($ParentLeftID + 1) and ($ParentRightID - 1)";
		} elseif ($ParentLeftID < $LeftID) {
			$SqlValueLeft = "case 
				when $this->LeftKey between $ParentRightID and ($LeftID-1) then $this->LeftKey + $DeltaRL
				when $this->LeftKey between $LeftID and $RightID then $this->LeftKey - ($LeftID-$ParentRightID)
				else $this->LeftKey end";
			$SqlValueRight = "case 
				when $this->RightKey between $ParentRightID and $LeftID then $this->RightKey + $DeltaRL
				when $this->RightKey between $LeftID and $RightID then $this->RightKey - ($LeftID-$ParentRightID) 
				else $this->RightKey end";
			$Sets += array($this->LeftKey => $SqlValueLeft, $this->RightKey => $SqlValueRight);
			$WhereCondition = "$this->LeftKey between $ParentLeftID and $RightID or $this->RightKey between $ParentLeftID and $RightID";
		} else {
			$SqlValueLeft = "case 
				when $this->LeftKey between $RightID and $ParentRightID then $this->LeftKey - $DeltaRL
				when $this->LeftKey between $LeftID and $RightID then $this->LeftKey + ($ParentRightID-1-$RightID)
				else $this->LeftKey end";
			$SqlValueRight = "case
				when $this->RightKey between ($RightID+1) and ($ParentRightID-1) then $this->RightKey - $DeltaRL
				when $this->RightKey between $LeftID and $RightID then $this->RightKey + ($ParentRightID-1-$RightID)
				else $this->RightKey end";
			$Sets += array($this->LeftKey => $SqlValueLeft, $this->RightKey => $SqlValueRight);
			$WhereCondition = "$this->LeftKey between $LeftID and $ParentRightID or $this->RightKey between $LeftID and $ParentRightID";
		}
		
		//if (is_array($Where)) $this->SQL->Where($Where);
		
		$this->Database->BeginTransaction();
		
		$Result = $this->SQL
			->Update($this->Name)
			->Set($this->ParentKey, $ParentNodeID)
			->Where($this->PrimaryKey, $NodeID)
			->Put();
		
		foreach ($Sets as $Field => $SqlValue) $this->SQL->Set($Field, $SqlValue, False, False);
		$Result = $this->SQL
			->Update($this->Name)
			->Where("($WhereCondition)", Null, False, False)
			->Put();
		
		$this->Database->CommitTransaction();
		$this->CachedNodeResults = array();
		
		return $Result;
	}
	
	/**
	* Change items position.
	* 
	* @param mixed $ID1, id of node or node object.
	* @param mixed $ID2, id of node or node object.
	* @return mixed $Result.
	*/
	public function ChangePosition($ID1, $ID2) {
		
		list($LeftID1, $RightID1, $Depth1, $NodeID1, $ParentID1) = $this->NodeValues($ID1);
		list($LeftID2, $RightID2, $Depth2, $NodeID2, $ParentID2) = $this->NodeValues($ID2);
		if (!$NodeID1 || !$NodeID1) return False;

		$this->Database->BeginTransaction();
		
		$this->SQL
			->Update($this->Name)
			->Set($this->LeftKey, $LeftID2)
			->Set($this->RightKey, $RightID2)
			->Set($this->DepthKey, $Depth2)
			->Set($this->ParentKey, $ParentID2)
			->Where($this->PrimaryKey, $NodeID1, False, False)
			->Put();
	
		$Result = $this->SQL
			->Update($this->Name)
			->Set($this->LeftKey, $LeftID1)
			->Set($this->RightKey, $RightID1)
			->Set($this->DepthKey, $Depth1)
			->Set($this->ParentKey, $ParentID1)
			->Where($this->PrimaryKey, $NodeID2, False, False)
			->Put();
		
		$this->Database->CommitTransaction();
		$this->CachedNodeResults = array();
		return $Result;
	}
	
	/**
	* Swapping nodes within the same level and limits of one parent with all its children: 
	* $ID1 placed before or after $ID2.
	* 
	* @param mixed $ID1.
	* @param mixed $ID2.
	* @param mixed $Position, default after.
	* @param mixed $Where.
	* @return mixed $Result.
	*/
	public function ChangePositionAll($ID1, $ID2, $Position = 'after', $Where = False) {
		list($LeftID1, $RightID1, $Depth1, $NodeID1) = $this->NodeValues($ID1);
		if (!$NodeID1) return False;
		list($LeftID2, $RightID2, $Depth2, $NodeID2) = $this->NodeValues($ID2);
		if (!$NodeID2) return False;

		if ($Depth1 != $Depth2) {
			trigger_error('Cannot change position.', E_USER_ERROR);
			return False;
		}
		
		switch (strtolower($Position)) {
			case 'before': {
				$Between1 = "$LeftID1 and $RightID1";
				$Between2 = $LeftID2 . ' and ' . ($LeftID1 - 1);
				$Between3 = ($RightID1 + 1) . ' and ' . ($LeftID2 - 1);
				$DeltaRight = $RightID1 - $LeftID1 + 1;
				
				if ($LeftID1 > $LeftID2) {
					$DeltaLeft = $LeftID1 - $LeftID2;
					$SqlValueRight = "case
						when {$this->LeftKey} between $Between1 then {$this->RightKey} - $DeltaLeft
						when {$this->LeftKey} between $Between2 then {$this->RightKey} + $DeltaRight
						else {$this->RightKey} end";
					$SqlValueLeft = "case
						when {$this->LeftKey} between $Between1 then {$this->LeftKey} - $DeltaLeft
						when {$this->LeftKey} between $Between2 then {$this->LeftKey} + $DeltaRight
						else {$this->LeftKey} end";
					$WhereCondition = "{$this->LeftKey} between $LeftID2 and $RightID1";
				} else {
					$DeltaLeft = $LeftID2 - $LeftID1;
					$SqlValueRight = "case
						when {$this->LeftKey} between $Between1 then {$this->LeftKey} + ($DeltaLeft - $DeltaRight)
						when {$this->LeftKey} between $Between3 then {$this->LeftKey} - $DeltaRight 
						else {$this->LeftKey} end";
					$SqlValueLeft = "case
						when {$this->LeftKey} between $Between1 then {$this->LeftKey} + ($DeltaLeft - $DeltaRight)
						when {$this->LeftKey} between $Between3 then {$this->LeftKey} - $DeltaRight
						else {$this->LeftKey} end";
					$WhereCondition = "{$this->LeftKey} between $LeftID1 and ($LeftID2 - 1)";
				}
			} break;
			case 'after': {
				if ($LeftID1 > $LeftID2) {
					$SqlValueRight = "case 
						when {$this->LeftKey} between $LeftID1 and $RightID1 then {$this->RightKey} - ($LeftID1 - $LeftID2 - ($RightID2 - $LeftID2 + 1))
						when {$this->LeftKey} between ($RightID2 + 1) and ($LeftID1 - 1) then {$this->RightKey} + ($RightID1 - $LeftID1 + 1)
						else {$this->RightKey} end";
					$SqlValueLeft = "case 
						when {$this->LeftKey} between $LeftID1 and $RightID1 then {$this->LeftKey} - ($LeftID1 - $LeftID2 - ($RightID2 - $LeftID2 + 1))
						when {$this->LeftKey} between ($RightID2 + 1) and ($LeftID1 - 1) then {$this->LeftKey} + ($RightID1 - $LeftID1 + 1) 
						else {$this->LeftKey} end";
					$WhereCondition = "{$this->LeftKey} between ($RightID2 + 1) and $RightID1";
				} else {
					$SqlValueRight = "case 
						when {$this->LeftKey} between $LeftID1 and $RightID1 then {$this->RightKey} + ($RightID2 - $RightID1)
						when {$this->LeftKey} between ($RightID1 + 1) and $RightID2 then {$this->RightKey} - ($RightID1 - $LeftID1 + 1)
						else {$this->RightKey} end";
					$SqlValueLeft = "case
						when {$this->LeftKey} between $LeftID1 and $RightID1 then {$this->LeftKey} + ($RightID2 - $RightID1)
						when {$this->LeftKey} between ($RightID1 + 1) and $RightID2 then {$this->LeftKey} - ($RightID1 - $LeftID1 + 1) 
						else {$this->LeftKey} end";
					$WhereCondition = "{$this->LeftKey} between $LeftID1 and $RightID2";
				}
			} break;
			default: {
				trigger_error('Unknown position.', E_USER_ERROR);
				return False;
			}
		}
		
		//if (is_array($Where)) $this->SQL->Where($Where);
		$Result = $this->SQL
			->Update($this->Name)
			->Set($this->RightKey, $SqlValueRight, False, False)
			->Set($this->LeftKey, $SqlValueLeft, False, False)
			->Where($WhereCondition, Null, False, False)
			->Put();
		
		$this->CachedNodeResults = array();
		
		return $Result;
	}
	
	/**
	* Delete $Node from the tree without deleting it's children.
	* 
	* @param mixed $Node, id of node or node object.
	* @return mixed $Result.
	*/
	public function Delete($Node, $Where = False) {
		list($LeftID, $RightID, $Depth, $NodeID) = $this->NodeValues($Node);
		if (!$NodeID) return False;
		
		$this->Database->BeginTransaction();
		parent::Delete(array($this->PrimaryKey => $NodeID));
		
		$SqlValueDepth = "case
			when {$this->LeftKey} between $LeftID and $RightID then {$this->DepthKey} - 1
			else {$this->DepthKey} end";
		$SqlValueRight = "case 
			when {$this->RightKey} between $LeftID and $RightID then {$this->RightKey} - 1
			when {$this->RightKey} > $RightID then {$this->RightKey} - 2
			else {$this->RightKey} end";
		$SqlValueLeft = "case 
			when {$this->LeftKey} between $LeftID and $RightID then {$this->LeftKey} - 1
			when {$this->LeftKey} > $RightID then {$this->LeftKey} - 2 
			else {$this->LeftKey} end";

		$Result = $this->SQL
			->Update($this->Name)
			->Set($this->DepthKey, $SqlValueDepth, False, False)
			->Set($this->RightKey, $SqlValueRight, False, False)
			->Set($this->LeftKey, $SqlValueLeft, False, False)
			->Where($this->RightKey .' >', $LeftID, False, False)
			->Put();
		$this->Database->CommitTransaction();
		$this->CachedNodeResults = array();
		return $Result;
	}

	/**
	* Delete node $Node from the tree and all it children.
	* 
	* @param mixed $Node, id of node or node object.
	* @return mixed $Result.
	*/
	public function DeleteAll($Node, $Where = False) {
		list($LeftID, $RightID, $Depth, $NodeID) = $this->NodeValues($Node);
		if (!$NodeID) return False;
		$this->Database->BeginTransaction();
		
		$this->SQL
			->Where($this->LeftKey, "between $LeftID and $RightID", False, False)
			->Delete($this->Name);
		
		$DeltaID = $RightID - $LeftID + 1;
		$SqlValueLeft = "case 
			when {$this->LeftKey} > $LeftID then {$this->LeftKey} - $DeltaID 
			else {$this->LeftKey} end";
		$SqlValueRight = "case 
			when {$this->RightKey} > $LeftID then $this->RightKey - $DeltaID 
			else {$this->RightKey} end";
		
		//if (is_array($Where)) $this->SQL->Where($Where);
		$Result = $this->SQL
			->Update($this->Name)
			->Set($this->LeftKey, $SqlValueLeft, False, False)
			->Set($this->RightKey, $SqlValueRight, False, False)
			->Where($this->RightKey . ' >', $RightID)
			->Put();
			
		$this->Database->CommitTransaction();
		$this->CachedNodeResults = array();
		return $Result;
	}
	
	/**
	* Returns all elements of a branch starting from $NodeID.
	* 
	* @param int $NodeID.
	* @param mixed $Conditions	Where conditions and/or options.
	* @return Gdn_DataSet $Result.
	*/
	public function Branch($NodeID, $Conditions = False) {
		$NodeID = (int)$NodeID;
		$Fields = GetValue('Fields', $Conditions, 'a.*', True);
		if (is_array($Conditions)) $this->SQL->Where($Conditions);
		$Result = $this->SQL
			->Select($Fields)
			->From($this->Name . ' a')
			->From($this->Name . ' b')
			//->SelectCase("a.{$this->LeftKey}", array("a.{$this->LeftKey} + 1 < a.{$this->RightKey}" => 1, '' => 0), 'N')
			->Where("b.{$this->PrimaryKey} = $NodeID", Null, False, False)
			->Where("a.{$this->LeftKey} >= b.{$this->LeftKey}", Null, False, False)
			->Where("a.{$this->RightKey} <= b.{$this->RightKey}", Null, False, False)
			->OrderBy("a.{$this->LeftKey}")
			->Get();
		return $Result;
	}

	/**
	* Returns all parents of node $NodeID.
	* 
	* @param int $NodeID.
	* @param mixed $Conditions.
	* @return mixed $Result.
	*/
	public function Parents($NodeID, $Conditions = False) {
		$NodeID = (int)$NodeID;
		$Fields = GetValue('Fields', $Conditions, 'a.*', True);
		if (is_array($Conditions)) $this->SQL->Where($Conditions);
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
	* Returns a slightly opened tree from a $Node.
	* 
	* @param mixed $Node.
	* @param mixed $Where.
	* @return mixed $Result.
	*/
	public function Ajar($Node, $Where = False) {
		
		if ($Node instanceof Gdn_DataSet) $DataSet = $Node; // Parents
		elseif ($Node instanceof StdClass) $DataSet = $this->Parents($Node->{$this->PrimaryKey});
		else $DataSet = $this->Parents($Node); // Integer
		
		if ($DataSet instanceof Gdn_DataSet) $NumRows = $DataSet->NumRows();
		else $NumRows = count($DataSet);
		
		$this->SQL
			->Select('a.*')
			->From($this->Name . ' a')
			->BeginWhereGroup()
			->Where($this->DepthKey, 1, False, False);
		
		$i = 0;
		foreach ($DataSet as $Row) {
			$Row = (array)$Row;
			if ((++$i == $NumRows) && ($Row[$this->LeftKey] + 1) == $Row[$this->RightKey]) break;
			$this->SQL
				->OrOp()
				->BeginWhereGroup()
				->Where($this->DepthKey, $Row[$this->DepthKey] + 1)
				->Where($this->LeftKey.' >', $Row[$this->LeftKey])
				->Where($this->RightKey.' <', $Row[$this->RightKey])
				->EndWhereGroup();
		}
		$this->SQL->EndWhereGroup();
		if (is_array($Where)) $this->SQL->Where($Where);
		$Result = $this->SQL
			->OrderBy($this->LeftKey)
			->Get();
		return $Result;
	}
	
	/**
	* Gets parent node of $Node.
	* 
	* @param mixed $Node, id of node or node object.
	* @return mixed $Result.
	*/
	public function GetParent($Node, $Conditions = False) {
		list($LeftID, $RightID, $Depth, $NodeID) = $this->NodeValues($Node);
		if (!$NodeID) return False;
		$Conditions[$this->LeftKey.' <'] = $LeftID;
		$Conditions[$this->RightKey.' >'] = $RightID;
		$Conditions[$this->DepthKey] = $Depth - 1;
		$Conditions['CacheNodes'] = True;
		$Result = $this->GetNodes($Conditions);
		return $Result;
	}
	
	
	/**
	* Get siblings of $Node using ParentID.
	* 
	* @param mixed $Node
	* @param array $Conditions
	* @return mixed $Result		Dataset collection.
	*/
	public function GetSiblings($Node, $Conditions = False) {
		if (is_object($Node)) $ParentID = $Node->ParentID;
		else $ParentID = $Node; // if $Node is numeric 
		$Conditions[$this->ParentKey] = $ParentID;
		$Result = $this->GetNodes($Conditions);
		return $Result;
	}
	
	/**
	* Gets children nodes of $Node.
	* 
	* @param mixed $Node
	* @param mixed $Conditions
	* @return mixed $Result		Dataset collection.
	*/
	
	public function GetChildren($Node, $Conditions = False) {
		list($LeftID, $RightID, $Depth, $NodeID) = $this->NodeValues($Node);

		$DirectDescendants = GetValue('DirectDescendants', $Conditions, False, True);
		if ($DirectDescendants !== False) $Conditions[$this->DepthKey] = $Depth + 1;
		
		$IncludeSelf = GetValue('IncludeSelf', $Conditions, False, True);
		$OperatorSuffix = ($IncludeSelf) ? '=' : '';
		
		$Conditions[$this->LeftKey . ' >'.$OperatorSuffix] = $LeftID;
		$Conditions[$this->RightKey . '<'.$OperatorSuffix] = $RightID;
		$Result = $this->GetNodes($Conditions);
		return $Result;
	}
	
	/**
	* Returns all elements of the tree sorted by left.
	* Where options:
	* 	DirectDescendants
	* 	WithNoChildrens
	* 	
	* @param mixed $Conditions	Where conditions and/or options.
	* @return mixed $Result		Dataset collection.
	*/
	public function GetNodes($Conditions = False) {
		$CacheNodes = GetValue('CacheNodes', $Conditions, False, True);
		$Fields = GetValue('Fields', $Conditions, '*', True);
		
		if (GetValue('WithNoChildrens', $Conditions, False, True)) 
			$this->SQL->Where("($this->RightKey - $this->LeftKey)", 1, False, False);
		if (GetValue('IncludeRoot', $Conditions, True, True) == False) 
			$Conditions["{$this->PrimaryKey} <>"] = 1;
		
		if (is_array($Conditions)) $this->SQL->Where($Conditions);
		$Result = $this->SQL
			->Select($Fields)
			->From($this->Name)
			->OrderBy($this->LeftKey)
			->Get();
		if ($CacheNodes) foreach ($Result as $Data) $this->CachedNodeResults[$Data->{$this->PrimaryKey}] = $Data;
		return $Result;
	}
	
	
	/**
	* Receives left, right and level for node with number $NodeID.
	* 
	* @param int $NodeID.
	* @param array $Conditions.
	*	ResetCache
	* @return mixed $Result.
	*/
	public function GetNode($NodeID, $Conditions = False) {
		if (!is_numeric($NodeID)) trigger_error(sprintf("Not numeric NodeID (%s)", VarDump($NodeID)), E_USER_ERROR);
		$Result =& $this->CachedNodeResults[$NodeID];
		$ResetCache = GetValue('ResetCache', $Conditions, False, True);
		if ($ResetCache) $Result = Null;
		if ($Result === Null) {
			$Conditions[$this->PrimaryKey] = $NodeID;
			$Conditions['CacheNodes'] = True;
			$this->GetNodes($Conditions);
			// If still null set to false because node with $NodeID is not exists.
			if (is_null($Result)) $Result = False;
		}
		return $Result;
	}
	
	/**
	* Returns root; 
	* 
	* @return object $Root.
	*/
	public function GetRoot() {
		$Result = $this->GetNode(1);
		return $Result;
	}

	/**
	* Get all nodes without children.
	* 
	* @param mixed $Conditions. 
	* @return mixed $Result.
	*/
	public function GetLeafs($Conditions = False) {
		$this->SQL->Where($this->RightKey, $this->LeftKey .'+1', False);
		$Result = $this->GetNodes($Conditions);
		return $Result;
	}
	
	/**
	* Undocumented 
	* 
	* @param mixed $Node.
	* @return mixed $Result.
	*/
	protected function GetNodeWidth($Node) {
		list($LeftID, $RightID, $Depth, $NodeID) = $this->NodeValues($Node);
		$Width = ($RightID - $LeftID) + 1;
		return $Width;
	}
	
	
	/**
	* Get corrupted nodes of tree.
	* 
	* @return mixed $Result.
	*/
	public function GetCorruptedRows() {
		$SQL = Gdn::SQL();
		// 1. Left key is always less than the right
		$CorruptedSql[] = $this
			->SelectNodeFields()
			->Select("Name, 'Dummy' as M")
			->From($this->Name)
			->Where($this->LeftKey . '>=', $this->RightKey, False, False)
			->GetSelect();
		$SQL->Reset();
		// TODO: 2. The least left key is always 1
		// TODO: 3. The greatest right key is always equal to doubled number of nodes
		
		// 4. The difference between right and left key is always the odd number
		// N % 2, use %% because called as $Field = sprintf($Function, $Field); Prevents error printf(): Too few arguments.
		$CorruptedSql[] = $this
			->SelectNodeFields()
			->Select("Name")
			->From($this->Name)
			->Select("$this->RightKey - $this->LeftKey", '(%s) %% 2', 'M')
			->Having('M', 0, False, False)
			->GetSelect();
		$SQL->Reset();
		// 5. If the level of the node is an odd number then the left key is always an odd number, the same thing for even numbers;
		$CorruptedSql[] = $this
			->SelectNodeFields()
			->Select("Name")
			->From($this->Name)
			->Select("$this->LeftKey - $this->DepthKey + 2", '(%s) %% 2', 'M')
			->Having('M', 0, False, False)
			->GetSelect();
		$SQL->Reset();
		
		// 6. The left and right keys are always unique
		$CorruptedSql[] = $this
			->SelectNodeFields('t1')
			->Select('t1.Name')
			->From(array($this->Name .' t1', $this->Name .' t2', $this->Name .' t3'))
			->Select('t1.'.$this->PrimaryKey, 'count', 'Rep')
			->Where("t1.$this->LeftKey <> t2.$this->LeftKey", Null, False, False)
			->Where("t1.$this->LeftKey <> t2.$this->RightKey", Null, False, False)
			->Where("t1.$this->RightKey <> t2.$this->LeftKey", Null, False, False)
			->Where("t1.$this->RightKey <> t2.$this->RightKey", Null, False, False)
			->GroupBy('t1.'.$this->PrimaryKey)
			->Having("max(t3.$this->RightKey) <>", "sqrt(4 * Rep + 1) + 1", False, False)
			->GetSelect();
		$SQL->Reset();
		
		$Table = implode("\nunion all\n", $CorruptedSql);
		$SqlQuery = "select * from ($Table) as t group by t.$this->PrimaryKey";
		$Result = $SQL->Query($SqlQuery);
	
		return $Result;
	}
	
	
	# Part of Nested Set Tree Library by Rolf Brugger
	# http://www.edutech.ch/contribution/nstrees
	
	public function HasChildren($Node) {
		list($LeftID, $RightID) = $this->NodeValues($Node);
		$Result = (($RightID - $LeftID) > 1);
		return $Result;
	}
	
	public function IsRoot($Node) {
		list($LeftID) = $this->NodeValues($Node);
		return ($LeftID == 1);
	}
	
	public function IsLeaf($Node) {
		list($LeftID, $RightID) = $this->NodeValues($Node);
		$Result = (($RightID - $LeftID) == 1);
		return $Result;
	}
	
	/**
	* Returns true, if $Node1 is a direct child or in the subtree of $Node2.
	* 
	* @param mixed $Node1.
	* @param mixed $Node2.
	* @return mixed $Result.
	*/
	public function IsChild($Node1, $Node2) {
		list($LeftID1, $RightID1, $Depth1, $NodeID1) = $this->NodeValues($Node1);
		list($LeftID2, $RightID2, $Depth2, $NodeID2) = $this->NodeValues($Node2);
		$Result = (($LeftID1 > $LeftID2) && $RightID1 < $RightID2);
		return $Result;
	}
	
	
	// DEPRECATED
	
	public function GetChildrens($Node, $Where = False) {
		if (Debug()) trigger_error('Function renamed to GetChildren().');
		return $this->GetChildren($Node, $Where);
	}
	
	protected function _NodeValues($Node) {
		if (Debug()) trigger_error('Function renamed to NodeValues().');
		return $this->NodeValues($Node);
	}
	
	public function Full($Fields = '', $Where = False) {
		if (Debug()) Deprecated('Full', 'GetNodes');
		if ($Fields) $Where['Fields'] = $Fields;
		return $this->GetNodes($Where);
	}
	
}




