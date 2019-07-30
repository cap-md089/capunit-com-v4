<?php
	class Output {
		public static function doGet($e, $c, $l, $m, $a) {
			$html = "";
			if (!$l) {
				$form = new AsyncForm('/signin', 'Sign In');
				$form->reload = false;

				$message = "<div style=\"line-height: 1.6em\">Enter your CAPUnit.com login information below to sign in to the site. ";
				$message .= "By logging into this site you agree to the terms and conditions ";
				$message .= "located <a href='https://mdx89.capunit.com/tandc'>here</a>.  Our Privacy Policy may be accessed at <a href='https://mdx89.capunit.com/privacy'>this page</a>.";
				$message .= "</div>";
				$form->addField('eula',$message,'textread');
				$form->addField('name', 'User Name')->addField('password', 'Password', 'password')->setSubmitInfo('Sign In');
				$form->addHiddenField('returnurl', isset($e['raw']['returnurl']) ? $e['raw']['returnurl'] : '/');

				$createlink = "Don't have an account yet? ";
				$createlink .= new Link("createaccount", "Create your account here");
				$createlink .= " and gain access to all of the great features of CAPUnit.com!";

				$html = $form . $createlink;
			} else {
				if ($e['raw']['returnurl'][0] == 'h') {
					$url = strpos($e['raw']['returnurl'], '?') == FALSE ? ("{$e['raw']['returnurl']}?capid=" . $m->capid) : ("{$e['raw']['returnurl']}&capid=" . $m->capid);
					$html = "<script>document.location.href = '{$url}';</script>";
				} else {
					$html = "<script>getHtml('{$e['raw']['returnurl']}');</script>";
				}
			}

			return [
				'title' => 'Sign in',
				'body' => [
					'MainBody' => $html,
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

			$m = Member::Create($e['raw']['name'], $e['raw']['password'], $a);

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

			if ($e['raw']['returnurl'][0] == 'h') {
				$url = strpos($e['raw']['returnurl'], '?') == FALSE ? ("{$e['raw']['returnurl']}?capid=" . $m->capid) : ("{$e['raw']['returnurl']}&capid=" . $m->capid);
				$html = "<script>document.location.href = '{$url}';</script>";
			} else {
				$html = "<script>setCookies($cookies);getHtml('{$e['raw']['returnurl']}');</script>";
			}

			return $html;
		}
	}
