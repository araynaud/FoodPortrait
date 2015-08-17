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

$_REQUEST["debug"]=true;
//response: image metadata from EXIF and url.

//echo jsValue($fpConfig, true, true);

function errorMessage($msg)
{
	global $response;
	$response["message"] = $msg;
	$response["time"] = getTimer(true);
	echo jsValue($response, true);
}

$username = fpCurrentUsername();
debugVar("username",true);
debug("Request", $_REQUEST);
debug("GET request", $_GET);
debug("POST request", $_POST);
debug("POST files", $_FILES, true);

$response=array();
if(empty($_FILES))
	return errorMessage("No File uploaded.");		

$firstFile = reset($_FILES);
$tmpFile = $firstFile["tmp_name"];
$mimeType = $firstFile["type"];
$filename = utf8_decode($firstFile["name"]);
$filename = cleanupFilename($filename);

$getcwd=getcwd();
$freeSpace=disk_free_space("/");

$uploaded = is_uploaded_file($tmpFile);
$message="OK";
if(!$uploaded)
	return errorMessage("Uploaded file not found.");
//verify file type
if(!startsWith($mimeType, "image"))
	return errorMessage("Uploaded file $filename is not an image. ($mimeType)");

//cleanup file name
$filename = cleanupFilename($filename);
//move file to destination dir
$dataRoot = getConfig("_upload.path");
$dataRootUrl = getConfig("_upload.url");

createDir($dataRoot, "$username/original"); //depending on user permissions? // username/subdir
$uploadDir  = combine($dataRoot, $username, "original");
$uploadedFile = combine($dataRoot, $username, "original", $filename);
$uploadUrl = combine($dataRootUrl, $username, $filename);
$filesize = filesize($tmpFile);
$success = move_uploaded_file($tmpFile, $uploadedFile);
$maxUploadSize = ini_get("upload_max_filesize");
//$resized = createThumbnail($uploadDir, $filename, '..', 1000);
$resized = createThumbnail($uploadDir, $filename, '..', 225);

$response["post"] = $_POST;
addVarToArray($response, "filename");
addVarToArray($response, "uploadUrl");
addVarToArray($response, "resized");

addVarToArray($response, "success");
addVarToArray($response, "filesize");
addVarToArray($response, "mimeType");
//addVarToArray($response, "maxUploadSize");
debug("moving to $uploadUrl", $success);
if(!$success)
	return errorMessage("Cannot move file into target dir.");

//save exif data
$exif = getImageMetadata($uploadedFile);
$dateTaken = getExifDateTaken($filename, $exif);
$description = arrayGetCoalesce($exif, "ImageDescription", "IPTC.Caption");

writeCsvFile("$uploadedFile.exif.txt", $exif);
writeTextFile("$uploadedFile.exif.js", jsValue($exif));

//TODO: insert row in upload table
//when to update?
$db = new SqlManager($fpConfig);
$db->disconnect();

$message =  "File uploaded.";
addVarToArray($response, "message");
addVarToArray($response, "dateTaken");
addVarToArray($response, "description");
addVarToArray($response, "exif");

$response["time"] = getTimer(true);
echo jsValue($response,true, true);
?>