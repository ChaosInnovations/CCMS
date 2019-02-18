<?php

namespace Mod;

use \Lib\CCMS\Response;
use \Lib\CCMS\Request;
use \PDO;
use \PDOException;

class Database extends PDO
{
    // Singleton pattern
    private static $instance = null;
    
    public static function Instance()
    {
        if (!self::$instance instanceof Database) {
            self::$instance = new static();
        }
        
        return self::$instance;
    }
    
    
    private $connectionOpen = false;
    private $connectionStatus = "";
    private $connection = null;
    
    public function __construct()
    {
        global $db_config;
        
        try {
            parent::__construct("mysql:host=" . $db_config->host . ";dbname=" . $db_config->database, $db_config->user, $db_config->pass);
            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connectionOpen = true;
        } catch(PDOException $e) {
            $this->connectionStatus = $e->getMessage();
        }
    }
    
    public function isConnectionOpen()
    {
        return $this->connectionOpen;
    }
    
    public function getConnectionStatus()
    {
        return $this->connectionStatus;
    }
    
    
    public static function hookOpenConnection(Request $request)
    {
        $instance = self::Instance();
        
        if (!$instance->isConnectionOpen()) {
            return new Response("No database connection. Reason:\n" . $instance->getConnectionStatus());
        }
    }
}