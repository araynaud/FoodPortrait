<?php
require_once("../include/includes.php");
setContentType("text","plain");
session_start();

//get query parameters from request
//query database: upload, user and user_answer tables
//return list of uploads with image urls.

//TODO: add sort, limit = columns x rows
//filters = meal, date
function userLatestUploads($db, $username, $filters = null)
{
	$uploads = $db->selectWhere(array("table" => "user_upload", "order_by" => "image_date_taken desc", "username" => $username));
	return $uploads;
}

function setExists(&$uploads)
{
	foreach ($uploads as &$u)
		$u["exists"] = uploadedFileExists($u);
}

//filter uploads from selected user
function demographicPortrait($db, $filters)
{
	$users = filterUsers($db, $filters);
	$uploads = $db->selectWhere(array("table" => "user_upload", "order_by" => "image_date_taken desc", "username" => $users));
	return $uploads;
}

//get username list from profile filters
function filterUsers($db, $filters)
{
	if(!$filters) return null; //all users

	$query = "SELECT username FROM user";
//where username in (select username from user_answer where question_id = 0 and answer_id = 3)
//and username in (select username from user_answer where question_id = 16 and answer_id = 65)

	$and = "WHERE";
	foreach ($filters as $key => $answerId) 
	{
		$questionId = substringAfter($key,"Q_");
debug("filterUsers", "$key = $questionId");
		if($questionId=="") continue;
		$query .= " $and username in (select username from user_answer where question_id = $questionId and answer_id = $answerId)";
		$and="AND";
	}
debug("filterUsers", $query);

	$users = $db->select($query, null, true);

	return $users;
}

// end functions

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

//if profile filters( Q_ ) : demographic
//otherwise: personal
$users = filterUsers($db, $params);
if($users)
	$results = demographicPortrait($db, $params);
else
	$results = userLatestUploads($db, $username);

//$results = array_filter($results, "uploadedFileExists");	
setExists($results);
$db->disconnect();

$response=array();
addVarToArray($response, "username");
addVarToArray($response, "params");
addVarToArray($response, "users");
addVarToArray($response, "results");

echo jsValue($response, true);
?>