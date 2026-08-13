<?php
if (!defined('OK_LOADME')) {
    die('o o p s !');
}

$condition = '';
$row = $db->getAllRecords(DB_TBLPREFIX . '_mbrs', 'COUNT(*) as totref', $condition);
$myregtotal = $row[0]['totref'];

$condition = '';
$row = $db->getAllRecords(DB_TBLPREFIX . '_mbrplans', 'COUNT(*) as totref', $condition);
$myreftotal = $row[0]['totref'];

$hostcalcarr = get_admcalcumount();
$myincometotal = printmoney($hostcalcarr['hist_tincome']);

$totincome = printmoney($hostcalcarr['hist_earning']);
$mytxintotal = printmoney($hostcalcarr['hist_tincome']);
$mytxouttotal = printmoney($hostcalcarr['hist_toutcome']);
$mytottrx = $hostcalcarr['hist_tot'];

$condition = '';
$row = $db->getAllRecords(DB_TBLPREFIX . '_items', 'COUNT(*) as totitem', $condition);
$mytotitem = floatval($row[0]['totitem']);

$condition = '';
$row = $db->getAllRecords(DB_TBLPREFIX . '_sales', 'COUNT(*) as totorder', $condition);
$mytotorder = floatval($row[0]['totorder']);

$condition = " AND txtoken LIKE '%|TXTYPE:ORDER|%' AND txstatus = '1'";
$row = $db->getAllRecords(DB_TBLPREFIX . '_transactions', 'SUM(txamount) as totsales', $condition);
$mytotsales = printmoney($row[0]['totsales']);

$lastses = '0';
$condition = " AND sestype = 'member' AND sesidmbr > '0' AND sestime > '{$lastses}' GROUP BY sesidmbr";
$row = $db->getAllRecords(DB_TBLPREFIX . '_sessions', 'COUNT(*) as totonline', $condition);
$mbrtotonline = intval($row[0]['totonline']);

$condition = " AND txtoken LIKE '%|fbacktype:9|%' AND txtoken LIKE '%|proofimg:%' AND txstatus = '0'";
$row = $db->getAllRecords(DB_TBLPREFIX . '_transactions', 'COUNT(*) as totprofpay, SUM(txamount) as sumprofpay', $condition);
$mbrtotprofpay = intval($row[0]['totprofpay']);
$mbrsumprofpay = printmoney($row[0]['sumprofpay']);

$condition = '';
$row = $db->getAllRecords(DB_TBLPREFIX . '_mbrs', 'SUM(ewallet) as totmbrwallet', $condition);
$mbrtotwallet = printmoney($row[0]['totmbrwallet']);

$condition = " AND txtoken LIKE '%|WIDR:OUT|%' AND txstatus != '1'";
$row = $db->getAllRecords(DB_TBLPREFIX . '_transactions', 'SUM(txamount) as totwithdrawreq', $condition);
$mbrwithdrawreq = printmoney($row[0]['totwithdrawreq']);

// ---

switch ($mbrstr['mbrstatus']) {
    case "1":
        $regbadge_class = "badge-success";
        $regbadge_text = "Active";
        break;
    case "2":
        $regbadge_class = "badge-warning";
        $regbadge_text = "Limited";
        break;
    case "3":
        $regbadge_class = "badge-danger";
        $regbadge_text = "Blocked";
        break;
    default:
        $regbadge_class = "badge-default";
        $regbadge_text = "Inactive";
}
$myregstatus = "<div class='badge {$regbadge_class}'>{$regbadge_text}</div>";

