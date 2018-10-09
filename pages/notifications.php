<?php
	class Output {

		public static function doGet ($e, $c, $l, $m, $a) {
			if (!$l) {return false;}
			if (!$a->paid) {return ['error' => 501];}

			$rhtml = '';

			$pdo = DBUtils::CreateConnection();
			$stmt = $pdo->prepare("select * from Notifications where CAPID=:cid AND deleted=0");
			$stmt->bindValue(':cid', $m->capid);
			$notices = DBUtils::ExecutePDOStatement($stmt);
			$stmt = $pdo->prepare("select * from Notifications where CAPID=:cid AND deleted=0 AND Acknowledged=0");
			$stmt->bindValue(':cid', $m->capid);
			$noticesUnread = DBUtils::ExecutePDOStatement($stmt);

			if (count($notices) > 0) {
				$html = new DetailedListPlus("Notifications");

				$markAsReadButton = new AsyncButton(Null, "Mark as Read", "reload");
				$markAsUnreadButton = new AsyncButton(Null, "Mark as Unread", "reload");

				foreach ($notices as $notice) {
					if ($notice['deleted']) {
						continue;
					}
					$display = '<br />';
					$form = new AsyncForm(null, null, null, $notice['id']);
					$form->setSubmitInfo("Delete");
					$form->addHiddenField('noticeid', $notice['id']);
					if($notice['Acknowledged']==0) {
						$markAsReadButton->data = 'r'.$notice['id'];
						$html->addElement("<b>{$notice['message']}</b>", $notice['remarks'].$form, $markAsReadButton);
					} else {
						$markAsUnreadButton->data = 'u'.$notice['id'];
						$html->addElement($notice['message'], $notice['remarks'].$form, $markAsUnreadButton);
					}
				}
				$rhtml .= $html;
			} else
			{
				$rhtml = "<h2 class=\"title\">No Notifications</h2>";
			}

			return [
				'body' => [
					'MainBody' => $rhtml,
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
			$stmt = $pdo->prepare("update Notifications set deleted=1 where CAPID=:cid AND id=:nid");
			$stmt->bindValue(':cid', $m->capid);
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
				$stmt = $pdo->prepare("update Notifications set Acknowledged=1 where CAPID=:cid AND id=:nid");
			} else if ($command == 'u') {
				$stmt = $pdo->prepare("update Notifications set Acknowledged=0 where CAPID=:cid AND id=:nid");
			}
			$stmt->bindValue(':cid', $m->capid);
			$stmt->bindValue(':nid', $data);
			$notices = DBUtils::ExecutePDOStatement($stmt);
		}
	}
