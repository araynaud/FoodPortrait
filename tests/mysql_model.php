<?php
require_once("../include/includes.php");

$fpConfig = readConfigFile("../fp.config");
$db = new SqlManager($fpConfig);
$table = reqParam("table", "form_question");
$dbModel = $table ? $db->getTableModel($table) : $db->getDbModel();
debugVar("dbModel", true);
getTimer(true);
$db->disconnect();
getTimer(true);

echo json_encode($dbModel, JSON_PRETTY_PRINT);
?>