if (intval($mbrstr['mpid']) > 0) {
    $myplanpay = '';
    switch ($mbrstr['mpstatus']) {
        case "1":
            $badge_class = "badge-success";
            $badge_text = "Active";
            break;
        case "2":
            $badge_class = "badge-warning";
            $badge_text = "Expire";
            break;
        case "3":
            $badge_class = "badge-danger";
            $badge_text = "Pending";
            break;
        default:
            $badge_class = "badge-primary";
            $badge_text = "";
            $myplanpay = "<a href='index.php?hal=planpay' class='btn btn-outline-danger'>Make Payment</a>";
    }
    $myplanstatus = "<div class='badge {$badge_class}'>{$badge_text}</div>" . $myplanpay;
    $reg_date = formatdate($mbrstr['reg_date']);
} else {
    $myplanstatus = "<a href='index.php?hal=planreg' class='btn btn-outline-primary'>{$LANG['g_register']}</a>";
}

// ---

$recenttrx = '';
$condition = "";
$limitrecent = intval($cfgrow['maxpage'] / 3);
$userData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_transactions WHERE 1 " . $condition . " ORDER BY txid DESC LIMIT " . $limitrecent);
if (count($userData) > 0) {
    foreach ($userData as $val) {
        $sestime = strtotime($val['reg_utctime'] ?? '');
        $timejoin = time_since($sestime);
        $dlnimgfile = ($val['mbr_image']) ? $val['mbr_image'] : $cfgrow['mbr_defaultimage'];
        $val['fullname'] = $val['firstname'] . ' ' . $val['lastname'];

        $fafontbadge = '';
        switch ($val['txstatus']) {
            case "1":
                $fafontbadge .= "fa-check text-primary";
                break;
            case "2":
                $fafontbadge .= "fa-exclamation text-warning";
                break;
            case "3":
                $fafontbadge .= "fa-times text-danger";
                break;
            default:
                $fafontbadge .= "fa-question text-light";
        }

        $txamount = printmoney($val['txamount']);

        $recenttrx .= "<ul class='list-unstyled list-unstyled-border'>
                                <li class='media'>
                                    <i class='fa fa-2x fa-fw {$fafontbadge} mr-2'></i>
                                    <div class='media-body'>
                                        <div class='media-right'>{$bpprow['currencysym']}{$txamount} {$bpprow['currencycode']}</div>
                                        <div class='media-title'>{$val['txmemo']}</div>
                                        <div class='float-right text-muted text-small'>{$val['txbatch']}</div>
                                        <div class='text-muted text-small'>{$val['txdatetm']}</div>
                                    </div>
                                </li>
                            </ul>";
    }
} else {
    $recenttrx = '<div class="text-center mt-4 text-muted">
                        <div>
                            <i class="fa fa-3x fa-question-circle"></i>
                        </div>
                        <div>' . $LANG['g_norecordinfo'] . '</div>
                   </div>';
}

// ---

$recentwdr = '';
$condition = " AND txtoken LIKE '%|WIDR:%' AND id > 0";
$userData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_transactions LEFT JOIN " . DB_TBLPREFIX . "_mbrs ON txtoid = id WHERE 1 " . $condition . " ORDER BY txstatus, txdatetm DESC LIMIT 3");
if (count($userData) > 0) {
    foreach ($userData as $val) {
        $sestime = strtotime($val['txdatetm'] ?? '');
        $timereq = time_since($sestime);
        $usrwdrstr = getmbrinfo($val['txtoid']);
        $dlnimgfile = ($usrwdrstr['mbr_image']) ? $usrwdrstr['mbr_image'] : $cfgrow['mbr_defaultimage'];
        $usrwdrstr['fullname'] = $usrwdrstr['firstname'] . ' ' . $usrwdrstr['lastname'];
        switch ($val['txstatus']) {
            case "1":
                $badgestatus = "<span class='badge badge-secondary' data-toggle='tooltip' title='{$LANG['g_withdrawispaid']}'><i class='fa fa-fw fa-check'></i></span>";
                break;
            case "2":
                $badgestatus = "<span class='badge badge-info' data-toggle='tooltip' title='{$LANG['g_withdrawislook']}'><i class='fa fa-fw fa-user'></i></span>";
                break;
            default:
                $badgestatus = "<span class='badge badge-light' data-toggle='tooltip' title='{$LANG['g_withdrawiswait']}'><i class='fa fa-fw fa-question'></i></span>";
        }
        $usrwdrmail = maskmail($usrwdrstr['email']);
        $stremail = (strlen($usrwdrmail ?? '') > 16) ? substr($usrwdrmail ?? '', 0, 14) . '...' : $usrwdrmail;
        $txamount = printmoney($val['txamount']);
        $recentwdr .= "<li class='media'>
                            <img class='mr-3 rounded-circle' width='48' src='{$dlnimgfile}' alt='avatar'>
                            <div class='media-body'>
                                <div class='float-right text-small text-success'>{$timereq} ago</div>
                                <div class='media-title'>{$bpprow['currencysym']}{$txamount}</div>
                                <div class='float-right media-title'>{$badgestatus}</div>
                                <span class='text-small text-muted'>
                                    <div><a href='index.php?hal=getuser&getId={$usrwdrstr['id']}&getMpid={$usrwdrstr['mpid']}' data-toggle='tooltip' title='{$usrwdrstr['fullname']}'>{$usrwdrstr['username']}</a></div>
                                    <div data-toggle='tooltip' title='{$usrwdrmail}'>{$stremail}</div>
                                </span>
                            </div>
                       </li>";
    }
} else {
    $recentwdr = '<div class="text-center mt-4 text-muted">
                        <div>
                            <i class="fa fa-3x fa-question-circle"></i>
                        </div>
                        <div>' . $LANG['g_norecordinfo'] . '</div>
                   </div>';
}

