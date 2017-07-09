<?php

class module_transitmanager {
	
	public $dependencies = [];
	
	function seed() {
		global $conn, $sqlstat, $sqlerr;
		if (!$this->isSeeded() and $sqlstat) {
		}
	}
	
	function isSeeded() {
		global $conn, $sqlstat, $sqlerr;
		if ($sqlstat) {
			$stmt = $conn->prepare("SHOW TABLES LIKE 'module_transitmanager_permissions';");
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
	
	function routes_view() {
		return "<h4>We're still working on the Route Viewer</h4>";
	}
	
	function scheduling_view() {
		
		$pdata = [["2016fallfair", "August 26, 2016 to August 28, 2016"],
		          ["2016septemberA", "September 2, 2016 to September 15, 2016"],
				  ["2016septemberB", "September 16, 2016 to October 7, 2016"],
				  ["2016october", "October 8, 2016 to November 4, 2016"],
				  ["2016novdec", "November 5, 2016 to December 30, 2016"],
				  ["2017janfebmar", "December 31, 2016 to March 31, 2017"],
				  ["2017aprmay", "April 2, 2017 to May 28, 2017"],
				  ["2017june", "May 29, 2017 to June 22, 2017"],
				  ["2017summer", "June 23, 2017 to September 4, 2017"]];
		
		$periods = "";
		foreach ($pdata as $p) {
			$periods .= "<option value=\"{$p[0]}\">{$p[1]}</option>\n";
		}
		
		$html  = "<p>Passengers: <span id=\"riders\">0</span>.</p>\n";
		$html .= "<div id=\"mapContainer\">\n";
		$html .= "<div id=\"mapdisplay\" class=\"map\"></div>\n";
		$html .= "</div>\n";
		$html .= "<div id=\"scheduleContainer\">\n";
		$html .= "<div class=\"form-group\" style=\"width:100%;\">\n";
		$html .= "<label for=\"periodSel\">Schedule:</label>\n";
		$html .= "<select class=\"form-control\" id=\"periodSel\" onchange=\"periodChanged();\">{$periods}</select>\n";
		$html .= "</div>\n";
		$html .= "<div id=\"scheduleNav\" class=\"btn-group btn-group-justified\">Loading...</div>\n";
		$html .= "<div id=\"scheduleBlockContainer\">\n";
		$html .= "<div id=\"scheduleBlockLeft\" class=\"col-2-sm col-1-lg\">\n";
		$html .= "<div class=\"form-group\" style=\"width:100%;\">\n";
		$html .= "<label for=\"leftRoute\">Route:</label>\n";
		$html .= "<select class=\"form-control\" id=\"leftRoute\" onchange=\"routeChanged('left');\"></select>\n";
		$html .= "</div>\n";
		$html .= "<div id=\"leftScheduleBlockContent\">Loading...</div>\n";
		$html .= "</div>\n";
		$html .= "<div id=\"scheduleBlockRight\" class=\"hide-sm col-1-lg\">\n";
		$html .= "<div class=\"form-group\" style=\"width:100%;\">\n";
		$html .= "<label for=\"rightRoute\">Route:</label>\n";
		$html .= "<select class=\"form-control\" id=\"rightRoute\" onchange=\"routeChanged('right');\"></select>\n";
		$html .= "</div>\n";
		$html .= "<div id=\"rightScheduleBlockContent\">Loading...</div>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";
		$html .= "<div id=\"contentStore\" class=\"contentStore\"></div>\n";
		$html .= "<script src=\"http://maps.googleapis.com/maps/api/js?key=AIzaSyClfDSYsOnNbzXa1dZQWhULq-sJ3AqjPpA\"></script>\n";
		$html .= "<script src=\"/assets/site/modules/transitmanager/scheduleview.js\"></script>\n";
		return $html;
		
	}
	
	function planner_view() {
		return $this->planner_viewmini();
	}
	
	function planner_viewmini() {
		return "<h4>We're still working on the Trip Planner</h4>";
	}
	
	function notices_view() {
		return $this->notices_viewmini();
	}
	
	function notices_viewmini() {
		return "<h4>We're still working on Notices</h4>";
	}
	
	function driver_shifts() {
		return "<h4>We're working on Driving Shits</h4>";
	}
	
	function designer() {
		return "<h4>We're working on the Designer</h4>";
	}
	
	function ajax_schedule_getcontent() {
		if (isset($_POST["content"])) {
			if (file_exists("../server_modules/transitmanager/scheduledata/" . $_POST["content"] . ".html")) {
				$result = file_get_contents("../server_modules/transitmanager/scheduledata/" . $_POST["content"] . ".html");
			} else {
				$result = "No data found: " . $_POST["content"];
			}
			return $result;
		} else {
			return "";
		}
	}
	
	function ajax_schedule_getcontentstore() {
		$dataPath = "../server_modules/transitmanager/scheduledata/";
		$store = "";
		if (isset($_POST["content"])) {
			foreach (scandir($dataPath) as $path) {
				$fullPath = $dataPath . $path;
				if (substr($path, 0, strlen($_POST["content"])) === $_POST["content"] and file_exists($fullPath) and is_file($fullPath)) {
					$store .= "<div id=\"content-" . str_replace(".html", "", $path) . "\">";
					$store .= file_get_contents($fullPath);
					$store .= "</div>";
				}
			}
		}
		return $store;
	}

}

?>