<?php
	function absentee ($e, $c, $l, $m, $a) {
		if (!$a->paid) return '';
		$pdo = DBUtils::CreateConnection();
		$stmt = $pdo->prepare("SELECT COUNT(*) AS Count FROM ".DB_TABLES['Absentee']." WHERE CAPID = :cid;");
		$stmt->bindValue(':cid', $m->uname);
		$data = DBUtils::ExecutePDOStatement($stmt)[0]['Count'];
		$form = new AsyncForm("absentee");
		$time = time();
		$notes = '';
		if ($data > 0) {
			$stmt = $pdo->prepare("SELECT AbsentUntil, AbsentNotes FROM ".DB_TABLES['Absentee']." WHERE CAPID = :cid;");
			$stmt->bindValue(':cid', $m->uname);
			$data = DBUtils::ExecutePDOStatement($stmt)[0];
			$time = $data['AbsentUntil'];
			$notes = $data['AbsentNotes'];
		}
		$form->addField('absentuntil', 'When will you be absent until?', 'datetime-local', Null, Null, date('Y-m-d\TH:i:s', $time));
		$form->addField('absentnotes', 'Notes about duration?', 'textarea', Null, Null, $notes);
		$html = <<<EOD
<h2 class="title">Absentee Information</h2>
<div>
EOD;
		$html .= $form;
		$html .= "</div>";
		return [
			'text' => $html,
			'title' => 'Absentee Information'
		];
	}