// ---

$recentrefl = '';
$condition = "";
$userData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_mbrs LEFT JOIN " . DB_TBLPREFIX . "_mbrplans ON id = idmbr WHERE 1 " . $condition . " ORDER BY mpid DESC LIMIT 9");
if (count($userData) > 0) {
    foreach ($userData as $val) {
        $sestime = ($val['mpid'] > 0) ? strtotime($val['reg_utctime'] ?? '') : strtotime($val['in_date'] ?? '');
        $timejoin = time_since($sestime);
        $dlnimgfile = ($val['mbr_image']) ? $val['mbr_image'] : $cfgrow['mbr_defaultimage'];
        $val['fullname'] = $val['firstname'] . ' ' . $val['lastname'];
        switch ($val['mpstatus']) {
            case "0":
                $badgestatus = "<span class='badge badge-light' data-toggle='tooltip' title='Inactive'><i class='fa fa-fw fa-question'></i></span>";
                break;
            case "1":
                $badgestatus = "<span class='badge badge-success' data-toggle='tooltip' title='Active'><i class='fa fa-fw fa-check'></i></span>";
                break;
            case "2":
                $badgestatus = "<span class='badge badge-warning' data-toggle='tooltip' title='Inactive'><i class='fa fa-fw fa-exclamation'></i></span>";
                break;
            case "2":
                $badgestatus = "<span class='badge badge-danger' data-toggle='tooltip' title='Inactive'><i class='fa fa-fw fa-times'></i></span>";
                break;
            default:
                $badgestatus = "<span class='badge badge-secondary' data-toggle='tooltip' title='Registered only'><i class='fa fa-fw fa-user'></i></span>";
        }
        $valmail = maskmail($val['email']);
        $stremail = (strlen($valmail ?? '') > 16) ? substr($valmail ?? '', 0, 14) . '...' : $valmail;
        $recentrefl .= "<li class='media'>
                            <img class='mr-3 rounded-circle' width='48' src='{$dlnimgfile}' alt='avatar'>
                            <div class='media-body'>
                                <div class='float-right text-small text-success'>{$timejoin} ago</div>
                                <div class='media-title'><a href='index.php?hal=getuser&getId={$val['id']}&getMpid={$val['mpid']}'>{$val['username']}</a></div>
                                <div class='float-right media-title'>{$badgestatus}</div>
                                <span class='text-small text-muted'>
                                <div>{$val['fullname']}</div><div data-toggle='tooltip' title='{$valmail}'>{$stremail}</div>
                                </span>
                            </div>
                       </li>";
    }
} else {
    $recentrefl = '<div class="text-center mt-4 text-muted">
                        <div>
                            <i class="fa fa-3x fa-question-circle"></i>
                        </div>
                        <div>' . $LANG['g_norecordinfo'] . '</div>
                   </div>';
}
?>

