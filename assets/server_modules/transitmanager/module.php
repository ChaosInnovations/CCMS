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
	
	function ajax_drivers_display_getview() {
		$viewName = (isset($_POST["view"])) ? $_POST["view"] : "default";
		if ($viewName == "default") {
			$viewName = "month-".date("Y-F");
		}
		
		$arg = explode("-", $viewName);
		$d = $arg[0];
		
		$head = "";
		$body = "";
		$foot = "";
		
		// Test - default view 
		
		$prev = "";
		$prevTarget = "";
		$here = "";
		$hereTarget = "";
		$next = "";
		$nextTarget = "";
		
		if ($d == "day") {
			$m = $this->monthToOrd($arg[2]);
			$prev = date("F j, Y", strtotime("{$arg[1]}-{$m}-{$arg[3]}")-3600*24);
			$prevTarget = "day-".strtolower(date("Y-F-d", strtotime("{$arg[1]}-{$m}-{$arg[3]}")-3600*24));
			$here = date("F j, Y", strtotime("{$arg[1]}-{$m}-{$arg[3]}"));
			$hereTarget = "month-".$arg[1]."-".$arg[2];
			$next = date("F j, Y", strtotime("{$arg[1]}-{$m}-{$arg[3]}")+3600*24);
			$nextTarget = "day-".strtolower(date("Y-F-d", strtotime("{$arg[1]}-{$m}-{$arg[3]}")+3600*24));
			$body = $this->get_day_shifts($arg[1], $arg[2], $arg[3]); // Args are year, month, day
		} else if ($d == "month") {
			$m = $this->monthToOrd($arg[2]);
			$prev = date("F Y", strtotime("{$arg[1]}-{$arg[2]}-1")-3600*24);
			$prevTarget = "month-".strtolower(date("Y-F", strtotime("{$arg[1]}-{$arg[2]}-1")-3600*24));
			$here = date("F Y", strtotime("{$arg[1]}-{$arg[2]}-1"));
			$hereTarget = "year-".$arg[1];
			$next = date("F Y", strtotime("{$arg[1]}-{$arg[2]}-1")+3600*24*31);
			$nextTarget = "month-".strtolower(date("Y-F", strtotime("{$arg[1]}-{$arg[2]}-1")+3600*24*31));
			$body = $this->get_month_calendar($arg[1], $arg[2]); // Args are year, month
		} else if ($d == "year") {
			$prev = $arg[1]-1;
			$prevTarget = "year-".$prev;
			$here = $arg[1];
			$hereTarget = "";
			$next = $arg[1]+1;
			$nextTarget = "year-".$next;
			$body = $this->get_year_calendar($arg[1]); // Arg is year
		} else if ($d == "shift") {
			$shift = new Shift($arg[1]); // Arg is shift number
			//$prev = ???;
			//$prevTarget = [most recent shift];
			$here = "Shift 2: Bus Driving and Cleaning on July 9, 2017";
			$hereTarget = "day-2017-july-09";
			//$next = ???;
			//$nextTarget = [next soonest shift];
			$body = $this->get_shift_display($shift);
		} else if ($d == "profile") {
			
		} else if ($d == "agenda") {
			
		} else if ($d == "shiftlist") {
			
		} else if ($d == "manage") {
			
		} 
		
		$head .= "<div class=\"btn-group\" style=\"width:100%;\">";
		$head .= "<button class=\"btn btn-primary col-xs-2\" title=\"{$prev}\" onclick=\"changeToView('{$prevTarget}');\"><span class=\"glyphicon glyphicon-arrow-left\"></span></button>";
		$head .= "<button class=\"btn btn-default col-xs-8\" title=\"Change\" onclick=\"changeToView('{$hereTarget}');\"><b>{$here}</b></button>";
		$head .= "<button class=\"btn btn-primary col-xs-2\" title=\"{$next}\" onclick=\"changeToView('{$nextTarget}');\"><span class=\"glyphicon glyphicon-arrow-right\"></span></button></div>";

		
		$foot .= "<div class=\"row\"><div class=\"col-xs-4 btn-group\">";
		$foot .= "<button class=\"btn btn-success col-xs-6\" title=\"New Shift\"><span class=\"glyphicon glyphicon-plus\"></span></button>";
		$foot .= "<button class=\"btn btn-success col-xs-6\" title=\"View Drivers\"><span class=\"glyphicon glyphicon-user\"></span></button></div>";
		$foot .= "<div class=\"col-xs-8 btn-group\">";
		$foot .= "<button class=\"btn btn-default col-xs-8\" title=\"View Profile\"><span class=\"glyphicon glyphicon-user\"></span>&nbsp;Thomas Boland</button>";
		$foot .= "<button class=\"btn btn-default col-xs-4\" title=\"Shifts\">Shifts</button></div></div>";

		// End test
		
		return $head."§§§".$body."§§§".$foot;
	}
	
	function get_day_shifts($y, $m, $d) {
		$m = $this->monthToOrd($m);
		
		$day = $this->generateDayData(strtotime("{$y}-{$m}-{$d}"));
		
		$html = "";
		
		$dt = strtotime("{$y}-{$m}-{$d}");
		
		// Assumes shifts are in order of start time, although it shouldn't really matter, just looks a bit odd
		
		$start = 0;
		$end = 0;
		foreach ($day as $s) {
			if ($s->starttime < $start or $start == 0) {
				$start = $s->starttime;
			}
			if ($s->endtime > $end) {
				$end = $s->endtime;
			}
		}
		
		$start = max($start, strtotime("{$y}-{$m}-{$d} 00:00:00.000"));
		$end = min($end, strtotime("{$y}-{$m}-{$d} 23:59:59.999"));
		
		$columns = [];
		
		foreach ($day as $s) {
			$added = false;
			for ($i = 0; $i < count($columns); $i++) {
				if (date("G:i", $columns[$i][count($columns[$i])-1]->endtime) == date("G:00", $s->starttime) or date("G", $columns[$i][count($columns[$i])-1]->endtime) < date("G", $s->starttime)) {
					array_push($columns[$i], $s);
					$added = true;
					break;
				}
			}
			if (!$added) {
				array_push($columns, [$s]);
			}
		}
		
		$num = max(1, count($columns));
		
		$datestr = date("l, F j, Y", strtotime("{$y}-{$m}-{$d}"));
		
		$html .= "<div class=\"table-responsive\"><table class=\"table table-bordered\"><tr><th><span class=\"glyphicon glyphicon-time\"></span></th><th class=\"text-center\" colspan=\"{$num}\">{$datestr}</th></tr>";
		
		if (count($day) > 0) {
			for ($h = date("G", $start); $h <= date("G", $end); $h++) {
				$time = date("g", strtotime("{$y}-{$m}-{$d} {$h}:00:00.000")).substr(date("a", strtotime("{$y}-{$m}-{$d} {$h}:00:00.000")), 0, 1);
				$html .= "<tr><th>{$time}</th>";
				foreach ($columns as $c) {
					$shift = false;
					foreach ($c as $s) {
						$e = min($s->endtime, strtotime("{$y}-{$m}-{$d} 23:59:59.999"));
						if (date("G", $s->starttime) == $h) {
							// Subtract a minute to make sure a block isn't filled when on the hour
							$len = date("G", $e-60) - date("G", $s->starttime) + 1;
							$col = ($s->selected === null) ? "d9534f" : "5cb85c";
							$stme = date("g:ia", $s->starttime);
							$etme = date("g:ia", $e);
							$elapsed_h = date("G", $e - $s->starttime)-1;
							$elapsed_m = str_replace("0", "", date("i", $e - $s->starttime));
							$elps = "{$elapsed_h} hour".($elapsed_h!=1?"s":"")." and {$elapsed_m} minute".($elapsed_m!=1?"s":"");
							$html .= "<td rowspan=\"{$len}\" style=\"background-color:#{$col};color:#fff;cursor:pointer;\" onclick=\"changeToView('shift-{$s->id}');\">";
							$html .= "<p>{$s->name}<br />{$stme} to {$etme} ({$elps})<br />";
							if ($s->selected === null) {
								$html .= "Available";
							} else {
								$html .= "Filled by:<br />{$s->selected->name}";
							}
							$html .= "</p></td>";
							$shift = true;
							break;
						}
						if (date("G", $s->starttime) < $h and date("G", $e) >= $h) {
							$shift = true;
							break;
						}
					}
					if (!$shift) {
						$html .= "<td></td>";
					}
				}
				$html .= "</tr>";
			}
		} else {
			$html .= "<tr><td class=\"text-center\" colspan=\"2\"><h3>No shifts for this day!</h3></td></tr>";
		}
		
		$html .= "</table></div>";
		
		return $html;
	}
	
	function get_month_calendar($y, $m) {
		$m = $this->monthToOrd($m);
		$month = $this->generateMonthData(strtotime("{$y}-{$m}-1"));
		
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
					$btext = ($day["shifts"]["total"] == 0) ? "No shifts" : "{$day["shifts"]["avail"]}/{$day["shifts"]["total"]} shifts left";
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
	
	function get_year_calendar($y) {
		$year = $this->generateYearData(strtotime("{$y}-01-01"));				  
				  
		$html = "";
		
		$html .= "<div class=\"table-responsive\"><table class=\"table table-bordered\">";
		
		foreach ($year as $row) {
			$html .= "<tr>";
			
			foreach ($row as $month) {
				$btype = ($month["shifts"]["total"] == 0) ? "default" : (($month["shifts"]["avail"] == 0) ? "success" : "danger");
				$btext = ($month["shifts"]["total"] == 0) ? "No shifts" : "{$month["shifts"]["avail"]}/{$month["shifts"]["total"]} shifts left";
				$dmth = date("F", $month["date"]);
				$dstr = date("F, Y", $month["date"]);
				$target = date("Y-F", $month["date"]);
				$html .= "<td>{$dmth}<br />";
				$html .= "<button class=\"btn btn-{$btype} btn-block\" title=\"{$dstr}\" onclick=\"changeToView('month-{$target}');\">";
				$html .= "{$btext}</button></td>";
			}
			
			$html .= "</tr>";
		}
		
		$html .= "</table></div>";
		
		return $html;
	}
	
	function get_shift_display($s) {
		$html = "";
		
		$dstr = date("l, F j, Y", $s->starttime);
		$stme = date("g:ia", $s->starttime);
		$etme = date("g:ia", $s->endtime);
		$elapsed_h = date("G", $s->endtime - $s->starttime)-1;
		$elapsed_m = str_replace("0", "", date("i", $s->endtime - $s->starttime));
		$elps = "{$elapsed_h} hour".($elapsed_h!=1?"s":"")." and {$elapsed_m} minute".($elapsed_m!=1?"s":"");
		
		$html .= "<h4>{$dstr}</h4>";
		$html .= "<h4>Starts at {$stme} and ends at {$etme} ({$elps})</h4>";
		
		if ($s->selected === null) {
			$html .= "<h4>Shift not filled!</h4>";
			$btype = "success";
		} else {
			$html .= "<p><b>Filled by:<br />{$s->selected->name}</b></p>";	
			$btype = "warning";
		}
		
		$html .= "<h4>Tasks:</h4><ul>";
		foreach ($s->tasks as $t) {
			$html .= "<li>{$t}</li>";
		}
		$html .= "</ul>";
		
		$html .= "<button class=\"btn btn-{$btype} btn-block\" title=\"Sign Up\" style=\"height:80px;\">Sign Up</button>";
		
		$html .= "<h4>Wait List:</h4><div class=\"table-responsive\"><table class=\"table\">";
		$html .= "<tr><th></th><th>Driver</th></tr>";
		$i = 1;
		foreach ($s->waitlist as $w) {
			$html .= "<tr><td>{$i}</td><td>{$w->name}</td></tr>";
			$i++;
		}
		
		if (count($s->waitlist) == 0) {
			$html .= "<tr><td class=\"text-center\" colspan=\"2\">There's nobody on the wait list.</td></tr>";
		}
		
		$html .= "</table></div>";
		
		return $html;
	}
	
	function monthToOrd($m) {
		return["january"=>1,"february"=>2,"march"=>3,"april"=>4,"may"=>5,"june"=>6,"july"=>7,"august"=>8,"september"=>9,"october"=>10,"november"=>11,"december"=>12][strtolower($m)];
	}
	
	function generateDayData($date) {
		global $conn, $sqlstat, $sqlerr;
		$day = [];
		
		if ($sqlstat) {
			$stmt = $conn->prepare("SELECT * FROM module_transitmanager_drivershifts WHERE end>=:morn AND start<=:eve ORDER BY start ASC;");
			$stmt->bindParam(":morn", date("Y-m-d 00:00:00", $date));
			$stmt->bindParam(":eve", date("Y-m-d 00:00:00", $date+3600*24));
			$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
			$shifts = $stmt->fetchAll();
			foreach ($shifts as $s) {
				array_push($day, new Shift($s["sid"]));
			}
		}
		
		return $day;
	}
	
	function generateMonthData($date) {
		$ym = date("Y-m", $date);
		$startBlank = date("w", $date);
		$numDays = date("t", $date);
		$endBlank = 7 - ($startBlank + $numDays) % 7;
		$month = [[]];
		$row = 0;
		$d = $startBlank;		
		$prev = date("F, Y", $date-3600*24);
		$prevTarget = "day-".strtolower(date("Y-F", $date-3600*24));
		$next = date("F, Y", $date+3600*24);
		$nextTarget = "day-".strtolower(date("Y-F", $date+3600*24));
		
		if ($d != 0) {
			array_push($month[0], ["date"=>"blank","size"=>$startBlank,"text"=>$prev,"target"=>$prevTarget]);
		}
		
		for ($i = 1; $i<=$numDays; $i++) {
			$day = $this->generateDayData(strtotime("{$ym}-{$i}"));
			$shifts = ["total"=>0,"avail"=>0];
			foreach ($day as $s) {
				$shifts["total"]++;
				if ($s->selected == null) {
					$shifts["avail"]++;
				}
			}
			if ($d >= 7) {
				$d = 0;
				$row++;
				array_push($month, []);
			}
			array_push($month[$row], ["date"=>strtotime("{$ym}-{$i}"),"shifts"=>$shifts]);
			$d++;
		}
		
		if ($d != 7) {
			array_push($month[$row], ["date"=>"blank","size"=>$endBlank,"text"=>$next,"target"=>$nextTarget]);
		}
		
		return $month;
	}
	
	function generateYearData($date) {
		
		$y = date("Y", $date);
		
		$year = [[]];
		$row = 0;
		$col = 0;
		
		for ($m=1;$m<=12;$m++) {
			$month = $this->generateMonthData(strtotime("{$y}-{$m}-01"));
			$shifts = ["total"=>0,"avail"=>0];
			foreach ($month as $w) {
				foreach ($w as $day) {
					if ($day["date"] == "blank") {
						continue;
					}
					$shifts["total"] += $day["shifts"]["total"];
					$shifts["avail"] += $day["shifts"]["avail"];
				}
			}
			if ($col >= 4) {
				$col = 0;
				$row++;
				array_push($year, []);
			}
			array_push($year[$row], ["date"=>strtotime("{$y}-{$m}-01"),"shifts"=>$shifts]);
			$col++;
		}
		return $year;
	}

}

class Shift {
	
	public $name = "Error";
	public $selected = null;
	public $waitlist = [];
	public $starttime = 0;
	public $endtime = 0;
	public $volunteerable = false;
	public $wage = 1.0;
	public $tasks = [];
	public $id = 0;
	
	function __construct($shiftId) {
		global $conn, $sqlstat, $sqlerr;
		
		$this->id = $shiftId;
		
		if ($sqlstat) {
			$stmt = $conn->prepare("SELECT * FROM module_transitmanager_drivershifts WHERE sid=:sid");
			$stmt->bindParam(":sid", $shiftId);
			$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
			$shifts = $stmt->fetchAll();
			if (count($shifts) == 1) {
				$s = $shifts[0];
				$l = explode(";", $s["list"]);
				$this->name = $s["name"];
				$this->starttime = strtotime($s["start"]);
				$this->endtime = strtotime($s["end"]);
				$this->volunteerable = $s["vol"] == 1;
				$this->wage = $s["wage"];
				$this->tasks = explode("§§§", $s["task"]);
				$this->selected = new AuthUser(array_shift($l));
				$this->waitlist = array_map(function($uid){return new AuthUser($uid);}, $l);
			}
		}
	}
	
}

?>