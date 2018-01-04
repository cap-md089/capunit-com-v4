<?php
	require_once (BASE_DIR."lib/DB_Utils.php");
	require_once (BASE_DIR."lib/File.php");

	class Account {
		public $id = '';

		public $orgSQL = '';

		public $orgIDs = [];

		public $paid = false;

		public function __construct ($id) {
			$this->id = $id;
			$pdo = DBUtils::CreateConnection();
			$stmt = $pdo->prepare("SELECT UnitID, Paid FROM Accounts WHERE AccountID = :aid;");
			$stmt->bindValue(":aid", $this->id);
			$data = DBUtils::ExecutePDOStatement($stmt);
			$this->orgIDs = [];
			$this->orgSQL = '(';
			if (count ($data)) {
				foreach ($data as $datum) {
					$this->orgIDs[] = $datum['UnitID'];
					$this->orgSQL .= $datum['UnitID'].', ';
					$this->paid = ($datum['Paid'] == 1) || $this->paid;
				}
			} else {
				$this->orgSQL.='0';
			}
			$this->orgSQL = rtrim($this->orgSQL, ', ') . ')';
		}

		public function getAccountNumber () {
			return $this->id;
		}

		public function getEvents ($id=Null) {
			$ret = [];
			$pdo = DBUtils::CreateConnection();
			$stmt = $pdo->prepare("SELECT EventNumber FROM ".DB_TABLES['EventInformation']." WHERE AccountID = :id;");
			$stmt->bindValue(':id', $this->id);
			$data = DBUtils::ExecutePDOStatement($stmt);
			foreach ($data as $datum) {
				$ret[] = Event::Get($datum['EventNumber']);
			}
			return $ret;
 		}

		public function getEventCount() {
			$pdo = DBUtils::CreateConnection();
			$stmt = $pdo->prepare("SELECT COUNT(*) AS `Data` FROM ".DB_TABLES['EventInformation']." WHERE AccountID = :id;");
			$stmt->bindValue(':id', $this->id);
			$data = DBUtils::ExecutePDOStatement($stmt);
			return $data[0]['Data'];
		}

		public function getGoogleCalendarAccountIdMain() {
			return $this->getGoogleCalendarAccountId("Main");
		}

		public function getGoogleCalendarAccountIdWing() {
			return $this->getGoogleCalendarAccountId("Wing");
		}

		private function getGoogleCalendarAccountId($calType) {
			$pdo = DBUtils::CreateConnection();
			$stmt = $pdo->prepare("SELECT CalendarID FROM ".DB_TABLES['GoogleCalendarIDs']." 
				WHERE AccountID = :id AND CalendarType = :calType;");
			$stmt->bindValue(":id", $this->id);
			$stmt->bindValue(":calType", $calType);
			$data = DBUtils::ExecutePDOStatement($stmt);
			return $data[0]['CalendarID'];
		}

		public function getMembers () {
			$ret = [];
			$pdo = DBUtils::CreateConnection();
			$stmt = $pdo->prepare("SELECT CAPID FROM ".DB_TABLES['Member']." WHERE ORGID in $this->orgSQL;");
			$stmt->bindValue(":id", $this->id);
			$data = DBUtils::ExecutePDOStatement($stmt);
			foreach ($data as $datum) {
				$ret[] = Member::Estimate($datum['CAPID']);
			}
			return $ret;
		}

		public function getFiles () {
			$ret = [];
			$pdo = DBUtils::CreateConnection();
			$stmt = $pdo->prepare("SELECT ID FROM ".DB_TABLES['FileData']." WHERE AccountID = :id;");
			$stmt->bindValue(':id', $this->id);
			$data = DBUtils::ExecutePDOStatement($stmt);
			foreach ($data as $datum) {
				$ret[] = $datum['ID'];
			}
			return $ret;
		}

		public function genFiles () {
			$data = $this->getFiles();
			foreach ($data as $datum) {
				yield File::Get($datum);
			}
		}

		public function getFilesSize () {
			$pdo = DBUtils::CreateConnection();
			$stmt = $pdo->prepare("SELECT SUM(LENGTH(Data)) AS Size FROM ".DB_TABLES['FileData']." WHERE AccountID = :aid;");
			$stmt->bindValue(':aid', $this->id);
			$data = DBUtils::ExecutePDOStatement($stmt);
			return (int)$data[0]['Size'];
		}

		public function hasMember (\Member $mem1) {
			$mems = $this->getMembers();
			foreach ($mems as $mem2) {
				if ($mem1->uname == $mem2->uname) {
					return true;
				}
			}
			return false;
		}

		public function getNextMeetingTimestamp () {
			$pdo = DBUtils::CreateConnection();
			$stmt = $pdo->prepare("");
		}

		public function __toString () {
			if (count($this->id) == 3) { // mer 
				return strtoupper($this->id); // MER
			} else { // md089
				return strtoupper(substr($this->id, 0, 2)) . '-' . substr($this->id, 2, 3); // MD-089
			}
		}
	}