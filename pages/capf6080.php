<?php
	require_once(BASE_DIR."lib/pdf/fpdf.php");

	class PDF extends FPDF {
		// Page footer
		function Footer()
		{
			// Position at 0.7 in from bottom
			$this->SetY(-0.6);
			// Arial bold 8
			$this->SetFont('Arial','B',8);
			$formNumber = "CAP Form 60-80  Feb 2018   ";
			$this->Cell($this->GetStringWidth($formNumber),0.2,$formNumber,0,0,'L');
			// Arial regular 8
			$this->SetFont('Arial','',8);
			$formNumber = "(Local version)";
			$this->Cell($this->GetStringWidth($formNumber),0.2,$formNumber,0,0,'L');
			// Arial italic 8
			$this->SetFont('Arial','I',8);
			// Date
			$myDate = date('Y-m-d');
			$this->Cell(0,0.2,$myDate,0,0,'R');
		}
	}

	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			if (!$l) {return ['error' => 411];}

			$get = isset($e['uri'][0]);
			if ($get) {
				$event = Event::Get($e['uri'][0]);
			} else {
				$event = false;
			}

			$filename = "CAPF6080.pdf";
			header('Content-disposition: attachment; filename="'.$filename.'"');
			header("Content-Type: application/pdf");
			$counter=0;

			$pdf = new PDF('P','in',array(8.5,11));
			$checkHeight = new PDF('P','in',array(8.5,11));
			$pdf->SetMargins(0.25,0.35,0.25);
			$checkHeight->SetMargins(0.25,0,0.25);
			$pdf->AliasNbPages();
			$pdf->SetTitle("CAPF 60-80");
			$pdf->AddPage();
			$checkHeight->AddPage();
			$checkHeight->SetFont('Arial','',8);
			$pdf->SetAutoPageBreak('true',0.5);
			$pdf->SetTextColor(0);
			$pdf->SetFillColor(0);

			//draw the lines down the page and across the bottom
			$pdf->Line(0.25,0.35,0.25,10.4);
			$pdf->Line(8.25,0.35,8.25,10.4);
			$pdf->Line(0.25,10.4,8.25,10.4);

			$lineHeight = 0.17;

			if(substr($m->memberRank,0,2) == "C/") {
				$textRankName = $m->RankName."  ";
				$textCAPID = $m->uname."  ";
				$textSquadron = $m->Squadron."  ";
			} else {
				$textRankName = "                                                                                     ";
				$textCAPID = " ";
				$textSquadron = "                       ";
			}

			//Page Title
			$pdf->SetFont('Arial','B',14);
			$pdf->Cell(0,.3,"CIVIL AIR PATROL CADET ACTIVITY PERMISSION SLIP","T",1,"C");
			//Set text font
			$pdf->SetFont('Arial','',8);
			$pdf->Cell(0,.15,"SUGGESTED BEST PRACTICE for LOCAL \"WEEKEND\" ACTIVITIES:","",1,"C");
			$pdf->Cell(0,.15,"Announce the activity at least 2 weeks in advance and require participating cadets to sign-up via this form 1 week prior to the event",0,1,"C");
			$pdf->SetFont('Arial','',4);
			$pdf->Cell(0,.08,"","",1,"C");

			$pdf->SetFont('Arial','B',12);
			$pdf->Cell(0,.25,"1. INFORMATION on the PARTICIPATING CADET","T",1,"C");
			$pdf->SetFont('Arial','B',10);
			$textString = "Cadet Grade & Name: ";
			$strlen = $pdf->GetStringWidth($textString);
			$pdf->Cell($strlen,$lineHeight,$textString,0,0,"L");
			$pdf->SetFont('Arial','',10);
			$textString = $textRankName;
			$strlen = $pdf->GetStringWidth($textString);
			$pdf->Cell($strlen,$lineHeight,$textString,0,0,"L");
			$pdf->SetFont('Arial','B',10);
			$textString = "CAPID: ";
			$strlen = $pdf->GetStringWidth($textString);
			$pdf->Cell($strlen,$lineHeight,$textString,0,0,"L");
			$pdf->SetFont('Arial','',10);
			$textString = $textCAPID;
			$strlen = $pdf->GetStringWidth($textString);
			$pdf->Cell(0,$lineHeight,$textString,0,1,"L");

			$pdf->SetFont('Arial','B',10);
			$textString = "Unit Charter Nuber: ";
			$strlen = $pdf->GetStringWidth($textString);
			$pdf->Cell($strlen,$lineHeight,$textString,0,0,"L");
			$pdf->SetFont('Arial','',10);
			$textString = $textSquadron;
			$strlen = $pdf->GetStringWidth($textString);
			$pdf->Cell($strlen,$lineHeight,$textString,0,0,"L");
			$pdf->SetFont('Arial','B',10);
			$textString = "Activity Name: ";
			$strlen = $pdf->GetStringWidth($textString);
			$pdf->Cell($strlen,$lineHeight,$textString,0,0,"L");
			$pdf->SetFont('Arial','',10);
			$textString = $event->EventName."  ";
			$strlen = $pdf->GetStringWidth($textString);
			$pdf->Cell($strlen,$lineHeight,$textString,0,0,"L");
			$pdf->SetFont('Arial','B',10);
			$textString = "Activity Date: ";
			$strlen = $pdf->GetStringWidth($textString);
			$pdf->Cell($strlen,$lineHeight,$textString,0,0,"L");
			$pdf->SetFont('Arial','',10);
			$textString = date("d M Y", $event->StartDateTime)."  ";
			$strlen = $pdf->GetStringWidth($textString);
			$pdf->Cell(0,$lineHeight,$textString,0,1,"L");
			$pdf->SetFont('Arial','',4);
			$pdf->Cell(0,.08,"",0,1,"C");

			$pdf->SetFont('Arial','B',12);
			$pdf->Cell(0,.3,"2. INFORMATION about the ACTIVITY","T",1,"C");
			$pdf->SetFont('Arial','',10);
			$textString = "For hotel-based activity or conference";
			$pdf->Cell(3.8,$lineHeight,$textString,0,0,"L");
			$pdf->Cell(0,$lineHeight,$textString,0,1,"L");
			$pdf->SetFont('Arial','B',10);
			$textString = "Grade & Name of Supervising Senior: ";
			$pdf->Cell(3.8,$lineHeight,$textString,0,0,"L");
			$pdf->Cell(0,$lineHeight,$textString,0,1,"L");
			$pdf->SetFont('Arial','',4);
			$pdf->Cell(0,.08,"",0,1,"C");

			$pdf->SetFont('Arial','B',12);
			$pdf->Cell(0,.25,"3. PARENT's or GUARDIAN's CONTACT INFORMATION","T",1,"C");
			$pdf->SetFont('Arial','B',10);
			$textString = "Parent or";
			$pdf->Cell(2.6,$lineHeight,$textString,0,0,"L");
			$textString = "Relationship";
			$pdf->Cell(2.6,$lineHeight,$textString,0,0,"L");
			$textString = "Contact Number on";
			$pdf->Cell(0,$lineHeight,$textString,0,1,"L");
			$textString = "Guardian Name:";
			$pdf->Cell(2.6,$lineHeight,$textString,0,0,"L");
			$textString = "to Cadet:";
			$pdf->Cell(2.6,$lineHeight,$textString,0,0,"L");
			$textString = "Date(s) of Activity:";
			$pdf->Cell(0,$lineHeight,$textString,0,1,"L");
			$pdf->SetFont('Arial','',4);
			$pdf->Cell(0,.08,"",0,1,"C");

			$pdf->SetFont('Arial','B',12);
			$pdf->Cell(0,.25,"4. OTHER DOCUMENTS REQUIRED to PARTICIPATE","T",1,"C");
			$pdf->SetFont('Arial','',8);

			$tabstop = 3.7;
			$backspace = 0.1; //used to back up the freetext form names to accommodate for the checkbox

			$pdf->Cell(0,.15,"Check those that apply and attach with this form",0,1,"C");
			$pdf->Cell(.1,$lineHeight,"",0,0);
			if(strstr($event->RequiredForms, "CAPF 31")) {$checked="X";} else {$checked="";}
			$pdf->Cell(.12,.12,$checked,"LRTB",0);
			$pdf->Cell(.12,.12,"",0,0);
			$pdf->SetFont('Arial','B',8);
			$textString = "CAPF 31 ";
			$pdf->Cell(0.6,$lineHeight,$textString,0,0,"L");
			$pdf->SetFont('Arial','',8);
			$textString = "Application for Special Activity";
			$pdf->Cell($tabstop,$lineHeight,$textString,0,0,"L");

			$pdf->SetX($tabstop);
			if(strstr($event->RequiredForms, "CAPF 160")) {$checked="X";} else {$checked="";}
			$pdf->Cell(.12,.12,$checked,"LRTB",0);
			$pdf->Cell(.12,.12,"",0,0);
			$pdf->SetFont('Arial','B',8);
			$textString = "CAPF 160 ";
			$pdf->Cell(0.6,$lineHeight,$textString,0,0,"L");
			$pdf->SetFont('Arial','',8);
			$textString = "CAP Member Health History Form";
			$pdf->Cell(0,$lineHeight,$textString,0,1,"L");


			$pdf->Cell(.1,$lineHeight,"",0,0);
			if(strstr($event->RequiredForms, "CAPF 161")) {$checked="X";} else {$checked="";}
			$pdf->Cell(.12,.12,$checked,"LRTB",0);
			$pdf->Cell(.12,.12,"",0,0);
			$pdf->SetFont('Arial','B',8);
			$textString = "CAPF 161 ";
			$pdf->Cell(0.6,$lineHeight,$textString,0,0,"L");
			$pdf->SetFont('Arial','',8);
			$textString = "Emergency Information";
			$pdf->Cell($tabstop,$lineHeight,$textString,0,0,"L");

			$pdf->SetX($tabstop);
			//check for 'other' by deleting from string all listed forms, then if there is any text left, it is 'other'
			$formString = $event->RequiredForms;
			$formArray = [
				"CAP Identification Card, ",
				"CAPF 31 Application For CAP Encampment Or Special Activity, ",
				"CAPF 60-80 Civil Air Patrol Cadet Activity Permission Slip, ",
				"CAPF 101 Specialty Qualification Card, ",
				"CAPF 160 CAP Member Health History Form, ",
				"CAPF 161 Emergency Information, ",
				"CAPF 163 Permission For Provision Of Minor Cadet Over-The-Counter Medication, ",
				"CAP Identification Card",
				"CAPF 31 Application For CAP Encampment Or Special Activity",
				"CAPF 60-80 Civil Air Patrol Cadet Activity Permission Slip",
				"CAPF 101 Specialty Qualification Card",
				"CAPF 160 CAP Member Health History Form",
				"CAPF 161 Emergency Information",
				"CAPF 163 Permission For Provision Of Minor Cadet Over-The-Counter Medication"];
			foreach($formArray as $formName) {
				if(strstr($formString, $formName)) {$formString = substr($formString, strlen($formName));}
			}
			if(strlen($formString)>1) {$checked="X";} else {$checked="";}
			$pdf->Cell(.12,.12,$checked,"LRTB",0);
			$pdf->Cell(.12,.12,"",0,0);
			$pdf->SetFont('Arial','',8);
			$textString = "Other / Special Local Forms (specify, 2 lines maximum)";
			$pdf->Cell(0,$lineHeight,$textString,0,1,"L");

			$pdf->Cell(.1,$lineHeight,"",0,0);
			if(strstr($event->RequiredForms, "CAPF 163")) {$checked="X";} else {$checked="";}
			$pdf->Cell(.12,.12,$checked,"LRTB",0);
			$pdf->Cell(.12,.12,"",0,0);
			$pdf->SetFont('Arial','B',8);
			$textString = "CAPF 163 ";
			$pdf->Cell(0.6,.18,$textString,0,0,"L");
			$pdf->SetFont('Arial','',8);
			$textString = "Provision of Minor Over the Counter Medication";
			$pdf->Cell($tabstop-$backspace,$lineHeight,$textString,0,0,"L");

			$pdf->SetX($tabstop-$backspace);

			$checkHeight->SetXY($tabstop-$backspace,0);
			$textString = $formString;
