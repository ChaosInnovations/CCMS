<?php

class module_modulemanager {
	
	public $dependencies = [];
	
	function seed() {
		global $conn, $sqlstat, $sqlerr;
		if (!$this->isSeeded() and $sqlstat) {
		}
	}
	
	function isSeeded() {
		global $conn, $sqlstat, $sqlerr;
		if ($sqlstat) {
			$stmt = $conn->prepare("SHOW TABLES LIKE 'module_modulemanager_permissions';");
			$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
			if (count($stmt->fetchAll()) == 1) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	function __construct() {
		global $conn, $sqlstat, $sqlerr;
		global $page;
		$this->seed();
	}

}

?>