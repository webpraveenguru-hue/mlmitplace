<?php

if (!defined('OK_LOADME')) {
    die("<title>Error!</title><body>No such file or directory.</body>");
}

function passmeter($password) {
    global $payrow, $LANG;

    if ($payrow['testpayon'] == 1) {
        return 1;
    }

    $uppercase = preg_match('#[A-Z]#', $password);
    $lowercase = preg_match('#[a-z]#', $password);
    $number = preg_match('#[0-9]#', $password);
    $specialChars = preg_match('#[^\w]#', $password);
    if (!$uppercase || !$lowercase || !$number || !$specialChars || strlen($password ?? '') < 8) {
        return $LANG['g_passmeter'];
    } else {
        return 1;
    }
}

$pub_footerstr = "<!-- " . $ssysout('SSYS_ALIAS') . " -->";
$cfgtoken['shortenby'] = 'https://peppy.link/api';
cmdsetvars($cfgtoken);

function getmpidflow($mpid, $regonmppid = 0, $mbrstr = array()) {
    global $db, $bpprow, $cfgtoken, $frlmtdcfg;
    $isten = 10;
    if (intval($mpid) < 1) {
        return 0;
    }

    $sprstr = getmbrinfo('', '', $mpid);
    $bppxrow = ($sprstr['mppid'] > 0) ? get_planarr($sprstr['mppid']) : $bpprow;
    $sprstr['mpwidth'] = ($sprstr['mpwidth'] > $isten) ? $isten : $sprstr['mpwidth'];
    $maxwideexd = ($bppxrow['maxwidth'] < 1) ? $bppxrow['maxwidth'] : $sprstr['mpwidth'];
    $maxdeepexd = ($bppxrow['maxdepth'] < 1) ? $bppxrow['maxdepth'] : $sprstr['mpdepth'] * 2;

    if ($maxwideexd < 1 || $maxdeepexd < 1) {
        return $mpid;
    }

    $maxwideexd = ($maxwideexd > $frlmtdcfg['ismw']) ? $frlmtdcfg['ismw'] : $maxwideexd;
    $maxdeepexd = ($maxdeepexd > $frlmtdcfg['ismd']) ? $frlmtdcfg['ismd'] : $maxdeepexd;
    $stgId = ($regonmppid > 0) ? $regonmppid : $mbrstr['mppid'];
    $restricbymppid = ($regonmppid > 0 && $stgId > 0) ? " AND mppid = '{$stgId}'" : '';
    $bptoken = get_optionvals($bppxrow['bptoken']);
    $plantokenarr = get_optionvals($bppxrow['plantoken']);
    $filterstatus = ($plantokenarr['isfreedoact'] == 1) ? " AND mpstatus < '3' AND mpid != '{$mbrstr['mpid']}'" : " AND (mpstatus = '1' OR mpstatus = '2')";
    $filterstatus .= $restricbymppid;
    $mysprlist = "|1:" . $mpid . "|";
    $condition = " AND sprlist LIKE '%{$mysprlist}%'" . $filterstatus;
    $row = $db->getAllRecords(DB_TBLPREFIX . '_mbrplans', 'COUNT(*) as totref', $condition);
    $total = intval($row[0]['totref']);

    if ($total >= $sprstr['mpwidth']) {
        if ($bppxrow['spillover'] == 1) {
            $count_subrefsql = ", (SELECT COUNT(*) FROM " . DB_TBLPREFIX . "_mbrplans WHERE sprlist LIKE ovrid AND mpid != '{$mbrstr['mpid']}') as totsubref ";
            $ordby = 'totsubref ASC, reg_utctime ASC, mpid ASC';
        } else {
            $count_subrefsql = '';
            $ordby = 'reg_utctime ASC, mpid ASC, idmbr ASC';
        }

        $maxdeepexd = ($cfgtoken['isonetopref'] == '1') ? 25 : $maxdeepexd;
        for ($i = 1; $i <= $maxdeepexd; $i++) {
            $tmpmpid = array();
            $mpidx = "";

            $directsprlist = "|" . $i . ":" . $mpid . "|";
            $condition = " AND sprlist LIKE '%{$directsprlist}%'" . $filterstatus;
            $userData = $db->getRecFrmQry("SELECT mpid, mpstatus, CONCAT('%|1:',mpid,'|%') AS ovrid {$count_subrefsql} FROM " . DB_TBLPREFIX . "_mbrplans WHERE 1 " . $condition . " ORDER BY " . $ordby);
            if (count($userData) > 0) {
                foreach ($userData as $val) {
                    $mpidx = $val['mpid'];
                    $tmpmpid[] = $mpidx;
                    $subsprlist = "|1:" . $mpidx . "|";
                    $subcondition = " AND sprlist LIKE '%{$subsprlist}%'" . $filterstatus;
                    $row = $db->getAllRecords(DB_TBLPREFIX . '_mbrplans', 'COUNT(*) as totref', $subcondition);
                    $myreftotal = $row[0]['totref'];
                    if ($myreftotal < $sprstr['mpwidth'] && $mpidx > 0) {
                        if ($bppxrow['minref4splovr'] > 0) {
                            $sprrow = getmbrinfo('', '', $mpidx);
                            $refcondition = " AND idref = '{$sprrow['id']}'" . $filterstatus;
                            $row = $db->getAllRecords(DB_TBLPREFIX . '_mbrplans', 'COUNT(*) as totref', $refcondition);
                            $myperdltotal = $row[0]['totref'];
                            if ($myperdltotal < $bppxrow['minref4splovr']) {
                                continue;
                            }
                        }
                        if ($val['mpstatus'] == '1' || ($plantokenarr['isfreedoact'] == 1 && $val['mpstatus'] < '3')) {
                            return $mpidx;
                            exit;
                        }
                    }
                }
            }
        }

        if ($bppxrow['ifrollupto'] == 1) {
            foreach ((array) $tmpmpid as $key => $val) {
                $subsprlist = "|1:" . $val . "|";
                $subcondition = " AND sprlist LIKE '%{$subsprlist}%'" . $filterstatus;
                $row = $db->getAllRecords(DB_TBLPREFIX . '_mbrplans', 'COUNT(*) as totref', $subcondition);
                $myreftotal = $row[0]['totref'];
                if ($myreftotal < $sprstr['mpwidth']) {
                    if ($bppxrow['minref4splovr'] > 0) {
                        $sprrow = getmbrinfo('', '', $val);
                        $refcondition = " AND idref = '{$sprrow['id']}'" . $filterstatus;
                        $row = $db->getAllRecords(DB_TBLPREFIX . '_mbrplans', 'COUNT(*) as totref', $refcondition);
                        $myperdltotal = $row[0]['totref'];
                        if ($myperdltotal < $bppxrow['minref4splovr']) {
                            continue;
                        }
                    }
                    $mpidx = getmpidflow($val, $regonmppid, $mbrstr);
                    if ($mpidx > 0) {
                        return $mpidx;
                        exit;
                    }
                }
            }
        } else {
            return 0;
        }
    } else {
        return $mpid;
    }
}

function dosprlist($mpid, $sprlist, $mpdepth) {
    global $cfgtoken;

    $mbrmaxgendepth = 99;
    $mpdepth = ($cfgtoken['isonetopref'] == '1') ? $mbrmaxgendepth : $mpdepth;
    $sprlist = str_replace(' ', '', $sprlist ?? '');
    $sprlistarr = explode(',', $sprlist ?? '');
    $pos = 2;
    $mpid = intval($mpid);
    $newsprlist = array("|1:{$mpid}|");
    if ($mpid > 0) {
        foreach ($sprlistarr as $key => $value) {
            $valarr = explode(':', $value ?? '');
            $sprval = intval(str_replace('|', '', $valarr[1] ?? ''));
            if ($mpid == $sprval) {
                continue;
            }
            $newsprlist[] = "|{$pos}:{$sprval}|";
            if ($sprval < 1) {
                break;
            } else {
                $pos++;
            }
        }
        if ($mpdepth > 0) {
            $newsprlist = array_slice($newsprlist, 0, $mpdepth);
        }
    }
    $newsprout = implode(', ', $newsprlist);
    return $newsprout;
}

function getsprlistid($sprlist, $tier = '') {
    $mpid = [];
    $sprlist = str_replace(array(' ', '|'), '', $sprlist ?? '');
    $sprlistarr = explode(',', $sprlist ?? '');
    foreach ($sprlistarr as $key => $value) {
        $valarr = explode(':', $value ?? '');
        $postier = intval($valarr[0]);
        $valtier = intval($valarr[1]);
        if ($tier != '' && $postier != $tier) {
            continue;
        }
        $mpid[$postier] = $valtier;
    }
    return $mpid;
}

function getamount($xcm, $regfee, $mrank = 0) {
    $cm = str_replace(' ', '', $xcm ?? '');
    if (floatval($regfee) <= 0) {
        $resamount = (strpos($cm ?? '', '%') !== false) ? 0 : $cm;
    } else {
        $resamount = (strpos($cm ?? '', '%') !== false) ? $cm * $regfee / 100 : $cm;
    }
    $resamountstr = sprintf('%0.2f', $resamount);
    return $resamountstr;
}

function getcmlist($sprstr, $sprlist, $cmlist, $mbrstr = array(), $trxstr = array(), $rktokencm = []) {
    global $db, $bpparr, $frlmtdcfg;

    $sprcmlist = [];
    $sprppstr = getmbrinfo($sprstr['id'], '', '', $mbrstr['mppid']);
    if ((in_array($mbrstr['mppid'], $sprppstr['pparr_all']) && $sprppstr['reflink'] != '') || $frlmtdcfg['isregallrefs'] == 1) {
        $maxcmfromreg = ($bpparr[$mbrstr['mppid']]['regmaxcm'] > 0) ? $bpparr[$mbrstr['mppid']]['regmaxcm'] : $bpparr[$mbrstr['mppid']]['regfee'];
        $maxcmfromrenew = ($bpparr[$mbrstr['mppid']]['renewmaxcm'] > 0) ? $bpparr[$mbrstr['mppid']]['renewmaxcm'] : $bpparr[$mbrstr['mppid']]['renewfee'];
        $mbr_fee = (strpos($trxstr['txtoken'] ?? '', '|RENEW:') !== false) ? $mbrstr['renew_fee'] : $mbrstr['reg_fee'];
        $plan_fee = (strpos($trxstr['txtoken'] ?? '', '|RENEW:') !== false) ? $maxcmfromrenew : $maxcmfromreg;
        $plan_feenow = ($plan_fee <= 0 && $maxcmfromreg > 0) ? $maxcmfromreg : $plan_fee;
        $regnow_fee = (defined('ISAMOUNT_BYMBR')) ? $mbr_fee : $plan_feenow;
        $mpdepth = $mbrstr['mpdepth'];
        $minref2getcm = ($frlmtdcfg['isgencmbyup'] != 1) ? $bpparr[$mbrstr['mppid']]['minref2getcm'] : $bpparr[$sprstr['mppid']]['minref2getcm'];
        $minref2getcmarr = explode(',', trim($minref2getcm ?? ''));
        $sprlistarr = explode(',', str_replace(array(' ', '|'), '', $sprlist ?? ''));
        $defppid = ($frlmtdcfg['isgencmbyup'] != 1 && $frlmtdcfg['isregallrefs'] != 1) ? $mbrstr['mppid'] : $sprstr['mppid'];
        $sprppidcmarr = get_sprppcm($defppid, $cmlist);
        for ($i = 0; $i < $mpdepth; $i++) {
            $j = $i + 1;
            $valarr = explode(':', $sprlistarr[$i] ?? '');
            $sprval = intval($valarr[1]);
            if ($sprval < 1) {
                break;
            }
            $sprlvlstr = getmbrinfo('', '', $sprval);
            $sprpidcm = $sprcm = $sprcmrank = $sprtotrefonly = 0;
            $minrefontier = $minref2getcmarr[$i];
            if ($minrefontier > 0) {
                $condition = " AND idref = '{$sprlvlstr['id']}' AND (mpstatus = '1' OR mpstatus = '2') AND mpid != '{$mbrstr['mpid']}' ";
                $row = $db->getAllRecords(DB_TBLPREFIX . '_mbrplans', 'COUNT(*) as totref', $condition);
                $sprtotrefonly = $row[0]['totref'];
            }
            $sprpidcm = $sprppidcmarr[$sprlvlstr['mppid']][$j];
            $sprcmrank = $sprcm = getamount($sprpidcm, $regnow_fee);
            $minrefcmarr = array('adjcmlistrnew', 'adjcmlist');
            if (!in_array($rktokencm, $minrefcmarr) || $minrefontier <= $sprtotrefonly) {
                $sprcmrank = get_netcmrank($rktokencm, $sprlvlstr['mprankid'], $sprcm, $i);
            }
            $sprcmlist[$sprval] = $sprcmrank;
        }
    }
    return $sprcmlist;
}

