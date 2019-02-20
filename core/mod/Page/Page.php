<?php

namespace Mod;

use \Lib\CCMS\Response;
use \Lib\CCMS\Request;
use \Lib\CCMS\Utilities;
use \Mod\Database;
use \Mod\User;
use \PDO;

class Page
{
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

    public function __construct(string $pid)
    {
        $this->pageid = $pid;

        $this->title = $this->pageid;
        $this->body = "
            <div class='container-fluid'>
                <div class='row'>
                    <div class='col-xs-12'>
                        <h1>Something got messy on our server</h1>
                    </div>
                </div>
            </div>
        ";

        $stmt = Database::Instance()->prepare("SELECT * FROM content_pages WHERE pageid=:pid;");
        $stmt->bindParam(":pid", $this->pageid);
        $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
        $pages = $stmt->fetchAll();

        if (count($pages) != 1) {
            return;
        }

        $pdata = $pages[0];
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
    }

    public function getTop()
    {
        if (!$this->usetop) {
            return "";
        }

        return (new Page("_default/top"))->body;
    }

    public function getBottom()
    {
        if (!$this->usebottom) {
            return "";
        }

        return (new Page("_default/bottom"))->body;
    }

    public function insertHead()
    {
        $pre = "";
        if ($this->usehead) {
            $pre = (new Page("_default/head"))->head;
        }
        $sitetitle = Utilities::getconfig("websitetitle");
        echo "{$pre}<title>{$this->title} | {$sitetitle}</title>{$this->head}";
    }

    public function getContent()
    {
        global $https;
        
        $this->body = $this->getTop() . $this->body . $this->getBottom();

        $content  = '<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
		<link rel="stylesheet" href="/assets/site/css/fontawesome-5.0.13/css/fontawesome-all.min.css" media="all">
		<link rel="stylesheet" href="/assets/site/css/bootstrap-4.1.1/css/bootstrap.min.css" media="all">
		<link rel="stylesheet" href="/assets/site/css/site-1.3.3.css" media="all">
		<link rel="stylesheet" type="text/css" href="/assets/site/js/codemirror/lib/codemirror.css" media="all">
        <link rel="stylesheet" href="/core/mod/SecureMenu/securemenu-1.0.0.css" media="all">
		<script src="/assets/site/js/jquery-3.3.1.min.js"></script>';
        $content .= $this->insertHead();
        $content .= "<script>var SERVER_NAME = \"{$_SERVER["SERVER_NAME"]}\", SERVER_HTTPS = \"{$https}\";</script>";
        $content .= '
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
	<body id="page">';
        $content .= $this->body;
        $content .= '
		<script src="/assets/site/js/js.cookie.js"></script>
		<script src="/assets/site/js/popper.min.js"></script>
		<script src="/assets/site/css/bootstrap-4.1.1/js/bootstrap.min.js"></script>
		<script src="/assets/site/js/site-1.1.0.js"></script>
        <script type="text/javascript" src="/assets/site/js/codemirror/lib/codemirror.js"></script>
        <script type="text/javascript" src="/assets/site/js/codemirror/mode/xml/xml.js"></script>
		<script type="text/javascript" src="/assets/site/js/codemirror/mode/html/html.js"></script>
		<script type="text/javascript" src="/assets/site/js/codemirror/mode/css/css.js"></script>
		<script type="text/javascript" src="/assets/site/js/codemirror/mode/javascript/javascript.js"></script>
		<script type="text/javascript" src="/assets/site/js/codemirror/mode/htmlmixed/htmlmixed.js"></script>
	</body>
</html>';

        return $content;
    }

    public static function pageExists(string $pid)
    {
        $stmt = Database::Instance()->prepare("SELECT pageid FROM content_pages WHERE pageid=:pid;");
        $stmt->bindParam(":pid", $pid);
        $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
        return count($stmt->fetchAll()) == 1;
    }

    public static function getTitleFromId(string $pid)
    {
        $stmt = Database::Instance()->prepare("SELECT title FROM content_pages WHERE pageid=:pid;");
        $stmt->bindParam(":pid", $pid);
        $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
        $pages = $stmt->fetchAll();

        if (count($pages) != 1) {
            return "Unknown";
        }

        return urldecode($pages[0]["title"]);
    }

    public static function hook(Request $request)
    {
        $pageid = $request->getEndpoint();

        if (!Page::pageExists($pageid)) {
            $pageid = "_default/notfound";
        }

        $stmt = Database::Instance()->prepare("UPDATE users SET collab_pageid=:pid WHERE uid=:uid;");
        $stmt->bindParam(":pid", $pageid);
        $stmt->bindParam(":uid", User::$currentUser->uid);
        $stmt->execute();

        $page = new Page($pageid);

        return new Response($page->getContent(), false);
    }

    public static function hookNewPage(Request $request)
    {
        if (isset($_POST["s"]) and $_POST["s"] and !User::$currentUser->permissions->page_createsecure) {
            return new Response("FALSE");
        }

        if ((!isset($_POST["s"]) or !$_POST["s"]) and !User::$currentUser->permissions->page_create) {
            return new Response("FALSE");
        }

        $secure = isset($_POST["s"]) and $_POST["s"];
        $npid = ($secure ? "secure/" : "") . "newpage";

        $c = 0;
        $ok = !Page::pageExists($npid);
        while (!$ok) {
            $c++;
            $npid = ($secure ? "secure/" : "") . "newpage" . $c;
            $ok = !Page::pageExists($npid);
        }

        $s = $secure ? "1" : "0";
        $now = date("Y-m-d");

        $stmt = Database::Instance()->prepare("SELECT * FROM content_pages WHERE pageid='_default/page';");
        $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
        $template = $stmt->fetchAll()[0];

        $stmt = Database::Instance()->prepare("INSERT INTO content_pages VALUES (:pageid, :title, :head, :body, :usehead, :usetop, :usebottom, :secure, :now);");
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
        return new Response($npid);
    }

