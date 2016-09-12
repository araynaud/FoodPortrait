<?php
//require_once("../../lib/swiftmailer/lib/swift_required.php");

setContentType("text","plain");

function createEmail($to, $subject, $body, $isHtml)
{
	$email = getConfig("email");

	$message = Swift_Message::newInstance();
	$message->setSubject($subject);

	// Set the From and To address with an associative array
	$from = @$email["from"] ? array($email["username"] => $email["from"]) : $email["username"];
	$message->setFrom($from);
	$message->setTo($to); 	//array('arthur.raynaud@icix.com' => 'Arthur icix'));

	// Give it a body
	$contentType = $isHtml ? 'text/html' : 'text/plain';
	$message->setBody($body, $contentType);

	// And optionally an alternative body
	// Add alternative parts with addPart()
//	$message->addPart('My amazing body in plain text', 'text/plain');

	return $message;
}

function sendEmail($message)
{
	$email = getConfig("email");

	$transport = Swift_SmtpTransport::newInstance($email["host"], $email["port"], $email["protocol"]);
	$transport->setUsername($email["username"]);
	$transport->setPassword($email["password"]);

	// Create the Mailer using your created Transport
	//TODO keep $mailer static, create only once
	$mailer = Swift_Mailer::newInstance($transport);

	// Send the message
	return $mailer->send($message);
}
?>