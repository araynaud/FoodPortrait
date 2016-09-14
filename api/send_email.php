<?php
require_once("../include/includes.php");
require_once("../../lib/swiftmailer/lib/swift_required.php");
require_once("../include/email_functions.php");

//setContentType("text","plain");
$template = getParam("template", "signup");
$send = getParamBoolean("send");
$to = reqParam("to", "arthur_raynaud@hotmail.com");
//$to = "arthur.raynaud@icix.com";
$message = createEmailFromTemplate($template, $to);

$sentDate = date("Y-m-d H:i:s");
echo "Sent: $sentDate<br/>";
$sentDate = md5($sentDate);
echo "MD5: $sentDate<br/>";
echo "To: $to<br/>\n";

echo $message->getSubject() . "\n";
echo $message->getBody();

if($send)
{
	$result = sendEmail($message);
	echoJsVar("result");
}

?>