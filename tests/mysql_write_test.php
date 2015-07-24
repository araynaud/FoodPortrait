<?php
require_once("../include/includes.php");

debug("phpversion", phpversion());

$fpConfig = readConfigFile("../fp.config");
$db = new SqlManager($fpConfig);
$table = reqParam("table", "form_question");
getTimer(true);

$serial=substr(time(), 8);
$where = array("table"=>"products", "id" => 33);
$values = array("product_name"=>'Panxhouette', "product_code" => "UPC$serial");
$updated = $db->update($values, $where);
debugVar("updated", $updated);
getTimer(true);

$id = $db->getIdValue("products");
getTimer(true);
debugVar("id");

$record = array("table"=>"products", "product_name"=> "Inxhouerte $serial", "product_code" => "PC$serial", "price" => 129.95);
$inserted = $db->insert($record);
debugVar("inserted");
getTimer(true);

//$where = array("table"=>"products", "id" => $inserted);
//$deleted = $db->delete($where);
//debugVar("deleted");
//getTimer(true);

$id = $db->getIdValue("products");
getTimer(true);
debugVar("id");

$db->disconnect();
getTimer(true);
?>