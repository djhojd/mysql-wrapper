<?PHP
if ( !defined( 'SORT_ORDER_ASCENDENT' ) ) define( 'SORT_ORDER_ASCENDENT', 'ASC' );
if ( !defined( 'SORT_ORDER_DESCENDENT' ) ) define( 'SORT_ORDER_DESCENDENT', 'DESC' );

class wrapper_class
{
    var $sql;
    var $table;
    var $columns		= array();
    var $result  		= array();
    var $dataFilter     = array();
	var $dataOrder		= array();
    var $filteredColumns= array();
    var $maxRows;

	var $SortCriterion;
	var $SortOrder;

	var $relations		= array ();

	var $NumberOfRows	= 0;

	var $debugMode		= 0;

	var $formSpecialColumns = array();
	var $formHiddenColumns 	= array("id");


	var $formListColumns 	= array( );
	var $formListActions	= array( );

    function wrapper_class($table)
    {
        $this->sql      		= &new_mysql_class();
        $this->table    		= $table;
        $this->columns  		= $this->sql->GetColumns( $this->table );

        $this->filteredColumns  = $this->columns;
		unset($this->filteredColumns[array_search("id", $this->filteredColumns)]);

#		$this->GetData();
		$this->MaxRows();

		$this->otherConditions = "";
		$this->otherSelectConditions = "";
    }

	function debug()
	{
		$this->debugMode = 1 - $this->debugMode;
	}

	function AddRelation ( $field, $target_table, $target_field = "id" )
	{
		$this->relations[] = array ( "field" => $field, "target_table" => $target_table, "target_field" => $target_field );
	}

	function SetDataFilter ( $aColumn = "", $aValue = "", $anOpperator = "=" )
	{
		$this->dataFilter[] = array( "column" => $aColumn, "value" => $aValue, "opperator" => $anOpperator );
	}

	function ClearDataFilter ()
	{
		$this->dataFilter = array();
	}

    function SetDataOrder ( $aColumn = "", $anOrder = SORT_ORDER_ASCENDENT )
    {
        $this->dataOrder[] = array( "column" => $aColumn, "order" => $anOrder );
    }

    function ClearDataOrder ()
    {
        $this->dataOrder = array();
    }

	function SetSortFilter ( $aSortCriterion = "", $aSortOrder = SORT_ORDER_ASCENDENT )
	{
		$this->SortCriterion 	= $aSortCriterion;
		$this->SortOrder		= $aSortOrder;
	}

	function ClearSortFilter ()
	{
		$this->SortCriterion 	= "";
		$this->SortOrder		= "";
	}

	function SetColumnFilter ( $anArray = "" )
	{
		$deleteList = explode(",", $anArray);
		foreach( $deleteList as $key => $value )
		{
			unset($this->filteredColumns[array_search(trim($value), $this->filteredColumns)]);
		}
	}

	function ClearColumnFilter ()
	{
        $this->filteredColumns  = $this->columns;
		unset($this->filteredColumns[array_search("id", $this->filteredColumns)]);
	}

    function SelectByID( $anID )
    {
        $sqlstr = "SELECT * FROM " . $this->table . " WHERE `id` = ".(int)$anID;
        $this->sql->Query( $sqlstr );

        if ( $this->sql->GetNumberOfRows() > 0 )
        {
            $this->sql->Fetch();
            $this->result = $this->sql->row;
            return ( true );
        }

        return ( false );
    }

    function SelectValueByName( $aName )
    {
        $sqlstr = "SELECT * FROM " . $this->table . " WHERE `name` = " . $aName;
        $this->sql->Query( $sqlstr );

        if ( $this->sql->GetNumberOfRows() > 0 )
        {
            $this->sql->Fetch();
            return $this->sql->row['value'];
        }

        return ( false );
    }

