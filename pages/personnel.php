<?php
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			if (!$l) {return ['error' => 411];}
			if (!$m->hasPermission('PersonnelFiles')) {return ['error' => 402];}

			if (isset($e['uri'][$e['uribase-index']])) {
				$data = $e['uri'][$e['uribase-index']];
				$mem = Member::Estimate($data, true);

                                $pdo = DBUtils::CreateConnection();
                                $stmt = $pdo->prepare("SELECT FileID FROM ".DB_TABLES['FileMemberAssignments']." WHERE MemberID = :mid;");
                                $stmt->bindValue(':mid', $mem->uname);
                                $filedata = DBUtils::ExecutePDOStatement($stmt);
                                // if (count($data) > 0) {
                                        $html = "<br /><br /><h2>Member Files for $mem->RankName</h2>";
                                // }
                                $hasfiles = false;
                                foreach ($filedata as $row) {
                                        $file = File::Get($row["FileID"]);
                                        if(!!$file) {
                                                $ab = new AsyncButton(Null,  "Delete", "deleteMemberFile");
                                                $ab->data = 'delfi'.json_encode(array(
                                                        'fid' => $file->ID,
                                                        'mid' => $mem->uname
                                                ));
                                                $html .= (new FileDownloader($file->Name, $file->ID))." ";
                                                if(($m->hasPermission('PersonnelFilesDel'))) {
                                                        $html .= $ab."<br />";
                                                }
                                                $hasfiles = true;
                                        }
                                }
                                if(!$hasfiles) { $html .= "There are no files associated with this member.<br />"; }
                                $form = new AsyncForm (Null, 'Add File');
                                $form->addField ('memberFiles', '&nbsp;', 'file');
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
                                        foreach ($e['form-data']['memberFiles'] as $fileID) {
                                                $stmt = $pdo->prepare("INSERT INTO ".DB_TABLES['FileMemberAssignments']." (FileID, MemberID) VALUES (:fileid, :mid);");
                                                $stmt->bindValue(':fileid', rtrim($fileID));
                                                $stmt->bindValue(':mid', $mem->uname);
                                                $success = $stmt->execute() && $success;
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
                        	}  else {
	                                return ['error' => '402'];
        	                }
			}
		}
	}
