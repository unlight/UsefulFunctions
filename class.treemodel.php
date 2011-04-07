<?php if (!defined('APPLICATION')) exit();

# Credit http://php.russofile.ru/ru/authors/sql/nestedsets01/
# 2005 Kuzma Feskov <kuzma[at]russofile[dot]ru>

class TreeModel extends Gdn_Model {
	
	protected $LeftKey = 'TreeLeft';
	protected $RightKey = 'TreeRight';
	protected $DepthKey = 'Depth';
	protected $ParentKey = 'ParentID';
	
	// TODO: 
	// CELKO:
	// $FieldOrder => Field name for table nasted set order field
	// $FieldIgnore => Field name for tablr nested set ignore field
	
	protected function GetNodeWidth($NodeID) {
		$Width = $this->SQL
			->Select($this->RightKey.'-'.$this->RightKey.'+1', '', 'Width')
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
            ->Where($this->RightKey, $this->LeftKey .'+1', FALSE)
			->Get();
		return $Result;
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
			->Where("n.{$this->LeftKey} >=", 'p.'.$this->LeftKey, False, False)
			->Where("n.{$this->LeftKey} <= ", 'p.'.$this->RightKey, False, False)
			->GroupBy("n.{$this->PrimaryKey}")
			->GetSelect();
		$this->SQL->Reset();
		$TemporaryTableSql = "select t1.{$this->PrimaryKey}, t1.RowCount from ($Sql) as t1";
		$TemporaryTable = "select (t2.RowCount-1) from ($TemporaryTableSql) as t2 where t2.{$this->PrimaryKey} = tree.{$this->PrimaryKey}";
		$Query = "update {$Px}{$this->Name} tree set tree.Depth = ($TemporaryTable)";
		$this->SQL->Query($Query);
	}
	
	public function GetRoot() {
		$Root = $this
			->_SelectTreeFields()
			->From($this->Name)
			->Where($this->PrimaryKey, -1, False, False)
			->Get()
			->FirstRow();
		return $Root;
	}
	
	/**
	* Sets initial parameters of a tree and creates root of tree
	* ATTENTION, all previous values in table are destroyed.
	*/
	public function Clear($Set = NULL) {
		$this->SQL->Truncate($this->Name);
		//$this->Delete(); // why need this?
		if (is_array($Set)) $this->SQL->Insert($this->Name, $Set);
	}
	
	
	protected function _SelectTreeFields() {
		$this->SQL
			->Select($this->LeftKey)
			->Select($this->RightKey)
			->Select($this->DepthKey)
			->Select($this->ParentKey);
		return $this->SQL;
	}
	
	protected $_CachedResult;
	
	
	/**
	* Receives left, right and level for unit with number id.
	*/
	public function GetNode($ID, $ResetCache = FALSE) {
		$Result =& $this->_CachedResult[$ID];
		if (!isset($this->_CachedResult[$ID]) || $ResetCache) {
			$Result = $this
				->_SelectTreeFields()
				->From($this->Name)
				->Where($this->PrimaryKey, $ID)
				->Get()
				->FirstRow();
		}
		return $Result;
	}
	
