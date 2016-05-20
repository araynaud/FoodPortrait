<?php
require_once("../include/includes.php");
setContentType("text","plain");
session_start();

//get query parameters from request
//query database: upload, user and user_answer tables
//return list of uploads with image urls.
$params = getJsonPostData();
debugVar("params");
if(!$params) $params = $_REQUEST;

$username = fpCurrentUsername();

$db = new SqlManager($fpConfig);

if($db->offline)
{
	echo file_get_contents("query.json");
	return;
}

$questions = getFormQuestions($db);
$questions = arrayIndexBy($questions, "id");
//debugVar("questions", true);

$portraitType = arrayExtract($params, "portrait");
//convert age to year_born
ageToYearBorn($db, $params);

$order = arrayExtract($params, "order");
$groupBy = arrayExtract($params, "group");
$limit = reqParam("limit", 20);

if(!$groupBy)
{
	$results = demographicPortrait($db, $params, $portraitType);
	//$users = arrayDistinct($results, "username");
	$users = filterUsers($db, $params);
	arrayDistinct($results, "username");
	setExists($results);
	$results = array("all" => $results);
}
else
{
	//if groupBy is a profile question, translate group=gender => group=Q_0
	$questionsByField = arrayIndexBy($questions, "field_name");
	if(array_key_exists($groupBy, $questionsByField))
	{
		$qid = $questionsByField[$groupBy]["id"];
		$groupBy = "Q_$qid";
	}

	$groups = getDistinctGroups($db, $params, $groupBy);

	$questionId = getQuestionId($groupBy);
	$form_answers = null;
	if($questionId !== "")
	{
		$qtype = getQuestionType($questionId);	
		if(isset($questions[$questionId]["form_answers"]))
			$form_answers = arrayIndexBy($questions[$questionId]["form_answers"], "id");
	}


debugVar("groups");
	$results = array();
	foreach ($groups as $value) 
	{
		if($value === "NULL") continue;
		$params[$groupBy] = $value;

		$groupKey = $value;
		if(is_numeric($value)) 
			$groupKey = "group_$value";

		if(@$form_answers[$value])
			$groupTitles[$groupKey] = $form_answers[$value]["label"];

		$results[$groupKey] = demographicPortrait($db, $params, $portraitType);
		setExists($results[$groupKey]);
	}
}

$queries = $db->getLog();
$db->disconnect();

$response=array();
$response["time"] = getTimer(true);
addVarsToArray($response, "params age years users groups groupTitles queries results");
echo jsValue($response, true);
getTimer();
?>