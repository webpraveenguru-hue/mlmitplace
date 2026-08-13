<?php
if (!defined('OK_LOADME')) {
    die('o o p s !');
}

$pparr_inact = array_values(array_diff($mbrstr['pparr_all'], $mbrstr['pparr_act']));
$mbrstr = getmbrinfo($mbrstr['idmbr'], '', '', $pparr_inact[0]);

if ($mbrstr['idmbr'] != $mbrstr['id']) {
    redirpageto('index.php?hal=planreg');
    exit;
}

$bpprow = get_planarr($mbrstr['mppid']);
$planimg = $bpprow['planimg'];
$bpnowplantokenarr = get_optionvals($bpprow['plantoken']);

$paytokenarr = get_optionvals($payrow['paytoken']);
$pgdatatokenarr = get_optionvals($payrow['pgdatatoken']);

$pgdatatoken = $mbrstr['pgdatatoken'];
$pgmbrtokenarr = get_optionvals($pgdatatoken);

$bankcfg = get_optarr($pgdatatokenarr['bankcfg']);
$mbrbankcfg = get_optarr($pgmbrtokenarr['bankcfg']);

$ewalletcfg = get_optarr($pgdatatokenarr['ewalletcfg']);
$mbrewalletcfg = get_optarr($pgmbrtokenarr['ewalletcfg']);

$unpaidtxid = get_unpaidtxid($mbrstr);
if ($unpaidtxid > 0) {
    $txidstr = $unpaidtxid;
    $payforstr = "<i class='fas fa-file-invoice fa-fw'></i>";
} else {
    $condition = ' AND txtoken LIKE "%|REG:' . $mbrstr['mpid'] . '|%" ';
    $row = $db->getAllRecords(DB_TBLPREFIX . '_transactions', '*', $condition);
    $trxstr = array();
    foreach ($row as $value) {
        $trxstr = array_merge($trxstr, $value);
    }
    $txidstr = $trxstr['txid'];
    $payforstr = 'REGISTERED';

    if ($trxstr['txstatus'] == 1) {
        redirpageto('index.php?hal=dashboard');
        exit;
    }
}

// get transaction details
$trxstr = get_txinfo($txidstr);
$istxreg = (strpos($trxstr['txtoken'] ?? '', '|REG:') !== false) ? 1 : 0;
$mbr_fee = ($istxreg == 1) ? $mbrstr['reg_fee'] : $mbrstr['renew_fee'];
$paynow_fee = ($trxstr['txamount'] > 0) ? $trxstr['txamount'] : $mbr_fee;

$txtoken = get_optionvals($trxstr['txtoken']);
$txmpid = $txidstr . '-' . $mbrstr['mpid'];
$paybatch = strtoupper($mbrstr['mpid'] . date("m-DH-dis"));
$regfee = $totewallet = $totbank = $totmanualpay = $tottestpay = $paynow_fee;
$ispayg = 0;
$paygatearr = array('ewallet', 'bank', 'manualpay', 'testpay');
foreach ($paygatearr as $key => $value) {
    if ($payrow[$value . 'on'] == 1) {
        if ($payrow[$value . 'fee'] > 0) {
            ${'fee' . $value} = getamount($payrow[$value . 'fee'], $regfee);
            ${'tot' . $value} = $regfee + ${'fee' . $value};
        } else {
            ${'fee' . $value} = 0;
        }
        $ispayg++;
    }
    if ($pgdatatokenarr[$value . 'on'] == 1) {
        $valdatatoken = get_optarr($pgdatatokenarr[$value . 'cfg']);
        if ($valdatatoken[$value . 'fee'] > 0) {
            ${'fee' . $value} = getamount($valdatatoken[$value . 'fee'], $regfee);
            ${'tot' . $value} = $regfee + ${'fee' . $value};
        } else {
            ${'fee' . $value} = 0;
        }
        $ispayg++;
    }
}

if ($ispayg <= 1) {
    $colmdclass = "col-md-12";
} elseif ($ispayg <= 2) {
    $colmdclass = "col-md-6";
} else {
    $colmdclass = "col-md-4";
}

