<?php
		require ('config.php');
		require_once (BASE_DIR.'lib/Account.php');
		require_once (BASE_DIR.'lib/Member.php');

		$_ACCOUNT = new Account('md089');

		$gesCount = 0;
		$cadetCount = 0;

		$member = Member::Estimate(542488);

		$member->cookieData = ".CAPAUTH=6028EE2B20B814EA897B6E70F0331731B2DB9C2B19C66F4D56E8234911807A5C54485C3F076E6E4035C88FF0C3F2F91524E1308789FBA320F5D8F7F961685884A70ACB55052B48D0FCEB67346831D50C0096F1E7C5FCB594636AD660AED947FFBAFFBD51CF2322F7DA3A0C67EF556647438670C0; path=/; secure; HttpOnly";

		$m = $member;
                $m->cookieData = preg_replace('/HttpOnly/', '', $m->cookieData); // cURL and NHQ don't like HttpOnly in the headers
                preg_match_all('/(?:ASP\.NET_SessionId|\.CAPAUTH|CAPCUSTOMER)=.*?;/', $m->cookieData, $cookies);
                $m->cookieData = implode(' ', $cookies[0]);

		$members = $_ACCOUNT->getMembers();

		foreach ($members as $mem) {
			if ($mem->seniorMember) {
				continue;
			}

			$card = $member->get101Card($mem->uname);

			$hasGes = false;

			foreach ($card['quals'] as $q) {
				if ($q['name'] == "GES") {
					$hasGes = $q['active'];
					break;
				}
			}

			echo "$mem->uname ($mem->RankName): " . ($hasGes ? "YES" : "NO") . "\n";

			$cadetCount++;
			if ($hasGes) {
				$gesCount++;
			}
		}

		echo "\n\nTotal cadets: $cadetCount\n";
		echo "GES cadets: $gesCount\n";
