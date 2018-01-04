<?php
	class TaskRecipients implements Iterator {
		private $position = 0;

		public $TaskID = 0;

		public $TaskRecipients;

		public function __construct ($id) {
			global $_ACCOUNT;
			$this->TaskID = $id;
			$this->position = 0;
			$pdo = DBUtils::CreateConnection();
			$stmt = $pdo->prepare("SELECT CAPID, Done, DoneComments FROM ".DB_TABLES['TaskRecipients']." WHERE TaskID = :tid;");
			$stmt->bindValue(":tid", $id);
			$this->TaskRecipients = DBUtils::ExecutePDOStatement($stmt);
		}

		public function add (\Member $member) {
			$pdo = DBUtils::CreateConnection();
			$stmt = $pdo->prepare("INSERT INTO ".DB_TABLES['TaskRecipients']." VALUES (:tid, :cid, 0, '');");
			$stmt->bindValue(":cid", $member->uname);
			$stmt->bindValue(':tid', $this->TaskID);
			if (!$stmt->execute()) {
				trigger_error($stmt->errorInfo()[2], E_USER_WARNING);
			}
			$this->TaskRecipients[] = [
				'DoneComments' => '',
				'Done' => 0,
				'CAPID' => $member->uname
			];
		}

		public function done (\Member $mem, $comments) {
			$pdo = DBUtils::CreateConnection();
			$stmt = $pdo->prepare("UPDATE ".DB_TABLES['TaskRecipients']." SET Done=1, DoneComments=:comment WHERE TaskID = :tid AND CAPID = :cid;");
			$stmt->bindValue(":tid", $this->TaskID);
			$stmt->bindValue(':comment', $comments);
			$stmt->bindValue(":cid", $mem->uname);
			if (!$stmt->execute()) {
				trigger_error($stmt->errorInfo()[2], E_USER_WARNING);
			}
		}

		public function getNotDone () {
			$pdo = DBUtils::CreateConnection();
			$stmt = $pdo->prepare("SELECT COUNT(*) AS Done FROM ".DB_TABLES['TaskRecipients']." WHERE Done = 0 AND TaskID = :tid");
			$stmt->bindValue(":tid", $this->TaskID);
			return DBUtils::ExecutePDOStatement($stmt)[0]['Done'];
		}

		public function has (\Member $member) {
			$ret = false;
			foreach ($this->TaskRecipients as $row) {
				$ret = $ret || ((int)$row['CAPID'] == $member->uname);
				if ($ret) return true;
			}
			return false;
		}

		public function rewind () {
			$this->position = 0;
		}

		public function current () {
			$row = $this->TaskRecipients[$this->position];
			return [
				'Done' => $row['Done'] == 1,
				'Comments' => $row['DoneComments']
			];
		}

		public function key () {
			return (int)$this->TaskRecipients[$this->position]['CAPID'];
		}

		public function next () {
			$this->position++;
		}

		public function valid () {
			return isset($this->TaskRecipients[$this->position]);
		}
	}