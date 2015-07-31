<?php
require_once("../include/includes.php");
setContentType("text","plain");
session_start(); 
//receive file and other form fields
//move file to temp location:  $BASE_IMAGE_DIR/$username/$filename
//load, resize, crop image.
//do not keep original file

//response: image metadata from EXIF and url.

//echo jsValue($fpConfig, true, true);

$username = fpCurrentUsername();
debugVar("username",true);
debug("Request", $_REQUEST);
debug("GET request", $_GET);
debug("POST request", $_POST);
debug("POST files", $_FILES, true);

$response=array();
if(empty($_FILES))
{
	$message =  "No File uploaded.";
	addVarToArray($response, "message");
	$response["time"] = getTimer();
	echo jsValue($response,true);
	return;
}

$uploadedFile = reset($_FILES);
$tmpFile = $uploadedFile["tmp_name"];
$mimeType = $uploadedFile["type"];
$filename = $uploadedFile["name"];
addVarToArray($response,"uploadedFile");

$getcwd=getcwd();
$freeSpace=disk_free_space("/");

$uploaded=is_uploaded_file($tmpFile);
$message="OK";
if(!$uploaded)
	$message = "File not found.";

//verify file type
//if(strstr($mimeType, "image") === false)
//	$message="Uploaded file is not an image";

//cleanup file name
$filename=cleanupFilename($filename);

//move file to destination dir
$dataRoot = getConfig("_upload.path");

createDir($dataRoot, "$username/original"); //depending on user permissions? // username/subdir
$target = combine($dataRoot, $username, "original", $filename); // "original");
debug($tmpFile, $target);
$moved = move_uploaded_file($tmpFile, $target);
$filesize = $moved ? filesize($target) : 0;
$maxUploadSize = ini_get("upload_max_filesize");

$response["post"] = $_POST;
addVarToArray($response, "target");
addVarToArray($response, "moved");
addVarToArray($response, "filesize");
addVarToArray($response, "maxUploadSize");
debug("moving to $target", $moved);
if(!$moved)
	$message = "Cannot move file into $target";
else
	$message =  "File uploaded.";
addVarToArray($response, "message");

$response["time"] = getTimer();
echo jsValue($response,true, true);

//echo jsValue($_FILES, true, true);
getTimer(true);
?>