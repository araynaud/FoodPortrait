<?php
//filter uploads from selected user
function demographicPortrait($db, $filters, $portraitType)
{
	global $users;
	splitFilters($filters, $imageFilters, $demoFilters);
	$sqlParams = array("table" => "user_upload_search");

	if($portraitType == "demographic")
		$sqlParams["where"] = userFilterCondition($demoFilters);
	else
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

//get username list from profile filters
function filterUsers($db, $filters)
{

	splitFilters($filters, $imageFilters, $demoFilters);
	debug("filterUsers", $filters);

	if(!$filters) return null; //all users

	$query = "SELECT username FROM user";
	$where = userFilterCondition($demoFilters);
	if($where)
		$query .= " WHERE $where";

	$users = $db->select($query, null, true);
	return $users;
}


//convert age to year_born
function ageToYearBorn($db, &$params)
{
	$age = arrayExtract($params, "age");
	if(!$age) return;
	
	$age = explode(":", $age);
	$currentYear = date("Y");
	$years = array();
	foreach ($age as $a) 
		array_unshift($years, $a ? ($currentYear - $a) : $a);

	$question = getFormQuestions($db, array("field_name" => "year_born"));
	if(!count($question)) return;

debug("question", $question, true);
	$qid = $question[0]["id"];
	return $params["Q_$qid"] = implode(":", $years);
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
		else if($value !== "")
			$demoFilters[$questionId] = $value;
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

//where username in (select username from user_answer where question_id = 0 and answer_id = 3)
//and username in (select username from user_answer where question_id = 16 and answer_id = 65)
function userFilterCondition($filters)
{
	debug("userFilterCondition", $filters, "print_r");

	global $questions;
	$and="";
	$query = "";
	foreach ($filters as $questionId => &$answer)
	{
		$range = contains($answer, ":");
		$multiple = contains($answer, ",");
		$qtype = @$questions[$questionId]["data_type"];
		debug("Q $questionId $qtype", $answer, true);

		$col = $range || $multiple ? "answer_value" : "answer_id";
		$subQuery = "select username from user_profile_answer where question_id = $questionId";
		$subQuery .= " and $col ";
		if($range)
		{
            $answer = explode(":", $answer);
			debug("range", $answer);
            if($answer[0] && $answer[1]) sort($answer);
			$min = $answer[0];
			$max = $answer[1];
			if($min !== "" && $max !== "")
				$subQuery .= "BETWEEN $min and $max";
			else if($min !== "")
				$subQuery .= ">= $min";
			else if($max !== "")
				$subQuery .= "<= $max";
		}
        else if($multiple)
        {
        	debug("multiple", $answer);
			$subQuery .= "IN ($answer)";
        }
        else
			$subQuery .= "= $answer";

		debug("subQuery", $subQuery);
		$query .= "$and username in ($subQuery)";
		$and = " AND";
	}

	debug("userFilterCondition", $query);
	return $query;
}

function userFilterCondition0($filters)
{
	global $questions;
	$and="";
	$query = "";
	foreach ($filters as $questionId => &$answerId)
	{
        if(contains($answerId, ":"))
            $answerId = explode(":", $answerId);

		$qtype = @$questions[$questionId]["data_type"];
debug("Q $questionId $qtype", $answerId, true, true);		

		//if not multiple choice : answer_value = $answerId
		$query .= " $and username in (select username from user_answer where question_id = $questionId";

		if($qtype == "number" && is_array($answerId) && count($answerId) == 2) // min <= value <= max
		{
			if($min = $answerId[0])
				$query .= " and answer_value >= $min";
		
			if($max = $answerId[1])
				$query .= " and answer_value <= $max";
		}
		else if($qtype == "number" && is_array($answerId) && count($answerId) == 1) // == value
		{
			if($min = $answerId[0])
				$query .= " and answer_value = $min";
		}
		else if($qtype == "number")
			$query .= " and answer_value = $answerId";
		else if($qtype == "text")
			$query .= " and answer_text = '$answerId'";
		else 
			$query .=" and answer_id = $answerId";

		$query .=  ")";


		$and="AND";
	}
	debug("userFilterCondition", $query);
	return $query;
}
?>
