<?php

namespace Mod;

use \Lib\CCMS\Request;
use \Mod\ModuleMenu;
use \Mod\SecureMenu;

class ModuleManager {
    public static function hookMenu(Request $request) {
        SecureMenu::Instance()->addModal("dialog_modules", "Manage Modules", "", "");
        ModuleMenu::Instance()->addEntry("showDialog('modules');", "Manage Modules");
    }
}