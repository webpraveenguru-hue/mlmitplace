<?php
include_once('../common/init.loader.php');

$seskey = verifylog_sess('member');
if ($seskey == '' || !defined('ISLOADSTORE')) {
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
$pointvaluetocash = $bptoken['pointvaluetocash'];

$_SESSION['redirto'] = redir_to($FORM['redir']);

if ($FORM['dosubmit'] == '1' && $FORM['convertpoint'] > 0 && $pointvaluetocash > 0) {
    extract($FORM);

    $redirto = $_SESSION['redirto'];
    $_SESSION['redirto'] = '';

    // convert point to cash
    // add cash to wallet and update epoint
    $oldmbrpoint = $mbrstr['epoint'];
    $oldmbrwallet = $mbrstr['ewallet'];

    $restpoint = fmod($convertpoint, $pointvaluetocash);
    $fixepoint = $convertpoint - $restpoint;
    $exchval = $fixepoint / $pointvaluetocash;
    $ewallet = $oldmbrwallet + $exchval;

    $data = array(
        'ewallet' => $ewallet,
    );
    $update = $db->update(DB_TBLPREFIX . '_mbrs', $data, array('id' => $mbrstr['id']));
    // adjust wallet
    $newtrxid = adjusttrxwallet($oldmbrwallet, $ewallet, $mbrstr['id'], $LANG['g_convertpointcash'] . " (PV:{$fixepoint})");

    // insert point history
    dodb_point(0, $mbrstr, -$fixepoint, "Convert to cash", '50', "|exchtype:ppcash|, |txidconvert:{$newtrxid}|, |exchval:{$exchval}|");

    redirpageto($redirto);
    exit;
}
?>

<div class="row">
    <div class="col-md-12">

        <?php
        if ($mbrstr['epoint'] < $pointvaluetocash) {
            ?>
            <div class="empty-state" data-height="200">
                <div class="empty-state-icon bg-danger">
                    <i class="fas fa-exclamation"></i>
                </div>
                <p class="lead">
                    <?php echo myvalidate($LANG['g_convertpointcasherror']); ?>
                </p>
                <div class="text-md-center mt-2">
                    <a href='javascript:;' class='btn btn-primary' data-dismiss="modal"><?php echo myvalidate($LANG['g_continue']); ?></a>
                </div>
            </div>
            <?php
        } else {
            $afhash = md5($mdlhashy . '1' . $mbrstr['id']);
            $pvcstr = $pointvaluetocash;
            $cvcstr = 1;
            $g_convertpointcashinfo = str_replace(['[[PV]]', '[[CV]]'], [$pvcstr, $bpprow['currencysym'] . $cvcstr], $LANG['g_convertpointcashinfo'] ?? '');
            ?>
            <form method="post" action="convertpointcash.php">
                <div class="form-group">
                    <?php
                    if ($cfgtoken['ismbrkyc'] == 1 && $mbrstr['kystatus'] != 1) {
                        echo myvalidate($LANG['m_kycverifynote']);
                        echo "<div class='mt-4'><a href='index.php?hal=accountcfg' class='btn btn-warning'>{$LANG['g_continue']} <i class='fas fa-fw fa-arrow-right'></i></a></div>";
                    } else {
                        ?>
                        <div class="alert alert-light text-small text-info mb-2"><?php echo myvalidate($g_convertpointcashinfo); ?></div>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"> <?php echo myvalidate($LANG['g_convertpointamount']); ?></span>
                            </div>
                            <input type="number" step="<?php echo myvalidate($pointvaluetocash); ?>" name="convertpoint" class="form-control" onkeypress="return (event.charCode != 8 && event.charCode == 0 || (event.charCode == 46 || (event.charCode >= 48 && event.charCode <= 57)))">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit">Submit</button>
                            </div>
                        </div>
                        <div class="text-md-center mt-4">
                            <a href="javascript:;" class="btn btn-warning" data-dismiss="modal"><i class="fa fa-fw fa-times"></i> Cancel</a>
                        </div>
                        <?php
                    }
                    ?>
                </div>
                <input type="hidden" name="dosubmit" value="1">
                <input type="hidden" name="mdlhashy" value="<?php echo myvalidate($mdlhashy); ?>">
                <input type="hidden" name="redir" value="withdrawreq">
            </form>
            <?php
        }
        ?>

    </div>

</div>
