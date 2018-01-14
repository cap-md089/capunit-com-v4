<?php
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			if (!$l) {return false;}
			if (!$a->paid) {return ['error' => 501];}

			$rhtml = '';

			$tasks = Task::GetForTasker($m);

			if (count($tasks) > 0) {
				$html = new DetailedListPlus("Tasks you've assigned");

				foreach ($tasks as $task) {
					$butt = new AsyncButton(Null, "Done", "reload");
					if ($task->Done) {
						continue;
					}
					$complete = '<br />';
					foreach ($task->TaskRecipients as $capid => $data) {
						$done = $data['Done'];
						$comments = $data['Comments'];
						$mem = Member::Estimate($capid);
						$complete .= $mem->RankName . ": <span style=\"color:".($done ? "green":"red")."\">".($done?"COMPLETE":"INCOMPLETE")."</span>";
						if (strlen($comments) > 0) {
							$complete .= ", '".strip_tags($comments)."'";
						}
						$complete .= "<br />";
					}
					$butt->data = 'a'.$task->ID;
					$html->addElement("$task->Name", $task->Description.$complete, $butt);
				}

				$rhtml .= $html;
			}

			$tasks = Task::GetFor($m);

			if (count($tasks) == 0) {
				$rhtml = "<h2 class=\"title\">No tasks!</h2>";
			}

			$html = new DetailedListPlus("Tasks assigned to you");

			$butt = new AsyncButton(Null, "Complete!", "reload");

			$links = [];

			foreach ($tasks as $task) {
				$butt->data = 'r'.$task->ID;
				$form = new AsyncForm (null, null, null, "task{$task->ID}");
				$form->addField('comments', 'Comments', 'textarea');
				$form->addHiddenField('taskid', $task->ID);
				$html->addElement("From: ".$task->Tasker->RankName, "<h2 class=\"titl\">$task->Name</h2><br />$task->Description$form", $butt);
				$links[] = [
					'Type' => 'ref',
					'Target' => "task{$task->ID}",
					'Text' => $task->Name
				];
			}

			return [
				'body' => [
					'MainBody' => $rhtml.$html,
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
							'Target' => '/tasklist',
							'Text' => 'View tasks'
						]
					]),
					'SideNavigation' => UtilCollection::GenerateSideNavigation($links)
				],
				'title' => 'User tasks'
			];
		}

		public static function doPost ($e, $c, $l, $m, $a) {
			if (!$l) return false;
			if (!$a->paid) {return ['error' => 501];}

			$task = Task::Get($e['form-data']['taskid']);
			$task->done($m, $e['form-data']['comments']);
		}

		public static function doPut ($e, $c, $l, $m, $a) {
			if (!$l) return false;
			if (!$a->paid) {return ['error' => 501];}

			$f = substr($e['raw']['data'], 0, 1);
			$data = substr($e['raw']['data'], 1);

			if ($f == 'r') {
				$task = Task::Get($data);
				$task->done($m, "");
			} else if ($f == 'a') {
				$task = Task::Get($data);
				$task->Done = 1;
			}
		}
	}
