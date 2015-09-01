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

function errorMessage($msg)
{
	global $response;
	$response["post"] = $_POST;
	$response["message"] = $msg;
	$response["time"] = getTimer(true);
	echo jsValue($response, true);
}

$username = fpCurrentUsername();
$upload_id = postParam("upload_id");
debugVar("username",true);
debug("Request", $_REQUEST);
debug("GET request", $_GET);
debug("POST request", $_POST);
debug("POST files", $_FILES, true);

$response=array();

if(empty($_FILES) && !$upload_id)
	return errorMessage("No File uploaded.");		



if($upload_id)
{
	$db = new SqlManager($fpConfig);
	$success = saveUploadData($db, $_POST);
	$db->disconnect();
}

$response["post"] = $_POST;
$nbFiles=count($_FILES);

if(!empty($_FILES))
{
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
	$dataRoot = getConfig("upload._diskPath");
	$dataRootUrl = getConfig("upload.baseUrl");

	createDir($dataRoot, "$username/original"); //depending on user permissions? // username/subdir
	$uploadDir  = combine($dataRoot, $username, "original");
	$uploadedFile = combine($dataRoot, $username, "original", $filename);
	$uploadUrl = combine($dataRootUrl, $username, $filename);
	$filesize = filesize($tmpFile);
	$success = move_uploaded_file($tmpFile, $uploadedFile);
	$maxUploadSize = ini_get("upload_max_filesize");
	$resized = createThumbnail($uploadDir, $filename, '..', 225);

	addVarToArray($response, "filename");
	addVarToArray($response, "uploadUrl");
	if(getConfig("debug.output"))
		addVarToArray($response, "resized");

	addVarToArray($response, "filesize");
	addVarToArray($response, "mimeType");
	//addVarToArray($response, "maxUploadSize");
	debug("moving to $uploadUrl", $success);
	if(!$success)
		return errorMessage("Cannot move file into target dir.");

	//save exif data
	$message =  "File uploaded.";
	$exif = getImageMetadata($uploadedFile);
	$dateTaken = getExifDateTaken($filename, $exif);

	if(!$dateTaken)
		$dateTaken = getIptcDate($exif);

	//if(!$dateTaken)
	//$dateTaken = getFileDate($filename);
	$description = arrayGetCoalesce($exif, "ImageDescription", "IPTC.Caption");
	$description = trim($description);

	writeCsvFile("$uploadedFile.exif.txt", $exif);
	writeTextFile("$uploadedFile.exif.js", jsValue($exif));
}

//TODO: insert row in upload table
//when to update?

if($success)
{
	$db = new SqlManager($fpConfig);
	if($db->offline)
		$upload_id = -1;
	else if(!$upload_id) //step 1
		$upload_id = $db->offline ? -1 : saveUploadData($db, $exif);
	else //step 2
	{
		$success = saveUploadData($db, $_POST);
		$message =  "Details saved.";
	}

	$db->disconnect();
}

addVarToArray($response, "nbFiles");
addVarToArray($response, "success");
addVarToArray($response, "message");
addVarToArray($response, "upload_id");
addVarToArray($response, "dateTaken");
addVarToArray($response, "description");
if(getConfig("debug.output"))
	addVarToArray($response, "exif");

$response["time"] = getTimer(true);
echo jsValue($response,true, true);
?>