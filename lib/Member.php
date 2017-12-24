<?php
    /**
     * @package lib/Member
     *
     * A collection of methods that can sign in as someone through Civil Air Patrol's National Headquarters portal and can be used to get access levels for this site, names of users, etc
     *
     * @author Andrew Rioux <arioux303931@gmail.com>
     *
     * @copyright 2016-2017 Rioux Development Team
     */

	require_once (BASE_DIR . "lib/DB_Utils.php");
	require_once (BASE_DIR . "lib/MyCURL.php");
	require_once (BASE_DIR . "lib/general.php");
	require_once (BASE_DIR . "lib/logger.php");
    require_once (BASE_DIR . "lib/Permissions.php");
	require_once (BASE_DIR . "lib/Registry.php");

    function _g ($html, $key) {
        $indexWorking = 0;$indexStart = 0;$indexEnd = 0;$strTemp = $html;
        $indexStart = strpos($strTemp, $key)-1;
        $indexWorking = $indexStart;
        $indexEnd = strlen($strTemp);
        while ($indexWorking <= $indexEnd) {
            if (substr($strTemp, $indexWorking, 5) == 'value') {
                $indexStart = $indexWorking + 7;
                break;
            }
            $indexWorking++;
        }
        while ($indexWorking <= $indexEnd) {
            if (substr($strTemp, $indexWorking, 1) == '>') {
                $indexEnd = $indexWorking - 3;
                break;
            }
            $indexWorking++;
        }
        $rkey = substr($strTemp, $indexStart, $indexEnd-$indexStart);
        return (rtrim($rkey));
    }

    /**
     * This generates a Member, a user with a name, rank, contact set, access levels, etc
     */
    class Member {
        const TestMode = true; // Should just be left at true
        const SkipNHQ = (HOST_OS == 'Windows NT'); // Whether to return an admin Member object when creating a member

        /**
         * @var int $uname Contains username, converted to a CAPID if in text form, e.g. lastname + firstinitial + middleinitial
         * @var int $capid CAPID
         */
        public $uname = 0, $capid = 0;

        /**
         * @var bool Whether or not this is a valid user, though a programmer never gets to use something invalid
         */
        public $success = false;

        /**
         * @var string Name of the member
         */
        public $memberName = '';

        /**
         * @var string Rank of the member
         */
        public $memberRank = 'C/AB';

        /**
         * @var string memberRank + memberName
         */
        public $RankName = '';

        /**
         * @var string Session ID
         */
        public $sid = '';

        /**
         * @var array[][] Contact information
         */
        public $contact;

        /**
         * @var string Cookie data used to sign into NHQ as this user
         */
        public $cookieData = '';

        /**
         * @var int[] Member permissions, for now 0 if they don't have it and 1 if they do
         */
        public $perms;

        /**
         * @var bool A rudimentary check to see if the user is in the same squadron as those operating the website
         */
        public $squadMember = false;

        /**
         * @var string Level of access determining permissions for user
         */
        public $AccessLevel = '';

        /**
         * @var string $firstName First name
         * @var string $middleName Middle name
         * @var string $lastName Last name
         * @var string $suffix Suffix
         */
        public $firstName = '', $middleName = '', $lastName = '', $suffix = '';

        /**
         * Gets login cookies given a username and user password
         *
         * @param str $uname Username of person to log in as
         * @param str $upass Password of person to log in as
         *
         * @return array Associative array with the keys 'success' for if the account is valid and 'cookieData' for the cookie data (only set fi success is true)
         */
        public static function GetLoginCookies ($uname, $upass) {
            $url = "https://www.capnhq.gov/CAP.eServices.Web/default.aspx";

            $ch = new MyCURL();

            $info = $ch->download($url); // Get login form, contains ASPX session variables

            $_ = $info["body"];

            $payload = array (
                // Fake a form submit, submitting all fields a browser sends (including the submit button)
                "__LASTFOCUS" => "",
                "__VIEWSTATE" => _g($_, "__VIEWSTATE"),
                "__EVENTTARGET" => "",
                "__EVENTARGUMENT" => "",
                "__EVENTVALIDATION" => _g($_, "__EVENTVALIDATION"),
                "__VIEWSTATEGENERATOR" => _g($_, "__VIEWSTATEGENERATOR"),
                'Login1$UserName' => $uname,
                'Login1$Password' => $upass,
                'Login1$LoginButton' => 'Sign+in'
            );

            $fields_string = ''; // Convert an associative array to a query string
            foreach ($payload as $key=>$value) {
                $fields_string .= urlencode($key)."=". urlencode($value) . "&";
            }
            $fields_string = rtrim($fields_string, "&");

            $ch = new MyCURL();

            $ch->setOpts (array (
                CURLOPT_POST => count($payload),
                CURLOPT_POSTFIELDS => $fields_string,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_HTTPHEADER => [ // Required headers aren't known, this just sends all headers a browser
                    // sends (with a modified user-agent)
                    'Host: www.capnhq.gov',
                    'User-Agent: EventManagementLoginBot/2.0',
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*,q=0.8',
                    'Accept-Language: en-US,en;q=0.5',
                    'Accept-Encoding: gzip, deflate, br',
                    'Connection: keep-alive',
                    'Upgrade-Insecure-Requests: 1'
                ],
            ), false);

            $result = $ch->download($url);

            if (!isset($result['headers']['response_code'])) {
                return array (
                    'success' => false,
                    'down' => true,
                    'reset' => false
                );
            }


            if ($result["headers"]["response_code"] == 302) { // NHQ redirects to another page instead of reloading
                // the signin form if the user signed in correctly
                if (substr($result['headers']['Location'], 0, 38) == '/CAP.eServices.Web/NL/Recover.aspx?UP=') {
                    // Detects whether or not the user is being redirected to reset their password
                    return array (
                        "success" => false,
                        "reset" => true,
                        'down' => false
                    );
                }
                return array (
                    "success" => true,
                    "cookieData" => $result["headers"]["Set-Cookie"]
                );
            } else {
                return array (
                    "success" => false
                );
            }
        }

        /**
         * Creates a user given a username and password, allows for getting access levels, etc
         *
         * @param str $uname Username of CAP account to create (if not 6 digit int string, downloads page to get int string)
         * @param str $upass Password of CAP account to create
         * @param str $mname A member name, skips trying to get that
         * @param array[] $contact A contact object, skips trying to get that
         * @param bool $ins In squadron
         *
         * @return Member Fully realized member
         */
        public static function Create ($uname, $upass, $mname=Null, $contact=Null, $ins=false) {
            global $_ACCOUNT;
            if (self::SkipNHQ) { // Used to create a member for testing with something like SQLMap or MetaSploit to 
                // handle security stuff, NHQ is trusted to have done the same
                $m = self::Estimate(542488);
                $m->setSessionID();
                return $m;
            }
            $m = new self ();
            $m->uname = $uname;
            $m->upass = $upass;
            $m->data = self::GetLoginCookies($uname, $upass);
            $m->success = $m->data["success"];
            if ($m->success) {
                // Checks whether the user is valid
                $m->cookieData = $m->data["cookieData"];

                $m->cookieData = preg_replace('/HttpOnly/', '', $m->cookieData); // cURL and NHQ don't like HttpOnly in the headers
                preg_match_all('/(?:ASP\.NET_SessionId|\.CAPAUTH|CAPCUSTOMER)=.*?;/', $m->cookieData, $cookies);
                $m->cookieData = implode(' ', $cookies[0]);

                if (!isset($mname)) {
                    // Is the argument there? If so, skip a bit of webscraping
                    $h = $m->goToPage("/CAP.eServices.Web/MyAccount/GeneralInfo.aspx")['body'];
                    error_reporting(E_ALL ^ E_WARNING); // NHQ has some nasty html
                    $h = Util_Collection::ParseHTML($h);
                    error_reporting(E_ALL);


                    // Code to get member name
                    $fn = $h->getElementById("txtFirstName");
                    $mn = $h->getElementById("txtMI");
                    $ln = $h->getElementById("txtLastName");
                    $s = $h->getElementById("txtSuffix");

                    $m->memberName = "No name";

                    if ($fn || $mn || $ln || $s) {
                        $m->memberName = "";
                    }

                    if ($fn && $fn->hasAttribute("value") && $fn->getAttribute("value") != '') {
                        // There were problems with elements being undefined
                        $m->memberName = $fn->getAttribute("value"); // Build member name
                        $m->firstName =  $fn->getAttribute("value");
                    }
                    if ($mn && $mn->hasAttribute("value") && $mn->getAttribute("value") != '') {
                        $m->memberName .= ' '.$mn->getAttribute("value");
                        $m->middleName = $mn->getAttribute("value");
                    }
                    if ($ln && $ln->hasAttribute("value") && $ln->getAttribute("value") != '') {
                        $m->memberName .= ' '.$ln->getAttribute("value");
                        $m->lastName = $ln->getAttribute("value");
                    }
                    if ($s && $s->hasAttribute("value") && $s->getAttribute("value") != '') {
                        $m->memberName .= ' '.$s->getAttribute("value");
                        $m->suffix = $s->getAttribute("value");
                    }

                    // Get the member rank
                    $r = $h->getElementById("txtRank");
                    $m->seniorMember = false;
                    if ($r) {
                        $m->memberRank = $r->getAttribute("value");
                        // Does the member rank start with C/...? If so, it is a cadet.
                        $m->seniorMember = !(preg_match('/C\/.{2,6}/', $m->memberRank) || substr($m->memberRank, 0, 5) == 'CADET');
                    } else {
                        $m->memberRank = "CADET";
                        $m->seniorMember = false;
                    }
                } else {
                    // Skip the webscraping
                    $mname = explode(':', $mname);
                    $m->memberName = $mname[0];
                    $m->memberRank = $mname[1];
                }

                if (!isset ($contact)) {
                    // Is the argument there? If so, skip a bit of webscraping
                    error_reporting(E_ALL ^ E_WARNING); // NHQ has some nasty html
                    $h = $m->goToPage("/CAP.eServices.Web/MyAccount/ContactInfo.aspx");
                    $h = Util_Collection::ParseHTML($h['body']);
                    error_reporting(E_ALL);
                    $table = $h->getElementById("gvContactInformation");
                    $m->contact = array (
                        "ALPHAPAGER" => [],
                        "ASSISTANT" => [],
                        "CADETPARENTEMAIL" => [],
                        "CADETPARENTPHONE" => [],
                        "CELLPHONE" => [],
                        "DIGITALPAGER" => [],
                        "EMAIL" => [],
                        "HOMEFAX" => [],
                        "HOMEPHONE" => [],
                        "INSTANTMESSAGER" => [],
                        "ISDN" => [], // What even is ISDN?
                        "RADIO" => [],
                        "TELEX" => [],
                        "WORKFAX" => [],
                        "WORKPHONE" => []
                    );

                    foreach ($m->contact as $k => $v) { // Keep code DRY
                        $m->contact[$k] = array (
                            "PRIMARY" => [],
                            "SECONDARY" => [],
                            "EMERGENCY" => []
                        );
                    }

                    $trs = $table->getElementsByTagName("tr");
                    // I didn't like getting text of elements
                    function getEText ($e, $i) {
                        return str_replace([" ", "\n", "\r"], "", Util_Collection::GetTextOfElement($e->item($i)));
                    }

                    $l = $trs->length;
                    for ($i = 1; $i < $l; $i++) {
                        $el = $trs->item($i);
                        $children = $el->getElementsByTagName("td");

                        if (getEText($children, 4) == "True") {
                            continue;
                        }
                        $t = getEText($children, 2);
                        $m->contact[getEText($children, 0)][getEText($children, 1)][] = $t;
                    }

                    function getEText2($e, $i) {
                        return trim(UtilCollection::GetTextOfElement($e->childNodes->item($i)));
                    }
                } else {
                    $m->contact = $contact;
                }

                $uname = Null;

                if ((int)$m->uname === 0 || !$ins) { // $m->uname is a string name, not a six digit CAPID (XXXXXX)
                    error_reporting(E_ALL ^ E_WARNING); // NHQ has some nasty html
                    $h = Util_Collection::ParseHtml($m->goToPage("/preview/GatherEmails.aspx?t=a")['body']);
                    error_reporting(E_ALL);
                    $trs = $h->getElementById("gvEmails")->getElementsByTagName("tr");
                    $l = $trs->length;
                    if ((int)$m->uname === 0) {
                        // Look for the table row that has the same CAPID
                        for ($i = 1; $i < $l; $i++) {
                            if (str_replace(" ", "", getEText2($trs->item($i), 2)) == str_replace(" ", "", $m->memberRank . " " . $m->memberName)) {
                                $uname = getEText2($trs->item($i), 1);
                            }
                        }
                        $m->uname = isset($uname) ? $uname : $m->uname;
                        $m->capid = $m->uname;
                    }

                    // $m->squadMember = $_ACCOUNT->hasMember(new self (array (
                    //     'uname' => $m->uname
                    // )));
                }

                $m->perms = $m->getAccessLevels(); // Load permissions
                $m->logger->Log("Current SID: ".$m->setSessionID(), 8); // Set Session ID and log it at the same time!
                $m->capid = $m->uname; // Alias
            }

            return $m;
        }

        /**
         * This is used to create a Member given a session ID and some cookies
         *
         * @param array Associative array of a JSON decoded cookie
         * @param str|Null $su Person to `su` in as, requires Developer permissionss
         *
         * @return Member Fully realized member
         */
        public static function Check ($cookies, $su=Null) {
            $cookies = json_decode($cookies, true); // Used to be troubles with json_decode running on a variable
            // already json_decode-d
            $logger = new Logger ("MemberAccess");
            $pdo = DB_Utils::CreateConnection();
            $sess = DB_TABLES['SessionStorage'];
            $stmt = $pdo->prepare("DELETE FROM $sess WHERE `time` < ".time().";");
            $deleted = false;
            try {
                $deleted = $stmt->execute();

                if (!$deleted) {
                    $logger->Warn("Could not delete member sessions, ".var_export($stmt->errorInfo(), true), 2);
                }
            } catch (PDOException $e) {
                $logger->Warn("Could not delete member sessions, ".var_export($e->getMessage(), true), 2); // Heh, why do we even have logs? They aren't checked...
            }

            // Get session IDs
            $stmt = $pdo->prepare("SELECT * FROM $sess WHERE `time` > :time AND sessionid = :sid AND mid = :mid;");
            $logger->Log("Session ID: ".$cookies['sid'], 8);
            $stmt->bindValue(":time", time(), PDO::PARAM_INT);
            $stmt->bindValue(":sid", $cookies['sid'], PDO::PARAM_STR);
            $stmt->bindValue(':mid', $cookies['uname']);
            $sid = $cookies['sid'];
            $logger->Log("", 8, "SQL Statement: "."SELECT * FROM $sess WHERE `time` > ".time()." AND sessionid = '$sid';");
            $ret = DB_Utils::ExecutePDOStatement($stmt);


            $logger->Log($cookies['uname']." has ".count($ret)." session IDs stored", 8);
            if (count($ret) != 1) { // If there are more, sessions are acting weird
                return array ("valid" => false);
            } else {
                // Make it so a user doesn't have to sign in every twenty minutes, just twenty minutes after they browse
                // to a page
                $stmt = $pdo->prepare("UPDATE $sess SET `time` = :time WHERE sessionid = :sid;");
                $stmt->bindValue(':time', time()+(1200));
                $stmt->bindValue(':sid', $cookies['sid']);
                DBUtils::ExecutePDOStatement($stmt);


                $data = [
                    "uname" => $cookies['uname'],
                    "capid" => $cookies['uname'],
                    "success" => true,
                    "memberName" => $ret[0]['mname'],
                    "memberRank" => $ret[0]['mrank'],
                    "sid" => $cookies['sid'],
                    "contact" => json_decode($ret[0]['contacts'], true),
                    "cookieData" => $ret[0]['cdata'],
                    'seniorMember' => !(preg_match('/C\/.{2,6}/', $ret[0]['mrank']) || substr($ret[0]['mrank'], 0, 5) == 'CADET')
                ];
                $m = new self ($data);
                $m->perms = $m->getAccessLevels($su); // If someone is trying to su, check there
                // Admittedly, su is kind of redundant now because of the new su program which
                // overrides the CAPID in the session database
                $m->dutyPositions = $m->getDutyPositions($su);
                $m->flight = $m->getFlight($su);
                $sid = $m->setSessionID();
                return array (
                    'mem' => $m,
                    'valid' => true,
                    'sid' => $sid
                );
            }
        }

        /**
         * Provides an estimation of what the users details are like by using CAPWATCH file downloads
         *
         * Cannot go onto NHQ for advanced things, due to lack of cookies
         *
         * @param int CAP ID
         *
         * @return \Member A member
         */
        public static function Estimate ($capid) {
            $pdo = DB_Utils::CreateConnection();

            $stmt = $pdo->prepare('SELECT NameLast, NameFirst, NameMiddle, NameSuffix, Rank FROM '.DB_TABLES['Member'].' WHERE CAPID = :cid;');
            $stmt->bindValue(':cid', $capid);
            $data = DB_Utils::ExecutePDOStatement($stmt);
            if (count($data) != 1) return false;
            $data = $data[0];

            $mname = $data['NameFirst'] . ' ' . substr($data['NameMiddle'], 0, 1) . ' ' . $data['NameLast'] . ' ' . $data['NameSuffix'];
            $mrank = $data['Rank'];

            // Preparing data
            $data = [
                'uname' => $capid,
                'memberName' => $mname,
                'memberRank' => $mrank,
                'seniorMember' => !(preg_match('/C\/.{2,6}/', $mrank) || substr($mrank, 0, 5) == 'CADET'),
                'success' => true
            ];

            $mem = new self ($data);


            // As there is no webscraping to get contact info, instead load the data from the database
            $mem->getCAPWATCHContact();
            // Load extra variables...
            $mem->perms = $mem->getAccessLevels();
            $mem->dutyPositions = $mem->getDutyPositions();
            $mem->flight = $mem->getFlight();

            return $mem;
        }

        /**
         * Generator return a list of members that have a duty position
         * 
         * @param string Duty position to check for
         * 
         * @return Generator|Member[]
         */
        public static function GetByDutyPosition ($dpts) {
            $pdo = DBUtils::CreateConnection();
            $dp = DB_TABLES["DutyPosition"];
            $cdp = DB_TABLES["CadetDutyPositions"];
            $stmt = $pdo->prepare("(SELECT CAPID FROM $dp WHERE Duty = :dp) UNION (SELECT CAPID FROM $cdp WHERE Duty = :cdp);");
            $stmt->bindValue(":dp", $dpts);
            $stmt->bindValue(":cdp", $dpts);
            $data = DBUtils::ExecutePDOStatement($stmt);
            
            foreach ($data as $datum) {
                yield self::Estimate($datum['CAPID']);
            }
        }

        /**
         * Given data, creates a member
         *
         * Also can be created without anything, but will have to be populated by Member::Create, Member::Estimate, or Member::Check
         *
         * @param array $cookies Associative array of cookies
         *
         * @return Member A partial member
         */
        private function __construct ($cookies=Null) {
            if (isset($cookies)) {
                // Convert an associative array to object
                foreach ($cookies as $k => $v) {
                    $this->$k = $v;
                }
            }
            $this->capid = $this->uname; // Alias
            $this->RankName = $this->memberRank . ' ' . $this->memberName;

            $this->logger = new Logger ("MemberAccess");
            if (!self::SkipNHQ) {
                $this->curl = new MyCURL ();
            }
        }


        /**
         * Gets contact info from CAPWATCH files
         */
        public function getCAPWATCHContact () {
            $this->contact = array (
                "ALPHAPAGER" => [],
                "ASSISTANT" => [],
                "CADETPARENTEMAIL" => [],
                "CADETPARENTPHONE" => [],
                "CELLPHONE" => [],
                "DIGITALPAGER" => [],
                "EMAIL" => [],
                "HOMEFAX" => [],
                "HOMEPHONE" => [],
                "INSTANTMESSAGER" => [],
                "ISDN" => [],
                "RADIO" => [],
                "TELEX" => [],
                "WORKFAX" => [],
                "WORKPHONE" => []
            );

            // Same as webscraping program, just pull from the database

            foreach ($this->contact as $k => $v) {
                $this->contact[$k] = array (
                    "PRIMARY" => [],
                    "SECONDARY" => [],
                    "EMERGENCY" => []
                );
            }

            $pdo = DB_Utils::CreateConnection();
            $stmt = $pdo->prepare('SELECT `Type`, `Priority`, `Contact` FROM '.DB_TABLES['MemberContact'].' WHERE CAPID = :cid;');
            $stmt->bindValue(':cid', $this->uname);
            $data = DB_Utils::ExecutePDOStatement($stmt);
            foreach ($data as $datum) {
                $this->contact[str_replace(' ', '', $datum['Type'])][$datum['Priority']][] = $datum['Contact'];
            }
        }

        /**
         * Gets access levels for a user
         *
         * @param str|null $su If defined, and if the user has the 'Developer' flag in the UserAccess table, returns perms for that user
         *
         * @return array Associative array of member permissions
         */
        public function getAccessLevels ($su=Null) {
            $pdo = DB_Utils::CreateConnection();
            
            global $_ACCOUNT;

            // Get the access levels, but only for the current account (e.g., no global access levels)
            $stmt = $pdo->prepare("SELECT * FROM ".DB_TABLES['AccessLevel']." WHERE CAPID = :CAPID AND (".DB_TABLES['AccessLevel'].".AccountID = :aid OR ".DB_TABLES['AccessLevel'].".AccountID = 'www');");
            $stmt->bindParam(":CAPID", $this->uname);
            $stmt->bindParam(":aid", $_ACCOUNT->id);
            $rows = array ();
            $perms = array ();
            try {
                if ($stmt->execute()) {
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $rows[] = $row;
                    }
                }
            } catch (PDOException $e) {
                $this->logger->Warn("$this->memberName could not get access levels", 1);
                return array ();
            }

            if (count($rows) == 1) { // If there are mores rows, somethings wrong
                $this->AccessLevel = $rows[0]['AccessLevel']; // We've switched to access levels instead of individual permissions
                $perms = Permissions::GetPermissions($this);
                $perms['Developer'] = ($this->uname == 542488 || $this->uname == 546319) ? 1 : 0; // If they are special they get to be developers
            } else {
                $this->AccessLevel = "Member";
                $perms = Permissions::Member;
                $perms['Developer'] = 0;
            }

            $this->logger->Log ("$this->uname ($this->memberName) tried to log in, got permissions ".print_r($perms, true), 4);

            if (isset($perms['Developer']) && $perms['Developer'] && isset($su)) {
                global $SecurityLogger;
                $SecurityLogger->Log ("$this->uname ($this->memberName) getting permissions for $su", 4);
                $u = $this->uname;
                $this->uname = $su;
                $perms = $this->getAccessLevels();
                $this->uname = $u;
            }

            return $perms;
        }

        /**
         * Gets a users duty positions
         *
         * @param str|Null $su Person to su in as, requires Developer permissions
         *
         * @return str[] Duty positions
         */
        public function getDutyPositions ($su=Null) {
            $duties = [];    

            global $_ACCOUNT;

            if (isset($su)) {
                // Loads the access levels for the user to check if developer, the permission required for su
                $perms = $this->getAccessLevels();
            }

            if (isset($perms['Developer']) && $perms['Developer'] == 1 && isset($su)) {
                global $SecurityLogger;
                $SecurityLogger->Log ("$this->uname ($this->memberName) getting duty positions for $su", 4);
                $u = $this->uname;
                $this->uname = $su;
                $duties = $this->getDutyPositions();
                $this->uname = $u;
            } else {
                $pdo = DB_Utils::CreateConnection();
                $orgs = $_ACCOUNT->orgSQL;
                $stmt = $pdo->prepare("(SELECT Duty FROM ".DB_TABLES['CadetDutyPositions']." WHERE CAPID = :cid AND ORGID in $orgs) UNION (SELECT Duty FROM ".DB_TABLES['DutyPosition']." WHERE CAPID = :cidt AND ORGID in $orgs) UNION (SELECT position AS Duty FROM ".DB_TABLES['TempDP']." WHERE capid = :tcid AND AccountID = :aid AND ExpireTime > :time);");
                $stmt->bindParam(":cid", $this->uname);
                $stmt->bindParam(":cidt", $this->uname);
                $stmt->bindValue(':tcid', $this->uname);
                $stmt->bindValue(':aid', $_ACCOUNT->id);
                $stmt->bindValue(':time', time());
                $data = DB_Utils::ExecutePDOStatement($stmt);
                foreach ($data as $datum) {
                    $duties[] = $datum['Duty'];
                }
            }

            return $duties;
        }

        /**
         * Gets the flight of a user
         *
         * @param str|Null $su Person to su in as, requires Developer permissions
         *
         * @return string Flight
         */
        public function getFlight ($su=Null) {
            global $_ACCOUNT;
            if (isset($su)) {
                // Check if the user has permission to su in as another user
                $perms = $this->getAccessLevels();
            }

            if (isset($perms['Developer']) && $perms['Developer'] == 1 && isset($su)) {
                global $SecurityLogger;
                $SecurityLogger->Log ("$this->uname ($this->memberName) getting flight for $su", 4);
                $u = $this->uname;
                $this->uname = $su;
                $flight = $this->getFlight();
                $this->uname = $u;
                return $flight;
            } else {
                if ($this->seniorMember) {
                    return 'Senior Member';
                }
                $pdo = DB_Utils::CreateConnection();
                $stmt = $pdo->prepare("SELECT `Flight` FROM ".DB_TABLES['Flights']." WHERE `capid` = :capid;");
                $stmt->bindParam(':capid', $this->uname);
                $data = DB_Utils::ExecutePDOStatement($stmt);
                if (count($data) != 1) {
                    return 'None';
                }
                return $data[0]['Flight'];
            }
        }

        /**
         * Gets the flight and checks against the value
         *
         * @param str $flight Flight to check
         *
         * @return bool In flight
         */
        public function inFlight ($flight) {
            return $this->getFlight() == $flight;
        }

        /**
         * Returns whether or not a user has a duty position
         *
         * @param str $dutyPosition Position to check
         *
         * @return bool Whether or not the user has a duty position
         */
        public function hasDutyPosition ($dutyPosition) {
            if ($this->hasPermission('Developer')) return true;
            if (gettype($dutyPosition) == 'string') {
                return in_array($dutyPosition, $this->dutyPositions);
            } else {
                $ret = false;
                foreach ($dutyPosition as $dp) {
                    $ret = $ret || in_array($dp, $this->dutyPositions);
                    if ($ret) return true;
                }
                return $ret;
            }
        }

        /**
         * Returns whether or not a user meets a threshold for a permission
         *
         * @param str $perm Permission to check
         * @param int $thresh Threshold to check, defaults to 1
         *
         * @return bool Whether or not the user has appropriate permissions
         */
        public function hasPermission ($permission, $threshold=1) {
            if ($this->perms['Developer'] == 1) return true;
            return $this->perms[$permission] >= $threshold;
        }

        /**
         * Sets the session ID for a user given an ID
         *
         * @param str $id ID to set. If null, falls back to $this->sid. If that is null, creates one that is about 103 characters long
         *
         * @return str Set session ID
         */
        public function setSessionID ($id=Null) {
            $this->expireTime = time() + (60 * 20); // 20 minutes later
            $this->sid = isset($id) ?
                $id :
                isset ($this->sid) && $this->sid != '' ? 
                    $this->sid :
                    uniqid($this->uname.mt_rand(), true).(crypt(uniqid('', true), 'hallo, a good salt am i to protect people'));

            
            $pdo = DB_Utils::CreateConnection();

            if (false) {
                $stmt = $pdo->prepare("DELETE FROM ".DB_TABLES['SessionStorage']." WHERE mid = :cap;");
            } else {
                $stmt = $pdo->prepare("DELETE FROM ".DB_TABLES['SessionStorage']." WHERE mid = :cap AND `time` < :time;");
                $stmt->bindValue(':time', time());
            }
            $stmt->bindValue(":cap", $this->uname);
            $stmt->execute();

            $this->logger->Log("$this->uname trying to check session IDs", 4);

            $stmt = $pdo->prepare("INSERT INTO ".DB_TABLES["SessionStorage"]." VALUES (:mid, :sid, :time, :contacts, :cdata, :mname, :mrank);");
            $stmt->bindValue(':mid', $this->uname);
            $stmt->bindValue(':sid', $this->sid);
            $stmt->bindValue(':time', $this->expireTime);
            $stmt->bindValue(':contacts', json_encode($this->contact));
            $stmt->bindValue(':cdata', $this->cookieData);
            $stmt->bindValue(':mname', $this->memberName);
            $stmt->bindValue(':mrank', $this->memberRank);
            $this->logger->Log("$this->uname inserting with SQL `$stmt->queryString`, values ($this->uname, $this->sid, $this->expireTime)", 8);
            try {
                if (!$stmt->execute()) {
                    $this->logger->Warn("$this->uname couldn't set session ID $this->sid, ". var_export($stmt->errorInfo(), true), 3);
                    return false;
                }
                $this->logger->Log("$this->uname set session ID $this->sid", 4);
            } catch (PDOException $e) {
                $this->logger->Warn("$this->uname couldn't set session ID $this->sid, ".$e->getMessage(), 2);
                return false;
            }

            return $this->sid;
        }

        /**
         * Uses the MyCURL object to download data
         *
         * This automatically sends cookies and also a User agent
         *
         * @param str $url The URL to load
         *
         * @return array See MyCURL::download
         */
        public function goToPage ($url) {
            $this->curl->setOpts (array (
                CURLOPT_HTTPHEADER => [
                    "Cookie: " . $this->cookieData,
                    'Host: www.capnhq.gov',
                    'User-Agent: Mozilla/5.0 (compatible; EventManagementLoginBot/2.1)',
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*,q=0.8',
                    'Accept-Language: en-US,en;q=0.5',
                    'Accept-Encoding: gzip, deflate, br',
                    'Connection: keep-alive',
                    'Upgrade-Insecure-Requests: 1'
                ],
                CURLOPT_FOLLOWLOCATION => false
            ), false);
            if (!preg_match("/https\:\/\/www\.capnhq\.gov\//", $url)) {
                $url = "https://www.capnhq.gov$url";
            }
            return $this->curl->download($url, true);
        }

        /**
         * Creates a JSON encoded object that represents all that this object needs, particularly uname, cookieData, and sid
         *
         * @return str JSON encoded object
         */
        public function toObjectString () {
            return json_encode(array (
                "uname"         => $this->uname,
                "success"       => $this->success,
                "sid"           => $this->sid,
            ));
        }

        /**
         * Creates a full JSON encoded representation of this Member
         *
         * @return str JSON encoded string
         */
        public function toFullObjectString () {
            return json_encode(array (
                "uname"         => $this->uname,
                "success"       => $this->success,
                "memberName"    => $this->memberName,
                "memberRank"    => $this->memberRank,
                "sid"           => $this->sid,
                "contact"       => $this->contact,
                "cookieData"    => $this->cookieData
            ));
        }

        /**
         * Creates a JSON encoded object that represents all that this object needs, particularly uname, cookieData, and sid
         *
         * @return str JSON encoded object
         */
        public function __toString () {
            return $this->toObjectString();
        }

        /**
         * A DRY implementation of getBestContact
         *
         * @return str Best email fit
         */
        public function getBestEmail () {
            return $this->getBestContact(["EMAIL", "CADETPARENTEMAIL"]);
        }

        /**
         * A DRY implementation of getBestContact
         *
         * @return str Best phone fit
         */
        public function getBestPhone () {
            $phone = $this->getBestContact(["CELLPHONE",'WORKPHONE','HOMEPHONE','CADETPARENTPHONE']);
            if (preg_match("/[0-9]{10}/", $phone)) {
                $phone = '(' . substr($phone, 0, 3) . ')' . substr($phone, 3, 3) . '-' . substr($phone, 6, 4);
            }
            return $phone;
        }

        /**
         * Gets the best contact based on an array of what is passed
         *
         * If ['EMAIL', 'CADETPARENTEMAIL'] was passed
         * it would try to get the first primary email, then it would try the first secondary email, then the first
         * primary cadet parent email, the second cadet parent email, the first emergency email, and finally the first
         * emergency cadet parent email
         *
         * @param array $cl Contact list
         *
         * @return str Best contact based on list given
         */
        public function getBestContact ($cl) {
            for ($i = 0, $p = Null; $i < count($cl) && !isset($p); $i++) {
                $p = isset($p) ? $p : isset($this->contact[$cl[$i]]["PRIMARY"][0]) ? $this->contact[$cl[$i]]["PRIMARY"][0] : Null;
            }
            if (isset($p)) return $p;

            for ($i = 0; $i < count($cl) && !isset($p); $i++) {
                $p = isset($p) ? $p : isset($this->contact[$cl[$i]]["SECONDARY"][0]) ? $this->contact[$cl[$i]]["SECONDARY"][0] : Null;
            }
            if (isset($p)) return $p;

            for ($i = 0; $i < count($cl); $i++) {
                $p = isset($p) ? $p : isset($this->contact[$cl[$i]]["EMERGENCY"][0]) ? $this->contact[$cl[$i]]["EMERGENCY"][0] : Null;
            }
            return isset($p) ? $p : false;
        }

		/**
		 * Gets the list of available CAP WATCH files
		 *
		 * @return str[] Associative array list of key-value pairs (db id vs name)
		 */
		public function getCAPWATCHList () {
			$retdata = [];

			$data = $this->goToPage('/cap.capwatch.web/download.aspx');

			$h = Util_Collection::ParseHTML($data['body']);

			$sel = $h->getElementById('ctl00_MainContentPlaceHolder_OrganizationChooser1')->getElementsByTagName('select')->item(0);

			$nodes = $sel->childNodes;

			$l = $nodes->length;

			for ($i = 0; $i < $l; $i++) {
				$node = $nodes->item($i);
				$key = $node->getAttribute('value');
				$value = $node->textContent;

				if ($key !== '0') {
					$retdata[$key] = $value;
				}
			}

			return $retdata;
		}

		/**
		 * Downloads a CAPWATCH zip file given an id, optionally a place to download them
		 *
		 * @param str $id ID to use, the key of the associative array result of getCAPWATCHList
		 * @param str|null $fileLocation Location of zip to be downloaded to
		 *
		 * @return str File location of CAPWATCH file
		 */
		public function getCAPWATCHFile ($id, $floc=Null) {
			if (!isset($floc)) {
				$floc = BASE_DIR . "capwatch-zips/CAPWATCH-".date('Ymd-G').".zip";
			}

			$data = $this->goToPage('/cap.capwatch.web/download.aspx');

			$url = "https://www.capnhq.gov/cap.capwatch.web/download.aspx";

            $ch = new MyCURL();

            $_ = $data["body"];

            $payload = array (
                "__LASTFOCUS" => "",
                "__VIEWSTATE" => _g($_, "__VIEWSTATE"),
                "__EVENTTARGET" => "",
                "__EVENTARGUMENT" => "",
                "__EVENTVALIDATION" => _g($_, "__EVENTVALIDATION"),
                "__VIEWSTATEGENERATOR" => _g($_, "__VIEWSTATEGENERATOR"),
				"ctl00_LeftNavigationMenu_ExpandState" => "enn",
				"ctl00_LeftNavigationMenu_SelectedNode" => "",
				"ctl00_LeftNavigationMenu_PopulateLog" => "",
				'ctl00$MainContentPlaceHolder$ddlZipType' => 'Zip',
				'ctl00$MainContentPlaceHolder$btnSubmit' => 'Submit',

                'ctl00$MainContentPlaceHolder$OrganizationChooser1$ctl00' => $id
            );

            $fields_string = '';
            foreach ($payload as $key=>$value) {
                $fields_string .= urlencode($key)."=". urlencode($value) . "&";
            }
            $fields_string = rtrim($fields_string, "&");

            $ch = new MyCURL();

            $ch->setOpts (array (
                CURLOPT_POST => count($payload),
                CURLOPT_POSTFIELDS => $fields_string,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_HTTPHEADER => [
                    'Host: www.capnhq.gov',
                    'User-Agent: Mozilla/5.0 (compatible; EventManagementLoginBot/2.1)',
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*,q=0.8',
                    'Accept-Language: en-US,en;q=0.8',
                    'Accept-Encoding: gzip, deflate, br',
                    'Connection: keep-alive',
                    'Upgrade-Insecure-Requests: 1',
                    'Cookie: '.$this->cookieData
                ],
            ), false);

            $data = $ch->download($url);

            $_ = $data["body"];

            $payload = array (
                "__LASTFOCUS" => "",
                "__VIEWSTATE" => _g($_, "__VIEWSTATE"),
                "__EVENTTARGET" => 'ctl00$MainContentPlaceHolder$lnkGetData',
                "__EVENTARGUMENT" => "",
                "__EVENTVALIDATION" => _g($_, "__EVENTVALIDATION"),
                "__VIEWSTATEGENERATOR" => _g($_, "__VIEWSTATEGENERATOR"),
				'ctl00_LeftNavigationMenu_ExpandState' => "enn",
				'ctl00_LeftNavigationMenu_SelectedNode' => "",
				'ctl00_LeftNavigationMenu_PopulateLog' => "",
				'ctl00$MainContentPlaceHolder$ddlZipType' => 'Zip',

                'ctl00$MainContentPlaceHolder$OrganizationChooser1$ctl00' => $id
            );

            $fields_string = '';
            foreach ($payload as $key=>$value) {
                $fields_string .= urlencode($key)."=". urlencode($value) . "&";
            }
            $fields_string = rtrim($fields_string, "&");

            $ch = new MyCURL();

			$ch->setOpts (array (
                CURLOPT_POST => count($payload),
                CURLOPT_POSTFIELDS => $fields_string,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_HTTPHEADER => array (
                    'Host: www.capnhq.gov',
                    'Origin: www.capnhq.gov',
                    'Referer:https://www.capnhq.gov/cap.capwatch.web/download.aspx',
                    'Content-Type:application/x-www-form-urlencoded',
                    'User-Agent: Mozilla/5.0 (compatible; EventManagementLoginBot/2.1)',
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language: en-US,en;q=0.5',
                    'Accept-Encoding: gzip, deflate, br',
                    'Connection: keep-alive',
                    'Upgrade-Insecure-Requests: 1',
                    'Cookie: '.$this->cookieData,
                    'Cache-Control: max-age=0',
                    'Content-Length: '.strlen($fields_string)
                )
            ), false);

            if (file_exists($floc)) {
                unlink($floc);
            }

            file_put_contents($floc, $ch->download("https://www.capnhq.gov/cap.capwatch.web/download.aspx")['body']);

            return $floc;
		}

        /**
         * Returns whether or not the user is a POC of an event
         *
         * @param \Event $event Event to check
         *
         * @return bool Whether or not member is POC of event
         */
        public function isPOC ($event) {
            return $event->isPOC($this);
        }

        /**
         * Returns, based on analytical data, preferred browser
         *
         * @return string[] Browser name (index 0), browser version (index 1), and how many times they've visited with that browser (index 3)
         */
        public function getBrowser () {
            $pdo = DBUtils::CreateConnection();
            $stmt = $pdo->prepare("select Type, Version, Hits from BrowserAnalytics where :cid = 542488 and Hits in ((select Max(Hits) from
BrowserAnalytics where CAPID = :cid));");
            $stmt->bindValue(':cid', $this->uname);
            $data = DBUtils::ExecutePDOStatement($stmt);
            return [
                $data['Type'],
                $data['Version'],
                $data['Hits']
            ];
        }

        /**
         * Returns a list of teams the member belongs to, including as a team lead, mentor, or coach
         * 
         * @return Team[] List of teams
         */
        public function getTeams () {
            $data = $this->getTeamIDs();
            $ret = [];

            foreach ($data as $datum) {
                $ret[] = Team::Get($datum);
            }

            return $ret;
        }
        
        /**
         * Generates a list of teams, to help with memory issues
         * 
         * @return Generator|Team[] Team generator
         */
        public function genTeams () {
            $data = $this->getTeamIDs();
            
            foreach ($data as $datum) {
                yield Team::Get($datum);
            }
        }

        /**
         * Gets IDs of teams member belongs
         * 
         * @return int[] List of team ids
         */
        public function getTeamIDs () {
            $pdo = DBUtils::CreateConnection();
            $stmt = $pdo->prepare("SELECT TeamID FROM ".DB_TABLES['TeamMembers']." WHERE CAPID = :cid;");
            $stmt->bindValue(':cid', $this->uname);
            $data = DBUtils::ExecutePDOStatement($stmt);
            $ret = [];

            foreach ($data as $datum) {
                $ret[] = $datum['TeamID'];
            }

            $stmt = $pdo->prepare("SELECT TeamID FROM ".DB_TABLES['Team']." WHERE TeamLead = :cid OR TeamCoach = :cid OR TeamMentor = :cid OR 542488 = :cid OR 546319 = :cid;");
            $stmt->bindValue(':cid', $this->uname);
            $data = DBUtils::ExecutePDOStatement($stmt);

            foreach ($data as $datum) {
                $ret[] = $datum['TeamID'];
            }

            return $ret;
        }

        /**
         * Gets IDs for accounts that have squadrons that the member belongs to
         * 
         * @return string[] List of account IDs
         */
        public function getAccountIDs () {
            $pdo = DBUtils::CreateConnection();
            $acc = DB_TABLES['Accounts'];
            $mem = DB_TABLES['Member'];
            $stmt = $pdo->prepare("SELECT AccountID FROM $acc AS A INNER JOIN $mem AS M ON A.UnitID = M.ORGID WHERE M.CAPID = :cid;");
            $stmt->bindValue(':cid', $this->uname);
            $data = DBUTils::ExecutePDOStatement($stmt);
            $ret = [];

            foreach ($data as $datum) {
                $ret[] = $datum['AccountID'];
            }
            
            return $ret;
        }

        /**
         * Returns an array of Accounts
         * 
         * @return Account[] Array of accounts
         */
        public function getAccounts () {
            $d = $this->getAccountIDs();
            $ret = [];

            foreach ($d as $data) {
                $ret[] = new Account($data);
            }

            return $ret;
        }

        /**
         * Generates an array of Accounts
         * 
         * @return Generator|Account[] Account generator
         */
        public function genAccounts () {
            $d = $this->getAccountIDs();
            
            foreach ($d as $data) {
                yield new Account($data);
            }
        }
    }
