<?php

class Notice {
	
	public $noticeid = "";
	public $level = "info";
	public $content = "";
	public $canclose = true;
	public $lastsfor = 0;
	public $publish = "";
	public $expire = 0;
	
	function __construct($noticeid) {
		global $conn, $sqlstat, $sqlerr;
		$this->noticeid = $noticeid;
		
		if ($sqlstat) {
			$stmt = $conn->prepare("SELECT * FROM module_notices_content WHERE noticeid=:nid;");
			$stmt->bindParam(":nid", $noticeid);
			$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
			$ndatas = $stmt->fetchAll();
			if (count($ndatas) == 1) {
				$ndata = $ndatas[0];
				$this->level = $ndata["level"];
				$this->content = urldecode($ndata["content"]);
				$this->canclose = $ndata["canclose"];
				$this->lastsfor = $ndata["lastsfor"];
				$this->publish = date("l, F j, Y", strtotime($ndata["publish"]));
				$this->expire = strtotime($ndata["expire"]);
			}
		} else {
			$this->level = "urgent";
			$this->content = "Something got messy on our server! We should have it fixed soon.";
			$this->publish = date("l, F j, Y", time());
			$this->expire = time()+3600*24*30;
		}
	}
	
	function compile() {
		$c = "";
		if ($this->canclose) {
			$c = "<button class=\"close\" onclick=\"$('#notice-{$this->noticeid}').addClass('hide');\">&times;</button>";
		}
		$cmp = "<div id=\"notice-{$this->noticeid}\" class=\"notice {$this->level}\">
{$c}<div class=\"notice-body\">{$this->content}<p><i>Notice published on {$this->publish}</i></p></div></div>";
		return $cmp;
	}
	
}

class module_notices {
	
	public $dependencies = [];
	public $name = "Site Notices";
	
	function seed() {
		global $conn, $sqlstat, $sqlerr;
		if (!$this->isSeeded() and $sqlstat) {
			$stmt = $conn->prepare("CREATE TABLE module_notices_permissions (uid char(32) NOT NULL, pwd char(128) NOT NULL, UNIQUE KEY uid (uid));");
			$stmt->execute();
			$stmt = $conn->prepare("CREATE TABLE module_notices_content (noticeid varchar(128) NOT NULL, level tinytext NOT NULL, content text NOT NULL, target text NOT NULL, canclose bool NOT NULL, lastsfor smallint NOT NULL, publish date NOT NULL, expire date NOT NULL, UNIQUE KEY noticeid (noticeid));");
			$stmt->execute();
		}
	}
	
	function isSeeded() {
		global $conn, $sqlstat, $sqlerr;
		if ($sqlstat) {
			$stmt = $conn->prepare("SHOW TABLES LIKE 'module_notices_permissions';");
			$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
			if (count($stmt->fetchAll()) == 1) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	function __construct() {
		global $conn, $sqlstat, $sqlerr;
		global $page;
		$this->seed();
		
		$notices = [];
		
		if ($sqlstat) {
			$stmt = $conn->prepare("SELECT noticeid FROM module_notices_content WHERE (publish<=:now AND expire>:now) AND (target=:pid OR target='*');");
			$now = date("Y-m-d");
			$stmt->bindParam(":now", $now);
			$stmt->bindParam(":pid", $page->pageid);
			$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
			$nids = $stmt->fetchAll();
			
			foreach ($nids as $n) {
				array_push($notices, new Notice($n["noticeid"]));
			}
			foreach ($notices as $n) {
				if (isset($_COOKIE["notice-{$n->noticeid}"])) {
					if ($n->lastsfor == 0 or $_COOKIE["notice-{$n->noticeid}"] < $n->lastsfor) {
						setcookie("notice-{$n->noticeid}", $_COOKIE["notice-{$n->noticeid}"] + 1, $n->expire);
					} else {
						setcookie("notice-{$n->noticeid}", $_COOKIE["notice-{$n->noticeid}"] + 1, $n->expire);
					}
				} else {
					setcookie("notice-{$n->noticeid}", 1, $n->expire);
				}
			}
		}
	}
	
	function getModal() {
		$modal = "";
		return $modal;
	}
	
	function manager() {
		global $conn, $sqlstat, $sqlerr;
		global $authuser;
		return "";
	}
	
	function show() {
		global $conn, $sqlstat, $sqlerr;
		global $authuser, $page;
		
		$notices = [];

		$content = "";
		
		if ($sqlstat) {
			$stmt = $conn->prepare("SELECT noticeid FROM module_notices_content WHERE (publish<=:now AND expire>:now) AND (target=:pid OR target='*');");
			$now = date("Y-m-d");
			$stmt->bindParam(":now", $now);
			$stmt->bindParam(":pid", $page->pageid);
			$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
			$nids = $stmt->fetchAll();
			foreach ($nids as $n) {
				array_push($notices, new Notice($n["noticeid"]));
			}
		}

		$content .= "<div class=\"container-fluid\">";

		foreach ($notices as $n) {
			if (isset($_COOKIE["notice-{$n->noticeid}"])) {
				if ($n->lastsfor == 0 or $_COOKIE["notice-{$n->noticeid}"] < $n->lastsfor) {
					$content .= $n->compile();
				}
			} else {
				$content .= $n->compile();
			}
		}

		$content .= "</div>";
		return $content;
	}	
}

?>