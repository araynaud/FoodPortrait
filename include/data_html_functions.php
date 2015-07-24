<?php


function getMaxColumnLenghts($rows, $header=true, $max=0)
{
    $headerRow = reset($rows);
    if($header)
    {
        $colNames = array_keys($headerRow);
        $colNames = array_combine($colNames, $colNames);
        array_unshift($rows, $colNames);
    }
    $lenghts = array();
    foreach ($rows as $row)
    {
        foreach ($row as $key => $value)
        {
            $len = strlen($value);
            if($max && $len > $max) $len = $max;
            if(!isset($lenghts[$key]) || $len > $lenghts[$key])
                $lenghts[$key] = $len;
        }
    }
    return  $lenghts;
}

//truncate or pad to fit exact length
function setLength($str, $length)
{
    $len = strlen($str);
    if($len == $length) return $str;
    if($len < $length)
        return str_pad($str, $length);
    if($length>3)
        return substr($str, 0, $length-3) . "...";
    return substr($str, 0, $length);
}

function rowsToTextTable($rows, $header=true, $delimiter="\t", $max=0)
{
    //empty result
    if (!$rows || !is_array($rows) || !count($rows))
        return "\nNo Results\n";
    //header row
    $lenghts = getMaxColumnLenghts($rows, $header, $max);
    $result = "";
    if($header)
    {
        $sep = "\n";
        $headerRow = reset($rows);
        foreach ($headerRow as $key => $value)
        {
            $result .= $sep . setLength($key, $lenghts[$key]);
            $sep = $delimiter;
        }
    }

    // output data of each row
    foreach ($rows as $row)
    {
        $sep = "\n";
        foreach ($row as $key => $value)
        {
            $result .= $sep . setLength($value, $lenghts[$key]);
            $sep = $delimiter;
        }
    }
    $result .= "\n\n";
    return $result;
}

function rowsToHtmlTable($rows, $header=true)
{
    //empty result
    if (!$rows || !is_array($rows) || !count($rows))
        return "<p>No Results</p>";

    $result = "<table>\n";
    //header row
    if($header)
    {
        $result .= "<tr>";
        $headerRow = reset($rows);
        foreach ($headerRow as $key => $value)
            $result .= "<th>" . makeTitle($key) . "</th>"; 
       $result .= "</tr>\n";
    }

    // output data of each row
    foreach ($rows as $row)
    {
       $result .= "<tr>";
       foreach ($row as $key => $value)
           $result .= "<td>$value</td>"; 
       $result .= "</tr>\n";
    }
    $result .= "</table>\n";
    return $result;
}

//output table of text fields or let angular do the job
function rowsToHtmlTableForm($rows, $header=true)
{
}

function rowsToDropDown($rows, $textColumns=null, $valueColumn=null)
{
    if (!$rows || !is_array($rows) || !count($rows))
        return "No Results";
	
	$columns = array_keys(reset($rows));
	setIfNull($valueColumn, $columns[0]);
	setIfNull($textColumns, $columns[1]);
	if(!is_array($textColumns))
		$textColumns = explode(" ", $textColumns);

    $result = "<select>\n";
    // output data of each row
    foreach ($rows as $row)
    {
		$value = @$row[$valueColumn];
		$result .= $value || $value==0 ? "<option value='$value'>" :  "<option>";
		$sep="";
		$prev="";
		foreach ($textColumns as $key)
		{
		   $sep =   isset($row[$prev]) ? " " : "";
		   $value = isset($row[$key]) ? $row[$key] : $key;
           $result .= $sep . $value . $sep;
		   $prev=$key;
		}
       $result .= "</option>\n";
    }
    $result .= "</select>\n";
    return $result;	
}
?>