function addcmlist($memo, $tokencode, $valcmlist = array(), $mbrstr = array(), $trxstr = array(), $addtxtoken = '') {
    global $db, $cfgrow, $bpprow;

    if (!function_exists('delivermail')) {
        require_once(INSTALL_PATH . '/common/mailer.do.php');
    }
    $reg_utctime = $cfgrow['datetimestr'];
    $addtxtoken = ($addtxtoken != '') ? ', ' . trim($addtxtoken ?? '', ',') : '';
    $refarrtot = count(array_filter($valcmlist));
    $cmcount = $cmdocount = 0;
    foreach ((array) $valcmlist as $key => $value) {
        $cmcount++;
        $sprstr = getmbrinfo('', '', $key);
        $txamount = (float) $value;
        $txtoken = "|SRCTXID:{$trxstr['txid']}|, |SRCIDMBR:{$mbrstr['id']}|, |SRCSLID:{$trxstr['newslid']}|, |SRCLVPOS:{$cmcount}|, |LCM:{$tokencode}|";
        $txonehash = md5($sprstr['id'] . $txamount . $mbrstr['mppid'] . $txtoken ?? '');
        $condition = " AND txtoken LIKE '%|txonehash:$txonehash|%'";
        $existTxData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_transactions WHERE 1 " . $condition . "");
        if (count($existTxData) > 0) {
            continue;
        }
        if ($txamount > 0) {
            $cmcountstr = (strpos($tokencode ?? '', 'TIER') !== false) ? " [{$cmcount}]" : '';
            $cmcountstr = (strpos($tokencode ?? '', 'PREF') !== false && $refarrtot > 1) ? " [{$cmcount}]" : $cmcountstr;
            $data = array(
                'txdatetm' => $reg_utctime,
                'txtoid' => intval($sprstr['id']),
                'txamount' => $txamount,
                'txmemo' => $memo . $cmcountstr,
                'txppid' => $mbrstr['mppid'],
                'txtoken' => $txtoken . $addtxtoken . ", |txonehash:$txonehash|",
            );
            $insert = $db->insert(DB_TBLPREFIX . '_transactions', $data);
            $cmdocount++;

            if ($insert && $sprstr['id'] > 0) {
                $cntaddarr['ncm_memo'] = $memo . $cmcountstr;
                $cntaddarr['ncm_amount'] = $bpprow['currencysym'] . printmoney($txamount) . ' ' . $bpprow['currencycode'];
                $cntaddarr['dln_username'] = $mbrstr['username'];
                delivermail('mbr_newcm', $sprstr['id'], $cntaddarr);
            }
        }
    }
    $cmcountarr['cmcount'] = $cmcount;
    $cmcountarr['cmadded'] = $cmdocount;
    return $cmcountarr;
}

function dolvldone($mbrstr, $trxstr, $mppid = 1) {
    global $db, $bptoken, $bpparr, $frlmtdcfg;

    $bpnowplantokenarr = get_optionvals($bpparr[$mbrstr['mppid']]['plantoken']);
    $totgenreentryacc = ($bpnowplantokenarr['totgenreentryacc'] < 1) ? 1 : $bpnowplantokenarr['totgenreentryacc'];
    $dirsprstr = ($frlmtdcfg['isregallrefs'] == 1) ? getmbrinfo($mbrstr['idspr']) : getmbrinfo($mbrstr['idspr'], '', '', $mbrstr['mppid']);
    $mpidspr = ($dirsprstr['mppid'] > $mbrstr['mppid']) ? $mbrstr['mppid'] : $dirsprstr['mppid'];
    $rwlist = ($frlmtdcfg['isgencmbyup'] != 1) ? $bpparr[$mbrstr['mppid']]['rwlist'] : $bpparr[$mpidspr]['rwlist'];
    for ($i = 1; $i <= $mbrstr['mpdepth']; $i++) {
        $mpidarr = getsprlistid($mbrstr['sprlist'], $i);
        $mpid = $mpidarr[$i];
        if ($mpid < 1 || $mbrstr['mpwidth'] <= 0) {
            break;
        } else {
            $sprtag = "|{$i}:{$mpid}|";
            $condition = " AND sprlist LIKE '%{$sprtag}%' AND mpstatus != '0'";
            $row = $db->getAllRecords(DB_TBLPREFIX . '_mbrplans', 'COUNT(*) as totref', $condition);
            $myreftotal = $row[0]['totref'];
            $ix = $i;
            if (pow($mbrstr['mpwidth'], $ix) == $myreftotal) {
                $sprstr = getmbrinfo('', '', $mpid);
                $rwdx = "FRWD{$mpid}-{$ix}";
                $condition = ' AND txtoid = "' . $sprstr['id'] . '" AND txppid = "' . $mbrstr['mppid'] . '" AND txtoken LIKE "' . "%|LCM:{$rwdx}|%" . '" ';
                $sql = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_transactions WHERE 1 " . $condition . "");
                if (count($sql) < 1) {
                    $iy = $ix - 1;
                    $rwlistarr = explode(',', str_replace(' ', '', $rwlist ?? ''));
                    $fixedrwd = getamount($rwlistarr[$iy], $trxstr['txamount']);
                    $getarrcmlist = array($sprstr['mpid'] => $fixedrwd);
                    addcmlist("Level Reward", "{$rwdx}", $getarrcmlist, $mbrstr, $trxstr);
                    dotrxwallet();
                }

                $data = array(
                    'recyclingit' => $ix,
                );
                $db->update(DB_TBLPREFIX . '_mbrplans', $data, array('mpid' => $mpid));

                if ($mbrstr['mpdepth'] == $i) {
                    $mbrcyc = getmbrinfo('', '', $mpid);
                    $isrecyclingdiffpp = $bptoken['isrecyclingdiffpp'];
                    $isrecyclingok = ($isrecyclingdiffpp != '1' && $mbrstr['mppid'] != $mbrcyc['mppid']) ? false : true;
                    $isrecycling = $bpparr[$mbrstr['mppid']]['isrecycling'];
                    if ($isrecycling > 0 && $isrecyclingok) {
                        if ($isrecycling == 3) {
                            // process cycling fee only [plan repayment]
                            $nowregfee = $bpparr[$mbrstr['mppid']]['regfee'];
                            $newamount = $mbrcyc['ewallet'] - $nowregfee;
                            $recycppname = $bpparr[$mbrstr['mppid']]['ppname'];
                            adjusttrxwallet($mbrcyc['ewallet'], $newamount, $mbrcyc['id'], "Repayment {$recycppname}");
                            $data = array(
                                'ewallet' => $newamount,
                            );
                            $db->update(DB_TBLPREFIX . '_mbrs', $data, array('id' => $mbrcyc['id']));
                        } else {
                            // re-entry to the same plan
                            if ($isrecycling == 4) {
                                // Re-entry to cycling structure
                                $entrytoidmbr = $mbrcyc['id'];
                            } else if ($isrecycling == 1) {
                                // Re-entry follow sponsor
                                $entrytoidmbr = $mbrcyc['idspr'];
                            } else {
                                // Re-entry follow referrer ($isrecycling == 2)
                                $entrytoidmbr = $mbrcyc['idref'];
                            }
                            for ($i = 1; $i <= $totgenreentryacc; $i++) {
                                $refnowarr = get_toprefnow($entrytoidmbr, $mbrstr['mppid']);
                                $entrytoidmbr = $refnowarr['id'];
                                do_autoregplan($mbrcyc, $mbrstr['mpid'], $entrytoidmbr, $mbrcyc['mppid']);
                            }
                        }
                    }

                    // re-entry to another plan
                    $recyclingto = $bpparr[$mbrstr['mppid']]['recyclingto'];
                    if ($recyclingto > 0) {
                        if ($isrecycling == 1) {
                            $entrytoidmbr = $mbrcyc['idspr'];
                        } else {
                            $entrytoidmbr = $mbrcyc['idref'];
                        }

                        for ($i = 1; $i <= $totgenreentryacc; $i++) {
                            $refnowarr = get_toprefnow($entrytoidmbr, $mbrstr['mppid']);
                            $entrytoidmbr = $refnowarr['id'];
                            do_autoregplan($mbrcyc, $mbrstr['mpid'], $entrytoidmbr, $recyclingto);
                        }
                    }

                    // process cycling fee
                    $recyclingfee = $bpparr[$mbrstr['mppid']]['recyclingfee'];
                    if (floatval($recyclingfee) > 0) {
                        $maxfullrward = $getarrcmlist[$mbrcyc['mpid']];
                        $nowregfee = $bpparr[$mbrstr['mppid']]['regfee'];
                        $nextregfee = $bpparr[$recyclingto]['regfee'];
                        $netpoolrwrd = $maxfullrward - $nowregfee - $nextregfee;
                        $getcycfee = getamount($recyclingfee, $netpoolrwrd);
                        $newamount = $mbrcyc['ewallet'] - $getcycfee;
                        $recycppname = $bpparr[$mbrstr['mppid']]['ppname'];
                        adjusttrxwallet($mbrcyc['ewallet'], $newamount, $mbrcyc['id'], "Admin Charge {$recycppname}");
                        $data = array(
                            'ewallet' => $newamount,
                        );
                        $db->update(DB_TBLPREFIX . '_mbrs', $data, array('id' => $mbrcyc['id']));
                    }
                }
            }
        }
    }
}

