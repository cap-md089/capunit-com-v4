<?php
	require_once (BASE_DIR . "lib/DB_Utils.php");
	require_once (BASE_DIR . "lib/general.php");
	require_once (BASE_DIR . "lib/Permissions.php");
	require_once (BASE_DIR . "lib/Registry.php");
	require_once (BASE_DIR . "lib/OldMember.php");

	class Member {
		private const DEFAULT_ITERATION_COUNT = 343506;
		private const PASSWORD_MAX_AGE_SECONDS = 6 * 30 * 24 * 60 * 60;
		private const PASSWORD_MIN_AGE_SECONDS =      7 * 24 * 60 * 60;

		public const PASSWORD_ERROR_COMPLEXITY = 'Password is not complex enough.';
		public const PASSWORD_ERROR_HISTORY = 'Password cannot be one of the 5 previous passwords';
		public const PASSWORD_ERROR_EXPIRED = 'Password has expired';
		public const PASSWORD_ERROR_INCORRECT = 'The account credentials could not be verified';
		public const PASSWORD_ERROR_ACCOUNT_NOT_FOUND = 'The account credentials could not be verified';

		private static function HashPassword($password, $salt, $iterations) {
			$hash = $password . $salt;
			for ($i = 0; $i < $iterations; $i++) {
				$hash = hash('sha512', $hash . $salt, true);
			}
			return $hash;
		}

		public static function StageUserCreation($capid, $email) {
			$pdo = DBUtils::CreateConnection();

			$stmt = $pdo->prepare("SELECT COUNT(*) as CAPIDCount FROM UserAccountInfo WHERE CAPID = :capid;");
			$stmt->bindValue(':capid', $capid);
			$values = DBUtils::ExecutePDOStatement($stmt);

			if ($values[0]['CAPIDCount'] >= 1) {
				return [
					'success' => false
				];
			}

			$m = OldMember::Estimate($capid);
			if (!$m) {
				return [
					'success' => false
				];
			}

			if (!(
				(count($m->contact['CADETPARENTEMAIL']['PRIMARY']) > 0 && $m->contact['CADETPARENTEMAIL']['PRIMARY'][0] == $email) ||
				(count($m->contact['CADETPARENTEMAIL']['SECONDARY']) > 0 && $m->contact['CADETPARENTEMAIL']['SECONDARY'][0] == $email) ||
				(count($m->contact['CADETPARENTEMAIL']['EMERGENCY']) > 0 && $m->contact['CADETPARENTEMAIL']['EMERGENCY'][0] == $email) ||
				(count($m->contact['EMAIL']['PRIMARY']) > 0 && $m->contact['EMAIL']['PRIMARY'][0] == $email) ||
				(count($m->contact['EMAIL']['SECONDARY']) > 0 && $m->contact['EMAIL']['SECONDARY'][0] == $email) ||
				(count($m->contact['EMAIL']['EMERGENCY']) > 0 && $m->contact['EMAIL']['EMERGENCY'][0] == $email)
			)) {
				return [
					'success' => false
				];
			}

			$token = bin2hex(random_bytes(25));

			$stmt = $pdo->prepare('INSERT INTO UserAccountToken (Token, TokenExpire, CAPID, EmailAddress) VALUES (:token, :tokenexpire, :capid, :emailaddress);');
			$stmt->bindValue(':token', $token);
			$stmt->bindValue(':tokenexpire', time() + (24 * 60 * 60));
			$stmt->bindValue(':capid', $capid);
			$stmt->bindValue(':emailaddress', $email);

			return [
				'success' => $stmt->execute(),
				'token' => $token
			];
		}

		public static function IsValidToken($token) {
			$pdo = DBUtils::CreateConnection();

			$stmt = $pdo->prepare('DELETE FROM UserAccountToken WHERE TokenExpire < :now;');
			$stmt->bindValue(':now', time());
			$stmt->execute();

			$stmt = $pdo->prepare('SELECT CAPID FROM UserAccountToken WHERE Token = :token;');
			$stmt->bindValue(':token', $token);
			$data = DBUtils::ExecutePDOStatement($stmt);

			return count($data) == 1;
		}

		public static function AddUser($token, $username, $password) {
			$pdo = DBUtils::CreateConnection();

			$stmt = $pdo->prepare('DELETE FROM UserAccountToken WHERE TokenExpire < :now;');
			$stmt->bindValue(':now', time());
			$stmt->execute();

			$stmt = $pdo->prepare('SELECT CAPID FROM UserAccountToken WHERE Token = :token;');
			$stmt->bindValue(':token', $token);
			$data = DBUtils::ExecutePDOStatement($stmt);

			print_r($data);

			echo "\n\n";

			if (count($data) != 1) {
				return [
					'success' => false,
					'reason' => 'Invalid token'
				];
			}

			$stmt = $pdo->prepare('SELECT COUNT(*) as MemberCount FROM UserAccountInfo where UserID = :userid;');
			$stmt->bindValue(':userid', $username);
			$values = DBUtils::ExecutePDOStatement($stmt);
			if ($values[0]['MemberCount'] != 0) {
				return [
					'success' => false,
					'reason' => 'Username already taken'
				];
			}

			$stmt = $pdo->prepare('INSERT INTO UserAccountInfo (UserID, CAPID, Status) VALUES (:userid, :capid, 1);');
			$stmt->bindValue(':userid', $username);
			$stmt->bindValue(':capid', $data[0]['CAPID']);

			if (!$stmt->execute()) {
				trigger_error($stmt->errorInfo()[2], E_USER_WARNING);
			}

			$mem = self::Get($username);
			$mem->setPassword($password);

			return [
				'success' => true,
				'member' => $mem
			];
		}

		public static function Create($username, $password) { return self::Signin($username, $password); }
		public static function Signin($username, $password) {
			$pdo = DBUtils::CreateConnection();

			$stmt = $pdo->prepare('SELECT CAPID, PasswordHash, PasswordSalt, PasswordIterationCount, AddTime FROM AccountPasswords WHERE HistoryIndex = 0 AND UserID = :username;');
			$stmt->bindValue(':username', $username);
			
			$data = DBUtils::ExecutePDOStatement($stmt);

			if (count($data) != 1) {
				return new class {
					public $success = false;
					public $data = [
						'success' => false
					];
				};
			}
			
			$hashed_password = self::HashPassword($password, $data[0]['PasswordSalt'], $data[0]['PasswordIterationCount']);
			
			if ($hashed_password != $data[0]['PasswordHash']) {
				return new class {
					public $success = false;
					public $data = [
						'success' => false
					];
				};
			}

			if (($data[0]['AddTime'] + self::PASSWORD_MAX_AGE_SECONDS) < time()) {
				return new class {
					public $success = false;
					public $data = [
						'success' => false,
						'reset' => true
					];
				};
			}

			$m = new self(
				$username,
				$data[0]['CAPID']
			);
			
			$m->setSessionID();
			$m->perms = $m->getAccessLevels();
			$m->capid = $m->uname;

			return $m;
		}

		public static function Check($cookies) {
			$pdo = DBUtils::CreateConnection();

			$cookies = json_decode($cookies, true);

			$sess = DB_TABLES['SessionStorage'];
			$stmt = $pdo->prepare("DELETE FROM $sess WHERE `time` < " . time() . ";");
			$stmt->execute();

            $stmt = $pdo->prepare("SELECT * FROM $sess WHERE `time` > :time AND sessionid = :sid AND mid = :mid;");
            $stmt->bindValue(":time", time(), PDO::PARAM_INT);
            $stmt->bindValue(":sid", $cookies['sid'], PDO::PARAM_STR);
            $stmt->bindValue(':mid', $cookies['uname']);
            $sid = $cookies['sid'];
			$ret = DB_Utils::ExecutePDOStatement($stmt);

			if (count($ret) != 1) {
				return array ("valid" => false);
			} else {
                $stmt = $pdo->prepare("UPDATE $sess SET `time` = :time WHERE sessionid = :sid;");
                $stmt->bindValue(':time', time()+(1200));
                $stmt->bindValue(':sid', $cookies['sid']);
				DB_Utils::ExecutePDOStatement($stmt);

                $m = self::Estimate($cookies['uname']);
                $m->sid = $cookies['sid'];
                $m->cookieData = $ret[0]['cdata'];

                $m->perms = $m->getAccessLevels();
                $m->dutyPositions = $m->getDutyPositions();
				$m->flight = $m->getFlight();
				$sid = $m->setSessionID();
				return array (
					'mem' => $m,
					'valid' => true,
					'sid' => $sid
				);
			}
		}

		public static function Get($username, $global=false, $account=null) {
			$pdo = DBUtils::CreateConnection();

			$stmt = $pdo->prepare("SELECT CAPID FROM UserAccountInfo WHERE UserID = :userid");
			$stmt->bindValue(':userid', $username);

			$data = DBUtils::ExecutePDOStatement($stmt);

			if (count($data) != 1) {
				return false;
			}

			return new self($username, $data[0]['CAPID'], $global, $account);
		}

		public static function Estimate($capid, $global=false, $account=null) {
			$pdo = DBUtils::CreateConnection();

			$stmt = $pdo->prepare("SELECT UserID FROM UserAccountInfo WHERE CAPID = :capid");
			$stmt->bindValue(':capid', $capid);

			$data = DBUtils::ExecutePDOStatement($stmt);

			if (count($data) != 1) {
				return false;
			}

			return new self($data[0]['UserID'], $capid, $global, $account);
		}

		public static function GetByDutyPosition ($dpts) {
			$pdo = DBUtils::CreateConnection();

            $dp = DB_TABLES["DutyPosition"];
            $cdp = DB_TABLES["CadetDutyPositions"];
            $stmt = $pdo->prepare("(SELECT CAPID FROM $dp WHERE Duty = :dp) UNION (SELECT CAPID FROM $cdp WHERE Duty = :cdp);");
            $stmt->bindValue(":dp", $dpts);
            $stmt->bindValue(":cdp", $dpts);
            $data = DBUtils::ExecutePDOStatement($stmt);
            
            foreach ($data as $datum) {
                yield self::Estimate($datum['CAPID']);
            }
		}

		public $username = '';

		public $capid = 0;

		private $member = null;

		private function __construct(
			$username,
			$capid,
			$global = false,
			$account = null
		) {
			$this->username = $username;
			$this->capid = $capid;
			$this->member = OldMember::Estimate($capid, $global, $account);
		}

		public function setPassword($password) {
			if (!(
				preg_match("/[ \^!@#$%&*(){}_+-=<>,.?\/[\]\\\|;'\"]/", $password) == 1 &&
				preg_match("/[a-z]/", $password) == 1 &&
				preg_match("/[A-Z]/", $password) == 1 &&
				preg_match("/[0-9]/", $password) == 1
			)) {
				return [
					'success' => false,
					'reason' => 'Password does not meet complexity requirements'
				];
			}

			$pdo = DBUtils::CreateConnection();

			$stmt = $pdo->prepare("SELECT PasswordHash, PasswordSalt, PasswordIterationCount FROM UserPasswordData where UserID = :userid");
			$stmt->bindValue('userid', $this->username);
			$values = DBUtils::ExecutePDOStatement($stmt);
			$c = count($values);
			if ($c != 0) {
				foreach ($values as $passrow) {
					$enc_pass = self::HashPassword($password, $passrow['PasswordSalt'], $passrow['PasswordIterationCount']);
					if ($enc_pass == $passrow['PasswordHash']) {
						return [
							'success' => false,
							'reason' => 'Cannot reuse an old password'
						];
					}
				}

				if($c == 5) { $pdo->exec("DELETE FROM UserPasswordData WHERE HistoryIndex = 4;"); }
				if($c >= 4) { $pdo->exec("UDPATE UserPasswordData SET HistoryIndex = 4 WHERE HistoryIndex = 3;"); }
				if($c >= 3) { $pdo->exec("UDPATE UserPasswordData SET HistoryIndex = 3 WHERE HistoryIndex = 2;"); }
				if($c >= 2) { $pdo->exec("UDPATE UserPasswordData SET HistoryIndex = 2 WHERE HistoryIndex = 1;"); }
				if($c >= 1) { $pdo->exec("UDPATE UserPasswordData SET HistoryIndex = 1 WHERE HistoryIndex = 0;"); }
			}
			
			$iterations = self::DEFAULT_ITERATION_COUNT;
			$salt = bin2hex(random_bytes(128));
			$hash = self::HashPassword($password, $salt, $iterations);
	
			$stmt = $pdo->prepare("INSERT INTO UserPasswordData (UserID, PasswordHash, PasswordSalt, PasswordIterationCount, HistoryIndex, AddTime) VALUES (:userid, :phash, :psalt, :piter, 0, :addtime);");
			$stmt->bindValue(':userid', $this->username);
			$stmt->bindValue(':phash', $hash);
			$stmt->bindValue(':psalt', $salt);
			$stmt->bindValue(':piter', $iterations);
			$stmt->bindValue(':addtime', time());
			$stmt->execute();

			return [
				'success' => true
			];
		}

		public function __call($name, $arguments) {
			return call_user_func_array(array($this->member, $name), $arguments);
		}

		public function __get($name) {
			return $this->member->$name;
		}

		public function __set($name, $value) {
			$this->member->$name = $value;
		}
	}
