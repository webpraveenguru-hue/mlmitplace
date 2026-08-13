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

    $orirefid = getmbrinfo($unref, 'username')['id'];

    $addusrsarr = explode(chr(13), $addusrs);

    $newusradded = 0;
    require_once('../common/mailer.do.php');
    include_once('../common/sandbox.php');

    foreach ($addusrsarr as $key => $value) {

        // Firstname, Lastname, Username, Email, Password
        $userdatarr = explode(',', $value ?? '');
        $firstname = trim($userdatarr[0] ?? '');
        $lastname = trim($userdatarr[1] ?? '');
        $username = trim($userdatarr[2] ?? '');
        $email = trim($userdatarr[3] ?? '');
        $password = ($userdatarr[4] != '') ? $userdatarr[4] : substr(md5($username . $email), rand(0, 18), 8);
        $thisrefun = trim($userdatarr[5] ?? '');

        // reserved username
        $isunexist = is_unamereserved($username);

        // if new username exist, keep using old username
        $condition = ' AND username LIKE "' . $username . '" ';
        $sql = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_mbrs WHERE 1 " . $condition . "");
        if (count($sql) > 0 || $isunexist || $firstname == '' || $username == '' || $email == '') {
            // do nothing
            $_SESSION['dotoaster'] = "toastr.warning('Record not added <strong>One or more required data is empty or Username is exist!</strong>', 'Warning');";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['dotoaster'] = "toastr.error('Record not added <strong>Invalid input format!</strong>', 'Error');";
        } else {
            $in_date = date('Y-m-d H:i:s', time() + (3600 * $cfgrow['time_offset']));
            $log_ip = get_userip();
            $country = get_countrycode($log_ip);
            $password = getpasshash($password);
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
                // send welcome email
                $cntaddarr['fullname'] = $firstname . ' ' . $lastname;
                $cntaddarr['login_url'] = $cfgrow['site_url'] . "/" . MBRFOLDER_NAME;
                $cntaddarr['rawpassword'] = $password;
                delivermail('mbr_reg', $newmbrid, $cntaddarr);

                // register to membership
                if ($newusradded > 0) {
                    $entrytoidmbr = ($thisrefun == '' || $cfgtoken['isonetopref'] == '1') ? $entrytoidmbr : getmbrinfo($thisrefun, 'username')['id'];
                    $refnowarr = get_toprefnow($entrytoidmbr);
                    $unref = $refnowarr['username'];
                }
                $newmbrarr = getmbrinfo($newmbrid);
                $refstr = getmbrinfo($unref, 'username');
                $regtoppid = ($regtoppid > 0) ? $regtoppid : 1;
                $resultarr = regmbrplans($newmbrarr, $refstr['id'], $regtoppid);

                $newusradded++;
            }

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
            } else {
                $_SESSION['dotoaster'] = "toastr.error('Record not added <strong>Please try again!</strong>', 'Warning');";
            }
        }
    }

    if ($newusradded > 0) {
        $_SESSION['dotoaster'] = "toastr.success('{$newusradded} Record added successfully!', 'Success');";
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

        <form method="post" action="addusers.php")'>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Default Referrer Username</label>
                    <input type="text" name="unref" id="unref" class="form-control" value="<?php echo myvalidate($unrefdef); ?>" placeholder="Enter referrer username" onBlur="checkMember('un2i', this.value, '')">
                </div>
                <div class="form-group col-md-6">
                    <label>Referrer Name</label>
                    <div id="resultGetMbr">?</div>
                </div>
            </div>

            <div class="form-group">
                <label for="planinfo">Import Data <span class="text-danger">*</span></label>
                <textarea class="form-control rowsize-md" name="addusrs" id="addusrs" placeholder="Insert import data here, read instructions" required></textarea>
                <div class="text-small text-warning text-right" id="addusrsLineCount">Lines: -</div>
            </div>
            <div class="form-group">
                <label>Instructions:</label>
                <ul class="text-small">
                    <li>
                        Insert new user by follow the format below, new user for each line:
                        <div>
                            <code>
                                Firstname, Lastname, Username, Email, Password, referrer<br />
                                Fname1, Lname1, Username1, Email1, Password1, referrer1<br />
                                Demo, Test, userdemo, demo@demomo.test, mypass, <br />
                                Demo9, Test9, userdemo9, demo9@demomo.test,, referrer9<br />
                            </code>
                        </div>
                    </li>
                    <li>If firstname empty, username exist, or email is not valid, the system will skip it and continue to the next data.</li>
                    <li>If password empty, it will auto generate by system.</li>
                    <li>If referrer empty, it will use default referrer.</li>
                </ul>
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
                <button type="submit" name="submit" value="submit" id="submit" class="btn btn-primary" id="btn-one">
                    <i class="fa fa-fw fa-plus"></i> Submit
                </button>
                <input type="hidden" name="dosubmit" value="1">
                <input type="hidden" name="dumbtoken" value="<?php echo myvalidate($_SESSION['dumbtoken']); ?>">
                <input type="hidden" name="mdlhasher" value="<?php echo myvalidate($mdlhasher); ?>">
            </div>

        </form>

    </div>

</div>
<script>
    $(document).ready(function () {
        var maxLines = 25;
        $('#addusrs').on('input', function () {
            var textarea = $(this);
            var lines = textarea.val().split('\n');

            if (lines.length > maxLines) {
                textarea.val(lines.slice(0, maxLines).join('\n'));
            }
        });
        $('#addusrs').on('input propertychange', function () {
            var text = $(this).val();
            var lines = text.split('\n');
            var lineCount = lines.length;

            if (text === '') {
                lineCount = 0;
            }
            $('#addusrsLineCount').text('Lines: ' + lineCount + ' /' + maxLines);
        });

        $("#btn-one").click(function () {
            // disable button
            $(this).prop("disabled", true);
            // add spinner to button
            $(this).html('<span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>Processing...');
        });
    });
</script>