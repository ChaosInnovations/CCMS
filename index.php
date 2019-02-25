<?php

use \Lib\CCMS\CCMSCore;

require_once "core/autoload.php";

$https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? "https" : "http";
$baseUrl = $https . "://" . $_SERVER["SERVER_NAME"];

$core = new CCMSCore();
$request = $core->buildRequest();
$response = $core->processRequest($request);
$response->send(false);
$core->dispose();

// Force HTTPS
/*
$httpsURL = 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
if(!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS']!=='on'){
	if(count($_POST)>0) {
		die('Page should be accessed with HTTPS, but a POST Submission has been sent here. Adjust the form to point to '.$httpsURL);
	}
	header("Status: 301 Moved Permanently");
	header("Location: {$httpsURL}");
	exit();
}
*/