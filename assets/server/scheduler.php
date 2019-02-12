<?php
	include "secure.php";
	include "pagegen.php";
	include "mail.php";
	include "collab.php";
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
		$authuser = new AuthUser(uidFromToken($_POST["token"]));
	} else {
		setcookie("token", "0", 1);
		$authuser = new AuthUser(null);
	}
	$mailer = new Mailer();
	$mailer->host = $mail_config->host;
	$mailer->username = $mail_config->user;
	$mailer->password = $mail_config->pass;
	$mailer->from = $mail_config->from;
	
	if (!$sqlstat) {
		exit();
	}
	
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
	
?>