//				$textString = "This forces a MultiCell wrap to many lines in an effort to drive the comments block to page 2.  I will also modify the previous page information block to be much larger than normal to force this text change as well.  We will see what the outcome looks like.";
			$charCount = strlen($textString);
			$checkHeight->SetFont('Arial','',8);
			$checkHeight->MultiCell(0,$lineHeight,$textString,0,"L");
			$maxLines = 2;
			while($checkHeight->GetY() > ($lineHeight * $maxLines)) {
				$textString = substr($textString, 0, $charCount-1);
				$charCount = strlen($textString);
				$checkHeight->SetXY($tabstop-$backspace,0);
				$checkHeight->MultiCell(0,$lineHeight,$textString,0,"L");
			}
			$pdf->MultiCell(0,$lineHeight,$textString,0,"L");

			$pdf->SetFont('Arial','',4);
			$pdf->Cell(0,.1,"",0,1,"C");

			$pdf->SetFont('Arial','B',12);
			$pdf->Cell(0,.3,"5. PARENT's or GUARDIAN's AUTHORIZATION","T",1,"C");
			$pdf->SetFont('Arial','',8);
			$pdf->Cell(0,.15,"Cadets who have reached the age of majority, write \"N.A.\"",0,1,"C");

			$pdf->SetFont('Arial','B',10);
			$textString = "I authorize my cadet to participate";
			$pdf->Cell(2.6,.18,$textString,0,0,"L");
			$textString = "Signature:";
			$pdf->Cell(3.6,.18,$textString,0,0,"L");
			$textString = "Date:";
			$pdf->Cell(0,.18,$textString,0,1,"L");
			$textString = "in the activity described above.";
			$pdf->Cell(0,.18,$textString,0,0,"L");
			$pdf->SetFont('Arial','',8);
			$pdf->Cell(0,.18,"",0,1,"C");
			$pdf->SetFont('Arial','',8);
			$pdf->Cell(0,.15,"Disposition: Units may discard this completed form when the activity concludes.",0,1,"C");

			$pdf->SetFont('Arial','',4);
			$pdf->Cell(0,.1,"",0,1,"C");

			$pdf->SetFont('Arial','B',8);
			$textString = "Please detach on the dashed line.  The upper portion is for CAP and the lower portion is for the parent's or Guardian's reference.";
			$pdf->Cell(0,.2,$textString,"T",1,"C");
			$pdf->Cell(0,.1,"",0,1,"C");

			$thisY = $pdf->GetY();
			$startX = .5;
			$endX = 8.25;
			$lineLength = .35;
			$dashSpace = .25;
			for($i = $startX; $i < ($endX - $lineLength); $i += ($lineLength + $dashSpace))
			{
				$pdf->Line($i, $thisY, $i + $lineLength, $thisY);
			}

			$pdf->SetFont('Arial','B',12);
			$pdf->Cell(0,.3,"6. HELPFUL INFORMATION for PARENTS & GUARDIANS",0,1,"C");
			$pdf->SetFont('Arial','',8);
			$pdf->Cell(0,.15,"To be completed by the cadet with assistance from local leaders or activity hosts",0,1,"C");
			$pdf->Cell(0,.12,"",0,1,"C");

			$colStart = 4.25;
			$lineHeight = 0.18;
			$horizBuffer = 0.05;

			$pdo = DBUtils::CreateConnection();
			$unitSql = "SELECT Accounts.UnitID, Data_Organization.Region, Data_Organization.Wing, ";
			$unitSql .= "Data_Organization.Unit, Data_Organization.Name FROM Accounts ";
			$unitSql .= "INNER JOIN Data_Organization ON Accounts.UnitID=Data_Organization.ORGID WHERE ";
			$unitSql .= "AccountID=:aid ORDER BY Accounts.MainOrg DESC, Accounts.UnitID ASC;";

			$stmt = $pdo->prepare($unitSql);
			$stmt->bindValue(':aid', $a->id);
			$unitData = DBUtils::ExecutePDOStatement($stmt);

			$eventPrefix = $unitData[0]['Wing']."-".$unitData[0]['Unit']."-";

			$resetY = $pdf->GetY();

			$pdf->SetX($colStart);
			$pdf->SetFont('Arial','B',10);
			$textString = "Event Number: ";
			$strlen = $pdf->GetStringWidth($textString);
			$pdf->Cell($strlen+$horizBuffer,$lineHeight,$textString,0,0,"L");
			$pdf->SetFont('Arial','',10);
			$textString = $eventPrefix.$event->EventNumber."  ";
			$pdf->Cell(0,$lineHeight,$textString,0,1,"L");

			$pdf->SetX($colStart);
			$pdf->SetFont('Arial','B',10);
			$textString = "Event Status: ";
			$strlen = $pdf->GetStringWidth($textString);
			$pdf->Cell($strlen+$horizBuffer,$lineHeight,$textString,0,0,"L");
			$pdf->SetFont('Arial','',10);
			$textString = $event->Status;
			$pdf->Cell(0,$lineHeight,$textString,0,1,"L");

			$pdf->SetX($colStart);
			$pdf->SetFont('Arial','B',10);
			$textString = "Event Name: ";
			$strlen = $pdf->GetStringWidth($textString);
			$pdf->Cell($strlen+$horizBuffer,$lineHeight,$textString,0,0,"L");
			$pdf->SetFont('Arial','',10);
			$textString = $event->EventName."  ";
			$pdf->Cell(0,$lineHeight,$textString,0,1,"L");

			$dateAlign = 1.5;

			$pdf->SetX($colStart);
			$pdf->SetFont('Arial','B',10);
			$textString = "Meet Date Time: ";
			$strlen = $pdf->GetStringWidth($textString);
			$pdf->Cell($strlen+$horizBuffer,$lineHeight,$textString,0,0,"L");
