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
			
			$stmt = $pdo->prepare("(select CAPID from ".DB_TABLES['Member']." where CAPID not in (SELECT capid as CAPID from ".DB_TABLES['Flights'].") and Rank like 'C/%');");
			$data = DBUtils::ExecutePDOStatement($stmt);
			foreach ($data as $datum) {
				$stmt = $pdo->prepare("INSERT INTO ".DB_TABLES['Flights']." (CAPID, Flight) VALUES (:id, 'Charlie');");
				$stmt->bindValue(":id", $datum['CAPID']);
				if (!$stmt->execute()) {
					trigger_error($stmt->errorInfo()[2], 512);
				}
			}

			$pager = new PaginatorC1();
			$forms = [];

			foreach ($flights as $flight) {
				$form = new AsyncForm();
				$form->reload = true;
				$stmt = $pdo->prepare("SELECT capid FROM ".DB_TABLES['Flights']." WHERE Flight = :fly;");
				$stmt->bindValue(":fly", $flight);
				$data = DBUtils::ExecutePDOStatement($stmt);
				foreach ($data as $datum) {
					$mem = Member::Estimate($datum['capid']);
					$form->addField($mem->uname, $mem->memberRank.' '.$mem->memberName, 'radio', Null, $flights, $flight);
					$form->addHiddenField('capid[]', $mem->uname);
				}
				$pager->addPage($flight, $form->getHtml());
			}

			return [
				'body' => [
					'MainBody' => $pager.'',
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

			$pdo = DBUtils::CreateConnection();

			if (isset($e['form-data']['capid'])) {
				foreach ($flights as $flight) {
					$capids = $e['form-data']['capid'];
					foreach ($capids as $capid) {
						$stmt = $pdo->prepare("UPDATE ".DB_TABLES['Flights']." SET Flight=:flight WHERE capid=:cid;");
						$stmt->bindValue(":flight", $e['form-data'][$capid]);
						$stmt->bindValue(":cid", $capid);
						if (!$stmt->execute()) {
							trigger_error($stmt->errorInfo()[2], 512);
						}
					}
				}
			}
		}
	}