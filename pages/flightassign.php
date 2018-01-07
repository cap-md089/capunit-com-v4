<?php
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			if (!$l) return ['error' => 411];
			if (!$m->hasPermission('FlightAssign')) return ['error' => 401];
			
			$flights = [
				'Alpha',
				'Bravo',
				'Charlie',
				'Staff',
				'XRay'
			];

			$pdo = DBUtils::CreateConnection();
			
			$stmt = $pdo->prepare("insert into ".DB_TABLES['Flights']." (CAPID, Flight, Mentor, AccountID) select 
			".DB_TABLES['Member'].".CAPID, 'Charlie' as Flight, Null as Mentor, :aid as AccountID from ".DB_TABLES['Member']."
			left join ".DB_TABLES['Flights']." on ".DB_TABLES['Member'].".CAPID = ".DB_TABLES['Flights'].".CAPID
			where ".DB_TABLES['Flights'].".CAPID IS NULL AND Rank like 'C/%' AND ORGID in $a->orgSQL;");
			$stmt->bindValue(":aid", $a->id);
			if (!$stmt->execute()) {
				trigger_error($stmt->errorInfo()[2], 512);
			}


			$html = '<form method="POST" enctype="multipart/form-data"
			data-form-reload="true" action="/flightassign" class="asyncForm flightAssign"
			onsubmit="return handleFormSubmit(this, false);"><div id=\"flights_box\">';
			$bhtml = "<div id=\"flights\">";
			$hhtml = "<div id=\"flights-title\">";

			foreach ($flights as $flight) {
				$hhtml .= "<div class=\"title\">$flight</div>";
				$fhtml = "<div class=\"flight $flight\">";
				$stmt = $pdo->prepare("SELECT capid FROM ".DB_TABLES['Flights']." WHERE Flight = :fly;");
				$stmt->bindValue(":fly", $flight);
				$data = DBUtils::ExecutePDOStatement($stmt);
				foreach ($data as $datum) {
					$mem = Member::Estimate($datum['capid']);
					$fhtml .= "<div id=\"{$datum['capid']}\" class=\"cadet\">";
					$fhtml .= "$mem->RankName<input name=\"capids[]\" type=\"hidden\" value=\"$mem->uname:$flight\" />";
					$fhtml .= "</div>";
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
							'Text' => 'Administratoin',
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
				'Staff'
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
			print_r($e['form-data']);
			foreach ($e['form-data']['capids'] as $capid) {
				$data = explode(':', $capid);
				$stmt = $pdo->prepare("UPDATE ".DB_TABLES['Flights']." SET Flight=:flight WHERE capid=:cid AND AccountID=:aid;");
				$stmt->bindValue(":flight", $data[1]);
				$stmt->bindValue(":cid", $data[0]);
				$stmt->bindValue(':aid', $a->id);
				if (!$stmt->execute()) {
					trigger_error($stmt->errorInfo()[2], 512);
				}
			}
		}
	}