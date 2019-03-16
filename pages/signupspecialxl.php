<?php
	require_once(BASE_DIR."lib/xlsxwriter.class.php");
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			if (!$l) {
				return ['error' => 411];
			}

			$pdo = DBUtils::CreateConnection();

			$tblAtt = DB_TABLES['SpecialAttendance'];
			$tblEvt = DB_TABLES['EventInformation'];

                        $ev = isset($e['uri'][$e['uribase-index']]) ? $e['uri'][$e['uribase-index']] : false;
                        $event = $ev ? Event::Get((int)$ev, $a) : false;

                        if (!$event) {
                                return [
                                        'error' => 311
                                ];
                        }

                        $sql = "Call getSpecialAttendance(:aid, :eid)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->bindValue(':aid', $a->id);
                        $stmt->bindValue(':eid', $event->EventNumber);
                        $data = DBUtils::ExecutePDOStatement($stmt);

			$filename = "SpecialEvent_".$data[0]['AccountID']."-".$data[0]['EventID'].".xlsx";
			header('Content-disposition: attachment; filename="'.XLSXWriter::sanitize_filename($filename).'"');
			header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");

			$writer = new XLSXWriter();
			$writer->setAuthor('CAPUnit.com');

//			$header=array(
//				"Timestamp"=>'string',"EventID"=>'string',
//				"CAPID"=>'string',"Rank Name"=>'string',"Status"=>'string',
//				"Plan to use CAP Transport"=>'string',"GeoLoc"=>'string',"DutyPref"=>'string',"Comments"=>'string'
//			);
//			$writer->writeSheetHeader('Sheet1', $header);
//			$counter=1;

/*			foreach($data as $datum) {
				$eventID=$datum['AccountID']."-".$datum['EventID'];
				if(!$datum['PlanToUseCAPTransportation']) {$PTUCT='No';} else {$PTUCT='Yes';}
				$row = array(
					$datum['Timestamp'],
					$eventID,
					$datum['CAPID'],
					$datum['MemberRankName'],
					$datum['Status'],
					$PTUCT,
					$datum['GeoLoc'],
					$datum['DutyPreference'],
					$datum['Comments']
				);
				$writer->writeSheetRow('Sheet1', $row);
				++$counter;
			}
*/


				$row = array('Timestamp','eventID','CAPID','MemberRankName',
					'Status','PTUCT','GeoLoc','DutyPreference','Comments'
				);
				$writer->writeSheetRow('Sheet1', $row);




			echo $writer->writeToString();
			exit(0);
		}
	}
