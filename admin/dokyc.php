<?php
include_once('../common/init.loader.php');

if (verifylog_sess('admin') == '') {
    die('o o p s !');
}
if ($mdlhasher != $FORM['mdlhasher']) {
    echo myvalidate($LANG['a_loadingmdlcnt']);
    redirpageto('index.php', 1);
    exit;
}

// load kyc images
if ($FORM['kyid'] > 0 && $FORM['mbrid'] > 0 && $FORM['kytype'] != '') {
    $hash = md5($FORM['kyid'] . date("dH"));
    do_getkycimg($FORM['kyid'], $FORM['mbrid'], $FORM['kytype'], $hash);
    die();
}

$_SESSION['redirto'] = redir_to($FORM['redir']);

$redirto = $_SESSION['redirto'];
$_SESSION['redirto'] = '';

if (isset($FORM['kyID']) && $FORM['kyID'] > 0) {
    $condition = ' AND kyid LIKE "' . $FORM['kyID'] . '" ';
    $sql = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_mbrkycs LEFT JOIN " . DB_TBLPREFIX . "_mbrs ON kyidmbr = id WHERE 1 " . $condition . "");
    $kycStr = $sql[0];

    $kymbrnote = $kycStr['kymbrnote'];
    $kymbrnote = str_replace('&lt;p&gt;', '<p>', $kymbrnote ?? '');
    $kymbrnote = str_replace('&lt;/p&gt;', '</p>', $kymbrnote ?? '');

    $kystatus_arr = array('0' => $LANG['g_kycunverified'], '1' => $LANG['g_kycverified'], '2' => $LANG['g_kycincomplete']);
    $kystatus_menu = select_opt($kystatus_arr, $kycStr['kystatus']);
    $kyctokenarr = get_optionvals($kycStr['kytoken']);
}

if (isset($FORM['dosubmit']) && $FORM['dosubmit'] == '1') {
    extract($FORM);
    $editId = intval($kyID);

    $kymbrnote = ($kymbrnote != '') ? "<p>* {$kymbrnote}</p>" . $kycStr['kymbrnote'] : $kycStr['kymbrnote'];
    $kytoken = $kycStr['kytoken'];

    if ($kystatus == '1') {
        $kyorder = 2;
    } else if ($kystatus == '2') {
        $kyorder = 1;
    } else {
        $kyorder = 0;
    }
    $data = array(
        'kyupdate' => $cfgrow['datetimestr'],
        'kystatus' => intval($kystatus),
        'kyorder' => intval($kyorder),
        'kytoken' => $kytoken,
        'kymbrnote' => mystriptag($kymbrnote),
        'kyadminfo' => mystriptag($kyadminfo),
    );
    $update = $db->update(DB_TBLPREFIX . '_mbrkycs', $data, array('kyid' => $editId));

    if ($update) {
        $_SESSION['dotoaster'] = "toastr.success('Record updated successfully!', 'Success');";
    } else {
        $_SESSION['dotoaster'] = "toastr.error('Record not updated <strong>Please try again!</strong>', 'Warning');";
    }

    header('location: ' . $redirto);
    exit;
}

if (isset($FORM['delId']) && $FORM['delId'] > 0) {

    $delId = intval($FORM['delId']);
    $hasdel = md5($delId . date("dH"));
    if ($FORM['hash'] == $hasdel) {
        if (defined('ISDEMOMODE') && $delId <= 1) {
            $_SESSION['dotoaster'] = "toastr.error('Demo Mode - Delete KYC failed!', 'Error');";
        } else {
            $db->delete(DB_TBLPREFIX . '_mbrkycs', array('kyid' => $delId));
            $_SESSION['dotoaster'] = "toastr.success('KYC removed successfully!', 'Success');";
        }
    } else {
        $_SESSION['dotoaster'] = "toastr.error('KYC failed to remove!', 'Error');";
    }

    $redirto = redir_to($FORM['redir']);
    header('location: ' . $redirto);
    exit;
}
?>

<div class="row">
    <div class="col-md-12">

        <p class="text-primary">Fields with <span class="text-danger">*</span> are mandatory!</p>

        <form method="post" action="dokyc.php">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Name</label>
                    <div><?php echo myvalidate($kycStr['firstname'] . ' ' . $kycStr['lastname']); ?></div>
                </div>
                <div class="form-group col-md-6">
                    <label>Username / Email</label>
                    <div><?php echo myvalidate($kycStr['username'] . ' (' . $kycStr['email'] . ')'); ?></div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label>Address</label>
                    <div>
                        <?php echo myvalidate((($kycStr['address'] != '') ? $kycStr['address'] : '-') . ', ' . (($kycStr['state'] != '') ? $kycStr['state'] : '-')) . ', ' . $country_array[$kycStr['country']]; ?>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-12">
                    <label>KYC Images</label>
                    <div>
                        <img src="data:<?php echo myvalidate($kyctokenarr['kyimgidtype']); ?>;base64,<?php echo myvalidate($kycStr['kyimgid']); ?>" class='img-fluid border mb-2'>
                        <img src="data:<?php echo myvalidate($kyctokenarr['kyimgbilltype']); ?>;base64,<?php echo myvalidate($kycStr['kyimgbill']); ?>" class='img-fluid border mb-2'>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-12">
                    <label>Status <span class="text-danger">*</span></label>
                    <select name="kystatus" id="kystatus" class="form-control">
                        <?php echo myvalidate($kystatus_menu); ?>
                    </select>

                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-12">
                    <div class='alert alert-light text-small'><?php echo isset($kymbrnote) ? $kymbrnote : ''; ?></div>
                    <label>Note for Member (optional)</label>
                    <textarea class="form-control rowsize-sm" name="kymbrnote" id="kymbrnote"></textarea>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label>Admin Note</label>
                    <textarea class="form-control rowsize-sm" name="kyadminfo" id="kyadminfo" placeholder="Available for administrator only"><?php echo isset($kycStr['kyadminfo']) ? $kycStr['kyadminfo'] : ''; ?></textarea>
                </div>
            </div>

            <div class="text-md-right">
                <a href="javascript:;" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-fw fa-times"></i> Cancel</a>
                <button type="submit" name="submit" value="submit" id="submit" class="btn btn-primary">
                    <i class="fa fa-fw fa-check"></i> Submit
                </button>
                <input type="hidden" name="kyID" value="<?php echo myvalidate($FORM['kyID']); ?>">
                <input type="hidden" name="dosubmit" value="1">
                <input type="hidden" name="redir" value="kyclist">
                <input type="hidden" name="mdlhasher" value="<?php echo myvalidate($mdlhasher); ?>">
            </div>
        </form>

    </div>

</div>
