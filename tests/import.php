<?php
// import.php: import multiple images into DB, resize them for FP web app
require_once("../include/includes.php");
setContentType("text","plain");
session_start(); 


//loop all images in a dir
//find files that are not in database => insert into user_uploads
//or 
function importImages($db, $username)
{
	// list files in dir that are not in the database for this user

	$dataRoot = getConfig("upload._diskPath");
	$userDir = combine($dataRoot, $username);
	$files = scandir($userDir);
	array_shift($files);
	array_shift($files);
debug(count($files) . " files", $files);

$dbImages = $db->select("SELECT distinct filename from user_upload where username='$username'", null, true);
debug(count($dbImages) . " DB filenames", $dbImages);
	//difference: insert
	//interserction : update or ignore ?
}

$CSV_SEPARATOR="\t"; //columns inside a row
$CSV_SEPARATOR2=";"; //values inside a field

function loadUsers($filename)
{
	if(!$filename) return;
	$data = readCsvTableFile($filename, false, true);
	return $data;
}

function importUsers($users, $questions)
{
	$rows = array();
	foreach ($users as $user)
	{
		$username = arrayExtract($user, "username");
		if(!$username) continue;
		$username = str_replace(" ", "", $username);
		if(!$username) continue;

	//1 insert into user
		$userRow = array();
		$userRow["table"] = "user";
		$userRow["username"] = $username;
		$userRow["password"] = md5($username);
		$rows[] = $userRow;

	//2 insert into user_answer 1 row per user column
		foreach ($user as $column => $userValues)
		{
			if(!isset($questions[$column]) || !$userValues) continue;

			$q = $questions[$column];
//TODO: if question type == multiple,  $userValue is array: insert several rows
			$userValues = toArray($userValues, ";");
			foreach ($userValues as $key => $userValue)
			{
				$row = array();
				$row["table"] = "user_answer";
				$row["username"] = $username;
				$row["field"] = $column;
				$row["value"] = $userValue;
				$row["question_id"] = $q["id"];
				$answer = findAnswer($q, $userValue);
				if($answer)
				{
					$row["answer_id"] = $answer["id"];
					$row["answer"] = $answer;
					if($answer["value"])
						$row["answer_value"] = $answer["value"];
				}
				else if($q["data_type"] == "number")
					$row["answer_value"] = $userValue;
				else
				//TODO if question type is text or number 
					$row["answer_text"] = $userValue; //TODO: Find answer id, match by  partial text;
				$rows[] = $row;
			}
		}
	}
	return $rows;
}

//TODO: Find answer id, match by  partial text;
function findAnswer($question, $userValue)
{
	if(!isset($question["form_answers"])) return null;

	foreach ($question["form_answers"] as $key => $ans)
		if(matchStrings($userValue, $ans["label"]))
			return $ans;

	return null;
}

//match first word ?
function matchStrings($userValue, $label)
{
//	debug("matchStrings $label", $userValue);

	if(!strcasecmp($userValue, $label)) return true;
	
	$firstWord = substringBefore($label, " ");
	return startsWith($userValue, $firstWord);
}


//TODO: check loggedin. if not admin, return to home page
$username = reqParam("username");
$filename = "../docs/FoodDiaryUsers.txt";
$users = loadUsers($filename);

$db = new SqlManager($fpConfig);
$questions = getFormQuestions($db);
$questions = arrayIndexBy($questions, "field_name");

$rows = importUsers($users, $questions);

// TODO: SqlManager: insert/update multiple rows;

$db->disconnect();

$response = array();
//$response["questions"] = $questions;
//$response["users"] = $users;
addVarsToArray($response, "rows questions");
$response["time"] = getTimer(true);
echo jsValue($response, true, true);
?>