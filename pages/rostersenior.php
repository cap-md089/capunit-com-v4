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

//			if (!$a->paid) {return ['error' => 501];}

			$pdo = DBUtils::CreateConnection();

			$tblAtt = DB_TABLES['Attendance'];
			$tblEvt = DB_TABLES['EventInformation'];

			$sql = 'SELECT CONCAT(Data_Member.NameLast, ", ", Data_Member.NameFirst, " ", ';
			$sql .= 'LEFT(Data_Member.NameMiddle,1)) as Name, ';
			$sql .= 'Data_Member.Rank as Grade, ';
			$sql .= 'Data_Member.CAPID, Data_Member.Expiration ';
			$sql .= 'FROM Data_Member ';
			$sql .= 'WHERE (NOT Data_Member.Type="CADET") AND (Data_Member.Expiration > (UNIX_TIMESTAMP(NOW()) - (60 * 60 * 24 * 90))  ) ';
			$sql .= 'AND Data_Member.ORGID IN ';
			$sql .= "(SELECT UnitID FROM Accounts WHERE AccountID=:aid) ORDER BY Name ASC;";

			$stmt = $pdo->prepare($sql);
			$stmt->bindValue(':aid', $a->id);
			$data = DBUtils::ExecutePDOStatement($stmt);

			$unitSql = "SELECT Accounts.UnitID, Data_Organization.Region, Data_Organization.Wing, ";
			$unitSql .= "Data_Organization.Unit, Data_Organization.Name FROM Accounts ";
			$unitSql .= "INNER JOIN Data_Organization ON Accounts.UnitID=Data_Organization.ORGID WHERE ";
			$unitSql .= "AccountID=:aid ORDER BY Accounts.MainOrg DESC, Accounts.UnitID ASC;";

			$stmt = $pdo->prepare($unitSql);
			$stmt->bindValue(':aid', $a->id);
			$unitData = DBUtils::ExecutePDOStatement($stmt);

			$filename = "SeniorRoster.pdf";
			header('Content-disposition: attachment; filename="'.$filename.'"');
			header("Content-Type: application/pdf");
			$counter=0;

			$pdf = new PDF('P','in',array(8.5,11));
			$pdf->AliasNbPages();
			$pdf->SetTitle("Senior Roster");
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
			$pdf->Cell(0,.3,"Senior Attendance Log",0,1,"C");
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
			}
			$pdf->SetFont('Arial','B',9);
			$pdf->Cell(0,.3,$unitString."    ".$unitName,0,1);


			$wMember = 1.5;  $wGrade = 0.5;  $wCAPID = 0.8;
			$wExpiration = 1.0;  $wSignature = 1.5;  $wFlight = 1.0;
			$cellHeight = 0.2;  $border = 0;  $fillState = false;
			$pdf->Cell($wMember,$cellHeight,"Member",$border,0,"L",$fillState);
			$pdf->Cell($wGrade,$cellHeight,"Grade",$border,0,"L",$fillState);
			$pdf->Cell($wCAPID,$cellHeight,"CAPID",$border,0,"C",$fillState);
			$pdf->Cell($wExpiration,$cellHeight,"Expiration",$border,0,"L",$fillState);
			$pdf->Cell($wSignature,$cellHeight,"Signature",$border,1,"C",$fillState);

			$pdf->SetFont('Arial','',9);
			$pdf->SetFillColor(210);
			$cellHeight = 0.2;  $border = 0;  $fillState = false;
			$alternator=0;
			foreach($data as $datum) {
				if(!$alternator) {
					$fillState = false;
					$alternator = 1;
				} else {
					$fillState = true;
					$alternator = 0;
				}

				$expireDate = date('Y-m-d',$datum['Expiration']);
				$pdf->Cell($wMember,$cellHeight,$datum['Name'],$border,0,"L",$fillState);
				$pdf->Cell($wGrade,$cellHeight,$datum['Grade'],$border,0,"L",$fillState);
				$pdf->Cell($wCAPID,$cellHeight,$datum['CAPID'],$border,0,"C",$fillState);
                                if($datum['Expiration'] <= time()+(60*60*24*30)) {$border = "TBLR";}
                                $pdf->Cell($wExpiration,$cellHeight,$expireDate,$border,0,"L",$fillState);
                                $border = 0;
				$pdf->Cell($wSignature,$cellHeight,"",$border,1,"C",$fillState);
			}

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





