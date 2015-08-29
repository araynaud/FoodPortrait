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

//echo jsValue($postJson, true);

if($db->offline)
{	echo file_get_contents("query.json");
	return;
}

$uploads = array_filter($uploads, "uploadedFileExists");
echo jsValue($uploads, true, true);
?>