function regmbrplans($mbrstr = array(), $refidmbr = '', $thisppid = 0, $existmpid = 0) {
    global $db, $cfgrow, $cfgtoken, $bpprow, $LANG, $frlmtdcfg;

    $existmpid = intval($existmpid);

    $resultarr = [];
    $resultarr['mpid'] = $resultarr['txid'] = $resultarr['regfee'] = 0;

    if ($thisppid < 1) {
        // place new referral under the last registered plan
        $refstr = getmbrinfo($refidmbr);
    } else {
        // place new referral under the same plan
        $refstr = getmbrinfo($refidmbr, '', '', $thisppid);
    }
    $refstrtmp = $refstr;
    $ppid = ($refidmbr > 0 && $frlmtdcfg['isxplans'] == 1) ? intval($refstr['mppid']) : $thisppid;
    $orirefmpid = intval($refstr['mpid']);

    if ($frlmtdcfg['isxplans'] == 1) {
        $pprefarr = ($refstr['reflink'] != '') ? $refstr['pparr_all'] : $refstr['pparr_act'];
        if (in_array($ppid, $pprefarr)) {
            // if referrer registered to the plan and active
            $refstr = getmbrinfo($refidmbr, '', '', $ppid);
        } else {
            // if referrer is not registered to the same plan, use admin as sposnor
            $refstr = getmbrinfo('', '', 0);
        }
        $restricbymppid = $ppid;
    } else {
        $restricbymppid = 0;
    }

    if ($refstr['idmbr'] <= 0 && $cfgtoken['isgetnewref'] == 'new' && $thisppid > 0) {
        $condition = " AND mpstatus = '1' AND mppid = '{$thisppid}' AND idmbr > '0'";
        $row = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_mbrplans WHERE 1 " . $condition . " ORDER BY cyclingbyid DESC LIMIT 1");
        $newrefid = $row[0];
        $refstr = getmbrinfo('', '', $newrefid['mpid']);
    }

    // disable self referring
    if ($refstr['username'] == $mbrstr['username'] && $frlmtdcfg['isselfreferring'] != 1) {
        $refstr = getmbrinfo('', '', 0);
    }

    $refmpid = intval($refstr['mpid']);
    $mppid = intval($ppid);
    $idref = intval($refstr['id']);
    $idmbr = $mbrstr['id'];

    // stages
    $stgId = $mppid;
    if ($stgId < 1 || $stgId > $frlmtdcfg['mxstages']) {
        $stgId = 1;
    }
    $row = $db->getAllRecords(DB_TBLPREFIX . '_payplans', '*', ' AND ppid = "' . $stgId . '"');
    foreach ($row as $value) {
        $bpprow = array_merge((array) $bpprow, $value);
    }

    $condition = " AND idmbr = '{$idmbr}' AND mppid = '{$stgId}' AND cyclingbyid = '0'";
    $sql = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_mbrplans WHERE 1 " . $condition . "");
    if (($bpprow['planstatus'] == 1 && count($sql) < 1) || $frlmtdcfg['isxplans'] == 1) {
        $reg_date = $cfgrow['datestr'];
        $reg_utctime = $cfgrow['datetimestr'];
        $reg_ip = get_userip();

        $mpstatus = ($bpprow['regfee'] <= 0) ? 1 : 0;
        $reg_expd = $reg_date;

        $is_plansubscr = is_plansubscr($stgId);
        if ($is_plansubscr) {
            $expdarr = get_actdate($bpprow['expday']);
            $reg_expd = $expdarr['next'];
        }

        $renew_fee = ($bpprow['renewfee'] > 0) ? floatval($bpprow['renewfee']) : floatval($bpprow['regfee']);

        $rprmpid = getmpidflow($refmpid, $restricbymppid, $mbrstr);
        $sprstr = ($frlmtdcfg['isxplans'] == 1) ? getmbrinfo('', '', $rprmpid) : getmbrinfo($refstr['id']);
        $idspr = intval($sprstr['id']);

        // self referring
        if (($idmbr == $idref || $idmbr == $idspr) && $frlmtdcfg['isselfreferring'] != 1) {
            $idref = $idspr = 0;
            $sprlist = '';
        } else {
            $sprlist = dosprlist($sprstr['mpid'], $sprstr['sprlist'], $sprstr['mpdepth']);
        }

        // checking if member exist
        $existmbr = getmbrinfo('', '', $existmpid);

        // reset default plan
        $resetidmbr = ($existmbr['idmbr'] > 0) ? $existmbr['idmbr'] : $idmbr;
        $data = ['isdefault' => '0'];
        $db->update(DB_TBLPREFIX . '_mbrplans', $data, array('idmbr' => $resetidmbr));

        $isexistmpid = ($existmbr['mpid'] > 0) ? $existmpid : 0;
        if ($isexistmpid > 0) {
            $data = array(
                'mppid' => $stgId,
                'isdefault' => 1,
                'reg_date' => $reg_date,
                'reg_expd' => $reg_expd,
                'reg_ip' => $reg_ip,
                'reg_fee' => (float) $bpprow['regfee'],
                'renew_fee' => (float) $renew_fee,
                'mpstatus' => $mpstatus,
                'idref' => $idref,
                'idspr' => $idspr,
                'sprlist' => $sprlist,
                'mpwidth' => $bpprow['maxwidth'],
                'mpdepth' => $bpprow['maxdepth'],
            );
            $update = $db->update(DB_TBLPREFIX . '_mbrplans', $data, array('mpid' => $isexistmpid));
            $resultarr['mpid'] = $isexistmpid;
        } else {
            $idhostmbr = $orirefmpid;
            $hostspr = ($idspr != $refstr['id']) ? $idspr : $refstr['id'];

            if ($frlmtdcfg['ismultiprogs'] == 1) {
                $condition = ' AND idmbr = "' . $mbrstr['id'] . '" ';
                $row = $db->getAllRecords(DB_TBLPREFIX . '_mbrplans', 'COUNT(*) as totmpid', $condition);
                $isdocount = $row[0]['totmpid'];
                $aliasname = $mbrstr['username'] . $isdocount;
            } else {
                $aliasname = '';
            }

            $data = array(
                'idhostmbr' => $idhostmbr,
                'idmbr' => $idmbr,
                'mppid' => $stgId,
                'isdefault' => 1,
                'reg_date' => $reg_date,
                'reg_expd' => $reg_expd,
                'reg_utctime' => $reg_utctime,
                'reg_ip' => $reg_ip,
                'reg_fee' => (float) $bpprow['regfee'],
                'renew_fee' => (float) $renew_fee,
                'mpstatus' => $mpstatus,
                'hostspr' => $hostspr,
                'idref' => $idref,
                'idspr' => $idspr,
                'sprlist' => $sprlist,
                'mpwidth' => $bpprow['maxwidth'],
                'mpdepth' => $bpprow['maxdepth'],
            );
            $insert = $db->insert(DB_TBLPREFIX . '_mbrplans', $data);
            $newmbrplanid = $db->lastInsertId();
            $resultarr['mpid'] = $newmbrplanid;
        }
        // get updated $mbrstr
        $mbrstr = getmbrinfo('', '', $resultarr['mpid']);

        // do webhook - member approval
        $datalistarr['status'] = $mpstatus;
        do_mbrwebhook($mbrstr, $datalistarr);

        $resultarr['idref'] = $mbrstr['idref'];
        $resultarr['idspr'] = $mbrstr['idspr'];

        if ($update || $insert) {
            $_SESSION['dotoaster'] = "toastr.success('{$LANG['g_toastsuccessinfo']}', '{$LANG['g_toastsuccess']}');";

            // add transaction records
            if ($bpprow['regfee'] > 0) {
                $data = array(
                    'txdatetm' => $reg_utctime,
                    'txfromid' => $idmbr,
                    'txamount' => (float) $bpprow['regfee'],
                    'txmemo' => $LANG['g_registrationfee'],
                    'txppid' => $stgId,
                    'txtoken' => "|REG:{$resultarr['mpid']}|",
                );
                $insert = $db->insert(DB_TBLPREFIX . '_transactions', $data);
                $newtrxid = $db->lastInsertId();
                $resultarr['txid'] = $newtrxid;
                $resultarr['regfee'] = (float) $bpprow['regfee'];
            }

            // send new referral signup
            if ($idspr > 0 && $isexistmpid == 0) {
                if (!function_exists('delivermail')) {
                    require_once(INSTALL_PATH . '/common/mailer.do.php');
                }
                $cntaddarr['ppname'] = $bpprow['ppname'];
                $cntaddarr['dln_fullname'] = $mbrstr['firstname'] . " " . $mbrstr['lastname'];
                $cntaddarr['dln_username'] = $mbrstr['username'];
                delivermail('mbr_newdl', $idspr, $cntaddarr);
            }
        } else {
            $_SESSION['dotoaster'] = "toastr.error('{$LANG['g_toastfailinfo']}', '{$LANG['g_toastfail']}');";
        }

        return $resultarr;
    } else {
        $resultarr['errstr'] = "Already registered to {$bpprow['ppname']}!";
        return $resultarr;
    }
}

function iscontentmbr($pgavalon, $pgppids, $mbrstr) {
    $hasil = true;

    $mppidarr = mbrpparr($mbrstr['id']);
    $mbrppidarr = $mppidarr['mppid'];
    $pgppidsarr = str_getcsv($pgppids ?? '');
    $tmatch = count(array_intersect((array) $mbrppidarr, $pgppidsarr));
    $avalfor = get_optionvals($pgavalon);

    if ($avalfor['mbr'] == '1') {
        if ($avalfor['mbpp1'] != '1' && $mbrstr['mpstatus'] == 1) {
            $hasil = false;
        }
        if ($avalfor['mbpp0'] != '1' && $mbrstr['mpstatus'] != 1) {
            $hasil = false;
        }
        if ($tmatch == 0) {
            $hasil = false;
        }
    } else {
        if ($tmatch == 0 && $mbrstr['mpid'] > 0) {
            $hasil = false;
        }
    }

    return $hasil;
}

function dotrxwallet($txtoid = 0, $limit = 25) {
    global $db, $cfgrow;

    $sqltoid = ($txtoid == 0) ? "txtoid > '0'" : "txtoid = '{$txtoid}'";
    $ListData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_transactions WHERE 1 AND txfromid = '0' AND " . $sqltoid . " AND txstatus = '0' AND txtoken NOT LIKE '%|WIDR:%' LIMIT {$limit}");
    if (count($ListData) > 0) {
        $numcount = $ewallet = 0;
        $txtmstamp = $cfgrow['datetimestr'];
        foreach ($ListData as $val) {

            $txbatch = 'WLN' . date("dH-ims-") . $val['txid'];
            $txtoken = $val['txtoken'] . ', |WALT:IN|';

            $data = array(
                'txpaytype' => 'system',
                'txbatch' => $txbatch,
                'txtmstamp' => $txtmstamp,
                'txtoken' => $txtoken,
                'txstatus' => 1,
            );
            $update = $db->update(DB_TBLPREFIX . '_transactions', $data, array('txid' => $val['txid']));

            $mbrstr = getmbrinfo($val['txtoid']);
            $ewallet = $mbrstr['ewallet'] + $val['txamount'];
            $update = $db->update(DB_TBLPREFIX . '_mbrs', array('ewallet' => $ewallet), array('id' => $mbrstr['id']));

            $numcount++;
            if ($numcount < 1) {
                break;
            }
        }
    }
}

function adjusttrxwallet($oldamount, $newamount, $idmbr, $txtokenstr = '', $txadminfo = '', $isminval = 0, $addtxtoken = '') {
    global $db, $cfgrow, $LANG;

    if ($oldamount != $newamount && ($newamount > 0 || $isminval == 1)) {

        $hittxrow = $db->getRecFrmQry("SELECT COUNT(txid) as hittx FROM " . DB_TBLPREFIX . "_transactions");
        $hittx = $hittxrow[0]['hittx'] + 1;
        $numrand = mt_rand(10, 99);
        $txbatch = date("dH-{$idmbr}i-s{$numrand}{$hittx}");
        if ($oldamount < $newamount) {
            // add
            $txfromid = 0;
            $txtoid = $idmbr;
            $txamount = $newamount - $oldamount;
            $txmemo = "Wallet Credit Correction";
            $txbatch = 'WLN' . $txbatch;
            $txtoken = '|WALT:IN|';
        } else {
            // deduct
            $txfromid = $idmbr;
            $txtoid = 0;
            $txamount = $oldamount - $newamount;
            $txmemo = "Wallet Debit Correction";
            $txbatch = 'WLT' . $txbatch;
            $txtoken = '|WALT:OUT|';
        }

        $mbrstr = getmbrinfo($idmbr);
        $txamount = (float) $txamount;

        $txtoken64 = base64_encode($txtokenstr);
        $txtoken = $txtoken . ", |NOTE:{$txtoken64}|";
        $txtoken = ($addtxtoken != '') ? $txtoken . ", " . $addtxtoken : $txtoken;

        $txdatetm = $cfgrow['datetimestr'];
        $data = array(
            'txdatetm' => $txdatetm,
            'txfromid' => $txfromid,
            'txtoid' => $txtoid,
            'txpaytype' => 'other',
            'txamount' => $txamount,
            'txmemo' => $txmemo,
            'txbatch' => $txbatch,
            'txtmstamp' => $txdatetm,
            'txppid' => $mbrstr['mppid'],
            'txstatus' => 1,
            'txtoken' => $txtoken,
            'txadminfo' => $txadminfo,
        );

        $insert = $db->insert(DB_TBLPREFIX . '_transactions', $data);
        $newtrxid = $db->lastInsertId();
        return $newtrxid;
    }
}

function getwebssdata($mbrstr, $url) {
    $mbrid = $mbrstr['id'];
    if (function_exists('curl_init') && intval($mbrid) > 0 && filter_var($url, FILTER_VALIDATE_URL) !== FALSE && $_SESSION['getwebssdata' . $mbrid] == '') {
        $ch = curl_init("htt" . "ps://ww" . 'w.goog' . "leapis.c" . 'om/pagespeed' . "online/v5/runPagespeed?url={$url}&screenshot=true");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        curl_close($ch);
        $googlepsdata = json_decode($response ?? '', true);
        $snap = $googlepsdata['lighthouseResult']['audits']['final-screenshot']['details']['data'];

        if ($snap) {
            $screen_shot = str_replace("data:image/jpeg;base64,", "", $snap ?? '');
            $imgsnap = base64_decode($screen_shot ?? '');
            $imgtofile = "/assets/imagextra/mbr_imgweb_{$mbrid}.jpg";
            $datfile = INSTALL_PATH . $imgtofile;
            file_put_contents($datfile, $imgsnap, LOCK_EX);
            $_SESSION['getwebssdata' . $mbrid] = 1;
            return $imgtofile;
        }
    }
}

function do_imgresize($targetFile, $originalFile, $newWidth, $newHeight = 0, $ext = '') {

    $info = getimagesize($originalFile);
    $mime = ($ext == '') ? $info['mime'] : "image/{$ext}";

    switch ($mime) {
        case 'image/jpeg':
            $image_save_func = 'imagejpeg';
            $new_image_ext = 'jpg';
            break;

        case 'image/png':
            $image_save_func = 'imagepng';
            $new_image_ext = 'png';
            break;

        case 'image/gif':
            $image_save_func = 'imagegif';
            $new_image_ext = 'gif';
            break;

        default:
            exit();
    }

    $img = imagecreatefromstring(file_get_contents($originalFile));
    list($width, $height) = getimagesize($originalFile);
    $propHeight = ($height / $width) * $newWidth;
    $newHeight = ($newHeight > 0) ? $newHeight : $propHeight;
    $tmp = imagecreatetruecolor((int) $newWidth, (int) $newHeight);
    imagecopyresampled($tmp, $img, 0, 0, 0, 0, (int) $newWidth, (int) $newHeight, $width, $height);
    $targetFile = '../assets/imagextra/' . $targetFile;
    if (file_exists($targetFile)) {
        unlink($targetFile);
    }
    $newimg = "$targetFile.$new_image_ext";
    $image_save_func($tmp, $newimg);
    return $newimg;
}

