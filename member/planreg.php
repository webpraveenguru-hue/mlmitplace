<?php
if (!defined('OK_LOADME')) {
    die('o o p s !');
}

$didId = intval($FORM['toppid']);
$bpprow = get_planarr($didId);
$planimg = $bpprow['planimg'];

$isregppidarr = do_reginorder($mbrstr);
if ($frlmtdcfg['isreginorder'] == 0 || in_array($didId, $isregppidarr)) {
    $mbrpplistarr = mbrpparr($mbrstr['id']);
    $mbrppstr = $mbrpplistarr[$didId];
} else {
    $mbrppstr = $mbrstr;
}

$newsprstr = '';
if ($FORM['myrefun'] != '') {
    // custom referrer username
    $refstr = getmbrinfo($FORM['myrefun'], 'username');
    $refidmbr = $refstr['id'];
} else {
    // default referrer username from existing referrer or from session tracking id
    $refstr = getmbrinfo($mbrstr['idref']);
    $refidmbr = ($refstr['mpid'] > 0) ? $refstr['id'] : $sesref['id'];

    if ($refidmbr < 1) {
        $mbrtokenarr = get_optionvals($mbrstr['mbrtoken']);
        $refidmbr = intval($mbrtokenarr['refbyidmbr']);
        $refstr = getmbrinfo($refidmbr);
    }
}

// disable self referring
if ($refstr['username'] == $mbrstr['username']) {
    $refidmbr = 0;
}

$sesref = getmbrinfo($refidmbr, '', '', $bpprow['ppid']);
$refmpid = $sesref['mpid'];

$ceknewmpid = getmpidflow($refmpid, $didId, $mbrstr);
if ($ceknewmpid != $refmpid) {
    $sesnewref = getmbrinfo('', '', $ceknewmpid);
    $newsprstr = sprintf($LANG['g_referrertosponsor'], $sesref['username'], $sesnewref['username']);
    $idref = $sesnewref['id'];
}

if ($bpprow['planstatus'] == 1 && $FORM['doid'] > 0) {

    if ($frlmtdcfg['isxplans'] == 1) {
        //--- register to new plan (register with multiple plans)
        $resultarr = regmbrplans($mbrstr, $refidmbr, $bpprow['ppid']);
    } else {
        //--- update from current plan to new plan (one plan at a time)
        $resultarr = regmbrplans($mbrstr, $refidmbr, $bpprow['ppid'], $mbrstr['mpid']);
    }
    redirpageto('index.php?hal=planpay&didId=' . $bpprow['ppid']);
    exit;
}
?>

<div class="section-header">
    <h1><i class="fa fa-fw fa-unlock-alt"></i> <?php echo myvalidate($LANG['m_planreg']); ?></h1>
</div>

