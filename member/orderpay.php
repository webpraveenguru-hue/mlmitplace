<?php
if (!defined('OK_LOADME')) {
    die('o o p s !');
}

$itid = intval($FORM['itid']);
$itemstr = get_iteminfo($itid);
$ithash = md5($mdlhashy . $itid . '+' . $itemstr['itstatus'] . $mbrstr['id']);
$lhash = $FORM['l'];
$afhash = md5($mdlhashy . '1' . $mbrstr['id']);

if (($itid > 1 && $ithash != $lhash) || ($itid <= 1 && $afhash != $lhash)) {
    ?>
    <div class="section-body">
        <div class="row">
            <div class="col-md-12">
                <article class="article article-style-b">
                    <div class="article-details">
                        <div class="article-title">
                            <h5 class="text-danger">Product not found</h5>
                        </div>
                        <p>You will be redirected to the store page within few seconds.</p>
                    </div>
                </article>
            </div>
        </div>
    </div>
    <?php
    $hal = 'store';
    redirpageto('index.php?hal=' . $hal);
    die();
}

$condition = " AND txfromid = '{$mbrstr['id']}' AND txitid = '{$itid}' ORDER BY txid DESC";
$txrow = $db->getAllRecords(DB_TBLPREFIX . '_transactions', '*', $condition);
$trxstr = $txrow[0];
$txid = $trxstr['txid'];
$txtoken = get_optionvals($trxstr['txtoken']);

// get price based on plan
$addamount = floatval($FORM['addamount']);
$itpricenow = get_itpricebyplan($itemstr, $mbrstr['mppid'], $addamount);

if ($itpricenow <= 0) {
    if ($itid > 1) {
        include_once('../common/sendbox.php');
        $FORM['sb_type'] = 'payreg';

        $newtrxid = do_preparetxorderit($mbrstr, $itemstr, $itpricenow, '');

        $txitid = "BELINV" . $newtrxid . "-" . $itid;
        $txbatch = strtoupper('F' . date("mdH-iD")) . $itid;
        doipnbox($txitid, $itpricenow, 'system', $txbatch, '', 'continue', 0, '');
        $hal = 'orderlist';
        redirpageto('index.php?hal=' . $hal);
        die();
    }
    $_SESSION['dotoaster'] = "toastr.error('{$LANG['m_depositfailed']}', 'Error');";
    $hal = 'withdrawreq';
    redirpageto('index.php?hal=' . $hal);
    die();
}

// get transaction details
$unpaidtxid = get_unpaidtxid($mbrstr, $itid);
if ($unpaidtxid > 0) {
    $txidstr = $unpaidtxid;
    $trxstr = get_txinfo($txidstr);
    $txtoken = $trxstr['txtoken'];
    $txtoken = put_optionvals($txtoken, 'STORE', $itemstr['itsku']);
    if (intval($FORM['slid']) > 0) {
        $txtoken = put_optionvals($txtoken, 'RENEWSLID', intval($FORM['slid']));
    }
    $data = array(
        'txdatetm' => $cfgrow['datetimestr'],
        'txamount' => (float) $itpricenow,
        'txmemo' => 'Order ' . $itemstr['itname'],
        'txtoken' => $txtoken,
    );

    $db->update(DB_TBLPREFIX . '_transactions', $data, array('txid' => $txidstr));
    $txtoken = get_optionvals($trxstr['txtoken']);
} else {
    if ($itpricenow > 0) {
        $txtokenrenew = (intval($FORM['slid']) > 0) ? ", |RENEWSLID:" . intval($FORM['slid']) . "|" : '';
        $txidstr = do_preparetxorderit($mbrstr, $itemstr, $itpricenow, $txtokenrenew);
    }
}

$paytoken = $payrow['paytoken'];
$paytokenarr = get_optionvals($paytoken);

$pgdatatokenarr = get_optionvals($payrow['pgdatatoken']);

$pgdatatoken = $mbrstr['pgdatatoken'];
$pgmbrtokenarr = get_optionvals($pgdatatoken);

$bankcfg = get_optarr($pgdatatokenarr['bankcfg']);
$mbrbankcfg = get_optarr($pgmbrtokenarr['bankcfg']);

$ewalletcfg = get_optarr($pgdatatokenarr['ewalletcfg']);
$mbrewalletcfg = get_optarr($pgmbrtokenarr['ewalletcfg']);

