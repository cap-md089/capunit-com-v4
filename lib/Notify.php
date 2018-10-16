<?php
	require_once (BASE_DIR."lib/DB_Utils.php");

	function SetNotify ($capid, $message, $remarks=Null) {
		$pdo = DB_Utils::CreateConnection();

		$sqlin = 'SELECT CAPID FROM '.DB_TABLES['Data_Member'].' WHERE CAPID=:cid ';
		$sqlin .= 'UNION SELECT CAPID FROM '.DB_TABLES['SignInData'].' WHERE CAPID=:cid;';
		$stmt = $pdo->prepare($sqlin);
		$stmt->bindValue(':cid', $capid);
		$validID = DBUtils::ExecutePDOStatement($stmt);

		$addNotice = false;
		if(count($validID) > 0) {
			$sqlin = 'INSERT INTO '.DB_TABLES['Notifications'];
			$sqlin .= '(CAPID, timestamp, message, remarks)';
			$sqlin .= 'VALUES (:cid, :time, :msg, :rmks);';
			$stmt = $pdo->prepare($sqlin);
			$stmt->bindValue(':cid', $capid);
			$stmt->bindValue(':time', time());
			$stmt->bindValue(':msg', $message);
			$stmt->bindValue(':rmks', $remarks);
			$addNotice = DBUtils::ExecutePDOStatement($stmt);
		}
		return addNotice;
	}

