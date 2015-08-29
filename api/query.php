<?php
require_once("../include/includes.php");

//get query parameters from request
//query database: upload, user and user_answer tables
//return list of uploads with image urls.
setContentType("text","plain");

$postJson = getJsonPostData();

echo jsValue($postJson, true);
//getTimer(true);
?>