<?php
if (!defined('OK_LOADME') && $_REQUEST['do'] != 'backup') {
    die('o o p s !');
}

include_once('../common/init.loader.php');
if (defined('ISDEMOMODE')) {
    die();
}
include_once('../common/umver.php');

function do_backup() {
    $dat = date('Ymd_His');
    if (function_exists('gzencode')) {
        $cmp = "gz";
        $cnt_file = ".gz";
        $cnt_type = "application/x-gzip";
    } else {
        $cmp = $cnt_file = "";
        $cnt_type = "text/sql";
    }

    if (strpos($_SERVER['HTTP_USER_AGENT'] ?? '', 'MSIE') !== false) {
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
    } else {
        header('Cache-Control: must-revalidate');
        header('Pragma: no-cache');
    }
    header('Content-Type: application/octet-stream');
    header('Expires: 0');
    header("Content-Disposition: attachment; filename=" . DB_NAME . "_$dat.sql" . $cnt_file);

    gobackup($cmp, 1);

    exit();
}

function do_restore() {
    global $db, $ssysout;

    $_SESSION['errextmsg'] = '';
    $fnme = $_FILES['restoredb']['name'];
    $file = $_FILES['restoredb']['tmp_name'];

    if (strpos($fnme ?? '', ".sql.gz") !== false) {
        $f = gzfile($file);
    } else {
        $f = file($file);
    }

    $first_line = trim($f[0] ?? '');

    $f = join('', $f);
    if (!strlen($f ?? ''))
        $_SESSION['errextmsg'] = 'Can not restore database';
    if (!preg_match("/^# {$ssysout('SSYS_NAME')} /", $first_line))
        $_SESSION['errextmsg'] = 'Invalid backup file';

    $cntproc = 0;
    $ssysname = strtoupper($ssysout('SSYS_NAME'));
    foreach (preg_split('/;\n/', $f) as $sql) {
        $cntproc++;
        if (strlen($sql ?? '')) {
            $sql = str_replace("#{$ssysname}#", DB_TBLPREFIX, $sql ?? '');
            $db->doQueryStr($sql, 1);
        }
    }
}

// start --- database funcs

if ($_REQUEST['do'] == 'backup') {
    $seskey = verifylog_sess('admin');
    if ($seskey == '') {
        redirpageto('login.php?res=errses');
    } else {
        do_backup();
        $_SESSION['infomsg'] = 'Database backup completed';
        redirpageto('index.php?hal=updates');
    }
    exit;
}

if ($FORM['dorestore'] == '1' && $_FILES['restoredb']['size']) {
    do_restore();
    if (!$_SESSION['errextmsg'])
        $_SESSION['infomsg'] = 'Database restoration completed';
    redirpageto('index.php?hal=updates');
    exit;
}

if ($FORM['do'] == 'optmz') {
    $tables = $db->getRecFrmQry("SHOW TABLES FROM " . DB_NAME);
    foreach ($tables as $key => $table) {
        $table_name = $table['Tables_in_' . DB_NAME];
        $sql = "REPAIR TABLE $table_name";
        $sqlexec = $db->doQueryStr($sql, 1) or $_SESSION['errextmsg'] = 'Database repair failed!';
    }
    if (!$_SESSION['errextmsg'])
        $_SESSION['infomsg'] = 'Database optimization completed';

    redirpageto('index.php?hal=updates');
    exit;
}

if ($FORM['do'] == 'webstatus') {
    extract($FORM);
    $data = array(
        'site_status' => intval($site_status),
        'site_status_note' => base64_encode($site_status_note),
    );
    $update = $db->update(DB_TBLPREFIX . '_configs', $data, array('cfgid' => $didId));
    redirpageto('index.php?hal=updates');
    exit;
}
if ($cfgrow['site_status'] == '1') {
    $headsite_status = '<span class="badge badge-success">
                        <i class="fas fa-fw fa-globe"></i> Online
                    </span>';
    $btnwebstatus = '                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-fw fa-pause"></i> Offline
                </button>';
    $site_status = 0;
} else {
    $headsite_status = '<span class="badge badge-danger">
                        <i class="fas fa-fw fa-exclamation-triangle"></i> Offline
                    </span>';
    $btnwebstatus = '            <button type="submit" class="btn btn-success">
                    <i class="fas fa-fw fa-play"></i> Online
                </button>';
    $site_status = 1;
}

