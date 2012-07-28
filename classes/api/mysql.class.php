<?

// ---

$GLOBALS["__db_instance"] = false;

function &new_mysql_class()
{
    if ( false === $GLOBALS["__db_instance"] )
    {
        $GLOBALS["__db_instance"] = new mysql_class();
        $GLOBALS["__db_instance"]->SelectDatabase( DB_DATABASE );
    }

    return $GLOBALS["__db_instance"];
}


// ---


class mysql_class
{
	var $m_ConnectionID;
	var $m_Database;
	var $row;
	var $m_Result;
	var $m_RowsCount;
	var $m_AffectedRows;
	var $m_Connected;

	function mysql_class( $server=DB_SERVER, $user=DB_USER, $password=DB_PASS )
	{
		$this->m_Database  = "";
		$this->m_Connected = false;

		$this->m_ConnectionID = @mysql_connect( $server, $user, $password ) or trigger_error( "Could not connect to mysql sever", E_USER_ERROR );
		if ( false === $this->m_ConnectionID ) return;

		$this->m_Connected = true;

		register_shutdown_function( "ShutdownDB" );
		//Debuggg("opening conn");
	}

	function Close()
	{
	    //Debuggg("closing conn");
		if ( $this->m_Connected === true ) @mysql_close( $this->m_ConnectionID );
	}

	function SelectDatabase( $database )
	{
		$m_Result = @mysql_select_db( $database, $this->m_ConnectionID );
		if ( false === $m_Result ) return ( false );

		$this->m_Database = $database;
		return ( true );
	}

	function Query( $query_string )
	{
		$this->m_Result = @mysql_query( $query_string, $this->m_ConnectionID );
		if ( false === $this->m_Result )
		{
			$this->m_RowsCount = 0;
			return ( false );
		}

		$this->m_RowsCount = @mysql_num_rows( $this->m_Result );
		return ( true );
	}

	function Insert( $query_string )
	{
		$this->m_Result = @mysql_query( $query_string, $this->m_ConnectionID );
		if ( false === $this->m_Result ) return ( false );

		$this->m_AffectedRows = @mysql_affected_rows( $this->m_ConnectionID );
		return ( true );
	}

	function Delete( $query_string )
	{
		$this->m_Result = @mysql_query( $query_string, $this->m_ConnectionID );
		if ( false === $this->m_Result ) return ( false );

		$this->m_AffectedRows = @mysql_affected_rows( $this->m_ConnectionID );
		return ( true );
	}

	function Update( $query_string )
	{
		$this->m_Result = @mysql_query( $query_string, $this->m_ConnectionID );
		if ( false === $this->m_Result ) return ( false );

		# we can't really relay on affected rows in this case since the records updated with the same info won't show up as modified
		$this->m_AffectedRows = @mysql_affected_rows( $this->m_ConnectionID );
		return ( true );
	}

	function GetInsertID()
	{
		$this->m_Result = @mysql_insert_id( $this->m_ConnectionID );
		return ( $this->m_Result );
	}

	function GetColumns( $table )
    {
        // Query to get the tables in the current database:
        $result = mysql_query("SHOW COLUMNS FROM " . $table);

		if ( @mysql_num_rows( $result ) == 0 )
		{
			die("Problems with table: " . $table);
		}

        // Fetchs the array to be returned:
        $columns = array();
        while ($array_data = mysql_fetch_array($result))
        {
            $columns[] = $array_data[0];
        }

        // Returns the array or NULL
        if (count($columns) > 0)
        {
            return ( $columns );
        } else {
            return ( false );
        }
    }

	function Fetch()
	{
		$this->row = @mysql_fetch_array( $this->m_Result );
		return ( $this->row === false ? false : true );
	}

	function GetNumberOfRows()
	{
		return $this->m_RowsCount;
	}

	function GetNumberOfAffectedRows()
	{
		return $this->m_AffectedRows;
	}

	function SmartEscape($value)
    {
        if (get_magic_quotes_gpc()) {
            $value = stripslashes($value);
        }

        $value = mysql_real_escape_string($value);

        return $value;
    }

};

function ShutdownDB()
{
    $sql = &new_mysql_class();
    $sql->Close();
}

?>