    function SelectAll()
    {
        $sqlstr = "SELECT * FROM " . $this->table;
        $this->sql->Query( $sqlstr );

        for ( $i = 0; $i < $this->sql->GetNumberOfRows(); $i++ )
        {
            $this->sql->Fetch();
            $this->result[ $this->sql->row['name'] ] = $this->sql->row['value'];
        }

        return $this->result;
    }

    function GetNumberOf( $aFilter = "" )
    {
        $conds = array();

        if ( $aFilter !== "" ) $conds[] = "`filter_field` = " . (int)$aFilter;

        $sqlstr = "SELECT COUNT(*) FROM " . $this->table . " ";
        if ( count( $conds ) > 0 ) $sqlstr .= " WHERE ".implode( " AND ", $conds );


        $this->sql->Query( $sqlstr );
        $this->sql->Fetch();

        return $this->sql->row[0];
    }


    // Filter[] ( array( column, value, opperator ) )
    function GetData( $aFilter = "", $aSortCriterion = "", $aSortOrder = SORT_ORDER_DESCENDENT, $anOffset = "", $aLength = "" )
    {
        if ( empty($aFilter) )
            $aFilter = $this->dataFilter;

        if ( empty($this->dataOrder) )
        {
            if ( !empty($this->SortOrder) )
                $aSortOrder = $this->SortOrder;

            if ( empty($aSortCriterion) )
                $aSortCriterion = $this->SortCriterion;
            if ( !empty($aSortCriterion) )
                $this->dataOrder[] = array( "column" => $aSortCriterion, "order" => $aSortOrder );
        }

		$this->result = array();
        $conds = array();

        if ( !empty($aFilter) )
        {
            foreach ($aFilter as $filter)
            {
                $conds[] = "`" . $filter["column"] . "` " . $filter["opperator"] . " '" . $filter["value"] . "'";
            }
        }

        $order = array();
        if ( !empty($this->dataOrder) )
        {
            foreach ($this->dataOrder as $anOrder)
            {
                $order[] = $anOrder["column"] . " " . $anOrder["order"];
            }
        }

        $sqlstr = "SELECT " . $this->otherSelectConditions . " * FROM " . $this->table;
        if ( count( $conds ) > 0 )      			$sqlstr .= " WHERE " . implode( " AND ", $conds ) . $this->otherConditions;
		elseif ( !empty($this->otherConditions) )	$sqlstr .= " WHERE " . $this->otherConditions;
        if ( !empty($order) )    			        $sqlstr .= " ORDER BY " . implode(", ", $order);
        if ( $anOffset !== "" || $aLength !== "" ) 	$sqlstr .= " LIMIT " . (int)$anOffset . ", " . (int)$aLength;
        $this->sql->Query( $sqlstr );

		if( $this->debugMode == 1 )
		{
			echo "<br>";
			echo $sqlstr;
			echo "<br>";
		}


        for ( $i = 0; $i < $this->sql->GetNumberOfRows(); $i++ )
        {
            $this->sql->Fetch();
            foreach ($this->columns as $theColumn)
            {
                $this->result[$i][$theColumn] = $this->sql->row[$theColumn];
            }
        }

		$this->NumberOfRows = $this->sql->GetNumberOfRows();
		if( $this->sql->GetNumberOfRows() > 0 )
		{

			$columns = array_keys ( $this->result[0] );


			for ( $i = 0; $i < count( $this->result ); $i++ )
			{
				for ( $j = 0; $j < count( $this->relations ); $j++)
				{
					$relation = $this->relations[$j];

					if( !empty( $this->result[$i][ $relation["field"]] ) )
					{
#						$this->sql->Query( "SELECT * FROM " . $relation["target_table"] . " WHERE " . $relation["target_field"] ." = '" .  $this->result[$i][ $relation["field"]] . "' LIMIT 0,1" );
						$this->sql->Query( "SELECT * FROM " . $relation["target_table"] . " WHERE " . $relation["target_field"] ." = '" .  $this->result[$i][ $relation["field"]] . "'" );

						if( $this->sql->GetNumberOfRows() > 0 )
						{
							$kk = -1;
							while( $this->sql->Fetch() )
							{
								$kk++;

								$k 	= 0;
								foreach ( $this->sql->row as $key => $value  )
								{
									$k++;
									if( $k % 2 == 0 )
									{
										$this->result[$i][ $relation["target_table"] ][ $this->sql->row["id"] ][ $key ] = $value;

										$this->result[$i][ $relation["target_table"] . "-" . $kk . "-" . $key] = $value;
									}
								}
							}
						}

						$this->sql->Query( "SELECT * FROM " . $relation["target_table"] . " WHERE " . $relation["target_field"] ." = '" .  $this->result[$i][ $relation["field"]] . "'" );

						if( $this->sql->GetNumberOfRows() == 1 )
						{
							while( $this->sql->Fetch() )
							{
								$k = 0;
								foreach ( $this->sql->row as $key => $value  )
								{
									$k++;
									if( $k % 2 == 0 )
									{
										$this->result[$i][ $relation["target_table"] . "-" . $key] = $value;
									}
								}
							}
						}
					}
				}
			}
		}

        return $this->NumberOfRows;
    }

