<?php
    class Output {
        public static function doGet ($e, $c, $l, $m, $a) {
			if (!$l) {
				return ['error' => 411];
			}
			if (!$a->paid) {return ['error' => 501];}
            $html = '';
            $pdo = DBUtils::CreateConnection();

            $tblAtt = DB_TABLES['Attendance'];
            $tblEvt = DB_TABLES['EventInformation'];
            $sql = "SELECT $tblAtt.*, $tblEvt.EventLocation, $tblEvt.StartDateTime FROM $tblEvt INNER JOIN ";
            $sql .= "$tblAtt ON $tblAtt.EventID = $tblEvt.EventNumber WHERE $tblAtt.AccountID=:aid AND ";
            $sql .= "$tblEvt.AccountID=:aid AND $tblAtt.CAPID=:cid AND $tblAtt.Attendance='Committed/Attended' ";
            $sql .= "ORDER BY StartDateTime;";

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':aid', $a->id);
            $stmt->bindValue(':cid', $m->capid);

            $data = DBUtils::ExecutePDOStatement($stmt);

            $attendanceData = "";
            $columns[0] = "Event Number";
            $columns[1] = "Event Location";
            $columns[2] = "Start Date/Time";
            $columns[3] = "Plan to use CAP Transport";
            $columns[4] = "Comments";
            for ($line = "", i = 0 ; i <= 5 ; i++) { $line .= $columns[i].","; }
            $line = left($line, strlen($line) - 2);

            foreach ($data as $datum) {
                $columns[0] = $a->id."-".$datum['EventID'];
                $columns[1] = $datum['EventLocation'];
                $columns[2] = date('d M Y, H:i',$datum['StartDateTime']);
                $columns[3] = $datum['PlanToUseCAPTransportation'];
                $columns[4] = $datum['Comments'];

                for ($line = "<p>", i = 0 ; i <= 5 ; i++) { $line .= $columns[i]."\t"; }
                $line = left($line, strlen($line) - 2)."</p>";
                $attendanceData .= $line."\r\n";
                // for ($line = "", i = 0 ; i <= 5 ; i++) { $line .= $columns[i].","; }
                // $line = left($line, strlen($line) - 2);
                // $attendanceData .= $line."\r\n";
    
            }


            return [
                'body' => [
                    'MainBody' => $attendanceData,
                    'BreadCrumbs' => UtilCollection::GenerateBreadCrumbs([
                        [
							'Target' => '/',
							'Text' => 'Home'
						],
						[
							'Target' => '/viewattendance',
							'Text' => 'View Attendance'
						]
                    ])
                ],
                'title' => 'Teams'
            ];
        }
    }
