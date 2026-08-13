<?php

include_once('init.db.php');
if (!defined('OK_LOADME')) {
    die("<title>Error!</title><body>No such file or directory.</body>");
}

// baseplan
$row = $db->getAllRecords(DB_TBLPREFIX . '_baseplan', '*', ' AND bpid = "' . $didId . '"');
foreach ($row as $value) {
    $bpprow = array_merge($bpprow, $value);
}
$bpprow['currencysym'] = base64_decode($bpprow['currencysym'] ?? '');
$bptoken = get_optionvals($bpprow['bptoken']);
$bpprowbase = $bpprow;

// payplan
$row = $db->getAllRecords(DB_TBLPREFIX . '_payplans', '*', ' AND ppid = "' . $didId . '"');
foreach ($row as $value) {
    $bpprow = array_merge($bpprow, $value);
}
$mw = $bpprow['ma' . $mwstr . 'dth'];
$md = $bpprow['m' . $mdstr . 'pth'];
$plantokenarr = get_optionvals($bpprow['plantoken']);
$planimg = ($bpprow['planimg']) ? $bpprow['planimg'] : DEFIMG_PLAN;
$planlogo = ($bpprow['planlogo']) ? $bpprow['planlogo'] : DEFIMG_LOGO;

// store
$row = $db->getAllRecords(DB_TBLPREFIX . '_storecfg', '*', ' AND stcid = "' . $didId . '"');
foreach ($row as $value) {
    $stcrow = array_merge($stcrow, $value);
}
$stctoken = get_optionvals($stcrow['stctoken']);
if ($stcrow['stcstatus'] == 1) {
    define('ISLOADSTORE', 1);
}

// paymentgate
$row = $db->getAllRecords(DB_TBLPREFIX . '_paygates', '*', ' AND paygid = "' . $didId . '"');
foreach ($row as $value) {
    $payrow = array_merge($payrow, $value);
}

// navigator functions
include_once('navpage.class.php');
$pages = new Paginator();

// other functions
include_once('sys.func.php');
include_once('value.list.php');
include_once('en.lang.php');

// add access security layer
dumbtoken();
$bpparr = pplandbarr();

// current date time
$cfgrow['datestr'] = date('Y-m-d', time() + (3600 * $cfgrow['time_offset']));
$cfgrow['datetimestr'] = date('Y-m-d H:i:s', time() + (3600 * $cfgrow['time_offset']));

// language
$langloadf = INSTALL_PATH . '/common/lang/' . $cfgrow['langiso'] . '.lang.php';
if (file_exists($langloadf)) {
    $TEMPLANG = $LANG;
    include($langloadf);
    $LANG = array_filter($LANG);
    $LANG = array_merge($TEMPLANG, $LANG);
    $TEMPLANG = '';
}

// limit subadmin pages
if ($_SESSION['isunsubadm']) {
    $unsetadminpage_array = array(
        'generalcfg' => 1,
        'payplancfg' => 1,
        'paymentopt' => 1,
        'updates' => 1
    );
    $avaladminpage_array = \array_diff_key($avaladminpage_array, $unsetadminpage_array);
}

// get referrer id
$sesref = array();
do_isvalises();
if ($_SESSION['ref_sess_un'] || $_COOKIE['ref_sess_un']) {

    if ($_SESSION['ref_sess_un'] != $_COOKIE['ref_sess_un']) {
        setcookie('ref_sess_un', $_SESSION['ref_sess_un'] ?? '', time() + (86400 * $cfgrow['maxcookie_days']));
    }

    $ref_sess_un = ($_SESSION['ref_sess_un']) ? $_SESSION['ref_sess_un'] : $_COOKIE['ref_sess_un'];

    // get member details
    $sesref = getmbrinfo($ref_sess_un, 'username');

    // check for max personal ref
    if ($bpparr[$sesref['mppid']]['limitref'] > 0) {
        $refcondition = " AND idref = '{$sesref['id']}' AND mppid = '{$sesref['mppid']}'";
        $row = $db->getAllRecords(DB_TBLPREFIX . '_mbrplans', 'COUNT(*) as totref', $refcondition);
        $myperdltotal = $row[0]['totref'];
        if ($bpparr[$sesref['mppid']]['limitref'] <= $myperdltotal) {
            $newmpid = getmpidflow($sesref['mpid'], $sesref['mppid'], $sesref);
            $sesref = getmbrinfo('', '', $newmpid);
        }
    }
}

