<?php

namespace Lib\CCMS\Security;

use \PDO;
use \Lib\CCMS\Response;
use \Lib\CCMS\Request;
use \Lib\CCMS\Security\UserPermissions;

class User
{
    public $name = "User";
    public $email = "";
    public $uid = null;
    public $pwdHash = "";
    public $registerdate = "";
    public $rawperms = "";
    public $permissions = null;
    public $notify = false;
    public $online = false;
    
    public static $currentUser = null;

    public function __construct($uid)
    {
        global $conn, $sqlstat;

        $this->uid = $uid;
        $this->permissions = new UserPermissions();

        if (!$sqlstat) {
            return;
        }

        $stmt = $conn->prepare("SELECT * FROM users WHERE uid=:uid;");
        $stmt->bindParam(":uid", $this->uid);
        $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
        $udata = $stmt->fetchAll();
        
        if (count($udata) != 1) {
            $this->uid = null;
            return;
        }

        $this->email = User::normalizeEmail($udata[0]["email"]);
        $this->name = $udata[0]["name"];
        $this->pwdHash = $udata[0]["pwd"];

        $this->notify = $udata[0]["notify"] && strtotime($udata[0]["last_notif"])<strtotime("now")-(30*60); // 30-minute cooldown
        $this->online = strtotime($udata[0]["collab_lastseen"])>strtotime("now")-10;

        $this->registerdate = date("l, F j, Y", strtotime($udata[0]["registered"]));

        $rawperm = $udata[0]["permissions"];
        $this->rawperms = $rawperm;

        $this->permissions->owner = !(strpos($rawperm, "owner;") === false);
        $this->permissions->admin_managesite = !(strpos($rawperm, "admin_managesite;") === false);
        $this->permissions->admin_managepages = !(strpos($rawperm, "admin_managepages;") === false);
        $this->permissions->page_createsecure = !(strpos($rawperm, "page_createsecure;") === false);
        $this->permissions->page_editsecure = !(strpos($rawperm, "page_editsecure;") === false);
        $this->permissions->page_deletesecure = !(strpos($rawperm, "page_deletesecure;") === false);
        $this->permissions->page_viewsecure = !(strpos($rawperm, "page_viewsecure;") === false);
        $this->permissions->page_create = !(strpos($rawperm, "page_create;") === false);
        $this->permissions->page_edit = !(strpos($rawperm, "page_edit;") === false);
        $this->permissions->page_delete = !(strpos($rawperm, "page_delete;") === false);
        $this->permissions->toolbar = !(strpos($rawperm, "toolbar;") === false);

        // Implicit permissions:
        $this->permissions->admin_managesite |= $this->permissions->owner;
        $this->permissions->admin_managepages |= $this->permissions->admin_managesite;

        $this->permissions->page_deletesecure |= $this->permissions->admin_managepages;
        $this->permissions->page_createsecure |= $this->permissions->admin_managepages;
        $this->permissions->page_editsecure |= $this->permissions->page_createsecure;
        $this->permissions->page_viewsecure |= $this->permissions->page_editsecure;

        $this->permissions->page_create |= $this->permissions->page_createsecure;
        $this->permissions->page_edit |= $this->permissions->page_editsecure;
        $this->permissions->page_delete |= $this->permissions->page_deletesecure;

        $this->permissions->toolbar |= (
            $this->permissions->page_create ||
            $this->permissions->page_edit ||
            $this->permissions->page_delete
        );

        // Blacklists
        $this->permissions->page_viewblacklist = preg_split('@;@', $udata[0]["permviewbl"], NULL, PREG_SPLIT_NO_EMPTY);
        $this->permissions->page_editblacklist = preg_split('@;@', $udata[0]["permeditbl"], NULL, PREG_SPLIT_NO_EMPTY);
    }
    
    public function isValidUser()
    {
        return $this->uid !== null;
    }
    
    public function authenticate($password)
    {
        return password_verify($password, $this->pwdHash);
    }
    
    public function notify($what)
    {
        global $conn, $notifMailer, $TEMPLATES; // authuser is sender
        if (User::$currentUser->uid == $uid) {
            return;
        }
        $what .= ";";
        $stmt = $conn->prepare("UPDATE users SET collab_notifs = CONCAT(`collab_notifs`,:what) WHERE uid=:uid;");
        $stmt->bindParam(":what", $what);
        $stmt->bindParam(":uid", $this->uid);
        $stmt->execute();
        
        if ($this->online || !$this->notify) {
            // Don't email if online already or has disabled email notifications/within notification cooldown.
            return;
        }
        
        // Reset recipient's notification cooldown
        $stmt = $conn->prepare("UPDATE users SET last_notif=UTC_TIMESTAMP WHERE uid=:uid;");
        $stmt->bindParam(":uid", $this->uid);
        $stmt->execute();
        
        $nType = substr($what, 0, 1);
        
        if ($nType == "R" || $nType == "U") {
            // Chat
            $rn = "";
            if ($nType == "U") {
                $rn = "you";
            }
            else
            {
                $rid = substr($what, 1, strlen($what)-2);
                $stmt = $conn->prepare("SELECT room_name FROM collab_rooms WHERE room_id=:rid;");
                $stmt->bindParam(":rid", $rid);
                $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
                $rn = $stmt->fetchAll()[0]["room_name"];
            }
            $body = $TEMPLATES["email-notif-chat"](User::$currentUser->name, $rn);
            $oldFrom = $notifMailer->from;
            $notifMailer->from = User::$currentUser->name;
            $mail = $notifMailer->compose([[$this->email, $this->name]], User::$currentUser->name." sent a message", $body, "");
            $mail->send();
            $notifMailer->from = $oldFrom;
        }
    }

    function unnotify($what)
    {
        global $conn;
        $what .= ";";
        $stmt = $conn->prepare("UPDATE users SET collab_notifs = REPLACE(`collab_notifs`,:what,'') WHERE uid=:uid;");
        $stmt->bindParam(":what", $what);
        $stmt->bindParam(":uid", $this->uid);
        $stmt->execute();
    }
    
    public static function userFromToken($token)
    {
        global $conn, $sqlstat;
        
        if (!$sqlstat) {
            return new User(null);
        }
        
        $stmt = $conn->prepare("SELECT * FROM tokens WHERE tid=:tid;");
        $stmt->bindParam(":tid", $token);
        $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);

        return new User($stmt->fetchAll()[0]["uid"]);
    }
    
    public static function userFromEmail($email)
    {
        return new User(User::uidFromEmail($email));
    }
    
    public static function uidFromEmail($email)
    {
        return md5(User::normalizeEmail($email));
    }
    
    public static function normalizeEmail($email)
    {
        $email = strtolower($email);
        $email = preg_replace('/\s+/', '', $email); // remove whitespace
        return $email;
    }
    
    public static function numberOfOwners()
    {
        global $conn, $sqlstat;
        
        if (!$sqlstat) {
            return 0;
        }
        
        $stmt = $conn->prepare("SELECT uid FROM users WHERE permissions LIKE '%owner%';");
        $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);

        return count($stmt->fetchAll());
    }
    
    public static function hook(Request $request)
    {
        $token = $request->getCookie("token");
        
        if (AccountManager::validateToken($token, $_SERVER["REMOTE_ADDR"])) {
            User::$currentUser = User::userFromToken($token);
            return;
        }
        
        User::$currentUser = new User(null);
        
        setcookie("token", "0", 1);
    }
}
