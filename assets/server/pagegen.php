<?php

if (substr($_SERVER["PHP_SELF"], -strlen("pagegen.php")) == "pagegen.php") {
	global $conn, $sqlstat, $sqlerr;
	
	include "secure.php";
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
	if (isset($_GET["newpage"])) {
		if ((!isset($_POST["s"]) and $authuser->permissions->page_create) or (isset($_POST["s"]) and $authuser->permissions->page_createsecure)) {
			for ($c=0;$c>-1;$c++) {
				if ($c == 0) {
					if (isset($_POST["s"])) {
						$npid = "secure/emptypage";
					} else {
						$npid = "emptypage";
					}
				} else {
					if (isset($_POST["s"])) {
						$npid = "secure/emptypage" . $c;
					} else {
						$npid = "emptypage" . $c;
					}
				}
				if (invalidPage($npid)) {
					break;
				}
			}
			if ($sqlstat) {
				if (isset($_POST["s"])) {
					$secure = "1";
				} else {
					$secure = "0";
				}
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
				$stmt->bindParam(":secure", $secure);
				$stmt->execute();
				echo $npid;
			} else {
				echo "FALSE";
			}
		} else {
			echo "FALSE";
		}
	} else {
		echo "FALSE";
	}
}

function ajax_checkpid() {
	if (isset($_POST["pageid"]) and isset($_POST["check"])) {
		if ($_POST["check"] == $_POST["pageid"] or invalidPage($_POST["check"])) {
			if ($_POST["check"] == $_POST["pageid"] or ($_POST["pageid"] != "home" and $_POST["pageid"] != "notfound" and $_POST["pageid"] != "secureaccess")) {
				echo "TRUE";
			} else {
				echo "FALSE";
			}
		} else {
			echo "FALSE";
		}
	}
}

