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


function loadUsers($filename)
{
    if(!$filename) return;
    $data = readCsvTableFile($filename, false, true);
    return $data;
}

// === USER IMPORT FUNCTIONS
function importUsers($users, $questions)
{
    $rows = array();
    foreach ($users as $user)
    {
        $username = arrayExtract($user, "username");
        if(!$username) continue;
        $username = str_replace(" ", "", $username);
        if(!$username) continue;

    //1 insert into user
        $userRow = array();
        $userRow["table"] = "user";
        $userRow["username"] = $username;
        $userRow["password"] = md5($username);
        $rows[] = $userRow;

    //2 insert into user_answer 1 row per user column
        foreach ($user as $column => $userValues)
        {
            if(!isset($questions[$column]) || !$userValues) continue;

            $q = $questions[$column];
//TODO: if question type == multiple,  $userValue is array: insert several rows
            $userValues = toArray($userValues, ";");
            foreach ($userValues as $key => $userValue)
            {
                $row = array();
                $row["table"] = "user_answer";
                $row["username"] = $username;
//              $row["field"] = $column;
//              $row["value"] = $userValue;
                $row["question_id"] = $q["id"];
                $answer = findAnswer($q, $userValue);
                if($answer)
                {
                    $row["answer_id"] = $answer["id"];
//                  $row["answer"] = $answer;

                    if(@$answer["value"])
                        $row["answer_value"] = $answer["value"];
                    else if(@$answer["data_type"] == "number")
                        $row["answer_value"] = $userValue;
                    else if(@$answer["data_type"] == "text")
                        $row["answer_text"] = $userValue;
                }
                else if(@$q["data_type"] == "number")
                    $row["answer_value"] = $userValue;
                else if(@$q["data_type"] == "text")
                    $row["answer_text"] = $userValue;

                if(@$row["answer_id"] || @$row["answer_text"] || @$row["answer_value"])
                    $rows[] = $row;
            }
        }
    }
    return $rows;
}

//TODO: Find answer id, match by  partial text;
function findAnswer($question, $userValue)
{
    if(!isset($question["form_answers"])) return null;

    $ans = null;
    foreach ($question["form_answers"] as $ans)
        if(matchStrings($userValue, $ans["label"]))
            return $ans;
    if(@$ans["data_type"])  return $ans;
    return null;
}

//match first word ?
function matchStrings($userValue, $label)
{
//  debug("matchStrings $label", $userValue);

    if(!strcasecmp($userValue, $label)) return true;
    
    $firstWord = substringBefore($label, " ");
    return startsWith($userValue, $firstWord);
}


//loop all images in a dir
//find files that are not in database => insert into user_uploads
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

// === End USER IMPORT FUNCTIONS

?>