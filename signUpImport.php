<?php

require ("config.php");
require_once ("lib/Account.php");
$_ACCOUNT = new Account("md001");
require_once ("lib/GoogleCalendar.php");
Registry::Initialize();
require_once ("lib/Event.php");
require_once ("lib/vendor/autoload.php");
require_once ("lib/logger.php");
require_once ("lib/general.php");
require_once ("lib/DB_Utils.php");
require_once ("lib/Member.php");
echo "Script initialized\n";
echo Util_Collection::GetTimestamp2("02/09/2019 06:00");
echo "\n";

$pdo = DB_Utils::CreateConnection();

$stmt = $pdo->prepare("SELECT * FROM SignInData;");
//$stmt->bindValue(':orgid', $id);

$data = DBUtils::ExecutePDOStatement($stmt);


if(count($data)>0) {
	foreach($data as $datum) {
		$member=Member::Estimate($datum['CAPID']);
		$orgid=Util_Collection::GetOrgIDFromUnit($datum['Squadron']);

                    //explode squadron
                    $unit=explode("-", $datum['Squadron']);

                    //insert member data into Data_Member table
                    $sqlin = "INSERT INTO EventManagement.Data_Member ";
                    $sqlin .= "(Timestamp, DataSource, CAPID, NameLast, NameFirst, NameMiddle, NameSuffix, Gender, ORGID, Wing, UNIT, ";
                    $sqlin .= "Rank, Expiration, Type, RankDate, Region) VALUES ";
                    $sqlin .= "(:ts, :src, :cid, :nlast, :nfirst, :nmid, :nsuf, :gen, :orgid, :wing, :unit, :rank, :expdte, :type, :rdte, :reg) ";
                    $sqlin .= "ON DUPLICATE KEY UPDATE Timestamp = :ts, DataSource = :src, NameLast = :nlast, NameFirst = :nfirst, ";
                    $sqlin .= "NameMiddle = :nmid, NameSuffix = :nsuf, Gender = :gen, ORGID = :orgid, Wing = :wing, ";
                    $sqlin .= "UNIT = :unit, Rank = :rank, Expiration = :expdte, Type = :type, RankDate = :rdte, Region = :reg;";
                    $stmt = $pdo->prepare($sqlin);
                    $stmt->bindValue(':ts', time());
                    $stmt->bindValue(':src', "S");
                    $stmt->bindValue(':cid', $datum['CAPID']);
                    $stmt->bindValue(':nlast', $datum['MemberNameLast']);
                    $stmt->bindValue(':nfirst', $datum['MemberNameFirst']);
                    $stmt->bindValue(':nmid', "");
                    $stmt->bindValue(':nsuf', $datum['NameSuffix']);
                    $stmt->bindValue(':gen', $datum['Gender']);
                    $stmt->bindValue(':orgid', UtilCollection::GetOrgIDFromUnit($member->Squadron));
                    $stmt->bindValue(':wing', $unit[1]);
                    $stmt->bindValue(':unit', $unit[2]);
                    $stmt->bindValue(':rank', $datum['MemberRank']);
                    $stmt->bindValue(':expdte', strtotime($datum['Expiration']));
                    $stmt->bindValue(':type', $datum['Type']);
                    $stmt->bindValue(':rdte', strtotime($datum['RankDate']));
                    $stmt->bindValue(':reg', $unit[0]);

                    try {
                        if (!$stmt->execute()) {
                            echo "signUpImport couldn't execute update Data_Member on $member->uname, \n";
                        }
                        echo "signUpImport update Data_Member on $member->uname\n";
                    } catch (PDOException $e) {
                        echo "signUpImport couldn't update Data_Member on $member->uname due to exception, \n";
                    }





	}
}

