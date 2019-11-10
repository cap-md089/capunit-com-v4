<?php
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			if (!$l) return ['error' => 411];
			// if (!$a->paid) return ['error' => 501];
			if (!$a->hasMember($m) && !$m->IsRioux) return ['error' => 431];
			$targetexpiring = time()+(60*60*24*30); //one month from now
			$targetexpired = time(); //now
			$color = '';

			$pdo = DB_Utils::CreateConnection();

			$cadets = []; $seniorm = [];
			foreach ($a->genMembers() as $mem) {
					$label = "$mem->uname: $mem->RankName";
					if($mem->Expiration < $targetexpiring && $mem->Expiration >= $targetexpired) {
							$color = 'color:orange';
							$checked = 'true';
					} else if ($mem->Expiration < $targetexpired) {
							$color = 'color:red';
							$checked = 'false';
					} else {
							$color = '';
							$checked = 'true';
					}
					if($mem->seniorMember) {
							$seniorm[] = $label;
							$seniorcolor[] = $color;
							$checked == 'true' ? $seniorchecked[] = $label : $seniorchecked[] = '';
					} else {
							$cadets[] = $label;
							$cadetcolor[] = $color;
							$checked == 'true' ? $cadetchecked[] = $label : $cadetchecked[] = '';
					}
			}

			$legend = 'Members colored orange are within 30 days of their membership expiring.  Members colored red are those whose membership has already expired.  Expired memberships continue to appear in CAPWATCH downloads for a period of 90 days following expiration.';
			$form = new AsyncForm (Null, "Select members to include in email address listing", Null, "MultiAdd");
			$form
				->addField("cadets", 'Cadets (and parents)', 'multcheckbox', Null, $cadets, $cadetchecked, Null, $cadetcolor)
				->addField('seniorm', 'Senior Members', 'multcheckbox', Null, $seniorm, $seniorchecked, Null, $seniorcolor)
				->setOption('reload', false);

			$form->setSubmitInfo('Submit', null, null, null, false);

			return [
				'body' => [
					'MainBody' => $form.'</br><h3>'.$legend.'</h3>',
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
			$seniorm = explode(', ', AsyncForm::ParseCheckboxOutput($e['form-data']['seniorm'], $seniorm));

			$nc = [];
			foreach ($cadets as $cadet) {
				$nc[] = explode(': ', $cadet)[0];
			}
			foreach ($seniorm as $senior) {
				$nc[] = explode(': ', $senior)[0];
			}

			$emails = '';
			foreach ($nc as $n) {
				$mem = OldMember::Estimate($n);
				if ($mem && $mem->uname != 0) {
					$memails = $mem->getAllEmailAddresses();
					$emails .= $memails;
				}
			}
			$emails = rtrim($emails, '; ');

			return $emails."<script>$('html').animate({scrollTop: '500px'}, 'slow');</script>";
		}
	}
