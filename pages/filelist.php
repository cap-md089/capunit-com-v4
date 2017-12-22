<?php
	class Output {
		public static function doPut ($e, $c, $l, $m, $a) {
			$pdo = DBUtils::CreateConnection();
			$stmt = $pdo->prepare("SELECT ID, Name, IsPhoto, LENGTH(DATA) AS Size FROM ".DB_TABLES['FileData']." WHERE AccountID = :aid;");
			$stmt->bindValue(':aid', $a->id);
			$data = DBUtils::ExecutePDOStatement($stmt);
			$ret = [];
			foreach ($data as $file) {
				$ret[$file['ID']] = [
					$file['Name']." (".$file['Size'].")",
					$file['IsPhoto'] == 1
				];
			}
			return json_encode($ret);
		}
	}