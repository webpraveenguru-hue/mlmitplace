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

if (isset($FORM['delId']) && $FORM['delId'] != "") {
    $hasdel = md5($FORM['delId'] . date("dH"));
    if ($FORM['hash'] == $hasdel) {

        $rowstr = get_iteminfo($FORM['delId']);
        $db->delete(DB_TBLPREFIX . '_items', array('itid' => $FORM['delId']));

        $_SESSION['dotoaster'] = "toastr.success('Record deleted successfully!', 'Success');";
    } else {
        $_SESSION['dotoaster'] = "toastr.error('Record deleted failed!', 'Error');";
    }

    header('location: ' . $_SESSION['redirto']);
    $_SESSION['redirto'] = '';
    exit;
}

$editId = intval($FORM['editId']);
$dupId = intval($FORM['dupId']);

if ($editId > 0 || $dupId > 0) {
    $thisId = ($editId > 0) ? $editId : $dupId;
    $rowstr = get_iteminfo($thisId);
    $ittokenarr = get_optionvals($rowstr['ittoken']);

    $itplanpricearr = unserialize($rowstr['itplanprice'] ?? '');
    $itplandircmlistarr = unserialize($rowstr['itplandircmlist'] ?? '');
    $itplancmlistarr = unserialize($rowstr['itplancmlist'] ?? '');

    if ($dupId > 0) {
        $itnamestr = '[duplicate] ' . $rowstr['itname'];
        $rowstr['itimage'] = '';
    } else {
        $itnamestr = $rowstr['itname'];
    }
    $_SESSION['redirto'] = redir_to($FORM['redir']);

    $itexpinvalarr = array('', '1m', '6m', '1y');
    $itexpinval_cek = radiobox_opt($itexpinvalarr, $rowstr['itexpinval']);

    // 0=disable, 1=enable for all, 2=enable for member only, 7=hidden, 8=archive, 9=waiting approval, 13=suspend
    $itstatusarr = array(0, 1, 7, 9, 13);
    $itstatus_cek = radiobox_opt($itstatusarr, $rowstr['itstatus']);

    // get vendor details
    $vendorarr = getmbrinfo($rowstr['itidmbr']);
}

if (isset($FORM['dosubmit']) && $FORM['dosubmit'] == '1') {
    extract($FORM);
    $editId = intval($editId);

    $itdatetm = date('Y-m-d H:i:s', time() + (3600 * $cfgrow['time_offset']));
    $itimage = imageupload('itimage_' . md5($_FILES['itimage']['name']), $_FILES['itimage'], $old_itimage);

    $itplanprice = serialize($itplanprice);
    $itplandircmlist = serialize($itplandircmlist);
    $itplancmlist = serialize($itplancmlist);

    $itdeliverarr = array();
    $itdeliverarr['itdlfile'] = $itdlfile;
    $itdeliverarr['itgetcnt'] = $itgetcnt;
    $itdeliverarr['iturlredir'] = $iturlredir;
    $itdeliverarr['itmailbody'] = $itmailbody;
    $itdeliverarr['itmailstatus'] = $itmailstatus;
    $itdeliver = base64_encode(serialize($itdeliverarr));

    $ittoken = $rowstr['ittoken'];
    $ittoken = put_optionvals($ittoken, 'buyerpoint', floatval($buyerpoint));
    $ittoken = put_optionvals($ittoken, 'itcashback', floatval($itcashback));

    $data = array(
        'itgrid' => $itgrid,
        'itsku' => $itsku,
        'itname' => $itname,
        'itdescr' => $itdescr,
        'itsalesnote' => $itsalesnote,
        'itimage' => $itimage,
        'itprice' => $itprice,
        'itplanprice' => $itplanprice,
        'itcmlist' => $itcmlist,
        'itplandircmlist' => $itplandircmlist,
        'itplancmlist' => $itplancmlist,
        'itpointlist' => $itpointlist,
        'itstatus' => $itstatus,
        'itexpinval' => $itexpinval,
        'itdeliver' => $itdeliver,
        'ittoken' => $ittoken,
        'itkeywords' => $itkeywords,
    );
    $redirto = $_SESSION['redirto'];
    $_SESSION['redirto'] = '';

    if ($editId > 0) {
        // if update product
        $condition = ' AND itid = "' . $editId . '" ';
    } else {
        // if new product exist, keep using old product
        $condition = ' AND itname LIKE "' . $itname . '" AND itname != "" ';
    }
    $sql = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_items WHERE 1 " . $condition . "");
    if (count($sql) > 0) {
        if ($editId > 0) {
            $update = $db->update(DB_TBLPREFIX . '_items', $data, array('itid' => $editId));
            if ($update) {
                $_SESSION['dotoaster'] = "toastr.success('Record updated successfully!', 'Success');";
            } else {
                $_SESSION['dotoaster'] = "toastr.warning('You did not change anything!', 'Info');";
            }
        } else {
            // do nothing
            $_SESSION['dotoaster'] = "toastr.warning('Record not added <strong>Item exist!</strong>', 'Warning');";
        }
    } else {

        // add product
        $data_add = array(
            'itdatetm' => $itdatetm,
        );
        $data = array_merge($data, $data_add);

        $insert = $db->insert(DB_TBLPREFIX . '_items', $data);

        if ($insert) {
            $_SESSION['dotoaster'] = "toastr.success('Record added successfully!', 'Success');";
        } else {
            $_SESSION['dotoaster'] = "toastr.error('Record not added <strong>Please try again!</strong>', 'Warning');";
        }
    }
    header('location: ' . $redirto);
    exit;
}

