<?php
include_once('../common/init.loader.php');

if ($cfgrow['site_status'] != 1) {
    redirpageto($cfgrow['site_url'] . '/' . MBRFOLDER_NAME . '/offline.php');
    exit;
}

$hal = mystriptag($FORM['hal']);
$pagefile = ($avalmemberpage_array[$hal] == 1) ? $hal . '.php' : 'dashboard.php';

$menuactive = array();
foreach ($avalmemberpage_array as $key => $value) {
    $menuactive[$key] = ($key == $hal) ? ' class="active"' : '';
}

$seskey = verifylog_sess('member');
if ($seskey == '') {
    // force login for empty session
    redirpageto('login.php?err=expiry');
    exit;
}

$sesRow = getlog_sess($seskey);
$username = get_optionvals($sesRow['sesdata'], 'un');

// Get member details
$mbrstr = getmbrinfo($username, 'username');
$mbrstr['fullname'] = $mbrstr['firstname'] . ' ' . $mbrstr['lastname'];

// if require email confirm
if ($mbrstr['isconfirm'] < 1 && $cfgtoken['ismbrneedconfirm'] > 0) {
    get_codeconfirm($mbrstr);
    redirpageto("regconfirm.php");
    exit;
}

if ($mbrstr['id'] < 1) {
    // force logout for unknown username
    redirpageto("logout.php?un={$username}&err=notfound");
    exit;
}
if (($mbrstr['mbrstatus'] < 1 && ($hal == 'planreg' || $hal == 'planpay')) || $mbrstr['mbrstatus'] == 3) {
    $pagefile = 'dashboard.php';
}
if ($mbrstr['mpid'] < 1 && in_array($hal, array("userlist", "historylist", "withdrawreq", "genealogyview", "getuser", "mmbanner"))) {
    $pagefile = 'dashboard.php';
}

// Get sponsor details
$sprstr = getmbrinfo($mbrstr['idspr']);

// load my language
$mylangstr = ($mbrstr['mylang'] == '') ? $cfgrow['langiso'] : $mbrstr['mylang'];
$langloadf = INSTALL_PATH . '/common/lang/' . $mylangstr . '.lang.php';
if (file_exists($langloadf)) {
    $TEMPLANG = $LANG;
    include($langloadf);
    $LANG = array_filter($LANG);
    $LANG = array_merge($TEMPLANG, $LANG);
    $TEMPLANG = '';
}

// language list
$langliststr = '';
foreach ($langlistarr as $key => $value) {
    $langicon = ($key == $LANG['lang_iso']) ? "fa-check" : "fa-minus";
    $langliststr .= "<a href='index.php?hal={$FORM['hal']}&lang={$key}&langdt={$_SESSION['dumbtoken']}' class='dropdown-item has-icon'><i class='fas {$langicon}'></i> {$value}</a>";
}

// update language
if ($langlistarr[$FORM['lang']] != '' && $FORM['langdt'] == $_SESSION['dumbtoken']) {
    $httpurlref = $_SERVER['HTTP_REFERER'];
    $langiso = strtolower(mystriptag($FORM['lang']));
    $update = $db->update(DB_TBLPREFIX . '_mbrs', array('mylang' => $langiso), array('id' => $mbrstr['id']));
    header("Location: {$httpurlref}");
    exit;
}

// do sales action
$deliverhash = hash('sha256', $FORM['dodeliver'] . $FORM['slid'] . $mdlhashy);
if ($FORM['dodeliver'] != '' && $deliverhash == $FORM['didhash']) {
    $slid = intval($FORM['slid']);
    $row = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_sales WHERE slid = '{$slid}' ");
    $slarr = $row[0];

    $didhit = get_optionvals($slarr['sltoken'], $FORM['dodeliver']);
    $sltoken = put_optionvals($slarr['sltoken'], $FORM['dodeliver'], $didhit + 1);

    $data = array(
        'sltoken' => $sltoken,
    );
    $update = $db->update(DB_TBLPREFIX . '_sales', $data, array('slid' => $slarr['slid']));

    echo ($FORM['dodeliver'] == 'didfile') ? 'Downloading...' : '';
    redirpageto(base64_decode($FORM['doredir'] ?? ''));
    exit;
}

// process download
if ($FORM['dlfn'] && $FORM['dlid'] > 0) {
    $flhashshort = md5($FORM['dlid'] . $FORM['dlfn'] . '*');
    $dlordhash = hash('md5', $FORM['dlid'] . $mdlhashy . date('d_H'));
    if ($FORM['l'] == md5($cfgrow['dldir'] . $FORM['dlid'] . date("md")) || $FORM['i'] == $flhashshort || $FORM['o'] == $dlordhash) {
        $flid = intval($FORM['dlid']);
        $condition = " AND flid = '{$flid}' ";
        $row = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_files WHERE 1 " . $condition . "");
        $filRow = array();
        foreach ($row as $value) {
            $filRow = array_merge($filRow, $value);
        }

        // count download
        $fldlcount = $filRow['fldlcount'] + 1;
        $data = array(
            'fldlcount' => $fldlcount,
        );
        $update = $db->update(DB_TBLPREFIX . '_files', $data, array('flid' => $flid));

        $extfile = pathinfo($filRow['flpath'] ?? '', PATHINFO_EXTENSION);
        $dlfilename = ($FORM['dlfn'] || $FORM['i']) ? $FORM['dlfn'] : preg_replace('/[\W]/', '_', $filRow['flname']) . '.' . $extfile;
        // process download
        $mtype = "application/force-download";
        dodlfile($filRow['flpath'], $dlfilename, $mtype);
    } else {
        header("Location: index.php?hal=digiload");
        exit;
    }
}

// session time interval
$logtimeago = time_since($sesRow['sestime']);

// -----

include_once('mbrheader.php');
?>

<!-- Main Content -->
<div class="main-content">
    <section class="section">
        <?php
        if (!defined('ISLOADSTORE') && array_key_exists($hal, $avalstorepage_array)) {
            include ("../common/unavailable.php");
        } else if (file_exists($pagefile)) {
            include ($pagefile);
        } else {
            include ("nofile.php");
        }
        ?>
    </section>
</div>

<!-- Modal -->
<div class="modal fade" id="myModal" role="dialog" aria-labelledby="...">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">

        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">New message</h5>
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
            </div>
        </div>

    </div>
</div>

<?php
include_once('mbrfooter.php');

