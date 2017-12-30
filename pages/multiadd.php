<?php
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			if (!$l) return ['error' => 411];
			if (!$a->paid) return ['error' => 501];
			
			$pdo = DB_Utils::CreateConnection();

			$ev = $e['uri'][$e['uribase-index']];
			$event = Event::Get($ev);

			if (!($m->hasPermission('SignUpEdit') || $event->isPOC($m))) return ['error' => 401];
			
			$mems = $a->getMembers();
			$cadets = []; $seniorm = [];
			foreach ($mems as $mem) {
				($mem->seniorMember ? $seniorm[] = "$mem->uname: $mem->RankName" : $cadets[] = "$mem->uname: $mem->RankName");
			}

			$form = new AsyncForm (Null, "Add people to event", Null, "MultiAdd");
			$form->addHiddenField('ev', $ev)
				->addField("cadets", 'Cadets', 'multcheckbox', Null, $cadets, Null)
				->addField('seniorm', 'Senior Members', 'multcheckbox', Null, $seniorm, Null, Null)
				->setOption('reload', false);

			$form->setSubmitInfo('Submit', null, null, null, true);

			return [
				'body' => [
					'MainBody' => $form.'',
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
							'Target' => '/multiadd/'.$ev,
							'Text' => 'Add attendance'
						]
					])
				],
				'title' => "Multi-Add for event $ev"
			];
		}
		
		public static function doPost ($e, $c, $l, $m, $a) {
			if (!$l) return ['error' => 411];
			if (!$a->paid) {return ['error' => 501];}
			
			$pdo = DB_Utils::CreateConnection();

			$ev = $e['raw']['ev'];
			$event = Event::Get($ev);

			if (!($m->hasPermission('SignUpEdit') || $event->isPOC($m))) return ['error' => 401];

			$mems = $a->getMembers();
			$cadets = []; $seniorm = [];
			foreach ($mems as $mem) {
				($mem->seniorMember ? $seniorm[] = "$mem->uname: $mem->RankName" : $cadets[] = "$mem->uname: $mem->RankName");
			}

			$attend = $event->getAttendance();

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
				}
			}

			return JSSnippet::PageRedirect("eventviewer", [$ev]);
		}
	}