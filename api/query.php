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
}

//get username list from profile filters
function filterUsers($db, $filters)
{
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

$results = userLatestUploads($db, $username);
$results = array_filter($results, "uploadedFileExists");	
setExists($results);
$db->disconnect();

$response=array();
addVarToArray($response, "username");
addVarToArray($response, "params");
addVarToArray($response, "results");

echo jsValue($response, true);
?>