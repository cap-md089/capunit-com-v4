<?php
    /**
        * This function is used to send the appropriate mail to 
        * select addresses at the appropriate time
    */
    function eventMailer(Member $member, Event $form, Event $database=null) {
        //compare form and database for changed values
        //build changed values string and full event string
        
        global $_ACCOUNT;

        if (!isset($database)) { // Event was created, cannot compare against database
            $html = $member->RankName." has created an event and ";
            $html .= "you have been identified as a Point of Contact!<br />";
            $html .= "<br />Here are some basic event details: <br />";
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
            if ($form->CAPPOC1ID != 0) $contact[$form->CAPPOC1Name] = $form->CAPPOC1Email;
            if ($form->CAPPOC2ID != 0) $contact[$form->CAPPOC2Name] = $form->CAPPOC2Email;
            if ($form->ExtPOCName != '') $contact[$form->ExtPOCName] = $form->ExtPOCEmail;
            return UtilCollection::sendFormattedEmail(
                $contact,
                $html,
                "New event created: $form->EventName (".date('h:i A n/j/Y', $form->StartDateTime).")"
            );
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
                    'RegistrationDeadline','ParticipationFeeDue','ParticipationFee','Meals','LodgingArrangements','Activity',
                    'HighAdventureDescription','RequiredEquipment','EventWebsite','RequiredForms','Comments','AcceptSignups',
                    'SignUpDenyMessage','ReceiveEventUpdates','ReceiveSignUpUpdates','PublishToWingCalendar','GroupEventNumber',
                    'Complete','Administration','Status','Debrief','CAPPOC1ID','CAPPOC1Name','CAPPOC1Phone','CAPPOC1Email',
                    'CAPPOC2ID','CAPPOC2Name','CAPPOC2Phone','CAPPOC2Email','ExtPOCName','ExtPOCPhone','ExtPOCEmail','Author',
                    'PartTime','TeamID'
                ];

                foreach ($things as $thing) {
                    if ($form->$thing != $database->$thing) {
                        if ($thing == 'MeetDateTime' || $thing == 'StartDateTime' || $thing == 'EndDateTime' || $thing == 'PickupDateTime' || $thing == 'RegistrationDeadline') {
                            $fields[$thing] = [date('h:i A n/j/Y', $form->$thing),date('h:i A n/j/Y', $database->$thing)];
                        } else if ($thing == 'TransportationProvided') {
                            $fields[$thing] = [$form->$thing ? 'YES' : 'NO', $database->$thing ? 'YES' : 'NO'];  
                        } else {
                            $fields[$thing] = [$form->$thing, $database->$thing];
                        }
                    }
                }

                $html = $member->RankName." has updated event $_ACCOUNT-$form->EventNumber.<br /><ul>";

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

                $html .= "View the event <a href=\"".(new Link("eventviewer", "", [$form->EventNumber]))->getURL(false)."\">here</a>";

                $contact = [];
                if ($form->CAPPOC1ID != 0) $contact[$form->CAPPOC1Name] = $form->CAPPOC1Email;
                if ($form->CAPPOC2ID != 0) $contact[$form->CAPPOC2Name] = $form->CAPPOC2Email;
                if ($form->ExtPOCName != '') $contact[$form->ExtPOCName] = $form->ExtPOCEmail;
                
                if ($database->CAPPOC1ID != 0) $contact[$database->CAPPOC1Name] = $database->CAPPOC1Email;
                if ($database->CAPPOC2ID != 0) $contact[$database->CAPPOC2Name] = $database->CAPPOC2Email;
                if ($database->ExtPOCName != '') $contact[$database->ExtPOCName] = $database->ExtPOCEmail;
                
                return UtilCollection::sendFormattedEmail(
                    $contact,
                    $html,
                    "Event update for $_ACCOUNT-$form->EventNumber"
                );
            }
        }
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