<div class="section-header">
    <h1><i class="fa fa-fw fa-chart-line"></i> <?php echo myvalidate($LANG['g_dashboardtitle']); ?></h1>
</div>

<div class="section-body">

    <div class="row">
        <div class="col-lg-4 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <a href='index.php?hal=userlist&fltrby=reg'>
                    <div class="card-icon bg-info">
                        <i class="far fa-address-book"></i>
                    </div>
                </a>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4><?php echo myvalidate($LANG['g_registered']); ?></h4>
                    </div>
                    <div class="card-body">
                        <?php echo myvalidate($myregtotal); ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <a href='index.php?hal=userlist'>
                    <div class="card-icon bg-info">
                        <i class="far fa-handshake"></i>
                    </div>
                </a>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4><?php echo myvalidate($LANG['g_member']); ?></h4>
                    </div>
                    <div class="card-body">
                        <?php echo myvalidate($myreftotal); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <a href='index.php?hal=userlist&dodisp=online'>
                    <div class="card-icon bg-info">
                        <i class="far fa-compass"></i>
                    </div>
                </a>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4><?php echo myvalidate($LANG['g_mbronline']); ?></h4>
                    </div>
                    <div class="card-body">
                        <?php echo myvalidate($mbrtotonline); ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <a href='index.php?hal=itemlist'>
                    <div class="card-icon bg-info">
                        <i class="far fa-hdd"></i>
                    </div>
                </a>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4><?php echo myvalidate($LANG['g_product']); ?></h4>
                    </div>
                    <div class="card-body">
                        <?php echo myvalidate($mytotitem); ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <a href='index.php?hal=saleslist'>
                    <div class="card-icon bg-info">
                        <i class="far fa-list-alt"></i>
                    </div>
                </a>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4><?php echo myvalidate($LANG['m_orderlist']); ?></h4>
                    </div>
                    <div class="card-body">
                        <?php echo myvalidate($mytotorder); ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <a href='index.php?hal=historylist&dohal=proof'>
                    <div class="card-icon bg-info">
                        <i class="far fa-file-image"></i>
                    </div>
                </a>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4><?php echo myvalidate($LANG['g_proofpay']); ?></h4>
                    </div>
                    <div class="card-body">
                        <?php echo myvalidate($mbrtotprofpay . ' <small>(' . $bpprow['currencysym'] . $mbrsumprofpay . ')</small>'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 col-md-12 col-12 col-sm-12">
            <div class="row">
                <div class="col-md-6 col-sm-6 col-12">
                    <div class="card card-statistic-1">
                        <a href='index.php?hal=historylist'>
                            <div class="card-icon bg-warning">
                                <i class="far fa-credit-card"></i>
                            </div>
                        </a>
                        <div class="card-wrap">
                            <div class="card-header">
                                <h4><?php echo myvalidate($LANG['g_income']); ?></h4>
                            </div>
                            <div class="card-body">
                                <?php echo myvalidate($bpprow['currencysym'] . $myincometotal); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-sm-6 col-12">
                    <div class="card card-statistic-1">
                        <a href='index.php?txadminfo=order&hal=historylist'>
                            <div class="card-icon bg-warning">
                                <i class="far fa-chart-bar"></i>
                            </div>
                        </a>
                        <div class="card-wrap">
                            <div class="card-header">
                                <h4><?php echo myvalidate($LANG['g_sales']); ?></h4>
                            </div>
                            <div class="card-body">
                                <?php echo myvalidate($bpprow['currencysym'] . $mytotsales); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-sm-6 col-12">
                    <div class="card card-statistic-1">
                        <a href='index.php?hal=userlist&_stbel=ewallet&_stype=up'>
                            <div class="card-icon bg-warning">
                                <i class="far fa-building"></i>
                            </div>
                        </a>
                        <div class="card-wrap">
                            <div class="card-header">
                                <h4><?php echo myvalidate($LANG['g_mbrwallet']); ?></h4>
                            </div>
                            <div class="card-body">
                                <?php echo myvalidate($bpprow['currencysym'] . $mbrtotwallet); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-sm-6 col-12">
                    <div class="card card-statistic-1">
                        <a href='index.php?hal=withdrawlist'>
                            <div class="card-icon bg-warning">
                                <i class="far fa-calendar"></i>
                            </div>
                        </a>
                        <div class="card-wrap">
                            <div class="card-header">
                                <h4><?php echo myvalidate($LANG['g_mbrwithdrawreq']); ?></h4>
                            </div>
                            <div class="card-body">
                                <?php echo myvalidate($bpprow['currencysym'] . $mbrwithdrawreq); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4><?php echo myvalidate($LANG['g_performance']); ?></h4>
                </div>
                <div class="card-body">
                    <canvas id="myChart" height="192"></canvas>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <canvas id="myChart1" height="128"></canvas>
                </div>
            </div>
            <?php
            if ($cfgtoken['isldrboardbyref'] == 1) {
                ?>
                <div class="card">
                    <div class="card-header">
                        <h4><?php echo myvalidate($LANG['g_leaderboard'] . ' - ' . $LANG['g_referral']); ?></h4>
                        <div class="card-header-action">
                            <?php
                            $_SESSION['leadboardpid'] = ($FORM['doforldboard'] == '1') ? intval($FORM['lbppid']) : (($_SESSION['leadboardpid'] > 0) ? $_SESSION['leadboardpid'] : '');

                            $planldboardname = $LANG['g_all'];
                            $ismarked = ($_SESSION['leadboardpid'] < 1) ? ' &#10003;' : '';

                            $optviewcycle = '<a class="dropdown-item" href="index.php?hal=dashboard&doforldboard=1">' . $planldboardname . $ismarked . '</a>';
                            $condition = " AND planstatus = '1' ORDER BY ppid ASC";
                            $planData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_payplans WHERE 1" . $condition . "");
                            if (count($planData) > 1) {
                                foreach ($planData as $val) {
                                    $ismarked = '';
                                    if ($_SESSION['leadboardpid'] == $val['ppid']) {
                                        $planldboardname = $val['ppname'];
                                        $ismarked = ' &#10003;';
                                    }
                                    $optviewcycle .= '<a class="dropdown-item" href="index.php?hal=dashboard&lbppid=' . $val['ppid'] . '&doforldboard=1">' . $val['ppname'] . $ismarked . '</a>';
                                }
                                ?>
                                <div class="dropdown">
                                    <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown">
                                        <?php echo myvalidate($LANG['g_plan']); ?> <span class="badge badge-light"><?php echo myvalidate($planldboardname); ?></span>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <?php echo myvalidate($optviewcycle); ?>
                                    </div>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <?php
                                $thismonth = date("M Y");
                                $selmonth = ($FORM['lbmonth'] != '') ? $FORM['lbmonth'] . '-' : date("Y-m-");
                                $ldboardmonthlist = '';

                                $months = array();
                                $count = 0;
                                while ($count <= 5) {
                                    $ismarked = '';
                                    $prevMonth = ($count == 1) ? "first day of previous month" : "-$count month";
                                    $nowmonth = date('Y-m', strtotime($prevMonth));
                                    $nowmonthstr = date('M Y', strtotime($prevMonth));
                                    $count++;
                                    if ($FORM['lbmonth'] == $nowmonth) {
                                        $ismarked = ' &#10003;';
                                        $thismonth = $nowmonthstr;
                                    }
                                    $ldboardmonthlist .= '<a class="dropdown-item" href="index.php?hal=dashboard&lbmonth=' . $nowmonth . '">' . $nowmonthstr . $ismarked . '</a>';
                                }
                                ?>
                                <h2 class="section-title dropdown">
                                    <?php echo myvalidate($LANG['g_bymonth'] . ' '); ?>
                                    <a href="javascript:;" class="text-info dropdown-toggle" data-toggle="dropdown">
                                        <?php echo myvalidate($thismonth); ?>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <?php echo myvalidate($ldboardmonthlist); ?>
                                    </div>
                                </h2>
                                <table class="table table-striped table-hover table-sm">
                                    <thead>
                                        <tr>
                                            <th class="text-center" scope="col"></th>
                                            <th class="text-center" scope="col"></th>
                                            <th scope="col"><?php echo myvalidate($LANG['g_username']); ?></th>
                                            <th class="text-right" scope="col"><?php echo myvalidate($LANG['g_referral']); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $countlboard = 0;
                                        $dlinertotarr = get_leaderboard($_SESSION['leadboardpid'], $selmonth);
                                        foreach ($dlinertotarr as $key => $value) {
                                            $countlboard++;
                                            $rklogostr = ($value['mbr_image'] != '') ? $value['mbr_image'] : DEFIMG_LOGO;
                                            ?>
                                            <tr>
                                                <th class="text-center" scope="row"><?php echo myvalidate($countlboard); ?></th>
                                                <td><img class='mr-3 rounded-circle img-fluid' width='32' src='<?php echo myvalidate($rklogostr); ?>' alt='<?php echo myvalidate($value['username']); ?>'></td>
                                                <td><?php echo myvalidate($value['username']); ?></td>
                                                <td class="text-right"><?php echo myvalidate($value['dltotal']); ?></td>
                                            </tr>
                                            <?php
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h2 class="section-title"><?php echo myvalidate($LANG['g_alltime']); ?></h2>
                                <table class="table table-striped table-hover table-sm">
                                    <thead>
                                        <tr>
                                            <th class="text-center" scope="col"></th>
                                            <th class="text-center" scope="col"></th>
                                            <th scope="col"><?php echo myvalidate($LANG['g_username']); ?></th>
                                            <th class="text-right" scope="col"><?php echo myvalidate($LANG['g_referral']); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $countlboard = 0;
                                        $dlinertotarr = get_leaderboard($_SESSION['leadboardpid']);
                                        foreach ($dlinertotarr as $key => $value) {
                                            $countlboard++;
                                            $rklogostr = ($value['mbr_image'] != '') ? $value['mbr_image'] : DEFIMG_LOGO;
                                            ?>
                                            <tr>
                                                <th class="text-center" scope="row"><?php echo myvalidate($countlboard); ?></th>
                                                <td><img class='mr-3 rounded-circle img-fluid' width='32' src='<?php echo myvalidate($rklogostr); ?>' alt='<?php echo myvalidate($value['username']); ?>'></td>
                                                <td><?php echo myvalidate($value['username']); ?></td>
                                                <td class="text-right"><?php echo myvalidate($value['dltotal']); ?></td>
                                            </tr>
                                            <?php
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
            ?>

            <div class="card">
                <div class="card-body">
                    <canvas id="myChart2" height="128"></canvas>
                </div>
            </div>
            <?php
            if ($cfgtoken['isldrboardbyearn'] == 1) {
                ?>
                <div class="card">
                    <div class="card-header">
                        <h4><?php echo myvalidate($LANG['g_leaderboard'] . ' - ' . $LANG['g_income']); ?></h4>
                        <div class="card-header-action">
                            <?php
                            $_SESSION['incomeboardpid'] = ($FORM['doforicmboard'] == '1') ? intval($FORM['lbppid']) : (($_SESSION['incomeboardpid'] > 0) ? $_SESSION['incomeboardpid'] : '');

                            $planldboardname = $LANG['g_all'];
                            $ismarked = ($_SESSION['incomeboardpid'] < 1) ? ' &#10003;' : '';

                            $optviewcycle = '<a class="dropdown-item" href="index.php?hal=dashboard&doforicmboard=1">' . $planldboardname . $ismarked . '</a>';
                            $condition = " AND planstatus = '1' ORDER BY ppid ASC";
                            $planData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_payplans WHERE 1" . $condition . "");
                            if (count($planData) > 1) {
                                foreach ($planData as $val) {
                                    $ismarked = '';
                                    if ($_SESSION['incomeboardpid'] == $val['ppid']) {
                                        $planldboardname = $val['ppname'];
                                        $ismarked = ' &#10003;';
                                    }
                                    $optviewcycle .= '<a class="dropdown-item" href="index.php?hal=dashboard&lbppid=' . $val['ppid'] . '&doforicmboard=1">' . $val['ppname'] . $ismarked . '</a>';
                                }
                                ?>
                                <div class="dropdown">
                                    <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown">
                                        <?php echo myvalidate($LANG['g_plan']); ?> <span class="badge badge-light"><?php echo myvalidate($planldboardname); ?></span>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <?php echo myvalidate($optviewcycle); ?>
                                    </div>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <?php
                                $thismonth = date("M Y");
                                $selmonth = ($FORM['ibmonth'] != '') ? $FORM['ibmonth'] . '-' : date("Y-m-");
                                $ldboardmonthlist = '';

                                $months = array();
                                $count = 0;
                                while ($count <= 5) {
                                    $ismarked = '';
                                    $prevMonth = ($count == 1) ? "first day of previous month" : "-$count month";
                                    $nowmonth = date('Y-m', strtotime($prevMonth));
                                    $nowmonthstr = date('M Y', strtotime($prevMonth));
                                    $count++;
                                    if ($FORM['ibmonth'] == $nowmonth) {
                                        $ismarked = ' &#10003;';
                                        $thismonth = $nowmonthstr;
                                    }
                                    $ldboardmonthlist .= '<a class="dropdown-item" href="index.php?hal=dashboard&ibmonth=' . $nowmonth . '">' . $nowmonthstr . $ismarked . '</a>';
                                }
                                ?>
                                <h2 class="section-title dropdown">
                                    <?php echo myvalidate($LANG['g_bymonth'] . ' '); ?>
                                    <a href="javascript:;" class="text-info dropdown-toggle" data-toggle="dropdown">
                                        <?php echo myvalidate($thismonth); ?>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <?php echo myvalidate($ldboardmonthlist); ?>
                                    </div>
                                </h2>
                                <table class="table table-striped table-hover table-sm">
                                    <thead>
                                        <tr>
                                            <th class="text-center" scope="col"></th>
                                            <th class="text-center" scope="col"></th>
                                            <th scope="col"><?php echo myvalidate($LANG['g_username']); ?></th>
                                            <th class="text-right" scope="col"><?php echo myvalidate($LANG['g_income']); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $countlboard = 0;
                                        $dlinertotarr = get_incomeboard($_SESSION['incomeboardpid'], $selmonth);
                                        foreach ($dlinertotarr as $key => $value) {
                                            $countlboard++;
                                            $rklogostr = ($value['mbr_image'] != '') ? $value['mbr_image'] : DEFIMG_LOGO;
                                            ?>
                                            <tr>
                                                <th class="text-center" scope="row"><?php echo myvalidate($countlboard); ?></th>
                                                <td><img class='mr-3 rounded-circle img-fluid' width='32' src='<?php echo myvalidate($rklogostr); ?>' alt='<?php echo myvalidate($value['username']); ?>'></td>
                                                <td><?php echo myvalidate($value['username']); ?></td>
                                                <td class="text-right"><?php echo myvalidate($value['earntot']); ?></td>
                                            </tr>
                                            <?php
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h2 class="section-title"><?php echo myvalidate($LANG['g_alltime']); ?></h2>
                                <table class="table table-striped table-hover table-sm">
                                    <thead>
                                        <tr>
                                            <th class="text-center" scope="col"></th>
                                            <th class="text-center" scope="col"></th>
                                            <th scope="col"><?php echo myvalidate($LANG['g_username']); ?></th>
                                            <th class="text-right" scope="col"><?php echo myvalidate($LANG['g_income']); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $countlboard = 0;
                                        $dlinertotarr = get_incomeboard($_SESSION['incomeboardpid']);
                                        foreach ($dlinertotarr as $key => $value) {
                                            $countlboard++;
                                            $rklogostr = ($value['mbr_image'] != '') ? $value['mbr_image'] : DEFIMG_LOGO;
                                            ?>
                                            <tr>
                                                <th class="text-center" scope="row"><?php echo myvalidate($countlboard); ?></th>
                                                <td><img class='mr-3 rounded-circle img-fluid' width='32' src='<?php echo myvalidate($rklogostr); ?>' alt='<?php echo myvalidate($value['username']); ?>'></td>
                                                <td><?php echo myvalidate($value['username']); ?></td>
                                                <td class="text-right"><?php echo myvalidate($value['earntot']); ?></td>
                                            </tr>
                                            <?php
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
            ?>

            <div class="card">
                <div class="card-header">
                    <h4>Overview</h4>
                </div>
                <div class="card-body">
                    <div class="summary">
                        <div class="summary-info">
                            <h4><span class="text-success"><i class="fas fa-caret-up"></i></span><?php echo myvalidate($bpprow['currencysym'] . $mytxintotal); ?> <span class="text-danger"><i class="fas fa-caret-down"></i></span><?php echo myvalidate($bpprow['currencysym'] . $mytxouttotal); ?></h4>
                            <div class="text-muted"><?php echo myvalidate($mytottrx); ?> total transactions</div>
                            <h3 class="mt-2"><span class="text-info"></span><?php echo myvalidate($bpprow['currencysym'] . $totincome . ' ' . $bpprow['currencycode']); ?></h3>
                            <div class="d-block mt-2">
                                <a href="index.php?hal=historylist">View Details</a>
                            </div>
                        </div>
                        <div class="alert alert-light mt-2">
                            Commission pool balance: <?php echo myvalidate('<strong>' . $bpprow['currencysym'] . $cfgrow['poolwlet'] . '</strong> ' . $bpprow['currencycode']); ?>
                            <?php
                            $uptopluscnt = file_get_contents("../common/plus.html");
                            ?>
                            <a href="javascript:;" data-href="<?php echo myvalidate($ssysout('SSYS_URL')); ?>/docs/<?php echo myvalidate(strtolower($ssysout('SSYS_NAME'))); ?>/index.php?todo=upgrade" class="btn btn-sm btn-danger bootboxconfirm" data-poptitle="Update Commission Pool Balance" data-popmsg="<?php echo myvalidate($uptopluscnt); ?>" data-toggle="tooltip" title="Update commission pool balance"><i class="fa fa-fw fa-edit"></i></a>

                        </div>
                        <div class="summary-item">
                            <h6><span class='text-muted'>Recent <?php echo myvalidate($limitrecent); ?> transactions</span></h6>
                            <?php echo myvalidate($recenttrx); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12 col-12 col-sm-12">
            <div class="card">
                <div class="card-body">
                    <canvas id="myChart3" height="256"></canvas>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4>Withdrawal</h4>
                    <div class="card-header-action">
                        <a href="index.php?hal=withdrawlist" class="btn btn-primary" data-toggle="tooltip" title="View All"><i class="fa fa-ellipsis-h"></i></a>
                    </div>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled list-unstyled-border">
                        <?php echo myvalidate($recentwdr); ?>
                    </ul>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4>Recent Members</h4>
                    <div class="card-header-action">
                        <a href="index.php?hal=userlist" class="btn btn-primary" data-toggle="tooltip" title="View All"><i class="fa fa-ellipsis-h"></i></a>
                    </div>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled list-unstyled-border">
                        <?php echo myvalidate($recentrefl); ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Template JS File -->
<script src="../assets/js/chart.min.js"></script>

<!-- Page Specific JS File -->
<script src="../assets/js/acpchart.js"></script>
