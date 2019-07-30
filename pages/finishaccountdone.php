<?php
	class Output {
		public static function doGet($e, $c, $l, $m, $a) {

			$newLink = new Link('signin', 'Sign in');
			$message = "Your CAPUnit.com account has been created! Please sign in at the ".$newLink." page.";

			return [
				'title' => 'Account Creation Success',
				'body' => [
					'MainBody' => $message . "",
					'BreadCrumbs' => UtilCollection::GenerateBreadCrumbs([
						[
							'Text' => 'Home',
							'Target' => '/'
						],
						[
							'Text' => 'Sign in',
							'Target' => '/signin'
						]
					])
				]
			];
		}

	}
