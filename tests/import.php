<?php
// import.php: import multiple images into DB, resize them for FP web app
require_once("../include/includes.php");
setContentType("text","plain");
session_start(); 

$CSV_SEPARATOR="\t"; //columns inside a row
$CSV_SEPARATOR2=";"; //values inside a field

//TODO: check loggedin. if not admin, return to home page
$insert = reqParamBoolean("insert");
$username = reqParam("username");
$filename = "../docs/FoodDiaryUsers.txt";
$users = loadUsers($filename);

$db = new SqlManager($fpConfig);
$questions = getFormQuestions($db);
$questions = arrayIndexBy($questions, "field_name");

$rows = importUsers($users, $questions);

// TODO: SqlManager: insert/update multiple rows;
if($insert)
	foreach($rows as $row)
		$db->insert($row);

$db->disconnect();

$rowsByTable = arrayGroupBy($rows, "table");
$users = $rowsByTable["user"];
$userAnswers = $rowsByTable["user_answer"];
$db->groupJoinResults($users, $userAnswers, "user_answers", "username", "username");

$response = array();
addVarsToArray($response, "users questions");
$response["time"] = getTimer(true);
echo jsValue($response, true);
?>