// debug option
if ($FORM['do'] == 'debugopt') {
    extract($FORM);
    $data = array(
        'testpayon' => intval($testpayon),
    );
    $update = $db->update(DB_TBLPREFIX . '_paygates', $data, array('paygid' => $didId));
    redirpageto('index.php?hal=updates');
    exit;
}
if ($payrow['testpayon'] == '1') {
    $headtestpay = '<span class="badge badge-danger">
                        <i class="fas fa-fw fa-vial"></i> Enable
                    </span>';
    $btntestpay = '                <button type="submit" class="btn btn-info">
                    <i class="fas fa-fw fa-satellite-dish"></i> Disable
                </button>';
    $testpayon = 0;
} else {
    $headtestpay = '<span class="badge badge-secondary">
                        <i class="fas fa-fw fa-satellite-dish"></i> Disable
                    </span>';
    $btntestpay = '            <button type="submit" class="btn btn-warning">
                    <i class="fas fa-fw fa-vial"></i> Enable
                </button>';
    $testpayon = 1;
}

if ($FORM['dbtest'] == 'truncated' && $payrow['testpayon'] == 1 && !defined('ISDEMOMODE')) {
    $db->doQueryStr("TRUNCATE " . DB_TBLPREFIX . "_transactions");
    $db->doQueryStr("TRUNCATE " . DB_TBLPREFIX . "_points");
    $db->doQueryStr("TRUNCATE " . DB_TBLPREFIX . "_mbrs");
    $db->doQueryStr("TRUNCATE " . DB_TBLPREFIX . "_mbrplans");
    $db->doQueryStr("TRUNCATE " . DB_TBLPREFIX . "_epins");

    //skip err when table is not exist
    $db->doQueryStr("TRUNCATE " . DB_TBLPREFIX . "_sales", 1);

    $db->doQueryStr("DELETE FROM " . DB_TBLPREFIX . "_paygates WHERE 1 AND pgidmbr > '0'");
    $db->doQueryStr("ALTER TABLE " . DB_TBLPREFIX . "_paygates AUTO_INCREMENT = 2");

    redirpageto('index.php?hal=dashboard');
    exit;
}

// ---

$licdm = $cfgtoken['licdm'];
if (isset($FORM['doblkey']) && $FORM['doblkey'] == '1') {
    extract($FORM);
    if (strlen($myblkey ?? '') > 8) {
        $nowver = ($cfgrow['softversion'] != '') ? $cfgrow['softversion'] : $umbasever;
        $baseArr = array('lickey' => $cfgrow['lickey'], 'myver' => base64_encode($nowver ?? ''), 'domain' => base64_encode($_SERVER['HT' . "TP_H" . 'OST'] ?? ''), 'ccid' => $ccid, 'lictype' => $lictype);
        $arrdata = array_merge($baseArr, array('licpk' => $myblkey, 'do' => 'plkey'));
        $arrResponse = do_postarrdata($arrdata);
        if ($arrResponse['isvalid'] != 1) {
            $_SESSION['errextmsg'] = $arrResponse['errmsg'];
        } else {
            $newcfgtoken = $cfgrow['cfgtoken'];
            $newcfgtoken = put_optionvals($newcfgtoken, 'licdm', $arrResponse['licdm']);
            $newcfgtoken = put_optionvals($newcfgtoken, 'lictype', $arrResponse['lictype']);
            $newcfgtoken = put_optionvals($newcfgtoken, 'licpk', $arrResponse['licpk']);
            $data = array(
                'cfgtoken' => $newcfgtoken,
            );
            $update = $db->update(DB_TBLPREFIX . '_configs', $data, array('cfgid' => '1'));
        }
    } else {
        $_SESSION['errextmsg'] = 'Invalid license!';
    }
    redirpageto('index.php?hal=updates');
    exit;
}

$dbakint_menu = '';
$dbakintarr = array('0' => 'Disable', '1w' => 'Weekly', '1m' => 'Monthly');
foreach ($dbakintarr as $key => $value) {
    $isselected = ($key == $cfgtoken['dbakint']) ? " selected" : '';
    $dbakint_menu .= "<option value='{$key}'{$isselected}>{$value}";
}

