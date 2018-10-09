<?php

	require_once (BASE_DIR."lib/TaskRecipients.php");

	class Task {
		public $Name = '';

		public $Tasker = 0;

		public $ID = 0;

		public $Description = '';

		public $TaskRecipients;

		public $Done = 0;

		public static function Get (int $id) {
			global $_ACCOUNT;
			$pdo = DB_Utils::CreateConnection();
			$stmt = $pdo->prepare("SELECT * FROM ".DB_TABLES['Tasks']." WHERE ID = :tid AND AccountID = :aid");
			$stmt->bindValue(":tid", $id);
			$stmt->bindValue(":aid", $_ACCOUNT->id);
			$data = DBUtils::ExecutePDOStatement($stmt);
			if (count($data) != 1) {
				return false;
			}
			$data[0]['ID'] = $id;
			return new self ($data[0]);
		}

		public static function Create ($name, $description='') {
			global $_USER, $_ACCOUNT;

			$pdo = DBUtils::CreateConnection();

			$stmt = $pdo->prepare("INSERT INTO ".DB_TABLES['Tasks']."
			(Name, Tasker, Description, AccountID, Done) VALUES (
					:name, :tasker, :desc, :aid, 0
				)");
			$stmt->bindValue(":name", $name);
			$stmt->bindValue(":tasker", $_USER->uname);
			$stmt->bindValue(":desc", $description);
			$stmt->bindValue(":aid", $_ACCOUNT->id);
			if (!$stmt->execute()) {
				trigger_error($stmt->errorInfo()[2], 512);
			}

			return new self (array (
				'ID' => $pdo->lastInsertId(),
				'Name' => $name,
				'Description' => $description,
				'Tasker' => $_USER->uname,
				'Done' => 0
			));
		}

		public static function GetFor (\Member $mem = Null) {
			if (!isset($mem)) {
				global $_USER;
				$mem = $_USER;
			}
			global $_ACCOUNT;
			$pdo = DBUtils::CreateConnection();

			$stmt = $pdo->prepare("SELECT TaskID FROM ".DB_TABLES['TaskRecipients']." WHERE CAPID = :cid AND Done = 0;");
			$stmt->bindValue(':cid', $mem->uname);
			$data = DBUtils::ExecutePDOStatement($stmt);
			$ret = [];
			foreach ($data as $datum) {
				$ret[] = self::Get($datum['TaskID']);
			}
			return $ret;
		}

		public static function GetForTasker (\Member $mem = Null, $done = False) {
			if (!isset($mem)) {
				global $_USER;
				$mem = $_USER;
			}

			global $_ACCOUNT;

			$pdo = DBUtils::CreateConnection();

			$stmt = $pdo->prepare("SELECT ID FROM ".DB_TABLES['Tasks']." WHERE Tasker = :cid AND Done = :done AND AccountID = :aid;");
			$stmt->bindValue(':cid', $mem->uname);
			$stmt->bindValue(':done', $done ? 1 : 0);
			$stmt->bindValue(':aid', $_ACCOUNT->id);
			$data = DBUtils::ExecutePDOStatement($stmt);
			$ret = [];
			foreach ($data as $datum) {
				$ret[] = self::Get($datum['ID']);
			}
			return $ret;
		}

		private function __construct ($data) {
			$this->data = $data;
			foreach ($data as $k => $v) {
				$this->$k = $v;
			}
			$this->Tasker = Member::Estimate($data['Tasker']);
			$this->ID = (int)$data['ID'];

			$this->TaskRecipients = new TaskRecipients($this->ID);
		}

		public function done (\Member $mem, $comments) {
			return $this->TaskRecipients->done($mem, $comments);
		}

		public function isAlive () {
			return $this->TaskRecipients->getNotDone() > 0;
		}

		public function has (\Member $mem) {
			return $this->TaskRecipients->has($mem);
		}

		public function save () {
			global $_ACCOUNT;
			$pdo = DBUtils::CreateConnection();
			$stmt = $pdo->prepare("UPDATE ".DB_TABLES['Tasks']." SET Name=:name, Description=:desc, Done=:done WHERE ID=:id AND AccountID = :aid");
			$stmt->bindValue(":name", $this->Name);
			$stmt->bindValue(':desc', $this->Description);
			$stmt->bindValue(":done", $this->Done);
			$stmt->bindValue(':id', $this->ID);
			$stmt->bindValue(':aid', $_ACCOUNT->id);
			if(!$stmt->execute()) {
				trigger_error($stmt->errorInfo()[2], E_USER_WARNING);
            }
		}

		public function __destruct () {
			$this->save();
		}
	}
