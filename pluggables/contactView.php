<?php
	function contactView ($e, $c, $l, $m, $a) {
		$html = '';
//		$html .= ($m->hasDutyPosition(["Cadet Flight Sergeant", "Cadet Flight Commander"])) ? 'T' : 'F';
		if ($m->hasDutyPosition(["Cadet Flight Sergeant", "Cadet Flight Commander"])) {
			$pdo = DB_Utils::CreateConnection();
			$emails = '';
			$stmt = $pdo->prepare("SELECT Flights.Flight, Member.CAPID, Member.Rank, Member.NameFirst, Member.NameLast, Absentee.AbsentUntil,
Absentee.AbsentNotes, (Absentee.AbsentUntil - :time) AS Absent FROM ".DB_TABLES['Member']." AS Member INNER JOIN ".DB_TABLES['Flights']." AS Flights ON Member.CAPID = Flights.CAPID
AND Flights.Flight = :flight LEFT JOIN ".DB_TABLES['Absentee']." AS Absentee ON Absentee.capid = Member.CAPID 
ORDER BY Absent;");
			$flight = $m->getFlight();
			$stmt->bindValue(':flight', $flight);
			$stmt->bindValue(':time', time());
			$data = DB_Utils::ExecutePDOStatement($stmt);
			$flightmembers = [];
			foreach ($data as $datum) {
				if (!isset($flightmembers[$datum['CAPID']])) {
					$flightmembers[$datum['CAPID']] = [
						'Rank' => $datum['Rank'],
						'Name' => $datum['NameFirst'] . ' ' . $datum['NameLast'],
						'Contact' => [],
						'AbsentUntil' => $datum['AbsentUntil'],
						'AbsentNotes' => $datum['AbsentNotes'],
						'Absent' => $datum['Absent'] != Null && $datum['Absent'] > 0,
						'_Absent' => $datum['Absent'],
						'TAbsent' => ($datum['Absent'] > 0 && $datum['Absent'] != Null) ? 't' : 'f'
					];
				}
			}
			$stmt = $pdo->prepare("SELECT MbrContact.CAPID, MbrContact.Contact, MbrContact.Type, MbrContact.Priority, MbrContact.DoNotContact FROM ".DB_TABLES['MemberContact']." AS MbrContact INNER JOIN ".DB_TABLES['Flights']." AS Flights ON MbrContact.CAPID = Flights.CAPID WHERE (MbrContact.`Type` LIKE '%PHONE%' OR MbrContact.`Type` LIKE '%EMAIL') AND MbrContact.DoNotContact = 0 AND Flights.Flight = :flight;");
			$stmt->bindParam(':flight', $flight);
			$cdata = DB_Utils::ExecutePDOStatement($stmt);
			foreach ($cdata as $datum) {
				$flightmembers[$datum['CAPID']]['Contact'][] = [
					'Contact' => $datum['Contact'],
					'Priority' => $datum['Priority'],
					'Type' => $datum['Type'],
					'DoNotContact' => $datum['DoNotContact'] == 1
				];
			}
			$elist = [];
			$dl = new DetailedListPlus();
			foreach ($flightmembers as $mem) {
				$phones = '';
				if (count($mem['Contact']) == 0) {
					$phones = 'Sorry, this person does not have any listed phone numbers';
				} else {
					usort($mem['Contact'], function ($a, $b) {
						return strcmp($a['Contact'], $b['Contact']);
					});
				}
				$rbutt = new AsyncButton(Null, 'Remove', 'contactViewerAddToEmailList');
				$abutt = new AsyncButton(Null, 'Add', 'contactViewerAddToEmailList');
				for ($i = 0; $i < count($mem['Contact']); $i++) {
					$cont = $mem['Contact'][$i]['Contact'];
					if (!$mem['Contact'][$i]['DoNotContact']) {
						if (is_numeric($cont)) {
							$phones .= '('.substr($cont, 0, 3).') '.substr($cont, 3, 3).'-'.substr($cont, 6, 4).' ('.strtoupper($mem['Contact'][$i]['Priority'] . ' '.$mem['Contact'][$i]['Type']).')<br />';
						} else {
							if (!$mem['Absent'] && !in_array($cont, $elist)) {
								$elist[] = $cont;
								$emails .= $cont.'; ';
								$phones .= $cont . ' ('.strtoupper($mem['Contact'][$i]['Priority']) . ' '. $mem['Contact'][$i]['Type'] .') '.$rbutt->getHtml($cont).'<br />';
							} else {
								$phones .= $cont . ' ('.strtoupper($mem['Contact'][$i]['Priority']) . ' '. $mem['Contact'][$i]['Type'] .') '.$abutt->getHtml($cont).'<br />';
							}
						}
					}
				}
				if ($mem['Absent']) {
					$phones .= <<<EOD
<div style="margin-left:40px"><h3>Absent notes:</h3>
<div>{$mem['AbsentNotes']}</div></div>
EOD;
				}
				$dl->addElement($mem['Rank'] . ' ' . $mem['Name'].($mem['Absent']?" <span class=\"red\">This person is absent</span>":""), $phones, Null, Null, !$mem['Absent']);
			}
			$dl->defaultopen = true;
			$emails = rtrim($emails, '; ');
			$html .= <<<HTM
<h2 class="title">Flight contact list</h2>
<div style="margin:10px;font-style:italic;" id="emailList">$emails</div>
$dl
HTM;
			return [
				'text' => $html,
				'title' => 'Contact View'
			];
		} else if ($m->hasDutyPosition(['Cadet Executive Officer', 'Cadet Deputy Commander', 'Cadet Commander'])) {
			$pdo = DB_Utils::CreateConnection();
			$emails = '';
			$stmt = $pdo->prepare("SELECT Flights.Flight, Member.CAPID, Member.Rank, Member.NameFirst, Member.NameLast, Absentee.AbsentUntil,
Absentee.AbsentNotes, (Absentee.AbsentUntil - :time) AS Absent FROM ".DB_TABLES['Member']." AS Member INNER JOIN ".DB_TABLES['Flights']." AS Flights ON Member.CAPID = Flights.CAPID 
LEFT JOIN ".DB_TABLES['Absentee']." AS Absentee ON Absentee.capid = Member.CAPID 
ORDER BY Absent;");
			$flight = $m->getFlight();
			$stmt->bindValue(':time', time());
			$data = DB_Utils::ExecutePDOStatement($stmt);
			$flightmembers = [];
			foreach ($data as $datum) {
				$flightmembers[$datum['CAPID']] = [
					'Rank' => $datum['Rank'],
					'Name' => $datum['NameFirst'] . ' ' . $datum['NameLast'],
					'Contact' => [],
					'AbsentUntil' => $datum['AbsentUntil'],
					'AbsentNotes' => $datum['AbsentNotes'],
					'Absent' => $datum['Absent'] != Null && $datum['Absent'] > 0,
					'_Absent' => $datum['Absent'],
					'TAbsent' => ($datum['Absent'] > 0 && $datum['Absent'] != Null) ? 't' : 'f'
				];
			}
			$stmt = $pdo->prepare("SELECT MbrContact.CAPID, MbrContact.Contact, MbrContact.Type, MbrContact.Priority, MbrContact.DoNotContact FROM ".DB_TABLES['MemberContact']." AS MbrContact WHERE (MbrContact.`Type` LIKE '%PHONE%' OR MbrContact.`Type` LIKE '%EMAIL') AND MbrContact.DoNotContact = 0;");
			$cdata = DB_Utils::ExecutePDOStatement($stmt);
			foreach ($cdata as $datum) {
				if (!isset($flightmembers[$datum['CAPID']])) {
					continue;
				}
				$flightmember = $flightmembers[$datum['CAPID']];
				
				$contact = $flightmember['Contact'];

				$contact[] = [
					'Contact' => $datum['Contact'],
					'Priority' => $datum['Priority'],
					'Type' => $datum['Type'],
					'DoNotContact' => $datum['DoNotContact'] == 1,
					'Absent' => false,
					'_Absent' => 0,
					'TAbsent' => 'f',
					'AbsentUntil' => 0,
					'AbsentNotes' => ''
				];

				$flightmember['Contact'] = $contact;

				$flightmembers[$datum['CAPID']] = $flightmember;
			}
			$elist = [];
			$dl = new DetailedListPlus();
			foreach ($flightmembers as $mem) {
				$phones = '';
				if (count($mem['Contact']) == 0) {
					$phones = 'Sorry, this person does not have any listed phone numbers';
				} else {
					usort($mem['Contact'], function ($a, $b) {
						return strcmp($a['Contact'], $b['Contact']);
					});
				}
				$rbutt = new AsyncButton(Null, 'Remove', 'contactViewerAddToEmailList');
				$abutt = new AsyncButton(Null, 'Add', 'contactViewerAddToEmailList');
				for ($i = 0; $i < count($mem['Contact']); $i++) {
					$cont = $mem['Contact'][$i]['Contact'];
					if (!$mem['Contact'][$i]['DoNotContact']) {
						if (is_numeric($cont)) {
							$phones .= '('.substr($cont, 0, 3).') '.substr($cont, 3, 3).'-'.substr($cont, 6, 4).' ('.strtoupper($mem['Contact'][$i]['Priority'] . ' '.$mem['Contact'][$i]['Type']).')<br />';
						} else {
							if (isset($mem['Absent']) && !$mem['Absent'] && !in_array($cont, $elist)) {
								$elist[] = $cont;
								$emails .= $cont.'; ';
								$phones .= $cont . ' ('.strtoupper($mem['Contact'][$i]['Priority']) . ' '. $mem['Contact'][$i]['Type'] .') '.$rbutt->getHtml($cont).'<br />';
							} else {
								$phones .= $cont . ' ('.strtoupper($mem['Contact'][$i]['Priority']) . ' '. $mem['Contact'][$i]['Type'] .') '.$abutt->getHtml($cont).'<br />';
							}
						}
					}
				}
				if (isset($mem['Absent']) && $mem['Absent']) {
					$phones .= <<<EOD
<div style="margin-left:40px"><h3>Absent notes:</h3>
<div>{$mem['AbsentNotes']}</div></div>
EOD;
				}
				$dl->addElement($mem['Rank'] . ' ' . $mem['Name'].($mem['Absent']?" <span class=\"red\">This person is absent</span>":""), $phones, Null, Null, false);
			}
			$emails = rtrim($emails, '; ');
			$dl->defaultopen = false;
			$html .= <<<HTM
<h2 class="title">Flight contact list</h2>
<div style="margin:10px;font-style:italic;" id="emailList">$emails</div>
$dl
HTM;
			return [
				'text' => $html,
				'title' => 'Contact View'
			];
		}
		return '';
	}
