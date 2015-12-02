<?php
require_once("../include/includes.php");
setContentType("text","plain");
session_start(); 
//receive file and other form fields
//move file to temp location:  $BASE_IMAGE_DIR/$username/$filename
//load, resize, crop image.
//do not keep original file
//insert into upload table.
//What if user/filename already exists? can user reuse existing image, select from uploads?
//response: image metadata from EXIF and url.

//echo jsValue($fpConfig, true, true);

$username = fpCurrentUsername();
$upload_id = reqParam("upload_id");
debugVar("username",true);
debug("Request", $_REQUEST);
debug("GET request", $_GET);
debug("POST request", $_POST);
debug("POST files", $_FILES, true);

if(!$username)
	return errorMessage("Not logged in.");		

if(!$upload_id)
	return errorMessage("No File deleted.");		

$db = new SqlManager($fpConfig);
if($db->offline)
	return errorMessage("DB offline. No File deleted.");		

//if profile filters( Q_ ) : demographic
//otherwise: personal
$params = array("table" => "user_upload");
addVarsToArray($params, "username upload_id");
//$results = demographicPortrait($db, $params);
$success = $db->delete($params);
$message = $success  ? "record $upload_id deleted." : "record id $upload_id not deleted.";
$db->disconnect();

//TODO delete image file: check if other records for this user use this filename. if only 1, delete file and its thumbs.

$response = array();
addVarsToArray($response, "success message upload_id");
$response["time"] = getTimer(true);
echo jsValue($response, true, true);
?>