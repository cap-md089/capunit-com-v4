<?php
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			if (!$l) return ['error' => 411];
			if (!$m->hasPermission('DownloadCAPWATCH')) return ['error' => 402];

			$pdo = DBUtils::CreateConnection();
			$stmt = $pdo->prepare("SELECT * FROM ".DB_TABLES['CAPWATCHUnits']." WHERE CAPID = :cid;");
			$stmt->bindValue(":cid", $m->uname);
			$data = DBUtils::ExecutePDOStatement($stmt);

			$form = new AsyncForm (Null, "CAPWATCH Import");
			$messageText = "<div style=\"line-height: 1.6em\">Enter one unit identifier in each ";
			$messageText.= "text field. To download data for additional units, ";
			$messageText .= "click <u>Add unit</u> to display an additional text field. Unit ";
			$messageText .= "identifiers must be in the format <nobr>RRR-WW-UUU</nobr> or ";
			$messageText .= "<nobr>RRR-WWW-UUU,</nobr> to include the dashes between elements and ";
			$messageText .= "leading zeros on the unit number. Duplicate unit identifiers ";
			$messageText .= "will be removed during processing.</div>";
			$form->addField('description', $messageText, 'textread');
			$form->addField("units[]", (new AsyncButton(Null, 'Remove unit', 
					'removeUnit'))->getHtml(), Null, Null, Null, Null, "templateAdder");

			foreach ($data as $unit) {
					$form->addField("units[]", (new AsyncButton(Null, 'Remove unit', 
							'removeUnit'))->getHtml(), Null, Null, Null, $unit['Squadron'], 
							$unit['Squadron']);
			}

			$form->addField("", (new AsyncButton(Null, "Add unit", "addUnit"))->getHtml(), "textread");
			$messageText = "<div style=\"line-height: 1.6em\">Your eServices password entered here ";
			$messageText .= "will be used once then discarded.  It will not be saved.  In order ";
			$messageText .= "to successfully download and import CAPWATCH files, you must first ";
			$messageText .= "request and receive authorization on eServices by your unit commander. ";
			$messageText .= "Go to <a href=\"https://www.capnhq.gov/cap.capwatch.web/Modules/CapwatchRequest.aspx\" 
					target=\"_blank\">";
			$messageText .= "this eServices page</a> to request permission. ";
			$messageText .= "(if you receive an error message after logging in, close the tab and click ";
			$messageText .= "the link here again to open the request page) ";
			$messageText .= "Detailed instructions ";
			$messageText .= "on how to fill out the web form are located on our site at "; 
			$messageText .= new Link('eservicesrequest', 'this page') . ".</div>";
			$form->addField('CAPWATCH', $messageText, 'textread');
			$form->addField("esp", "eServices password", "password");
			if($m->capid == 546319) {
					$form->addField('importOrgs','Import Organization Files','checkbox',Null,Null,'true');
			} else {
					$form->addHiddenField('importOrgs','false');
			}
			$messageText = "<div style=\"line-height: 1.6em\">There may be a significant delay ";
			$messageText .= "after the \"IMPORT\" button is clicked while the files are fetched ";
			$messageText .= "and imported. ";
			$messageText .= "A message will be displayed immediately below  ";
			$messageText .= "the \"CAPWATCH Import\" title at the top of the page when processing is complete.";
			$messageText .= "</div>";
			$form->addField('CAPWATCH', $messageText, 'textread');
			$form->setSubmitInfo("Import");

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
							'Text' => 'Import CAPWATCH'
						]
					])
				],
				'title' => 'Import CAPWATCH'
			];
		}

		public static function doPost ($e, $c, $l, $m, $a) {
			if (!$l) return ['error' => 411];
			if (!$m->hasPermission('DownloadCAPWATCH')) return ['error' => 402];

			$pdo = DBUtils::CreateConnection();
			$stmt = $pdo->prepare("SELECT DISTINCT Region FROM ".DB_TABLES['Organization'].";");
			$regions_raw = DBUtils::ExecutePDOStatement($stmt);
			$stmt = $pdo->prepare("SELECT DISTINCT Wing FROM ".DB_TABLES['Organization'].";");
			$wings_raw = DBUtils::ExecutePDOStatement($stmt);
			$stmt = $pdo->prepare("SELECT DISTINCT Unit FROM ".DB_TABLES['Organization'].";");
			$unitnums_raw = DBUtils::ExecutePDOStatement($stmt);

			$regions = [];
			$wings = [];
			$unitnums = [];

			foreach ($wings_raw as $wing) {
				$wings[] = $wing['Wing'];
			}

			foreach ($unitnums_raw as $unitnum) {
				$unitnums[] = $unitnum['Unit'];
			}

			foreach ($regions_raw as $region) {
				$regions[] = $region['Region'];
			}

			$retMessage = "";
			$uniqueuid = [];
			foreach ($e['form-data']['units'] as $uid) {
					if(!in_array($uid, $uniqueuid) && (strlen($uid) >= 10) ) 
							{ array_push($uniqueuid, strtoupper($uid)); }
			}
			$stmt = $pdo->prepare("DELETE FROM ".DB_TABLES['CAPWATCHUnits']." WHERE CAPID=:cid;");
			$stmt->bindValue(':cid', $m->uname);
			$stmt->execute();

			if($e['form-data']['importOrgs'] == 'true') {
					$unitcounter=0;
			} else {
					$unitcounter=1;
			}

			foreach ($uniqueuid as $uid) {
				if(strlen($uid) >= 10) {
					//validate input
					$validInput = false;
					$mysplit = explode("-", $uid);
					if(count($mysplit) == 3) {
							if(in_array($mysplit[0], $regions) && 
								in_array($mysplit[1], $wings) && 
								in_array($mysplit[2], $unitnums)) {
								$sqlstmt = "SELECT ORGID FROM ".DB_TABLES['Organization'];
								$sqlstmt .= " WHERE Region=:rid AND Wing=:wid AND Unit=:uid;";
									$stmt = $pdo->prepare($sqlstmt);
									$stmt->bindValue(':rid', $mysplit[0]);
									$stmt->bindValue(':wid', $mysplit[1]);
									$stmt->bindValue(':uid', $mysplit[2]);
									$unitquery = DBUtils::ExecutePDOStatement($stmt);
									if(count($unitquery) == 1) {
											$orgID = $unitquery[0]['ORGID'];
											$validInput = true;
									}
							}
					}



					if(!$validInput) {
							$retMessage .= "Unit ".$uid." is not a valid unit identifier and will ";
							$retMessage .= "not be saved.<br />";	
					} else {
							//need to download and import here
							$importMessage = "";

							$retVal = ImportCAPWATCH($m, $orgID, $e['form-data']['esp'], !$unitcounter);
							if($retVal === 0) {
									$retMessage .= "Unit ".$uid." Imported.<br />";
							} else {
									$retMessage .= "Unit ".$uid." ".$retVal.".<br />";
							}

							//write value to database
							$sqlstmt = "INSERT INTO ".DB_TABLES['CAPWATCHUnits'];
							$sqlstmt .= " (CAPID, Squadron) VALUES (:cid, :sqn);";
							$stmt = $pdo->prepare($sqlstmt);
							$stmt->bindValue(':cid', $m->uname);
							$stmt->bindValue(':sqn', $uid);
							if (!$stmt->execute()) {
									trigger_error($stmt->errorInfo()[2], 512);
							}
							$unitcounter = 1;
					}
				} else {
					if(strlen($uid) >= 1) {
							$retMessage .= "Unit ".$uid." is not a valid unit identifier and will ";
							$retMessage .= "not be saved.<br />";
					}
				}
			}
			return $retMessage;
		}
	}
