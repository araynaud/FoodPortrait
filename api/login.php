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
// POST: {action: sendResetEmail, email}, return success or error if user not found
// POST: {action: resetPassword, email, key, password}, return success or error if user not found or invalid key or empty password

// GET/POST  {}		return current session["user"];
// GET/POST  {action: logout} unset session["user"], return empty or null user object;

// response: {success: true, user: {}, message: }

//compare MD5 password in db with MD5 password submitted	
function validatePassword($dbUser, $postData)
{
	if(!$dbUser || !isset($postData["password"]))	return false;
	return $dbUser["password"] == $postData["password"];
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
		$response["success"] = $db->insert($params);
		if($response["success"])
		{
			sendEmailFromTemplate("signup", $params);
			$response["user"] = fpSetUser($postData);
		}
		else
			$response["message"] = "This username or email is already taken. Please choose a different username, or log in to your account, or reset your password.";
		break;

	case "sendResetEmail":
		$dbUser = getUser($db, $postData["email"], "email");
		$response["success"] = !!$dbUser;
		if(!$dbUser)
		{
			$response["message"] = "No user found with this email address.";
			break;
		}

		//Generate key, update user in db, then send in email
		$resetKey = getResetKey($dbUser["email"]);
		$dbUser["reset_key"] = $resetKey;
		$response["success"] = updateUser($db, $dbUser);
		if(!$response["success"])
		{
			$response["message"] = "Error updating user.";
			break;
		}

		$resetLink = combine("#/reset-password", $dbUser["email"], $resetKey);
		$data = array("resetLink" => $resetLink); 
		$response["success"] = sendEmailFromTemplate("reset1", $dbUser, $data);
		$response["message"] = $response["success"] ? "Reset email has been sent." : "Error sending reset email.";
		break;

	case "resetPassword":
		$response["success"] = false;

		if(!@$postData["password"])
		{
			$response["message"] = "Password cannot be empty.";
			break;
		}

		$dbUser = getUser($db, $postData["email"], "email");
		if(!$dbUser)
		{
			$response["message"] = "No user found with this email address.";
			break;
		}

		if($dbUser["reset_key"] !== $postData["key"])
		{
			$response["message"] = "Invalid reset key.";
			break;
		}

		$dbUser["reset_key"] = "";
		$dbUser["password"]  = $postData["password"];
		$response["success"] = updateUser($db, $dbUser);

		$response["message"] = $response["success"]  ? "Password has been changed." : "Error setting Password.";
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