<?php

	require_once (BASE_DIR . "lib/logger.php");

	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			$nowtime = time();
			$logger = New Logger ("CreateAccount");

			if (!isset($e['uri'][$e['uribase-index']])) {
				$logger->Log($nowtime." finishaccount: failure: empty token passed token passed", 8);
				return [
					'error' => 311
				];
			}

			$token = $e['uri'][$e['uribase-index']];

			if (!Member::IsValidToken($token)) {
				$logger->Log($nowtime." token: ".$token." finishaccount: failure: invalid token passed", 8);
				return [
					'error' => 315
				];
			}

			$form = new AsyncForm();

			$message = "<div style=\"line-height: 1.6em\">Please select a user name for use in authenticating your access to CAPUnit.com. ";
			$message .= "User names should be 45 characters or less. Only one user name may be associated with each CAPID. ";
//			$message .= "located <a href='https://mdx89.capunit.com/tandc'>here</a>.  Our Privacy Policy may be accessed at <a href='https:/$
			$message .= "</div>";
			$message2 = "<div style=\"line-height: 1.6em\">Please enter and confirm a password. ";
			$message2 .= "Passwords must be greater than 10 characters in length and must consist of at least one of each ";
			$message2 .= "of the following: uppercase character, lowercase character, special character*, and a number. ";
			$message2 .= "Additionally, passwords may not be the same as the user name.</br>";
			$message2 .= "* a special character is a space character or one of the following symbols: ";
			$message2 .= '^ ! @ # $ % & * ( ) { } _ + - = < > , . ? / [ ] \ | ; \' "';
			$message2 .= "</div>";

			$form
				->addField('usernamerules',$message,'textread')
				->addField('username', 'Please choose a username')
				->addField('passwordrules',$message2,'textread')
				->addField('password1', 'Enter password', 'password')
				->addField('password2', 'Confirm password', 'password')
				->addHiddenField('token', $token);

			$form->reload = false;

			$form->setSubmitInfo(
				'Create Account',
				Null,
				Null,
				Null,
				true
			);

			return [
				'title' => 'Finish account setup',
				'body' => [
					'MainBody' => $form."",
					'BreadCrumbs' => UtilCollection::GenerateBreadCrumbs([
						[
							'Text' => 'Home',
							'Target' => '/'
						],
						[
							'Text' => 'Finish account setup',
							'Target' => '/finishaccount'
						]
					])
				]
			];
		}

		public static function doPost ($e, $c, $l, $m, $a) {
			$nowtime = time();
			$logger = New Logger ("CreateAccount");

			if (!isset($e['raw']['token']) || !isset($e['raw']['username']) || !isset($e['raw']['password1']) || !isset($e['raw']['password2'])) {
				$logger->Log($nowtime." username: ".$e['raw']['username']." token: ".$e['raw']['token']." finishaccount: failure: empty value", 8);
				return [
					'error' => 311
				];
			}

			if ($e['raw']['password1'] != $e['raw']['password2']) {
				$logger->Log($nowtime." username: ".$e['raw']['username']." token: ".$e['raw']['token']." finishaccount: failure: passwords do not match", 8);
				return 'The supplied passwords do not match';
			}


			$token = $e['raw']['token'];

			$status = Member::AddUser($token, $e['raw']['username'], $e['raw']['password1']);

			if (!$status['success']) {
				$logger->Log($nowtime." username: ".$e['raw']['username']." token: ".$token." finishaccount: failure: ".$status['reason'], 8);
				return $status['reason'];
			}

			$logger->Log($nowtime." username: ".$e['raw']['username']." token: ".$token." finishaccount: success", 8);
			return "<script>getHtml('/finishaccountdone');</script>";
		}
	}
