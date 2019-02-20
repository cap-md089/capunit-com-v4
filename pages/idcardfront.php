<?php
	require_once(BASE_DIR."lib/pdf/fpdf.php");

	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			if (!$l) {
				return ['error' => 411];
			}

//use this URL to download 101 card information
//https://www.capnhq.gov/CAP.OPSQuals.Web/EmergencyServices/101Card.aspx

//    card legend info
//    &infin;</div>Indicates Does Not Expire</div>
//    &diams;</div>Indicates Skills Evaluator</div>
//    *</div>Indicates Supervised Trainee Status</div>
//    **</div>Indicates NIMS Training Incomplete</div>
//    +</div>Indicates Aircraft Ground Handling Incomplete</div>

//use this URL to download photo
//https://www.capnhq.gov/images/thumb/421170.jpg


                        $cid = $m->capid;
                        $ev = isset($e['uri'][$e['uribase-index']]) ? $e['uri'][$e['uribase-index']] : false;
                        if ($ev && $m->hasPermission("EditEvent")) {
                                $cid = $ev;
                        }
                        if (!$a->paid) {return ['error' => 501];}

			$pdo = DBUtils::CreateConnection();

			$tblAtt = DB_TABLES['Attendance'];
			$tblEvt = DB_TABLES['EventInformation'];

			$filename = "idcardfront.pdf";
			header('Content-disposition: attachment; filename="'.$filename.'"');
			header("Content-Type: application/pdf");
			$counter=0;

			$pdf = new FPDF('P','in',array(5.11,8.85));
			$pdf->AliasNbPages();
			$pdf->SetTitle("ID Front");
			$pdf->AddPage();
//			$pdf->SetMargins(0.25,0.25,0.25);
			$pdf->SetAutoPageBreak('true',0.5);
			//Set Title font
			$pdf->SetFont('Arial','B',14);
			$pdf->SetTextColor(0);
			$pdf->SetFillColor(0);

			$rectx = 0.375;
			$recty = 3;
			$rectw = 2.13;
			$recth = 3.36;
//			$pdf->Rect($rectx, $recty, $rectw, $recth);

			//Page Title
//			$pdf->Cell(0,.3,"ID Card",0,1,"C");
			//Set text font
			$pdf->SetFont('Arial','',9);
			//insert CAP logo in upper left corner
			$pdf->Text(0.8, 3.2, "Civil Air Patrol");
			$pdf->Text(0.4, 3.4, "Security                     Badge");
			$imagex=0.4; $imagey=3.4; $imagew=0.6; $imageh=0;
//			$pdf->Image(BASE_DIR.'images/CAP_Seal_Monochrome.png',$imagex,$imagey,$imagew,$imageh);

			$pdf->Output();
			exit(0);
		}
	}





