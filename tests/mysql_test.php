<?php
require_once("../include/includes.php");

$fpConfig = readConfigFile("../fp.config");

debug("phpversion", phpversion());
$db = new SqlManager($fpConfig);
$table = reqParam("table", "form_question");
getTimer(true);

$tables = $db->showTables();
debugVar("tables");
getTimer(true);

$columns = $db->getColumnNames($table);
debugVar("columns");
getTimer(true);

$columns = $db->showColumns($table);
echo rowsToTextTable($columns);
getTimer(true);

$pk = $db->getPrimaryKey($table);
debugVar("pk");
getTimer(true);

$fk = $db->getForeignKey($table);
echo rowsToTextTable($fk);
getTimer(true);

$fk = $db->getForeignKeyReferences($table);
echo rowsToTextTable($fk);
getTimer(true);

debugVar("_REQUEST");
$params = array();
foreach ($_REQUEST as $key => $value) 
	$params[$key] = parseValue($value);
$params["table"] = $table;

debugVar("params");

$rows = $db->selectWhere($params);
echo rowsToTextTable($rows, true, "|", 30); 
getTimer(true);

$exists = $db->count($params);
debugVar("exists", true);
getTimer(true);

$exists = $db->exists($params);
debugVar("exists", true);
getTimer(true);

$db->disconnect();
getTimer(true);
?>