    // Filter[] ( array( column, value, opperator ) )
    function GetImage( $aColumn = "id", $aTable = "images", $aRelationColumn = "id", $aFilter = "", $aSortCriterion = "", $aSortOrder = SORT_ORDER_DESCENDENT, $anOffset = "", $aLength = "" )
    {
        $aSortCriterion = "position";
        $aSortOrder 	= "ASC";

		for ( $i = 0; $i < count( $this->result ); $i++ )
        {
			$aFilter = array( 0 => array( "column" => $aColumn, "value" => $this->result[$i][$aRelationColumn], "opperator" => "=" ) );
			$conds = array();

			if ( $aFilter !== "" )
			{
				foreach ($aFilter as $filter)
				{
					$conds[] = "`" . $filter["column"] . "` " . $filter["opperator"] . " '" . $filter["value"] . "'";
				}
			}

			$sqlstr = "SELECT * FROM " . $aTable;
			if ( count( $conds ) > 0 )     				$sqlstr .= " WHERE " . implode( " AND ", $conds );
			if ( $aSortCriterion != "" )				$sqlstr .= " ORDER BY `" . $aSortCriterion . "` " . $aSortOrder;
			if ( $anOffset !== "" || $aLength !== "" ) 	$sqlstr .= " LIMIT " . (int)$anOffset . ", " . (int)$aLength;

			$this->sql->Query( $sqlstr );
            $this->sql->Fetch();

			$this->result[$i]["image"] = $this->sql->row["file"];
        }

        return $this->sql->GetNumberOfRows();
    }


    // columnList[] ( column, value )
    function Insert( $columnList )
    {
        $tempCols = array();
        $tempVals = array();
        foreach ( $columnList as $theColumn )
        {
            $tempCols[] = "`" . $theColumn["column"] . "`";
            $tempVals[] = "'" . $this->sql->SmartEscape( $theColumn["value"] ) . "'";
        }
        $sqlstr  = "INSERT INTO " . $this->table . " ( " . implode(",", $tempCols) . " ) VALUES ( " . implode (",", $tempVals) . " )";

        $this->sql->Insert( $sqlstr );
        return ( $this->sql->GetNumberOfAffectedRows() > 0 ? $this->sql->GetInsertID() : false );
    }

    function InsertFiltered()
    {
        $tempCols 	= array();
        $tempVals 	= array();
		$columnList	= array();
		foreach( $this->filteredColumns as $key => $value )
		{
			if ( isset( $_POST[$value] ) )
			{
				$columnList[] = array("column" => $value, "value" => $_POST[$value]);
			}
		}

        foreach ( $columnList as $theColumn )
        {
			if ( isset( $_POST[$theColumn["column"]] ) )
			{
				$tempCols[] = "`" . $theColumn["column"] . "`";
				$tempVals[] = "'" . $this->sql->SmartEscape( $theColumn["value"] ) . "'";
			}
		}
        $sqlstr  = "INSERT INTO " . $this->table . " ( " . implode(",", $tempCols) . " ) VALUES ( " . implode (",", $tempVals) . " )";

        $this->sql->Insert( $sqlstr );
        return ( $this->sql->GetNumberOfAffectedRows() > 0 ? $this->sql->GetInsertID() : false );
    }


