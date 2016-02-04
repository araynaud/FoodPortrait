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
		$u["exists"] = uploadedFileExists($u);
}

//filter uploads from selected user
function demographicPortrait($db, $filters)
{
	global $users;
	splitFilters($filters, $imageFilters, $demoFilters);

/*
	$users = filterUsers($db, $demoFilters);
debug("demographicPortrait users", $users);

	if($users === null)
		$users = fpCurrentUsername();
	else if(empty($users))
		return array();
*/
	$sqlParams = array("table" => "user_upload_search", "order_by" => "upload_id desc");

//	if($users !== null)
//		$sqlParams["username"] = $users;
	if($demoFilters)
		$sqlParams["where"] = userFilterCondition($demoFilters);

	//TODO: use date_min and date_max
	$date_min = arrayExtract($imageFilters, "date_min");
	$date_max = arrayExtract($imageFilters, "date_max");

	//searchText: add %%
	$searchText = searchWords(arrayExtract($imageFilters, "searchText"));
	if($searchText)
		$sqlParams["searchText"] = $searchText;	

	foreach ($imageFilters as $key => $value)
		$sqlParams[$key] = $value;	

	$uploads = $db->selectWhere($sqlParams);
	return $uploads;
}


function randomize($db, $size)
{
	$minmax = $db->selectRow("SELECT MIN(upload_id) minid, MAX(upload_id) maxid from user_upload");
debug("min max ids", $minmax);
	$ids = randomArray($size, $minmax["minid"], $minmax["maxid"]);
//	sort($ids);
//	return array_values($ids);

	$sqlParams = array("table" => "user_upload_search", "order_by" => "upload_id desc", "upload_id" => $ids);
	$uploads = $db->selectWhere($sqlParams);
	return $uploads;
}


function randomArray($size, $min, $max)
{
	$arr = array();
	for($i=0; $i < $size;)
	{
		$id = rand ($min, $max);
		if(isset($arr[$id])) continue;
		$arr[$id] = $id;
		$i++;
	}
	return array_values($arr);
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
debug("filterUsers", $filters, "print_r");

	if(!$filters) return null; //all users

	$query = "SELECT username FROM user";
//where username in (select username from user_answer where question_id = 0 and answer_id = 3)
//and username in (select username from user_answer where question_id = 16 and answer_id = 65)

	$where = userFilterCondition($filters);
	if($where)
		$query .= " WHERE $where";

/*	$and = "WHERE";
	foreach ($filters as $questionId => $answerId) 
	{
		$query .= " $and username in (select username from user_answer where question_id = $questionId and answer_id = $answerId)";
		$and="AND";
	}
debug("filterUsers", $query);
*/
	$users = $db->select($query, null, true);
	return $users;
}

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

$mode = getParam("mode");
$limit = reqParam("limit", 20);
if($mode=="random")
	$results = randomize($db, $limit);
else
	$results = demographicPortrait($db, $params);
//$results = array_filter($results, "uploadedFileExists");	
setExists($results);

$queries = $db->getLog();

$db->disconnect();

$response=array();
$response["time"] = getTimer();
debugVar("db");
addVarsToArray($response, "params queries users results");
getTimer();
echo jsValue($response, true);
?>