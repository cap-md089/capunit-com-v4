<?php
	require_once ("config.php");

	function isValidSession ($sess) {
		
	}

	require_once(BASE_DIR . "lib/Registry.php");

    /**
     *
     * PREPEARATION OF ACCOUNT DATA
     *
     */

    $_ACCOUNT = null;

    $acc = explode('.', $_SERVER['SERVER_NAME']);
    if (count($acc) == 2) {
        $_ACCOUNT = new Account("www");
    } else if (count($acc) == 3) {
		if ($acc[0] == 'capeventmanager') {
			$acc[0] = 'mdx89';
		}
        $_ACCOUNT = new Account($acc[0]);
    } else {
        exit(255);
    }

	if (!function_exists('mime_content_type')) {
		function mime_content_type ($path) {
			$path = explode(".", $path);
			return [
				'css' => 'text/css',
				'js' => 'text/javascript',
				'png' => 'image/png'
			][$path[count($path)-1]];
		}
	}

    Registry::Initialize();

	$_SERVER['REQUEST_URI'] = preg_replace('/\.[\.+]\//', '', $_SERVER['REQUEST_URI']);

	$path = explode("/", explode("?", $_SERVER['REQUEST_URI'])[0]);

	if (true) {

		if (in_array($path[count(explode('/', HOST_SUB_DIR))], ['images', 'scripts', 'nstyles'])) {
			$s = explode('.', $path[count(explode('/', HOST_SUB_DIR))+1]);
			if ($s[count($s)-1] == 'css') {
				header('Content-type: text/css');
			} else {
				header('Content-type: '.mime_content_type(BASE_DIR . substr(implode('/', $path), strlen(HOST_SUB_DIR)+1)));
			}
			header('Cache-control: no-cache, must-revalidate');
			header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
			header("X-Account: ".$_ACCOUNT->id);
			header_remove("Etag");
			if($path[count(explode('/', HOST_SUB_DIR))] == 'user-uploads') {
				readfile(BASE_DIR . substr(implode('/', $path), strlen(HOST_SUB_DIR)+1));
			} else {
				require(BASE_DIR . substr(implode('/', $path), strlen(HOST_SUB_DIR)+1));
			}
		}
	}
