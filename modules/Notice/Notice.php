<?php

namespace Mod;

use \Lib\CCMS\Response;
use \Lib\CCMS\Request;
use \Lib\CCMS\Utilities;
use \Lib\ContentType;
use \Mod\Database;
use \Mod\ModuleMenu;
use \Mod\SecureMenu;
use \Mod\User;
use \PDO;

class Notice extends ContentType
{
    private static $table = null;
    private static function table() {
        if (!self::$table instanceof ContentType) {
            self::$table = new ContentType("module_notice");
            if (!self::$table->tableExists) {
                $columns = [
                    "`noticeid` varchar(128) NOT NULL",
                    "`level` tinytext NOT NULL",
                    "`content` text NOT NULL",
                    "`target` text NOT NULL",
                    "`canclose` tinyint(1) NOT NULL",
                    "`lastsfor` smallint NOT NULL",
                    "`publish` date NOT NULL",
                    "`expire` date NOT NULL",
                    "UNIQUE KEY `noticeid` (`noticeid`)",
                ];
                self::$table->createTable($columns);
            }
        }
        return self::$table;
    }

    public $noticeid = "";
    public $level = "info";
    public $content = "";
    public $canclose = true;
    public $lastsfor = 0;
    public $publish = "";
    public $publishTme = "";
    public $expire = 0;
    public $expireTme = "";

    public function __construct(string $notice_id)
    {
        $this->noticeid = $notice_id;

        $this->level = "urgent";
        $this->content = "Something got messy on our server! We should have it fixed soon.";
        $this->publish = date("l, F j, Y", time());
        $this->expire = time()+3600*24*30;

        $notices = self::table()->getTableEntry("noticeid", $this->noticeid);

        if (count($notices) != 1) {
            return;
        }

        $ndata = $notices[0];
        $this->level = $ndata["level"];
        $this->content = base64_decode($ndata["content"]);
        $this->canclose = $ndata["canclose"];
        $this->lastsfor = $ndata["lastsfor"];
        $this->publish = date("l, F j, Y", strtotime($ndata["publish"]));
        $this->publishTme = $ndata["publish"];
        $this->expire = strtotime($ndata["expire"]);
        $this->expireTme = $ndata["expire"];
    }

    public function compile()
    {
        $c = "";
		if ($this->canclose) {
			$c = "<button class=\"close\" data-dismiss=\"alert\">&times;</button>";
		}
		$lvl = ($this->level == "urgent" ? "danger" : ($this->level == "warning" ? "warning" : ($this->level == "success" ? "success" : "primary")));
		$cmp  = "<div id=\"notice-{$this->noticeid}\" class=\"alert alert-{$lvl}\">";
		$cmp .= $c;
		$cmp .= $this->content;
		$cmp .= "<hr /><p class=\"mb-0\"><i>Notice published on {$this->publish}</i></p>";
		$cmp .= "</div>";
		return $cmp;
    }

    public static function noticeExists($notice_id)
    {
        $notices = self::table()->getTableEntry("noticeid", $notice_id);
        return count($notices) == 1;
    }

    public static function hookMenu(Request $request)
    {
        $garbage = self::table();

        if (!User::$currentUser->permissions->owner) {
            return;
        }

        $noticelist = "";
		$stmt = Database::Instance()->prepare("SELECT noticeid, target FROM module_notice ORDER BY publish ASC;");
		$stmt->execute();$stmt->setFetchMode(\PDO::FETCH_ASSOC);
		$nids = $stmt->fetchAll();
		foreach ($nids as $n) {
			$notice = new Notice($n["noticeid"]);
			$cnt = htmlspecialchars(substr($notice->content, 0, 40) . (strlen($notice->content) > 40 ? "..." : ""));
			$lvl = ucfirst($notice->level);
			$dis = $notice->canclose ? "Yes" : "No";
			$persist = $notice->lastsfor == 0 ? "Always" : ($notice->lastsfor . " visit" . ($notice->lastsfor != 1 ? "s" : ""));
			$pub = $notice->publishTme;
			$exp = $notice->expireTme;
			$del = '<button class="btn btn-outline-danger" title="Delete Notice" onclick="dialog_module_notice_delete(\'' . $notice->noticeid . '\');"><i class="fas fa-trash"></i></button>';
			$noticelist .= "<tr><td>{$notice->noticeid}</td><td>{$n["target"]}</td><td><pre><code>{$cnt}</code></pre></td><td>{$lvl}</td><td>{$dis}</td><td>{$persist}</td><td>{$pub}</td><td>{$exp}</td><td>{$del}</td></tr>";
		}

        $template_vars = [
            'publishdate' => date("Y-m-d"),
            'expiredate' => date("Y-m-d", strtotime("+1 week")),
            'noticelist' => $noticelist,
        ];
        $noticeListBody = Utilities::fillTemplate(file_get_contents(dirname(__FILE__) . "/templates/NoticesModalBody.template.html"), $template_vars);
        SecureMenu::Instance()->addModal("dialog_module_notice", "Manage Notices", $noticeListBody, "");
        ModuleMenu::Instance()->addEntry("showDialog('module_notice');", "Manage Notices");
    }