function get_actdate($intvdatetime, $basedate = '') {
    global $cfgrow;

    $basedate = ($basedate == '') ? date('Y-m-d H:i:s', time() + (3600 * $cfgrow['time_offset'])) : $basedate;
    $arrdate = getdate(strtotime($basedate ?? ''));
    $istime = (strlen($basedate ?? '') > 12 && $arrdate['hours'] != '') ? 'y' : 'n';

    $result = [];
    $intvdatetime = str_replace(" ", "", strtoupper($intvdatetime ?? ''));
    if (!is_numeric($intvdatetime)) {
        $result['var'] = substr($intvdatetime, -1);
        $result['val'] = str_replace($result['var'] ?? '', "", $intvdatetime ?? '');
        $result['val'] = intval($result['val']);

        $varforval = strtoupper($result['var']);
        switch ($varforval) {
            case "H":
                $result['var_str'] = 'Hour';
                $result['val_str'] = $result['val'] * 0;
                $strjng = 'hour';
                break;
            case "W":
                $result['var_str'] = 'Week';
                $result['val_str'] = $result['val'] * 7;
                $strjng = 'week';
                break;
            case "M":
                $result['var_str'] = 'Month';
                $result['val_str'] = $result['val'] * 30;
                $strjng = 'month';
                break;
            case "Y":
                $result['var_str'] = 'Year';
                $result['val_str'] = $result['val'] * 365;
                $strjng = 'year';
                break;
            default:
                $result['var_str'] = 'Day';
                $result['val_str'] = $result['val'];
                $strjng = 'day';
        }
        if ($result['val'] > 1)
            $strjng .= 's';
    } else {
        $result['var'] = 'D';
        $result['var_str'] = 'Day';
        $strjng = 'day';
        $result['val'] = $result['val_str'] = intval($intvdatetime);
        if ($result['val'] > 1)
            $strjng .= 's';
    }

    $str_basedate = strtotime($basedate ?? '');
    $str_diffdate = $result['val'] . ' ' . $strjng;
    $str_basedate_add = strtotime("+" . $str_diffdate ?? '', $str_basedate);
    $str_basedate_les = strtotime("-" . $str_diffdate ?? '', $str_basedate);

    $result['unit'] = $strjng;
    if ($istime == 'y') {
        $result['next'] = date("Y-m-d H:i:s", $str_basedate_add);
        $result['prev'] = date("Y-m-d H:i:s", $str_basedate_les);
    } else {
        $result['next'] = date("Y-m-d", $str_basedate_add);
        $result['prev'] = date("Y-m-d", $str_basedate_les);
    }

    $result['now'] = $basedate;
    $dateTimeEnd = $result['next'];
    $dateTimeBegin = $result['now'];
    $timedifference = strtotime($dateTimeEnd ?? '') - strtotime($dateTimeBegin ?? '');
    $result['diffdays'] = floor($timedifference / 86400);

    return $result;
}

function get_unpaidtxid($mbrstr, $itid = 0) {
    global $db;

    if ($itid > 0) {
        $condition = " AND txitid = '{$itid}' AND (txtoken LIKE '%|STORE:%')";
    } else {
        $condition = " AND txppid = '{$mbrstr['mppid']}' AND (txtoken LIKE '%|REG:%' OR txtoken LIKE '%|RENEW:%')";
    }
    $txunpaidrow = $db->getRecFrmQry("SELECT txid FROM " . DB_TBLPREFIX . "_transactions WHERE txfromid = '{$mbrstr['id']}' AND txamount > 0 AND txstatus = '0'{$condition} ORDER BY txid DESC");
    return $txunpaidrow[0]['txid'];
}

function do_expmbr($limitcheck = 48) {
    global $db, $cfgrow, $bptoken, $bpparr;

    $reg_utctime = $cfgrow['datetimestr'];
    $now_date = $cfgrow['datestr'];
    foreach ($bpparr as $key => $value) {

        $graceday = floatval($value['graceday']);
        $is_plansubscr = is_plansubscr($value['ppid']);
        if ($is_plansubscr) {

            //reminder
            $remindreg = $bptoken['remindreg'];
            if (intval($remindreg) > 0) {
                $expdarr = get_actdate($remindreg, $now_date);
                $remindate = $expdarr['next'];
                $condition = " AND mpstatus = '1' AND mppid = '{$value['ppid']}' AND reg_date < reg_expd AND reg_expd <= '{$remindate}' AND mptoken NOT LIKE '%|rmdmbrexp:{$now_date}|%' AND rmdexp = '0' ORDER BY RAND() LIMIT {$limitcheck}";
                $userData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_mbrs LEFT JOIN " . DB_TBLPREFIX . "_mbrplans ON id = idmbr WHERE 1 " . $condition . "");

                if (count($userData) > 0) {
                    foreach ($userData as $val) {
                        // send message here
                        require_once('mailer.do.php');
                        $cntaddarr['ppname'] = $value['ppname'];
                        $cntaddarr['fullname'] = $val['firstname'] . ' ' . $val['lastname'];
                        $cntaddarr['login_url'] = $cfgrow['site_url'] . "/" . MBRFOLDER_NAME;
                        delivermail('mbr_rereg', $val['id'], $cntaddarr);

                        $mptoken = put_optionvals($val['mptoken'], 'rmdmbrexp', $now_date);
                        $db->update(DB_TBLPREFIX . '_mbrplans', array('rmdexp' => '1', 'mptoken' => $mptoken), array('mpid' => $val['mpid']));

                        do_renewtx($reg_utctime, $val);
                    }
                }
            }

            //expired
            $grace_prev = date('Y-m-d', strtotime('-' . $graceday . ' day' ?? '', strtotime($reg_utctime ?? '')));

            $condition = " AND (mpstatus = '1' OR mpstatus = '2') AND mppid = '{$value['ppid']}' AND reg_date < reg_expd AND reg_expd < '{$reg_utctime}' ORDER BY RAND() LIMIT {$limitcheck}";
            $userData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_mbrs LEFT JOIN " . DB_TBLPREFIX . "_mbrplans ON id = idmbr WHERE 1 " . $condition . "");
            if (count($userData) > 0) {
                foreach ($userData as $val) {
                    do_renewtx($reg_utctime, $val);

                    if ($val['mpstatus'] == '1' && $graceday > 0 && $val['reg_expd'] < $grace_prev && $val['reg_date'] < $val['reg_expd'] && $val['reg_fee'] > 0) {
                        $db->update(DB_TBLPREFIX . '_mbrplans', array('mpstatus' => 2), array('mpid' => $val['mpid']));

                        $datalistarr['status'] = '2';
                        do_mbrwebhook($val, $datalistarr);
                    }
                }
            }

            // auto-renewal using available ewallet balance
            $isrenewbywallet = get_optionvals($value['plantoken'], 'isrenewbywallet');

            if ($isrenewbywallet == '1') {
                $condition = " AND mpstatus = '2' ORDER BY RAND() LIMIT {$limitcheck}";
                $userData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_mbrs LEFT JOIN " . DB_TBLPREFIX . "_mbrplans ON id = idmbr WHERE 1 " . $condition . "");
                if (count($userData) > 0) {
                    foreach ($userData as $val) {
                        if ($val['ewallet'] >= $val['renew_fee'] && $val['renew_fee'] > 0) {
                            $mbrstr = getmbrinfo('', '', $val['mpid']);
                            do_walletplanpay($mbrstr);
                        }
                    }
                }
            }
        }
    }
}

function do_delinactivembr($mpstatus = 0, $limitcheck = 48) {
    global $db, $cfgrow, $bptoken, $cfgtoken;

    $timetodelreg = $bptoken['timetodelinactiveacc'];
    if (intval($timetodelreg) > 0) {
        $expdarr = get_actdate($timetodelreg, $cfgrow['datetimestr']);
        $timetodelexpd = $expdarr['prev'];
        $condition = " AND mpstatus = '{$mpstatus}' AND reg_utctime <= '{$timetodelexpd}'";
        $condition .= " AND mptoken NOT LIKE '%|ismanpayconfirm:%'";
        $condition .= " ORDER BY RAND() LIMIT {$limitcheck}";
        $userData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_mbrs LEFT JOIN " . DB_TBLPREFIX . "_mbrplans ON id = idmbr WHERE 1 " . $condition . "");

        if (count($userData) > 0) {
            foreach ($userData as $val) {
                do_mbrdel($val['id'], $cfgtoken['mbrdelopt'], $val['mpid']);
            }
        }
    }
}

function do_walletplanpay($mbrstr) {
    global $db, $cfgrow, $payrow, $bpparr;

    $txid = get_unpaidtxid($mbrstr);
    $mpid = $mbrstr['mpid'];
    $newmppid = $mbrstr['mppid'];

    $condition = " AND txid = '{$txid}'";
    $txrow = $db->getAllRecords(DB_TBLPREFIX . '_transactions', '*', $condition);
    $txrowamount = $txrow[0]['txamount'];

    // add wallet service fee
    $pgdatatokenarr = get_optionvals($payrow['pgdatatoken']);
    $ewalletcfg = get_optarr($pgdatatokenarr['ewalletcfg']);
    $walletfee = getamount($ewalletcfg['ewalletfee'], $txrowamount);
    $txrowamountfee = $txrowamount + $walletfee;

    if ($mbrstr['ewallet'] >= $txrowamountfee) {
        $txbatch = "R" . date("md") . "-" . date("Hi") . "{$mpid}";
        $newamount = $mbrstr['ewallet'] - $txrowamountfee;

        include_once('sandbox.php');
        $FORM['sb_type'] = 'payreg';
        $FORM['sb_label'] = 'ewalletlabel';
        $FORM['sb_txtokenarr'] = ['WALT' => 'OUT'];
        $txmpid = $txid . '-' . $mpid;
        doipnbox($txmpid, $txrowamountfee, 'ewalletlabel', $txbatch, '-HTTPREF-', 'continue', 0, $ewalletcfg['ewalletlabel']);

        $mpstatus = ($newamount >= 0) ? 1 : 3;
        $data = array(
            'mpstatus' => $mpstatus,
        );
        $update = $db->update(DB_TBLPREFIX . '_mbrplans', $data, array('mpid' => $mpid));

        $datalistarr['status'] = $mpstatus;
        do_mbrwebhook($mbrstr, $datalistarr);
    }
}

function do_prerenewtx($txmpid, $mpstatus) {
    global $cfgrow;

    if ($mpstatus == 2) {
        $sb_txmpidarr = explode('-', $txmpid ?? '');
        $mpid = $sb_txmpidarr[1];
        $reg_utctime = $cfgrow['datetimestr'];
        $mbrstr = getmbrinfo('', '', $mpid);
        $txid = do_renewtx($reg_utctime, $mbrstr);
        $newtxmpid = $txid . '-' . $mpid;
    } else {
        $newtxmpid = $txmpid;
    }
    return $newtxmpid;
}

function do_renewtx($utctime, $mbrevalarr) {
    global $db, $bpparr, $LANG;

    $renewfee = $bpparr[$mbrevalarr['mppid']]['renewfee'];
    $renew_fee = ($renewfee > 0) ? $renewfee : $mbrevalarr['reg_fee'];
    $sql = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_transactions WHERE txfromid = '{$mbrevalarr['id']}' AND txppid = '{$mbrevalarr['mppid']}' AND txtoken LIKE '%|PREVEXP:{$mbrevalarr['reg_expd']}|%'");
    if ($renew_fee > 0 && count($sql) < 1) {
        $data = array(
            'txdatetm' => $utctime,
            'txfromid' => $mbrevalarr['id'],
            'txamount' => (float) $renew_fee,
            'txmemo' => $LANG['g_renewalfee'],
            'txppid' => $mbrevalarr['mppid'],
            'txtoken' => "|RENEW:{$mbrevalarr['mpid']}|, |PREVEXP:{$mbrevalarr['reg_expd']}|",
        );
        $db->insert(DB_TBLPREFIX . '_transactions', $data);
        $txid = $db->lastInsertId();
    } else {
        $txid = $sql[0]['txid'] ?? null;
    }
    return $txid;
}

function maskmail($email) {
    if (!defined('ISDEMOMODE')) {
        return $email;
    } else {
        $em = explode("@", $email ?? '');
        $name = implode('@', array_slice($em, 0, count($em) - 1));
        $len = floor(strlen($name ?? '') / 2);
        return substr($name, 0, $len) . str_repeat('*', $len) . "*@" . end($em);
    }
}

