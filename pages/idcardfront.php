<?php
	require_once(BASE_DIR."lib/pdf/fpdf.php");

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
			$pdf->SetMargins(0.25,0.25,0.25);
			$pdf->SetAutoPageBreak('true',0.5);
			//Set Title font
			$pdf->SetFont('Arial','B',14);
			$pdf->SetTextColor(0);
			$pdf->SetFillColor(0);
			$pdf->SetFillColor(0);

			$rectx = 0.375;
			$recty = 3;
			$rectw = 2.25;
			$recth = 3.5;
			$pdf->Rect($rectx, $recty, $rectw, $recth);

			//Page Title
			$pdf->Cell(0,.3,"ID Card",0,1,"C");
			//Set text font
			$pdf->SetFont('Arial','',11);
			//insert CAP logo in upper left corner
			$imagex=0.4; $imagey=3.4; $imagew=1.2; $imageh=0;
			$pdf->Image(BASE_DIR.'images/CAP_Seal_Monochrome.png',$imagex,$imagey,$imagew,$imageh);

			$pdf->Output();
			exit(0);
		}
	}





