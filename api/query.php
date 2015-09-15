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
	//$sql = "SELECT * FROM user_upload order by image_date_taken desc";
	$uploads = $db->selectWhere(array("table" => "user_upload", "order_by" => "image_date_taken desc", "username" => $username));
//	$uploads = array_filter($uploads, "uploadedFileExists");	
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

$postJson = getJsonPostData();
$username = fpCurrentUsername();

$db = new SqlManager($fpConfig);
if($db->offline)
{
	echo file_get_contents("query.json");
	return;
}


$uploads = userLatestUploads($db, $username);

setExists($uploads);

$db->disconnect();

//echo jsValue($postJson, true);
echo jsValue($uploads, true, true);
?>