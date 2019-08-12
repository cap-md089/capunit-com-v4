<?php
    class Output {
        public static function doGet ($e, $c, $l, $m, $a) {
		if (!$l) {return ['error' => 411];}
		if (!$m->hasPermission('Developer')) {return ['error' => 402];}

            $html = '';
            $pdo = DBUtils::CreateConnection();

            $tblsign = DB_TABLES['SignInData'];
            $sql = "SELECT $tblsign.* FROM $tblsign ORDER BY LastAccessTime DESC;";

            $stmt = $pdo->prepare($sql);
            $data = DBUtils::ExecutePDOStatement($stmt);

            $html .= "</br></br><h2>Tab-delimited raw sign-in data</h2>";

            $signinData = "<pre>";
            $columns[0] = "Access\t\t";
            $columns[1] = "Account";
            $columns[2] = "Count";
            $columns[3] = "CAPID";
            $columns[4] = "Squadron";
            $columns[5] = "Member\t\t";
            for ($line = "", $i = 0 ; $i < 7 ; $i++) { $line .= $columns[$i]."\t"; }
            $line = substr($line, 0, strlen($line) - 1)."";
            $signinData .= $line."\r\n";

            foreach ($data as $datum) {
                $columns[0] = date('d M Y, H:i',$datum['LastAccessTime']);
                $columns[1] = $datum['AccountID'];
                $columns[2] = $datum['AccessCount'];
                $columns[3] = $datum['CAPID'];
                $columns[4] = $datum['Squadron'];
                $columns[5] = $datum['MemberRank']." ".$datum['MemberName'];

                for ($line = "", $i = 0 ; $i < 7 ; $i++) { $line .= $columns[$i]."\t"; }
                $line = substr($line, 0, strlen($line) - 1)."";
                $signinData .= $line."\r\n";

            }
            $signinData .= "</pre>";


            return [
                'body' => [
                    'MainBody' => $html.$signinData,
                    'BreadCrumbs' => UtilCollection::GenerateBreadCrumbs([
                        [
							'Target' => '/',
							'Text' => 'Home'
						],
						[
							'Target' => '/viewsignin',
							'Text' => 'View Sign-in Data'
						]
                    ])
                ],
                'title' => 'Sign-ins'
            ];
        }
    }
