<?php
	class ErrorMSG {
		public static function Log ($message, $file) {
			$pdo = DB_Utils::CreateConnection();
            $stmt = $pdo->prepare('INSERT INTO '.DB_TABLES['ErrorMessages'].' (timestamp, enumber, errname, message, badfile, badline, context) 
            VALUES
            (:timestamp, :enumber, :errname, :message, :badfile, :badline, :context);');
            $time = time();
            $stmt->bindValue(':enumber', 512);
            $stmt->bindValue(':errname', "E_USER_WARNING");
            $stmt->bindValue(':message', $message);
            $stmt->bindValue(':badfile', $file);
            $stmt->bindValue(':badline', 0);
            $stmt->bindValue(':context', print_r($GLOBALS, true));
            $stmt->bindValue(':timestamp', $time);
            return $stmt->execute();
		}
	}