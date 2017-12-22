<?php
    /**
     * @package lib/analytics
     * 
     * A library which allows a different way of logging
	 * users going to specific pages, and can be used (hopefully)
	 * in the future with NeuralNets to guess how many people
	 * will show up to future events
     *
     * @author Andrew Rioux <arioux303931@gmail.com>
     * 
     * @copyright 2016-2017 Rioux Development Team
     */

    /**
     * Creates analytical data
     */
	class Analytics {
        /**
         * Logs someone signing in
         *
         * @return void
         */
		public static function LogSignin ($success, $uname) {
			
		}

        /**
         * Logs when someone browses to a page
         * 
         * @param Member|Null $user User to log
         * 
         * @return void
         */
        public static function LogBrowser ($user=Null) {
            if (isset($user)) {
                $user = $user->uname;
                if ($user == 0) {
                    $user = 'www';
                }
            } else {
                $user = 'www';
            }
            $browser = UtilCollection::GetBrowser();
            $pdo = DBUtils::CreateConnection();
            $stmt = $pdo->prepare("SELECT Hits FROM ".DB_TABLES['BrowserAnalytics']." WHERE (CAPID = :cid AND Type = :bid AND Version = :bv AND `IP` = :ip);");
            $stmt->bindValue(":cid", $user);
            $stmt->bindValue(":bid", $browser['browser']);
            $stmt->bindValue(":bv", $browser['majorver']);
            $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR']);
            $data = DBUtils::ExecutePDOStatement($stmt);
            if (count($data) == 0) {
                $stmt = $pdo->prepare("INSERT INTO ".DB_TABLES['BrowserAnalytics']." VALUES (:cid, :bid, :bv, 1, :ip);");
                $stmt->bindValue(':cid', $user);
                $stmt->bindValue(':bid', $browser['browser']);
                $stmt->bindValue(':bv', $browser['majorver']);
                $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR']);
                if (!$stmt->execute()) {
                    ErrorMSG::Log($stmt->errorInfo()[2], "analytics.php");
                }
            } else {
                $stmt = $pdo->prepare("UPDATE ".DB_TABLES['BrowserAnalytics']." SET Hits = :hits WHERE (CAPID = :cid AND Type = :bid AND Version = :bv AND `IP` = :ip);");
                $stmt->bindValue(':hits', $data[0]['Hits']+1);
                $stmt->bindValue(':cid', $user);
                $stmt->bindValue(':bid', $browser['browser']);
                $stmt->bindValue(':bv', $browser['majorver']);
                $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR']);
                if (!$stmt->execute()) {
                    ErrorMSG::Log($stmt->errorInfo()[2], "analytics.php");
                }
            }
        }
	}