function ajax_editpage() {
	global $conn, $sqlstat, $sqlerr;
	global $authuser;
	
	if (isset($_POST["pageid"]) and isset($_POST["newpageid"]) and isset($_POST["title"]) and isset($_POST["head"]) and isset($_POST["body"])) {
		if (!invalidPage($_POST["pageid"])) {
			$page = new Page($_POST["pageid"]);
			if ($authuser->permissions->owner or (((!$page->secure and $authuser->permissions->page_edit) or ($page->secure and $authuser->permissions->page_editsecure and !in_array($page->pageid, $authuser->permissions->page_viewblacklist))) and !in_array($page->pageid, $authuser->permissions->page_editblacklist))) {
				$newpageid = $_POST["newpageid"];
				if ($newpageid == $page->pageid or ($page->pageid != "home" and $page->pageid != "notfound" and $page->pageid != "secureaccess")) {
					if ($newpageid == $page->pageid or (invalidPage($newpageid) and $newpageid != "TRUE" and $newpageid !="FALSE")) {
						if ($sqlstat) {
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
							} else {
								return $newpageid;
							}
						} else {
							return "FALSE";
						}
					} else {
						return "FALSE";
					}
				} else {
					return "FALSE";
				}
			} else {
				return "FALSE";
			}
		} else {
			return "FALSE";
		}
	} else {
		return "FALSE";
	}
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

		if ($authuser->permissions->toolbar) {
			
			$secure .= $TEMPLATES["secure-navbar-start"];
			$secure .= $TEMPLATES["secure-navbar-button-home"];
			
			if ($authuser->permissions->owner or (($this->secure and $authuser->permissions->page_editsecure) or (!$this->secure and $authuser->permissions->page_edit)) and !in_array($this->pageid, $authuser->permissions->page_editblacklist)) {
				$secure .= $TEMPLATES["secure-navbar-button-edit"];
				$modals .= $TEMPLATES["secure-modal-start"]("dialog_edit", "Edit Page", "lg");
				$modals .= $TEMPLATES["secure-modal-edit-bodyfoot"];
				$modals .= $TEMPLATES["secure-modal-end"];
				$modals .= $TEMPLATES["secure-modal-edit-script"]($this->pageid, $this->rawtitle, $this->rawhead, $this->rawbody);
			}
			if ($authuser->permissions->page_create) {
				$secure .= $TEMPLATES["secure-navbar-button-create"];
			}
			if ($authuser->permissions->page_viewsecure) {
				$secure .= $TEMPLATES["secure-navbar-dropdown-secure-start"];
				foreach ($securepages as $sp) {
					$spd = new Page($sp);
					if (!in_array($sp, $authuser->permissions->page_viewblacklist)) {
						$secure .= "<li><a href=\"?p={$sp}\">{$spd->title}</a></li>";
					}
				}
				if ($authuser->permissions->page_createsecure) {
					$secure .= $TEMPLATES["navbar-separator"];
					$secure .= $TEMPLATES["secure-navbar-dropdown-secure-button-create"];
				}
				$secure .= $TEMPLATES["navbar-dropdown-end"];
			}
			if ($authuser->permissions->admin_managesite and count($modules) > 1) {
				$secure .= $TEMPLATES["navbar-separator"];
				$secure .= $TEMPLATES["secure-navbar-dropdown-modules-start"];
				$secure .= $TEMPLATES["navbar-dropdown-end"];
			}
			if ($authuser->permissions->admin_managepages) {
				$secure .= $TEMPLATES["secure-navbar-dropdown-admin-start"];
				if ($authuser->permissions->admin_managepages) {
					$secure .= $TEMPLATES["secure-navbar-dropdown-admin-button-pages"];
					$modals .= $TEMPLATES["secure-modal-start"]("dialog_managepages", "Page Manager", "lg");
					$modals .= $TEMPLATES["secure-modal-pages-bodyfoot"];
					$modals .= $TEMPLATES["secure-modal-end"];
				}
				if ($authuser->permissions->admin_managesite) {
					$secure .= $TEMPLATES["secure-navbar-dropdown-admin-button-site"];
					$config_websitetitle = getconfig("websitetitle");
					$config_primaryemail = getconfig("primaryemail");
					$config_secondaryemail = getconfig("secondaryemail");
					$config_defaulttitle = urlencode(getconfig("defaulttitle"));
					$config_defaulthead = urlencode(getconfig("defaulthead"));
					$config_defaultbody = urlencode(getconfig("defaultbody"));
					$config_defaultnav = urlencode(getconfig("defaultnav"));
					$config_defaultfoot = urlencode(getconfig("defaultfoot"));
					$modals .= urldecode("%3Cdiv%20class%3D%22modal%20fade%22%20id%3D%22dialog_managesite%22%20tabindex%3D%22-1%22%20role%3D%22dialog%22%20aria-labelledby%3D%22dialog_managesite_title%22%3E%0A%3Cdiv%20class%3D%22modal-dialog%20modal-lg%22%20role%3D%22document%22%3E%0A%3Cdiv%20class%3D%22modal-content%22%3E%0A%3Cdiv%20class%3D%22modal-header%22%3E%0A%3Cbutton%20type%3D%22button%22%20class%3D%22close%22%20data-dismiss%3D%22modal%22%20aria-label%3D%22Close%22%3E%3Cspan%20aria-hidden%3D%22true%22%3E%26times%3B%3C%2Fspan%3E%3C%2Fbutton%3E%0A%3Ch4%20class%3D%22modal-title%22%20id%3D%22dialog_managesite_title%22%3ESite%20Manager%3C%2Fh4%3E%0A%3C%2Fdiv%3E%0A%3Cdiv%20class%3D%22modal-body%22%3E%0A%3Ch4%3ESite%20Settings%3C%2Fh4%3E%0A%3Cform%20class%3D%22form-horizontal%22%20onsubmit%3D%22dialog_managesite_save()%3Breturn%20false%3B%22%3E%0A%3Cdiv%20class%3D%22form-group%22%3E%0A%3Cdiv%20class%3D%22col-sm-offset-3%20col-md-offset-2%20col-sm-9%20col-md-10%22%3E%0A%3Cinput%20type%3D%22submit%22%20class%3D%22btn%20btn-info%22%20title%3D%22Save%22%20value%3D%22Save%22%3E%0A%3C%2Fdiv%3E%0A%3C%2Fdiv%3E%0A%3Cdiv%20class%3D%22form-group%22%3E%0A%3Clabel%20class%3D%22control-label%20col-sm-3%20col-md-2%22%20for%3D%22dialog_managesite_websitetitle%22%3EWebsite%20Title%3C%2Flabel%3E%0A%3Cdiv%20class%3D%22col-sm-9%20col-md-10%22%3E%0A%3Cinput%20type%3D%22text%22%20id%3D%22dialog_managesite_websitetitle%22%20name%3D%22websitetitle%22%20class%3D%22form-control%22%20title%3D%22Website%20Title%22%20placeholder%3D%22Website%20Title%22%20value%3D%22{$config_websitetitle}%22%3E%0A%3C%2Fdiv%3E%0A%3C%2Fdiv%3E%0A%3Cdiv%20class%3D%22form-group%22%3E%0A%3Clabel%20class%3D%22control-label%20col-sm-3%20col-md-2%22%20for%3D%22dialog_managesite_primaryemail%22%3EPrimary%20Email%3C%2Flabel%3E%0A%3Cdiv%20class%3D%22col-sm-9%20col-md-10%22%3E%0A%3Cinput%20type%3D%22text%22%20id%3D%22dialog_managesite_primaryemail%22%20name%3D%22primaryemail%22%20class%3D%22form-control%22%20title%3D%22Primary%20Email%22%20placeholder%3D%22Primary%20Email%22%20value%3D%22{$config_primaryemail}%22%3E%0A%3C%2Fdiv%3E%0A%3C%2Fdiv%3E%0A%3Cdiv%20class%3D%22form-group%22%3E%0A%3Clabel%20class%3D%22control-label%20col-sm-3%20col-md-2%22%20for%3D%22dialog_managesite_secondaryemail%22%3ESecondary%20Email%3C%2Flabel%3E%0A%3Cdiv%20class%3D%22col-sm-9%20col-md-10%22%3E%0A%3Cinput%20type%3D%22text%22%20id%3D%22dialog_managesite_secondaryemail%22%20name%3D%22secondaryemail%22%20class%3D%22form-control%22%20title%3D%22Secondary%20Email%22%20placeholder%3D%22Secondary%20Email%22%20value%3D%22{$config_secondaryemail}%22%3E%0A%3C%2Fdiv%3E%0A%3C%2Fdiv%3E%0A%3Ch4%3EPage%20Defaults%3C%2Fh4%3E%0A%3Cdiv%20class%3D%22form-group%22%3E%0A%3Clabel%20class%3D%22control-label%20col-sm-3%20col-md-2%22%20for%3D%22dialog_managesite_defaulttitle%22%3EDefault%20Page%20Title%3C%2Fcode%3E%3C%2Flabel%3E%0A%3Cdiv%20class%3D%22col-sm-9%20col-md-10%22%3E%0A%3Cinput%20type%3D%22text%22%20id%3D%22dialog_managesite_defaulttitle%22%20name%3D%22defaulttitle%22%20class%3D%22form-control%22%20title%3D%22Default%20Page%20Title%22%20placeholder%3D%22Default%20Page%20Title%22%20value%3D%3E%0A%3C%2Fdiv%3E%0A%3C%2Fdiv%3E%0A%3Cdiv%20class%3D%22form-group%22%3E%0A%3Clabel%20class%3D%22control-label%20col-sm-3%20col-md-2%22%20for%3D%22dialog_managesite_defaulthead%22%3EDefault%20Page%20%3Ccode%3E%26lt%3Bhead%26gt%3B%3C%2Fcode%3E%3C%2Flabel%3E%0A%3Cdiv%20class%3D%22col-sm-9%20col-md-10%22%3E%0A%3Ctextarea%20id%3D%22dialog_managesite_defaulthead%22%20name%3D%22defaulthead%22%20class%3D%22form-control%20monospace%22%20title%3D%22Default%20Page%20Head%22%20placeholder%3D%22Default%20Page%20Head%22%20rows%3D%228%22%3E%3C%2Ftextarea%3E%0A%3C%2Fdiv%3E%0A%3C%2Fdiv%3E%0A%3Cdiv%20class%3D%22form-group%22%3E%0A%3Clabel%20class%3D%22control-label%20col-sm-3%20col-md-2%22%20for%3D%22dialog_managesite_defaultbody%22%3EDefault%20Page%20%3Ccode%3E%26lt%3Bbody%26gt%3B%3C%2Fcode%3E%3C%2Flabel%3E%0A%3Cdiv%20class%3D%22col-sm-9%20col-md-10%22%3E%0A%3Ctextarea%20id%3D%22dialog_managesite_defaultbody%22%20name%3D%22defaultbody%22%20class%3D%22form-control%20monospace%22%20title%3D%22Default%20Page%20Body%22%20placeholder%3D%22Default%20Page%20Body%22%20rows%3D%2216%22%3E%3C%2Ftextarea%3E%0A%3C%2Fdiv%3E%0A%3C%2Fdiv%3E%0A%3Cdiv%20class%3D%22form-group%22%3E%0A%3Clabel%20class%3D%22control-label%20col-sm-3%20col-md-2%22%20for%3D%22dialog_managesite_defaultnav%22%3EDefault%20Navigation%20Header%3C%2Fcode%3E%3C%2Flabel%3E%0A%3Cdiv%20class%3D%22col-sm-9%20col-md-10%22%3E%0A%3Ctextarea%20id%3D%22dialog_managesite_defaultnav%22%20name%3D%22defaultnav%22%20class%3D%22form-control%20monospace%22%20title%3D%22Default%20Navigation%20Header%22%20placeholder%3D%22Default%20Navigation%20Header%22%20rows%3D%2216%22%3E%3C%2Ftextarea%3E%0A%3C%2Fdiv%3E%0A%3C%2Fdiv%3E%0A%3Cdiv%20class%3D%22form-group%22%3E%0A%3Clabel%20class%3D%22control-label%20col-sm-3%20col-md-2%22%20for%3D%22dialog_managesite_defaultfoot%22%3EDefault%20Footer%3C%2Fcode%3E%3C%2Flabel%3E%0A%3Cdiv%20class%3D%22col-sm-9%20col-md-10%22%3E%0A%3Ctextarea%20id%3D%22dialog_managesite_defaultfoot%22%20name%3D%22defaultfoot%22%20class%3D%22form-control%20monospace%22%20title%3D%22Default%20Footer%22%20placeholder%3D%22Default%20Footer%22%20rows%3D%2216%22%3E%3C%2Ftextarea%3E%0A%3C%2Fdiv%3E%0A%3C%2Fdiv%3E%0A%3Cdiv%20class%3D%22form-group%22%3E%0A%3Cdiv%20class%3D%22col-sm-offset-3%20col-md-offset-2%20col-sm-9%20col-md-10%22%3E%0A%3Cinput%20type%3D%22submit%22%20class%3D%22btn%20btn-info%22%20title%3D%22Save%22%20value%3D%22Save%22%3E%0A%3C%2Fdiv%3E%0A%3C%2Fdiv%3E%0A%3C%2Fform%3E%0A%3C%2Fdiv%3E%0A%3Cdiv%20class%3D%22modal-footer%22%3E%0A%3Cbutton%20type%3D%22button%22%20class%3D%22btn%20btn-default%22%20data-dismiss%3D%22modal%22%3EClose%3C%2Fbutton%3E%0A%3C%2Fdiv%3E%0A%3C%2Fdiv%3E%0A%3C%2Fdiv%3E%0A%3C%2Fdiv%3E");
					$modals .= urldecode("%3Cscript%3Evar%20defaulttitle%20%3D%20decodeURIComponent(%22{$config_defaulttitle}%22)%3Bvar%20defaulthead%20%3D%20decodeURIComponent(%22{$config_defaulthead}%22)%3B%0Avar%20defaultbody%20%3D%20decodeURIComponent(%22{$config_defaultbody}%22)%3Bvar%20defaultnav%20%3D%20decodeURIComponent(%22{$config_defaultnav}%22)%3B%0Avar%20defaultfoot%20%3D%20decodeURIComponent(%22{$config_defaultfoot}%22)%3B%24(%22%23dialog_managesite_defaulttitle%22).val(defaulttitle)%3B%0A%24(%22%23dialog_managesite_defaulthead%22).val(defaulthead)%3B%24(%22%23dialog_managesite_defaultbody%22).val(defaultbody)%3B%0A%24(%22%23dialog_managesite_defaultnav%22).val(defaultnav)%3B%24(%22%23dialog_managesite_defaultfoot%22).val(defaultfoot)%3B%3C%2Fscript%3E");
				}
				if ($authuser->permissions->owner) {
					$secure .= $TEMPLATES["secure-navbar-dropdown-admin-button-users"];
					$permissions = "<p>owner;</p><p></p><p></p>";
					$tools = "<button class=\"btn btn-default\" title=\"Delete Account\" onclick=\"dialog_users_delete('{$authuser->uid}');\"><span class=\"glyphicon glyphicon-trash\"></span></button>";
					$tools .= "<button class=\"btn btn-default\" title=\"Reset Password\" onclick=\"dialog_users_reset('{$authuser->uid}');\"><span class=\"glyphicon glyphicon-refresh\"></span></button>";
					$tools .= "<a class=\"btn btn-default\" href=\"mailto:{$authuser->email}\" title=\"Send Email\"><span class=\"glyphicon glyphicon-envelope\"></span></a>";
					$userlist = "<tr class=\"success\"><td>{$authuser->name}</td><td>{$authuser->email}</td><td>{$permissions}</td><td>{$authuser->registerdate}</td><td>{$tools}</td></tr>";
					$stmt = $conn->prepare("SELECT * FROM users WHERE uid!=:uid;");
					$stmt->bindParam(":uid", $authuser->uid);
					$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
					$users = $stmt->fetchAll();
					foreach ($users as $user) {
						$date = date("l, F j, Y", strtotime($user["registered"]));
						$permissions = "<button class=\"btn btn-default\" type=\"button\" data-toggle=\"collapse\" data-target=\"#dialog_users_{$user["uid"]}_perms\" aria-expanded=\"false\" aria-controls=\"dialog_users_{$user["uid"]}_perms\">Show/Hide</button>";
						$permissions .= "<div class=\"collapse\" id=\"dialog_users_{$user["uid"]}_perms\"><div class=\"well\"><form class=\"form\" onsubmit=\"dialog_users_update('{$user["uid"]}');return false;\">";
						$permissions .= "<div class=\"form-group\"><label class=\"control-label\" for=\"dialog_users_{$user["uid"]}_perms_p\">Permissions</label>";
						$permissions .= "<textarea id=\"dialog_users_{$user["uid"]}_perms_p\" name=\"perm\" class=\"form-control monospace\" title=\"Permissions\" placeholder=\"No Permissions\" rows=\"4\">{$user["permissions"]}</textarea>";
						$permissions .= "</div><div class=\"form-group\"><label class=\"control-label\" for=\"dialog_users_{$user["uid"]}_perms_v\">View Blacklist</label>";
						$permissions .= "<textarea id=\"dialog_users_{$user["uid"]}_perms_v\" name=\"view\" class=\"form-control monospace\" title=\"View Blacklist\" placeholder=\"No Restrictions\" rows=\"4\">{$user["permviewbl"]}</textarea>";
						$permissions .= "</div><div class=\"form-group\"><label class=\"control-label\" for=\"dialog_users_{$user["uid"]}_perms_e\">Edit Blacklist</label>";
						$permissions .= "<textarea id=\"dialog_users_{$user["uid"]}_perms_e\" name=\"edit\" class=\"form-control monospace\" title=\"Edit Blacklist\" placeholder=\"No Restrictions\" rows=\"4\">{$user["permeditbl"]}</textarea>";
						$permissions .= "</div><input type=\"submit\" class=\"btn btn-default\" title=\"Update\" value=\"Update\"></form></div></div>";
						$tools = "<button class=\"btn btn-default\" title=\"Delete Account\" onclick=\"dialog_users_delete('{$user["uid"]}');\"><span class=\"glyphicon glyphicon-trash\"></span></button>";
						$tools .= "<button class=\"btn btn-default\" title=\"Reset Password\" onclick=\"dialog_users_reset('{$user["uid"]}');\"><span class=\"glyphicon glyphicon-refresh\"></span></button>";
						$tools .= "<a class=\"btn btn-default\" href=\"mailto:{$user["email"]}\" title=\"Send Email\"><span class=\"glyphicon glyphicon-envelope\"></span></a>";
						$userlist .= "<tr><td>{$user["name"]}</td><td>{$user["email"]}</td><td>{$permissions}</td><td>{$date}</td><td>{$tools}</td></tr>";
					}
					$modals .= urldecode("%3Cdiv%20class%3D%22modal%20fade%22%20id%3D%22dialog_users%22%20tabindex%3D%22-1%22%20role%3D%22dialog%22%20aria-labelledby%3D%22dialog_users_title%22%3E%0A%3Cdiv%20class%3D%22modal-dialog%20modal-lg%22%20role%3D%22document%22%3E%0A%3Cdiv%20class%3D%22modal-content%22%3E%0A%3Cdiv%20class%3D%22modal-header%22%3E%0A%3Cbutton%20type%3D%22button%22%20class%3D%22close%22%20data-dismiss%3D%22modal%22%20aria-label%3D%22Close%22%3E%3Cspan%20aria-hidden%3D%22true%22%3E%26times%3B%3C%2Fspan%3E%3C%2Fbutton%3E%0A%3Ch4%20class%3D%22modal-title%22%20id%3D%22dialog_users_title%22%3EUser%20Accounts%3C%2Fh4%3E%0A%3C%2Fdiv%3E%0A%3Cdiv%20class%3D%22modal-body%22%3E%0A%3Ch4%3ENew%20User%3C%2Fh4%3E%0A%3Cform%20class%3D%22form-horizontal%22%20role%3D%22edit%22%20onsubmit%3D%22dialog_users_new()%3Breturn%20false%3B%22%3E%0A%3Cdiv%20class%3D%22form-group%22%3E%0A%3Cdiv%20class%3D%22col-md-offset-2%20col-sm-offset-3%20col-md-10%20col-sm-9%22%3E%0A%3Cinput%20type%3D%22submit%22%20class%3D%22btn%20btn-info%22%20title%3D%22Create%22%20value%3D%22Create%22%3E%0A%3Cspan%20class%3D%22dialog_users_formfeedback_added%20hidden%22%3EUser%20Created!%3C%2Fspan%3E%0A%3Cspan%20class%3D%22dialog_users_formfeedback_notadded%20hidden%22%3EThere%20was%20an%20error.%20Check%20your%20connection.%3C%2Fspan%3E%0A%3C%2Fdiv%3E%0A%3C%2Fdiv%3E%0A%3Cdiv%20class%3D%22form-group%20has-feedback%22%3E%0A%3Clabel%20class%3D%22control-label%20col-sm-3%20col-md-2%22%20for%3D%22useremail%22%3EEmail%3A%3C%2Flabel%3E%0A%3Cdiv%20class%3D%22col-sm-9%20col-md-10%22%3E%0A%3Cinput%20type%3D%22text%22%20id%3D%22dialog_users_newemail%22%20name%3D%22email%22%20class%3D%22form-control%22%20title%3D%22Email%22%20placeholder%3D%22Email%22%20oninput%3D%22dialog_users_check_email()%3B%22%3E%0A%3Cspan%20class%3D%22glyphicon%20glyphicon-remove%20form-control-feedback%20hidden%22%3E%3C%2Fspan%3E%0A%3Cspan%20class%3D%22glyphicon%20glyphicon-ok%20form-control-feedback%20hidden%22%3E%3C%2Fspan%3E%0A%3C%2Fdiv%3E%0A%3C%2Fdiv%3E%0A%3Cdiv%20class%3D%22form-group%22%3E%0A%3Clabel%20class%3D%22control-label%20col-sm-3%20col-md-2%22%20for%3D%22username%22%3EName%3A%3C%2Flabel%3E%0A%3Cdiv%20class%3D%22col-sm-9%20col-md-10%22%3E%0A%3Cinput%20type%3D%22text%22%20id%3D%22dialog_users_newname%22%20name%3D%22name%22%20class%3D%22form-control%22%20title%3D%22Name%22%20placeholder%3D%22Name%22%3E%0A%3C%2Fdiv%3E%0A%3C%2Fdiv%3E%0A%3Ch5%3EPermissions%3C%2Fh5%3E%0A%3Cdiv%20class%3D%22form-group%22%3E%0A%3Cdiv%20class%3D%22checkbox%20col-sm-10%20col-sm-offset-1%22%3E%0A%3Clabel%3E%0A%3Cinput%20type%3D%22checkbox%22%20id%3D%22dialog_users_permission_owner%22%20value%3D%22%22%3E%0AOwner%0A%3C%2Flabel%3E%0A%3C%2Fdiv%3E%0A%3Cdiv%20class%3D%22checkbox%20col-sm-10%20col-sm-offset-1%22%3E%0A%3Clabel%3E%0A%3Cinput%20type%3D%22checkbox%22%20id%3D%22dialog_users_permission_admin_managepages%22%20value%3D%22%22%3E%0AManage%20Pages%0A%3C%2Flabel%3E%0A%3Clabel%3E%0A%3Cinput%20type%3D%22checkbox%22%20id%3D%22dialog_users_permission_admin_managesite%22%20value%3D%22%22%3E%0AManage%20Site%0A%3C%2Flabel%3E%0A%3C%2Fdiv%3E%0A%3Cdiv%20class%3D%22checkbox%20col-sm-10%20col-sm-offset-1%22%3E%0A%3Clabel%3E%0A%3Cinput%20type%3D%22checkbox%22%20id%3D%22dialog_users_permission_page_viewsecure%22%20value%3D%22%22%3E%0AView%20Secure%20Pages%0A%3C%2Flabel%3E%0A%3Clabel%3E%0A%3Cinput%20type%3D%22checkbox%22%20id%3D%22dialog_users_permission_page_editsecure%22%20value%3D%22%22%3E%0AEdit%20Secure%20Pages%0A%3C%2Flabel%3E%0A%3Clabel%3E%0A%3Cinput%20type%3D%22checkbox%22%20id%3D%22dialog_users_permission_page_createsecure%22%20value%3D%22%22%3E%0ACreate%20Secure%20Pages%0A%3C%2Flabel%3E%0A%3Clabel%3E%0A%3Cinput%20type%3D%22checkbox%22%20id%3D%22dialog_users_permission_page_deletesecure%22%20value%3D%22%22%3E%0ADelete%20Secure%20Pages%0A%3C%2Flabel%3E%0A%3C%2Fdiv%3E%0A%3Cdiv%20class%3D%22checkbox%20col-sm-10%20col-sm-offset-1%22%3E%0A%3Clabel%3E%0A%3Cinput%20type%3D%22checkbox%22%20id%3D%22dialog_users_permission_page_edit%22%20value%3D%22%22%3E%0AEdit%20Pages%0A%3C%2Flabel%3E%0A%3Clabel%3E%0A%3Cinput%20type%3D%22checkbox%22%20id%3D%22dialog_users_permission_page_create%22%20value%3D%22%22%3E%0ACreate%20Pages%0A%3C%2Flabel%3E%0A%3Clabel%3E%0A%3Cinput%20type%3D%22checkbox%22%20id%3D%22dialog_users_permission_page_delete%22%20value%3D%22%22%3E%0ADelete%20Pages%0A%3C%2Flabel%3E%0A%3C%2Fdiv%3E%0A%3Cdiv%20class%3D%22checkbox%20col-sm-10%20col-sm-offset-1%22%3E%0A%3Clabel%3E%0A%3Cinput%20type%3D%22checkbox%22%20id%3D%22dialog_users_permission_toolbar%22%20value%3D%22%22%3E%0AToolbar%0A%3C%2Flabel%3E%0A%3C%2Fdiv%3E%0A%3C%2Fdiv%3E%0A%3Cdiv%20class%3D%22form-group%22%3E%0A%3Cdiv%20class%3D%22col-md-offset-2%20col-sm-offset-3%20col-md-10%20col-sm-9%22%3E%0A%3Cinput%20type%3D%22submit%22%20class%3D%22btn%20btn-info%22%20title%3D%22Create%22%20value%3D%22Create%22%3E%0A%3Cspan%20class%3D%22dialog_users_formfeedback_added%20hidden%22%3EUser%20Created!%3C%2Fspan%3E%0A%3Cspan%20class%3D%22dialog_users_formfeedback_notadded%20hidden%22%3EThere%20was%20an%20error.%20Check%20your%20connection.%3C%2Fspan%3E%0A%3C%2Fdiv%3E%0A%3C%2Fdiv%3E%0A%3C%2Fform%3E%0A%3Chr%20width%3D%2290%25%22%20%2F%3E%0A%3Ch4%3ECurrent%20Users%3C%2Fh4%3E%0A%3Ctable%20class%3D%22table%20table-striped%22%3E%0A%3Cthead%3E%0A%3Ctr%3E%0A%3Cth%3EName%3C%2Fth%3E%3Cth%3EEmail%3C%2Fth%3E%3Cth%3EPermissions%3C%2Fth%3E%3Cth%3ERegistered%20On%3C%2Fth%3E%3Cth%3ETools%3C%2Fth%3E%0A%3C%2Ftr%3E%0A%3C%2Fthead%3E%0A%3Ctbody%3E%0A{$userlist}%0A%3C%2Ftbody%3E%0A%3C%2Ftable%3E%0A%3C%2Fdiv%3E%0A%3Cdiv%20class%3D%22modal-footer%22%3E%0A%3Cbutton%20type%3D%22button%22%20class%3D%22btn%20btn-default%22%20data-dismiss%3D%22modal%22%3EClose%3C%2Fbutton%3E%0A%3C%2Fdiv%3E%0A%3C%2Fdiv%3E%0A%3C%2Fdiv%3E%0A%3C%2Fdiv%3E");
				}
				$secure .= $TEMPLATES["navbar-dropdown-end"];
			}
			$secure .= $TEMPLATES["secure-navbar-dropdown-account-start"];
			$secure .= $TEMPLATES["secure-navbar-dropdown-account-button-details"];
			$secure .= $TEMPLATES["navbar-separator"];
			$secure .= $TEMPLATES["secure-navbar-dropdown-account-button-logout"];
			$secure .= $TEMPLATES["navbar-dropdown-end"];
			$secure .= $TEMPLATES["secure-navbar-button-about"];
			$secure .= $TEMPLATES["secure-navbar-nav-end"];
			$secure .= "<p class=\"navbar-text navbar-right\"><a href=\"#\" class=\"navbar-link\">{$authuser->name}</a></p>";
			$secure .= $TEMPLATES["secure-navbar-end"];
			$modals .= urldecode("%3Cdiv%20class%3D%22modal%20fade%22%20id%3D%22dialog_account%22%20tabindex%3D%22-1%22%20role%3D%22dialog%22%20aria-labelledby%3D%22dialog_users_title%22%3E%0A%3Cdiv%20class%3D%22modal-dialog%20modal-lg%22%20role%3D%22document%22%3E%0A%3Cdiv%20class%3D%22modal-content%22%3E%0A%3Cdiv%20class%3D%22modal-header%22%3E%0A%3Cbutton%20type%3D%22button%22%20class%3D%22close%22%20data-dismiss%3D%22modal%22%20aria-label%3D%22Close%22%3E%3Cspan%20aria-hidden%3D%22true%22%3E%26times%3B%3C%2Fspan%3E%3C%2Fbutton%3E%0A%3Ch4%20class%3D%22modal-title%22%20id%3D%22dialog_users_title%22%3EAccount%20Details%3C%2Fh4%3E%0A%3C%2Fdiv%3E%0A%3Cdiv%20class%3D%22modal-body%22%3E%0A%3Ch4%3EYour%20Profile%3C%2Fh4%3E%0A%3Cform%20class%3D%22form-horizontal%22%20role%3D%22edit%22%20onsubmit%3D%22dialog_account_save()%3Breturn%20false%3B%22%3E%0A%3Cdiv%20class%3D%22form-group%22%3E%0A%3Clabel%20class%3D%22contol-label%20col-sm-3%20col-md-2%22%20for%3D%22dialog_account_name%22%3EName%3A%3C%2Flabel%3E%0A%3Cdiv%20class%3D%22col-sm-9%20col-md-10%22%3E%0A%3Cinput%20name%3D%22name%22%20title%3D%22Name%22%20class%3D%22form-control%22%20id%3D%22dialog_account_name%22%20type%3D%22text%22%20placeholder%3D%22Name%22%20value%3D%22{$authuser->name}%22%20%2F%3E%0A%3C%2Fdiv%3E%0A%3C%2Fdiv%3E%0A%3Cdiv%20class%3D%22form-group%22%3E%0A%3Cdiv%20class%3D%22col-sm-offset-3%20col-md-offset-2%20col-sm-9%20col-md-10%22%3E%0A%3Cbutton%20class%3D%22btn%20btn-default%22%20type%3D%22submit%22%3ESave%3C%2Fbutton%3E%0A%3Cspan%20class%3D%22dialog_account_formfeedback_saved%20hidden%22%3ESaved!%3C%2Fspan%3E%0A%3Cspan%20class%3D%22dialog_account_formfeedback_notsaved%20hidden%22%3ECouldn%27t%20save!%20Check%20your%20connection.%3C%2Fspan%3E%0A%3C%2Fdiv%3E%0A%3C%2Fdiv%3E%0A%3Cdiv%20class%3D%22form-group%22%3E%0A%3Clabel%20class%3D%22contol-label%20col-sm-3%20col-md-2%22%20for%3D%22dialog_account_email%22%3EEmail%3A%3C%2Flabel%3E%0A%3Cdiv%20class%3D%22col-sm-9%20col-md-10%22%3E%0A%3Cp%20class%3D%22form-control-static%22%20id%3D%22dialog_account_email%22%3E{$authuser->email}%3C%2Fp%3E%0A%3C%2Fdiv%3E%0A%3C%2Fdiv%3E%0A%3Cdiv%20class%3D%22form-group%22%3E%0A%3Clabel%20class%3D%22contol-label%20col-sm-3%20col-md-2%22%20for%3D%22dialog_account_regdate%22%3ERegistered%20on%3A%3C%2Flabel%3E%0A%3Cdiv%20class%3D%22col-sm-9%20col-md-10%22%3E%0A%3Cp%20class%3D%22form-control-static%22%20id%3D%22dialog_account_regdate%22%3E{$authuser->registerdate}%3C%2Fp%3E%0A%3C%2Fdiv%3E%0A%3C%2Fdiv%3E%0A%3Cdiv%20class%3D%22form-group%22%3E%0A%3Clabel%20class%3D%22contol-label%20col-sm-3%20col-md-2%22%20for%3D%22dialog_account_perms%22%3EPermissions%3A%3C%2Flabel%3E%0A%3Cdiv%20class%3D%22col-sm-9%20col-md-10%22%3E%0A%3Cp%20class%3D%22form-control-static%22%20id%3D%22dialog_account_perms%22%3E{$authuser->rawperms}%3C%2Fp%3E%0A%3C%2Fdiv%3E%0A%3C%2Fdiv%3E%0A%3C%2Fform%3E%0A%3Ch4%3EChange%20your%20Password%3C%2Fh4%3E%0A%3Cform%20class%3D%22form-horizontal%22%20role%3D%22edit%22%20onsubmit%3D%22dialog_account_changepass()%3Breturn%20false%3B%22%3E%0A%3Cdiv%20class%3D%22form-group%20has-feedback%22%3E%0A%3Clabel%20class%3D%22contol-label%20col-sm-3%20col-md-2%22%20for%3D%22dialog_account_cpwd%22%3ECurrent%20Password%3A%3C%2Flabel%3E%0A%3Cdiv%20class%3D%22col-sm-9%20col-md-10%22%3E%0A%3Cinput%20name%3D%22cpwd%22%20title%3D%22Current%20Password%22%20class%3D%22form-control%22%20id%3D%22dialog_account_cpwd%22%20type%3D%22password%22%20placeholder%3D%22Current%20Password%22%20oninput%3D%22dialog_account_check_cpwd()%3B%22%20%2F%3E%0A%3Cspan%20class%3D%22glyphicon%20glyphicon-remove%20form-control-feedback%20hidden%22%3E%3C%2Fspan%3E%0A%3Cspan%20class%3D%22glyphicon%20glyphicon-ok%20form-control-feedback%20hidden%22%3E%3C%2Fspan%3E%0A%3C%2Fdiv%3E%0A%3C%2Fdiv%3E%0A%3Cdiv%20class%3D%22form-group%22%3E%0A%3Clabel%20class%3D%22contol-label%20col-sm-3%20col-md-2%22%20for%3D%22dialog_account_npwd%22%3ENew%20Password%3A%3C%2Flabel%3E%0A%3Cdiv%20class%3D%22col-sm-9%20col-md-10%22%3E%0A%3Cdiv%20class%3D%22input-group%22%3E%0A%3Cinput%20name%3D%22npwd%22%20title%3D%22New%20Password%22%20class%3D%22form-control%22%20id%3D%22dialog_account_npwd%22%20type%3D%22password%22%20placeholder%3D%22New%20Password%22%20oninput%3D%22dialog_account_check_npwd()%3B%22%20%2F%3E%0A%3Cspan%20class%3D%22input-group-btn%22%3E%0A%3Cbutton%20class%3D%22btn%20btn-default%22%20type%3D%22button%22%20onclick%3D%22dialog_account_toggleshownpwd()%3B%22%3E%3Cspan%20id%3D%22dialog_account_toggleshownpwd_symbol%22%20class%3D%22glyphicon%20glyphicon-eye-open%22%3E%3C%2Fspan%3E%3C%2Fbutton%3E%0A%3C%2Fspan%3E%0A%3C%2Fdiv%3E%0A%3Cspan%20class%3D%22dialog_account_formfeedback_badnpwd%20hidden%22%3EYour%20password%20must%20contain%20at%20least%208%20characters.%3C%2Fspan%3E%0A%3C%2Fdiv%3E%0A%3C%2Fdiv%3E%0A%3Cdiv%20class%3D%22form-group%22%3E%0A%3Cdiv%20class%3D%22col-sm-offset-3%20col-md-offset-2%20col-sm-9%20col-md-10%22%3E%0A%3Cbutton%20class%3D%22btn%20btn-default%22%20type%3D%22submit%22%3EChange%20Password%3C%2Fbutton%3E%0A%3C%2Fdiv%3E%0A%3C%2Fdiv%3E%0A%3C%2Fform%3E%0A%3Ch4%3EDelete%20your%20Account%3C%2Fh4%3E%0A%3Cdiv%20class%3D%22col-sm-offset-3%20col-md-offset-2%20col-sm-9%20col-md-10%22%3E%0A%3Cbutton%20class%3D%22btn%20btn-danger%22%20onclick%3D%22dialog_users_delete(%27{$authuser->uid}%27)%3B%22%3EDelete%20Account%3C%2Fbutton%3E%0A%3C%2Fdiv%3E%0A%3C%2Fdiv%3E%0A%3Cdiv%20class%3D%22modal-footer%22%3E%0A%3Cbutton%20type%3D%22button%22%20class%3D%22btn%20btn-default%22%20data-dismiss%3D%22modal%22%3EClose%3C%2Fbutton%3E%0A%3C%2Fdiv%3E%0A%3C%2Fdiv%3E%0A%3C%2Fdiv%3E%0A%3C%2Fdiv%3E");
			$modals .= $TEMPLATES["secure-modal-start"]("dialog_about", "About", "md");
			$modals .= $TEMPLATES["secure-modal-about-body"]($ccms_info->version, $ccms_info->release, $ccms_info->a_email, $ccms_info->author, $ccms_info->website, getconfig("creationdate"));
			$modals .= $TEMPLATES["secure-modal-about-foot"];
			$modals .= $TEMPLATES["secure-modal-end"];
		}
		
		$modals .= "</div>";
		
		$base = urldecode(getconfig("defaultnav"));
		$header = $secure . $base . $modals;
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
				if (in_array($mod, $availablemodules)) {
					if (method_exists($modules[$mod], $func)) {
						$content = $modules[$mod]->$func();
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