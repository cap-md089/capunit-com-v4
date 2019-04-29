<?php
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			if (!$l) return ['error' => 411];
			if (!$m->hasPermission('PermissionsManagement')) return ['error' => 401];

			$pdo = DBUtils::CreateConnection();
			$stmt = $pdo->prepare("SELECT capid, AccessLevel FROM ".DB_TABLES['AccessLevel']." WHERE AccountID = :aid;");
			$stmt->bindValue(":aid", $a->id);
			$data = DBUtils::ExecutePDOStatement($stmt);

			$form = new AsyncForm (Null, "Permissions");
			$consts = (new ReflectionClass("Permissions"))->getConstants();
			$constss = [];
			foreach ($consts as $const => $nil) {
				$constss[] = $const;
			}
			$form->addField("", "", "radio", Null, $constss, Null, "templateAdder");

			foreach ($data as $user) {
				$mem = Member::Estimate($user['capid']);
				$form
					->addField($user['capid'], $mem->memberRank . ' ' . $mem->memberName, "radio", Null, $constss, $mem->AccessLevel)
					->addHiddenField('capids[]', $user['capid'])
					->addField("", (new AsyncButton(Null, "Remove user", "memberPermissionsRemoveAUser"))->getHtml($user['capid']), "textread");
			}

			$form->addField("", (new AsyncButton(Null, "Add user", "memberPermissionsAddAUser")).'', "textread");

			return [
				'body' => [
					'MainBody' => $form.'',
					'BreadCrumbs' => UtilCollection::GenerateBreadCrumbs([
						[
							'Target' => '/',
							'Text' => 'Home'
						],
						[
							'Target' => '/admin',
							'Text' => 'Administration'
						],
						[
							'Target' => '/permmgmt',
							'Text' => 'Permission Mamagement'
						]
					])
				],
				'title' => 'Permissions management'
			];
		}

		public static function doPost ($e, $c, $l, $m, $a) {
			if (!$l) return ['error' => 411];
			if (!$m->hasPermission('PermissionsManagement')) return ['error' => 401];

			$pdo = DBUtils::CreateConnection();

			foreach ($e['form-data']['capids'] as $capid) {
				$stmt = $pdo->prepare("SELECT COUNT(capid) AS Rows FROM ".DB_TABLES['AccessLevel']." WHERE AccountID = :aid AND capid = $capid;");
				$stmt->bindValue(":aid", $a->id);
				$data = DBUtils::ExecutePDOStatement($stmt);
				$count = $data[0]["Rows"];
				$mem = Member::Estimate($capid);
				echo $count;
				if ($count == 0) {
					$stmt = $pdo->prepare("INSERT INTO ".DB_TABLES['AccessLevel']." VALUES ($capid, :alevel, :mname, :mrank, :aid);");
					$stmt->bindValue(":alevel", $e['form-data'][$capid]);
					$stmt->bindValue(":mname", $mem->memberName);
					$stmt->bindValue(':mrank', $mem->memberRank);
					$stmt->bindValue(':aid', $a->id);
					if (!$stmt->execute()) { 
						trigger_error($stmt->errorInfo()[2], 512);
					}
				} else {
					$stmt = $pdo->prepare("UPDATE ".DB_TABLES['AccessLevel']." SET AccessLevel = :alevel, memname = :mname, memrank = :mrank WHERE capid = $capid AND AccountID = :aid");
					$stmt->bindValue(":alevel", $e['form-data'][$capid]);
					$stmt->bindValue(":mname", $mem->memberName);
					$stmt->bindValue(':mrank', $mem->memberRank);
					$stmt->bindValue(':aid', $a->id);
					if (!$stmt->execute()) { 
						trigger_error($stmt->errorInfo()[2], 512);
					}
				}
			}
		}

		public static function doPut ($e, $c, $l, $m, $a) {
			if (!$l) return ['error' => 411];
			if (!$m->hasPermission('PermissionsManagement')) return ['error' => 401];

			$pdo = DBUtils::CreateConnection();
			$stmt = $pdo->prepare("DELETE FROM ".DB_TABLES['AccessLevel']." WHERE AccountID = :aid AND capid = :cid;");
			$stmt->bindValue(":aid", $a->id);
			$stmt->bindValue(":cid", $e['raw']['data']);
			if (!$stmt->execute()) { 
				trigger_error($stmt->errorInfo()[2], 512);
			}
			return 'Member deleted';
		}
	}