    public static function hookRemovePage(Request $request)
    {
        if (!isset($_POST["pid"]) or !Page::pageExists($_POST["pid"])) {
            return new Response("FALSE");
        }

        $pid = $_POST["pid"];
        if (in_array($pid, ["", "secureaccess"]) || substr($pid, 0, 9) === "_default/") {
            return new Response("SPECIAL");
        }

        if (!User::$currentUser->permissions->admin_managepages) {
            return new Response("FALSE");
        }

        $stmt = Database::Instance()->prepare("DELETE FROM content_pages WHERE pageid=:pid;");
        $stmt->bindParam(":pid", $pid);
        $stmt->execute();
        return new Response("TRUE");
    }

    public static function hookSecurePage(Request $request)
    {
        if (!isset($_POST["state"])) {
            return new Response("FALSE");
        }

        if (!isset($_POST["pid"]) or !Page::pageExists($_POST["pid"])) {
            return new Response("FALSE");
        }

        $pid = $_POST["pid"];
        if (in_array($pid, ["", "secureaccess"]) || substr($pid, 0, 9) === "_default/") {
            return new Response("SPECIAL");
        }

        if (!User::$currentUser->permissions->admin_managepages) {
            return new Response("FALSE");
        }

        $s = $_POST["state"] == "true" ? "1" : "0";
        $now = date("Y-m-d");
        $stmt = Database::Instance()->prepare("UPDATE content_pages SET secure=:secure, revision=:now WHERE pageid=:pid;");
        $stmt->bindParam(":secure", $s);
        $stmt->bindParam(":now", $now);
        $stmt->bindParam(":pid", $pid);
        $stmt->execute();
        return new Response("TRUE");
    }

    public static function hookCheckPid(Request $request)
    {
        if (!isset($_POST["pageid"]) ||
            !isset($_POST["check"])) {
            return new Response("FALSE");
        }
        if ($_POST["check"] != $_POST["pageid"] &&
            Page::pageExists($_POST["check"])) {
            return new Response("FALSE");
        }
        if ($_POST["check"] != $_POST["pageid"] &&
            in_array($_POST["pageid"], ["", "secureaccess"]) || substr($_POST["pageid"], 0, 9) === "_default/") {
            return new Response("FALSE");
        }

        return new Response("TRUE");
    }

    public static function hookEditPage(Request $request)
    {
        if (!isset($_POST["pageid"]) ||
            !isset($_POST["newpageid"]) ||
            !isset($_POST["title"]) ||
            !isset($_POST["usehead"]) ||
            !isset($_POST["head"]) ||
            !isset($_POST["usetop"]) ||
            !isset($_POST["body"]) ||
            !isset($_POST["usebottom"])) {
            return new Response("FALSE");
        }

        if (!Page::pageExists($_POST["pageid"])) {
            return new Response("FALSE");
        }

        $page = new Page($_POST["pageid"]);

        if (!User::$currentUser->permissions->page_edit ||
            ($page->secure && !User::$currentUser->permissions->page_editsecure) ||
            in_array($page->pageid, User::$currentUser->permissions->page_viewblacklist) ||
            in_array($page->pageid, User::$currentUser->permissions->page_editblacklist)) {
            return new Response("FALSE");
        }

        $newpageid = $_POST["newpageid"];

        if (!($newpageid == $page->pageid || !(in_array($page->pageid, ["", "secureaccess"]) || substr($page->pageid, 0, 9) === "_default/"))) {
            return new Response("FALSE");
        }

        if (!(!Page::pageExists($newpageid) || $newpageid == $page->pageid) ||
            in_array($newpageid, ["TRUE", "FALSE"])) {
            return new Response("FALSE");
        }

        $now = date("Y-m-d");
        $stmt = Database::Instance()->prepare("UPDATE content_pages SET pageid=:pageid, title=:title, head=:head, body=:body, usehead=:usehead, usetop=:usetop, usebottom=:usebottom, revision=:now WHERE pageid=:oldpid;");
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
            return new Response("TRUE");
        }

        return new Response($newpageid);
    }
    
    public static function placeholderPageId($args, $func, $request)
    {
        return $request->getEndpoint();
    }
    
    public static function placeholderSitemap() {
        $content = "<ul>";
        $stmt = Database::Instance()->prepare("SELECT pageid, title, secure FROM content_pages WHERE pageid NOT LIKE '_default/%' ORDER BY pageid ASC;");
        $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
        $pdatas = $stmt->fetchAll();
        foreach ($pdatas as $pd) {
            if ($pd["secure"] == "1" and !User::$currentUser->permissions->page_viewsecure or in_array($pd["pageid"], User::$currentUser->permissions->page_viewblacklist)) {
                continue;
            } else {
                $title = urldecode($pd["title"]);
                $content  .= "<li><a href=\"/{$pd["pageid"]}\" title=\"{$title}\">{$title}</a></li>";
            }
        }
        $content .= "</ul>";
        
        return $content;
    }
    
    public static function placeholderPageRevision($args, $func, $request) {
        $pageid = $request->getEndpoint();

        if (!Page::pageExists($pageid)) {
            $pageid = "_default/notfound";
        }

        $page = new Page($pageid);
        
        return $page->revision;
    }
}
