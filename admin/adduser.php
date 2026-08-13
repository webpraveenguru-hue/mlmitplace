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

if ($FORM['doact'] == 'excsv') {
    $condition = base64_decode($FORM['sqlflter'] ?? '');
    $arrlabel = array('Upline', 'ID', 'Username', 'First Name', 'Last Name', 'Email', 'Phone', 'Address', '');
    $arrdata = array('idspr', 'id', 'username', 'firstname', 'lastname', 'email', 'phone', 'address');
    $arrdataext = [];
    dlcsv_mbrdata('mbrsdata', $arrlabel, $arrdata, $condition, $arrdataext);
    exit;
}

$_SESSION['redirto'] = redir_to($FORM['redir']);
$entrytoidmbr = 0;

$isdoapprmbrarr = array(0, 1);
$isdoapprmbr_cek = radiobox_opt($isdoapprmbrarr, $_SESSION['isdoapprmbr']);

if (isset($FORM['dosubmit']) && $FORM['dosubmit'] == '1') {
    extract($FORM);

    $_SESSION['isdoapprmbr'] = $isdoapprmbr;

    $redirto = $_SESSION['redirto'];
    $_SESSION['redirto'] = '';

    if (!dumbtoken($dumbtoken, 32)) {
        $_SESSION['show_msg'] = showalert('danger', $LANG['g_error'], $LANG['g_invalidtoken']);
        $redirval = $cfgrow['site_url'] . "/" . ADMFOLDER_NAME . "?res=erradmtoken";
        redirpageto($redirval);
        exit;
    }

    // reserved username
    $isunexist = is_unamereserved($FORM['username']);

    // if new username exist, keep using old username
    $condition = ' AND username LIKE "' . $FORM['username'] . '" ';
    $sql = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_mbrs WHERE 1 " . $condition . "");
    if (count($sql) > 0 || $isunexist) {
        // do nothing
        $_SESSION['dotoaster'] = "toastr.warning('Record not added <strong>Username exist!</strong>', 'Warning');";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['dotoaster'] = "toastr.error('Record not added <strong>Invalid input format!</strong>', 'Error');";
    } else {
        $in_date = date('Y-m-d H:i:s', time() + (3600 * $cfgrow['time_offset']));
        $log_ip = get_userip();
        $country = get_countrycode($log_ip);
        $password = ($password1 != '') ? getpasshash($password1) : $password;
        $data = array(
            'in_date' => $in_date,
            'firstname' => mystriptag($firstname),
            'lastname' => mystriptag($lastname),
            'username' => mystriptag($username, 'user'),
            'email' => mystriptag($email, 'email'),
            'password' => $password,
            'log_ip' => $log_ip,
            'country' => $country,
        );
        $insert = $db->insert(DB_TBLPREFIX . '_mbrs', $data);
        $newmbrid = $db->lastInsertId();

        if ($newmbrid > 0) {
            require_once('../common/mailer.do.php');

            // send welcome email
            $cntaddarr['fullname'] = $firstname . ' ' . $lastname;
            $cntaddarr['login_url'] = $cfgrow['site_url'] . "/" . MBRFOLDER_NAME;
            $cntaddarr['rawpassword'] = $password2;
            delivermail('mbr_reg', $newmbrid, $cntaddarr);

            // register to membership
            $newmbrstr = getmbrinfo($newmbrid);
            $refstr = getmbrinfo($unref, 'username');
            $regtoppid = ($regtoppid > 0) ? $regtoppid : 1;
            $resultarr = regmbrplans($newmbrstr, $refstr['id'], $regtoppid);
        }

        if ($insert) {
            // activate new registered members
            if ($resultarr['txid'] > 0 && $_SESSION['isdoapprmbr'] == '1') {
                $txid = $resultarr['txid'];
                $mpid = $resultarr['mpid'];

                $initbatch = 'IM';
                $txbatch = $refstr['mpid'] . $initbatch . $mpid . "-" . date("mdH") . $txid;
                $payamount = $bpparr[$regtoppid]['regfee'];

                include_once('../common/sandbox.php');
                $FORM['sb_type'] = 'payreg';
                $FORM['sb_label'] = 'admadds';
                $FORM['sb_txtokenarr'] = ['HOSTREF' => $refstr['mpid']];
                $txmpid = $txid . '-' . $mpid;
                doipnbox($txmpid, $payamount, 'admadds', $txbatch, '-HTTPREF-', 'continue', 0, '');
            }

            $_SESSION['dotoaster'] = "toastr.success('Record added successfully!', 'Success');";
        } else {
            $_SESSION['dotoaster'] = "toastr.error('Record not added <strong>Please try again!</strong>', 'Warning');";
        }
    }
    header('location: ' . $redirto);
    exit;
}

