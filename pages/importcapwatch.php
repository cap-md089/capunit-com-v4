<?php
	define ("USER_REQUIRED", true);
	
	function mdate ($time) {
		return date('Y-m-d\TH:i:s', $time);
	}

    class Output {
        public static function doGet ($eventdata, $cookie, $loggedin, $member, $account) {
			if (!$loggedin) {
				return ['error' => 411];
			}
			//need to adjust permission query to appropriate for CAPWATCH import
			if (!$member->hasPermission("AddEvent")) {return ['error' => 402];}

			$organizations = $member->getCAPWATCHList();

			$form = new AsyncForm ();
			$i = 0;
			$form->addField('','The download and import process takes several minutes.  Results will be displayed when the process is complete.','textRead');
			if ($member->capid == 546319) {
				$form->addField('importOrgs','Import Organization Files','checkbox',Null,Null,'1');
			} else {
				$form->addHiddenField('importOrgs','true');
			}
			foreach ($organizations as $org => $name) {
				$form->addField("orgs".$i."[]", $name, 'checkbox');
				$form->addHiddenField("orgids[]", $org);
				$form->addHiddenField("orgnam[]", $name);
				$form->addHiddenField("orgnum[]", $i);
				$i++;
			}

			//$form->addField("orgs", "Organizations", "multcheckbox", Null, $organizations, Null, Null, true);

			// foreach ($organizations as $organization => $_) {
			// 	$retval = ImportCAPWATCH($member, $organization);
			// 	if ($retval) {
			// 		echo("CAPWATCH import by $member->$uname on organization $organization failed.");
			// 	} else {
			// 		echo("CAPWATCH import by $member->$uname on organization $organization succeeded.");
			// 	}
			// }

			$form->reload = false;

			return [
				'body' => [
					'MainBody' => $form.'',
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
							'Text' => 'Import CAPWATCH Files'
						]
					])
				],
				'title' => 'Import CAPWATCH Files'
			];
        }

		public static function doPost ($e, $c, $l, $m, $a) {
			if (!$l) return false;
			if (!$m->hasPermission("AddEvent")) {return ['error' => 402];}
			
			$orgs = [];

			foreach ($e['form-data']['orgnum'] as $num) {
				$orgs[] = $e['form-data']['orgs'.$num][0];
			}

			$ids = (explode(", ", AsyncForm::ParseCheckboxOutput($orgs, $e['form-data']['orgids'])));
			$names = (explode(", ", AsyncForm::ParseCheckboxOutput($orgs, $e['form-data']['orgnam'])));
			$errs = [];

			ob_start();
			$errors = 0;
			$counter = 0;
			foreach ($ids as $id) {
				$errs[] = ImportCAPWATCH($m, $id, $e['form-data']['importOrgs']);
				if ($errs[$counter]) {
					$errors = 1;
				}
				$counter += 1;
			}
			ob_end_clean();

			if ($errors == 0) {
				return "CAPWATCH files for all selected units processed successfully!<br>";
			} else {
				$retval = "Processing outcome for selected units is as follows:<br>"; 
				$counter=0;
				foreach ($ids as $id) {
					$retval.=$names[$counter];
					if($errs[$counter]) {
						$retval.=" ".$errs[$counter]."<br>";
					} else {//endif errs
						$retval.=" CAPWATCH file processed successfully.<br>";
					}
				} //end for
					
				return $retval;
			} // end else errors

		} //end doPost function
	
	} //end class Output