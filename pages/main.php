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
    <h4>How to become an Senior Member (Age 18+)</h4>
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
            <section class="halfSection">
                <div>
            
                </div>
                <h4>How to become an Senior Member (Age 18+)</h4>
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

            $dir = HOST_SUB_DIR;
            
            $ae = new Link ("page", "<img src=\"/{$dir}images/aerospace.png\" /><p style=\"text-align:center;\">Aerospace Education</p>", ['aerospaceeducation']);
            $cp = new Link ("page", "<img style=\"display:block;margin:0 auto;\" src=\"/{$dir}images/programs.png\" /><p style=\"text-align:center;\">Cadet Programs</p>", ['cadetprograms']);
            $es = new Link ("page", "<img style=\"display:block;margin:0 auto;\" src=\"/{$dir}images/emergency.png\" /><p style=\"text-align:center;\">Emergency Services</p>", ['emergencyservices']);

            $rightsection1 = <<<rightsection
<section class="halfSection">
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
            
            $html .= $leftsection1 . $rightsection1 . "<div class=\"divider\"></div>";

            $pdo = DBUtils::CreateConnection();
            $stmt = $pdo->prepare("SELECT EventNumber FROM ".DB_TABLES['EventInformation']." WHERE MeetDateTime > :now AND AccountID = :aid AND Activity LIKE '%Recurring Meeting%' LIMIT 1;");
            $stmt->bindValue (':now', time());
			$stmt->bindValue (':aid', $a->id);
            $event = DBUtils::ExecutePDOStatement($stmt);
			print_r($event);
			echo "\n";
            if (count($event) !== 1) {
                $html .= "<section class=\"halfSection\" style=\"text-align: center\">No upcoming meeting</section>";
            } else {
                $e = Event::Get($event[0]['EventNumber']);
				if (!!$e) {
                $link = new Link('eventviewer', "View details", [$e->EventNumber]);
                $html .= "<section class=\"halfSection\" style=\"text-align: left\">
                	    <h3 style=\"text-align: center\">Next Meeting</h3>
                    	<strong>Time:</strong> ".date('D, d M Y H:i:s', $e->MeetDateTime)."<br />
            	        <strong>Location:</strong> $e->MeetLocation<br />
        	            <strong>Uniform of the Day:</strong> $e->Uniform<br />
    	                $link
	                </section>";
				}
            }

            // $stmt = $pdo->prepare("SELECT EventNumber FROM ".DB_TABLES['EventInformation']." WHERE MeetDateTime > :now AND (ShowUpcoming = 1 OR Activity LIKE '%Recurring Meeting%') LIMIT :limit;");
            $stmt = $pdo->prepare("SELECT EventNumber FROM ".DB_TABLES['EventInformation']." WHERE MeetDateTime > :now AND AccountID = :aid AND ShowUpcoming = 1 ORDER BY MeetDateTime ASC LIMIT :limit;");
            $stmt->bindValue(':now', time());
			$stmt->bindValue(':aid', $a->id);
            $stmt->bindValue(':limit', (int)Registry::get('Website.ShowUpcomingEvents'), PDO::PARAM_INT);
            $data = DBUtils::ExecutePDOStatement($stmt);
            $html .= "<section class=\"halfSection\" style=\"float:right;line-height:1.4em\">";
            $html .= "<h3 style=\"text-align:center;line-height:initial\">Upcoming Events</h3>";
            foreach ($data as $datum) {
                $e = Event::Get($datum['EventNumber']);
                $html .= "<strong>".date('j F', $e->MeetDateTime)."</strong> ".(new Link('eventviewer', $e->EventName, [$e->EventNumber])).'<br />';
            }
            $html .= "</section>";

            $count = 0;
            $con = Registry::Get("Contact");
            $count = !!$con->FaceBook ?
                (!!$con->Twitter ? 2 : 1) :
                (!!$con->Twitter ? 1 : 0);

            if ($count > 0) {
                $html .= "<div class=\"divider\"></div>";
                $class = $count == 2 ? 'halfSection' : 'fullSection';
                if (!!$con->Twitter) {
                    $html .= <<<EOD
<section class="$class">
    <a class="twitter-timeline"
    data-height="600px"
    data-width="100%"
    href="https://twitter.com/$con->Twitter">Tweets by CAPStMarys</a>
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
