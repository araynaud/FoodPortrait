<?php
require_once("../include/includes.php");
setContentType("text","plain");
session_start();
echo jsValue($fpConfig, true);
?>