<?php 
// MySQL utility functions.
// handle 1 global connection. open / close connection if needed
class SqlManager
{
	public $offline = false;
	private $mysqlConfig = null;
	private $mysqlConnection = null;
	private static $MYSQL_PARAM_TYPES = array("string"=>"s", "integer" => "i", "double"=>"d",  "blob"=>"b");
	private $sqlLog = array();

	function __construct($config)
	{
		$this->configure($config);
	}

	private function configure($config)
	{
		$this->debug = arrayGet($config, "debug.sql");
		if($this->offline = arrayGet($config, "debug.offline")) return;
	    $this->mysqlConfig = isset($config["_mysql"]) ? $config["_mysql"] : $config;
	    //debug("config", $this->mysqlConfig);
	}

	public function getLog()
	{
		return $this->sqlLog;
	}

	public function getDbModel()
	{
		$model = array();
		$tables = $this->showTables();
		foreach ($tables as $table)
			$model[$table] = $this->getTableModel($table);
		return $model;
	}

	public function getTableModel($tableName)
	{
		$model = array();
		$model["columns"] = $this->showColumns($tableName);
		$model["columnNames"] = $this->getColumnNames($tableName);
		$model["pk"] = $this->getPrimaryKey($tableName);
		$model["fk"] = $this->getForeignKey($tableName);
		$model["fkref"] = $this->getForeignKeyReferences($tableName);
		return $model;
	}

	private function connect()
	{
		if($this->offline) return;

	    // Create connection
	    if(!$this->mysqlConnection)
		{
	        $this->mysqlConnection = new mysqli($this->mysqlConfig["host"], $this->mysqlConfig["username"], $this->mysqlConfig["password"], $this->mysqlConfig["dbname"]);
			debug("opened connection", $this->mysqlConnection->connect_error);
		}

	    if ($this->mysqlConnection->connect_error)
		    throw new Exception($this->mysqlConnection->connect_error);

	    return $this->mysqlConnection;
	}

	public function disconnect()
	{
	    if($this->mysqlConnection)
		{
	        $this->mysqlConnection->close();
			debug("closed connection");
		}
	    $this->mysqlConnection = null;
	}

	//get complete table
	//get result of select as array of rows. each row is an associative array
	public function selectAll($tableName)
	{
	    $sql = "SELECT * FROM $tableName";
	    return $this->select($sql);
	}

	public function selectWhere($params)
	{	
		$table   = arrayExtract($params, "table");
		$groupBy = arrayExtract($params, "group_by");
		$limit   = arrayExtract($params, "limit");
		$orderBy = arrayExtract($params, "order_by");
		$reverse = arrayExtract($params, "reverse");
		if($orderBy === "random") $orderBy = "rand()";

		$sql = "SELECT * FROM $table" . $this->sqlWhere($params);
		if($groupBy)	$sql .= " GROUP BY $groupBy";
		if($orderBy)	$sql .= " ORDER BY $orderBy";
		if($reverse)    $sql .= " DESC";
		if($limit)		$sql .= " LIMIT $limit";
debug("selectWhere SQL: $sql", $params);
	    return $this->select($sql, $params);
	}

	public function exists($params)
	{
		$table = arrayExtract($params, "table");
	    $query = "SELECT 1 FROM $table" . $this->sqlWhere($params);
		$query = "SELECT EXISTS($query) ex";
		return $this->selectValue($query, $params);
	}

	public function selectAllDistinct($tableName, $column)
	{
	    $sql = "SELECT DISTINCT $column FROM $tableName";
	    return $this->selectColumn($sql);
	}

	public function distinct($params)
	{		
		$searchText = arrayExtract($params, "searchText");
		$table = arrayExtract($params, "table");
		$columns = arrayExtract($params, "columns");
		$reverse = arrayExtract($params, "reverse");
	    $query = "SELECT DISTINCT $columns FROM $table" . $this->sqlWhere($params);
	    $query .= " ORDER BY 1";
		if($reverse) $query .= " DESC";
		return $this->selectColumn($query, $params);
	}

	public function count($params)
	{
		$table = arrayExtract($params, "table");
	    $query = "SELECT COUNT(1) nb FROM $table" . $this->sqlWhere($params);
		return $this->selectValue($query, $params);
	}

	private function mysqlParamType($param)
	{	
		$type = gettype($param);
		return isset($MYSQL_PARAM_TYPES[$type]) ? $MYSQL_PARAM_TYPES[$type] : $type[0];
	}

	public function getPrimaryKey($tableName)
	{
		if($this->offline) return array();
		return $this->callProcedure("getPrimaryKey", array($tableName), true);
	}

	public function getForeignKey($tableName)
	{
		if($this->offline) return array();
		return $this->callProcedure("getForeignKey", array($tableName));
	}

	public function getIdValue($tableName)
	{
		$query = "SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = ?";
		return $this->selectValue($query, array($tableName));
	}

	public function getForeignKeyReferences($tableName)
	{
		return $this->callProcedure("getForeignKeyReferences", array($tableName));
	}