	/**
	* Receives parent left, right and level for unit with number $id.
	*/
	function GetParent($ID, $Condition = '', $ResetCache = FALSE) {
		$Node = $this->GetNode($ID);
		if (!$Node) return $Node;
		
		$NodeID = $Node->{$this->PrimaryKey};
		$Level = $Node->{$this->DepthKey} - 1;
		$LeftKey = $Node->{$this->LeftKey};
		$RightKey = $Node->{$this->LeftKey};
		
		$Result =& $this->_CachedResult[$ID];
		
		if (!isset($Result) || $ResetCache) {
			$Result = $this->SQL
				->Select()
				->From($this->Name)
				->Where($this->LeftKey.' <', $LeftKey)
				->Where($this->RightKey.' >', $RightKey)
				->Where($this->DepthKey, $Level)
				->OrderBy($this->LeftKey)
				->Get()
				->FirstRow();
		}
		return $Result;
	}
	
	
	/**
	* Adds a new child element to the end of the list of child elements
	*/
	public function AppendTo($Parent, $Data) {
		if (is_numeric($Parent)) $Parent = $this->GetNode($Parent);
		list($ParentLeftID, $ParentRightID, $ParentDepth) = $this->_NodeValues($Parent);
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
		
		SetValue($this->LeftKey, $Data, $ParentRightID);
		SetValue($this->RightKey, $Data, $ParentLeftID + 1);
		SetValue($this->DepthKey, $Data, $ParentDepth + 1);
		
		$ResultID = $this->SQL->Insert($this->Name, $Data);
		$this->Database->CommitTransaction();
		return $ResultID;

	}
	
	/**
	* Adds a new child element to the top of the list of child elements
	*/
	public function PrependTo($Parent, $Data) {
		if (is_numeric($Parent)) $Parent = $this->GetNode($Parent);
		list($ParentLeftID, $ParentRightID, $ParentDepth) = $this->_NodeValues($Parent);
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
		
		SetValue($this->LeftKey, $Data, $ParentLeftID + 1);
		SetValue($this->RightKey, $Data, $ParentLeftID + 2);
		SetValue($this->DepthKey, $Data, $ParentDepth + 1);
		
		$ResultID = $this->SQL->Insert($this->Name, $Data);
		$this->Database->CommitTransaction();
		return $ResultID;
	}
	
