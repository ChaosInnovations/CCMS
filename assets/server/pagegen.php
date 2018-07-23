<?php

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
	$title = getconfig("defaulttitle");
	$head = getconfig("defaulthead");
	$body = getconfig("defaultbody");
	$stmt = $conn->prepare("INSERT INTO content_pages (pageid, title, head, body, revision, secure) VALUES (:pageid, :title, :head, :body, :now, :secure);");
	$stmt->bindParam(":pageid", $npid);
	$stmt->bindParam(":title", $title);
	$stmt->bindParam(":head", $head);
	$stmt->bindParam(":body", $body);
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
	if (in_array($pid, ["home", "notfound", "secureaccess"])) {
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
	if (in_array($pid, ["home", "notfound", "secureaccess"])) {
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
	    in_array($_POST["pageid"], ["home", "notfound", "secureaccess"])) {
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
		!isset($_POST["title"]) ||
		!isset($_POST["head"]) ||
		!isset($_POST["body"])) {
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
	
	if (!($newpageid == $page->pageid || !in_array($page->pageid, ["home", "notfound", "secureaccess"]))) {
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
	$stmt = $conn->prepare("UPDATE content_pages SET pageid=:pageid, title=:title, head=:head, body=:body, revision=:now WHERE pageid=:oldpid;");
	$stmt->bindParam(":pageid", $newpageid);
	$stmt->bindParam(":oldpid", $page->pageid);
	$stmt->bindParam(":title", $_POST["title"]);
	$stmt->bindParam(":head", $_POST["head"]);
	$stmt->bindParam(":body", $_POST["body"]);
	$stmt->bindParam(":now", $now);
	$stmt->execute();
	
	if ($newpageid == $page->pageid) {
		return "TRUE";
	}
	
	return $newpageid;
}

class Page {
	
	public $pageid = "";
	public $queryerr = "";
	public $rawtitle = "Empty%20Page";
	public $title = "Empty Page";
	public $rawhead = "";
	public $head = "";
	public $rawbody = "%3Ch1%3EEmpty%20Page!%3C%2Fh1%3E";
	public $body = "<h1>Empty Page!</h1>";
	public $rawnavmod = "";
	public $navmod = "";
	public $nav = "";
	public $rawfootmod = "";
	public $footmod = "";
	public $foot = "";
	public $secure = false;
	public $revision = "";
	
	function __construct($pid=null) {
		global $queryerr;
		global $conn, $sqlstat, $sqlerr;
		if ($pid != null) {
			$pageid = $pid;
		} else {
			global $pageid;
		}
		$this->pageid = $pageid;
		$this->queryerr = $queryerr;
		
		if ($sqlstat) {
			$stmt = $conn->prepare("SELECT * FROM content_pages WHERE pageid=:pid;");
			$stmt->bindParam(":pid", $pageid);
			$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
			$pdatas = $stmt->fetchAll();
			if (count($pdatas) == 1) {
				$pdata = $pdatas[0];
				$this->rawtitle = $pdata["title"];
				$this->rawhead = $pdata["head"];
				$this->rawbody = $pdata["body"];
				$this->rawnavmod = $pdata["navmod"];
				$this->rawfootmod = $pdata["footmod"];
				$this->title = urldecode($this->rawtitle);
				$this->head = urldecode($this->rawhead);
				$this->body = urldecode($this->rawbody);
				$this->navmod = urldecode($this->rawnavmod);
				$this->footmod = urldecode($this->rawfootmod);
				$this->secure = $pdata["secure"];
				$this->revision = date("l, F j, Y", strtotime($pdata["revision"]));
			} else {					
				$this->title = $pageid;
				$this->body = "<div class='container-fluid'><div class='row'><div class='col-xs-12'><h1>Something got messy on our server</h1></div></div></div>";
			}
		} else {
			$this->title = $pageid;
			$this->body = "<div class='container-fluid'><div class='row'><div class='col-xs-12'><h1>Something got messy on our server</h1></div></div></div>";
		}
	}
	
	function getHeader() {
		global $authuser, $modules;
		global $conn, $sqlstat, $sqlerr;
		global $ccms_info;
		global $TEMPLATES;
		global $availablemodules, $modules;
		
		$securepages = [];
		if ($sqlstat) {
			$stmt = $conn->prepare("SELECT pageid FROM content_pages WHERE secure=1;");
			$stmt->execute();
			$stmt->setFetchMode(PDO::FETCH_ASSOC);
			$ps = $stmt->fetchAll();
			foreach ($ps as $p) {
				array_push($securepages, $p["pageid"]);
			}
		}
	
		$secure = "";
		$modals = "<div id=\"modals\">";
		$script = "<script>";

		if ($authuser->permissions->toolbar) {
			
			$secure .= $TEMPLATES["secure-navbar-start"];
			
			if ((($this->secure and $authuser->permissions->page_editsecure) or (!$this->secure and $authuser->permissions->page_edit)) and !in_array($this->pageid, $authuser->permissions->page_editblacklist)) {
				$modals .= $TEMPLATES["secure-modal-start"]("dialog_edit", "Edit Page", "lg");
				$modals .= $TEMPLATES["secure-modal-edit-bodyfoot"];
				$modals .= $TEMPLATES["secure-modal-end"];
				$script .= $TEMPLATES["secure-modal-edit-script"]($this->pageid, $this->rawtitle, $this->rawhead, $this->rawbody);
			}
			if ($authuser->permissions->admin_managepages) {
				$secure .= $TEMPLATES["secure-navbar-dropdown-admin-start"];
				$stmt = $conn->prepare("SELECT pageid, title, secure, revision FROM content_pages ORDER BY pageid ASC;");
				$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
				$pages = $stmt->fetchAll();
				$stmt = $conn->prepare("SELECT * FROM users;");
				$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
				$users = $stmt->fetchAll();
				$modals .= $TEMPLATES["secure-modal-start"]("dialog_admin", "Administration", "lg");
				$modals .= $TEMPLATES["secure-modal-admin-bodyfoot"]($authuser, $pages, $users);
				$script .= $TEMPLATES["secure-modal-admin-script"]();
				$modals .= $TEMPLATES["secure-modal-end"];
				if ($authuser->permissions->owner) {
					$secure .= $TEMPLATES["secure-navbar-dropdown-admin-button-users"];
					$modals .= $TEMPLATES["secure-modal-start"]("dialog_manageusers", "User Accounts", "lg");
					$modals .= $TEMPLATES["secure-modal-manageusers-bodyfoot"]($users, $authuser->uid);
					$modals .= $TEMPLATES["secure-modal-end"];
				}
				$secure .= $TEMPLATES["navbar-dropdown-end"];
			}
			$secure .= $TEMPLATES["secure-navbar-nav-end"];
			$secure .= $TEMPLATES["secure-navbar-end"];
			$modals .= $TEMPLATES["secure-modal-start"]("dialog_account", "Account Details", "lg");
			$modals .= $TEMPLATES["secure-modal-account-bodyfoot"]($authuser);
			$modals .= $TEMPLATES["secure-modal-end"];
			$script .= $TEMPLATES["secure-modal-account-script"];
			foreach ($availablemodules as $m) {
				$mc = $modules[$m];
				if (method_exists($mc, "getModal")) {
					$modals .= $TEMPLATES["secure-modal-start"]("dialog_module_".$m, $mc->name, "lg");
					$modals .= $mc->getModal();
					$modals .= $TEMPLATES["secure-modal-end"];
				}
				if (method_exists($mc, "getScript")) {
					$script .= $mc->getScript();
				}
			}
		}
		
		$modals .= "</div>";
		$script .= "</script>";
		
		$newSecure = "";
		
		if ($authuser->permissions->toolbar) {
			$newSecure .= $TEMPLATES["secure-menu"]($authuser, $securepages, $availablemodules, $modules);
		}
		
		$base = urldecode(getconfig("defaultnav"));
		$header = $secure . $newSecure . $modals . $script . $base;
		return $header;
	}

	function getFooter() {
		$footer = urldecode(getconfig("defaultfoot"));
		return $footer;
	}
	
	function resolvePlaceholders() {
		global $authuser;
		global $availablemodules, $modules;
		
		if ($this->navmod != "") {
			$this->nav = urldecode($this->navmod);
		} else {
			$this->nav = $this->getHeader();
		}
		if ($this->footmod != "") {
			$this->foot = urldecode($this->footmod);
		} else {
			$this->foot = $this->getFooter();
		}
		$this->body = $this->nav . $this->body . $this->foot;
		$placeholders = [];
		preg_match_all("/\{\{.+\}\}/", $this->body, $placeholders);
		foreach ($placeholders[0] as $pcode) {
			if ($pcode != null and $pcode != "") {
				$pcode_trim = trim($pcode, "{}");
				$placeparts = explode(">", $pcode_trim, 2);
				if (count($placeparts) == 2) {
					$mod = $placeparts[0];
					$func = $placeparts[1];
				} else {
					$mod = "builtin";
					$func = $placeparts[0];
				}
				$funcparts = explode(":", $func, 2);
				$func = "place_" . $funcparts[0];
				$args = null;
				if (count($funcparts) == 2) {
					$args = explode(";", $funcparts[1]);
				}
				if (in_array($mod, $availablemodules)) {
					if (method_exists($modules[$mod], $func)) {
						try {
							$content = $modules[$mod]->$func($args);
						} catch (Exception $e) {
							echo $e;
							$content = $modules[$mod]->$func();
						}
					} else {
						$content = "<script>console.warn('No function \'{$func}\' in module \'{$mod}\'!');</script>";
					}
				} else {
					$content = "<script>console.warn('No such module \'{$mod}\'!');</script>";
				}
				$this->body = str_replace($pcode, $content, $this->body);
			}
		}
	}
	
	function insertHead() {
		$sitetitle = getconfig("websitetitle");
		echo "<title>{$this->title} | {$sitetitle}</title>{$this->head}";		
	}
	
	function insertBody() {
		echo $this->body;
	}

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
		$pages = ["home", "notfound", "secureaccess"];
	}
	return !in_array($pageid, $pages);
}

?>