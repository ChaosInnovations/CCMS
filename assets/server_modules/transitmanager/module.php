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
		$html .= "<script src=\"assets/site/modules/transitmanager/scheduleview.js\"></script>\n";
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
	
	function drivers_shiftdisplay() {
		// Test - sends empty display but asks for the default view (Current month)
		$html  = "<div id=\"transitmanager-drivershift-display-container\" class=\"panel panel-default\">\n";
		$html .= "<div class=\"panel-heading\"></div>\n";
		$html .= "<div class=\"panel-body\"></div>\n";
		$html .= "<div class=\"panel-footer\"></div></div>\n";
		$html .= "<script src=\"assets/site/modules/transitmanager/driver-shiftdisplay.js\"></script>\n";
		return $html;
	}
	
	function get_month_calendar($y, $m) {
		$month = [[["date"=>"blank","size"=>6,"text"=>"June 2017","target"=>"2017-june"],
		           ["date"=>strtotime("2017-07-01"),"shifts"=>["total"=>3,"avail"=>2]]],
				  [["date"=>strtotime("2017-07-02"),"shifts"=>["total"=>3,"avail"=>0]],
				   ["date"=>strtotime("2017-07-03"),"shifts"=>["total"=>0,"avail"=>0]],
				   ["date"=>strtotime("2017-07-04"),"shifts"=>["total"=>0,"avail"=>0]],
				   ["date"=>strtotime("2017-07-05"),"shifts"=>["total"=>0,"avail"=>0]],
				   ["date"=>strtotime("2017-07-06"),"shifts"=>["total"=>3,"avail"=>0]],
				   ["date"=>strtotime("2017-07-07"),"shifts"=>["total"=>3,"avail"=>0]],
				   ["date"=>strtotime("2017-07-08"),"shifts"=>["total"=>3,"avail"=>0]]],
				  [["date"=>strtotime("2017-07-09"),"shifts"=>["total"=>3,"avail"=>0]],
				   ["date"=>strtotime("2017-07-10"),"shifts"=>["total"=>0,"avail"=>0]],
				   ["date"=>strtotime("2017-07-11"),"shifts"=>["total"=>0,"avail"=>0]],
				   ["date"=>strtotime("2017-07-12"),"shifts"=>["total"=>0,"avail"=>0]],
				   ["date"=>strtotime("2017-07-13"),"shifts"=>["total"=>3,"avail"=>0]],
				   ["date"=>strtotime("2017-07-14"),"shifts"=>["total"=>3,"avail"=>0]],
				   ["date"=>strtotime("2017-07-15"),"shifts"=>["total"=>3,"avail"=>0]]],
				  [["date"=>strtotime("2017-07-16"),"shifts"=>["total"=>3,"avail"=>0]],
				   ["date"=>strtotime("2017-07-17"),"shifts"=>["total"=>0,"avail"=>0]],
				   ["date"=>strtotime("2017-07-18"),"shifts"=>["total"=>0,"avail"=>0]],
				   ["date"=>strtotime("2017-07-19"),"shifts"=>["total"=>0,"avail"=>0]],
				   ["date"=>strtotime("2017-07-20"),"shifts"=>["total"=>3,"avail"=>0]],
				   ["date"=>strtotime("2017-07-21"),"shifts"=>["total"=>3,"avail"=>0]],
				   ["date"=>strtotime("2017-07-22"),"shifts"=>["total"=>3,"avail"=>0]]],
				  [["date"=>strtotime("2017-07-23"),"shifts"=>["total"=>3,"avail"=>0]],
				   ["date"=>strtotime("2017-07-24"),"shifts"=>["total"=>0,"avail"=>0]],
				   ["date"=>strtotime("2017-07-25"),"shifts"=>["total"=>0,"avail"=>0]],
				   ["date"=>strtotime("2017-07-26"),"shifts"=>["total"=>0,"avail"=>0]],
				   ["date"=>strtotime("2017-07-27"),"shifts"=>["total"=>3,"avail"=>0]],
				   ["date"=>strtotime("2017-07-28"),"shifts"=>["total"=>3,"avail"=>0]],
				   ["date"=>strtotime("2017-07-29"),"shifts"=>["total"=>3,"avail"=>0]]],
				  [["date"=>strtotime("2017-07-30"),"shifts"=>["total"=>3,"avail"=>0]],
				   ["date"=>strtotime("2017-07-31"),"shifts"=>["total"=>0,"avail"=>0]],
				   ["date"=>"blank","size"=>5,"text"=>"August 2017","target"=>"2017-august"]]];
		$html = "";
		
		$html .= "<div class=\"table-responsive\"><table class=\"table table-bordered\"><tr><th style=\"width:14.29%;\">Sunday</th><th style=\"width:14.28%;\">Monday</th><th style=\"width:14.29%;\">Tuesday</th><th style=\"width:14.28%;\">Wednesday</th><th style=\"width:14.29%;\">Thursday</th><th style=\"width:14.28%;\">Friday</th><th style=\"width:14.29%;\">Saturday</th></tr>";
		
		foreach ($month as $week) {
			$html .= "<tr>";
			
			foreach ($week as $day) {
				if ($day["date"] == "blank") {
					$html .= "<td colspan=\"{$day["size"]}\" style=\"height:60px;\">";
					$html .= "<button class=\"btn btn-default btn-block\" title=\"{$day["text"]}\" style=\"height:100%;\" onclick=\"changeToView('month-{$day["target"]}');\">";
					$html .= "{$day["text"]}</button></td>";
				} else {
					$btype = ($day["shifts"]["total"] == 0) ? "default" : (($day["shifts"]["avail"] == 0) ? "success" : "danger");
					$btext = ($day["shifts"]["total"] == 0) ? "No shifts" : "{$day["shifts"]["avail"]}/{$day["shifts"]["total"]} shifts remaining";
					$dnum = date("j", $day["date"]);
					$dstr = date("F j, Y", $day["date"]);
					$target = date("Y-F-d", $day["date"]);
					$html .= "<td>{$dnum}<br />";
					$html .= "<button class=\"btn btn-{$btype} btn-block\" title=\"{$dstr}\" onclick=\"changeToView('day-{$target}');\">";
					$html .= "{$btext}</button></td>";
				}
			}
			
			$html .= "</tr>";
		}
		
		$html .= "</table></div>";
		
		return $html;
	}
	
	function ajax_drivers_display_getview() {
		$viewName = (isset($_POST["view"])) ? $_POST["view"] : "default";
		if ($viewName == "default") {
			$viewName = "month-2017-july";
		}
		
		$arg = explode("-", $viewName);
		$d = $arg[0];
		
		$head = "";
		$body = "";
		$foot = "";
		
		// Test - default view 

		$head .= "<div class=\"btn-group\" style=\"width:100%;\">";
		$head .= "<button class=\"btn btn-primary col-xs-2\" title=\"June 2017\"><span class=\"glyphicon glyphicon-arrow-left\"></span></button>";
		$head .= "<button class=\"btn btn-default col-xs-8\" title=\"Change\"><b>July 2017</b></button>";
		$head .= "<button class=\"btn btn-primary col-xs-2\" title=\"August 2017\"><span class=\"glyphicon glyphicon-arrow-right\"></span></button></div>";
		
		if ($d == "month") {
			$body = $this->get_month_calendar($arg[1], $arg[2]); // Args are year, month
		} else {
			$body = $viewName;
		}
		
		$foot .= "<div class=\"row\"><div class=\"col-xs-4 btn-group\">";
		$foot .= "<button class=\"btn btn-success col-xs-6\" title=\"New Shift\"><span class=\"glyphicon glyphicon-plus\"></span></button>";
		$foot .= "<button class=\"btn btn-success col-xs-6\" title=\"View Drivers\"><span class=\"glyphicon glyphicon-user\"></span></button></div>";
		$foot .= "<div class=\"col-xs-8 btn-group\">";
		$foot .= "<button class=\"btn btn-default col-xs-8\" title=\"View Profile\"><span class=\"glyphicon glyphicon-user\"></span>&nbsp;Thomas Boland</button>";
		$foot .= "<button class=\"btn btn-default col-xs-4\" title=\"Shifts\">Shifts</button></div></div>";

		// End test
		
		return $head."§§§".$body."§§§".$foot;
	}

}

?>