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
            $sql = "SELECT $tblAtt.*, $tblEvt.EventName, $tblEvt.EventLocation, $tblEvt.StartDateTime, ";
            $sql .= "$tblEvt.EndDateTime FROM $tblEvt INNER JOIN $tblAtt ON $tblAtt.EventID = $tblEvt.EventNumber ";
            $sql .= "WHERE $tblAtt.AccountID=:aid AND $tblEvt.AccountID=:aid AND $tblAtt.CAPID=:cid AND ";
            $sql .= "$tblAtt.Status='Committed/Attended' ORDER BY StartDateTime DESC;";

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':aid', $a->id);
            $stmt->bindValue(':cid', $m->capid);

            $data = DBUtils::ExecutePDOStatement($stmt);

            $html = "<h2>Tab-delimited raw attendance data</h2>";

            $attendanceData = "<pre>";
            $columns[0] = "Event Number";
            $columns[1] = "Event Name";
            $columns[2] = "Event Location";
            $columns[3] = "Start Date/Time";
            $columns[4] = "End Date/Time";
            $columns[5] = "Plan to use CAP Transport";
            $columns[6] = "Comments";
            for ($line = "", $i = 0 ; $i < 7 ; $i++) { $line .= $columns[$i]."\t"; }
            $line = substr($line, 0, strlen($line) - 2)."";
            // for ($line = "", $i = 0 ; $i < 5 ; $i++) { $line .= $columns[$i].","; }
            // $line = substr($line, 0, strlen($line) - 2);
            $attendanceData .= $line."\r\n";

            foreach ($data as $datum) {
                $columns[0] = $a->id."-".$datum['EventID'];
                $columns[1] = $datum['EventName'];
                $columns[2] = $datum['EventLocation'];
                $columns[3] = date('d M Y, H:i',$datum['StartDateTime']);
                $columns[4] = date('d M Y, H:i',$datum['EndDateTime']);
                if(!$datum['PlanToUseCAPTransportation']) {$columns[5]='No';} else {$columns[5]='Yes';}
                $columns[6] = $datum['Comments'];

                for ($line = "", $i = 0 ; $i < 7 ; $i++) { $line .= $columns[$i]."\t"; }
                $line = substr($line, 0, strlen($line) - 2)."";
                $attendanceData .= $line."\r\n";
                // for ($line = "", i = 0 ; i <= 5 ; i++) { $line .= $columns[i].","; }
                // $line = left($line, strlen($line) - 2);
                // $attendanceData .= $line."\r\n";
    
            }
            $attendanceData .= "</pre>";


            return [
                'body' => [
                    'MainBody' => $html.$attendanceData,
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
                'title' => 'Attendance'
            ];
        }
    }
