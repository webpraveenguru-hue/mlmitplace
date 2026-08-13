<?php

include_once('init.loader.php');

function doipnbox($txmpid, $payamount, $paygate, $txbatch, $redirurl, $ipnreturn = '', $skipamount = 0, $addtoken = '') {
    global $db, $cfgrow, $cfgtoken, $bpprow, $bpparr, $frlmtdcfg, $LANG, $FORM;

    $defredirurl = $cfgrow['site_url'] . '/' . MBRFOLDER_NAME;
    $redirurl = ($redirurl != '') ? $redirurl : $defredirurl;
    $redirurl = ($redirurl == '-HTTPREF-') ? $_SERVER['HTTP_REFERER'] : $redirurl;

    $txtmstamp = $cfgrow['datetimestr'];
    $sb_txmpidarr = explode('-', $txmpid ?? '');
    $txid = $sb_txmpidarr[0];
    $mpid = $sb_txmpidarr[1];

    // get member details
    $mbrstr = getmbrinfo('', '', $mpid);
    if ($mbrstr['mpstatus'] == '0') {
        $mbrstr = do_resprmpid($mbrstr);
    }

    // get transaction details
    $trxstr = get_txinfo($txid);
    $existingtxstatus = $trxstr['txstatus'];

    // remove proof of payment file
    $proofimg = get_optionvals($trxstr['txtoken'], 'proofimg');
    if ($proofimg) {
        $proofimgfile = INSTALL_PATH . '/assets/imagextra/' . $proofimg;
        if (file_exists($proofimgfile)) {
            unlink($proofimgfile);
            $trxstr['txtoken'] = put_optionvals($trxstr['txtoken'], 'proofimg', '');
            $data = array(
                'txtoken' => $trxstr['txtoken'],
            );
            $update = $db->update(DB_TBLPREFIX . '_transactions', $data, array('txid' => $trxstr['txid']));
        }
    }

    $txpaytype = $paygate;
    $txbatch = ($txbatch == '') ? strtoupper(date("RmdH-Di")) . $txid : $txbatch . '-' . $txid;
    if ($FORM['sb_type'] == 'payreg' && get_optionvals($trxstr['txtoken'], 'isapproved') == 1) {
        if ($ipnreturn == 'exit') {
            exit;
        } else if ($ipnreturn == 'continue') {
            // continue the process
        } else if ($ipnreturn) {
            die($ipnreturn);
        } else {
            $_SESSION['dotoaster'] = "toastr.warning('Payment previously has been approved!', 'Info');";
            redirpageto($redirurl);
            exit;
        }
    }

    $txamount = $payamount;
    $is_plansubscr = is_plansubscr($mbrstr['mppid']);
    $startdatenow = 1;
    $startactdate = ($startdatenow == 1) ? $cfgrow['datestr'] : $mbrstr['reg_date'];
    $reg_expd = ($is_plansubscr && $mbrstr['reg_date'] > $mbrstr['reg_expd']) ? $startactdate : $mbrstr['reg_expd'];

    // is the trx exist
    $newtrxid = 0;
    $condition = " AND (txtoken LIKE '%|REG:{$mbrstr['mpid']}|%' OR txtoken LIKE '%|RENEW:{$mbrstr['mpid']}|%')";
    $sqlstr = "SELECT * FROM " . DB_TBLPREFIX . "_transactions WHERE txfromid = '{$mbrstr['id']}' AND txppid = '{$mbrstr['mppid']}' AND ((txpaytype LIKE '{$txpaytype}' AND txbatch LIKE '{$txbatch}') OR txstatus = '0')" . $condition;
    $sql = $db->getRecFrmQry($sqlstr);

    if ($is_plansubscr && count($sql) < 1) {
        $data = array(
            'txdatetm' => $txtmstamp,
            'txfromid' => $mbrstr['id'],
            'txamount' => (float) $txamount,
            'txmemo' => $LANG['g_renewalfee'],
            'txppid' => $mbrstr['mppid'],
            'txtoken' => "|RENEW:{$mbrstr['mpid']}|, |PREVEXP:{$reg_expd}|",
        );
        $insert = $db->insert(DB_TBLPREFIX . '_transactions', $data);
        $newtrxid = $db->lastInsertId();

        // get recent transaction details
        $trxstr = get_txinfo($newtrxid);
    } else if ($FORM['sb_subs'] == 'renew' && $sql[0]['txid'] > 0 && $FORM['sb_ikey'] == INSTALL_KEYS) {
        $trxstr = get_txinfo($sql[0]['txid']);
    }
    // ---

    $mptoken = $mbrstr['mptoken'];
    if (strpos($trxstr['txtoken'] ?? '', '|RENEW:') !== false) {
        $expdarr = get_actdate($bpparr[$mbrstr['mppid']]['expday'], $reg_expd);
        $reg_expd = $expdarr['next'];

        $renewx = intval(get_optionvals($mptoken, 'renewx')) + 1;
        $mptoken = put_optionvals($mptoken, 'renewx', $renewx);
        $mptoken = put_optionvals($mptoken, 'istrial', '0');

        $isrenew = 1;
    } else {
        $isrenew = '';
    }

    if (($trxstr['txamount'] <= $txamount || $skipamount == 1) && get_optionvals($trxstr['txtoken'], 'isapproved') != 1) {
        // member
        $mptoken = put_optionvals($mptoken, 'isinitpay', '1');
        $data = array(
            'reg_expd' => $reg_expd,
            'mpstatus' => 1,
            'mptoken' => $mptoken,
            'rmdexp' => 0,
        );
        $mpupdate = $db->update(DB_TBLPREFIX . '_mbrplans', $data, array('mpid' => $mpid));

        if ($mpupdate) {
            $data = array(
                'isvendor' => $bpparr[$mbrstr['mppid']]['isregvendor'],
            );
            if ($cfgtoken['ismbrneedconfirm'] == 2) {
                $data['isconfirm'] = 1;
            }
            $update = $db->update(DB_TBLPREFIX . '_mbrs', $data, array('id' => $mbrstr['id']));

            $datalistarr['status'] = '1';
            do_mbrwebhook($mbrstr, $datalistarr);
        }

        // transaction
        $txtoken = ($update) ? put_optionvals($trxstr['txtoken'], 'isapproved', 1) : $trxstr['txtoken'];
        $txtoken = ($txpaytype == 'ewalletlabel') ? put_optionvals($txtoken, 'sb_tag', $addtoken) : $txtoken . ", {$addtoken}";

        if (is_array($FORM['sb_txtokenarr']) && count($FORM['sb_txtokenarr']) > 0) {
            foreach ($FORM['sb_txtokenarr'] as $key => $value) {
                $txtoken = put_optionvals($txtoken, $key, $value);
            }
        }

        $trxstrtxamount = $trxstr['txamount'];

        $amountadjt = $txamount - $trxstrtxamount;
        $txadminfo = ($amountadjt != 0) ? 'Payment service fee: ' . $amountadjt . chr(13) . $trxstr['txadminfo'] : $trxstr['txadminfo'];
        $txamount = floatval($txamount);
        $data = array(
            'txpaytype' => $txpaytype,
            'txamount' => $txamount,
            'txbatch' => $txbatch,
            'txtmstamp' => $txtmstamp,
            'txtoken' => $txtoken,
            'txstatus' => 1,
            'txadminfo' => $txadminfo,
        );
        $update = $db->update(DB_TBLPREFIX . '_transactions', $data, array('txid' => $trxstr['txid']));

        // deduct wallet fund
        if ($txpaytype == 'ewalletlabel') {
            $ewallet = $mbrstr['ewallet'] - $txamount;
            $data = array(
                'ewallet' => $ewallet,
            );
            $update = $db->update(DB_TBLPREFIX . '_mbrs', $data, array('id' => $mbrstr['id']));
        }

        // process ranks before generate commissions
        do_ranker($mbrstr, 1);

        // process commission
        if ($update && ($newtrxid > 0 || $existingtxstatus == 0)) {

            // get updated transaction details
            $trxstr = get_txinfo($trxstr['txid']);

            // retrieve original amount to the transaction history
            $trxstrforcm = array();
            $trxstrforcm = $trxstr;
            $trxstrforcm['txamount'] = $trxstrtxamount;

            // personal referral commission list
            $refstr = ($frlmtdcfg['isregallrefs'] == 1) ? getmbrinfo($mbrstr['idref']) : getmbrinfo($mbrstr['idref'], '', '', $mbrstr['mppid']);

            $mpidref = ($refstr['mppid'] > $mbrstr['mppid']) ? $mbrstr['mppid'] : $refstr['mppid'];
            $cmdrlist = ($frlmtdcfg['isgencmbyup'] != 1) ? $bpparr[$mbrstr['mppid']]['cmdrlist'] : $bpparr[$mpidref]['cmdrlist'];
            $reflist = dosprlist($refstr['mpid'], $refstr['sprlist'], $mbrstr['mpdepth']);
            $getarrcmlist = getcmlist($refstr, $reflist, $cmdrlist, $mbrstr, $trxstrforcm, 'adjcmdrlist');
            addcmlist($LANG['g_referrercommission'], 'PREF', $getarrcmlist, $mbrstr, $trxstr);

            // level commission list
            $sprstr = getmbrinfo($mbrstr['idspr'], '', '', $mbrstr['mppid']);

            $mpidspr = ($sprstr['mppid'] > $mbrstr['mppid']) ? $mbrstr['mppid'] : $sprstr['mppid'];

            $cminitlist = ($frlmtdcfg['isgencmbyup'] != 1) ? $bpparr[$mbrstr['mppid']]['cmlist'] : $bpparr[$mpidspr]['cmlist'];
            if ($isrenew == 1) {
                $cmrenwlist = ($frlmtdcfg['isgencmbyup'] != 1) ? $bpparr[$mbrstr['mppid']]['cmlistrnew'] : $bpparr[$mpidspr]['cmlistrnew'];
                $cmlist = ($cmrenwlist == '') ? $cminitlist : $cmrenwlist;
                $adjcmstr = 'adjcmlistrnew';
                $levelcommissionstr = $LANG['g_renewcommission'];
            } else {
                $cmlist = $cminitlist;
                $adjcmstr = 'adjcmlist';
                $levelcommissionstr = $LANG['g_levelcommission'];
            }

            $getarrcmlist = getcmlist($sprstr, $mbrstr['sprlist'], $cmlist, $mbrstr, $trxstrforcm, $adjcmstr);
            addcmlist($levelcommissionstr, 'TIER', $getarrcmlist, $mbrstr, $trxstr);
            $maxcmfromreg = ($bpparr[$mbrstr['mppid']]['regmaxcm'] > 0) ? $bpparr[$mbrstr['mppid']]['regmaxcm'] : $bpparr[$mbrstr['mppid']]['regfee'];
            $cmpool = getamount($bpparr[$mbrstr['mppid']]['cmforpool'], $maxcmfromreg);
            $poolwletnow = $cfgrow['poolwlet'] + $cmpool;
            $data = array(
                'poolwlet' => $poolwletnow,
            );
            $update = $db->update(DB_TBLPREFIX . '_configs', $data, array('cfgid' => '1'));

            //process available commission to wallet
            dotrxwallet();

            // level complete reward list
            dolvldone($mbrstr, $trxstrforcm);
            // add point to sponsor
            dodb_sprpoint($mbrstr, '3', $trxstr['txid'], 'reg');
        }

        if ($ipnreturn == 'exit') {
            exit;
        } else if ($ipnreturn == 'continue') {
            // continue the process
        } else if ($ipnreturn) {
            die($ipnreturn);
        } else {
            $_SESSION['dotoaster'] = "toastr.success('Payment has been successfully approved!', 'Success');";
            redirpageto($redirurl);
            exit;
        }
    } else {
        die('Invalid amount or duplicate process!');
    }
}

