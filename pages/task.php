<?php
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			if (!$l) {
				return ['error' => 411];
			}
			if (!$m->hasPermission("AssignTasks")) {return ['error' => 402];}
			if (!$a->paid) {return ['error' => 501];}

			$get = isset($e['uri'][0]);
			if ($get) {
				$task = Task::Get($e['uri'][0]);
			} else {
				$task = false;
			}

			if (isset($e['uri'][1])) $html = 'Task created successfully. '.new Link('task', 'Add another task');
			else $html = '';

			$title = '';

			if (!!$task) {
				$form = new AsyncForm(Null, 'Edit Task');
				$form
					->addField('taskName', 'Task name', 'text', Null, Null, $task->Name)
					->addField('description', 'Description', 'textarea', Null, [
						'value' => $task->Description
					]);

				foreach ($task->TaskRecipients as $recipient => $done) {
					$form->addField("capids[]", (new AsyncButton(Null, 'Remove person', 'removeAttendanceUserMultiAdd'))->getHtml(), Null, Null, Null, $recipient);
				}	
				
				$form
					->addField("capids[]", (new AsyncButton(Null, 'Remove person', 'removeAttendanceUserMultiAdd'))->getHtml(), Null, Null, Null, Null, 'templateAdder')
					->addField('', (new AsyncButton(Null, "Add someone", "addUserToAttendance"))->getHtml(), 'textread', Null, Null, Null, Null);

				$form->addHiddenField('func', 'edit')->addHiddenField('task', $task->ID);

				$title = 'Edit a task';
			} else {
				$form = new AsyncForm(Null, 'Create Task');
				$form
					->addField('taskName', 'Task name', 'text', Null, Null)
					->addField('description', 'Description', 'textarea', Null, Null)
					->addField("capids[]", (new AsyncButton(Null, 'Remove person', 'removeAttendanceUserMultiAdd'))->getHtml(), Null, Null, Null, Null, 'templateAdder')
					->addField('', (new AsyncButton(Null, "Add someone", "addUserToAttendance"))->getHtml(), 'textread', Null, Null, Null, Null);

				$form->addHiddenField('func', 'add');

				$title = 'Add a task';
			}
			$form->setOption('reload', false);
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
							'Target' => '/admin',
							'Text' => 'Administration'
						],
						[
							'Target' => '/task',
							'Text' => !!$task ? "Edit task '$task->Name'" : 'Assign Task'
						]
					])
				],
				'title' => $title
			];
		}

		public static function doPost ($e, $c, $l, $m, $a) {
			if (!$l) {return ['error' => 411];}
			if (!$m->hasPermission("AssignTasks")) {return ['error' => 402];}
			if (!$a->paid) {return ['error' => 501];}

			if ($e['form-data']['func'] == 'edit') {
				$task = Task::Get($e['form-data']['task']);
				$task->Name = $e['form-data']['taskName'];
				$task->Description = $e['form-data']['description'];
				$task->save();
				$pdo = DBUtils::CreateConnection();
				$stmt = $pdo->prepare("DELETE FROM ".DB_TABLES['TaskRecipients']." WHERE TaskID = :tid");
				$stmt->bindValue(":tid", $task->ID);
				if (!$stmt->execute()) {
					trigger_error($stmt->errorInfo()[2], E_USER_WARNING);
				}
				foreach ($e['form-data']['capids'] as $capid) {
					$mem = Member::Estimate($capid);
					if ($mem && $capid != 0) {
						$task->TaskRecipients->add($mem);
					}
				}
				return 'Task updated successfully. '.new Link('task', 'Add another task');
			} else {
				$task = Task::Create($e['form-data']['taskName'], $e['form-data']['description']);
				foreach ($e['form-data']['capids'] as $capid) {
					$mem = Member::Estimate($capid);
					if ($mem && $capid != 0) {
						$task->TaskRecipients->add($mem);
					}
				}
				return JSSnippet::PageRedirect('task', [$task->ID, 'true']);
			}
		}
	}