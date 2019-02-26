<?php

namespace Mod;

use \Lib\CCMS\ContentType;
use \Lib\CCMS\Response;
use \Lib\CCMS\Request;
use \Lib\CCMS\Utilities;
use \Mod\Database;
use \Mod\ModuleMenu;
use \Mod\SecureMenu;
use \Mod\SecureMenu\Panel;
use \Mod\User;
use \PDO;

class Page extends ContentType
{
    private static $table = null;
    private static function table() {
        if (!self::$table instanceof ContentType) {
            self::$table = new ContentType("content_pages");
            if (!self::$table->tableExists) {
                $columns = [
                    "`pageid` varchar(255) NOT NULL",
                    "`title` text NOT NULL",
                    "`head` longtext NOT NULL",
                    "`body` longtext NOT NULL",
                    "`usehead` tinyint(1) NOT NULL DEFAULT '1'",
                    "`usetop` tinyint(1) NOT NULL DEFAULT '1'",
                    "`usebottom` tinyint(1) NOT NULL DEFAULT '1'",
                    "`secure` tinyint(1) NOT NULL",
                    "`revision` date NOT NULL",
                    "UNIQUE KEY `pageid` (`pageid`)",
                ];
                self::$table->createTable($columns);
            }
        }
        return self::$table;
    }

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

        $pages = self::table()->getTableEntry("pageid", $this->pageid);

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
        return "{$pre}<title>{$this->title} | {$sitetitle}</title>{$this->head}";
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
        <link rel="stylesheet" href="/core/mod/Collaboration/collaboration-1.0.0.css" media="all">
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
        return self::table()->tableEntryExists("pageid", $pid);
    }

    public static function getTitleFromId(string $pid)
    {
        $pages = self::table()->getTableEntry("pageid", $pid);

        if (count($pages) != 1) {
            return "Unknown";
        }

        return urldecode($pages[0]["title"]);
    }

    public static function hook(Request $request)
    {
        global $baseUrl;
        
        $pageid = $request->getEndpoint();

        if (!Page::pageExists($pageid)) {
            $pageid = "_default/notfound";
        }

        // This should be moved to the Collaboration module
        $stmt = Database::Instance()->prepare("UPDATE users SET collab_pageid=:pid WHERE uid=:uid;");
        $stmt->bindParam(":pid", $pageid);
        $stmt->bindParam(":uid", User::$currentUser->uid);
        $stmt->execute();

        $page = new Page($pageid);
        
        if (User::$currentUser->permissions->page_edit) {
            SecureMenu::Instance()->addEntry("edit", "Edit Page", "showDialog('edit');", '<i class="fas fa-edit"></i>', SecureMenu::VERTICAL);
            $template_vars = [
                'baseUrl' => $baseUrl,
                'useheadChecked' => ($page->usehead ? ' checked' : ''),
                'usetopChecked' => ($page->usetop ? ' checked' : ''),
                'usebottomChecked' => ($page->usebottom ? ' checked' : ''),
                'pageid' => $pageid,
                'rawtitle' => $page->rawtitle,
                'rawhead' => $page->rawhead,
                'rawbody' => $page->rawbody,
            ];
            $editModalBody = Utilities::fillTemplate(file_get_contents(dirname(__FILE__) . "/templates/EditModalBody.template.html"), $template_vars);
            $editModalFooter = file_get_contents(dirname(__FILE__) . "/templates/EditModalFooter.template.html");
            SecureMenu::Instance()->addModal("dialog_edit", "Edit Page", $editModalBody, $editModalFooter);
        }
        if (User::$currentUser->permissions->page_create) {
            SecureMenu::Instance()->addEntry("newpage", "New Page", "createPage();", '<i class="fas fa-plus"></i>', SecureMenu::VERTICAL);
        }
        if (User::$currentUser->permissions->page_viewsecure) {
            SecureMenu::Instance()->addEntry("securepageTrigger", "Secure Pages", "triggerPane('securepage');", '<i class="fas fa-lock"></i>', SecureMenu::VERTICAL);
            $secureListEntryTemplate = file_get_contents(dirname(__FILE__) . "/templates/SecurePagePanelEntry.template.html");
            $securePageList = "";

            $stmt = Database::Instance()->prepare("SELECT pageid FROM content_pages WHERE secure=1 AND pageid NOT LIKE '_default/%';");
            $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
            $ps = $stmt->fetchAll();
            foreach ($ps as $p) {
                if (!in_array($p["pageid"], User::$currentUser->permissions->page_viewblacklist)) {
                    $template_vars = [
                        'url' => "/" . $p["pageid"],
                        'title' => Page::getTitleFromId($p["pageid"]),
                    ];
                    $securePageList .= Utilities::fillTemplate($secureListEntryTemplate, $template_vars);
                }
            }
            $template_vars = [
                'create' => "",
                'list' => $securePageList,
            ];
            if (User::$currentUser->permissions->page_createsecure) {
                $template_vars['create'] = file_get_contents(dirname(__FILE__) . "/templates/SecurePagePanelCreate.template.html");
            }
            $panelContent = Utilities::fillTemplate(file_get_contents(dirname(__FILE__) . "/templates/SecurePagePanel.template.html"), $template_vars);
            SecureMenu::Instance()->addPanel(new Panel("securepage", "Secure Pages", $panelContent, Panel::SLIDE_HORIZONTAL));
        }

        if (User::$currentUser->permissions->admin_managepages) {
            $pageListEntryTemplate = file_get_contents(dirname(__FILE__) . "/templates/PagesModalEntry.template.html");
            $pageListEntryToolsTemplate = file_get_contents(dirname(__FILE__) . "/templates/PagesModalEntryTools.template.html");
            $compiledPageList = "";

            $stmt = Database::Instance()->prepare("SELECT pageid, title, secure, revision FROM content_pages ORDER BY pageid ASC;");
            $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
            $pages = $stmt->fetchAll();
            foreach ($pages as $pageData) {
                $tools = "";
                if (!in_array($pageData["pageid"], ["home", "secureaccess"])) {
                    $toolsTempateVars = [
                        'pageid' => $pageData["pageid"],
                        'checked' => ($pageData["secure"] ? ' checked' : ''),
                    ];
                    $tools = Utilities::fillTemplate($pageListEntryToolsTemplate, $toolsTempateVars);
                }
                $template_vars = [
                    'pageid' => $pageData["pageid"],
                    'title' => urldecode($pageData["title"]),
                    'revisiondate' => date("l, F j, Y", strtotime($pageData["revision"])),
                    'tools' => $tools,
                ];
                $compiledPageList .= Utilities::fillTemplate($pageListEntryTemplate, $template_vars);
            }

            $template_vars = [
                'pagelist' => $compiledPageList,
            ];
            $pageListBody = Utilities::fillTemplate(file_get_contents(dirname(__FILE__) . "/templates/PagesModalBody.template.html"), $template_vars);
            SecureMenu::Instance()->addModal("dialog_pages", "Manage Pages", $pageListBody, "");
            ModuleMenu::Instance()->addEntry("showDialog('pages');", "Manage Pages");
        }
        
        SecureMenu::Instance()->addEntry("home", "Home", "location.assign('/');", '<i class="fas fa-home"></i>', SecureMenu::HORIZONTAL);

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

        $template = self::table()->getTableEntry("pageid", "_default/page")[0];

        $values = [
            $npid,
            $template["title"],
            $template["head"],
            $template["body"],
            $template["usehead"],
            $template["usetop"],
            $template["usebottom"],
            $now,
            $s,
        ];

        self::table()->addEntry($values);

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

        self::table()->dropEntry("pageid", $pid);
        
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
