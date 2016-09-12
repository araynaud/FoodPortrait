<?php
require_once("../include/includes.php");
require_once("../../lib/swiftmailer/lib/swift_required.php");
require_once("../include/email_functions.php");

// Create the message
$to      = array('arthur.raynaud@icix.com' => 'Arthur icix');
$subject = "Hello Arthur @ icix";
$body = "My <b>amazing</b> body";
$message = createEmail($to, $subject, $body, true);
echoJsVar("message");

$result  = sendEmail($message);

echoJsVar("result");
?>