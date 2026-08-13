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

$editId = intval($FORM['editId']);

if (isset($editId) && $editId != "") {
    $salesstr = get_salesinfo($editId);
    $_SESSION['redirto'] = redir_to($FORM['redir']);

    $slstatusarr = array(0, 1, 2, 3, 4);
    $slstatus_cek = radiobox_opt($slstatusarr, $salesstr['slstatus']);
}

if (isset($FORM['dosubmit']) && $FORM['dosubmit'] == '1') {
    extract($FORM);
    $editId = intval($editId);

    if (!dumbtoken($dumbtoken)) {
        $_SESSION['show_msg'] = showalert('danger', 'Error!', $LANG['g_invalidtoken']);
        $redirval = "?res=errtoken";
        redirpageto($redirval);
        exit;
    }

    $data = array(
        'slitid' => intval($slitid),
        'slprice' => $slprice,
        'slstatus' => intval($slstatus),
        'slnote' => $slnote,
    );

    $redirto = $_SESSION['redirto'];
    $_SESSION['redirto'] = '';

    if (isset($editId) && $editId > 0) {
        // if update sales history
        $condition = ' AND slid = "' . $editId . '" ';
    }
    $sql = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_sales WHERE 1 " . $condition . "");
    if (count($sql) > 0) {
        if ($slitid > 0) {
            $update = $db->update(DB_TBLPREFIX . '_sales', $data, array('slid' => $editId));
            if ($update) {
                $_SESSION['dotoaster'] = "toastr.success('Record updated successfully!', 'Success');";
            } else {
                $_SESSION['dotoaster'] = "toastr.warning('Update failed. Data is unchanged!', 'Info');";
            }
        } else {
            // do nothing
            $_SESSION['dotoaster'] = "toastr.error('Update failed. <strong>Item is not exist!</strong>', 'Warning');";
        }
    }
    header('location: ' . $redirto);
    exit;
}

$itemlistopt = get_itemlist($mbrstr, $salesstr['slitid'])
?>

<div class="row">
    <div class="col-md-12">

        <p class="text-primary">Fields with <span class="text-danger">*</span> are mandatory!<br />
            <?php
            if ($salesstr['slid'] > 0) {
                ?>
                <span class="text-small text-muted">will be processes as is without additional changes in the transaction history and others.</span></p>
            <?php
        }
        ?>

        <form method="post" action="mydosales.php">

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Product <span class="text-danger">*</span></label>
                    <select name="slitid" id="slitid" class="form-control" onBlur="checkMember('id2itname', this.value, '1');">
                        <?php echo myvalidate($itemlistopt); ?>
                    </select>
                </div>
                <div class="form-group col-md-8">
                    <label>Name</label>
                    <div id="resultGetMbr1"><?php echo isset($itemname) ? "<span class='text-primary'><strong>{$itemname}</strong></span>" : '?'; ?></div>
                </div>
            </div>

            <?php
            if ($salesstr['slid'] > 0) {
                ?>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Amount <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <div class="input-group-text"><i class="fa fa-fw fa-money-bill-wave"></i></div>
                            </div>
                            <input type="number" min="0" step="0.01" name="slprice" id="slprice" class="form-control" value="<?php echo isset($salesstr['slprice']) ? $salesstr['slprice'] : ''; ?>" placeholder="Payment amount" required>
                        </div>
                    </div>
                    <div class="form-group col-md-8">
                        <label><?php echo myvalidate($LANG['g_transactionid']); ?></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <div class="input-group-text"><i class="fa fa-fw fa-receipt"></i></div>
                            </div>
                            <input type="text" name="slbatch" id="slbatch" class="form-control" value="<?php echo isset($salesstr['slbatch']) ? $salesstr['slbatch'] : ''; ?>" placeholder="Enter transaction id" readonly>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label for="selectgroup-pills">Change Status Only</label>
                        <div class="selectgroup selectgroup-pills">
                            <label class="selectgroup-item">
                                <input type="radio" name="slstatus" value="0" class="selectgroup-input"<?php echo myvalidate($slstatus_cek[0]); ?>>
                                <span class="selectgroup-button selectgroup-button-icon"><i class="fas fa-fw fa-question-circle"></i> Unpaid</span>
                            </label>
                            <label class="selectgroup-item">
                                <input type="radio" name="slstatus" value="1" class="selectgroup-input"<?php echo myvalidate($slstatus_cek[1]); ?>>
                                <span class="selectgroup-button selectgroup-button-icon"><i class="fas fa-fw fa-check-circle"></i> Paid</span>
                            </label>
                            <label class="selectgroup-item">
                                <input type="radio" name="slstatus" value="2" class="selectgroup-input"<?php echo myvalidate($slstatus_cek[2]); ?>>
                                <span class="selectgroup-button selectgroup-button-icon"><i class="fas fa-fw fa-user-circle"></i> OnHold</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label>Sales Note</label>
                        <input type="text" name="slnote" id="slnote" class="form-control" value="<?php echo isset($salesstr['slnote']) ? $salesstr['slnote'] : ''; ?>" placeholder="Sales note">
                    </div>
                </div>

                <?php
            }
            ?>

            <div id="resultGetFields1"></div>

            <div class="text-md-right">
                <?php
                if ($salesstr['slid'] < 1) {
                    ?>
                    <div class="float-left">
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" name="doapprove" value="1" class="custom-control-input" id="doapprove" checked="">
                                <label class="custom-control-label" for="doapprove">Approve this sales and process the commission (if any).</label>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
                <a href="javascript:;" class="btn btn-secondary" data-dismiss="modal"><i class="far fa-fw fa-times-circle"></i> Cancel</a>
                <button type="submit" name="submit" value="submit" id="submit" class="btn btn-primary">
                    <i class="fa fa-fw fa-plus-circle"></i> Submit
                </button>
                <input type="hidden" name="editId" value="<?php echo myvalidate($editId); ?>">
                <input type="hidden" name="dosubmit" value="1">
                <input type="hidden" name="dumbtoken" value="<?php echo myvalidate($_SESSION['dumbtoken']); ?>">
                <input type="hidden" name="mdlhashy" value="<?php echo myvalidate($mdlhashy); ?>">
            </div>

        </form>

    </div>

</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#slitid").trigger('blur');
    });
</script>