//			$pdf->SetFont('Arial','',10);
			$textString = date('d M Y, H:i',$event->MeetDateTime);
			$pdf->SetX($colStart+$dateAlign);
			$pdf->Cell(0,$lineHeight,$textString,0,1,"L");

			$pdf->SetX($colStart);
			$pdf->SetFont('Arial','B',10);
			$textString = "Meet Location: ";
			$strlen = $pdf->GetStringWidth($textString);
			$pdf->Cell($strlen+$horizBuffer,$lineHeight,$textString,0,0,"L");
			$checkHeight->SetXY($colStart+$strlen+$horizBuffer,0);
			$pdf->SetFont('Arial','',10);
			$checkHeight->SetFont('Arial','',10);
			$textString = $event->MeetLocation."  ";
			$charCount = strlen($textString);
			$checkHeight->MultiCell(0,$lineHeight,$textString,0,"L");
			$maxLines = 2;
			while($checkHeight->GetY() > ($lineHeight * $maxLines)) {
				$textString = substr($textString, 0, $charCount-1);
				$charCount = strlen($textString);
				$checkHeight->SetXY($colStart+$strlen+$horizBuffer,0);
				$checkHeight->MultiCell(0,$lineHeight,$textString,0,"L");
			}
			$pdf->MultiCell(0,$lineHeight,$textString,0,"L");

			$pdf->SetX($colStart);
			$pdf->SetFont('Arial','B',10);
			$textString = "Start Date Time: ";
			$strlen = $pdf->GetStringWidth($textString);
			$pdf->Cell($strlen+$horizBuffer,$lineHeight,$textString,0,0,"L");
