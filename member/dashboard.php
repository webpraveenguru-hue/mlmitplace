<?php
if (!defined('OK_LOADME')) {
    die('o o p s !');
}

// create tx for manual renewal in advance
if ($FORM['renewHash'] == md5($mbrstr['mpid'] . $mbrstr['reg_expd']) && $FORM['renewId'] == $mbrstr['mpid'] && $FORM['redir']) {
    $utctime = date('Y-m-d H:i:s', time() + (3600 * $cfgrow['time_offset']));
    do_renewtx($utctime, $mbrstr);
    redirpageto('index.php?hal=' . $FORM['redir']);
    exit;
}

$condition = ' AND sprlist LIKE "%:' . $mbrstr['mpid'] . '|%" ';
$row = $db->getAllRecords(DB_TBLPREFIX . '_mbrplans', 'COUNT(*) as totref', $condition);
$myreftotal = $row[0]['totref'];

$condition = " AND sprlist LIKE '%:" . $mbrstr['mpid'] . "|%' AND mpstatus = '1' ";
$row = $db->getAllRecords(DB_TBLPREFIX . '_mbrplans', 'COUNT(*) as totref', $condition);
$myactreftotal = $row[0]['totref'];

$condition = ' AND idref = "' . $mbrstr['id'] . '" ';
$row = $db->getAllRecords(DB_TBLPREFIX . '_mbrplans', 'COUNT(*) as totref', $condition);
$myrefonly = $row[0]['totref'];

$condition = ' AND txtoid = "' . $mbrstr['id'] . '" AND txstatus = "1" AND txtoken LIKE "%|LCM:%" ';
$row = $db->getAllRecords(DB_TBLPREFIX . '_transactions', 'SUM(txamount) as totincome', $condition);
$myincometotal = sprintf("%0.2f", $row[0]['totincome']);
$myewallet = sprintf("%0.2f", $mbrstr['ewallet']);

$hitratio = $LANG['g_performance'] . ': ';
$adjthits = ($mbrstr['hits'] > 0) ? $mbrstr['hits'] : 1;
$prcnthit = $myrefonly / $adjthits * 100;
if ($prcnthit > 100000) {
    $hitratio .= "<i class='fas fa-star fa-fw text-warning'></i><i class='fas fa-star fa-fw text-warning'></i><i class='fas fa-star fa-fw text-warning'></i>";
} else if ($prcnthit > 10000) {
    $hitratio .= "<i class='fas fa-star fa-fw text-warning'></i><i class='fas fa-star fa-fw text-warning'></i>";
} else if ($prcnthit > 1000) {
    $hitratio .= "<i class='fas fa-star fa-fw text-warning'></i>";
} else {
    $hitratio = $LANG['m_ibconversion'] . ': ';
    $hitratio .= ($myrefonly > 0) ? number_format($prcnthit, 2) . '%' : '0%';
}

// ---
$lastrefurl = get_optionvals($mbrstr['mbrtoken'], 'lastrefurlhit');
$lastrefurlhit = ($lastrefurl != '') ? $lastrefurl : $LANG['g_never'];

$reficopercen = get_icopercentage($myreftotal, $myactreftotal, 'ref');
$hiticopercen = get_icopercentage($cfgrow['datetimestr'], $lastrefurl, 'hit');
$amounticopercen = get_icopercentage($myincometotal, $myewallet, 'amount');
// ---

$condition = " AND (txfromid = '{$mbrstr['id']}' OR txtoid = '{$mbrstr['id']}') ";
$hostcalcarr = get_calcumount($mbrstr, $condition);

$refbon = $hostcalcarr['hist_refbonus'];
$sprbon = $hostcalcarr['hist_sprbonus'];
$rwdbon = $hostcalcarr['hist_rwdbonus'];
$mypaymn = $hostcalcarr['hist_mypaymn'];
$renewfee = $hostcalcarr['hist_renewfee'];
$reqwdrwait = $hostcalcarr['hist_reqwdrwait'];

$reqwdrdone = $hostcalcarr['hist_reqwdrdone'];
$feewdr = $hostcalcarr['hist_feewdr'];
$waletout = $hostcalcarr['hist_waletout'];

$mytxintotal = $hostcalcarr['hist_earning'];
$mytxouttotal = $reqwdrdone + $feewdr + $waletout;
$mydiftrx = $hostcalcarr['hist_pending'];
$mytottrx = $hostcalcarr['hist_tot'];
$mytxwallet = $hostcalcarr['hist_ewallet'];
// ---

$mbrimgstr = ($mbrstr['mbr_image']) ? $mbrstr['mbr_image'] : $cfgrow['mbr_defaultimage'];