    // columnList[] ( column, value )
    // Filter[] ( array( column, value, opperator ) )
    function Update( $columnList, $aFilter = "" )
    {
        $sqlstr  = "UPDATE " . $this->table . " SET ";

        $tempCols = array();
        $tempVals = array();
        foreach ( $columnList as $theColumn )
        {
            $tempUpdate[] = "`" . $theColumn["column"] . "` = '" . $this->sql->SmartEscape( $theColumn["value"] ) . "'";
        }

        $sqlstr .= implode(",", $tempUpdate);

        $conds = array();

        if ( $aFilter !== "" )
        {
            foreach ($aFilter as $filter)
            {
                $conds[] = "`" . $filter["column"] . "` " . $filter["opperator"] . " '" . $filter["value"] . "'";
            }
        }

        $sqlstr .= " WHERE " . implode(" AND ", $conds);
        $this->sql->Update( $sqlstr );
    }

    function UpdateFiltered( $aFilter = "" )
    {
        $sqlstr  = "UPDATE " . $this->table . " SET ";

		if ( empty($aFilter) ) $aFilter = array( 0 => array( "column" => "id", "value" => $_GET["id"], "opperator" => "=" ) );

        $tempCols = array();
        $tempVals = array();

		$columnList	= array();
		foreach( $this->filteredColumns as $key => $value )
		{
			if ( isset( $_POST[$value] ) )
			{
				$columnList[] = array("column" => $value, "value" => $_POST[$value]);
			}
		}

        foreach ( $columnList as $theColumn )
        {
			if ( isset( $_POST[$theColumn["column"]] ) )
			{
				$tempUpdate[] = "`" . $theColumn["column"] . "` = '" . $this->sql->SmartEscape( $theColumn["value"] ) . "'";
			}
        }

        $sqlstr .= implode(",", $tempUpdate);

        $conds = array();

        if ( $aFilter !== "" )
        {
            foreach ($aFilter as $filter)
            {
                $conds[] = "`" . $filter["column"] . "` " . $filter["opperator"] . " '" . $filter["value"] . "'";
            }
        }

        $sqlstr .= " WHERE " . implode(" AND ", $conds);

        $this->sql->Update( $sqlstr );
    }

    function Delete( $deleteValue, $deleteColumn = "id" )
    {
        $sqlstr = "DELETE FROM " . $this->table . " WHERE `" . $deleteColumn . "` = '" . $deleteValue . "'";
        $this->sql->Delete( $sqlstr );
    }

	function MaxRows($aFilter = "", $aColumn = "position")
	{
        $conds = array();

        if ( empty($aFilter) ) 	$aFilter = $this->dataFilter;

        if ( $aFilter !== "" )
        {
            foreach ($aFilter as $filter)
            {
                $conds[] = "`" . $filter["column"] . "` " . $filter["opperator"] . " '" . $filter["value"] . "'";
            }
        }

		$sqlstr = "SELECT MAX(" . $aColumn . ") as max FROM " . $this->table;
        if ( count( $conds ) > 0 )      $sqlstr .= " WHERE " . implode( " AND ", $conds );

		$this->sql->Query( $sqlstr );
		$this->sql->Fetch();
		$this->maxRows = $this->sql->row["max"];

		return ( true );
	}

