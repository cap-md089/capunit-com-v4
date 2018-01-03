<?php
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			$form = new AsyncForm();
			$form
				->addField('test1', 'Checkbox test', 'checkbox')
				->addField('test2', 'Multcheckbox test', 'multcheckbox', '', [
					'Test1', 'Test2', 'other'
				])
				->addField('test3', 'Radio test', 'radio', '', [
					'Test1', 'Test2', 'other'	
				])
				->addField('test5', 'Autocomplete test', 'autocomplete', '', [
					'Test 1', 'Test 2'	
				])
				->addField('test6', 'Textarea test', 'textarea', '', [
					25, 50
				])
				->addField('test7', 'File test', 'file')
				->addField('', 'A label', 'label')
				->addField('', 'Readable text', 'readtext')
				->addField('test8', 'Range', 'range', '', [
					'max' => 75,
					'min' => 20,
					'value' => 45
				])
				->addField('test9', 'Date range', 'daterange', '', [
					'max' => time()+3600,
					'min' => time()
				])
				->addField('test10', 'Text field', 'text')
				->addField('test11', 'Password field', 'password')
				->addField('test12', 'Datetime test', 'datetime-local');

			$form->reload = false;

			$form->setSubmitInfo("SUBMIT", null, null, null, true);

			$html = <<<HTM
<br /><br /><br />
<form method="get" class="searchForm" action="/test">
	<div role="search">
		<input type="text" value="" name="s1" placeholder="Search" />
		<button aria-label="Do search"></button>
	</div>
</form>
HTM;

			return [
				'body' => [
					'MainBody' => $form->getHtml().$html
				]
			];
		}

		public static function doPost ($e, $c, $l, $m, $a) {
			return gettype($e['form-data']['test1']=='true').($e['form-data']['test1']=='true'?'t':'f').'<pre>'.print_r($e['form-data'], true).'</pre>';
		}
	}