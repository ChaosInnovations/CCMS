<?php

// Still need comment and voting implementations

class module_blog {
	
	public $dependencies = [];
	
	function seed() {
		global $conn, $sqlstat, $sqlerr;
		if (!$this->isSeeded() and $sqlstat) {
			$conn->exec("CREATE TABLE `module_blog_permissions` (`uid` char(32) NOT NULL, `permissions` bit(4) NOT NULL DEFAULT b'0');");
			$conn->exec("CREATE TABLE `module_blog_content` (`pid` char(32) NOT NULL, `author` CHAR(32) NOT NULL, `posted` DATETIME NOT NULL, `editor` CHAR(32) NULL, `edited` DATETIME NULL, `content` MEDIUMTEXT NULL, `voting` BOOLEAN NOT NULL DEFAULT TRUE, `comments` BOOLEAN NOT NULL DEFAULT TRUE);");
		}
	}
	
	function isSeeded() {
		global $conn, $sqlstat, $sqlerr;
		if ($sqlstat) {
			$stmt = $conn->prepare("SHOW TABLES LIKE 'module_blog_permissions';");
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
	
	function GetRecentPosts($number, $start=0) {
		global $conn, $sqlstat, $sqlerr;
		$results = array();
		if ($sqlstat) {
			$stmt = $conn->prepare("SELECT * FROM `module_blog_content` ORDER BY `posted` DESC LIMIT " . $start . "," . $number . ";");
			$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
			foreach ($stmt->fetchAll() as $result) {
				$entry = new module_blog_entry();
				$entry->read_from_database_row($result);
				array_push($results, $entry);
			}
		}
		return $results;
	}
	
	function viewmini() {
		$entries = $this->GetRecentPosts(3);
		$html = "<div><style scoped=\"scoped\"></style>";
		if (count($entries) > 0) {
			foreach ($entries as $entry) {
				$html .= $entry->get_view_listpreview();
			}
		} else {
			$html .= "<h3>No blog entries to show!</h3>";
		}
		$html .= "</div>";
		return $html;
	}

}

class module_blog_entry {
	
	private $content_raw = "";
	private $content = "";
	private $content_preview = "";
	private $author = "";
	private $author_name = "";
	private $posted_on = "";
	private $editor = "";
	private $editor_name = "";
	private $edited_on = "";
	private $voting = false;
	private $comments = false;
	
	function __construct() {
		global $conn, $sqlstat, $sqlerr;
	}
	
	function read_from_database($pid) {
		
	}
	
	function read_from_database_row($data) {
		if ($data != null) {
			$this->author = $data["author"];
			$this->posted_on = $data["posted"];
			$this->editor = $data["editor"];
			$this->edited_on = $data["edited"];
			$this->voting = $data["voting"] == "1";
			$this->comments = $data["comments"] == "1";
			$this->content_raw = $data["content"];
			
			$this->content = urldecode($this->content_raw);
			$this->content_preview = substr($this->content, 0, 100);
			if (strlen($this->content_preview) < strlen($this->content)) {
				$this->content_preview .= "...";
			}
			
			$this->author_name = nameOfUser($this->author);
			$this->posted_on = date("l, F j, Y \\a\\t H:m a", strtotime($this->posted_on));
		}
	}
	
	function get_view_full() {
		
	}
	
	function get_view_part() {
		
	}
	
	function get_view_preview() {
		
	}
	
	function get_view_card() {
		
	}
	
	function get_view_headline() {
		
	}
	
	function get_view_listpreview() {
		$html  = "<div class=\"blog-post listpreview\">";
		$html .= "<span class=\"blog-post postinfo\">";
		$html .= "Posted by {$this->author_name} on {$this->posted_on}.";
		$html .= "</span><blockquote>{$this->content_preview}</blockquote></div>";
		return $html;
	}
	
}

?>