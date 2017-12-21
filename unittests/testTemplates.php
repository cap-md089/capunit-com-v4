<?php
	function testTemplates () {
		function testAsyncButton () {
			flog ("Ehh, what's up doc?");
		}
		function testAsyncForm () {
			$checkbox = [
				'Test 1',
				'Test 2',
				'Other',
				'Other'
			];
			$result = [
				'true',
				'false',
				'true',
				'Test',
				'false',
				'Test 2'
			];
			flog ("Checkbox output: ");
			foreach ($result as $res) {
				flog('--'.$res);
			}
			$nresult = AsyncForm::ParseCheckboxOutput($result, $checkbox);
			flog ("Result: ".$nresult);
			flog ("Expected result: 'Test 1,Test'");
		}
		function testDetailedListPlus () {
			flog ("Ehh, what's up doc?");
		}
		function testLink () {
			flog ("Ehh, what's up doc?");
		}

		flog ("Testing AsyncButton");
		testAsyncButton();
		flog ("Done testing AsyncButton");
		flog ("Testing AsyncForm");
		testAsyncForm();
		flog ("Done testing AsyncForm");
		flog ("Testing DetailedListPlus");
		testDetailedListPlus();
		flog ("Done testing DetailedListPlus");
		flog ("Testing Link");
		testLink();
		flog ("Done testing Link");
		flog ("Done with templates");
	}