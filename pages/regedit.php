<?php
	function treeView ($data, $chain) {
		if (gettype($data) == 'object') {
			$dl = new DetailedListPlus ();
			foreach ($data as $k => $v) {
				$dl->addElement($k, treeView($v, $chain == '' ? $k : "$chain.$k"));
			}
			return $dl;
		} else {
			$form = new AsyncForm ();
			$form->addField($chain, "$chain", Null, Null, Null, $data);
			$form->addHiddenField('key', $chain);
			return $form->getHtml();
		}
	}

	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			if (!$l) return ['error' => 411];
			if (!($m->hasPermission('RegistryEdit') || $m->hasPermission('Developer'))) return ['error' => 401];

			return [
				'body' => [
					'MainBody' => treeView(Registry::$_data, '')->getHtml(),
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
							'Target' => '/regedit',
							'Text' => 'Edit Site Configuration'
						]
					])
				],
				'title' => 'Site Configuration'
			];
		}

		public static function doPost ($e, $c, $l, $m, $a) {
			if (!$l) return ['error' => 411];
			if (!($m->hasPermission('RegistryEdit') || $m->hasPermission('Developer'))) return ['error' => 401];
			
			$key = $e['form-data']['key'];
			$key2 = str_replace(".", "_", $key);
			$value = $e['form-data'][$key2];

			$pdo = DBUtils::CreateConnection();
			$stmt = $pdo->prepare("SELECT COUNT(*) AS Fields FROM ".DB_TABLES['Registry']." WHERE AccountID = :aid AND RegistryKey = :key;");
			$stmt->bindValue(':aid', $a->id);
			$stmt->bindValue(':key', $key);
			$data = DBUtils::ExecutePDOStatement($stmt);
			if ($data[0]['Fields'] == 0) {
				$stmt = $pdo->prepare("SELECT Type FROM ".DB_TABLES['Registry']." WHERE AccountID = 'www' AND RegistryKey = :key;");
				$stmt->bindValue(':key', $key);
				$data = DBUtils::ExecutePDOStatement($stmt)[0]['Type'];

				$stmt = $pdo->prepare("INSERT INTO ".DB_TABLES['Registry']." VALUES (:aid, :rke, :val, '$data');");
				$stmt->bindValue(':aid', $a->id);
				$stmt->bindValue(':rke', $key);
				$stmt->bindValue(':val', $value);
				if (!$stmt->execute()) {
					trigger_error($stmt->errorInfo()[2], E_USER_WARNING);
				}
			} else {
				$stmt = $pdo->prepare("UPDATE ".DB_TABLES['Registry']." SET Value = :val WHERE (AccountID = :aid AND RegistryKey = :key);");
				$stmt->bindValue(':val', $value);
				$stmt->bindValue(':key', $key);
				$stmt->bindValue(':aid', $a->id);
				if (!$stmt->execute()) {
					trigger_error($stmt->errorInfo()[2], E_USER_WARNING);
				}
			}
		}
	}