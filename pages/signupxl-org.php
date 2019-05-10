php
	require_once(BASE_DIR."lib/xlsxwriter.class.php");
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			if (!$l) {
				return ['error' => 411];
			}
			$ev = isset($e['uri'][$e['uribase-index']]) ? $e['uri'][$e['uribase-index']] : false;
			if ($ev && $m->hasPermission("EditEvent")) {
				$event = Event::Get($ev);
			} else {
				$event = false;
			}

			$pdo = DBUtils::CreateConnection();

			$tblAtt = DB_TABLES['Attendance'];
			$tblEvt = DB_TABLES['EventInformation'];

			$sql = "SELECT $tblAtt.* FROM $tblAtt ";
			$sql .= "WHERE $tblAtt.AccountID=:aid AND $tblAtt.EventID=:eid ";
			$sql .= "ORDER BY $tblAtt.Timestamp;";

			$stmt = $pdo->prepare($sql);
			$stmt->bindValue(':aid', $a->id);
			$stmt->bindValue(':eid', $event->EventNumber);

			$data = DBUtils::ExecutePDOStatement($stmt);

			$filename = "SignupEvent-".$a->id."-".$event->EventNumber.".xlsx";
			header('Content-disposition: attachment; filename="'.XLSXWriter::sanitize_filename($filename).'"');
			header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");

			$writer = new XLSXWriter();
			$writer->setAuthor('CAPUnit.com');

			$header=array(
				'Timestamp'=>'string','CAPID'=>'string',"Grade/Name"=>'string',"Status"=>'string',
				"Plan to use CAP Transport"=>'string',"Confirmed"=>'string',"Comments"=>'string',"OrgData"=>'string'
			);
			$writer->writeSheetHeader('Sheet1', $header);
			$counter=1;

			foreach($data as $datum) {
//				$eventID=$datum['AccountID']."-".$datum['EventID'];
				$member = Member::Estimate($datum['CAPID']);
				$morg = UtilCollection::GetOrgIDFromUnit($member->Squadron);

				$sql = "SELECT * FROM Data_OrgContact WHERE ORGID=:oid;";
				$stmt = $pdo->prepare($sql);
				$stmt->bindValue(':oid', $morg);
				$orgquery = DBUtils::ExecutePDOStatement($stmt);
				$orgdata = "";
				if(count($orgquery)>0) {
					foreach($orgquery as $contactline) {
						$orgdata .= implode($contactline);
					}
				} else {
					$orgdata = "";
				}

				if(!$datum['PlanToUseCAPTransportation']) {$PTUCT='No';} else {$PTUCT='Yes';}
				$timestamp=date('d M Y, H:i',$datum['Timestamp']);
				$row = array(
					$timestamp,
					$datum['CAPID'],
					$datum['MemberRankName'],
					$datum['Status'],
					$PTUCT,
					$datum['Confirmed'],
					$datum['Comments'],
					$orgdata
				);
				$writer->writeSheetRow('Sheet1', $row);
				++$counter;
			}
			echo $writer->writeToString();
			exit(0);
		}
	}