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
//TODO: add actions: sendResetEmail and resetPassword 

//compare MD5 password in db with MD5 password submitted	
function validatePassword($dbUser, $postData)
{
	if(!$dbUser || !isset($postData["password"]))	return false;
	return $dbUser["password"] == $postData["password"];
	//return $dbUser["password"] == md5($postData["password"]);
}

switch ($action)
{
	case "login":
		//find user by username or by email
		$dbUser = getUser($db, $postData["username"]);
		if(!$dbUser)
			$dbUser = getUser($db, $postData["username"], "email");
		debugVar("dbUser");
		$response["success"] = validatePassword($dbUser, $postData);
		if($response["success"])
		{			
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
			$response["message"] = "This username or email is already taken. Please choose a different username, or log in to your account, or reset your password.";
		break;

	case "sendResetEmail":
		$dbUser = getUser($db, $postData["email"], "email");
		$response["success"] = !!$dbUser;
		if(!$dbUser)
			$response["message"] = "No user found with this email address.";
		else
		{
			$response["success"] = sendEmailFromTemplate("reset1", $dbUser);
			$response["message"] = $response["success"] ? "Reset email has been sent." : "Error sending reset email.";
		}
		break;

	case "resetPassword":
		$dbUser = getUser($db, $postData["email"], "email");
		$updated = false;
		if($dbUser)
		{
			$where = array("email" => $postData["email"]);
			$where = array("username" => $dbUser["username"]);
			$updated = $db->update($postData, $where);
		}
		$response["success"] = !!$dbUser && $updated;
		$response["message"] = $response["success"]  ? "resetPassword done." : "resetPassword error.";
		break;

	case "logout":
		fpUserLogout();
		$response["message"] = "User logged out.";
	default:
		$response["user"] = fpCurrentUser();
}
$db->disconnect();

echo jsValue($response, true, true);
?>