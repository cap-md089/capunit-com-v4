<?php
	function tasks ($e, $c, $l, $m, $a) {
		if ($l && $a->paid) {
			$tasks = Task::GetFor($m);
			$c = count($tasks);
			$l = new Link("tasklist", $c > 1 ? "Complete them now":$c == 1?"Complete it now":"View tasks");
			$html = "<h2 class=\"title\">Tasks</h2><div>";
			$html .= "You have ".count($tasks)." assigned task".($c==1?"":"s")." to be completed. $l";
			$html .= "</div>";
			return [
				'text' => $html,
				'title' => 'Tasks'
			];
		} else {
			return [
				'text' => '',
				'title' => ''
			];
		}
	}