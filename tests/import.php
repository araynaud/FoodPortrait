<?php
// import.php: import multiple images into DB, resize them for FP web app
require_once("../include/includes.php");
setContentType("text","plain");
session_start(); 


//loop all images in a dir
//find files that are not in database => insert into user_uploads
//or 
function importImages($db, $username)
{
	// list files in dir that are not in the database for this user

	$dataRoot = getConfig("upload._diskPath");
	$userDir = combine($dataRoot, $username);
	$files = scandir($userDir);
	array_shift($files);
	array_shift($files);
debug(count($files) . " files", $files);

$dbImages = $db->select("SELECT distinct filename from user_upload where username='$username'", null, true);
debug(count($dbImages) . " DB filenames", $dbImages);
	//difference: insert
	//interserction : update or ignore ?
}

$CSV_SEPARATOR="\t"; //columns inside a row
$CSV_SEPARATOR2=";"; //values inside a field

function importUsers($filename)
{
	if(!$filename) return;
	//$txt = readTextFile($filename);
	$data = readCsvTableFile($filename, false, true);
	return $data;
}

//do the same as in upload.php
//move original to ./original/
//resize to 1000, .tn
function importImage($dir, $filename)
{	
	//cleanup file name
	$filename = cleanupFilename($filename);
	//move file to destination dir
	$dataRoot = getConfig("upload._diskPath");
	$dataRootUrl = getConfig("upload.baseUrl");

	createDir($dataRoot, "$username/original"); //depending on user permissions? // username/subdir
	createDir($dataRoot, "$username/.tn"); //depending on user permissions? // username/subdir
//resize / move image
	$uploadDir  = combine($dataRoot, $username, "original");
	$uploadedFile = combine($dataRoot, $username, "original", $filename);
	$uploadUrl = combine($dataRootUrl, $username, $filename);
	$filesize = filesize($tmpFile);
//	$success = move_uploaded_file($tmpFile, $uploadedFile);
//	$maxUploadSize = ini_get("upload_max_filesize");
	$resized = createThumbnail($uploadDir, $filename, '..', 1000);
	$resizedDir = combine($dataRoot, $username);
	$resized = createThumbnail($resizedDir, $filename, '.tn', 225);

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

	unlink($uploadedFile);

//TODO: insert row in upload table
//when to update? if username/filename already exists

	$db = new SqlManager($fpConfig);
	if($db->offline)
		$upload_id = -1;
	else if(!$upload_id) //step 1
		$upload_id = saveUploadData($db, $exif);
	else //step 2
	{
		$success = saveUploadData($db, $_POST);
		$message =  "Details saved.";
	}

	$db->disconnect();
}


//resize image in multiple versions:
//eg: hd:1920, ss:1000, tn:225
function resizeImageMultiple($dir, $filename)
{
	
}

//TODO: check loggedin. if not admin, return to home page

$username = reqParam("username");
$filename = "../docs/FoodDiaryUsers.txt";

$db = new SqlManager($fpConfig);
$users = importUsers($filename);
importImages($db, $username);
$db->disconnect();

$response = array();
addVarToArray($response, "users");
$response["time"] = getTimer(true);
echo jsValue($response, true, true);
?>