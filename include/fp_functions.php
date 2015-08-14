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

function getJsonPostData()
{
    $postdata = file_get_contents("php://input");
    if($postdata)
        $postdata = json_decode($postdata, true);
    return $postdata;
}

function hasProfile($db, $username)
{
    return $db->exists(array("table" => "user_answer", "username" => $username));
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


function saveUploadData($db, $data, $username = NULL)
{
    $data["table"] = "user_upload";
    if(!isset($data["username"]))
        $data["username"] = $username;

    $result = $db->saveRow($data);
    return $result;
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

?>