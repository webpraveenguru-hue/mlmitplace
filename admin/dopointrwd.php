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
        $db->delete(DB_TBLPREFIX . '_pointrwds', array('prid' => $FORM['delId']));
        $_SESSION['dotoaster'] = "toastr.success('Reward removed successfully!', 'Success');";
    } else {
        $_SESSION['dotoaster'] = "toastr.error('Reward removal failed!', 'Error');";
    }

    $redirto = $_SESSION['redirto'];
    $_SESSION['redirto'] = '';

    header('location: ' . $redirto);
    exit;
}

$editId = intval($FORM['editId']);
$prtype_menu = select_opt($prtypeopt_array);

if (isset($editId) && $editId > 0) {
    $rowstr = get_pointrwdinfo($editId);
    $_SESSION['redirto'] = redir_to($FORM['redir']);

    $prtype_menu = select_opt($prtypeopt_array, $rowstr['prtype']);

    $prstatusarr = array(0, 1);
    $prstatus_cek = radiobox_opt($prstatusarr, $rowstr['prstatus']);
}

if (isset($FORM['dosubmit']) and $FORM['dosubmit'] == '1') {
    extract($FORM);
    $editId = intval($editId);

    $redirto = $_SESSION['redirto'];
    $_SESSION['redirto'] = '';

    $primage = imageupload('primage_' . md5($prtype . $_FILES['primage']['name']), $_FILES['primage'], $old_primage);

    $prppidlist = (is_array($prppidlist)) ? '"' . implode('","', $prppidlist) . '"' : '';
    $prval = (is_array($prval[$prtype])) ? '"' . implode('","', $prval[$prtype]) . '"' : $prval[$prtype];

    $prtoken = '';

    $data = array(
        'prname' => mystriptag($prname),
        'prinfo' => mystriptag($prinfo),
        'primage' => $primage,
        'prtype' => $prtype,
        'prminpoints' => floatval($prminpoints),
        'prval' => $prval,
        'prppidlist' => $prppidlist,
        'prstatus' => intval($prstatus),
        'prtoken' => $prtoken,
        'pradminfo' => $pradminfo,
    );

    $condition = ' AND prid = "' . $editId . '" ';
    $sql = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_pointrwds WHERE 1 " . $condition . "");

    if (count($sql) > 0) {
        $data_add = array(
            'prupdate' => $cfgrow['datetimestr'],
        );
        $data = array_merge($data, $data_add);

        $update = $db->update(DB_TBLPREFIX . '_pointrwds', $data, array('prid' => $editId));
        if ($update) {
            $_SESSION['dotoaster'] = "toastr.success('Reward updated successfully!', 'Success');";
        } else {
            $_SESSION['dotoaster'] = "toastr.warning('{$LANG['g_nomajorchanges']}', 'Info');";
        }
    } else {
        $data_add = array(
            'prdatetm' => $cfgrow['datetimestr'],
            'prupdate' => $cfgrow['datetimestr'],
        );
        $data = array_merge($data, $data_add);

        $insert = $db->insert(DB_TBLPREFIX . '_pointrwds', $data);
        if ($insert) {
            $_SESSION['dotoaster'] = "toastr.success('Reward added successfully!', 'Success');";
        } else {
            $_SESSION['dotoaster'] = "toastr.error('Reward not added <strong>Please try again!</strong>', 'Warning');";
        }
    }
    header('location: ' . $redirto);
    exit;
}
?>

