<?php
 
    require_once ("config.php");

    // print_r($_SERVER);

    $_METHOD = strtoupper($_SERVER['REQUEST_METHOD']);
    $_AJAX = isset($GLOBALS['_'.$_METHOD]['ajax']) && $GLOBALS['_'.$_METHOD]['ajax'] == 'true';
    $_METHODD = $GLOBALS['_'.$_METHOD];
    $dir = HOST_SUB_DIR;
    $_SERVER['REQUEST_URI'] = preg_replace('/\.[\.+]\//', '', $_SERVER['REQUEST_URI']);
    $path = explode("/", explode("?", $_SERVER['REQUEST_URI'])[0]);

    set_error_handler(function ($errno, $errstr, $errfile, $errline, $errcontext) {
        http_response_code (500);
        $_ERROR = [
            "enumber" => $errno,
            "errname" => Util_Collection::GetErrorName($errno),
            "message" => $errstr,
            "badfile" => $errfile,
            "badline" => $errline,
            "context" => $errcontext
        ];
        require (BASE_DIR."/500.php");
        echo ErrOutput::doGet($_ERROR);
        exit(255);
        return true;
    }, E_ALL);

    header ("Cache-control: no-cache, must-revalidate");

	// I put a comment here
    
    $libs = scandir (BASE_DIR . "lib");
    foreach ($libs as $lib) {
        if (is_file(BASE_DIR . "lib/$lib") && explode(".", $lib)[1] == 'php') {
            require_once (BASE_DIR . "lib/$lib");
        }
    }

    /**
     *
     * PREPARATION OF ACCOUNT DATA
     *
     */

    $_ACCOUNT = null;

    $acc = explode('.', $_SERVER['SERVER_NAME']);
    if (count($acc) == 2) {
        $_ACCOUNT = new Account("sales");
    } else if (count($acc) == 3) {
		if ($acc[0] == 'capeventmanager') {
            $acc[0] = 'md089';
		} else if ($acc[0] == 'www') {
            $acc[0] = 'sales';
        }
        $_ACCOUNT = new Account($acc[0]);
    } else {
        exit(255);
    }
    $GOOGLE_CREDENTIAL_PATH = BASE_DIR.'../credentials/'.$_ACCOUNT->id.'.json';
    GoogleCalendar::init();

    Registry::Initialize();

    Event::SetAccount($_ACCOUNT);

    if (!$_AJAX && !in_array($path[count(explode('/', HOST_SUB_DIR))], ['images', 'scripts', 'styles', 'user-uploads'])) {
        header("Cache-control: no-cache,must-revalidate");
        $title = Registry::get("Website.Name");
        if (UtilCollection::GetBrowser()['browser'] == 'Google Chrome') {
            $icon = '<link rel="shortcut icon" href="/filedownloader/'.Registry::Get('Website.Logo').'?ajax=true" />';
        } else {
		    $icon = '<link rel="shortcut icon" href="/'.HOST_SUB_DIR.'favicon.ico" />';
        }
        $subdir = Registry::get('Styling.Preset');
        echo <<<HTM
<!DOCTYPE html><html class="desktop" xmlns="http://www.w3.org/1999/xhtml"><head><meta name="google-site-verification" content="jGvPfo4gwlWE7haIfP7yatINNAnnlcjjo0fZq0KGPOU" /><title id="website_title">$title</title>$icon</head><body id="mother" style="display:none"><script src="/{$dir}scripts/{$subdir}/load.js"></script></body></html>
HTM;
        exit(0);
    }

    $SecurityLogger = new Logger("SecurityLogger");

    $_FUNC = strtolower(explode("/", explode("?", $_SERVER['REQUEST_URI'])[0])[count(explode('/', HOST_SUB_DIR))]);
    if ($_FUNC == "" || $_FUNC[0] == '?' || $_FUNC[0] == '.') {
        $_FUNC = "main";
    } else if (in_array($path[count(explode('/', HOST_SUB_DIR))], ['images', 'scripts', 'styles', 'user-uploads'])) {
        $_FUNC = "filemanager";
    }

    /**
     *
     * PREPARATION OF MEMBER DATA
     *
     */

    $_ERROR = Null;
    $_USER = Null;
    $_LOGGEDIN = false;
	$_COOKIE = json_decode(isset($_METHODD['cookies']) ? urldecode($_METHODD['cookies']) : '{}', true);

    if ($_FUNC == "signin") {
        echo "--COMPLEXITYPERPLEXITYSTYLIZEDWAYLAYING\nName: MainBody\n\n";

        if (!(isset($_METHODD['name']) && isset($_METHODD['password']))) {
            echo json_encode (array (
                'valid' => false,
                'cookie' => array ()
            ));
            exit(0);
        }

        $m = Member::Create ($_METHODD['name'], $_METHODD['password']);
		Analytics::LogSignin($m->success, $_METHODD['name']);

		if ($m->success) {
			echo json_encode(array (
				"valid" => true,
				"cookie" => array (
					"LOGIN_DETAILS" => $m->toObjectString()
				)
			));
		} else {
            if (isset($m->data['reset']) && $m->data['reset']) {
                echo json_encode(array (
                    "valid" => false,
                    "reset" => true
                ));
            } else if (isset($m->data['down']) && $m->data['down']) {
                echo json_encode(array (
                    "valid" => false,
                    "down" => true
                ));
            } else {
                echo json_encode(array (
                    "valid" => false,
                    "cookie" => array ()
                ));
            }
		}
		exit(0);
	}

    if (/*defined("USER_REQUIRED") && USER_REQUIRED && */isset($_COOKIE["LOGIN_DETAILS"])) {
    	if (isset($_METHODD['su']) && false) {
            $SecurityLogger->Log("User signing in as ".$_METHODD['su'], 4);
            $_USER = Member::Check($_COOKIE["LOGIN_DETAILS"], $_METHODD['su']);
        } else {
            $_USER = Member::Check($_COOKIE["LOGIN_DETAILS"]);
        }
        $_LOGGEDIN = $_USER['valid'];
		$_USER = $_LOGGEDIN ? $_USER['mem'] : Null;
	}
 
    if ($_FUNC != 'gettemplates') {
        Analytics::LogBrowser($_USER);
    }

    if (file_exists(BASE_DIR."pages/$_FUNC.php")) {
        include (BASE_DIR."pages/$_FUNC.php");
    } else { // Not found or not to be displayed
        http_response_code (404);
        include (BASE_DIR."404.php");
    } 

    /**
     *
     * PREPARATION OF PARAMETER DATA
     *
     */

    $e = array ();

    if (count(explode("?", $_SERVER['REQUEST_URI'])) > 1) {
        $e['querystring'] = explode("?", $_SERVER['REQUEST_URI'])[1];
        $_e = explode("&", $e['querystring']);
        $e_ = array ();
        foreach ($_e as $v) {
            if (!in_array(explode("=", $v)[0], ['ajax', 'cookies', 'method'])) {
                $e_[explode("=", $v)[0]] = urldecode(explode("=", $v)[1]);
            }
        }
        $e['parameter'] = $e_;
    } else if (count($_METHODD > 0)) {
        $e['querystring'] = '';
        $e['parameter'] = array();
        foreach ($_METHODD as $k => $v) {
            if (!in_array($k, ['ajax', 'cookies', 'method', 'form'])) {
                $e['parameter'][$k] = $v;
            }
        }
    } else {
        $e['querystring'] = '';
        $e['parameter'] = array ();
    }

    $e['uri'] = explode("/", explode("?", $_SERVER['REQUEST_URI'])[0]);
    if ($e['uri'][count($e['uri'])-1] == '') {
        unset($e['uri'][count($e['uri'])-1]);
    }
    for ($i = 0; $i < count(explode('/', HOST_SUB_DIR)); $i++) {
        unset($e['uri'][1]);
    }
    array_shift($e['uri']);

    if (isset($_METHODD['form']) && $_METHODD['form'] == 'true') {
        $e['form-data'] = $_METHODD;
        if (isset($_METHODD['filesList']) && count($_METHODD['filesList']) > 0 && $_METHODD['filesList'] != '') {
            foreach ($_METHODD['filesList'] as $flist) {
                if (isset ($_FILES[substr($flist, 0, -2)]) && count($_FILES[substr($flist, 0, -2)]) > 0) {
                    $e['form-data'][$flist] = [];
                    $flist = substr($flist, 0, -2);
                    for ($i = 0; $i < count($_FILES[$flist]['size']); $i++) {
                        $e['form-data'][$flist.'[]'][] = [
                            'name' => $_FILES[$flist]['name'][$i],
                            'type' => $_FILES[$flist]['type'][$i],
                            'tmp_name' => $_FILES[$flist]['tmp_name'][$i],
                            'error' => $_FILES[$flist]['error'][$i],
                            'size' => $_FILES[$flist]['size'][$i]
                        ];
                    }
                } else if (isset($e['form-data'][$flist]) && count($e['form-data'][$flist]) > 0) {
                    $d = [];
                    foreach ($_METHODD[$flist] as $file) {
                        $d[] = $file;
                    }
                    $e['form-data'][$flist] = $d;
                }
            }
        }
    }

    $e['raw'] = $GLOBALS['_'.$_METHOD];

    $method = $_METHOD;
    if (isset($e['raw']['method'])) $method = $e['raw']['method'];

    $e['uribase-index'] = 0;

    $fmethod = "do" . ucfirst(strtolower($method)); // doGet, doPost, doPut etc

    /**
     *
     * EXECUTION OF PAGE
     *
     */

    $fromindex = true;

    $data = Output::$fmethod($e, $_COOKIE, $_LOGGEDIN, $_USER, $_ACCOUNT);

    if ($data === false) {
        header('X-User-Error: 411');
        header('X-User-Title: '.Registry::get("Website.Name").' '.Registry::get("Website.Separator").' '.'Error');
        die();
    }

    if (gettype($data) !== "string") {
        if (isset($data['headers']) && count($data['headers']) > 0) {
            foreach ($data['headers'] as $k => $v) {
                header ("$k: $v");
            }
        }

        if (isset($data['error'])) {
            header ("X-User-Error: ".$data['error']);
        }

        if (isset($data['title'])) {
            header ("X-User-Title: ".Registry::get("Website.Name")." ".Registry::get("Website.Separator")." ".$data['title']);
        } else {
            header ("X-User-Title: ".Registry::get("Website.Name"));
        }

        $html = '';

        if (isset($data['navigation'])) {
            $html .= "--COMPLEXITYPERPLEXITYSTYLIZEDWAYLAYING\nName: Crumbs\n\n".$data['navigation']."\n\n";
        }

        if (isset($_METHODD['embed']) && $_METHODD['embed'] == 'true' && isset($data['body'])) {
            if (gettype($data['body']) !== 'string') {
                foreach ($data['body'] as $name => $value) {
                    $html .= "\n$value\n";
                }
            } else {
                $html .= "\n{$data['body']}\n";
            }
        } else if (isset($data['body'])) {
            if (gettype($data['body']) !== 'string') {
                if (!isset($data['body']['SideNavigation'])) {
                    $data['body']['SideNavigation'] = UtilCollection::GenerateSideNavigation([]);
                }
                if (!isset($data['body']['BreadCrumbs'])) {
                    $data['body']['BreadCrumbs'] = UtilCollection::GenerateBreadCrumbs([
                        [
                            'Target' => '/',
                            'Text' => 'Home'
                        ],
                        [
                            'Target' => '/'.$_FUNC,
                            'Text' => isset($data['Title']) ? $data['Title'] : $_FUNC
                        ]
                    ]);
                }
                foreach ($data['body'] as $name => $value) {
                    $html .= "--COMPLEXITYPERPLEXITYSTYLIZEDWAYLAYING\nName: $name\n\n{$value}\n\n\n";
                }
            } else {
                $value = UtilCollection::GenerateSideNavigation([]);
                $html .= "--COMPLEXITYPERPLEXITYSTYLIZEDWAYLAYING\nName: MainBody\n\n{$data['body']}\n\n\n";
                $html .= "--COMPLEXITYPERPLEXITYSTYLIZEDWAYLAYING\nName: SideNavigation\n\n{$value}\n\n\n";
                $value = UtilCollection::GenerateBreadCrumbs([
                    [
                        'Target' => '/',
                        'Text' => 'Home'
                    ],
                    [
                        'Target' => '/'.$_FUNC,
                        'Text' => isset($data['Title']) ? $data['Title'] : $_FUNC
                    ]
                ]);
                $html .= "--COMPLEXITYPERPLEXITYSTYLIZEDWAYLAYING\nName: BreadCrumbs\n\n{$value}\n\n\n";
            }
        } else {
            $html = '';
        }
    } else {
        header ("X-User-Title: ".Registry::get("Website.Name"));
        $html = "--COMPLEXITYPERPLEXITYSTYLIZEDWAYLAYING\nName: MainBody\n\n$data\n\n\n";
    }

    header ("Access-Control-Allow-Origin: *");

    echo $html;
