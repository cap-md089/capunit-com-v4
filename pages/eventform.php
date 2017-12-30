<?php
	define ("USER_REQUIRED", true);
	
	function mdate ($time) {
		if ($time>0) {
			return date('Y-m-d\TH:i:s', $time);
		} else {
			return 0;
		}
	}

    class Output {
        public static function doGet ($eventdata, $cookie, $loggedin, $member, $account) {
			if (!$loggedin) {
				return ['error' => 411];
			}
			if (!$member->hasPermission("AddEvent")) {return ['error' => 402];}

			$get = isset($eventdata['uri'][0]);
			if ($get) {
				$event = Event::Get($eventdata['uri'][0]);
			} else {
				$event = false;
			}

            if (!!$event) {
				if (!($member->hasPermission("EditEvent") || $event->isPOC($member))) {return ['error' => 402];}
				$form = new AsyncForm ('eventform', 'Edit Event', Null, 'eventForm');

				$form->addField ('eventName', 'Event Name', 'text', Null, Null, $event->EventName)
					->addField ('', 'Calendar Information', 'label')
					->addField ('meetDate', 'Meet Date and Time', 'datetime-local', Null, Null, mdate($event->MeetDateTime))
					->addField ('meetLocation', 'Meet Location', 'text', Null, Null, $event->MeetLocation)
					->addField ('startDate', 'Start Date and Time', 'datetime-local', Null, Null, mdate($event->StartDateTime))
					->addField ('eventLocation', 'Event Location', 'text', Null, Null, $event->EventLocation)
					->addField ('endDate', 'End Date and Time', 'datetime-local', Null, Null, mdate($event->EndDateTime))
					->addField ('pickupDate', 'Pickup Date and Time', 'datetime-local', Null, Null, mdate($event->PickupDateTime))
					->addField ('pickupLocation', 'Pickup Location', 'text', Null, Null, $event->PickupLocation)
					->addField ('transportationProvided', 'Transportation Provided', 'checkbox', Null, Null, $event->TransportationProvided)
					->addField ('transportationDescription', 'Transportation Description', 'text', Null, Null, $event->TransportationDescription)
					->addField ('', 'Activity Information', 'label')
					->addField ('comments', 'Comments', 'textarea', Null, Null, $event->Comments)
					->addField ('activity', 'Activity Type', 'multcheckbox', Null, [
						'Recurring Meeting', 'Classroom/Tour/Light', 'Backcountry', 'Flying', 'Physically Rigorous', 'Other'
					], explode(', ', $event->Activity))
					->addField ('lodging', 'Lodging Arrangements', 'multcheckbox', Null, [
						'Hotel or Individual Room', 'Open Bay Building', 'Large Tent', 'Individual Tent', 'Other'
					], explode(', ', $event->LodgingArrangements))
					->addField ('eventWebsite', 'Event Website', 'url', Null, Null, $event->EventWebsite)
					->addField ('highAdventureDescription', 'High Adventure Description', 'textarea', Null, Null, $event->HighAdventureDescription)
					->addField ('', 'Logistics Information', 'label')
					->addField ('uniform', 'Uniform', 'multcheckbox', Null, [
						'Dress Blue A', 'Dress Blue B', 'Battle Dress Uniform or Airman Battle Uniform (BDU ABU)', 
						'PT Gear', 'Polo Shirts (Senior Members)', 'Blue Utilities (Senior Members)', 'Civilian Attire'
					], explode(', ', $event->Uniform))
					->addField ('requiredForms', 'Required Forms', 'multcheckbox', Null, [
						'CAPF 31 Application For CAP Encampment Or Special Activity', 
						'CAPF 32 Civil Air Patrol Cadet Activity Permission Slip',
						'CAPF 101 Specialty Qualification Card',
						'CAPF 160 CAP Member Health History Form',
						'CAPF 161 Emergency Information',
						'CAPF 163 Permission For Provision Of Minor Cadet Over-The-Counter Medication',
						'CAP Identification Card',
						'Other'
					], explode(', ', $event->RequiredForms))
					->addField ('requiredEquipment', 'Required Equipment', 'text', Null, Null, $event->RequiredEquipment)
					->addField ('registrationDeadline', 'Registation Deadline', 'datetime-local', Null, Null, mdate($event->RegistrationDeadline))
					->addField ('registrationInformation', 'Registration Information', 'textarea', Null, Null, $event->RegistrationInformation)
					->addField ('participationFee', 'Participation Fee', 'text', Null, Null, 0)
					->addField ('participationFeeDeadline', 'Participation Fee Due', 'datetime-local', Null, Null, mdate($event->ParticipationFeeDue))
					->addField ('meals', 'Meals', 'multcheckbox', Null, [
						'No Meals Provided', 'Meals Provided', 'Bring Own Food', 'Bring Money', 'Other'
					], explode(', ', $event->Meals))
					->addField ('', 'Points of Contact', 'label')
					->addField ('CAPPOC1ID', 'First CAP POC ID ('.(new AsyncButton(Null, 'Select', 'selectCAPIDForEventForm'))->getHtml('1').')', 'text', 'capPOC1', Null, $event->CAPPOC1ID)
					->addField ('CAPPOC1Phone', 'First CAP POC Phone', 'text', 'capPOCPHONE1', Null, $event->CAPPOC1Phone)
					->addField ('CAPPOC1Email', 'First CAP POC Email', 'text', 'capPOCEMAIL1', Null, $event->CAPPOC1Email)
					->addField ('CAPPOC2ID', 'Second CAP POC ID ('.(new AsyncButton(Null, 'Select', 'selectCAPIDForEventForm'))->getHtml('2').')', 'text', 'capPOC2', Null, $event->CAPPOC2ID)
					->addField ('CAPPOC2Phone', 'Second CAP POC Phone', 'text', 'capPOCPHONE2', Null, $event->CAPPOC2Phone)
					->addField ('CAPPOC2Email', 'Second CAP POC Email', 'text', 'capPOCEMAIL2', Null, $event->CAPPOC2Email)
					->addField ('ExtPOCName', 'External POC Name', 'text', Null, Null, $event->ExtPOCName)
					->addField ('ExtPOCPhone', 'External POC Phone', 'text', Null, Null, $event->ExtPOCPhone)
					->addField ('ExtPOCEmail', 'External POC Email', 'text', Null, Null, $event->ExtPOCEmail)
					->addField ('', 'Administrative Information', 'label')
					->addField ('acceptSignups', 'Accept Sign-Ups','checkbox', Null, Null, $event->AcceptSignups)
					->addField ('signUpDeny', 'Sign-Up Deny Message', 'textarea', Null, Null, $event->SignUpDenyMessage)
					->addField ('desiredParticipants', 'Desired Number of Participants', 'range', Null, [
						'value' => $event->DesiredNumParticipants,
						'min' => 0,
						'max' => 50
					])
					->addField ('groupEventNumber', 'Group Event Number', 'radio', Null, [
						'Not Required', 'To Be Applied For', 'Applied For', 'Other'
					], $event->GroupEventNumber)
					->addField ('eventStatus', 'Event Status', 'radio', Null, $member->AccessLevel == 'CadetStaff' ? ['Draft'] : [
						'Tentative', 'Confirmed', 'Complete', 'Cancelled', 'Information Only', 'Draft'
					], $event->Status)
					->addField ('entryComplete', 'Entry Complete', 'checkbox', Null, Null, $event->Complete)
					->addField ('publishToWing', 'Publish to Wing Calendar', 'checkbox', Null, Null, $event->PublishToWingCalendar)
					->addField ('adminComments', 'Administrative Comments', 'textarea', Null, Null, $event->Administration)
					->addField ('TeamID', 'Team ID', 'text', Null, Null, $event->TeamID)
					->addField ('', 'Debrief information', 'label')
					->addField ('Debrief', 'Debrief', 'textarea', Null, Null, $event->Debrief)
					->addField ('eventFiles', 'Event files', 'file');

				$form->addHiddenField('function', 'edit');
				$form->addHiddenField('eventnumber', $event->EventNumber);

				$form->setOption('reload', false);
				$form->setOption('beforeSend', 'checkInputs');
				
				$form->setSubmitInfo('Submit', null, null, null, false);

				return [
					'title' => 'Edit Event',
					'body' => $form.''
				];
			} else {
				$form = new AsyncForm ('eventform', 'Create an Event', Null, 'eventForm');

				$form->addField ('eventName', 'Event Name', 'text')
					->addField ('', 'Calendar Information', 'label')
					->addField ('meetDate', 'Meet Date and Time', 'datetime-local')
					->addField ('meetLocation', 'Meet Location', 'text')
					->addField ('startDate', 'Start Date and Time', 'datetime-local')
					->addField ('eventLocation', 'Event Location', 'text')
					->addField ('endDate', 'End Date and Time', 'datetime-local')
					->addField ('pickupDate', 'Pickup Date and Time', 'datetime-local')
					->addField ('pickupLocation', 'Pickup Location', 'text')
					->addField ('transportationProvided', 'Transportation Provided', 'checkbox')
					->addField ('transportationDescription', 'Transportation Description', 'text')
					->addField ('', 'Activity Information', 'label')
					->addField ('comments', 'Comments', 'textarea')
					->addField ('activity', 'Activity Type', 'multcheckbox', Null, [
						'Recurring Meeting', 'Classroom/Tour/Light', 'Backcountry', 'Flying', 'Physically Rigorous', 'Other'
					])
					->addField ('lodging', 'Lodging Arrangements', 'multcheckbox', Null, [
						'Hotel or Individual Room', 'Open Bay Building', 'Large Tent', 'Individual Tent', 'Other'
					])
					->addField ('eventWebsite', 'Event Website', 'url')
					->addField ('highAdventureDescription', 'High Adventure Description', 'textarea')
					->addField ('', 'Logistics Information', 'label')
					->addField ('uniform', 'Uniform', 'multcheckbox', Null, [
						'Dress Blue A', 'Dress Blue B', 'Battle Dress Uniform or Airman Battle Uniform (BDU ABU)', 
						'PT Gear', 'Polo Shirts (Senior Members)', 'Blue Utilities (Senior Members)', 'Civilian Attire'
					])
					->addField ('requiredForms', 'Required Forms', 'multcheckbox', Null, [
						'CAPF 31 Application For CAP Encampment Or Special Activity', 
						'CAPF 32 Civil Air Patrol Cadet Activity Permission Slip',
						'CAPF 101 Specialty Qualification Card',
						'CAPF 160 CAP Member Health History Form',
						'CAPF 161 Emergency Information',
						'CAPF 163 Permission For Provision Of Minor Cadet Over-The-Counter Medication',
						'CAP Identification Card',
						'Other'
					])
					->addField ('requiredEquipment', 'Required Equipment', 'text')
					->addField ('registrationDeadline', 'Registation Deadline', 'datetime-local', Null, Null, 0)
					->addField ('registrationInformation', 'Registration Information', 'textarea')
					->addField ('participationFee', 'Participation Fee', 'text', Null, Null, 0)
					->addField ('participationFeeDeadline', 'Participation Fee Due', 'datetime-local', Null, Null, 0)
					->addField ('meals', 'Meals', 'multcheckbox', Null, [
						'No Meals Provided', 'Meals Provided', 'Bring Own Food', 'Bring Money', 'Other'
					])
					->addField ('', 'Points of Contact', 'label')
					->addField ('CAPPOC1ID', 'First CAP POC ID ('.(new AsyncButton(Null, 'Select', 'selectCAPIDForEventForm'))->getHtml('1').')', 'text', 'capPOC1')
					->addField ('CAPPOC1Phone', 'First CAP POC Phone', 'text', 'capPOCPHONE1')
					->addField ('CAPPOC1Email', 'First CAP POC Email', 'text', 'capPOCEMAIL1')
					->addField ('CAPPOC2ID', 'Second CAP POC ID ('.(new AsyncButton(Null, 'Select', 'selectCAPIDForEventForm'))->getHtml('2').')', 'text', 'capPOC2')
					->addField ('CAPPOC2Phone', 'Second CAP POC Phone', 'text', 'capPOCPHONE2')
					->addField ('CAPPOC2Email', 'Second CAP POC Email', 'text', 'capPOCEMAIL2')
					->addField ('ExtPOCName', 'External POC Name', 'text')
					->addField ('ExtPOCPhone', 'External POC Phone', 'text')
					->addField ('ExtPOCEmail', 'External POC Email', 'text')
					->addField ('', 'Administrative Information', 'label')
					->addField ('acceptSignups', 'Accept Sign-Ups','checkbox')
					->addField ('signUpDeny', 'Sign-Up Deny Message', 'textarea')
					->addField ('desiredParticipants', 'Desired Number of Participants', 'range', Null, [
						'value' => 8,
						'min' => 0,
						'max' => 50
					])
					->addField ('groupEventNumber', 'Group Event Number', 'radio', Null, [
						'Not Required', 'To Be Applied For', 'Applied For', 'Other'
					])
					->addField ('eventStatus', 'Event Status', 'radio', Null, $member->AccessLevel == 'CadetStaff' ? ['Draft'] : [
						'Tentative', 'Confirmed', 'Complete', 'Cancelled', 'Information Only', 'Draft'
					])
					->addField ('entryComplete', 'Entry Complete', 'checkbox')
					->addField ('publishToWing', 'Publish to Wing Calendar', 'checkbox')
					->addField ('adminComments', 'Administrative Comments', 'textarea')
					->addField ('TeamID', 'Team ID', 'text')
					->addField ('Debrief', 'Debrief', 'textarea')
					->addField ('eventFiles', 'Event files', 'file');


				$form->addHiddenField('function', 'create');

				$form->setOption('reload', false);
				$form->setOption('beforeSend', 'checkInputs');

				$form->setSubmitInfo('Submit', null, null, null, false);

				return [
					'title' => 'Create Event',
					'body' => $form.''
				];
			}
        }

        public static function doPost ($eventdata, $cookie, $loggedin, $member, $account) {
			if (!$loggedin) {
				return [
					'error' => 411
				];
			}

			$pdo = DBUtils::CreateConnection();

			if ($eventdata['form-data']['function'] == 'create') {
				if (!$member->hasPermission('AddEvent') && $member->AccessLevel !== 'CadetStaff') return ['error' => 402];

				$poc1 = Member::Estimate($eventdata['form-data']['CAPPOC1ID']);
				$poc2 = Member::Estimate($eventdata['form-data']['CAPPOC2ID']);

				$event = Event::Create(array (
					'EventName' => $eventdata['form-data']['eventName'],
					'MeetLocation' => $eventdata['form-data']['meetLocation'],
					'EventLocation' => $eventdata['form-data']['eventLocation'],
					'PickupLocation' => $eventdata['form-data']['pickupLocation'],
					'TransportationDescription' => $eventdata['form-data']['transportationDescription'],
					'RequiredEquipment' => $eventdata['form-data']['requiredEquipment'],
					'ParticipationFee' => $eventdata['form-data']['participationFee'],
					'MeetDateTime' => $eventdata['form-data']['meetDate'],
					'StartDateTime' => $eventdata['form-data']['startDate'],
					'EndDateTime' => $eventdata['form-data']['endDate'],
					'PickupDateTime' => $eventdata['form-data']['pickupDate'],
					'RegistrationDeadline' => $eventdata['form-data']['registrationDeadline'],
					'RegistrationInformation' => $eventdata['form-data']['registrationInformation'],
					'ParticipationFeeDue' => $eventdata['form-data']['participationFeeDeadline'],
					'TransportationProvided' => $eventdata['form-data']['transportationProvided'] == 'true',
					'AcceptSignups' => $eventdata['form-data']['acceptSignups'] == 'true',
					'PublishToWingCalendar' => $eventdata['form-data']['publishToWing'] == 'true',
					'Comments' => $eventdata['form-data']['comments'],
					'HighAdventureDescription' => $eventdata['form-data']['highAdventureDescription'],
					'SignUpDenyMessage' => $eventdata['form-data']['signUpDeny'],
					'Administration' => $eventdata['form-data']['adminComments'],
					'Activity' => AsyncForm::ParseCheckboxOutput($eventdata['form-data']['activity'], [
						'Recurring Meeting', 'Classroom/Tour/Light', 'Backcountry', 'Flying', 'Physically Rigorous', 'Other'
					]),
					'LodgingArrangements' => AsyncForm::ParseCheckboxOutput($eventdata['form-data']['lodging'], [
						'Hotel or Individual Room', 'Open Bay Building', 'Large Tent', 'Individual Tent', 'Other'
					]),
					'Meals' => AsyncForm::ParseCheckboxOutput($eventdata['form-data']['meals'], [
						'No Meals Provided', 'Meals Provided', 'Bring Own Food', 'Bring Money', 'Other'
					]),
					'GroupEventNumber' => $eventdata['form-data']['groupEventNumber'],
					'Status' => $eventdata['form-data']['eventStatus'],
					'EventWebsite' => $eventdata['form-data']['eventWebsite'],
					'Uniform' => AsyncForm::ParseCheckboxOutput($eventdata['form-data']['uniform'], [
						'Dress Blue A', 'Dress Blue B', 'Battle Dress Uniform or Airman Battle Uniform (BDU ABU)', 
						'PT Gear', 'Polo Shirts (Senior Members)', 'Blue Utilities (Senior Members)', 'Civilian Attire'
					]),
					'RequiredForms' => AsyncForm::ParseCheckboxOutput($eventdata['form-data']['requiredForms'], [
						'CAPF 31 Application For CAP Encampment Or Special Activity', 
						'CAPF 32 Civil Air Patrol Cadet Activity Permission Slip',
						'CAPF 101 Specialty Qualification Card',
						'CAPF 160 CAP Member Health History Form',
						'CAPF 161 Emergency Information',
						'CAPF 163 Permission For Provision Of Minor Cadet Over-The-Counter Medication',
						'CAP Identification Card',
						'Other'
					]),
					'DesiredNumParticipants' => $eventdata['form-data']['desiredParticipants'],
					'Complete' => $eventdata['form-data']['entryComplete'] == 'true',
					'CAPPOC1ID' => $eventdata['form-data']['CAPPOC1ID'],
					'CAPPOC1Name' => !!$poc1 ? $poc1->memberRank . ' ' . $poc1->memberName : '',
					'CAPPOC1Phone' => $eventdata['form-data']['CAPPOC1Phone'],
					'CAPPOC1Email' => $eventdata['form-data']['CAPPOC1Email'],
					'CAPPOC2ID' => $eventdata['form-data']['CAPPOC2ID'],
					'CAPPOC2Name' => !!$poc2 ? $poc2->memberRank . ' ' . $poc2->memberName : '',
					'CAPPOC2Phone' => $eventdata['form-data']['CAPPOC2Phone'],
					'CAPPOC2Email' => $eventdata['form-data']['CAPPOC2Email'],
					'ExtPOCName' => $eventdata['form-data']['ExtPOCName'],
					'ExtPOCPhone' => $eventdata['form-data']['ExtPOCPhone'],
					'ExtPOCEmail' => $eventdata['form-data']['ExtPOCEmail'],
					'TeamID' => $eventdata['form-data']['TeamID'],
					'Debrief' => $eventdata['form-data']['Debrief']
				), Null, $member);

				if (gettype($event) == gettype('string')) {
					return $event;
				}

				if (isset($eventdata['form-data']['eventFiles'])) {
					foreach ($eventdata['form-data']['eventFiles'] as $file) {
						$stmt = $pdo->prepare("INSERT INTO ".DB_TABLES['FileEventAssignments']." VALUES (:fid, :eid, :aid);");
						$stmt->bindValue(":fid", $file);
						$stmt->bindValue(':eid', $event->EventNumber);
						$stmt->bindValue(':aid', $account->id);
						if (!$stmt->execute()) {
							trigger_error($stmt->errorInfo()[2], 512);
						}
					}
				}
				
				//return value for updateCalendarEvent is currently text and is undisplayed here
				try {
					GoogleCalendar::updateCalendarEvent($event);
				} catch (Exception $e) {
					//need to indicate to user that calendar update failed
				}

				//eventMailer should return an execution status and be reported/error recorded
				eventMailer($member, $event);
				$event->save();

				return JSSnippet::PageRedirect('calendar');

			} else if ($eventdata['form-data']['function'] == 'edit') {
				$ev = $eventdata['form-data']['eventnumber'];
				$event = Event::Get($ev);

				if (!$event) {
					return $event->error;
				}

				if (!$event->isPOC($member)) {
					return ['error' => 402];
				}

				$poc1 = Member::Estimate($eventdata['form-data']['CAPPOC1ID']);
				$poc2 = Member::Estimate($eventdata['form-data']['CAPPOC2ID']);

				if ($eventdata['form-data']['Debrief'] != '') {
					$debrief = rtrim($event->Debrief . "\n\n$member->RankName\n\n" . $eventdata['form-data']['Debrief'], "\n");
 				} else {
					$debrief = $event->Debrief;
				}

				$event->set(array (
					'EventName' => $eventdata['form-data']['eventName'],
					'MeetLocation' => $eventdata['form-data']['meetLocation'],
					'EventLocation' => $eventdata['form-data']['eventLocation'],
					'PickupLocation' => $eventdata['form-data']['pickupLocation'],
					'TransportationDescription' => $eventdata['form-data']['transportationDescription'],
					'RequiredEquipment' => $eventdata['form-data']['requiredEquipment'],
					'ParticipationFee' => $eventdata['form-data']['participationFee'],
					'MeetDateTime' => $eventdata['form-data']['meetDate'],
					'StartDateTime' => $eventdata['form-data']['startDate'],
					'EndDateTime' => $eventdata['form-data']['endDate'],
					'PickupDateTime' => $eventdata['form-data']['pickupDate'],
					'RegistrationDeadline' => $eventdata['form-data']['registrationDeadline'],
					'RegistrationInformation' => $eventdata['form-data']['registrationInformation'],
					'ParticipationFeeDue' => $eventdata['form-data']['participationFeeDeadline'],
					'TransportationProvided' => $eventdata['form-data']['transportationProvided'] == 'true',
					'AcceptSignups' => $eventdata['form-data']['acceptSignups'] == 'true',
					'PublishToWingCalendar' => $eventdata['form-data']['publishToWing'] == 'true',
					'Comments' => $eventdata['form-data']['comments'],
					'HighAdventureDescription' => $eventdata['form-data']['highAdventureDescription'],
					'SignUpDenyMessage' => $eventdata['form-data']['signUpDeny'],
					'Administration' => $eventdata['form-data']['adminComments'],
					'Activity' => AsyncForm::ParseCheckboxOutput($eventdata['form-data']['activity'], [
						'Recurring Meeting', 'Classroom/Tour/Light', 'Backcountry', 'Flying', 'Physically Rigorous', 'Other'
					]),
					'LodgingArrangements' => AsyncForm::ParseCheckboxOutput($eventdata['form-data']['lodging'], [
						'Hotel or Individual Room', 'Open Bay Building', 'Large Tent', 'Individual Tent', 'Other'
					]),
					'Meals' => AsyncForm::ParseCheckboxOutput($eventdata['form-data']['meals'], [
						'No Meals Provided', 'Meals Provided', 'Bring Own Food', 'Bring Money', 'Other'
					]),
					'GroupEventNumber' => $eventdata['form-data']['groupEventNumber'],
					'Status' => $eventdata['form-data']['eventStatus'],
					'EventWebsite' => $eventdata['form-data']['eventWebsite'],
					'Uniform' => AsyncForm::ParseCheckboxOutput($eventdata['form-data']['uniform'], [
						'Dress Blue A', 'Dress Blue B', 'Battle Dress Uniform or Airman Battle Uniform (BDU ABU)', 
						'PT Gear', 'Polo Shirts (Senior Members)', 'Blue Utilities (Senior Members)', 'Civilian Attire'
					]),
					'RequiredForms' => AsyncForm::ParseCheckboxOutput($eventdata['form-data']['requiredForms'], [
						'CAPF 31 Application For CAP Encampment Or Special Activity', 
						'CAPF 32 Civil Air Patrol Cadet Activity Permission Slip',
						'CAPF 101 Specialty Qualification Card',
						'CAPF 160 CAP Member Health History Form',
						'CAPF 161 Emergency Information',
						'CAPF 163 Permission For Provision Of Minor Cadet Over-The-Counter Medication',
						'CAP Identification Card',
						'Other'
					]),
					'DesiredNumParticipants' => $eventdata['form-data']['desiredParticipants'],
					'Complete' => $eventdata['form-data']['entryComplete'] == 'true',
					'CAPPOC1ID' => $eventdata['form-data']['CAPPOC1ID'],
					'CAPPOC1Name' => !!$poc1 ? $poc1->memberRank . ' ' . $poc1->memberName : '',
					'CAPPOC1Phone' => $eventdata['form-data']['CAPPOC1Phone'],
					'CAPPOC1Email' => $eventdata['form-data']['CAPPOC1Email'],
					'CAPPOC2ID' => $eventdata['form-data']['CAPPOC2ID'],
					'CAPPOC2Name' => !!$poc2 ? $poc2->memberRank . ' ' . $poc2->memberName : '',
					'CAPPOC2Phone' => $eventdata['form-data']['CAPPOC2Phone'],
					'CAPPOC2Email' => $eventdata['form-data']['CAPPOC2Email'],
					'ExtPOCName' => $eventdata['form-data']['ExtPOCName'],
					'ExtPOCPhone' => $eventdata['form-data']['ExtPOCPhone'],
					'ExtPOCEmail' => $eventdata['form-data']['ExtPOCEmail'],
					'Author' => $member->uname,
					'TeamID' => $eventdata['form-data']['TeamID'],
					'Debrief' =>$debrief
				));


				if (isset($eventdata['form-data']['eventFiles'])) {
					foreach ($eventdata['form-data']['eventFiles'] as $file) {
						$stmt = $pdo->prepare("INSERT INTO ".DB_TABLES['FileEventAssignments']." VALUES (:fid, :eid, :aid);");
						$stmt->bindValue(":fid", $file);
						$stmt->bindValue(':eid', $event->EventNumber);
						$stmt->bindValue(':aid', $account->id);
						if (!$stmt->execute() && $stmt->errorInfo()[1] != 1062) {
							trigger_error($stmt->errorInfo()[2], 512);
						}
					}
				}

				if ($event->hasError()) {
					return $event->checkErrors();
				}

				//return value for updateCalendarEvent is currently text and is undisplayed here
				try {
					GoogleCalendar::updateCalendarEvent($event);
				} catch (Exception $e) {
					//need to indicate to user that calendar update failed
				}

				eventMailer($member, $event);
				return JSSnippet::PageRedirect('calendar');
			}
        }
    }
