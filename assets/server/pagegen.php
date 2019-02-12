<?php

use \Lib\CCMS\Page;

function ajax_newpage() {
	global $conn, $sqlstat, $authuser;
	if (isset($_POST["s"]) and $_POST["s"] and !$authuser->permissions->page_createsecure) {
		return "FALSE";
	}
	if ((!isset($_POST["s"]) or !$_POST["s"]) and !$authuser->permissions->page_create) {
		return "FALSE";
	}
	if (!$sqlstat) {
		return "FALSE";
	}
	$secure = isset($_POST["s"]) and $_POST["s"];
	$npid = ($secure ? "secure/" : "") . "newpage";
	$c = 0;
	$ok = invalidPage($npid);
	while (!$ok) {
		$c++;
		$npid = ($secure ? "secure/" : "") . "newpage" . $c;
		$ok = invalidPage($npid);
	}
	$s = $secure ? "1" : "0";
	$now = date("Y-m-d");
	
	$stmt = $conn->prepare("SELECT * FROM content_pages WHERE pageid='_default/page';");
	$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
	$template = $stmt->fetchAll()[0];
	
	$stmt = $conn->prepare("INSERT INTO content_pages VALUES (:pageid, :title, :head, :body, :usehead, :usetop, :usebottom, :secure, :now);");
	$stmt->bindParam(":pageid", $npid);
	$stmt->bindParam(":title", $template["title"]);
	$stmt->bindParam(":head", $template["head"]);
	$stmt->bindParam(":body", $template["body"]);
	$stmt->bindParam(":usehead", $template["usehead"]);
	$stmt->bindParam(":usetop", $template["usetop"]);
	$stmt->bindParam(":usebottom", $template["usebottom"]);
	$stmt->bindParam(":now", $now);
	$stmt->bindParam(":secure", $s);
	$stmt->execute();
	return $npid;
}

function ajax_removepage() {
	global $conn, $sqlstat;
	global $authuser;
	if (!$sqlstat) {
		return "FALSE";
	}
	if (!isset($_POST["pid"]) or invalidPage($_POST["pid"])) {
		return "FALSE";
	}
	$pid = $_POST["pid"];
	if (in_array($pid, ["", "secureaccess"]) || substr($pid, 0, 9) === "_default/") {
		return "SPECIAL";
	}
	if (!$authuser->permissions->admin_managepages) {
		return "FALSE";
	}
	$stmt = $conn->prepare("DELETE FROM content_pages WHERE pageid=:pid;");
	$stmt->bindParam(":pid", $pid);
	$stmt->execute();
	return "TRUE";
}

function ajax_securepage() {
	global $conn, $sqlstat;
	global $authuser;
	if (!$sqlstat) {
		return "FALSE";
	}
	if (!isset($_POST["state"])) {
		return "FALSE";
	}
	if (!isset($_POST["pid"]) or invalidPage($_POST["pid"])) {
		return "FALSE";
	}
	$pid = $_POST["pid"];
	if (in_array($pid, ["", "secureaccess"]) || substr($pid, 0, 9) === "_default/") {
		return "SPECIAL";
	}
	if (!$authuser->permissions->admin_managepages) {
		return "FALSE";
	}
	$s = $_POST["state"] == "true" ? "1" : "0";
	$now = date("Y-m-d");
	$stmt = $conn->prepare("UPDATE content_pages SET secure=:secure, revision=:now WHERE pageid=:pid;");
	$stmt->bindParam(":secure", $s);
	$stmt->bindParam(":now", $now);
	$stmt->bindParam(":pid", $pid);
	$stmt->execute();
	return "TRUE";
}

function ajax_checkpid() {
	if (!isset($_POST["pageid"]) ||
	    !isset($_POST["check"])) {
		return "FALSE";
	}
	if ($_POST["check"] != $_POST["pageid"] &&
	    !invalidPage($_POST["check"])) {
		return "FALSE";
	}
	if ($_POST["check"] != $_POST["pageid"] &&
	    in_array($_POST["pageid"], ["", "secureaccess"]) || substr($_POST["pageid"], 0, 9) === "_default/") {
		return "FALSE";
	}
	
	return "TRUE";
}

function ajax_editpage() {
	global $conn, $sqlstat, $sqlerr;
	global $authuser;
	
	if (!isset($_POST["pageid"]) ||
	    !isset($_POST["newpageid"]) ||
		!isset($_POST["title"]) ||
		!isset($_POST["usehead"]) ||
		!isset($_POST["head"]) ||
		!isset($_POST["usetop"]) ||
		!isset($_POST["body"]) ||
		!isset($_POST["usebottom"])) {
		return "FALSE";
	}
	
	if (invalidPage($_POST["pageid"])) {
		return "FALSE";
	}
	
	$page = new Page($_POST["pageid"]);
	
	if (!$authuser->permissions->page_edit ||
	    ($page->secure && !$authuser->permissions->page_editsecure) ||
		in_array($page->pageid, $authuser->permissions->page_viewblacklist) ||
		in_array($page->pageid, $authuser->permissions->page_editblacklist)) {
		return "FALSE";
	}
	
	$newpageid = $_POST["newpageid"];
	
	if (!($newpageid == $page->pageid || !(in_array($page->pageid, ["", "secureaccess"]) || substr($page->pageid, 0, 9) === "_default/"))) {
		return "FALSE";
	}
	
	if (!(invalidPage($newpageid) || $newpageid == $page->pageid) ||
	    in_array($newpageid, ["TRUE", "FALSE"])) {
		return "FALSE";
	}
	
	if (!$sqlstat) {
		return "FALSE";
	}
	
	$now = date("Y-m-d");
	$stmt = $conn->prepare("UPDATE content_pages SET pageid=:pageid, title=:title, head=:head, body=:body, usehead=:usehead, usetop=:usetop, usebottom=:usebottom, revision=:now WHERE pageid=:oldpid;");
	$stmt->bindParam(":pageid", $newpageid);
	$stmt->bindParam(":oldpid", $page->pageid);
	$stmt->bindParam(":title", $_POST["title"]);
	$stmt->bindParam(":usehead", $_POST["usehead"]);
	$stmt->bindParam(":head", $_POST["head"]);
	$stmt->bindParam(":usetop", $_POST["usetop"]);
	$stmt->bindParam(":body", $_POST["body"]);
	$stmt->bindParam(":usebottom", $_POST["usebottom"]);
	$stmt->bindParam(":now", $now);
	$stmt->execute();
	
	if ($newpageid == $page->pageid) {
		return "TRUE";
	}
	
	return $newpageid;
}

function page_title($pid) {
	global $conn;
	$stmt = $conn->prepare("SELECT title FROM content_pages WHERE pageid=:pid;");
	$stmt->bindParam(":pid", $pid);
	$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
	$pages = $stmt->fetchAll();
	if (count($pages) == 0) {
		return "Not Found";
	}
	return urldecode($pages[0]["title"]);
}

function invalidPage($pid=null) {
	global $conn, $sqlstat, $sqlerr;
	
	if ($pid != null) {
		$pageid = $pid;
	} else {
		global $pageid;
	}
	
	$pages = [];
	
	if ($sqlstat) {
		$stmt = $conn->prepare("SELECT pageid FROM content_pages;");
		$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$ps = $stmt->fetchAll();
		foreach ($ps as $p) {
			array_push($pages, $p["pageid"]);
		}
	} else {
		$pages = ["", "secureaccess"];
	}
	return !(in_array($pageid, $pages) || substr($pageid, 0, 9) === "_default/");
}

?>