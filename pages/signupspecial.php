<?php
	require_once(BASE_DIR."lib/pdf/fpdf.php");

	class PDF extends FPDF {
		// Page footer
		function Footer()
		{
		    // Position at 0.7 in from bottom
		    $this->SetY(-0.6);
		    // Arial italic 8
		    $this->SetFont('Arial','I',8);
		    // Date
		    $myDate = date('Y-m-d');
		    $this->Cell($this->GetStringWidth($myDate),0.2,$myDate,0,0,'L');
		    // Page number
		    $this->Cell(0,0.2,'Page '.$this->PageNo().' of {nb}',0,0,'R');
		}
	}

	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			if (!$l) {
				return ['error' => 411];
			}

			$get = isset($e['uri'][0]);
			if ($get) {
				$event = Event::Get($e['uri'][0]);
			} else {
				$event = false;
			}

			if (!$a->paid) {return ['error' => 501];}

			$pdo = DBUtils::CreateConnection();

			$tblAtt = DB_TABLES['SpecialAttendance'];
			$tblEvt = DB_TABLES['EventInformation'];

			$sql = "Call getSpecialAttendance(:aid, :eid)";
			$stmt = $pdo->prepare($sql);
			$stmt->bindValue(':aid', $a->id);
			$stmt->bindValue(':eid', $event->EventNumber);
			$memberData = DBUtils::ExecutePDOStatement($stmt);

			$unitSql = "SELECT Accounts.UnitID, Data_Organization.Region, Data_Organization.Wing, ";
			$unitSql .= "Data_Organization.Unit, Data_Organization.Name FROM Accounts ";
			$unitSql .= "INNER JOIN Data_Organization ON Accounts.UnitID=Data_Organization.ORGID WHERE ";
			$unitSql .= "AccountID=:aid ORDER BY Accounts.MainOrg DESC, Accounts.UnitID ASC;";

			$stmt = $pdo->prepare($unitSql);
			$stmt->bindValue(':aid', $a->id);
			$unitData = DBUtils::ExecutePDOStatement($stmt);

			//merge sign-in data into member tables
