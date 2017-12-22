<?php
	function ImportGSEvents () {
		global $_ACCOUNT;
		if (php_sapi_name() == 'cli') {
			require_once (BASE_DIR."lib/DB_Utils.php");
			require_once (BASE_DIR."lib/Account.php");
			require_once (BASE_DIR."lib/Event.php");
			$_ACCOUNT = new Account("md089");
		} else {
			global $_ACCOUNT;
		}
		$_ACCOUNT = new Account("md089");
		$pdo = DBUtils::CreateConnection();
		$events = explode("\n", file_get_contents(BASE_DIR."data/EventData.csv"));
		$fevents = fopen(BASE_DIR."data/EventData.csv", "r");
		$stmt = $pdo->prepare("DELETE FROM ".DB_TABLES['EventInformation']." WHERE AccountID=:aid;");
		$stmt->bindValue(":aid", $_ACCOUNT->id);
		if (!$stmt->execute()) {
			trigger_error($stmt->errorInfo()[2], E_USER_WARNING);
		}

		set_error_handler(function ($errno, $errstr, $errfile, $errline, $errcontext) {
			http_response_code (500);
			$_ERROR = [
				"enumber" => $errno,
				"errname" => Util_Collection::GetErrorName($errno),
				"message" => $errstr,
				"badfile" => $errfile,
				"badline" => $errline,
				"context" => $errcontext
			];
			unset($_ERROR['context']['events']);
			print_r($_ERROR);
			exit(255);
			return true;
		}, E_ALL);
		$e = fgetcsv($fevents);

		// for ($i = 1, $e = fgetcsv($fevents); $i < count($events)-1; $i++, $e = fgetcsv($fevents)) {
		while (($e = fgetcsv($fevents)) !== FALSE) {
			preg_match('/(\d{1,3}(\.\d{2})??)/', $e[7], $matches);
			$fee = isset($matches[1]) ? (float)$matches[1] : 0;
			$event = Event::Create(array (
				'EventNumber' => $e[54],
				'EventName' => $e[1],
				'MeetDateTime' => UtilCollection::GetTimestamp($e[2], $e[18]),
				'MeetLocation' => $e[3],
				'StartDateTime' => UtilCollection::GetTimestamp($e[4], $e[19]),
				'EventLocation' => $e[5],
				'EndDateTime' => UtilCollection::GetTimestamp($e[22], $e[23]),
				'PickupDateTime' => UtilCollection::GetTimestamp($e[24], $e[25]),
				'PickupLocation' => $e[27],
				'TransportationProvided' => $e[6] == 'Transportation Provided',
				'Uniform' => $e[21],
				'DesiredNumParticipants' => $e[31],
				'RegistrationDeadline' => UtilCollection::GetTimestamp($e[37]),
				'ParticipationFeeDue' => UtilCollection::GetTimestamp($e[8]),
				'ParticipationFee' => $fee,
				'Meals' => $e[9],
				'Activity' => $e[10],
				'HighAdventureDescription' => $e[11],
				'RequiredEquipment' => $e[12],
				'EventWebsite' => $e[13],
				'RequiredForms' => $e[16],
				'Comments' => $e[17],
				'AcceptSignups' => $e[41] != 'No',
				'SignUpDenyMessage' => $e[44],
				'ReceiveEventUpdates' => $e[39] == 'Yes',
				'PublishToWingCalendar' => $e[43] != 'No',
				'GroupEventNumber' => $e[38],
				'Administration' => $e[34],
				'Status' => $e[35],
				'Debrief' => $e[36],
				'CAPPOC1Name' => $e[14],
				'CAPPOC1Phone' => $e[15],
				'CAPPOC1Email' => $e[26],
				'ExtPOCName' => $e[28],
				'ExtPOCPhone' => $e[29],
				'ExtPOCEmail' => $e[30]
			), $_ACCOUNT);
		}
	}

	function ImportGSAttendance () {
		global $_ACCOUNT;
		if (php_sapi_name() == 'cli') {
			require_once (BASE_DIR."lib/DB_Utils.php");
			require_once (BASE_DIR."lib/Account.php");
			require_once (BASE_DIR."lib/Event.php");
			require_once (BASE_DIR."lib/general.php");
			require_once (BASE_DIR."lib/Attendance.php");
			require_once (BASE_DIR."lib/Member.php");
			$_ACCOUNT = new Account("md089");
		} else {
			global $_ACCOUNT;
		}
		$_ACCOUNT = new Account("md089");
		$pdo = DBUtils::CreateConnection();
		set_error_handler(function ($errno, $errstr, $errfile, $errline, $errcontext) {
			http_response_code (500);
			$_ERROR = [
				"enumber" => $errno,
				"errname" => Util_Collection::GetErrorName($errno),
				"message" => $errstr,
				"badfile" => $errfile,
				"badline" => $errline,
				"context" => $errcontext
			];
			unset($_ERROR['context']['events']);
			print_r($_ERROR);
			exit(255);
			return true;
		}, E_ALL);

		$stmt = $pdo->prepare("DELETE FROM ".DB_TABLES['Attendance']." WHERE AccountID=:aid;");
		$stmt->bindValue(":aid", $_ACCOUNT->id);
		if (!$stmt->execute()) {
			trigger_error($stmt->errorInfo()[2], E_USER_WARNING);
		}

		$fatt = fopen(BASE_DIR."data/AttendanceData.csv", "r");
		$e = fgetcsv($fatt);

		while (($e = fgetcsv($fatt)) !== FALSE) {
			// $event = Event::Get($e[1]);
			// $att = $event->getAttendance();
			// $mem = Member::Estimate($e[3]);
			// $att->add();

			$stmt = $pdo->prepare('INSERT INTO '.DB_TABLES['Attendance'].' VALUES (:time, :eid, :cid, :crank, :comments, :status, :plantouse, :accountid);');
			preg_match('/((.*), (.*) (.) (.*)|(.*)[,\.] (.*))/', $e[2], $name);
			if (count($name) == 8) {
				$name = "{$name[7]} {$name[6]}";
			} else if (count($name) == 6) {
				$name = "{$name[5]} {$name[2]} {$name[4]} {$name[3]}";
			} else if (count($name) == 0) {
				$name = '';
			} else {
				echo "Empty name: $name".PHP_EOL;
				$name = '';
			}
			$time = UtilCollection::GetTimestamp2($e[0]);
			$stmt->bindValue(':plantouse', $e[8] == 'Yes' ? 1 : 0);
			$stmt->bindValue(':time', $time);
			$stmt->bindValue(':eid', $e[1]);
			$stmt->bindValue(':cid', $e[3]);
			$stmt->bindValue(':crank', $name);
			$stmt->bindValue(':comments', $e[6]);
			$stmt->bindValue(':status', $e[7]);
			$stmt->bindValue(':accountid', $_ACCOUNT->id);
			if (!$stmt->execute()) {
				if ($stmt->errorInfo()[1] != 1062) {
					trigger_error($stmt->errorInfo()[2], E_USER_WARNING);
				}
			}
		}
	}

	function ImportGSFiles () {
		global $_ACCOUNT;
		if (php_sapi_name() == 'cli') {
			require_once (BASE_DIR."lib/DB_Utils.php");
			require_once (BASE_DIR."lib/Account.php");
			require_once (BASE_DIR."lib/Event.php");
			require_once (BASE_DIR."lib/general.php");
			require_once (BASE_DIR."lib/Attendance.php");
			require_once (BASE_DIR."lib/Member.php");
			$_ACCOUNT = new Account("md089");
		} else {
			global $_ACCOUNT;
		}
		$pdo = DBUtils::CreateConnection();
		set_error_handler(function ($errno, $errstr, $errfile, $errline, $errcontext) {
			http_response_code (500);
			$_ERROR = [
				"context" => array_keys($errcontext),
				"enumber" => $errno,
				"errname" => Util_Collection::GetErrorName($errno),
				"message" => $errstr,
				"badfile" => $errfile,
				"badline" => $errline
			];
			unset($_ERROR['context']['events']);
			unset($_ERROR['context']['member']);
			unset($_ERROR['context']['file']);
			print_r($_ERROR);
			// if ($errno != 8) exit(255);
			return true;
		}, E_ALL);
		
		$efdir = scandir(BASE_DIR."data/EventDocuments");
		$efdir = [
			'459', '463', '465', '469', '473'
		];
		$member = Member::Estimate(546319);

		foreach ($efdir as $efd) {
			if (is_dir(BASE_DIR."data/EventDocuments/$efd") && $efd !== '..') {
				$efdi = scandir(BASE_DIR."data/EventDocuments/$efd");
				foreach ($efdi as $ef) {
					$f = BASE_DIR."data/EventDocuments/$efd/$ef";
					if (is_file($f)) {
						$file = File::Create($ef, file_get_contents($f), $member);
						$file->Comments = 'Old Event File';
						$stmt = $pdo->prepare("INSERT INTO FileEventAssignments VALUES (:fid, :eid, :aid);");
						$stmt->bindValue(':fid', $file->ID);
						$stmt->bindValue(':eid', $efd);
						$stmt->bindValue(':aid', $_ACCOUNT->id);
						if (!$stmt->execute ()) {
							if ($stmt->errorInfo()[1] == 1062) {
								continue;
							}
							trigger_error($stmt->errorInfo()[2], E_USER_WARNING);
						}
					}
				}
			} 
		}
	}