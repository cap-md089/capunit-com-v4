<?php
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			$pdo = DBUtils::CreateConnection();
			$stmt = $pdo->prepare("SELECT SUM(Hits) AS Hits FROM BrowserAnalytics;");
			$TOTAL_BROWSES = DBUtils::ExecutePDOStatement($stmt);
			
			
		}
	}