<?php

use \Mod\Database;

class builtin_placeholders {
	public $dependencies = [];
    
	function place_sitemap() {
        $content = "<ul>";
        $stmt = Database::Instance()->prepare("SELECT pageid, title, secure FROM content_pages WHERE pageid NOT LIKE '_default/%' ORDER BY pageid ASC;");
        $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
        $pdatas = $stmt->fetchAll();
        foreach ($pdatas as $pd) {
            if ($pd["secure"] == "1" and !User::$currentUser->permissions->page_viewsecure or in_array($pd["pageid"], User::$currentUser->permissions->page_viewblacklist)) {
                continue;
            } else {
                $title = urldecode($pd["title"]);
                $content  .= "<li><a href=\"/{$pd["pageid"]}\" title=\"{$title}\">{$title}</a></li>";
            }
        }
        $content .= "</ul>";
        
		return $content;
	}
	
	function place_pagerevision() {
		global $page;
		return $page->revision;
	}
}

?>