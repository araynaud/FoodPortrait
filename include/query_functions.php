<?php
//filter uploads from selected user
function demographicPortrait($db, $filters, $portraitType)
{
	global $users;
	splitFilters($filters, $imageFilters, $demoFilters);
	$sqlParams = array("table" => "user_upload_search");

	if($portraitType == "demographic")
		$sqlParams["where"] = userFilterCondition($demoFilters);
	else // if($demoFilters)
		$sqlParams["username"] = fpCurrentUsername();

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

function getDistinctGroups($db, $params, $groupBy)
{
	$params["table"] = "user_upload";
	$params["columns"] = $groupBy;
	return $db->distinct($params);
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
		//if not multiple choice : answer_value = $answerId
		$query .= " $and username in (select username from user_answer where question_id = $questionId and answer_id = $answerId)";
		$and="AND";
	}
	debug("userFilterCondition", $query);
	return $query;
}
?>