$groupcatlist = $itdlfilelist = $itgetcntlist = '<option value="">-</option>';

$row = $db->getAllRecords(DB_TBLPREFIX . '_groups', '*', " AND grtype = 'item'");
$grouprow = array();
foreach ($row as $value) {
    $grouprow = array_merge($grouprow, $value);
    $strsel = ($grouprow['grid'] == $rowstr['itgrid']) ? ' selected' : '';
    $groupcatlist .= "<option value='{$grouprow['grid']}'{$strsel}>{$grouprow['grtitle']}</option>";
}

$itdeliverarr = unserialize(base64_decode($rowstr['itdeliver'] ?? ''));

$row = $db->getAllRecords(DB_TBLPREFIX . '_pages', '*', " AND pgstatus = '1'");
$grouprow = array();
foreach ($row as $value) {
    $grouprow = array_merge($grouprow, $value);
    $strsel = ($grouprow['pgid'] == $itdeliverarr['itgetcnt']) ? ' selected' : '';
    $itgetcntlist .= "<option value='{$grouprow['pgid']}'{$strsel}>{$grouprow['pgtitle']}</option>";
}

$iturlredir = $itdeliverarr['iturlredir'];

$itmailstatusarr = array(0, 1);
$itmailstatus_cek = radiobox_opt($itmailstatusarr, $itdeliverarr['itmailstatus']);
$itmailbody = $itdeliverarr['itmailbody'];
?>

