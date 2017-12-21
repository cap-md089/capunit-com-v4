<?php
	function testEvent ($member) {
		$pdo = DB_Utils::CreateConnection();
		flog ("Member required, creating member");
		flog ("Creating member");
		// $member = Member::Create("542488", "thisisalongpasswordOK091101");
		flog ("Created member, logging details:");
		flog ("-- CAPID: ".$member->uname);
		flog ("-- Name: ".$member->memberName);
		flog ("-- Rank: ".$member->memberRank);
		flog ("-- Best email: ".$member->getBestEmail());
		flog ("-- Best phone: ".$member->getBestPhone());
		flog ("Getting list of available CAPWATCH files...");
		$list = $member->getCAPWATCHList();
		foreach ($list as $k => $v) {
			flog ("-- ID $k available for unit $v");
		}
		flog ("Downloading last CAPWATCH file");	
		// $member->getCAPWATCHFile($k);
		flog ("CAPWATCH File downloaded");
		flog ("Session ID for this member is ".$member->sid);
		flog ("Checking validity 5 times");
		for ($i = 0; $i < 5; $i++) {
			$mem = Member::Check($member->toObjectString());
			if ($mem['valid'] == false) {
				fwarn ("Member does not have a stable session");
			}
		}
		flog ("Estimating details of member based on CAPID");
		$member2 = Member::Estimate($member->uname);
		flog ("-- CAPID: ".$member2->uname);
		flog ("-- Name: ".$member2->memberName);
		flog ("-- Rank: ".$member2->memberRank);
		flog ("-- Best email: ".$member2->getBestEmail());
		flog ("-- Best phone: ".$member2->getBestPhone());
		flog ("Done with member");
		
		$event = Event::Get(6);
		
		flog ("Fetched event with event number ".$event->EventNumber.", info:");
		flog ("-- Name: ".$event->EventName);

		flog ("Testing attendance");
		$attendance = $event->getAttendance();
		flog ("-- Does the event attendance have the member? ".var_export($attendance->has($member), true));
		flog ("Event object: Does the event have this member? ".var_export($event->isAttending($member), true));
		if ($attendance->has($member)) {
			flog ("-- Removing member object: ".var_export($attendance->remove($member), true));
			flog ("-- Does the event attendance have the member? ".var_export($attendance->has($member), true));
		}
		flog ("-- Adding member object: ".var_export($attendance->add($member, false, ''), true));
		flog ("Event object: Does the event have this member? ".var_export($event->isAttending($member), true));
		flog ("-- Does the event attendance have the member? ".var_export($attendance->has($member), true));
		flog ("-- Removing member object: ".var_export($attendance->remove($member), true));
		flog ("-- Does the event attendance have the member? ".var_export($attendance->has($member), true));
		flog ("Event object: Does the event have this member? ".var_export($event->isAttending($member), true));
		flog ("-- Done with attendance");
		
		flog ("Creating event");
		
		$event2 = Event::Create (array (
			'EventName' => 'Test event 2',
			'MeetDateTime' => time(),
			'StartDateTime' => time()+2,
			'EndDateTime' => time()+4,
			'PickupDateTime' => time()+6
		));

		flog ("Name of new event: ".$event2->EventName);
		flog ("ID of new event: ".$event2->EventNumber);
		flog ("Modifying event name");

		$event2->EventName = 'Test Event 3';
		flog ("Errors? ".($event2->hasError()?'true':'false'));
		if ($event2->hasError()) {
			flog ("Errors: ".$event2->checkErrors());
		}
		flog ("Did the save go well? ".json_encode($event2->save()));

		$event3 = Event::Get($event2->EventNumber);

		flog ("New name of event: ".$event3->EventName);
		
		flog ("Deleting event");
		$id = $event2->EventNumber;
		flog ("Did deletion go well? ".var_export($event2->remove(), true));
		//$event3->_destroy();
		flog ("Deleted");

		flog ("Resetting table auto insert id");
		$pdo->prepare('ALTER TABLE '.DB_TABLES['EventInformation'].' AUTO_INCREMENT = '.($id-1).';')->execute();

		flog ("Done with event");
	
	}