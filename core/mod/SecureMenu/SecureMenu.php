<?php

namespace Mod;

use \Lib\CCMS\Request;
use \Lib\CCMS\Response;
use \Lib\CCMS\Utilities;
use \Mod\Page;
use \Mod\SecureMenu\Panel;
use \Mod\SiteConfiguration;
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
    /** @var Panel[] $panels description */
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

    public function addPanel(Panel $panel)
    {
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

    public static function hookAboutMenu(Request $request)
    {
        $ccms_info = parse_ini_file($_SERVER["DOCUMENT_ROOT"] . "/core/ccms-info.ini");

        $template_vars =[
            'version' => $ccms_info["version"],
            'releasedate' => date("l, F j, Y", strtotime($ccms_info["release"])),
            'authoremail' => $ccms_info["a_email"],
            'authorname' => $ccms_info["author"],
            'ccmswebsite' => $ccms_info["website"],
            'creationdate' => date("l, F j, Y", strtotime(SiteConfiguration::getconfig("creationdate"))),
        ];
        $aboutModalBody = Utilities::fillTemplate(file_get_contents(dirname(__FILE__) . "/templates/AboutModal.template.html"), $template_vars);
        SecureMenu::Instance()->addModal("dialog_about", "About", $aboutModalBody, "");
        SecureMenu::Instance()->addEntry("about", "About", "showDialog('about');", '<i class="fas fa-question"></i>', SecureMenu::VERTICAL);
    }
    
    public static function hook(Request $request)
    {
        if (!User::$currentUser->permissions->toolbar) {
            return;
        }
        
        $secureMenuTemplate = file_get_contents(dirname(__FILE__) . "/templates/SecureMenu.template.html");
        $secureMenuEntryTemplate = file_get_contents(dirname(__FILE__) . "/templates/SecureMenuEntry.template.html");
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
            $compiledPanels .= $panel->getCompiledPanel();
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

        return new Response($menu, false);
    }
}