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

//save exif data
$uploadedFile = "C:\\inetpub\\wwwroot\\upload\\newuser\\original\\";
$uploadedFile .= "BAK20100908_3148.jpg";

//$uploadedFile = "C:\\Users\\arthur.raynaud\\Pictures\\2015\\Kokopelli\\";
//$uploadedFile .= "20150306_214016.m4v";
//$uploadedFile .= "20150306_214016.jpg";
//$uploadedFile .= "20150306_214016_cut.gif";
$exif = getImageMetadata($uploadedFile);
$dateTaken = getExifDateTaken($uploadedFile, $exif);
$message =  "";
addVarToArray($response, "message");
addVarToArray($response, "dateTaken");
addVarToArray($response, "exif");

$response["time"] = getTimer(true);
echo jsValue($response,true, true);
?>