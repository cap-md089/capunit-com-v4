<?php
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			if (isset($e['uri'][1])) {
				$download = ($e['uri'][1][0] == '1');
				if ($download) $e['uri'][1] = substr($e['uri'][0], 1);
			} else {
				$download = false;
			}
			$file = File::Get($e['uri'][0]);
			$sid = isset($e['uri'][1]) ? $e['uri'][1] : '';

			if (!$file) {
				return ['error' => 311];
			}

			if ($file->MemberOnly && !$l) {
				return ['error' => 411];
			}

			$log = new Logger ('ImageRequests');

			if (!$file->IsPhoto) {
				$pdo = DB_Utils::CreateConnection();
				$stmt = $pdo->prepare("SELECT * FROM ".DB_TABLES['FileSessions']." WHERE fileid = :fid AND sessid = :sid;");
				$stmt->bindValue(":fid", $e['uri'][0]);
				$stmt->bindValue(':sid', $sid);
				$data = DB_Utils::ExecutePDOStatement($stmt);
				if (count($data) != 1) {
					$log->Warn("File requested without appropriate session: ".$file->ID, 1);
					$text = "Haha, nope.";
					$name = $file->Name . ".txt";
				} else {
					$name = $file->Name;
					$text = $file->Data;
				}

				$stmt = $pdo->prepare("DELETE FROM ".DB_TABLES['FileSessions']." WHERE fileid = :fid AND sessid = :sid;");
				$stmt->bindValue(":fid", $e['uri'][0]);
				$stmt->bindValue(':sid', $sid);
				if (!$stmt->execute()) {
					trigger_error($stmt->errorInfo()[2], E_USER_WARNING);
				}
			} else {
				$text = $file->Data;
			}

			header("X-Being-Downloaded: ".($download?'t':'f'));

			if (!$file->IsPhoto || $download) {
				header ("Content-Disposition: attachment; filename=\"$file->Name\"");
				header ("Content-Type: application/octet-stream");
			} else {
				header ("Content-Type: $file->ContentType");
			}

			echo $text;
			exit(0);
		}

		public static function doPut ($e, $c, $l, $m, $a) {
			$file = File::Get($e['raw']['data']);

			if (!$file) {
				return ['error' => 311];
			}

			if (($file->MemberOnly && $l) || !$file->MemberOnly) {
				$sid = uniqid("file", true);
				$pdo = DB_Utils::CreateConnection();
				$stmt = $pdo->prepare("INSERT INTO ".DB_TABLES['FileSessions']." VALUES (:fid, :sid);");
				$stmt->bindValue(":fid", $e['raw']['data']);
				$stmt->bindValue(":sid", $sid);
				if (!$stmt->execute()) {
					trigger_error($stmt->errorInfo()[2], E_USER_WARNING);
				}
				return '/filedownloader/'.$file->ID.'/'.$sid.'?ajax=true';
			} else {
				return [
					'error' => 411
				];
			}
		}
	}