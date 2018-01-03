<?php

    class Output {
        public static function doGet ($e, $c, $l, $m, $a) {
            if (!$l) return ['error' => 411];
            if (!$m->hasPermission("AssignPosition")) return ['error' => 401];
			if (!$a->paid) {return ['error' => 501];}
            $form = new AsyncForm (Null, "Assign a Temporary Position");
            $form->addField('capid', (new AsyncButton(Null, 'Select Member', 'selectCAPIDForEventForm'))->getHtml('1'), 'text', 'capPOC1')
                ->addField('position', 'Position to assign', 'radio', Null, explode("\n", trim(file_get_contents(BASE_DIR."data/dutypositions.txt"))))
                ->addField('until', 'Duration of Assignment', 'datetime-local');
            $form->reload = false;

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
							'Target' => '/tduties',
							'Text' => 'Assign a temporary duty'
						]
                    ])
                ],
                'title' => 'Assign Duty Position'
            ];
        }

        public static function doPost ($e, $c, $l, $m, $a) {
            if (!$l) return ['error' => 411];
            if (!$m->hasPermission("AssignPosition")) return ['error' => 401];
			if (!$a->paid) {return ['error' => 501];}

            $pdo = DBUtils::CreateConnection();
            $stmt = $pdo->prepare("INSERT INTO ".DB_TABLES['TempDP']." VALUES (:dp, :exp, :cid, :aid);");
            $stmt->bindValue(":dp", trim($e['form-data']['position']));
            $stmt->bindValue(':exp', $e['form-data']['until']);
            $stmt->bindValue(':cid', $e['form-data']['capid']);
            $stmt->bindValue(':aid', $a->id);
            if (!$stmt->execute()) {
                trigger_error($stmt->errorInfo()[2], 512);
            }
            return "Duty assigned";
        }
    }