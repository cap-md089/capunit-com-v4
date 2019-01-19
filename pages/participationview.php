<?php
	require_once(BASE_DIR."lib/pdf/fpdf.php");
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			if (!$l) {
				return ['error' => 411];
			}
			//
			// USE THIS CAPID FOR GETTING ATTENDANCE RECORDS
			// This allows for admins to view other users attendance
			//
			$cid = $m->capid;
			$ev = isset($e['uri'][$e['uribase-index']]) ? $e['uri'][$e['uribase-index']] : false;
			if ($ev && $m->hasPermission("EditEvent")) {
				$cid = $ev;
			}
			if (!$a->paid) {return ['error' => 501];}

			$pdo = DBUtils::CreateConnection();

			$tblAtt = DB_TABLES['Attendance'];
			$tblEvt = DB_TABLES['EventInformation'];

			$sql = "SELECT $tblAtt.*, $tblEvt.EventName, $tblEvt.EventLocation, $tblEvt.StartDateTime, ";
			$sql .= "$tblEvt.EndDateTime FROM $tblEvt INNER JOIN $tblAtt ON $tblAtt.EventID = $tblEvt.EventNumber ";
			$sql .= "WHERE $tblAtt.AccountID=:aid AND $tblEvt.AccountID=:aid AND $tblAtt.CAPID=:cid ";
			$sql .= "ORDER BY StartDateTime DESC;";

			$stmt = $pdo->prepare($sql);
			$stmt->bindValue(':aid', $a->id);
			$stmt->bindValue(':cid', $m->capid);

			$data = DBUtils::ExecutePDOStatement($stmt);

			$filename = "Attendance.pdf";
			header('Content-disposition: attachment; filename="'.$filename.'"');
			header("Content-Type: application/pdf");

//			$writer = new XLSXWriter();
//			$writer->setAuthor('CAPUnit.com');

			$header=array(
				'Event Number'=>'string',"Event Name"=>'string',"Event Location"=>'string',
				"Start Date/Time"=>'string',"End Date/Time"=>'string',"Hours"=>'##0.0',
				"Plan to use CAP Transport"=>'string',"Status"=>'string',"Comments"=>'string'
			);
//			$writer->writeSheetHeader('Sheet1', $header);
			$counter=1;

			$pdf = new FPDF('P','in',array(8.5,11));
			$pdf->AddPage(); $pdf->SetMargins(0.25,0.35,0.25);
			$pdf->SetAutoPageBreak('true',0.5);
			$pdf->SetFont('Arial','',12);

			foreach($data as $datum) {
				$eventID=$datum['AccountID']."-".$datum['EventID'];
				if(!$datum['PlanToUseCAPTransportation']) {$PTUCT='No';} else {$PTUCT='Yes';}
				$startdate=date('d M Y, H:i',$datum['StartDateTime']);
				$enddate=date('d M Y, H:i',$datum['EndDateTime']);
				if($datum['Status']=='Committed/Attended') {
					$hours=($datum['EndDateTime']-$datum['StartDateTime'])/(60*60);
				} else {$hours=0;}
				$row = array(
					$eventID,
					$datum['EventName'],
					$datum['EventLocation'],
					$startdate,
					$enddate,
					$hours,
					$PTUCT,
					$datum['Status'],
					$datum['Comments']
				);
				$pdf->Cell(2,0.5,$datum['EventName'].' '.$startdate,'LRBT',1);
//				$pdf->Cell(2,0.5,$datum['EventName'].' '.$startdate,'LRBT',1);
//				$writer->writeSheetRow('Sheet1', $row);
				++$counter;
			}
//			echo $writer->writeToString();
			$pdf->Output();
			exit(0);
		}
	}



