<?php
require_once("../include/includes.php");

//setContentType("text","plain");
$template = getParam("template", "signup");
$send = getParamBoolean("send");
$to = reqParam("to");
//$to = "arthur.raynaud@icix.com";
$message = createEmailFromTemplate($template, $to);

$email_config = getConfig("email");
echoJsVar("email_config");

$sentDate = date("Y-m-d H:i:s");
echo "Sent: $sentDate<br/>";
$sentDate = md5($sentDate);
echo "MD5: $sentDate<br/>";
echo "To: $to<br/>\n";

if($message)
{
	echo $message->getSubject() . "\n";
	echo $message->getBody();
}

if($send)
{
	$result = sendEmail($message);
	echoJsVar("result");
}

?>