<?php

class module_backup {
	
	public $dependencies = [];
	public $name = "Backup and Restore";
	
	function seed() {
		global $conn, $sqlstat, $sqlerr;
		if (!$this->isSeeded() and $sqlstat) {
		}
	}
	
	function isSeeded() {
		global $conn, $sqlstat, $sqlerr;
		if ($sqlstat) {
			$stmt = $conn->prepare("SHOW TABLES LIKE 'module_backup_permissions';");
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