$newcfgtoken = $cfgrow['cfgtoken'];
if (isset($FORM['ismoreopts']) && $FORM['ismoreopts'] == '1') {
    extract($FORM);

    if ($cfgrow['admin_user'] == $subadmuser) {
        $subadmuser = '';
        $subadmis = '';
    }
    $subadmuser = base64_encode($subadmuser ?? '');
    $subadmpass = base64_encode($subadmpass ?? '');
    $newcfgtoken = put_optionvals($newcfgtoken, 'subadmuser', $subadmuser);
    $newcfgtoken = put_optionvals($newcfgtoken, 'subadmpass', $subadmpass);
    $newcfgtoken = put_optionvals($newcfgtoken, 'subadmis', $subadmis);
    $dbakint = trim($dbakint ?? '');
    $dbakeml = base64_encode(trim($dbakeml ?? ''));
    $newcfgtoken = put_optionvals($newcfgtoken, 'dbakint', $dbakint);
    $newcfgtoken = put_optionvals($newcfgtoken, 'dbakeml', $dbakeml);
}

if (isset($FORM['isintermopt']) && $FORM['isintermopt'] == '1') {
    extract($FORM);
    $newcfgtoken = put_optionvals($newcfgtoken, 'themeclr', $themeclr);
    $newcfgtoken = put_optionvals($newcfgtoken, 'istoastactvty', $istoastactvty);
    $_SESSION['notifytoaststatus'] = ($istoastactvty == '1') ? 'ON' : 'Off';
}

if (isset($FORM['docfgtoken']) && $FORM['docfgtoken'] == '1') {
    extract($FORM);

    $data = array(
        'cfgtoken' => $newcfgtoken,
    );
    $update = $db->update(DB_TBLPREFIX . '_configs', $data, array('cfgid' => '1'));

    redirpageto('index.php?hal=updates');
    exit;
}

$cfgtokenarr = get_optionvals($cfgrow['cfgtoken']);
$subadmuser = base64_decode($cfgtokenarr['subadmuser'] ?? '');
$subadmpass = base64_decode($cfgtokenarr['subadmpass'] ?? '');
$subadmis_cek = checkbox_opt($cfgtokenarr['subadmis']);

// start --- the color options
$themeclr_cek = radiobox_opt($colortheme_array, $cfgtokenarr['themeclr'], 1);
$themeclr_opt = '';
foreach ($colortheme_array as $key => $val) {
    $ico_color = ($key != $cfgtokenarr['themeclr']) ? ' style="color:#' . $val . '"' : '';
    $themeclr_opt .= <<<INI_HTML
<label class="selectgroup-item">
    <input type="radio" name="themeclr" value="{$key}" class="selectgroup-input"{$themeclr_cek[$val]}>
        <span class="selectgroup-button selectgroup-button-icon" data-toggle="tooltip" title="#{$val}"><i class="fas fa-paint-brush"{$ico_color}></i> #{$val}</span>
</label>
INI_HTML;
}
// end

$istoastactvty_cek = checkbox_opt($cfgtokenarr['istoastactvty']);
if (isset($FORM['dosubmit']) && $FORM['dosubmit'] == '1') {
    extract($FORM);
    $baseArr = ($isreuse == 1) ? array('myruname' => $myruname, 'addlic' => '1') : array('rfname' => $rfname, 'rlname' => $rlname, 'remail' => $remail, 'runame' => $runame);
    $arrdata = array_merge($baseArr, array('lickey' => $cfgrow['lickey'], 'do' => 'reg'));
    $arrResponse = do_postarrdata($arrdata);
    if ($arrResponse['isvalid'] != 1) {
        $_SESSION['errmsg'] = $arrResponse['errmsg'];
    } else {
        $envacc = ($arrResponse['username']) ? $arrResponse['username'] : $cfgrow['envacc'];
        $lichash = $arrResponse['lichash'];
        $data = array('envacc' => $envacc, 'licstatus' => $arrResponse['licstatus'], 'lichash' => $lichash);
        $update = $db->update(DB_TBLPREFIX . '_configs', $data, array('cfgid' => '1'));
    }
    redirpageto('index.php?hal=updates');
    exit;
}

