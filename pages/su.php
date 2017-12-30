<?php
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			if (!$l) {return ['error' => 411];}
			if (!$m->hasPermission('Developer')) {return ['error' => 402];}

			return [
				'body' => [
					'MainBody' => (new AsyncButton(Null, 'Pick someone', 'su')).'',
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
							'Target' => '/su',
							'Text' => 'Su'
						]
					])
				]
			];
		}

		public static function doPut ($e, $c, $l, $m, $a) {
			if (!$l) {return ['error' => 411];}
			if (!$m->hasPermission('Developer')) {return ['error' => 402];}

			$data = $e['raw']['predata'];

			$mem = Member::Estimate($data);

			unset($m->sid);
			$m->uname = $data;
			$m->memberName = $mem->memberName;
			$m->memberRank = $mem->memberRank;
			$m->seniorMember = $mem->seniorMember;
			$m->getCAPWATCHContact();
			$m->perms = $mem->getAccessLevels();
			$m->flight = $mem->getFlight();
			$m->setSessionId();
			return json_encode($m->toObjectString());
		}
	}