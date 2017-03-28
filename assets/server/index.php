<?php

if (isset($_GET["func"])) {
	
	include "secure.php";
	include "pagegen.php";
	include "mail.php";
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
					$modclass = "module_{$path}";
					$modules[$path] = new $modclass();
				} catch (Exception $e) {
					echo $e;
				}
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
		$authuser = new AuthUser($_POST["token"]);
	} else {
		setcookie("token", "0", 1);
		$authuser = new AuthUser(null);
	}
	$mailer = new Mailer();
	$mailer->host = $mail_config->host;
	$mailer->username = $mail_config->user;
	$mailer->password = $mail_config->pass;
	$mailer->from = $mail_config->from;
	
	if (in_array($_GET["func"], get_defined_functions()["user"])) {
		echo $_GET["func"]();
	} else {
		$funcparts = explode("|", $_GET["func"], 2);
		if (count($funcparts) == 2) {
			$mod = $funcparts[0];
			$func = $funcparts[1];
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

} else {
	header("Location: /");
	echo "<script>window.location = '../../';</script>";
}

?>