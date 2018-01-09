<?php
	function blog ($e, $c, $l, $m, $a) {
		if (!$a->paid) return '';
		if ($m->hasDutyPosition(["Cadet Public Affairs Officer", "Cadet Public Affairs NCO", "Public Affairs Officer"])) {
			$form = new AsyncForm("rssimport");
			$form->setOption('reload', false);
			$form->addField('rssFile', 'Import RSS Feed', 'file');
			$l1 = new Link("page", "View pages", ['list']);
			$l2 = new Link("page", "Add page", ['add']);
			$l3 = new Link("blog", "Post blog post", ['post']);
			$l5 = new Link("blog", "View blog posts");
			$htm = <<<HTM
<h2 class="title">Feel like adding to the blog?</h2>
<div>
	$l1 | $l2 | $l3 | $l5<br />
</div>
HTM;
			return ['text' => $htm, 'title' => 'Blog Information'];
		}
		return '';
	}
