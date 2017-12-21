<?php
	function testFile () {
		$mem = Member::Estimate(542488);

		flog ("Creating text file 'hello.txt' with text 'Hello world!'");
		$file = File::Create('hello.txt', 'Hello world!', $mem, '');
		flog ("File id: ".$file->ID);

		flog ("Attempting to retrieve file");
		$file2 = File::Get($file->ID);
		flog ("Text: ".$file2->Data);
		flog ("MIME type: ".$file2->ContentType);

		flog ("Changing text in file");
		$file->Data = "Goodbye world!";
		$file->save();
		flog ("Saved the data");
		$file2 = File::Get($file->ID);
		flog ("New text: ".$file2->Data);

		flog ("Deleting file");
		flog ("Did it go well? ".($file->remove()?'Yes':'No'));

		$fd = new FileDownloader("Download test file", 'file594d85182ce530.37209142');
		flog ("Download test file <a onclick=\"alert('hi');\">". $fd."</a>");

		flog ("Done with File");
	}