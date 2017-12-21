<?php
	class Output {
		public static function doPost ($e, $c, $l, $m, $a) {
			if (!$a->paid) {return ['error' => 501];}
			$pdo = DBUtils::CreateConnection();
			$stmt = $pdo->prepare("SELECT COUNT(*) AS Count FROM ".DB_TABLES['Absentee']." WHERE CAPID = :cid;");
			$stmt->bindValue(':cid', $m->uname);
			$data = DBUtils::ExecutePDOStatement($stmt)[0]['Count'];
			if ($data > 0) {
				$stmt = $pdo->prepare("UPDATE ".DB_TABLES['Absentee']." SET AbsentUntil=:auntil, AbsentNotes=:notes WHERE CAPID=:cid");
				$stmt->bindValue(":auntil", $e['raw']['absentuntil']);
				$stmt->bindValue(':notes', $e['raw']['absentnotes']);
				$stmt->bindValue(':cid', $m->uname);
				DBUtils::ExecutePDOStatement($stmt);
			} else {
				$stmt = $pdo->prepare("INSERT INTO ".DB_TABLES['Absentee']." VALUES (:cid, :auntil, :notes)");
				$stmt->bindValue(":auntil", $e['raw']['absentuntil']);
				$stmt->bindValue(':notes', $e['raw']['absentnotes']);
				$stmt->bindValue(':cid', $m->uname);
				DBUtils::ExecutePDOStatement($stmt);
			}
		}
	}