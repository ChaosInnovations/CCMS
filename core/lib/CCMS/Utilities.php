<?php

namespace Lib\CCMS;

use \Mod\Database;
use \PDO;

class Utilities
{
    function load_jsons()
    {
        global $db_config, $ccms_info;
        $db_config = json_decode(file_get_contents($_SERVER["DOCUMENT_ROOT"] . "/assets/server/db-config.json", true));
        $ccms_info = json_decode(file_get_contents($_SERVER["DOCUMENT_ROOT"] . "/assets/server/ccms-info.json", true));
    }
    
    function getconfig($property)
    {
        global $conn, $sqlstat, $sqlerr;
        if ($sqlstat) {
            $stmt = Database::Instance()->prepare("SELECT * FROM config WHERE property=:property;");
            $stmt->bindParam(":property", $property);
            $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
            $result = $stmt->fetchAll();
            if (count($result) != 1) {
                return "";
            }
            return $result[0]["value"];
        } else {
            return $sqlerr;
        }
    }

    function setconfig($property, $value)
    {
        global $conn, $sqlstat, $sqlerr;
        if ($sqlstat) {
            $stmt = $conn->prepare("UPDATE config SET {$property}=:val WHERE 1=1;");
            $stmt->bindParam(":val", $value);
            $stmt->execute();
            return true;
        } else {
            return $sqlerr;
        }
    }
}