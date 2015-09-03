<?php
require_once("../include/includes.php");
setContentType("text","plain");
session_start();
//$fpConfig["countries"] = readCsvTableFile("countries.csv", 0, true); // false, true);
echo jsValue($fpConfig, true);
//getTimer(true);
?>