$manualpaytagsarr = array("[[currencysym]]" => $bpprow['currencysym'], "[[currencycode]]" => $bpprow['currencycode'], "[[feeamount]]" => $feemanualpay, "[[amount]]" => $regfee, "[[totamount]]" => $totmanualpay, "[[payplan]]" => $bpprow['ppname']);
$manualpayguide = base64_decode($paytokenarr['manualpayguide'] ?? '');
$manualpayipnraw = base64_decode($payrow['manualpayipn'] ?? '');
$manualpayipn = strtr($manualpayguide . $manualpayipnraw, $manualpaytagsarr);

if ($txtoken['proofimg'] != '' || ($txtoken['fbacktype'] == '9' && $trxstr['txstatus'] != 1)) {
    if ($txtoken['sb_label'] == 'bankacc') {
        $paybymethod = $LANG['g_banktitle'];
    } else if ($txtoken['sb_label'] == 'manualpayipn') {
        $paybymethod = $payrow['manualpayname'];
    }

    $confirmbtn = 'Re-confirm Payment';
    $manpaytxidstr = base64_decode($txtoken['manpaytxid']);
    $imgproofpay = ($txtoken['proofimg'] != '') ? "<p><hr /><div class='text-small text-info'>" . $LANG['m_feedbackpayexist'] . "</div><div><strong>" . $paybymethod . "</strong></div>" . $LANG['m_feedbackpaytxid'] . ": <strong>" . $manpaytxidstr . '</strong></p><img src="' . '../assets/imagextra/' . $txtoken['proofimg'] . '" border=0 width="50%">' : '';
} else {
    $confirmbtn = 'Confirm Payment';
    $manpaytxidstr = $imgproofpay = "";
}

$banktagsarr = array("[[currencysym]]" => $bpprow['currencysym'], "[[currencycode]]" => $bpprow['currencycode'], "[[feeamount]]" => $feebank, "[[amount]]" => $regfee, "[[totamount]]" => $totbank, "[[payplan]]" => $bpprow['ppname']);
$bankpaystr = ($bankcfg['bankguide'] != '') ? base64_decode($bankcfg['bankguide'] ?? '') : "Please send the payment of [[currencysym]][[amount]] + [[feeamount]] = [[currencysym]][[totamount]] [[currencycode]] to the following bank account. Once payment is complete, please confirm by uploading your proof of payment.";
$bankpaystr = strtr($bankpaystr, $banktagsarr);
$bankpaystr .= "<p><div class=''><span class='text-small'>{$LANG['g_bankname']}:</span> {$bankcfg['bankname']}</div><div class=''><span class='text-small'>{$LANG['g_bankaccnumber']}:</span> {$bankcfg['bankaccno']}</div><div class=''><span class='text-small'>{$LANG['g_bankaccname']}:</span> {$bankcfg['bankaccname']}</div></p>";
$bankipn64 = base64_encode($bankpaystr . $imgproofpay . '<hr /><button type="button" class="btn btn-warning btn-lg mt-2" onclick="location.href = \'index.php?hal=feedback&ispaidconfirm=' . base64_encode($txmpid ?? '') . '&pby=bankacc\'">' . $confirmbtn . '</button>');

$confirmpaystr = ($imgproofpay == '') ? "<div class='mt-2 text-danger'><i class='fas fa-exclamation-triangle fa-fw'></i> {$LANG['m_confirmpayment']}</div>" : '';
$manualpayipn64 = base64_encode($manualpayipn . $imgproofpay . '' . $confirmpaystr . '<hr /><button type="button" class="btn btn-warning btn-lg mt-2" onclick="location.href = \'index.php?hal=feedback&ispaidconfirm=' . base64_encode($txmpid ?? '') . '&pby=manualpayipn\'">' . $confirmbtn . '</button>');
?>

