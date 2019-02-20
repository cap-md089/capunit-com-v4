<?php
	require_once(BASE_DIR."lib/tfpdf/tfpdf.php");

	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			if (!$l) {
				return ['error' => 411];
			}

                        $cid = $m->capid;
                        $ev = isset($e['uri'][$e['uribase-index']]) ? $e['uri'][$e['uribase-index']] : false;
                        if ($ev && $m->hasPermission("EditEvent")) {
                                $cid = $ev;
                        }
                        if (!$a->paid) {return ['error' => 501];}

			$cardData = $m->get101Card($cid);
//			$cardData = $m->get101Card(421170);
//			$cardData = $m->get101Card(102995);

			$pdo = DBUtils::CreateConnection();

			$tblAtt = DB_TABLES['Attendance'];
			$tblEvt = DB_TABLES['EventInformation'];

			$filename = "idcardback.pdf";
			header('Content-disposition: attachment; filename="'.$filename.'"');
			header("Content-Type: application/pdf");
			$counter=0;

			$pdf = new tFPDF('P','in',array(5.11,8.85));
			$pdf->AliasNbPages();
			$pdf->SetTitle("ID Back");
			$pdf->AddPage();
			$pdf->SetMargins(0.3,0.25,0.25);
//			$pdf->SetAutoPageBreak('true',0.5);
			//Set Title font
			$pdf->AddFont('Tahoma','','tahoma.ttf',true);
			$pdf->SetTextColor(0);
			$pdf->SetFillColor(0);

			$cardWidth = 2.05;
			$spacerPhoto = 1.05;
			$textSize = 8;
			$cellHeight = ($textSize-2)/50;
			//Set text font
			$pdf->SetFont('Arial','',10);
			$pdf->Cell(1,3.2,'',0,1);  //spacer to move text start to top of card

//loop through quals

			$pageWidth = 2.05;
			$legendWidth = 0.12;
			$columnWidth = $pageWidth/3;
			$fontSize = 7;
			$counter = 0;
			$cellHeight = 0.13;
//				$pdf->Cell($columnWidth, $cellHeight, count($cardData['quals']), 1, 1);
			$pdf->SetFont('Tahoma','',$fontSize);

			$pdf->Cell($pageWidth, $cellHeight, mb_convert_encoding("&infin;",'UTF-8','HTML-ENTITIES')." Indicates Does Not Expire", "RTL", 1);
			$pdf->Cell($pageWidth, $cellHeight, mb_convert_encoding("&diams;",'UTF-8','HTML-ENTITIES')." Indicates Skills Evaluator", "RTL", 1);
			$pdf->Cell($pageWidth, $cellHeight, "* Indicates Supervised Trainee Status", "RTL", 1);
			$pdf->Cell($pageWidth, $cellHeight, "** Indicates NIMS Training Incomplete", "RTL", 1);
			$pdf->Cell($pageWidth, $cellHeight, "+ Indicates Acft Ground Handling Incompete", "LRTB", 1);

			foreach($cardData['quals'] as $qual) {
				$modStat = $counter++ % 3;
				$lineStat = ($modStat == 2) ? 1 : 0;

				$cellText = '';

				if($qual['evaluator']) {
//					$cellText = "^";
					$cellText = mb_convert_encoding("&diams;",'UTF-8','HTML-ENTITIES');
				} elseif ($qual['supervised']) {
					$cellText = "*";
				} elseif ($qual['nims']) {
					$cellText = "**";
				} elseif ($qual['aircraft']) {
					$cellText = "+";
				}

				$cellText .= $qual['name'];

				$cellPostText = '';
				if ($qual['expires']) {
					$cellPostText .= $qual['expires'];
				} elseif($qual['dne']) {
//					$cellPostText .= "I";
					$cellPostText .= mb_convert_encoding("&infin;",'UTF-8','HTML-ENTITIES');
				}

				$pdf->Cell($columnWidth/2, $cellHeight, $cellText, "LTB", 0);
				$pdf->Cell($columnWidth/2, $cellHeight, $cellPostText, "BRT", $lineStat,"R");

			}
			if (!$lineStat) {
				$pdf->Cell($columnWidth/2, $cellHeight, " ", 0, 1);
			}

			$pdf->Cell($pageWidth, $cellHeight/3, " ", 1, 1);


			if(strlen($cardData['driversLicense']['details'])>35) {
				$pdf->SetFont('Tahoma','',$fontSize-1);
			}
			if(strlen($cardData['driversLicense']['details'])>44) {
				$pdf->SetFont('Tahoma','',$fontSize-2);
			}

//			$pdf->Cell($pageWidth, $cellHeight, "Drv's Lic (S,CV2,7,8,9,12,15,PT,SPV)  4/15/20", 1, 1);
			$pdf->Cell($pageWidth-0.2, $cellHeight, $cardData['driversLicense']['details'], "TLB", 0);
			$myDate = date("n/y",strtotime($cardData['driversLicense']['expires']));
			$pdf->Cell(0.2, $cellHeight, $myDate, "TRB", 1,"R");

//			$pdf->Cell($columnWidth, $cellHeight, "1234567890123456789", 1, 1);




			$pdf->Output();
			exit(0);
		}
	}





