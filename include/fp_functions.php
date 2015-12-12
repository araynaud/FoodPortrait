<?php

function fpCurrentUser()
{
    return arrayGet($_SESSION, "fp_user");
}

function fpCurrentUsername()
{
    return arrayGet($_SESSION, "fp_user.username");
}

function fpSetUser($user)
{
   return $_SESSION["fp_user"] = $user;
}

function fpUserLogout()
{
    unset($_SESSION["fp_user"]);
}

function readJsonFile($filename)
{
    $postdata = file_get_contents($filename);
    if($postdata)
        $postdata = json_decode($postdata, true);
    return $postdata;
}

function getJsonPostData()
{
    return readJsonFile("php://input");

    $postdata = file_get_contents("php://input");
    if($postdata)
        $postdata = json_decode($postdata, true);
    return $postdata;
}

function errorMessage($msg)
{
    global $response;
    $response["post"] = $_POST;
    $response["message"] = $msg;
    $response["time"] = getTimer(true);
    die(jsValue($response, true));
}

function hasProfile($db, $username)
{
    return $db->exists(array("table" => "user_answer", "username" => $username));
}


function getFormQuestions($db, $section_id=null)
{
    if(!$db || $db->offline)
    {
        $questions = readJsonFile("../api/form_data.json");
        if(isset($questions["questions"]))
            $questions = $questions["questions"];
        return $questions;
    }

    if(contains($section_id, ","))
        $section_id = explode(",", $section_id);
    $p = array("table" => "form_question", "order_by" => "section_id, position, id");
    if($section_id) 
        $p["section_id"] = $section_id;
    $form_questions = $db->selectWhere($p);

    $p = array("table" => "form_answer", "order_by" => "question_id, position, id");
    $form_answers = $db->selectWhere($p);
    //group join the 2 results
    return $db->groupJoinResults($form_questions, $form_answers, "form_answers", "id", "question_id");
}

//save form: insert,update,delete user_answers for this user
//loop through $user_answers 
//try to save each
//delete answers that are not provided
function saveAnswers($db, $username, $user_answers)
{
    $result = 0;
    if(!$db || !$username) return $result;

    $status = $db->delete(array("table" => "user_answer", "username" => $username));
    if($status) $result += $status;
    foreach ($user_answers as $answer)
    {
        $answer["username"] = $username;
        $answer["table"] = "user_answer";
        $status = $db->saveRow($answer);
        if($status) $result++;
    }
    return $result;
}

//process uploaded file
//store in destination
function processUpload($file, $username=null)
{
    if(!$username)
        $username = fpCurrentUsername();

    $tmpFile = $file["tmp_name"];
    $mimeType = $file["type"];
    $filename = utf8_decode($file["name"]);
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

    //move file to destination dir
    $dataRoot = getConfig("upload._diskPath");
    $dataRootUrl = getConfig("upload.baseUrl");

    createDir($dataRoot, $username);
    $uploadDir  = combine($dataRoot, $username);
    $uploadedFile = combine($dataRoot, $username, $filename);
    $filesize = filesize($tmpFile);
    $success = move_uploaded_file($tmpFile, $uploadedFile);
    debug("move to $uploadedFile", $success);
    if(!$success)
        return errorMessage("Cannot move file into target dir.");

    return processImage($uploadDir, $filename);
}

//process image in data folder: extract metadata, resize
function processImage($uploadDir, $filename)
{
    $uploadedFile = combine($uploadDir, $filename);
    //save exif data
    $message =  "File uploaded.";
    $exif = getImageMetadata($uploadedFile);
    $dateTaken = getExifDateTaken($uploadedFile, $exif);
    if(!$dateTaken)     $dateTaken = getIptcDate($exif);
    if(!$dateTaken)   $dateTaken = getFileDate($uploadedFile);
    $exif["dateTaken"]  = $dateTaken;

    $description = arrayGetCoalesce($exif, "ImageDescription", "IPTC.Caption");
    $description = trim($description);

    writeCsvFile("$uploadedFile.txt", $exif);
    writeTextFile("$uploadedFile.js", jsValue($exif));

    //resize images and keep hd version
    $sizes = getConfig("thumbnails.sizes");
    $resized = resizeMultiple($uploadDir, $filename, $sizes);
    $keep = getConfig("thumbnails.keep");
    if($keep)
    {
        moveFile("$uploadDir/.$keep", $filename, $uploadDir);
        deleteDir("$uploadDir/.$keep");
        unset($resized[$keep]);
    }
    if($dateTaken)
        setFileDate($uploadedFile, $dateTaken);

    $vars = get_defined_vars();
    $result = array();
    $exif["meal"] = selectMeal($dateTaken);
    $result["_exif"] = $exif;
    $result["success"] = true;
    return addVarsToArray($result, "filename filesize mimeType dateTaken description", $vars);
}


