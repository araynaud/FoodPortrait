<?php
require_once("../include/includes.php");
setContentType("text","plain");
session_start();

//get query parameters from request
//query database: upload, user and user_answer tables
//return list of uploads with image urls.
$params = getJsonPostData();
debugVar("params");
if(!$params) $params = $_REQUEST;

$username = fpCurrentUsername();

$db = new SqlManager($fpConfig);
if($db->offline)
{
	echo file_get_contents("query.json");
	return;
}

$portraitType = arrayExtract($params, "portrait");
$order = arrayExtract($params, "order");
$groupBy = arrayExtract($params, "group");
$limit = reqParam("limit", 20);

if(!$groupBy)
{
	$results = demographicPortrait($db, $params, $portraitType);
	$users = arrayDistinct($results, "username");
	setExists($results);
	$results = array("all" => $results);
}
else
{
	splitFilters($params, $imageFilters, $demoFilters);
	$users = $distinctGroups = getDistinctGroups($db, $imageFilters, $groupBy);
	$results = array();
	foreach ($distinctGroups as $value) 
	{
		if($value === "NULL") continue;
		$params[$groupBy] = $value;
		$results[$value] = demographicPortrait($db, $params, $portraitType);
		setExists($results[$value]);
	}
}

$queries = $db->getLog();
$db->disconnect();

$response=array();
$response["time"] = getTimer();
addVarsToArray($response, "params queries users results");
echo jsValue($response, true);
getTimer();
?>