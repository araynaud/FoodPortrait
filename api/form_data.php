<?php
require_once("../include/includes.php");
session_start(); 
$db = new SqlManager($fpConfig);

$postJson = getJsonPostData();
$action = reqParam("action");
if(!$action)
	$action = @$postJson["action"];
$username = fpCurrentUsername();
$response = array();

switch ($action)
{
	case "saveForm":
		//save form: insert,update,delete user_answers for this user
		$user_answers = $postJson["formData"];
		$result = saveAnswers($db, $username, $user_answers);
		$response = array("answers" => count($user_answers), "result" => $result);
		break;
	case "form_questions":
	default:
		$section_id = reqParam("section_id");
		$where = "";
		if(contains($section_id, ","))
			$where = " where section_id in ($section_id)";
		else if($section_id)
			$where = " where section_id = $section_id";
		$sql = "SELECT * FROM form_question $where order by section_id, position, id";
		$form_questions = $db->select($sql);
		$sql = "SELECT * FROM form_answer order by question_id, position, id";
		$form_answers = $db->select($sql);
		//group join the 2 results
		$response["questions"] = $db->groupJoinResults($form_questions, $form_answers, "form_answers", "id", "question_id");
		if($username)
		$response["user_answers"] = $db->selectWhere(array("table" => "user_answer", "username" => $username));
		break;
}
$db->disconnect();

if(isAssociativeArray($response))
	$response["time"] = getTimer(true);
else if(count($response) && is_array($response[0]))
	$response[0]["time"] = getTimer(true);

echo jsValue($response);
?>