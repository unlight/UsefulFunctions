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
	protected $CachedNodeResults;
	
	/**
	* Undocumented 
	* 
	* @return object $Root.
	*/
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
	* Receives left, right and level for node with number $ID.
	* 
	* @param int $ID.
	* @param mixed $ResetCache.
	* @return mixed $Result.
	*/
	public function GetNode($ID, $ResetCache = False) {
		$Result =& $this->CachedNodeResults[$ID];
		if (!isset($this->CachedNodeResults[$ID]) || $ResetCache) {
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
			->OrderBy($this->TreeLeft, 'asc')
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
	
	// TODO: MAKE STATIC (need make static $this->PrimaryKey)
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
			->Select('TreeRight - TreeLeft', '(%s) %% 2', 'M')
			->Having('M', 0, False, False)
			->GetSelect();
		$SQL->Reset();
		// 5.If the level of the node is an odd number then the left key is always an odd number, the same thing for even numbers;
		$SQL->Reset();
		$CorruptedSql[] = $this
			->SelectNodeFields()
			->Select("Name")
			->From($this->Name)
			->Select('TreeLeft - Depth + 2', '(%s) %% 2', 'M')
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
	
	// TODO: MAKE IT STATIC
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
	* Receives parent left, right and level for unit with number $ID.
	* 
	* @param mixed $ID, id of node or node object.
	* @return mixed $Result.
	*/
	public function GetParent($ID) {
		list($LeftID, $RightID, $Depth, $NodeID) = $this->_NodeValues($ID);
		if (!$NodeID) return False;
		$Depth = $Depth - 1;
		//$Result =& $this->CachedNodeResults[$ID];
		$Result = $this->SQL
			//->SelectNodeFields()
			->Select('*')
			->From($this->Name)
			->Where($this->LeftKey.' <', $LeftID)
			->Where($this->RightKey.' >', $RightID)
			->Where($this->DepthKey, $Depth)
			->OrderBy($this->LeftKey)
			->Get()
			->FirstRow();
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
	* Adds a new child element to the top of the list of child elements.
	* 
	* @param mixed $Parent.
	* @param mixed $Data.
	* @return mixed $Result.
	*/
	public function PrependTo($Parent, $Data) {

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
	* Add a new element in the tree to element with number $ID.
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
	
	/**
	* Assigns a node with all its children to another parent.
	* 
	* @param mixed $ID, id of node or node object.
	* @param mixed $NewParentID, parent id of node or parent node object.
	* @return mixed $Result.
	*/
	public function MoveAll($ID, $NewParentID, $Where = False) {
		
		list($LeftID, $RightID, $Depth, $NodeID) = $this->_NodeValues($ID);
		list($ParentLeftID, $ParentRightID, $ParentDepth, $ParentNodeID) = $this->_NodeValues($NewParentID);
		
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
		
		list($LeftID1, $RightID1, $Depth1, $NodeID1) = $this->_NodeValues($ID1);
		list($LeftID2, $RightID2, $Depth2, $NodeID2) = $this->_NodeValues($ID2);
		if (!$NodeID1 || !$NodeID1) return False;

		$this->Database->BeginTransaction();
		
		$this->SQL
			->Update($this->Name)
			->Set($this->LeftKey, $LeftID2)
			->Set($this->RightKey, $RightID2)
			->Set($this->DepthKey, $Depth2)
			->Where($this->PrimaryKey, $NodeID1, False, False)
			->Put();
	
		$Result = $this->SQL
			->Update($this->Name)
			->Set($this->LeftKey, $LeftID1)
			->Set($this->RightKey, $RightID1)
			->Set($this->DepthKey, $Depth1)
			->Where($this->PrimaryKey, $NodeID2, False, False)
			->Put();
		
		$this->Database->CommitTransaction();
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
		list($LeftID1, $RightID1, $Depth1, $NodeID1) = $this->_NodeValues($ID1);
		if (!$NodeID1) return False;
		list($LeftID2, $RightID2, $Depth2, $NodeID2) = $this->_NodeValues($ID2);
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
		
		return $Result;
	}
	
	/**
	* Delete element with number $id from the tree wihtout deleting it's children.
	* 
	* @param mixed $ID, id of node or node object.
	* @return mixed $Result.
	*/
	public function Delete($ID, $Where = False) {
		list($LeftID, $RightID, $Depth, $NodeID) = $this->_NodeValues($ID);
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
		return $Result;
	}

	/**
	* Delete element with number $ID from the tree and all it children.
	* 
	* @param mixed $ID, id of node or node object.
	* @return mixed $Result.
	*/
	public function DeleteAll($ID, $Where = False) {
		list($LeftID, $RightID, $Depth, $NodeID) = $this->_NodeValues($ID);
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
		return $Result;
	}
	
	/**
	* Returns all elements of the tree sortet by left.
	* 
	* @param mixed $Fields.
	* @return mixed $Result.
	*/
	public function Full($Fields = '', $Where = False) {
		if ($Fields == '') {
			$this
				->SelectNodeFields()
				->Select('Name');
		} else $this->SQL->Select($Fields);
		//if (is_array($Where)) $this->SQL->Where($Where);
		$Result = $this->SQL
			->From($this->Name)
			->OrderBy($this->LeftKey)
			->Get();
		return $Result;
	}

	/**
	* Returns all elements of a branch starting from an element with number $ID.
	* 
	* @param int $NodeID.
	* @return Gdn_DataSet $Result.
	*/
	public function Branch($NodeID, $Fields = 'a.*', $Where = False) {
		$NodeID = (int)$NodeID;
		if (is_array($Where)) $this->SQL->Where($Where);
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
	* Returns all parents of element with number $ID.
	* 
	* @param int $NodeID.
	* @param mixed $Fields.
	* @param mixed $Where.
	* @return mixed $Result.
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
	* @param int $NodeID.
	* @param mixed $Where.
	* @return mixed $Result.
	*/
	public function Ajar($ID, $Where = False) {
		
		if (is_object($ID)) $DataSet = $ID;
		else $DataSet = $this->Parents($ID);
		
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
		$this->SQL->EndWhereGroup();
		if (is_array($Where)) $this->SQL->Where($Where);
		$Result = $this->SQL
			->OrderBy($this->LeftKey)
			->Get();
		return $Result;
	}
	
	/**
	* Undocumented 
	* 
	* @param mixed $ID.
	* @return mixed $Result.
	*/
	protected function GetNodeWidth($ID) {
		list($LeftID, $RightID, $Depth, $NodeID) = $this->_NodeValues($ID);
		// (rgt - lft) + 1)
		$Width = ($RightID - $LeftID) + 1;
		return $Width;
	}
	
	/**
	* Get all nodes without children.
	* 
	* @param mixed 
	* @return mixed $Result.
	*/
	public function GetLeafs($Fields = '*') {
		$Result = $this->SQL
			->Select($Fields)
			->From($this->Name)
			->Where($this->RightKey, $this->LeftKey .'+1', False)
			->Get();
		return $Result;
	}

	
	
	# Part of Nested Set Tree Library by Rolf Brugger
	# http://www.edutech.ch/contribution/nstrees
	
	public function HasChildren($ID) {
		list($LeftID, $RightID) = $this->_NodeValues($ID);
		$Result = (($RightID - $LeftID) > 1);
		return $Result;
	}
	
	public function IsRoot($ID) {
		//list($LeftID, $RightID, $Depth, $NodeID) = $this->_NodeValues($ID);
		list($LeftID) = $this->_NodeValues($ID);
		return ($LeftID == 1);
	}
	
	public function IsLeaf($Node) {
		list($LeftID, $RightID) = $this->_NodeValues($Node);
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
		list($LeftID1, $RightID1, $Depth1, $NodeID1) = $this->_NodeValues($Node1);
		list($LeftID2, $RightID2, $Depth2, $NodeID2) = $this->_NodeValues($Node2);
		$Result = (($LeftID1 > $LeftID2) && $RightID1 < $RightID2);
		return $Result;
	}
	
}




