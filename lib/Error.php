<?php
	class ErrorMSG {
		public static function Log ($message, $file) {
			$pdo = DB_Utils::CreateConnection();
            $stmt = $pdo->prepare('INSERT INTO '.DB_TABLES['ErrorMessages'].' (timestamp, enumber, errname, message, badfile, badline, context, capid) 
            VALUES
            (:timestamp, :enumber, :errname, :message, :badfile, :badline, :context, :capid);');
            $time = time();
            global $_USER;
            if (!isset($_USER)) {
                $capid = null;
            } else {
                $capid = $_USER->uname;
            }
            $stmt->bindValue(':enumber', 512);
            $stmt->bindValue(':errname', "E_USER_WARNING");
            $stmt->bindValue(':message', $message);
            $stmt->bindValue(':badfile', $file);
            $stmt->bindValue(':badline', 0);
            $stmt->bindValue(':context', print_r($GLOBALS, true));
            $stmt->bindValue(':timestamp', $time);
            $stmt->bindValue(':capid', $capid);
            return $stmt->execute();
		}
	}