<?php
	define ("USER_REQUIRED", false);
	
	function createDate ($year=Null, $month=Null, $day=Null, $hour=Null, $minute=Null, $second=Null) {
		$year = isset($year)?$year:1970;
		$month = isset($month)?$month+1:0;
		$day = isset($day)?$day:1;
		$hour = isset($hour)?$hour:0;
		$minute = isset($minute)?$minute:0;
		$second = isset($second)?$second:0;
		$date = new DateTime();
		$date->setTime($hour, $minute, $second);
		$date->setDate($year, $month, $day);
		return $date;
	}
	function getDateTime($timestamp) {
		$date = new DateTime();
		$date->setTimestamp($timestamp);
		return $date;
	}
	
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
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

			$nextMonth = createDate($thisYear, $thisMonth+1);
			$thisMonth = createDate($thisYear, $thisMonth);

			$date = getDateTime($nextMonth->getTimestamp()-1);
			$numberDays = $date->format('j');
			$numberWeeks = ceil(($numberDays + $thisMonth->format('w'))/7)+1;

			$Events = array_fill(1, $numberDays, []);

			$pdo = DB_Utils::CreateConnection();
			$stmt = $pdo->prepare('SELECT MeetDateTime, PickupDateTime, EventNumber, EventName, Status, 
			Author, TeamID FROM '.DB_TABLES['EventInformation'].' WHERE ((MeetDateTime < :monthend AND
			MeetDateTime > :monthstart) OR (PickupDateTime > :monthstart AND PickupDateTime < :monthend))
			AND AccountID = :aid;');
			$stmt->bindValue(':monthend', $nextMonth->getTimestamp());
			$stmt->bindValue(':monthstart', $thisMonth->getTimestamp());
			$stmt->bindValue(':aid', $a->id);
			$data = DB_Utils::ExecutePDOStatement($stmt);

			foreach ($data as $event) {
				$d1 = getDateTime($event['MeetDateTime']);
				$d2 = getDateTime($event['PickupDateTime']);

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
			$html .= '<p align="center">Link to our <a href='.$GcalLink.'>Google Calendar</a> and see events on your calendar</p>';

			if ($l && $m->hasPermission('AddEvent')) {
				$html .= new Link ('eventform', 'Add an event'). "<br />";
			}

			$html .= "<table id=\"eventCalendar\">";
			$nextM = $nextMonth->format('n');
			$nextY = $nextMonth->format('Y');
			$lastM = getDateTime($thisMonth->getTimestamp()-1)->format('n');
			$lastY = getDateTime($thisMonth->getTimestamp()-1)->format('Y');
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
					if ($l && ($m->hasPermission('EditEvent') || $event['Author'] == $m->uname)) {
						$count = count($Events[$CTD]);
					} else {
						foreach ($Events[$CTD] as $event) {
							$count += $event['Status'] == 'Draft' ? 0 : 1;
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

		public static function doPut ($e, $c, $l, $m, $a) {
			$ev = $e['raw']['data'];
			$event = Event::Get($ev);

			// First block
			$html = '--Event ID Number: '.$a->id."-$ev<br />";
			$html .= "Please contact the event POC listed below directly with any questions or comments<br />";
			$html .= (new Link('eventviewer', 'View more information and signup', [$ev])) . "<br /><br />";
			
			// Second block
			$html .= "--Meet at ".date('h:i A \o\n n/j/Y', $event->MeetDateTime).' at '.$event->MeetLocation.'<br />';
			$html .= "--Start at ".date('h:i A \o\n n/j/Y', $event->StartDateTime).' at '.$event->EventLocation.'<br />';
			$html .= "--End at ".date('h:i A \o\n n/j/Y', $event->EndDateTime).'<br />';
			$html .= "--Pickup at ".date('h:i A \o\n n/j/Y', $event->PickupDateTime).' at '.$event->PickupLocation.'<br /><br />';

			// Third (fourth?) block
			$html .= "--Transportation provided: ".($event->TransportationProvided == 1 ? 'YES' : 'NO').'<br />';
			$html .= "--Uniform: ".$event->Uniform.'<br />';
			$html .= "--Comments: ".$event->Comments.'<br />';
			$html .= "--Activity: ".$event->Activity.'<br />';
			$html .= "--Required forms: ".$event->RequiredForms.'<br />';
			$html .= "--Required equipment: ".$event->RequiredEquipment.'<br />';
			$html .= "--Registration Deadline: ".date('n/j/Y', $event->RegistrationDeadline).'<br />';
			$html .= "--Meals: ".$event->Meals.'<br />';
			if ($event->CAPPOC1ID != 0) {
				$html .= "--CAP Point of Contact: ".$event->CAPPOC1Name."<br />";
				$html .= "--CAP Point of Contact phone: ".$event->CAPPOC1Phone."<br />";
				$html .= "--CAP Point of Contact email: ".$event->CAPPOC1Email."<br />";
			}
			if ($event->CAPPOC2ID != 0) {
				$html .= "--CAP Point of Contact: ".$event->CAPPOC2Name."<br />";
				$html .= "--CAP Point of Contact phone: ".$event->CAPPOC2Phone."<br />";
				$html .= "--CAP Point of Contact email: ".$event->CAPPOC2Email."<br />";
			}
			if ($event->ExtPOCName != '') {
				$html .= "--CAP Point of Contact: ".$event->ExtPOCName."<br />";
				$html .= "--CAP Point of Contact phone: ".$event->ExtPOCPhone."<br />";
				$html .= "--CAP Point of Contact email: ".$event->ExtPOCEmail."<br />";
			}
			$html .= "--Desired number of Participants: ".$event->DesiredNumParticipants.'<br />';
			$html .= "--Event status: ".$event->Status;

			return $html;
		}
	}