	function Order($anAction = "insert", $aPosition = 0, $aColumn = "position")
	{
		if ($anAction == "insert")
		{
			$columnList = array(
								array("column" => $aColumn,	"value" => ( $this->maxRows + 1 ))
							   );
			$this->Update( $columnList, array( 0 => array( "column" => "id", "value" => $this->sql->GetInsertID(), "opperator" => "=" ) ) );
		}

		if ($anAction == "delete")
		{
			$this->Order("update", $this->maxRows);
		}

		if ($anAction == "update")
		{
			$this->SelectByID( $_GET["id"] );
			$oldPosition = $this->result[$aColumn];

			if ($oldPosition < $aPosition)
			{
				$aux 			= ($aPosition - $oldPosition);
			    $this->GetData( $this->dataFilter, $this->SortCriterion, SORT_ORDER_ASCENDENT, $oldPosition, $aux );
				$allResults = $this->result;
				for ($i = 0; $i < count($allResults); $i++)
				{
					$id		  	= $allResults[$i]["id"];
					$position 	= $allResults[$i][$aColumn]-1;

					$columnList = array(
										array("column" => $aColumn,	"value" => $position)
									   );
					$this->Update( $columnList, array( 0 => array( "column" => "id", "value" => $id, "opperator" => "=" ) ) );
				}
			} else {
				$aux 			= ($aPosition-1);
				$oldPosition 	= ($oldPosition - $aPosition);
			    $this->GetData( $this->dataFilter, $this->SortCriterion, SORT_ORDER_ASCENDENT, $aux, $oldPosition );
				$allResults = $this->result;

				for ($i = 0; $i < count($allResults); $i++)
				{
					$id		  	= $allResults[$i]["id"];
					$position 	= $allResults[$i][$aColumn]+1;

					$columnList = array(
										array("column" => $aColumn,	"value" => $position)
									   );
					$this->Update( $columnList, array( 0 => array( "column" => "id", "value" => $id, "opperator" => "=" ) ) );
				}
			}
			$columnList = array(
								array("column" => $aColumn,	"value" => $aPosition)
							   );
			$this->Update( $columnList, array( 0 => array( "column" => "id", "value" => $_GET["id"], "opperator" => "=" ) ) );
		}
	}

	function DisplayPosition( $aLink = "", $aColumn = "position" )
	{
		$CountRows = $this->GetData();
		for ($i = 0; $i < $CountRows; $i++)
		{
			$newLink = str_replace("{id}", $this->result[$i]["id"], $aLink);
			$return = "<select name=\"position\" onchange=\"MM_jumpMenu('parent','$newLink', this)\">";
			for ($k = 1; $k <= $CountRows; $k++)
			{
				$return .= "<option value='$k' ";
				if ($k == $this->result[$i]["position"]) $return .= "selected";
				$return .= ">$k</option>";
			}
			$return .= "</select>";
			$this->result[$i]["display_position"] = $return;
		}
	}

	function InitializePosition($table, $column = "position", $update_column = "id", $condition = "")
	{
		$this->SelectRows($table, $condition);

		$i = 0;
		while ($row = $this->FetchArray())
		{
			$i++;
			$id		= $row[$update_column];
			$query 	= "UPDATE $table SET $column='$i' WHERE $update_column='$id'";
			mysql_db_query($this->db, $query, $this->connect_id);
		}
	}

    function _Empty()
    {
        $sqlstr = "TRUNCATE TABLE `" . $this->table . "`";
        $this->sql->Query( $sqlstr );
    }

	function AddField( $name, $type="VARCHAR", $length = "255" )
	{
		$sqlstr = "ALTER TABLE `" . $this->table . "` ADD COLUMN " . $name . " " . $type . "(". $length . ")";
        $this->sql->Query( $sqlstr );
	}

	function DeleteField( $name )
	{
		$sqlstr = "ALTER TABLE `" . $this->table . "` DROP COLUMN " . $name;
		$this->sql->Query( $sqlstr );
	}

	function GetDataSelected ( $aValue = "", $targetColumn = "id")
	{
		$this->GetData();
		foreach ($this->result as $key => $value)
		{
			if ($aValue == $value[$targetColumn]) 	$this->result[$key]["selected"] = 'selected="selected"';
			else					 				$this->result[$key]["selected"] = '';
		}
#		print_r($this->result);
		return $this->result;
	}

