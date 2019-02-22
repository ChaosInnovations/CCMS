<?php

namespace Mod;

use \Lib\CCMS\Request;
use \Lib\CCMS\Response;
use \Lib\CCMS\Utilities;
use \Mod\Page;
use \Mod\User;
use \PDO;

class SecureMenu
{
    // Singleton pattern
    private static $instance = null;
    
    /**
     * @return self
     **/
    public static function Instance()
    {
        if (!self::$instance instanceof static) {
            self::$instance = new static();
        }
        
        return self::$instance;
    }
    
    const HORIZONTAL = 0;
    const VERTICAL = 1;
    
    private $horizontalEntries = [];
    private $verticalEntries = [];
    private $panels = [];
    private $modals = [];
    
    private function __construct()
    {
    }
    
    public function addEntry(string $id, string $title, string $function, $icon="", $side=self::HORIZONTAL)
    {
        $entry = [
            'id' => $id,
            'title' => $title,
            'onclick' => $function,
            'content' => $icon,
        ];
        
        switch ($side) {
            case (self::HORIZONTAL):
                array_push($this->horizontalEntries, $entry);
                break;
            case (self::VERTICAL):
                array_push($this->verticalEntries, $entry);
                break;
        }
    }

    public function addPanel(string $id, string $title, string $content, $direction=self::HORIZONTAL)
    {
        $panel = [
            'id' => $id,
            'title' => $title,
            'content' => $content,
            'direction' => ($direction == self::HORIZONTAL ? "horizontal" : "vertical"),
        ];

        array_push($this->panels, $panel);
    }
    
    public function addModal(string $id, string $title, string $body, string $footer)
    {
        $modal = [
            'id' => $id,
            'title' => $title,
            'body' => $body,
            'footer' => $footer,
        ];
        
        array_push($this->modals, $modal);
    }
    
    public static function hook(Request $request)
    {
        global $TEMPLATES, $availablemodules, $modules;
        
        if (!User::$currentUser->permissions->toolbar) {
            return;
        }
        
        $secureMenuTemplate = file_get_contents(dirname(__FILE__) . "/templates/SecureMenu.template.html");
        $secureMenuEntryTemplate = file_get_contents(dirname(__FILE__) . "/templates/SecureMenuEntry.template.html");
        $secureMenuPanelTemplate = file_get_contents(dirname(__FILE__) . "/templates/SecureMenuPanel.template.html");
        $secureMenuModalTemplate = file_get_contents(dirname(__FILE__) . "/templates/SecureMenuModal.template.html");
        
        $compiledHorizontalEntries = "";
        $compiledVerticalEntries = "";
        $compiledPanels = "";
        $compiledModals = "";
        
        $reversedHorizontalEntries = array_reverse(self::Instance()->horizontalEntries);
        foreach($reversedHorizontalEntries as $entry) {
            $compiledHorizontalEntries .= Utilities::fillTemplate($secureMenuEntryTemplate, $entry);
        }
        
        $reversedVerticalEntries = array_reverse(self::Instance()->verticalEntries);
        foreach($reversedVerticalEntries as $entry) {
            $compiledVerticalEntries .= Utilities::fillTemplate($secureMenuEntryTemplate, $entry);
        }

        foreach(self::Instance()->panels as $panel) {
            $compiledPanels .= Utilities::fillTemplate($secureMenuPanelTemplate, $panel);
        }

        foreach(self::Instance()->modals as $modal) {
            $compiledModals .= Utilities::fillTemplate($secureMenuModalTemplate, $modal);
        }
        
        $template_vars = [
            'horizontalEntries' => $compiledHorizontalEntries,
            'verticalEntries' => $compiledVerticalEntries,
            'panels' => $compiledPanels,
            'modals' => $compiledModals,
        ];
        $menu = Utilities::fillTemplate($secureMenuTemplate, $template_vars);
        
        //return new Response($menu, false);
        
        $modals = "<div id=\"modals\">";
        $script = "<script>";
        $secure = "";

        $secure .= $TEMPLATES["secure-menu"]();

        // Page manager menu
        if (User::$currentUser->permissions->admin_managepages) {
            $stmt = Database::Instance()->prepare("SELECT * FROM users;");
            $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
            $users = $stmt->fetchAll();

            $modals .= $TEMPLATES["secure-modal-start"]("dialog_admin", "Administration", "lg");
            $modals .= $TEMPLATES["secure-modal-admin-bodyfoot"]($users);
            $script .= $TEMPLATES["secure-modal-admin-script"]();
            $modals .= $TEMPLATES["secure-modal-end"];
        }

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

        $menu = $menu . $modals . $script;

        return new Response($menu, false);
    }
}