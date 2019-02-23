<?php

namespace Mod;

use \Lib\CCMS\Request;
use \Lib\CCMS\Utilities;
use \Mod\SecureMenu;
use \Mod\SecureMenu\Panel;
use \Mod\User;

class ModuleMenu
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

    private $entries = [];

    public function __construct()
    {

    }

    public function addEntry(string $onclick, string $title)
    {
        $entry = [
            'onclick' => $onclick,
            'title' => $title,
        ];
        array_push($this->entries, $entry);
    }

    public static function hookAddToSecureMenu(Request $request)
    {
        if (!User::$currentUser->permissions->admin_managesite) {
            return;
        }

        $panelTemplate = file_get_contents(dirname(__FILE__) . "/PanelContent.template.html");
        $panelEntryTemplate = file_get_contents(dirname(__FILE__) . "/PanelEntry.template.html");

        $compiledPanelEntries = "";
        foreach(self::Instance()->entries as $entry) {
            $compiledPanelEntries .= Utilities::fillTemplate($panelEntryTemplate, $entry);
        }

        $content = Utilities::fillTemplate($panelTemplate, ['list' => $compiledPanelEntries]);
        
        SecureMenu::Instance()->addEntry("moduleTrigger", "Modules", "triggerPane('module');", '<i class="fas fa-puzzle-piece"></i>', SecureMenu::VERTICAL);
        SecureMenu::Instance()->addPanel(new Panel("module", "Modules", $content, Panel::SLIDE_HORIZONTAL));
    }
}