	/**
	* Add a new element in the tree to element with number $id.
	*
	* @return integer Inserted element id
	*/
	public function InsertNode($ID, $Data, $Where = '') {
		$Node = $this->GetNode($ID);
		if (!$Node) return $Node;
		
		$Data = (array)$Data;
        list($LeftID, $RightID, $Depth) = $this->_NodeValues($Node);
		$Data[$this->LeftKey] = $RightID;
		$Data[$this->RightKey] = $RightID + 1;
		$Data[$this->DepthKey] = $Depth + 1;
		
		$this->Database->BeginTransaction();

		$this->SQL
			->Update($this->Name)
			->Set($this->LeftKey, "case when {$this->LeftKey} > $RightID then {$this->LeftKey}+2 else {$this->LeftKey} end", FALSE, FALSE)
			->Set($this->RightKey, "case when {$this->RightKey} >= $RightID then {$this->RightKey}+2 else {$this->RightKey} end", FALSE, FALSE)
			->Where($this->RightKey . '>=', $this->RightKey, FALSE, FALSE)
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
	function InsertNear($ID, $Data = array(), $Where = '') {
		$Node = $this->GetNode($ID);
		if (!$Node) return $Node;
		
		list($LeftID, $RightID, $Depth) = $this->_NodeValues($Node);
		
		$Data[$this->LeftKey] = $Node->{$this->RightKey} + 1;
		$Data[$this->RightKey] = $Node->{$this->RightKey} + 2;
		$Data[$this->DepthKey] = $Node->{$this->DepthKey};
		
		$this->Database->BeginTransaction();
		
		$this->SQL
			->Update($this->Name)
			->Set($this->LeftKey, "case when {$this->LeftKey} > {$Node->{$this->RightKey}} then {$this->LeftKey}+2 else {$this->LeftKey} end", False, False)
			->Set($this->RightKey, "case when {$this->RightKey} > {$Node->{$this->RightKey}} then {$this->RightKey}+2 else {$this->RightKey} end", False, False)
			->Where($this->RightKey . '>', $this->RightKey);

		$ResultID = $this->SQL->Insert($this->Name, $Data);
		$this->Database->CommitTransaction();
		return $ResultID;
	}
	
	protected function _NodeValues($Node) {
		$Return = array_map('intval', array(
			$Node->{$this->LeftKey}, 
			$Node->{$this->RightKey}, 
			$Node->{$this->DepthKey}, 
			$Node->{$this->ParentKey}
		));
		return $Return;
	}
	
	/**
	* Assigns a node with all its children to another parent.
	*
	* @param integer $ID node ID
	*/
	function MoveAll($ID, $NewParentID, $Where = '') {
		$Node = $this->GetNode($ID);
		if (!$Node) return $Node;
		list($LeftID, $RightID, $Depth) = $this->_NodeValues($Node);
		
		$ParentNode = $this->GetNode($NewParentID);
		if (!$ParentNode) return $ParentNode;
		list($ParentLeftID, $ParentRightID, $ParentDepth) = $this->_NodeValues($ParentNode);
		
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
		
		if ($ParentLeftID < $LeftID && $ParentRightID > $RightID && $ParentDepth < $Depth - 1) {
			
			$Between1 = $RightIDP1.' and '.$ParentRightIDM1;
			$SetValue1 = ((($ParentRightID-$RightID-$Depth+$ParentDepth)/2)*2+$Depth-$ParentDepth-1);
			
			$this->SQL
				->Set($this->DepthKey, "case 
					when {$this->LeftKey} between $LeftID and $RightID then {$this->DepthKey} $DepthSetValue 
					else {$this->DepthKey} end", FALSE, FALSE)
				->Set($this->RightKey, "case 
					when {$this->RightKey} between $Between1 then {$this->RightKey} - $RightLeft1
					when {$this->LeftKey} between {$LeftID} and {$RightID} then {$this->RightKey} + $SetValue1
					else {$this->RightKey} end", FALSE, FALSE)
				->Set($this->LeftKey, "case
					when {$this->LeftKey} between $Between1 then {$this->LeftKey} - $RightLeft1
					when {$this->LeftKey} between {$LeftID} and {$RightID} then {$this->LeftKey} + $SetValue1
					else {$this->LeftKey} end", FALSE, FALSE)
				->Where($this->LeftKey, "between {$Between1}", FALSE, FALSE);
			
		} elseif ($ParentLeftID < $LeftID) {
			$this->SQL
				->Set($this->DepthKey, "case 
					when {$this->LeftKey} between $LeftID and $RightID then {$this->DepthKey} $DepthSetValue 
					else {$this->DepthKey} end", FALSE, FALSE)
				->Set($this->LeftKey, "case
					when {$this->LeftKey} between $RightID and $LeftIDM1 then {$this->LeftKey} + $RightLeft1
					when {$this->LeftKey} between $LeftID and $RightID then {$this->LeftKey} - $LeftRightParentM
					else {$this->LeftKey}", FALSE, FALSE)
				->Set($this->RightKey, "case
					when {$this->RightKey} between $ParentRightID and $LeftID then {$this->RightKey} + $RightLeft1
					when {$this->RightKey} between $LeftID and $RightID then {$this->RightKey} - $LeftRightParentM
					else {$this->RightKey} end", FALSE, FALSE)
				->BeginWhereGroup()
				->Where($this->LeftKey, "between $ParentLeftID and $RightID", FALSE, FALSE)
				->OrWhere($this->RightKey, "between $ParentLeftID and $RightID", FALSE, FALSE)
				->EndWhereGroup();
		} else {
			$RightParentM1 = $ParentRightID - 1 - $RightID;
			$this->SQL
				->Set($this->DepthKey, "case 
					when {$this->LeftKey} between $LeftID and $RightID then {$this->DepthKey} $DepthSetValue else $Depth")
				->Set($this->LeftKey, "case
					when {$this->LeftKey} between $RightID and $ParentRightID THEN {$this->LeftKey} - $RightLeft1
					when {$this->LeftKey} between $LeftID and $RightID THEN {$this->LeftKey} + $RightParentM1
					else {$this->LeftKey} end", FALSE, FALSE)
				->Set($this->RightKey, "case
					when {$this->RightKey} between $RightIDP1 and $ParentRightIDM1 then {$this->RightKey} - $RightLeft1
					when {$this->RightKey} between $LeftID AND $RightID then {$this->RightKey} + $RightParentM1 
					else {$this->RightKey} end", FALSE, FALSE)
				->BeginWhereGroup()
				->Where("{$this->LeftKey} between $LeftID and $ParentRightID", NULL, FALSE, FALSE)
				->OrWhere("{$this->RightKey} between $LeftID and $ParentRightID", NULL, FALSE, FALSE)
				->EndWhereGroup();
		}
		
		$this->SQL->Update($this->Name)->Put();
		return TRUE;
	}
	
	
	/**
	* Change items position.
	*
	* @param integer $id1 first item ID
	* @param integer $id2 second item ID
	*/
	function ChangePosition($ID1, $ID2) {
		
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
			->Where($this->PrimaryKey, (int)$ID1, FALSE, FALSE)
			->Put();
		
		$this->SQL
			->Update($this->Name)
			->Set($this->LeftKey, $Node1->{$this->LeftKey})
			->Set($this->RightKey, $Node1->{$this->RightKey})
			->Set($this->DepthKey, $Node1->{$this->DepthKey})
			->Where($this->PrimaryKey, (int)$ID2, FALSE, FALSE)
			->Put();
		
		$this->Database->CommitTransaction();
		return TRUE;
	}
	
	
	/**
	* Swapping nodes within the same level and limits of one parent with all its children: $id1 placed before or after $id2.
	*
	*/
	function ChangePositionAll($ID1, $ID2, $Position = 'after', $Where = '') {
		$Node1 = $this->GetNode($ID1);
		if (!$Node1) return $Node1;
		list($LeftID1, $RightID1, $Depth1) = $this->_NodeValues($Node1);
		
		$Node2 = $this->GetNode($ID2);
		if (!$Node2) return $Node2;
		list($LeftID2, $RightID2, $Depth2) = $this->_NodeValues($Node2);

		if ($Depth1 != $Depth2) {
			trigger_error('Cannot change position.', E_USER_ERROR);
			return False;
		}
		
		
		if ($Position == 'before') {
			$RightLeft1 = $RightID1 - $LeftID1 + 1;
			if ($LeftID1 > $LeftID2) {
				$LeftIDsDiff = $LeftID1 - $LeftID2;
				$LeftIDM1 = $LeftID1 - 1;
				$RightID1P1 = $RightID1 + 1;
				$LeftID2M1 = $LeftID2 - 1;
				
				$this->SQL
					->Set($this->RightKey, "case
						when {$this->LeftKey} between $LeftID1 and $RightID1 {$this->RightKey} - $LeftIDsDiff
						when {$this->LeftKey} between $LeftID2 AND $LeftIDM1 then {$this->RightKey} + $RightLeft1 
						else {$this->RightKey} end", FALSE, FALSE)
					->Set($this->LeftKey, "case
						when {$this->LeftKey} between $LeftID1 and $RightID1 then {$this->LeftKey} - $LeftIDsDiff
						when {$this->LeftKey} between $LeftID2 and $LeftIDM1 then {$this->LeftKey} + $RightLeft1 
						else {$this->LeftKey} end", FALSE, FALSE)
					->Where($this->LeftKey, "between $LeftID2 AND $RightID1", FALSE, FALSE);
			} else {
				$LeftIDsDiff = $LeftID2 - $LeftID1;
				$this->SQL
					->Set($this->RightKey, "case
						when {$this->LeftKey} between $LeftID1 and $RightID1 then {$this->LeftKey} + ($LeftIDsDiff - $RightLeft1)
						when {$this->LeftKey} between $RightID1P1 and $LeftID2M1 then {$this->LeftKey} - $RightLeft1 
						else {$this->LeftKey} end", FALSE, FALSE)
					->Set($this->LeftKey, "case
						WHEN {$this->LeftKey} BETWEEN $LeftID1 AND $RightID1 THEN {$this->LeftKey} + ($LeftIDsDiff - $RightLeft1)
						WHEN {$this->LeftKey} BETWEEN $RightID1P1 AND $LeftID2M1 THEN {$this->LeftKey} - $RightLeft1
						ELSE {$this->LeftKey} end", FALSE, FALSE)
					->Where($this->LeftKey, "BETWEEN $LeftID1 AND $LeftID2M1", FALSE, FALSE);
			}
		} elseif ($Position = 'after') {
            if ($LeftID1 > $LeftID2) {
				$Value1 = $LeftID1 - $LeftID2 - ($RightID2 - $LeftID2 + 1); // $RightID1
				$this->SQL->Set($this->LeftKey, "case 
					when {$this->LeftKey} between $LeftID1 and $RightID1 then {$this->LeftKey} - $Value1
					when {$this->LeftKey} between ($RightID2 + 1) and ($LeftID1 - 1) then {$this->LeftKey} + ($RightID1 - $LeftID1 + 1)
					else {$this->LeftKey} end", False, False)
				$this->SQL->Set($this->LeftKey, "case 
					when {$this->LeftKey} between $LeftID1 and $RightID1 then {$this->LeftKey} - $Value1
					when {$this->LeftKey} BETWEEN ($RightID2 + 1) and ($LeftID1 - 1) then {$this->LeftKey} + ($RightID1 - $LeftID1 + 1) 
					else {$this->LeftKey} end", False, False);
				$this->SQL->Where("{$this->LeftKey} between ($RightID2 + 1) and $RightID1", Null, False, False)
			} else {
				$this->SQL->Set($this->RightKey, "case 
					when {$this->LeftKey} between $LeftID1 and $RightID1 then {$this->RightKey} + ($RightID2 - $RightID1)
					when {$this->LeftKey} between ($RightID1 + 1) and $RightID2 then {$this->RightKey} - ($RightID1 - $LeftID1 + 1)
					else {$this->LeftKey} end", False, False);
                $this->SQL->Set($this->LeftKey, "case 
					WHEN {$this->LeftKey} BETWEEN $LeftID1 AND $RightID1 THEN {$this->LeftKey} + ($RightID2 - $RightID1)
					WHEN {$this->LeftKey} BETWEEN ($RightID1 + 1) AND $RightID2 THEN {$this->LeftKey} - ($RightID1 - $LeftID1 + 1) 
					ELSE {$this->LeftKey} end", False, False);
				$this->SQL->Where("{$this->LeftKey} between $LeftID1 and $RightID2", Null, False, False)
			}
		}
		// $this->SQL->Where($Where)
		$this->SQL->Update($this->Name)->Put();
		return TRUE;
	}
	
	/**
	* Delete element with number $id from the tree wihtout deleting it's children.
    *
	*/
	function Delete($ID, $Where = '') {
		$Node = $this->GetNode($ID);
		if (!$Node) return $Node;
		list($LeftID, $RightID) = $this->_NodeValues($Node);
		
		$this->Database->BeginTransaction();
		$this->Delete(array($this->PrimaryKey => $ID));
		
		$this->SQL
			->Set($this->DepthKey, "case
				when {$this->LeftKey} between $LeftID and $RightID then {$this->DepthKey} - 1
				else {$this->DepthKey} end", FALSE, FALSE)
			->Set($this->LeftKey, "case
				when {$this->LeftKey} between $LeftID and $RightID then {$this->LeftKey} - 1
				when {$this->LeftKey} > $RightID then {$this->LeftKey} - 2
				else {$this->LeftKey} end", FALSE, FALSE)
			->Set($this->LeftKey, "case
				when {$this->LeftKey} between $LeftID and $RightID then {$this->LeftKey} - 1
				when {$this->LeftKey} > $RightID then {$this->LeftKey} - 2
				else {$this->LeftKey} end", FALSE, FALSE)
			->Where($this->LeftKey .' >', $LeftID, FALSE, FALSE);
		if (is_array($Where)) $this->Where($Where);

		$Result = $this->SQL->Update($this->Name)->Put();
		$this->Database->CommitTransaction();
		return $Result;
	}

	/**
	* Delete element with number $ID from the tree and all it children.
	*
	*/
	function DeleteAll($ID, $Where = '') {
        // TODO:
		$Node = $this->GetNode($ID);
		if (!$Node) return $Node;
        list($LeftID, $RightID) = $this->_NodeValues($Node);
        $this->Database->BeginTransaction();
        $this->SQL
            ->Where($this->LeftKey, "between $LeftID and $RightID", FALSE, FALSE)
            ->Delete($this->Name);
        
        $DeltaID = (($RightID - $LeftID) + 1);
		
		//$this->SQL->Where($Where);
		$this->SQL
			->Update($this->Name)
			->Set($this->LeftKey, "when {$this->LeftKey} > $LeftID then {$this->LeftKey} - 1 - $DeltaID else {$this->LeftKey} end", FALSE, FALSE)
			->Set($this->RightKey, "when $this->RightKey > $LeftID then $this->RightKey - $DeltaID else {$this->LeftKey}")
			->Where($this->RightKey . ' >', $RightID)
			->Put();
		$this->Database->CommitTransaction();
		return TRUE;
	}

	/**
	* Returns all elements of the tree sortet by left.
	*
	*/
	public function GetFull($Fields = '*') {
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
	public function Branch($ID) {
        $Result = $this->SQL
            ->Select('a.*')
            ->From($this->Name . ' a')
            ->From($this->Name . ' b')
            // N? 
            ->SelectCase("a.{$this->LeftKey}", array("a.{$this->LeftKey} + 1 < a.{$this->RightKey}" => 1, '' => 0), 'N')
            ->Where('b.'.$this->PrimaryKey, (int)$ID, FALSE, FALSE)
            ->Where("a.{$this->LeftKey} >=", 'b.'.$this->LeftKey, FALSE, FALSE)
            ->Where("a.{$this->RightKey} <=", 'b.'.$this->RightKey, FALSE, FALSE)
            ->OrderBy('a.'.$this->LeftKey)
            ->Get();
		return $Result;
	}

	/**
	* Returns all parents of element with number $ID.
	*
	*/
	function Parents($ID) {
        $Result = $this->SQL
            ->From($this->Name . ' a')
            ->From($this->Name . ' b')
            ->Select('a.*')
            // What is N? 
            ->SelectCase('a.'.$this->LeftKey, array("a.{$this->LeftKey} + 1 < a.{$this->RightKey}" => 1, '' => 0), 'N')
            ->Where('b.'.$this->PrimaryKey, (int)$ID, FALSE, FALSE)
            ->Where("b.{$this->LeftKey} between a.{$this->LeftKey} and a.{$this->RightKey}", NULL, FALSE, FALSE)
            ->OrderBy('a.'.$this->LeftKey)
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
            ->Where($this->DepthKey, 1, FALSE, FALSE);
		
        $i = 0;
        foreach ($DataSet->ResultArray() as $Row) {
            if ((++$i == $NumRows) && ($Row[$this->LeftKey] + 1) == $Row[$this->RightKey]) break;
            $this->SQL
                ->OrOp()
                ->BeginWhereGroup()
				->Where($this->DepthKey, $Row[$this->DepthKey] + 1, FALSE, FALSE)
                ->Where($this->LeftKey.' >', $Row[$this->LeftKey], FALSE, FALSE)
                ->Where($this->RightKey.' <', $Row[$this->RightKey], FALSE, FALSE)
                ->EndWhereGroup();
        }
        $Result = $this->SQL
            ->EndWhereGroup()
            ->OrderBy($this->LeftKey)
            ->Get();
        return $Result;
	}

	
}




