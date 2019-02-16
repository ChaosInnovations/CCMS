<?php

use \Lib\CCMS\CCMSCore;

require_once "assets/server/autoload.php";

$core = new CCMSCore();
$request = $core->buildRequest();
$response = $core->processRequest($request);
$response->send();
$core->dispose();

use \Lib\CCMS\Mailer;
use \Lib\CCMS\Page;
use \Lib\CCMS\Security\User;
use \Lib\CCMS\Security\AccountManager;
use \Lib\CCMS\Utilities;

foreach ($argv as $arg) {
    $e=explode("=",$arg);
    if(count($e)==2)
        $_GET[$e[0]]=$e[1];
    else    
        $_GET[$e[0]]=0;
}

// Delete setup script and STATE if it still exists (first time launch)
if (file_exists("STATE") && file_get_contents("STATE") == "5:0" &&
    file_exists("provisioning.json") && file_exists("database.sql")) {
	// Provision
	$provisionData = json_decode(file_get_contents("provisioning.json"));
	// {"db":{"host":null,"user":null,"pass":null,"data":null},"admin":{"name":null,"email":null,"pass":null}};
	$dbConfig = [];
	$dbConfig["host"] = $provisionData->db->host;
	$dbConfig["user"] = $provisionData->db->user;
	$dbConfig["pass"] = $provisionData->db->pass;
	$dbConfig["database"] = $provisionData->db->data;
	file_put_contents("./assets/server/db-config.json", json_encode($dbConfig));
	
	// Write database
	$conn = null;
	try {
		$conn = new PDO("mysql:host=" . $dbConfig["host"] . ";dbname=" . $dbConfig["database"], $dbConfig["user"], $dbConfig["pass"]);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	} catch(PDOException $e) {
		echo("Something went wrong!");
		die();
	}
	
	// Run statements from database.sql
	$dbSrc = file_get_contents("database.sql");
	$stmt = $conn->prepare($dbSrc);
	if (!$stmt->execute()) {
		echo("Something went wrong!");
		die();
	}
	
	// Add Admin account
	$uid = User::uidFromEmail($provisionData->admin->email);
	$pwdHash = password_hash($provisionData->admin->pass, PASSWORD_DEFAULT);
	$stmt = $conn->prepare("INSERT INTO `users` (uid, pwd, email, name, registered, permissions) VALUES (:uid, :pwd, :email, :name, UTC_TIMESTAMP, 'owner;');");
	$stmt->bindParam(":uid", $uid);
	$stmt->bindParam(":pwd", $pwdHash);
	$stmt->bindParam(":email", $provisionData->admin->email);
	$stmt->bindParam(":name", $provisionData->admin->name);
	$stmt->execute();
	
	// Remove setup files
	unlink("setup.php");
	unlink("STATE");
	unlink("provisioning.json");
	unlink("database.sql");
	
	// Reload
	header("Location: /");
	die();
}

$url = trim($_SERVER["REQUEST_URI"], "/");
if (strstr($url, '?')) $url = substr($url, 0, strpos($url, '?'));

$pageid = $url;
if (isset($_GET["p"])) {
	$pageid = $_GET["p"];
	header("Location: /" . $pageid);
    die();
}

ob_end_clean();
ignore_user_abort(true);
ob_start();

$https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? "https" : "http";
$baseUrl = $https . "://" . $_SERVER["SERVER_NAME"];

date_default_timezone_set("UTC");

// Prevent caching
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include "assets/server/pagegen.php";
include "assets/server/secure.php";
include "assets/server/templates.php";
include "assets/server/collab.php";

Utilities::load_jsons();

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
$mailer->host     = Utilities::getconfig("email_primary_host");
$mailer->username = Utilities::getconfig("email_primary_user");
$mailer->password = Utilities::getconfig("email_primary_pass");
$mailer->from     = Utilities::getconfig("email_primary_from");

$notifMailer = new Mailer();
$notifMailer->host     = Utilities::getconfig("email_notifs_host");
$notifMailer->username = Utilities::getconfig("email_notifs_user");
$notifMailer->password = Utilities::getconfig("email_notifs_pass");
$notifMailer->from     = Utilities::getconfig("email_notifs_from");

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

if (isset($_COOKIE["token"]) and AccountManager::validateToken($_COOKIE["token"], $_SERVER["REMOTE_ADDR"])) {
	$authuser = User::userFromToken($_COOKIE["token"]);
    if (Page::pageExists($pageid)) {
        $stmt = $conn->prepare("UPDATE users SET collab_pageid=:pid WHERE uid=:uid;");
        $stmt->bindParam(":pid", $pageid);
        $stmt->bindParam(":uid", $authuser->uid);
        $stmt->execute();
    }
} else {
	setcookie("token", "0", 1);
	$authuser = new User(null);
}

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

if (isset($_GET["run_scheduled_tasks"])) {
    $stmt = $conn->prepare("SELECT * FROM schedule WHERE after <= NOW();");
    $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
    $jobs = $stmt->fetchAll();
    foreach ($jobs as $job) {
        $args = json_decode($job["args"], true);
        if (in_array($job["function"], get_defined_functions()["user"])) {
            $func = $job["function"];
            $func($args);
        } else {
            $funcparts = explode("|", $job["function"], 2);
            if (count($funcparts) == 2) {
                $mod = $funcparts[0];
                $func = $funcparts[1];
            } else {
                $mod = "builtin";
                $func = $funcparts[0];
            }
            if (in_array($mod, $availablemodules)) {
                if (method_exists($modules[$mod], $func)) {
                    $modules[$mod]->$func($args);
                }
            }
        }
        $stmt = $conn->prepare("DELETE FROM schedule WHERE `index`=:idx;");
        $stmt->bindParam(":idx", $job["index"]);
        $stmt->execute();
    }
}

if (substr($pageid, 0, 4) == "api/") {
    
    // Should have modules register endpoints paired with functions.
    // Functions should accept parameters (string $endpoint, array $args)
    // > What about dynamic endpoints?
    //   > endpoint registration could be regex to match
    //   > all registrations matching regex should be called
    // > How to adapt this to work with the scheduler?
    // > How to make this work with page loads?
    
    $old_func = substr($pageid, 4);
    if (in_array("ajax_" . $old_func, get_defined_functions()["user"])) {
        $func = "ajax_" . $old_func;
        echo $func();
    } else {
        $funcparts = explode("|", $old_func, 2);
        if (count($funcparts) == 2) {
            $mod = $funcparts[0];
            $func = "ajax_" . $funcparts[1];
        } else {
            $mod = "builtin";
            $func = "ajax_" . $funcparts[0];
        }
        if (in_array($mod, $availablemodules)) {
            if (method_exists($modules[$mod], $func)) {
                $result = $modules[$mod]->$func();
            } else {
                $result = "FALSE";
            }
        } else {
            $result = "FALSE";
        }
        echo $result;
    }

    $size = ob_get_length();
    header("Content-Length: {$size}");
    ob_end_flush();
    flush();
    
    exit();
}

if (!Page::pageExists($pageid)) {
	$pageid = "_default/notfound";
}
$page = new Page($pageid);

if (($page->secure and !$authuser->permissions->page_viewsecure) or ($authuser->permissions->page_viewsecure and in_array($page->pageid, $authuser->permissions->page_viewblacklist))) {
	header("Location: /secureaccess?n={$pageid}");
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
echo $page->body;
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
<?php

$size = ob_get_length();
header("Content-Length: {$size}");
ob_end_flush();
flush();