	public function callProcedure($procedure, $params, $singleColumn=false, $singleRow=false)
	{
	    $query = "CALL $procedure";
	    $sep="(";
		foreach ($params as $key => $value)
		{
			$query .= $sep . "?";
			$sep = ",";
		}
		$query .= ")";
	    return $this->select($query, $params, $singleColumn, $singleRow);
	}

	public function getColumnNames($tableName)
	{
	    $query = "SHOW COLUMNS FROM $tableName";
		return $this->query($query, true);
	}

	public function showColumns($tableName)
	{
	    $query = "SHOW COLUMNS FROM $tableName";
		return $this->select($query);
	}

	public function showTables()
	{
		return $this->select("SHOW TABLES", null, true);
	}

	//bind parameters for markers, where (s = string, i = integer, d = double,  b = blob)
	private function bindParams($statement, $params)
	{
		if(!$params) return $statement;

		$types = "";
		if (phpversion() >= '5.3')
		{
		    $bind = array();
		    foreach($params as $key => $val)
		    {
				$types .= $this->mysqlParamType($val);
		        $bind[] = &$params[$key];
		    }
		} 
		else
		    $bind = array_values($params);

		array_unshift($bind, $types);
	debug("bindParams", $bind);
		call_user_func_array(array($statement, 'bind_param'), $bind);

		return $statement;
	}

	public function select($query, $params=null, $singleColumn=false, $singleRow=false)
	{	
		debug("SQL: $query", $params);
		if($this->debug && count($params))
			$this->sqlLog[] = array("SQL" => $query) + $params;
		else if($this->debug)
			$this->sqlLog[] = $query;

		if($this->offline) return;
	    $this->connect(); 
		//create a prepared statement
		$statement = $this->mysqlConnection->prepare($query);
		if (!$statement)
		    throw new Exception($this->mysqlConnection->error);

		$this->bindParams($statement, $params);
		$status = $statement->execute();
		$result = $statement->get_result();
debug("statement status", $status);		
debug("statement result ", $statement->num_rows);		
debug("statement affected_rows", $statement->affected_rows);		
		if(!$status)
		{
			debug("SQL Error ". $this->mysqlConnection->errno, $this->mysqlConnection->error);
			$rows = $status;
		}
		else if($statement->insert_id)
			$rows = $statement->insert_id;
		else if($result)
		    $rows = SqlManager::getResultData($result, $singleColumn, $singleRow);
		else //if($statement->affected_rows)
			$rows = $statement->affected_rows;
//		else
//			$rows = $status;

		$statement->close();
//debug("returning", $rows);
	    return $rows;
	}

	public function selectRow($query, $params=null)
	{	
		return $this->select($query, $params, false, true);
	}

	public function selectColumn($query, $params=null)
	{	
		return $this->select($query, $params, true, false);
	}

	public function selectValue($query, $params=null)
	{	
		return $this->select($query, $params, true, true);
	}

	//get result of select as array of rows. each row is an associative array
	private function query($query, $singleColumn=false, $singleRow=false)
	{
		debug("SQL: query", $query);
		if($this->debug)
			$this->sqlLog[] = $query;

		if($this->offline) return;

	    $this->connect(); //if connection not already open 
	    $result = $this->mysqlConnection->query($query);
	    $rows = SqlManager::getResultData($result, $singleColumn, $singleRow);
	    return $rows;
	}

	private static function getResultData($result, $singleColumn=false, $singleRow=false)
	{
	    if($singleColumn && $singleRow)
	   		return SqlManager::getResultValue($result);
	    if($singleRow)
	   		return  SqlManager::getResultRow($result);
	    if($singleColumn)
	   		return SqlManager::getResultColumn($result, 0);
		return SqlManager::getResultRowsAssoc($result);
	}

	//return rows as 2-dimensional array
	private static function getResultRows($result)
	{
	    $rows = array();
	    while($row = $result->fetch_row()) 
	        $rows[]=$row;
		$result->free();
	    return $rows;
	}

	//return a scalar value
	private static function getResultValue($result, $index=0)
	{
		$value = null;
	    $row = is_numeric($index) ? $result->fetch_row() : $result->fetch_assoc();
	    if($row)
	        $value = $row[$index];
		$result->free();
	    return $value;
	}

	//return a column as 1-dimensional array
	private static function getResultColumn($result, $index=0)
	{
	    $rows = array();
	    while($row = is_numeric($index) ? $result->fetch_row() : $result->fetch_assoc())
	        $rows[] = $row[$index];
		$result->free();
	    return $rows;
	}

	//return rows as array of associative arrays
	private static function getResultRowsAssoc($result)
	{
	    $rows = array();
	    while($row = $result->fetch_assoc()) 
	    {
	    	SqlManager::setNullFields($row);
	        $rows[] = $row;
	    }
		$result->free();
	    return $rows;
	}

	private static function setNullFields(&$row)
	{
		foreach ($row as $key => &$value) 
			if($value==="NULL") 
				$value=NULL;
		return $row;
	}

