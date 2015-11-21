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
	foreach ($uploads as &$u)
		$u["exists"] = uploadedFileExists($u);
}

//filter uploads from selected user
function demographicPortrait($db, $filters)
{
	global $users;
	splitFilters($filters, $imageFilters, $demoFilters);
	$users = filterUsers($db, $demoFilters);
debug("demographicPortrait users", $users);

	if($users === null)
		$users = fpCurrentUsername();
	else if(count($users) == 0)
		return array();

	$sqlParams = array("table" => "user_upload_search", "order_by" => "image_date_taken desc");

	//TODO: use date_min and date_max
	$date_min = arrayExtract($imageFilters, "date_min");
	$date_max = arrayExtract($imageFilters, "date_max");

	//searchText: add %%
	$searchText = searchWords(arrayExtract($imageFilters, "searchText"));
	if($searchText)
		$sqlParams["searchText"] = $searchText;	

	foreach ($imageFilters as $key => $value)
		$sqlParams[$key] = $value;	

	$sqlParams["username"] = $users;
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
debug("filterUsers", $filters, "print_r");

	if(!$filters) return null; //all users

	$query = "SELECT username FROM user";
//where username in (select username from user_answer where question_id = 0 and answer_id = 3)
//and username in (select username from user_answer where question_id = 16 and answer_id = 65)

	$and = "WHERE";
	foreach ($filters as $questionId => $answerId) 
	{
		$query .= " $and username in (select username from user_answer where question_id = $questionId and answer_id = $answerId)";
		$and="AND";
	}
debug("filterUsers", $query);

	$users = $db->select($query, null, true);

	return $users;
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

//if profile filters( Q_ ) : demographic
//otherwise: personal
$results = demographicPortrait($db, $params);
//$results = array_filter($results, "uploadedFileExists");	
setExists($results);
$db->disconnect();

$response=array();
addVarToArray($response, "params");
addVarToArray($response, "users");
addVarToArray($response, "results");

echo jsValue($response, true);
?>