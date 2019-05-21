<?php
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

			$tblAtt = DB_TABLES['SpecialAttendance'];
			$tblEvt = DB_TABLES['EventInformation'];

			$sql = "SELECT $tblAtt.* FROM $tblAtt ";
			$sql .= "WHERE $tblAtt.AccountID=:aid AND $tblAtt.EventID=:eid ";
			$sql .= "ORDER BY $tblAtt.Timestamp;";

			$stmt = $pdo->prepare($sql);
			$stmt->bindValue(':aid', $a->id);
			$stmt->bindValue(':eid', $event->EventNumber);

			$data = DBUtils::ExecutePDOStatement($stmt);

			$filename = "SignupSpecialEvent-".$a->id."-".$event->EventNumber.".xlsx";
			header('Content-disposition: attachment; filename="'.XLSXWriter::sanitize_filename($filename).'"');
			header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");

			$writer = new XLSXWriter();
			$writer->setAuthor('CAPUnit.com');

			$header=array(
				'Timestamp'=>'string','CAPID'=>'string',"Grade/Name"=>'string',"Status"=>'string',
				"Plan to use CAP Transport"=>'string',"Confirmed"=>'string',"Comments"=>'string',"GeoLoc"=>'string',"DutyPreference"=>'string',
				'Email'=>'string','Phone'=>'string','Uniform'=>'string','Squadron'=>'string','OrgEmail'=>'string','CommanderName'=>'string'
			);
			$writer->writeSheetHeader('Sheet1', $header);
			$counter=1;

			foreach($data as $datum) {
//				$eventID=$datum['AccountID']."-".$datum['EventID'];
				if(!$datum['PlanToUseCAPTransportation']) {$PTUCT='No';} else {$PTUCT='Yes';}
				$timestamp=date('d M Y, H:i',$datum['Timestamp']);
				$member = Member::Estimate($datum['CAPID']);
				if($datum['EmailAddress'] == '') {$em = $member->getBestEmail();} else {$em = $datum['EmailAddress'];}
				if($datum['PhoneNumber'] == '') {$ep = $member->getBestPhone();} else {$ep = $datum['PhoneNumber'];}
                                $morg = UtilCollection::GetOrgIDFromUnit($member->Squadron);

                                $sql = "SELECT * FROM Data_OrgContact WHERE ORGID=:oid;";
                                $stmt = $pdo->prepare($sql);
                                $stmt->bindValue(':oid', $morg);
                                $orgquery = DBUtils::ExecutePDOStatement($stmt);
                                $orgdata = ""; $orgemail = "";
                                if(count($orgquery)>0) {
                                        foreach($orgquery as $contactline) {
                                                if($contactline['Type'] == "EMAIL") {
                                                        $orgemail .= $contactline['Contact'].", ";
                                                }
                                        }
                                } else {
                                        $orgemail = "";
                                }
                                if(strlen($orgemail)>2) {$orgemail = substr($orgemail, 0, strlen($orgemail)-2);}

                                $sqlcdr = "SELECT * FROM Data_Commanders WHERE ORGID=:oid;";
                                $stmtcdr = $pdo->prepare($sqlcdr);
                                $stmtcdr->bindValue(':oid', $morg);
                                $cdrquery = DBUtils::ExecutePDOStatement($stmtcdr);
                                $cdrname = "";
                                if(count($cdrquery) == 1) {
					$cdrname = $cdrquery[0]['CAPID']." ".$cdrquery[0]['Rank']." ".$cdrquery[0]['NameFirst']." ";
					$cdrname .= $cdrquery[0]['NameLast']." ".$cdrquery[0]['NameSuffix'];
                                }

				$row = array(
					$timestamp,
					$datum['CAPID'],
					$datum['MemberRankName'],
					$datum['Status'],
					$PTUCT,
					$datum['Confirmed'],
					$datum['Comments'],
					$datum['GeoLoc'],
					$datum['DutyPreference'],
					$em,
					$ep,
					$datum['Uniform'],
					$member->Squadron,
					$orgemail,
					$cdrname
				);
				$writer->writeSheetRow('Sheet1', $row);
				++$counter;
			}
			echo $writer->writeToString();
			exit(0);
		}
	}