$refnowarr = get_toprefnow($entrytoidmbr);
$unrefdef = $refnowarr['username'];

// list available plans
$avalmbrplanarr = array();
foreach ($bpparr as $key => $value) {
    $avalmbrplanarr[$value['ppid']] = $value['ppname'] . ' - ' . $value['regfee'];
}
$avalmbrplan_menu = select_opt($avalmbrplanarr);
?>

<div class="row">
    <div class="col-md-12">

        <p class="text-primary">Fields with <span class="text-danger">*</span> are mandatory!</p>

        <form method="post" action="adduser.php" oninput='password1.setCustomValidity(password2.value != password1.value ? "Passwords do not match." : "")'>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Recommended Referrer Username</label>
                    <input type="text" name="unref" id="unref" class="form-control" value="<?php echo myvalidate($unrefdef); ?>" placeholder="Enter referrer username" onBlur="checkMember('un2i', this.value, '')">
                </div>
                <div class="form-group col-md-6">
                    <label>Referrer Name</label>
                    <div id="resultGetMbr">?</div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label><?php echo myvalidate($LANG['g_firstname']); ?> <span class="text-danger">*</span></label>
                    <input type="text" name="firstname" id="firstname" class="form-control" value="" minlength="3" placeholder="Enter member first name" required>
                </div>
                <div class="form-group col-md-6">
                    <label><?php echo myvalidate($LANG['g_lastname']); ?> <span class="text-danger">*</span></label>
                    <input type="text" name="lastname" id="lastname" class="form-control" value="" minlength="3" placeholder="Enter member last name" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Username <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text"><i class="fa fa-fw fa-user"></i></div>
                        </div>
                        <input type="text" name="username" id="username" class="form-control" value="" minlength="4" maxlength="16" placeholder="Enter member name" required>
                    </div>
                </div>
                <div class="form-group col-md-6">
                    <label>Email <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text"><i class="fa fa-fw fa-envelope"></i></div>
                        </div>
                        <input type="email" name="email" id="email" class="form-control" value="" minlength="8" placeholder="Enter member email" required>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <input type="hidden" name="password" value="<?php echo isset($rowstr['password']) ? $rowstr['password'] : ''; ?>">
                <div class="form-group col-md-6">
                    <label>Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" name="password2" id="password2" value="" placeholder="Enter member password" required>
                </div>
                <div class="form-group col-md-6">
                    <label>Password Confirm <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" name="password1" id="password1" value="" placeholder="Confirm member password">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Membership Plan</label>
                    <select name="regtoppid" id="regtoppid" class="form-control select1" required>
                        <?php echo myvalidate($avalmbrplan_menu); ?>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label for="selectgroup-pills">Directly Approve Member Account</label>
                    <div class="selectgroup selectgroup-pills">
                        <label class="selectgroup-item">
                            <input type="radio" name="isdoapprmbr" value="0" class="selectgroup-input"<?php echo myvalidate($isdoapprmbr_cek[0]); ?>>
                            <span class="selectgroup-button selectgroup-button-icon"><i class="fas fa-fw fa-times"></i> No</span>
                        </label>
                        <label class="selectgroup-item">
                            <input type="radio" name="isdoapprmbr" value="1" class="selectgroup-input"<?php echo myvalidate($isdoapprmbr_cek[1]); ?>>
                            <span class="selectgroup-button selectgroup-button-icon"><i class="fas fa-fw fa-check"></i> Yes</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="text-md-right">
                <a href="javascript:;" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-fw fa-times"></i> Cancel</a>
                <button type="submit" name="submit" value="submit" id="submit" class="btn btn-primary">
                    <i class="fa fa-fw fa-plus"></i> Submit
                </button>
                <input type="hidden" name="dosubmit" value="1">
                <input type="hidden" name="dumbtoken" value="<?php echo myvalidate($_SESSION['dumbtoken']); ?>">
                <input type="hidden" name="mdlhasher" value="<?php echo myvalidate($mdlhasher); ?>">
            </div>

        </form>

    </div>

</div>
