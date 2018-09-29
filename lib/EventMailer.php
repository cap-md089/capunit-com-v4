<?php
    /**
        * These functions are used to send the appropriate mail to
        * select addresses at the appropriate time
    */
    function eventMailer(Member $member, Event $form, Event $database=null, Event $sourceEvent=null) {
        //compare form and database for changed values
        //build changed values string and full event string

        $homeUnit = $member->Squadron;  //ie  "MER-MD-089"
        $homeAccountID = UtilCollection::GetAccountIDFromUnit($homeUnit);  //lib/general.php
        $_ACCOUNT = new Account($homeAccountID);

        if (!isset($database)) { // Event was created, cannot compare against database
            if (!isset($sourceEvent)) { // Event was created from the form, not linked from another event
                $html = $member->RankName." has created an event and ";
                $html .= "you have been identified as a Point of Contact!<br />";
                $html .= "<br />Here are some basic event details: <br />";
                $html .= "<br />Event name: $form->EventName";
                $html .= "<br />Event ID Number: <a href=\"".(new Link("eventviewer", '', [$form->EventNumber]))->getURL(false)."\">$_ACCOUNT-$form->EventNumber</a><br />";
            } else {  // Created event is linked to another event
                $pdo = DB_Utils::CreateConnection();
                $stmt = $pdo->prepare('SELECT UnitID as ORGID FROM '.DB_TABLES['Accounts'].' WHERE AccountID=:aid AND MainOrg=1;');
                $stmt->bindValue(':aid', $sourceEvent->SourceAccountID);
                $data = DB_Utils::ExecutePDOStatement($stmt);
                if (count($data) != 1) {
                    //there was an error; need to add error logging here.  return
                    return [
                        'error' => 311
                    ];
                } else {
                    $data = $data[0];
                    $stmt = $pdo->prepare('SELECT Region, Wing, Unit, Name FROM '.DB_TABLES['Organization'].' WHERE ORGID = :oid;');
                    $stmt->bindValue(':oid', $data['ORGID']);
                    $data2 = DB_Utils::ExecutePDOStatement($stmt);
                    if (count($data2) != 1) {
                        //there was an error; need to add error logging here.  return
                        return [
                            'error' => 311
                        ];
                    } else {
                        $data2 = $data2[0];
                        $linkedUnitName = $data2['Region']."-".$data2['Wing']."-".$data2['Unit'];
                        $html = $member->RankName." has linked to Event ".$_ACCOUNT.'-'.$sourceEvent->EventNumber." at ";
                        $html .= $linkedUnitName."";
                        $html .= "you have been identified as a Point of Contact!<br />";
                        $html .= "<br />Here are some basic details of the local event: <br />";
                        $html .= "<br />Event name: $form->EventName";
                        $html .= "<br />Event ID Number: <a href=\"".(new Link("eventviewer", '', [$form->EventNumber]))->getURL(false)."\">$form->GetAccount()->id-$form->EventNumber</a><br />";
                    }
                }
            }
            $html .= "<br />Meet at ".date('h:i A \o\n n/j/Y', $form->MeetDateTime).' at '.$form->MeetLocation.'<br />';
            $html .= "Start at ".date('h:i A \o\n n/j/Y', $form->StartDateTime).' at '.$form->EventLocation.'<br />';
			$html .= "End at ".date('h:i A \o\n n/j/Y', $form->EndDateTime).'<br />';
            $html .= "Pickup at ".date('h:i A \o\n n/j/Y', $form->PickupDateTime).' at '.$form->PickupLocation.'<br /><br />';

            $html .= "Transportation provided: ".($form->TransportationProvided == 1 ? 'YES' : 'NO').'<br />';
			$html .= "Uniform: ".$form->Uniform.'<br />';
			$html .= "Comments: ".$form->Comments.'<br />';
			$html .= "Activity: ".$form->Activity.'<br />';
			$html .= "Required forms: ".$form->RequiredForms.'<br />';
			$html .= "Required equipment: ".$form->RequiredEquipment.'<br />';
			$html .= "Registration Deadline: ".date('n/j/Y', $form->RegistrationDeadline).'<br />';
			$html .= "Meals: ".$form->Meals.'<br />';
			$html .= "Desired number of Participants: ".$form->DesiredNumParticipants.'<br />';
            $html .= "Event status: ".$form->Status;
            $contact = [];
            if ($form->CAPPOC1ID != 0) $contact[$form->CAPPOC1Name] = $form->CAPPOC1Email;
            if ($form->CAPPOC2ID != 0) $contact[$form->CAPPOC2Name] = $form->CAPPOC2Email;
            if ($form->ExtPOCName != '') $contact[$form->ExtPOCName] = $form->ExtPOCEmail;
            if (strpos(php_uname('r'), 'amzn1') !== false) {
                return UtilCollection::sendFormattedEmail(
                    $contact,
                    $html,
                    "Event $_ACCOUNT-$form->EventNumber created: $form->EventName (".date('h:i A n/j/Y', $form->StartDateTime).")"
                );
            }
        } else { // Event was changed, data is available for comparison
            if($form->Status=='Complete' && $form->EndDateTime < time()) {
                if($form->Debrief != '') {
                    //construct email providing the debrief text to POCs and all
                    //designated administrative personnel (DCC, C/OPSO, C/CC, C/DC, etc)
                } else {
                    //construct email describing the event and provide a link to add debrief
                    //need new 'add debrief' page which appends text of form to debrief field
                    //and adds login information to debrief text
                }
            } else {
                $fields = [];
                $things = [
                    'EventName','MeetDateTime','MeetLocation','StartDateTime','EventLocation','EndDateTime','PickupDateTime',
                    'PickupLocation','TransportationProvided','TransportationDescription','Uniform','DesiredNumParticipants',
                    'RegistrationDeadline','RegistrationInformation','ParticipationFeeDue','ParticipationFee','Meals',
                    'LodgingArrangements','Activity',
                    'HighAdventureDescription','RequiredEquipment','EventWebsite','RequiredForms','Comments','AcceptSignUps',
                    'SignUpDenyMessage','PublishToWingCalendar','ShowUpcoming','GroupEventNumber',
                    'Complete','Administration','Status','Debrief','CAPPOC1ID','CAPPOC1Name','CAPPOC1Phone','CAPPOC1Email',
                    'CAPPOC1ReceiveEventUpdates','CAPPOC1ReceiveSignUpUpdates','CAPPOC2ReceiveEventUpdates','CAPPOC2ReceiveSignUpUpdates',
                    'CAPPOC2ID','CAPPOC2Name','CAPPOC2Phone','CAPPOC2Email','ExtPOCName','ExtPOCPhone','ExtPOCEmail','Author',
                    'AdditionalEmailAddresses','ExtPOCReceiveEventUpdates','PartTime','TeamID'
                ];

                foreach ($things as $thing) {
                    if ($form->$thing != $database->$thing) {
                        if ($thing == 'MeetDateTime' || $thing == 'StartDateTime' || $thing == 'EndDateTime' || $thing == 'PickupDateTime' || $thing == 'RegistrationDeadline' || $thing == 'ParticipationFeeDue') {
                            $fields[$thing] = [date('h:i A n/j/Y', $form->$thing),date('h:i A n/j/Y', $database->$thing)];
                        } else if ($thing == 'TransportationProvided' || $thing == 'AcceptSignUps' || $thing == 'Complete' || $thing == 'CAPPOC1ReceiveEventUpdates' || $thing == 'CAPPOC1ReceiveSignUpUpdates' || $thing == 'CAPPOC2ReceiveEventUpdates' || $thing == 'CAPPOC2ReceiveSignUpUpdates' || $thing == 'ExtPOCReceiveEventUpdates' || $thing == 'PartTime') {
                            $fields[$thing] = [$form->$thing ? 'YES' : 'NO', $database->$thing ? 'YES' : 'NO'];  
                        } else {
                            $fields[$thing] = [$form->$thing, $database->$thing];
                        }
                    }
                }

                $html = $member->RankName." has updated event $_ACCOUNT-$form->EventNumber: $form->EventName";
                $html .= " On ".date('h:i A n/j/Y', $form->StartDateTime)."<br /><ul>";

                foreach ($fields as $field => $vals) {
                    if ($vals[0] == '') {
                        $html .= "<li>$field has been removed</li>";
                    } else if ($vals[1] == '') {
                        $html .= "<li>$field has been added ({$vals[0]})</li>";
                    } else {
                        $html .= "<li>$field has been changed from {$vals[1]} to {$vals[0]}</li>";
                    }
                }

                $html .= "</ul><br />";

                $html .= "View the event at <a href=\"".(new Link("eventviewer", "", [$form->EventNumber]))->getURL(false)."\">this link</a>";

                $contact = [];
                if ($form->CAPPOC1ID != 0 && $form->CAPPOC1ReceiveEventUpdates) $contact[$form->CAPPOC1Name] = $form->CAPPOC1Email;
                if ($form->CAPPOC2ID != 0 && $form->CAPPOC2ReceiveEventUpdates) $contact[$form->CAPPOC2Name] = $form->CAPPOC2Email;
                if ($form->ExtPOCName != '' && $form->ExtPOCReceiveEventUpdates) $contact[$form->ExtPOCName] = $form->ExtPOCEmail;

                if ($database->CAPPOC1ID != 0 && $database->CAPPOC1ReceiveEventUpdates) $contact[$database->CAPPOC1Name] = $database->CAPPOC1Email;
                if ($database->CAPPOC2ID != 0 && $database->CAPPOC2ReceiveEventUpdates) $contact[$database->CAPPOC2Name] = $database->CAPPOC2Email;
                if ($database->ExtPOCName != '' && $database->ExtPOCReceiveEventUpdates) $contact[$database->ExtPOCName] = $database->ExtPOCEmail;

                if ($form->AdditionalEmailAddresses != '') $contact[$form->AdditionalEmailAddresses] = $form->AdditionalEmailAddresses;

                $pdo = DB_Utils::CreateConnection();
                $stmt = $pdo->prepare('SELECT * FROM '.DB_TABLES['EventInformation'].' WHERE SourceAccountID=:sai AND SourceEventNumber=:sen;');
                $stmt->bindValue(':sai', $form->SourceAccountID);
                $stmt->bindValue(':sen', $form->SourceEventNumber);
                $data = DB_Utils::ExecutePDOStatement($stmt);
                if (count($data) > 0) {
                    foreach ($data as $datum) {
                        $contactLink = [];
                        if ($datum['CAPPOC1ID'] != 0 && $datum['CAPPOC1ReceiveEventUpdates']) $contactLink[$datum['CAPPOC1Name']] = $datum['CAPPOC1Email'];
                        if ($datum['CAPPOC2ID'] != 0 && $datum['CAPPOC2ReceiveEventUpdates']) $contactLink[$datum['CAPPOC2Name']] = $datum['CAPPOC2Email'];

                        if ($datum['AdditionalEmailAddresses'] != '') $contactLink[$datum['AdditionalEmailAddresses']] = $datum['AdditionalEmailAddresses'];

                        if (strpos(php_uname('r'), 'amzn1') !== false) {
                            UtilCollection::sendFormattedEmail(
                                $contactLink,
                                $html,
                                "Event $_ACCOUNT-$form->EventNumber updated: $form->EventName (".date('h:i A n/j/Y', $form->StartDateTime).")"
                            );
                        }
                    }
                }
                if (strpos(php_uname('r'), 'amzn1') !== false) {
                    return UtilCollection::sendFormattedEmail(
                        $contact,
                        $html,
                        "Event $_ACCOUNT-$form->EventNumber updated: $form->EventName (".date('h:i A n/j/Y', $form->StartDateTime).")"
                    );
                }
            }
        }
    }

    function eventMailerNew(Member $member, Event $form) {
        //compare form and database for changed values
        //build changed values string and full event string
/*
        $homeUnit = $member->Squadron;
        $homeAccountID = UtilCollection::GetAccountIDFromUnit($homeUnit);  //lib/general.php
        $_ACCOUNTm = new Account($homeAccountID);
        $_ACCOUNTl = new Account($form->AccountID);

        $html = $member->RankName." has created an event and ";
        $html .= "you have been identified as a Point of Contact!<br />";
        $html .= "<br />Here are some basic event details: <br />";
        $html .= "<br />Event name: $form->EventName";
        $html .= "<br />Event ID Number: <a href=\"".(new Link("eventviewer", '', [$form->EventNumber]))->getURL(false)."\">$_ACCOUNTl-$form->EventNumber</a><br />";

        $html .= "<br />Meet at ".date('h:i A \o\n n/j/Y', $form->MeetDateTime).' at '.$form->MeetLocation.'<br />';
        $html .= "Start at ".date('h:i A \o\n n/j/Y', $form->StartDateTime).' at '.$form->EventLocation.'<br />';
			$html .= "End at ".date('h:i A \o\n n/j/Y', $form->EndDateTime).'<br />';
        $html .= "Pickup at ".date('h:i A \o\n n/j/Y', $form->PickupDateTime).' at '.$form->PickupLocation.'<br /><br />';

        $html .= "Transportation provided: ".($form->TransportationProvided == 1 ? 'YES' : 'NO').'<br />';
        $html .= "Uniform: ".$form->Uniform.'<br />';
        $html .= "Comments: ".$form->Comments.'<br />';
        $html .= "Activity: ".$form->Activity.'<br />';
        $html .= "Required forms: ".$form->RequiredForms.'<br />';
        $html .= "Required equipment: ".$form->RequiredEquipment.'<br />';
        $html .= "Registration Deadline: ".date('n/j/Y', $form->RegistrationDeadline).'<br />';
        $html .= "Meals: ".$form->Meals.'<br />';
        $html .= "Desired number of Participants: ".$form->DesiredNumParticipants.'<br />';
        $html .= "Event status: ".$form->Status;
        $contact = [];
        if ($form->CAPPOC1ID != 0 && $form->CAPPOC1ReceiveEventUpdates) $contact[$form->CAPPOC1Name] = $form->CAPPOC1Email;
        if ($form->CAPPOC2ID != 0 && $form->CAPPOC2ReceiveEventUpdates) $contact[$form->CAPPOC2Name] = $form->CAPPOC2Email;
        if ($form->ExtPOCName != '' && $form->ExtPOCReceiveEventUpdates) $contact[$form->ExtPOCName] = $form->ExtPOCEmail;
        if ($form->AdditionalEmailAddresses != '') $contact[$form->AdditionalEmailAddresses] = $form->AdditionalEmailAddresses;

        if (strpos(php_uname('r'), 'amzn1') !== false) {
            return UtilCollection::sendFormattedEmail(
                $contact,
                $html,
                "Event $_ACCOUNTl-$form->EventNumber created: $form->EventName (".date('h:i A n/j/Y', $form->StartDateTime).")"
            );
        }
*/    }

    function eventMailerEdit(Member $member, Event $form, Event $database) {
        //compare form and database for changed values
        //build changed values string and full event string

        $homeUnit = $member->Squadron;
        $homeAccountID = UtilCollection::GetAccountIDFromUnit($homeUnit);  //lib/general.php
        $_ACCOUNTm = new Account($homeAccountID);
        $_ACCOUNTl = new Account($form->GetAccount()->id);

        if($form->Status=='Complete' && $form->EndDateTime < time()) {
            if($form->Debrief != '') {
                //construct email providing the debrief text to POCs and all
                //designated administrative personnel (DCC, C/OPSO, C/CC, C/DC, etc)
            } else {
                //construct email describing the event and provide a link to add debrief
                //need new 'add debrief' page which appends text of form to debrief field
                //and adds login information to debrief text
            }
        } else {
            $fields = [];
            $things = [
                'EventName','MeetDateTime','MeetLocation','StartDateTime','EventLocation','EndDateTime','PickupDateTime',
                'PickupLocation','TransportationProvided','TransportationDescription','Uniform','DesiredNumParticipants',
                'RegistrationDeadline','RegistrationInformation','ParticipationFeeDue','ParticipationFee','Meals',
                'LodgingArrangements','Activity',
                'HighAdventureDescription','RequiredEquipment','EventWebsite','RequiredForms','Comments','AcceptSignUps',
                'SignUpDenyMessage','PublishToWingCalendar','ShowUpcoming','GroupEventNumber',
                'Complete','Administration','Status','Debrief','CAPPOC1ID','CAPPOC1Name','CAPPOC1Phone','CAPPOC1Email',
                'CAPPOC1ReceiveEventUpdates','CAPPOC1ReceiveSignUpUpdates','CAPPOC2ReceiveEventUpdates','CAPPOC2ReceiveSignUpUpdates',
                'CAPPOC2ID','CAPPOC2Name','CAPPOC2Phone','CAPPOC2Email','ExtPOCName','ExtPOCPhone','ExtPOCEmail','Author',
                'AdditionalEmailAddresses','ExtPOCReceiveEventUpdates','PartTime','TeamID'
            ];

            foreach ($things as $thing) {
                if ($form->$thing != $database->$thing) {
                    if ($thing == 'MeetDateTime' || $thing == 'StartDateTime' || $thing == 'EndDateTime' || $thing == 'PickupDateTime' || $thing == 'RegistrationDeadline' || $thing == 'ParticipationFeeDue') {
                        $fields[$thing] = [date('h:i A n/j/Y', $form->$thing),date('h:i A n/j/Y', $database->$thing)];
                    } else if ($thing == 'TransportationProvided' || $thing == 'AcceptSignUps' || $thing == 'Complete' || $thing == 'CAPPOC1ReceiveEventUpdates' || $thing == 'CAPPOC1ReceiveSignUpUpdates' || $thing == 'CAPPOC2ReceiveEventUpdates' || $thing == 'CAPPOC2ReceiveSignUpUpdates' || $thing == 'ExtPOCReceiveEventUpdates' || $thing == 'PartTime') {
                        $fields[$thing] = [$form->$thing ? 'YES' : 'NO', $database->$thing ? 'YES' : 'NO'];  
                    } else {
                        $fields[$thing] = [$form->$thing, $database->$thing];
                    }
                }
            }

            $html = $member->RankName." has updated event $_ACCOUNTl-$form->EventNumber: $form->EventName";
            $html .= " On ".date('h:i A n/j/Y', $form->StartDateTime)."<br /><ul>";

            foreach ($fields as $field => $vals) {
                if ($vals[0] == '') {
                    $html .= "<li>Field [$field] has been removed</li>";
                } else if ($vals[1] == '') {
                    $html .= "<li>Field [$field] has been added ({$vals[0]})</li>";
                } else {
                    $html .= "<li>Field [$field] has been changed from ({$vals[1]}) to ({$vals[0]})</li>";
                }
            }

            $html .= "</ul><br />";

            $html .= "View the event at <a href=\"".(new Link("eventviewer", "", [$form->EventNumber]))->getURL(false)."\">this link</a>";

            $contact = [];
            if ($form->CAPPOC1ID != 0 && $form->CAPPOC1ReceiveEventUpdates) $contact[$form->CAPPOC1Name] = $form->CAPPOC1Email;
            if ($form->CAPPOC2ID != 0 && $form->CAPPOC2ReceiveEventUpdates) $contact[$form->CAPPOC2Name] = $form->CAPPOC2Email;
            if ($form->ExtPOCName != '' && $form->ExtPOCReceiveEventUpdates) $contact[$form->ExtPOCName] = $form->ExtPOCEmail;

            if ($database->CAPPOC1ID != 0 && $database->CAPPOC1ReceiveEventUpdates) $contact[$database->CAPPOC1Name] = $database->CAPPOC1Email;
            if ($database->CAPPOC2ID != 0 && $database->CAPPOC2ReceiveEventUpdates) $contact[$database->CAPPOC2Name] = $database->CAPPOC2Email;
            if ($database->ExtPOCName != '' && $database->ExtPOCReceiveEventUpdates) $contact[$database->ExtPOCName] = $database->ExtPOCEmail;

            if ($form->AdditionalEmailAddresses != '') $contact[$form->AdditionalEmailAddresses] = $form->AdditionalEmailAddresses;

            //include addresses of POCs for events linked to this one
            $pdo = DB_Utils::CreateConnection();
            $stmt = $pdo->prepare('SELECT * FROM '.DB_TABLES['EventInformation'].' WHERE SourceAccountID=:sai AND SourceEventNumber=:sen;');
            $stmt->bindValue(':sai', $_ACCOUNTl->id);
            $stmt->bindValue(':sen', $form->EventNumber);
            $data = DB_Utils::ExecutePDOStatement($stmt);
            if (count($data) > 0) {
                foreach ($data as $datum) {
                    $contactLink = [];
                    if ($datum['CAPPOC1ID'] != 0 && $datum['CAPPOC1ReceiveEventUpdates']) $contactLink[$datum['CAPPOC1Name']] = $datum['CAPPOC1Email'];
                    if ($datum['CAPPOC2ID'] != 0 && $datum['CAPPOC2ReceiveEventUpdates']) $contactLink[$datum['CAPPOC2Name']] = $datum['CAPPOC2Email'];
                    if ($datum['AdditionalEmailAddresses'] != '') $contactLink[$datum['AdditionalEmailAddresses']] = $datum['AdditionalEmailAddresses'];

                    if (strpos(php_uname('r'), 'amzn1') !== false) {
                        UtilCollection::sendFormattedEmail(
                            $contactLink,
                            $html,
                            "Source Event $_ACCOUNTl-$form->EventNumber updated: $form->EventName (".date('h:i A n/j/Y', $form->StartDateTime).")"
                        );
                    }
                }
            }
            if (strpos(php_uname('r'), 'amzn1') !== false) {
                return UtilCollection::sendFormattedEmail(
                    $contact,
                    $html,
                    "Event $_ACCOUNTl-$form->EventNumber updated: $form->EventName (".date('h:i A n/j/Y', $form->StartDateTime).")"
                );
            }
        }
    }

    function eventMailerLinkSet(Member $member, Event $form, Event $sourceEvent) {
        //compare form and database for changed values
        //build changed values string and full event string

        $homeUnit = $member->Squadron;
        $homeAccountID = UtilCollection::GetAccountIDFromUnit($homeUnit);  //lib/general.php
        $_ACCOUNTm = new Account($homeAccountID);
        $_ACCOUNTl = new Account($form->GetAccount()->id);
        $_ACCOUNTo = new Account($sourceEvent->GetAccount()->id);

        $html = $member->RankName." has linked to Event {$_ACCOUNTo}-".$sourceEvent->EventNumber;
        $html .= " and you have been identified as a Point of Contact!<br />";
        $html .= "<br />Here are some basic details of the local event: <br />";
        $html .= "<br />Event name: $form->EventName";
        $html .= "<br />Event ID Number: <a href=\"".(new Link("eventviewer", '', [$form->EventNumber]))->getURL(false)."\">".$form->GetAccount()->id."-{$form->EventNumber}</a><br />";
        $html .= "<br />Meet at ".date('h:i A \o\n n/j/Y', $form->MeetDateTime).' at '.$form->MeetLocation.'<br />';
        $html .= "Start at ".date('h:i A \o\n n/j/Y', $form->StartDateTime).' at '.$form->EventLocation.'<br />';
        $html .= "End at ".date('h:i A \o\n n/j/Y', $form->EndDateTime).'<br />';
        $html .= "Pickup at ".date('h:i A \o\n n/j/Y', $form->PickupDateTime).' at '.$form->PickupLocation.'<br /><br />';

        $html .= "Transportation provided: ".($form->TransportationProvided == 1 ? 'YES' : 'NO').'<br />';
        $html .= "Uniform: ".$form->Uniform.'<br />';
        $html .= "Comments: ".$form->Comments.'<br />';
        $html .= "Activity: ".$form->Activity.'<br />';
        $html .= "Required forms: ".$form->RequiredForms.'<br />';
        $html .= "Required equipment: ".$form->RequiredEquipment.'<br />';
        $html .= "Registration Deadline: ".date('n/j/Y', $form->RegistrationDeadline).'<br />';
        $html .= "Meals: ".$form->Meals.'<br />';
        $html .= "Desired number of Participants: ".$form->DesiredNumParticipants.'<br />';
        $html .= "Event status: ".$form->Status;
        $contact = [];
        if ($form->CAPPOC1ID != 0) $contact[$form->CAPPOC1Name] = $form->CAPPOC1Email;
        if ($form->CAPPOC2ID != 0) $contact[$form->CAPPOC2Name] = $form->CAPPOC2Email;
        if ($form->ExtPOCName != '') $contact[$form->ExtPOCName] = $form->ExtPOCEmail;
        if ($form->AdditionalEmailAddresses != '') $contact[$form->AdditionalEmailAddresses] = $form->AdditionalEmailAddresses;
        if (strpos(php_uname('r'), 'amzn1') !== false) {
            return UtilCollection::sendFormattedEmail(
                $contact,
                $html,
                "Event $_ACCOUNTl-$form->EventNumber linked: $form->EventName (".date('h:i A n/j/Y', $form->StartDateTime).")"
            );
        }
    }

    function eventMailerLinkUnset(Member $member, Event $form) {

        $homeUnit = $member->Squadron;
        $homeAccountID = UtilCollection::GetAccountIDFromUnit($homeUnit);  //lib/general.php
        $_ACCOUNTm = new Account($homeAccountID);
        $_ACCOUNTl = new Account($form->GetAccount()->id);
        $_ACCOUNTo = new Account($form->SourceAccountID);

        $html = $member->RankName." has unlinked an event for which ";
        $html .= "you are identified as a Point of Contact.<br />";
        $html .= "<br />Here are some basic event details: <br />";
        $html .= "<br />Event name: $form->EventName";
        $html .= "<br />Host Event ID Number: <a href=\"".(new Link("eventviewer", '', [$form->EventNumber]))->getURL(false)."\">$_ACCOUNTo-$form->SourceEventNumber</a>";
        $html .= "<br />Local Event ID Number: <a href=\"".(new Link("eventviewer", '', [$form->EventNumber]))->getURL(false)."\">$_ACCOUNTl-$form->EventNumber</a><br />";

        $html .= "<br />Local event details: <br />";
        $html .= "<br />Meet at ".date('h:i A \o\n n/j/Y', $form->MeetDateTime).' at '.$form->MeetLocation.'<br />';
        $html .= "Start at ".date('h:i A \o\n n/j/Y', $form->StartDateTime).' at '.$form->EventLocation.'<br />';
        $html .= "End at ".date('h:i A \o\n n/j/Y', $form->EndDateTime).'<br />';
        $html .= "Pickup at ".date('h:i A \o\n n/j/Y', $form->PickupDateTime).' at '.$form->PickupLocation.'<br /><br />';

        $html .= "Transportation provided: ".($form->TransportationProvided == 1 ? 'YES' : 'NO').'<br />';
        $html .= "Uniform: ".$form->Uniform.'<br />';
        $html .= "Comments: ".$form->Comments.'<br />';
        $html .= "Activity: ".$form->Activity.'<br />';
        $html .= "Required forms: ".$form->RequiredForms.'<br />';
        $html .= "Required equipment: ".$form->RequiredEquipment.'<br />';
        $html .= "Registration Deadline: ".date('n/j/Y', $form->RegistrationDeadline).'<br />';
        $html .= "Meals: ".$form->Meals.'<br />';
        $html .= "Desired number of Participants: ".$form->DesiredNumParticipants.'<br />';
        $html .= "Event status: ".$form->Status;
        $contact = [];
        if ($form->CAPPOC1ID != 0) $contact[$form->CAPPOC1Name] = $form->CAPPOC1Email;
        if ($form->CAPPOC2ID != 0) $contact[$form->CAPPOC2Name] = $form->CAPPOC2Email;
        if ($form->ExtPOCName != '') $contact[$form->ExtPOCName] = $form->ExtPOCEmail;
        if ($form->AdditionalEmailAddresses != '') $contact[$form->AdditionalEmailAddresses] = $form->AdditionalEmailAddresses;
        if (strpos(php_uname('r'), 'amzn1') !== false) {
            return UtilCollection::sendFormattedEmail(
                $contact,
                $html,
                "Event $_ACCOUNTl-$form->EventNumber unlinked: $form->EventName (".date('h:i A n/j/Y', $form->StartDateTime).")"
            );
        }
    }

    function eventMailerDelete(Member $member, Event $form) {

        $homeUnit = $member->Squadron;
        $homeAccountID = UtilCollection::GetAccountIDFromUnit($homeUnit);  //lib/general.php
        $_ACCOUNT = new Account($homeAccountID);

        $html = $member->RankName." has deleted an event for which ";
        $html .= "you were identified as a Point of Contact.<br />";
        $html .= "<br />Here are some basic event details: <br />";
        $html .= "<br />Event name: $form->EventName";
        $html .= "<br />Event ID Number: $_ACCOUNT-$form->EventNumber";
        $html .= "<br />Meet at ".date('h:i A \o\n n/j/Y', $form->MeetDateTime).' at '.$form->MeetLocation.'<br />';
        $html .= "Start at ".date('h:i A \o\n n/j/Y', $form->StartDateTime).' at '.$form->EventLocation.'<br />';
        $html .= "End at ".date('h:i A \o\n n/j/Y', $form->EndDateTime).'<br />';
        $html .= "Pickup at ".date('h:i A \o\n n/j/Y', $form->PickupDateTime).' at '.$form->PickupLocation.'<br /><br />';

        $html .= "Transportation provided: ".($form->TransportationProvided == 1 ? 'YES' : 'NO').'<br />';
        $html .= "Uniform: ".$form->Uniform.'<br />';
        $html .= "Comments: ".$form->Comments.'<br />';
        $html .= "Activity: ".$form->Activity.'<br />';
        $html .= "Required forms: ".$form->RequiredForms.'<br />';
        $html .= "Required equipment: ".$form->RequiredEquipment.'<br />';
        $html .= "Registration Deadline: ".date('n/j/Y', $form->RegistrationDeadline).'<br />';
        $html .= "Meals: ".$form->Meals.'<br />';
        $html .= "Desired number of Participants: ".$form->DesiredNumParticipants.'<br />';
        $html .= "Event status: ".$form->Status;
        $contact = [];
        if ($form->CAPPOC1ID != 0) $contact[$form->CAPPOC1Name] = $form->CAPPOC1Email;
        if ($form->CAPPOC2ID != 0) $contact[$form->CAPPOC2Name] = $form->CAPPOC2Email;
        if ($form->ExtPOCName != '') $contact[$form->ExtPOCName] = $form->ExtPOCEmail;
        if ($form->AdditionalEmailAddresses != '') $contact[$form->AdditionalEmailAddresses] = $form->AdditionalEmailAddresses;
        if (strpos(php_uname('r'), 'amzn1') !== false) {
            return UtilCollection::sendFormattedEmail(
                $contact,
                $html,
                "Event $_ACCOUNT-$form->EventNumber deleted: $form->EventName (".date('h:i A n/j/Y', $form->StartDateTime).")"
            );
        }
    }

    function eventMailerClone(Member $member, Event $oldEvent, Event $form) {
        //compare form and database for changed values
        //build changed values string and full event string

        $homeUnit = $member->Squadron;
        $homeAccountID = UtilCollection::GetAccountIDFromUnit($homeUnit);  //lib/general.php
        $_ACCOUNT = new Account($homeAccountID);

        $html = $member->RankName." has cloned event ";
        $html .= "<a href=\"".(new Link("eventviewer", '', [$oldEvent->EventNumber]))->getURL(false)."\">$_ACCOUNT-$oldEvent->EventNumber</a> ";
        $html .= "and you are identified as a Point of Contact!<br />";
        $html .= "<br />Here are some basic details of the new event (which may change): <br />";
        $html .= "<br />Event name: $form->EventName";
        $html .= "<br />Event ID Number: <a href=\"".(new Link("eventviewer", '', [$form->EventNumber]))->getURL(false)."\">$_ACCOUNT-$form->EventNumber</a><br />";
        $html .= "<br />Meet at ".date('h:i A \o\n n/j/Y', $form->MeetDateTime).' at '.$form->MeetLocation.'<br />';
        $html .= "Start at ".date('h:i A \o\n n/j/Y', $form->StartDateTime).' at '.$form->EventLocation.'<br />';
        $html .= "End at ".date('h:i A \o\n n/j/Y', $form->EndDateTime).'<br />';
        $html .= "Pickup at ".date('h:i A \o\n n/j/Y', $form->PickupDateTime).' at '.$form->PickupLocation.'<br /><br />';

        $html .= "Transportation provided: ".($form->TransportationProvided == 1 ? 'YES' : 'NO').'<br />';
        $html .= "Uniform: ".$form->Uniform.'<br />';
        $html .= "Comments: ".$form->Comments.'<br />';
        $html .= "Activity: ".$form->Activity.'<br />';
        $html .= "Required forms: ".$form->RequiredForms.'<br />';
        $html .= "Required equipment: ".$form->RequiredEquipment.'<br />';
        $html .= "Registration Deadline: ".date('n/j/Y', $form->RegistrationDeadline).'<br />';
        $html .= "Meals: ".$form->Meals.'<br />';
        $html .= "Desired number of Participants: ".$form->DesiredNumParticipants.'<br />';
        $html .= "Event status: ".$form->Status;
        $contact = [];
        if ($form->CAPPOC1ID != 0 && $form->CAPPOC1ReceiveEventUpdates) $contact[$form->CAPPOC1Name] = $form->CAPPOC1Email;
        if ($form->CAPPOC2ID != 0 && $form->CAPPOC2ReceiveEventUpdates) $contact[$form->CAPPOC2Name] = $form->CAPPOC2Email;
        if ($form->ExtPOCName != '' && $form->ExtPOCReceiveEventUpdates) $contact[$form->ExtPOCName] = $form->ExtPOCEmail;
        if ($form->AdditionalEmailAddresses != '') $contact[$form->AdditionalEmailAddresses] = $form->AdditionalEmailAddresses;
        if (strpos(php_uname('r'), 'amzn1') !== false) {
            return UtilCollection::sendFormattedEmail(
                $contact,
                $html,
                "Event $_ACCOUNT-$form->EventNumber cloned: $form->EventName (".date('h:i A n/j/Y', $form->StartDateTime).")"
            );
        }
    }


    function errorMailer(Member $member, $errorMessage) {
        //send email to developers and admins regarding a system error

        global $_ACCOUNT;

        $html = $member->RankName." attempted a function and an error occurred. <br />";
        $html .= $errorMessage;
        $contact = ["grioux.cap@gmail.com","arioux.cap@gmail.com"];
        return UtilCollection::sendFormattedEmail(
            $contact,
            $html,
            "Error Occurred: (".date('h:i A n/j/Y').")"
        );
    }


    // Replacement function:

    /*
    UtilCollection::sendFormattedEmail(
        ['name' => 'email address'], // People being addressed
        $html, // HTML to send, converts to text as well for alternate situations to gracefully downgrade
        $subject, // Email subject
        $from = 'no-reply', // Who it is from, defaults to no-reply. Adds @capunit.com to this for the full address
        $fromName = 'Do not reply' // The name of who it is from
    )
    */

    // $to = $formdata['form-data']['CAPPOC1Email'];
    // $subject = "Test mail";
    // $message = "This message contains text intended to be event information.\r\n";
    // $from = "CAP Event Manager <eventmanager@capunit.com>";
    // $headers = "From:" . $from . "\r\n" . 
    // 'Reply-To: CAP Event Manager <eventmanager@capunit.com>' . "\r\n" . 
    // 'X-Mailer: PHP/' . phpversion();
    // mail($to,$subject,$message,$headers);


?>