function get_countrycode2($log_ip) {
    global $country_array;

    require_once('geoip.class.php');
    $geoplugin = new geoPlugin();
    $geoplugin->locate($log_ip);

    $countryc = $geoplugin->countryCode;
    $countryc = strtoupper($countryc ?? '');
    if (array_key_exists($countryc, $country_array)) {
        return $countryc;
    } else {
        return '';
    }
}

function get_countrycode($log_ip) {
    global $country_array;

    $ch = curl_init('ht' . 'tp:/' . "/ipwh" . 'o.is/' . $log_ip);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
    $json = curl_exec($ch);
    curl_close($ch);
    $iresponse = json_decode($json ?? '', true);
    $countryc = strtoupper($iresponse['country_code'] ?? '');
    if (array_key_exists($countryc, $country_array)) {
        return $countryc;
    } else {
        return '';
    }
}

function ppdblist($ppidarr = array(), $listall = 0, $isoptall = 0) {
    global $db, $LANG;

    $result = ($isoptall == 1) ? "<option value='0'>{$LANG['g_all']}" : '';
    $condition = ($listall != 1) ? " AND planstatus = '1'" : '';
    $userData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_payplans WHERE 1 AND ppname != '' " . $condition . "");
    if (count($userData) > 0) {
        foreach ($userData as $val) {
            $isselect = (in_array($val['ppid'], $ppidarr)) ? " selected" : "";
            $isselect = ($val['planstatus'] != '1') ? " disabled" : $isselect;
            $result .= "<option value='{$val['ppid']}'{$isselect}>{$val['ppname']}";
        }
    }
    return $result;
}

function get_planarr($mppid = 1) {
    global $bpprowbase, $bpparr, $planlogo;

    $mppid = ($mppid < 1) ? 1 : $mppid;
    $bpprowplan = $bpparr[$mppid];
    $result = (intval($bpprowplan['ppid']) < 1) ? $bpprowbase : array_merge($bpprowbase, $bpprowplan);
    $planimg = ($result['planimg']) ? $result['planimg'] : DEFIMG_PLAN;
    $planlogo = ($result['planlogo']) ? $result['planlogo'] : DEFIMG_LOGO;
    $result['planimg'] = $planimg;
    $result['planlogo'] = $planlogo;
    return $result;
}

function definebanfldr() {
    global $cfgtoken;
    $lc_dbuser = (defined('DB_USER')) ? DB_USER : '';
    $lc_dbpass = (defined('DB_PASSWORD')) ? DB_PASSWORD : '';
    $lc_dbhash = (defined('DB_HASHPASSWORD')) ? DB_HASHPASSWORD : '';
    if (md5($cfgtoken['lictype'] . '#' . $cfgtoken['licstr'] . '-' . base64_encode(str_replace('ww' . 'w.', '', strtolower($_SERVER['H' . 'TTP_H' . 'OS' . 'T'] ?? '')))) != $cfgtoken['licvh'] && md5($lc_dbuser . '*' . $lc_dbpass) != $lc_dbhash) {
        die();
    }
}

function do_autoregplan($mbrstr, $cyclingbyid, $entrytoidmbr, $newmppid = 1, $regplanby = '') {
    global $db, $bpparr, $payrow, $FORM;

    $data = array(
        'isdefault' => '0',
        'cyclingbyid' => $cyclingbyid,
    );
    $update = $db->update(DB_TBLPREFIX . '_mbrplans', $data, array('mpid' => $mbrstr['mpid']));

    $resultarr = regmbrplans($mbrstr, $entrytoidmbr, $newmppid);
    $txid = $resultarr['txid'];
    $mpid = $resultarr['mpid'];
    $doreactive = ($regplanby == 'wallet') ? '1' : get_optionvals($bpparr[$mbrstr['mppid']]['plantoken'], 'doreactive');

    if ($doreactive == '1') {

        $initbatch = ($regplanby == 'wallet') ? 'A' : 'E';
        $txbatch = $initbatch . date("md") . "-" . date("Hi") . "{$mpid}";
        $payamount = $bpparr[$newmppid]['regfee'];

        $pgdatatokenarr = get_optionvals($payrow['pgdatatoken']);
        $ewalletcfg = get_optarr($pgdatatokenarr['ewalletcfg']);
        $walletfee = getamount($ewalletcfg['ewalletfee'], $payamount);
        $txrowamountfee = $payamount + $walletfee;
        $newamount = $mbrstr['ewallet'] - $txrowamountfee;

        include_once('sandbox.php');
        $FORM['sb_type'] = 'payreg';
        $FORM['sb_label'] = 'ewalletlabel';
        $FORM['sb_txtokenarr'] = ['WALT' => 'OUT'];
        $txmpid = $txid . '-' . $mpid;
        doipnbox($txmpid, $txrowamountfee, 'ewalletlabel', $txbatch, '-HTTPREF-', 'continue', 0, $ewalletcfg['ewalletlabel']);

        $mpstatus = ($newamount >= 0) ? 1 : 3;
        $data = array(
            'mpstatus' => $mpstatus,
        );
        $update = $db->update(DB_TBLPREFIX . '_mbrplans', $data, array('mpid' => $resultarr['mpid']));

        $datalistarr['status'] = $mpstatus;
        $mbrstr = getmbrinfo('', '', $resultarr['mpid']);
        do_mbrwebhook($mbrstr, $datalistarr);
    }
}

function cmdsetvars($cfgtoken) {
    global $cfgtoken, $frlmtdcfg, $ssysout;

    $frlmtdcfg = array();
    $frlmtdcfg['mxstages'] = (2 + 2);
    $frlmtdcfg['mxranks'] = (2 * 5);
    $frlmtdcfg['isxplans'] = ($cfgtoken['isonceplan'] == 1) ? 0 : 1;
    $frlmtdcfg['ismw'] = (4 + 6);
    $frlmtdcfg['ismd'] = (11 + 9);
    $frlmtdcfg['isregallrefs'] = 1;
    $ssysmaker = hash('sha256', SSYS_NAME . "@{$ssysout('SSYS_DOMAIN')} [{$ssysout('SSYS_AUTHOR')}] => " . SSYS_URL);
    if ($ssysout('SSYS_MARKER') != $ssysmaker) {
        die("! Invalid s y s t e m h a s h");
    }
}

function mbrpparr($idmbr) {
    global $db;

    $result = [];
    $condition = " AND idmbr = '{$idmbr}'";
    $userData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_mbrplans WHERE 1" . $condition . "");
    if (count($userData) > 0) {
        foreach ($userData as $val) {
            $result['mppid'][] = $val['mppid'];
            foreach ($val as $key => $value) {
                $result[$val['mppid']][$key] = $value;
            }
        }
    }
    return $result;
}

function do_movembr($mbrstr, $newunspr) {
    global $db;

    $newsprstr = ($newunspr == '') ? getmbrinfo(0) : getmbrinfo($newunspr, 'username');
    $newsprlist = dosprlist($newsprstr['mpid'], $mbrstr['sprlist'], $mbrstr['mpdepth']);
    $mptoken = put_optionvals($mbrstr['mptoken'], 'previdspr', $mbrstr['idspr']);
    $data = array(
        'idspr' => intval($newsprstr['id']),
        'sprlist' => $newsprlist,
        'mptoken' => $mptoken,
    );
    $update = $db->update(DB_TBLPREFIX . '_mbrplans', $data, array('mpid' => $mbrstr['mpid']));

    $xdlist = ":" . $mbrstr['mpid'] . "|";
    $condition = " AND sprlist LIKE '%{$xdlist}%'";
    $userData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_mbrplans WHERE 1 " . $condition . "");
    foreach ($userData as $val) {
        $mysprstr = getmbrinfo($val['idspr']);
        $sprlist = dosprlist($mysprstr['mpid'], $val['sprlist'], $val['mpdepth']);
        $mptoken = put_optionvals($val['mptoken'], 'previdspr', $val['idspr']);
        $data = array(
            'idspr' => intval($mysprstr['id']),
            'sprlist' => $sprlist,
            'mptoken' => $mptoken,
        );
        $db->update(DB_TBLPREFIX . '_mbrplans', $data, array('mpid' => $val['mpid']));
    }
    return $update;
}

function do_rebuildmbr($dombrstr) {
    global $db, $frlmtdcfg;

    $orisprstr = getmbrinfo($dombrstr['idspr']);
    $xdlist = "|1:" . $dombrstr['mpid'] . "|";
    $condition = " AND sprlist LIKE '%{$xdlist}%'";
    $userData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_mbrplans WHERE 1 " . $condition . "");
    foreach ($userData as $val) {
        $newsprmpid = ($frlmtdcfg['isrebuildmbr'] != 1) ? 0 : getmpidflow($orisprstr['mpid'], $dombrstr['mppid']);
        $sprstr = getmbrinfo('', '', $newsprmpid);
        $nowmbrstr = getmbrinfo('', '', $val['mpid']);
        do_movembr($nowmbrstr, $sprstr['username']);
    }
}

function do_dbbakup() {
    global $db, $cfgrow, $cfgtoken, $umbasever, $ssysout;

    $dbaknow = $cfgrow['datetimestr'];
    $dbakint = $cfgtoken['dbakint'];
    $dbakeml = base64_decode($cfgtoken['dbakeml'] ?? '');
    $dbakdate = base64_decode($cfgtoken['dbakdate'] ?? '');

    $nextbak = get_actdate($dbakint, $dbakdate);
    $datenextbak = $nextbak['next'];
    if (($dbakdate == null || $datenextbak <= $dbaknow) && $dbakint != '0' && $dbakeml != '') {
        $dat = date('Ymd_His');
        if (function_exists('gzencode')) {
            $cmp = "gz";
            $backup_filename = "" . DB_NAME . "_$dat.sql.$cmp";
        } else {
            $cmp = "";
            $backup_filename = "" . DB_NAME . "_$dat.sql";
        }

        include_once(INSTALL_PATH . '/common/umver.php');
        require_once(INSTALL_PATH . '/common/mailer.do.php');
        $bakdbcnt = gobackup($cmp);

        $msgsubject = "Database backup " . $backup_filename;

        $fmessagehtml = "<font size=3><b>{$ssysout('SSYS_NAME')} v{$umbasever} - Database Backup</b></font><br /><br />";
        $fmessagehtml .= "{$cfgtoken['site_subname']}<br />";
        $fmessagehtml .= "Creation date: <b>" . date("Y-m-d H:i:s", time()) . "</b><br />";
        $fmessagehtml .= "Database: " . DB_NAME . "<br />";

        $fmessage = "{$ssysout('SSYS_NAME')} v{$umbasever} - Database Backup\n";
        $fmessage .= "{$cfgtoken['site_subname']}\n";
        $fmessage .= "Creation date: " . date("Y-m-d H:i:s", time()) . "\n";
        $fmessage .= "Database: " . DB_NAME . "\n";

        $isdomailer = domailer($cfgtoken['site_subname'], $dbakeml, $msgsubject, $fmessagehtml, $fmessage, $bakdbcnt, $backup_filename);
        if ($isdomailer) {
            $newcfgtoken = $cfgrow['cfgtoken'];
            $newcfgtoken = put_optionvals($newcfgtoken, 'dbakdate', base64_encode($dbaknow));
            $data = array(
                'cfgtoken' => $newcfgtoken,
            );
            $update = $db->update(DB_TBLPREFIX . '_configs', $data, array('cfgid' => '1'));
        }
    }
}

