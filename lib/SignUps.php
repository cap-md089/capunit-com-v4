<?php
    require_once (BASE_DIR."lib/general.php");
    require_once (BASE_DIR."lib/DB_Utils.php");
    require_once (BASE_DIR."lib/Member.php");
    require_once (BASE_DIR."lib/PHPMailer.php");
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
            global $_ACCOUNT;
            $retval = '';
            //if returns, 
            if(count($signups) > 0) {
                foreach ($signups as $signup) {
                    $_ACCOUNT = new Account($signup['AccountID']);
                    self::SendEvent($signup['AccountID'], $signup['EventNumber']);
                    $sqlin = 'UPDATE '.DB_TABLES['SignUpQueue'].' SET SummarySent=:nowtime WHERE AccountID=:account ';
                    $sqlin .= 'AND EventNumber=:evnum AND SummarySent=0;'; 
                    $stmt = $pdo->prepare($sqlin);
                    $stmt->bindValue(':nowtime', time());
                    $stmt->bindValue(':account', $signup['AccountID']);
                    $stmt->bindValue(':evnum', $signup['EventNumber']);
  
                    try {
                        if (!$stmt->execute()) {
                            $retval .= "Couldn't execute update signup, ". var_export($stmt->errorInfo(), true)."\n";
                        } else {
                            $retval .= "Statement executed.\n";
                        }
                    } catch (PDOException $e) {
                        $retval .= "Couldn't execute update signup due to exception, ".$e->getMessage()."\n";
                    }
//                  $response = DBUtils::ExecutePDOStatement($stmt);
                    $retval .= "AccountID: ".$signup['AccountID'].", EventNumber: ".$signup['EventNumber'];
                    $retval .= ", nowtime: ".date(DATE_RSS, time())."\n";
                }
            } else {
                $retval = "Nothing to do.\n";
            }
            return $retval;
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
                if (!$force && time() > $event['StartDateTime']) { return Null; }
                $sendemails = []; $allemails = [];
                if ($event['CAPPOC1Email']) { array_push($sendemails, $event['CAPPOC1Email']); }
                if ($event['CAPPOC2Email']) { array_push($sendemails, $event['CAPPOC2Email']); }
                if ($event['AdditionalEmailAddresses']) { array_push($sendemails, $event['AdditionalEmailAddresses']); }
                if ($event['ExtPOCEmail']) { array_push($sendemails, $event['ExtPOCEmail']); }

                $sqlin = 'CALL ListIDsAlpha(:account, :event);';
                $stmt = $pdo->prepare($sqlin);
                $stmt->bindValue(':account', $account);
                $stmt->bindValue(':event', $EventNumber);
                $attendees = DBUtils::ExecutePDOStatement($stmt);

                $newSeniorHtml = ''; $newCadetHtml = ''; $allSeniorHtml = ''; $allCadetHtml = '';
                $newattendeecount = 0; $newseniorcount = 0; $newcadetcount = 0;
                $allattendeecount = 0; $allseniorcount = 0; $allcadetcount = 0;
                if(count($attendees)) {
                    foreach ($attendees as $attendee) {
                        $member = Member::Estimate($attendee['CAPID']);
                        if($attendee['SummaryEmailSent']==0) {
                            if($attendee['Type']=="SENIOR") {
                                $newSeniorHtml .= $member->RankName." [".$member->getBestEmail().", ".$member->getBestPhone().']<br />\n';
                                $newseniorcount += 1;
                            } else {
                                $newCadetHtml .= $member->RankName." [".$member->getBestEmail().", ".$member->getBestPhone().']<br />\n';
                                $newcadetcount += 1;
                            }
                            $newattendeecount += 1;
                        }
                        if($attendee['Type']=="SENIOR") {
                            $allSeniorHtml .= $member->RankName." [".$member->getBestEmail().", ".$member->getBestPhone().']<br />\n';
                            $allseniorcount += 1;
                        } else {
                            $allCadetHtml .= $member->RankName." [".$member->getBestEmail().", ".$member->getBestPhone().']<br />\n';
                            $allcadetcount += 1;
                        }
                        array_push($allemails, $member->getAllEmailAddresses());
                        $allattendeecount += 1;
                    }
                }

                if($newattendeecount > 1) {
                    $newstatement = ' are '.$newattendeecount.' new signups ';
                } elseif ($newattendeecount == 0) {
                    $newstatement = ' are no new signups ';
                } else {
                    $newstatement = ' is one new signup ';
                }
                if($allattendeecount > 1) {
                    $allstatement = ' are '.$allattendeecount.' signups ';
                } elseif ($allattendeecount == 0) {
                    $allstatement = ' are no signups ';
                } else {
                    $allstatement = ' is one signup ';
                }

                $subject = 'Signup Update ('.$allattendeecount.' member';
                if ($allattendeecount != 1) { $subject .= "s"; }
                $subject .= '), Event '.$event['EventNumber'];
                $subject .= ': '.$event['EventName'].' on ';
                $subject .= date(DATE_RSS, $event['StartDateTime']);

                $html = 'View all event details at this page: https://'.$account;
                $html .= '.capunit.com/eventviewer/'.$EventNumber.'/<br /><br />';

                $memberhtml = 'There '.$newstatement.' for this event.  <br />';
                if($newseniorcount > 0) {
                    $memberhtml .= "Seniors:<br />".$newSeniorHtml;
                }
                if($newcadetcount > 0) {
                    $memberhtml .= "Cadets:<br />".$newCadetHtml;
                }
                $memberhtml .= '<br /><br />There '.$allstatement.' for this event.  <br />';
                if($allseniorcount > 0) {
                    $memberhtml .= "Seniors:<br />".$allSeniorHtml;
                }
                if($allcadetcount > 0) {
                    $memberhtml .= "Cadets:<br />".$allCadetHtml;
                }

                $plain = $html.$memberhtml;
//                $html .= '<a href=\"mailto:'.implode(', ', $emails).'?subject=CAP Event '.$EventNumber.': ';
  //              $html .= $event['EventName'].'\">All participant emails</a><br /><br />';
                $html .= $memberhtml;

                $stmtreset = "UPDATE Attendance SET SummaryEmailSent=1 WHERE EventID=:event AND AccountID=:account AND SummaryEmailSent=0;";
                $stmt = $pdo->prepare($stmtreset);
                $stmt->bindValue(':account', $account);
                $stmt->bindValue(':event', $EventNumber);
                $updated = DBUtils::ExecutePDOStatement($stmt);

                UtilCollection::sendFormattedEmail($sendemails, $html, $subject);

                $returnmessage = "Attendance summary email sent to ".count($sendemails)." address";
                if(count($sendemails) != 1) {
                    $returnmessage .= "es.";
                } else {
                    $returnmessage .= ".";
                }
                return $returnmessage;

            } else {
                //need to log error here because there wasn't exactly one event row (event deleted?)
                $returnmessage = "There was an error.  Attendance summary email was not sent.";
                return $returnmessage;
            }
        }
    }

