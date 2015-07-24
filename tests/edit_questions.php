<?php
require_once("../include/includes.php");

$fpConfig = readConfigFile("../fp.config");
$db = new SqlManager($fpConfig);
debugVar("mysqlConfig");

$sql = "SELECT username, CONCAT(first_name, ' ', last_name) name, email, birth_date FROM user"; // where username is null";
$sql = "SELECT  * FROM form_question"; // where username is null";
$rows = $db->select($sql);
echo formatMs(getTimer());
?>
HTML Table<br/>
<?php echo rowsToHtmlTable($rows); ?>
<br/>
JSON:
<pre>
<?php echo json_encode($rows, JSON_PRETTY_PRINT); 
$db->disconnect();
echo formatMs(getTimer());
?> 
</pre>
