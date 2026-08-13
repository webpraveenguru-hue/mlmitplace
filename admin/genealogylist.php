<?php
if (!defined('OK_LOADME')) {
    die('o o p s !');
}

$topmbrsopt = '';
$topmbrs = array();
$row = $db->getAllRecords(DB_TBLPREFIX . '_mbrplans', '*', ' AND idspr = "0"');
foreach ($row as $value) {
    $mbrstr = getmbrinfo('', '', $value['mpid']);
    $isselected = ($FORM['loadId'] == $mbrstr['mpid']) ? ' selected' : '';
    $isusercyc = ($value['cyclingbyid'] > 0) ? ' &#x269F;' : '';
    $nowplanname = $bpparr[$value['mppid']]['ppname'];
    $topmbrsopt .= "<option value='{$mbrstr['mpid']}'{$isselected}>{$mbrstr['id']}.{$mbrstr['mpid']} [{$nowplanname}] {$mbrstr['username']} ({$mbrstr['firstname']} {$mbrstr['lastname']} - " . maskmail($mbrstr['email']) . "){$isusercyc}</option>";
}

$_SESSION['statusFltr'] = $FORM['statusFltr'] = ($FORM['statusFltr'] != '') ? $FORM['statusFltr'] : $_SESSION['statusFltr'];
$statusmbrsopt = '';
$statusmbrsarr = array('0' => $LANG['g_all'], '1' => $LANG['g_enhanced']);
foreach ($statusmbrsarr as $key => $value) {
    $isselected = ($FORM['statusFltr'] == $key) ? ' selected' : '';
    $statusmbrsopt .= "<option value='{$key}'{$isselected}>{$value}</option>";
}

$mbrstr = getmbrinfo('', '', $FORM['loadId']);
if ($mbrstr['idspr'] != 0) {
    $nowplanname = $bpparr[$mbrstr['mppid']]['ppname'];
    $topmbrsopt .= "<option value='{$mbrstr['mpid']}' selected>{$mbrstr['id']}.{$mbrstr['mpid']} [{$nowplanname}] {$mbrstr['username']} ({$mbrstr['firstname']} {$mbrstr['lastname']} - " . maskmail($mbrstr['email']) . ")</option>";
}

$icyc = 0;
// plans by member
$optviewcycle = '';
$userData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_mbrplans WHERE 1 AND idmbr = '{$mbrstr['id']}' ORDER BY mpid");
if (count($userData) > 0) {
    foreach ($userData as $val) {
        $icyc++;
        $ismarked = ($FORM['loadId'] == $val['mpid']) ? ' &#10003;' : '';
        $isusercyc = ($val['cyclingbyid'] > 0) ? ' &#x269F;' : '';
        $plancycname = ($frlmtdcfg['isxplans'] == 1) ? $bpparr[$val['mppid']]['ppname'] . $isusercyc : $LANG['g_cycle'] . ' ' . $icyc;
        $optviewcycle .= "<a class='dropdown-item' href='index.php?hal=genealogylist&loadId={$val['mpid']}&leadppid={$val['mppid']}'>" . strtoupper($mbrstr['username']) . " - {$plancycname}{$ismarked}</a>";
    }
}

if ($mbrstr['cyclingbyid'] > 0) {
    $plancycname = $bpparr[$mbrstr['mppid']]['ppname'] . ' &#x269F;';
    $plancycstr = $LANG['g_cycle'];
} else {
    $plancycname = $bpparr[$mbrstr['mppid']]['ppname'];
    $plancycstr = $LANG['g_plan'];
}

// plans for referrals
$nowppid = intval($FORM['nowppid'] ?? '');
$isdlplanmarked = ($nowppid == '0') ? ' &#10003;' : '';
$listplandl = "<a class='dropdown-item' href='index.php?hal=genealogylist&loadId={$FORM['loadId']}&nowppid=0&leadppid={$FORM['leadppid']}'>" . "{$LANG['g_all']}{$isdlplanmarked}</a>";

$planData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_payplans WHERE 1 ORDER BY ppid");
if (count($planData) > 0) {
    foreach ($planData as $val) {
        $isdlplanmarked = ($nowppid == $val['ppid']) ? ' &#10003;' : '';
        $listplandl .= "<a class='dropdown-item' href='index.php?hal=genealogylist&loadId={$FORM['loadId']}&nowppid={$val['ppid']}&leadppid={$FORM['leadppid']}'>" . "{$val['ppname']}{$isdlplanmarked}</a>";
    }
}
$nameplandl = ($nowppid > 0) ? $bpparr[$nowppid]['ppname'] : $LANG['g_all'];

