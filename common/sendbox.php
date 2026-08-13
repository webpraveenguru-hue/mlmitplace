<?php

include_once('init.loader.php');

function doipnbox($txitid, $payamount, $paygate, $txbatch, $redirurl, $ipnreturn = '', $skipamount = 0, $addtoken = '') {
    global $db, $cfgrow, $bpprow, $bpparr, $LANG, $FORM;

    $defredirurl = $cfgrow['site_url'] . '/' . MBRFOLDER_NAME;
    $redirurl = ($redirurl != '') ? $redirurl : $defredirurl;
    $redirurl = ($redirurl == '-HTTPREF-') ? $_SERVER['HTTP_REFERER'] : $redirurl;
    $redirurl = ($redirurl == '-ADMSALESTXAPPROVAL-') ? $cfgrow['site_url'] . '/' . ADMFOLDER_NAME . '/index.php?hal=saleslist' : $redirurl;

    $sb_txitidarr = explode('-', str_replace('BELINV', '', $txitid ?? ''));
    $txid = $sb_txitidarr[0];
    $itid = $sb_txitidarr[1];

    // get transaction details
    $trxstr = get_txinfo($txid);
    $existingtxstatus = $trxstr['txstatus'];

    // get member details
    $mbrstr = getmbrinfo($trxstr['txfromid']);
    // get item details
    $itemstr = get_iteminfo($itid);

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

    $newtrxid = 0;
    $sqlstr = "SELECT * FROM " . DB_TBLPREFIX . "_transactions WHERE txfromid = '{$mbrstr['id']}' AND txitid = '{$itid}' AND ((txpaytype LIKE '{$txpaytype}' AND txbatch LIKE '{$txbatch}') OR txstatus = '0')";
    $sql = $db->getRecFrmQry($sqlstr);
    if (count($sql) < 1) {
        $data = array(
            'txdatetm' => $cfgrow['datetimestr'],
            'txfromid' => $mbrstr['id'],
            'txamount' => (float) $itemstr['itprice'],
            'txmemo' => 'Order ' . $itemstr['itname'],
            'txitid' => $itid,
            'txtoken' => "|TXSTORE:{$itid}|, |STORE:{$itemstr['itsku']}|",
        );
        $insert = $db->insert(DB_TBLPREFIX . '_transactions', $data);
        $newtrxid = $db->lastInsertId();

        // get recent transaction details
        $trxstr = get_txinfo($newtrxid);
    } else if ($FORM['sb_subs'] == 'renew' && $sql[0]['txid'] > 0 && $FORM['sb_ikey'] == INSTALL_KEYS) {
        $trxstr = get_txinfo($sql[0]['txid']);
    }
    // ---

    if (($trxstr['txamount'] <= $txamount || $skipamount == 1) && get_optionvals($trxstr['txtoken'], 'isapproved') != 1) {
        // transaction
        $txtoken = $trxstr['txtoken'];
        $txtoken = ($addtoken) ? $txtoken . ", {$addtoken}" : $txtoken;

        $amountadjt = $txamount - $trxstr['txamount'];
        $txadminfo = ($amountadjt != 0) ? 'Payment service fee: ' . $amountadjt . chr(13) . $trxstr['txadminfo'] : $trxstr['txadminfo'];
        $txtoken = put_optionvals($txtoken, 'addtxsfee', $amountadjt);
        $data = array(
            'txpaytype' => $txpaytype,
            'txamount' => (float) $txamount,
            'txbatch' => $txbatch,
            'txtmstamp' => $cfgrow['datetimestr'],
            'txtoken' => $txtoken,
            'txstatus' => 1,
            'txadminfo' => $txadminfo,
        );
        $update = $db->update(DB_TBLPREFIX . '_transactions', $data, array('txid' => $trxstr['txid']));

        if ($update && ($newtrxid > 0 || $existingtxstatus == 0)) {
            // process add order
            $txstr = get_txinfo($trxstr['txid']);
            dodb_item($itemstr, $mbrstr, $txstr, $mbrstr['idspr']);

            // adjust wallet
            if ($itid == 1) {
                $newewalletfund = $mbrstr['ewallet'] + $trxstr['txamount'];
                adjusttrxwallet($mbrstr['ewallet'], $newewalletfund, $mbrstr['id'], $itemstr['itname'] . " ({$txbatch})");
                $data = array(
                    'ewallet' => $newewalletfund,
                );
                $db->update(DB_TBLPREFIX . '_mbrs', $data, array('id' => $mbrstr['id']));
            }
            // deduct wallet fund
            if ($txpaytype == 'ewalletlabel') {
                $ewallet = $mbrstr['ewallet'] - $txamount;
                $data = array(
                    'ewallet' => $ewallet,
                );
                $db->update(DB_TBLPREFIX . '_mbrs', $data, array('id' => $mbrstr['id']));
            }

            $sprstr = getmbrinfo($mbrstr['idspr']);

            $getsalescm = get_salescmlist($mbrstr, $itemstr, $sprstr, $trxstr);

            // sales level commission list
            addcmlist($LANG['g_salescommission'], 'SLSTIER', $getsalescm['network'], $mbrstr, $trxstr);

            // sales personal commission
            addcmlist($LANG['g_salescommission'], 'SLSMYREF', $getsalescm['personal'], $mbrstr, $trxstr);

            // sales cashback commission
            addcmlist($LANG['g_salescashback'], 'SLSCASHBACK', $getsalescm['cashback'], $mbrstr, $trxstr);

            // do vendor earning
            do_vendorsl($trxstr['txid']);

            // add point to buyer
            $buyerpoint = get_optionvals($itemstr['ittoken'], 'buyerpoint');
            dodb_point(0, $mbrstr, $buyerpoint, 'Purchase Item', '4', "|POSRCTXID:{$trxstr['txid']}|");

            // add point to sponsor
            dodb_sprpoint($mbrstr, '5', $trxstr['txid'], 'sales');

            $datalistarr['status'] = '1';
            $datalistarr['amount'] = (float) $txamount;
            $datalistarr['receiptnum'] = $txbatch;
            do_slswebhook($mbrstr, $datalistarr);
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

function dotxsuspend($txitid, $suspendbatch, $addtoken) {
    global $db, $cfgrow, $bpprow;

    if ($suspendbatch != 'cancel') {
        $sb_txitidarr = explode('-', str_replace('BELINV', '', $txitid ?? ''));
        $txid = $sb_txitidarr[0];
        $itid = $sb_txitidarr[1];

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
                'txtmstamp' => $cfgrow['datetimestr'],
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

$paytoken = $payrow['paytoken'];
$isppsendbox = get_optionvals($paytoken, 'paypalsbox');

if ($FORM['sb_type'] == 'payreg') {
    $txitid = $FORM['sb_txitid'];
    $payamount = $FORM['sb_amount'];
    $paybatch = $FORM['sb_batch'];
    $paygate = $FORM['sb_label'];
    $redirurl = $FORM['sb_success'];
    $ipnreturn = $FORM['sb_ipnreturn'];
    doipnbox($txitid, $payamount, $paygate, $paybatch, $redirurl, $ipnreturn);
}

if ($FORM['custom'] != '' && $FORM['mc_currency'] == $bpprow['currencycode']) {
    $txitid = $FORM['custom'];
    $skipamount = 0;
    if ($FORM['txn_type'] == 'web_accept') {
        $payamount = $FORM['mc_gross'];
    }
    $paybatch = $FORM['txn_id'];

    require('paypal.ipv.php');
    $ipn = new PaypalIPN();
    if ($isppsendbox == 1) {

        // ---
        $postarr = array();
        foreach ($FORM as $key => $value) {
            $postarr[] = $key . '=' . $value;
        }
        printlog('sendbox.ipn/response', implode(', ', $postarr));
        $postarr = '';
        // ---

        $ipn->useSandbox();
    }
    $verified = $ipn->verifyIPN();

    printlog("sendbox.ipn/PayPal", "result:{$verified} / amount:{$payamount} / txn_type:{$FORM['txn_type']}");

    if ($verified) {
        if ($payamount < 0 || $FORM['txn_type'] == 'subscr_cancel' || $FORM['txn_type'] == 'subscr_eot') {
            $suspendbatch = ($payamount < 0) ? $paybatch : 'cancel';
            $payment_status = ($FORM['payment_status']) ? $FORM['payment_status'] : $FORM['txn_type'];
            dotxsuspend($txitid, $suspendbatch, "|payment_status:{$payment_status}|, |amount:{$payamount}|");
        } else {
            doipnbox($txitid, $payamount, 'paypalacc', $paybatch, '', 'OK', $skipamount);
        }
    }
}

if ($FORM['invoice'] != '') {
    $txitid = $FORM['invoice'];
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
        doipnbox($txitid, $payamount, 'coinpaymentsmercid', $FORM['currency2'] . '-' . $FORM['txn_id'], '', 'IPN OK');
    }
}