<?php
if (defined('ISDEMOMODE')) {
    ?>
    <div class="row">
        <div class="col-md-12">
            <p class="text-danger">Sorry, this feature is disabled in demo mode!</p>
        </div>
    </div>
    <?php
    die();
}
?>
<div class="row">
    <div class="col-md-12">

        <p class="text-primary">Fields with <span class="text-danger">*</span> are mandatory!</p>

        <form method="post" action="dopointrwd.php" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Title <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" name="prname" id="prname" class="form-control" value="<?php echo isset($rowstr['prname']) ? $rowstr['prname'] : ''; ?>" placeholder="Reward name" required>
                    </div>
                    <div class="form-text text-muted">The display name for the reward</div>
                </div>
                <div class="form-group col-md-6">
                    <label>Illustration</label>
                    <div class="input-group">
                        <input type="file" name="primage" id="primage" class="form-control" value="<?php echo isset($rowstr['primage']) ? $rowstr['primage'] : DEFIMG_FILE; ?>" accept=".jpg,.jpeg,.png">
                        <input type="hidden" name="old_primage" value="<?php echo myvalidate($rowstr['primage']); ?>">
                    </div>
                    <div class="form-text text-muted">The image must have a maximum size of 1Mb</div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label><?php echo myvalidate($LANG['g_description']); ?></label>
                    <textarea class="form-control" name="prinfo" id="prinfo" placeholder="Reward description"><?php echo isset($rowstr['prinfo']) ? $rowstr['prinfo'] : ''; ?></textarea>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Reward Type</label>
                    <select name="prtype" id="prtypeopt" class="form-control" data-height="100%">
                        <?php echo myvalidate($prtype_menu); ?>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label>Minimum Points <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" name="prminpoints" id="prminpoints" class="form-control" value="<?php echo isset($rowstr['prminpoints']) ? $rowstr['prminpoints'] : ''; ?>" placeholder="Reward name" required>
                    </div>
                </div>
            </div>
            <?php
            $prvalarr = str_getcsv($rowstr['prval'] ?? '');
            $prvalstr = $prvalarr[0];

            // list digital download
            $digdldopt = '';
            $condition = " AND flname != '' AND flstatus = '1'";
            $rowData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_files WHERE 1 " . $condition . "");
            if (count($rowData) > 0) {
                foreach ($rowData as $val) {
                    $isselect = (in_array($val['flid'], $prvalarr)) ? " selected" : "";
                    $digdldopt .= "<option value='{$val['flid']}'{$isselect}>{$val['flname']}";
                }
            }

            // list digital content
            $digcntopt = '';
            $condition = " AND pgtitle != '' AND pgstatus = '1'";
            $rowData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_pages WHERE 1 " . $condition . "");
            if (count($rowData) > 0) {
                foreach ($rowData as $val) {
                    $isselect = (in_array($val['pgid'], $prvalarr)) ? " selected" : "";
                    $digcntopt .= "<option value='{$val['pgid']}'{$isselect}>{$val['pgtitle']}";
                }
            }
            ?>
            <div class="form-row subcfg-option">
                <div class="form-group col-md-12 prtype-item" id="prtypefile">
                    <label>File name (<a href='index.php?hal=digifile'>manage</a>)</label>
                    <select name="prval[file]" id="prval-file" class="form-control" data-height="100%">
                        <?php echo myvalidate($digdldopt); ?>
                    </select>
                </div>
                <div class="form-group col-md-12 prtype-item" id="prtypepage">
                    <label>Title (<a href='index.php?hal=digicontent'>manage</a>)</label>
                    <select name="prval[page]" id="prval-page" class="form-control" data-height="100%">
                        <?php echo myvalidate($digcntopt); ?>
                    </select>
                </div>
                <div class="form-group col-md-12 prtype-item" id="prtypecash">
                    <label>Amount (add to member wallet)</label>
                    <input type="number" name="prval[cash]" id="prval-cash" class="form-control" value="<?php echo isset($prvalstr) ? $prvalstr : ''; ?>" placeholder="Exchange amount">
                </div>
                <div class="form-group col-md-12 prtype-item" id="prtypecustom">
                    <label>Custom rewards (manual process)</label>
                    <textarea class="form-control" name="prval[custom]" id="prval-custom" placeholder="Reward details, e.g. gift or vacation voucher, vip access, pricy gadget, dream car, etc."><?php echo isset($prvalstr) ? $prvalstr : ''; ?></textarea>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="selectgroup-pills">Status</label>
                    <div class="selectgroup selectgroup-pills">
                        <label class="selectgroup-item">
                            <input type="radio" name="prstatus" value="0" class="selectgroup-input"<?php echo myvalidate($prstatus_cek[0]); ?>>
                            <span class="selectgroup-button selectgroup-button-icon"><i class="fa fa-fw fa-times"></i> Disable</span>
                        </label>
                        <label class="selectgroup-item">
                            <input type="radio" name="prstatus" value="1" class="selectgroup-input"<?php echo myvalidate($prstatus_cek[1]); ?>>
                            <span class="selectgroup-button selectgroup-button-icon"><i class="fa fa-fw fa-check"></i> Enable</span>
                        </label>
                    </div>
                </div>
                <div class="form-group col-md-6">
                    <!--label>Available Payplan</label>
                    <select name="prppidlist[]" id="prppidlist" class="form-control" multiple="multiple" data-height="100%">
                    <?php echo myvalidate($prppidlist_menu); ?>
                    </select-->
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label>Admin Note</label>
                    <textarea class="form-control rowsize-sm" name="pradminfo" id="pradminfo"><?php echo isset($rowstr['pradminfo']) ? $rowstr['pradminfo'] : ''; ?></textarea>
                </div>
            </div>

            <div class="text-md-right">
                <a href="javascript:;" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-fw fa-times"></i> Cancel</a>
                <button type="submit" name="submit" value="submit" id="submit" class="btn btn-primary">
                    <i class="fa fa-fw fa-check"></i> Submit
                </button>
                <input type="hidden" name="editId" value="<?php echo myvalidate($editId); ?>">
                <input type="hidden" name="dosubmit" value="1">
                <input type="hidden" name="mdlhasher" value="<?php echo myvalidate($mdlhasher); ?>">
            </div>

        </form>

    </div>

</div>
<style>
    .prtype-item {
        display: none
    }

    .prtype-show {
        display: block
    }
</style>
<script type="text/javascript">
    var prtypenow = '<?php echo myvalidate($rowstr['prtype']); ?>';
    if (prtypenow !== '') {
        $('#prtype' + prtypenow).addClass('prtype-show'); // Show what is necessary
    }
    $('#prtypeopt').change(function () {
        $('.prtype-item').removeClass('prtype-show'); // hide all visible
        $('#prtype' + $(this).val()).addClass('prtype-show'); // Show what is necessary
    });
</script>