$displaygen = ($mbrstr['id'] > 0) ? $displaygen = "<script type='text/javascript'>new Treant(chart_config);</script>" : '';

$disprankcolor = show_rankcolorlist(1);
?>

<link rel="stylesheet" href="../assets/fellow/treant/Treant.css">
<link rel="stylesheet" href="../assets/fellow/treant/simple-scrollbar.css">
<link rel="stylesheet" href="../assets/fellow/treant/perfect-scrollbar.css">

<div class="section-header">
    <h1><i class="fa fa-fw fa-sitemap"></i> <?php echo myvalidate($LANG['m_genealogyview']); ?></h1>
</div>

<div class="section-body">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Member Genealogy</h4>
                    <div class="card-header-action">
                        <?php
                        if ($icyc > 1) {
                            ?>
                            <div class="dropdown">
                                <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown">
                                    <?php echo myvalidate($plancycstr); ?> <span class="badge badge-light"><?php echo myvalidate($plancycname); ?></span>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <?php echo myvalidate($optviewcycle); ?>
                                </div>
                            </div>
                            <?php
                        }
                        if ($FORM['loadId'] > 0) {
                            ?>
                            <div class="dropdown">
                                <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown">
                                    <?php echo myvalidate($LANG['g_referrals']); ?> <span class="badge badge-light"><?php echo myvalidate($nameplandl); ?></span>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <?php echo myvalidate($listplandl); ?>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
                <div class="card-body">
                    <form method="get" action="index.php">
                        <input type="hidden" name="hal" value="genealogylist">
                        <div class="form-group">
                            <div class="input-group">
                                <div class="input-group-prepend d-none d-md-block">
                                    <span class="input-group-text">Front end Member</span>
                                </div>
                                <select name='loadId' class="custom-select text-monospace" id="inputGroupSelect04">
                                    <option selected>-</option>
                                    <?php echo myvalidate($topmbrsopt); ?>
                                </select>
                                <select name='statusFltr' class="custom-select text-monospace">
                                    <?php echo myvalidate($statusmbrsopt); ?>
                                </select>
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-primary" type="button">Load</button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <?php echo ($FORM['loadId'] > 0) ? myvalidate($disprankcolor) : ''; ?>

                    <div class="genchart" id="genviewer">
                        <div class="empty-state" data-height="400">
                            <div class="empty-state-icon bg-info">
                                <i class="fas fa-question"></i>
                            </div>
                            <h2><?php echo myvalidate($LANG['g_norecordgen']); ?></h2>
                            <p class="lead">
                                <?php echo myvalidate($LANG['g_norecordgeninfo']); ?>
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<style>
<?php
foreach ($bpparr as $val) {
    $ppnowid = $val['ppid'];
    $ppbdcolor = base64_decode(get_optionvals($val['plantoken'], 'ppbdcolor'));
    $ppnowbdcolor = ($ppbdcolor != '') ? $ppbdcolor : '#999';
    $ppbdsize = get_optionvals($val['plantoken'], 'ppbdsize');
    $ppnowbdsize = ($ppbdsize > 0 && $ppbdsize < 6) ? $ppbdsize : 1;
    $ppbgcolor = base64_decode(get_optionvals($val['plantoken'], 'ppbgcolor'));
    $ppnowbgcolor = ($ppbgcolor != '') ? $ppbgcolor : '#EEE';
    echo ".ppmbr{$ppnowid}{border-color: {$ppnowbdcolor} !important;border-width: {$ppnowbdsize}px !important;} ";
}

$rankarr = ranklist();
echo ".rkmbr0{background-color: #EEE !important;} ";
foreach ($rankarr as $val) {
    $rknowid = $val['rkid'];
    $rkbgcolor = get_optionvals($val['rktoken'], 'rkbgcolor');
    $rknowbgcolor = ($rkbgcolor != '') ? $rkbgcolor : '#EEE';
    echo ".rkmbr{$rknowid}{background-color: {$rknowbgcolor} !important;} ";
}
?>
</style>

<script src="../assets/fellow/treant/raphael.js"></script>
<script src="../assets/fellow/treant/Treant.js"></script>
<script src="../assets/fellow/treant/jquery.mousewheel.js"></script>
<script src="../assets/fellow/treant/perfect-scrollbar.js"></script>
<script src="loadgentree.php?loadId=<?php echo myvalidate($FORM['loadId']); ?>&ppidFltr=<?php echo myvalidate($nowppid); ?>&statusFltr=<?php echo myvalidate($FORM['statusFltr']); ?>&mdlhasher=<?php echo myvalidate($mdlhasher); ?>"></script>

<?php
echo myvalidate($displaygen);
