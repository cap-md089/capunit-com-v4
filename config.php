<?php
  date_default_timezone_set("America/New_York"); 

  error_reporting (E_ALL); // Fix errors as they appear
  ini_set("display_errors", "empty");

  if (get_magic_quotes_gpc()) {
    $_GET   = array_map ('stripslashes', $_GET);
    $_POST  = array_map ('stripslashes', $_POST);
  }

  define ("START_TIME", time());

  error_reporting(E_ALL ^ E_NOTICE);
  define ("WEBSITE_HOST", $_SERVER['SERVER_NAME']);
  define ("WEBSITE_ADDRESS", ($_SERVER['SERVER_PORT'] == 443 ? "https" : "http") . "://" . WEBSITE_HOST . "/");
  error_reporting(E_ALL);

  // Database login info
  const DB_PROTOCOL = "mysql";
  const DB_UNAME = "em";
  const DB_UPASS = "alongpassword2017";
  const DB_HOST = "127.0.0.1";
  const DB_NAME = "DEventManagement";
  const DB_PORT = "33389";
  define("DB_TABLES", parse_ini_file("tables.ini"));
  define("DBTABLES", parse_ini_file("tables.ini"));

  // Host options
  const HOST_ADDRESS = 'capunit.com';
  define("HOST_OS", php_uname("s"));
  define("HOST_SSL", true);
  const HOST_SUB_DIR = ''; // If the website is located in a subdirectory, make urls /subdir/uri instead of /uri (must end with /)
  // THE FOLLOWING IS *DEPRECATED*, shouldn't be changed or else code will break
  const HOST_USE_ABS_LINKS = true; // Use a URL like https://HOST_ADDRESS/uri or /uri, /uri if true 

  const DEBUG_DEBUG_LEVEL = 9; // 9 is debug everything, 1 is debug almost nothing, 0 is don't debug (0 is recommended)
  const DEBUG_LOG_LEVEL = 8; // 9 is log everything, 1 is log almost nothing, 0 is don't log (5 is recommended)
  const DEBUG_FORMAT = '[%s] {%s} [%s]: %s'; // Logline (number of times a logger has logged something), time, level, data
  const DEBUG_FILE_COUNT = 1; // 0 is each logger has 36 files (9 log levels * 4 error levels (DEBUG, LOG, WARN, ERROR)),
  // 1 is they have 4 (DEBUG, LOG, WARN, ERROR), 2 is they have 1 (DEBUG_FORMAT becomes $errlevel . DEBUG_FORMAT), 3 is there is one
  // log file (DEBUG_FORMAT becomes Logger::name . $errlevel . DEBUG_FORMAT), 4 is everything is logged to main.LOG
  // 1 is recommended, see API documentation for the Logger class

  const DEVELOPER_ADMIN = true; // This allows developers to do EVERYTHING, not just view log files

  // This is used to define the root directory used in the project
  define ("BASE_DIR", dirname(__FILE__) . '/');

  define ("AWS_SERVER", extension_loaded("bz2"));

  if (!HOST_SSL) {
    echo 'This website needs an SSL certificate to function, or to have the HOST_SSL option in the config file set to true.';
    die();
  }
