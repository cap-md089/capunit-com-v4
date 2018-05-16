<?php
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			if (!$l) {return ['error' => 411];}
			if (!$m->hasPermission('Developer')) {return ['error' => 402];}

			if (isset($e['uri'][$e['uribase-index']])) {
				$data = $e['uri'][$e['uribase-index']];
				$mem = Member::Estimate($data, true);
				unset($m->sid);
				$m->uname = $data;
				$m->memberName = $mem->memberName;
				$m->memberRank = $mem->memberRank;
				$m->seniorMember = $mem->seniorMember;
				$m->getContact(); 

				$m->perms = $m->getAccessLevels();
				
				$m->flight = $mem->getFlight();
				$m->setSessionId();
				$json = json_encode($m->toObjectString());
				return [
					'body' => [
						'MainBody' => '<script>localStorage.setItem("LOGIN_DETAILS", '.$json.');getHtml("/admin");</script>'
					]
				];
			}

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
			$m->getContact();

			$m->perms = $mem->getAccessLevels();
			$m->flight = $mem->getFlight();
			$m->setSessionId();
			return json_encode($m->toObjectString());
		}
	}
