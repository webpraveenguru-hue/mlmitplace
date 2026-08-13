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
        $db->delete(DB_TBLPREFIX . '_epins', array('epid' => $FORM['delId']));
        $_SESSION['dotoaster'] = "toastr.success('Record deleted successfully!', 'Success');";
    } else {
        $_SESSION['dotoaster'] = "toastr.error('Record deleted failed!', 'Error');";
    }

    header('location: ' . $_SESSION['redirto']);
    $_SESSION['redirto'] = '';
    exit;
}


$istrydecopinarr = array(0, 1);
$istrydecopin_cek = radiobox_opt($istrydecopinarr, $_SESSION['istrydecopin']);

if (isset($FORM['dosubmit']) and $FORM['dosubmit'] == '1') {
    extract($FORM);

    $redirto = $_SESSION['redirto'];
    $_SESSION['redirto'] = '';
    $_SESSION['istrydecopin'] = $istrydecopin;

    $mbrstr = getmbrinfo($epusedun, 'username');
    $epusedid = intval($mbrstr['id']);

    $actid = ($eptype == 'item') ? $itid : $plid;

    require_once('../common/char.class.php');

    for ($i = 0; $i < $epintot; $i++) {
        $itcodesyntax = base64_decode($stctoken['itcodesyntax'] ?? '');
        $epcode = CharRand::customRand($itcodesyntax, CharRand::CHAR_ALPHA . CharRand::CHAR_NUMERIC);
        $epcode = ($epcode == '') ? substr(md5($actid . $cfgrow['datetimestr'] . $i), 5, 10) : $epcode;
        $data = array(
            'epusedid' => $epusedid,
            'epdate' => $cfgrow['datetimestr'],
            'epcode' => $epcode,
            'epvalue' => intval($actid),
            'eptype' => $eptype,
            'eptoken' => '',
            'epadminfo' => mystriptag($epadminfo),
        );
        $insert = $db->insert(DB_TBLPREFIX . '_epins', $data);

        // process decoding epin
        if ($actid == 'item' && $istrydecopin == '1' && $epusedid > 0) {
            $epinarr = get_epininfo($epcode, 'epcode', $mbrstr);
            $itemarr = get_iteminfo($epinarr['epvalue']);
            do_decodepinitem($mbrstr, $itemarr, $epinarr);
        }
    }

    if ($insert) {
        $_SESSION['dotoaster'] = "toastr.success('Record added successfully!', 'Success');";
    } else {
        $_SESSION['dotoaster'] = "toastr.error('Record not added <strong>Please try again!</strong>', 'Warning');";
    }
    header('location: ' . $redirto);
    exit;
}
?>

