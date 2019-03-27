<?php
	function zdevInfo ($e, $c, $l, $m, $a) {
		$pdo = DB_Utils::CreateConnection();
		$html = '';
		if ($m->hasPermission('Developer')) {
			$html .= <<<HTM
<h2 class="title">Developer</h2>
HTM;
			$html .= new Link ("unittests", "View unit tests for this website?")."<br />";
			$html .= new Link ("teapot", "Teapot!")."<br />";
			$html .= new Link ("su", "su as someone")."<br />";
			$html .= new Link ("analytics", "View browsing analytics")."<br />";
			//$html .= "<pre>".print_r(Registry::$_data, true)."</pre>";

                        $l1 = (new AsyncButton('participationview', 'Download Participation Visualization', 'participationView'))->getHtml($m->capid);
                        $html .= "$l1<br />";
                        $l1 = (new AsyncButton('idcardfront', 'Download ID Card Front', 'idFront'))->getHtml($m->capid);
                        $html .= "$l1<br />";
                        $l1 = (new AsyncButton('idcardback', 'Download ID Card Back', 'idBack'))->getHtml($m->capid);
                        $html .= "$l1<br />";
                        $l1 = new Link("viewsignin", "View Sign-ins");
                        $html .= "$l1<br />";

		}
		return $html == '' ? '' : [
			'title' => 'Developer',
			'text' => $html
		];
	}
