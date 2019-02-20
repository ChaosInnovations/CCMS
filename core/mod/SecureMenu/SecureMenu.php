<?php

namespace Mod;

use \Lib\CCMS\Request;
use \Lib\CCMS\Response;
use \Mod\Page;
use \Mod\User;
use \PDO;

class SecureMenu
{
    public static function hook(Request $request)
    {
        global $TEMPLATES, $availablemodules, $modules;

        $securepages = [];
        $stmt = Database::Instance()->prepare("SELECT pageid FROM content_pages WHERE secure=1 AND pageid NOT LIKE '_default/%';");
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $ps = $stmt->fetchAll();
        foreach ($ps as $p) {
            array_push($securepages, $p["pageid"]);
        }
        
        $modals = "<div id=\"modals\">";
        $script = "<script>";
        $secure = "";

        $secure .= $TEMPLATES["secure-menu"]($securepages, $availablemodules, $modules);

        $pageid = $request->getEndpoint();

        if (!Page::pageExists($pageid)) {
            $pageid = "_default/notfound";
        }
        
        $currentPage = new Page($pageid);
        
        // Edit menu
        if (($currentPage->secure ? User::$currentUser->permissions->page_editsecure : User::$currentUser->permissions->page_edit) &&
            (!in_array($currentPage->pageid, User::$currentUser->permissions->page_editblacklist))) {
            $modals .= $TEMPLATES["secure-modal-start"]("dialog_edit", "Edit Page", "lg");
            $modals .= $TEMPLATES["secure-modal-edit-bodyfoot"]($currentPage);
            $modals .= $TEMPLATES["secure-modal-end"];
            $script .= $TEMPLATES["secure-modal-edit-script"](
                $currentPage->pageid,
                $currentPage->rawtitle,
                $currentPage->rawhead,
                $currentPage->rawbody
            );
        }

        // Page manager menu
        if (User::$currentUser->permissions->admin_managepages) {
            $stmt = Database::Instance()->prepare("SELECT pageid, title, secure, revision FROM content_pages ORDER BY pageid ASC;");
            $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
            $pages = $stmt->fetchAll();

            $stmt = Database::Instance()->prepare("SELECT * FROM users;");
            $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
            $users = $stmt->fetchAll();

            $modals .= $TEMPLATES["secure-modal-start"]("dialog_admin", "Administration", "lg");
            $modals .= $TEMPLATES["secure-modal-admin-bodyfoot"]($pages, $users);
            $script .= $TEMPLATES["secure-modal-admin-script"]();
            $modals .= $TEMPLATES["secure-modal-end"];
        }

        // Account menu
        $modals .= $TEMPLATES["secure-modal-start"]("dialog_account", "Account Details", "lg");
        $modals .= $TEMPLATES["secure-modal-account-bodyfoot"]();
        $modals .= $TEMPLATES["secure-modal-end"];
        $script .= $TEMPLATES["secure-modal-account-script"];

        // Module menus
        if (User::$currentUser->permissions->admin_managesite) {
            /*
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
            */
        }

        $modals .= "</div>";
        $script .= "</script>";

        $menu = $secure . $modals . $script;

        return new Response($menu, false);
    }
}