$updateinfostr = ($cfgrow['softversion'] == $umbasever) ? "<span class='text-muted float-right'>v{$umbasever}</span>" : "<span class='text-warning float-right'>v{$umbasever}</span>";
if ($umisverup == 1) {
    $updateinfostr = ($cfgtoken['cnvnum'] > $cfgrow['softversion']) ? '<span id="newverstr" class="badge badge-success">Version ' . $cfgtoken['cnvnum'] . ' is available!</span>' : '<span id="newverstr" class="badge badge-light">You are using the latest version!</span>';
}
$displayedversion = ($umbasever > $cfgrow['softversion']) ? $umbasever : $cfgrow['softversion'];

$extmsgstr = ($_SESSION['errextmsg']) ? showalert('danger', 'Error', $_SESSION['errextmsg']) : '';
$_SESSION['errextmsg'] = '';

$infomsgstr = ($_SESSION['infomsg']) ? showalert('success', 'Info', $_SESSION['infomsg']) : '';
$_SESSION['infomsg'] = '';

$errmsg = $_SESSION['errmsg'];
$errmsgstr = ($errmsg) ? showalert('danger', 'Error', $errmsg) : showalert('warning', 'Optional', "You can also register your license manually from the <a href='{$ssysout('SSYS_URL')}/join' target='_blank' data-toggle='tooltip' title='Register to {$ssysout('SSYS_AUTHOR')}'>{$ssysout('SSYS_AUTHOR')}</a> site.");
$_SESSION['errmsg'] = '';

$lickeystr = base64_decode($cfgrow['lickey'] ?? '');
$lickeyhidestr = trim(substr($lickeystr ?? '', 5, 8) . '...', '-');

$admin_content = <<<INI_HTML
<div class="section-header">
    <h1><i class="fa fa-fw fa-briefcase-medical text-success"></i> {$LANG['a_updates']}</h1>
</div>
INI_HTML;
echo myvalidate($admin_content);

$lictypestr = base64_decode($cfgtoken['licstr'] ?? '');
$licbyrstr = base64_decode($cfgtoken['licbyr'] ?? '');
$liksrc = base64_decode($cfgtoken['liksrc'] ?? '');
$islicplus = get_inarrmind();
?>

