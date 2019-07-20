<?php
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			if (!isset($e['uri'][$e['uribase-index']])) {
				return [
					'error' => 311
				];
			}

			$token = $e['uri'][$e['uribase-index']];

			if (!Member::IsValidToken($token)) {
				return [
					'error' => 311
				];
			}

			$form = new AsyncForm();

			$form
				->addField('username', 'Please choose a username')
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
			if (!isset($e['raw']['token']) || !isset($e['raw']['username']) || !isset($e['raw']['password1']) || !isset($e['raw']['password2'])) {
				return [
					'error' => 311
				];
			}

			if ($e['raw']['password1'] != $e['raw']['password2']) {
				return 'The supplied passwords do not match';
			}

			$token = $e['raw']['token'];

			$status = Member::AddUser($token, $e['raw']['username'], $e['raw']['password1']);

			if (!$status['success']) {
				return $status['reason'];
			}

			return 'Your user account has been successfully created! '.JSSnippets::SigninLink("Sign in now");
		}
	}
