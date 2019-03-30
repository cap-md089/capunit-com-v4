<?php
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
//			if (!$l) return ['error' => 411];
			// if (!$a->paid) return ['error' => 501];

			$pdo = DB_Utils::CreateConnection();

			$ev = $e['uri'][$e['uribase-index']];
			$event = Event::Get($ev);

			if (!($m->hasPermission('SignUpEdit') || $event->isPOC($m))) return ['error' => 401];

			$IDForm = new AsyncForm (Null, "Scan your CAP ID card to register your attendance", Null, "CAPIDAdd");
			$IDForm->addHiddenField('func', 'addCAPID')
				->addHiddenField('ev', $ev)
				->addField('capid', ' ', null, null, null, null, 'autofocus')
				->setOption('reload', true)
				->setSubmitInfo('Add', null, null, null, true);

/* 			$mems = $a->getMembers();
			$cadets = []; $seniorm = [];
			foreach ($mems as $mem) {
				($mem->seniorMember ? $seniorm[] = "$mem->uname: $mem->RankName" : $cadets[] = "$mem->uname: $mem->RankName");
			}

			$form = new AsyncForm (Null, "Add people to event", Null, "MultiAdd");
			$form->addHiddenField('ev', $ev)
				->addHiddenField('func', 'addMembers')
				->addField("cadets", 'Cadets', 'multcheckbox', Null, $cadets, Null)
				->addField('seniorm', 'Senior Members', 'multcheckbox', Null, $seniorm, Null, Null)
				->setOption('reload', false);

			$form->setSubmitInfo('Submit', null, null, null, true);
*/
			return [
				'body' => [
					'MainBody' => $IDForm.'',
					'BreadCrumbs' => UtilCollection::GenerateBreadCrumbs([
						[
							'Target' => '/',
							'Text' => 'Home'
						],
						[
							'Target' => '/calendar',
							'Text' => 'Calendar'
						],
						[
							'Target' => '/eventviewer/'.$ev,
							'Text' => "View '$event->EventName'"
						],
						[
							'Target' => '/scanadd/'.$ev,
							'Text' => 'Scan attendance'
						]
					])
				],
				'title' => "Scan-Add for event $ev"
			];
		}

		public static function doPost ($e, $c, $l, $m, $a) {
			if (!$l) return ['error' => 411];
			if (!$a->paid) {return ['error' => 501];}

			if (!($m->hasPermission('SignUpEdit') || $event->isPOC($m))) return ['error' => 401];

			$pdo = DB_Utils::CreateConnection();

			$ev = $e['raw']['ev'];
			$event = Event::Get($ev);

			$attend = $event->getAttendance();

			$added = false;

/*			if ($e['raw']['func'] == 'addMembers') {
				$mems = $a->getMembers();
				$cadets = []; $seniorm = [];
				foreach ($mems as $mem) {
					($mem->seniorMember ? $seniorm[] = "$mem->uname: $mem->RankName" : $cadets[] = "$mem->uname: $mem->RankName");
				}

				$cadets = explode(', ', AsyncForm::ParseCheckboxOutput($e['form-data']['cadets'], $cadets));
				$seniorm = explode(', ', AsyncForm::ParseCheckboxOutput($e['form-data']['seniorm'], $seniorm));

				$nc = [];
				foreach ($cadets as $cadet) {
					$nc[] = explode(': ', $cadet)[0];
				}
				foreach ($seniorm as $senior) {
					$nc[] = explode(': ', $senior)[0];
				}

				foreach ($nc as $n) {
					$mem = Member::Estimate($n);
					if ($mem && $mem->uname != 0) {
						$attend->add($mem, false, "Multi-Add by $m->memberName ($m->uname) on ".date('d M Y'));
						$added = true;
					}
				}


			} else */ if ($e['raw']['func'] == 'addCAPID') {
				$mem = Member::Estimate(trim($e['form-data']['capid']));
				if ($mem && $mem->uname != 0) {
					$attend->add($mem, false, "Scan-Add by $m->memberName ($m->uname) on ".date('d M Y'));
					$added = true;
				}
			}

			if ($added == true) {
				SignUps::Add($a->id, $event->EventNumber);
			}
			return JSSnippet::PageRedirect("scanadd", [$ev]);
		}
	}
