<?php

namespace Lib\CCMS;

use \Lib\CCMS\Utilities;
use \Mod\Database;
use \Mod\User;
use \PDO;

class Utilities
{
    function load_jsons()
    {
        global $ccms_info;
        $ccms_info = json_decode(file_get_contents($_SERVER["DOCUMENT_ROOT"] . "/assets/server/ccms-info.json", true));
    }
    
    function getconfig($property)
    {
        $stmt = Database::Instance()->prepare("SELECT * FROM config WHERE property=:property;");
        $stmt->bindParam(":property", $property);
        $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
        $result = $stmt->fetchAll();
        if (count($result) != 1) {
            return "";
        }
        return $result[0]["value"];
    }

    function setconfig($property, $value)
    {
        $stmt = Database::Instance()->prepare("UPDATE config SET {$property}=:val WHERE 1=1;");
        $stmt->bindParam(":val", $value);
        $stmt->execute();
        return true;
    }
    
    public static function hookSetConfig(Request $request)
    {
        //api/config/set
        
        if (!User::$currentUser->permissions->managesite) {
            return new Response("FALSE");
        }
        
        if (isset($_POST["websitetitle"])) {
            Utilities::setconfig("websitetitle", $_POST["websitetitle"]);
        }
        if (isset($_POST["primaryemail"])) {
            Utilities::setconfig("primaryemail", $_POST["primaryemail"]);
        }
        return new Response("TRUE");
    }
}