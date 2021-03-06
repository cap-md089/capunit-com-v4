<?php
    /**
     * @package lib/Event
     *
     * A representation of an Event
     *
     * @author Andrew Rioux <arioux303931@gmail.com>
     *
     * @copyright 2016-2019 Rioux Development Team
     */

    require_once (BASE_DIR."lib/Attendance.php");
    require_once (BASE_DIR."lib/SpecialAttendance.php");
    require_once (BASE_DIR."lib/Account.php");

    /**
     * This represents an Event and interfaces with the database to provide a useful API
     */
    class Event {
        /**
         * @var int UNIX timestamp of last modified date
         */
        public $TimeModified = 0;

        /**
         * @var int UNIX timestamp of date/time event was created
         */
        public $TimeCreated = 0;

        /**
         * @var int Event number of event
         */
        public $EventNumber = 0;

        /**
         * @var string Name of event
         */
        public $EventName = '';

        /**
         * @var int UNIX timestamp of when to meet for event
         */
        public $MeetDateTime = 0;

        /**
         * @var string Location to meet
         */
        public $MeetLocation = '';

        /**
         * @var int UNIX timestamp of when the event starts
         */
        public $StartDateTime = 0;

        /**
         * @var string Event location
         */
        public $EventLocation = '';

        /**
         * @var int UNIX timestamp of when event ends
         */
        public $EndDateTime = 0;

        /**
         * @var int UNIX timestamp of when after event to pickup
         */
        public $PickupDateTime = 0;

        /**
         * @var string Location for pickup after event
         */
        public $PickupLocation = '';

        /**
         * @var bool Is there transportation provided to the event?
         */
        public $TransportationProvided = false;

        /**
         * @var string If so, what kind?
         */
        public $TransportationDescription = '';

        /**
         * @var string Uniform to wear at the event
         */
        public $Uniform = '';

        /**
         * @var int Desired number of participants
         */
        public $DesiredNumParticipants = 0;

        /**
         * @var int UNIX timestamp of when signups close
         */
        public $RegistrationDeadline = 0;

        /**
         * @var int UNIX timestamp of when signups close
         */
        public $RegistrationInformation = '';

        /**
         * @var int UNIX timestamp of when the money is due
         */
        public $ParticipationFeeDue = 0;

        /**
         * @var float Amount of money due to sign up for the event (USD)
         */
        public $ParticipationFee = 0.0;

        /**
         * @var string Description of meals
         */
        public $Meals = '';

        /**
         * @var string Location to meet
         */
        public $LodgingArrangements = '';

                 /**
         * @var string Activity description
         */
        public $Activity = '';

        /**
         * @var string Description of adventureness
         */
        public $HighAdventureDescription = '';

        /**
         * @var string Equipment to be brought
         */
        public $RequiredEquipment = '';

        /**
         * @var string Website where more info can be found, signups on government side, etc
         */
        public $EventWebsite = '';

        /**
         * @var string Forms required at event location
         */
        public $RequiredForms = '';

        /**
         * @var string Comments about the event
         */
        public $Comments = '';

        /**
         * @var bool Idk? Reserved stuff for the future?
         */
        public $AcceptSignUps = true;

        /**
         * @var string Idk? Reserved stuff for the future?
         */
        public $SignUpDenyMessage = '';

        /**
         * @var bool Whether or not this should be on the wing calendar
         */
        public $PublishToWingCalendar = false;

        /**
         * @var bool Whether or not attendance view should be displayed to all members
         *  or only to Managers and POCs
         */
        public $PrivateAttendance = false;

        /**
         * @var bool Whether or not to show this event on the home page in the upcoming events section
         */
        public $showUpcoming = true;

        /**
         * @var int Event number in Group calendar
         */
        public $GroupEventNumber = 0;

        /**
         * @var int Event number from Wing
         */
        public $WingEventNumber = 0;

        /**
         * @var bool Whether or not the event has passed
         */
        public $Complete = false;

        /**
         * @var string Administration comments
         */
        public $Administration = '';

        /**
         * @var string Whether or not this is commited to happen, maybe happening, or cancelled
         */
        public $Status = '';

        /**
         * @var string Debrief for cadets about the event
         */
        public $Debrief = '';

        /**
         * @var int $CAPPOC1ID ID of CAP POC 1
         * @var string $CAPPOC1Name Name of CAP POC 1
         * @var string $CAPPOC1Phone Phone number for CAP POC 1
         * @var string $CAPPOC1Email Email for CAP POC 1
         * @var int $CAPPOC1RxUpdates Flag to send event updates
         * @var int $CAPPOC1RxRoster Flag to send signup updates
         * @var int $CAPPOC2ID ID of CAP POC 2
         * @var string $CAPPOC1Name Name of CAP POC 2
         * @var string $CAPPOC2Phone Phone number for CAP POC 2
         * @var string $CAPPOC2Email Email for CAP POC 2
         * @var int $CAPPOC2RxUpdates Flag to send event updates
         * @var int $CAPPOC2RxRoster Flag to send signup updates
         */
        public $CAPPOC1ID = 0,
               $CAPPOC1Name = '',
               $CAPPOC1Phone = '',
               $CAPPOC1Email = '',
               $CAPPOC1RxUpdates = 0,
               $CAPPOC1RxRoster = 0,
               $CAPPOC2ID = 0,
               $CAPPOC2Name = '',
               $CAPPOC2Phone = '',
               $CAPPOC2Email = '',
               $CAPPOC2RxUpdates = 0,
               $CAPPOC2RxRoster = 0;

        /**
         * @var int $CAPPOC1ReceiveEventUpdates 
         * @var int $CAPPOC2ReceiveEventUpdates 
         * @var int $CAPPOC1ReceiveSignUpUpdates 
         * @var int $CAPPOC2ReceiveSignUpUpdates 
         * @var int $ExtPOCReceiveEventUpdates 
         */
        public $CAPPOC1ReceiveEventUpdates = false,
               $CAPPOC1ReceiveSignUpUpdates = false, 
               $CAPPOC2ReceiveEventUpdates = false, 
               $CAPPOC2ReceiveSignUpUpdates = false, 
               $ExtPOCReceiveEventUpdates = false;

        /**
         * @var string $ExtPOCName Name of external POC
         * @var string $ExtPOCPhone Phone number of external POC
         * @var string $ExtPOCEmail Email of external POC
         * @var int $ExtPOCRxUpdates Flag to send event updates
         */
        public $ExtPOCName = '',
               $ExtPOCPhone = '',
               $ExtPOCEmail = '';

        /**
         * @var int CAPID of author of event, allowing for cadet staff to create draft events only they and managers can see
         */
        public $Author = 0;

        /**
         * @var bool Whether or not it is possible to sign up for part of the event
         */
        public $PartTime = false;

		/**
		 * @var bool Whether or not this event is special, requiring a special attendance table
		 */
		public $IsSpecial = false;

        /**
         * @var int Is this event for a team? If so, TeamID, else 0
         */
        public $TeamID = 0;

        /**
         * @var int source event number for linked events
         */
        public $SourceEventNumber = 0;

        /**
         * @var string source account id for linked events
         */
        public $SourceAccountID = '';

        /**
         * @var string Contains a MySQL error, if it exists
         */
        public $error = '';

        /**
         * @var bool So it doesn't save after being removed on GBC
         */
        private $destroyed = false;

        /*
         * @var \Account Account to use instead of default account, defaults to default account
         */
        private static $account;

        /**
         *
         */
        public static function SetAccount (\Account $account) {
            self::$account = $account;
        }

        /**
         *
         */
        public static function GetAccount () {
            return self::$account;
        }

        /**
         * Gets an event based on the event number
         *
         * @param int $ev Event number
         *
         * @return Event
         */
        public static function Get (int $ev, \Account $acc = Null) {
            global $_ACCOUNT;
            if (!isset ($acc)) {
                $acc = $_ACCOUNT;
            }

            $pdo = DB_Utils::CreateConnection();
            $stmt = $pdo->prepare('SELECT * FROM '.DB_TABLES['EventInformation'].' WHERE EventNumber = :ev AND AccountID = :id;');
            $stmt->bindValue(':ev', $ev);
            $stmt->bindValue(':id', $acc->id);
            $data = DB_Utils::ExecutePDOStatement($stmt);
            if (count($data) != 1) {
                return false;
            }
            return new self ($data[0]);
        }

        /**
         * Creates an event given data
         *
         * @param string[] $data Data to create event with, uses default variables as keys required
         *
         * @return Event
         */
        public static function Create (array $data, \Account $acc = Null, \Member $member = Null) {
            global $_ACCOUNT;

            if (isset ($acc)) {
                $_ACCOUNT = $acc;
            }

            $data['Author'] = isset($member) ? $member->uname : 0;

            $event = new self($data);

            $errors = $event->checkErrors();
            if (!!$errors) {
                return $errors;
            }

            $pdo = DB_Utils::CreateConnection();

            if (!isset($data['EventNumber'])) {
                $stmt = $pdo->prepare('SELECT(SELECT MAX(EventNumber) FROM '.DB_TABLES['EventInformation'].' WHERE AccountID=:aid)+1 AS EventNumber');
                $stmt->bindValue(":aid", $_ACCOUNT->id);
                $data = DBUtils::ExecutePDOStatement($stmt);
                $event->EventNumber = (int)$data[0]['EventNumber'];
            }
            if ($event->EventNumber == 0) { $event->EventNumber = 1; }

            $stmt = $pdo->prepare("INSERT INTO ".DB_TABLES['EventInformation']." (
					Created, AccountID, EventNumber, EventName, MeetDateTime, MeetLocation, StartDateTime, EventLocation, 
					EndDateTime, PickupLocation, PickupDateTime, TransportationProvided, TransportationDescription, 
					Uniform, DesiredNumParticipants, RegistrationDeadline, RegistrationInformation, ParticipationFeeDue, 
                    ParticipationFee, LodgingArrangements, Meals, Activity, HighAdventureDescription, RequiredEquipment, 
                    EventWebsite, RequiredForms, Comments, AcceptSignUps, SignUpDenyMessage, PublishToWingCalendar, 
                    ShowUpcoming, GroupEventNumber, Complete, Administration, Status, Debrief, 
                    CAPPOC1ID, CAPPOC1Name, CAPPOC1Phone, CAPPOC1Email, CAPPOC1ReceiveEventUpdates, CAPPOC1ReceiveSignUpUpdates, 
                    CAPPOC2ID, CAPPOC2Name, CAPPOC2Phone, CAPPOC2Email, CAPPOC2ReceiveEventUpdates, CAPPOC2ReceiveSignUpUpdates, 
                    AdditionalEmailAddresses, ExtPOCName, ExtPOCPhone, ExtPOCEmail, ExtPOCReceiveEventUpdates, Author, PartTime, TeamID,
                    SourceEventNumber, SourceAccountID, IsSpecial, PrivateAttendance
				) VALUES (
                    :created, :accountid, :eventnumber, :eventName, :meetDate, :meetLocation, :startDate, :eventLocation, 
                    :endDate, :pickupLocation, :pickupDate, :transportationProvided, :transportationDescription, 
                    :uniform, :desiredParticipants, :registrationDeadline, :registrationInformation, :participationFeeDeadline, 
                    :participationFee, :lodging, :meals, :activity, :highAdventureDescription, :requiredEquipment, 
                    :eventWebsite, :requiredForms, :comments, :acceptSignups, :signUpDeny, :publishToWing, 
                    :showUpcoming, :groupEventNumber, :entryComplete, :adminComments, :eventStatus, :debrief, 
                    :CAPPOC1ID, :CAPPOC1Name, :CAPPOC1Phone, :CAPPOC1Email, :CAPPOC1REU, :CAPPOC1RSU, 
                    :CAPPOC2ID, :CAPPOC2Name, :CAPPOC2Phone, :CAPPOC2Email, :CAPPOC2REU, :CAPPOC2RSU, 
                    :additionalEmailAddresses, :ExtPOCName, :ExtPOCPhone, :ExtPOCEmail, :ExtPOCREU, :author, :parttime, :teamid,
                    :sourceEventNumber, :sourceAccountID, :isSpecial, :privateAttendance
				);");

            $stmt->bindValue(':created', time());
            $stmt->bindValue(':accountid', $_ACCOUNT->id);
            $stmt->bindValue(':eventnumber', $event->EventNumber);
            $stmt->bindValue(':eventName', $event->EventName);
            $stmt->bindValue(':meetDate', $event->MeetDateTime);
            $stmt->bindValue(':meetLocation', $event->MeetLocation);
            $stmt->bindValue(':startDate', $event->StartDateTime);
            $stmt->bindValue(':eventLocation', $event->EventLocation);
            $stmt->bindValue(':endDate', $event->EndDateTime);
            $stmt->bindValue(':pickupDate', $event->PickupDateTime);
            $stmt->bindValue(':pickupLocation', $event->PickupLocation);
            $stmt->bindValue(':transportationProvided', $event->TransportationProvided ? 1 : 0);
            $stmt->bindValue(':transportationDescription', $event->TransportationDescription);
            $stmt->bindValue(':uniform', $event->Uniform);
            $stmt->bindValue(':desiredParticipants', $event->DesiredNumParticipants);
            $stmt->bindValue(':registrationDeadline', $event->RegistrationDeadline);
			$stmt->bindValue(':registrationInformation', $event->RegistrationInformation);
            $stmt->bindValue(':participationFeeDeadline', $event->ParticipationFeeDue);
            $stmt->bindValue(':participationFee', $event->ParticipationFee);
            $stmt->bindValue(':lodging', $event->LodgingArrangements);
            $stmt->bindValue(':meals', $event->Meals);
            $stmt->bindValue(':activity', $event->Activity);
            $stmt->bindValue(':highAdventureDescription', $event->HighAdventureDescription);
            $stmt->bindValue(':requiredEquipment', $event->RequiredEquipment);
            $stmt->bindValue(':eventWebsite', $event->EventWebsite);
            $stmt->bindValue(':requiredForms', $event->RequiredForms);
            $stmt->bindValue(':comments', $event->Comments);
            $stmt->bindValue(':acceptSignups', $event->AcceptSignUps);
            $stmt->bindValue(':signUpDeny', $event->SignUpDenyMessage);
            $stmt->bindValue(':publishToWing', $event->PublishToWingCalendar ? 1 : 0);
            $stmt->bindValue(':privateAttendance', $event->PrivateAttendance ? 1 : 0);
			$stmt->bindValue(':showUpcoming', $event->showUpcoming ? 1 : 0);
            $stmt->bindValue(':groupEventNumber', $event->GroupEventNumber);
            $stmt->bindValue(':entryComplete', $event->Complete ? 1 : 0);
            $stmt->bindValue(':adminComments', $event->Administration);
            $stmt->bindValue(':eventStatus', $event->Status);
            $stmt->bindValue(':debrief', $event->Debrief);
            $stmt->bindValue(':CAPPOC1ID', $event->CAPPOC1ID);
            $stmt->bindValue(':CAPPOC1Name', $event->CAPPOC1Name);
            $stmt->bindValue(':CAPPOC1Phone', $event->CAPPOC1Phone);
            $stmt->bindValue(':CAPPOC1Email', $event->CAPPOC1Email);
            $stmt->bindValue(':CAPPOC1REU', $event->CAPPOC1ReceiveEventUpdates ? 1 : 0);
            $stmt->bindValue(':CAPPOC1RSU', $event->CAPPOC1ReceiveSignUpUpdates ? 1 : 0);
            $stmt->bindValue(':CAPPOC2ID', $event->CAPPOC2ID);
            $stmt->bindValue(':CAPPOC2Name', $event->CAPPOC2Name);
            $stmt->bindValue(':CAPPOC2Phone', $event->CAPPOC2Phone);
            $stmt->bindValue(':CAPPOC2Email', $event->CAPPOC2Email);
            $stmt->bindValue(':CAPPOC2REU', $event->CAPPOC2ReceiveEventUpdates ? 1 : 0);
            $stmt->bindValue(':CAPPOC2RSU', $event->CAPPOC2ReceiveSignUpUpdates ? 1 : 0);
            $stmt->bindValue(':additionalEmailAddresses', $event->AdditionalEmailAddresses);
            $stmt->bindValue(':ExtPOCName', $event->ExtPOCName);
            $stmt->bindValue(':ExtPOCPhone', $event->ExtPOCPhone);
            $stmt->bindValue(':ExtPOCEmail', $event->ExtPOCEmail);
            $stmt->bindValue(':ExtPOCREU', $event->ExtPOCReceiveEventUpdates ? 1 : 0);
            $stmt->bindValue(':author', isset($member)?$member->uname:0);
            $stmt->bindValue(':parttime', $event->PartTime ? 1 : 0);
            $stmt->bindValue(':teamid', $event->TeamID);
            $stmt->bindValue(':sourceEventNumber', $event->SourceEventNumber);
            $stmt->bindValue(':sourceAccountID', $event->SourceAccountID);
			$stmt->bindValue(':isSpecial', $event->IsSpecial ? 1 : 0);

            $event->success = $stmt->execute() ? true : false;
            $event->error = $stmt->errorInfo();
            if (!$event->success) {
				trigger_error($stmt->errorInfo()[2], E_USER_WARNING);
            }

            $stmt = $pdo->prepare('SELECT TimeModified FROM '.DB_TABLES['EventInformation'].' WHERE EventNumber = :ev;');
            $stmt->bindValue(':ev', $event->EventNumber);
            if (!$stmt->execute()) {
				trigger_error($stmt->errorInfo()[2], E_USER_WARNING);
            }

            $event->TimeModified = strtotime($stmt->fetch(PDO::FETCH_ASSOC)['TimeModified']);

            return $event;
        }

        /**
         * Constructs an event object and returns it
         *
         * @param string[] $data Data to use
         *
         * @return Event
         */
        private function __construct (array $data) {
            $this->data = $data;
            foreach ($data as $k => $v) {
                $this->$k = $v;
            }

            $boolvars = [
                "TransportationProvided",
                "AcceptSignUps",
                "PublishToWingCalendar",
                "PrivateAttendance",
                "Complete",
                "PartTime",
                "CAPPOC1ReceiveEventUpdates",
                "CAPPOC1ReceiveSignUpUpdates",
                "CAPPOC2ReceiveEventUpdates",
                "CAPPOC2ReceiveSignUpUpdates",
                "ExtPOCReceiveEventUpdates",
				"IsSpecial"
            ];
            foreach ($boolvars as $bool) {
                $this->$bool = (gettype($this->$bool) == 'boolean' ? $this->$bool : ($this->$bool == 1));
            }

            $intvars = [
                "EventNumber",
                "MeetDateTime",
                "StartDateTime",
                "EndDateTime",
                "PickupDateTime",
                "DesiredNumParticipants",
                "RegistrationDeadline",
                "ParticipationFeeDue",
                "CAPPOC1ID",
                "CAPPOC2ID",
                "TeamID"
            ];
            foreach ($intvars as $int) {
                $this->$int = (int)$this->$int;
            }

			if ($this->IsSpecial) {
				$this->attendance = new SpecialAttendance($this->EventNumber);
			} else {
				$this->attendance = new Attendance($this->EventNumber);
			}
        }

        /**
         * Saves the event in the database
         *
         * @return self
         */
        public function save () {
//            global $_ACCOUNT;
            $_ACCOUNT=$this->GetAccount();

            $errors = $this->checkErrors();
            if (!!$errors) {
                return $errors;
            }

            $pdo = DB_Utils::CreateConnection();

            $stmt = $pdo->prepare('UPDATE '.DB_TABLES['EventInformation'].' SET
                EventName = :eventName, MeetDateTime = :meetDate, MeetLocation = :meetLocation,
                StartDateTime = :startDate, EventLocation = :eventLocation, EndDateTime = :endDate,
                PickupDateTime = :pickupDate, PickupLocation = :pickupLocation,
                TransportationProvided = :transportationProvided, TransportationDescription = :transportationDescription,
                Uniform = :uniform, DesiredNumParticipants = :desiredParticipants,
                RegistrationDeadline = :registrationDeadline, RegistrationInformation = :registrationInformation,
                ParticipationFeeDue = :participationFeeDeadline, LodgingArrangements =:lodging,
                ParticipationFee = :participationFee, Meals = :meals, Activity = :activity,
                HighAdventureDescription = :highAdventureDescription, RequiredEquipment = :requiredEquipment,
                EventWebsite = :eventWebsite, RequiredForms = :requiredForms, Comments = :comments,
                AcceptSignUps = :acceptSignups, SignUpDenyMessage = :signUpDeny, ShowUpcoming =:showUpcoming,
                PublishToWingCalendar = :publishToWing, GroupEventNumber = :groupEventNumber, PrivateAttendance = :privateAttendance,
                Complete = :entryComplete, Administration = :adminComments, Status = :eventStatus,
                Debrief = :debrief, CAPPOC1ID = :CAPPOC1ID, CAPPOC1Name = :CAPPOC1Name, CAPPOC1Phone = :CAPPOC1Phone, CAPPOC1Email = :CAPPOC1Email,
                CAPPOC2ID = :CAPPOC2ID, CAPPOC2Name = :CAPPOC2Name, CAPPOC2Phone = :CAPPOC2Phone, CAPPOC2Email = :CAPPOC2Email, 
                ExtPOCName = :ExtPOCName, ExtPOCPhone = :ExtPOCPhone, AdditionalEmailAddresses =:additionalEmailAddresses,
                ExtPOCEmail = :ExtPOCEmail, Author = :author, PartTime = :parttime, TeamID = :teamid,
                CAPPOC1ReceiveEventUpdates = :POC1REU, CAPPOC1ReceiveSignUpUpdates = :POC1RSU,
                CAPPOC2ReceiveEventUpdates = :POC2REU, CAPPOC2ReceiveSignUpUpdates = :POC2RSU,
                ExtPOCReceiveEventUpdates = :ExtREU, SourceEventNumber = :sourceEventNumber, SourceAccountID = :sourceAccountID, IsSpecial = :isSpecial
                WHERE EventNumber = :ev AND AccountID = :aid;');

            $stmt->bindValue(':eventName', $this->EventName);
            $stmt->bindValue(':meetDate', $this->MeetDateTime);
            $stmt->bindValue(':meetLocation', $this->MeetLocation);
            $stmt->bindValue(':startDate', $this->StartDateTime);
            $stmt->bindValue(':eventLocation', $this->EventLocation);
            $stmt->bindValue(':endDate', $this->EndDateTime);
            $stmt->bindValue(':pickupDate', $this->PickupDateTime);
            $stmt->bindValue(':pickupLocation', $this->PickupLocation);
            $stmt->bindValue(':transportationProvided', $this->TransportationProvided ? 1 : 0);
            $stmt->bindValue(':transportationDescription', $this->TransportationDescription);
            $stmt->bindValue(':uniform', $this->Uniform);
            $stmt->bindValue(':desiredParticipants', $this->DesiredNumParticipants);
            $stmt->bindValue(':registrationDeadline', $this->RegistrationDeadline);
            $stmt->bindValue(':registrationInformation', $this->RegistrationInformation);
            $stmt->bindValue(':participationFeeDeadline', $this->ParticipationFeeDue);
            $stmt->bindValue(':participationFee', $this->ParticipationFee);
            $stmt->bindValue(':lodging', $this->LodgingArrangements);
            $stmt->bindValue(':meals', $this->Meals);
            $stmt->bindValue(':activity', $this->Activity);
            $stmt->bindValue(':highAdventureDescription', $this->HighAdventureDescription);
            $stmt->bindValue(':requiredEquipment', $this->RequiredEquipment);
            $stmt->bindValue(':eventWebsite', $this->EventWebsite);
            $stmt->bindValue(':requiredForms', $this->RequiredForms);
            $stmt->bindValue(':comments', $this->Comments);
            $stmt->bindValue(':acceptSignups', $this->AcceptSignUps);
            $stmt->bindValue(':signUpDeny', $this->SignUpDenyMessage);
            $stmt->bindValue(':privateAttendance', $this->PrivateAttendance ? 1 : 0);
            $stmt->bindValue(':publishToWing', $this->PublishToWingCalendar ? 1 : 0);
            $stmt->bindValue(':showUpcoming', $this->showUpcoming ? 1 : 0);
            $stmt->bindValue(':groupEventNumber', $this->GroupEventNumber);
            $stmt->bindValue(':entryComplete', $this->Complete ? 1 : 0);
            $stmt->bindValue(':adminComments', $this->Administration);
            $stmt->bindValue(':eventStatus', $this->Status);
            $stmt->bindValue(':debrief', $this->Debrief);
            $stmt->bindValue(':CAPPOC1ID', $this->CAPPOC1ID);
            $stmt->bindValue(':CAPPOC1Name', $this->CAPPOC1Name);
            $stmt->bindValue(':CAPPOC1Phone', $this->CAPPOC1Phone);
            $stmt->bindValue(':CAPPOC1Email', $this->CAPPOC1Email);
            $stmt->bindValue(':POC1REU', $this->CAPPOC1ReceiveEventUpdates ? 1 : 0);
            $stmt->bindValue(':POC1RSU', $this->CAPPOC1ReceiveSignUpUpdates ? 1 : 0);
            $stmt->bindValue(':CAPPOC2ID', $this->CAPPOC2ID);
            $stmt->bindValue(':CAPPOC2Name', $this->CAPPOC2Name);
            $stmt->bindValue(':CAPPOC2Phone', $this->CAPPOC2Phone);
            $stmt->bindValue(':CAPPOC2Email', $this->CAPPOC2Email);
            $stmt->bindValue(':POC2REU', $this->CAPPOC2ReceiveEventUpdates ? 1 : 0);
            $stmt->bindValue(':POC2RSU', $this->CAPPOC2ReceiveSignUpUpdates ? 1 : 0);
            $stmt->bindValue(':additionalEmailAddresses', $this->AdditionalEmailAddresses);
            $stmt->bindValue(':ExtPOCName', $this->ExtPOCName);
            $stmt->bindValue(':ExtPOCPhone', $this->ExtPOCPhone);
            $stmt->bindValue(':ExtPOCEmail', $this->ExtPOCEmail);
            $stmt->bindValue(':ExtREU', $this->ExtPOCReceiveEventUpdates ? 1 : 0);
            $stmt->bindValue(':author', $this->Author);
            $stmt->bindValue(':parttime', $this->PartTime ? 1 : 0);
            $stmt->bindValue(':teamid', $this->TeamID);
            $stmt->bindValue(':sourceEventNumber', $this->SourceEventNumber);
            $stmt->bindValue(':sourceAccountID', $this->SourceAccountID);
			$stmt->bindValue(':isSpecial', $this->IsSpecial ? 1 : 0);
            $stmt->bindValue(':ev', $this->EventNumber);
            $stmt->bindValue(':aid', $_ACCOUNT->id);

            $this->success = $stmt->execute();
            $this->error = $stmt->errorInfo();
            if (!$this->success) {
				trigger_error($stmt->errorInfo()[2], E_USER_WARNING);
            }

            $stmt = $pdo->prepare('SELECT TimeModified FROM '.DB_TABLES['EventInformation'].' WHERE EventNumber = :ev;');
            $stmt->bindValue(':ev', $this->EventNumber);
            if(!$stmt->execute()) {
				trigger_error($stmt->errorInfo()[2], E_USER_WARNING);
            }

            $this->TimeModified = strtotime($stmt->fetch(PDO::FETCH_ASSOC)['TimeModified']);

            return $this->success;
        }

        /**
         * Returns whether or not the Member is attending
         *
         * @param Member $member The member to check
         *
         * @return bool Whether or not they are attending
         */
        public function isAttending (\Member $member) {
            return/* whether or not */$this/* events */->attendance->has(/* the */$member);
        }

        /**
         * Returns the raw SQL output for the attendance
         *
         * @return \Attendance Attendance data
         */
        public function getAttendance () {
            return $this/* events */->attendance;
        }

        /**
         * Checks whether or not the values of this event are good
         *
         * @return bool|string Returns false when everything is good, a string detailing error otherwise
         */
        public function checkErrors () {
            $intvars = [
                "EventNumber",
                "MeetDateTime",
                "StartDateTime",
                "EndDateTime",
                "PickupDateTime",
                "DesiredNumParticipants",
                "RegistrationDeadline",
                "ParticipationFeeDue",
                "CAPPOC1ID",
                "CAPPOC2ID",
                "Author",
                "TeamID",
                "SourceEventNumber"
            ];
            $boolvars = [
                "TransportationProvided",
                "AcceptSignUps",
                "PublishToWingCalendar",
                "PrivateAttendance",
                "Complete",
                "PartTime",
                "CAPPOC1ReceiveEventUpdates",
                "CAPPOC1ReceiveSignUpUpdates",
                "CAPPOC2ReceiveEventUpdates",
                "CAPPOC2ReceiveSignUpUpdates",
                "ExtPOCReceiveEventUpdates"
            ];
            $stringvars = [
                "EventName",
                "MeetLocation",
                "EventLocation",
                "PickupLocation",
                "TransportationDescription",
                "Uniform",
                "Meals",
                "Activity",
                "HighAdventureDescription",
                "RequiredEquipment",
                "EventWebsite",
                "RequiredForms",
                "Comments",
                "SignUpDenyMessage",
                "Administration",
                "Status",
                "Debrief",
                "CAPPOC1Name",
                "CAPPOC1Phone",
                "CAPPOC1Email",
                "CAPPOC2Name",
                "CAPPOC2Phone",
                "CAPPOC2Email",
                "ExtPOCName",
                "ExtPOCPhone",
                "ExtPOCEmail",
                "SourceAccountID"
            ];

            foreach ($intvars as $intvar) {
                if ((int)$this->$intvar != $this->$intvar) {
                    return "\${$intvar} is not an integer ({$this->$intvar})";
                }
            }

            if ((float)$this->ParticipationFee != $this->ParticipationFee) {
                return "\$ParticipationFee is not a float ({$this->ParticipationFee})";
            }

            foreach ($boolvars as $boolvar) {
                if (gettype($this->$boolvar) != 'boolean') {
                    return "\${$boolvar} is not a boolean";
                }
            }

            foreach ($stringvars as $stringvar) {
                if (gettype($this->$stringvar) != 'string') {
                    return "\${$stringvar} is not a string";
                }
            }

            return false;
        }

        public function getFiles () {
            global $_ACCOUNT;
            $pdo = DBUtils::CreateConnection();
            $stmt = $pdo->prepare("SELECT FileID FROM ".DB_TABLES['FileEventAssignments']." WHERE AccountID=:aid AND EID=:eid;");
            $stmt->bindValue(":aid", $_ACCOUNT->id);
            $stmt->bindValue(':eid', $this->EventNumber);
            $data = DBUtils::ExecutePDOStatement($stmt);
            $ret = [];
            foreach ($data as $datum) {
                $ret[] = $datum['FileID'];
            }
            return $ret;
        }

        /**
         * Checks whether or not there is an error in a boolean form
         *
         * @return bool True if there are errors, false if there aren't any
         */
        public function hasError () {
            return !!$this->checkErrors();
        }

        /**
         * A function to save the Event when it is collected
         */
        public function __destruct () {
            if (!$this->destroyed) $this->save();
        }

        /**
         * Instead of setting via property over and over, just use an array for construction similar to Event::Create
         */
        public function set ($arr) {
            foreach ($arr as $k => $v) {
                $this->$k = $v;
				$this->data[$k] = $v;
            }
        }

        /**
         * Removes the event and unsets this object
         */
        public function remove (\Account $acct) {
            try {
                GoogleCalendar::removeCalendarEvent($this);
            } catch (Exception $e) {

            }
            $pdo = DB_Utils::CreateConnection();
            // if (Registry::get('Administration.ArchiveDeleteEvents')) {
            // $stmt = $pdo->prepare('UPDATE '.DB_TABLES['EventInformation'].' SET
            // `Status`=:status
            // WHERE `EventNumber` = :ev AND `AccountID` = :aid;');
            // } else {
            $stmt = $pdo->prepare('DELETE FROM '.DB_TABLES['EventInformation'].' WHERE EventNumber = :ev AND AccountID = :aid;');
            $this->attendance->clearAll();
            $this->destroyed = true;
            // }
            // var_export($this->EventNumber);
            // echo PHP_EOL;
            // var_export($_ACCOUNT->id);
            // echo PHP_EOL;
            // $stmt->bindValue(':status', "Deleted");
            $stmt->bindValue(':aid', $acct->id);
            $stmt->bindValue(':ev', $this->EventNumber);
            // unset($this);
            $stmt->execute();
            return [$acct->id, $this->EventNumber, $stmt->errorInfo(), $stmt->rowCount()];
            // return [];
        }

        /**
         * Determines whether or not a user is a POC
         *
         * @param \Member $member The member to check
         *
         * @return bool Whether or not the member is a POC
         */
        public function isPOC ($member) {
            return isset($member) && (
                ($member->uname == $this->CAPPOC1ID || $member->uname == $this->CAPPOC2ID) ||
                $member->uname == $this->Author ||
                $member->hasPermission('EditEvent'));
        }

        /**
         * Just for testing, don't use!
         */
        public function _destroy () {
            $this->destroyed = true;
        }
    }