function dotxsuspend($txmpid, $suspendbatch, $addtoken) {
    global $db, $cfgrow, $bpprow;

    if ($suspendbatch != 'cancel') {
        $txtmstamp = $cfgrow['datetimestr'];
        $sb_txmpidarr = explode('-', $txmpid);
        $txid = $sb_txmpidarr[0];
        $mpid = $sb_txmpidarr[1];

        // get transaction details
        $condition = ($suspendbatch != '') ? ' AND txbatch = "' . $suspendbatch . '" ' : ' AND txid = "' . $txid . '" ';
        $row = $db->getAllRecords(DB_TBLPREFIX . '_transactions', '*', $condition);
        $trxstr = array();
        foreach ($row as $value) {
            $trxstr = array_merge($trxstr, $value);
        }

        if ($trxstr['txstatus'] != '3') {
            $txtoken = $trxstr['txtoken'] . ', ' . $addtoken;
            $data = array(
                'txtmstamp' => $txtmstamp,
                'txtoken' => $txtoken,
                'txstatus' => 3,
            );
            $update = $db->update(DB_TBLPREFIX . '_transactions', $data, array('txid' => $trxstr['txid']));
        }
    }
}

$pgdatatoken = $payrow['pgdatatoken'];
$pgdatatokenarr = get_optionvals($pgdatatoken);

