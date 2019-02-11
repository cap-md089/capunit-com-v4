<?php
	class Attendance implements Iterator {
		private $position = 0;

		public $EventNumber;

		public $EventAttendance;

		public function __construct (int $ev) {
			global $_ACCOUNT;
			$this->position = 0;
			$pdo = DB_Utils::CreateConnection();
			$sqlin = "CALL getAttendance(:aid, :ev);";
//			$sqlin = "(SELECT * FROM Attendance WHERE (AccountID=:aid AND EventID=:ev));";
			$stmt = $pdo->prepare($sqlin);
			$stmt->bindValue(':ev', $ev);
			$stmt->bindValue(':aid', $_ACCOUNT->id);
			$this->EventAttendance = DB_Utils::ExecutePDOStatement($stmt);
			$this->EventNumber = $ev;

//			$eventIDsSQL = "SELECT AccountID, EventNumber FROM EventInformation ";
//			$eventIDsSQL .= "WHERE SourceEventNumber=:ev AND SourceAccountID=:aid";
//			$stmt = $pdo->prepare($eventIDsSQL);
//			$stmt->bindValue(':ev', $ev);
//			$stmt->bindValue(':aid', $_ACCOUNT->id);
//			$events = DB_Utils::ExecutePDOStatement($stmt);
//
//			foreach ($events as $eventInfo) {
//				$acc = new Account($eventInfo['AccountID']);
//				$ev = Event::Get($eventInfo['EventNumber'], $acc);
//
//				$att = $ev->getAttendance();
//
//				foreach ($att->EventAttendance as $attItem) {
//					$this->EventAttendance[] = $attItem;
//				}
//			}
			// $sqlin .= "UNION (SELECT * FROM Attendance INNER JOIN ";
			// $sqlin .= "(SELECT AccountID, EventNumber FROM EventInformation ";
			// $sqlin .= "WHERE SourceEventNumber=:ev AND SourceAccountID=:aid) AS t2 ON ";
			// $sqlin .= "(Attendance.AccountID COLLATE utf16_general_ci=t2.AccountID AND Attendance.EventID";
			// $sqlin .= " COLLATE utf16_general_ci=t2.EventNumber));";
		}

		public function has (\Member $member) {
			$ret = false;
			foreach ($this->EventAttendance as $row) {
				$ret = $ret || ((int)$row['CAPID'] == $member->uname);
				if ($ret) return true;
			}
			return false;
		}

		public function add (\Member $member, $plantouse=false, $comments='') {
			global $_ACCOUNT;
			$pdo = DB_Utils::CreateConnection();
			$stmt = $pdo->prepare('INSERT INTO '.DB_TABLES['Attendance'].' VALUES (:time, :eid, :cid, :crank, :comments, :status, :plantouse, :accountid, :reqs, :sent);');
			$time = time();
			$stmt->bindValue(':plantouse', $plantouse ? 1 : 0);
			$stmt->bindValue(':time', $time);
			$stmt->bindValue(':eid', $this->EventNumber);
			$stmt->bindValue(':cid', $member->uname);
			$stmt->bindValue(':crank', $member->memberRank . ' ' . $member->memberName);
			$stmt->bindValue(':comments', $comments);
			$stmt->bindValue(':status', 'Committed/Attended');
			$stmt->bindValue(':accountid', $_ACCOUNT->id);
			$stmt->bindValue(':reqs', '');
			$stmt->bindValue(':sent', 0);
			$this->EventAttendance[] = [
				'PlanToUseCAPTransportation' => $plantouse ? 1 : 0,
				'Timestamp' => $time,
				'EventID' => $this->EventNumber,
				'CAPID' => $member->uname,
				'MemberRankName' => $member->memberRank . ' ' . $member->memberName,
				'Comments' => $comments,
				'Status' => 'Commited/Attended',
				'Requirements' => '',
				'SummaryEmailSent' => 0
			];
			if (!$stmt->execute()) {
				if ($stmt->errorInfo()[1] == 1062) {
					return false;
				}
				trigger_error($stmt->errorInfo()[2], E_USER_WARNING);
			}
			return true;
		}

		public function remove (\Member $member) {
			global $_ACCOUNT;
			$pdo = DB_Utils::CreateConnection();
			$stmt = $pdo->prepare('DELETE FROM '.DB_TABLES['Attendance'].' WHERE CAPID = :cid AND EventID = :eid AND AccountID = :aid;');
			$stmt->bindValue(':cid', $member->uname);
			$stmt->bindValue(':eid', $this->EventNumber);
			$stmt->bindValue(':aid', $_ACCOUNT->id);
			for ($i = 0; $i < count($this->EventAttendance); $i++) {
				if ($this->EventAttendance[$i]['CAPID'] == $member->capid) {
					array_splice($this->EventAttendance, $i, 1);
					break;
				}
			}
			if (!$stmt->execute()) {
				trigger_error($stmt->errorInfo()[2], E_USER_WARNING);
			}
			return true;
		}

		public function modify (\Member $member, $plantouse=Null, $comments=Null, $status=Null) {
			global $_ACCOUNT;
			for ($i = 0; $i < count($this->EventAttendance); $i++) {
				if ($this->EventAttendance[$i]["CAPID"] == $member->capid) {
					break;
				}
			}
			$row = $this->EventAttendance[$i];
			if (isset($plantouse)) $row['PlanToUseCAPTransportation'] = $plantouse;
			if (isset($comments)) $row['Comments'] = $comments;
			if (isset($status)) $row['Status'] = $status;

			$pdo = DB_Utils::CreateConnection();
			$stmt = $pdo->prepare('UPDATE '.DB_TABLES['Attendance'].' SET
				PlanToUseCAPTransportation=:plantouse,
				Comments=:comments,
				Status=:status
			WHERE
				EventID=:eid
			AND
				CAPID=:capid
			AND
				AccountID=:aid;');
			$stmt->bindValue(':plantouse', $row['PlanToUseCAPTransportation'] ? 1 : 0);
			$stmt->bindValue(':comments', $row['Comments']);
			$stmt->bindValue(':status', $row['Status']);
			$stmt->bindValue(':eid', $this->EventNumber);
			$stmt->bindValue(':capid', $row['CAPID']);
			$stmt->bindValue(':aid', $_ACCOUNT->id);
			if (!$stmt->execute()) {
				trigger_error($stmt->errorInfo()[2], E_USER_WARNING);
			}
			return true;
		}

		public function clearAll () {
			global $_ACCOUNT;
			$pdo = DBUtils::CreateConnection();
			$stmt = $pdo->prepare ("DELETE FROM ".DB_TABLES['Attendance']." WHERE EventID = :id AND AccountID=:aid;");
			$stmt->bindValue(':id', $this->EventNumber);
			$stmt->bindValue(':aid', $_ACCOUNT->id);
			if (!$stmt->execute()) {
				trigger_error($stmt->errorInfo()[2], 512);
			}
			return true;
		}

		/**
		 * Iterator for using this kind of code:
		 * foreach (\Attendance as int(CAPID) => array[]) {
		 * 		// do stuff with data (the array[])
		 *		// get member data with Member::Estimate(CAPID)
		 * }
		 */

		public function rewind () {
			$this->position = 0;
		}

		public function current () {
			$row = $this->EventAttendance[$this->position];
			return [
				'PlanToUseCAPTransportation' => $row['PlanToUseCAPTransportation'],
				'Timestamp' => $row['Timestamp'],
				'MemberRankName' => $row['MemberRankName'],
				'Comments' => $row['Comments'],
				'Status' => $row['Status']
			];
		}

		public function key () {
			return (int)$this->EventAttendance[$this->position]['CAPID'];
		}

		public function next () {
			$this->position++;
		}

		public function valid () {
			return isset($this->EventAttendance[$this->position]);
		}
	}
