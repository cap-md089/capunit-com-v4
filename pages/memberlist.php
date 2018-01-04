<?php
	class Output {
		public static function doPut ($e, $c, $l, $m, $a) {
			$pdo = DB_Utils::CreateConnection();
			$stmt = $pdo->prepare("SELECT NameFirst, NameLast, CAPID, NameMiddle, NameSuffix, Rank FROM ".DB_TABLES['Member']." WHERE ORGID in (SELECT UnitID as ORGID FROM Accounts WHERE AccountID = :aid);");
			$stmt->bindValue(':aid', $a->id);
			$data = DB_Utils::ExecutePDOStatement($stmt);
			$ret = [];
			foreach ($data as $datum) {
				$ret[$datum['CAPID']] = "{$datum['Rank']} {$datum['NameFirst']}" . ($datum['NameMiddle'] == '' ? ' '.$datum['NameMiddle'] : '')." {$datum['NameLast']}" .($datum['NameSuffix'] == '' ? ' '.$datum['NameSuffix'] : '');
			}
			$html = json_encode($ret);
			return $html;
		}
	}