	function GetSelectHTML( $aHTMLname, $aSelectedValue = "", $aHTMLstyle = "", $aHTMLonChange = "", $anOptionValueField = "id", $anOptionNameField = "name" )
	{
		$html_code = '<select name="' . $aHTMLname . '" onChange = "' . $aHTMLonChange . '" style = "' . $aHTMLstyle . '">';
		$html_code.= '<option value="">ORICARE</option>';

		foreach ($this->result as $key => $value )
		{
			if( !empty($aSelectedValue) && ( $this->result[$key][$anOptionValueField] == $aSelectedValue ) )
				$selected_text = 'selected="selected"';
			else
				$selected_text = "";

			$html_code.= '<option value="' . $this->result[$key][$anOptionValueField] . '" ' . $selected_text . '>' . $this->result[$key][$anOptionNameField] . '</option>';
		}

		$html_code.= '</select>';

		return $html_code;
	}

	function GetCheckBoxSelected ( $aValue = "", $targetColumn = "id" )
	{
		if ( is_array($aValue) )
		{
			$allSelected = explode(",", $aValue);
	#		print_r($allSelected);

			foreach ($this->result as $key => $value)
			{
				if ( in_array($value[$targetColumn], $allSelected) ) 	$this->result[$key]["checked"] = 'checked="checked"';
				else													$this->result[$key]["checked"] = '';
			}
		} else {
			foreach ($this->result as $key => $value)
			{
				if ( $value[$targetColumn] == $aValue ) 				$this->result[$key]["checked"] = 'checked="checked"';
				else													$this->result[$key]["checked"] = '';
			}
		}
#		print_r($this->result);

		return $this->result;
	}

	function returnInput( $row, $value = "" )
	{

		$search		= array( "_" );
		$replace	= " ";
		$title		= ucwords( str_replace( $search, $replace, $row["Field"] ) );
		$name		= $row["Field"];

		if( in_array( $name, $this->formHiddenColumns ) )
		{
			return array();
		}

		if( isset( $this->formSpecialColumns[$name] ) )
		{
			$type = "special";
		}
		else
		{
			switch( $row["Type"] )
			{

				case "text" :
								{
									$type		= "text";
									$length		= 0;
									break;
								}
				default:
								{
									$type		= substr( $row["Type"], 0, strpos( $row["Type"], "(" ) );
									$length		= substr( $row["Type"], strpos( $row["Type"], "(" ) + 1, strpos( $row["Type"], ")" ) - strpos( $row["Type"], "(" ) - 1 );
									break;
								}
			}
		}


		switch( $type )
		{
			case "int" :	{
								$input = '<input type="text" name="' . $name . '" value="' . $value . '">';
								break;
							}
			case "varchar" :{
								$input = '<input type="text" name="' . $name . '" value="' . $value . '">';
								break;
							}
			case "tinyint" :{
								if( $value == 1 )
									$checked = 'checked="checked"';
								else
									$checked = '';

								$input = '<input type="checkbox" name="' . $name . '" ' . $checked . '>';
								break;
							}
			case "text" :	{
								$input = '<textarea name="' . $name . '" >' . $value . '</textarea>';
								break;
							}
			case "enum" :	{
								$values = explode( ",", $length );

								$input = '';



								for( $i=0; $i < count( $values ); $i++ )
								{
									$values[$i] = substr( $values[$i], 1, strlen( $values[$i] ) - 2 );
									if( $value == $values[$i] )
									{
										$checked = 'checked="checked"';
									}
									else
									{
										$checked = '';
									}

									$input .= '<input type="radio" name="' . $name . '" value="' . $values[$i] . '" ' . $checked . '>' . $values[$i] . '<br>';
								}
								break;
							}
			case "special"; {
								$input = $this->returnSpecialInput( $name, $value );
							}


		}


		//$input 		= $type . " | " . $length;
		return array( $title, $input );
	}


