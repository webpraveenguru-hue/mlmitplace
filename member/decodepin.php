<?php

include_once('../common/init.loader.php');

$seskey = verifylog_sess('member');
if ($seskey == '') {
    die('o o p s !');
}
if ($mdlhashy != $FORM['mdlhashy']) {
    echo myvalidate($LANG['a_loadingmdlcnt']);
    redirpageto('index.php', 1);
    exit;
}

$sesRow = getlog_sess($seskey);
$mbrusername = get_optionvals($sesRow['sesdata'], 'un');

// Get referrer/member details
$mbrstr = getmbrinfo($mbrusername, 'username');

$_SESSION['redirto'] = redir_to($FORM['redir']);
$redirto = $_SESSION['redirto'];

if (isset($FORM['epcodelist']) && $FORM['epcodelist'] != '') {

    $_SESSION['redirto'] = '';
    $iscoded = 0;

    $resultmsg = '';
    $epcodelistsarr = explode(',', str_replace(' ', '', $FORM['epcodelist']));
    foreach ($epcodelistsarr as $value) {
        $epinarr = get_epininfo($value, 'epcode', $mbrstr);

        if ($mbrstr['id'] == $epinarr['epusedid'] || $epinarr['epusedid'] < 1) {
            if ($epinarr['eptype'] == 'item') {
                // get product details
                $itemarr = get_iteminfo($epinarr['epvalue']);
                // add new transaction
                do_decodepinitem($mbrstr, $itemarr, $epinarr);

                $iscoded++;
            }
            if ($epinarr['eptype'] == 'plan' && $stctoken['stcismbrdecodeplan'] == '1') {
                $epmppid = $epinarr['epvalue'];
                if ($mbrstr['mpstatus'] != '1' || ($mbrstr['mppid'] == $epmppid && $mbrstr['reg_date'] < $mbrstr['reg_expd'])) {
                    do_decodepinplan($mbrstr, $epinarr);
                    $iscoded++;
                } else {
                    $resultmsg = $LANG['g_epinskipmbractive'];
                }
                $redirto = redir_to($FORM['dashboard']);
            }
        }
    }
    $resultmsg = ($resultmsg == '') ? "{$LANG['g_epintotdecoded']} {$iscoded}" : $resultmsg;
    $_SESSION['dotoaster'] = "toastr.success('{$resultmsg}', 'Success');";
}
header('location: ' . $redirto);
exit;