<div class="row">
    <div class="col-md-12">

        <?php
        if ($rowstr['itid'] > 1) {
            ?>
            <div class="mb-4">
                Item by <span class="badge badge-info"><?php echo myvalidate($vendorarr['username']); ?></span>
            </div>
            <?php
        }
        ?>

        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item text-center">
                <a class="nav-link active" id="itmopts-tab" data-toggle="tab" href="#itmopts" role="tab" aria-controls="itmopts" aria-selected="true"><i class="fa fa-fw fa-shopping-bag"></i><span class="d-none d-sm-block"> Options</span></a>
            </li>
            <?php
            if ($rowstr['itid'] > 1) {
                ?>
                <li class="nav-item text-center">
                    <a class="nav-link" id="cmisions-tab" data-toggle="tab" href="#cmisions" role="tab" aria-controls="cmisions" aria-selected="false"><i class="fa fa-fw fa-calculator"></i><span class="d-none d-sm-block"> Price and Commission</span></a>
                </li>
                <?php
            }
            ?>
            <li class="nav-item text-center">
                <a class="nav-link" id="ideliver-tab" data-toggle="tab" href="#ideliver" role="tab" aria-controls="ideliver" aria-selected="false"><i class="fa fa-fw fa-truck-loading"></i><span class="d-none d-sm-block"> Delivery Method</span></a>
            </li>
        </ul>

        <form method="post" action="doitem.php" enctype="multipart/form-data">
            <div class="tab-content" id="myTabContent">

                <div class="tab-pane fade show active" id="itmopts" role="tabpanel" aria-labelledby="itmopts-tab">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label><?php echo myvalidate($LANG['g_product']); ?> <span class="text-danger">*</span></label>
                            <input type="text" name="itname" id="itname" class="form-control" value="<?php echo isset($itnamestr) ? $itnamestr : ''; ?>" placeholder="Product name" required>
                        </div>
                        <div class="form-group col-md-8">
                            <label>Image</label>
                            <div class="input-group">
                                <input type="file" accept="image/*" name="itimage" id="itimage" class="form-control" value="<?php echo isset($rowstr['itimage']) ? $rowstr['itimage'] : DEFIMG_FILE; ?>">
                                <input type="hidden" name="old_itimage" value="<?php echo myvalidate($rowstr['itimage']); ?>">
                            </div>
                            <div class="form-text text-muted">The image must have a maximum size of 512Kb</div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-12">
                            <label><?php echo myvalidate($LANG['g_description']); ?></label>
                            <textarea class="form-control rowsize-md" name="itdescr" id="summernotemodal" placeholder="Product description"><?php echo isset($rowstr['itdescr']) ? $rowstr['itdescr'] : ''; ?></textarea>
                        </div>
                    </div>

                    <?php
                    if ($editId != 1 || ($editId == 1 && $rowstr['itid'] > 1)) {
                        $defitcmlist = ($thisId < 1) ? base64_decode($stctoken['itemcmlist'] ?? '') : '';
                        ?>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Default Price <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <div class="input-group-text"><i class="fa fa-fw fa-coins"></i></div>
                                    </div>
                                    <input type="number" min="-1" step="0.01" name="itprice" id="itprice" class="form-control" value="<?php echo isset($rowstr['itprice']) ? $rowstr['itprice'] : ''; ?>" placeholder="Product price" required>
                                </div>
                            </div>
                            <div class="form-group col-md-8">
                                <label>Default Network Commission</label>
                                <textarea class="form-control" name="itcmlist" id="itcmlist" placeholder="Network commission from sales, from lower to higher level, separated with comma"><?php echo isset($rowstr['itcmlist']) ? $rowstr['itcmlist'] : $defitcmlist; ?></textarea>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Cashback</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <div class="input-group-text"><i class="fa fa-fw fa-hand-holding-heart"></i></div>
                                </div>
                                <input type="number" min="-1" step="0.01" name="itcashback" id="itcashback" class="form-control" value="<?php echo isset($ittokenarr['itcashback']) ? $ittokenarr['itcashback'] : ''; ?>" placeholder="Comission to buyer">
                            </div>
                        </div>
                        <div class="form-group col-md-8">
                            <label><?php echo myvalidate($LANG['g_salesnote']); ?></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <div class="input-group-text"><i class="fa fa-fw fa-receipt"></i></div>
                                </div>
                                <input type="text" name="itsalesnote" id="itsalesnote" class="form-control" value="<?php echo isset($rowstr['itsalesnote']) ? $rowstr['itsalesnote'] : ''; ?>" placeholder="Noted when sales occurs">
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Buyer Point</label>
                            <input type="number" min="0" name="buyerpoint" id="buyerpoint" class="form-control" value="<?php echo myvalidate($ittokenarr['buyerpoint'] > 0) ? $ittokenarr['buyerpoint'] : 0; ?>" placeholder="Buyer sales point">
                        </div>
                        <div class="form-group col-md-8">
                            <label>Sales Point</label>
                            <textarea class="form-control" name="itpointlist" id="itpointlist" placeholder="Sales point for sponsor, from lower to higher level, separated with comma"><?php echo isset($rowstr['itpointlist']) ? $rowstr['itpointlist'] : ''; ?></textarea>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="selectgroup-pills">Status</label>
                            <div class="selectgroup selectgroup-pills">
                                <label class="selectgroup-item">
                                    <input type="radio" name="itstatus" value="0" class="selectgroup-input"<?php echo myvalidate($itstatus_cek[0]); ?>>
                                    <span class="selectgroup-button selectgroup-button-icon"><i class="fa fa-fw fa-times"></i> <?php echo myvalidate($itemstatus_array[0]); ?></span>
                                </label>
                                <label class="selectgroup-item">
                                    <input type="radio" name="itstatus" value="1" class="selectgroup-input"<?php echo myvalidate($itstatus_cek[1]); ?>>
                                    <span class="selectgroup-button selectgroup-button-icon"><i class="fa fa-fw fa-check"></i> <?php echo myvalidate($itemstatus_array[1]); ?></span>
                                </label>
                                <?php
                                if ($rowstr['itidmbr'] > 0) {
                                    ?>
                                    <label class="selectgroup-item">
                                        <input type="radio" name="itstatus" value="9" class="selectgroup-input"<?php echo myvalidate($itstatus_cek[3]); ?>>
                                        <span class="selectgroup-button selectgroup-button-icon"><i class="fa fa-fw fa-question"></i> <?php echo myvalidate($itemstatus_array[9]); ?></span>
                                    </label>
                                    <!--label class="selectgroup-item">
                                        <input type="radio" name="itstatus" value="7" class="selectgroup-input"<?php echo myvalidate($itstatus_cek[2]); ?>>
                                        <span class="selectgroup-button selectgroup-button-icon"><i class="fa fa-fw fa-user-secret"></i> <?php echo myvalidate($itemstatus_array[7]); ?></span>
                                    </label-->
                                    <label class="selectgroup-item">
                                        <input type="radio" name="itstatus" value="13" class="selectgroup-input"<?php echo myvalidate($itstatus_cek[4]); ?>>
                                        <span class="selectgroup-button selectgroup-button-icon"><i class="fa fa-fw fa-exclamation"></i> <?php echo myvalidate($itemstatus_array[13]); ?></span>
                                    </label>
                                    <?php
                                }
                                ?>
                            </div>
                        </div>
                        <div class="form-group col-md-8">
                            <label>Category</label>
                            <select name="itgrid" id="flgrid" class="form-control" data-height="100%">
                                <?php echo myvalidate($groupcatlist); ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-12">
                            <label>Keywords</label>
                            <textarea class="form-control" name="itkeywords" id="itkeywords" placeholder="Product keyword, separated with comma"><?php echo isset($rowstr['itkeywords']) ? $rowstr['itkeywords'] : ''; ?></textarea>
                        </div>
                    </div>

                </div>

                <div class="tab-pane fade show" id="cmisions" role="tabpanel" aria-labelledby="cmisions-tab">
                    <?php
                    foreach ($bpparr as $key => $value) {
                        $ppid = $value['ppid'];
                        $itplanprice = $itplanpricearr[$ppid];
                        $itplandircmlist = $itplandircmlistarr[$ppid];
                        $itplancmlist = $itplancmlistarr[$ppid];
                        ?>
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>Plan</label>
                                <div class="input-group">
                                    <h6 class="alert alert-info"><?php echo myvalidate($value['ppname']); ?></h6>
                                </div>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Price</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <div class="input-group-text"><i class="fa fa-fw fa-coins"></i></div>
                                    </div>
                                    <input type="number" min="0" step="0.01" name="itplanprice[<?php echo myvalidate($ppid); ?>]" id="itplanprice<?php echo myvalidate($ppid); ?>" class="form-control" value="<?php echo isset($itplanprice) ? $itplanprice : ''; ?>" placeholder="Product price for this plan">
                                </div>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Personal Commission</label>
                                <textarea class="form-control" name="itplandircmlist[<?php echo myvalidate($ppid); ?>]" id="itplandircmlist<?php echo myvalidate($ppid); ?>" placeholder="Personal commission from sales, paid to the referrer"><?php echo isset($itplandircmlist) ? $itplandircmlist : ''; ?></textarea>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Network Commission</label>
                                <textarea class="form-control" name="itplancmlist[<?php echo myvalidate($ppid); ?>]" id="itplancmlist<?php echo myvalidate($ppid); ?>" placeholder="Network commission from sales, paid to the sponsors, from lower to higher level, separated with comma"><?php echo isset($itplancmlist) ? $itplancmlist : ''; ?></textarea>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>

                <div class="tab-pane fade show" id="ideliver" role="tabpanel" aria-labelledby="ideliver-tab">
                    <div class="form-row">
                        <div class="form-group col-md-12">
                            <div class="alert alert-light text-small">Use the following options to configure how the product will be delivered after the order is completed. Leave the option as is or empty to ignore the delivery method.</div>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Access to the Digital Content</label>
                            <select name="itgetcnt" id="itgetcnt" class="form-control" data-height="100%">
                                <?php echo myvalidate($itgetcntlist); ?>
                            </select>
                            <div class="text-small text-muted">Add a access button in the member order list.</div>
                        </div>
                        <div class="form-group col-md-6">
                            <label>URL Redirection</label>
                            <input type="text" name="iturlredir" id="iturlredir" class="form-control" value="<?php echo isset($iturlredir) ? $iturlredir : ''; ?>" placeholder="https://...">
                            <div class="text-small text-muted">Add a button to access the URL in the member order list.</div>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Order Message</label>
                            <textarea class="form-control rowsize-md" name="itmailbody" id="itmailbody"><?php echo isset($itmailbody) ? $itmailbody : ''; ?></textarea>
                            <div class="text-small text-muted">Add a message inside the order email content.</div>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Message Status</label>
                            <div class="selectgroup selectgroup-pills">
                                <label class="selectgroup-item">
                                    <input type="radio" name="itmailstatus" value="0" class="selectgroup-input"<?php echo myvalidate($itmailstatus_cek[0]); ?>>
                                    <span class="selectgroup-button selectgroup-button-icon"><i class="fa fa-fw fa-times"></i> Disable</span>
                                </label>
                                <label class="selectgroup-item">
                                    <input type="radio" name="itmailstatus" value="1" class="selectgroup-input"<?php echo myvalidate($itmailstatus_cek[1]); ?>>
                                    <span class="selectgroup-button selectgroup-button-icon"><i class="fa fa-fw fa-check"></i> Enable</span>
                                </label>
                            </div>
                            <code class="font-weight-light">
                                <div class="text-small">Available Tags: <span class="text-muted">[[firstname]], [[fullname]], [[username]], [[email]], [[itemname]]</span></div>
                                <div class="text-small">Usage: <span class="text-muted">add a <strong>[[ordermessage]]</strong> tag to the New Item Order (mbr_order) message in the <a href='index.php?ntId=7&hal=notifylist'>Notification List</a> page.</span></div>
                            </code>
                        </div>
                    </div>
                </div>

                <div class="text-md-right">
                    <a href="javascript:;" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-fw fa-times"></i> Cancel</a>
                    <button type="submit" name="submit" value="submit" id="submit" class="btn btn-primary" onclick="return VerifyUploadFileSize('itimage', 'The image size must not exceed', 524288);">
                        <i class="fa fa-fw fa-check"></i> Submit
                    </button>
                    <input type="hidden" name="editId" value="<?php echo myvalidate($editId); ?>">
                    <input type="hidden" name="dosubmit" value="1">
                    <input type="hidden" name="mdlhasher" value="<?php echo myvalidate($mdlhasher); ?>">
                </div>

            </div>
        </form>

    </div>

</div>