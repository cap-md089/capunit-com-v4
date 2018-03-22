<?php
	define ("USER_REQUIRED", false);
	require_once (BASE_DIR."lib/logger.php");
	require_once (BASE_DIR."lib/general.php");
	
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			if (!$l) {
				return [
					'title' => 'Calendar',
					'body' => $a->getGoogleCalendarEmbedLink()
				];
			} 

			$months = [
				'January',
				'February',
				'March',
				'April',
				'May',
				'June',
				'July',
				'August',
				'September',
				'October',
				'November',
				'December'
			];

			$statuses = [
				'Complete',
				'Cancelled'
			];

			function getByOne ($a, $id) {
				if ($id > count($a)) {
					$id = 1;
				} else if ($id < 1) {
					$id = count($a);
				}
				return $a[$id-1];
			}

			$thisMonth = (int)(isset($e['uri'][$e['uribase-index']+1]) ? $e['uri'][$e['uribase-index']+1] : date('n'))-1;
			$thisYear = (int)(isset($e['uri'][$e['uribase-index']]) ? $e['uri'][$e['uribase-index']] : date('Y'));

			$thisMonthNum = $thisMonth;

			$nextMonth = UtilCollection::createDate($thisYear, $thisMonth+1);
			$thisMonth = UtilCollection::createDate($thisYear, $thisMonth);

			$date = UtilCollection::getDateTime($nextMonth->getTimestamp()-1);
			$numberDays = $date->format('j');
			$numberWeeks = ceil(($numberDays + $thisMonth->format('w'))/7)+1;

			$Events = array_fill(1, $numberDays, []);

			$pdo = DBUtils::CreateConnection();
			// $stmt = Null; // Initialize the variable in a higher scope so that there are no scope issues
			// ORDERBY created to only display the first x events, based on event creation date
			$stmt = $pdo->prepare('SELECT Created, MeetDateTime, PickupDateTime, EventNumber, EventName, Status, 
			Author, TeamID FROM '.DB_TABLES['EventInformation'].' WHERE ((MeetDateTime < :monthend AND
			MeetDateTime > :monthstart) OR (PickupDateTime > :monthstart AND PickupDateTime < :monthend))
			AND AccountID = :aid ORDER BY Created;');
			$eventLimit = 0;
			if(!$a->paid || ($a->paid && $a->expired)) {
				$eventLimit = $a->unpaidEventLimit;
			} else {
				$eventLimit = $a->paidEventLimit;
			}

			$stmt->bindValue(':monthend', $nextMonth->getTimestamp());
			$stmt->bindValue(':monthstart', $thisMonth->getTimestamp());
			$stmt->bindValue(':aid', $a->id);
			$data = DBUtils::ExecutePDOStatement($stmt);
			$eventlist = "";

			for ($i = 0; ($i < $eventLimit && $i < count($data)); $i++) {
				$event = $data[$i];
				$eventlist .= $event['EventNumber'].', ';
				$d1 = UtilCollection::getDateTime($event['MeetDateTime']);
				$d2 = UtilCollection::getDateTime($event['PickupDateTime']);

				$d1d = $d1->format('j');
				$d2d = $d2->format('j');

				echo $d1->format('m').PHP_EOL.$d2->format('m').PHP_EOL.$thisMonth->format('m').PHP_EOL.$nextMonth->format('m').PHP_EOL;

				if ($d1->format('m') != $d2->format('m')) {
					if ($d2->format('m') != $thisMonth->format('m')) {
						if ($d2->format('m') == '01' || (int)$d2->format('m') > (int)$d1->format('m')) {
							$d2d = 31;
						}
					}
					if ($d1->format('m') != $thisMonth->format('m')) {
						if ($d1->format('m') == '12' || (int)$d1->format('m') < (int)$d2->format('m')) {
							$d1d = 1;
						}
					}
				}

				for ($k = $d1d; $k <= $d2d; $k++) {
					if (isset($Events[$k]))	$Events[$k][] = $event;
				}
			}
			$eventlist = rtrim($eventlist, ', ');

			$Calendar = array_fill(0, $numberWeeks-1, array_fill(0, 7, Null));

			for ($i = (int)$thisMonth->format('N') % 7, $j = 1; $i < 7; $i++, $j++) {
				$Calendar[0][$i] = $j;
			}
			for ($i = 1; $i < $numberWeeks-2; $i++) {
				$s = $Calendar[$i-1][6];
				for ($j = 0; $j < 7; $j++) {
					$Calendar[$i][$j] = $s+$j+1;
				}
			}
			$s = $Calendar[count($Calendar)-2][6];
			for ($i = $s+1, $j = 0; $i < $numberDays + 1; $i++, $j++) {
				$Calendar[count($Calendar)-1][$j] = $i;
			}

			$html = "";

			$GcalLink = $a->getGoogleCalendarShareLink();
			$html .= '<p align="center">Link to our <a href='.$GcalLink.' target=\"_blank\">Google Calendar</a> and see events on your calendar</p>';
//			$html .= 'events this month: '.count($data).' event limit: '.$eventLimit.' event numbers: '.$eventlist.' </br>';

			if ($l && $m->hasPermission('AddEvent')) {
				$html .= new Link ('eventform', 'Add an event'). "<br />";
			}

			$html .= "<table id=\"eventCalendar\">";
			$nextM = $nextMonth->format('n');
			$nextY = $nextMonth->format('Y');
			$lastM = UtilCollection::getDateTime($thisMonth->getTimestamp()-1)->format('n');
			$lastY = UtilCollection::getDateTime($thisMonth->getTimestamp()-1)->format('Y');
			$html .= "<caption>";
			$html .= "<span class=\"fleft\">".new Link("Calendar", getByOne($months, $lastM), [$lastY, $lastM])."</span>";
			$html .= getByOne($months, $thisMonth->format('n'));
			$html .= '<span class="fright">'.new Link("Calendar", getByOne($months, $nextM), [$nextY, $nextM])."</span>";
			$html .= "</caption>";
			$html .= "<tbody>";
			$dow = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
			if ($e['parameter']['mobile'] !== 'true') {
				$html .= "<tr>";
				foreach ($dow as $day) {
					$html .= "<th>$day</th>";
				}
				$html .= "</tr>";
			}
			foreach ($Calendar as $CRow) {
				$CRowHTML = "<tr>";
				$i = 0;
				foreach ($CRow as $CTD) {
					if ($CTD == null) {
						if ($e['parameter']['mobile'] !== 'true') {
							$CRowHTML .= "<td></td>";
						}
						$i++;
						continue;
					}
					$count = 0;
					if ($l && ($m->hasPermission('EditEvent'))) {
						$count = count($Events[$CTD]);
					} else {
						foreach ($Events[$CTD] as $event) {
							$count += $event['Status'] == 'Draft' || ($l && $event['Author'] == $m->uname) ? 0 : 1;
						}
					}
					if ($e['parameter']['mobile'] == 'true' && $count == 0) {
						$i++;
						continue;
					}
					$TD = "<td>";
					$TD .= "<div class=\"td-row\">" . (strlen($CTD."") == 1 ? "0" : "") . "$CTD.";
					if ($e['parameter']['mobile'] == 'true') {
						$TD .= " {$dow[$i]}";
					}
					$TD .= "</div>";
					$TD .= "<div class=\"td-data\"><ul>";
					foreach ($Events[$CTD] as $event) {
						$stat = -1;
						switch ($event['Status']) {

							case 'Deleted' :
								$stat = 0;
							break;

							case 'Information Only' :
								$stat = 1;
							break;

							case 'Cancelled' :
								$stat = 2;
							break;

							case 'Tentative' :
								$stat = 6;
							break;

							case 'Confirmed' :
							case 'Complete' :
								$stat = 3;
							break;

							case 'Draft' :
								if ($l && ($m->hasPermission('EditEvent') || $event['Author'] == $m->uname)) {
									$stat = 4;
								} else {
									$stat = -1;
								}
							break;
						}

						if ($event['TeamID'] != 0) {
							// if ($l) $stat = in_array($event['TeamID'], $m->getTeamIDs()) ? 5 : -1;
							// else $stat = -1;
							$stat = 5 + ($stat == 4 ? 40 : 0);
						}
						$lh = $stat != -1 ? "<li class=\"ce$stat\">".(new Link('eventviewer', $event['EventName'], [$event['EventNumber']]))."</li>" : '';
						$TD .= $lh;
					}
					$TD .= "</ul></div></td>";
					$CRowHTML .= $TD;
					$i++;
				}
				$CRowHTML .= "</tr>";
				$html .= $CRowHTML;
			}
			$html .= "</tbody>";
			$html .= "</table>";

			if (count($data) == 0 && $e['parameter']['mobile']) {
				$html .= "<div class=\"sorry\">Sorry, no events for this month</div>";
			}

			return [
				'title' => 'Calendar',
				'body' => $html
			];
		}

	}
