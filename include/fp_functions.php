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
        $status = $db->saveRow($answer); //, "question_id, answer_id");
        if($status) $result++;
    }
    return $result;
}
?>