$dataMap = array("FileName" => "filename", 
         "ExifImageWidth"   => "image_width", 
         "ExifImageLength"  => "image_height",
         "dateTaken"        => "image_date_taken",
         "ImageDescription" => "caption", 
         "IPTC.Caption"     => "caption", 
         "meal"             => "meal",
         "course"           => "course",
         "mood"             => "mood");

function saveUploadData($db, $metadata)
{   
    global $dataMap, $fpConfig;

    $dbConnected = ($db != NULL);
    if(!$dbConnected)
        $db = new SqlManager($fpConfig);

    if($db->offline) return -1;

    //TODO: use remap between exif data and db row?
    //step 1: insert record based on image EXIF metadata
    if(!isset($metadata["upload_id"]))
    {
        $data = arrayRemap($metadata, $dataMap);
//        $data["meal"] = selectMeal($data["image_date_taken"]);
    }
    else //step 2: update record based on form data
        $data = $metadata;

    $data["username"] = fpCurrentUsername();
    $data["table"] = "user_upload";
    $result = $db->saveRow($data);

    if(!$dbConnected)
        $db->disconnect();

    return $result;
}

//select meal based on photo time
function selectMeal($date)
{
    if(!$date) return;
    $hour = substringBetween($date, " ", ":");
    $mealId = 0;
    $list = getConfig("dropdown.meal");
    foreach($list as $mealId => $meal)
        if(!isset($meal["start"]) || $hour >= $meal["start"] && $hour < $meal["end"]) 
            break;
    return $list[$mealId]["name"];
};

//uploaded
function getImagePath($u)
{
    $basePath = getConfig("upload._diskPath");
    return combine($basePath, $u["username"], $u["filename"]);
}

function getImageUrl($u)
{
    $basePath = getConfig("upload.baseUrl");
    return combine($basePath, $u["username"], $u["filename"]);
}

function uploadedFileExists($u)
{
    $imagePath = getImagePath($u);
    return file_exists($imagePath);
}


// Extract metadata from uploaded image
function getImageMetadata($filename)
{
debug("getImageMetadata", $filename);
    $exif = getExifData($filename); //, true, true);
debug("getImageMetadata getExifData", $exif);
  
//    $exif['format'] = getImageSizeInfo($filename);
    $size = getimagesize($filename, $iptc);

    if(!$iptc) return $exif;

    $exif['IPTC'] = parseIptcTags($iptc);
    return $exif;
}

/*

IPTC.APP13.1#090;%G
IPTC.APP13.2#005;BAK20100907_3124
IPTC.APP13.2#025;Brian Knapp
IPTC.APP13.2#055;20100907
IPTC.APP13.2#060;144209
IPTC.APP13.2#120;Mid-morning snack: chocolate chips

*/

function parseIptcTags($iptcInfo)
{
    $iptcHeaderArray = getConfig("_IPTC.headers");
    $tags = array();
    foreach ($iptcInfo as $key => $value)
    { 
        if(!$value) continue;
        $iptc = iptcparse($value);
debug("IPTC $key", $iptc);        
        if(!$iptc || !is_array($iptc)) continue;
        foreach ($iptc as $key2 => $arr)
        {
debug("IPTC $key.$key2", $arr);            
            if(!array_key_exists($key2, $iptcHeaderArray)) continue;
            $tk = $iptcHeaderArray[$key2];
            $tags[$tk] = arraySingleToScalar($arr);
        }
    }

    return $tags;
}


function getIptcDate($exif)
{
    if(!$date = arrayGet($exif, "IPTC.CreationDate")) 
        return "";

    $time = arrayGet($exif, "IPTC.CreationTime");
    $time = substringBefore($time, "-");
    $date .= " $time";
//20100907 232516 => 2010-09-07 23:25:16-0700
    $date = strInsert($date, "-", 4);
    $date = strInsert($date, "-", 7);
    $date = strInsert($date, ":", 13);
    $date = strInsert($date, ":", 16);
    return $date;
}


?>