<div class="section-body">
    <div class="row">
        <div class="col-md-12">
            <article class="article article-style-b">
                <div class="article-header">
                    <div class="article-image" data-background="<?php echo myvalidate($planimg); ?>">
                    </div>
                    <div class="article-badge">
                        <span class="article-badge-item bg-danger">
                            <?php echo myvalidate($bpprow['currencysym'] . $bpprow['regfee'] . ' ' . $bpprow['currencycode']); ?>
                        </span>
                        <?php
                        if ($sesref['username'] && $mbrstr['username'] != $sesref['username']) {
                            ?>
                            <span class="article-badge-item bg-warning">
                                <?php echo ($mbrppstr['mpid'] > 0) ? myvalidate($LANG['g_referrerby'] . ' ' . $mbrstr['username']) : myvalidate($LANG['g_referrerby'] . ' ' . $sesref['username']); ?>
                            </span>
                            <?php
                        }
                        ?>
                    </div>
                </div>
                <div class="article-details">
                    <div class="article-title">
                        <h4><?php echo myvalidate($bpprow['ppname']); ?></h4>
                    </div>
                    <p><?php echo myvalidate($bpprow['planinfo']); ?></p>
                    <div class="article-cta">
                        <?php echo myvalidate($newsprstr); ?>

                        <?php
                        if ($mbrppstr['idmbr'] == $mbrstr['id'] && $mbrppstr['mpstatus'] > 0) {
                            ?>
                            <span class="badge badge-secondary">
                                REGISTERED
                            </span>
                            <?php
                            if ($mbrppstr['mpstatus'] == 1) {
                                ?>
                                <span class="badge badge-success">
                                    ACTIVE <i class="fas fa-fw fa-check"></i>
                                </span>
                                <?php
                            } else {
                                ?>
                                <span class="badge badge-warning">
                                    INACTIVE <i class="fas fa-fw fa-exclamation"></i>
                                </span>
                                <?php
                            }
                            ?>
                            <?php
                        } elseif ($mbrppstr['idmbr'] == $mbrstr['id'] && $mbrppstr['mpstatus'] == 0) {
                            ?>
                            <a href="index.php?hal=planpay&doid=<?php echo myvalidate($bpprow['ppid']); ?>" class="btn btn-lg btn-danger text-uppercase"><?php echo myvalidate($LANG['m_makepayment']); ?> <i class="fas fa-fw fa-long-arrow-alt-right"></i></a>
                            <?php
                        } else {
                            if ($bpprow['planstatus'] == 1) {
                                $refbystr = ($sesref['username']) ? "{$LANG['m_yourdefref']} <strong>{$sesref['username']}</strong>" : '';
                                $refbystr .= ($sesnewref['username']) ? "{$LANG['m_yournewspr']} <strong>{$sesnewref['username']}</strong>" : '';
                                if ($sesref['username'] == '' || $sesref['username'] == $mbrstr['username']) {
                                    $refbystr = '';
                                }
                                ?>
                                <div class="form-group">
                                    <form method="GET" id="doregform">
                                        <div class="input-group">
                                            <?php
                                            if ($cfgtoken['ismanspruname'] == 1 && ($mbrstr['mpstatus'] < 1 || $sesref['username'] == '' || $frlmtdcfg['isregallrefs'] == 1)) {
                                                ?>
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><?php echo myvalidate($LANG['m_refusername']); ?></span>
                                                </div>
                                                <input type="text" class="form-control" name="myrefun" placeholder="<?php echo myvalidate($LANG['m_enternewref']); ?>">
                                                <div class="input-group-append">
                                                    <?php
                                                } else {
                                                    ?>
                                                    <div class="text-right">
                                                        <?php
                                                    }
                                                    $isoneplanstr = ($cfgtoken['isonceplan'] != '1') ? "<div class='text-small text-warning'>{$LANG['m_confirmregnoreff']}</div>" : '';

                                                    $ppnowsql = $db->getAllRecords(DB_TBLPREFIX . '_payplans', '*', ' AND ppid = "' . $mbrstr['mppid'] . '"');
                                                    $ppnowrow = $ppnowsql[0];

                                                    $isalreadyregstr = ($mbrstr['mpstatus'] == '1') ? "{$LANG['m_confirmalreadyreg']} <strong>{$ppnowrow['ppname']} {$bpprow['currencysym']}{$mbrstr['reg_fee']}</strong>. " : '';
                                                    ?>
                                                    <button class="btn btn-primary bootboxformconfirm" data-form="doregform" data-poptitle="<?php echo myvalidate($bpprow['ppname']); ?> - <?php echo myvalidate($bpprow['currencysym'] . $bpprow['regfee'] . ' ' . $bpprow['currencycode']); ?>" data-popmsg="<div class='text-muted'><?php echo myvalidate($refbystr . $isoneplanstr); ?></div><div class='mt-2 mb-4'><?php echo myvalidate($isalreadyregstr . $LANG['m_confirmreg']); ?></div><div class='text-danger'><?php echo myvalidate($LANG['m_confirmregnote']); ?></div>" type="submit">
                                                        <?php echo myvalidate($LANG['g_register']); ?> <i class="fas fa-fw fa-long-arrow-alt-right"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <input type="hidden" name="hal" value="planreg">
                                            <input type="hidden" name="doid" value="<?php echo myvalidate($bpprow['ppid']); ?>">
                                            <input type="hidden" name="toppid" value="<?php echo myvalidate($bpprow['ppid']); ?>">
                                        </div>
                                    </form>
                                    <?php
                                } else {
                                    ?>
                                    <span class="badge badge-danger text-uppercase"><i class="fa fa-fw fa-times"></i> <?php echo myvalidate($LANG['m_regdisable']); ?></span>
                                    <?php
                                }
                            }
                            ?>

                        </div>
                    </div>
            </article>

        </div>
    </div>
</div>
