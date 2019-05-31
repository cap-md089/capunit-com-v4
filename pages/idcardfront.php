<?php
	require_once(BASE_DIR."lib/tfpdf/tfpdf.php");

	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			if (!$l) {
				return ['error' => 411];
			}

//			$cardData = $m->get101Card(421170);

                        $cid = $m->capid;
                        $ev = isset($e['uri'][$e['uribase-index']]) ? $e['uri'][$e['uribase-index']] : false;
                        if ($ev && $m->hasPermission("EditEvent")) {
                                $cid = $ev;
                        }
                        if (!$a->paid) {return ['error' => 501];}

			$cardData = $m->get101Card($cid);

			$pdo = DBUtils::CreateConnection();

			$tblAtt = DB_TABLES['Attendance'];
			$tblEvt = DB_TABLES['EventInformation'];

			$filename = "idcardfront.pdf";
			header('Content-disposition: attachment; filename="'.$filename.'"');
			header("Content-Type: application/pdf");
			$counter=0;

			$pdf = new tFPDF('P','in',array(5.11,8.85));
			$pdf->AliasNbPages();
			$pdf->SetTitle("ID Front");
			$pdf->AddPage();
			$pdf->SetMargins(0.25,0.25,0.25);
//			$pdf->SetAutoPageBreak('true',0.5);
			//Set Title font
			$pdf->AddFont('Tahoma','','tahoma.ttf',true);
			$pdf->SetFont('Tahoma','',14);
			$pdf->SetTextColor(0);
			$pdf->SetFillColor(0);

			$url = "";
			if ($cardData['hasImage']) {
				$photo = $m->download101Image($cardData['CAPID']);
				file_put_contents(BASE_DIR."temp/".$cardData['CAPID']."", $photo);
				system("convert " . BASE_DIR . "temp/" . $cardData['CAPID'] . " " . BASE_DIR . "temp/" . $cardData['CAPID'] . ".jpg");
				$url = BASE_DIR."temp/".$cardData['CAPID'].".jpg";
			} else {
				$url = BASE_DIR."images/NoVal.jpg";
			}
			if(strlen($cardData['name']<30)){
				$imagex=0.3; $imagey=4.33; $imagew=1; $imageh=0; $spacerPhoto = 1.05;
			} else {
				$imagex=0.3; $imagey=4.33; $imagew=0.9; $imageh=0;  $spacerPhoto = 0.95;
			}
			$pdf->Image($url,$imagex,$imagey,$imagew,$imageh);

			$imagex=0.3; $imagey=3.25; $imagew=0.6; $imageh=0;
			$pdf->Image(BASE_DIR."images/MDWG_Patch.jpg",$imagex,$imagey,$imagew,$imageh);
			$imageh=0;  $imagex=2.38-$imagew;
			$pdf->Image(BASE_DIR."images/SquadronPatchColor.jpg",$imagex,$imagey,$imagew,$imageh);

			$cardWidth = 2.13;
			$textSize = 8;
			$cellHeight = ($textSize-2)/50;
			//Page Title
