<?php

//find user by username of other field
function getUser($db, $username, $field="username")
{
    $params = array("table" => "user", $field => $username);
    $dbUser = $db->selectWhere($params);
    return reset($dbUser);
}

function updateUser($db, $dbUser)
{
    $username = arrayExtract($dbUser, "username");
    $dbUser["table"] = "user";
    $where = array("username" => $username);
    return $db->update($dbUser, $where);
}

function hasProfile($db, $username)
{
    return $db->exists(array("table" => "user_answer", "username" => $username));
}

function getFormQuestions($db, $filters=null)
{
    if(!$db || $db->offline)
    {
        $questions = readJsonFile("../api/form_data.json");
        if(isset($questions["questions"]))
            $questions = $questions["questions"];
        return $questions;
    }


    $p = array("table" => "form_question", "order_by" => "section_id, position, id", "required" => 1);
    if($filters)
        foreach ($filters as $key => &$value) 
        {
            if(contains($value, ","))
                $value = explode(",", $value);
            if($value) 
                $p[$key] = $value;
        }
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

?>