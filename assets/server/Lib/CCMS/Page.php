<?php

namespace Lib\CCMS;

use \PDO;

class Page {
	
	public $pageid = "";
	public $rawtitle = "Empty%20Page";
	public $title = "Empty Page";
	public $rawhead = "";
	public $head = "";
	public $rawbody = "%3Ch1%3EEmpty%20Page!%3C%2Fh1%3E";
	public $body = "<h1>Empty Page!</h1>";
	public $usehead = true;
	public $usetop = true;
	public $usebottom = true;
	public $secure = false;
	public $revision = "";
	
	function __construct($pid=null) {
		global $conn, $sqlstat, $sqlerr;
		if ($pid !== null) {
			$pageid = $pid;
		} else {
			global $pageid;
		}
		$this->pageid = $pageid;
		
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
				$this->usehead = $pdata["usehead"];
				$this->usetop = $pdata["usetop"];
				$this->usebottom = $pdata["usebottom"];
				$this->title = urldecode($this->rawtitle);
				$this->head = urldecode($this->rawhead);
				$this->body = urldecode($this->rawbody);
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
	
	function getTop() {
		global $authuser;
		global $conn, $sqlstat, $sqlerr;
		global $TEMPLATES;
		global $availablemodules, $modules;
		
		$securepages = [];
		if ($sqlstat) {
			$stmt = $conn->prepare("SELECT pageid FROM content_pages WHERE secure=1 AND pageid NOT LIKE '_default/%';");
			$stmt->execute();
			$stmt->setFetchMode(PDO::FETCH_ASSOC);
			$ps = $stmt->fetchAll();
			foreach ($ps as $p) {
				array_push($securepages, $p["pageid"]);
			}
		}
		
		$modals = "<div id=\"modals\">";
		$script = "<script>";

		if ($authuser->permissions->toolbar) {
			
			if ((($this->secure and $authuser->permissions->page_editsecure) or (!$this->secure and $authuser->permissions->page_edit)) and !in_array($this->pageid, $authuser->permissions->page_editblacklist)) {
				$modals .= $TEMPLATES["secure-modal-start"]("dialog_edit", "Edit Page", "lg");
				$modals .= $TEMPLATES["secure-modal-edit-bodyfoot"]($this);
				$modals .= $TEMPLATES["secure-modal-end"];
				$script .= $TEMPLATES["secure-modal-edit-script"]($this->pageid, $this->rawtitle, $this->rawhead, $this->rawbody);
			}
			if ($authuser->permissions->admin_managepages) {
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
			}
			$modals .= $TEMPLATES["secure-modal-start"]("dialog_account", "Account Details", "lg");
			$modals .= $TEMPLATES["secure-modal-account-bodyfoot"]($authuser);
			$modals .= $TEMPLATES["secure-modal-end"];
			$script .= $TEMPLATES["secure-modal-account-script"];
			if ($authuser->permissions->admin_managesite) {
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
		}
		
		$modals .= "</div>";
		$script .= "</script>";
		
		$secure = "";
		
		if ($authuser->permissions->toolbar) {
			$secure .= $TEMPLATES["secure-menu"]($authuser, $securepages, $availablemodules, $modules);
		}
		
		$top = "";
		if ($this->usetop) {
			$top = (new Page("_default/top"))->body;
		}
		$header = $secure . $modals . $script . $top;
		return $header;
	}

	function getBottom() {
		$bottom = "";
		if ($this->usebottom) {
			$bottom = (new Page("_default/bottom"))->body;
		}
		return $bottom;
	}
	
	function resolvePlaceholders() {
		global $authuser;
		global $availablemodules, $modules;
		
		$this->body = $this->getTop() . $this->body . $this->getBottom();
		$placeholders = [];
		preg_match_all("/\{{2}[^\}]+\}{2}/", $this->body, $placeholders);
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
				$args = [];
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
		$pre = "";
		if ($this->usehead) {
			$pre = (new Page("_default/head"))->head;
		}
		$sitetitle = getconfig("websitetitle");
		echo "{$pre}<title>{$this->title} | {$sitetitle}</title>{$this->head}";		
	}
	
	function insertBody() {
		echo $this->body;
	}

}