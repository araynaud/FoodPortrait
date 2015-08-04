<?php
require_once("../include/includes.php");
setContentType("text","plain");
session_start(); 

$db = new SqlManager($fpConfig);
$postData = $_POST;
if(!$postData)
	$postData = getJsonPostData();

//$postData = array("action" => "login", "username" => "amy", "password" => "koKoBird" );
debugVar("postData");

$action = $postData ? @$postData["action"] : reqParam("action");
debugVar("action");

$response = array();

// POST: {action: login, username:, password: md5}, return user if successful or null if login fail
// POST: {action: register, username, email, password: md5, first_name, last_name}, return user if successful or null if login already exists.
// GET/POST  {}		return current session["user"];
// GET/POST  {action: logout} unset session["user"], return empty or null user object;

// response: {success: true, user: {}, message: }

function validatePassword($dbUser, $postData)
{
	if(!$dbUser || !isset($postData["password"]))	return false;

	return $dbUser["password"] == $postData["password"];
	return $dbUser["password"] == md5($postData["password"]);
}

switch ($action)
{
	case "login":
		$params = arrayCopyMultiple($postData, "username");
		$params["table"] = "user";
		$dbUser = $db->selectWhere($params);
		$dbUser = reset($dbUser);
		debugVar("dbUser");
		$response["success"] = false;
		if(validatePassword($dbUser, $postData))
		{
			//TODO compare MD5
			$response["success"] = true;
			$response["message"] = "User logged in.";			
			unset($dbUser["password"]);
			$dbUser["hasProfile"] = hasProfile($db, $dbUser["username"]);

			$response["user"] = fpSetUser($dbUser);
		}
		else
		{
			$response["message"] = "Login failed.";
			fpUserLogout();
		}
		break;

	case "signup":
		$params = $postData;
		$params["table"] = "user";
		unset($params["action"]);
		if($db->insert($params))
			$response["user"] = fpSetUser($postData);
		else
			$response["message"] = "This username is already taken. Please choose a different username, or log in to your account.";
		break;

	case "logout":
		fpUserLogout();
		$response["message"] = "User logged out.";
	default:
		$response["user"] = fpCurrentUser();
		break;
}
$db->disconnect();

echo jsValue($response, true, true);
?>