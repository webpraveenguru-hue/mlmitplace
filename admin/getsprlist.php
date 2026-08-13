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

$redirto = $_SESSION['redirto'];
$_SESSION['redirto'] = '';

$mpid = intval($FORM['mpId']);
$mbrstr = getmbrinfo('', '', $mpid);
$sprlistarr = getsprlistid($mbrstr['sprlist']);

$uplinearr = [];
foreach ($sprlistarr as $key => $value) {
    $sprstr = getmbrinfo('', '', $value);
    if ($sprstr['mpid'] < 1) {
        break;
    }
    $cardbrdcolor = ($sprstr['id'] == $mbrstr['idref']) ? 'secondary' : 'light';
    $uplinebadge = badgembrplanstatus($sprstr['mbrstatus'], $sprstr['mpstatus'], $bpparr[$sprstr['mppid']]['ppname'], $sprstr['mbr_image']);
    $uplinearr[] = <<<INI_HTML
                <div class="card card-{$cardbrdcolor}">
                  <div class="card-header">
                    <h4>{$sprstr['username']}</h4>
                    <div class="card-header-action">
                      <span class="badge badge-info">#{$key}</span>
                    </div>
                  </div>
                  <div class="card-body">
                    <div class="float-md-right">
                    {$uplinebadge}
                    <a href="index.php?hal=getuser&getId={$sprstr['id']}&getMpid={$sprstr['mpid']}" class="badge badge-primary">{$LANG['g_memberprofile']}</a>
                    </div>
                    <div class="float-left">
                    <div class="text-monospace text-small text-muted">{$sprstr['reg_utctime']}</div>
                    {$sprstr['fullname']}<br />
                    {$sprstr['email']}
                    </div>
                  </div>
                </div>
INI_HTML;
}
$uplistarr = array_reverse($uplinearr);
$upliststr = implode("<div class='text-center'><i class='fa fa-fw fa-arrow-up'></i></div>", $uplistarr);
?>

<div class="row">
    <div class="col-md-12">

        <?php echo myvalidate($upliststr); ?>

        <hr />
        <div class="text-md-center">
            <a href="javascript:;" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-fw fa-times"></i> Close</a>
        </div>

    </div>

</div>
