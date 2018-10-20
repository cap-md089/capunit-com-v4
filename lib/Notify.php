<?php
	require_once (BASE_DIR."lib/DB_Utils.php");

	function SetNotify ($capid, $account, $message, $fileID, $remarks=Null) {
		$pdo = DB_Utils::CreateConnection();

		$sqlin = 'SELECT CAPID FROM '.DB_TABLES['Member'].' WHERE CAPID=:cid ';
		$sqlin .= 'UNION SELECT CAPID FROM '.DB_TABLES['SignInData'].' WHERE CAPID=:cid;';
		$stmt = $pdo->prepare($sqlin);
		$stmt->bindValue(':cid', $capid);
		$validID = DBUtils::ExecutePDOStatement($stmt);

		$addNotice = false;
		if(count($validID) > 0 || substr($capid, 0, 1)=='9') {
			$sqlin = 'INSERT INTO '.DB_TABLES['Notifications'];
			$sqlin .= '(CAPID, AccountID, timestamp, message, FileID, remarks)';
			$sqlin .= 'VALUES (:cid, :acct, :time, :msg, :fid, :rmks);';
			$stmt = $pdo->prepare($sqlin);
			$stmt->bindValue(':cid', $capid);
			$stmt->bindValue(':acct', $account);
			$stmt->bindValue(':time', time());
			$stmt->bindValue(':msg', $message);
			$stmt->bindValue(':fid', $fileID);
			$stmt->bindValue(':rmks', $remarks);
			$addNotice = DBUtils::ExecutePDOStatement($stmt);
		}
		return $addNotice;
	}

