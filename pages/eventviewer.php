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
				if ($m->hasPermission("SignUpEdit") && $a->paid) {
					$html .= " | ".new Link ("multiadd", "Add attendees", [$ev]);
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
				$pdo = DBUtils::CreateConnection();
				$stmt = $pdo->prepare("SELECT FileID FROM ".DB_TABLES['FileEventAssignments']." WHERE EID = :ev AND AccountID = :aid;");
				$stmt->bindValue(':aid', $a->id);
				$stmt->bindValue(':ev', $event->EventNumber);
				$data = DBUtils::ExecutePDOStatement($stmt);
				if (count($data) > 0) {
					$html .= "<br /><br /><h3>Event Files</h3>";
				}
				foreach ($data as $row) {
					$file = File::Get($row["FileID"]);
					if ($file) {
						$html .= (new FileDownloader($file->Name, $file->ID))->getHtml()."<br />";
					}
				}
				$html .= "<br /><br />";
				if(strlen($event->Administration) > 0) {
					$html .= "<b>Event administration comments:</b> ".$event->Administration.'<br />';
					$html .= "<br /><br />";
				}

				$attendance = $event->getAttendance();
				if (!$attendance->has($m)) {
					$form = new AsyncForm (Null, 'Sign up');
					$form->addField('comments', 'Comments', 'textarea')->
						addField('capTransport', 'Are you using CAP transportation?', 'checkbox')->
						addHiddenField('eid', $ev)->
						addHiddenField('func', 'signup');
					$form->reload = true;
					$html .= $form;
					$html .= "<br /><br />";
				}

				$dlist = new DetailedListPlus("Current Attendance");
				$alist = new AsyncButton(null, "CAPID list", "attendanceIDPopup");
				$elist = new AsyncButton(null, "Email list", "attendanceEmailPopup");
				if(count($attendance->EventAttendance) > 0) {
					$html .= $alist->getHtml('atdir'.$event->EventNumber);
					$html .= " | ".$elist->getHtml('ateml'.$event->EventNumber);
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
			if ($e['raw']['func'] == 'signup') {
				$attendance = new Attendance($e['form-data']['eid']);
				$ev = Event::Get($e['form-data']['eid']);
				if ($attendance->has($m)) {
					return "You're already signed up!";
				}
				//add in here a flag to add an entry to an event signup table

				//change this from 'best' email to 'all' emails
				UtilCollection::sendFormattedEmail([
					'<'.$m->getBestEmail().'>' => $m->getBestEmail(),
					'<'.$m->getBestContact(['CADETPARENTEMAIL']).'>' => $m->getBestContact(['CADETPARENTEMAIL'])
				], 'You have successfully signed up for event '.$a.'-'.$e['form-data']['eid'].', '.$ev->EventName.'.
				View more information <a href="'.(new Link('eventviewer', 'here', [$e['form-data']['eid']]))->getURL(false).'">here</a>',
				'Event signup: Event '.$ev->EventNumber);
				return $attendance->add($m, 
					$e['form-data']['capTransport'] == 'true', 
					$e['form-data']['comments']) === true ? 
						"You're signed up!" : "Something went wrong!";
			} else if ($e['raw']['func'] == 'signupedit') {
				$attendance = new Attendance($e['form-data']['eid']);
				print_r($attendance);
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

			if ($a->paid && $func == "delete" && ($event->isPOC($m) || $m->hasPermission("EditEvent"))) {
				$data = $event->remove();
				var_export($data);
				echo "$event->EventNumber\n";
				return JSSnippet::PageRedirect('Calendar') . ($data ? "Event deleted" : "Some error occurred");
			} else if (($a->paid || $a->getEventCount() < 5) && $func == "clone" && ($m->hasPermission("CopyEvent"))) {
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
						'MainBody' => $ne->EventNumber
					]
				];
			} else if ($func == 'atmod' && ($m->hasPermissionLevel("SignUpEdit") || $event->isPOC($m))) {
				
			} else if (($func == 'atdel' && $m->AccessLevel == "Admin")) {
				$event->getAttendance()->remove(Member::Estimate($data['cid']));
			} else if (($func == 'atdir')) {
				$html = '';
				$event = Event::Get((int)$data);
				if ($event->CAPPOC1ID != '') {
					$html .= $event->CAPPOC1ID.", ";
				}
				if ($event->CAPPOC2ID != '') {
					$html .= $event->CAPPOC2Email.", ";
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
			} else {
				return ['error' => '402'];
			}	
		}
	}
