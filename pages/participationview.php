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
			$sql .= "ORDER BY StartDateTime DESC limit 25;";



			$sql = "select lastTwentyFive.AccountID, lastTwentyFive.EventNumber, ";
			$sql .= "lastTwentyFive.Activity, lastTwentyFive.Status, ";
			$sql .= "from_unixtime(lastTwentyFive.StartDateTime) as startDate, ";
			$sql .= "attData.CAPID from ((SELECT EventInformation.AccountID, ";
			$sql .= "EventInformation.Activity, EventInformation.Status, ";
			$sql .= "EventInformation.EventNumber, EventInformation.StartDateTime FROM ";
			$sql .= "EventInformation where EventInformation.AccountID=";
			$sql .= ":aid ";
			$sql .= "and EventInformation.StartDateTime<=unix_timestamp() ";
			$sql .= "order by startdatetime desc) as lastTwentyFive) ";
			$sql .= "left join ((select Attendance.AccountID, Attendance.EventID, Attendance.CAPID ";
			$sql .= "from Attendance where Attendance.CAPID=:cid) ";
			$sql .= "as attData) on lastTwentyFive.AccountID=attData.AccountID and ";
			$sql .= "lastTwentyFive.EventNumber=attData.EventID order by startdatetime desc;";





			$stmt = $pdo->prepare($sql);
			$stmt->bindValue(':aid', $a->id);
			$stmt->bindValue(':cid', $cid);

			$data = DBUtils::ExecutePDOStatement($stmt);

			$filename = "Attendance.pdf";
			header('Content-disposition: attachment; filename="'.$filename.'"');
			header("Content-Type: application/pdf");
			$counter=0;

			$pdf = new FPDF('P','in',array(8.5,11));
			$pdf->AddPage(); $pdf->SetMargins(0.25,0.35,0.25);
			$pdf->SetAutoPageBreak('true',0.5);
			$pdf->SetFont('Arial','',12);
			$pdf->SetTextColor(0);

			$pdf->SetFillColor(0);
			$widthSet = 0.015625;
			$heightBar = .2;
			$heightEnd = .25;
			$horizOffset = 6.5;
			$pdf->Rect($horizOffset+(($counter-1)*$widthSet),1,$widthSet,$heightEnd,"F");
			foreach($data as $datum) {
				if(substr($datum['Activity'],0,8) == "Squadron" && $datum['Status'] != "Cancelled") {
					if(!!$datum['CAPID']) {
						$pdf->SetFillColor(0);
					} else {
						$pdf->SetFillColor(255);
					}
					$pdf->Rect($horizOffset+($counter*$widthSet),1,$widthSet,$heightBar,"F");
					++$counter;
				}
			}
			$pdf->SetFillColor(0);
			$pdf->Rect($horizOffset+(($counter+1)*$widthSet),1,$widthSet,$heightEnd,"F");
			$dataCount = count($data);

			$data = DBUtils::ExecutePDOStatement($stmt);


			foreach($data as $datum) {
				if(substr($datum['Activity'],0,8) == "Squadron" && $datum['Status'] != "Cancelled") {
					$dataString = $datum['startDate'].', '.$datum['EventNumber'].', ';
					$dataString .= $datum['Activity'].', '.$datum['CAPID'].', '.$datum['Status'];
					$pdf->Cell($pdf->GetStringWidth($dataString),0.3,$dataString,'LRTB',1);
				}
			}
			$pdf->Output();
			exit(0);
		}
	}








