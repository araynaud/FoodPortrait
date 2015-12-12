<?php
require_once("../include/includes.php");
setContentType("text","plain");
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
		$response["questions"] = getFormQuestions($db, $section_id);
		if($username)
			$response["user_answers"] = $db->selectWhere(array("table" => "user_answer", "username" => $username));
}
$db->disconnect();

if(isAssociativeArray($response))
	$response["time"] = getTimer(true);
else if(count($response) && is_array($response[0]))
	$response[0]["time"] = getTimer(true);

echo jsValue($response);
?>