$coinpaymentscfg = get_optarr($pgdatatokenarr['coinpaymentscfg']);

$paypalcfg = get_optarr($pgdatatokenarr['paypalcfg']);
$isppsandbox = $paypalcfg['paypalsbox'];

if ($FORM['sb_type'] == 'payreg') {
    $txmpid = do_prerenewtx($FORM['sb_txmpid'], $FORM['sb_mpstatus']);
    $payamount = $FORM['sb_amount'];
    $paybatch = $FORM['sb_batch'];
    $paygate = $FORM['sb_label'];
    $redirurl = $FORM['sb_success'];
    $ipnreturn = $FORM['sb_ipnreturn'];
    if ($paygate == 'ewalletlabel') {
        doipnbox($txmpid, $payamount, $paygate, $paybatch, $redirurl, $ipnreturn, 0, $FORM['sb_tag']);
    } else {
        doipnbox($txmpid, $payamount, $paygate, $paybatch, $redirurl, $ipnreturn);
    }
}

if ($FORM['custom'] != '' && $FORM['mc_currency'] == $bpprow['currencycode']) {
    $txmpid = $FORM['custom'];
    $skipamount = 0;
    if ($FORM['txn_type'] == 'web_accept') {
        $payamount = $FORM['mc_gross'];
    }
    $paybatch = $FORM['txn_id'];

    require('paypal.ipv.php');
    $ipn = new PaypalIPN();
    if ($isppsandbox == 1) {

        // ---
        $postarr = array();
        foreach ($FORM as $key => $value) {
            $postarr[] = $key . '=' . $value;
        }
        printlog('sandbox.ipn/response', implode(', ', $postarr));
        $postarr = '';
        // ---

        $ipn->useSandbox();
    }
    $verified = $ipn->verifyIPN();

    if ($verified) {
        if ($payamount < 0 || $FORM['txn_type'] == 'subscr_cancel' || $FORM['txn_type'] == 'subscr_eot') {
            $suspendbatch = ($payamount < 0) ? $paybatch : 'cancel';
            $payment_status = ($FORM['payment_status']) ? $FORM['payment_status'] : $FORM['txn_type'];
            dotxsuspend($txmpid, $suspendbatch, "|payment_status:{$payment_status}|, |amount:{$payamount}|");
        } else {
            //doipnbox($txmpid, $payamount, $paygate, $paybatch, '', 'OK', $skipamount);
            doipnbox($txmpid, $payamount, 'paypalacc', $paybatch, '', 'exit', $skipamount);
        }
    }
}

if ($FORM['invoice'] != '') {
    $txmpid = $FORM['invoice'];
    $payamount = $FORM['amount1'];

    $hmac_pass = 1;
    $merchant_id = $coinpaymentscfg['coinpaymentsmercid'];
    $coinpaymentsipnkey = $coinpaymentscfg['coinpaymentsipnkey'];

    $merchant = isset($FORM['merchant']) ? $FORM['merchant'] : '';
    if ($merchant != $merchant_id) {
        $hmac_pass = 0;
        $merchant_idfail = $merchant_id;
    }

    $request = file_get_contents('php://input');
    $hmac = hash_hmac("sha512", $request, $coinpaymentsipnkey);
    if ($coinpaymentsipnkey && $hmac_pass == 1 && $hmac != $_SERVER['HTTP_HMAC']) {
        $hmac_pass = 0;
        $coinpaymentsipnkeyfail = $coinpaymentsipnkey;
    }


    if ($hmac_pass == 1 && ($FORM['status'] >= '100' || $FORM['status'] == '2' || $FORM['status'] == '1')) {
        doipnbox($txmpid, $payamount, 'coinpaymentsmercid', $FORM['currency2'] . '-' . $FORM['txn_id'], '', 'IPN OK');
    }
}