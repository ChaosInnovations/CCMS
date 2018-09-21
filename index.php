<?php
// Delete setup script and STATE if it still exists (first time launch)
$firstLaunch = false;
if (file_exists("STATE")) {
	$firstLaunch = true;
	unlink("setup.php");
	unlink("STATE");
}

$url = trim($_SERVER["REQUEST_URI"], "/");
if (strstr($url, '?')) $url = substr($url, 0, strpos($url, '?'));

$pageid = $url;
if (isset($_GET["p"])) {
	$pageid = $_GET["p"];
	header("Location: /" . $pageid);
}
if ($pageid == "") {
	$pageid = "home";
}

$https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? "https" : "http";

include "assets/server/pagegen.php";
include "assets/server/secure.php";
include "assets/server/mail.php";
include "assets/server/templates.php";

load_jsons();

$conn = null;
$sqlstat = true;
$sqlerr = "";
$msgs = [];
try {
	$conn = new PDO("mysql:host=" . $db_config->host . ";dbname=" . $db_config->database, $db_config->user, $db_config->pass);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
	$sqlstat = false;
	$sqlerr = $e;
	array_push($msgs, $e);
}

$mailer = new Mailer();
$mailer->host = $mail_config->host;
$mailer->username = $mail_config->user;
$mailer->password = $mail_config->pass;
$mailer->from = $mail_config->from;

if (isset($_COOKIE["token"]) and validToken($_COOKIE["token"])) {
	$authuser = new AuthUser(uidFromToken($_COOKIE["token"]));
	if ($pageid != "notfound") {
		$stmt = $conn->prepare("UPDATE users SET collab_pageid=:pid WHERE uid=:uid;");
		$stmt->bindParam(":pid", $pageid);
		$stmt->bindParam(":uid", $authuser->uid);
		$stmt->execute();
	}
} else {
	setcookie("token", "0", 1);
	$authuser = new AuthUser(null);
}


if ($pageid == "") {
	header("Location: /");
} else if (invalidPage()) {
	$pageid = "notfound";
}
$page = new Page($pageid);

// Prevent caching
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Force HTTPS if this is a secure page or secureaccess, otherwise force HTTP:
/*
if ($page->secure || $pageid == "secureaccess") {
	$httpsURL = 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	if(!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS']!=='on'){
		if(count($_POST)>0) {
			die('Page should be accessed with HTTPS, but a POST Submission has been sent here. Adjust the form to point to '.$httpsURL);
		}
		header("Status: 301 Moved Permanently");
		header("Location: {$httpsURL}");
		exit();
	}
} else {
	$httpURL = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ){
		header("Status: 301 Moved Permanently");
		header("Location: {$httpURL}");
		exit();
	}
}
*/

if (($page->secure and !$authuser->permissions->page_viewsecure) or ($authuser->permissions->page_viewsecure and in_array($page->pageid, $authuser->permissions->page_viewblacklist))) {
	header("Location: /secureaccess?n={$pageid}");
}
// LOAD MODULES
$modulepath = "assets/server_modules/";
$availablemodules = ["builtin"];
$modules = [];
include ("assets/server/builtin_placeholders.php");
$modules["builtin"] = new builtin_placeholders();
foreach (scandir($modulepath) as $path) {
	if ($path != "." and $path != "..") {
		if (file_exists("{$modulepath}{$path}/module.php")) {
			include("{$modulepath}{$path}/module.php");
			array_push($availablemodules, $path);
			try {
				$modclass = "\\module\\{$path}\\module";
				$modules[$path] = new $modclass();
			} catch (Exception $e) {
				echo $e;
			}
		}
	}
}
foreach ($availablemodules as $m) {
	foreach ($modules[$m]->dependencies as $d) {
		if (!in_array($d, $availablemodules)) {
			array_splice($availablemodules, array_search($m, $availablemodules), 1);
			unset($modules[$m]);
			array_push($msgs, "Missing dependency for module ".$m.": ".$d.".");
			break;
		}
	}
}
$page->resolvePlaceholders();
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
		<link rel="stylesheet" href="/assets/site/css/fontawesome-5.0.13/css/fontawesome-all.min.css" media="all">
		<link rel="stylesheet" href="/assets/site/css/bootstrap-4.1.1/css/bootstrap.min.css" media="all">
		<link rel="stylesheet" href="/assets/site/css/site-1.3.3.css" media="all">
		<link rel="stylesheet" type="text/css" href="/assets/site/js/codemirror/lib/codemirror.css" media="all">
		<script src="/assets/site/js/jquery-3.3.1.min.js"></script>
		<?php
$page->insertHead();
echo "<script>var SERVER_NAME = \"{$_SERVER["SERVER_NAME"]}\", SERVER_HTTPS = \"{$https}\";</script>";
		?>
		<style>
			.navbar .nav li * {
				color: #fff !important;
			}
			.notice-body {
				width: 100%;
				padding-right: 50px;
			}
			.close {
				z-index: 999;
			}
			.monospace {
				font-family: Menlo, Monaco, Consolas, "Courier New", monospace;
			}
		</style>
	</head>
	<body id="page">
		<?php
$page->InsertBody();
		?>
		<script>
			<?php
foreach ($msgs as $m) {
	echo 'console.warn("'.$m.'");';
}
			?>
		</script>
		<script src="/assets/site/js/js.cookie.js"></script>
		<script src="/assets/site/js/popper.min.js"></script>
		<script src="/assets/site/css/bootstrap-4.1.1/js/bootstrap.min.js"></script>
		<script src="/assets/site/js/site-1.1.0.js"></script>
        <script type="text/javascript" src="/assets/site/js/codemirror/lib/codemirror.js"></script>
        <script type="text/javascript" src="/assets/site/js/codemirror/mode/xml/xml.js"></script>
		<script type="text/javascript" src="/assets/site/js/codemirror/mode/html/html.js"></script>
		<script type="text/javascript" src="/assets/site/js/codemirror/mode/css/css.js"></script>
		<script type="text/javascript" src="/assets/site/js/codemirror/mode/javascript/javascript.js"></script>
		<script type="text/javascript" src="/assets/site/js/codemirror/mode/htmlmixed/htmlmixed.js"></script>
	</body>
</html>