<div class="section-header">
    <h1><i class="fa fa-fw fa-money-check"></i> <?php echo myvalidate($LANG['m_planpay']); ?></h1>
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
                            <?php echo myvalidate($bpprow['currencysym'] . $regfee . ' ' . $bpprow['currencycode']); ?>
                        </span>
                        <?php
                        if ($sprstr['reflink'] != '' && $istxreg == 1) {
                            ?>
                            <span class="article-badge-item bg-warning">
                                <?php echo myvalidate($LANG['g_sponsorby'] . ' ' . $sprstr['username']); ?>
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
                        <span class="badge badge-secondary">
                            <?php echo myvalidate($payforstr); ?>
                        </span>
                        <span class="badge badge-danger">
                            <?php echo myvalidate($LANG['g_unpaid']); ?>
                        </span>
                    </div>
                </div>
            </article>

        </div>
    </div>

    <h2 class="section-title"><?php echo myvalidate($LANG['m_payoption']); ?></h2>
    <p class="section-lead"><?php echo myvalidate($LANG['m_payinfo']); ?></p>

    <div class="row">
        <?php
        if ($pgdatatokenarr['ewalleton'] == 1) {
            if ($totewallet <= $mbrstr['ewallet']) {
                $ewalletfrm = ' method="post" action="../common/sandbox.php" id="dopayform"';
                $ewalletbdg = 'success';
                $ewalletbtn = '';
            } else {
                $ewalletfrm = '';
                $ewalletbdg = 'danger';
                $ewalletbtn = ' disabled';
            }
            ?>
            <div class="<?php echo myvalidate($colmdclass); ?>">
                <div class="card card-primary">
                    <div class="card-body text-center">
                        <?php echo myvalidate($avalpaygateicon_array['ewalletlabel']); ?>
                        <h4><?php echo myvalidate($ewalletcfg['ewalletlabel']); ?></h4>
                        <div class="mt-4">Amount: <?php echo myvalidate($bpprow['currencysym'] . $regfee); ?></div>
                        <div><code>Service Fee: <?php echo myvalidate($bpprow['currencysym'] . $feeewallet); ?></code></div>
                        <h6>Total: <?php echo myvalidate($bpprow['currencysym'] . $totewallet . ' ' . $bpprow['currencycode']); ?></h6>

                        <div class="mt-4"><span class="badge badge-<?php echo myvalidate($ewalletbdg); ?>">Available: <?php echo myvalidate($bpprow['currencysym'] . $mbrstr['ewallet'] . ' ' . $bpprow['currencycode']); ?></span></div>
                        <form<?php echo myvalidate($ewalletfrm); ?>>
                            <input type="hidden" name="sb_type" value="payreg">
                            <input type="hidden" name="sb_txmpid" value="<?php echo myvalidate($txmpid); ?>">
                            <input type="hidden" name="sb_amount" value="<?php echo myvalidate($totewallet); ?>">
                            <input type="hidden" name="sb_batch" value="<?php echo myvalidate($paybatch); ?>">
                            <input type="hidden" name="sb_label" value="ewalletlabel">
                            <input type="hidden" name="sb_success" value="<?php echo myvalidate($cfgrow['site_url']) . '/' . MBRFOLDER_NAME . '/ipnhub.php?hal=dashboard'; ?>">
                            <input type="hidden" name="sb_tag" value="<?php echo myvalidate($ewalletcfg['ewalletlabel']); ?>">
                            <button type="submit" name="dopay" value="1" id="dopay" class="btn btn-primary btn-lg mt-4"<?php echo myvalidate($ewalletbtn); ?>>
                                Make Payment
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php
        }
        if ($pgdatatokenarr['bankon'] == 1) {
            $makepaymentstr = ($txtoken['proofimg'] != '' || ($txtoken['fbacktype'] == '9' && $trxstr['txstatus'] != 1)) ? $LANG['m_reviewpayment'] : $LANG['m_makepayment'];
            ?>
            <div class="<?php echo myvalidate($colmdclass); ?>">
                <div class="card card-primary">
                    <div class="card-body text-center">
                        <?php echo myvalidate($avalpaygateicon_array['bankacc']); ?>
                        <h4><?php echo myvalidate($LANG['g_banktitle']); ?></h4>
                        <div class="mt-4"><?php echo myvalidate($LANG['g_amount']); ?>: <?php echo myvalidate($bpprow['currencysym'] . $regfee); ?></div>
                        <div><code><?php echo myvalidate($LANG['g_servicefee']); ?>: <?php echo myvalidate($bpprow['currencysym'] . $feebank); ?></code></div>
                        <h6>Total: <?php echo myvalidate($bpprow['currencysym'] . $totbank . ' ' . $bpprow['currencycode']); ?></h6>
                        <button type="button" class="openPopup btn btn-primary btn-lg mt-4" data-encbase64="<?php echo myvalidate($bankipn64); ?>" data-poptitle="<?php echo myvalidate($avalpaygateicon_array['bankacc'] . ' ' . $LANG['g_banktitle']); ?>">
                            <?php echo myvalidate($makepaymentstr); ?>
                        </button>
                    </div>
                </div>
            </div>
            <?php
        }
        if ($payrow['manualpayon'] == 1) {
            $makepaymentstr = ($txtoken['proofimg'] != '' || ($txtoken['fbacktype'] == '9' && $trxstr['txstatus'] != 1)) ? $LANG['m_reviewpayment'] : $LANG['m_makepayment'];
            ?>
            <div class="<?php echo myvalidate($colmdclass); ?>">
                <div class="card card-primary">
                    <div class="card-body text-center">
                        <?php echo myvalidate($avalpaygateicon_array['manualpayipn']); ?>
                        <h4><?php echo myvalidate($payrow['manualpayname']); ?></h4>
                        <div class="mt-4"><?php echo myvalidate($LANG['g_amount']); ?>: <?php echo myvalidate($bpprow['currencysym'] . $regfee); ?></div>
                        <div><code><?php echo myvalidate($LANG['g_servicefee']); ?>: <?php echo myvalidate($bpprow['currencysym'] . $feemanualpay); ?></code></div>
                        <h6>Total: <?php echo myvalidate($bpprow['currencysym'] . $totmanualpay . ' ' . $bpprow['currencycode']); ?></h6>
                        <button type="button" class="openPopup btn btn-primary btn-lg mt-4" data-encbase64="<?php echo myvalidate($manualpayipn64); ?>" data-poptitle="<?php echo myvalidate($avalpaygateicon_array['manualpayipn'] . ' ' . $payrow['manualpayname']); ?>">
                            <?php echo myvalidate($makepaymentstr); ?>
                        </button>
                    </div>
                </div>
            </div>
            <?php
        }
        if ($payrow['testpayon'] == 1) {
            ?>
            <div class="<?php echo myvalidate($colmdclass); ?>">
                <div class="card card-danger">
                    <div class="card-body text-center">
                        <i class="fa fa-cog fa-fw"></i>
                        <h4><?php echo myvalidate($payrow['testpaylabel']); ?></h4>
                        <div class="mt-4"><?php echo myvalidate($LANG['g_amount']); ?>: <?php echo myvalidate($bpprow['currencysym'] . $regfee); ?></div>
                        <div><code><?php echo myvalidate($LANG['g_servicefee']); ?>: <?php echo myvalidate($bpprow['currencysym'] . $feetestpay); ?></code></div>
                        <h6>Total: <?php echo myvalidate($bpprow['currencysym'] . $tottestpay . ' ' . $bpprow['currencycode']); ?></h6>
                        <div class="mt-4"><?php echo myvalidate($LANG['m_testpayinfo']); ?></div>
                        <form method="post" action="../common/sandbox.php" id="dopayform">
                            <input type="hidden" name="sb_type" value="payreg">
                            <input type="hidden" name="sb_txmpid" value="<?php echo myvalidate($txmpid); ?>">
                            <input type="hidden" name="sb_amount" value="<?php echo myvalidate($tottestpay); ?>">
                            <input type="hidden" name="sb_batch" value="<?php echo myvalidate($paybatch); ?>">
                            <input type="hidden" name="sb_label" value="testpaylabel">
                            <input type="hidden" name="sb_success" value="<?php echo myvalidate($cfgrow['site_url']) . '/' . MBRFOLDER_NAME . '/ipnhub.php?hal=dashboard'; ?>">
                            <button type="submit" name="dopay" value="1" id="dopay" class="btn btn-danger btn-lg mt-4">
                                <?php echo myvalidate($LANG['m_makepayment']); ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php
        }
        ?>
    </div>
</div>
