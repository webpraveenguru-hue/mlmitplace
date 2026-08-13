<?php

// Error reporting value //
//-------------------------------------------------------
// PHP error reporting. supported values are given below.
// 0 - Turn off all error reporting
// 1 - Running errors
// 2 - Running errors + notices
// 3 - All errors except notices and warnings
// 4 - All errors except notices and strict
// 5 - All errors except notices
// 6 - All errors
$php_error_reporting = 3;

// setting PHP error reporting (do not edit, for developer/debug only)
switch ($php_error_reporting) {
    case 0: error_reporting(0);
        break;
    case 1: error_reporting(E_ERROR | E_WARNING | E_PARSE);
        break;
    case 2: error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
        break;
    case 3: error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));
        break;
    case 4: error_reporting(E_ALL ^ E_NOTICE ^ E_STRICT);
        break;
    case 5: error_reporting(E_ALL ^ E_NOTICE);
        break;
    case 6: error_reporting(E_ALL);
        break;
    default:
        error_reporting(E_ALL);
}
// end error reporting
# ------------------------------------------------------------------------ #

include_once('config.php');
if (!defined('INSTALL_PATH')) {
    define('INSTALL_PATH', '');
}
if (INSTALL_PATH == '') {
    header("Location: ../install");
}

$setsesname = md5(DB_NAME . INSTALL_PATH);
session_name($setsesname);
session_start();

if (!defined('OK_LOADME')) {
    die("<title>Error!</title><body>No such file or directory.</body>");
}
// -----

$FORM = array_merge((array) $FORM, (array) $_REQUEST);
$LANG = array_merge((array) $LANG, (array) $lang);

// database
include_once('db.func.php');

$dsn = "mysql:dbname=" . DB_NAME . ";host=" . DB_HOST . "";
$pdo = "";

try {
    $pdo = new PDO($dsn, base64_decode(DB_USER), base64_decode(DB_PASSWORD));
    $pdo->exec('SET CHARACTER SET utf8');

    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

$db = new Database($pdo);
$db->doQueryStr("SET SESSION sql_mode = ''");

// load site configuration
$tplstr = $cfgrow = $bpprow = $bpparr = $stcrow = $payrow = array();
$mwstr = 'xwi';
$mdstr = 'axde';
$didId = 1;

// settings
$row = $db->getAllRecords(DB_TBLPREFIX . '_configs', '*', ' AND cfgid = "' . $didId . '"');
foreach ($row as $value) {
    $cfgrow = array_merge($cfgrow, $value);
}
$cfgrow['md5sess'] = 'sess_' . md5(INSTALL_PATH) . '_';
$cfgrow['site_url'] = (defined('INSTALL_URL')) ? INSTALL_URL : trim($cfgrow['site_url'] ?? '');
$site_logo = ($cfgrow['site_logo']) ? $cfgrow['site_logo'] : DEFIMG_LOGO;
$cfgtoken = get_optionvals($cfgrow['cfgtoken']);
$cfgrow['_isnocredit'] = get_isnocredit($cfgtoken);
$langlist = base64_decode($cfgtoken['langlist'] ?? '');
$langlistarr = json_decode($langlist, true);
if (empty(array_filter((array) $langlistarr))) {
    $langlistarr['en'] = 'English';
}

// dark css
if ($cfgtoken['isdarkthemeopt'] == 1) {
    $whatdarkcss = '<link rel="stylesheet" href="../assets/css/customdark.css">';
} else if ($cfgtoken['isdarkthemeopt'] == 2) {
    $whatdarkcss = '<link rel="stylesheet" href="../assets/css/customsidedark.css">';
} else {
    $whatdarkcss = '';
}
$customdark_css = ($cfgtoken['isdarktheme'] != 1) ? '' : $whatdarkcss;

// website logo style
switch ($cfgtoken['weblogostyle']) {
    case 'rounded':
        $weblogo_style = ' rounded';
        break;
    case 'circle':
        $weblogo_style = ' rounded-circle';
        break;
    case 'thumbnail':
        $weblogo_style = ' img-thumbnail img-shadow';
        break;
    default:
        $weblogo_style = '';
}

// custom color css
if ($cfgtoken['themeclr'] != '') {
    $stylecolor_css = '<link rel="stylesheet" href="../assets/css/colors/' . $cfgtoken['themeclr'] . '/style.css"><link rel="stylesheet" href="../assets/css/colors/' . $cfgtoken['themeclr'] . '/components.css">';
} else {
    $stylecolor_css = '<link rel="stylesheet" href="../assets/css/style.css"><link rel="stylesheet" href="../assets/css/components.css">';
}
