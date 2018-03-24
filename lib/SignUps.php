<?php
    Class SignUps {
        static function Add($account, $EventNumber) {
            $pdo = DBUtils::CreateConnection();
            $sqlin = 'INSERT INTO '.DB_TABLES['SignUpQueue']; 
            $sqlin .= ' (AccountID, EventNumber) VALUES (:account, :EventNumber);';
            $stmt = $pdo->prepare($sqlin);
            $stmt->bindValue(':account', $account);
            $stmt->bindValue(':EventNumber', $EventNumber);
            // $return = DBUtils::ExecutePDOStatement($stmt);
            
            //error checking
            if (!$stmt->execute()) {
                if ($stmt->errorInfo()[1] == 1062) {
                    return false;
                }
                trigger_error($stmt->errorInfo()[2], E_USER_WARNING);
            }
            return true;
        }

        static function Send() {
            //query SignUpQueue
            $pdo = DBUtils::CreateConnection();
            $sqlin = 'SELECT AccountID, EventNumber FROM '.DB_TABLES['SignUpQueue'].' WHERE SummarySent=0;'; 
            $stmt = $pdo->prepare($sqlin);
            $signups = DBUtils::ExecutePDOStatement($stmt);
            //if returns, 
            foreach ($signups as $signup) {
                self::SendEvent($signup['AccountID'], $signup['EventNumber']);
            }

        }

        static function SendEvent($account, $EventNumber, $force=false) {
            //query in turn each event number
            //collect event information, attendance information
            $pdo = DBUtils::CreateConnection();
            $sqlin = 'SELECT CAPPOC1Email, CAPPOC1ReceiveSignUpUpdates, CAPPOC2Email, CAPPOC2ReceiveSignUpUpdates, ';
            $sqlin .= 'EventName, StartDateTime, CAPPOC1Name, CAPPOC2Name, EventNumber, AccountID, AdditionalEmailAddresses, ExtPOCEmail ';
            $sqlin .= ' FROM '.DB_TABLES['EventInformation'].' WHERE AccountID = :account AND EventNumber = :event;'; 
            $stmt = $pdo->prepare($sqlin);
            $stmt->bindValue(':account', $account);
            $stmt->bindValue(':event', $EventNumber);
            $event = DBUtils::ExecutePDOStatement($stmt);
            if(count($event) == 1) {
                $event = $event[0];
                if (!$force && time()>$event->StartDateTime) { return Null; }
                $sqlin = 'SELECT CAPID ';
                $sqlin .= ' FROM '.DB_TABLES['Attendance'].' WHERE AccountID = :account AND EventID = :event AND SummaryEmailSent=0;';
                $stmt = $pdo->prepare($sqlin);
                $stmt->bindValue(':account', $account);
                $stmt->bindValue(':event', $EventNumber);
                $newattendees = DBUtils::ExecutePDOStatement($stmt);
                $newattendeelist = '';
                if(count($newattendees)) {
                     foreach($newattendees as $at) {
                          $newattendeelist .= $at['CAPID'].', ';
                     }
                     $newattendeelist = rtrim($newattendeelist, ', ');
                }
                $sqlin = 'SELECT CAPID ';
                $sqlin .= ' FROM '.DB_TABLES['Attendance'].' WHERE AccountID = :account AND EventID = :event;';
                $stmt = $pdo->prepare($sqlin);
                $stmt->bindValue(':account', $account);
                $stmt->bindValue(':event', $EventNumber);
                $allattendees = DBUtils::ExecutePDOStatement($stmt);
                $allattendeelist = '';
                if(count($allattendees)) {
                     foreach($allattendees as $at) {
                          $allattendeelist .= $at['CAPID'].', ';
                     }
                     $allattendeelist = rtrim($allattendeelist, ', ');
                }

                $subject = 'Signup Update ('.count($allattendees).' member';
                if (count($allattendees) != 1) { $subject .= "s"; }
                $subject .= '), Event '.$event['EventNumber'];
                $subject .= ': '.$event['EventName'].' on ';
                $subject .= date(DATE_RSS, $event['StartDateTime']);

                if(count($newattendees) > 1) {
                    $newstatement = ' are '.count($newattendees).' new signups ';
                } elseif (count($newattendees) == 0) {
                    $newstatement = ' are no new signups ';
                } else {
                    $newstatement = ' is one new signup ';
                }

                if(count($allattendees) > 1) {
                    $allstatement = ' are '.count($allattendees).' signups ';
                } elseif (count($allattendees) == 0) {
                    $allstatement = ' are no signups ';
                } else {
                    $allstatement = ' is one signup ';
                }

                $html = 'View all event details at this page: https://'.$account;
                $html .= '.capunit.com/eventviewer/'.$EventNumber.'/<br /><br />';

                $debug = true;

                $emails = [];
                if ($event['CAPPOC1Email']) { array_push($emails, $event['CAPPOC1Email']); }
                if ($event['CAPPOC2Email']) { array_push($emails, $event['CAPPOC2Email']); }
                if ($event['AdditionalEmailAddresses']) { array_push($emails, $event['AdditionalEmailAddresses']); }
                if ($event['ExtPOCEmail']) { array_push($emails, $event['ExtPOCEmail']); }
                if($debug) { $html .= "POC emails: ".implode(', ',$emails)."<br />"; }
                if($debug) { $html .= "new attendee IDs: ".$newattendeelist."<br />"; }
                if($debug) { $html .= "all attendee IDs: ".$allattendeelist."<br />"; }
                $memberhtml = 'There '.$newstatement.' for this event.  ';

                $orderby = "SELECT CAPID FROM ";
                $orderby .= "(SELECT CAPID, MemberNameLast AS NameLast, MemberNameFirst AS NameFirst, MemberRank AS Rank, ";
                $orderby .= "IF(MemberRank = \"\", 'CADET', IF(LEFT(MemberRank, 2) = \"C/\", 'CADET', 'SENIOR')) AS Type ";
                $orderby .= "FROM ".DB_TABLES['SignInData']." WHERE CAPID IN (:attendeelist) AND AccountID=:account ";
                $orderby .= "UNION ";
                $orderby .= "SELECT CAPID, NameLast, NameFirst, Rank, IF(Rank = \"\", 'CADET', IF(LEFT(Rank, 2) = \"C/\", 'CADET', 'SENIOR')) AS Type FROM ";
                $orderby .= DB_TABLES['Member']." WHERE CAPID IN (:attendeelist) AND CAPID NOT IN (SELECT CAPID FROM ";
                $orderby .= DB_TABLES['SignInData']." WHERE CAPID IN (:attendeelist) AND AccountID=:account)) AS tj1 WHERE Type=:mytype ORDER BY NameLast, NameFirst;";
                $stmt = $pdo->prepare($orderby);
                $stmt->bindValue(':account', $account);
                $stmt->bindValue(':attendeelist', $newattendeelist);
                $stmt->bindValue(':mytype', "CADET");
                $attendees = DBUtils::ExecutePDOStatement($stmt);
                if (count($attendees) >0) {
                    $memberhtml .= 'Cadets:<br />';
                    foreach ($attendees as $attendee) {
                        $member = Member::Estimate($attendee['CAPID']);
                        $memberhtml .= $member->RankName." [".$member->getBestEmail().", ".$member->getBestPhone().']<br />';
                    }
                    if($debug) { $html .= "new cadet: ".$attendee['CAPID']."<br />"; }
                }

                $orderby = "SELECT CAPID FROM ";
                $orderby .= "(SELECT CAPID, MemberNameLast AS NameLast, MemberNameFirst AS NameFirst, MemberRank AS Rank, ";
                $orderby .= "IF(MemberRank = \"\", 'CADET', IF(LEFT(MemberRank, 2) = \"C/\", 'CADET', 'SENIOR')) AS Type ";
                $orderby .= "FROM ".DB_TABLES['SignInData']." WHERE CAPID IN (:attendeelist) AND AccountID=:account ";
                $orderby .= "UNION ";
                $orderby .= "SELECT CAPID, NameLast, NameFirst, Rank, IF(Rank = \"\", 'CADET', IF(LEFT(Rank, 2) = \"C/\", 'CADET', 'SENIOR')) AS Type FROM ";
                $orderby .= DB_TABLES['Member']." WHERE CAPID IN (:attendeelist) AND CAPID NOT IN (SELECT CAPID FROM ";
                $orderby .= DB_TABLES['SignInData']." WHERE CAPID IN (:attendeelist) AND AccountID=:account)) AS tj1 WHERE Type=:mytype ORDER BY NameLast, NameFirst;";
                $stmt = $pdo->prepare($orderby);
                $stmt->bindValue(':account', $account);
                $stmt->bindValue(':attendeelist', $newattendeelist);
                $stmt->bindValue(':mytype', "SENIOR");
                $attendees = DBUtils::ExecutePDOStatement($stmt);
                if (count($attendees) >0) {
                    $memberhtml .= 'Senior Members:<br />';
                    foreach ($attendees as $attendee) {
                        $member = Member::Estimate($attendee['CAPID']);
                        $memberhtml .= $member->RankName." [".$member->getBestEmail().", ".$member->getBestPhone().']<br />';
                    }
                    if($debug) { $html .= "new senior: ".$attendee['CAPID']."<br />"; }
                }

                $memberhtml .= '<br /><br />There '.$allstatement.' for this event.  ';

                $orderby = "SELECT CAPID FROM ";
                $orderby .= "(SELECT CAPID, MemberNameLast AS NameLast, MemberNameFirst AS NameFirst, MemberRank AS Rank, ";
                $orderby .= "IF(MemberRank = \"\", 'CADET', IF(LEFT(MemberRank, 2) = \"C/\", 'CADET', 'SENIOR')) AS Type ";
                $orderby .= "FROM ".DB_TABLES['SignInData']." WHERE CAPID IN (:attendeelist) AND AccountID=:account ";
                $orderby .= "UNION ";
                $orderby .= "SELECT CAPID, NameLast, NameFirst, Rank, IF(Rank = \"\", 'CADET', IF(LEFT(Rank, 2) = \"C/\", 'CADET', 'SENIOR')) AS Type FROM ";
                $orderby .= DB_TABLES['Member']." WHERE CAPID IN (:attendeelist) AND CAPID NOT IN (SELECT CAPID FROM ";
                $orderby .= DB_TABLES['SignInData']." WHERE CAPID IN (:attendeelist) AND AccountID=:account)) AS tj1 WHERE Type=:mytype ORDER BY NameLast, NameFirst;";
                $stmt = $pdo->prepare($orderby);
                $stmt->bindValue(':account', $account);
                $stmt->bindValue(':attendeelist', $allattendeelist);
                $stmt->bindValue(':mytype', "CADET");
                $attendees = DBUtils::ExecutePDOStatement($stmt);
                if (count($attendees) >0) {
                    $memberhtml .= 'Cadets:<br />';
                    foreach ($attendees as $attendee) {
                        $member = Member::Estimate($attendee['CAPID']);
                        $memberhtml .= $member->RankName." [".$member->getBestEmail().", ".$member->getBestPhone().']<br />';
                    if($debug) { $html .= "all cadet: ".$attendee['CAPID']."<br />"; }
                    }
                }

                $orderby = "SELECT CAPID FROM ";
                $orderby .= "(SELECT CAPID, MemberNameLast AS NameLast, MemberNameFirst AS NameFirst, MemberRank AS Rank, ";
                $orderby .= "IF(MemberRank = \"\", 'CADET', IF(LEFT(MemberRank, 2) = \"C/\", 'CADET', 'SENIOR')) AS Type ";
                $orderby .= "FROM ".DB_TABLES['SignInData']." WHERE CAPID IN (:attendeelist) AND AccountID=:account ";
                $orderby .= "UNION ";
                $orderby .= "SELECT CAPID, NameLast, NameFirst, Rank, IF(Rank = \"\", 'CADET', IF(LEFT(Rank, 2) = \"C/\", 'CADET', 'SENIOR')) AS Type FROM ";
                $orderby .= DB_TABLES['Member']." WHERE CAPID IN (:attendeelist) AND CAPID NOT IN (SELECT CAPID FROM ";
                $orderby .= DB_TABLES['SignInData']." WHERE CAPID IN (:attendeelist) AND AccountID=:account)) AS tj1 WHERE Type=:mytype ORDER BY NameLast, NameFirst;";
                $stmt = $pdo->prepare($orderby);
                $stmt->bindValue(':account', $account);
                $stmt->bindValue(':attendeelist', $allattendeelist);
                $stmt->bindValue(':mytype', "SENIOR");
                $attendees = DBUtils::ExecutePDOStatement($stmt);
                if (count($attendees) >0) {
                    $memberhtml .= 'Senior Members:<br />';
                    foreach ($attendees as $attendee) {
                        $member = Member::Estimate($attendee['CAPID']);
                        $memberhtml .= $member->RankName." [".$member->getBestEmail().", ".$member->getBestPhone().']<br />';
                    if($debug) { $html .= "all senior: ".$attendee['CAPID']."<br />"; }
                    }
                }

                $html .= '<a href=\"mailto:'.implode(', ', $emails).'\">All participant emails</a><br /><br />';
                if($debug) { $html .= "emails: ".implode(', ',$emails)."<br />"; }
                $html .= $memberhtml;

                $stmtreset = "UPDATE Attendance SET SummaryEmailSent=1 WHERE EventID=:event AND AccountID=:account AND SummaryEmailSent=0;";
                $stmt = $pdo->prepare($stmtreset);
                $stmt->bindValue(':account', $account);
                $stmt->bindValue(':event', $EventNumber);
//                $updated = DBUtils::ExecutePDOStatement($stmt);

                UtilCollection::sendFormattedEmail($emails, $html, $subject);

                //update row in SignUpQueue to reflect when the signup summary email was sent
                $stmtreset = "UPDATE SignUpQueue SET SummarySent=:nowtime WHERE EventNumber=:event AND AccountID=:account AND SummarySent=0;";
                $stmt = $pdo->prepare($stmtreset);
                $stmt->bindValue(':nowtime', time());
                $stmt->bindValue(':account', $account);
                $stmt->bindValue(':event', $EventNumber);
                $updaterow = DBUtils::ExecutePDOStatement($stmt);

                $returnmessage = "Attendance summary email sent to ".count($emails)." address";
                if(count($emails) != 1) {
                    $returnmessage .= "es.";
                }
                return $returnmessage;

            } else {
                //need to log error here because there wasn't exactly one event row (event deleted?)
                $returnmessage = "There was an error.  Attendance summary email was not sent.";
                return $returnmessage;
            }
        }
    }

//          inSubject = 'Signup Update, Event ' + eventNumber + ': ' + eventArea[eventRow][eventColumnNumbers['Event Name']] + ' on ' +
  //          Utilities.formatDate(new Date(eventArea[eventRow][eventColumnNumbers['Meet Date']]), timeZone, "EEE' 'MMM' 'dd' 'yyyy");
