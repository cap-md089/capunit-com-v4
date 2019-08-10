<?php
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			if (!$l) return ['error' => 411];
			// if (!$a->paid) return ['error' => 501];
			if (!$a->hasMember($m)) return ['error' => 431];

			$pdo = DB_Utils::CreateConnection();

			$cadets = []; $seniorm = [];
			foreach ($a->genMembers() as $mem) {
				($mem->seniorMember ? $seniorm[] = "$mem->uname: $mem->RankName" : $cadets[] = "$mem->uname: $mem->RankName");
			}

			$form = new AsyncForm (Null, "Select members to include parents only in email address listing", Null, "MultiAdd");
			$form
				->addField("cadets", 'Cadets', 'multcheckbox', Null, $cadets, $cadets)
				->setOption('reload', false);

			$form->setSubmitInfo('Submit', null, null, null, false);

			return [
				'body' => [
					'MainBody' => $form.'',
					'BreadCrumbs' => UtilCollection::GenerateBreadCrumbs([
						[
							'Target' => '/',
							'Text' => 'Home'
						]
					])
				],
				'title' => "Email Selector"
			];
		}

		public static function doPost ($e, $c, $l, $m, $a) {
			if (!$l) return ['error' => 411];
			if (!$a->paid) {return ['error' => 501];}

			$pdo = DB_Utils::CreateConnection();

//			if (!($m->hasPermission('SignUpEdit') || $event->isPOC($m))) return ['error' => 401];

			$mems = $a->getMembers();
			$cadets = []; $seniorm = [];
			foreach ($mems as $mem) {
				($mem->seniorMember ? $seniorm[] = "$mem->uname: $mem->RankName" : $cadets[] = "$mem->uname: $mem->RankName");
			}

			$cadets = explode(', ', AsyncForm::ParseCheckboxOutput($e['form-data']['cadets'], $cadets));

			$nc = [];
			foreach ($cadets as $cadet) {
				$nc[] = explode(': ', $cadet)[0];
			}

			$emails = '';
			foreach ($nc as $n) {
				$mem = OldMember::Estimate($n);
				if ($mem && $mem->uname != 0) {
					$memails = $mem->getParentEmailAddresses();
					$emails .= $memails;
				}
			}
			$emails = rtrim($emails, '; ');

			return $emails."<script>$('html').animate({scrollTop: '500px'}, 'slow');</script>";
		}
	}
