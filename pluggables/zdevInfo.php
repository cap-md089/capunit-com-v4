<?php
	function zdevInfo ($e, $c, $l, $m, $a) {
		$pdo = DB_Utils::CreateConnection();
		$html = '';
		if ($m->hasPermission('Developer')) {
			$count = 0;
			$stmt = $pdo->prepare('SELECT COUNT(*) FROM '.DB_TABLES['ErrorMessages'].' WHERE resolved=0;');
			$data = DB_Utils::ExecutePDOStatement($stmt);
			$count = $data[0]['COUNT(*)'];
			$link = new Link("errremark", "View most recent issues?");
			$html .= <<<HTM
<h2 class="title">Website issues</h2>
<div>There are $count unresolved issues</div>
<div>$link</div>
HTM;
			$html .= new Link ("unittests", "View unit tests for this website?")."<br />";
			$html .= new Link ("teapot", "Teapot!")."<br />";
			$html .= new Link ("su", "su as someone")."<br />";
			$html .= new Link ("analytics", "View browsing analytics")."<br />";
			//$html .= "<pre>".print_r(Registry::$_data, true)."</pre>";
		}
		return $html == '' ? '' : [
			'title' => 'Developer',
			'text' => $html
		];
	}