//			$pdf->Cell(0,.3,"ID Card",0,1,"C");
			//Set text font
			$pdf->SetFont('Arial','B',12);
			$pdf->Cell(1,3.55,'',0,1);  //spacer to move text start to top of card
			$pdf->Cell($cardWidth,$cellHeight+0.05,"Civil Air Patrol",0,1,"C");
			$pdf->Cell($cardWidth,0.15,"Security Badge",0,1,"C");
			$pdf->Cell($spacerPhoto,0.04,'',0,1);  //spacer to move text to the top of photo
			$pdf->Cell($spacerPhoto,0.13,'',0,0);  //spacer to move text to the right of photo
			if(strlen($cardData['name']<30)){
				$pdf->SetFont('Arial','B',$textSize+2);
			} else {
				$pdf->SetFont('Arial','B',$textSize-2);
			}

			$pdf->MultiCell(2.15-$spacerPhoto,$cellHeight+0.05,$cardData['name'],0,"L");
			$pdf->Cell(1,0.05,'',0,1);  //spacer to move text start down
			$pdf->Cell($spacerPhoto,$cellHeight,'',0,0);  //spacer to move text to the right of photo
			$pdf->SetFont('Arial','B',$textSize);
			$pdf->Cell(0.3,$cellHeight,"Unit:",0,0,"L");
			$pdf->SetFont('Arial','',$textSize);
			$pdf->Cell(0.8,$cellHeight,$cardData['unit'],0,1,"L");
			$pdf->Cell($spacerPhoto,$cellHeight,'',0,0);  //spacer to move text to the right of photo
			$pdf->SetFont('Arial','B',$textSize);
			$pdf->Cell(0.45,$cellHeight,"CAPID:",0,0,"L");
			$pdf->SetFont('Arial','',$textSize);
			$pdf->Cell(1,$cellHeight,$cardData['CAPID'],0,1,"L");
			$pdf->Cell($spacerPhoto+0.1,0.2,'',0,0);  //spacer to move text to the right of photo
			$pdf->SetFont('Arial','B',$textSize);
			$pdf->Cell(0.45,0.2,"Height",0,0,"L");
			$pdf->Cell(0.45,0.2,"Weight",0,1,"L");
			$pdf->Cell($spacerPhoto+0.1,0.1,'',0,0);  //spacer to move text to the right of photo
			$pdf->SetFont('Arial','',$textSize);
			$pdf->Cell(0.45,$textSize/100,$cardData['height']." in",0,0,"L");
			$pdf->Cell(0.45,$textSize/100,$cardData['weight']." lbs",0,1,"L");
			$pdf->Cell($spacerPhoto+0.1,0.2,'',0,0);  //spacer to move text to the right of photo
			$pdf->SetFont('Arial','B',$textSize);
			$pdf->Cell(0.45,0.22,"Eyes",0,0,"L");
			$pdf->Cell(0.45,0.22,"Hair",0,1,"L");
			$pdf->Cell($spacerPhoto+0.1,0.1,'',0,0);  //spacer to move text to the right of photo
			$pdf->SetFont('Arial','',$textSize);
			$pdf->Cell(0.45,$textSize/100,$cardData['eyes'],0,0,"L");
			$pdf->Cell(0.45,$textSize/100,$cardData['hair'],0,1,"L");

			$pdf->Cell(1,0.1,'',0,1);  //spacer
			$today = date("j F Y");
			$pdf->Cell($cardWidth,0.25,"Printed on ".$today,0,1,"C");

			$pdf->SetFont('Arial','B',20);
			$pdf->SetTextColor(255,0,0);  //make font red
			$pdf->Cell(1,0.05,'',0,1);  //spacer to move text below photo
			$pdf->MultiCell($cardWidth,0.25,"MDWG 2019 SAREX",0,"C");


			if ($cardData['hasImage']) {
				unlink(BASE_DIR."temp/".$cardData['CAPID']."");
			}

//			$pdf->Text(0.8, 3.2, "_");
/*			$pdf->Text(0.8, 3.2, "Civil Air Patrol");
			$pdf->Text(0.4, 3.35, "Security                   Badge");
//mb_convert_encoding("&infin;",'UTF-8','HTML-ENTITIES')
			$imagex=0.4; $imagey=3.4; $imagew=0.8; $imageh=0;
//			$pdf->Image(BASE_DIR.'images/CAP_Seal_Monochrome.png',$imagex,$imagey,$imagew,$imageh);
			$pdf->Image(BASE_DIR.'images/546319.jpg',$imagex,$imagey,$imagew,$imageh);
			$pdf->SetFont('Arial','',9);
			$pdf->Text(1.23, 3.5, $cardData['name']);
			$pdf->SetFont('Arial','B',9);
			$pdf->Text(1.23, 3.65, "Unit:");
			$pdf->SetFont('Arial','',9);
			$pdf->Text(1.7, 3.65, $cardData['unit']);
			$pdf->SetFont('Arial','B',9);
			$pdf->Text(1.23, 3.8, "CAPID:");
			$pdf->SetFont('Arial','',9);
//			$pdf->Text(1.7, 3.8, $cardData['id']);
			$pdf->SetFont('Arial','B',9);
			$pdf->Text(1.4, 4, "Height");
			$pdf->Text(1.9, 4, "Weight");
			$pdf->SetFont('Arial','',9);
			$pdf->Text(1.4, 4.15, $cardData['height']." in");
			$pdf->Text(1.9, 4.15, $cardData['weight']. "lbs");
			$pdf->SetFont('Arial','B',9);
			$pdf->Text(1.4, 4.3, "Eyes");
			$pdf->Text(1.9, 4.3, "Hair");
			$pdf->SetFont('Arial','',9);
			$pdf->Text(1.4, 4.42, $cardData['eyes']);
			$pdf->Text(1.9, 4.42, $cardData['hair']);
*/
			$pdf->Output();
			exit(0);
		}
	}





