<?php
	define ("USER_REQUIRED", true);
	
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
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
			if ($m->hasPermission("AddEvent")) {
				$perm = 'true';
				$notInAcct = !$a->hasMember($m);
				$stmt = $pdo->prepare("SELECT ORGID FROM ".DB_TABLES['Member']." WHERE CAPID = :cid;");
				$stmt->bindValue(":cid", $m->capid);
				$orgid_data = DBUtils::ExecutePDOStatement($stmt);
				$mbr_orgid = $orgid_data['ORGID'];
				$stmt = $pdo->prepare("SELECT UnitID FROM ".DB_TABLES['Account']." WHERE AccountID = :aid;");
				$stmt->bindValue(":aid", $a->id);
				$data = DBUtils::ExecutePDOStatement($stmt);
				$notInAcct = 'true';
				foreach ($data as $datum) {
					if ($datum['UnitID'] == $mbr_orgid) {
						$notInAcct = 'false';
					}
				}
				$notLinked = true; //need to query database for linked event
				//need to add linked event fields to database before implementing
				if ($perm && $notInAcct && notLinked) {
//					$html .= " | ".new Link ("linkEvent", "Link To Event", [$ev]);
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
			$html .= "Event: ".$event->EventName.'<br />';
			$html .= "Event ID Number: $a-$ev<br />";
			$html .= "Please contact the event POC listed below directly with any questions or comments<br />";
			
			// Second block
			$html .= "Meet at ".date('h:i A \o\n n/j/Y', $event->MeetDateTime).' at '.$event->MeetLocation.'<br />';
			$html .= "Start at ".date('h:i A \o\n n/j/Y', $event->StartDateTime).' at '.$event->EventLocation.'<br />';
			$html .= "End at ".date('h:i A \o\n n/j/Y', $event->EndDateTime).'<br />';
			$html .= "Pickup at ".date('h:i A \o\n n/j/Y', $event->PickupDateTime).' at '.$event->PickupLocation.'<br /><br />';

			// Third (fourth?) block
			$html .= "Transportation provided: ".($event->TransportationProvided == 1 ? 'YES' : 'NO').'<br />';
			$html .= "Uniform: ".$event->Uniform.'<br />';
			$html .= "Comments: ".$event->Comments.'<br />';
			$html .= "Activity: ".$event->Activity.'<br />';
			$html .= "Required forms: ".$event->RequiredForms.'<br />';  
			$html .= "Required equipment: ".$event->RequiredEquipment.'<br />';
			$html .= "Registration Deadline: ".date('n/j/Y', $event->RegistrationDeadline).'<br />';
			$html .= "Meals: ".$event->Meals.'<br />';
			if ($event->CAPPOC1ID != 0) {
				$html .= "CAP Point of Contact: ".$event->CAPPOC1Name."<br />";
				$html .= "CAP Point of Contact phone: ".$event->CAPPOC1Phone."<br />";
				$html .= "CAP Point of Contact email: ".$event->CAPPOC1Email."<br />";
			}
			if ($event->CAPPOC2ID != 0) {
				$html .= "CAP Point of Contact: ".$event->CAPPOC2Name."<br />";
				$html .= "CAP Point of Contact phone: ".$event->CAPPOC2Phone."<br />";
				$html .= "CAP Point of Contact email: ".$event->CAPPOC2Email."<br />";
			}
			if ($event->ExtPOCName != '') {
				$html .= "CAP Point of Contact: ".$event->ExtPOCName."<br />";
				$html .= "CAP Point of Contact phone: ".$event->ExtPOCPhone."<br />";
				$html .= "CAP Point of Contact email: ".$event->ExtPOCEmail."<br />";
			}
			$html .= "Desired number of Participants: ".$event->DesiredNumParticipants.'<br />';
			$html .= "Event status: ".$event->Status.'<br />';

			if ($l) {
				$html .= "<br /><br />";
				$html .= "Event administration comments: ".$event->Administration.'<br />';
				$html .= "<br /><br />";
				$dlist = new DetailedListPlus("Current Attendance");
				$alist = new AsyncButton(null, "Short attendance listing", "attendanceListingPopup");
				$html .= $alist->getHtml('atdir'.$event->EventNumber);
				$attendance = $event->getAttendance();
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
							if ($member) $dlist->addElement("$capid: $member->memberRank $member->memberName".(($event->isPOC($m) || $m->hasPermission("EditEvent")?" (".$member->getBestEmail().")": "")), $form->getHtml(), $ab);
						} else {	
							$color = ($data['Status'] == 'Committed/Attended' ? 'color:green' :
								($data['Status'] == 'Rescinded commitment to attend' ? 'color:yellow' :
									($data['Status'] == 'No show' ? 'color:red' : '')));
							$dlist->addElement("$capid: $member->memberRank $member->memberName", "Comments: {$data['Comments']}<br />Status: <span style=\"$color\">{$data['Status']}</span><br />
							Plan to use CAP transportation: ".($data['PlanToUseCAPTransportation']?'Yes':'No')."<br />");
						}
					}
				}
				$html .= $dlist;

				if (!$attendance->has($m)) {
					$form = new AsyncForm (Null, 'Sign up');
					$form->addField('comments', 'Comments', 'textarea')->
						addField('capTransport', 'Are you using CAP transportation?', 'checkbox')->
						addHiddenField('eid', $ev)->
						addHiddenField('func', 'signup');
					$form->reload = true;
					$html .= $form;
				}
			} else {
				$html .= "<h4>Please sign in to view restricted content</h4>";
				$html .= "<a href=\"#\" id=\"signin_link\">Sign in now</a>";
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
				if (!$attendance->has($m)) {
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
				return JSSnippet::PageRedirect('Calendar') . ($event->remove() ? "Event deleted" : "Some error occurred");
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
				return $ne->save() ? $ne->EventNumber : 0;
			} else if ($func == 'atmod' && ($m->hasPermissionLevel("SignUpEdit") || $event->isPOC($m))) {
				
			} else if (($func == 'atdel' && $m->AccessLevel == "Admin")) {
				$event->getAttendance()->remove(Member::Estimate($data['cid']));
			} else if (($func == 'atdir')) {
				$html = '';
				$event = Event::Get((int)$data);
				$att = $event->getAttendance();
				foreach ($att as $cid => $data) {
					$html .= $cid.', ';
				}
				return rtrim($html, ', ');
			} else {
				return ['error' => '402'];
			}	
		}
	}