<?php
    /**
     * @package lib/general
     *
     * Creates the Util_Collection object, just a collection of functions that help out in different places
     *
     * @author Andrew Rioux <arioux303931@gmail.com>
     *
     * @copyright 2016-2017 Rioux Development Team
     */
    class Util_Collection {
        /**
         * A function that returns a formatted ISO datetime
         *
         * @return str Formatted datetime string
         */
        public static function GetISOTime () {
            return (new DateTime())->format("Y-M-d H:m:s");
        }

        /**
         * Parses HTTP headers, returns it as an associative array of key: value pairs
         *
         * Also returns the response code under the response_code key
         *
         * @param str HTTP headers seperated by \r\n
         *
         * @return str[] HTTP headers
         */
        public static function ParseHeaders ($headers) {
            $head = array();
            $headers = explode("\n", $headers);
            foreach( $headers as $k=>$v )
            {
                $t = explode( ':', $v, 2 );
                if (isset($t[1])) {
                    if (isset($head[trim($t[0])])) {
                        $head[trim($t[0])] = $head[trim($t[0])] . trim($t[1]);
                    } else {
                        $head[ trim($t[0]) ] = trim( $t[1] );
                    }
                } else {
                    $head[] = $v;
                    if (preg_match("#HTTP/[0-9\.]+\s+([0-9]+)#",$v, $out))
                        $head['response_code'] = intval($out[1]);
                }
            }
            return $head;
        }

        /**
         * Parses HTML. For malformed HTML, it might be best to use error_reporting (E_ALL ^ E_WARNING) beforehand
         * and error_reporting (E_ALL) afterwards
         *
         * @param str $html HTMl to parse
         *
         * @return DOMDocument Parsed HTML document
         */
        public static function ParseHTML ($html) {
            $d = new DOMDocument();
            libxml_use_internal_errors(true);
            @$d->loadHTML(preg_replace("/\&/", "", $html));
            libxml_clear_errors();
            libxml_use_internal_errors(false);
            return $d;
        }

        /**
         * @deprecated
         * This was before I realized you could just use ->textContent of a DOMNode and was kept for backwards compatibility
         *
         * @param DOMNode $dnode A node to get the text of
         *
         * @return str Text of node
         */
        public static function GetTextOfElement ($domelement) {
            return $domelement->textContent;
        }

        /**
         * A function copied off of the internet to get the browser name off of the user agent, doesn't get browser versions
         *
         * @param str $user_agent User agent string
         *
         * @return str Browser name
         */
        public static function get_browser_name($user_agent) {
            $user_agent = isset($user_agent) ? $user_agent : $_SERVER["HTTP_USER_AGENT"];
            if (strpos($user_agent, 'Opera') || strpos($user_agent, 'OPR/')) return 'Opera';
            elseif (strpos($user_agent, 'Edge')) return 'Edge';
            elseif (strpos($user_agent, 'Chrome')) return 'Chrome';
            elseif (strpos($user_agent, 'Safari')) return 'Safari';
            elseif (strpos($user_agent, 'Firefox')) return 'Firefox';
            elseif (strpos($user_agent, 'MSIE') || strpos($user_agent, 'Trident/7')) return 'Internet Explorer';
            return 'Other';
        }

        /**
         * Prettifies HTML for the browser. No one likes a white page, and this also includes the sign in form
         *
         * Due to how this is at the end of most files, headers can be sent before the file is sent
         *
         * @param str $html HTML to insert into page
         *
         * @return str New HTML to be returned to browser
         *
         * @deprecated Unless you are teapot.php
         */
        public static function prettify_html ($html) {
            require BASE_DIR . "templates/footer.php";
            require BASE_DIR . "templates/header.php";
            require BASE_DIR . "templates/head.php";
            $head = HEAD_HTML;
            $header = HEADER_HTML;
            $footer = FOOTER_HTML;
            $signin = new AsyncForm ();
            $nhtml = <<<HTM
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        $head
    </head>

    <body>
        $header
        $signin
        <div id="body">
            $html
        </div>
        $footer
    </body>
</html>
HTM;
            return $nhtml;
        }

        /**
         * Creates HTML if there is an error
         *
         * @param Exception $e Exception from try/catch in main execution block
         *
         * @deprecated This is handled now by index.php, otherwise known as the router
         */
        public static function outputError ($e) {
            $error = $e->getFile().":".$e->getLine().": ".$e->getMessage();
            echo <<<HTM
<h2>Oops! Some error occurred.</h2>
<p>Here are the details:<br /><br />
<span style="font-family:monospace">$error</span>
</p>
HTM;
            self::outputHTML();
        }

        /**
         * Displays HTML if there is AJAX or if there isn't
         *
         * @deprecated Use the object format, this is if you are using procedural code which is impossible to get unless you are teapot.php
         */
        public static function outputHTML () {
            global $ajax;
            echo $ajax ? ob_get_clean() : self::prettify_html(ob_get_clean());
        }

        /**
         * Logs data given at the log level
         *
         * If there is a user signed in, this adds '-user' to the filename
         *
         * @param mixed $data Data to log
         * @param str $fname Name of file to log to
         * @param int $level Log level
         */
        public static function LogData ($data, $fname, $level) {
            global $_USER;
            if (isset($_USER)) {
                (new Logger($fname.'-user'))->Log($data, $level, $_USER->uname);
            } else {
                (new Logger($fname))->Log($data, $level);
            }
        }

        /**
         * Takes an error number and returns the error itself
         *
         * @param int $errno Error level constant value
         *
         * @return str Error level name
         */
        public static function GetErrorName ($errno) {
            switch ($errno) {
                case E_ERROR : return 'E_ERROR'; break;
                case E_WARNING : return 'E_WARNING'; break;
                case E_PARSE: return 'E_PARSE'; break;
                case E_NOTICE: return 'E_NOTICE'; break;
                case E_CORE_ERROR : return 'E_CORE_ERROR'; break;
                case E_CORE_WARNING : return 'E_CORE_WARNING'; break;
                case E_COMPILE_ERROR : return 'E_COMPILE_ERROR'; break;
                case E_COMPILE_WARNING : return 'E_COMPILE_WARNING'; break;
                case E_USER_ERROR : return 'E_USER_ERROR'; break;
                case E_USER_WARNING : return 'E_USER_WARNING'; break;
                case E_USER_NOTICE : return 'E_USER_NOTICE'; break;
                case E_STRICT : return 'E_STRICT'; break;
                case E_RECOVERABLE_ERROR : return 'E_RECOVERABLE_ERROR'; break;
                case E_DEPRECATED : return 'E_DEPRECATED'; break;
                case E_USER_DEPRECATED : return 'E_USER_DEPRECATED'; break;
                case E_ALL : return 'E_ALL'; break;
            }
            return 'Other';
        }

        /**
         * Returns timestamp for a given time format
         *
         * @param str $ts Timestamp
         *
         * @return int Timestamp
         */
        public static function GetTimestamp ($ts, $ts2='') {
            if ($ts == '') { return 0; }
            $datetime = new DateTime();
            $ts = explode("/", $ts);
            if (count($ts) < 3) { return 0; }
            $ts[2] == 1900 ? $ts[2] = 1940 : Null;
            $datetime->setDate(isset($ts[2])?(int)$ts[2]:0, isset($ts[0])?(int)$ts[0]:0, isset($ts[1])?(int)$ts[1]:0);
            if ($ts2 != '') {
                $re = '/^([012]\d|\d):??([0-6]\d|\d)(?:\:([0-6]\d|\d))?? ([AP]M)?/i';
                preg_match_all($re, $ts2, $matches, PREG_SET_ORDER, 0);
                $datetime->setTime(((int)$matches[0][1]+((strtolower($matches[0][4]) == 'pm')?12:0)), (int)$matches[0][2], (int)$matches[0][3]);
            }
            return $datetime->getTimestamp();
        }

        public static function GetTime ($ts) {
            if ($ts == '') { return 0; }
            $datetime = new DateTime();
            $re = '/^([012]\d|\d):??([0-6]\d|\d)(?:\:([0-6]\d|\d))?? ([AP]M)?/i';
            preg_match_all($re, $ts, $matches, PREG_SET_ORDER, 0);
            if (count($matches) == 0) {
                return 0;
            }
            $datetime->setTime(((int)$matches[0][1]+((strtolower($matches[0][4]) == 'pm')?12:0)), (int)$matches[0][2], (int)$matches[0][3]);
            return $datetime->getTimestamp();
        }

        public static function GetTimestamp2 ($ts) {
            if ($ts == '') return 0;
            $dt = new DateTime();
            $ts = explode(" ", $ts);
            $ts[0] = explode("/", $ts[0]);
            $ts[1] = explode(":", $ts[1]);
            $dt->setDate($ts[0][2], $ts[0][0], $ts[0][1]);
            $dt->setTime($ts[1][0], $ts[1][1], isset($ts[1][2]) ? $ts[1][2] : 0);
            return $dt->getTimestamp();
        }

        public static function GetBrowser() {
            $u_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
            $bname = 'Unknown';
            $platform = 'Unknown';
            $version= "";

            //First get the platform?
            if (preg_match('/linux/i', $u_agent)) {
                $platform = 'linux';
            }
            elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
                $platform = 'mac';
            }
            elseif (preg_match('/windows|win32/i', $u_agent)) {
                $platform = 'windows';
            }

            // Next get the name of the useragent yes seperately and for good reason
            if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent))
            {
                $bname = 'Internet Explorer';
                $ub = "MSIE";
            }
            elseif(preg_match('/Firefox/i',$u_agent))
            {
                $bname = 'Mozilla Firefox';
                $ub = "Firefox";
            }
            elseif(preg_match('/Chrome/i',$u_agent))
            {
                $bname = 'Google Chrome';
                $ub = "Chrome";
            }
            elseif(preg_match('/Safari/i',$u_agent))
            {
                $bname = 'Apple Safari';
                $ub = "Safari";
            }
            elseif(preg_match('/Opera/i',$u_agent))
            {
                $bname = 'Opera';
                $ub = "Opera";
            }
            elseif(preg_match('/Netscape/i',$u_agent))
            {
                $bname = 'Netscape';
                $ub = "Netscape";
            } else {
                $ub = "Unknown";
            }

            // finally get the correct version number
            $known = array('Version', $ub, 'other');
            $pattern = '#(?<browser>' . join('|', $known) .
            ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
            if (!preg_match_all($pattern, $u_agent, $matches)) {
                // we have no matching number just continue
            }

            // see how many we have
            $i = count($matches['browser']);
            if ($i != 1) {
                //we will have two since we are not using 'other' argument yet
                //see if version is before or after the name
                if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
                    $version= $matches['version'][0];
                }
                elseif (isset($matches['version'][1])) {
                    $version= $matches['version'][1];
                }
            }
            else {
                $version= $matches['version'][0];
            }

            // check if we have a number
            if ($version==null || $version=="") {$version="?";}

            return array(
                'userAgent' => $u_agent,
                'browser'      => $bname,
                'majorver'   => $version,
                'platform'  => $platform,
                'pattern'    => $pattern
            );
        }

        public static function formatSizeUnits($bytes) {
            if ($bytes >= 1073741824) {
                $bytes = number_format($bytes / 1073741824, 1) . ' GB';
            } elseif ($bytes >= 1048576) {
                $bytes = number_format($bytes / 1048576, 1) . ' MB';
            } elseif ($bytes >= 1024) {
                $bytes = number_format($bytes / 1024, 1) . ' KB';
            } elseif ($bytes > 1) {
                $bytes = $bytes . ' bytes';
            } elseif ($bytes == 1) {
                $bytes = $bytes . ' byte';
            } else {
                $bytes = '0 bytes';
            }

            return $bytes;
        }

        public static function sendFormattedEmail ($addresses, $html, $subject, $alttext='', $from='events', $fromname='CAPUnit.com') {
/*            global $_ACCOUNT;
            $mail = new PHPmailer();
            $mail->Username = '';
            $mail->Password = '';
            $mail->Host = '';
            $mail->SMTPAuth = true;
            $mail->SMTPSecure = 'tls';
            $mail->Port = 0;
            $mail->isHTML(true);
            $mail->setFrom($from.'@capunit.com', $fromname);
            $mail->Subject = $subject;
            $mail->Body = <<<EOD
<div style="background-color:#f0f8ff;padding: 20px">
<header style="background:#28497e;padding:10px;height:200px;width:100%;margin:0;padding:0">
    <div style="padding:0;margin:0;background-image:url('https://$_ACCOUNT->id.capunit.com/images/header.png');width:100%;height:100%;background-size:contain;background-repeat:no-repeat;background-position:50% 50%"></div>
</header>
<div style="border: 5px solid #28497e;margin:0;padding: 20px">
$html
</div>
<footer style="background:#28497e;padding:25px;color: white">
    &copy; CAPUnit.com 2017
</footer>
</div>
EOD;
            if(strlen($alttext)>0) { $altbodytext = $alttext; } else
                { $altbodytext = $html; }
            $html = preg_replace('/<a.*href="(.*)".*>(.*)<\/a>/', '$2 ($1)', $html);
            $mail->AltBody = "CAPUnit.com notification\n\n".strip_tags(preg_replace('/<br.*>/', "\n", $altbodytext))."\n\nCopyright 2017 CAPUnit.com";
            $ret = true;
            foreach ($addresses as $name => $address) {
                $mail->AddAddress($address, $name);
				if (strpos(php_uname('r'), 'amzn1') !== false) {
                    $ret = $mail->send() || $ret;
                }
                $mail->clearAddresses();
            }
            return $ret;
*/        }

        public static function GenerateSideNavigation (array $links) {
            $html = '<ul id="nav">';
            global $_LOGGEDIN;
			global $_USER;
            if ($_LOGGEDIN && isset($_USER) && $_USER != false) {
				global $_ACCOUNT;
                $loggedIn=Member::Estimate($_USER->uname);
                $html .= "<li><a href=\"#\" class=\"signout_link\"><span class=\"arrow\"></span><span>Sign Out ".$loggedIn->RankName."</span></a></li>";

                if ($loggedIn->hasPermission('PersonnelFilesDel')) {
                    $groupIDs = 990101;
                } else { $groupIDs = 0; }

				$pdo = DBUtils::CreateConnection();
                $sqlstmt = "SELECT id FROM ".DB_TABLES['Notifications']." WHERE CAPID=:cid AND deleted=0 AND Acknowledged=0 ";
                $sqlstmt .= "UNION SELECT id FROM ".DB_TABLES['Notifications']." WHERE CAPID IN (";
                $sqlstmt .= ":groups) AND AccountID=(:acct) AND deleted=0 AND Acknowledged=0;";
                $stmt = $pdo->prepare($sqlstmt);
                $stmt->bindValue(':cid', $loggedIn->uname);
                $stmt->bindValue(':groups', $groupIDs);
                $stmt->bindValue(':acct', $_ACCOUNT->id);
				$unack = DBUtils::ExecutePDOStatement($stmt);

                $sqlstmt = "SELECT id FROM ".DB_TABLES['Notifications']." WHERE CAPID=:cid AND deleted=0 ";
                $sqlstmt .= "UNION SELECT id FROM ".DB_TABLES['Notifications']." WHERE CAPID IN (";
                $sqlstmt .= ":groups) AND AccountID=(:acct) AND deleted=0;";
                $stmt = $pdo->prepare($sqlstmt);
                $stmt->bindValue(':cid', $loggedIn->uname);
                $stmt->bindValue(':groups', $groupIDs);
                $stmt->bindValue(':acct', $_ACCOUNT->id);
				$active = DBUtils::ExecutePDOStatement($stmt);

				if(count($unack)>0) {
					$html .= "<li><a href=\"#\" onclick=\"getHtml('/notifications');return false;\"><span class=\"arrow\"></span><span>New Notifications: ".count($unack)."</span></a></li>";
				} else {
					$html .= "<li><a href=\"#\" onclick=\"getHtml('/notifications');return false;\"><span class=\"arrow\"></span><span>Notifications: ".count($active)."</span></a></li>";
				}
				$html .= "<li><a href=\"#\" onclick=\"getHtml('/personnel');return false;\"><span class=\"arrow\"></span><span>Personnel Files</span></a></li>";
                if ($_USER->uname == 542488 || $_USER->uname == 546319) {
                    $html .= "<li><a href=\"#\" onclick=\"getHtml();return false;\"><span class=\"arrow\"></span><span>Reload</span></a></li>";
					$stmt = $pdo->prepare("select id from ErrorMessages where resolved=0");
					$data = DBUtils::ExecutePDOStatement($stmt);
					$html .= "<li><a href=\"#\" onclick=\"getHtml('/errremark');return false;\"><span class=\"arrow\"></span><span>Errors: ".count($data)."</span></a></li>";
                }
            } else {
				$html .= '<li><a href="#" class="signin_link"><span class="arrow"></span><span>Sign In/Create Account</span></a></li>';
            }
            $html .= "<li><a href=\"#\" onclick=\"window.history.go(-1);return false;\"><span class=\"arrow\"></span><span>Go back</span></a></li>";
            foreach ($links as $link) {
                switch (strtolower($link['Type'])) {
                    case 'samesource' :
                    case 'pagelink' :
                    case 'reference' :
                    case 'ref' :
                        $html .= '<li>'.new PageLink('<span class="arrow"></span><span>' . $link['Text'].'</span>', $link['Target']) . '</li>'.PHP_EOL;
                    break;

                    case 'external' :
                    case 'anotherpage' :
                    case 'link' :
                        $linke = explode('/', ltrim($link['Target'], '/'));
                        $html .= '<li>'.new Link($linke[0], '<span class="arrow"></span><span>' . $link['Text'].'</span>', array_slice($linke, 1)).'</li>'.PHP_EOL;
                    break;
                }
            }
            $html .= '</ul>';
            return $html;
        }

        public static function GenerateBreadCrumbs (array $links) {
            $html = '<ul>';
            for ($i = 0; $i < count($links); $i++) {
                $link = $links[$i];
                $linke = explode('/', ltrim($link['Target'], '/'));
                $html .= "<li>".new Link($linke[0], $link['Text'], array_slice($linke, 1))."</li>";

                if ($i + 1 == count($links)) continue;
                $html .= " <li class=\"divider\">/</li> ";
            }
            $html .= "</ul>";
            return $html;
        }

        public static function implode_all($glue, $arr){
            for ($i=0; $i<count($arr); $i++) {
                if (@is_array($arr[$i]))
                    $arr[$i] = implode_all ($glue, $arr[$i]);
            }
            return implode($glue, $arr);
        }

        public static function CommandersUnit (string $page, Member $m) {
            $logger = New Logger ("CommandersUnit");
            $UnitCommanderIndex = strpos($page, "Unit Commander") + strlen("Unit Commander") + 2;
            if ($UnitCommanderIndex < 1000) {
                // the 'Unit Commander' text should appear much later in the text, like 1500.
                $logger->Warn("Unit Commander text did not appear where expected for $m->uname.  Please check /preview/Widgets/Commanders.aspx", 2);
                return 0;
            }
            $UnitWing = substr($page, $UnitCommanderIndex, 3);
            if(substr($UnitWing, 2, 1) == '-') {
                $UnitWing = substr($page, $UnitCommanderIndex, 2);
            }
            if(!ctype_alpha($UnitWing)) {
                $logger->Warn("UnitWing contained non-alphabetic characters for $m->uname.  Please check /preview/Widgets/Commanders.aspx", 2);
                return 0;
            }
            $UnitNumber = substr($page, $UnitCommanderIndex + strlen($UnitWing) + 1, 3);
            if(!ctype_digit($UnitNumber)) {
                if($UnitNumber !== "NTC" && $UnitNumber !== "WDC") {
                    $logger->Warn("UnitNumber contained non-numeric characters for $m->uname.  Please check /preview/Widgets/Commanders.aspx", 2);
                    return 0;
                }
            }

            $pdo = DB_Utils::CreateConnection();
            $stmt = $pdo->prepare('SELECT ORGID FROM '.DB_TABLES['Organization'].' WHERE Wing = :wid AND Unit = :uid;');
            $stmt->bindValue(':wid', $UnitWing);
            $stmt->bindValue(':uid', $UnitNumber);
            $data = DB_Utils::ExecutePDOStatement($stmt);
            if (count($data) != 1) {
                //there was an error; return zero to indicate no uniquely identified organizational id number
                $logger->Warn("Unit Commander text did not appear where expected for $m->uname.  Please check /preview/Widgets/Commanders.aspx", 2);
                return 0;
            } else {
                $data = $data[0];
                return $data['ORGID'];
            }
        }

        public static function createDate ($year=Null, $month=Null, $day=Null, $hour=Null, $minute=Null, $second=Null) {
            $year = isset($year)?$year:1970;
            $month = isset($month)?$month+1:0;
            $day = isset($day)?$day:1;
            $hour = isset($hour)?$hour:0;
            $minute = isset($minute)?$minute:0;
            $second = isset($second)?$second:0;
            $date = new DateTime();
            $date->setTime($hour, $minute, $second);
            $date->setDate($year, $month, $day);
            return $date;
        }

        public static function getDateTime($timestamp) {
            $date = new DateTime();
            $date->setTimestamp($timestamp);
            return $date;
        }

        public static function GetAccountIDFromUnit (string $unitString) {
			if ( strlen($unitString) <2 ) { return "err1"; }

			$unit = explode("-", $unitString);
			if ( count($unit) < 3 ) { return "err2"; }

            $pdo = DB_Utils::CreateConnection();
            $stmt = $pdo->prepare('SELECT ORGID FROM '.DB_TABLES['Organization'].' WHERE Region = :rid AND Wing = :wid AND Unit = :uid;');
			$stmt->bindValue(':rid', $unit[0]);
            $stmt->bindValue(':wid', $unit[1]);
            $stmt->bindValue(':uid', $unit[2]);
            $data = DB_Utils::ExecutePDOStatement($stmt);
            if (count($data) != 1) {
                //there was an error; return indicate no uniquely identified organizational id number
                return "err3";
            } else {
                $data = $data[0];
				$stmt = $pdo->prepare('SELECT AccountID FROM '.DB_TABLES['Accounts'].' WHERE UnitID = :uid AND MainOrg=1;');
				$stmt->bindValue(':uid', $data['ORGID']);
				$data2 = DB_Utils::ExecutePDOStatement($stmt);
				if (count($data2) != 1) {
					//there was an error; return indicate no uniquely identified account id number
					return "err4";
				} else {
					return $data2[0]['AccountID'];
				}
            }
        }

	public static function GetOrgIDFromUnit (string $uin) {

		$unit = explode("-", $uin);
		if ( count($unit) < 3 ) { return "err2"; }

		$pdo = DB_Utils::CreateConnection();
		$stmt = $pdo->prepare('SELECT ORGID FROM '.DB_TABLES['Organization'].' WHERE Region = :rid AND Wing = :wid AND Unit = :uid;');
		$stmt->bindValue(':rid', $unit[0]);
		$stmt->bindValue(':wid', $unit[1]);
		$stmt->bindValue(':uid', $unit[2]);
		$data = DB_Utils::ExecutePDOStatement($stmt);
		if (count($data) != 1) {
			//there was an error; return indicate no uniquely identified organizational id number
			return "err3";
		} else {
			return $data[0]['ORGID'];
		}
	}

    }

    class UtilCollection extends Util_Collection {}
