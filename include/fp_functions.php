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

function errorMessage($msg)
{
    global $response;
    $response["post"] = $_POST;
    $response["message"] = $msg;
    $response["time"] = getTimer(true);
    die(jsValue($response, true));
}

function setExists(&$uploads)
{
    if(!$uploads) return;
    foreach ($uploads as &$u)
    {
        $u["exists"] = uploadedFileExists($u);
        if(!uploadedFileExists($u, ".ss"))
            $u["noss"] = true;
    }
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
    if(!$dateTaken)   $dateTaken = getDateFromFilename($filename);
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
function getImagePath($u, $subdir="")
{
    $basePath = getConfig("upload._diskPath");
    return combine($basePath, $u["username"], $subdir, $u["filename"]);
}

function getImageUrl($u, $subdir="")
{
    $basePath = getConfig("upload.baseUrl");
    return combine($basePath, $u["username"], $subdir, $u["filename"]);
}

function uploadedFileExists($u, $subdir="")
{
    $imagePath = getImagePath($u, $subdir);
    return file_exists($imagePath);
}
?>