switch ($mbrstr['mbrstatus']) {
    case "1":
        $regbadge_class = "badge-success";
        $regbadge_text = $LANG['g_active'];
        break;
    case "2":
        $regbadge_class = "badge-warning";
        $regbadge_text = $LANG['g_limited'];
        break;
    case "3":
        $regbadge_class = "badge-danger";
        $regbadge_text = $LANG['g_pending'];
        break;
    default:
        $regbadge_class = "badge-secondary";
        $regbadge_text = $LANG['g_inactive'];
}
$myregstatus = "<div class='badge {$regbadge_class}'>{$regbadge_text}</div>";

if (intval($mbrstr['mpid']) > 0) {
    $myplanpay = '';
    switch ($mbrstr['mpstatus']) {
        case "1":
            $badge_class = "badge-success";
            $badge_text = $LANG['g_active'];
            break;
        case "2":
            $badge_class = "badge-warning";
            $badge_text = $LANG['g_expire'];
            break;
        case "3":
            $badge_class = "badge-danger";
            $badge_text = $LANG['g_pending'];
            break;
        default:
            $badge_class = "badge-secondary";
            $badge_text = $LANG['g_inactive'];
    }
    $myplanstatus = "<div class='badge {$badge_class}'>{$badge_text}</div>" . $myplanpay;
    $reg_date = formatdate($mbrstr['reg_date']);
    $regmbrsince = "<span class='text-muted'>{$LANG['m_registeredsince']}</span> {$reg_date}";
} else {
    $myplanstatus = "<a href='#planreg' class='btn btn-outline-danger'>{$LANG['g_register']}</a>";
    $regmbrsince = '';
}

// ---

$sprstr = getmbrinfo($mbrstr['idspr']);
$sprstr['fullname'] = $sprstr['firstname'] . ' ' . $sprstr['lastname'];
$sprimgstr = ($sprstr['mbr_image']) ? $sprstr['mbr_image'] : $cfgrow['mbr_defaultimage'];
$spremailstr = (strlen($sprstr['email'] ?? '') > 23) ? substr($sprstr['email'] ?? '', 0, 20) . '...' : $sprstr['email'];
$sprphonestr = ($sprstr['phone']) ? $sprstr['phone'] : '-';
$sprcountrystr = ucwords(strtolower($country_array[$sprstr['country']] ?? ''));
$sprstatusstr = badgembrplanstatus($sprstr['mbrstatus'], $sprstr['mpstatus'], $bpparr[$sprstr['mppid']]['ppname']);
$spraboutstr = ($sprstr['mbr_intro']) ? "<blockquote class='text-small'>" . base64_decode($sprstr['mbr_intro'] ?? '') . "</blockquote>" : '';

// ---

