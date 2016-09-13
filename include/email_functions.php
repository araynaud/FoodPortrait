<?php
//require_once("../../lib/swiftmailer/lib/swift_required.php");

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
	if(!$isHtml)
		$body = strip_tags($body);
	$message->setBody($body, $contentType);

	// And optionally an alternative body
	// Add alternative parts with addPart()
	if($isHtml)
	{
		$textBody = strip_tags($body);
		debug("createEmail textBody", $textBody);
		$message->addPart($textBody, 'text/plain');
	}
	return $message;
}

function createEmailFromTemplate($templateName, $to)
{
	global $APP_DIR;
	$email = getConfig("email");
	$templateDir = $email["templates"];
	$template = readTextFile("$APP_DIR/$templateDir/$templateName.html");
	if(!$template) return null;

	//$style = readTextFile("$APP_DIR/$templateDir/email.css");
	//if($style)
	//	$style = "<style type=\"text/css\">$style</style>";
	$logo = $email["baseUrl"] . getConfig("app.logo");
	$name = "Panx Houette";
	$reset_key = md5(date("Y-m-d H:i:s"));
	$trans = array("to" => $to, "name" => $name, "logo" => $logo, "site" => getConfig("defaultTitle"),
		"baseUrl" => $email["baseUrl"], "reset_key" => $reset_key);
	$template = evalTemplate($template, $trans);

	$subject = substringBefore($template, "\n");
	$body = substringAfter($template, "\n");

//replace classes with inlineStyles
	$styles = readConfigFile("$APP_DIR/$templateDir/inline.css");
	if($styles)
		$body = "<div class=\"fp-email\">$body</div>";
	$body = inlineStyles($body, $styles);

// head/style element
//	if($style)
//		$body = "$style\n<div class=\"fp-email\">$body</div>";

// body with inline style attribute
//	if($style)
//		$body = "<div style=\"$style\">$body</div>";

	debug("createEmail subject", $subject);
	debug("createEmail body", $body);
	return createEmail($to, $subject, $body, true);
}

function evalTemplate($template, $trans)
{
	$data = array();
	foreach ($trans as $key => $value) 
		$data['$' . $key] = $value;
	$text = strtr($template, $data);
	return $text;
}

function replaceVariables($str, $trans)
{
	if(!$trans) return $str;

	foreach($trans as $name => $value)
  		$str = str_replace('$' . $name, $value, $str);
	return $str;
}

function inlineStyles($str, $styles)
{
	if(!$styles) return $str;

	foreach($styles as $name => $value)
	{
		$style = $value; 
		if(is_array($value))
			$style = implode("; ", $value);
		$style = "style=\"$style\"";
  		$str = str_replace("class=\"$name\"", $style, $str);
	}
	return $str;
}

function getMessageBody($message)
{
	$stream =& $message->build();
	return $stream->readFull();
}

function sendEmail($message)
{
	if(!$message) return false;

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