<div class="row">
    <div class="col-md-6 col-sm-12">
        <?php
        if (!$islicplus) {
            ?>
            <div class="card">
                <div class="card-header">
                    <h4>License Upgrade</h4>
                </div>
                <div class="card-body">
                    You are currently using a Regular license. Please consider upgrading your license to enjoy more features.<br /><br /><span class="badge badge-danger"><i class="fas fa-bullhorn fa-fw"></i> Limited Time Offer!</span>
                    <blockquote class="text-info mt-4">Upgrade today to get a discount and free unlimited updates.</blockquote>
                </div>
                <div class="card-footer bg-whitesmoke text-md-right">
                    <button class="btn btn-primary" onclick="window.open('<?php echo myvalidate($ssysout('SSYS_URL')); ?>/docs/unimatrix?todo=upgrade', '_blank'); return false;">
                        Upgrade Instructions <i class="fas fa-fw fa-external-link-alt"></i>
                    </button>
                </div>
            </div>
            <?php
        } else {
            ?>
            <div class="card">
                <div class="card-header">
                    <h4>Installation Upgrade</h4>
                </div>
                <div class="card-body">
                    It appears you are using an Extended license, while the installation is using a Regular system. To enjoy additional features, please download and install a new system using the Extended license.
                    <blockquote class="text-info mt-4">If you have any questions or need assistance, please contact us.</blockquote>
                </div>
                <div class="card-footer bg-whitesmoke text-md-right">
                    <button class="btn btn-primary" onclick="window.open('<?php echo myvalidate($ssysout('SSYS_URL')); ?>/docs/unimatrix', '_blank'); return false;">
                        Download <i class="fas fa-fw fa-external-link-alt"></i>
                    </button>
                </div>
            </div>
            <?php
        }
        ?>
        <div class="card">
            <form method="post" action="index.php" id="webstatusform">
                <input type="hidden" name="hal" value="updates">
                <input type="hidden" name="do" value="webstatus">
                <input type="hidden" name="site_status" value="<?php echo myvalidate($site_status); ?>">
                <div class="card-header">
                    <h4>Website Status</h4>
                    <div class="card-header-action">
                        <?php echo myvalidate($headsite_status); ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Offline Message</label>
                        <textarea class="form-control rowsize-md" name="site_status_note" id="summernotemini" placeholder="Offline Message"><?php echo isset($cfgrow['site_status_note']) ? base64_decode($cfgrow['site_status_note'] ?? '') : ''; ?></textarea>
                    </div>
                </div>
                <div class="card-footer bg-whitesmoke text-md-right">
                    <?php echo myvalidate($btnwebstatus); ?>
                </div>
            </form>
        </div>
    </div>
    <div class="col-md-6 col-sm-12">
        <div class="card">
            <form method="post" action="index.php" id="debugoptform">
                <input type="hidden" name="hal" value="updates">
                <input type="hidden" name="do" value="debugopt">
                <input type="hidden" name="testpayon" value="<?php echo myvalidate($testpayon); ?>">
                <div class="card-header">
                    <h4>Debug Mode</h4>
                    <div class="card-header-action">
                        <?php echo myvalidate($headtestpay); ?>
                    </div>
                </div>
                <div class="card-body">
                    After installation and configuration, you may need to test the registration and payment flow to make sure the system has been configured properly.<br /><br />To do this, you can enable Debug Mode to simulate these processes without the need to do real payments. This method will use the System Test option on the Payment Options page.
                </div>
                <div class="card-footer bg-whitesmoke text-md-right">
                    <?php echo myvalidate($btntestpay); ?>
                </div>
            </form>
        </div>
        <?php
        if ($payrow['testpayon'] == 1) {
            ?>
            <div class="card card-danger">
                <div class="card-header">
                    <h4>Reset Database</h4>
                </div>
                <div class="card-body">
                    Use the button below to reset or purge your current test records. <span class="text-danger">Important!</span> Back up your database before performing this process.
                </div>
                <div class="card-footer bg-whitesmoke text-md-right">
                    <a href="javascript:;" data-href="index.php?hal=updates&dbtest=truncated" class="btn btn-warning bootboxconfirm mb-2" data-poptitle="Purge Test Records" data-popmsg="<p>Are you sure want to purge the member and transaction records?</p><p><span class='badge badge-danger'><i class='fa fa-exclamation-triangle'></i> This action cannot be undone!</span></p>" data-toggle="tooltip" title="Purge Test Records"><i class="fa fa-fw fa-users"></i><i class="fa fa-fw fa-arrow-right"></i><i class="far fa-fw fa-trash-alt"></i> Purge Test Records</a>
                </div>
            </div>
            <?php
        }

        $verlabel = ($umverttel != '') ? " <span class='badge badge-light'>{$umverttel}</span>" : '';
        ?>
    </div>
</div>

<div class="col-12">
    <?php echo myvalidate($infomsgstr); ?>
    <?php echo myvalidate($extmsgstr); ?>
</div>

<script>
    function domyLK() {
        var x = document.getElementById("myLK");
        if (x.innerHTML === "<?php echo myvalidate($lickeyhidestr); ?>") {
            x.innerHTML = "<?php echo myvalidate($lickeystr); ?>";
        } else {
            x.innerHTML = "<?php echo myvalidate($lickeyhidestr); ?>";
        }
    }
</script>

