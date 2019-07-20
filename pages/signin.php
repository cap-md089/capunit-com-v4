<?php
	class Output {
		public static function doGet($e, $c, $l, $m, $a) {
			$form = new AsyncForm('/signin', 'Sign in');
			$form->reload = false;

			$message = "<div style=\"line-height: 1.6em\">Enter your eServices login information below to sign into the site. Your password is not";
			$message .= "permanently stored.  By providing your eServices information you agree to the terms and conditions ";
			$message .= "which may be requested via email to <a href='mailto:support@capunit.com'>support@capunit.com</a></div>";
			$form->addField('eula',$message,'textread');
			$form->addField('name', 'CAP ID')->addField('password', 'Password', 'password')->setSubmitInfo('Log in');
			$form->addHiddenField('returnurl', isset($e['raw']['returnurl']) ? $e['raw']['returnurl'] : '/');

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
							'Text' => 'Sign in',
							'Target' => '/signin'
						]
					])
				]
			];
		}

		public static function doPost($e, $c, $l, $m, $a) {
			if (!(isset($e['raw']['name']) && isset($e['raw']['password']))) {
				return json_encode(array (
					'valid' => false
				));
			}

			$m = Member::Create($e['raw']['name'], $e['raw']['password']);

			$cookies = null;

			if ($m->success) {
				$cookies = json_encode(array(
					'valid' => true,
					'cookie' => array(
						'LOGIN_DETAILS' => $m->toObjectString()
					)
				));
			} else {
				if (isset($m->data['reset']) && $m->data['reset']) {
					$cookies = json_encode(array (
						"valid" => false,
						"reset" => true
					));
				} else if (isset($m->data['down']) && $m->data['down']) {
					$cookies = json_encode(array (
						"valid" => false,
						"down" => true
					));
				} else {
					$cookies = json_encode(array (
						"valid" => false,
						"cookie" => array ()
					));
				}
			}

			$returnurl = $e['raw']['returnurl'];

			return JSSnippet::PrepareJS("setCookies($cookies);getHtml('$returnurl');");
		}
	}
