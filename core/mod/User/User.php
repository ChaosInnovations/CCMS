<?php

namespace Mod;

use \Lib\CCMS\Response;
use \Lib\CCMS\Request;
use \Mod\Database;
use \Mod\Mailer;
use \Mod\User\AccountManager;
use \Mod\User\UserPermissions;
use \PDO;

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
        $this->uid = $uid;
        $this->permissions = new UserPermissions();

        $stmt = Database::Instance()->prepare("SELECT * FROM users WHERE uid=:uid;");
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
        global $TEMPLATES;
        if (User::$currentUser->uid == $uid) {
            return;
        }
        $what .= ";";
        $stmt = Database::Instance()->prepare("UPDATE users SET collab_notifs = CONCAT(`collab_notifs`,:what) WHERE uid=:uid;");
        $stmt->bindParam(":what", $what);
        $stmt->bindParam(":uid", $this->uid);
        $stmt->execute();
        
        if ($this->online || !$this->notify) {
            // Don't email if online already or has disabled email notifications/within notification cooldown.
            return;
        }
        
        // Reset recipient's notification cooldown
        $stmt = Database::Instance()->prepare("UPDATE users SET last_notif=UTC_TIMESTAMP WHERE uid=:uid;");
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
                $stmt = Database::Instance()->prepare("SELECT room_name FROM collab_rooms WHERE room_id=:rid;");
                $stmt->bindParam(":rid", $rid);
                $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
                $rn = $stmt->fetchAll()[0]["room_name"];
            }
            $body = $TEMPLATES["email-notif-chat"](User::$currentUser->name, $rn);
            $oldFrom = Mailer::NotifInstance()->from;
            Mailer::NotifInstance()->from = User::$currentUser->name;
            $mail = Mailer::NotifInstance()->compose([[$this->email, $this->name]], User::$currentUser->name." sent a message", $body, "");
            $mail->send();
            Mailer::NotifInstance()->from = $oldFrom;
        }
    }

    function unnotify($what)
    {
        $what .= ";";
        $stmt = Database::Instance()->prepare("UPDATE users SET collab_notifs = REPLACE(`collab_notifs`,:what,'') WHERE uid=:uid;");
        $stmt->bindParam(":what", $what);
        $stmt->bindParam(":uid", $this->uid);
        $stmt->execute();
    }
    
    public static function userFromToken($token)
    {
        $stmt = Database::Instance()->prepare("SELECT * FROM tokens WHERE tid=:tid;");
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
        $stmt = Database::Instance()->prepare("SELECT uid FROM users WHERE permissions LIKE '%owner%';");
        $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);

        return count($stmt->fetchAll());
    }
    
    public static function hookAuthenticateFromRequest(Request $request)
    {
        $token = $request->getCookie("token");
        
        if (AccountManager::validateToken($token, $_SERVER["REMOTE_ADDR"])) {
            User::$currentUser = User::userFromToken($token);
            return;
        }
        
        User::$currentUser = new User(null);
        
        setcookie("token", "0", 1);
    }
    
    public static function hookCheckUser(Request $request)
    {
        if (!$_POST["email"]) {
            return new Response("FALSE");
        }
        
        if (!User::userFromEmail($_POST["email"])->isValidUser()) {
            return new Response("FALSE");
        }
        
        return new Response("TRUE");
    }
    
    public static function hookCheckPassword(Request $request)
    {
        if (!isset($_POST["password"])) {
            return "FALSE";
        }
        
        $uid = User::$currentUser->uid;
        if (isset($_POST["email"])) {
            $uid = User::uidFromEmail($_POST["email"]);
        }
        
        $userToAuthenticate = new User($uid);
        if (!$userToAuthenticate->isValidUser()) {
            return new Response("FALSE");
        }
        
        if (!$userToAuthenticate->authenticate($_POST["password"])) {
            return new Response("FALSE");
        }
        return new Response("TRUE");
    }
    
    public static function hookNewUser(Request $request)
    {
        // api/user/new
        global $TEMPLATES;
        global $baseUrl;
        
        if (!isset($_POST["email"]) or !isset($_POST["name"]) or !isset($_POST["permissions"])) {
            return new Response("FALSE");
        }
        
        $uid = User::uidFromEmail($_POST["email"]);
        if ((new User($uid))->isValidUser()) {
            // User already exists.
            return new Response("FALSE");
        }
        if (!User::$currentUser->permissions->owner) {
            // Only owners can change users
            return new Response("FALSE");
        }
        
        $body = $TEMPLATES["email-newuser"]($_POST["name"], User::$currentUser->name, $baseUrl, Utilities::getconfig("websitetitle"));
        $mail = Mailer::NotifInstance()->compose([[User::normalizeEmail($_POST["email"]), $_POST["name"]]], "Account Created", $body, "");
        
        if (!$mail->send()) {
            return new Response("FALSE");
        }
        
        $email = User::normalizeEmail($_POST["email"]);
        $now = date("Y-m-d");
        $pwd = password_hash("password", PASSWORD_DEFAULT);
        
        $stmt = $Database::Instance()->prepare("INSERT INTO users VALUES (:uid, :pwd, :email, :name, :now, :perms, '', '', 0, NULL, '', 1, 0);");
        $stmt->bindParam(":uid", $uid);
        $stmt->bindParam(":pwd", $pwd);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":name", $_POST["name"]);
        $stmt->bindParam(":now", $now);
        $stmt->bindParam(":perms", $_POST["permissions"]);
        $stmt->execute();
        
        return new Response("TRUE");
    }
    
    public static function hookRemoveUser(Request $request)
    {
        // api/user/remove
        if (!isset($_POST["uid"]) or !(new User($_POST["uid"]))->isValidUser()) {
            return new Response("FALSE");
        }
        $uid = $_POST["uid"];
        if (!User::$currentUser->permissions->owner and User::$currentUser->uid != $uid) {
            return new Response("FALSE");
        }
        if (User::$currentUser->permissions->owner and User::$currentUser->uid == $uid and User::numberOfOwners() <= 1) {
            return new Response("OWNER");
        }
        $stmt = Database::Instance()->prepare("DELETE FROM users WHERE uid=:uid;");
        $stmt->bindParam(":uid", $uid);
        $stmt->execute();

        $stmt = Database::Instance()->prepare("DELETE FROM tokens WHERE uid=:uid;");
        $stmt->bindParam(":uid", $uid);
        $stmt->execute();

        return new Response("TRUE");
    }
    
    public static function hookPasswordReset(Request $request)
    {
        // api/user/password/reset
        if (!isset($_POST["uid"]) or !(new User($_POST["uid"]))->isValidUser()) {
            return new Response("FALSE");
        }
        if (!User::$currentUser->permissions->owner) {
            return new Response("FALSE");
        }
        $pwd = password_hash("password", PASSWORD_DEFAULT);
        $stmt = Database::Instance()->prepare("UPDATE users SET pwd=:pwd WHERE uid=:uid;");
        $stmt->bindParam(":pwd", $pwd);
        $stmt->bindParam(":uid", $_POST["uid"]);
        $stmt->execute();
        return new Response("TRUE");
    }
    
    public static function hookPasswordChange(Request $request)
    {
        // api/user/password/edit
        if (!isset($_POST["cpwd"]) or !isset($_POST["npwd"])) {
            return new Response("FALSE");
        }
        if (!User::$currentUser->isValidUser()) {
            return new Response("FALSE");
        }
        if (!User::$currentUser->authenticate($_POST["cpwd"])) {
            return new Response("FALSE");
        }
        $pwd = password_hash($_POST["npwd"], PASSWORD_DEFAULT);
        $stmt = Database::Instance()->prepare("UPDATE users SET pwd=:pwd WHERE uid=:uid;");
        $stmt->bindParam(":uid", User::$currentUser->uid);
        $stmt->bindParam(":pwd", $pwd);
        $stmt->execute();
        echo new Response("TRUE");
    }
    
    public static function hookEditUser(Request $request)
    {
        // api/user/edit
        if (!User::$currentUser->isValidUser()) {
            return new Response("FALSE");
        }
        
        if (isset($_POST["name"])) {
            $stmt = Database::Instance()->prepare("UPDATE users SET name=:name WHERE uid=:uid;");
            $stmt->bindParam(":name", $_POST["name"]);
            $stmt->bindParam(":uid", User::$currentUser->uid);
            $stmt->execute();
        }
        if (isset($_POST["notify"])) {
            $stmt = Database::Instance()->prepare("UPDATE users SET notify=:notify WHERE uid=:uid;");
            $stmt->bindParam(":notify", $_POST["notify"]);
            $stmt->bindParam(":uid", User::$currentUser->uid);
            $stmt->execute();
        }
        if (isset($_POST["permissions"]) and User::$currentUser->permissions->owner) {
            $stmt = Database::Instance()->prepare("UPDATE users SET permissions=:new WHERE uid=:uid;");
            $stmt->bindParam(":new", $_POST["permissions"]);
            $stmt->bindParam(":uid", $_POST["uid"]);
            $stmt->execute();
        }
        if (isset($_POST["permviewbl"]) and User::$currentUser->permissions->owner) {
            $stmt = Database::Instance()->prepare("UPDATE users SET permviewbl=:new WHERE uid=:uid;");
            $stmt->bindParam(":new", $_POST["permviewbl"]);
            $stmt->bindParam(":uid", $_POST["uid"]);
            $stmt->execute();
        }
        if (isset($_POST["permeditbl"]) and User::$currentUser->permissions->owner) {
            $stmt = Database::Instance()->prepare("UPDATE users SET permeditbl=:new WHERE uid=:uid;");
            $stmt->bindParam(":new", $_POST["permeditbl"]);
            $stmt->bindParam(":uid", $_POST["uid"]);
            $stmt->execute();
        }
        return new Response("TRUE");
    }
    
    public static function placeholderLoginForm($args)
    {
        if (User::$currentUser->uid == null) {
            $html = '
<form id="loginform" class="form" onsubmit="return loginSubmission();">
    <div class="form-group">
        <label class="col-form-label" for="loginemail">Email Address</label>
        <div class="input-group">
            <input type="email" id="loginemail" autocomplete="email" class="form-control border-right-0 border-secondary" title="Email" placeholder="Email Address" oninput="loginCheckEmail();">
            <div class="input-group-append">
                <div class="input-group-text bg-transparent border-left-0 border-secondary">
                    <i class="fas fa-times" style="display:none;"></i>
                    <i class="fas fa-check" style="display:none;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="form-group">
        <label class="col-form-label" for="loginpass">Current Password</label>
        <div class="input-group">
            <input type="password" id="loginpass" autocomplete="current-password" class="form-control border-right-0 border-secondary" title="Password" placeholder="Password" oninput="loginCheckPass();">
            <div class="input-group-append">
                <div class="input-group-text bg-transparent border-left-0 border-secondary">
                    <i class="fas fa-times" style="display:none;"></i>
                    <i class="fas fa-check" style="display:none;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="form-group">
        <input id="loginbutton" type="submit" class="btn btn-success" title="Log In" value="Log In">
    </div>
</form>
<script>
var isLoggingIn = false;

function loginCheckEmail() {
    module_ajax("checkuser", {email: $("#loginemail").val()}, function (data) {
        if (data == "TRUE") {
            $("#loginemail").parent().removeClass("has-error");
            $("#loginemail").parent().addClass("has-success");
            $("#loginemail").parent().find(".fa-times").hide();
            $("#loginemail").parent().find(".fa-check").show();
        } else {
            $("#loginemail").parent().removeClass("has-success");
            $("#loginemail").parent().addClass("has-error");
            $("#loginemail").parent().find(".fa-check").hide();
            $("#loginemail").parent().find(".fa-times").show();
        }
    });
    loginCheckPass();
}

function loginCheckPass() {
    module_ajax("checkpass", {email: $("#loginemail").val(), password: $("#loginpass").val()}, function(data) {
        if (data == "TRUE") {
            $("#loginpass").parent().removeClass("has-error");
            $("#loginpass").parent().addClass("has-success");
            $("#loginpass").parent().find(".fa-times").hide();
            $("#loginpass").parent().find(".fa-check").show();
            loginSubmission();
        } else {
            $("#loginpass").parent().removeClass("has-success");
            $("#loginpass").parent().addClass("has-error");
            $("#loginpass").parent().find(".fa-check").addClass("hidden");
            $("#loginpass").parent().find(".fa-times").removeClass("hidden");
        }
    });
}

function loginSubmission() {
    if (isLoggingIn) {
        return false;
    }
    isLoggingIn = true;
    $("#loginbutton")[0].disabled = true;
    module_ajax("newtoken", {email: $("#loginemail").val(), password: $("#loginpass").val()}, function(data) {
        if (data != "FALSE") {
            var d = new Date(Date.now()+(3600000*24*30));
            document.cookie = "token="+data+"; expires="+d.toUTCString()+"; path=/";
            if (window.location.search.indexOf("?n") == -1) {
                window.location.reload(true);
            } else {
                var url = BASE_URL + "/" + window.location.search.substr(window.location.search.indexOf("?n")+3);
                window.location.assign(url);
            }
        } else {
            $("#loginbutton")[0].disabled = false;
            isLoggingIn = false;
        }
    });
    return false;
}
</script>';
            return $html;
        } else {
            $html = "<h5 class=\"card-title\">You're logged in.</h5>";
            $html .= "<h6 class=\"card-subtitle mb-2 text-muted\">You now have access to these pages:</h6>";
            $html .= "<div class=\"list-group\">";
            $stmt = Database::Instance()->prepare("SELECT pageid, title FROM content_pages WHERE secure=1 AND pageid NOT LIKE '_default/%' ORDER BY pageid ASC;");
            $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
            $pdatas = $stmt->fetchAll();
            foreach ($pdatas as $pd) {
                if (User::$currentUser->permissions->page_viewsecure and !in_array($pd["pageid"], User::$currentUser->permissions->page_viewblacklist)) {
                    $title = urldecode($pd["title"]);
                    $html .= "<a class=\"list-group-item list-group-item-action\" href=\"/{$pd["pageid"]}\" title=\"{$title}\">{$title}</a>";
                }
            }
            $html .= "</div>";
            return $html;
        }
    }
}