//			$pdf->SetFont('Arial','',10);
			$textString = date('d M Y, H:i',$event->StartDateTime);
			$pdf->SetX($colStart+$dateAlign);
			$pdf->Cell(0,$lineHeight,$textString,0,1,"L");

			$pdf->SetX($colStart);
			$pdf->SetFont('Arial','B',10);
			$textString = "Event Location: ";
			$strlen = $pdf->GetStringWidth($textString);
			$pdf->Cell($strlen+$horizBuffer,$lineHeight,$textString,0,0,"L");
			$checkHeight->SetXY($colStart+$strlen+$horizBuffer,0);
			$pdf->SetFont('Arial','',10);
			$textString = $event->EventLocation."  ";
			$charCount = strlen($textString);
			$checkHeight->MultiCell(0,$lineHeight,$textString,0,"L");
			$maxLines = 2;
			while($checkHeight->GetY() > ($lineHeight * $maxLines)) {
				$textString = substr($textString, 0, $charCount-1);
				$charCount = strlen($textString);
				$checkHeight->SetXY($colStart+$strlen+$horizBuffer,0);
				$checkHeight->MultiCell(0,$lineHeight,$textString,0,"L");
			}
			$pdf->MultiCell(0,$lineHeight,$textString,0,"L");

			$pdf->SetX($colStart);
			$pdf->SetFont('Arial','B',10);
			$textString = "End Date Time: ";
			$strlen = $pdf->GetStringWidth($textString);
			$pdf->Cell($strlen+$horizBuffer,$lineHeight,$textString,0,0,"L");
