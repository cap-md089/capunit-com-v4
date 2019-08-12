<?php
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			if (!$l) return ['error' => 411];
			if (!$m->hasPermission('DownloadCAPWATCH')) return ['error' => 402];

			$message = "<div style=\"line-height: 1.6em\">CAPWATCH file download permission is obtained from your unit ";
			$message .= "commander via eServices. You may copy and paste the following text to populate the text boxes ";
			$message .= "in the CAPWATCH Request form. <br /><br /><br />";
			$message .= "<u>\"Name of local application the data will support\"</u><br />";
			$message .= "CAPUnit.com";
			$message .= "<br /><br />";
			$message .= "<u>\"Description of functionality provided by the local application\"</u><br />";
			$message .= "Integrated event management, sign-up and attendance management, pre-filled forms, ";
			$message .= "teams, flight membership, cadet empowerment, and much more.";
			$message .= "<br /><br />";
			$message .= "<u>\"Mission supports:\"</u><br />";
			$message .= "Select OPS, AE, and CP.";
			$message .= "<br /><br />";
			$message .= "<u>\"Method for receiving data:\"</u><br />";
			$message .= "Select API and Standard Download.";
			$message .= "<br /><br />";
			$message .= "<u>\"Organization:\"</u> pulldown will be pre-filled with your specific unit. ";
			$message .= "If you have responsibility for more than one organization, you will have to request ";
			$message .= "access to each organization individually.<br /><br /><br />";
			$message .= "A sample filled-in form is displayed here: <br />";
			$message .= "<img src=\"/images/CapwatchRequest.jpg\" width=\"640\">";

			return [
				'body' => [
					'MainBody' => $message.'',
					'BreadCrumbs' => UtilCollection::GenerateBreadCrumbs([
						[
							'Target' => '/',
							'Text' => 'Home'
						],
						[
							'Target' => '/admin',
							'Text' => 'Administration'
						],
						[
							'Target' => '/importcapwatch',
							'Text' => 'Import CAPWATCH'
						]
					])
				],
				'title' => 'Import CAPWATCH'
			];
		}
	}
