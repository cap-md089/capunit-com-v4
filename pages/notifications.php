<?php
	class Output {

		public static function doGet ($e, $c, $l, $m, $a) {
			if (!$l) {return false;}
			if (!$a->paid) {return ['error' => 501];}

			$rhtml = '';

			$memberAccounts = $m->GetAccountIDs();
			if ($m->hasPermission('PersonnelFilesDel')) {
				$groupIDs = 990101;
			} else { $groupIDs = 0; }
			$pdo = DBUtils::CreateConnection();

			$sqlstmt = "SELECT * FROM ".DB_TABLES['Notifications']." WHERE CAPID=:cid AND deleted=0 ";
			$sqlstmt .= "UNION SELECT * FROM ".DB_TABLES['Notifications']." WHERE CAPID IN (";
			$sqlstmt .= ":groups) AND AccountID=(:acct) AND deleted=0;";

			$stmt = $pdo->prepare($sqlstmt);
			$stmt->bindValue(':cid', $m->capid);
			$stmt->bindValue(':groups', $groupIDs);
			$stmt->bindValue(':acct', $a->id);
			$notices = DBUtils::ExecutePDOStatement($stmt);

			if (count($notices) > 0) {
				$html = new DetailedListPlus("Notifications");

				foreach ($notices as $notice) {
					if ($notice['deleted']) {
						continue;
					}
					$display = '<br />';
					$form = new AsyncForm(null, null, null, $notice['id']);
					$form->setSubmitInfo("Delete");
					$form->addHiddenField('noticeid', $notice['id']);
					if($notice['Acknowledged']==0) {
						$markAsReadButton = new AsyncButton(Null, "Mark as Read", "reload");
						$markAsReadButton->data = 'r'.$notice['id'];
						$html->addElement("<b>{$notice['message']}</b>", $notice['remarks'].$form, $markAsReadButton);
					} else {
						$markAsUnreadButton = new AsyncButton(Null, "Mark as Unread", "reload");
						$markAsUnreadButton->data = 'u'.$notice['id'];
						$html->addElement($notice['message'], $notice['remarks'].$form, $markAsUnreadButton);
					}
				}
				$rhtml .= $html;
			} else
			{
				$rhtml = "<h2 class=\"title\">No Notifications</h2>";
			}

			$thtml = "Groups: ".$groupIDs."<br />Account: ".$a->id."<br />";

			return [
				'body' => [
					'MainBody' => "<br />".$rhtml,
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
							'Target' => '/notifications',
							'Text' => 'Notifications'
						]
					])
				],
				'title' => 'Notifications'
			];
		}


		public static function doPost ($e, $c, $l, $m, $a) {
			if (!$l) return false;
			if (!$a->paid) {return ['error' => 501];}

			$pdo = DBUtils::CreateConnection();
			$stmt = $pdo->prepare("update Notifications set deleted=1 where id=:nid");
			$stmt->bindValue(':nid', $e['form-data']['noticeid']);
			$notices = DBUtils::ExecutePDOStatement($stmt);
		}

		public static function doPut ($e, $c, $l, $m, $a) {
			if (!$l) return false;
			if (!$a->paid) {return ['error' => 501];}

			$command = substr($e['raw']['data'], 0, 1);
			$data = substr($e['raw']['data'], 1);

			$pdo = DBUtils::CreateConnection();

			if ($command == 'r') {
				$stmt = $pdo->prepare("update Notifications set Acknowledged=1 where id=:nid");
			} else if ($command == 'u') {
				$stmt = $pdo->prepare("update Notifications set Acknowledged=0 where id=:nid");
			}
			$stmt->bindValue(':nid', $data);
			$notices = DBUtils::ExecutePDOStatement($stmt);
		}
	}
