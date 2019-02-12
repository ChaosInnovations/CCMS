<?php

namespace Lib\CCMS\Security;

use \PDO;
use \Lib\CCMS\Security\UserPermissions;

class User {
	
	public $name = "User";
	public $email = "";
	public $uid = null;
	public $registerdate = "";
	public $rawperms = "";
	public $permissions = null;
	public $notify = true;
	public $online = false;
	
	function __construct($uid) {
		global $conn, $sqlstat, $sqlerr;
		
		$this->permissions = new UserPermissions();
		$this->uid = $uid;
		if ($uid != null and $sqlstat and validUser($uid)) {			
			$stmt = $conn->prepare("SELECT * FROM users WHERE uid=:uid;");
			$stmt->bindParam(":uid", $uid);
			$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
			$udata = $stmt->fetchAll();
			
			$this->uid = $uid;
			$this->email = fix_email($udata[0]["email"]);
			$this->name = $udata[0]["name"];
			$this->notify = $udata[0]["notify"] && strtotime($udata[0]["last_notif"])<strtotime("now")-(30*60); // 30-minute cooldown
			$this->online = strtotime($udata[0]["collab_lastseen"])>strtotime("now")-10;
			$this->registerdate = date("l, F j, Y", strtotime($udata[0]["registered"]));
			$rawperm = $udata[0]["permissions"];
			$this->rawperms = $rawperm;
			
			$this->permissions->owner = !(strpos($rawperm, "owner;") === false);
			$this->permissions->admin_managesite = (!(strpos($rawperm, "admin_managesite;") === false) or $this->permissions->owner);
			$this->permissions->admin_managepages = (!(strpos($rawperm, "admin_managepages;") === false) or $this->permissions->admin_managesite);
			$this->permissions->page_createsecure = (!(strpos($rawperm, "page_createsecure;") === false) or $this->permissions->admin_managepages);
			$this->permissions->page_editsecure = (!(strpos($rawperm, "page_editsecure;") === false) or $this->permissions->page_createsecure);
			$this->permissions->page_deletesecure = (!(strpos($rawperm, "page_deletesecure;") === false) or $this->permissions->admin_managepages);
			$this->permissions->page_viewsecure = (!(strpos($rawperm, "page_viewsecure;") === false) or $this->permissions->page_editsecure);
			$this->permissions->page_create = (!(strpos($rawperm, "page_create;") === false) or $this->permissions->page_createsecure);
			$this->permissions->page_edit = (!(strpos($rawperm, "page_edit;") === false) or $this->permissions->page_editsecure);
			$this->permissions->page_delete = (!(strpos($rawperm, "page_delete;") === false) or $this->permissions->page_deletesecure);
			$this->permissions->toolbar = (!(strpos($rawperm, "toolbar;") === false) or 
										   $this->permissions->owner or
										   $this->permissions->admin_managesite or
										   $this->permissions->admin_managepages or
										   $this->permissions->page_createsecure or
										   $this->permissions->page_editsecure or
										   $this->permissions->page_deletesecure or
										   $this->permissions->page_create or
										   $this->permissions->page_edit or
										   $this->permissions->page_delete);
			//Also need to read blacklists
			$this->permissions->page_viewblacklist = preg_split('@;@', $udata[0]["permviewbl"], NULL, PREG_SPLIT_NO_EMPTY);
			$this->permissions->page_editblacklist = preg_split('@;@', $udata[0]["permeditbl"], NULL, PREG_SPLIT_NO_EMPTY);
		}
	}
	
}