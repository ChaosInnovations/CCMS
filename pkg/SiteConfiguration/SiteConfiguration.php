<?php

namespace Package;

use \Package\CCMS\Response;
use \Package\CCMS\Request;
use \Package\Database;
use \Package\User;
use \PDO;

class SiteConfiguration
{
    public static function getconfig($property)
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

    public static function setconfig($property, $value)
    {
        $stmt = Database::Instance()->prepare("UPDATE config SET {$property}=:val WHERE 1=1;");
        $stmt->bindParam(":val", $value);
        $stmt->execute();
        return true;
    }
    
    public static function hookSetConfig(Request $request)
    {
        //api/siteconfiguration/set
        if (!User::$currentUser->permissions->managesite) {
            return new Response("FALSE");
        }
        
        if (isset($_POST["websitetitle"])) {
            self::setconfig("websitetitle", $_POST["websitetitle"]);
        }
        if (isset($_POST["primaryemail"])) {
            self::setconfig("primaryemail", $_POST["primaryemail"]);
        }
        return new Response("TRUE");
    }
}
