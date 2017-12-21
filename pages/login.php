<?php
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			$form = new AsyncForm('/login', 'Sign in', 'login', 'login');
			
			$form
				->addField('name', 'CAP ID')
				->addField('password', 'Password', 'password')
				->setSubmitInfo('Log in');

			$form->addHiddenField('redirectTo', $e['parameter']['redirect']);

			return [
				'body' => [
					'SideNavigation' => "<ul><li><a href=\"#\" onclick=\"window.history.go(-1);return false;\"><span class=\"arrow\"></span><span>Go back</span></a></li></ul>",
					'MainBody' => $form.''
				],
				'title' => 'Login'
			];
		}

		public static function doPost ($e, $c, $l, $m, $a) {
			
		}
	}