<?php
	require_once (BASE_DIR."lib/general.php");
	require_once (BASE_DIR."lib/Notify.php");


	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			if (!$l) {return ['error' => 411];}

                        $pdo = DBUtils::CreateConnection();

			if (!$m->hasPermission('PersonnelFiles')) {
                                $stmt = $pdo->prepare("SELECT * FROM ".DB_TABLES['FileMemberAssignments']." WHERE MemberID = :mid;");
                                $stmt->bindValue(':mid', $m->uname);
                                $filedata = DBUtils::ExecutePDOStatement($stmt);
                                // if (count($filedata) > 0) {
                                        $html = "<br /><br /><h2>Files for $m->RankName</h2>";
                                // }
                                $hasfiles = false;
				$rowhtml = "";
                                foreach ($filedata as $row) {
                                        $file = File::Get($row["FileID"]);
                                        if(!!$file) {
						$rowhtml .= "<tr><td>";
                                                $rowhtml .= (new FileDownloader($file->Name, $file->ID))." ";
						$rowhtml .= "</td><td>".$row['FileType'].'</td><td align="center">';
						if ($row['Requested'] > 0) {
							$rowhtml .= date("Y-m-d", $row['Requested']).'</td><td align="center">';
						} else {
							$rowhtml .= '--</td><td align="center">';
						}
						if ($row['Approved'] > 0) {
							$rowhtml .= date("Y-m-d", $row['Approved']).'</td><td>';
						} else {
							$rowhtml .= '--</td><td>';
						}
						$rowhtml .= $row['Award']."</td><td>".$row['Comment']."</td>";
						$rowhtml .= "</tr>";
                                                $hasfiles = true;
                                        }
                                }
                                if(!$hasfiles) { $html .= "There are no files associated with your account.<br />"; }
				else {
					$html .= "<table id=\"persdoctab\"><tr><th>File</th><th>File Type</th><th>Requested</th><th>Approved</th><th>Award</th><th>Comment</th></tr>";
					$html .= $rowhtml."</table>";
				}
                                $form = new AsyncForm (Null, 'Add File');
                                $form->addField ('memberFiles', '&nbsp;', 'file');
				$form->addField ('fileType', 'Select Type', 'select', null, [
					'CAPF 2', 'CAPF 2A', 'CAPF 5', 'CAPF 31', 'CAPF 60-80', 'CAPF 60-91',
					'CAPF 60-92', 'CAPF 60-93', 'CAPF 60-94',
					'Drill Test', 'Answer Sheet', 'Speech Critique', 'Essay Critique', 'SDA',
					'Other'
				], 'CAPF 2A');
				$form->addField ('requested', 'Form Requested', 'datetime-local');
				$form->addField ('approved', 'Form Approved', 'datetime-local');
				$form->addField ('award', 'Award Ribbon', 'select', null, [
					'Command Service Ribbon', 'Red Service Ribbon', 'Find Ribbon',
					'Air Search and Rescue Ribbon', 'Disaster Relief Ribbon',
					'IACE Ribbon', 'National Cadet Competition Ribbon',
					'National Color Guard Ribbon', 'Cadet Advisory Council Ribbon',
					'Cadet Community Service Ribbon', 'Cadet Special Activities Ribbon',
					'Cadet Orientation Pilot Ribbon', 'Counter Drug Ribbon',
					'Encampment Ribbon', 'Recruiter Ribbon', 'A Scott Crossfield Award',
					'Other', 'Not Applicable'
				], 'Not Applicable');
				$form->addField ('comment', 'Comment', 'text');
                                $form->addHiddenField ('mid', $m->uname);
                                $form->addHiddenField ('func', 'addfiles');
				$form->addHiddenField ('ver', 0);
				$form->addHiddenField ('notify', 1);
                                $form->reload = true;
                                $html .= '<br />'.$form;

				return [
					'body' => [
						'MainBody' => $html,
						'BreadCrumbs' => UtilCollection::GenerateBreadCrumbs([
							[
								'Target' => '/',
								'Text' => 'Home'
							],
							[
								'Target' => '/personnel',
								'Text' => 'Personnel'
							]
						])
					]
				];
			}

			if (isset($e['uri'][$e['uribase-index']])) {
				$data = $e['uri'][$e['uribase-index']];
				$mem = Member::Estimate($data, true);
                                $hasfiles = false;

                                $stmt = $pdo->prepare("SELECT * FROM ".DB_TABLES['FileMemberAssignments']." WHERE MemberID = :mid;");
                                $stmt->bindValue(':mid', $mem->uname);
                                $filedata = DBUtils::ExecutePDOStatement($stmt);
				if (count($filedata) > 0) {
					$html = new DetailedListPlus("Member files for ".$mem->RankName);
					foreach ($filedata as $row) {
						$file = File::Get($row["FileID"]);
						if(!!$file) {
	                                                $titlestring = $file->Name."<br />uploaded on ".date("Y-m-d", $file->Created)." by ".$file->UploadN;

	                                                $abdel = new AsyncButton(Null,  "Delete", "deleteMemberFile");
	                                                $abdel->data = 'delfi'.json_encode(array(
	       	                                                 'fid' => $file->ID,
	       	                                                 'mid' => $mem->uname
	                                           	));

                                		        $form = new AsyncForm(null, 'Update File Information', null, $row['FileID']);
	                                	        $form->setSubmitInfo("Update and Verify");
							$form->addField ('fileType', 'Select Type', 'select', null, [
								'CAPF 2', 'CAPF 2A', 'CAPF 5', 'CAPF 31', 'CAPF 60-80', 'CAPF 60-91',
								'CAPF 60-92', 'CAPF 60-93', 'CAPF 60-94', 'Other'
							], $row['FileType']);
							$form->addField ('requested', 'Form Requested', 'datetime-local', null, null, date('Y-m-d\TH:i:s', $row['Requested']));
							$form->addField ('approved', 'Form Approved', 'datetime-local', null, null, date('Y-m-d\TH:i:s', $row['Approved']));
							$form->addField ('award', 'Award Ribbon', 'select', null, [
								'Command Service Ribbon', 'Red Service Ribbon', 'Find Ribbon',
								'Air Search and Rescue Ribbon', 'Disaster Relief Ribbon',
								'IACE Ribbon', 'National Cadet Competition Ribbon',
								'National Color Guard Ribbon', 'Cadet Advisory Council Ribbon',
								'Cadet Community Service Ribbon', 'Cadet Special Activities Ribbon',
								'Cadet Orientation Pilot Ribbon', 'Counter Drug Ribbon',
								'Encampment Ribbon', 'Recruiter Ribbon', 'A Scott Crossfield Award',
								'Other', 'Not Applicable'
							], $row['Award']);
							$form->addField ('comment', 'Comment', 'text', null, null, $row['Comment']);
			                                $form->addHiddenField ('mid', $mem->uname);
			                                $form->addHiddenField ('func', 'update');
							$form->addHiddenField ('fid', $file->ID);
							$form->addHiddenField ('ver', 1);
							$form->addHiddenField ('notify', 0);
			                                $form->reload = true;

							if($row['Verified']) {
								$html->addElement($titlestring, 'Verified '.$abdel.'<br />'.$form, (new FileDownloader("Download", $file->ID)));
							} else {
								$abver = new AsyncButton(Null, "Verify", "verifyMemberFile");
								$abver->data = 'verfi'.json_encode(array(
									'fid' => $file->ID,
									'fname' => $file->Name,
									'mid' => $mem->uname
								));
								$html->addElement($titlestring, $abver.' '.$abdel.'<br />'.$form, (new FileDownloader("Download", $file->ID)));
							}

							$hasfiles = true;
						}
					}

					$html = '<br />'.$html;

				} else {
					$html = "";
				}

                                if(!$hasfiles) { $html .= "There are no files associated with ".$mem->RankName."<br />"; }

                                $form = new AsyncForm (Null, 'Add File');
                                $form->addField ('memberFiles', '&nbsp;', 'file');
				$form->addField ('fileType', 'Select Type', 'select', null, [
					'CAPF 2', 'CAPF 2A', 'CAPF 5', 'CAPF 31', 'CAPF 60-80', 'CAPF 60-91',
					'CAPF 60-92', 'CAPF 60-93', 'CAPF 60-94',
					'Drill Test', 'Answer Sheet', 'Speech Critique', 'Essay Critique', 'SDA',
					'Other'
				], 'CAPF 2A');
				$form->addField ('requested', 'Form Requested', 'datetime-local');
				$form->addField ('approved', 'Form Approved', 'datetime-local');
				$form->addField ('award', 'Award Ribbon', 'select', null, [
					'Command Service Ribbon', 'Red Service Ribbon', 'Find Ribbon',
					'Air Search and Rescue Ribbon', 'Disaster Relief Ribbon',
					'IACE Ribbon', 'National Cadet Competition Ribbon',
					'National Color Guard Ribbon', 'Cadet Advisory Council Ribbon',
					'Cadet Community Service Ribbon', 'Cadet Special Activities Ribbon',
					'Cadet Orientation Pilot Ribbon', 'Counter Drug Ribbon',
					'Encampment Ribbon', 'Recruiter Ribbon', 'A Scott Crossfield Award',
					'Other'
				], 'Other');
				$form->addField ('ver', 'Verified', 'checkbox', Null, 1);
				$form->addField ('comment', 'Comment', 'text');
                                $form->addHiddenField ('mid', $mem->uname);
                                $form->addHiddenField ('func', 'addfiles');
				$form->addHiddenField ('notify', 0);
                                $form->reload = true;
                                $html .= '<br />'.$form;

				return [
					'body' => [
						'MainBody' => $html,
						'BreadCrumbs' => UtilCollection::GenerateBreadCrumbs([
							[
								'Target' => '/',
								'Text' => 'Home'
							],
							[
								'Target' => '/personnel',
								'Text' => 'Personnel'
							]
						])
					]
				];
			}

			$picklink = new AsyncButton(Null, 'Pick someone', 'personnel');
			$returnstring = "<br /><br />".$picklink;
			return [
				'body' => [
					'MainBody' => $returnstring,
					'BreadCrumbs' => UtilCollection::GenerateBreadCrumbs([
						[
							'Target' => '/',
							'Text' => 'Home'
						],
						[
							'Target' => '/personnel',
							'Text' => 'Personnel'
						]
					])
				]
			];
		}

                public static function doPost ($e, $c, $l, $m, $a) {
                        if (!$l) {
                                return ['error' => 411];
                        }
                        if (!isset($e['raw']['func'])) {
                                return ['error' => 311];
                        }
                        if ($e['raw']['func'] == 'addfiles') {
                                $mem = null;
                                if (isset ($e['form-data']['mid'])) {
                                        $mem = Member::Estimate($e['form-data']['mid']);
                                        if (!$mem) {
                                                return ['error' => 311];
                                        }
                                } else {
                                        return ['error' => 311];
                                }

                                $success = true;
                                $pdo = DBUtils::CreateConnection();
                                if (isset ($e['form-data']['memberFiles'][0])) {
					$fileinfo = $e['form-data']['memberFiles'][0];
					$sqlstmt = "INSERT INTO ".DB_TABLES['FileMemberAssignments'];
					$sqlstmt .= " (FileID, MemberID, FileType, Requested, Approved, Award, Verified, Comment) ";
					$sqlstmt .= "VALUES (:fileid, :mid, :ftype, :dtereq, :dteapp, :award, :verified, :comment);";
                                        $stmt = $pdo->prepare($sqlstmt);
                                        $stmt->bindValue(':fileid', rtrim($fileinfo));
                                        $stmt->bindValue(':mid', $mem->uname);
                                        $stmt->bindValue(':ftype', $e['form-data']['fileType']);
                                        $stmt->bindValue(':dtereq', $e['form-data']['requested']);
                                        $stmt->bindValue(':dteapp', $e['form-data']['approved']);
                                        $stmt->bindValue(':award', $e['form-data']['award']);
					$stmt->bindValue(':verified', $e['form-data']['ver']=='true' ? 1 : 0);
                                        $stmt->bindValue(':comment', $e['form-data']['comment']);

                                        $success = $stmt->execute() && $success;
					if(!($e['form-data']['ver']=='true')) {
						$message = "Form upload verification ICO ".$mem->RankName;
						$remarks = "Verify uploaded document ";
						$remarks .= new Link("personnel", "here", [$mem->uname]);
						SetNotify(990101, $a->id, $message, rtrim($fileinfo), $remarks);
					}
                                }
                                return [
                                        'body' => $success ? 'All file uploads worked!' : 'A file failed to upload'
                                ];
			}
                        if ($e['raw']['func'] == 'update') {
                                $mem = null;
                                if (isset ($e['form-data']['mid'])) {
                                        $mem = Member::Estimate($e['form-data']['mid']);
                                        if (!$mem) {
                                                return ['error' => 311];
                                        }
                                } else {
                                        return ['error' => 311];
                                }

                                $success = true;
                                $pdo = DBUtils::CreateConnection();
				$sqlstmt = "UPDATE ".DB_TABLES['FileMemberAssignments'];
				$sqlstmt .= " SET FileType = :ftype, Requested = :dtereq, Approved = :dteapp,";
				$sqlstmt .= " Award = :award, Verified = :verified, Comment = :comment";
				$sqlstmt .= " WHERE FileID = :fileid AND MemberID = :mid;";
                                $stmt = $pdo->prepare($sqlstmt);
                                $stmt->bindValue(':fileid', rtrim($e['form-data']['fid']));
                                $stmt->bindValue(':mid', $mem->uname);
                                $stmt->bindValue(':ftype', $e['form-data']['fileType']);
                                $stmt->bindValue(':dtereq', $e['form-data']['requested']);
                                $stmt->bindValue(':dteapp', $e['form-data']['approved']);
                                $stmt->bindValue(':award', $e['form-data']['award']);
				$stmt->bindValue(':verified', 1);
                                $stmt->bindValue(':comment', $e['form-data']['comment']);

                                $success = $stmt->execute() && $success;

                                return [
                                        'body' => $success ? 'Metadata updated successfully!' : 'Metadata failed to update '.print_r($stmt->errorInfo(), true)
                                ];
			}
		}




		public static function doPut ($e, $c, $l, $m, $a) {
			if (!$l) {return ['error' => 411];}
			if (!$m->hasPermission('PersonnelFilesDel')) {return ['error' => 402];}

                        if (isset($e['raw']['data'])) {
                                $ev = $e['raw']['data'];
                                $func = substr($ev, 0, 5);
                                $data = substr($ev, 5);
	                        if ($func == 'delfi') {
        	                        $pdo = DBUtils::CreateConnection();
                	                $data = json_decode($data, true);
                        	        $stmt = $pdo->prepare("DELETE FROM ".DB_TABLES['FileMemberAssignments']." WHERE FileID = :fid AND MemberID = :mid;");
	                                $stmt->bindValue(':fid', $data['fid']);
        	                        $stmt->bindValue(':mid', $data['mid']);
                	                return $stmt->execute() ? 'File removed' : 'Error when deleting file';
				} else if ($func == 'verfi') {
        	                        $pdo = DBUtils::CreateConnection();
                	                $data = json_decode($data, true);

					//set verified flag in file table
					$sqlstmt = "UPDATE ".DB_TABLES['FileMemberAssignments']." SET Verified=1, VerBy=:verby, VerOn=:veron ";
					$sqlstmt .= "WHERE FileID = :fid AND MemberID = :mid;";
                        	        $stmt = $pdo->prepare($sqlstmt);
	                                $stmt->bindValue(':fid', $data['fid']);
        	                        $stmt->bindValue(':mid', $data['mid'], PDO::PARAM_INT);
        	                        $stmt->bindValue(':verby', $m->RankName);
        	                        $stmt->bindValue(':veron', time());
                	                $verifyreturn = $stmt->execute() ? 'File verified. ' : ('Error when updating file status. '.print_r($stmt->errorInfo(), true));

					//remove group notification of pending file verification
					$stmt = $pdo->prepare("UPDATE ".DB_TABLES['Notifications']." SET deleted=1 WHERE FileID=:fid AND CAPID=990101;");
	                                $stmt->bindValue(':fid', $data['fid']);
					$removenotice = $stmt->execute() ? '<br />Group notice deleted' : '<br />Error when deleting group notice';

					//add member notification of file verification completion
					$message = "File ".$data['fname']." verified.";
					$remarks = "Verified by ".$m->RankName." on ".date("Y-m-d", time());
		                        $sqlin = 'INSERT INTO '.DB_TABLES['Notifications'];
		                        $sqlin .= '(CAPID, AccountID, timestamp, message, FileID, remarks)';
		                        $sqlin .= 'VALUES (:cid, :acct, :time, :msg, :fid, :rmks);';
		                        $stmt = $pdo->prepare($sqlin);
		                        $stmt->bindValue(':cid', $data['mid']);
		                        $stmt->bindValue(':acct', $a->id);
		                        $stmt->bindValue(':time', time());
		                        $stmt->bindValue(':msg', $message);
		                        $stmt->bindValue(':fid', $data['fid']);
		                        $stmt->bindValue(':rmks', $remarks);
		                        $addnotice = $stmt->execute() ? '<br />Member notice added' : '<br />Error when adding member notice';

					return $verifyreturn.$removenotice.$addnotice;
                        	}  else {
	                                return ['error' => '402'];
        	                }
			}
		}
	}