$recentrefl = '';
$condition = " AND sprlist LIKE '%:{$mbrstr['mpid']}|%' AND mppid = '{$mbrstr['mppid']}' AND id != {$mbrstr['id']}";
$userData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_mbrs LEFT JOIN " . DB_TBLPREFIX . "_mbrplans ON id = idmbr WHERE 1 " . $condition . " ORDER BY mpid DESC LIMIT 9");
if (count($userData) > 0) {
    foreach ($userData as $val) {
        $sestime = strtotime($val['reg_utctime'] ?? '');
        $timejoin = time_since($sestime);
        $dlnimgfile = ($val['mbr_image']) ? $val['mbr_image'] : $cfgrow['mbr_defaultimage'];
        $val['fullname'] = $val['firstname'] . ' ' . $val['lastname'];
        $stremail = (strlen($val['email'] ?? '') > 24) ? substr($val['email'] ?? '', 0, 21) . '...' : $val['email'];
        $recentrefl .= "<li class='media'>
                            <img class='mr-3 rounded-circle' width='48' src='{$dlnimgfile}' alt='avatar'>
                            <div class='media-body'>
                                <div class='float-right text-small text-success'>{$timejoin} {$LANG['g_timeago']}</div>
                                <div class='media-title'>{$val['username']}</div>
                                <span class='text-small text-muted'><div>{$val['fullname']}</div><div data-toggle='tooltip' title='{$val['email']}'>{$stremail}</div></span>
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

$unpaidtxid = get_unpaidtxid($mbrstr);

$expdatestr = ($mbrstr['reg_expd'] > $mbrstr['reg_date']) ? $LANG['m_expiration'] . ': ' . formatdate($mbrstr['reg_expd']) : '';
$istrial = get_optionvals($mbrstr['mptoken'], 'istrial');

$mysiteurl = $mbrstr['reflink'];
?>

<div class="section-header">
    <h1><i class="fa fa-fw fa-chart-line"></i> <?php echo myvalidate($LANG['g_dashboardtitle']); ?></h1>
</div>

<div class="section-body">
    <div class="row">
        <div class="col-lg-4 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-info">
                    <i class="far fa-thumbs-up"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4><?php echo myvalidate($LANG['g_hits']); ?></h4>
                    </div>
                    <div class="card-body">
                        <?php echo myvalidate($mbrstr['hits']); ?>
                        <div class="text-small text-muted">
                            <?php echo myvalidate($hitratio); ?>
                        </div>
                    </div>
                    <div class="card-footer text-small text-small mt-2 border-top">
                        <i class="fas fa-calendar-alt"></i> <?php echo myvalidate($LANG['g_lastvisit'] . ': <strong>' . $lastrefurlhit . '</strong>'); ?>
                        <span class="float-right"><?php echo myvalidate($hiticopercen); ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <a href="index.php?hal=userlist&dohal=clear">
                    <div class="card-icon bg-info">
                        <i class="far fa-handshake"></i>
                    </div>
                </a>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4><?php echo myvalidate($LANG['m_ibpersonal']); ?></h4>
                    </div>
                    <div class="card-body">
                        <?php echo myvalidate($myrefonly); ?>
                        <div class="text-small text-muted">
                            <?php echo myvalidate($LANG['g_totalreferral']); ?>: <?php echo myvalidate($myreftotal); ?>
                        </div>
                    </div>
                    <div class="card-footer text-small mt-2 border-top">
                        <i class="fas fa-user-check text-success"></i> <?php echo myvalidate($LANG['g_active'] . ': <strong>' . $myactreftotal . '</strong>'); ?>
                        <span class="float-right"><?php echo myvalidate($reficopercen); ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <a href="index.php?hal=historylist&dohal=clear">
                    <div class="card-icon bg-warning">
                        <i class="far fa-credit-card"></i>
                    </div>
                </a>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4><?php echo myvalidate($LANG['g_earning']); ?></h4>
                    </div>
                    <div class="card-body">
                        <?php echo myvalidate($bpprow['currencysym'] . printmoney($myincometotal)); ?>
                        <div class="text-small text-muted">
                            <?php echo myvalidate($LANG['m_incometotal']); ?>
                        </div>
                    </div>
                    <div class="card-footer text-small text-small mt-2 border-top">
                        <a href="index.php?hal=withdrawreq"><i class="fas fa-wallet fa-fw<?php echo ($myewallet < 0) ? ' text-danger' : ''; ?>"></i></a> <?php echo myvalidate($LANG['m_ibwallet'] . ': <strong>' . $bpprow['currencysym'] . printmoney($myewallet) . '</strong> ' . $bpprow['currencycode']); ?>
                        <span class="float-right"><?php echo myvalidate($amounticopercen); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <?php
        if ($bptoken['timetodelinactiveacc'] != '') {
            if ($mbrstr['mpid'] > 0 && $mbrstr['mpstatus'] <= 1) {
                $expdarr = get_actdate($bptoken['timetodelinactiveacc'], $mbrstr['reg_utctime']);
                $onrem_expd = $expdarr['next'];
                $invdtexp = ($onrem_expd > $cfgrow['datestr']) ? $onrem_expd : '';

                if ($invdtexp != '') {
                    ?>
                    <script language="JavaScript" type="text/javascript">
                        window.onload = function ()
                        {
                            var counter = document.getElementById("countdown-holder");
                            var targetDate = new Date(<?php echo strtotime($invdtexp) * 1000; ?>);

                            setInterval(function () {
                                counter.innerHTML = '<i class="fas fa-hourglass-half fa-fw text-secondary"></i> ' + countdown(targetDate).toString();
                            }, 1000);
                        }
                    </script>
                    <?php
                }
            }
        }
        ?>

        <div class="col-lg-8 col-md-12 col-12 col-sm-12">
            <?php
            $txunpaidarr = get_txinfo($unpaidtxid);
            $txtoken = get_optionvals($txunpaidarr['txtoken']);
            $makepaymentstr = ($txtoken['proofimg'] != '' || ($txtoken['fbacktype'] == '9' && $txunpaidarr['txstatus'] != 1)) ? $LANG['m_pendingreview'] : $LANG['m_makepayment'];
            $myplanpaybtn = "<a href='index.php?hal=planpay' class='btn btn-outline-danger'>{$makepaymentstr}</a>";

            $myplanstatusbtn = ($unpaidtxid > 0) ? $myplanpaybtn : $myplanstatus;

            if (intval($mbrstr['mpid']) < 1 || intval($mbrstr['mpstatus']) != 1 || $unpaidtxid > 0) {
                ?>
                <div class="alert alert-light alert-has-icon">
                    <div class="alert-icon text-danger"><i class="far fa-bell"></i></div>
                    <div class="alert-body text-danger">
                        <div class="alert-title"><?php echo myvalidate($LANG['m_notice']); ?></div>
                        <?php
                        if (intval($mbrstr['mpid']) < 1) {
                            echo myvalidate($LANG['m_noticereg']) . " <strong>any program</strong>.";
                        } elseif (intval($mbrstr['mpstatus']) != 1) {
                            if ($unpaidtxid > 0) {
                                echo myvalidate($LANG['m_noticepay']);
                            } else {
                                echo myvalidate($LANG['m_noticeadm']);
                                $myplanstatus = '';
                                $myplanstatusbtn = "<a href='index.php?hal=feedback' class='btn btn-info'>{$LANG['m_contactus']}</a>";
                            }
                        } elseif ($unpaidtxid > 0) {
                            echo myvalidate($LANG['m_noticerepay']);
                        }
                        ?>
                        <div class="mt-4">
                            <div class="float-md-right">
                                <?php echo myvalidate($myplanstatusbtn); ?>
                            </div>
                            <div class="text-small text-info"><span id="countdown-holder"></span></div>
                        </div>
                    </div>
                </div>
                <?php
            }
            ?>

            <!-- Performance -->
            <?php
            if ($mbrstr['reflink'] != '') {
                ?>
                <div class="card">
                    <div class="card-header">
                        <h4><?php echo myvalidate($LANG['g_performance']); ?></h4>
                    </div>
                    <div class="card-body">
                        <canvas id="myChart" height="128"></canvas>
                    </div>
                </div>
                <?php
            } else {
                // keep myChart to avoid display error and hide it with display:none
                echo '<canvas id="myChart" style="display:none;"></canvas>';
            }
            ?>

            <!-- Overview -->
            <div class="card">
                <div class="card-header">
                    <h4><?php echo myvalidate($LANG['g_accoverview']); ?></h4>
                    <div class="card-header-action">
                        <?php echo myvalidate($myregstatus); ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="summary-item">

                        <ul class="list-unstyled list-unstyled-border">
                            <li class="media">
                                <div class="row">
                                    <div class="col-6 d-flex align-items-center">
                                        <div class="avatar-item">
                                            <img class='mr-3 rounded-circle img-fluid' width='<?php echo myvalidate($cfgrow['mbrmax_image_width']); ?>' height='<?php echo myvalidate($cfgrow['mbrmax_image_height']); ?>' src='<?php echo myvalidate($mbrimgstr); ?>' alt='<?php echo myvalidate($mbrstr['username']); ?>'>
                                            <?php
                                            if (strpos($mbrimgstr, 'mbr_defaultimage') !== false) {
                                                ?>
                                                <div class="avatar-badge float-left">
                                                    <a href="index.php?hal=accountcfg" title="Update" data-toggle="tooltip"><i class="fas fa-wrench text-secondary"></i></a>
                                                </div>
                                                <?php
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-6 text-right">
                                        <?php
                                        if ($mbrstr['peppylinkqrurl'] != '') {
                                            ?>
                                            <a href="javascript:;" data-href="getpeppylink.php?mdlhashy=<?php echo myvalidate($mdlhashy); ?>" class="openPopup" data-poptitle="Referral Link QR Code">
                                                <img class='mr-3 img-fluid px-md-5' src='<?php echo myvalidate($mbrstr['peppylinkqrurl']); ?>' alt='' data-toggle="tooltip" title="Copy and Download">
                                            </a>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                </div>
                            </li>
                            <li class="media">
                                <div class="media-body">
                                    <div class="text-small"><?php echo myvalidate($LANG['g_registered']); ?></div>
                                    <div class="media-title"><?php echo formatdate($mbrstr['in_date']); ?></div>
                                </div>
                            </li>
                            <li class="media">
                                <div class="media-body">
                                    <div class="text-small"><?php echo myvalidate($LANG['g_name']); ?></div>
                                    <div class="media-title"><?php echo myvalidate($mbrstr['fullname'] . ' (' . $mbrstr['email'] . ')'); ?></div>
                                </div>
                            </li>
                            <?php
                            if ($mysiteurl != '') {

                                if ($cfgtoken['ispeppy'] == 1) {
                                    ?>
                                    <li class="media">
                                        <div class="media-body">
                                            <div class="text-small">
                                                <?php echo myvalidate($LANG['g_shorturl']); ?>
                                                <?php
                                                if ($mbrstr['peppylinkplurl'] != '') {
                                                    ?>
                                                    <a href="<?php echo myvalidate($mbrstr['peppylinkplurl']); ?>" target="_blank" class="d-sm-none" data-toggle="tooltip" title="<?php echo myvalidate($mbrstr['peppylinkplurl']); ?>"><span class="text-small"><i class="fa fa-fw fa-external-link-alt"></i></span></a>
                                                    <?php
                                                }
                                                ?>
                                            </div>
                                            <div class="media-title">
                                                <?php
                                                if ($mbrstr['peppylinkplurl'] != '') {
                                                    ?>
                                                    <a class="d-none d-sm-block" href="<?php echo myvalidate($mbrstr['peppylinkplurl']); ?>" target="_blank" data-toggle="tooltip" title="<?php echo myvalidate($mbrstr['peppylinkplurl']); ?>">
                                                        <?php echo myvalidate($mbrstr['peppylinkplurl']); ?>
                                                    </a>
                                                    <div class="d-sm-none">
                                                        <div class="form-group">
                                                            <div class="input-group mb-3">
                                                                <input type="text" class="form-control form-control-sm" value="<?php echo myvalidate($mbrstr['peppylinkplurl']); ?>" id="mypeppyrefurlid" readonly>
                                                                <div class="input-group-append">
                                                                    <button class="btn btn-primary btn-sm" type="button" onclick="copyInputText('mypeppyrefurlid')" data-toggle="tooltip" title="Copy link"><i class="fa fa-copy fa-fw"></i></button>
                                                                </div>
                                                            </div>
                                                        </div>

                                                    </div>
                                                    <?php
                                                } else {
                                                    ?>
                                                    <span class="text-muted text-small">
                                                        <a href="javascript:;" data-href="getpeppylink.php?act=add&mdlhashy=<?php echo myvalidate($mdlhashy); ?>" class="btn btn-outline-warning btn-sm openPopup" data-poptitle="Referral Link QR Code" data-toggle="tooltip" title="<?php echo myvalidate($LANG['g_shortmyurl']); ?>"><i class="fas fa-link fa-spin"></i> <?php echo myvalidate($LANG['m_shortsecureurlinfo']); ?></a>
                                                    </span>
                                                    <?php
                                                }
                                                ?>

                                            </div>
                                        </div>
                                    </li>
                                    <?php
                                }
                                ?>

                                <li class="media">
                                    <div class="media-body">
                                        <div class="text-small">
                                            <?php echo myvalidate($LANG['g_default'] . ' ' . $LANG['g_refurl']); ?> <a href="<?php echo myvalidate($mysiteurl); ?>" target="_blank" class="d-sm-none" data-toggle="tooltip" title="<?php echo myvalidate($mysiteurl); ?>"><span class="text-small"><i class="fa fa-fw fa-external-link-alt"></i></span></a>
                                        </div>
                                        <div class="media-title">
                                            <a class="d-none d-sm-block" href="<?php echo myvalidate($mysiteurl); ?>" target="_blank" data-toggle="tooltip" title="<?php echo myvalidate($mysiteurl); ?>">
                                                <?php echo myvalidate($mysiteurl); ?>
                                            </a>
                                            <div class="d-sm-none">
                                                <div class="form-group">
                                                    <div class="input-group mb-3">
                                                        <input type="text" class="form-control form-control-sm" value="<?php echo myvalidate($mysiteurl); ?>" id="myrefurlid" readonly>
                                                        <div class="input-group-append">
                                                            <button class="btn btn-primary btn-sm" type="button" onclick="copyInputText('myrefurlid')" data-toggle="tooltip" title="Copy link"><i class="fa fa-copy fa-fw"></i></button>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <?php
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Membership -->
            <div class="card">
                <div class="card-header">
                    <h4><?php echo myvalidate($LANG['g_membership']); ?></h4>
                    <div class="card-header-action">
                        <?php
                        if ($mbrstr['mpid'] > 0) {
                            echo myvalidate($myplanstatus);
                        }
                        ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="summary">
                        <div class="summary-info">
                            <h4><span class="text-success"><i class="fas fa-caret-up"></i></span><?php echo myvalidate($bpprow['currencysym'] . printmoney($mytxintotal)); ?> <span class="text-danger"><i class="fas fa-caret-down"></i></span><?php echo myvalidate($bpprow['currencysym'] . printmoney($mytxouttotal)); ?> <small><span class="text-warning"><i class="far fa-pause-circle"></i></span><?php echo myvalidate($bpprow['currencysym'] . printmoney($mydiftrx)); ?></small></h4>
                            <div class="text-muted"><?php echo myvalidate($LANG['m_totalhistory'] . ': ' . $mytottrx); ?></div>
                            <h3 class="mt-2"><span class="text-info"><i class="fas fa-wallet fa-fw"></i></span><?php echo myvalidate($mytxwallet . ' ' . $bpprow['currencysym'] . $bpprow['currencycode']); ?></h3>
                            <div class="d-block mt-2">
                                <a href="index.php?hal=historylist"><?php echo myvalidate($LANG['m_detailhistory']); ?></a>
                            </div>
                        </div>
                        <div class="summary-item">
                            <a name='planreg'></a>

                            <h6><?php echo myvalidate($regmbrsince); ?></h6>
                            <ul class="list-unstyled list-unstyled-border">

                                <?php
                                $mbrpplistarr = mbrpparr($mbrstr['id']);
                                $isregppidarr = do_reginorder($mbrstr);

                                // display registered payplan only
                                $condition = " AND ppid = '{$mbrstr['mppid']}'";
                                // display all available payplan
                                $condition = " AND planstatus = '1'";
                                $nextbtndisable = '';
                                $isregbtnexist = '';
                                $condition .= " ORDER BY ppid ASC";

                                $minmbrppid = (count($mbrstr['pparr_all']) > 0) ? min($mbrstr['pparr_all']) : 0;

                                $userData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_payplans WHERE 1" . $condition . "");
                                if (count($userData) > 0) {
                                    // payplan list
                                    foreach ($userData as $val) {
                                        $mbrplanlogo = ($val['planlogo']) ? $val['planlogo'] : DEFIMG_PLAN;
                                        $mbrppstr = $mbrpplistarr[$val['ppid']];
                                        $expdatestr = ($mbrppstr['reg_expd'] > $mbrppstr['reg_date']) ? $LANG['m_expiration'] . ': ' . formatdate($mbrppstr['reg_expd']) : '';

                                        $strregfree = $bpprow['currencysym'] . printmoney($val['regfee']) . ' ' . $bpprow['currencycode'];
                                        $reglinkbtn = "<a href='index.php?hal=planreg&toppid={$val['ppid']}' class='btn btn-outline-primary btn-sm'>{$LANG['g_register']}</a>";
                                        $istrial = get_optionvals($mbrppstr['mptoken'], 'istrial');

                                        if ($val['ppid'] < $minmbrppid) {
                                            if ($frlmtdcfg['isreginorder'] == 0 || $frlmtdcfg['isxplans'] == 1) {
                                                $myplanstatus = ($unpaidtxid > 0) ? '' : $reglinkbtn;
                                            } else {
                                                $myplanstatus = ($unpaidtxid > 0) ? '' : "<i class='fas fa-ban'></i>";
                                                $strregfree = '';
                                            }
                                        } else if (in_array($val['ppid'], $mbrstr['pparr_all'])) {
                                            $myplanpay = $regppsince = '';
                                            switch ($mbrppstr['mpstatus']) {
                                                case '1':
                                                    $badge_class = "badge-success";
                                                    $badge_text = $LANG['g_active'];
                                                    $reg_date = formatdate($mbrppstr['reg_date']);
                                                    $regppsince = "<span class='text-muted'>{$LANG['m_registerppsince']}</span> {$reg_date}";
                                                    break;
                                                case '2':
                                                    $badge_class = "badge-warning";
                                                    $badge_text = $LANG['g_expire'];
                                                    break;
                                                case '3':
                                                    $badge_class = "badge-danger";
                                                    $badge_text = $LANG['g_pending'];
                                                    break;
                                                default:
                                                    $badge_class = "badge-primary";
                                                    $badge_text = "";
                                                    $myplanpay = ($mbrppstr['mppid'] == $val['ppid']) ? $myplanpaybtn : $bpprow['currencysym'] . $val['regfee'] . ' ' . $bpprow['currencycode'];
                                            }


                                            $myplanstatus = "<div class='badge {$badge_class}'>{$badge_text}</div>" . $myplanpay;
                                            $strregfree = '';
                                        } else if ($mbrppstr['mpid'] < 1 || $isregppidarr['upnextid'] <= $val['ppid']) {
                                            $myplanstatus = (in_array($val['ppid'], $isregppidarr) && $val['planstatus'] == 1 && $mbrppstr['mpstatus'] != 1) ? $reglinkbtn : "<i class='fas fa-times text-danger'></i>";

                                            if ($frlmtdcfg['isxplans'] == 1 && $mbrppstr['mpstatus'] != 1 && $isregbtnexist != 1 && $myplanstatus == '') {
                                                $myplanstatus = $reglinkbtn;
                                                $isregbtnexist = 1;
                                            }

                                            if ($unpaidtxid > 0) {
                                                $myplanstatus = '';
                                            }

                                            $regppsince = '';
                                        } else {
                                            $myplanstatus = $regppsince = $strregfree = '';
                                        }
                                        ?>

                                        <li class="media">
                                            <div>
                                                <img class="mr-3 rounded" width="50" src="<?php echo myvalidate($mbrplanlogo); ?>" alt="Membership">
                                                <?php
                                                $mbrank = ranklist($mbrppstr['mprankid']);
                                                if (intval($mbrppstr['mprankid']) > 0 && $mbrank['rklogo'] != '') {
                                                    ?>
                                                    <br />
                                                    <img class='mr-3 rounded-circle img-fluid' width='50' src='<?php echo myvalidate($mbrank['rklogo']); ?>' data-toggle="tooltip" title='<?php echo myvalidate($mbrank['rkname']); ?>' alt='<?php echo myvalidate($mbrank['rkname']); ?>'>
                                                    <?php
                                                }
                                                ?>
                                            </div>
                                            <div class="media-body">
                                                <div class="media-title"><?php echo myvalidate($val['ppname']); ?></div>
                                                <div class="text-muted text-small"><?php echo myvalidate($val['planinfo']); ?></div>

                                                <h6 class="mt-3">
                                                    <div class="media-right text-right">
                                                        <div><?php echo myvalidate($strregfree); ?></div>
                                                        <div><?php echo myvalidate($myplanstatus); ?></div>
                                                    </div>
                                                    <?php echo myvalidate($regppsince); ?>
                                                    <div>
                                                        <span class="text-small">
                                                            <?php
                                                            if ($val['expday'] > 0) {
                                                                if ($mbrppstr['reg_expd'] < $cfgrow['datestr']) {
                                                                    ?>
                                                                    <span class="badge badge-danger"><?php echo myvalidate($expdatestr); ?></span>
                                                                    <?php
                                                                } else {
                                                                    $renewHash = md5($mbrppstr['mpid'] . $mbrppstr['reg_expd']);
                                                                    ?>
                                                                    <span class="badge badge-info"><?php echo myvalidate($expdatestr); ?></span>
                                                                    <?php
                                                                    if ($cfgtoken['isadvrenew'] == '1' && intval($bpparr[$mbrppstr['mppid']]['expday']) > 0 && $unpaidtxid < 1 && $mbrppstr['reg_expd'] > $cfgrow['datestr']) {
                                                                        ?>
                                                                        <a href="javascript:;" data-href="index.php?hal=dashboard&renewHash=<?php echo myvalidate($renewHash); ?>&renewId=<?php echo myvalidate($mbrppstr['mpid']); ?>&redir=planpay" class="btn btn-sm btn-outline-primary bootboxconfirm" data-poptitle="<?php echo myvalidate($LANG['m_currentexp']); ?> <?php echo formatdate($mbrppstr['reg_expd']); ?>" data-popmsg="<?php echo myvalidate($LANG['m_advancerenewconfirm']); ?>" data-toggle="tooltip" title="<?php echo myvalidate($LANG['g_advancerenew']); ?>"><?php echo myvalidate($LANG['g_renewnow']); ?> <i class="fa fa-fw fa-forward"></i></a>
                                                                        <?php
                                                                    }
                                                                }
                                                                if ($istrial > 0) {
                                                                    ?>
                                                                    <span class="badge badge-danger">Trial</span>
                                                                    <?php
                                                                }
                                                            }
                                                            ?>
                                                        </span>
                                                    </div>
                                                </h6>

                                            </div>
                                        </li>
                                        <?php
                                    }
                                }
                                ?>

                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12 col-12 col-sm-12">
            <?php
            if ($mbrstr['idspr'] > 0) {
                ?>
                <div class="card">
                    <div class="card-header">
                        <h4><?php echo myvalidate($LANG['g_mysponsor']); ?></h4>
                        <div class="card-header-action">
                            <?php echo myvalidate($sprstatusstr); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled list-unstyled-border">
                            <li class='media'>
                                <img class='mr-3 rounded-circle' width='48' src='<?php echo myvalidate($sprimgstr); ?>' alt='avatar'>
                                <div class='media-body'>
                                    <div class='float-right text-small text-success'></div>
                                    <div class='media-title'><?php echo myvalidate($sprstr['username']); ?></div>
                                    <span class='text-small text-muted'>
                                        <div><?php echo myvalidate($sprstr['fullname']); ?></div>
                                        <div data-toggle='tooltip' title='<?php echo myvalidate($sprstr['email']); ?>'><i class="fa fa-fw fa-envelope"></i> <?php echo myvalidate($spremailstr); ?></div>
                                        <div><i class="fa fa-fw fa-mobile-alt"></i> <?php echo myvalidate($sprphonestr); ?></div>
                                        <div><?php echo myvalidate($sprcountrystr); ?></div>
                                    </span>
                                </div>
                            </li>
                        </ul>
                        <div><?php echo myvalidate($spraboutstr); ?></div>
                    </div>
                </div>
                <?php
            }
            ?>

            <div class="card card-statistic-1">
                <div class="card-icon bg-success">
                    <i class="far fa-gem"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4><?php echo myvalidate($LANG['g_point']); ?></h4>
                    </div>
                    <div class="card-body">
                        <?php
                        echo myvalidate($mbrstr['epoint']);

                        // point to cash
                        if ($bptoken['pointvaluetocash'] > 0) {
                            ?>
                            <a href="javascript:;" data-href="convertpointcash.php?mdlhashy=<?php echo myvalidate($mdlhashy); ?>" data-poptitle="<i class='fa fa-fw fa-wallet'></i> <?php echo myvalidate($LANG['g_convertpointcash']); ?>" class="btn btn-primary btn-sm openPopup" data-toggle="tooltip" title="<?php echo myvalidate($LANG['g_convertedtocash']); ?>"><i class="fa fa-fw fa-building"></i></a>
                            <?php
                        }
                        // point to reward
                        if ($cfgtoken['ismbrpointrwd'] == '1') {
                            ?>
                            <a href="javascript:;" data-href="exchpointrwd.php?mdlhashy=<?php echo myvalidate($mdlhashy); ?>" data-poptitle="<i class='fa fa-fw fa-gift'></i> <?php echo myvalidate($LANG['g_pointexchangerwd']); ?>" class="btn btn-primary btn-sm openPopup" data-toggle="tooltip" title="<?php echo myvalidate($LANG['g_pointexchangerwd']); ?>"><i class="fa fa-fw fa-gift"></i></a>
                            <?php
                            // get member qualified for any reward or not
                            $condition = " ORDER BY prminpoints ASC ";
                            $minpointData = $db->getRecFrmQry("SELECT prminpoints FROM " . DB_TBLPREFIX . "_pointrwds WHERE 1 " . $condition . "");
                            $isgetrwd = ($minpointData[0]['prminpoints'] != '' && $minpointData[0]['prminpoints'] <= $mbrstr['epoint']) ? "<span class='ml-2 beep'></span>" : '';
                            echo myvalidate($isgetrwd);
                        }
                        ?>
                    </div>
                    <?php
                    $condition = " AND pofromid = '{$mbrstr['id']}' AND potoid = '0'";
                    $pointData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_points WHERE 1 " . $condition . " ORDER BY poid DESC LIMIT 3");
                    if (count($pointData) > 0) {
                        $pointrwdlist = '';
                        foreach ($pointData as $val) {
                            $popointstr = "<span class='text-danger'>{$val['popoint']}</span>";
                            $pomemostr = str_replace('Exchange', $popointstr, $val['pomemo']);
                            $postatusico = ($val['postatus'] == '1') ? "<span class='float-right' data-toggle='tooltip' title='{$LANG['g_complete']}'><i class='far fa-fw fa-check-circle text-success'></i></span>" : "<span class='float-right' data-toggle='tooltip' title='{$LANG['g_inprocess']}'><i class='far fa-fw fa-clock text-warning'></i></span>";
                            $pointrwdlist .= "<div>" . formatdate($val['podatetm']) . " <i class='fas fa-fw fa-gem text-small'></i> {$pomemostr}{$postatusico}</div>";
                        }
                        ?>
                        <div class="card-footer text-small mt-4 border-top bg-whitesmoke">
                            <strong>Recent 3 Point Task:</strong>
                            <?php echo myvalidate($pointrwdlist); ?>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>

            <?php
            if ($stctoken['stcismbrdecodeitem'] != '1' || $stctoken['stcismbrdecodeplan'] == '1') {
                ?>
                <div class="card">
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4><?php echo myvalidate($LANG['g_epindecode']); ?></h4>
                        </div>
                        <div class="card-body">
                            <form method="post" action="decodepin.php" id="doepinform">
                                <div class="form-row">
                                    <div class="text-muted text-small"><?php echo myvalidate($LANG['g_epinusage']) ?></div>
                                    <textarea class="form-control rowsize-sm" name="epcodelist" id="epcodelist" placeholder="List of available <?php echo myvalidate($LANG['g_epinlist']); ?>"><?php echo myvalidate($epcodelist) ?></textarea>
                                    <div class="text-md-right mt-2">
                                        <button type="reset" class="btn btn-warning"><i class="fa fa-fw fa-times"></i> Reset</button>
                                        <button type="submit" class="btn btn-primary bootboxformconfirm" data-form="doepinform" data-poptitle="<?php echo myvalidate($LANG['g_epindecode']) ?>" data-popmsg="<div class='mb-2'><?php echo myvalidate($LANG['g_epindecodeconfirm']); ?></div><div class='text-danger'><?php echo myvalidate($LANG['g_epindecodenoundo']); ?></div>">
                                            <i class="fa fa-fw fa-check"></i> Submit
                                        </button>
                                    </div>
                                </div>
                                <input type="hidden" name="redir" value="orderlist">
                                <input type="hidden" name="mdlhashy" value="<?php echo myvalidate($mdlhashy); ?>">
                            </form>
                        </div>
                    </div>
                </div>
                <?php
            }
            ?>

            <div class="card">
                <div class="card-header">
                    <h4><?php echo myvalidate($LANG['g_recentref']); ?></h4>
                    <div class="card-header-action">
                        <a href="index.php?hal=userlist" class="btn btn-primary" data-toggle="tooltip" title="<?php echo myvalidate($LANG['g_viewall']); ?>"><i class="fa fa-ellipsis-h"></i></a>
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
<script src="../assets/js/ucpchart.js"></script>
<script src="../assets/js/countdown.min.js"></script>

