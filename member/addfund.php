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
?>

<div class="row">
    <div class="col-md-12">

        <?php
        $unpaidtxid = get_unpaidtxid($mbrstr);
        if ($unpaidtxid > 0) {
            ?>
            <div class="empty-state" data-height="200">
                <div class="empty-state-icon bg-danger">
                    <i class="fas fa-exclamation"></i>
                </div>
                <p class="lead">
                    <?php echo myvalidate($LANG['m_addfundstop']); ?>
                </p>
                <div class="text-md-center mt-2">
                    <a href='index.php?hal=planpay' class='btn btn-primary'><?php echo myvalidate($LANG['g_continue']); ?></a>
                </div>
            </div>
            <?php
        } else {
            $afhash = md5($mdlhashy . '1' . $mbrstr['id']);
            ?>
            <form method="get" action="index.php">
                <div class="form-group">
                    <div class="alert alert-light text-small text-info mb-2"><?php echo myvalidate($LANG['m_depositamountnote']); ?></div>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"> <?php echo myvalidate($LANG['m_depositamount'] . " ({$bpprow['currencycode']})"); ?></span>
                        </div>
                        <input type="number" name="addamount" class="form-control" onkeypress="return (event.charCode != 8 && event.charCode == 0 || (event.charCode == 46 || (event.charCode >= 48 && event.charCode <= 57)))">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit">Submit</button>
                        </div>
                    </div>
                    <div class="text-md-center mt-4">
                        <a href="javascript:;" class="btn btn-warning" data-dismiss="modal"><i class="fa fa-fw fa-times"></i> Cancel</a>
                    </div>
                </div>
                <input type="hidden" name="dosubmit" value="1">
                <input type="hidden" name="l" value="<?php echo myvalidate($afhash); ?>">
                <input type="hidden" name="hal" value="orderpay">
                <input type="hidden" name="itid" value="1">
            </form>
            <?php
        }
        ?>

    </div>

</div>