function do_mbrdel($delId, $istx = '', $delmpid = 0) {
    global $db, $cfgtoken, $frlmtdcfg;

    if ($delmpid < 1) {
        // remove all member account
        $delmbrstr = getmbrinfo($delId);
        $db->delete(DB_TBLPREFIX . '_mbrs', array('id' => $delId));
        $db->delete(DB_TBLPREFIX . '_mbrplans', array('idmbr' => $delId));
        $db->delete(DB_TBLPREFIX . '_sessions', array('sesidmbr' => $delId));
    } else {
        // remove member payplan only
        $delmbrstr = getmbrinfo('', '', $delmpid);
        $db->delete(DB_TBLPREFIX . '_mbrplans', array('mpid' => $delmbrstr['mpid']));
    }

    // remove transaction history
    if ($istx == '1') {
        $condition = " AND (txtoken LIKE '%|SRCIDMBR:{$delId}|%' OR txfromid = '{$delId}' OR txtoid = '{$delId}')";
        if ($delmpid > 0) {
            $condition .= " AND txppid = '{$delmbrstr['mppid']}'";
        }
        $deltxrow = $db->getAllRecords(DB_TBLPREFIX . '_transactions', '*', $condition);
        foreach ($deltxrow as $key => $txval) {
            $deltxid = $txval['txid'];
            $db->delete(DB_TBLPREFIX . '_transactions', array('txid' => $deltxid));

            // adjust member ewallet
            if ($frlmtdcfg['isdelmbrtxadjust'] == 1 && $txval['txtoid'] > 0 && $txval['txtoid'] != $delId && $txval['txstatus'] == '1') {
                $mbrtostr = getmbrinfo($txval['txtoid']);
                $newamount = $mbrtostr['ewallet'] - $txval['txamount'];
                adjusttrxwallet($mbrtostr['ewallet'], $newamount, $txval['txtoid'], "Reversal {$txval['txmemo']}", "Adjustment from the member removal: {$delmbrstr['fullname']} ({$delId} {$delmbrstr['username']}) {$delmbrstr['email']}", 1);
                $data = array(
                    'ewallet' => $newamount,
                );
                $db->update(DB_TBLPREFIX . '_mbrs', $data, array('id' => $mbrtostr['id']));
            }
        }
    }

    // remove link
    del_peppyinfo($delmbrstr['peppylinkpllid'], 'pllid');

    do_ranker($delmbrstr);
    do_rebuildmbr($delmbrstr);
}

function get_withdrawfee() {
    global $cfgrow;

    $wdrwfeearr = [];
    $wdvarval = $cfgrow['wdrawfee'];
    $wdvarvalarr = explode('|', $wdvarval ?? '');
    $fval = (strpos($wdvarvalarr[0] ?? '', '%') !== false) ? $wdvarvalarr[0] / 100 : $wdvarvalarr[0];
    $wdrwfeearr['fee'] = (float) $fval;
    $wdrwfeearr['cap'] = (float) $wdvarvalarr[1];
    return $wdrwfeearr;
}

function get_pgmbrtoken($mbrstr) {
    global $db, $mbrpaystr;

    $pgdatatoken = $mbrstr['pgdatatoken'];
    $pgmbrtokenarr = get_optionvals($pgdatatoken);
    $mbrbankcfg = get_optarr($pgmbrtokenarr['bankcfg']);
    $mbrpaystr = [];
    $mbrpayrow = $db->getAllRecords(DB_TBLPREFIX . '_paygates', '*', ' AND pgidmbr = "' . $mbrstr['id'] . '"');
    $mbrpaystr['manualpayipn'] = base64_decode($mbrpayrow[0]['manualpayipn'] ?? '');
    $mbrpaystr['bankinfo'] = $mbrbankcfg['bankname'] . ' *' . substr($mbrbankcfg['bankaccno'] ?? '', -4);
    $mbrpaystr['bankacc'] = $mbrbankcfg['bankname'] . ' ACC: ' . $mbrbankcfg['bankaccno'] . ' Name: ' . $mbrbankcfg['bankaccname'];

    return $mbrpaystr;
}

function do_withdrawreq($mbrstr, $txamount, $txpaytype) {
    global $db, $cfgrow, $payrow, $LANG, $avalpaymentopt_array, $avalwithdrawgate_array;

    if ($txamount <= 0) {
        return false;
    }
    $wdrwfeearr = get_withdrawfee();
    $fval = $wdrwfeearr['fee'];
    $fcapval = $wdrwfeearr['cap'];

    $mbrpaystr = get_pgmbrtoken($mbrstr);
    $txamountval = $txamount;
    $txwdrfee = $txamountfee = 0;
    if ($fval > 0) {
        $txwdrfee = $txamount * $fval;
        $txamountfeeopt = ($fcapval > 0 && $fcapval <= $txwdrfee) ? $fcapval : $txwdrfee;
        $txamountfee = (float) sprintf('%0.2f', $txamountfeeopt);
        $txamountval = $txamount - $txamountfee;
    }

    // deduct wallet
    $ewallet = $mbrstr['ewallet'] - $txamount;
    $data = array(
        'ewallet' => $ewallet,
    );
    $update = $db->update(DB_TBLPREFIX . '_mbrs', $data, array('id' => $mbrstr['id']));

    // add withdraw request
    $paybyopt = ($txpaytype == 'manualpayipn') ? $payrow[$avalpaymentopt_array[$txpaytype]] : $avalwithdrawgate_array[$txpaytype];
    $paybyopt .= ($txpaytype == 'coinpaymentsmercid') ? ' ' . $mbrpaystr['coinpaymentscryptoid'] : '';
    $txadminfo = "Payout To [{$paybyopt}]: ";
    $txadminfo .= $mbrpaystr[$txpaytype];
    $txdatetm = $cfgrow['datetimestr'];

    $addtokenstr = ($txpaytype == 'stripeacc') ? ", |stripeacc:{$mbrpaystr[$txpaytype]}|" : '';
    $data = array(
        'txdatetm' => $txdatetm,
        'txpaytype' => $txpaytype,
        'txfromid' => 0,
        'txtoid' => $mbrstr['id'],
        'txamount' => $txamountval,
        'txmemo' => $LANG['g_withdrawstr'],
        'txppid' => $mbrstr['mppid'],
        'txtoken' => "|WIDR:OUT|, |WDRTXFEE:{$txamountfee}|" . $addtokenstr,
        'txstatus' => 0,
        'txadminfo' => $txadminfo,
    );
    $insert = $db->insert(DB_TBLPREFIX . '_transactions', $data);

    if ($insert) {
        $newtrxid = $db->lastInsertId();
        if ($txamountfee > 0) {
            $txdatetm = $cfgrow['datetimestr'];
            $txlogtime = date('mdH-is-' . $newtrxid, time() + (3600 * $cfgrow['time_offset']));
            $txbatch = "WDFE" . date("m-dH-i") . $newtrxid;
            $data = array(
                'txdatetm' => $txdatetm,
                'txpaytype' => $txpaytype,
                'txfromid' => $mbrstr['id'],
                'txtoid' => 0,
                'txamount' => $txamountfee,
                'txbatch' => $txbatch,
                'txmemo' => $LANG['g_withdrawfee'],
                'txppid' => $mbrstr['mppid'],
                'txtoken' => "|WDRTXID:{$newtrxid}|, |NOTE:" . base64_encode("WDRID-{$txlogtime}") . "|",
                'txstatus' => 1,
            );
            $insertrx = $db->insert(DB_TBLPREFIX . '_transactions', $data);
        }
    }

    return $insert;
}

function is_plansubscr($mppid = 1) {
    global $bpparr;

    $iswhat = ($bpparr[$mppid]['expday'] != '') ? true : false;
    return $iswhat;
}

function is_unamereserved($username) {
    global $cfgrow;

    $unamereservedarr = explode(',', str_replace(' ', '', $cfgrow['badunlist'] ?? ''));
    $isexist = (in_array($username, $unamereservedarr)) ? true : (($username == $cfgrow['admin_user']) ? true : false);

    return $isexist;
}

function do_reginorder($mbrstr) {
    global $bpparr, $frlmtdcfg;

    $directnextid = '';
    $nextppidarr = [];

    foreach ($bpparr as $key => $value) {
        $ppid = $value['ppid'];
        if ($mbrstr['mppid'] == $ppid) {
            continue;
        }
        if ($frlmtdcfg['isreginorder'] == 0) {
            // all available
            $nextppidarr[] = $ppid;
        } else {
            if ($mbrstr['mppid'] < $ppid) {
                $nextppidarr[] = $ppid;
                if ($directnextid == '') {
                    $directnextid = 'upnextid';
                    $nextppidarr['upnextid'] = $ppid;
                }
                if ($frlmtdcfg['isreginorder'] == 2) {
                    break;
                }
            } else {
                continue;
            }
        }
    }

    return $nextppidarr;
}

function get_txinfo($tbvalue, $tbfield = 'txid', $addconditionqry = '') {
    global $db;

    $txRow = [];
    if ($tbvalue != '') {
        $addconditionqry = ($addconditionqry != '') ? "AND {$addconditionqry}" : '';
        $row = $db->getAllRecords(DB_TBLPREFIX . '_transactions', '*', " AND {$tbfield} = '{$tbvalue}' " . $addconditionqry);
        foreach ($row as $value) {
            $txRow = array_merge($txRow, $value);
        }
    }

    return $txRow;
}

// check referrer and get new sponsor
function do_resprmpid($mbrstr) {
    global $db, $cfgtoken, $frlmtdcfg;

    $refstr = getmbrinfo($mbrstr['idref'], '', '', $mbrstr['mppid']);
    $mpidref = ($mbrstr['idhostmbr'] > 0) ? $mbrstr['idhostmbr'] : $refstr['mpid'];

    // get ref based on single ref or not
    if ($cfgtoken['isonetopref'] == '1') {
        $refarr = getmbrinfo('', '', $mpidref);
        $mpidref = get_toprefnow($refarr['id'])['mpid'];
    }

    $sprmpid = getmpidflow($mpidref, $mbrstr['mppid'], $mbrstr);
    $sprstr = getmbrinfo('', '', $sprmpid);
    $idspr = intval($sprstr['id']);

    $sprlist = dosprlist($sprstr['mpid'], $sprstr['sprlist'], $mbrstr['mpdepth']);
    $data = array(
        'idspr' => $idspr,
        'sprlist' => $sprlist,
    );
    $update = $db->update(DB_TBLPREFIX . '_mbrplans', $data, array('mpid' => $mbrstr['mpid']));
    $mbrstr = getmbrinfo('', '', $mbrstr['mpid']);

    return $mbrstr;
}

function get_calcumount($mbrstr, $condition) {
    global $db;

    $result = [];
    $toth = $refbon = $sprbon = $rwdbon = $slsbon = $totwalet = $waletout = $totern = $mypaymn = $renewfee = $totpending = $reqwdrwait = $reqwdrdone = $feewdr = 0;
    $userData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_transactions WHERE 1 " . $condition . "");
    foreach ($userData as $val) {
        $toth++;
        $txtoken = get_optionvals($val['txtoken']);

        if ($val['txstatus'] == 1) {

            // general incoming and payout/debet
            if ($val['txfromid'] == $mbrstr['id']) {
                if ($txtoken['WIDR'] == 'OUT') {
                    $totwalet = $totwalet - $val['txamount'];
                }
                if ($txtoken['WALT'] == 'OUT') {
                    $waletout = $waletout + $val['txamount'];
                }
                if (strpos($val['txtoken'] ?? '', '|REG:') !== false) {
                    $mypaymn = $mypaymn + $val['txamount'];
                }
            } elseif ($val['txtoid'] == $mbrstr['id']) {
                if ($txtoken['WALT'] == 'IN') {
                    $totwalet = $totwalet + $val['txamount'];
                }
                if ($txtoken['WIDR'] != 'OUT') {
                    $totern = $totern + $val['txamount'];
                }
            }

            // referrer bonuses
            if ($txtoken['LCM'] == 'PREF' && $txtoken['WALT'] == 'IN') {
                $refbon = $refbon + $val['txamount'];
            }

            // sponsor bonuses
            if ($txtoken['LCM'] == 'TIER' && $txtoken['WALT'] == 'IN') {
                $sprbon = $sprbon + $val['txamount'];
            }

            // reward bonuses
            if (strpos($val['txtoken'] ?? '', '|LCM:FRWD') !== false && $txtoken['WALT'] == 'IN') {
                $rwdbon = $rwdbon + $val['txamount'];
            }

            // sales bonuses
            if ($txtoken['LCM'] == 'SLSTIER' && $txtoken['WALT'] == 'IN') {
                $slsbon = $slsbon + $val['txamount'];
            }

            // renewal fee
            if (strpos($val['txtoken'] ?? '', '|RENEW:') !== false) {
                $renewfee = $renewfee + $val['txamount'];
            }

            // withdraw amount
            if ($txtoken['WIDR'] == 'OUT') {
                $reqwdrdone = $reqwdrdone + $val['txamount'];
            }
            // withdraw fee
            if (strpos($val['txtoken'] ?? '', '|WDRTXID:') !== false) {
                $feewdr = $feewdr + $val['txamount'];
            }
        } else {
            if ($txtoken['WIDR'] == 'OUT') {
                $reqwdrwait = $reqwdrwait + $val['txamount'];
            }
            $totpending = $totpending + $val['txamount'];
        }
    }

    $result['hist_tot'] = $toth;
    $result['hist_refbonus'] = sprintf("%0.2f", $refbon);
    $result['hist_sprbonus'] = sprintf("%0.2f", $sprbon);
    $result['hist_rwdbonus'] = sprintf("%0.2f", $rwdbon);
    $result['hist_slsbon'] = sprintf("%0.2f", $slsbon);
    $result['hist_waletout'] = sprintf("%0.2f", $waletout);
    $result['hist_ewallet'] = sprintf("%0.2f", $totwalet - $waletout - $reqwdrdone - $reqwdrwait - $feewdr);
    $result['hist_mypaymn'] = sprintf("%0.2f", $mypaymn);
    $result['hist_earning'] = sprintf("%0.2f", $totern);
    $result['hist_renewfee'] = sprintf("%0.2f", $renewfee);
    $result['hist_reqwdrwait'] = sprintf("%0.2f", $reqwdrwait);
    $result['hist_reqwdrdone'] = sprintf("%0.2f", $reqwdrdone);
    $result['hist_feewdr'] = sprintf("%0.2f", $feewdr);
    $result['hist_pending'] = sprintf("%0.2f", $totpending);

    return $result;
}

