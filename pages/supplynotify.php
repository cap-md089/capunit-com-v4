<?php
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			$form = new AsyncForm();

			$form->addField('request', 'Make a request');

			return [
				'body' => [
					'MainBody' => $form
				]
			];
		}

		public static function doPost ($e, $c, $l, $m, $a) {
			if (!$l) return false;
			$request = $e['form-data']['request'];
			$capid = $m->capid;
			$accountid = $a->id;

			$pdo = DBUtils::CreateConnection();
			$stmt = $pdo->prepare("INSERT INTO ".DB_TABLES['SupplyNotifications']." VALUES (:capid, :req, :aid)");
			$stmt->bindValue(':capid', $capid);
		//	$stmt->
		}
	}
