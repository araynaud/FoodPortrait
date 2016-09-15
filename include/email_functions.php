<?php
function loadSwiftMailer()
{
	global $APP_DIR;
	$swiftmailer_path =  getConfig("lib.swiftmailer");	
	require_once("$APP_DIR/$swiftmailer_path/lib/swift_required.php");
}

function createEmail($to, $subject, $body, $isHtml)
{
	$email_config = getConfig("_email");

	loadSwiftMailer();
	$message = Swift_Message::newInstance();
	$message->setSubject($subject);

	// Set the From and To address with a string or associative array
	//'email@address.com' or array('email@address.com' => 'Your Name'));
	$from = @$email_config["from"] ? array($email_config["username"] => $email_config["from"]) : $email_config["username"];
	$message->setFrom($from);
	$message->setTo($to);

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

function createEmailFromTemplate($templateName, $user, $data=null)
{
	global $APP_DIR;
	$email_config = getConfig("_email");
	if(!$email_config || !$templateName || !$user) return null;

	$templateDir = $email_config["templates"];
	$template = readTextFile("$APP_DIR/$templateDir/$templateName.html");
	if(!$template) return null;

	$name     = is_string($user) ? substringBefore($user, "@") : $user["first_name"] . " " . $user["last_name"];
	$to_email = is_string($user) ? $user : $user["email"];
	if(isset($email_config["to"])) $to_email = $email_config["to"];

	$logo = combine($email_config["baseUrl"], getConfig("app.logo"));

	$cfg = array("site" => getConfig("defaultTitle"), "baseUrl" => $email_config["baseUrl"], "logo" => $logo,
		"name"=> $name, "to" => $to_email);

	$template = replaceVariables($template, $cfg);
	$template = replaceVariables($template, $user);
	$template = replaceVariables($template, $data);

	$subject = substringBefore($template, "\n");
	$body    = substringAfter ($template, "\n");

	//replace classes with inlineStyles
	$styles = readConfigFile("$APP_DIR/$templateDir/inline.css");
	if($styles)
	{	
		$body = "<div class=\"fp-email\">$body</div>";
		$body = inlineStyles($body, $styles);
	}

	debug("createEmail subject", $subject);
	debug("createEmail body", $body);
	return createEmail($to_email, $subject, $body, true);
}

function getResetKey($username)
{
	return md5($username . " " . date("Y-m-d H:i:s"));
}

function evalTemplate($template, $variables)
{
	if(!$template || !is_array($variables)) return $template;

	$data = array();
	foreach ($trans as $key => $value) 
		$data['$' . $key] = $value;
	$text = strtr($template, $data);
	return $text;
}

function replaceVariables($template, $variables)
{
	if(!$template || !is_array($variables)) return $template;

	foreach($variables as $name => $value)
  		$template = str_replace('$'.$name, $value, $template);
	return $template;
}

function inlineStyles($str, $styles)
{
	if(!$str || !is_array($styles)) return $str;

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

function sendEmail($message)
{
	$email_config = getConfig("_email");
	if(!$message || !$email_config) return false;

	loadSwiftMailer();
	$transport = Swift_SmtpTransport::newInstance($email_config["host"], $email_config["port"], $email_config["protocol"]);
	$transport->setUsername($email_config["username"]);
	$transport->setPassword($email_config["password"]);

	// Create the Mailer using your created Transport
	//TODO keep $mailer static, create only once
	$mailer = Swift_Mailer::newInstance($transport);

	// Send the message
	return $mailer->send($message);
}

function sendEmailFromTemplate($templateName, $user, $data=null)
{	
	$message = createEmailFromTemplate($templateName, $user, $data);
	return sendEmail($message);
}
?>