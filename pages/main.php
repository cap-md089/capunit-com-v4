<?php
    define ("USER_REQUIRED", false);

    class Output {
        public static function doGet($e, $c, $l, $m, $a) {
            if (Registry::get("Styling.Preset") == 'marylandwing') {
                $divider = "<hr class=\"hr-divider\">";

                $dir = HOST_SUB_DIR;

                $leftsection1 = <<<leftsection
<section class="half">
    <div>

    </div>
    <h4>How to become a Senior Member (Age 18+)</h4>
    <div>
        <p>
            As a CAP Senior Member, you can choose to serve in one of 25 Specialty Track Career Fields 
            ranging from Public Affairs, to Administration, Communications, IT or Cadet Programs.
            We need your skills, and we will train you in the CAP Career Field you choose.
        </p>
        <p>
            Right now we need Aircrews, Scanner, Observers, and Pilots.  We can train you to 
            fly exciting Search and Rescue Missions.  Service to your Community and Country is 
            part-time, and can be an exciting second career.
        </p>
    </div>
    <h4>How to become a Cadet (Age 10-21)</h4>
    <div>
        <p>
            The CAP Cadet Program trains tomorrow's Leaders today.
        </p>
        <p>
            The program is run by our Cadet Leaders, under the direction of trained and 
            screened CAP Senior Members.  Weekly meetings at our local Squadrons develop Character 
            and Leadership.
        </p>
        <p>
            There is NO Obligation for Military service.
        </p>
        <p>
            We offer our Cadets' summer/winter Encampments, Flying, Pilot Training, Rocketry, travel 
            oppurtunities, and traing and participation in Search and Rescue operations.
        </p>
    </div>
</section>
leftsection;


                $middlesection1 = <<<middlesection
<section class="third">
    <div class="tbd">To<br/>Be<br/>Replaced<br/></div><br />$divider
    <div class="tbd">To<br/>Be<br/>Replaced<br/></div><br />$divider
    <div class="tbd">To<br/>Be<br/>Replaced<br/></div><br />$divider
</section>
middlesection;

                // SEARCH FORM
                $form = new AsyncForm('main', null, null, 'search');

                $form->addField("search", "nolabel", 'text', 'mainSearch', ['placeholder' => 'Search'])->setSubmitInfo('', '', 'hide');

                $form->reload = false;
                // END SEARCH FORM

                $ae = new Link ("page", "<img src=\"/{$dir}images/aerospace.png\" /><p style=\"text-align:center;\">Aerospace Education</p>", ['aerospaceeducation']);
                $cp = new Link ("page", "<img style=\"display:block;margin:0 auto;\" src=\"/{$dir}images/programs.png\" /><p style=\"text-align:center;\">Cadet Programs</p>", ['cadetprograms']);
                $es = new Link ("page", "<img style=\"display:block;margin:0 auto;\" src=\"/{$dir}images/emergency.png\" /><p style=\"text-align:center;\">Emergency Services</p>", ['emergencyservices']);

                $rightsection1 = <<<rightsection
<section class="half">
    <div>
        $form
    </div>
    <div style="width:180px;margin:0 auto;">
        $ae
    </div>
    $divider
    <div style="width:180px;margin:0 auto;">
        $cp
    </div>
    $divider
    <div style="width:180px;margin:0 auto;">
        $es
    </div>
</section>
rightsection;

                $leftsection2 = <<<leftsection
<section class="half">
<a class="twitter-timeline"
    data-height="600px"
    data-width="100%"
    href="https://twitter.com/CAPStMarys">Tweets by CAPStMarys</a>
<script async src="//platform.twitter.com/widgets.js" charset="utf-8"></script>
</section>
leftsection;

                $rightsection2 = <<<middlesection
<section class="half">
    <div class="fb-page" data-href="https://www.facebook.com/CAP-St-Marys-154994714519/" data-tabs="timeline" data-height="600" data-width="500" data-small-header="false" data-adapt-container-width="true" data-hide-cover="false" data-show-facepile="false"><blockquote cite="https://www.facebook.com/CAP-St-Marys-154994714519/" class="fb-xfbml-parse-ignore"><a href="https://www.facebook.com/CAP-St-Marys-154994714519/">CAP St. Marys</a></blockquote></div>
</section>
middlesection;

                $leftsection3 = <<<leftsection
<section class="half">
    <iframe style="height:600px;border:0pt none;width:99%;margin:0 0.5%;" src="/{$dir}blog?ajax=true&embed=true"></iframe>
</section>
leftsection;

                $rightsection3 = <<<rightsection
<section class="half">
    <iframe style="height:600px;border:0pt none;width:99%;margin:0 0.5%;" src="/{$dir}photolibrary?ajax=true&embed=true"></iframe>
</section>
rightsection;
return $leftsection1 . $rightsection1 . '<script id="facebook-jssdk" src="//connect.facebook.net/en_US/all.js#xfbml=1&version=v2.8"></script>';
//return $leftsection1 . $rightsection1 . $divider . $leftsection2 . $rightsection2 . $divider . $leftsection3 . $rightsection3 . '<script id="facebook-jssdk" src="//connect.facebook.net/en_US/all.js#xfbml=1&version=v2.8"></script>';   
}
            $finallinks = [
                [
                    'Type' => 'link',
                    'Target' => '/teamlist',
                    'Text' => 'Team List'
                ]
            ];
            if ($l) {
                $finallinks[] = [
                    'Type' => 'link',
                    'Target' => '/admin',
                    'Text' => 'Administration'
                ];
            }

            $html = '';

            $leftsection1 = <<<leftsection
            <section class="halfSection" style="float:left">
                <div>

                </div>
                <h4>How to become a Senior Member (Age 18+)</h4>
                <div>
                    <p>
                        As a CAP Senior Member, you can choose to serve in one of 25 Specialty Track Career Fields
                        ranging from Public Affairs, to Administration, Communications, IT or Cadet Programs.
                        We need your skills, and we will train you in the CAP Career Field you choose.
                    </p>
                    <p>
                        Right now we need Aircrews, Scanner, Observers, and Pilots.  We can train you to
                        fly exciting Search and Rescue Missions.  Service to your Community and Country is
                        part-time, and can be an exciting second career.
                    </p>
                    <p>
                        The application form can be downloaded from <a href="https://drive.google.com/open?id=1qAh_B3XHR2AFLkRG7og2297IRLDXm_4_">this link</a>.
                    </p>
                </div>
                <h4>How to become a Cadet (Age 12-21)*</h4>
                <div>
                    <p>
                        The CAP Cadet Program trains tomorrow's Leaders today.
                    </p>
                    <p>
                        The program is run by our Cadet Leaders, under the direction of trained and
                        screened CAP Senior Members.  Weekly meetings at our local Squadrons develop Character
                        and Leadership.
                    </p>
                    <p>
                        There is NO Obligation for Military service.
                    </p>
                    <p>
                        We offer our Cadets' summer/winter Encampments, Flying, Pilot Training, Rocketry, travel
                        oppurtunities, and traing and participation in Search and Rescue operations.
                    </p>
                    <p>
                        The application form can be downloaded from <a href="https://drive.google.com/open?id=136TeTQYn_DnOqyi_ZJl2Ho5ts1WoCJMw">this link</a>.
                        The online application (recommended) may be accessed at <a href="https://www.capnhq.gov/CAP.MembershipSystem.Web/CadetOnlineApp.aspx">this link</a>.
                    </p>
                    <p>
                        *Cadets may join a Middle School flight, if available, when they are enrolled in a middle school.  The 12 year minimum age
                        restriction does not apply in that case.  Contact your local unit for more details.
                    </p>
                </div>
            </section>
leftsection;

            $dir = HOST_SUB_DIR;

            $ae = new Link ("page", "<img src=\"/{$dir}images/aerospace.png\" /><p style=\"text-align:center;\">Aerospace Education</p>", ['aerospaceeducation']);
            $cp = new Link ("page", "<img style=\"display:block;margin:0 auto;\" src=\"/{$dir}images/programs.png\" /><p style=\"text-align:center;\">Cadet Programs</p>", ['cadetprograms']);
            $es = new Link ("page", "<img style=\"display:block;margin:0 auto;\" src=\"/{$dir}images/emergency.png\" /><p style=\"text-align:center;\">Emergency Services</p>", ['emergencyservices']);

            $rightsection1 = <<<rightsection
<section class="halfSection" style="float:right">
    <div style="width:180px;margin:10px auto;">
        $ae
    </div>
    <div class="divider"></div>
    <div style="width:180px;margin:10px auto;">
        $cp
    </div>
    <div class="divider"></div>
    <div style="width:180px;margin:10px auto;">
        $es
    </div>
</section>
rightsection;

            // $html .= $leftsection1 . $rightsection1 . "<div class=\"divider\"></div>";
            if($l && $a->hasMember($m)) {
                // $html .= "paid: ".$a->paid." expiresIn: ".$a->expiresIn." expired: ".$a->expired;
                if($a->paid) {
                    if($a->expiresIn < 0){
                        $html .= "<section><h3 style=\"text-align: center\"><font color=\"red\">";
                        //need to add unit admin email addresses as a link here
                        $html .= "This subscription has expired!!  Please contact someone on your account administrative staff (";
                        foreach ($a->adminName as $capid => $rankname) {
                            $html .= "<a href=\"mailto:".$a->adminEmail[$capid];
                            $html .= "?subject=Upgrade our CAPUnit.com account, please";
                            $html .= "&body=".$rankname.", please contact sales@capunit.com to renew our CAPUnit.com account!\">";
                            $html .= $rankname."</a>, ";
                        }
                        $html = rtrim($html, ', ');
                        $html .= ") to request a CAPUnit.com account renewal.";
                        $html .= "</font></h3></section>";
                        $html .= "<div class=\"divider\"></div>";
                    } else if($a->expiresIn < 32){
                        $html .= "<section><h3 style=\"text-align: center\"><font color=\"red\">";
                        $html .= "This subscription expires ";
                        if ($a->expiresIn > 1 ) {
                            $html .= "in ".$a->expiresIn." days!  ";
                        } else if ($a->expiresIn > 0 ) {
                            $html .= "tomorrow!!  ";
                        } else {
                            $html .= "today!!  ";
                        }
                        //need to add unit admin email addresses as a link here
                        $html .= "Please contact someone on your account administrative staff (";
                        foreach ($a->adminName as $capid => $rankname) {
                            $html .= "<a href=\"mailto:".$a->adminEmail[$capid];
                            $html .= "?subject=Upgrade our CAPUnit.com account, please";
                            $html .= "&body=".$rankname.", please contact sales@capunit.com to upgrade our CAPUnit.com account!\">";
                            $html .= $rankname."</a>, ";
                        }
                        $html = rtrim($html, ', ');
                        $html .= ") to avoid an interruption in CAPUnit.com premium account features.";
                        $html .= "</font></h3></section>";
                        $html .= "<div class=\"divider\"></div>";
                    }
                } else {
//                    $html .= "<section><h4 style=\"text-align: center\">";
                    //need to add unit admin email addresses as a link here
//                    $html .= "This is the free version of CAPUnit.com.  To gain access to unlimited events many additional features, please ";
//                    $html .= "contact someone on your account administrative staff (";
//                    foreach ($a->adminName as $capid => $rankname) {
//                        $html .= "<a href=\"mailto:".$a->adminEmail[$capid];
//                        $html .= "?subject=Upgrade our CAPUnit.com account, please";
//                        $html .= "&body=".$rankname.", please contact sales@capunit.com to upgrade our CAPUnit.com account!\">";
//                        $html .= $rankname."</a>, ";
//                    }
//                    $html = rtrim($html, ', ');
//                    $html .= ") to request a CAPUnit.com account upgrade.";
//                    $html .= "</h4></section>";
//                    $html .= "<div class=\"divider\"></div>";
                }
            }

                $html .= "<section class=\"halfSection\" style=\"text-align: left\">";


			$pdo = DBUtils::CreateConnection();
            $stmt = $pdo->prepare("SELECT EventNumber FROM ".DB_TABLES['EventInformation']." WHERE MeetDateTime > :now AND AccountID = :aid AND LEFT(Activity, 8)=\"Squadron\" LIMIT 1;");
            $stmt->bindValue (':now', time());
			$stmt->bindValue (':aid', $a->id);
            $event = DBUtils::ExecutePDOStatement($stmt);
			print_r($event);
			echo "\n";
            if (count($event) !== 1) {
                $meetinghtml = "<h3 style=\"text-align: center\">No Upcoming Meeting</h3>";
            } else {
                $e = Event::Get($event[0]['EventNumber'], $a);
				if (!!$e) {
                $link = new Link('eventviewer', "View details", [$e->EventNumber]);
                $meetinghtml = "<h3 style=\"text-align: center\">Next Meeting</h3>
                        <strong>Event:</strong> $e->EventName<br />
                    	<strong>Time:</strong> ".date('D, d M Y H:i', $e->MeetDateTime)."<br />
            	        <strong>Location:</strong> $e->MeetLocation<br />
        	            <strong>Uniform of the Day:</strong> $e->Uniform<br />
    	                $link";
				}
            }

	if($l && !$m->seniorMember) {
		//find member orgid
		$myorgid = Util_Collection::getOrgIDFromUnit($m->Squadron);
		//check last download date
		$sqlCWD = "SELECT Timestamp FROM CAPWATCH_Download_Log WHERE ORGID=:oid;";
		$stmtCWD = $pdo->prepare($sqlCWD);
		$stmtCWD->bindValue(':oid', $myorgid);
		$CWD = DBUtils::ExecutePDOStatement($stmtCWD);

		$stmtreqs = $pdo->prepare("SELECT * FROM Data_CadetAchvAprs WHERE CAPID=:cid ORDER BY CAPID, CadetAchvID DESC LIMIT 1;");
		$stmtreqs->bindValue (':cid', $m->capid);
		$topapproval = DBUtils::ExecutePDOStatement($stmtreqs);
		if (count($topapproval) == 1) {
			// INC, PND, APR are the potential status
			switch ($topapproval[0]['Status']) {
				case 'INC':
					$message="Promotion requirements for your next grade are not yet complete.  Review the requirements ";
					$nextGrade=$topapproval[0]['CadetAchvID'];
					break;
				case 'PND':
					$message="Approval for your next promotion is pending.  Review requirements for your next grade ";
					$nextGrade=$topapproval[0]['CadetAchvID'] + 1;
					break;
				case 'APR':
					$message="Promotion requirements for your current grade are complete.  Review requirements for your next grade ";
					$nextGrade=$topapproval[0]['CadetAchvID'] + 1;
					break;
			}
			$stmtact = $pdo->prepare("SELECT * FROM Data_CadetAchv WHERE CAPID=:cid AND CadetAchivID=:caid;");
			$stmtact->bindValue (':cid', $m->capid);
			$stmtact->bindValue (':caid', $topapproval[0]['CadetAchvID']);
			$topachv = DBUtils::ExecutePDOStatement($stmtact);
			$ptDate = $topachv[0]['PhyFitTestPass'];
			if ($topachv[0]['LeadLabDateP'] < 0) {
				$llMessage = "You need to pass a leadership test.  Take the test ";
			} else { $llMessage = ""; }
			if ($topachv[0]['AEDateP'] < 0) {
				$aeMessage = "You need to pass an aerospace test.  Take a test ";
			} else { $aeMessage = ""; }
			if ($topachv[0]['MoralLDateP'] < 0) {
				$mlMessage = "You need credit for Moral Leadership.  Attend a Character Development session.";
			} else { $mlMessage = ""; }
			if ($topachv[0]['DrillDate'] < 0) {
				$ddMessage = "You need credit for a drill test.  Request the test from your Chain of Command.  Study ";
			} else { $ddMessage = ""; }
			if ($topachv[0]['CadetOath'] == 0) {
				$oMessage = "You need credit for reciting the Cadet Oath.  Request the test from your Chain of Command.";
			} else { $oMessage = ""; }
		} else {
			$message="Promotion requirements for your first promotion are not yet complete.  Review the requirements ";
			$nextGrade = 1;
			$ptDate = -1;
			$llMessage = "You need to pass a leadership test.  Take the test ";
			$aeMessage = "You need to pass an aerospace test.  Take a test ";
			$mlMessage = "You need credit for Moral Leadership.  Attend a Character Development session.";
			$ddMessage = "You need credit for a drill test.  Request the test from your Chain of Command.  Study ";
			$oMessage = "You need credit for reciting the Cadet Oath.  Request the test from your Chain of Command.";
		}
		$stmt2 = $pdo->prepare("SELECT * FROM Data_CdtAchvEnum WHERE CadetAchvID=:mID;");
		$stmt2->bindValue (':mID', $nextGrade);
		$reqs = DBUtils::ExecutePDOStatement($stmt2);
		if (count($reqs) == 1) {
			$reqs = $reqs[0];
			$ptMessage = "You do not have credit for PT.  Attend a PT session.";

			// web links
			$cadetTrackerLink = "<a target=\"_blank\" href=\"https://www.capnhq.gov/CAP.eServices.Web/Reports.aspx?id=161\">Cadet Track Report</a>";
			$promoLink = "<a target=\"_blank\" href=\"".$reqs['ReqsWebLink']."\">here</a>";
			$llLink = "<a target=\"_blank\" href=\"".$reqs['LeadTestWebLink']."\">here</a>";
			if ($reqs['Aerospace'] == 1) {
				$aeLink = "<a target=\"_blank\" href=\"".$reqs['AeroTestWebLink']."\">here</a>";
			} else { $aeLink = ""; }
			$drillLink = "<a target=\"_blank\" href=\"".$reqs['DrillTestWebLink']."\">this test</a>";

			$html .= "<h3 style=\"text-align: center\">Promotion Requirements</h3>";
			$html .= $message.$promoLink.".<br /><br />";

			// select the latest approved (or pending) achievement record
			$sqlstmt = "SELECT Data_CadetAchvAprs.Status, Data_CadetAchvAprs.DateMod, Data_CadetAchv.PhyFitTestPass ";
			$sqlstmt .= "FROM EventManagement.Data_CadetAchvAprs LEFT JOIN EventManagement.Data_CadetAchv ";
			$sqlstmt .= "ON Data_CadetAchvAprs.CAPID=Data_CadetAchv.CAPID ";
			$sqlstmt .= "AND Data_CadetAchvAprs.CadetAchvID=Data_CadetAchv.CadetAchivID ";
			$sqlstmt .= "WHERE Data_CadetAchvAprs.CAPID=:cid AND ";
			$sqlstmt .= '(Data_CadetAchvAprs.Status="APR" OR Data_CadetAchvAprs.Status="PND")';
			$sqlstmt .= "ORDER BY CadetAchvID DESC LIMIT 1;";

			$stmt3 = $pdo->prepare($sqlstmt);
			$stmt3->bindValue (':cid', $m->capid);
			$apprv = DBUtils::ExecutePDOStatement($stmt3);
			if (count($apprv) == 1) {
				$apprDate = $apprv[0]['DateMod'];
				$eligDate = $apprDate + (60 * 60 * 24 * 56);  //56 days after last approval is eligible date
				if($eligDate < time()) {  // the cadet is already eligible
					$html .= "You met your minimum time of service in your present grade on ".date("d M Y", $eligDate);
					$html .= "  Once your other promotion requirements are met, you may be promoted immediately.<br /><br />";
				} else {  // the cadet is not yet eligible
					$html .= "You will meet your minimum time of service in your present grade on ".date("d M Y", $eligDate);
					if($apprv[0]['Status'] == 'PND') {
						$html .= "  Since your other promotion requirements are already met, you will be promoted that day.<br /><br />";
					} else {
						$html .= "  If your other promotion requirements are met before that date, you will be promoted that day.<br /><br />";
					}
				}
				if($ptDate < -1) { $ptDate = $apprv[0]['PhyFitTestPass']; }
			} else { $html .= "<br />"; }

			$newhtml = "<ul>";
			if ($llMessage != "" && $reqs['Leadership'] == 1) {
				$newhtml .= "<li>".$llMessage.$llLink."</li>";
			}
			if (($aeMessage != "") && ($aeLink != "")) {
				$newhtml .= "<li>".$aeMessage.$aeLink."</li>";
			}
			if ($ptDate == -1) {
				$newhtml .= "<li>".$ptMessage."</li>";
			} else {
				if($topapproval[0]['Status'] != 'INC') {
					$ptDate = $apprv[0]['PhyFitTestPass'];
				}
				$newhtml .= "<li>You passed the CPFT on ".date("d M Y", $ptDate);
				$ptExpire = $ptDate + (60 * 60 * 24 * 182); //6 months after last approved PT date or current PT date
				$newhtml .= " and your PT credit ";
				if ($ptExpire < time()) {
					$newhtml .= "expired on ".date("d M Y", $ptExpire);
					$newhtml .= ".  You will need to participate in a CPFT event before you are eligible to promote.";
				} else {
					$newhtml .= " will expire on ".date("d M Y", $ptExpire);
					if($eligDate < $ptExpire) {
						$newhtml .= ".  If all promotion requirements are complete prior to this you can be promoted.";
					} else {
						$newhtml .= ", however you will not be eligible to promote prior to this.  ";
						$newhtml .= "You will need to participate in a CPFT event before you are eligible to promote.";
					}
				}
			}
			if ($mlMessage != "" && $reqs['CharDev'] == 1) {
				$newhtml .= "<li>".$mlMessage."</li>";
			}
			if ($ddMessage != "" && $reqs['Drill'] != "No") {
				$newhtml .= "<li>".$ddMessage.$drillLink." to ensure success.</li>";
			}
			if ($oMessage != "") {
				$newhtml .= "<li>".$oMessage."</li>";
			}
			if ($reqs['SDA'] == 1) {
				$newhtml .= "<li>You have an ";
				$newhtml .= "<a target=\"_blank\" href=\"https://www.gocivilairpatrol.com/programs/cadets/library/cadet-staff-duty-analysis\">";
				$newhtml .= "SDA requirement</a> for this promotion.</li>";
			}
			if($newhtml != "<ul>") {
				if(count($CWD) == 1) {
					//if last download date, display with 'incomplete reqs'
					$newhtml .= "</ul></section>";
					$CWDdate = date("d M Y", $CWD[0]['Timestamp']);
					$html .= "<h3 style=\"text-align: center\">Incomplete Promotion Requirements as of ".$CWDdate."</h3>";
					$html .= "<h4 style=\"text-align: center\">Complete these requirements to promote</h4>".$newhtml;
				} else {
					//if no last download date, no 'incomplete reqs' display
					$html .= "</section>";
				}
			} else {
				$html .= "</section>";
			}
			$html .= "<section class=\"halfSection\" style=\"float:right;line-height:1.4em\">".$meetinghtml;

		}
	} else {
		$html .= $meetinghtml."</section>";
		$html .= "<section class=\"halfSection\" style=\"float:right;line-height:1.4em\">";
	}

            // $stmt = $pdo->prepare("SELECT EventNumber FROM ".DB_TABLES['EventInformation']." WHERE MeetDateTime > :now AND (ShowUpcoming = 1 OR Activity LIKE '%Recurring Meeting%') LIMIT :limit;");
            $sqlString = "SELECT EventNumber FROM ".DB_TABLES['EventInformation'];
//need to modify sqlstring based on member in account
            if($l && $a->hasMember($m)) {
                $sqlString .= " WHERE PickupDateTime > :now AND Status!='Draft' AND AccountID = :aid AND ShowUpcoming = 1 ORDER BY MeetDateTime ASC LIMIT :limit;";
            } else {
                $sqlString .= " WHERE PickupDateTime > :now AND Status!='Draft' AND Status!='Private' AND AccountID = :aid AND ShowUpcoming = 1 ORDER BY MeetDateTime ASC LIMIT :limit;";
            }
            $stmt = $pdo->prepare($sqlString);
            $stmt->bindValue(':now', time());
			$stmt->bindValue(':aid', $a->id);
            $stmt->bindValue(':limit', (int)Registry::get('Website.ShowUpcomingEvents'), PDO::PARAM_INT);
            $data = DBUtils::ExecutePDOStatement($stmt);
//            $html .= "<section class=\"halfSection\" style=\"float:right;line-height:1.4em\">";
            if (count($data) > 0) {
//			$params = "time= ".time()." account= ".$a->id." limit= ".(int)Registry::get('Website.ShowUpcomingEvents');
//			SetNotify(546319, "mdx89", "data-count: ".count($data), 0, $params);
                $html .= "<h3 style=\"text-align:center;line-height:initial\">Upcoming Events</h3>";
                foreach ($data as $datum) {
//			SetNotify(546319, "mdx89", "datum-eventnumber: ".$datum['EventNumber'], 0);
                    $e = Event::Get($datum['EventNumber'], $a);
                    if($e->Status == "Cancelled") $html .= "<span style=\"color:red\">";
                    $html .= "<strong>".date('j F', $e->MeetDateTime)."</strong> ";
                    if($e->Status == "Cancelled") $html .= "</span>";
                    $html .= (new Link('eventviewer', $e->EventName, [$e->EventNumber]));
                    if($e->Status == "Cancelled") $html .= " <strong><span style=\"color:red\">Cancelled!</span></strong>";
                    $html .= '<br />';
                }
            } else {
                $html .= "<h3 style=\"text-align:center;line-height:initial\">No Upcoming Events</h3>";
            }
            $html .= "</section>";

            $html .= "<div class=\"divider\"></div>";
            $html .= $leftsection1 . $rightsection1 . "<div class=\"divider\"></div>";

            $count = 0;
            $con = Registry::Get("Contact");
            $count = !!$con->FaceBook ?
                (!!$con->Twitter ? 2 : 1) :
                (!!$con->Twitter ? 1 : 0);

            if ($count > 0) {
                // $html .= "<div class=\"divider\"></div>";
                $class = $count == 2 ? 'halfSection' : 'fullSection';
                if (!!$con->Twitter) {
                    $html .= <<<EOD
<section class="$class">
    <a class="twitter-timeline"
    data-height="600px"
    data-width="100%"
    href="https://twitter.com/$con->Twitter">Tweets by $con->Twitter</a>
</section>
EOD;
                }
                if (!!$con->FaceBook) {
                    $html .= <<<EOD
<section id="facebook-feed" class="$class">
    <div class="fb-page" data-height="600" data-href="https://www.facebook.com/$con->FaceBook/" data-tabs="timeline" data-small-header="false" data-adapt-container-width="true" data-hide-cover="false" data-show-facepile="true"><blockquote cite="https://www.facebook.com/$con->FaceBook/" class="fb-xfbml-parse-ignore"><a href="https://www.facebook.com/$con->FaceBook/">CAP St. Marys</a></blockquote></div>
</section>
EOD;
                }
            }

            $stmt = $pdo->prepare("SELECT `name`, `id` FROM `".DB_TABLES['BlogPages']."` WHERE `parentname` = '' AND AccountID = :aid AND NOT `id` = 'none';");
            global $_ACCOUNT;
            $stmt->bindValue(':aid', $_ACCOUNT->id);
            $links = DB_Utils::ExecutePDOStatement($stmt);
            foreach ($links as $datum) {
                $finallinks[] = [
                    'Type' => 'link',
                    'Target' => '/page/'.$datum['id'],
                    'Text' => $datum['name']
                ];
            }

            return [
                'body' => [
                    'MainBody' => $html,
                    'SideNavigation' => UtilCollection::GenerateSideNavigation($finallinks),
                    'BreadCrumbs' => UtilCollection::GenerateBreadCrumbs([
                        [
                            'Target' => '/',
                            'Text' => 'Home'
                        ]
                    ])
                ]
            ];
        }

        public static function doPost ($e, $c, $l, $m, $a) {
            return [
                'body' => JSSnippet::PageRedirect('search', [], [
                    'query' => urlencode($e['form-data']['search'])
                ])
            ];
        }
    }
