<?php
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			if (!$l) {return ['error' => 411];}
			if (!$m->hasPermission('PersonnelFiles')) {return ['error' => 402];}

			if (isset($e['uri'][$e['uribase-index']])) {
				$data = $e['uri'][$e['uribase-index']];
				$mem = Member::Estimate($data, true);

                                $pdo = DBUtils::CreateConnection();
                                $stmt = $pdo->prepare("SELECT * FROM ".DB_TABLES['FileMemberAssignments']." WHERE MemberID = :mid;");
                                $stmt->bindValue(':mid', $mem->uname);
                                $filedata = DBUtils::ExecutePDOStatement($stmt);
                                // if (count($data) > 0) {
                                        $html = "<br /><br /><h2>Member Files for $mem->RankName</h2>";
                                // }
                                $hasfiles = false;
				$rowhtml = "";
                                foreach ($filedata as $row) {
                                        $file = File::Get($row["FileID"]);
                                        if(!!$file) {
						$rowhtml .= "<tr><td>";
                                                $ab = new AsyncButton(Null,  "Delete", "deleteMemberFile");
                                                $ab->data = 'delfi'.json_encode(array(
                                                        'fid' => $file->ID,
                                                        'mid' => $mem->uname
                                                ));
                                                $rowhtml .= (new FileDownloader($file->Name, $file->ID))." ";
                                                if(($m->hasPermission('PersonnelFilesDel'))) {
                                                        $rowhtml .= $ab;
                                                }
						$rowhtml .= "</td><td>".$row['FileType']."</td><td>";
						$rowhtml .= date("Y-m-d", $row['Requested'])."</td><td>";
						$rowhtml .= date("Y-m-d", $row['Approved'])."</td><td>".$row['Award']."</td><td>".$row['Comment']."</td>";
						$rowhtml .= "</tr>";
                                                $hasfiles = true;
                                        }
                                }
                                if(!$hasfiles) { $html .= "There are no files associated with this member.<br />"; }
				else {
					$html .= "<table><tr><th>File</th><th>File Type</th><th>Uploaded</th><th>Requested</th><th>Approved</th><th>Award</th><th>Comment</th></tr>";
					$html .= $rowhtml."</table>";
				}
                                $form = new AsyncForm (Null, 'Add File');
                                $form->addField ('memberFiles', '&nbsp;', 'file');
				$form->addField ('fileType', 'Select Type', 'select', null, [
					'CAPF 2', 'CAPF 2A', 'CAPF 5', 'CAPF 31', 'CAPF 60-80', 'CAPF 60-91', 'CAPF 60-92', 'CAPF 60-93', 'CAPF 60-94', 'Other'
				], 'CAPF 2A');
				$form->addField ('requested', 'Form Requested', 'datetime-local');
				$form->addField ('approved', 'Form Approved', 'datetime-local');
				$form->addField ('award', 'Award Ribbon', 'select', null, [
					'Command Service Ribbon', 'Red Service Ribbon', 'Find Ribbon', 'Other'
				], 'Other');
				$form->addField ('comment', 'Comment', 'text');
                                $form->addHiddenField ('mid', $mem->uname);
                                $form->addHiddenField ('func', 'addfiles');
                                $form->reload = true;
                                $html .= $form;

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
						$sqlstmt .= " (FileID, MemberID, FileType, Uploaded, Requested, Approved, Award, Comment) ";
						$sqlstmt .= "VALUES (:fileid, :mid, :ftype, :dteup, :dtereq, :dteapp, :award, :comment);";
                                                $stmt = $pdo->prepare($sqlstmt);
                                                $stmt->bindValue(':fileid', rtrim($fileinfo));
                                                $stmt->bindValue(':mid', $mem->uname);
                                                $stmt->bindValue(':ftype', $e['form-data']['fileType']);
                                                $stmt->bindValue(':dtereq', $e['form-data']['requested']);
                                                $stmt->bindValue(':dteapp', $e['form-data']['approved']);
                                                $stmt->bindValue(':award', $e['form-data']['award']);
                                                $stmt->bindValue(':comment', $e['form-data']['comment']);

                                                $success = $stmt->execute() && $success;
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
                        	}  else {
	                                return ['error' => '402'];
        	                }
			}
		}
	}
