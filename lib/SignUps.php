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
                sendEvent($signup['AccountID'], $signup['EventNumber']);
            }

        }

        static function sendEvent($account, $EventNumber, $force=false) {
            //query in turn each event number
            //collect event information, attendance information
            $sqlin = 'SELECT CAPPOC1Email, CAPPOC1ReceiveSignUpUpdates, CAPPOC2Email, CAPPOC2ReceiveSignUpUpdates, ';
            $sqlin = 'EventName, StartDateTime, CAPPOC1Name, CAPPOC2Name, EventNumber, AccountID, AdditionalEmailAddresses, ExtPOCEmail ';
            $sqlin .= ' FROM '.DB_TABLES['EventInformation'].' WHERE AccountID = :account AND EventNumber = :event;'; 
            $stmt = $pdo->prepare($sqlin);
            $stmt->bindValue(':account', $account);
            $stmt->bindValue(':event', $EventNumber);
            $event = DBUtils::ExecutePDOStatement($stmt);
            if(count($event) == 1) {
                $event = $event[0];
                if (!$force && time()>$event->StartDateTime) { return Null; }
                $sqlin = 'SELECT CAPID ';
                $sqlin .= ' FROM '.DB_TABLES['Attendance'].' WHERE AccountID = :account AND EventNumber = :event AND SummaryEmailSent=0 '; 
                $sqlin .= 'ORDER BY NameLast;';
                $stmt = $pdo->prepare($sqlin);
                $stmt->bindValue(':account', $account);
                $stmt->bindValue(':event', $EventNumber);
                $newattendees = DBUtils::ExecutePDOStatement($stmt);
                $newattendeelist = implode(',', $newattendees);
                $sqlin = 'SELECT CAPID ';
                $sqlin .= ' FROM '.DB_TABLES['Attendance'].' WHERE AccountID = :account AND EventNumber = :event '; 
                $sqlin .= 'ORDER BY NameLast;';
                $stmt = $pdo->prepare($sqlin);
                $stmt->bindValue(':account', $account);
                $stmt->bindValue(':event', $EventNumber);
                $allattendees = DBUtils::ExecutePDOStatement($stmt);
                $allattendeelist = implode(',', $allattendees);

                $orderby = "SELECT CAPID FROM ";
                $orderby .= "(SELECT CAPID, MemberNameLast AS NameLast, MemberNameFirst AS NameFirst, MemberRank AS Rank, ";
                $orderby .= "IF(MemberRank = \"\", 'CADET', IF(LEFT(MemberRank, 2) = \"C/\", 'CADET', 'SENIOR')) AS Type ";
                $orderby .= "FROM ".DB_TABLES['SignInData']." WHERE CAPID IN (:attendeelist) ";
                $orderby .= "UNION ";
                $orderby .= "SELECT CAPID, NameLast, NameFirst, Rank, IF(Rank = \"\", 'CADET', IF(LEFT(Rank, 2) = \"C/\", 'CADET', 'SENIOR')) AS Type FROM ";
                $orderby .= DB_TABLES['Data_Member']." WHERE CAPID IN (:attendeelist) AND CAPID NOT IN (SELECT CAPID FROM ";
                $orderby .= DB_TABLES['SignInData']." WHERE CAPID IN (:attendeelist))) AS tj1 WHERE Type=:mytype ORDER BY NameLast, NameFirst;";

                $subject = 'Signup Update ('.count($allattendees).' member';
                if (count($allattendees) != 1) { $subject .= "s"; }
                $subject .= '), Event '.$event->EventNumber.': '.$event->EventName.' on ';
                $subject .= date(DATE_RSS, $event->StartDateTime);

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

                $html = 'View all event details at this page: https://'.$signup['AccountID'];
                $html .= 'capunit.com/eventviewer/'.$signup['EventNumber'].'/<br /><br />';

                $emails = '';
                if ($event->CAPPOC1Email) { $emails .= $event->CAPPOC1Email.', '; }
                if ($event->CAPPOC2Email) { $emails .= $event->CAPPOC2Email.', '; }
                if ($event->AdditionalEmailAddresses) { $emails .= $event->AdditionalEmailAddresses.', '; }
                if ($event->ExtPOCEmail) { $emails .= $event->ExtPOCEmail.', '; }
                $memberhtml = 'There '.$newstatement.' for this event.  ';
                if (count($newattendees) >0) {$memberhtml .= 'They are:<br />';}
                $stmt = $pdo->prepare($orderby);
                $stmt->bindValue(':attendeelist', $newattendees);
                $stmt->bindValue(':mytype', "CADET");
                $attendees = DBUtils::ExecutePDOStatement($stmt);
                if (count($attendees) >0) {$memberhtml .= 'Cadets:<br />';}
                foreach ($attendees as $attendee) {
                    $member = Member::Estimate($attendee);
                    $memberhtml .= $member->rankName." [".$member->getBestEmail().", ".$member->getBestPhone().']<br />';
                }
                $stmt = $pdo->prepare($orderby);
                $stmt->bindValue(':attendeelist', $newattendees);
                $stmt->bindValue(':mytype', "SENIOR");
                $attendees = DBUtils::ExecutePDOStatement($stmt);
                if (count($attendees) >0) {$memberhtml .= 'Senior Members:<br />';}
                foreach ($attendees as $attendee) {
                    $member = Member::Estimate($attendee);
                    $memberhtml .= $member->rankName." [".$member->getBestEmail().", ".$member->getBestPhone().']<br />';
                }

                $memberhtml .= '<br /><br />There '.$allstatement.' for this event.  ';
                if (count($allattendees) >0) {$memberhtml .= 'They are:<br />';}
                $stmt = $pdo->prepare($orderby);
                $stmt->bindValue(':attendeelist', $allattendees);
                $stmt->bindValue(':mytype', "CADET");
                $attendees = DBUtils::ExecutePDOStatement($stmt);
                if (count($attendees) >0) {$memberhtml .= 'Cadets:<br />';}
                foreach ($attendees as $attendee) {
                    $member = Member::Estimate($attendee);
                    $memberhtml .= $member->rankName." [".$member->getBestEmail().", ".$member->getBestPhone().']<br />';
                    array_push($emails, $member->getBestEmail());
                }
                $stmt = $pdo->prepare($orderby);
                $stmt->bindValue(':attendeelist', $allattendees);
                $stmt->bindValue(':mytype', "SENIOR");
                $attendees = DBUtils::ExecutePDOStatement($stmt);
                if (count($attendees) >0) {$memberhtml .= 'Senior Members:<br />';}
                foreach ($attendees as $attendee) {
                    $member = Member::Estimate($attendee);
                    $memberhtml .= $member->rankName." [".$member->getBestEmail().", ".$member->getBestPhone().']<br />';
                    array_push($emails, $member->getBestEmail());
                }

                $html .= '<a href=\"mailto:'.implode(', ', $emails).'\">All participant emails</a><br /><br />';
                $html .= $memberhtml;

                $stmtreset = "UPDATE Attendance SET SummaryEmailSent=1 WHERE EventID=:event AND AccountID=:account AND SummarySent=0;";
                $stmt = $pdo->prepare($stmtreset);
                $stmt->bindValue(':account', $account);
                $stmt->bindValue(':event', $EventNumber);
                $updated = DBUtils::ExecutePDOStatement($stmt);

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
