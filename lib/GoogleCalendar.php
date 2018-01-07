<?php
    require_once (BASE_DIR."lib/DB_Utils.php");
    require_once (BASE_DIR."lib/vendor/autoload.php");
	require_once (BASE_DIR."lib/logger.php");

    class GoogleCalendar {
        public static $accountId;
        public static $client;
        public static $service;
		public static $logger;
		public static $loglevel;
        
        public static function init () {
			self::$loglevel = 2;
			self::$logger = new Logger("GoogleCalendarUpdate");
			global $_ACCOUNT;
			self::$accountId = $_ACCOUNT->id;

			self::$logger->Log("init:: AccountID=: ".$_ACCOUNT->id, self::$loglevel);
            self::$client = new Google_Client();
			if(!self::$client) {self::$logger->Log("init:: client creator returned false", self::$loglevel);} else {self::$logger->Log("init:: client creator returned true", self::$loglevel);}

/*			if ($credentials_file = checkServiceAccountCredentialsFile()) {
				self::$logger->Log("init:: no service account credentials file present", self::$loglevel);
			} 
*/			if (getenv('GOOGLE_APPLICATION_CREDENTIALS')) {
				self::$logger->Log("init:: google application credentials env present", self::$loglevel);
			}

			$retval = self::$client->setApplicationName("capunit.com calendar update");
			if(!$retval) {self::$logger->Log("init:: application name not set", self::$loglevel);} else {self::$logger->Log("init:: application name set", self::$loglevel);}

            $retval = self::$client->useApplicationDefaultCredentials();
			if(!$retval) {self::$logger->Log("init:: default credentials not found", self::$loglevel);} else {self::$logger->Log("init:: default credentials found", self::$loglevel);}

            $retval = self::$client->setScopes(Google_Service_Calendar::CALENDAR);
			if(!$retval) {self::$logger->Log("init:: calendar scope not set", self::$loglevel);} else {self::$logger->Log("init:: calendar scope set", self::$loglevel);}

            self::$service = new Google_Service_Calendar(self::$client);
			if(!self::$service) {self::$logger->Log("init:: calendar creator returned false", self::$loglevel);} else {self::$logger->Log("init:: calendar creator returned true", self::$loglevel);}

		}

        public static function updateCalendarEvent (Event $CUevent) {
            if ($CUevent->Status == 'Draft') {
				self::$logger->Log("Draft event, deleting from Google Calendar");
                self::removeCalendarEvent($CUevent);
                return;
            }
//            global $_ACCOUNT;
//            $eventId = $_ACCOUNT.'-'.$CUevent->EventNumber;
  //          $calendarId = $_ACCOUNT->getGoogleCalendarAccountIdMain();
    //        $wingCalendarId = $_ACCOUNT->getGoogleCalendarAccountIdWing();
            $eventId = 'MD-089-476';
            $calendarId = 'grioux.cap@gmail.com';
            $wingCalendarId = 'jsuslg20d20560cljmvr9oeu48@group.calendar.google.com';
			self::$logger->Log("Update Calendar Event::  EventID=: ".$eventId." CalID=: ".$calendarId." WCalID=: ".$wingCalendarId, self::$loglevel);
            
            $optParams = array(
                'q' => 'Event ID Number: '.$eventId,
                'orderBy' => 'startTime',
                'singleEvents' => TRUE,
            );
			self::$logger->Log("Update Calendar Event::  EventID=: ".$eventId." CalID=: ".$calendarId." WCalID=: ".$wingCalendarId, self::$loglevel);
            $results = self::$service->events->listEvents($calendarId, $optParams);
            $wingResults = self::$service->events->listEvents($wingCalendarId, $optParams);
            
            $returnString = '';

            //remove all event items from both calendars
            $results = $results->getItems();
            foreach ($results as $Gevent) {
                self::$service->events->delete($calendarId, $Gevent->getId());
            }
            $wingResults = $wingResults->getItems();
            foreach ($wingResults as $Gevent) {
                self::$service->events->delete($wingCalendarId, $Gevent->getId());
            }

            //remove all registration items
            $optParams = array(
                'q' => 'Event ID Number: '.$eventId.'-Reg',
                'orderBy' => 'startTime',
                'singleEvents' => TRUE,
            );
            $results = self::$service->events->listEvents($calendarId, $optParams);
            $results = $results->getItems();
            foreach ($results as $Gevent) {
                self::$service->events->delete($calendarId, $Gevent->getId());
            }
            //remove all registration fee items
            $optParams = array(
                'q' => 'Event ID Number: '.$eventId.'-Fee',
                'orderBy' => 'startTime',
                'singleEvents' => TRUE,
            );
            $results = self::$service->events->listEvents($calendarId, $optParams);
            $results = $results->getItems();
            foreach ($results as $Gevent) {
                self::$service->events->delete($calendarId, $Gevent->getId());
            }

            if ($CUevent->Status != 'Deleted') {
                //build event data and create Google event

                $descriptionString = "--Please contact the POC listed below directly with questions or comments.\n\n";
                $descriptionString .= "Event Information Link\n(Page includes event information and any applicable download links):\n";
                $descriptionString .= "https://".$_ACCOUNT->id.".capunit.com/eventviewer/".$CUevent->EventNumber."/\n\n";
                
                // Second block
                $descriptionString .= "--Meet at ".date('h:i A \o\n n/j/Y', $CUevent->MeetDateTime).' at '.$CUevent->MeetLocation."\n";
                $descriptionString .= "--Start at ".date('h:i A \o\n n/j/Y', $CUevent->StartDateTime).' at '.$CUevent->EventLocation."\n";
                $descriptionString .= "--End at ".date('h:i A \o\n n/j/Y', $CUevent->EndDateTime)."\n";
                $descriptionString .= "--Pickup at ".date('h:i A \o\n n/j/Y', $CUevent->PickupDateTime).' at '.$CUevent->PickupLocation."\n\n";

                // Third (fourth?) block
                $descriptionString .= "--Transportation provided: ".($CUevent->TransportationProvided == 1 ? 'YES' : 'NO')."\n";
                $descriptionString .= "--Uniform: ".$CUevent->Uniform."\n";
                $descriptionString .= "--Comments: ".$CUevent->Comments."\n";
                $descriptionString .= "--Activity: ".$CUevent->Activity."\n";
                $descriptionString .= "--Required forms: ".$CUevent->RequiredForms."\n";
                $descriptionString .= "--Required equipment: ".$CUevent->RequiredEquipment."\n";
                $descriptionString .= "--Registration Deadline: ".date('n/j/Y', $CUevent->RegistrationDeadline)."\n";
                $descriptionString .= "--Meals: ".$CUevent->Meals."\n";
                if ($CUevent->CAPPOC1ID != 0) {
                    $descriptionString .= "--CAP Point of Contact: ".$CUevent->CAPPOC1Name."\n";
                    $descriptionString .= "--CAP Point of Contact phone: ".$CUevent->CAPPOC1Phone."\n";
                    $descriptionString .= "--CAP Point of Contact email: ".$CUevent->CAPPOC1Email."\n";
                }
                if ($CUevent->CAPPOC2ID != 0) {
                    $descriptionString .= "--CAP Point of Contact: ".$CUevent->CAPPOC2Name."\n";
                    $descriptionString .= "--CAP Point of Contact phone: ".$CUevent->CAPPOC2Phone."\n";
                    $descriptionString .= "--CAP Point of Contact email: ".$CUevent->CAPPOC2Email."\n";
                }
                if ($CUevent->ExtPOCName != '') {
                    $descriptionString .= "--CAP Point of Contact: ".$CUevent->ExtPOCName."\n";
                    $descriptionString .= "--CAP Point of Contact phone: ".$CUevent->ExtPOCPhone."\n";
                    $descriptionString .= "--CAP Point of Contact email: ".$CUevent->ExtPOCEmail."\n";
                }
                $descriptionString .= "--Desired number of Participants: ".$CUevent->DesiredNumParticipants."\n";
                $descriptionString .= "--Event status: ".$CUevent->Status;

                $colorId = '9';
                if ($CUevent->Status == 'Tentative') {
                    $colorId = '7';
                } else if ($CUevent->Status == 'Cancelled') {
                    $colorId = '11';
                } else if ($CUevent->Status == 'Information Only') {
                    $colorId = '1';
                } else if ($CUevent->Status == 'Draft') {
                    $colorId = '6';
                }

                //create new event
                $Gevent = new Google_Service_Calendar_Event(array(
                    'summary' => $CUevent->EventName,
                    'location' => $CUevent->EventLocation,
                    'description' => '--Event ID Number: '.$eventId."\n".$descriptionString,
                    'start' => array(
                      'dateTime' => date('c', $CUevent->MeetDateTime),
                      'timeZone' => date('e', $CUevent->MeetDateTime),
                    ),
                    'end' => array(
                        'dateTime' => date('c', $CUevent->PickupDateTime),
                        'timeZone' => date('e', $CUevent->PickupDateTime),
                      ),
                    'colorId' => $colorId,
                  ));
                //insert new event into main calendar
                $Gevent = self::$service->events->insert($calendarId, $Gevent);

                //create new event
                $GwingEvent = new Google_Service_Calendar_Event(array(
                    'summary' => $CUevent->EventName,
                    'location' => $CUevent->EventLocation,
                    'description' => '--Event ID Number: '.$eventId."\n".$descriptionString,
                    'start' => array(
                      'dateTime' => date('c', $CUevent->MeetDateTime),
                      'timeZone' => date('e', $CUevent->MeetDateTime),
                    ),
                    'end' => array(
                        'dateTime' => date('c', $CUevent->PickupDateTime),
                        'timeZone' => date('e', $CUevent->PickupDateTime),
                      ),
                  ));
                //insert new event into wing calendar, if selected
                if ($CUevent->PublishToWingCalendar == true) {
                    $GwingEvent = self::$service->events->insert($wingCalendarId, $GwingEvent);
                }

                //build event registration data and create Google event, if applicable
                if ($CUevent->RegistrationDeadline > 0){
                    //create new event
                    $GregEvent = new Google_Service_Calendar_Event(array(
                        'summary' => "Registration Deadline for ".$CUevent->EventName,
                        'location' => $CUevent->EventLocation,
                        'description' => '--Event ID Number: '.$eventId."-Reg\n".$descriptionString,
                        'start' => array(
                        'dateTime' => date('c', $CUevent->RegistrationDeadline),
                        'timeZone' => date('e', $CUevent->RegistrationDeadline),
                        ),
                        'end' => array(
                            'dateTime' => date('c', $CUevent->RegistrationDeadline+60),
                            'timeZone' => date('e', $CUevent->RegistrationDeadline+60),
                            ),
                        'colorId' => '10',
                    ));
                    //insert new event into main calendar
                    $GregEvent = self::$service->events->insert($calendarId, $GregEvent);
                }

                //build event registration fee data and create Google event, if applicable
                if ($CUevent->ParticipationFeeDue > 0) {
                    //create new event
                    $GfeeEvent = new Google_Service_Calendar_Event(array(
                        'summary' => "Registration Fee Deadline for ".$CUevent->EventName,
                        'location' => $CUevent->EventLocation,
                        'description' => '--Event ID Number: '.$eventId."-Fee\n".$descriptionString,
                        'start' => array(
                        'dateTime' => date('c', $CUevent->ParticipationFeeDue),
                        'timeZone' => date('e', $CUevent->ParticipationFeeDue),
                        ),
                        'end' => array(
                            'dateTime' => date('c', $CUevent->ParticipationFeeDue+60),
                            'timeZone' => date('e', $CUevent->ParticipationFeeDue+60),
                        ),
                        'colorId' => '10',
                    ));
                    //insert new event into main calendar
                    $GfeeEvent = self::$service->events->insert($calendarId, $GfeeEvent);
                }
            }

            return $returnString;
        }



        public static function removeCalendarEvent (Event $CUevent) {
            global $_ACCOUNT;
            $eventId = $_ACCOUNT.'-'.$CUevent->EventNumber;
            $calendarId = $_ACCOUNT->getGoogleCalendarAccountIdMain();
            $wingCalendarId = $_ACCOUNT->getGoogleCalendarAccountIdWing();
            
            $optParams = array(
                'q' => 'Event ID Number: '.$eventId,
                'orderBy' => 'startTime',
                'singleEvents' => TRUE,
            );
            $results = self::$service->events->listEvents($calendarId, $optParams);
            $wingResults = self::$service->events->listEvents($wingCalendarId, $optParams);
            
            $returnString = '';

            //remove all event items from both calendars
            $results = $results->getItems();
            foreach ($results as $Gevent) {
                self::$service->events->delete($calendarId, $Gevent->getId());
            }
            $wingResults = $wingResults->getItems();
            foreach ($wingResults as $Gevent) {
                self::$service->events->delete($wingCalendarId, $Gevent->getId());
            }
        }

/*
Example code from API website


//add an event

$event = new Google_Service_Calendar_Event(array(
  'summary' => 'Google I/O 2015',
  'location' => '800 Howard St., San Francisco, CA 94103',
  'description' => 'A chance to hear more about Google\'s developer products.',
  'start' => array(
    'dateTime' => '2015-05-28T09:00:00-07:00',
    'timeZone' => 'America/Los_Angeles',
  ),
  'end' => array(
    'dateTime' => '2015-05-28T17:00:00-07:00',
    'timeZone' => 'America/Los_Angeles',
  ),
  'recurrence' => array(
    'RRULE:FREQ=DAILY;COUNT=2'
  ),
  'attendees' => array(
    array('email' => 'lpage@example.com'),
    array('email' => 'sbrin@example.com'),
  ),
  'reminders' => array(
    'useDefault' => FALSE,
    'overrides' => array(
      array('method' => 'email', 'minutes' => 24 * 60),
      array('method' => 'popup', 'minutes' => 10),
    ),
  ),
));

$calendarId = 'primary';
$event = $service->events->insert($calendarId, $event);
printf('Event created: %s\n', $event->htmlLink);


//add an attachment to an event

function addAttachment($calendarService, $driveService, $calendarId, $eventId, $fileId) {
  $file = $driveService->files->get($fileId);
  $event = $calendarService->events->get($calendarId, $eventId);
  $attachments = $event->attachments;

  $attachments[] = array(
    'fileUrl' => $file->alternateLink,
    'mimeType' => $file->mimeType,
    'title' => $file->title
  );
  $changes = new Google_Service_Calendar_Event(array(
    'attachments' => $attachments
  ));

  $calendarService->events->patch($calendarId, $eventId, $changes, array(
    'supportsAttachments' => TRUE
  ));
}



//add a recurring event

$event = new Google_Service_Calendar_Event();
$event->setSummary('Appointment');
$event->setLocation('Somewhere');
$start = new Google_Service_Calendar_EventDateTime();
$start->setDateTime('2011-06-03T10:00:00.000-07:00');
$start->setTimeZone('America/Los_Angeles');
$event->setStart($start);
$end = new Google_Service_Calendar_EventDateTime();
$end->setDateTime('2011-06-03T10:25:00.000-07:00');
$end->setTimeZone('America/Los_Angeles');
$event->setEnd($end);
$event->setRecurrence(array('RRULE:FREQ=WEEKLY;UNTIL=20110701T170000Z'));
$attendee1 = new Google_Service_Calendar_EventAttendee();
$attendee1->setEmail('attendeeEmail');
// ...
$attendees = array($attendee1,
                   // ...
                   );
$event->attendees = $attendees;
$recurringEvent = $service->events->insert('primary', $event);

echo $recurringEvent->getId();





*/


    }