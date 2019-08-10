<?php
    class Output {
        public static function doGet ($e, $c, $l, $m, $a) {
			if (!$a->paid) {return ['error' => 501];}
            $html = '';
            $pdo = DBUtils::CreateConnection();

            $stmt = $pdo->prepare("SELECT TeamID FROM ".DB_TABLES['Team']." WHERE AccountID=:aid;");
            $stmt->bindValue(':aid', $a->id);

            $data = DBUtils::ExecutePDOStatement($stmt);

            foreach ($data as $datum) {
                $team = Team::Get($datum['TeamID']);
                $str = "<h2 class=\"title\">$team->Name";
                if ($l && ($m->hasPermission('EditTeam') || $team->isLeader($m))) {
                    $str .= " (Team ID: $team->ID)</h3>";
                    $str .= new Link("teammodify", "Modify team", [$team->ID])." | ".(new AsyncButton("teamdelete", "Delete Team", 'alertReload'))->getHtml($team->ID);
                } else {
                    $str .= "</h3>";
                }
                $str .= "<p>$team->Description</p>";
				$emails = '';
                if ($team->Coach !== 0) {
                    $coach = Member::Estimate($team->Coach);
                    if ($coach) {
                        $str .= "Team Coach: ".$coach->RankName." (".$coach->getBestEmail().")<br />";
						$emails .= $coach->getBestEmail()."; ";
						$_email = $coach->getBestContact(['CADETPARENTEMAIL']);
						if (isset($_email) && $_email) {
							$emails .= "$_email; ";
						}
                    }
                }
                if ($team->Mentor !== 0) {
                    $mentor = Member::Estimate($team->Mentor);
                    if ($mentor) {
                        $str .= "Team Mentor: ".$mentor->RankName." (".$mentor->getBestEmail().")<br /><br />";
						$emails .= $mentor->getBestEmail()."; ";
						$_email = $mentor->getBestContact(['CADETPARENTEMAIL']);
						if (isset($_email) && $_email) {
							$emails .= "$_email; ";
						}
                    }
                }
				if ($team->Lead !== 0) {
                	$lead = (Member::Estimate($team->Lead));
                	if ($lead) {
						$str .= "Team Leader: ".$lead->RankName." (".$lead->getBestEmail().")<br />";
						$emails .= $lead->getBestEmail()."; ";
						$_email = $lead->getBestContact(['CADETPARENTEMAIL']);
						if (isset($_email) && $_email) {
							$emails .= "$_email; ";
						}
					}
                }
				foreach ($team->Members as $mem => $role) {
					$mem = Member::Estimate($mem);
					if ($mem != false) {
						$str .= "$role: {$mem->RankName}<br />";
					}
                }
                $html .= $str;

                if ($l && $team->isLeader($m)) {
                    $flightmembers = [];
                    foreach ($team->Members as $id => $role) {
                        if (!isset($flightmembers[$id])) {
                            $mem = Member::Estimate($id);
                            $flightmembers[$id] = [
                                'RankName' => $mem->RankName,
                                'Contact' => []
                            ];
                        }
                    }
                    $stmt = $pdo->prepare("SELECT MbrContact.CAPID, MbrContact.Contact, MbrContact.Type, MbrContact.Priority, MbrContact.DoNotContact FROM ".DB_TABLES['MemberContact']." AS MbrContact INNER JOIN ".DB_TABLES['TeamMembers']." AS TeamMembers ON MbrContact.CAPID = TeamMembers.CAPID WHERE (MbrContact.`Type` LIKE '%PHONE%' OR MbrContact.`Type` LIKE '%EMAIL') AND MbrContact.DoNotContact = 0 AND TeamMembers.TeamID = :teamid;");
                    $stmt->bindParam(':teamid', $team->ID);
                    $cdata = DB_Utils::ExecutePDOStatement($stmt);
                    foreach ($cdata as $datum) {
                        $flightmembers[$datum['CAPID']]['Contact'][] = [
                            'Contact' => $datum['Contact'],
                            'Priority' => $datum['Priority'],
                            'Type' => $datum['Type'],
                            'DoNotContact' => $datum['DoNotContact'] == 1
                        ];
                    }
                    $elist = [];
                    $dl = new DetailedListPlus();
                    foreach ($flightmembers as $mem) {
                        $phones = '';
                        if (count($mem['Contact']) == 0) {
                            $phones = 'Sorry, this person does not have any listed phone numbers';
                        } else {
                            usort($mem['Contact'], function ($a, $b) {
                                return strcmp($a['Contact'], $b['Contact']);
                            });
                        }
                        $rbutt = new AsyncButton(Null, 'Remove', 'contactViewerAddToEmailList');
                        $abutt = new AsyncButton(Null, 'Add', 'contactViewerAddToEmailList');
                        for ($i = 0; $i < count($mem['Contact']); $i++) {
                            $cont = $mem['Contact'][$i]['Contact'];
                            if (!$mem['Contact'][$i]['DoNotContact']) {
								$cont2 = str_replace(['(', ' ', ')', '-'], '', $cont);
                                if (is_numeric($cont2)) {
									$cont = $cont2;
                                    $phones .= '('.substr($cont, 0, 3).') '.substr($cont, 3, 3).'-'.substr($cont, 6, 4).' ('.strtoupper($mem['Contact'][$i]['Priority'] . ' '.$mem['Contact'][$i]['Type']).')<br />';
                                } else {
                                    if (!in_array($cont, $elist)) {
                                        $elist[] = $cont;
                                        $emails .= $cont.'; ';
                                        $phones .= $cont . ' ('.strtoupper($mem['Contact'][$i]['Priority']) . ' '. $mem['Contact'][$i]['Type'] .')<br />';
                                    } else {
                                        $phones .= $cont . ' ('.strtoupper($mem['Contact'][$i]['Priority']) . ' '. $mem['Contact'][$i]['Type'] .')<br />';
                                    }
                                }
                            }
                        }
                        $dl->addElement($mem['RankName'], $phones);
                    }
                    $emails = rtrim($emails, '; ');
					$emails = explode('; ', $emails);
					$emails = array_unique($emails);
					$emails = implode('; ', $emails);
                    $html .= '<div style="margin:10px;font-style:italic;" id="emailList">'.$emails.'</div>';
                    $html .= $dl; 
                }
            }


            return [
                'body' => [
                    'MainBody' => $html,
                    'BreadCrumbs' => UtilCollection::GenerateBreadCrumbs([
                        [
							'Target' => '/',
							'Text' => 'Home'
						],
						[
							'Target' => '/teamlist',
							'Text' => 'View teams'
						]
                    ])
                ],
                'title' => 'Teams'
            ];
        }
    }
