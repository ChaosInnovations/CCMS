<?php

namespace Package;

use \Package\CCMS\Response;
use \Package\CCMS\Request;
use \Package\CCMS\Utilities;
use \Package\ContentType;
use \Package\Database;
use \Package\ModuleMenu;
use \Package\SecureMenu;
use \Package\SecureMenu\Panel;
use \Package\SiteConfiguration;
use \Package\User;
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

                $db = Database::Instance();

                $dbTemplate = file_get_contents(dirname(__FILE__) . "/templates/database.template.sql");

                $stmt = $db->prepare($dbTemplate);
                $stmt->execute();
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
        $this->title = base64_decode($this->rawtitle);
        $this->head = base64_decode($this->rawhead);
        $this->body = base64_decode($this->rawbody);
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
        $sitetitle = SiteConfiguration::getconfig("websitetitle");
        return "{$pre}<title>{$this->title} | {$sitetitle}</title>{$this->head}";
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

        return base64_decode($pages[0]["title"]);
    }

    public static function hookHeadOpen(Request $request)
    {
        $content = file_get_contents(dirname(__FILE__) . "/templates/PageHeadOpen.template.html");

        return new Response($content, false);
    }

    public static function hookMain(Request $request)
    {
        $pageid = $request->getEndpoint();

        if (!Page::pageExists($pageid)) {
            $pageid = "_default/notfound";
        }

        $page = new Page($pageid);

        $page->body = $page->getTop() . $page->body . $page->getBottom();

        $template_vars = [
            'baseUrl' => $request->baseUrl,
            'head' => $page->insertHead(),
            'body' => $page->body,
        ];
        $content = Utilities::fillTemplate(file_get_contents(dirname(__FILE__) . "/templates/PageMain.template.html"), $template_vars);

        return new Response($content, false);
    }

    public static function hookBodyClose(Request $request)
    {
        $content = file_get_contents(dirname(__FILE__) . "/templates/PageBodyClose.template.html");

        return new Response($content, false);
    }

    public static function hookMenu(Request $request)
    {
        $pageid = $request->getEndpoint();

        if (!Page::pageExists($pageid)) {
            $pageid = "_default/notfound";
        }

        $page = new Page($pageid);
        
        if (User::$currentUser->permissions->page_edit) {
            SecureMenu::Instance()->addEntry("edit", "Edit Page", "showDialog('edit');", '<i class="fas fa-edit"></i>', SecureMenu::VERTICAL);
            $template_vars = [
                'baseUrl' => $request->baseUrl,
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
                if (!in_array($pageData["pageid"], ["", "admin"]) && substr($pageData["pageid"], 0, 9) !== "_default/") {
                    $toolsTempateVars = [
                        'pageid' => $pageData["pageid"],
                        'checked' => ($pageData["secure"] ? ' checked' : ''),
                    ];
                    $tools = Utilities::fillTemplate($pageListEntryToolsTemplate, $toolsTempateVars);
                } else {
                    $tools = "</td><td>";
                }
                $template_vars = [
                    'pageid' => $pageData["pageid"],
                    'title' => base64_decode($pageData["title"]),
                    'revisiondate' => date("l, F j, Y", strtotime($pageData["revision"])),
                    'tools' => $tools,
                ];
                $compiledPageList .= Utilities::fillTemplate($pageListEntryTemplate, $template_vars);
            }

            $template_vars = [
                'pagelist' => $compiledPageList,
            ];
            $pageListBody = Utilities::fillTemplate(file_get_contents(dirname(__FILE__) . "/templates/PagesModalBody.template.html"), $template_vars);
            $pageListFooter = file_get_contents(dirname(__FILE__) . "/templates/PagesModalFooter.template.html");
            SecureMenu::Instance()->addModal("dialog_pages", "Manage Pages", $pageListBody, $pageListFooter);
            ModuleMenu::Instance()->addEntry("showDialog('pages');", "Manage Pages");
        }
        
        SecureMenu::Instance()->addEntry("home", "Home", "location.assign('/');", '<i class="fas fa-home"></i>', SecureMenu::HORIZONTAL);
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

        $c = 1;
        while (Page::pageExists($npid)) {
            $npid = ($secure ? "secure/" : "") . "newpage" . $c++;
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
            $s,
            $now,
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
        if (in_array($pid, ["", "admin"]) || substr($pid, 0, 9) === "_default/") {
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
        if (in_array($pid, ["", "admin"]) || substr($pid, 0, 9) === "_default/") {
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
            in_array($_POST["pageid"], ["", "admin"]) || substr($_POST["pageid"], 0, 9) === "_default/") {
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

        if (!($newpageid == $page->pageid || !(in_array($page->pageid, ["", "admin"]) || substr($page->pageid, 0, 9) === "_default/"))) {
            return new Response("FALSE");
        }

        if (!(!Page::pageExists($newpageid) || $newpageid == $page->pageid) ||
            in_array($newpageid, ["TRUE", "FALSE"])) {
            return new Response("FALSE");
        }

        $title=base64_encode(urldecode($_POST["title"]));
        $head=base64_encode(urldecode($_POST["head"]));
        $body=base64_encode(urldecode($_POST["body"]));        

        $now = date("Y-m-d");
        $stmt = Database::Instance()->prepare("UPDATE content_pages SET pageid=:pageid, title=:title, head=:head, body=:body, usehead=:usehead, usetop=:usetop, usebottom=:usebottom, revision=:now WHERE pageid=:oldpid;");
        $stmt->bindParam(":pageid", $newpageid);
        $stmt->bindParam(":oldpid", $page->pageid);
        $stmt->bindParam(":title", $title);
        $stmt->bindParam(":usehead", $_POST["usehead"]);
        $stmt->bindParam(":head", $head);
        $stmt->bindParam(":usetop", $_POST["usetop"]);
        $stmt->bindParam(":body", $body);
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
                $title = base64_decode($pd["title"]);
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