	//return rows as array of associative arrays
	private static function getResultRow($result)
	{
	    $row = $result->fetch_assoc();
		$result->free();
	    return $row;
	}

//=============================== TODO update, insert, upsert, delete functions
// pass values and filters (at least primary key)
// check if columns part of table
	public function update($values, $where)
	{
		$table = arrayExtract($values, "table");
	    $sql = "UPDATE $table";

	    $valuesSql = $this->sqlUpdateValues($values);
	    if($valuesSql) 
	    	$sql .= " SET" . $valuesSql;
	    $whereSql = $this->sqlWhere($where);
	    $sql .= $whereSql;
	    $params = array_merge($values, $where);
debug("update SQL: $sql ", $params);
	    return $this->selectValue($sql, $params);
	}

	public function delete($where)
	{
		$table = arrayExtract($where, "table");
	    $sql = "DELETE FROM $table " . $this->sqlWhere($where);
debug("delete SQL: $sql ", $where);
	    return $this->selectValue($sql, $where);
	}

	private static function sqlWhere(&$params = null)
	{	
		$sql="";
		$sep="WHERE";
		setIfNull($params, $_REQUEST);
	 	//TODO: list of reserved keywords. check if params are valid columm names
		unset($params["debug"]);
		unset($params["table"]);
		unset($params["group_by"]);
		unset($params["order_by"]);
		unset($params["limit"]);
		$where = arrayExtract($params, "where");

	 	foreach($params as $key => $param)
		{
			$sql .= " $sep " . SqlManager::sqlCondition($params, $key, true);
			$sep = "AND";
		}

		if($where)
			$sql .= " $sep $where";
		return $sql;
	}

	private static function sqlCondition(&$params, $key, $statement)
	{	
		$value = $params[$key];
		if($value === NULL)	
		{
			unset($params[$key]);
			return "$key is NULL";
		}

		if(is_array($value) && startsWith($value[0], "%")) 
		{
			unset($params[$key]);
			$sqlValue = $sep = "";
			foreach ($value as $el)
			{
				$sqlValue .= "$sep$key LIKE '$el'";
				$sep = " AND ";
			}
			return $sqlValue;
		}
		
		if(is_array($value)) 
		{
			unset($params[$key]);
			$sqlValue = $sep = "";
			foreach ($value as $el)
			{
				$sqlValue .= "$sep'$el'";
				$sep = ",";
			}
			return "$key in ($sqlValue)";
		}

		//SQL operator	
		$op = "=";
		if(endsWith($key, "_max"))
		{
			$op= "<=";
			$key = substringBefore($key, "_max");
		}
		else if(endsWith($key, "_min"))
		{
			$op= ">=";
			$key = substringBefore($key, "_min");
		}
		else if(contains($value, "%"))
			$op = "LIKE";

		if($statement)
			return "$key $op ?";
		
		return "$key $op '$value'";
	}

	private static function sqlUpdateValues($params)
	{	
		if(!$params) return "";
		$sql="";
		$sep="";
	 	foreach($params as $key => $param)
		{
			$sql .= " $sep $key = ?";
			$sep = ",";
		}
		return $sql;
	}

//generate insert statement SQL
	public static function sqlInsert($params)
	{	
		if(!$params) return "";
	    $table = $params["table"];
		unset($params["table"]);
		$columns = array_keys($params);
		$columns = implode(", ", $columns);

	 	foreach($params as $key => $param)
			$values[] = "?";
		$values = implode(",", $values);
	    return "INSERT INTO $table ($columns) VALUES ($values)";
	}

	public function insert($values)
	{
		//non null values only;
		$values = array_filter($values);
	    $sql = $this->sqlInsert($values);
		unset($values["table"]);
debug("insert SQL: $sql ", $values);
		return $this->selectValue($sql, $values);
	}

	//TODO: generate insert/update form POST form or decoded JSON data
	//make function exists: if exists: insert else update
	//based on table, determine which field is primary key
	//TODO: specify which columns test existence
	public function saveRow($data, $testColumns="")
	{
//		if($this->offline) return;

		$table = $data["table"];
		//1: get table and primary key column name

		$status = false;
		if($testColumns)
			$where = arrayCopyMultiple($data, $testColumns);
		else
		{
			$pk = $this->getPrimaryKey($table);			
			$where = arrayCopyMultiple($data, $pk);
			//todo arrayUnsetMultiple
			$pkey=reset($pk);
			$updateValues = $data;
			unset($updateValues[$pkey]); //do not update pk value
		}

debug("saveRow", $data);
debug("saveRow", $where);	
		//2: if where or pk provided in data: try to update row
		if($where)
			$status = $this->update($updateValues, $where);
		//3: if not updated: try to insert
		if(!$status)
			$status = $this->insert($data);
		return $status;
	}


	//group child data by foreign key value
	//attach each group to corresponding parent record
	public static function groupJoinResults(&$parentData, $childData, $childField, $pkColumn, $fkColumn)
	{
		if(!$parentData || !$childData) return $parentData;
		$childData = arrayGroupBy($childData, $fkColumn);
		foreach ($parentData as $key => &$parent)
		{
			$parentId = $parent[$pkColumn];
			if(isset($childData[$parentId]))
				$parent[$childField] = $childData[$parentId];
		}
		return $parentData;
	}
}
?>