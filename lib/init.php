<?php
error_reporting(E_ALL);

/* Root dir */
$root = dirname(__FILE__)."/../";

/* Debug mode */
$debug = 0;

/* Database type */
$db_name = 'MYSQL';

/* Time without actions */
$life_time = ini_get("session.gc_maxlifetime");

/* Demo stand (deny settings, file upload)*/
$demo = 0;

$extjs = 'extjs';

/* App version */
$version = "1.070";
//$version = rand(); //for reset cache

/* Path to imgs and js */
$static = "static";

/* Path to OCR service */
$ocr_host = "http://homebuh.pro/api/ocr/recognize.php";

/* Google map key */
$g_key = '';

if($debug) {
  error_reporting(~E_NOTICE);
}

$settings = array();

require_once($root.'lib/functions.php');
require_once($root.'lib/casherror.php');
require_once($root.'lib/db/db.php');


$db = null;

if($db_name == 'MYSQL') {
  require_once($root.'lib/db/filecachdb.php');
  require_once($root.'lib/db/mysqli.php');
  
  /* MYSQL config */
  $conf = array('srv' => 'localhost', 'login' => 'root', 'pasw' => '', 'db' => 'cash');
  
  $db = new MySQLi_DB('MYSQLI', $conf['srv'], $conf['db'], $conf['login'], $conf['pasw']);
} else {

  /* Path to sqlite database */
  $sqlite_path = $root."data/cash.db3";

  require_once($root.'lib/db/sqlite.php');

  if (!extension_loaded('sqlite3')) {
    echo "SqLite3 module not loaded";
    throw new CashError("SqLite3 module not loaded");
  }

  $db = new SQLITE_DB($sqlite_path);
} //db_name

if(!$db) throw new CashError("DB connect error");
$db->connect();

/* csrf token protection */
$settings['csrf'] = csrf_protect();
if( empty( $settings['csrf'] ) ) exit;

/* Max uploaded file size */
$max_file_size = get_max_fileupload_size();



if((bool)$short) return;

require_once($root.'lib/lang.php');
$lng = new Lang();
$lng->set($_COOKIE['LANG']);

require_once($root.'lib/user.php');
require_once($root.'lib/cash.php');
require_once($root.'lib/update.php');

if($debug) {
  $db->debug = true;
}

$usr = new User($db, $lng);
$usr->auth();

$upd = new Update($db, $lng, $usr);


$ch = new Cash($db, $usr, $lng);

$settings['version'] = $version;
$settings['demo'] = $demo;
$settings['debug'] = $debug;
$settings['static'] = $static;
$settings['extjs'] = $extjs;
$settings['ocr_host'] = $ocr_host;
$settings['g_key'] = $g_key;

//setup or update
$settings['setup'] = 0;
$settings['update'] = 0;
if( $upd->needSetup() ) {
  $settings['setup'] = 1;
  $settings['site_name'] = $lng->get(213);
} else {
  if($upd->needUpdate()) {
    /*$settings['update'] = 1;
    $settings['site_name'] = $lng->get(219);*/
    $upd->update();
  }
  $settings = array_merge($settings, $ch->getSettings() );
}
$settings['date_format'] = $lng->getDateFormat();