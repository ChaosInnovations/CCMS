<?php
if (!isset($_GET["func"])) {
	header("Location: /");
	echo "<script>window.location = '../../';</script>";
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
header("Connection: close");

include "secure.php";
include "pagegen.php";
include "mail.php";
include "templates.php";
include "collab.php";
//include "vendor/autoload.php";

load_jsons();

$conn = null;
$sqlstat = true;
$sqlerr = "";
try {
	$conn = new PDO("mysql:host=" . $db_config->host . ";dbname=" . $db_config->database, $db_config->user, $db_config->pass);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
	$sqlstat = false;
	$sqlerr = $e;
}
if (isset($_POST["token"]) and validToken($_POST["token"])) {
	$authuser = new User(uidFromToken($_POST["token"]));
} else {
	setcookie("token", "0", 1);
	$authuser = new User(null);
}
$mailer = new Mailer();
$mailer->host = getconfig("email_primary_host");
$mailer->username = getconfig("email_primary_user");
$mailer->password = getconfig("email_primary_pass");
$mailer->from = getconfig("email_primary_from");
$notifMailer = new Mailer();
$notifMailer->host = getconfig("email_notifs_host");
$notifMailer->username = getconfig("email_notifs_user");
$notifMailer->password = getconfig("email_notifs_pass");
$notifMailer->from = getconfig("email_notifs_from");

// LOAD MODULES
$modulepath = "../server_modules/";
$availablemodules = ["builtin"];
$modules = [];
include ("builtin_placeholders.php");
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
			break;
		}
	}
}

if (in_array("ajax_" . $_GET["func"], get_defined_functions()["user"])) {
	$func = "ajax_".$_GET["func"];
	echo $func();
} else {
	$funcparts = explode("|", $_GET["func"], 2);
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
?>