<?php
require_once("../include/includes.php");
setContentType("text","plain");
session_start();

//get query parameters from request
//query database: upload, user and user_answer tables
//return list of uploads with image urls.

$postJson = getJsonPostData();
$username = fpCurrentUsername();

$db = new SqlManager($fpConfig);
//$sql = "SELECT * FROM user_upload order by image_date_taken desc";
$uploads = $db->selectWhere(array("table" => "user_upload", "username" => $username));
$db->disconnect();

function getImagePath($u)
{
	$basePath = getConfig("upload._diskPath");
	return combine($basePath, $u["username"], $u["filename"]);
}


function uploadedFileExists($u)
{
	$imagePath = getImagePath($u);
	return file_exists($imagePath);
}

//echo jsValue($postJson, true);

if(!$uploads)
	echo file_get_contents("query.json");
else
{
	$uploads = array_filter($uploads, "uploadedFileExists");
	echo jsValue($uploads, true);
}
//getTimer(true);
?>