function get_admcalcumount($ppid = 0) {
    global $db;

    $result = [];
    $toth = $refbon = $sprbon = $rwdbon = $slsbon = $tincome = $totern = $reqwdrwait = $reqwdrdone = $feewdr = $totpending = 0;

    $condition = " AND (txfromid = '0' OR txtoid = '0') ";
    $condition .= ($ppid > 0) ? " AND txppid = '{$ppid}' " : '';
    $userData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_transactions WHERE 1 " . $condition . "");
    foreach ($userData as $val) {
        $toth++;
        $txtoken = get_optionvals($val['txtoken']);

        if ($val['txstatus'] == 1) {

            // general incoming and payout/debet
            if ($val['txfromid'] == 0 && $val['txtoid'] > 0) {
                if ($txtoken['WIDR'] == 'OUT') {
                    $reqwdrdone = $reqwdrdone + $val['txamount'];
                }
            } elseif ($val['txfromid'] > 0 && $val['txtoid'] == 0) {
                if (strpos($val['txtoken'] ?? '', '|REG:') !== false || strpos($val['txtoken'] ?? '', '|RENEW:') !== false || strpos($val['txtoken'] ?? '', '|STORE:') !== false) {
                    $tincome = $tincome + $val['txamount'];
                }
                // withdraw fee
                if (strpos($val['txtoken'] ?? '', '|WDRTXID:') !== false) {
                    $feewdr = $feewdr + $val['txamount'];
                }
            }

            // referrer bonuses
            if ($txtoken['LCM'] == 'PREF' && $txtoken['WALT'] == 'IN') {
                $refbon = $refbon + $val['txamount'];
            }

            // sponsor bonuses
            if ($txtoken['LCM'] == 'TIER' && $txtoken['WALT'] == 'IN') {
                $sprbon = $sprbon + $val['txamount'];
            }

            // reward bonuses
            if (strpos($val['txtoken'] ?? '', '|LCM:FRWD') !== false && $txtoken['WALT'] == 'IN') {
                $rwdbon = $rwdbon + $val['txamount'];
            }

            // sales bonuses
            if ($txtoken['LCM'] == 'SLSTIER' && $txtoken['WALT'] == 'IN') {
                $slsbon = $slsbon + $val['txamount'];
            }
        } else {
            if ($txtoken['WIDR'] == 'OUT') {
                $reqwdrwait = $reqwdrwait + $val['txamount'];
            }
            $totpending = $totpending + $val['txamount'];
        }
    }

    $result['hist_tot'] = $toth;
    $result['hist_refbonus'] = sprintf("%0.2f", $refbon);
    $result['hist_sprbonus'] = sprintf("%0.2f", $sprbon);
    $result['hist_rwdbonus'] = sprintf("%0.2f", $rwdbon);
    $result['hist_slsbon'] = sprintf("%0.2f", $slsbon);
    $result['hist_tincome'] = sprintf("%0.2f", $tincome);
    $result['hist_earning'] = sprintf("%0.2f", $tincome - $reqwdrdone - $feewdr);
    $result['hist_toutcome'] = sprintf("%0.2f", $result['hist_tincome'] - $result['hist_earning']);
    $result['hist_reqwdrwait'] = sprintf("%0.2f", $reqwdrwait);
    $result['hist_reqwdrdone'] = sprintf("%0.2f", $reqwdrdone);
    $result['hist_feewdr'] = sprintf("%0.2f", $feewdr);
    $result['hist_pending'] = sprintf("%0.2f", $totpending);

    return $result;
}

function get_sysplanstr($mbrstr = array()) {
    global $bpprow;

    if ($mbrstr['id'] > 0) {
        if ($mbrstr['mpwidth'] < 1) {
            $planstr = ($mbrstr['mpdepth'] == 1) ? 'Unilevel' : 'Unilevel &darr;' . $mbrstr['mpdepth'];
        } elseif ($mbrstr['mpwidth'] == 1) {
            $planstr = 'Powerline &darr;' . $mbrstr['mpdepth'];
        } else {
            $planstr = 'Matrix ' . $mbrstr['mpwidth'] . '&times;' . $mbrstr['mpdepth'];
        }
    } else {
        if ($bpprow['maxwidth'] < 1) {
            $planstr = ($bpprow['maxdepth'] == 1) ? 'Unilevel' : 'Unilevel &darr;' . $bpprow['maxdepth'];
        } elseif ($bpprow['maxwidth'] == 1) {
            $planstr = 'Powerline &darr;' . $bpprow['maxdepth'];
        } else {
            $planstr = 'Matrix ' . $bpprow['maxwidth'] . '&times;' . $bpprow['maxdepth'];
        }
    }

    return $planstr;
}

function get_sprppcm($defppid, $cmlist, $sprmppid = 0, $sprtier = 0) {
    global $cfgrow, $bpprow, $frlmtdcfg;

    $ppVal = preg_replace("/(\x{00a0}|\s+|\r|\n)/", "", $cmlist ?? '');
    $ppvalarr = explode('#', $ppVal ?? '');

    $stagecmarr = $stagearr = [];
    if (count($ppvalarr) > 1) {
        // if multi commission settings found
        $mxstages = ($frlmtdcfg['mxstages'] > 1) ? intval($frlmtdcfg['mxstages']) : 1;
        for ($i = 1; $i <= $mxstages; $i++) {
            $value = $ppvalarr[$i];
            $tiervalarr = explode('=', $value ?? '');
            $ppidspr = $tiervalarr[0];

            if (in_array($ppidspr, $stagearr) || $value == '' || $ppidspr < 1 || ($ppidspr != $defppid)) {
                continue;
            }
            $stagearr[] = $ppidspr;
            $ppidcm = $tiervalarr[1];
            $tiercmarr = explode(',', $ppidcm ?? '');

            $tier = 0;
            $maxdepth = ($bpprow['maxdepth'] > 1) ? intval($bpprow['maxdepth']) : 1;
            for ($j = 0; $j < $maxdepth; $j++) {
                $tier = $j + 1;
                $tiercm = ($tiercmarr[$j] != '') ? $tiercmarr[$j] : 0;
                $tiercmstr = (preg_match("/[\d.]+%?/", $tiercm, $matches)) ? $matches[0] : 0;
                $stagecmarr[$ppidspr][$tier] = $tiercmstr;
            }
        }
    } else {
        $ppidcm = $ppvalarr[0];
        $ppidspr = ($sprmppid > 1) ? $sprmppid : $defppid;
        $tiercmarr = explode(',', $ppidcm ?? '');

        $tier = 0;
        $maxdepth = ($bpprow['maxdepth'] > 1) ? intval($bpprow['maxdepth']) : 1;
        for ($j = 0; $j < $maxdepth; $j++) {
            $tier = $j + 1;
            $tiercm = ($tiercmarr[$j] != '') ? $tiercmarr[$j] : 0;
            $tiercmstr = (preg_match("/[\d.]+%?/", $tiercm, $matches)) ? $matches[0] : 0;
            $stagecmarr[$ppidspr][$tier] = $tiercmstr;
        }
    }

    if (intval($sprmppid) > 0) {
        $result = $stagecmarr[$sprmppid][$sprtier];
    } else {
        ksort($stagecmarr);
        $result = $stagecmarr;
    }

    return $result;
}

function get_epininfo($epid, $epval = 'epid', $mbrstr = []) {
    global $db;

    $epinRow = [];
    if ($epid != '') {
        $epid = mystriptag($epid);
        $condition = "AND {$epval} = '{$epid}' AND epstatus = '1' AND epcode != '' ";
        if ($mbrstr['id'] > 0) {
            $condition .= "AND (eprefid = '{$mbrstr['id']}' OR eprefid = '0') AND (epusedid = '{$mbrstr['id']}' OR epusedid = '0') ";
            $row = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_epins LEFT JOIN " . DB_TBLPREFIX . "_mbrs ON eprefid = id WHERE 1 " . $condition . "");
        } else {
            $row = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_epins WHERE 1 " . $condition . "");
        }

        foreach ($row as $value) {
            $epinRow = array_merge($epinRow, $value);
        }
    }

    return $epinRow;
}

function get_iteminfo($itid, $mbrstr = array()) {
    global $db;

    $itemRow = [];
    if ($itid > 0) {
        $itid = intval($itid);
        $itbymbr = ($mbrstr['id'] > 0) ? " AND itidmbr = '{$mbrstr['id']}'" : '';
        $row = $db->getAllRecords(DB_TBLPREFIX . '_items LEFT JOIN ' . DB_TBLPREFIX . '_groups ON itgrid = grid ', '*', " AND itid = '{$itid}'" . $itbymbr);
        foreach ($row as $value) {
            $itemRow = array_merge($itemRow, $value);
        }
        $itemRow['itimages'] = ($itemRow['itimages']) ? $itemRow['itimages'] : DEFIMG_FILE;
    }

    return $itemRow;
}

function dodb_item($itemstr = array(), $mbrstr = array(), $txstr = array(), $slrefid = 0, $sltoken = '') {
    global $db, $cfgrow, $bpprow, $LANG;

    $slprice = floatval($txstr['txamount']);
    $slbatch = $txstr['txbatch'];

    if ($mbrstr['id'] > 0 && $itemstr['itid'] > 0) {
        $sldatetm = $cfgrow['datetimestr'];
        $itexpinval = $itemstr['itexpinval'];
        $renewsalesid = get_optionvals($txstr['txtoken'], 'RENEWSLID');
        if ($renewsalesid > 0) {
            $salesstr = get_salesinfo($renewsalesid);
            $nextexp = get_actdate($itexpinval, $salesstr['slexpdatetm']);
            $slexpdatetm = $nextexp['next'];

            $sltoken = $salesstr['sltoken'];
            $slbatchlist = get_optionvals($sltoken, 'slbatchlist');
            $sltoken = put_optionvals($sltoken, 'slbatchlist', "{$salesstr['slbatch']}, " . $slbatchlist);

            $sltoken = put_optionvals($sltoken, 'solditname', $itemstr['itname']);
            $sltoken = put_optionvals($sltoken, 'solditprice', $itemstr['itprice']);
            $sltoken = put_optionvals($sltoken, 'solditsalesnote', $itemstr['itsalesnote']);

            $sladminfo = $sladminfo . chr(13) . $salesstr['sladminfo'];
            $data = array(
                'slmbrun' => $mbrstr['username'],
                'slexpdatetm' => $slexpdatetm,
                'slppid' => $mbrstr['mppid'],
                'slprice' => $slprice,
                'slbatch' => $slbatch,
                'slqty' => 1,
                'slstatus' => 1,
                'slrefid' => $slrefid,
                'slnote' => $itemstr['itsalesnote'],
                'sltoken' => $sltoken,
                'sladminfo' => $sladminfo,
            );

            $update = $db->update(DB_TBLPREFIX . '_sales', $data, array('slid' => $renewsalesid));
            $renewalstr = " ({$LANG['g_renewal']})";
        } else {
            $nextexp = get_actdate($itexpinval, $sldatetm);
            $slexpdatetm = $nextexp['next'];

            $mbritfield64 = get_optionvals($txstr['txtoken'], 'mbritfield');
            $slfield = base64_decode($mbritfield64 ?? '');

            $sltoken = put_optionvals($sltoken, 'solditname', $itemstr['itname']);
            $sltoken = put_optionvals($sltoken, 'solditprice', $itemstr['itprice']);
            $sltoken = put_optionvals($sltoken, 'solditsalesnote', $itemstr['itsalesnote']);

            $sladminfo = ($slprice != $itemstr['itprice']) ? "Default Item price " . $itemstr['itprice'] : '';
            $data = array(
                'slitid' => $itemstr['itid'],
                'slmbrid' => $mbrstr['id'],
                'slmbrun' => $mbrstr['username'],
                'sldatetm' => $sldatetm,
                'slexpdatetm' => $slexpdatetm,
                'slppid' => $mbrstr['mppid'],
                'slprice' => $slprice,
                'slbatch' => $slbatch,
                'slqty' => 1,
                'slstatus' => 1,
                'slrefid' => $slrefid,
                'slnote' => $itemstr['itsalesnote'],
                'slfield' => $slfield,
                'sltoken' => $sltoken,
                'sladminfo' => $sladminfo,
            );
            $insert = $db->insert(DB_TBLPREFIX . '_sales', $data);
            $newsalesid = $db->lastInsertId();
            $renewalstr = '';
        }
        // send message here
        $itdeliverarr = unserialize(base64_decode($itemstr['itdeliver'] ?? ''));
        if ($itdeliverarr['itmailstatus'] == 1) {
            $addmessage = $itdeliverarr['itmailbody'];
            $ordermessage = do_parsemsgcnt($mbrstr, $itemstr, $addmessage);
        } else {
            $ordermessage = '';
        }
        require_once('mailer.do.php');
        $cntaddarr['itemname'] = $itemstr['itname'] . $renewalstr;

        // if item id=1 = deposit, use transaction amount as the product price
        $itemprice = ($itemstr['itid'] == '1' && $slprice > 0) ? $slprice : $itemstr['itprice'];
        $cntaddarr['itemprice'] = $bpprow['currencysym'] . printmoney($itemprice) . ' ' . $bpprow['currencycode'];
        $cntaddarr['orderid'] = $slbatch;
        $cntaddarr['ordermessage'] = $ordermessage;
        $cntaddarr['fullname'] = $mbrstr['firstname'] . ' ' . $mbrstr['lastname'];
        $cntaddarr['login_url'] = $cfgrow['site_url'] . "/" . MBRFOLDER_NAME;
        delivermail('mbr_order', $mbrstr['id'], $cntaddarr);
    }
}