<div class="row">
    <div class="col-md-12">

        <?php
        if ($FORM['epdate'] == '') {
            $eptypeopt = ($stctoken['stcismbrdecodeplan'] == '1') ? "<option value='plan'>Membership</option>" : '';
            ?>
            <p class="text-primary">Fields with <span class="text-danger">*</span> are mandatory!</p>
            <form method="post" action="doepin.php" id="doepin">

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Code Type and ID <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <select name='eptype' class="custom-select text-monospace" onchange="doHideShow(document.getElementById('eptypeopt'), 'item', true, 'dHS_searchinput1');doHideShow(document.getElementById('eptypeopt'), 'item', true, 'dHS_eptypeoptitem');doHideShow(document.getElementById('eptypeopt'), 'plan', true, 'dHS_searchinput2');document.getElementById('resultGetMbr1').textContent = '';" id="eptypeopt">
                                <option value='item'>Product</option>
                                <?php echo myvalidate($eptypeopt); ?>
                            </select>
                            <span id="dHS_searchinput1">
                                <input type="text" name="itid" id="itid" class="form-control" value="" placeholder="Product Id or name" onBlur="checkMember('id2itname', this.value, '1', '-', 'Product not found!')" list="searchresult1" autocomplete="off">
                                <datalist class="result" id="searchresult1"></datalist>
                            </span>
                            <span id="dHS_searchinput2" style="display:none;">
                                <select name='plid' class="custom-select text-monospace" onBlur="checkMember('id2progname', this.value, '1', '-', 'Program not found!')">
                                    <?php
                                    foreach ($bpparr as $key => $value) {
                                        $planamount = ($value['regfee'] > 0) ? $bpprow['currencysym'] . $value['regfee'] : $LANG['g_free'];
                                        echo "<option value='{$key}'>{$value['ppname']} ({$planamount})</option>";
                                    }
                                    ?>
                                </select>
                            </span>
                        </div>
                    </div>
                    <div class="form-group col-md-6">
                        <label><i class="fas fa-level-down-alt text-muted"></i></label>
                        <div id="resultGetMbr1"></div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6" id="searchinput3">
                        <label>Beneficiary Username</label>
                        <input type="text" name="epusedun" id="epusedun" class="form-control" value="" placeholder="Enter member username" onBlur="checkMember('un2i', this.value, '2', '-', 'Data not found!', 'all', 'Available for ALL')" list="searchresult3" autocomplete="off">
                        <datalist class="result" id="searchresult3"></datalist>
                    </div>
                    <div class="form-group col-md-6">
                        <label><i class="fas fa-level-down-alt text-muted"></i></label>
                        <div id="resultGetMbr2"></div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Total Generate <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <div class="input-group-text"><i class="fa fa-fw fa-tags"></i></div>
                            </div>
                            <input type="number" min="0" step="1" name="epintot" id="epintot" class="form-control" value="<?php echo isset($rowstr['epintot']) ? $rowstr['epintot'] : '1'; ?>" required>
                        </div>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Note (Admin Only)</label>
                        <textarea class="form-control" name="epadminfo" id="epadminfo" placeholder="Notes for <?php echo myvalidate($LANG['g_epinlist']); ?>, available for Admin only"><?php echo isset($rowstr['epadminfo']) ? $rowstr['epadminfo'] : ''; ?></textarea>
                    </div>
                </div>

                <div class="form-row" id="dHS_eptypeoptitem">
                    <div class="form-group col-md-12">
                        <label for="selectgroup-pills">Allow the system to process the codes automatically for the existing beneficiary username</label>
                        <div class="selectgroup selectgroup-pills">
                            <label class="selectgroup-item">
                                <input type="radio" name="istrydecopin" value="0" class="selectgroup-input"<?php echo myvalidate($istrydecopin_cek[0]); ?>>
                                <span class="selectgroup-button selectgroup-button-icon"><i class="fas fa-fw fa-times-circle"></i> Disable</span>
                            </label>
                            <label class="selectgroup-item">
                                <input type="radio" name="istrydecopin" value="1" class="selectgroup-input"<?php echo myvalidate($istrydecopin_cek[1]); ?>>
                                <span class="selectgroup-button selectgroup-button-icon"><i class="fas fa-fw fa-check-circle"></i> Enable</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="text-md-right">
                    <a href="javascript:;" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-fw fa-times"></i> Cancel</a>
                    <button type="submit" name="submit" value="submit" id="submit" class="btn btn-primary">
                        <i class="fa fa-fw fa-check"></i> Submit
                    </button>
                    <input type="hidden" name="dosubmit" value="1">
                    <input type="hidden" name="mdlhasher" value="<?php echo myvalidate($mdlhasher); ?>">
                </div>

            </form>
            <?php
        } else {
            $epcodearr = [];
            $epdate = base64_decode($FORM['epdate'] ?? '');
            $condition = "AND epstatus = '1' AND epdate = '{$epdate}' AND epcode != ''";
            $epinarr = $db->getRecFrmQry("SELECT epcode, epusedid, username FROM " . DB_TBLPREFIX . "_epins LEFT JOIN " . DB_TBLPREFIX . "_mbrs ON epusedid = id WHERE 1 " . $condition . "");
            $epusedidstr = ($epinarr[0]['epusedid'] > 0) ? $epinarr[0]['username'] : $LANG['g_all'];
            foreach ($epinarr as $key => $value) {
                $epcodearr[] = $value['epcode'];
            }
            $epcodelist = implode(', ', $epcodearr);
            ?>
            <form>
                <div class="form-row">
                    <label><?php echo myvalidate($LANG['g_date'] . ': ' . formatdate($epdate, 'l') . ' - <strong>' . $epusedidstr . '</strong>') ?></label>
                    <textarea class="form-control rowsize-md" name="epcodelist" id="epcodelist" placeholder="List of available <?php echo myvalidate($LANG['g_epinlist']); ?>" readonly><?php echo myvalidate($epcodelist) ?></textarea>
                    <div class="text-md-right mt-2">
                        <a href="javascript:;" class="btn btn-warning" data-dismiss="modal"><i class="fa fa-fw fa-times"></i> Close</a>
                        <a href="javascript:;" class="btn btn-primary" type="button" onclick="copyInputText('epcodelist')"><i class="fa fa-fw fa-copy"></i> Copy</a>
                    </div>
                </div>
            </form>
            <?php
        }
        ?>

    </div>

</div>

<script type="text/javascript">
    $(document).ready(function () {
        do_livesearch('item', 'dHS_searchinput1', 'searchresult1');
        do_livesearch('mbr', 'searchinput3', 'searchresult3', 'username');
    });
</script>