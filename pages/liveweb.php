<?php
	require_once(BASE_DIR."lib/pdf/fpdf.php");
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			$filename = "example.pdf";
			header('Content-disposition: attachment; filename="'.$filename.'"');
			header("Content-Type: application/pdf");
			$counter=0;

			$pdf = new FPDF('P','in',array(8.5,11));
			$pdf->AddPage(); $pdf->SetMargins(0.25,0.35,0.25);
			$pdf->SetAutoPageBreak('true',0.5);
			$pdf->SetFont('Arial','',12);
			$pdf->SetTextColor(0);

			$pdf->SetFillColor(0);
//			$pdf->Rect($horizOffset+(($counter-1)*$widthSet),1,$widthSet,$heightEnd,"F");
			$pdf->Cell(1,0.3,"My Text",1,0);

			$pdf->Output();
			exit(0);
		}
	}








