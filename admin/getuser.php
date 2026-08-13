<?php
if (!defined('OK_LOADME')) {
    die('o o p s !');
}

if (isset($FORM['setdef']) == '1') {
    $getId = intval($FORM['getId']);
    $getMpid = intval($FORM['getMpid']);

    $rowstr = getmbrinfo($getId, '', $getMpid);

    $data = ['isdefault' => '0'];
    $db->update(DB_TBLPREFIX . '_mbrplans', $data, array('idmbr' => $getId, 'mppid' => $rowstr['mppid']));
    $data = ['isdefault' => '1'];
    $db->update(DB_TBLPREFIX . '_mbrplans', $data, array('mpid' => $getMpid, 'mppid' => $rowstr['mppid']));

    redirpageto("index.php?hal=getuser&getId={$getId}&getMpid={$getMpid}");
    exit;
}

if (isset($FORM['getId']) && $FORM['getId'] > 0) {
    $getId = intval($FORM['getId']);
    $getMpid = intval($FORM['getMpid']);

    // Get member details
    $rowstr = getmbrinfo($getId, '', $getMpid);
    if ($rowstr['id'] < 1) {
        redirpageto('index.php?hal=userlist?err');
        exit;
    }

    // remove shortened
    $hashunshort = md5(INSTALL_HASH . $rowstr['peppylinkplurl'] . $rowstr['username'] . date("YmdH"));
    if (isset($FORM['unshortlink']) && $FORM['unshortlink'] == $rowstr['peppylinkpllid'] && $FORM['hashunshort'] == $hashunshort) {
        del_peppyinfo($rowstr['peppylinkpllid'], 'pllid');
        redirpageto("index.php?hal=getuser&getId={$getId}&getMpid={$getMpid}");
        exit;
    }

    $bpprow = get_planarr($rowstr['mppid']);

    // get transaction details
    $unpaidtxid = get_unpaidtxid($rowstr);
    $trxstr = get_txinfo($unpaidtxid);

    $mbr_sosmed = get_optionvals($rowstr['mbr_sosmed']);
    $mbr_twitter = $mbr_sosmed['mbr_twitter'];
    $mbr_facebook = $mbr_sosmed['mbr_facebook'];

    $status_arr = array('0' => 'Inactive', '1' => 'Active', '2' => 'Limited', '3' => 'Pending');
    $statusstr = select_opt($status_arr, $rowstr['mbrstatus'], 1);

    $mpstatus_arr = array('0' => 'Inactive', '1' => 'Active', '2' => 'Expire', '3' => 'Pending');

    $mbr_imagestr = ($rowstr['mbr_image']) ? $rowstr['mbr_image'] : $cfgrow['mbr_defaultimage'];

    $countrystr = select_opt($country_array, $rowstr['country'], 1);
    $countrystr = strtolower($countrystr ?? '');
    $countrystr = ucwords($countrystr ?? '');

    $mbrsite_catstr = select_opt($webcategory_array, $rowstr['mbrsite_cat'], 1);

    $showsite_cekicon = ($rowstr['showsite'] == 1) ? '<i class="fa fa-fw fa-check-circle text-success"></i>' : '<i class="fa fa-fw fa-times-circle text-danger"></i>';
    $optinme_cekstr = checkbox_opt($rowstr['optinme'], 1, 1);
    $isvendor_cekstr = checkbox_opt($rowstr['isvendor'], 1, 1);

    //item tot, sales tot, sales value, vendor fee value
    $condition = " AND itidmbr = '{$rowstr['id']}' ";
    $itsltxrow = $db->getAllRecords(DB_TBLPREFIX . '_items '
            . 'LEFT JOIN ' . DB_TBLPREFIX . '_sales ON itid = slitid '
            . 'LEFT JOIN ' . DB_TBLPREFIX . '_transactions ON itid = txitid', ""
            . "COUNT(DISTINCT(itid)) as totitem, "
            . "COUNT(DISTINCT(slid)) as totsales "
            . "", $condition);
    $vendoritemtot = $itsltxrow[0]['totitem'];
    $vendorsalestot = $itsltxrow[0]['totsales'];

    $condition = " AND txtoken LIKE '%|WLVENDOR:{$rowstr['id']}|%' ";
    $itsltxrow = $db->getAllRecords(DB_TBLPREFIX . '_transactions ', ""
            . "SUM(CASE WHEN txtoken LIKE '%|TXVENDOR:EARN|%' THEN txamount ELSE 0 END) as salesval, "
            . "SUM(CASE WHEN txtoken LIKE '%|TXVENDOR:FEE|%' THEN txamount ELSE 0 END) as feeval "
            . "", $condition);
    $vendorsalesvalue = $bpprow['currencysym'] . sprintf("%0.2f", $itsltxrow[0]['salesval']);
    $vendorfeevalue = $bpprow['currencysym'] . sprintf("%0.2f", $itsltxrow[0]['feeval']);

    $statusactstr = $renewfeeico = '';
    if ($rowstr['mpid'] < 1) {
        $markstatus = "<span class='alert alert-dark'>REGISTER ONLY</span>";
    } else {
        if ($rowstr['mpstatus'] == 1) {
            $markstatus = "<span class='alert alert-success text-uppercase'>{$mpstatus_arr[$rowstr['mpstatus']]}</span>";
        } else {
            $markstatus = "<span class='alert alert-secondary text-uppercase'>{$mpstatus_arr[$rowstr['mpstatus']]}</span>";
            $txmpid = $trxstr['txid'] . '-' . $rowstr['mpid'];

            $mbr_fee = (strpos($trxstr['txtoken'] ?? '', '|RENEW:') !== false) ? $rowstr['renew_fee'] : $rowstr['reg_fee'];
            $tottestpay = ($trxstr['txamount'] > 0) ? $trxstr['txamount'] : $mbr_fee;

            $renewfeeico = ($tottestpay != $rowstr['reg_fee']) ? "<i class='far fa-question-circle fa-fw text-light' data-toggle='tooltip' title='Renewal fee " . $bpprow['currencysym'] . $tottestpay . ' ' . $bpprow['currencycode'] . "'></i>" : '';

            $paybatch = strtoupper(date("DmdH-is")) . $rowstr['mpid'];
            if ($rowstr['mpstatus'] == 0 || $rowstr['mpstatus'] == 2) {
                $statusactstr = <<<INI_HTML
                <form method="post" action="../common/sandbox.php" id="dopayform">
                    <input type="hidden" name="sb_type" value="payreg">
                    <input type="hidden" name="sb_mpstatus" value="{$rowstr['mpstatus']}">
                    <input type="hidden" name="sb_txmpid" value="{$txmpid}">
                    <input type="hidden" name="sb_amount" value="{$tottestpay}">
                    <input type="hidden" name="sb_batch" value="{$paybatch}">
                    <input type="hidden" name="sb_label" value="other">
                    <input type="hidden" name="sb_success" value="-HTTPREF-">
                    <button type="submit" name="dopay" value="1" id="dopay" class="btn btn-sm btn-danger mt-4 bootboxformconfirm" data-form="dopayform" data-poptitle="Username: {$rowstr['username']}" data-popmsg="Are you sure want to approve this member?">
                        Manual Approval
                    </button>
                </form>
INI_HTML;
            }
        }
    }

    if ($rowstr['mbrstatus'] != 1) {
        $markstatus .= "<span class='alert alert-danger' data-toggle='tooltip' title='Account status is not Active!'><i class='fa fa-fw fa-exclamation-triangle'></i></span>";
    }

    if (strpos($rowstr['mbrsite_img'] ?? '', 'imgsrc') === false || !file_exists($rowstr['mbrsite_img'])) {
        $imgdata = ($rowstr['mbrsite_img'] == '') ? DEFIMG_SITE : $rowstr['mbrsite_img'];
        $siteimgstr = "<img src='{$imgdata}' width='100%' />";
    } else {
        $imgdata = file_get_contents($rowstr['mbrsite_img']);
        $siteimgstr = "<img src='data:image/jpeg;base64,{$imgdata}' width='100%' />";
    }

    $condition = " AND sprlist LIKE '%:{$rowstr['mpid']}|%'";
    $row = $db->getAllRecords(DB_TBLPREFIX . '_mbrplans', 'COUNT(*) as totref', $condition);
    $myreftotal = $row[0]['totref'];

    $backpage = ($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : "index.php?hal=userlist";

    $rowrefstr = getmbrinfo($rowstr['idref']);
    $rowsprstr = getmbrinfo($rowstr['idspr']);
} else {
    redirpageto('index.php?hal=dashboard');
    exit;
}

$planstr = get_sysplanstr($rowstr);
$outplanmark = ($rowstr['mpwidth'] != $bpprow['maxwidth'] || $rowstr['mpdepth'] != $bpprow['maxdepth']) ? "<i class='fa fa-exclamation-circle fa-fw text-danger' data-toggle='tooltip' title='{$LANG['a_outplanmark']}'></i>" : '';
$outplanreg = ($rowstr['reg_fee'] != $bpprow['regfee']) ? "<i class='fa fa-exclamation-circle fa-fw text-warning' data-toggle='tooltip' title='{$LANG['a_outplanreg']}'></i>" : '';

$mbrank = ranklist($rowstr['mprankid']);

$mbroptionlist = '';
$condition = "AND idmbr = '{$rowstr['id']}'";
$userData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_mbrplans WHERE 1 " . $condition . "");
$totuserplans = count($userData);
foreach ($userData as $val) {
    if ($val['mpstatus'] == '0') {
        $colormarkedstatus = ' text-muted';
    } else if ($val['mpstatus'] == '2') {
        $colormarkedstatus = ' text-warning';
    } else if ($val['mpstatus'] == '3') {
        $colormarkedstatus = ' text-danger';
    } else {
        $colormarkedstatus = '';
    }
    $mbrppname = $bpparr[$val['mppid']]['ppname'];
    $ismarkdefault = ($val['isdefault'] == '1') ? '<i class="fa fa-fw fa-check-circle text-primary"></i>' : '<i class="fa fa-fw fa-circle text-muted"></i>';
    $ismarkedselect = ($FORM['getMpid'] == $val['mpid']) ? ' &#10003;' : '';
    $mbroptionlist .= "<a class='dropdown-item text-monospace{$colormarkedstatus}' href='index.php?hal=getuser&getId={$val['idmbr']}&getMpid={$val['mpid']}'>{$ismarkdefault} {$mbrppname} #{$val['mpid']}{$ismarkedselect}</a>";
}
$mbrppname = $bpparr[$rowstr['mppid']]['ppname'];

// set as default or not
if ($rowstr['isdefault'] == '1' || $totuserplans <= 1) {
    $isdefcecked = " checked";
    $onclickdef = " onclick=\"location.href = 'index.php?hal=getuser&setdef=1&getId={$FORM['getId']}&getMpid={$FORM['getMpid']}'\"";
} else {
    $isdefcecked = "";
    $onclickdef = ' style="cursor: pointer;"' . " onclick=\"location.href = 'index.php?hal=getuser&setdef=1&getId={$FORM['getId']}&getMpid={$FORM['getMpid']}'\"";
}
?>

<div class="section-header">
    <h1><i class="fa fa-fw fa-user-circle"></i> <?php echo myvalidate($LANG['g_memberprofile']); ?></h1>
</div>

<div class="section-body">
    <?php
    if ($totuserplans > 1) {
        ?>
        <div class="row">
            <div class="col-md-6">
                <div class="btn-group">
                    <button type="button" class="btn btn-info" data-toggle="tooltip" title="Account by <?php echo myvalidate($rowstr['username']); ?>"><i class="fa fa-fw fa-user-tie"></i> <?php echo myvalidate($rowstr['username'] . " - {$mbrppname} #" . $rowstr['mpid']); ?></button>
                    <button type="button" class="btn btn-info dropdown-toggle dropdown-toggle-split" data-toggle="dropdown">
                        <span class="sr-only">Status</span>
                    </button>
                    <div class="dropdown-menu">
                        <?php echo myvalidate($mbroptionlist); ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="custom-switch mt-2">
                        <input type="checkbox" name="custom-switch-checkbox" class="custom-switch-input"<?php echo myvalidate($isdefcecked); ?>>
                        <span class="custom-switch-indicator"<?php echo myvalidate($onclickdef); ?>></span>
                        <span class="custom-switch-description">Update as default member.</span>
                    </label>
                </div>
            </div>
        </div>
        <?php
    }
    ?>

    <?php
    if ($rowstr['mpid'] > 0) {
        ?>
        <div class="row mt-sm-4">
            <div class="col-12 col-md-12 col-lg-6">
                <div class="card d-none d-md-block">
                    <div class="card-header">
                        <h4>Referrer</h4>
                        <div class="card-header-action">
                            <?php
                            echo ($rowrefstr['id'] > 0) ? badgembrplanstatus($rowrefstr['mbrstatus'], $rowrefstr['mpstatus'], $bpparr[$rowstr['mppid']]['ppname'], $rowrefstr['mbr_image']) : '';
                            if ($rowrefstr['mbrbiolink']) {
                                ?>
                                <a class="btn btn-icon btn-info" href="<?php echo myvalidate($rowrefstr['mbrbiolink']); ?>" data-toggle="tooltip" title="Visit member Bio Page" target="_blank"><i class="fas fa-user-tie"></i></a>
                                <?php
                            }
                            ?>
                            <a data-collapse="#ref-collapse" class="btn btn-icon btn-secondary" href="#"><i class="fas fa-minus"></i></a>
                        </div>
                    </div>
                    <div class="collapse show" id="ref-collapse">
                        <div class="card-body">
                            <div class="row">
                                <div class="form-group col-md-5 col-12">
                                    <label>Username</label>
                                    <h6><?php echo myvalidate($rowrefstr['username']); ?></h6>
                                </div>
                                <div class="form-group col-md-7 col-12">
                                    <label><?php echo myvalidate($LANG['g_name']); ?></label>
                                    <h6><?php echo myvalidate($rowrefstr['firstname'] . ' ' . $rowrefstr['lastname']); ?></h6>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-12">
                                    <label>Email</label>
                                    <h6><?php echo maskmail($rowrefstr['email']); ?></h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-12 col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Sponsor</h4>
                        <div class="card-header-action">
                            <?php
                            if ($rowsprstr['id'] > 0) {
                                ?>
                                <a href="javascript:;" data-href="getsprlist.php?redir=getuser&mdlhasher=<?php echo myvalidate($mdlhasher); ?>&mpId=<?php echo myvalidate($rowstr['mpid']); ?>" data-poptitle="<i class='fa fa-fw fa-users'></i> Sponsor List for <?php echo myvalidate($rowstr['username']); ?>" class="openPopup btn btn-icon btn-info mr-2" data-toggle="tooltip" title="Sponsor List for <?php echo myvalidate($rowstr['username']); ?>"><i class="fas fa-users"></i></a>
                                <?php
                                echo badgembrplanstatus($rowsprstr['mbrstatus'], $rowsprstr['mpstatus'], $bpparr[$rowstr['mppid']]['ppname'], $rowsprstr['mbr_image']);
                            }
                            if ($rowsprstr['mbrbiolink']) {
                                ?>
                                <a class="btn btn-icon btn-info" href="<?php echo myvalidate($rowsprstr['mbrbiolink']); ?>" data-toggle="tooltip" title="Visit member Bio Page" target="_blank"><i class="fas fa-user-tie"></i></a>
                                <?php
                            }
                            ?>
                            <a data-collapse="#spr-collapse" class="btn btn-icon btn-secondary" href="#"><i class="fas fa-minus"></i></a>
                        </div>
                    </div>
                    <div class="collapse show" id="spr-collapse">
                        <div class="card-body">
                            <div class="row">
                                <div class="form-group col-md-5 col-12">
                                    <label>Username</label>
                                    <h6><?php echo myvalidate($rowsprstr['username']); ?></h6>
                                </div>
                                <div class="form-group col-md-7 col-12">
                                    <label><?php echo myvalidate($LANG['g_name']); ?></label>
                                    <h6><?php echo myvalidate($rowsprstr['firstname'] . ' ' . $rowsprstr['lastname']); ?></h6>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-12">
                                    <label>Email</label>
                                    <h6><?php echo maskmail($rowsprstr['email']); ?></h6>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    ?>

    <div class="row mt-sm-2">
        <div class="col-12 col-md-12 col-lg-6">
            <?php
            if ($rowstr['mpid'] > 0) {
                ?>
                <div class="card profile-widget">
                    <div class="profile-widget-header">
                        <img alt="image" src="<?php echo myvalidate($mbr_imagestr); ?>" class="rounded-circle img-thumbnail profile-widget-picture">
                        <div class="profile-widget-items">
                            <div class="profile-widget-item">
                                <div class="profile-widget-item-label"><?php echo myvalidate($LANG['g_referrals']); ?></div>
                                <div class="profile-widget-item-value"><?php echo myvalidate($myreftotal); ?></div>
                            </div>
                            <div class="profile-widget-item">
                                <div class="profile-widget-item-label">Wallet</div>
                                <div class="profile-widget-item-value"><?php echo myvalidate($bpprow['currencysym'] . printmoney($rowstr['ewallet'])); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="profile-widget-description">
                        <div class="profile-widget-name">
                            <div class="text-muted d-inline text-small"><?php echo myvalidate($LANG['g_username']); ?>:</div> <?php echo myvalidate($rowstr['username'] . ' ' . $rowstr['kyverifiedimg']); ?> <div class="text-muted d-inline font-weight-normal text-small"><div class="slash"></div><?php echo formatdate($rowstr['in_date'], 'dt'); ?></div>
                            <a href="<?php echo myvalidate($cfgrow['site_url'] . '/' . UIDFOLDER_NAME . '/' . $rowstr['username']); ?>" target="_blank" data-toggle="tooltip" title="<?php echo myvalidate($cfgrow['site_url']) . '/' . UIDFOLDER_NAME . '/' . $rowstr['username']; ?>"><i class="fas fa-fw fa-link"></i></a>
                            <?php
                            if ($rowstr['peppylinkplurl']) {
                                ?>
                                <a href="<?php echo myvalidate($rowstr['peppylinkplurl']); ?>" target="_blank" data-toggle="tooltip" title="<?php echo myvalidate($rowstr['peppylinkplurl']); ?>"><i class="fas fa-fw fa-shield-alt text-info"></i></a>
                                <a href="javascript:;" data-href="index.php?hal=getuser&getId=<?php echo myvalidate($FORM['getId']); ?>&getMpid=<?php echo myvalidate($FORM['getMpid']); ?>&unshortlink=<?php echo myvalidate($rowstr['peppylinkpllid']); ?>&hashunshort=<?php echo myvalidate($hashunshort); ?>" class="bootboxconfirm" data-poptitle="Shortened Referral Link: <?php echo myvalidate($rowstr['username']); ?>" data-popmsg="<div><strong><?php echo myvalidate($rowstr['peppylinkplurl']); ?></strong></div><div class='mt-2 text-danger'>Are you sure want to delete this shortened link and QR Code?</div>" data-toggle="tooltip" title="Remove Shortened"><i class="fas fa-fw fa-trash-alt text-danger"></i></a>
                                <?php
                            }
                            ?>

                        </div>
                        <h6 class="text-small"><span class="badge badge-light">Payplan: <?php echo myvalidate($planstr . ' ' . $outplanmark); ?></span></h6>
                        <h6 class="text-small"><span class="badge badge-light">
                                <?php
                                $is_plansubscr = is_plansubscr($rowstr['mppid']);
                                if ($rowstr['reg_date'] < $rowstr['reg_expd'] && $is_plansubscr) {
                                    echo myvalidate($LANG['g_register']) . ': ' . formatdate($rowstr['reg_date']) . ' / ' . $LANG['g_expire'] . ': ' . formatdate($rowstr['reg_expd']);
                                }
                                ?>
                            </span></h6>
                        <h6 class="text-small"><span class="badge badge-light">Last seen: <?php echo ($rowstr['log_date'] > '2002-02-02') ? formatdate($rowstr['log_date'], 'dt') : formatdate($rowstr['in_date'], 'dt'); ?></span></h6>
                        <blockquote class="mt-4"><?php echo base64_decode($rowstr['mbr_intro'] ?? ''); ?></blockquote>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-12 col-md-4">
                        <a href="javascript:;" onclick="location.href = 'index.php?hal=historylist&dohal=filter&doval=<?php echo myvalidate($rowstr['id']); ?>&dompid=<?php echo myvalidate($rowstr['mpid']); ?>'" class="btn btn-sm btn-block btn-outline-info" data-toggle="tooltip" title="<?php echo myvalidate($LANG['g_historylist']); ?>"><i class="fa fa-fw fa-vote-yea"></i> History</a>
                    </div>
                    <div class="col-sm-12 col-md-4">
                        <a href="javascript:;" onclick="location.href = 'index.php?hal=userlist&dohal=filter&doval=<?php echo myvalidate($rowstr['id']); ?>&dompid=<?php echo myvalidate($rowstr['mpid']); ?>'" class="btn btn-sm btn-block btn-outline-info" data-toggle="tooltip" title="<?php echo myvalidate($LANG['g_referrallist']); ?>"><i class="fa fa-fw fa-user-friends"></i> Referral</a>
                    </div>
                    <div class="col-sm-12 col-md-4">
                        <a href="javascript:;" onclick="location.href = 'index.php?hal=genealogylist&loadId=<?php echo myvalidate($rowstr['mpid']); ?>'" class="btn btn-sm btn-block btn-outline-info" data-toggle="tooltip" title="Genealogy Structure"><i class="fa fa-fw fa-sitemap"></i> Structure</a>
                    </div>
                </div>
                <div class="d-block d-sm-none">
                    &nbsp;
                </div>
                <?php
            }
            ?>

            <article class="article mt-4">
                <div class="article-header">
                    <div class="article-image" data-background="<?php echo ($rowstr['mpid'] > 0) ? myvalidate($bpprow['planimg']) : myvalidate($bpprow['planlogo']); ?>">
                    </div>
                    <?php
                    if (intval($rowstr['mprankid']) > 0 && $mbrank['rklogo'] != '') {
                        ?>
                        <?php echo myvalidate($mbrank['rklogo']); ?>
                        <img class="overplanlogo-topnext img-fluid" alt="image" src="<?php echo myvalidate($mbrank['rklogo']); ?>" data-toggle="tooltip" title="<?php echo myvalidate($mbrank['rkname']); ?>">
                        <?php
                    }
                    ?>
                    <img class="overplanlogo-top img-fluid" alt="image" src="<?php echo myvalidate($bpprow['planlogo']); ?>">
                    <div class="article-title">
                        <h2 class="badge badge-primary"><?php echo ($bpprow['ppname']) ? myvalidate($bpprow['ppname']) : $cfgrow['site_name']; ?> - <?php echo ($bpprow['regfee'] > 0) ? myvalidate($bpprow['currencysym'] . $bpprow['regfee'] . ' ' . $bpprow['currencycode']) : 'FREE'; ?> <?php echo myvalidate($renewfeeico . $outplanreg); ?></h2>
                    </div>
                </div>
                <div class="article-details">
                    <div><?php echo ($bpprow['planinfo']) ? myvalidate($bpprow['planinfo']) : ''; ?></div>
                    <div class='article-cta mt-4'>
                        <?php echo myvalidate($markstatus . $statusactstr); ?>
                    </div>
                </div>
            </article>

        </div>

        <div class="col-12 col-md-12 col-lg-6">
            <div class="card">
                <form method="post" class="needs-validation" novalidate="">
                    <div class="card-header">
                        <h4><?php echo myvalidate($LANG['g_accoverview']); ?></h4>
                        <div class="card-header-action">
                            <?php
                            if ($rowstr['mbrbiolink']) {
                                ?>
                                <a class="btn btn-icon btn-info" href="<?php echo myvalidate($rowstr['mbrbiolink']); ?>" data-toggle="tooltip" title="Visit member Bio Page" target="_blank"><i class="fas fa-user-tie"></i></a>
                                <?php
                            }
                            ?>
                            <a class="btn btn-icon btn-primary" href="../<?php echo myvalidate(MBRFOLDER_NAME); ?>/login.php?ucpunlock=<?php echo myvalidate($rowstr['username']); ?>&icpunlock=<?php echo myvalidate($rowstr['id']); ?>" data-toggle="tooltip" title="Login as member" target="_blank"><i class="fas fa-unlock-alt"></i></a>
                        </div>
                    </div>
                    <div class="card-body">

                        <div class="row">
                            <div class="form-group col-md-6 col-12">
                                <label><?php echo myvalidate($LANG['g_firstname']); ?></label>
                                <h6><?php echo myvalidate($rowstr['firstname']); ?></h6>
                            </div>
                            <div class="form-group col-md-6 col-12">
                                <label><?php echo myvalidate($LANG['g_lastname']); ?></label>
                                <h6><?php echo myvalidate($rowstr['lastname']); ?></h6>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-6">
                                <?php
                                if ($rowstr['birthday'] > '2000-01-01') {
                                    $new_boddate = DateTime::createFromFormat('Y-m-d', $rowstr['birthday'] ?? '2000-01-01');
                                    $birthdaystr = $new_boddate->format('j M Y');
                                } else {
                                    $birthdaystr = "-";
                                }
                                ?>
                                <label><?php echo myvalidate($LANG['g_birthday']); ?></label>
                                <h6><?php echo myvalidate($birthdaystr); ?></h6>
                            </div>
                            <div class="form-group col-6">
                                <?php
                                $birthmd = substr($rowstr['birthday'] ?? '', 4, 6);
                                if ($birthmd == date('-m-d')) {
                                    ?>
                                    <i class="fa fa-gifts fa-3x fa-flip-horizontal fa-fade text-info"></i>
                                    <?php
                                }
                                ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-12">
                                <label>Email</label>
                                <h6><?php echo maskmail($rowstr['email']); ?></h6>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-12">
                                <label>Phone</label>
                                <h6><?php echo myvalidate($rowstr['phone']); ?></h6>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-12">
                                <label>Address</label>
                                <h6><?php echo myvalidate($rowstr['address']); ?> <?php echo myvalidate($rowstr['state']); ?></h6>
                                <h6><?php echo myvalidate($countrystr); ?></h6>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-12">
                                <label>Website <?php echo myvalidate($showsite_cekicon); ?></label>
                                <div class="text-muted font-weight-normal"><?php echo myvalidate($mbrsite_catstr); ?></div>
                                <h6><a href="<?php echo myvalidate($rowstr['mbrsite_url']); ?>" target="_blank" data-html="true" data-toggle="popover" data-trigger="hover" data-placement="left" data-content="<?php echo myvalidate($siteimgstr); ?>"><?php echo myvalidate($rowstr['mbrsite_title']); ?></a></h6>
                                <div class="text-muted form-text">
                                    <?php echo base64_decode($rowstr['mbrsite_desc'] ?? ''); ?>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-12">
                                <label>Subscribe to notifications</label>
                                <h6><?php echo myvalidate($optinme_cekstr); ?></h6>
                            </div>
                        </div>
                        <hr/>
                        <div class="row">
                            <div class="form-group col-12">
                                <label>Vendor</label>
                                <h5><span class='badge badge-info'><?php echo myvalidate($isvendor_cekstr); ?></span></h5>
                            </div>
                        </div>
                        <?php
                        if ($rowstr['isvendor'] == 1) {
                            ?>
                            <div class="row">
                                <div class="form-group col-6">
                                    <label>Item</label>
                                    <h6><?php echo myvalidate($vendoritemtot); ?></h6>
                                </div>
                                <div class="form-group col-6">
                                    <label>Sales</label>
                                    <h6><?php echo myvalidate($vendorsalestot); ?></h6>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-6">
                                    <label>Earning</label>
                                    <h6><?php echo myvalidate($vendorsalesvalue); ?></h6>
                                </div>
                                <div class="form-group col-6">
                                    <label>Fee</label>
                                    <h6><?php echo myvalidate($vendorfeevalue); ?></h6>
                                </div>
                            </div>
                            <?php
                        }
                        ?>

                        <div class="row">
                            <div class="col-12"><?php
                                if ($mbr_twitter) {
                                    ?>
                                    <span class="badge badge-success">
                                        <i class="fab fa-fw fa-twitter"></i> <?php echo myvalidate($mbr_twitter); ?>
                                    </span>
                                    <?php
                                }
                                if ($mbr_facebook) {
                                    ?>
                                    <span class="badge badge-success">
                                        <i class="fab fa-fw fa-facebook-f"></i> <?php echo myvalidate($mbr_facebook); ?>
                                    </span>
                                    <?php
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-whitesmoke text-right">
                        <a href="javascript:;" onclick="location.href = '<?php echo myvalidate($backpage); ?>'" class="btn btn-warning" data-toggle="tooltip" title="Back"><i class="fa fa-fw fa-undo-alt"></i> Back</a>
                        <a href="javascript:;" data-href="edituser.php?editId=<?php echo myvalidate($rowstr['id']); ?>&editMpid=<?php echo myvalidate($rowstr['mpid']); ?>&redir=getuser&mdlhasher=<?php echo myvalidate($mdlhasher); ?>" data-poptitle="<i class='fa fa-fw fa-edit'></i> Update Member #<?php echo myvalidate($rowstr['id'] . ' / ' . $rowstr['username']); ?>" class="btn btn-success openPopup" data-toggle="tooltip" title="Edit <?php echo myvalidate($rowstr['username']); ?>"><i class="fa fa-fw fa-edit"></i> Update</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
</div>
