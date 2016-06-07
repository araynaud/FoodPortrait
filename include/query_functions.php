<?php
//filter uploads from selected user
function demographicPortrait($db, $filters, $portraitType)
{
	global $users;
	splitFilters($filters, $imageFilters, $demoFilters);
debug("demographicPortrait", $demoFilters);

	$sqlParams = array("table" => "user_upload_search");

	if($portraitType == "personal")
		$sqlParams["username"] = fpCurrentUsername();
	else
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


$answerColumns = array("single" => "answer_id", "multiple" => "answer_id", "text" => "answer_text", "number" => "answer_value");
function getAnswerColumn($qtype, $interval=0)
{
	global $answerColumns;
	$col = $answerColumns[$qtype];
	if(!$col)	$col = "answer";

	if($qtype == "number" && $interval > 1)
		$col = "($col DIV $interval * $interval) min, ($col DIV $interval * $interval + $interval - 1) max";

	return $col;
}

function getDistinctGroups($db, $params, $groupBy, $interval=0)
{
	$where = "";
	$reverse = @$params["reverse"];
	splitFilters($params, $imageFilters, $demoFilters);
	if(hasDemographicFilters($params))
	{
		$params = $imageFilters;
		$where = userFilterCondition($demoFilters);
	}

	$questionId = getQuestionId($groupBy);
	$qtype = getQuestionType($questionId);
	// group by image filter: meal='Lunch'
	if($questionId === "") 
	{
		$params["table"] = "user_upload";
		$params["columns"] = $groupBy;
	}
	// group by demographic filter: Q_0='2'
	else
	{
		$params = array();
		$params["table"] = "user_profile_answer";
		$params["columns"] = getAnswerColumn($qtype, $interval);
		$params["question_id"] = $questionId;
		$params["reverse"] = $reverse;
		$params["where"] = $where;
	}

debug("getDistinctGroups", $params);
	$result = $db->distinct($params);
	if($qtype == "number" && $interval > 1)
		foreach ($result as $key => $row)
			$result[$key] = $row["min"] . ":" . $row["max"];

	return $result;
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
debug("splitFilters D", $demoFilters, "print_r");
}

function getQuestionId($key)
{
	return substringAfter($key,"Q_");
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

function getQuestionType($questionId)
{
	global $questions;
	return @$questions[$questionId]["data_type"];
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
		$qtype = getQuestionType($questionId);
		if($range) $qtype = "number";
		$col = getAnswerColumn($qtype);
		debug("Q $questionId $qtype $col", $answer);

		$subQuery = "select username from user_profile_answer where question_id = $questionId";
		$subQuery .= " and $col ";
		if($range)
		{
            $answer = explode(":", $answer);
			debug("range", $answer);
			$min = $answer[0];
			$max = $answer[1];
			 if($min && $max)
				sortMinMax($min, $max);

			if($min !== "" && $max !== "")
				$subQuery .= $min == $max ? "= $min" : "BETWEEN $min and $max";
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
        else if($qtype == "text")
			$subQuery .= "= '$answer'";
        else
			$subQuery .= "= $answer";

		debug("subQuery", $subQuery);
		$query .= "$and username in ($subQuery)";
		$and = " AND";
	}

	debug("userFilterCondition", $query);
	return $query;
}
?>
