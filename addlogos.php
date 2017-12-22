<?php
	require_once("config.php");
	require_once(BASE_DIR."lib/File.php");

	$_ACCOUNT = new Account("www");	

	ob_start();
	readfile(BASE_DIR."images/cap-logo.png");
	$data = ob_get_clean();
	$file = File::Create("cap-logo.png", $data, Member::Estimate(542488));
	echo $file->MD5.PHP_EOL;
	echo $file->ID.PHP_EOL;
	$file->save();


	$_ACCOUNT = new Account("md089");	

	ob_start();
	readfile(BASE_DIR."images/squad-logo.png");
	$data = ob_get_clean();
	@ob_end_clean();
	$file = File::Create("squad-logo.png", $data, Member::Estimate(542488));
	echo PHP_EOL.PHP_EOL.PHP_EOL;
	echo $file->MD5.PHP_EOL;
	echo $file->ID.PHP_EOL;
	$file->save();
	echo PHP_EOL;

	echo $_ACCOUNT->getFilesSize().PHP_EOL;