$txitid = 'BELINV' . $txidstr . '-' . $itid;
$itemprice = $totewallet = $totbank = $totmanualpay = $tottestpay = $itpricenow;

$ispayg = 0;
$paygatearr = array('ewallet', 'bank', 'manualpay', 'testpay');
foreach ($paygatearr as $key => $value) {
    if ($payrow[$value . 'on'] == 1) {
        if ($payrow[$value . 'fee'] > 0) {
            ${'fee' . $value} = getamount($payrow[$value . 'fee'], $itemprice);
            ${'tot' . $value} = $itemprice + ${'fee' . $value};
        } else {
            ${'fee' . $value} = 0;
        }
        $ispayg++;
    }
    if ($pgdatatokenarr[$value . 'on'] == 1) {
        $valdatatoken = get_optarr($pgdatatokenarr[$value . 'cfg']);
        if ($valdatatoken[$value . 'fee'] > 0) {
            ${'fee' . $value} = getamount($valdatatoken[$value . 'fee'], $itemprice);
            ${'tot' . $value} = $itemprice + ${'fee' . $value};
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

$tagsarr = array("[[currencysym]]" => $bpprow['currencysym'], "[[currencycode]]" => $bpprow['currencycode'], "[[feeamount]]" => $feemanualpay, "[[amount]]" => $itemprice, "[[totamount]]" => $totmanualpay, "[[payplan]]" => $itemstr['itname']);
$manualpayguide = base64_decode($paytokenarr['manualpayguide'] ?? '');
$manualpayguide = strtr($manualpayguide, $tagsarr);
$manualpayipn = base64_decode($payrow['manualpayipn'] ?? '');
$manualpayipn = strtr($manualpayipn, $tagsarr);

if ($txtoken['proofimg'] != '' || ($txtoken['fbacktype'] == '9' && $trxstr['txstatus'] != 1)) {
    if ($txtoken['sb_label'] == 'bankacc') {
        $paybymethod = $LANG['g_banktitle'];
    } else if ($txtoken['sb_label'] == 'manualpayipn') {
        $paybymethod = $payrow['manualpayname'];
    }

    if ($txtoken['proofimg'] != 'file_defaultimage.jpg') {
        $proofimgfile = "../assets/imagextra/{$txtoken['proofimg']}";
    } else {
        $proofimgfile = "../assets/image/file_defaultimage.jpg";
    }

    $confirmbtn = 'Re-confirm Payment';
    $manpaytxidstr = base64_decode($txtoken['manpaytxid']);
    $imgproofpay = ($txtoken['proofimg'] != '') ? "<p><hr /><div class='text-small text-info'>" . $LANG['m_feedbackpayexist'] . "</div><div><strong>" . $paybymethod . "</strong></div>" . $LANG['m_feedbackpaytxid'] . ": <strong>" . $manpaytxidstr . '</strong></p><img src="' . $proofimgfile . '" border=0 width="50%">' : '';
} else {
    $confirmbtn = 'Confirm Payment';
    $manpaytxidstr = $imgproofpay = "";
}

$banktagsarr = array("[[currencysym]]" => $bpprow['currencysym'], "[[currencycode]]" => $bpprow['currencycode'], "[[feeamount]]" => $feebank, "[[amount]]" => $itemprice, "[[totamount]]" => $totbank, "[[payplan]]" => $itemstr['itname']);
$bankpaystr = ($bankcfg['bankguide'] != '') ? base64_decode($bankcfg['bankguide'] ?? '') : "Please send the payment of [[currencysym]][[amount]] + [[feeamount]] = [[currencysym]][[totamount]] [[currencycode]] to the following bank account. Once payment is complete, please confirm by uploading your proof of payment.";
$bankpaystr = strtr($bankpaystr, $banktagsarr);
$bankpaystr .= "<p><div class=''><span class='text-small'>{$LANG['g_bankname']}:</span> {$bankcfg['bankname']}</div><div class=''><span class='text-small'>{$LANG['g_bankaccnumber']}:</span> {$bankcfg['bankaccno']}</div><div class=''><span class='text-small'>{$LANG['g_bankaccname']}:</span> {$bankcfg['bankaccname']}</div></p>";
$bankipn64 = base64_encode($bankpaystr . $imgproofpay . '<hr /><button type="button" class="btn btn-warning btn-lg mt-2" onclick="location.href = \'index.php?hal=feedback&ispaidconfirm=' . base64_encode($txitid ?? '') . '&pby=bankacc\'">' . $confirmbtn . '</button>');

$manualpayipn64 = base64_encode($manualpayguide . $manualpayipn . $imgproofpay . '<hr /><button type="button" class="btn btn-warning btn-lg mt-4" onclick="location.href = \'index.php?hal=feedback&ispaidconfirm=' . base64_encode($txitid) . '&pby=manualpayipn\'">' . $confirmbtn . '</button>');
?>

<div class="section-header">
    <h1><i class="fa fa-fw fa-money-check"></i> <?php echo myvalidate($LANG['m_planpay']); ?></h1>
</div>

<div class="section-body">
    <div class="row">
        <div class="col-md-12">
            <article class="article article-style-b">
                <div class="article-header">
                    <div class="article-image" data-background="<?php echo myvalidate($itemstr['itimage']); ?>">
                    </div>
                    <div class="article-badge">
                        <span class="article-badge-item bg-danger">
                            <?php echo myvalidate($bpprow['currencysym'] . $itemprice . ' ' . $bpprow['currencycode']); ?>
                        </span>
                        <?php
                        if ($sprstr['mpstatus'] == 1) {
                            ?>
                            <span class="article-badge-item bg-warning">
                                Sponsored by <?php echo myvalidate($sprstr['username']); ?>
                            </span>
                            <?php
                        }
                        ?>
                    </div>
                </div>
                <div class="article-details">
                    <div class="article-title">
                        <h4><?php echo myvalidate($itemstr['itname']); ?></h4>
                    </div>
                    <p><?php echo myvalidate($itemstr['itdescr']); ?></p>
                    <div class="article-cta">
                        <span class="badge badge-danger">
                            UNPAID
                        </span>
                    </div>
                </div>
            </article>

        </div>
    </div>

    <a name="paythis"></a>

    <h2 class="section-title"><?php echo myvalidate($LANG['m_payoption']); ?></h2>
    <p class="section-lead"><?php echo myvalidate($LANG['m_payinfo']); ?></p>

    <div class="row">
        <?php
        if ($itid > 1 && $pgdatatokenarr['ewalleton'] == 1 && $pgdatatokenarr['ewallet4store'] == 1) {
            if ($totewallet <= $mbrstr['ewallet']) {
                $ewalletfrm = ' method="post" action="../common/sendbox.php" id="dopayform"';
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
                        <div class="mt-4"><?php echo myvalidate($LANG['g_amount']); ?>: <?php echo myvalidate($bpprow['currencysym'] . $itemprice); ?></div>
                        <div><code><?php echo myvalidate($LANG['g_servicefee']); ?>: <?php echo myvalidate($bpprow['currencysym'] . $feeewallet); ?></code></div>
                        <h6>Total: <?php echo myvalidate($bpprow['currencysym'] . $totewallet . ' ' . $bpprow['currencycode']); ?></h6>

                        <div class="mt-4"><span class="badge badge-<?php echo myvalidate($ewalletbdg); ?>">Available: <?php echo myvalidate($bpprow['currencysym'] . $mbrstr['ewallet'] . ' ' . $bpprow['currencycode']); ?></span></div>
                        <form<?php echo myvalidate($ewalletfrm); ?>>
                            <input type="hidden" name="sb_type" value="payreg">
                            <input type="hidden" name="sb_txitid" value="<?php echo myvalidate($txitid); ?>">
                            <input type="hidden" name="sb_amount" value="<?php echo myvalidate($totewallet); ?>">
                            <input type="hidden" name="sb_batch" value="<?php echo myvalidate($paybatch); ?>">
                            <input type="hidden" name="sb_label" value="ewalletlabel">
                            <input type="hidden" name="sb_success" value="<?php echo myvalidate($cfgrow['site_url']) . '/' . MBRFOLDER_NAME . '/ipnhub.php?hal=orderlist'; ?>">
                            <input type="hidden" name="sb_tag" value="<?php echo myvalidate($ewalletcfg['ewalletlabel']); ?>">
                            <button type="submit" name="dopay" value="1" id="dopay" class="btn btn-primary btn-lg mt-4"<?php echo myvalidate($ewalletbtn); ?>>
                                <?php echo myvalidate($LANG['m_makepayment']); ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php
        }
        if ($pgdatatokenarr['bankon'] == 1 && $pgdatatokenarr['bank4store'] == 1) {
            $makepaymentstr = ($txtoken['proofimg'] != '' || ($txtoken['fbacktype'] == '9' && $trxstr['txstatus'] != 1)) ? $LANG['m_reviewpayment'] : $LANG['m_makepayment'];
            ?>
            <div class="<?php echo myvalidate($colmdclass); ?>">
                <div class="card card-primary">
                    <div class="card-body text-center">
                        <?php echo myvalidate($avalpaygateicon_array['bankacc']); ?>
                        <h4><?php echo myvalidate($LANG['g_banktitle']); ?></h4>
                        <div class="mt-4"><?php echo myvalidate($LANG['g_amount']); ?>: <?php echo myvalidate($bpprow['currencysym'] . $itemprice); ?></div>
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

        if ($payrow['manualpayon'] == 1 && $payrow['manualpay4store'] == 1) {
            $makepaymentstr = ($txtoken['proofimg'] != '' || ($txtoken['fbacktype'] == '9' && $trxstr['txstatus'] != 1)) ? $LANG['m_reviewpayment'] : $LANG['m_makepayment'];
            ?>
            <div class="<?php echo myvalidate($colmdclass); ?>">
                <div class="card card-primary">
                    <div class="card-body text-center">
                        <i class="fa fa-handshake fa-fw"></i>
                        <h4><?php echo myvalidate($payrow['manualpayname']); ?></h4>
                        <div class="mt-4"><?php echo myvalidate($LANG['g_amount']); ?>: <?php echo myvalidate($bpprow['currencysym'] . $itemprice); ?></div>
                        <div><code><?php echo myvalidate($LANG['g_servicefee']); ?>: <?php echo myvalidate($bpprow['currencysym'] . $feemanualpay); ?></code></div>
                        <h6>Total: <?php echo myvalidate($bpprow['currencysym'] . $totmanualpay . ' ' . $bpprow['currencycode']); ?></h6>
                        <button type="button" class="openPopup btn btn-primary btn-lg mt-4" data-encbase64="<?php echo myvalidate($manualpayipn64); ?>" data-poptitle="<i class='fa fa-fw fa-handshake'></i> <?php echo myvalidate($payrow['manualpayname']); ?>">
                            <?php echo myvalidate($makepaymentstr); ?>
                        </button>
                    </div>
                </div>
            </div>
            <?php
        }
        if ($payrow['testpayon'] == 1) {
            $paybatch = strtoupper(date("DmdH-is")) . $mbrstr['mpid'];
            ?>
            <div class="<?php echo myvalidate($colmdclass); ?>">
                <div class="card card-danger">
                    <div class="card-body text-center">
                        <i class="fa fa-cog fa-fw"></i>
                        <h4><?php echo myvalidate($payrow['testpaylabel']); ?></h4>
                        <div class="mt-4"><?php echo myvalidate($LANG['g_amount']); ?>: <?php echo myvalidate($bpprow['currencysym'] . $itemprice); ?></div>
                        <div><code><?php echo myvalidate($LANG['g_servicefee']); ?>: <?php echo myvalidate($bpprow['currencysym'] . $feetestpay); ?></code></div>
                        <h6>Total: <?php echo myvalidate($bpprow['currencysym'] . $tottestpay . ' ' . $bpprow['currencycode']); ?></h6>
                        <div class="mt-4"><?php echo myvalidate($LANG['m_testpayinfo']); ?></div>
                        <form method="post" action="../common/sendbox.php" id="dopayform">
                            <input type="hidden" name="sb_type" value="payreg">
                            <input type="hidden" name="sb_txitid" value="<?php echo myvalidate($txitid); ?>">
                            <input type="hidden" name="sb_amount" value="<?php echo myvalidate($tottestpay); ?>">
                            <input type="hidden" name="sb_batch" value="<?php echo myvalidate($paybatch); ?>">
                            <input type="hidden" name="sb_label" value="testpaylabel">
                            <input type="hidden" name="sb_success" value="<?php echo myvalidate($cfgrow['site_url']) . '/' . MBRFOLDER_NAME . '/ipnhub.php?hal=orderlist'; ?>">
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
