<?php
	require_once (BASE_DIR."lib/DB_Utils.php");
	require_once (BASE_DIR."lib/Account.php");

	class Registry {
		public static $_data;

		public static function Initialize () {
			global $_ACCOUNT;
			$pdo = DBUtils::CreateConnection();

			$stmt = $pdo->prepare("SELECT RegistryKey, Value, Type FROM ".DB_TABLES['Registry']." WHERE AccountID='www';");
			$data = DBUtils::ExecutePDOStatement($stmt, false);
			self::$_data = new StdClass();
			foreach ($data as $datum) {
				$key = $datum['RegistryKey'];
				$value = $datum['Value'];
				$type = $datum['Type'];
				
				$key = explode(".", $key);
				$data = self::$_data;
				for ($i = 0, $kv = $key[0]; $i < count($key); $i++, $kv=isset($key[$i])?$key[$i]:Null) {
					if (!isset($data->$kv) && $i < count($key) - 1) $data->$kv = new StdClass();
					else if ($i == count($key) - 1)	$data->$kv = self::cast($value, $type);
					$data = $data->$kv;
				}
			}

			$stmt = $pdo->prepare("SELECT RegistryKey, Value, Type FROM ".DB_TABLES['Registry']." WHERE AccountID=:aid;");
			$stmt->bindValue(':aid', $_ACCOUNT->id);
			$data = DBUtils::ExecutePDOStatement($stmt, false);
			foreach ($data as $datum) {
				$key = $datum['RegistryKey'];
				$value = $datum['Value'];
				$type = $datum['Type'];
				
				$key = explode(".", $key);
				$data = self::$_data;
				for ($i = 0, $kv = $key[0]; $i < count($key); $i++, $kv=isset($key[$i])?$key[$i]:Null) {
					if (!isset($data->$kv) && $i < count($key) - 1) $data->$kv = new StdClass();
					else if ($i == count($key) - 1) $data->$kv = self::cast($value, $type);
					$data = $data->$kv;
				}
			}
		}

		public static function get ($v) {
			if (count(explode('.', $v)) > 0) {
				$data = self::$_data;	
				foreach (explode('.', $v) as $kv) {
					$data = $data->$kv;
				}
				return $data;
			} else {
				return (self::$_data)->$v;
			}
		}
		
		public static function set ($key, $value) {
			global $_ACCOUNT;
			$pdo = DBUtils::CreateConnection();
			$stmt = $pdo->prepare("SELECT COUNT(*) AS Fields FROM ".DB_TABLES['Registry']." WHERE AccountID = :aid AND RegistryKey = :key;");
			$stmt->bindValue(':aid', $_ACCOUNT->id);
			$stmt->bindValue(':key', $key);
			$data = DBUtils::ExecutePDOStatement($stmt);
			if ($data[0]['Fields'] == 0) {
				$stmt = $pdo->prepare("SELECT Type FROM ".DB_TABLES['Registry']." WHERE AccountID = 'www' AND RegistryKey = :key;");
				$stmt->bindValue(':key', $key);
				$data = DBUtils::ExecutePDOStatement($stmt)[0]['Type'];

				$stmt = $pdo->prepare("INSERT INTO ".DB_TABLES['Registry']." VALUES (:aid, :rke, :val, '$data');");
				$stmt->bindValue(':aid', $_ACCOUNT->id);
				$stmt->bindValue(':rke', $key);
				$stmt->bindValue(':val', $value);
				if (!$stmt->execute()) {
					trigger_error($stmt->errorInfo()[2], E_USER_WARNING);
				}
			} else {
				$stmt = $pdo->prepare("UPDATE ".DB_TABLES['Registry']." SET Value = :val WHERE (AccountID = :aid AND RegistryKey = :key);");
				$stmt->bindValue(':val', $value);
				$stmt->bindValue(':key', $key);
				$stmt->bindValue(':aid', $_ACCOUNT->id);
				if (!$stmt->execute()) {
					trigger_error($stmt->errorInfo()[2], E_USER_WARNING);
				}
			}
		}

		private static function cast ($v, $t) {
			switch ($t) {
				case "bool" :
					return $v == 'true' || $v == 1;
				break;

				case "int" :
					return (int)$v;
				break;

				case "str" :
					return $v;
				break;
			}
		}
	}