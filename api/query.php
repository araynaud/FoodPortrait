<?php
require_once("../include/includes.php");
setContentType("text","plain");
session_start();

//get query parameters from request
//query database: upload, user and user_answer tables
//return list of uploads with image urls.

//TODO: add sort, limit = columns x rows
//filters = meal, date
function userLatestUploads($db, $username, $filters = null)
{
	$sqlParams = array("table" => "user_upload_search", "order_by" => "image_date_taken desc", "username" => $username);
	if($filters)
		foreach ($filters as $key => $value)
		{
			if(startsWith($key,"Q_")) continue;
			$sqlParams[$key] = $value;	
		}

	$uploads = $db->selectWhere($sqlParams);
	return $uploads;
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

//filter uploads from selected user
function demographicPortrait($db, $filters)
{
	global $users;
	splitFilters($filters, $imageFilters, $demoFilters);
	$sqlParams = array("table" => "user_upload_search");

	if($demoFilters)
		$sqlParams["where"] = userFilterCondition($demoFilters);

	//searchText: add %%
	$searchText = searchWords(arrayExtract($imageFilters, "searchText"));
	if($searchText)
		$sqlParams["searchText"] = $searchText;	

	foreach ($imageFilters as $key => $value)
		$sqlParams[$key] = $value;	

	if(!@$sqlParams["order_by"])
		$sqlParams["order_by"] = "upload_id";

	$uploads = $db->selectWhere($sqlParams);
	return $uploads;
}

//searchText: add %% to every word
function searchWords($text)
{
	$text = trim($text);
	if(!$text) return null;
	
	$words = explode(' ', $text);
	foreach ($words as &$value)
		$value = "%$value%";
	debug("searchWords", $words);
	return $words;
}

function splitFilters($filters, &$imageFilters, &$demoFilters)
{
	$imageFilters = array();
	$demoFilters = array();
	foreach ($filters as $key => $value) 
	{
		$questionId = substringAfter($key,"Q_");
		if($questionId==="")
			$imageFilters[$key] = $value;
		else
			$demoFilters[$questionId] =  $value;
	}

debug("splitFilters I", $imageFilters);
debug("splitFilters D", $demoFilters);
}

function hasDemographicFilters($filters)
{
	foreach ($filters as $key => $answerId) 
	{
		$questionId = substringAfter($key,"Q_");
		if($questionId!=="") return true;
	}
	return false;
}

//TODO: function searchText for every word like

//get username list from profile filters
function filterUsers($db, $filters)
{
debug("filterUsers", $filters);

	if(!$filters) return null; //all users

	$query = "SELECT username FROM user";
	$where = userFilterCondition($filters);
	if($where)
		$query .= " WHERE $where";

	$users = $db->select($query, null, true);
	return $users;
}


//where username in (select username from user_answer where question_id = 0 and answer_id = 3)
//and username in (select username from user_answer where question_id = 16 and answer_id = 65)
function userFilterCondition($filters)
{
	$and="";
	$query = "";
	foreach ($filters as $questionId => $answerId) 
	{
		$query .= " $and username in (select username from user_answer where question_id = $questionId and answer_id = $answerId)";
		$and="AND";
	}
	debug("userFilterCondition", $query);
	return $query;
}



// end functions

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

$order = arrayExtract($params, "order");
$group = arrayExtract($params, "group");
$limit = reqParam("limit", 20);
$results = demographicPortrait($db, $params);
$users = arrayDistinct($results, "username");

setExists($results);

if($group)
	$results = arrayGroupBy($results, $group);

$queries = $db->getLog();
$db->disconnect();

$response=array();
$response["time"] = getTimer();
addVarsToArray($response, "params queries users results");
echo jsValue($response, true);
getTimer();
?>