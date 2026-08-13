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

$_SESSION['redirto'] = redir_to($FORM['redir']);

if (isset($FORM['delId']) and $FORM['delId'] != "") {
    $hasdel = md5($FORM['delId'] . date("dH"));
    if ($FORM['hash'] == $hasdel) {
        $db->delete(DB_TBLPREFIX . '_sales', array('slid' => $FORM['delId']));
        $_SESSION['dotoaster'] = "toastr.success('Record deleted successfully!', 'Success');";
    } else {
        $_SESSION['dotoaster'] = "toastr.error('Record deleted failed!', 'Error');";
    }

    header('location: ' . $_SESSION['redirto']);
    $_SESSION['redirto'] = '';
    exit;
}

$editId = intval($FORM['editId']);

if (isset($editId) and $editId != "") {
    $salesstr = get_salesinfo($editId);
    $_SESSION['redirto'] = redir_to($FORM['redir']);

    $slstatusarr = array(0, 1, 2, 3, 4);
    $slstatus_cek = radiobox_opt($slstatusarr, $salesstr['slstatus']);

    $itemstr = get_iteminfo($salesstr['slitid']);
    $sellerstr = getmbrinfo($itemstr['itidmbr']);
}

if (isset($FORM['dosubmit']) and $FORM['dosubmit'] == '1') {
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
        'slmbrid' => intval($slmbrid),
        'slmbrun' => getusernameid(intval($slmbrid), 'username'),
        'slprice' => $slprice,
        'slbatch' => $slbatch,
        'slrefid' => intval($slrefid),
        'slstatus' => intval($slstatus),
        'slnote' => $slnote,
        'sladminfo' => mystriptag($sladminfo),
    );

    $redirto = $_SESSION['redirto'];
    $_SESSION['redirto'] = '';

    if (isset($editId) and $editId > 0) {
        // if update sales history
        $condition = ' AND slid = "' . $editId . '" ';
    } else {
        // if new sales history exist, keep using old slbatch
        $condition = ' AND slbatch LIKE "' . $slbatch . '" ';
    }
    $sql = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_sales WHERE 1 " . $condition . "");
    if (count($sql) > 0) {
        if ($editId > 0) {
            $update = $db->update(DB_TBLPREFIX . '_sales', $data, array('slid' => $editId));
            if ($update) {
                $_SESSION['dotoaster'] = "toastr.success('Record updated successfully!', 'Success');";
            } else {
                $_SESSION['dotoaster'] = "toastr.warning('You did not change anything!', 'Info');";
            }
        } else {
            // do nothing
            $_SESSION['dotoaster'] = "toastr.warning('Record not added <strong>Sales history exist!</strong>', 'Warning');";
        }
    }
    header('location: ' . $redirto);
    exit;
}
?>

<div class="row">
    <div class="col-md-12">

        <p class="text-primary">Fields with <span class="text-danger">*</span> are mandatory!<br />
            <?php
            if ($salesstr['slid'] > 0) {
                ?>
                <span class="text-small text-muted">will be processes as is without additional changes in the transaction history and others.</span>
                <?php
            }
            if ($sellerstr['id'] > 0) {
                echo "<div class='text-info'>{$LANG['g_seller']} <span class='badge badge-info'>{$sellerstr['username']}</span></div>";
            }
            ?>
        </p>
        <form method="post" action="dosales.php">

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Product ID <span class="text-danger">*</span></label>
                    <input type="number" min="1" name="slitid" id="slitid" class="form-control" value="<?php echo isset($salesstr['slitid']) ? $salesstr['slitid'] : ''; ?>" placeholder="Enter product ID" onBlur="checkMember('id2itname', this.value, '1');" required>
                </div>
                <div class="form-group col-md-8">
                    <label>Name</label>
                    <div id="resultGetMbr1"><?php echo isset($itemname) ? "<span class='text-primary'><strong>{$itemname}</strong></span>" : '?'; ?></div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Member ID <span class="text-danger">*</span></label>
                    <input type="number" min="1" name="slmbrid" id="slmbrid" class="form-control" value="<?php echo isset($salesstr['slmbrid']) ? $salesstr['slmbrid'] : ''; ?>" placeholder="Enter Member ID" onBlur="checkMember('id2i', this.value, '2')" required>
                </div>
                <div class="form-group col-md-8">
                    <label><?php echo myvalidate($LANG['g_buyer']); ?></label>
                    <div id="resultGetMbr2"><?php echo isset($buyername) ? "<span class='text-primary'><strong>{$buyername}</strong></span>" : '?'; ?></div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Referrer ID</label>
                    <input type="number" min="0" name="slrefid" id="slrefid" class="form-control" value="<?php echo isset($salesstr['slrefid']) ? $salesstr['slrefid'] : ''; ?>" placeholder="Referrer id" onBlur="checkMember('id2i', this.value, '3')">
                </div>
                <div class="form-group col-md-8">
                    <label>Username</label>
                    <div id="resultGetMbr3"><?php echo isset($refname) ? "<span class='text-primary'><strong>{$refname}</strong></span>" : '?'; ?></div>
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
                            <input type="text" name="slbatch" id="slbatch" class="form-control" value="<?php echo isset($salesstr['slbatch']) ? $salesstr['slbatch'] : ''; ?>" placeholder="Enter transaction id">
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
                            <label class="selectgroup-item">
                                <input type="radio" name="slstatus" value="3" class="selectgroup-input"<?php echo myvalidate($slstatus_cek[3]); ?>>
                                <span class="selectgroup-button selectgroup-button-icon"><i class="fas fa-fw fa-receipt"></i> Expired</span>
                            </label>
                            <label class="selectgroup-item">
                                <input type="radio" name="slstatus" value="4" class="selectgroup-input"<?php echo myvalidate($slstatus_cek[4]); ?>>
                                <span class="selectgroup-button selectgroup-button-icon"><i class="fas fa-fw fa-times-circle"></i> Cancel</span>
                            </label>
                        </div>
                        <div class="text-small text-muted"><i class="fas fa-exclamation-triangle text-danger"></i> This option will not generate or update any commissions. For the manual payment with proof of payment, use <a href="index.php?hal=historylist&dohal=proof">Manual Approval</a> in the transaction history to manually approve the order.</div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label>Sales Note</label>
                        <input type="text" name="slnote" id="slnote" class="form-control" value="<?php echo isset($salesstr['slnote']) ? $salesstr['slnote'] : ''; ?>" placeholder="Sales note">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label>Admin Note</label>
                        <textarea class="form-control rowsize-md" name="sladminfo" id="sladminfo" placeholder="Sales description, available for administrator only"><?php echo isset($salesstr['sladminfo']) ? $salesstr['sladminfo'] : ''; ?></textarea>
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
                <input type="hidden" name="mdlhasher" value="<?php echo myvalidate($mdlhasher); ?>">
            </div>

        </form>

    </div>

</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#slitid").trigger('blur');
        $("#slmbrid").trigger('blur');
        $("#slrefid").trigger('blur');
    });
</script>