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
					'CAPF 60-92', 'CAPF 60-93', 'CAPF 60-94', 'Other'
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
				$form->addField ('comment', 'Comment', 'text');
                                $form->addHiddenField ('mid', $m->uname);
                                $form->addHiddenField ('func', 'addfiles');
				$form->addHiddenField ('ver', 0);
				$form->addHiddenField ('notify', 1);
                                $form->reload = true;
                                $html .= '<br /><br />'.$form;

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

                                $stmt = $pdo->prepare("SELECT * FROM ".DB_TABLES['FileMemberAssignments']." WHERE MemberID = :mid;");
                                $stmt->bindValue(':mid', $mem->uname);
                                $filedata = DBUtils::ExecutePDOStatement($stmt);
                                // if (count($data) > 0) {
					$html = "<style>table, th, td \{ border: 1px solid black; \}</style>";
                                        $html .= "<br /><br /><h2>Member Files for $mem->RankName</h2>";
                                // }
                                $hasfiles = false;
				$rowhtml = "";
                                foreach ($filedata as $row) {
                                        $file = File::Get($row["FileID"]);
                                        if(!!$file) {
						$rowhtml .= "<tr><td>";
                                                $abdel = new AsyncButton(Null,  "Delete", "deleteMemberFile");
                                                $abdel->data = 'delfi'.json_encode(array(
                                                        'fid' => $file->ID,
                                                        'mid' => $mem->uname
                                                ));
                                                $abver = new AsyncButton(Null,  "Verify", "verifyMemberFile");
                                                $abver->data = 'verfi'.json_encode(array(
                                                        'fid' => $file->ID,
                                                        'mid' => $mem->uname
                                                ));
                                                $rowhtml .= (new FileDownloader($file->Name, $file->ID))." ";
                                                if(($m->hasPermission('PersonnelFilesDel'))) {
							$rowhtml .= "<br />";
							if(!$row['Verified']) {
								$rowhtml .= $abver." ".$abdel;
							} else {
	                                                        $rowhtml .= "Verified ".$abdel;
							}
                                                }

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
                                if(!$hasfiles) { $html .= "There are no files associated with this member.<br />"; }
				else {
					$html .= "<table id=\"persdoctab\"><tr><th>File</th><th>File Type</th><th>Requested</th><th>Approved</th><th>Award</th><th>Comment</th></tr>";
					$html .= $rowhtml."</table>";
				}
                                $form = new AsyncForm (Null, 'Add File');
                                $form->addField ('memberFiles', '&nbsp;', 'file');
				$form->addField ('fileType', 'Select Type', 'select', null, [
					'CAPF 2', 'CAPF 2A', 'CAPF 5', 'CAPF 31', 'CAPF 60-80',
					'CAPF 60-91', 'CAPF 60-92', 'CAPF 60-93', 'CAPF 60-94',
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
                                $html .= '<br /><br />'.$form;

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

			return [
				'body' => [
					'MainBody' => (new AsyncButton(Null, 'Pick someone', 'personnel')).'',
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
                        	        $stmt = $pdo->prepare("UPDATE ".DB_TABLES['FileMemberAssignments']." SET Verified=1 WHERE FileID = :fid AND MemberID = :mid;");
	                                $stmt->bindValue(':fid', $data['fid']);
        	                        $stmt->bindValue(':mid', $data['mid']);
                	                $verifyreturn = $stmt->execute() ? 'File verified. ' : 'Error when updating file status. ';
					$stmt = $pdo->prepare("UPDATE ".DB_TABLES['Notifications']." SET deleted=1 WHERE FileID=:fid AND CAPID=990101;");
	                                $stmt->bindValue(':fid', $data['fid']);
					$removenotice = $stmt->execute() ? 'Notice deleted' : 'Error when deleting notice';
					return $verifyreturn.$removenotice;
                        	}  else {
	                                return ['error' => '402'];
        	                }
			}
		}
	}
