<?php
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			if (!$l) return ['error' => 411];
			if (!$m->hasPermission('FlightAssign')) return ['error' => 401];

			$flights = [
				Registry::Get("Administration.FlightNames.Flight1"),
				Registry::Get("Administration.FlightNames.Flight2"),
				Registry::Get("Administration.FlightNames.Flight3"),
				Registry::Get("Administration.FlightNames.Flight4"),
				Registry::Get("Administration.FlightNames.Flight5"),
				Registry::Get("Administration.FlightNames.Flight6"),
				Registry::Get("Administration.FlightNames.Flight7"),
			];
/*
			$flights = [
				'Alpha',
				'Bravo',
				'Charlie',
				'Delta',
				'Echo',
				'Staff',
				'XRay'
			];
}*/
			$pdo = DBUtils::CreateConnection();

//			$stmt = $pdo->prepare("insert into ".DB_TABLES['Flights']." (CAPID, Flight, Mentor, AccountID) select 
//			".DB_TABLES['Member'].".CAPID, 'Charlie' as Flight, Null as Mentor, :aid as AccountID from ".DB_TABLES['Member']."
//			left join ".DB_TABLES['Flights']." on ".DB_TABLES['Member'].".CAPID = ".DB_TABLES['Flights'].".CAPID
//			where (".DB_TABLES['Flights'].".CAPID IS NULL and ".DB_TABLES['Flights'].".AccountID IS NOT :aid2)
//			 AND (Rank like 'C/%' or Rank like 'CADET') AND ORGID in $a->orgSQL;");
//			$stmt->bindValue(":aid", $a->id);
//			$stmt->bindValue(":aid2", $a->id);
//			if (!$stmt->execute()) {
//				trigger_error($stmt->errorInfo()[2], 512);
//			}

			$html = '<form method="POST" enctype="multipart/form-data"
			data-form-reload="true" action="/flightassign" class="asyncForm flightAssign"
			onsubmit="return handleFormSubmit(this, false);"><div id=\"flights_box\">';
			$bhtml = "<div id=\"flights\">";
			$hhtml = "<div id=\"flights-title\">";

			foreach ($flights as $flight) {
				$hhtml .= "<div class=\"title\">$flight</div>";
				$fhtml = "<div class=\"flight $flight\">";
				$sqlstmt = "SELECT Flights.CAPID, CAPID_To_Account.NameLast, CAPID_To_Account.NameFirst FROM ".DB_TABLES['Flights']." INNER JOIN ";
				$sqlstmt .= "CAPID_To_Account ON Flights.CAPID=CAPID_To_Account.CAPID AND ";
				$sqlstmt .= "Flights.AccountID=CAPID_To_Account.AccountID ";
				$sqlstmt .= " WHERE Flights.Flight = :fly AND Flights.AccountID = :aid";
				$sqlstmt .= " ORDER BY CAPID_To_Account.NameLast, CAPID_To_Account.NameFirst;";

//				$stmt = $pdo->prepare("SELECT CAPID FROM ".DB_TABLES['Flights']." WHERE Flight = :fly AND AccountID = :aid;");
				$stmt = $pdo->prepare($sqlstmt);
				$stmt->bindValue(":fly", $flight);
				$stmt->bindValue(":aid", $a->id);
				$data = DBUtils::ExecutePDOStatement($stmt);
				foreach ($data as $datum) {
					$mem = Member::Estimate($datum['CAPID'], true);
					if ($mem) {
						$fhtml .= "<div id=\"{$datum['CAPID']}\" class=\"cadet\">";
						$fhtml .= "$mem->RankName<input name=\"capids[]\" type=\"hidden\" value=\"{$datum['CAPID']}:$flight\" />";
						$fhtml .= "</div>";
					}
					// $form->addField($mem->uname, $mem->memberRank.' '.$mem->memberName, 'radio', Null, $flights, $flight);
					// $form->addHiddenField('capid[]', $mem->uname);
				}
				$bhtml .= "$fhtml</div>";
			}
			$html = "$html$hhtml</div>$bhtml</div><input style=\"float:right;margin:10px\" class=\"forminput\" type=\"submit\" value=\"Update Changes\" /></form>";

			return [
				'body' => [
					'MainBody' => $html.'',
					'BreadCrumbs' => UtilCollection::GenerateBreadCrumbs([
						[
							'Text' => 'Home',
							'Target' => '/',
						],
						[
							'Text' => 'Administration',
							'Target' => '/admin'
						],
						[
							'Text' => 'Flight Assignment',
							'Target' => '/flightassign'
						]
					])
				],
				'title' => 'Flight assignments'
			];
		}

		public static function doPost ($e, $c, $l, $m, $a) {
			if (!$l) return ['error' => 411];
			if (!$m->hasPermission('FlightAssign')) return ['error' => 401];

			$flights = [
				'Alpha',
				'Bravo',
				'Charlie',
				'Delta',
				'Echo',
				'Staff',
				'XRay'
			];


			// if (isset($e['form-data']['capid'])) {
			// 	foreach ($flights as $flight) {
			// 		$capids = $e['form-data']['capid'];
			// 		foreach ($capids as $capid) {
			// 			$stmt = $pdo->prepare("UPDATE ".DB_TABLES['Flights']." SET Flight=:flight WHERE capid=:cid;");
			// 			$stmt->bindValue(":flight", $e['form-data'][$capid]);
			// 			$stmt->bindValue(":cid", $capid);
			// 			if (!$stmt->execute()) {
			// 				trigger_error($stmt->errorInfo()[2], 512);
			// 			}
			// 		}
			// 	}
			// }
			$pdo = DBUtils::CreateConnection();

			foreach ($e['form-data']['capids'] as $capid) {
				$data = explode(':', $capid);
				$stmt = $pdo->prepare("UPDATE ".DB_TABLES['Flights']." SET Flight=:flight WHERE CAPID=:cid AND AccountID=:aid;");
				$stmt->bindValue(":flight", $data[1]);
				$stmt->bindValue(":cid", $data[0]);
				$stmt->bindValue(':aid', $a->id);
				$success = $stmt->execute();
				if (!$success) {
					trigger_error($stmt->errorInfo()[2], 512);
				}
			}
		}
	}
