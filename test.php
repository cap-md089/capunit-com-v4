<?php
	require_once 'config.php';
	require_once 'lib/Account.php';
	require_once 'lib/Member.php';

	$_ACCOUNT = new Account('mdx89');
	$mem = Member::Create('542488', 'app/xPHP091101');

	print_r($mem->get101Card());
