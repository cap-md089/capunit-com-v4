<?php
	define ("USER_REQUIRED", true);
	
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			global $_ACCOUNT;
			$pdo = DB_Utils::CreateConnection();

			$ev = isset($e['uri'][$e['uribase-index']]) ? $e['uri'][$e['uribase-index']] : false;
			$event = $ev ? Event::Get((int)$ev) : false;

			if (!$event) {
				return [
					'error' => 311
				];
			}

			$html = '';

			$breaks = 'false';
			if ($l && ($event->isPOC($m) || $m->hasPermission("EditEvent"))) {
				if ($m->hasPermission("DeleteEvent")) {
					$html .= (new AsyncButton(Null, 'Delete event', 'delEvent'))->getHtml('delet'.$ev)." | ";
				}
				$html .= new Link ("eventform", "Edit event", [$ev]);
				if ($m->hasPermission("CopyEvent")) {
					$html .= " | ".(new AsyncButton(Null, 'Copy event', 'copyEvent'))->getHtml("clone".$ev)."<span style=\"display:none\" id=\"dateTimeOfCurrentEvent\">".date('Y-m-d\TH:i:s',$event->StartDateTime)."</span>";
				}
				if ($m->hasPermission("SignUpEdit")) {
					$html .= "<br />".new Link ("multiadd", "Add attendees", [$ev]);
					$html .= " | ".(new AsyncButton(Null, 'Send attendance summary','sendAttendance'))->getHtml('sende'.$ev);
				}
				$breaks = 'true';
			}
			if($l) {
				$perm = false;
				foreach ($m->genAccounts() as $acc) {
					$perm = $perm || $m->hasPermission('CopyEvent', 1, $acc); 
				}
				$notInAcct = !$a->hasMember($m);
				$notLinked = true; //need to query database for linked event
				//need to add linked event fields to database before implementing
				if ($perm && $notInAcct && $notLinked) {
//					$html .= " | ".new Link ("linkEvent", "Link To Event", [$ev]);
					// Better to use AsyncButton, similar to copy on line 26
											
				}
				$breaks = 'true';
			}
			if ($breaks == 'true') {
				$html .= "<br /><br />";
			}

			if ($event->Status == 'Draft') {
				$html .= '<span class="warning">WARNING: As this event is only a draft, dates, times and other details may change</span><br /><br />';
			}

			// First block
			$html .= "<b>Event:</b> ".$event->EventName.'<br />';
			$html .= "<b>Event ID Number:</b> $a-$ev<br />";
			$html .= "Please contact the event POC listed below directly with any questions or comments<br />";
			
			// Second block
			$html .= "<b>Meet</b> at ".date('h:i A \o\n n/j/Y', $event->MeetDateTime).' at '.$event->MeetLocation.'<br />';
			$html .= "<b>Start</b> at ".date('h:i A \o\n n/j/Y', $event->StartDateTime).' at '.$event->EventLocation.'<br />';
			$html .= "<b>End</b> at ".date('h:i A \o\n n/j/Y', $event->EndDateTime).'<br />';
			$html .= "<b>Pickup</b> at ".date('h:i A \o\n n/j/Y', $event->PickupDateTime).' at '.$event->PickupLocation.'<br /><br />';

			// Third block
			$html .= "<b>Transportation provided:</b> ".($event->TransportationProvided == 1 ? 'YES' : 'NO').'<br />';
			if(strlen($event->TransportationDescription) > 0) {
				$html .= "<b>Transportation Description:</b> ".$event->TransportationDescription.'<br />';
			}
			$html .= "<b>Uniform:</b> ".$event->Uniform.'<br />';
			if(strlen($event->Comments) > 0) {
				$html .= "<b>Comments:</b> ".$event->Comments.'<br />';
			}
			if(strlen($event->Activity) > 0) {
				$html .= "<b>Activity:</b> ".$event->Activity.'<br />';
			}
			if(strlen($event->RequiredForms) > 0) {
				$html .= "<b>Required forms:</b> ".$event->RequiredForms.'<br />';  
			}
			if(strlen($event->RequiredEquipment) > 0) {
				$html .= "<b>Required equipment:</b> ".$event->RequiredEquipment.'<br />';
			}
			if(strlen($event->HighAdventureDescription) > 0) {
				$html .= "<b>High Adventure Description:</b> ".$event->HighAdventureDescription.'<br />';
			}
			if($event->RegistrationDeadline > 0) {
				$html .= "<b>Registration Deadline:</b> ".date('n/j/Y', $event->RegistrationDeadline).'<br />';
			}
			if(strlen($event->RegistrationInformation) > 0) {
				$html .= "<b>Registration Information:</b> ".$event->RegistrationInformation.'<br />';
			}
			if($event->ParticipationFeeDue > 0) {
				$html .= "<b>Participation Fee Deadline:</b> ".date('n/j/Y', $event->ParticipationFeeDue).'<br />';
			}
			if($event->ParticipationFee > 0) {
				$html .= "<b>Participation Fee:</b> ".$event->ParticipationFee.'<br />';
			}
			if(strlen($event->Meals) > 0) {
				$html .= "<b>Meals:</b> ".$event->Meals.'<br />';
			}
			if(strlen($event->EventWebsite) > 0) {
				$html .= "<b>Event Website:</b> <A HREF=\"".$event->EventWebsite."\" target=\"_blank\">$event->EventWebsite</A>".'<br />';
			}
			if($event->TeamID > 0) {
				$pdo = DB_Utils::CreateConnection();
				$stmt = $pdo->prepare('SELECT `TeamName` FROM '.DB_TABLES['Team'].' WHERE TeamID = :tid AND AccountID = :aid;');
				$stmt->bindValue(':tid', $event->TeamID);
				$stmt->bindValue(':aid', $_ACCOUNT->id);
				$data = DB_Utils::ExecutePDOStatement($stmt);
				if(count($data) > 0) {
					$html .= "<b>Team Name:</b> ".$data[0]['TeamName'].'<br />';
				}
			}
			$html .= "<b>Desired number of Participants:</b> ".$event->DesiredNumParticipants.'<br />';
			$html .= "<b>Event status:</b> ".$event->Status.'<br /><br />';

			// Fourth block
			if ($event->CAPPOC1ID != 0) {
				$html .= "<b>CAP Point of Contact:</b> ".$event->CAPPOC1Name."<br />";
				$html .= "<b>CAP Point of Contact phone:</b> ".$event->CAPPOC1Phone."<br />";
				$html .= "<b>CAP Point of Contact email:</b> ".$event->CAPPOC1Email."<br />";
			}
			if ($event->CAPPOC2ID != 0) {
				$html .= "<b>CAP Point of Contact:</b> ".$event->CAPPOC2Name."<br />";
				$html .= "<b>CAP Point of Contact phone:</b> ".$event->CAPPOC2Phone."<br />";
				$html .= "<b>CAP Point of Contact email:</b> ".$event->CAPPOC2Email."<br />";
			}
			if ($event->ExtPOCName != '') {
				$html .= "<b>CAP Point of Contact:</b> ".$event->ExtPOCName."<br />";
				$html .= "<b>CAP Point of Contact phone:</b> ".$event->ExtPOCPhone."<br />";
				$html .= "<b>CAP Point of Contact email:</b> ".$event->ExtPOCEmail."<br />";
			}
			if ($l) {
				$member = Member::Estimate($event->Author);
				if($member && strlen($member->RankName) > 0) {
					$html .= "<b>Event Author:</b> ".$member->RankName."<br />";
				}
				if (strlen($event->AdditionalEmailAddresses) > 0) {
					$html .= "<b>Additional Email Addresses:</b> ".$event->AdditionalEmailAddresses.'<br />';
				}
				if ((strlen($event->GroupEventNumber) > 0) && ($event->GroupEventNumber != "Not Required")) {
					$html .= "<b>Group Event Number:</b> ".$event->GroupEventNumber.'<br />';
				}
				if ($event->PublishToWingCalendar == 1) {
					$html .= "<b>Publish to Wing Calendar:</b> Yes<br />";
				} else {
					$html .= "<b>Publish to Wing Calendar:</b> No<br />";
				}
			}


			if ($l) {
				if(strlen($event->Administration) > 0) {
					$html .= "<b>Event administration comments:</b> ".$event->Administration.'<br />';
					$html .= "<br /><br />";
				}

				$pdo = DBUtils::CreateConnection();
				$stmt = $pdo->prepare("SELECT FileID FROM ".DB_TABLES['FileEventAssignments']." WHERE EID = :ev AND AccountID = :aid;");
				$stmt->bindValue(':aid', $a->id);
				$stmt->bindValue(':ev', $event->EventNumber);
				$data = DBUtils::ExecutePDOStatement($stmt);
				// if (count($data) > 0) {
					$html .= "<br /><br /><h2>Event Files</h2>";
				// }
				$hasfiles = false;
				foreach ($data as $row) {
					$file = File::Get($row["FileID"]);
					if(($event->isPOC($m) || $m->hasPermission('SignUpEdit'))) {
						$ab = new AsyncButton(Null,  "Delete", "deleteEventFile");
						$ab->data = 'delfi'.json_encode(array(
							'fid' => $file->ID,
							'eid' => $event->EventNumber	
						));
					}
					if ($file) {
						$html .= (new FileDownloader($file->Name, $file->ID))->getHtml()." ";
						$html .= $ab."<br />";
						$hasfiles = true;
					}
				}
				if(!$hasfiles) { $html .= "There are no files associated with this event.<br />"; }
				$form = new AsyncForm (Null, 'Add File');
				$form->addField ('eventFiles', '&nbsp;', 'file');
				$form->addHiddenField ('eid', $event->EventNumber);
				$form->addHiddenField ('uribase-index', $event->EventNumber);
				$form->addHiddenField ('func', 'addfiles');
				$form->reload = true;
				$html .= $form;

				//add files list
				$html .= "<br /><br />";

				$attendance = $event->getAttendance();
				if (!$attendance->has($m) && (time() < $event->PickupDateTime) ) {
					$form = new AsyncForm (Null, 'Sign up');
					$form->addField('comments', 'Comments', 'textarea')->
						addField('capTransport', 'Are you using CAP transportation?', 'checkbox')->
						addHiddenField('eid', $ev)->
						addHiddenField('func', 'signup');
					$form->reload = true;
					$html .= $form;
					$html .= "<br /><br />";
				}
			}

			if ($l && $a->hasMember($m)) {
				$dlist = new DetailedListPlus("Current Attendance");
				$alist = new AsyncButton(null, "CAPID list", "attendanceIDPopup");
				$elist = new AsyncButton(null, "Email list", "attendanceEmailPopup");
				$clist = new AsyncButton(null, "Cronological list", "chronoNamePopup");
				if(count($attendance->EventAttendance) > 0) {
					$html .= $alist->getHtml('atdir'.$event->EventNumber);
					$html .= " | ".$elist->getHtml('ateml'.$event->EventNumber);
					$html .= " | ".$clist->getHtml('atchr'.$event->EventNumber);
				}
				foreach ($attendance as $capid => $data) {
					$member = Member::Estimate($capid);
					if ($member) {
						if (($event->isPOC($m) || $m->hasPermission('SignUpEdit')) || $capid == $m->uname) {
							$form = new AsyncForm(Null, Null, "nopadtop");

							$form->reload = true;
							$form->addField("comments", "Comments", "textarea", Null, ['value' => $data['Comments']], $data['Comments']);
							$form->addField("plantouse", "Plan to use CAP transportation", "checkbox", Null, Null, $data['PlanToUseCAPTransportation']);
							$form->addField("status", "Status", "radio", Null, [
								'Committed/Attended',
								'Rescinded commitment to attend',
								'No show'
							], $data['Status']);
							$form->addHiddenField('capid', $capid);
							$form->addHiddenField('eid', $ev);
							$form->addHiddenField('func', 'signupedit');
							$ab = new AsyncButton(Null, "Delete", "deleteAttendanceRecord");
							$ab->data = 'atdel'.json_encode(array(
								'cid' => $capid,
								'eid' => $event->EventNumber	
							));
							$memberinfo = "$capid: $member->memberRank $member->memberName";
							if(strlen($member->Squadron)>1) { 
								if($a->hasMember($member)) {
									$memberinfo .= " [".$member->Squadron."]"; 
								} else {
									$memberinfo .= " <b>[".$member->Squadron."]</b>"; 
								}
							}
							$memEmail = '';
							$memPhone = '';
							if($member->contact) {
								$memEmail = $member->getBestEmail();
								$memPhone = $member->getBestPhone();
							}
							if($memEmail || $memPhone) {
								//set contactstring
								//" [".$member->getBestEmail().", ".$member->getBestPhone()."]"
								$contactString = " [";
								if($memEmail && $memPhone) {
									$contactString .= $member->getBestEmail().", ".$member->getBestPhone();
								} elseif ($memEmail) {
									$contactString .= $member->getBestEmail();
								} else {
									$contactString .= $member->getBestPhone();
								}
								$contactString .= "]";
							} else {
								$contactString = "";
							}
							
							$memberinfo .= (($event->isPOC($m) || $m->hasPermission("EditEvent")?$contactString: ""));
							if ($member) $dlist->addElement($memberinfo, $form->getHtml(), $ab);
						} else {	
							$color = ($data['Status'] == 'Committed/Attended' ? 'color:green' :
								($data['Status'] == 'Rescinded commitment to attend' ? 'color:yellow' :
									($data['Status'] == 'No show' ? 'color:red' : '')));
							$dlist->addElement("$capid: $member->memberRank $member->memberName", "Comments: {$data['Comments']}<br />Status: <span style=\"$color\">{$data['Status']}</span><br />
							Plan to use CAP transportation: ".($data['PlanToUseCAPTransportation']?'Yes':'No')."<br />");
						}
					}
				}
				if(count($attendance->EventAttendance) > 0) {
					$html .= $dlist;
				}

			} else if ($l) {
				$html .= "<h4>Attendance information display is restricted to unit members</h4>";
			} else {
				$html .= "<h4>Please sign in to view restricted content</h4>";
				$html .= JSSnippet::SigninLink("Sign in now");
			}

			return [
				'body' => [
					'MainBody' => $html,
					'BreadCrumbs' => UtilCollection::GenerateBreadCrumbs([
						[
							'Target' => '/',
							'Text' => 'Home',
						],
						[
							'Target' => '/calendar',
							'Text' => 'Calendar'
						],
						[
							'Target' => '/eventviewer/'.$ev,
							'Text' => "View '$event->EventName'"
						]
					])
				],
				'title' => $event->EventName
			];
		}

		public static function doPost ($e, $c, $l, $m, $a) {
			if (!$l) {
				return ['error' => 411];
			}
			if (!isset($e['raw']['func'])) {
				return ['error' => 311];
			}
			if ($e['raw']['func'] == 'addfiles') {
				$event = null;
				if (isset ($e['form-data']['eid'])) {
					$event = Event::Get($e['form-data']['eid']);
					if (!$event) {
						return ['error' => 311];
					}
					$event = $e['form-data']['eid'];
				} else {
					return ['error' => 311];
				}
				$success = true;
				$pdo = DBUtils::CreateConnection();
				if (isset ($e['form-data']['eventFiles'][0])) {
					foreach ($e['form-data']['eventFiles'] as $fileID) {
						$stmt = $pdo->prepare("INSERT INTO ".DB_TABLES['FileEventAssignments']." (FileID, EID, AccountID) VALUES (:fileid, :evtid, :aid);");
						$stmt->bindValue(':fileid', rtrim($fileID));
						$stmt->bindValue(':evtid', $event);
						$stmt->bindValue(':aid', $a->id);
						$success = $stmt->execute() | $success;
					}
				}
				return [
					'body' => $success ? 'All file uploads worked!' : 'A file failed to upload'
				];
			} else if ($e['raw']['func'] == 'signup') {
					$attendance = new Attendance($e['form-data']['eid']);
				$ev = Event::Get($e['form-data']['eid']);
				if ($attendance->has($m)) {
					return "You're already signed up!";
				}
				//add in here a flag to add an entry to an event signup table
				SignUps::Add($a->id, $ev->EventNumber);

				/// @TODO: change this from 'best' email to 'all' emails
				UtilCollection::sendFormattedEmail([
					'<'.$m->getBestEmail().'>' => $m->getBestEmail(),
					'<'.$m->getBestContact(['CADETPARENTEMAIL']).'>' => $m->getBestContact(['CADETPARENTEMAIL'])
				], 'You have successfully signed up for event '.$a.'-'.$e['form-data']['eid'].', '.$ev->EventName.'.
				View more information <a href="'.(new Link('eventviewer', 'here', [$e['form-data']['eid']]))->getURL(false).'">here</a>',
				'Event signup: Event '.$ev->EventNumber);
				return $attendance->add($m, 
					$e['form-data']['capTransport'] == 'true', 
					$e['form-data']['comments']) ? 
						"You're signed up!" : "Something went wrong!";
			} else if ($e['raw']['func'] == 'signupedit') {
				$attendance = new Attendance($e['form-data']['eid']);
				$mem = Member::Estimate($e['form-data']['capid']);
				if (!$mem || !$attendance->has($mem)) {
					return ['error' => 311];
				}
				if ($m->hasPermission('SignUpEdit') && $e['form-data']['capid'] != $m->uname) {
					$member = Member::Estimate($e['form-data']['capid']);
				} else {
					$member = $m;
				}
				$attendance->modify($member, $e['form-data']['plantouse'] == 'true', 
					$e['form-data']['comments'], $e['form-data']['status']);
			} else {
				return [
					'error' => 311
				];
			}
		}

		public static function doPut ($e, $c, $l, $m, $a) {
			if (!$l) {
				return [
					'error' => 411
				];
			}
			if (isset($e['raw']['data'])) {
				$ev = $e['raw']['data'];
				$func = substr($ev, 0, 5);
				$data = substr($ev, 5);
				if ($func == 'delet') {
					$event = Event::Get($data);
					if (!$event) return ['error' => '311'];
					$func = 'delete';
				} else if ($func == 'clone') {
					$ev = $data;
					$event = Event::Get($ev);
					if (!$event) return ['error' => '311'];
				} else if ($func == 'atmod' || $func == 'atdel') {
					$data = json_decode($data, true);
					$event = Event::Get($data['eid']);
					if (!$event) return ['error' => '311'];
				}
			} else {
				return ['error' => '311'];
			}

			if ($func == "delete" && ($event->isPOC($m) || $m->hasPermission("EditEvent"))) {
				$data = $event->remove();
				var_export($data);
				echo "$event->EventNumber\n";
				return JSSnippet::PageRedirect('Calendar') . ($data ? "Event deleted" : "Some error occurred");
			} else if ($func == "clone" && ($m->hasPermission("CopyEvent"))) {

				$allowed = false;
				$monthevents = Null;
				$eventlist = '';
				$eventLimit = 0;

				$account = $a;

				//check to see if within event limit, if applicable
				if(!$account->paid || ($account->paid && $account->expired)) {
					$pdo = DB_Utils::CreateConnection();
		
					//get month date range
					$monthNumber = (int)date('n',$e['raw']['predata'])-1;
					$thisYear = (int)date('Y',$e['raw']['predata']);
					$nextMonth = UtilCollection::createDate($thisYear, $monthNumber+1);
					$thisMonth = UtilCollection::createDate($thisYear, $monthNumber);

					$sqlin = 'SELECT Created, EventNumber FROM '.DB_TABLES['EventInformation']; 
					$sqlin .= ' WHERE ((MeetDateTime < :monthend AND MeetDateTime > :monthstart) ';
					$sqlin .= 'OR (PickupDateTime > :monthstart AND PickupDateTime < :monthend)) ';
					$sqlin .= 'AND AccountID = :aid ORDER BY Created;';
					$stmt = $pdo->prepare($sqlin);
					$stmt->bindValue(':monthend', $nextMonth->getTimestamp(), PDO::PARAM_INT);
					$stmt->bindValue(':monthstart', $thisMonth->getTimestamp(), PDO::PARAM_INT);
					$stmt->bindValue(':aid', $account->id);
					$monthevents = DB_Utils::ExecutePDOStatement($stmt);

					$eventLimit = $account->unpaidEventLimit;
				} else {
					$eventLimit = $account->paidEventLimit;
				}
		
				//check monthevents to prevent access error when Null
				if(!is_null($monthevents) && (count($monthevents) > $eventLimit)) {
					$months = ['January','February','March','April','May','June','July',
						'August','September','October','November','December'];
					$response = "This account has exceeded the allowable event count limit for the month of ";
					$response .= $months[$monthNumber]." ".$thisYear." and this event cannot be copied at this time.  ";
					$response .= "Please contact someone on your account administrative staff (";
					foreach ($account->adminName as $capid => $rankname) {
						$response .= "<a href=\"mailto:".$account->adminEmail[$capid];
						$response .= "?subject=Upgrade our CAPUnit.com account, please";
						$response .= "&body=".$rankname.", please contact sales@capunit.com to upgrade our CAPUnit.com account ";
						$response .= "so that we can have more events on our calendar!\">";
						$response .= $rankname."</a>, ";
					}
					$response = rtrim($response, ', ');
					$response .= ") to request a CAPUnit.com account upgrade.";
					return [
						'body' => $response,
						'headers' => [
							'X-Event-Copy-Status' => 'denieded'
						]
					];
				}

				$d = $event->data;
				unset($d['EventNumber']);
				$ne = Event::Create($d);
				$d1 = $ne->StartDateTime - $ne->MeetDateTime;
				$d2 = $ne->EndDateTime - $ne->StartDateTime;
				$d3 = $ne->PickupDateTime - $ne->StartDateTime;
				$ne->StartDateTime = $e['raw']['predata'];
				$ne->MeetDateTime = $ne->StartDateTime - $d1;
				$ne->EndDateTime = $ne->StartDateTime + $d2;
				$ne->PickupDateTime = $ne->StartDateTime + $d3;				
				//return value for updateCalendarEvent is currently text and is undisplayed here
				try {
					GoogleCalendar::updateCalendarEvent($ne);
				} catch (Exception $e) {
					//need to indicate to user that calendar update failed
				}
				//eventMailer should return an execution status and be reported/error recorded
				eventMailer($m, $ne);
				return [
					'body' => [
						'MainBody' => $ne->EventNumber,
						'headers' => [
							'X-Event-Copy-Status' => 'accepteded'
						]
					]
				];
			} else if ($func == 'atmod' && ($m->hasPermissionLevel("SignUpEdit") || $event->isPOC($m))) {
				
			} else if (($func == 'atdel' && $m->AccessLevel == "Admin")) {
				$event->getAttendance()->remove(Member::Estimate($data['cid']));
			} else if (($func == 'atchr' && $m->AccessLevel == "Admin")) {
				//chronological sign-up list (need to separate by Senior/Cadet)
				$html = '';
				$event = Event::Get((int)$data);
				$pdo = DB_Utils::CreateConnection();
				
				$sqlin = 'SELECT * FROM '.DB_TABLES['Attendance']; 
				$sqlin .= ' WHERE EventID=:eid AND AccountID=:aid ';
				$sqlin .= ' ORDER BY Timestamp;';
				$stmt = $pdo->prepare($sqlin);
				$stmt->bindValue(':aid', $account->id);
				$stmt->bindValue(':eid', $event->EventNumber);
				$attendancerecords = DB_Utils::ExecutePDOStatement($stmt);
		
				$html = '';
				foreach ($attendancerecords as $record) {
					$attendee = Member::Estimate($record['CAPID']);
					if($attendee) {
						$html .= date(DATE_RSS, $attendee->Timestamp).': '.$attendee->MemberRankName.'<br />';
					}
				}
				return $html;
			} else if (($func == 'atdir')) {
				$html = '';
				$event = Event::Get((int)$data);
				if ($event->CAPPOC1ID != '') {
					$html .= $event->CAPPOC1ID.", ";
				}
				if ($event->CAPPOC2ID != '') {
					$html .= $event->CAPPOC2ID.", ";
				}
				$att = $event->getAttendance();
				foreach ($att as $cid => $data) {
					$html .= $cid.', ';
				}
				$emails = explode(", ", $html);
				$emails = array_unique($emails);
				$html = implode(", ", $emails);
				return rtrim($html, ', ');
			} else if (($func == 'ateml')) {
				$html = '';
				$event = Event::Get((int)$data);
				if ($event->CAPPOC1Email != '') {
					$html .= $event->CAPPOC1Email.", ";
				}
				if ($event->CAPPOC2Email != '') {
					$html .= $event->CAPPOC2Email.", ";
				}
				if ($event->ExtPOCEmail != 0) {
					$html .= $event->ExtPOCEmail.", ";
				}
				$att = $event->getAttendance();
				foreach ($att as $cid => $data) {
					$attendee = Member::Estimate($cid);
					if($attendee) {
						$html .= $attendee->getBestEmail().', ';
					}
				}
				$emails = explode(", ", $html);
				$emails = array_unique($emails);
				$html = implode(", ", $emails);
				return rtrim($html, ', ');
			} else if (($func == 'sende')) {
				$event = Event::Get((int)$data);
				return SignUps::SendEvent($a->id, $event->EventNumber, true);
			} else if (($func == 'delfi') && $m->hasPermission('EditEvent')) {
				$pdo = DBUtils::CreateConnection();
				$data = json_decode($data, true);
				$stmt = $pdo->prepare("DELETE FROM ".DB_TABLES['FileEventAssignments']." WHERE FileID = :fid AND EID = :eid AND AccountID = :aid");
				$stmt->bindValue(':fid', $data['fid']);
				$stmt->bindValue(':eid', $data['eid']);
				$stmt->bindValue(':aid', $a->id);
				return $stmt->execute() ? 'File removed' : 'Error when deleting file';
			}  else {
				return ['error' => '402'];
			}	
		}
	}
