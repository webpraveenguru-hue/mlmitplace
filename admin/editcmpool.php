<?php
include_once('../common/init.loader.php');

$seskey = verifylog_sess('admin');
if ($seskey == '') {
    die('o o p s !');
}
if ($mdlhashy != $FORM['mdlhashy']) {
    echo myvalidate($LANG['a_loadingmdlcnt']);
    redirpageto('index.php', 1);
    exit;
}

if (isset($FORM['dosubmit']) && $FORM['dosubmit'] == '1') {
    extract($FORM);

    $data = array(
        'poolwlet' => floatval($poolwlet),
    );
    $update = $db->update(DB_TBLPREFIX . '_configs', $data, array('cfgid' => '1'));

    if ($update) {
        $_SESSION['dotoaster'] = "toastr.success('Balance updated successfully!', 'Success');";
    } else {
        $_SESSION['dotoaster'] = "toastr.warning('{$LANG['g_nomajorchanges']}', 'Info');";
    }

    $redirto = $hal;
    header('location: index.php?hal=' . $redirto);
    exit;
}
?>

<div class="row">
    <div class="col-md-12">
        <form method="get" action="editcmpool.php">
            <div class="form-group">
                <div class="alert alert-light text-small text-info mb-2">This balance can be used to distribute commission to the member on an interval basis through the <a href="index.php?hal=ranklist"><?php echo myvalidate($LANG['a_rank']); ?></a> feature.</div>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Commission Pool <?php echo myvalidate("({$bpprow['currencycode']})"); ?></span>
                    </div>
                    <input type="text" name="poolwlet" class="form-control" value="<?php echo myvalidate($cfgrow['poolwlet']); ?>">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="submit">Update</button>
                    </div>
                </div>
                <div class="text-md-center mt-4">
                    <a href="javascript:;" class="btn btn-warning" data-dismiss="modal"><i class="fa fa-fw fa-times"></i> Cancel</a>
                </div>
            </div>
            <input type="hidden" name="dosubmit" value="1">
            <input type="hidden" name="mdlhashy" value="<?php echo myvalidate($mdlhashy); ?>">
            <input type="hidden" name="hal" value="dashboard">
        </form>
    </div>
</div>
