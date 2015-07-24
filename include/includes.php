<?php
$APP_DIR = ".";
if(!file_exists("$APP_DIR/fp.config"))
	$APP_DIR = "..";

$MT_DIR = "$APP_DIR/../mt";
if(!file_exists("$MT_DIR/include/http_functions.php"))
	$MT_DIR = "$APP_DIR/../../MediaThingy";

require_once("$MT_DIR/include/http_functions.php");
require_once("$MT_DIR/include/text_functions.php");
require_once("$MT_DIR/include/debug_functions.php");
require_once("$MT_DIR/include/path_functions.php");
require_once("$MT_DIR/include/dir_functions.php");
require_once("$MT_DIR/include/array_functions.php");
require_once("$MT_DIR/include/config_functions.php");
require_once("$MT_DIR/include/file_functions.php");
require_once("$MT_DIR/include/ui_functions.php");
require_once("$MT_DIR/include/json_xml_functions.php");

require_once("$APP_DIR/include/fp_functions.php");
require_once("$APP_DIR/include/SqlManager.php");
require_once("$APP_DIR/include/data_html_functions.php");

if(isDebugMode())
	header("Content-Type: text/plain");
startTimer();

$config = $fpConfig = readConfigFile("$APP_DIR/fp.config");
?>