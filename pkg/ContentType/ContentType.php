<?php

namespace Package;

use Package\Database;
use \PDO;

class ContentType
{
    private $tableName = "";
    protected $tableExists = false;
    private $db = null;

    protected function __construct($tableName) {
        $this->tableName = $tableName;
        $this->db = new Database();

        $tables = $this->db->execute("SHOW TABLES LIKE '{$tableName}'");
        $this->tableExists = count($tables) === 1;
    }

    protected function getTableStructure() {
        if (!$this->tableExists) {
            return "";
        }
        return $this->db->execute("SHOW CREATE TABLE {$this->tableName};")[0]["Create Table"];
    }

    protected function createTable($columns) {
        $columnList = implode(",", $columns);
        $this->db->execute("CREATE TABLE IF NOT EXISTS `{$this->tableName}` ({$columnList});");
        
        $tables = $this->db->execute("SHOW TABLES LIKE '{$this->tableName}'");
        $this->tableExists = count($tables) === 1;
    }

    protected function getTableEntry($column, $match) {
        $stmt = $this->db->prepare("SELECT * FROM `{$this->tableName}` WHERE `{$column}`=:match;");
        $stmt->bindParam(":match", $match);
        $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
        return $stmt->fetchAll();
    }

    protected function tableEntryExists($column, $match) {
        return count($this->getTableEntry($column, $match)) === 1;
    }

    protected function addEntry($values) {
        $keys = ":" . implode(",:", array_keys($values));
        $stmt = Database::Instance()->prepare("INSERT INTO `{$this->tableName}` VALUES ({$keys});");
        foreach ($values as $key => &$value) {
            $stmt->bindParam(":{$key}", $value);
        }
        $stmt->execute();
    }

    protected function dropEntry($column, $match) {
        $stmt = $this->db->prepare("DELETE FROM `{$this->tableName}` WHERE `{$column}`=:match;");
        $stmt->bindParam(":match", $match);
        $stmt->execute();
    }
}