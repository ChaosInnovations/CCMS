<?php

use \Lib\CCMS\CCMSCore;

require_once "assets/server/autoload.php";

use \Lib\CCMS\Utilities;
include "assets/server/pagegen.php";
include "assets/server/secure.php";
include "assets/server/templates.php";

$https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? "https" : "http";
$baseUrl = $https . "://" . $_SERVER["SERVER_NAME"];

Utilities::load_jsons(); // Still need this for now for configuration info.
                         // Should move this stuff to .ini files with a new loader class

// LOAD MODULES
$modulepath = "assets/server_modules/";
$availablemodules = ["builtin"];
$modules = [];
include "assets/server/builtin_placeholders.php";
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

$core = new CCMSCore();
$request = $core->buildRequest();
$response = $core->processRequest($request);
$response->send(false);
$core->dispose();

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