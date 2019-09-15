<?php
	class Output {
		public static function doGet($e, $c, $l, $m, $a) {
			if (!$l) {
				return [
					'error' => 411
				];
			}
			$form = new AsyncForm(null, 'Change password');
			$form->reload = false;

			$form->addField('password', 'Password', 'password');
			$form->addField('confirmPassword', 'Confirm Password', 'password');

			return [
				'title' => 'Change password',
				'body' => [
					'MainBody' => $form . '',
					'BreadCrumbs' => UtilCollection::GenerateBreadCrumbs([
						[
							'Text' => 'Home',
							'Target' => '/'
						],
						[
							'Text' => 'Admin',
							'Target' => '/admin'
						],
						[
							'Text' => 'Change password',
							'Target' => '/changepassword'
						]
					])
				]
			];
		}

		public static function doPost($e, $c, $l, $m, $a) {
			if (!$l) {
				return ['error' => 411];
			}

			if ($e['raw']['password'] != $e['raw']['confirmPassword']) {
				return 'Passwords must match';
			}

			$res = $m->setPassword($e['raw']['password']);

			if ($res['success']) {
				return 'Password change successful';
			} else {
				return $res['reason'];
			}
		}
	}
