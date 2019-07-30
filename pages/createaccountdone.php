<?php
	class Output {
		public static function doGet($e, $c, $l, $m, $a) {

			$message = "Thank you for requesting an account. Please check your inbox for a web link to complete your account setup.  This link will only be valid for 24 hours.";


			return [
				'title' => 'Create Account',
				'body' => [
					'MainBody' => $message . "",
					'BreadCrumbs' => UtilCollection::GenerateBreadCrumbs([
						[
							'Text' => 'Home',
							'Target' => '/'
						]
					])
				]
			];
		}

	}
