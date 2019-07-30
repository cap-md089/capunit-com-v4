<?php
		require ('config.php');
		require_once (BASE_DIR.'lib/Account.php');
		require_once (BASE_DIR.'lib/Member.php');

		$_ACCOUNT = new Account('md089');

		$gesCount = 0;
		$cadetCount = 0;

		$member = Member::Create("542488", "app/xNODE303931");

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