//			$sqlSignin = "SELECT SignInData.* FROM SignInData WHERE SignInData.CAPID NOT IN ";
//			$sqlSignin .= "(SELECT Data_Member.CAPID FROM Data_Member) ";
//			$sqlSignin .= "ORDER BY SignInData.LastAccessTime DESC;";
//			$stmtSignin = $pdo->prepare($sqlSignin);
//			$mergeData = DBUtils::ExecutePDOStatement($stmtSignin);

			$filename = "SignUpRoster.pdf";
			header('Content-disposition: attachment; filename="'.$filename.'"');
			header("Content-Type: application/pdf");
			$counter=0;

			$pdf = new PDF('P','in',array(8.5,11));
			$pdf->AliasNbPages();
			$pdf->SetTitle("Sign-Up Roster");
			$pdf->AddPage();
			$pdf->SetMargins(0.25,0.35,0.25);
			$pdf->SetAutoPageBreak('true',0.5);
			//Set Title font
			$pdf->SetFont('Arial','B',14);
			$pdf->SetTextColor(0);

			$pdf->SetFillColor(0);
			$widthSet = 0.015625;
			$heightBar = .2;
			$heightEnd = .25;
			$horizOffset = 6.5;

			//Page Title
			$pdf->Cell(0,.3,"Event Sign-Up Roster",0,1,"C");
			//Set text font
			$pdf->SetFont('Arial','',11);
			//insert CAP logo in upper left corner
			$imagex=0.3; $imagey=0.4; $imagew=1.2; $imageh=0;
			$pdf->Image(BASE_DIR.'images/CAP_Seal_Monochrome.png',$imagex,$imagey,$imagew,$imageh);

			//Insert spacers and event information
			$spacer = 1.3;  $linespace = 0.25;
			$pdf->Cell($spacer,$linespace,"",0,0);
			$pdf->SetFont('Arial','B',11);
			$pdf->Cell(0.5,$linespace,"Date: ",0,0);
			$pdf->SetFont('Arial','',11);
			$pdf->Cell(1.5,$linespace,date("D M j Y",$event->StartDateTime),0,0);
			$pdf->SetFont('Arial','B',11);
			$title = "Location: ";
			$boxSize = $pdf->GetStringWidth($title)+0.2;
			$pdf->Cell($boxSize,$linespace,$title,0,0);
			$pdf->SetFont('Arial','',11);
			$pdf->Cell(0,$linespace,$event->EventLocation,0,1);
			$pdf->Cell($spacer,$linespace,"",0,0);
			$pdf->SetFont('Arial','B',11);
			$title = "Uniform: ";
			$boxSize = $pdf->GetStringWidth($title)+0.2;
			$pdf->Cell($boxSize,$linespace,$title,0,0);
			$pdf->SetFont('Arial','',11);
			$pdf->MultiCell(0,$linespace,$event->Uniform,0,1);
			$pdf->Cell($spacer,$linespace,"",0,0);
			$pdf->SetFont('Arial','B',11);
			$title = "Activity: ";
			$boxSize = $pdf->GetStringWidth($title)+0.2;
			$pdf->Cell($boxSize,$linespace,$title,0,0);
			$pdf->SetFont('Arial','',11);
			$pdf->Cell(0,$linespace,$event->Activity,0,1);
			$pdf->Cell($spacer,$linespace,"",0,1);


			//Insert unit information
			$firstUnit="";
			$orgs=[];
			foreach($unitData as $units) {
				if(!$firstUnit) {
					$firstUnit=$units['Region']."-".$units['Wing'];
					$unitString=$firstUnit."-".$units['Unit'];
					$unitName = $units['Name'];
				} else {
					if($units['Region']."-".$units['Wing'] == $firstUnit) {
						$unitString .= "/".$units['Unit'];
					} else {
						$unitString .= "/".$units['Region']."-".$units['Wing']."-".$units['Unit'];
					}
					$unitName .= "/".$units['Name'];
				}
				array_push($orgs, $firstUnit."-".$units['Unit']);
			}
			$pdf->SetFont('Arial','B',9);
			$pdf->Cell(0,.3,$unitString."    ".$unitName,0,1);

			$seniorCount=0;
			$cadetCount=0;

			function compareLastFirst($a, $b) {
				return strcmp($a['NameLast'].$a['NameFirst'], $b['NameLast'].$b['NameFirst']);
			}
			usort($memberData, 'compareLastFirst');

			foreach($memberData as $datum) {
				if(substr($datum['MemberRankName'],0,2) == "C/") {$cadetCount++;
				} elseif (substr($datum['MemberRankName'],0,5) == "CADET") {$cadetCount++;
				} else {$seniorCount++;}
			}

			//insert senior member information
			if($seniorCount>0) {
				$titleFontSize = 10;  $textFontSize = 9;
				$s = "";  if($seniorCount>1) {$s = "s";}
				$pdf->SetFont('Arial','B',$titleFontSize);
				$pdf->Cell(0,.3,"[".$seniorCount."] Senior Member".$s,0,1,"C");
				$pdf->SetFont('Arial','B',$textFontSize);
				$wGradeName = 1.7;  $wCAPID = 0.55;  $wUnit = 0.8;
				$wExpiration = 0.7;  $wFlight = 0.5;  $wCell = 0.9;  $wEmail = 1.5;
				$wGeo = 0.9;  $wDuty = 1.4;
				$cellHeight = 0.18;  $border = 0;  $fillState = false;
				$pdf->Cell($wGradeName,$cellHeight,"Member Grade & Name",$border,0,"L",$fillState);
				$pdf->Cell($wCAPID,$cellHeight,"CAPID",$border,0,"C",$fillState);
				$pdf->Cell($wUnit,$cellHeight,"Unit",$border,0,"C",$fillState);
				$pdf->Cell($wGeo,$cellHeight,"Location",$border,0,"L",$fillState);
				$pdf->Cell($wDuty,$cellHeight,"Duty Pref",$border,0,"L",$fillState);
				$pdf->Cell($wCell,$cellHeight,"Best Phone",$border,0,"L",$fillState);
<<<<<<< HEAD
				$pdf->Cell($wEmail,$cellHeight,"Email",$border,1,"L",$fillState);
=======
				$pdf->Cell($wCell,$cellHeight,"Email",$border,0,"L",$fillState);
>>>>>>> 872c9f181aaaf2ea7b8c0e3cc78d962b26898815

				$pdf->SetFont('Arial','',$textFontSize);
				$pdf->SetFillColor(210);
				$cellHeight = 0.18;  $border = 0;  $fillState = false;
				$alternator=0;
<<<<<<< HEAD
				foreach($memberData as $datum) {
=======
				foreach($seniorData as $datum) {
>>>>>>> 872c9f181aaaf2ea7b8c0e3cc78d962b26898815
					$member = Member::Estimate($datum['CAPID']);
					if($member->seniorMember) {
						if(!$alternator) {
							$fillState = false;
							$alternator = 1;
						} else {
							$fillState = true;
							$alternator = 0;
						}

<<<<<<< HEAD
=======
						$expireDate = date('Y-m-d',$datum['Expiration']);
>>>>>>> 872c9f181aaaf2ea7b8c0e3cc78d962b26898815
						$pdf->Cell($wGradeName,$cellHeight,$datum['MemberRankName'],$border,0,"L",$fillState);
						$pdf->Cell($wCAPID,$cellHeight,$datum['CAPID'],$border,0,"C",$fillState);
						$bolder = 'B';
						foreach($orgs as $org) {
<<<<<<< HEAD
							if($member->Squadron==$org) {$bolder = '';}
						}
						$pdf->SetFont('Arial',$bolder,$textFontSize);
						$pdf->Cell($wUnit,$cellHeight,$member->Squadron,$border,0,"C",$fillState);
						$pdf->SetFont('Arial','',$textFontSize);
						$pdf->Cell($wGeo,$cellHeight,$datum['GeoLoc'],$border,0,"L",$fillState);
						$pdf->Cell($wDuty,$cellHeight,$datum['DutyPreference'],$border,0,"L",$fillState);
						$pdf->Cell($wCell,$cellHeight,$member->getBestPhone(),$border,0,"L",$fillState);
						if($pdf->getStringWidth($member->getBestEmail())>$wEmail) {$pdf->SetFont('Arial','',$textFontSize-1);}
						if($pdf->getStringWidth($member->getBestEmail())>$wEmail) {$pdf->SetFont('Arial','',$textFontSize-2);}
						$pdf->Cell($wEmail,$cellHeight,$member->getBestEmail(),$border,1,"L",$fillState);
						$pdf->SetFont('Arial','',$textFontSize);
=======
							if($datum['ORGID']==$org) {$bolder = '';}
						}
						$pdf->SetFont('Arial',$bolder,9);
						$pdf->Cell($wUnit,$cellHeight,$datum['FullUnit'],$border,0,"C",$fillState);
						$pdf->SetFont('Arial','',9);
//						if($datum['Expiration'] <= time()+(60*60*24*30)) {$border = "TBLR";}
//						$pdf->Cell($wExpiration,$cellHeight,$expireDate,$border,0,"L",$fillState);
//						$border = 0;
						$pdf->Cell($wGeo,$cellHeight,$datum['GeoLoc'],$border,0,"L",$fillState);
						$pdf->Cell($wDuty,$cellHeight,$datum['DutyPreference'],$border,0,"L",$fillState);
						$pdf->Cell($wCell,$cellHeight,$member->getBestPhone(),$border,0,"L",$fillState);
						$pdf->Cell($wCell,$cellHeight,$member->getBestEmail(),$border,1,"L",$fillState);
>>>>>>> 872c9f181aaaf2ea7b8c0e3cc78d962b26898815
					}
				}

			}  //end of print senior data section


			$pdf->Cell($spacer,$linespace,"",0,1);
			//insert cadet member information
			if($cadetCount>0) {
				$s = "";  if($cadetCount>1) {$s = "s";}
				$pdf->SetFont('Arial','B',$titleFontSize);
				$pdf->Cell(0,.3,"[".$cadetCount."] Cadet Member".$s,0,1,"C");
				$pdf->SetFont('Arial','B',$textFontSize);
				$wGradeName = 1.7;  $wCAPID = 0.55;  $wUnit = 0.8;
				$wExpiration = 0.7;  $wFlight = 0.5;  $wCell = 0.9;  $wEmail = 1.5;
				$wGeo = 0.9;  $wDuty = 1.4;
				$cellHeight = 0.18;  $border = 0;  $fillState = false;
				$pdf->Cell($wGradeName,$cellHeight,"Member Grade & Name",$border,0,"L",$fillState);
				$pdf->Cell($wCAPID,$cellHeight,"CAPID",$border,0,"C",$fillState);
				$pdf->Cell($wUnit,$cellHeight,"Unit",$border,0,"C",$fillState);
				$pdf->Cell($wGeo,$cellHeight,"Location",$border,0,"L",$fillState);
				$pdf->Cell($wDuty,$cellHeight,"Duty Pref",$border,0,"L",$fillState);
				$pdf->Cell($wCell,$cellHeight,"Best Phone",$border,0,"L",$fillState);
<<<<<<< HEAD
				$pdf->Cell($wEmail,$cellHeight,"Email",$border,1,"L",$fillState);
=======
//				$pdf->Cell($wCell,$cellHeight,"SecCell",$border,0,"L",$fillState);
				$pdf->Cell($wCell,$cellHeight,"Email",$border,1,"L",$fillState);
>>>>>>> 872c9f181aaaf2ea7b8c0e3cc78d962b26898815

				$pdf->SetFont('Arial','',$textFontSize);
				$pdf->SetFillColor(210);
				$cellHeight = 0.18;  $border = 0;  $fillState = false;
				$alternator=0;
<<<<<<< HEAD
				foreach($memberData as $datum) {
=======
				foreach($cadetData as $datum) {
>>>>>>> 872c9f181aaaf2ea7b8c0e3cc78d962b26898815
					$member = Member::Estimate($datum['CAPID']);
					if(!$member->seniorMember) {
						if(!$alternator) {
							$fillState = false;
							$alternator = 1;
						} else {
							$fillState = true;
							$alternator = 0;
						}

<<<<<<< HEAD
//						$expireDate = date('Y-m-d',$datum['Expiration']);
=======
						$expireDate = date('Y-m-d',$datum['Expiration']);
>>>>>>> 872c9f181aaaf2ea7b8c0e3cc78d962b26898815
						$pdf->Cell($wGradeName,$cellHeight,$datum['MemberRankName'],$border,0,"L",$fillState);
						$pdf->Cell($wCAPID,$cellHeight,$datum['CAPID'],$border,0,"C",$fillState);
						$bolder = 'B';
						foreach($orgs as $org) {
<<<<<<< HEAD
							if($member->Squadron==$org) {$bolder = '';}
						}
						$pdf->SetFont('Arial',$bolder,9);
						$pdf->Cell($wUnit,$cellHeight,$member->Squadron,$border,0,"C",$fillState);
						$pdf->SetFont('Arial','',9);
						$pdf->Cell($wGeo,$cellHeight,$datum['GeoLoc'],$border,0,"L",$fillState);
						$pdf->Cell($wDuty,$cellHeight,$datum['DutyPreference'],$border,0,"L",$fillState);
						$pdf->Cell($wCell,$cellHeight,$member->getBestPhone(),$border,0,"L",$fillState);
						if($pdf->getStringWidth($member->getBestEmail())>$wEmail) {$pdf->SetFont('Arial','',$textFontSize-1);}
						if($pdf->getStringWidth($member->getBestEmail())>$wEmail) {$pdf->SetFont('Arial','',$textFontSize-2);}
						$pdf->Cell($wEmail,$cellHeight,$member->getBestEmail(),$border,1,"L",$fillState);
						$pdf->SetFont('Arial','',$textFontSize);
=======
							if($datum['ORGID']==$org) {$bolder = '';}
						}
						$pdf->SetFont('Arial',$bolder,9);
						$pdf->Cell($wUnit,$cellHeight,$datum['FullUnit'],$border,0,"C",$fillState);
						$pdf->SetFont('Arial','',9);
//						if($datum['Expiration'] <= time()+(60*60*24*30)) {$border = "TBLR";}
//						$pdf->Cell($wExpiration,$cellHeight,$expireDate,$border,0,"L",$fillState);
//						$border = 0;
//						$pdf->Cell($wFlight,$cellHeight,$datum['Flight'],$border,0,"L",$fillState);
						$pdf->Cell($wGeo,$cellHeight,$datum['GeoLoc'],$border,0,"L",$fillState);
						$pdf->Cell($wDuty,$cellHeight,$datum['DutyPreference'],$border,0,"L",$fillState);
						$pdf->Cell($wCell,$cellHeight,$member->getBestPhone(),$border,0,"L",$fillState);
						$pdf->Cell($wCell,$cellHeight,$member->getBestEmail(),$border,1,"L",$fillState);
>>>>>>> 872c9f181aaaf2ea7b8c0e3cc78d962b26898815
					}
				}

			}  //end of print cadet data section

			$pdf->Cell($spacer,$linespace,"",0,1);

			$pdf->SetFont('Arial','B',10);
			$pdf->Cell(0,.25,"Guest Sign-in (Please Print)",0,1,"L",false);
			$pdf->Cell(2,.25,"Name","RLTB",0,"L",false);
			$pdf->Cell(2,.25,"Phone","RLTB",0,"L",false);
			$pdf->Cell(2,.25,"Email","RLTB",0,"L",false);
			$pdf->Cell(2,.25,"Sponsor Name","RLTB",1,"L",false);

			for($i = 0; $i <= 6; $i++) {
				if($i % 2 == 0) {
					$pdf->Cell(8,.3,"","RLTB",1,"L",true);
				} else {
					$pdf->Cell(8,.3,"","RLTB",1,"L",false);
				}
			}

			$pdf->Output();
			exit(0);
		}
	}





