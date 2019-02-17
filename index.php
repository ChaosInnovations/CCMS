<?php

use \Lib\CCMS\CCMSCore;

require_once "assets/server/autoload.php";

foreach ($argv as $arg) {
    $e=explode("=",$arg);
    if(count($e)==2)
        $_GET[$e[0]]=$e[1];
    else    
        $_GET[$e[0]]=0;
}

ob_end_clean();
ignore_user_abort(true);
ob_start();

use \Lib\CCMS\Security\User;
use \Lib\CCMS\Security\AccountManager;
use \Lib\CCMS\Utilities;
include "assets/server/pagegen.php";
include "assets/server/secure.php";
include "assets/server/collab.php";
include "assets/server/templates.php";

$https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? "https" : "http";
$baseUrl = $https . "://" . $_SERVER["SERVER_NAME"];

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

$core = new CCMSCore();
$request = $core->buildRequest();
$response = $core->processRequest($request);
$response->send();
$core->dispose();

use \Lib\CCMS\Mailer;
use \Lib\CCMS\Page;

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

// Prevent caching
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

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

$size = ob_get_length();
header("Content-Length: {$size}");
ob_end_flush();
flush();