// if rand ref
if (!defined('IS_UPDATER') && $sesref['id'] < 1 && $cfgrow['randref'] == 1) {
    $randun = '';
    if ($cfgrow['defaultref'] != '') {
        $refarr = explode(',', str_replace(' ', '', $cfgrow['defaultref'] ?? ''));
        $i = array_rand($refarr);
        $randun = $refarr[$i];
    }
    $condition = " AND mbrstatus = '1' AND mpstatus = '1' AND username = '{$randun}'";
    $sql = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_mbrplans LEFT JOIN " . DB_TBLPREFIX . "_mbrs ON idmbr = id WHERE 1 " . $condition . " LIMIT 1");
    if (count($sql) < 1) {
        $condition = ' AND mbrstatus = "1" AND mpstatus = "1" ORDER BY RAND() LIMIT 1';
        $row = $db->getAllRecords(DB_TBLPREFIX . '_mbrplans LEFT JOIN ' . DB_TBLPREFIX . '_mbrs ON idmbr = id', 'username', $condition);
        $randun = $row[0]['username'];
    }
    // get member details from rand ref
    if ($randun) {
        $sesref = getmbrinfo($randun, 'username');
        $_SESSION['ref_sess_un'] = $randun;
    }
}

if ($cfgtoken['disreflink'] == 1 || $sesref['reflink'] == '' || $sesref['mpstatus'] == 3) {
    $sesref = array();
    $_SESSION['ref_sess_un'] = '';
    setcookie('ref_sess_un', '', time() - 86400);
}

// banner location
define('BANNER_FOLDER', 'assets/imagextra/placard');
define('BANNER_URL', $cfgrow['site_url'] . '/' . BANNER_FOLDER);
definebanfldr();

// is demo
if (defined('ISDEMOMODE')) {
    $tplstr['demo_mode_warn'] = "<ul class='navbar-nav'><li><div class='badge badge-danger'>Demo Mode</div></li></ul>";
}
$bpprow['ma' . $mwstr . 'dth'] = ($mw > $frlmtdcfg['ismw']) ? (8 + 2) : $mw;
$bpprow['m' . $mdstr . 'pth'] = ($md > $frlmtdcfg['ismd']) ? (5 * 4) : $md;
// is debug
if ($payrow['testpayon'] == 1) {
    $tplstr['debug_mode_warn'] = "<ul class='navbar-nav'><li><div class='badge badge-danger'>Debug Mode</div></li></ul>";
}
/* ---
 * IMPORTANT! If you intend to use the Regular license for commercial purposes,
 * please support us by maintaining the script credits (powered by text)
 * in their original form. It will help us to gather impression so that we can
 * continue to provide free updates to you. Otherwise, you can support us
 * by upgrading your Regular license to a Plus (or Extended) license. Thank you.
 * ---
 */
$isfolink = 1;
$strbyus = base64_decode('PGJyIC8' . "+" . 'UG93ZXJlZCBieSA=');
if ($cfgrow['_isnocredit']) {
    $cfgrow['_isnocreditstr'] = '';
} else {
    $cfgrow['_isnocreditstr'] = "{$strbyus}<a href='{$ssysout('SSYS_URL')}/u" . "mx' target='_blank'>{$ssysout('SSYS_AUTHOR')}</a>";
}

// get modal hash
$mbrtempses = verifylog_sess('member');
$mdlhasher = hash('sha256', $ssysout('INSTALL_HASH') . "+" . $cfgrow['datestr']);
$mdlhashy = hash('sha256', $ssysout('INSTALL_KEYS') . "/" . $mbrtempses . $cfgrow['datestr']);

// end vars
$row = $value = '';
