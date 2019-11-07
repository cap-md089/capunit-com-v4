<?php
//        require_once (BASE_DIR . "lib/logger.php");


	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			// if (!$l) return ['error' => 411];
			// if (!$a->paid) return ['error' => 501];

			$message = '';

			$browsercookies = json_decode(isset($c['LOGIN_DETAILS']) ? urldecode($c['LOGIN_DETAILS']) : '{}', true);

			if (!$l && !isset($browsercookies['sid'])) return ['error' => 411];

			$pdo = DB_Utils::CreateConnection();

			// can only get here on initial load as this code removes login session
			if ($l) {
				$ev = $e['uri'][$e['uribase-index']];
				$event = Event::Get($ev);

				if (!($m->hasPermission('SignUpEdit') || $event->isPOC($m))) return ['error' => 401];

			} else {  	// should only get here if not logged in, check sid cookie and compare to scanadd sid
					// scanadd sid is taken from login sid used to display initial page
					// login sid cookie element is not removed, so login sid cookie is sent with every
					// subsequent page request, even after logged out

				if(!isset($browsercookies['sid'])) return ['error' => 301]; //should never get here...

				// delete stale MemberSessions entries
				$sqlstring = "DELETE FROM ScanAddSessions WHERE Expires<:exp;";
				$stmt = $pdo->prepare($sqlstring);
				$stmt->bindValue(":exp", time());
				$ret = DB_Utils::ExecutePDOStatement($stmt);

				// select current scanadd session info
				$sqlstring = "SELECT * FROM ScanAddSessions WHERE sessionid=:sid;";
				$stmt = $pdo->prepare($sqlstring);
				$stmt->bindValue(":sid", $browsercookies['sid']);
				$data = DB_Utils::ExecutePDOStatement($stmt);
				if(count($data) == 1) {
					$ev = $data[0]['EventNumber'];
					$event = Event::Get($ev);
				} else {
					return ['error' => 301];
				}

				$mem = Member::Estimate($data[0]['Lastcid']);

				if (strlen($mem->RankName)<3) {$mem->RankName = $data[0]['Lastcid'];}
				$messagetext = "Successfully signed up ".$mem->RankName." for this event";
				$message = '<div id="signup-message" style="margin:5px;padding:5px;background-color:#0f0; border: 1px solid #080;">'.$messagetext.'</div>';

			}

			$formstring = "<br /><b><u>Event ".$a->id."-".$event->EventNumber.": ".$event->EventName."</u></b><br />";
			$formstring .= "<b>Meet</b> at ".date('h:i A \o\n n/j/Y', $event->MeetDateTime).' at '.$event->MeetLocation.'<br />';
			$formstring .= "<b>Start</b> at ".date('h:i A \o\n n/j/Y', $event->StartDateTime).' at '.$event->EventLocation.'<br />';
			$formstring .= "<b>End</b> at ".date('h:i A \o\n n/j/Y', $event->EndDateTime).'<br />';
			$formstring .= "<b>Pickup</b> at ".date('h:i A \o\n n/j/Y', $event->PickupDateTime).' at '.$event->PickupLocation.'<br /><br />';
			$formstring .= "Scan your CAP ID card to register";

			$IDForm = new AsyncForm (Null, $formstring, Null, "CAPIDAdd");
			$IDForm->addHiddenField('func', 'addCAPID')
				->addHiddenField('ev', $ev)
				->addField('capid', ' ', null, null, ['id' => 'capid'])
				->setOption('reload', true)
				->setSubmitInfo('Add', null, null, null, true);

			if($l) {
				// add ScanAddSessions sid
				$sqlstring = "INSERT INTO ScanAddSessions ";
				$sqlstring .= "(mid, sessionid, Timestamp, mrankname, Expires, Account, EventNumber) VALUES ";
				$sqlstring .= "(:mcid, :sid, :ts, :mrn, :exp, :acct, :en)";
				$sqlstring .= "ON DUPLICATE KEY UPDATE sessionid=:sid, Timestamp=:ts, Expires=:exp;";
				$stmt = $pdo->prepare($sqlstring);
				$stmt->bindValue(":mcid", $m->capid);
				$stmt->bindValue(":sid", $browsercookies['sid']);
				$stmt->bindValue(":ts", time());
				$stmt->bindValue(":mrn", $m->RankName);
				$stmt->bindValue(":exp", $event->PickupDateTime);
				$stmt->bindValue(":acct", $a->id);
				$stmt->bindValue(":en", $event->EventNumber);
				$ret = DB_Utils::ExecutePDOStatement($stmt);

				// delete MemberSessions sid
				$sqlstring = "DELETE FROM MemberSessions WHERE sessionid=:sid;";
				$stmt = $pdo->prepare($sqlstring);
				$stmt->bindValue(":sid", $browsercookies['sid']);
				$ret = DB_Utils::ExecutePDOStatement($stmt);
			}

			return [
				'body' => [
					'MainBody' => $message.$IDForm,
					'BreadCrumbs' => UtilCollection::GenerateBreadCrumbs([
						[
							'Target' => '/',
							'Text' => 'Home'
						],
						[
							'Target' => '/calendar',
							'Text' => 'Calendar'
						],
						[
							'Target' => '/eventviewer/'.$ev,
							'Text' => "View '$event->EventName'"
						],
						[
							'Target' => '/scanadd/'.$ev,
							'Text' => 'Scan attendance'
						]
					])
				],
				'title' => "Scan-Add for event $ev"
			];
		}

		public static function doPost ($e, $c, $l, $m, $a) {
//			$logger = New Logger ("ScanAdd");

			$browsercookies = json_decode(isset($c['LOGIN_DETAILS']) ? urldecode($c['LOGIN_DETAILS']) : '{}', true);

			if (!isset($browsercookies['sid'])) return ['error' => 411];

			$pdo = DB_Utils::CreateConnection();

			$sqlstring = "SELECT * FROM ScanAddSessions WHERE sessionid=:sid;";
			$stmt = $pdo->prepare($sqlstring);
			$stmt->bindValue(":sid", $browsercookies['sid']);
			$data = DB_Utils::ExecutePDOStatement($stmt);
			if(count($data) == 1) {
				$data = $data[0];
				$ev = $data['EventNumber'];
				$event = Event::Get($ev);
//				$logger->Log("data: ".implode($data), 8);
			} else {
				return ['error' => 302];
			}

			$attend = $event->getAttendance();

			$added = false;

			if ($e['raw']['func'] == 'addCAPID') {
				$mem = Member::Estimate(trim($e['form-data']['capid']));
				if ($mem && $mem->uname != 0) {
					$attend->add($mem, 'Committed/Attended', false, "Scan-Add by ".$data['mrankname']." (".$data['mid'].") on ".date('d M Y'));
					$added = 'add';
					$sqlstring = "UPDATE ScanAddSessions SET Lastcid=:cid WHERE sessionid=:sid;";
					$stmt = $pdo->prepare($sqlstring);
					$stmt->bindValue(":sid", $browsercookies['sid']);
					$stmt->bindValue(":cid", $mem->uname);
					$data = DB_Utils::ExecutePDOStatement($stmt);
				}
			}

			if ($added == 'add') {
				SignUps::Add($a->id, $event->EventNumber);
			}

			return JSSnippet::PageRedirect("scanadd", []);
		}
	}