    public static function hookCheckNid(Request $request)
    {
        // api/notice/checknid
        if (!User::$currentUser->permissions->owner || !isset($_POST["nid"])) {
            return new Response("FALSE");
        }

        if (Notice::noticeExists($_POST["nid"])) {
            return new Response("FALSE");
        }

        return new Response("TRUE");
    }

    public static function hookNew(Request $request)
    {
        // api/notice/new
        if (!User::$currentUser->permissions->owner) {
            return new Response("FALSE");
        }

        if (!isset($_POST["nid"]) || !isset($_POST["target"]) || !isset($_POST["level"]) ||
            !isset($_POST["dismiss"]) || !isset($_POST["persist"]) || !isset($_POST["pub"]) ||
            !isset($_POST["exp"]) || !isset($_POST["content"])) {
            return new Response("FALSE");
        }

        if (Notice::noticeExists($_POST["nid"])) {
            return new Response("FALSE");
        }

        $pub = str_replacE("T", " ", $_POST["pub"]);
        $exp = str_replacE("T", " ", $_POST["exp"]);
        $dis = $_POST["dismiss"] === "true";

        $values = [
            $_POST["nid"],
            $_POST["level"],
            base64_encode(urldecode($_POST["content"])),
            $_POST["target"],
            $dis,
            $_POST["persist"],
            $pub,
            $exp,
        ];

        self::table()->addEntry($values);
        return new Response("TRUE");
    }

    public static function hookDelete(Request $request)
    {
        // api/notice/delete
        if (!User::$currentUser->permissions->owner || !isset($_POST["nid"])) {
            return new Response("FALSE");
        }

        if (!Notice::noticeExists($_POST["nid"])) {
            return new Response("FALSE");
        }

        self::table()->dropEntry("noticeid", $_POST["nid"]);
        return new Response("TRUE");
    }
    
    public static function placeholderShowNotices($args, $func, $request)
    {
        $notices = [];
        $content = "<div id=\"module_notice_outer\" class=\"container-fluid\"><div id=\"module_notice_inner\">";

        $pageid = $request->getEndpoint();

        $stmt = Database::Instance()->prepare("SELECT noticeid FROM module_notice WHERE (publish<=:now AND expire>:now) AND (target=:pid OR target='*');");
		$now = date("Y-m-d");
		$stmt->bindParam(":now", $now);
		$stmt->bindParam(":pid", $pageid);
		$stmt->execute();$stmt->setFetchMode(\PDO::FETCH_ASSOC);
		$nids = $stmt->fetchAll();
		foreach ($nids as $n) {
            $notice = new Notice($n["noticeid"]);
            if (!isset($_COOKIE["notice-{$notice->noticeid}"])) {
                $content .= $notice->compile();
                setcookie("notice-{$notice->noticeid}", 1, $notice->expire);
                continue;
            }

            if ($notice->lastsfor != 0 && $_COOKIE["notice-{$notice->noticeid}"] >= $notice->lastsfor) {
                continue;
            }

            setcookie("notice-{$notice->noticeid}", $_COOKIE["notice-{$notice->noticeid}"] + 1, $notice->expire);
            $content .= $notice->compile();
		}

        return $content;
    }
}