	function returnSpecialInput( $name, $value )
	{
		$row = $this->formSpecialColumns[$name];

		switch( $row["type"] )
		{
			case "selectName" :
				{
					$values = $row["extra"];

					$input = '<select name="' . $name . '">';

					for( $i = 0; $i < count( $values ); $i++ )
					{
						if( $value == $values[$i]["name"] )
						{
							$selected = 'selected="selected"';
						}
						else
						{
							$selected = '';
						}

						$input.= '	<option ' . $selected . '>' . $values[$i]["name"] .'</option>';
					}

					$input .= '</select>';
					break;
				}

			case "select" :
				{
					$values = $row["extra"];

					$input = '<select name="' . $name . '">';

					for( $i = 0; $i < count( $values ); $i++ )
					{
						if( $value == $values[$i] )
						{
							$selected = 'selected="selected"';
						}
						else
						{
							$selected = '';
						}

						$input.= '	<option ' . $selected . '>' . $values[$i] .'</option>';
					}

					$input .= '</select>';
					break;
				}
		}


		return $input;
	}


	function buildListForm( $offset, $length )
	{
		$this->GetData( "", "", "", $offset, $length );

		$rows = $this->result;

		if( $this->debugMode == 1 )
		{
			echo "<br>";
			echo $sqlstr;
			echo "<br>";
		}

		$outputString = "";

		for( $i=0; $i < count( $rows ); $i++ )
		{
			$outputString.= "<tr>";

			for( $j=0; $j < count( $this->formListColumns ); $j++ )
			{
				$outputString.= "	<td>" . $rows[$i][$this->formListColumns[$j]] . "</td>";
			}

			for( $j=0; $j < count( $this->formListActions ); $j++ )
			{
				if( $this->formListActions[$j]["link"] == "" )
				{
					$link = '<a href="admin.php?page=' . $this->table . '&action=' . $this->formListActions[$j]["action"] . '&id=' . $rows[$i]["id"] . '">' . $this->formListActions[$j]["action"] . '</a>';
				}
				else
				{
					$link = str_replace( "|ID|", $rows[$i]["id"] , $this->formListActions[$j]["link"] );
				}

				$outputString.= "	<td>" . $link . "</td>";
			}

			$outputString .= "<tr>";
		}


		return $outputString;
	}

	function buildViewForm( $id )
	{
		$this->SelectById( $id );

		$row 		= $this->result;
		$columns 	= array_keys( $row );

		$search		= array( "_" );
		$replace	= " ";


		for( $i=0; $i < count( $columns ); $i++ )
		{
			if( !in_array( $columns[$i], $this->formHiddenColumns ) )
			{
				$title = ucwords( str_replace( $search, $replace, $columns[$i] ) );
				$input = $row[ $columns[$i] ];

				if( !empty( $input ) )
				{
					?>
					<tr>
						<td><?=$title?></td>
						<td><?=$input?></td>
					</tr>
					<?php
				}
			}
		}
	}





	function buildEditForm( $id )
	{
		$this->SelectById( $id );
		$rowEdit	= $this->result;

		$result = mysql_query( "SHOW COLUMNS FROM " . $this->table );

		while( $row = mysql_fetch_assoc( $result ) )
		{
			list( $title, $input ) = $this->returnInput( $row, $rowEdit[$row["Field"]] );
			if( !empty( $input ) )
			{
				?>
				<tr>
					<td><?=$title?></td>
					<td><?=$input?></td>
				</tr>
				<?php
			}
		}
	}


	function buildAddForm( )
	{
		$result = mysql_query( "SHOW COLUMNS FROM " . $this->table );

		while( $row = mysql_fetch_assoc( $result ) )
		{
			list( $title, $input ) = $this->returnInput( $row, "" );
			if( !empty( $input ) )
			{
				?>
				<tr>
					<td><?=$title?></td>
					<td><?=$input?></td>
				</tr>
				<?php
			}
		}
	}

};
?>