function get_salescmlist($mbrstr, $itemstr = array(), $sprstr = array(), $trxstr = array()) {
    $refcmlist = $sprcmlist = $mbrcashback = $outcmarr = array();

    $ittokenarr = get_optionvals($itemstr['ittoken']);

    $itemfee = ($trxstr['txamount'] > 0) ? $trxstr['txamount'] : $itemstr['itprice'];
    $itcmlist = $itemstr['itcmlist'];

    $itplancmlist = unserialize($itemstr['itplancmlist'] ?? '');

    $mpdepth = $mbrstr['mpdepth'];
    $sprlist = $mbrstr['sprlist'];
    $sprlistarr = explode(',', str_replace(array(' ', '|'), '', $sprlist ?? ''));
    $cmlistarr = explode(',', str_replace(' ', '', $itcmlist ?? ''));
    for ($i = 0; $i < $mpdepth; $i++) {
        $valarr = explode(':', $sprlistarr[$i] ?? '');
        $sprval = intval($valarr[1]);
        if ($sprval < 1) {
            break;
        }

        $sprstr = getmbrinfo('', '', $sprval);
        $cmplanlistarr = explode(',', str_replace(' ', '', $itplancmlist[$sprstr['mppid'] ?? '']));
        $getcmnow = ($cmplanlistarr[$i] > 0) ? $cmplanlistarr[$i] : $cmlistarr[$i];

        $sprcm = getamount($getcmnow, $itemfee);
        $sprcmlist[$sprval] = $sprcm;
    }
    $outcmarr['network'] = $sprcmlist;

    // personal
    $refstr = getmbrinfo($mbrstr['idref']);
    $itplandircmlistarr = unserialize($itemstr['itplandircmlist'] ?? '');
    $dircmplanlistarr = explode(',', str_replace(' ', '', $itplandircmlistarr[$refstr['mppid']] ?? ''));
    $refval = $refstr['mpid'];
    $refcmlist[$refval] = $dircmplanlistarr[0];
    $outcmarr['personal'] = $refcmlist;

    // cashback
    $mbrcashback[$mbrstr['mpid']] = $ittokenarr['itcashback'];
    $outcmarr['cashback'] = $mbrcashback;

    return $outcmarr;
}

function get_itpricebyplan($itemstr, $mppid, $fixedprice = 0) {
    if ($fixedprice <= 0) {
        $itplanpricepid = 0;
        $itprice = $itemstr['itprice'];
        if ($mppid > 0) {
            $itplanprice = unserialize($itemstr['itplanprice'] ?? '');
            $itplanpricepid = floatval($itplanprice[$mppid]);
        }
        $itprice = ($itplanpricepid > 0) ? $itplanpricepid : $itprice;
        $itprice = sprintf("%0.2f", $itprice);
    } else {
        $itprice = $fixedprice;
    }
    return $itprice;
}

function do_expsalesitem($limitcheck = 48) {
    global $db, $cfgrow;

    $now_utctime = $cfgrow['datetimestr'];
    $now_date = $cfgrow['datestr'];

    //reminder
    $remindreg = '3';
    if (intval($remindreg) > 0) {
        $expdarr = get_actdate($remindreg, $now_date);
        $remindate = $expdarr['next'];
        $condition = " AND slstatus = '1' AND slexpdatetm > sldatetm AND slexpdatetm <= '{$remindate}' AND sltoken NOT LIKE '%|rmdslexp:{$now_date}|%' ORDER BY RAND() LIMIT {$limitcheck}";
        $userData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_sales LEFT JOIN " . DB_TBLPREFIX . "_mbrs ON slmbrid = id LEFT JOIN " . DB_TBLPREFIX . "_items ON slitid = itid WHERE 1 " . $condition . "");

        if (count($userData) > 0) {
            foreach ($userData as $val) {
                require_once('mailer.do.php');
                $cntaddarr['itemname'] = $val['itname'];
                $cntaddarr['expirydate'] = formatdate($val['slexpdatetm']);
                $cntaddarr['fullname'] = $val['firstname'] . ' ' . $val['lastname'];
                $cntaddarr['login_url'] = $cfgrow['site_url'] . "/" . MBRFOLDER_NAME;
                delivermail('mbr_rebuy', $val['id'], $cntaddarr);

                $sltoken = put_optionvals($val['sltoken'], 'rmdslexp', $now_date);
                $db->update(DB_TBLPREFIX . '_sales', array('sltoken' => $sltoken), array('slid' => $val['slid']));
            }
        }
    }

    $condition = " AND slstatus = '1' AND slexpdatetm > sldatetm AND slexpdatetm < '{$now_utctime}' AND slprice > '0' ORDER BY RAND() LIMIT {$limitcheck}";
    $salesData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_sales WHERE 1 " . $condition . "");
    if (count($salesData) > 0) {
        foreach ($salesData as $val) {
            $db->update(DB_TBLPREFIX . '_sales', array('slstatus' => 3), array('slid' => $val['slid']));
        }
    }
}

function get_periodintervalstr($expday) {
    $expdaystrarr = array(
        '' => 'One-time',
        '30' => '30 Days',
        '1m' => 'Monthly',
        '2m' => 'Bimonthly',
        '3m' => 'Quarterly',
        '6m' => 'Half-yearly',
        '1y' => 'Yearly'
    );
    $intrvalstr = $expdaystrarr[$expday];
    return $intrvalstr;
}

function get_peppyinfo($byval, $byid = 'plid') {
    global $db;

    $peplRow = [];
    if ($byval != '') {
        $row = $db->getAllRecords(DB_TBLPREFIX . '_peppylink', '*', " AND {$byid} = '{$byval}' ORDER BY plid DESC");
        foreach ($row as $value) {
            $peplRow = array_merge($peplRow, $value);
        }
    }

    return $peplRow;
}

function del_peppyinfo($byval, $byid = 'plid') {
    global $db;

    $result = false;
    if ($byval != '') {
        $pepplstr = get_peppyinfo($byval, $byid);
        $ispeppldel = $db->delete(DB_TBLPREFIX . '_peppylink', array('plid' => $pepplstr['plid']));
        if ($ispeppldel) {
            $mbrstr = getmbrinfo('', '', $pepplstr['plsrcid']);
            $mbrtokenarr = get_optionvals($mbrstr['mbrtoken']);
            $peppyapi = base64_decode($mbrtokenarr['peppymbrapi'] ?? '');
            $pltoken = get_optionvals($pepplstr['pltoken']);
            $apikey = ($pltoken['APISRC'] == 'ADM') ? '' : $peppyapi;
            $qrfile = "../assets/imagextra/qr/{$pltoken['QRFILE']}";
            if (file_exists($qrfile)) {
                @unlink($qrfile);
            }

            $result = del_peppylink($apikey, $pepplstr['pllid']);
        }
    }
    return $result;
}

function get_fileinfo($byval, $byid = 'flid') {
    global $db;

    $fileRow = [];
    if ($byval != '') {
        $row = $db->getAllRecords(DB_TBLPREFIX . '_files', '*', " AND {$byid} = '{$byval}' ORDER BY flid DESC");
        foreach ($row as $value) {
            $fileRow = array_merge($fileRow, $value);
        }
        $fltoken = get_optionvals($fileRow['fltoken']);
        $fileRow['peppylinkpllid'] = $fltoken['flppllid'];
    }

    return $fileRow;
}

function get_pointrwdinfo($byval, $byid = 'prid') {
    global $db;

    $pointrwdRow = [];
    if ($byval != '') {
        $row = $db->getAllRecords(DB_TBLPREFIX . '_pointrwds', '*', " AND {$byid} = '{$byval}' ORDER BY prid DESC");
        foreach ($row as $value) {
            $pointrwdRow = array_merge($pointrwdRow, $value);
        }
    }

    return $pointrwdRow;
}

function do_shortener($srcArr, $url, $metatitle, $metadesc = '', $linkid = '') {
    global $db, $cfgrow;

    $pltype = ($srcArr['pltype'] != '') ? $srcArr['pltype'] : 'link';
    $result = get_peppylink($srcArr['peppyapi'], $url, $metatitle, $metadesc, $linkid);
    $peppllid = $result['id'];

    $shorturlstr = (strpos($result['shorturl'], 'https://') === false) ? "https://" . $result['shorturl'] : $result['shorturl'];

    if ($result['error'] == 0) {
        $plstatus = 1;
        $apisrc = ($srcArr['peppyapi'] == '') ? 'ADM' : 'USR';
        if ($linkid == '') {
            $data = array(
                'pldatetm' => $cfgrow['datetimestr'],
                'plupdate' => $cfgrow['datetimestr'],
                'plmbrid' => intval($srcArr['plmbrid']),
                'pllid' => $result['id'],
                'plsrcid' => intval($srcArr['plsrcid']),
                'pltype' => $pltype,
                'plsrc' => $url,
                'plurl' => $shorturlstr,
                'plstatus' => $plstatus,
                'pltoken' => "|APISRC:{$apisrc}|",
                'pladminfo' => '',
            );

            $peppystr = get_peppyinfo($url, 'plsrc');
            if ($peppystr['plid'] < 1) {
                $db->insert(DB_TBLPREFIX . '_peppylink', $data);
            } else {
                $db->update(DB_TBLPREFIX . '_peppylink', $data, array('plid' => $peppystr['plid']));
            }
            $result = 1;
        } else {
            // update
            $peplstr = get_peppyinfo($linkid, 'pllid');
            $pltoken = put_optionvals($peplstr['pltoken'], 'APISRC', $apisrc);
            $data = array(
                'plupdate' => $cfgrow['datetimestr'],
                'plmbrid' => intval($srcArr['plmbrid']),
                'pllid' => $result['id'],
                'plsrcid' => intval($srcArr['plsrcid']),
                'plsrc' => $url,
                'plurl' => $shorturlstr,
                'plstatus' => $plstatus,
                'pltoken' => $pltoken,
            );
            $db->update(DB_TBLPREFIX . '_peppylink', $data, array('pllid' => $linkid));
            $result = 1;
        }

        // user
        if ($srcArr['plmbrid'] > 0 && $srcArr['plsrcid'] > 0) {
            $mbrstr = getmbrinfo('', '', $srcArr['plsrcid']);
            $mptoken = put_optionvals($mbrstr['mptoken'], 'mppllid', $peppllid);
            $data = array(
                'mptoken' => $mptoken,
            );
            $db->update(DB_TBLPREFIX . '_mbrplans', $data, array('mpid' => $srcArr['plsrcid']));
        }

        // file
        if ($pltype == 'file') {
            $filestr = get_fileinfo($srcArr['plsrcid']);
            $fltoken = put_optionvals($filestr['fltoken'], 'flppllid', $peppllid);
            $data = array(
                'fltoken' => $fltoken,
            );
            $db->update(DB_TBLPREFIX . '_files', $data, array('flid' => $srcArr['plsrcid']));
        }
    }
    return $result;
}