<div class="row">
    <div class="col-md-6 col-sm-12">
        <div class="card">
            <div class="card-header">
                <h4>License Info</h4>
            </div>
            <div class="card-body">
                <span class='text-small text-muted'>License Key</span>
                <h6 class="summary">
                    <div id="myLK" onclick="domyLK();" style="cursor: pointer;"><?php echo myvalidate($lickeyhidestr); ?></div>
                </h6>
                <span class='text-small text-muted'>Version</span>
                <h6 class="summary"><?php echo myvalidate($displayedversion . $verlabel); ?> <span class="badge badge-light"><?php echo myvalidate($lictypestr); ?></span></h6>
                <span class='text-small text-muted'>Installation Date</span>
                <h6 class="summary"><?php echo formatdate($cfgrow['installdate']); ?></h6>
                <span class='text-small text-muted'>Have a Question?</span>
                <h6 class="summary text-muted">Please feel free to ask <a href="https://drect.link/FUzuV" target="_blank">here</a>.</h6>
                <span class='text-small text-muted'>Need additional features or custom programming services?</span>
                <h6 class="summary text-muted">Please submit your request <a href="https://drect.link/iZOZu" target="_blank">here</a>.</h6>
                <?php
                if ($_SESSION['isunsubadm'] == '') {
                    ?>
                    <hr>
                    <span>
                        <a class="btn btn-sm btn-secondary" data-toggle="collapse" href="#collapseSvrInfo" role="button" aria-expanded="false" aria-controls="collapseSvrInfo">
                            Server Overview
                        </a>
                    </span>
                    <div class="collapse mt-2" id="collapseSvrInfo">
                        <?php
                        include('../common/reqlist.php');
                        echo myvalidate("<span class='text-small text-muted'>{$showreg_server}</span>");
                        ?>
                    </div>
                    <?php
                }
                ?>
            </div>
            <?php
            if ($umisverin == 1 && $isfolink == 1) {
                ?>
                <div class="card-footer bg-whitesmoke">
                    <?php echo myvalidate($updateinfostr); ?>
                </div>
                <?php
            }
            ?>
        </div>

        <div class="card">
            <form method="post" action="index.php" id="updform">
                <input type="hidden" name="hal" value="updates">

                <div class="card-header">
                    <h4>Optional: Branding Removal</h4>
                </div>

                <?php
                if ($cfgrow['_isnocredit']) {
                    ?>
                    <div class="card-body">
                        <h4 class="section-title mt-2">Sub-admin</h4>
                        <p class="section-lead text-muted text-small">
                            An additional administrator account with exceptional access to the site settings and maintenance pages.
                        </p>
                        <div class="form-group row">
                            <label for="subadmuser" class="col-sm-3 col-form-label">Username</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" name="subadmuser" id="subadmuser" placeholder="Username" value="<?php echo myvalidate($subadmuser); ?>" autocomplete="off">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="subadmpass" class="col-sm-3 col-form-label">Password</label>
                            <div class="col-sm-9">
                                <input type="password" class="form-control" name="subadmpass" id="subadmpass" placeholder="Password" autocomplete="off" value="<?php echo myvalidate($subadmpass); ?>">
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-sm-3"></div>
                            <div class="col-sm-9">
                                <div class="custom-control custom-checkbox">
                                    <input name="subadmis" value="1" type="checkbox" class="custom-control-input" id="subadmis"<?php echo myvalidate($subadmis_cek); ?>>
                                    <label class="custom-control-label" for="subadmis">Enable</label>
                                </div>
                            </div>
                        </div>

                        <h4 class="section-title mt-2">Auto-backup</h4>
                        <p class="section-lead text-muted text-small">
                            Automatically backup the database on an interval basis and send as attached to the defined email address.
                        </p>
                        <div class="form-group">
                            <div class="input-group">
                                <select class="custom-select" name="dbakint">
                                    <?php echo myvalidate($dbakint_menu); ?>
                                </select>
                                <input type="text" class="form-control" name="dbakeml" value="<?php echo base64_decode($cfgtoken['dbakeml'] ?? ''); ?>" placeholder="Email address">
                            </div>
                            <div class="form-text text-muted">Latest backup: <?php echo base64_decode($cfgtoken['dbakdate'] ?? ''); ?></div>
                        </div>

                    </div>
                    <div class="card-footer bg-whitesmoke text-md-right">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-fw fa-check"></i> Submit
                        </button>
                    </div>
                    <input type="hidden" name="ismoreopts" value="1">
                    <input type="hidden" name="docfgtoken" value="1">

                    <?php
                } else {
                    ?>

                    <div class="card-body">

                        <p class='text-muted'>If you do not have it, you can order the license <a href='<?php echo myvalidate($ssysout('SSYS_URL'));
                    ?>/order?um-pageup' target='_blank'>here</a>.</p>

                        <div class="form-group">
                            <input type="text" name="myblkey" class="form-control" id="myblkey" placeholder="Enter your branding removal license key">
                        </div>
                    </div>
                    <div class="card-footer bg-whitesmoke text-md-right">
                        <button type="submit" class="btn btn-danger" data-toggle="tooltip" title="Apply My License">
                            <i class="fas fa-fw fa-user-secret"></i> Submit
                        </button >
                    </div>
                    <input type="hidden" name="doblkey" value="1">

                    <?php
                }
                ?>
            </form>
        </div>
    </div>

    <div class="col-md-6 col-sm-12">
        <div class="card">
            <form method="post" action="index.php" id="updform">
                <input type="hidden" name="hal" value="updates">

                <div class="card-header">
                    <h4>License Registration</h4>
                </div>

                <?php
                if ($cfgrow['lichash'] && $cfgrow['licstatus'] > 0) {
                    $loginlinkstr = ($cfgrow['envacc'] != '') ? "{$ssysout('SSYS_URL')}/id/{$cfgrow['envacc']}/client" : "{$ssysout('SSYS_URL')}/client";
                    ?>

                    <div class="card-body">
                        <p>Your license has been registered by <Strong><?php echo myvalidate($cfgrow['envacc']); ?></Strong></p>
                        <?php
                        $days_ago = date('Y-m-d', strtotime('-25 days', strtotime($cfgrow['datetimestr'])));
                        if (($days_ago <= $cfgrow['installdate'] || $payrow['testpayon'] == 1) && $liksrc == 'codecanyon') {
                            ?>
                            <p>If you like this script, <strong>please <a href='https://codecanyon.net/downloads' target='_blank'>rate 5 Stars</a></strong> and get access to the latest version or minor updates directly from the <a href='<?php echo myvalidate($ssysout('SSYS_URL')); ?>/index.php?a=client&b=purchased' target='_blank'><?php echo myvalidate($ssysout('SSYS_NAME')); ?></a> website.</p>
                            <?php
                        }
                        ?>
                        <h6>Thank you for your business!</h6>
                    </div>
                    <div class="card-footer bg-whitesmoke text-md-right">
                        <button type="button" class="btn btn-primary" onclick="window.open('<?php echo myvalidate($loginlinkstr); ?>', '_blank')">
                            <?php echo myvalidate($ssysout('SSYS_AUTHOR')); ?> Login
                        </button>

                    </div>

                    <?php
                } else {
                    ?>

                    <div class="card-body">
                        <div>Register your license for free and get more benefits!</div>
                        <ul>
                            <li>Login to the <?php echo myvalidate($ssysout('SSYS_NAME')); ?> member only area.</li>
                            <li>Access the pre-release of latest version.</li>
                            <li>Enable additional features.</li>
                            <li>and more...</li>
                        </ul>

                        <?php echo myvalidate($errmsgstr); ?>

                        <div id='newregacc'>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="rfname"><?php echo myvalidate($LANG['g_firstname']); ?></label>
                                    <input type="text" name="rfname" class="form-control" id="rfname" placeholder="First name">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="rlname"><?php echo myvalidate($LANG['g_lastname']); ?></label>
                                    <input type="text" name="rlname" class="form-control" id="rlname" placeholder="Last name">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="runame"><?php echo myvalidate($ssysout('SSYS_AUTHOR')); ?> Username</label>
                                <input type="text" name="runame" class="form-control" id="runame" placeholder="Choose your <?php echo myvalidate($ssysout('SSYS_AUTHOR')); ?> username" value="<?php echo myvalidate($licbyrstr); ?>">
                                <span class='text-small text-muted'>If the username already exists, we will generate it randomly.</span>
                            </div>
                            <div class="form-group">
                                <label for="remail">Email Address</label>
                                <input type="email" name="remail" class="form-control" id="remail" placeholder="Email address">
                                <span class='text-small text-muted'>Your password will be sent to this email address.</span>
                            </div>
                        </div>
                        <div id='myregacc' class="d-none">
                            <div class="form-group">
                                <label for="myruname">Your <?php echo myvalidate($ssysout('SSYS_AUTHOR')); ?> Username</label>
                                <input type="text" name="myruname" class="form-control" id="myruname" placeholder="Your <?php echo myvalidate($ssysout('SSYS_AUTHOR')); ?> username" value="<?php echo myvalidate($licbyrstr); ?>">
                                <span class='text-small text-muted'>Please enter your <?php echo myvalidate($ssysout('SSYS_AUTHOR')); ?> username correctly.</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-whitesmoke text-md-right">
                        <div class="form-group float-left">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" name="isreuse" value="1" class="custom-control-input" id="isreuse" onclick="checkBoxCnt('isreuse', 'myregacc', 'newregacc');">
                                <label class="custom-control-label" for="isreuse">Use my existing <?php echo myvalidate($ssysout('SSYS_AUTHOR')); ?> account</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" data-toggle="tooltip" title="Register My License"><i class="fas fa-fw fa-unlock-alt"></i> Register</button >
                    </div>

                    <?php
                }
                ?>

                <input type="hidden" name="dosubmit" value="1">
            </form>
        </div>

        <?php
        if ($cfgrow['lichash'] && $cfgrow['licstatus'] > 0) {
            ?>
            <div class="card">
                <form method="post" action="index.php" id="interimopt">
                    <input type="hidden" name="hal" value="updates">
                    <div class="card-header">
                        <h4>Interim Options</h4>
                    </div>
                    <div class="card-body">
                        <div class="text-info">The features and/or options below are available as supplements for registered licenses and will be updated from time to time. In the future, it may be available as default or removed from the system. Please use it wisely.</div>

                        <h4 class="section-title mt-2">Activity Notification</h4>
                        <p class="section-lead text-muted text-small">
                            Display real-time notification for new registered members, login, and withdrawal requests.
                        </p>
                        <div class="form-group row">
                            <div class="section-lead">
                                <div class="custom-control custom-checkbox">
                                    <input name="istoastactvty" value="1" type="checkbox" class="custom-control-input" id="istoastactvty"<?php echo myvalidate($istoastactvty_cek); ?>>
                                    <label class="custom-control-label" for="istoastactvty">Enable</label>
                                </div>
                            </div>
                        </div>

                        <h4 class="section-title mt-2">Base Color</h4>
                        <p class="section-lead text-muted text-small">
                            Base color theme for the interface. The color scheme may be adjusted on the new version, including adding new schemes or removing existing ones.
                        </p>
                        <div class="selectgroup selectgroup-pills">
                            <div class="section-lead">
                                <?php echo myvalidate($themeclr_opt); ?>
                            </div>
                        </div>

                    </div>
                    <div class="card-footer bg-whitesmoke text-md-right">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-fw fa-check"></i> Submit
                        </button>
                    </div>

                    <input type="hidden" name="isintermopt" value="1">
                    <input type="hidden" name="docfgtoken" value="1">

                </form>
            </div>
            <div class="card">
                <form method="post" action="index.php" enctype="multipart/form-data" id="restoredbform">
                    <input type="hidden" name="hal" value="updates">
                    <div class="card-header">
                        <h4>Database Tools</h4>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <a href="index.php?hal=updates&do=optmz" data-toggle="tooltip" title="Optimize database" class="btn btn-info" type="button"><i class="fa fa-cogs"></i> Optimize</a>
                            <a href="updates.php?do=backup" data-toggle="tooltip" title="Backup database" class="btn btn-primary" type="button"><i class="fa fa-cloud-download-alt"></i> Backup</a>
                            <div class="form-text text-muted">Hint: Auto-backup available with the Branding Removal license.</div>
                        </div>
                        <div class="form-group">
                            <label for="restoredb">Restore database and <span class="text-danger">overwrite</span> current database</label>
                            <div class="input-group">
                                <input type="file" name="restoredb" id="restoredb" class="form-control">
                                <div class="input-group-append">
                                    <button class="btn btn-primary bootboxformconfirm" type="submit" data-form="restoredbform" data-poptitle="Restore Database" data-popmsg="Are you sure want to process?<br /><span class='text-danger'>Warning! This process cannot be undone.</span>"><i class="fa fa-cloud-upload-alt"></i> Restore</button>
                                </div>
                            </div>
                            <div class="form-text text-muted">Important: Make sure to upload a valid database backup file!</div>
                        </div>

                    </div>
                    <input type="hidden" name="dorestore" value="1">
                </form>
            </div>
            <?php
        }
        ?>

    </div>

</div>