//			$pdf->SetFont('Arial','',10);
			$textString = date('d M Y, H:i',$event->EndDateTime);
			$pdf->SetX($colStart+$dateAlign);
			$pdf->Cell(0,$lineHeight,$textString,0,1,"L");

			$pdf->SetX($colStart);
			$pdf->SetFont('Arial','B',10);
			$textString = "Pickup Date Time: ";
			$strlen = $pdf->GetStringWidth($textString);
			$pdf->Cell($strlen+$horizBuffer,$lineHeight,$textString,0,0,"L");
//			$pdf->SetFont('Arial','',10);
			$textString = date('d M Y, H:i',$event->PickupDateTime);
			$pdf->SetX($colStart+$dateAlign);
			$pdf->Cell(0,$lineHeight,$textString,0,1,"L");

			$pdf->SetX($colStart);
			$pdf->SetFont('Arial','B',10);
			$textString = "Pickup Location: ";
			$strlen = $pdf->GetStringWidth($textString);
			$pdf->Cell($strlen+$horizBuffer,$lineHeight,$textString,0,0,"L");
			$checkHeight->SetXY($colStart+$strlen+$horizBuffer,0);
			$pdf->SetFont('Arial','',10);
			$textString = $event->PickupLocation."  ";
			$charCount = strlen($textString);
			$checkHeight->MultiCell(0,$lineHeight,$textString,0,"L");
			$maxLines = 2;
			while($checkHeight->GetY() > ($lineHeight * $maxLines)) {
				$textString = substr($textString, 0, $charCount-1);
				$charCount = strlen($textString);
				$checkHeight->SetXY($colStart+$strlen+$horizBuffer,0);
				$checkHeight->MultiCell(0,$lineHeight,$textString,0,"L");
			}
			$pdf->MultiCell(0,$lineHeight,$textString,0,"L");

			$pdf->SetX($colStart);
			$pdf->SetFont('Arial','B',10);
			$textString = "Transportation Provided: ";
			$strlen = $pdf->GetStringWidth($textString);
			$pdf->Cell($strlen+$horizBuffer,$lineHeight,$textString,0,0,"L");
			$pdf->SetFont('Arial','',10);
			if($event->TransportationProvided == 1) {
				$textString = "Yes ";
			} else {
				$textString = "No ";
			}
			$pdf->Cell(0,$lineHeight,$textString,0,1,"L");

			if($event->RegistrationDeadline > 0) {
				$pdf->SetX($colStart);
				$pdf->SetFont('Arial','B',10);
				$textString = "Registration Deadline: ";
				$strlen = $pdf->GetStringWidth($textString);
				$pdf->Cell($strlen+$horizBuffer,$lineHeight,$textString,0,0,"L");
				$pdf->SetFont('Arial','',10);
				$textString = date('d M Y, H:i',$event->RegistrationDeadline);
				$strlen = $pdf->GetStringWidth($textString);
				if($textString == 0) {$textString = "";}
				$pdf->Cell(0,$lineHeight,$textString,0,1,"L");
			}

			if($event->ParticipationFeeDue > 0) {
				$pdf->SetX($colStart);
				$pdf->SetFont('Arial','B',10);
				$textString = "Fee Deadline: ";
				$strlen = $pdf->GetStringWidth($textString);
				$pdf->Cell($strlen+$horizBuffer,$lineHeight,$textString,0,0,"L");
				$pdf->SetFont('Arial','',10);
				$textString = date('d M Y, H:i',$event->ParticipationFeeDue);
				$strlen = $pdf->GetStringWidth($textString);
				if($textString == 0) {$textString = "";}
				$pdf->Cell($strlen+$horizBuffer,$lineHeight,$textString,0,0,"L");

				$pdf->SetFont('Arial','B',10);
				$textString = "Fee: ";
				$strlen = $pdf->GetStringWidth($textString);
				$pdf->Cell($strlen+$horizBuffer,$lineHeight,$textString,0,0,"L");
				$pdf->SetFont('Arial','',10);
				$textString = $event->ParticipationFee;
				$pdf->Cell(0,$lineHeight,$textString,0,1,"L");
			}

			if($event->CAPPOC1ID) {
				$pdf->SetX($colStart);
				$pdf->SetFont('Arial','B',10);
				$textString = "1st POC: ";
				$strlen = $pdf->GetStringWidth($textString);
				$pdf->Cell($strlen+$horizBuffer,$lineHeight,$textString,0,0,"L");
				$pdf->SetFont('Arial','',10);
				$checkHeight->SetXY($colStart+$strlen+$horizBuffer,0);
				$checkHeight->SetFont('Arial','',10);
				$textString = $event->CAPPOC1Name.": ".$event->CAPPOC1Phone.", ".$event->CAPPOC1Email;

				$charCount = strlen($textString);
				$checkHeight->MultiCell(0,$lineHeight,$textString,0,"L");
				$maxLines = 2;
				while($checkHeight->GetY() > ($lineHeight * $maxLines)) {
					$textString = substr($textString, 0, $charCount-1);
					$charCount = strlen($textString);
					$checkHeight->SetXY($colStart+$strlen+$horizBuffer,0);
					$checkHeight->MultiCell(0,$lineHeight,$textString,0,"L");
				}
				$pdf->MultiCell(0,$lineHeight,$textString,0,"L");
			}

			if($event->CAPPOC2ID) {
				$pdf->SetX($colStart);
				$pdf->SetFont('Arial','B',10);
				$textString = "2nd POC: ";
				$strlen = $pdf->GetStringWidth($textString);
				$pdf->Cell($strlen+.05,.18,$textString,0,0,"L");
				$pdf->SetFont('Arial','',10);
				$checkHeight->SetXY($colStart+$strlen+$horizBuffer,0);
				$checkHeight->SetFont('Arial','',10);
				$textString = $event->CAPPOC2Name.": ".$event->CAPPOC2Phone.", ".$event->CAPPOC2Email;

				$charCount = strlen($textString);
				$checkHeight->MultiCell(0,$lineHeight,$textString,0,"L");
				$maxLines = 2;
				while($checkHeight->GetY() > ($lineHeight * $maxLines)) {
					$textString = substr($textString, 0, $charCount-1);
					$charCount = strlen($textString);
					$checkHeight->SetXY($colStart+$strlen+$horizBuffer,0);
					$checkHeight->MultiCell(0,$lineHeight,$textString,0,"L");
				}
				$pdf->MultiCell(0,$lineHeight,$textString,0,"L");
			}

			if(strlen($event->ExtPOCName) > 1) {
				$pdf->SetX($colStart);
				$pdf->SetFont('Arial','B',10);
				$textString = "External POC: ";
				$strlen = $pdf->GetStringWidth($textString);
				$pdf->Cell($strlen+.05,.18,$textString,0,0,"L");
				$pdf->SetFont('Arial','',10);
				$checkHeight->SetXY($colStart+$strlen+$horizBuffer,0);
				$checkHeight->SetFont('Arial','',10);
				$textString = $event->ExtPOCName.": ".$event->ExtPOCPhone.", ".$event->ExtPOCEmail;

				$charCount = strlen($textString);
				$checkHeight->MultiCell(0,$lineHeight,$textString,0,"L");
				$maxLines = 2;
				while($checkHeight->GetY() > ($lineHeight * $maxLines)) {
					$textString = substr($textString, 0, $charCount-1);
					$charCount = strlen($textString);
					$checkHeight->SetXY($colStart+$strlen+$horizBuffer,0);
					$checkHeight->MultiCell(0,$lineHeight,$textString,0,"L");
				}
				$pdf->MultiCell(0,$lineHeight,$textString,0,"L");
			}




			$bottomY = $pdf->GetY();
			$pageNumber = $pdf->PageNo();
			$pdf->SetY($resetY);


			$pdf->SetFont('Arial','B',10);
			$textString = "Uniform: ";
			$strlen = $pdf->GetStringWidth($textString);
			$pdf->Cell($strlen+$horizBuffer,$lineHeight,$textString,0,0,"L");
			$pdf->SetFont('Arial','',10);
			$textString = $event->Uniform;
			$cellWidth = $colStart - $pdf->GetX();
			if( ( ($pdf->PageNo() == 1 ) && ($pdf->GetY() >= $bottomY) ) || ($pdf->PageNo() > $pageNumber) ) {$cellWidth = 0;}
			$pdf->MultiCell($cellWidth,$lineHeight,$textString,0,"L");

			if(strlen($event->TransportationDescription) > 0) {
				$pdf->SetFont('Arial','B',10);
				$textString = "Transport Desc: ";
				$strlen = $pdf->GetStringWidth($textString);
				$pdf->Cell($strlen+$horizBuffer,$lineHeight,$textString,0,0,"L");
				$pdf->SetFont('Arial','',10);
				$textString = $event->TransportationDescription."  ";
				$cellWidth = $colStart - $pdf->GetX();
				if( ( ($pdf->PageNo() == 1 ) && ($pdf->GetY() >= $bottomY) ) || ($pdf->PageNo() > $pageNumber) ) {$cellWidth = 0;}
				$pdf->MultiCell($cellWidth,$lineHeight,$textString,0,"L");
			}

			if(strlen($event->EventWebsite) > 0) {
				$pdf->SetFont('Arial','B',10);
				$textString = "Website: ";
				$strlen = $pdf->GetStringWidth($textString);
				$pdf->Cell($strlen+$horizBuffer,$lineHeight,$textString,0,0,"L");
				$pdf->SetFont('Arial','',10);
				$textString = $event->EventWebsite."  ";
				$cellWidth = $colStart - $pdf->GetX();
				if( ( ($pdf->PageNo() == 1 ) && ($pdf->GetY() > $bottomY) ) || ($pdf->PageNo() > $pageNumber) ) {$cellWidth = 0;}
				$pdf->MultiCell($cellWidth,$lineHeight,$textString,0,"L");
			}

			$currentPage = 1;
			if($pdf->PageNo() > $currentPage) {
				//draw the lines down the page and across the top and bottom
				$pdf->Line(0.25,0.35,0.25,10.4);
				$pdf->Line(8.25,0.35,8.25,10.4);
				$pdf->Line(0.25,0.35,8.25,0.35);
				$pdf->Line(0.25,10.4,8.25,10.4);
				$currentPage = $pdf->PageNo();
			}

			if(strlen($event->LodgingArrangements) > 0) {
				$pdf->SetFont('Arial','B',10);
				$textString = "Lodging: ";
				$strlen = $pdf->GetStringWidth($textString);
				$pdf->Cell($strlen+$horizBuffer,$lineHeight,$textString,0,0,"L");
				$pdf->SetFont('Arial','',10);
				$textString = $event->LodgingArrangements."  ";
				$cellWidth = $colStart - $pdf->GetX();
				if( ( ($pdf->PageNo() == 1 ) && ($pdf->GetY() >= $bottomY) ) || ($pdf->PageNo() > $pageNumber) ) {$cellWidth = 0;}
				$pdf->MultiCell($cellWidth,$lineHeight,$textString,0,"L");
			}
			if($pdf->PageNo() > $currentPage) {
				//draw the lines down the page and across the top and bottom
				$pdf->Line(0.25,0.35,0.25,10.4);
				$pdf->Line(8.25,0.35,8.25,10.4);
				$pdf->Line(0.25,0.35,8.25,0.35);
				$pdf->Line(0.25,10.4,8.25,10.4);
				$currentPage = $pdf->PageNo();
			}

			if(strlen($event->Activity) > 1) {
				$pdf->SetFont('Arial','B',10);
				$textString = "Activity: ";
				$strlen = $pdf->GetStringWidth($textString);
				$pdf->Cell($strlen+$horizBuffer,$lineHeight,$textString,0,0,"L");
				$pdf->SetFont('Arial','',10);
				$textString = $event->Activity;
				$cellWidth = $colStart - $pdf->GetX();
				if( ( ($pdf->PageNo() == 1 ) && ($pdf->GetY() >= $bottomY) ) || ($pdf->PageNo() > $pageNumber) ) {$cellWidth = 0;}
				$pdf->MultiCell($cellWidth,$lineHeight,$textString,0,"L");
			}
			if($pdf->PageNo() > $currentPage) {
				//draw the lines down the page and across the top and bottom
				$pdf->Line(0.25,0.35,0.25,10.4);
				$pdf->Line(8.25,0.35,8.25,10.4);
				$pdf->Line(0.25,0.35,8.25,0.35);
				$pdf->Line(0.25,10.4,8.25,10.4);
				$currentPage = $pdf->PageNo();
			}

			if(strlen($event->HighAdventureDescription) > 1) {
				$pdf->SetFont('Arial','B',10);
				$textString = "High Adventure Desc: ";
				$strlen = $pdf->GetStringWidth($textString);
				$pdf->Cell($strlen+$horizBuffer,$lineHeight,$textString,0,0,"L");
				$pdf->SetFont('Arial','',10);
				$textString = $event->HighAdventureDescription;
				$cellWidth = $colStart - $pdf->GetX();
				if( ( ($pdf->PageNo() == 1 ) && ($pdf->GetY() >= $bottomY) ) || ($pdf->PageNo() > $pageNumber) ) {$cellWidth = 0;}
				$pdf->MultiCell($cellWidth,$lineHeight,$textString,0,"L");
			}
			if($pdf->PageNo() > $currentPage) {
				//draw the lines down the page and across the top and bottom
				$pdf->Line(0.25,0.35,0.25,10.4);
				$pdf->Line(8.25,0.35,8.25,10.4);
				$pdf->Line(0.25,0.35,8.25,0.35);
				$pdf->Line(0.25,10.4,8.25,10.4);
				$currentPage = $pdf->PageNo();
			}



			if(strlen($event->RequiredEquipment) > 0) {
				$pdf->SetFont('Arial','B',10);
				$textString = "Required Equipment: ";
				$strlen = $pdf->GetStringWidth($textString);
				$pdf->Cell($strlen+$horizBuffer,$lineHeight,$textString,0,0,"L");
				$pdf->SetFont('Arial','',10);
				$textString = $event->RequiredEquipment;
				$cellWidth = $colStart - $pdf->GetX();
				if( ( ($pdf->PageNo() == 1 ) && ($pdf->GetY() >= $bottomY) ) || ($pdf->PageNo() > $pageNumber) ) {$cellWidth = 0;}
				$pdf->MultiCell($cellWidth,$lineHeight,$textString,0,"L");
			}

			if(strlen($event->Meals) > 0) {
				$pdf->SetFont('Arial','B',10);
				$textString = "Meals: ";
				$strlen = $pdf->GetStringWidth($textString);
				$pdf->Cell($strlen+$horizBuffer,$lineHeight,$textString,0,0,"L");
				$pdf->SetFont('Arial','',10);
				$textString = $event->Meals;
				$cellWidth = $colStart - $pdf->GetX();
				if( ( ($pdf->PageNo() == 1 ) && ($pdf->GetY() >= $bottomY) ) || ($pdf->PageNo() > $pageNumber) ) {$cellWidth = 0;}
				$pdf->MultiCell($cellWidth,$lineHeight,$textString,0,"L");
			}



			if(strlen($event->Comments) > 0) {
				$pdf->SetFont('Arial','B',10);
				$textString = "Comments: ";
				$strlen = $pdf->GetStringWidth($textString);
				$pdf->Cell($strlen+$horizBuffer,$lineHeight,$textString,0,0,"L");
				$pdf->SetFont('Arial','',10);
				$textString = $event->Comments."  ";
				$cellWidth = $colStart - $pdf->GetX();
				if( ( ($pdf->PageNo() == 1 ) && ($pdf->GetY() >= $bottomY) ) || ($pdf->PageNo() > $pageNumber) ) {$cellWidth = 0;}
				$pdf->MultiCell($cellWidth,$lineHeight,$textString,0,"L");
			}
			if($pdf->PageNo() > $currentPage) {
				//draw the lines down the page and across the top and bottom
				$pdf->Line(0.25,0.35,0.25,10.4);
				$pdf->Line(8.25,0.35,8.25,10.4);
				$pdf->Line(0.25,0.35,8.25,0.35);
				$pdf->Line(0.25,10.4,8.25,10.4);
				$currentPage = $pdf->PageNo();
			}

			$pdf->SetFont('Arial','',4);
			$pdf->Cell(0,.15,"",0,1,"C");



			$pdf->Output();
			exit(0);
		}
	}





