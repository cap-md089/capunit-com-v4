<?php
	require_once 'config.php';
	require_once 'lib/Account.php';
	require_once 'lib/Member.php';

	$_ACCOUNT = new Account('mdx89');
	$mem = Member::Create('542488', 'app/xPHP091101');

	echo json_encode($mem->get101Card(542488), JSON_PRETTY_PRINT);
	echo "\n";
	$lang = $mem->get101Card(421170);
	echo json_encode($lang, JSON_PRETTY_PRINT);
	echo "\n" . $lang['driversLicense']['expires'];

	file_put_contents('/home/arioux/542488.jpg', $mem->download101Image(542488));
