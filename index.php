<?php

include "assets/server/pagegen.php";
include "assets/server/secure.php";
include "assets/server/mail.php";
include "assets/server/templates.php";

if (isset($_GET["p"])) {
	$pageid = $_GET["p"];
} else {
	$pageid = "home";
}
if (isset($_GET["e"])) {
	$queryerr = $_GET["e"];
} else {
	$queryerr = "";
}

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
// Is the website set up already?
if ($sqlstat) {
	$stmt = $conn->prepare("SHOW TABLES LIKE 'config';");
	$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
	if (count($stmt->fetchAll()) != 1) {
		// No, it's not, so seed it.
		$conn->exec("CREATE TABLE `config` (`websitetitle` text NOT NULL,`primaryemail` text NOT NULL,`secondaryemail` text,`creationdate` date DEFAULT NULL,`defaulthead` mediumtext,`defaulttitle` text,`defaultbody` longtext,`defaultnav` mediumtext,`defaultfoot` mediumtext) ENGINE=InnoDB DEFAULT CHARSET=latin1;");
		$conn->exec("CREATE TABLE `users` (`uid` char(32) NOT NULL,`email` tinytext NOT NULL,`name` tinytext NOT NULL,`registered` date NOT NULL,`permissions` text NOT NULL,`permviewbl` text NOT NULL,`permeditbl` text NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1;ALTER TABLE `users` ADD UNIQUE KEY `uid` (`uid`);");
		$conn->exec("CREATE TABLE `access` (`uid` char(32) NOT NULL,`pwd` char(128) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1;ALTER TABLE `access` ADD UNIQUE KEY `uid` (`uid`);");
		$conn->exec("CREATE TABLE `tokens` (`uid` char(32) NOT NULL,`tid` char(32) NOT NULL,`start` date NOT NULL,`expire` date NOT NULL,`forcekill` tinyint(1) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1;ALTER TABLE `tokens` ADD UNIQUE KEY `tid` (`tid`);");
		$conn->exec("CREATE TABLE `content_pages` (`pageid` varchar(255) NOT NULL,`title` text NOT NULL,`head` longtext NOT NULL,`body` longtext NOT NULL,`navmod` mediumtext NOT NULL,`footmod` mediumtext NOT NULL,`secure` tinyint(1) NOT NULL,`revision` date NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1;ALTER TABLE `content_pages` ADD UNIQUE KEY `pageid` (`pageid`);");
		$conn->exec("INSERT INTO `config` VALUES ('New Website', 'email@example.com', NULL, '" . date("Y-m-d") . "', NULL, NULL, NULL, NULL, NULL);");
		$conn->exec("INSERT INTO `content_pages` VALUES ('home', 'Home%20Page', '', 'Empty', '', '', 0, '2017-01-29'),('notfound', 'Page%20not%20found!', '', 'Page not Found: %7B%7Bqueryerr%7D%7D', '', '', 0, '2017-01-29'),('secureaccess', 'Secure%20Access%20Portal', '', '%7B%7Bloginform%7D%7D', '', '', 0, '2017-01-29');");
	}
}
if ($pageid == "") {
	header("Location: ./");
} else if (invalidPage()) {
	header("Location: ?p=notfound&e={$pageid}");
}
if (isset($_COOKIE["token"]) and validToken($_COOKIE["token"])) {
	$authuser = new AuthUser($_COOKIE["token"]);
} else {
	setcookie("token", "0", 1);
	$authuser = new AuthUser(null);
}
$page = new Page($pageid);
if (($page->secure and !$authuser->permissions->page_viewsecure) or ($authuser->permissions->page_viewsecure and in_array($page->pageid, $authuser->permissions->page_viewblacklist))) {
	header("Location: ?p=secureaccess");
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
				$modclass = "module_{$path}";
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
		<link rel="stylesheet" href="assets/site/bootstrap-3.3.6/css/bootstrap.css">
		<link rel="stylesheet" href="assets/site/css/theme.default.css">
		<script src="assets/site/js/jquery-1.12.4.min.js"></script>
		<script src="assets/site/js/js.cookie.js"></script>
		<script src="assets/site/bootstrap-3.3.6/js/bootstrap.min.js"></script>
		<script src="assets/site/js/site.js"></script>
		<?php
$page->insertHead();
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
	</body>
</html>