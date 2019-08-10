<?php
    /**
     * @package lib/Account
     *
     * A representation of an Account
     *
     * @author Andrew Rioux <arioux303931@gmail.com>, Glenn Rioux <grioux.cap@gmail.com>
     *
     * @copyright 2016-2017 Rioux Development Team
     */

	require_once (BASE_DIR."lib/DB_Utils.php");
	require_once (BASE_DIR."lib/File.php");
	require_once (BASE_DIR."lib/Member.php");

    /**
     * This represents an Account and interfaces with the database to provide a useful API
     */
	class Account {
        /**
         * @var string the account identifier (like 'md089')
         */
		public $id = '';

        /**
         * @var string the SQL string (like '(916, 2529)')
         */
		public $orgSQL = '';

        /**
         * @var string[] an array of the account identifiers (like [916, 2529])
         */
		public $orgIDs = [];

        /**
         * @var bool indicator that this account is/was a paid account. 
         */
		public $paid = false;

        /**
         * @var bool indicator of currency of paid status
         */
		public $expired = true;

        /**
         * @var int indicator of currency of paid status
         */
		public $expiresIn = 0;

        /**
         * @var int limit of number of events allowed for this account when in a paid status
         */
		public $paidEventLimit = 0;

        /**
         * @var int limit of number of events allowed for this account when in an unpaid or expired status
         */
		public $unpaidEventLimit = 0;

        /**
         * @var string[] rank & name of Admins
         */
		public $adminName = [];

        /**
         * @var string[] email addresses of Admins
         */
		public $adminEmail = [];

        /**
         * Creates an Account object based on the account identifier
         *
         * @param string $id Account identifier
         */
		public function __construct ($id) {
			$this->id = $id;
			$pdo = DBUtils::CreateConnection();
			$sqlstatement = "SELECT UnitID, Paid, Expired, ExpiresIn, PaidEventLimit, UnpaidEventLimit FROM ".DB_TABLES['Accounts']." WHERE AccountID = :aid;";
			$stmt = $pdo->prepare($sqlstatement);
			$stmt->bindValue(":aid", $this->id);
			$data = DBUtils::ExecutePDOStatement($stmt);
			$this->orgIDs = [];
			$this->orgSQL = '(';
			if (count ($data)) {
				foreach ($data as $datum) {
					$this->orgIDs[] = $datum['UnitID'];
					$this->orgSQL .= $datum['UnitID'].', ';
				}
				$this->paid = ($data[0]['Paid'] == 1);
				$this->expired = ($data[0]['Expired'] == 1);
				$this->expiresIn = $data[0]['ExpiresIn'];
				$this->paidEventLimit = $data[0]['PaidEventLimit'];
				$this->unpaidEventLimit = $data[0]['UnpaidEventLimit'];
			} else {
				$this->orgSQL.='0';
			}
			$this->orgSQL = rtrim($this->orgSQL, ', ') . ')';

			//get Admin names & email addresses
			$sqlstatement = "SELECT capid, memname, memrank FROM ".DB_TABLES['AccessLevel']." WHERE AccessLevel = \"Admin\" AND AccountID = :aid;";
			$stmt = $pdo->prepare($sqlstatement);
			$stmt->bindValue(":aid", $this->id);
			$data = DBUtils::ExecutePDOStatement($stmt);
			if (count($data) > 0) {
				foreach ($data as $datum) {
					$this->adminName[$datum['capid']] = $datum['memrank']." ".$datum['memname'];
					$sqlstatement = "SELECT Contact FROM ".DB_TABLES['MemberContact']." WHERE CAPID = :cid ";
					$sqlstatement .= "AND (Type = \"EMAIL\" OR Type = \"CADET PARENT EMAIL\") AND DoNotContact = 0;";
					$stmt = $pdo->prepare($sqlstatement);
					$stmt->bindValue(":cid", $datum['capid']);
					$contactdata = DBUtils::ExecutePDOStatement($stmt);
					$emailaddresses = '';
					foreach ($contactdata as $emailaddress) {
						$emailaddresses .= $emailaddress['Contact'].', ';
					}
					if (strlen($emailaddresses) > 0) { $emailaddresses = rtrim($emailaddresses, ', '); }
					$this->adminEmail[$datum['capid']] = $emailaddresses;
				}
			}
		}

        /**
         * Gets an account identifier
         *
         * @return string AccountID
         */
		public function getAccountNumber () {
			return $this->id;
		}

        /**
         * Gets an array of event numbers
         *
         * @param string $id Account identifier
         *
         * @return int[]
         */
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

         /**
         * Gets a count of the events associated with an account
         *
         * @return int count of events
         */
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

		public function getGoogleCalendarShareLink() {
			return $this->getGoogleCalendarAccountId("Share");
		}

		public function getGoogleCalendarEmbedLink() {
			return $this->getGoogleCalendarAccountId("Embed");
		}

		private function getGoogleCalendarAccountId($calType) {
			$pdo = DBUtils::CreateConnection();
			$stmt = $pdo->prepare("SELECT CalendarID FROM ".DB_TABLES['GoogleCalendarIDs']."
				WHERE AccountID = :id AND CalendarType = :calType;");
			$stmt->bindValue(":id", $this->id);
			$stmt->bindValue(":calType", $calType);
			$data = DBUtils::ExecutePDOStatement($stmt);
			if(count($data)) {
				return $data[0]['CalendarID'];
			} else {
				return "Error";
			}
		}

		public function getMembers () {
			$ret = [];
			$nowtime = time();
			$pdo = DBUtils::CreateConnection();
			$stmt = $pdo->prepare("SELECT CAPID FROM ".DB_TABLES['Member']." WHERE Expiration>:nowt AND ORGID in $this->orgSQL ORDER BY NameLast, NameFirst;");
//			$stmt->bindValue(":id", $this->id);
			$stmt->bindValue(':nowt', $nowtime);
			$data = DBUtils::ExecutePDOStatement($stmt);
			foreach ($data as $datum) {
				$ret[] = OldMember::Estimate($datum['CAPID']);
			}
			return $ret;
		}

		public function genMembers () {
			$ret = [];
			$nowtime = time();
			$pdo = DBUtils::CreateConnection();
			$stmt = $pdo->prepare("SELECT CAPID FROM ".DB_TABLES['Member']." WHERE Expiration>:nowt AND ORGID in $this->orgSQL ORDER BY NameLast, NameFirst;");
//			$stmt->bindValue(':id', $this->id);
			$stmt->bindValue(':nowt', $nowtime);
			$data = DBUtils::ExecutePDOStatement($stmt);
			foreach ($data as $datum) {
				yield OldMember::Estimate($datum['CAPID']);
			}
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


		public function getNextMeetingTimestamp () {
			$pdo = DBUtils::CreateConnection();
			$stmt = $pdo->prepare("");
		}

		public function hasMember ($mem) {
			$pdo = DBUtils::CreateConnection();
			$stmt = $pdo->prepare("SELECT COUNT(*) AS ccount FROM ".DB_TABLES['Member']." WHERE ORGID in $this->orgSQL AND CAPID = :cid;");
			$stmt->bindValue(':cid', $mem->uname);
			$data = DBUtils::ExecutePDOStatement($stmt);
			return $data[0]['ccount'] == 1;
		}

		public function __toString () {
			if (count($this->id) == 3) { // mer 
				return strtoupper($this->id); // MER
			} else { // md089
				return strtoupper(substr($this->id, 0, 2)) . '-' . substr($this->id, 2, 3); // MD-089
			}
		}
	}
