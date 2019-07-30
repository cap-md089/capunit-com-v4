<?php

	require_once (BASE_DIR . "lib/logger.php");

	class Output {
		public static function doGet($e, $c, $l, $m, $a) {
			$form = new AsyncForm('/createaccount', 'Create Account');
			$form->reload = false;

			$message = "<div style=\"line-height: 1.6em\">Enter your CAPID and email address as stored in eServices to register for a CAPUnit.com account. ";
			$message .= "You will receive an email with a link to select a username and password and complete the account registration process. ";
			$message .= "Only one CAPUnit.com account may be created per CAPID.";
			$message .= "</div>";
			$form->addField('register',$message,'textread');
			$form->addField('cid', 'CAP ID')->addField('email', 'Email Address')->setSubmitInfo('Create Account');

			return [
				'title' => 'Sign in',
				'body' => [
					'MainBody' => $form . "",
					'BreadCrumbs' => UtilCollection::GenerateBreadCrumbs([
						[
							'Text' => 'Home',
							'Target' => '/'
						],
						[
							'Text' => 'Create Account',
							'Target' => '/createaccount'
						]
					])
				]
			];
		}

		public static function doPost($e, $c, $l, $m, $a) {
			$nowtime = time();

			if (!(isset($e['raw']['cid']) && isset($e['raw']['email']))) {
				return json_encode(array (
					'valid' => false
				));
			}

			$logger = New Logger ("CreateAccount");

//sample link https://docs.google.com/forms/d/e/1FAIpQLSd4nLxwz-00SvAEC-6TTbbIylLI-VnhM6qMXC_pIpxamNt4uw/formResponse?usp=pp_url&entry.279664546=546319&entry.1923260016=grioux@gmail.com&entry.586826806=https://md089.capunit.com/account/1234567890&submit=Submit

			//get registration link to pass to form
			$tokenReturn = Member::StageUserCreation($e['raw']['cid'], $e['raw']['email']);
			print_r($tokenReturn);
			echo PHP_EOL . PHP_EOL;
			if (!$tokenReturn['success']) {
				if ($tokenReturn['reason'] == 'CAPID count') {
					$logger->Log($nowtime." CAPID: ".$e['raw']['cid']." email: ".$e['raw']['email']." createaccount: message: failure: duplicate CAPID request", 8);
					return ['error' => 313];
				} elseif ($tokenReturn['reason'] == 'Cannot find member') {
					$logger->Log($nowtime." CAPID: ".$e['raw']['cid']." email: ".$e['raw']['email']." createaccount: message: failure: CAPID not present in member table", 8);
					return ['error' => 314];
				} elseif ($tokenReturn['reason'] == 'Email mismatch') {
					$logger->Log($nowtime." CAPID: ".$e['raw']['cid']." email: ".$e['raw']['email']." createaccount: message: failure: email not available for CAPID", 8);
					return ['error' => 316];
				} else {
					$logger->Log($nowtime." CAPID: ".$e['raw']['cid']." email: ".$e['raw']['email']." createaccount: message: failure: undefined error", 8);
					return ['error' => 317];
				}
			}

			//populate form input values
			$inlink = 'https://docs.google.com/forms/d/e/1FAIpQLSd4nLxwz-00SvAEC-6TTbbIylLI-VnhM6qMXC_pIpxamNt4uw/';
			$inlink .= 'formResponse?usp=pp_url&entry.279664546=';
			$inlink .= $e['raw']['cid'];
			$inlink .= '&entry.1923260016=';
			$inlink .= $e['raw']['email'];
			$inlink .= '&entry.586826806=';
			$inlink .= "https://mdx89.capunit.com/finishaccount/" . $tokenReturn['token'];
			$inlink .= '&submit=Submit';

			//submit link
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $inlink);

			// Set so curl_exec returns the result instead of outputting it.
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

			// Get the response and close the channel.
			$response = curl_exec($ch);
			curl_close($ch);

			$logger->Log($nowtime." CAPID: ".$e['raw']['cid']." email: ".$e['raw']['email']." createaccount: message: success: account request", 8);

			return "<script>getHtml('/createaccountdone');</script>";
		}

	}
