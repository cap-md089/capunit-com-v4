<?php
	require "config.php";
	require_once "lib/Member.php";

	$_ACCOUNT = new Account('md089');

	$pdo = DBUtils::CreateConnection();

	$stmt = $pdo->prepare("SELECT * FROM Data_MbrContact WHERE Type LIKE '%EMAIL%';");
	$data = DBUtils::ExecutePDOStatement($stmt);

	$results = array();

	foreach ($data as $datum) {
		if (!isset($results[$datum['CAPID']])) {
			$tokenReturn = Member::StageUserCreation($datum['CAPID'], $datum['Contact']);
			if (!$tokenReturn['success']) {
				if (isset($tokenReturn['token'])) {
					fwrite(STDERR, $stmt->errorInfo()[2] . PHP_EOL);
				} else {
					$oldCont = OldMember::Estimate((int)$datum['CAPID'])->contact;
					fwrite(STDERR, "Error for " . $datum['CAPID'] . ": " . $tokenReturn['reason'] . PHP_EOL);
				}

				continue;
			}

			$member = OldMember::Estimate((int)$datum['CAPID']);

			$results[$datum['CAPID']] = [
				'ORGID' => $datum['ORGID'],
				'CAPID' => $datum['CAPID'],
				'RankName' => $member->RankName,
				'URL' => "https://md089.capunit.com/finishaccount/" . $tokenReturn['token'],
				'Contacts' => [
					[
						'Priority' => $datum['Priority'],
						'Contact' => $datum['Contact'],
						'Type' => $datum['Type']
					]
				]
			];
		} else {
			array_push($results[$datum['CAPID']]['Contacts'], [
				'Priority' => $datum['Priority'],
				'Contact' => $datum['Contact'],
				'Type' => $datum['Type']
			]);
		}
	}

	echo "ORGID,CAPID,RankName,URL\n";
	foreach ($results as $result) {
		echo $result['ORGID'] . "," . $result['CAPID'] . "," . $result['RankName'] . "," . $result['URL'];

		foreach ($result['Contacts'] as $contact) {
			echo ',' . $contact['Priority'] . ':' . $contact['Type'] . ':' . $contact['Contact'];
		}
	}
