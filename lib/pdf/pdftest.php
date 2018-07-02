<?php
require('fpdf.php');

$pdf = new FPDF('P','in',array(8.5,11));
$pdf->AddPage();
$pdf->SetMargins(0.25,0.35,0.25);
$pdf->SetAutoPageBreak('true',0.5);
$pdf->SetFont('Arial','',12);
$pdf->Cell(2,0.5,'Hello World!','T',1);
$pdf->Output();
?>
