<?php
	define ("USER_REQUIRED", true);

	function copyArray($arr) {
		$ret = [];
		foreach ($arr as $key => $val) {
			$ret[$key] = $val;
		}
		return $ret;
	}

	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			global $_ACCOUNT;
			$pdo = DB_Utils::CreateConnection();

			$ev = isset($e['uri'][$e['uribase-index']]) ? $e['uri'][$e['uribase-index']] : false;
			$event = $ev ? Event::Get((int)$ev, $a) : false;

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
				$html .= " | ".(new AsyncButton('rostercadet', 'Download Cadet roster', 'rosterCadet'))->getHtml($ev);
				$html .= " | ".(new AsyncButton('rostersenior', 'Download Senior Member roster', 'rosterSenior'))->getHtml($ev);
				if ($m->hasPermission("SignUpEdit") || $event->isPOC($m)) {
					$html .= "<br />".new Link ("multiadd", "Add attendees", [$ev]);
					$html .= " | ".new Link ("scanadd", "Scan attendee CAPID", [$ev]);
//					$html .= " | ".(new AsyncButton(Null, 'Send attendance summary','sendAttendance'))->getHtml('sende'.$ev);
				}
				if ($event->SourceEventNumber > 0) {
					$sourceEvent = Event::Get($event->SourceEventNumber, new Account($event->SourceAccountID));
					$pdo = DB_Utils::CreateConnection();
					$stmt = $pdo->prepare('SELECT * FROM '.DB_TABLES['Accounts'].' WHERE AccountID=:aid AND MainOrg=1;');
					$stmt->bindValue(':aid', $event->SourceAccountID);
					$data = DB_Utils::ExecutePDOStatement($stmt);
					if (count($data) != 1) {
						//there was an error; need to add error logging here.  return
						return [
							'error' => 311
						];
					} else {
						$data = $data[0];
						$stmt = $pdo->prepare('SELECT Region, Wing, Unit, Name FROM '.DB_TABLES['Organization'].' WHERE ORGID = :oid;');
						$stmt->bindValue(':oid', $data['UnitID']);
						$data2 = DB_Utils::ExecutePDOStatement($stmt);
						if (count($data2) != 1) {
							//there was an error; need to add error logging here.  return
							return [
								'error' => 311
							];
						} else {
							$data2 = $data2[0];
							$linkedUnitName = $data2['Region']."-".$data2['Wing']."-".$data2['Unit'];
							$html .= '<br /><b>This event is linked to ';
							$html .= '<a href="https://'.$event->SourceAccountID.'.capunit.com/eventviewer/'.$event->SourceEventNumber.'" target="_blank">';
							$html .= $linkedUnitName.' Event '.$event->SourceEventNumber.'</a></b>';
							if ($m->hasPermission("LinkEvent")) {
								$html .= "<br />".(new AsyncButton(Null, 'Unlink this event','linkEventUnset'))->getHtml('linku'.$ev);
		//need to refresh page after this to remove linked event text and unlink link?
							}
						}
					}
				}
				$breaks = 'true';
			}
			if($l) {
				$perm = false;
				foreach ($m->genAccounts() as $acc) {
					$perm = $perm || $m->hasPermission('LinkEvent', 1, $acc);
				}
				$stmt = $pdo->prepare('SELECT * FROM '.DB_TABLES['EventInformation'].' WHERE SourceEventNumber=:sen AND SourceAccountID=:sai;');
				$stmt->bindValue(':sen', $ev);
				$stmt->bindValue(':sai', $_ACCOUNT->id);
				$evdata = DB_Utils::ExecutePDOStatement($stmt);
				$homeAccountID = UtilCollection::GetAccountIDFromUnit($m->Squadron);
				$eventLinked = false;
				$moreHtml = '';
				if (count($evdata) > 0) {
					$moreHtml = "<br /><br />This event is linked to by the following event";
					if (count($evdata) > 1) { $moreHtml .= "s"; }
					$moreHtml .= ":<br />";
					foreach($evdata as $datum) {
						$newAccount = new Account($datum['AccountID']);
						$moreHtml .= "<a href=\"https://{$newAccount->id}.capunit.com/eventviewer/".$datum['EventNumber']."/\" target=\"_blank\">".$datum['AccountID']."-".$datum['EventNumber']."</a><br />";
						if($datum['AccountID'] == $homeAccountID) {
							$eventLinked = "<a href=\"https://{$newAccount->id}.capunit.com/calendar/\" target=\"_blank\">home unit calendar</a><br />";
						}
					}
				}
				if (($a->hasMember($m) || $perm) && $eventLinked) { $html .= $moreHtml; }
				if ($perm && !$a->hasMember($m) && !$eventLinked) {
//					$html .= "<br />".(new AsyncButton(Null, 'Link to this event in the '.$m->Squadron.' calendar','linkEventSet'))->getHtml('links'.$ev);
//				} else if ($perm && $notInAcct) {
//					$html .= new Link ("linkeventunset", "Unlink from this event in the ".$m->Squadron." calendar", [$ev]);
					//need to implement unlink in own event view page, not just in remote account event
				}
				$breaks = 'true';
			}
			if ($breaks == 'true') {
				$html .= "<br /><br />";
			}

			if ($event->Status == 'Draft') {
				$html .= '<span class="warning">WARNING: As this event is only a draft, dates, times and other details may change</span><br /><br />';
			}

			// Title block
			if($l) {
				$myORGID = UtilCollection::GetOrgidFromUnit($m->Squadron);
				$pdo = DB_Utils::CreateConnection();
				$stmt = $pdo->prepare('SELECT AccountID FROM '.DB_TABLES['Accounts'].' WHERE UnitID = :oid;');
				$stmt->bindValue(':oid', $myORGID);
				$data = DB_Utils::ExecutePDOStatement($stmt);
				if (count($data) == 0) {
					$html .= "<br /><h4>Your unit (".$m->Squadron.") does not yet have a CAPUnit.com account.  ";
					$html .= "<a href=\"mailto:sales@capunit.com\">Contact us</a> to sign up your unit for a free ";
					$html .= "CAPUnit.com account and you can track signups and attendance with ease!</h4><br />";
				}
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
				if($l) {
					$formsText = $event->RequiredForms;
					if(strstr($formsText, "CAPF 32") || strstr($formsText, "CAPF 60-80")) {
						$html .= "<b>Click on link to download pre-filled form:</b> ".(new AsyncButton('capf6080', 'CAPF 60-80 Civil Air Patrol Cadet Activity Permission Slip', 'capf6080'))->getHtml($ev).'<br />';
					}
				} else {
				}
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
				$teamdata = DB_Utils::ExecutePDOStatement($stmt);
				if(count($teamdata) > 0) {
					$html .= "<b>Team Name:</b> ".$teamdata[0]['TeamName'].'<br />';
				}
			}
			$html .= "<b>Desired number of Participants:</b> ".$event->DesiredNumParticipants.'<br />';
			$html .= "<b>Event status:</b> ".$event->Status.'<br /><br />';

			// Fourth block
			if ($event->CAPPOC1ID != 0) {
				$html .= "<b>CAP Squadron Primary Point of Contact:</b> ".$event->CAPPOC1Name."<br />";
				if($l) {
					$html .= "<b>CAP Squadron Primary Point of Contact phone:</b> ".$event->CAPPOC1Phone."<br />";
					$html .= "<b>CAP Squadron Primary Point of Contact email:</b> ".$event->CAPPOC1Email."<br />";
				}
			}
			if ($event->CAPPOC2ID != 0) {
				$html .= "<b>CAP Squadron Secondary Point of Contact:</b> ".$event->CAPPOC2Name."<br />";
				if($l) {
					$html .= "<b>CAP Squadron Secondary Point of Contact phone:</b> ".$event->CAPPOC2Phone."<br />";
					$html .= "<b>CAP Squadron Secondary Point of Contact email:</b> ".$event->CAPPOC2Email."<br />";
				}
			}
			if ($event->ExtPOCName != '') {
				$html .= "<b>Non-Squadron Point of Contact:</b> ".$event->ExtPOCName."<br />";
				if($l) {
					$html .= "<b>Non-Squadron Point of Contact phone:</b> ".$event->ExtPOCPhone."<br />";
					$html .= "<b>Non-Squadron Point of Contact email:</b> ".$event->ExtPOCEmail."<br />";
				}
			}
			if ($l) {
				if ($event->Author != 0) {
						$member = Member::Estimate($event->Author);
						if($member && strlen($member->RankName) > 0) {
							$html .= "<b>Event Author:</b> ".$member->RankName."<br />";
						}
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
				$filedata = DBUtils::ExecutePDOStatement($stmt);
				// if (count($data) > 0) {
					$html .= "<br /><br /><h2>Event Files</h2>";
				// }
				$hasfiles = false;
				foreach ($filedata as $row) {
					$file = File::Get($row["FileID"]);
					if(($event->isPOC($m) || $m->hasPermission('SignUpEdit')) && !!$file) {
						$ab = new AsyncButton(Null,  "Delete", "deleteEventFile");
						$ab->data = 'delfi'.json_encode(array(
							'fid' => $file->ID,
							'eid' => $event->EventNumber
						));
						$html .= (new FileDownloader($file->Name, $file->ID))->getHtml()." ";
						if(($event->isPOC($m) || $m->hasPermission('SignUpEdit'))) {
							$html .= $ab."<br />";
						}
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
					if ($eventLinked) {
						$html .= "Your home unit has one or more events linked to this one.  Please sign up ";
						$html .= "using your $eventLinked";
					} else { //event is not linked to home squadron
						$form = new AsyncForm (Null, 'Sign up');
						$form->addField('comments', 'Comments', 'textarea')->
							addField('capTransport', 'Are you using CAP transportation?', 'checkbox')->
							addHiddenField('eid', $ev)->
							addHiddenField('func', 'signup');
						if ($event->IsSpecial) {
							$form
								->addField('geoloc', 'What is your geographic location?', 'text')
								->addField('duty', 'What are your top three desired duty/training positions?', 'text')
								->addField('email', 'What is your email address?', 'text', Null, Null, $m->getBestEmail())
								->addField('phone', 'What is your phone?', 'text', Null, Null, $m->getBestPhone())
								->addField('uniform', 'What uniform are you planning to wear?', 'text');
						}
						$form->reload = true;
						$html .= $form;
					}
					$html .= "<br /><br />";
				}
			}

			if ($l && ($a->hasMember($m) || $a->id="mdx89" || $m->IsRioux || $event->isPOC($m) )) {
//			if ($l) {
				$dlist = new DetailedListPlus("Current Attendance");
				$alist = new AsyncButton(null, "CAPID list", "attendanceIDPopup");
				$elist = new AsyncButton(null, "Email list", "attendanceEmailPopup");
				$clist = new AsyncButton(null, "Cronological list", "chronoNamePopup");
				$slist = new AsyncButton(null, "Signup list", "signupPopup");
				if( (count($attendance->EventAttendance) > 0) && ($m->hasPermission('SignUpEdit') || $event->isPOC($m)) ) {
					$html .= $alist->getHtml('atdir'.$event->EventNumber);
					$html .= " | ".$elist->getHtml('ateml'.$event->EventNumber);
					$html .= " | ".$clist->getHtml('atchr'.$event->EventNumber);
					if($event->IsSpecial) {
						$html .= " | ".(new AsyncButton('signupspecialxl', 'Download Signup spreadsheet', 'signupSpecialXL'))->getHtml($ev);
						$html .= " | ".(new AsyncButton('signupspecial', 'Download Sign-up roster', 'signupSpecial'))->getHtml($ev);
					} else {
//						$html .= " | ".$slist->getHtml('atsul'.$event->EventNumber);
						$html .= " | ".(new AsyncButton('signupxl', 'Download Signup spreadsheet', 'signupXL'))->getHtml($ev);
						$html .= " | ".(new AsyncButton('signupevent', 'Download Sign-up roster', 'signupEvent'))->getHtml($ev);
					}
				}
				foreach ($attendance as $capid => $data) {
					$member = Member::Estimate($capid);
					if ($member) {
						if (($event->isPOC($m) || $m->hasPermission('SignUpEdit')) || $capid == $m->uname) {
							$form = new AsyncForm(Null, Null, "nopadtop");

							$form->reload = true;
							$form->addField("comments", "Comments", "textarea", Null, ['value' => $data['Comments']], $data['Comments']);
							$form->addField("plantouse", "Plan to use CAP transportation", "checkbox", Null, Null, $data['PlanToUseCAPTransportation']);

							if($event->isPOC($m) || $m->hasPermission('SignUpEdit')) {
								$form->addField('confirmed', 'Confirmed', 'checkbox', Null, Null, $data['Confirmed']);
							} else {
								$form->addField('conftext', $data['Confirmed'] ? "Attendance Confirmed" : "Attendance Not Confirmed", 'textread');
								$form->addHiddenField('confirmed', $data['Confirmed']);
							}

							$form->addField("status", "Status", "radio", Null, [
								'Committed/Attended',
								'Rescinded commitment to attend',
								'No show'
							], $data['Status']);

							$idf = (new AsyncButton('idcardfront', 'Download ID Card Front', 'idFront'))->getHtml($capid);

							$form->addHiddenField('capid', $capid);
							$form->addHiddenField('eid', $ev);
							$form->addHiddenField('func', 'signupedit');
							if ($event->IsSpecial) {
							/*	if ($event->isPOC($m) || ($a->hasMember($m) && $m->hasPermission('SignUpEdit')) || $m->IsRioux)
									$idfront = new AsyncButton('idcardfront', 'Download ID Card Front', 'idFront');
									$idback = new AsyncButton('idcardback', 'Download ID Card Back', 'idBack');
								}
							*/	$form->addField('geoloc', 'What is your geographic location?', 'text', Null, Null, $data['GeoLoc']);
								$form->addField('duty', 'What are your top 3 desired duty/training positions?', 'text', Null, Null, $data['DutyPreference']);
								$form->addField('email', 'What is your email address?', 'text', Null, Null, $data['EmailAddress']);
								$form->addField('phone', 'What is your cell phone number?', 'text', Null, Null, $data['PhoneNumber']);
								$form->addField('uniform', 'What uniform are you planning to wear?', 'text', Null, Null, $data['Uniform']);
//								$form->addField('idfront', $idfront->getHtml($capid), 'textread');
//								$form->addField('idback', $idback->getHtml($capid), 'textread');
							}
							$ab = new AsyncButton(Null, "Delete", "deleteAttendanceRecord");
							$ab->data = 'atdel'.json_encode(array(
								'cid' => $capid,
								'eid' => $event->EventNumber
							));
							if($data['Status'] == 'No show') {
								$memberinfo = "<font color=\"red\">$capid: $member->memberRank $member->memberName</font>";
							} else if($data['Status'] == 'Rescinded commitment to attend') {
								$memberinfo = "<font color=\"#FFA500\">$capid: $member->memberRank $member->memberName</font>";
							} else if($data['Confirmed'] == 1) {
								$memberinfo = "<font color=\"green\">$capid: $member->memberRank $member->memberName</font>";
							} else {
								$memberinfo = "$capid: $member->memberRank $member->memberName";
							}
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
						} else if (!$event->PrivateAttendance) {
							$color = ($data['Status'] == 'Committed/Attended' ? 'color:green' :
								($data['Status'] == 'Rescinded commitment to attend' ? 'color:orange' :
									($data['Status'] == 'No show' ? 'color:red' : '')));
							if($data['Confirmed'] == 1) {
								$memberinfo = "<font color=\"green\">$capid: $member->memberRank $member->memberName</font>";
								$confirmedStatus = " -- Attendance slot is confirmed";
							} else {
								$memberinfo = "$capid: $member->memberRank $member->memberName";
								$confirmedStatus = "";
							}
							$elementString = "Comments: {$data['Comments']}<br />Status: <span style=\"$color\">{$data['Status']}";
							$elementString .= $confirmedStatus."</span><br />Plan to use CAP transportation: ";
							$elementString .= ($data['PlanToUseCAPTransportation']?'Yes':'No')."<br />";
							$dlist->addElement($memberinfo, $elementString);
						}
					}
				}

				if(count($attendance->EventAttendance) > 0) {
					$html .= $dlist;
				}

			} else if ($l) {
/*				$form = new AsyncForm(Null, Null, "nopadtop");

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
*/
//				$html .= "<h4>Attendance information display is restricted to unit members</h4>";
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
					$event = Event::Get($e['form-data']['eid'], $a);
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
				$pdo = DBUtils::CreateConnection();
				$stmt = $pdo->prepare('SELECT AccountID FROM '.DB_TABLES['EventInformation'].' WHERE SourceEventNumber=:sen AND SourceAccountID=:sai;');
				$stmt->bindValue(':sen', $e['form-data']['eid']);
				$stmt->bindValue(':sai', $a->id);
				$data = DB_Utils::ExecutePDOStatement($stmt);
				$homeAccountID = UtilCollection::GetAccountIDFromUnit($m->Squadron);
				$eventLinked = false;
				if (count($data) > 0) {
					foreach($data as $datum) {
						if($datum['AccountID'] == $homeAccountID) {
							return "Your unit has at least one event linked to this one.  Please sign up on your unit's calendar.";
						}
					}
				}
				$ev = Event::Get($e['form-data']['eid'], $a);
				$attendance = $ev->attendance;
				if ($attendance->has($m)) {
					return "You're already signed up!";
				}
				//add in here a flag to add an entry to an event signup table
				SignUps::Add($a->id, $ev->EventNumber);

				/// @TODO: change this from 'best' email to 'all' emails
				UtilCollection::sendFormattedEmail([
					'<'.$m->getBestEmail().'>' => $m->getBestEmail(),
					'<'.$m->getBestContact(['CADETPARENTEMAIL']).'>' => $m->getBestContact(['CADETPARENTEMAIL'])
				], $m->RankName.', you have successfully signed up for event '.$a.'-'.$e['form-data']['eid'].', '.$ev->EventName.'.
				View more information <a href="'.(new Link('eventviewer', 'here', [$e['form-data']['eid']]))->getURL(false).'">here</a>',
				'Event signup: Event '.$ev->EventNumber);
				if($ev->IsSpecial) {
					if($e['form-data']['email'] == '') {$em = $m->getBestEmail();} else {$em = $e['form-data']['email'];}
					if($e['form-data']['phone'] == '') {$ep = $m->getBestPhone();} else {$ep = $e['form-data']['phone'];}
					return $attendance->add($m,
						$e['form-data']['capTransport'] == 'true',
						$e['form-data']['comments'],
						$e['form-data']['geoloc'],
						$e['form-data']['duty'],
						$em,
						$ep,
						$e['form-data']['uniform']) ?
							"You're signed up!" : "Something went wrong!";
				} else {
					return $attendance->add($m,
						$e['form-data']['capTransport'] == 'true',
						$e['form-data']['comments']) ?
							"You're signed up!" : "Something went wrong!";
				}
			} else if ($e['raw']['func'] == 'signupedit') {
				$event = Event::Get($e['form-data']['eid'], $a);
				$attendance = $event->getAttendance();
				$mem = Member::Estimate($e['form-data']['capid']);
				if (!$mem || !$attendance->has($mem)) {
					return ['error' => 311];
				}
				if ($m->hasPermission('SignUpEdit') && $e['form-data']['capid'] != $m->uname) {
					$member = Member::Estimate($e['form-data']['capid']);
				} else {
					$member = $m;
				}
				if($event->IsSpecial) {
					if($e['form-data']['email'] == '') {$em = $m->getBestEmail();} else {$em = $e['form-data']['email'];}
					if($e['form-data']['phone'] == '') {$ep = $m->getBestPhone();} else {$ep = $e['form-data']['phone'];}
					$attendance->modify($member, $e['form-data']['plantouse'] == 'true', 
						$e['form-data']['comments'], $e['form-data']['status'], $e['form-data']['geoloc'], $e['form-data']['duty'], 
						$em, $ep, $e['form-data']['uniform']);
				} else {
					$attendance->modify($member, $e['form-data']['plantouse'] == 'true', 
						$e['form-data']['comments'], $e['form-data']['status'], $e['form-data']['confirmed'] == 'true');
				}
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
					$event = Event::Get($data, $a);
					if (!$event) return ['error' => '311'];
					$func = 'delete';
				} else if ($func == 'clone') {
					$ev = $data;
					$event = Event::Get($ev, $a);
					if (!$event) return ['error' => '311'];
				} else if ($func == 'links') {
					$ev = $data;
					$event = Event::Get($ev, $a);
					if (!$event) return ['error' => '311'];
				} else if ($func == 'linku') {
					$ev = $data;
					$event = Event::Get($ev, $a);
					if (!$event) return ['error' => '311'];
				} else if ($func == 'atmod' || $func == 'atdel') {
					$data = json_decode($data, true);
					$event = Event::Get($data['eid'], $a);
					if (!$event) return ['error' => '311'];
				}
			} else {
				return ['error' => '311'];
			}

			if ($func == "delete" && ($event->isPOC($m) || $m->hasPermission("EditEvent"))) {
				eventMailerDelete($m, $event);
				$data = $event->remove($a);
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
				$ne->Author = $m->uname;
				//return value for updateCalendarEvent is currently text and is undisplayed here
				try {
					GoogleCalendar::updateCalendarEvent($ne);
				} catch (Exception $e) {
					//need to indicate to user that calendar update failed
				}
				//eventMailer should return an execution status and be reported/error recorded
				eventMailerClone($m, $event, $ne);
				return [
					'body' => [
						'MainBody' => $ne->EventNumber,
					],
					'headers' => [
						'X-Event-Copy-Status' => 'accepteded'
					]
				];
			} else if ($func == 'links') {
				$allowed = false;
				$monthevents = Null;
				$eventlist = '';
				$eventLimit = 0;

				//get user's home account
				$homeUnit = $m->Squadron;
				$homeAccountID = UtilCollection::GetAccountIDFromUnit($homeUnit);  //lib/general.php
				$account = new Account($homeAccountID);
				$hasPerm = $m->hasPermission("LinkEvent", 1, $account);

				if (!$hasPerm) {
					return ['error' => '402'];
				}

				$pdo = DB_Utils::CreateConnection();

				//check to see if within event limit, if applicable
				if(!$account->paid || ($account->paid && $account->expired)) {
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
					$response .= $months[$monthNumber]." ".$thisYear." and this event cannot be linked to at this time.  ";
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
					return $response;
				}

				$d = $event->data;
				$data = copyArray($d);
				unset($data['EventNumber']);
//				unset($data['AccountID']);
				$data['CAPPOC1ID'] = $m->capid;
				$data['CAPPOC1Name'] = $m->RankName;
				$data['CAPPOC1Phone'] = $m->getBestPhone();
				$data['CAPPOC1Email'] = $m->getBestEmail();
				$data['CAPPOC1RxUpdates'] = 1;
				$data['CAPPOC1RxRoster'] = 1;
				$data['CAPPOC2ID'] = 0;
				$data['CAPPOC2Name'] = '';
				$data['CAPPOC2Phone'] = '';
				$data['CAPPOC2Email'] = '';
				$data['CAPPOC2RxUpdates'] = 0;
				$data['CAPPOC2RxRoster'] = 0;
				$data['CAPPOC1ReceiveEventUpdates'] = true;
				$data['CAPPOC1ReceiveSignUpUpdates'] = true;
				$data['CAPPOC2ReceiveEventUpdates'] = false;
				$data['CAPPOC2ReceiveSignUpUpdates'] = false;
				$data['ExtPOCReceiveEventUpdates'] = true;
				$data['ExtPOCName'] = $event->CAPPOC1Name;
				$data['ExtPOCPhone'] = $event->CAPPOC1Phone;
				$data['ExtPOCEmail'] = $event->CAPPOC1Email;
				$data['SourceEventNumber'] = $event->EventNumber;
				$data['SourceAccountID'] = $a->id;
				$data['Created'] = time();
				Event::SetAccount($account);
				$ne = Event::Create($data, $account, $m);

//return "New Account=".$account->id." New EventNumber=".$ne->EventNumber." Current Account=".$a->id." Current EventNumber=".$event->EventNumber;

				//move attendance records
				$sqlin = "UPDATE ".DB_TABLES['Attendance']." SET AccountID=:naid, EventID=:neid WHERE AccountID=:oaid AND EventID=:oeid ";
				$sqlin .= "AND CAPID IN (SELECT CAPID FROM Data_Member WHERE ORGID IN (SELECT UnitID FROM Accounts WHERE AccountID=:naid));";
				$stmt = $pdo->prepare($sqlin);
				$stmt->bindValue(':naid', $account->id);
				$stmt->bindValue(':neid', $ne->EventNumber);
				$stmt->bindValue(':oaid', $a->id);
				$stmt->bindValue(':oeid', $event->EventNumber);
				$moverecords = DB_Utils::ExecutePDOStatement($stmt);

				//return value for updateCalendarEvent is currently text and is undisplayed here
				try {
					GoogleCalendar::init($account);
					GoogleCalendar::updateCalendarEvent($ne, $account);
				} catch (Exception $e) {
					//need to indicate to user that calendar update failed
				}
				//eventMailer should return an execution status and be reported/error recorded

				//send email to home squadron
//				eventMailer($m, $ne);  //not needed because linked ExtPOC is primary POC1
				//send email to linked squadron
				eventMailerLinkSet($m, $ne, $event);

				$ne->_destroy();

				return 'Successfully linked to this event.';

			} else if ($func == 'linku' && ($m->hasPermission("LinkEvent"))) {
				eventMailerLinkUnset($m, $event);
				$event->SourceAccountID = '';
				$event->SourceEventNumber = 0;
				$event->save();
				return 'Successfully unlinked this event.';
			} else if ($func == 'atmod' && ($m->hasPermissionLevel("SignUpEdit") || $event->isPOC($m))) {

			} else if (($func == 'atdel' && $m->AccessLevel == "Admin")) {
				$event->getAttendance()->remove(Member::Estimate($data['cid']));
			} else if (($func == 'atchr' && $m->AccessLevel == "Admin")) {
				//chronological sign-up list (need to separate by Senior/Cadet)
				$html = '';
				$event = Event::Get((int)$data, $a);
				$pdo = DB_Utils::CreateConnection();

				if ($event->IsSpecial) {
					$sqlin = 'SELECT * FROM '.DB_TABLES['SpecialAttendance'];
				} else {
					$sqlin = 'SELECT * FROM '.DB_TABLES['Attendance'];
				}
				$sqlin .= ' WHERE EventID=:eid AND AccountID=:aid ';
				$sqlin .= ' ORDER BY Timestamp;';
				$stmt = $pdo->prepare($sqlin);
				$stmt->bindValue(':aid', $a->id);
				$stmt->bindValue(':eid', $event->EventNumber);
				$attendancerecords = DB_Utils::ExecutePDOStatement($stmt);

				$html = '';
				foreach ($attendancerecords as $myRecord) {
					$attendee = Member::Estimate($myRecord['CAPID']);
					if($attendee) {
						$html .= date(DATE_RSS, $myRecord['Timestamp']).': '.$myRecord['MemberRankName'].'<br />';
					}
				}
				return $html;
			} else if (($func == 'atsul' && $m->AccessLevel == "Admin")) {
				//sign-up list
				$html = '';
				$event = Event::Get((int)$data, $a);
				$pdo = DB_Utils::CreateConnection();

				if ($event->IsSpecial) {
					$sqlin = 'SELECT * FROM '.DB_TABLES['SpecialAttendance'];
				} else {
					$sqlin = 'SELECT * FROM '.DB_TABLES['Attendance'];
				}
				$sqlin .= ' WHERE EventID=:eid AND AccountID=:aid ';
				$sqlin .= ' ORDER BY Timestamp;';
				$stmt = $pdo->prepare($sqlin);
				$stmt->bindValue(':aid', $a->id);
				$stmt->bindValue(':eid', $event->EventNumber);
				$attendancerecords = DB_Utils::ExecutePDOStatement($stmt);

				$html = "Status: C=Committed, N=No Show, R=Rescinded; Transportation: Y or -<br />";
				$html .= 'S T Rank Name: Comments: Locations: Duty Pref:<br />';
				foreach ($attendancerecords as $myRecord) {
					$attendee = Member::Estimate($myRecord['CAPID']);
					if($attendee) {
						$html .= substr($myRecord['Status'],0,1)." ".($myRecord['PlanToUseCAPTransportation']?"Y":"-")." ";
						$html .= $myRecord['MemberRankName'].": ".$myRecord['Comments'];
						if($event->IsSpecial) {
							$html .= ": ".$myRecord['GeoLoc'].": ".$myRecord['DutyPreference'];
							$html .= ": ".$myRecord['EmailAddress'].": ".$myRecord['PhoneNumber'];
							$html .= ": ".$myRecord['Uniform'];
						}
						$html .= '<br />';
					}
				}
				return $html;
			} else if (($func == 'atdir')) {
				$html = '';
				$event = Event::Get((int)$data, $a);
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
				$event = Event::Get((int)$data, $a);
				if ($event->CAPPOC1Email != '') {
					$html .= $event->CAPPOC1Email."; ";
				}
				if ($event->CAPPOC2Email != '') {
					$html .= $event->CAPPOC2Email."; ";
				}
				if ($event->ExtPOCEmail != 0) {
					$html .= $event->ExtPOCEmail."; ";
				}
				$att = $event->getAttendance();
				foreach ($att as $cid => $data) {
					$attendee = Member::Estimate($cid);
					if($attendee) {
						$html .= $attendee->getAllEmailAddresses();
					}
				}
				$emails = explode(", ", $html);
				$emails = array_unique($emails);
				$html = implode(", ", $emails);
				return rtrim($html, ', ');
			} else if (($func == 'sende')) {
				$event = Event::Get((int)$data, $a);
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
