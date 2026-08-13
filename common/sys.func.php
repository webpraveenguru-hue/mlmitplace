<?php

if (!defined('OK_LOADME')) {
    die("<title>Error!</title><body>No such file or directory.</body>");
}
include("sys.class.php");
require(dirname(__DIR__, 1) . "/assets/fellow/peppy-link/funcs.php");

function get_rankrules($mprankid = 0) {
    global $db;

    $rankarr = array();
    $condition = " AND rkstatus = '1'";
    $condition .= ($mprankid > 0) ? " AND rkid = '{$mprankid}'" : "";
    $rankData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_ranks WHERE 1 AND rkname != '' " . $condition . " ORDER BY rkid");
    if (count($rankData) > 0) {
        foreach ($rankData as $val) {
            $rkid = $val['rkid'];
            $rktodolistarr = json_decode($val['rktodolist'], 1);
            $rkbonuslistarr = json_decode($val['rkbonuslist'], 1);

            $rankarr[$rkid]['id'] = $rkid;
            $rankarr[$rkid]['name'] = $val['rkname'];
            $rankarr[$rkid]['minpoint'] = $val['rkminpoint'];
            $rankarr[$rkid]['rulemydl'] = $rktodolistarr['rulemydl'];
            $rankarr[$rkid]['minmydl'] = $rktodolistarr['minmydl'];
            $rankarr[$rkid]['ruletotdl'] = $rktodolistarr['ruletotdl'];
            $rankarr[$rkid]['mintotdl'] = $rktodolistarr['mintotdl'];
            $rankarr[$rkid]['myppid'] = $rktodolistarr['myppid'];
            $rankarr[$rkid]['ruleslvol'] = $rktodolistarr['ruleslvol'];
            $rankarr[$rkid]['minslvol'] = $rktodolistarr['minslvol'];
            $rankarr[$rkid]['ruleslval'] = $rktodolistarr['ruleslval'];
            $rankarr[$rkid]['minslval'] = $rktodolistarr['minslval'];
            $rankarr[$rkid]['rankcmlist'] = $rkbonuslistarr['rankcmlist'];
            $rankarr[$rkid]['adjcmdrlist'] = $rkbonuslistarr['adjcmdrlist'];
            $rankarr[$rkid]['adjcmlist'] = $rkbonuslistarr['adjcmlist'];
            $rankarr[$rkid]['adjcmlistrnew'] = $rkbonuslistarr['adjcmlistrnew'];
        }
    }
    ksort($rankarr);
    return $rankarr;
}

function get_netcmrank($rktokencm = 'adjcmlist', $mprankid = 0, $cmrank = 0, $i = 0) {
    if ($mprankid > 0) {
        $rankrulesarr = get_rankrules($mprankid);
        $adjcmlist = $rankrulesarr[$mprankid][$rktokencm];
        $adjcmarr = explode(',', $adjcmlist ?? '');
        $adjcm = trim($adjcmarr[$i] ?? '');

        $newcmrank = floatval($cmrank);
        // if adjustment is percentage
        $adjcmval = (strpos($adjcm ?? '', '%') !== false && $adjcm > 0) ? $newcmrank * $adjcm / 100 : $adjcm;
        if (strpos($adjcm ?? '', '+') !== false || strpos($adjcm ?? '', '-') !== false) {
            $newcmrank = $newcmrank + floatval($adjcmval);
        } else if ($adjcm != '') {
            $newcmrank = $adjcmval;
        }
        // if percentage
        if (strpos($cmrank ?? '', '%') !== false) {
            $newcmrank .= '%';
        }
    } else {
        $newcmrank = $cmrank;
    }

    return $newcmrank;
}

function get_mbrtotvalrank($mpid, $ppid = 0) {
    global $db;

    $mbrStr = getmbrinfo('', '', $mpid);

    // ---
    $totvals = [];
    $ppidsql = ($ppid > 0) ? " AND mppid = '{$ppid}'" : '';

    // total personal referrals
    $condition = " AND idref = '{$mbrStr['id']}'" . $ppidsql;
    $row = $db->getAllRecords(DB_TBLPREFIX . '_mbrplans', 'COUNT(*) as totref', $condition);
    $totvals['totmyref'] = $row[0]['totref'];

    // total referrals
    $condition = " AND sprlist LIKE '%:{$mbrStr['mpid']}|%'" . $ppidsql;
    $row = $db->getAllRecords(DB_TBLPREFIX . '_mbrplans ', 'COUNT(*) as totref ', $condition);
    $totvals['totalldl'] = $row[0]['totref'];
    $condition = " AND (sprlist LIKE '%:{$mbrStr['mpid']}|%')";

    // total team sales volume/number
    $row = $db->getRecFrmQry("SELECT COUNT(*) as volsales FROM " . DB_TBLPREFIX . "_sales, (SELECT idmbr FROM " . DB_TBLPREFIX . "_mbrplans WHERE 1" . $condition . ") as idmbr WHERE slrefid = idmbr AND slstatus = '1'");
    $totvals['volsales'] = $row[0]['volsales'];

    // total team sales value/revenue
    $row = $db->getRecFrmQry("SELECT SUM(slprice) as valsales FROM " . DB_TBLPREFIX . "_sales, (SELECT idmbr FROM " . DB_TBLPREFIX . "_mbrplans WHERE 1" . $condition . ") as idmbr WHERE slrefid = idmbr AND slstatus = '1'");
    $totvals['valsales'] = $row[0]['valsales'];
    $mbrStr['totvalsrank'] = $totvals;
    return $mbrStr;
}

function do_ranker($mbrstr, $forcegetrank = '0') {
    global $db, $bpparr, $cfgtoken, $LANG;

    $mpid = $mbrstr['mpid'];
    if ($mpid < 1 || ($forcegetrank == '0' && $cfgtoken['rankmbropt'] == '0')) {
        return;
    }

    $sprlist = str_replace(' ', '', $mbrstr['sprlist'] ?? '');
    $sprlistarr = explode(',', $sprlist ?? '');
    $newsprlist = [];
    $newsprlist[] = $mpid;
    foreach ($sprlistarr as $key => $value) {
        $valarr = explode(':', $value ?? '');
        $sprval = intval(str_replace('|', '', $valarr[1] ?? ''));
        $newsprlist[] = $sprval;
    }

    $mpdepth = $bpparr[$mbrstr['mppid']]['maxdepth'];
    if ($mpdepth > 0) {
        $newsprlist = array_slice($newsprlist, 0, $mpdepth);
    }

    // check rank member and their sponsors
    foreach ($newsprlist as $key => $value) {

        $mbrStr = getmbrinfo('', '', $value);
        if ($mbrStr['username'] == '' || strpos($mbrStr['mptoken'] ?? '', '|isranklock:1|') !== false) {
            continue;
        }

        $mprankid = $mbrStr['mprankid'];
        $isdonextrank = 1;
        $isresetrank = 1;
        $baserankid = $nextrankid = ($isresetrank == 1) ? 0 : $mprankid;
        $nextranklist = get_rankrules();
        foreach ($nextranklist as $key => $val) {

            $usrStr = get_mbrtotvalrank($mbrStr['mpid'], $val['myppid']);

            // total personal referrals
            $myrefonly = $usrStr['totvalsrank']['totmyref'];

            // total referrals
            $myreftotal = $usrStr['totvalsrank']['totalldl'];

            // total team sales volume/number
            $myvolsales = $usrStr['totvalsrank']['volsales'];

            // total team sales value/revenue
            $myvalsales = $usrStr['totvalsrank']['valsales'];

            if ($val['rulepoint'] != '') {
                $ismypoint = ($val['minpoint'] <= $mbrpoint || floatval($val['minpoint'] <= 0)) ? 1 : 0;
                $isdonextrank = ($ismypoint != 1 && $val['rulepoint'] == 'and') ? 0 : 1;
            }
            if ($val['rulemydl'] != '') {
                $ismyrefonly = ($val['minmydl'] <= $myrefonly || floatval($val['minmydl'] <= 0)) ? 1 : 0;
                $isdonextrank = ($ismyrefonly != 1 && $val['rulemydl'] == 'and') ? 0 : $isdonextrank;
            }
            if ($val['ruletotdl'] != '') {
                $ismyreftotal = ($val['mintotdl'] <= $myreftotal || floatval($val['mintotdl'] <= 0)) ? 1 : 0;
                $isdonextrank = ($ismyreftotal != 1 && $val['ruletotdl'] == 'and') ? 0 : $isdonextrank;
            }

            if ($val['ruleslvol'] != '') {
                $ismyvolsales = ($val['minslvol'] <= $myvolsales || floatval($val['minslvol'] <= 0)) ? 1 : 0;
                $isdonextrank = ($ismyvolsales != 1 && $val['ruleslvol'] == 'and') ? 0 : $isdonextrank;
            }
            if ($val['ruleslval'] != '') {
                $myvalsales = ($val['minslval'] <= $myvalsales || floatval($val['minslval'] <= 0)) ? 1 : 0;
                $isdonextrank = ($myvalsales != 1 && $val['ruleslval'] == 'and') ? 0 : $isdonextrank;
            }

            // get next rank id and name
            $nextrankid = ($isdonextrank == 1) ? $val['id'] : $baserankid;
            $nextrankname = $val['name'];

            // safe rank id for next rank rules
            $baserankid = $nextrankid;
        }

        if ($nextrankid != $mprankid) {
            // update member rank id
            $data = array(
                'mprankid' => $nextrankid,
            );
            $update = $db->update(DB_TBLPREFIX . '_mbrplans', $data, array('mpid' => $usrStr['mpid']));

            // generate reward for rank achiever
            $condition = " AND txtoken LIKE 'RANK{$nextrankid}|%' AND txtoken LIKE '%|LCM:RANKBON|%'";
            $existTxData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_transactions WHERE 1 " . $condition . "");
            if (count($existTxData) < 1) {
                $bonusamount = get_netcmrank('rankcmlist', $nextrankid);
                $rankbonuslist = array($usrStr['id'] => $bonusamount);
                $dumbtrxstr = [];

                // to avoid duplicate transaction for rank bonus
                $dumbtrxstr['txid'] = 'DT' . date('Ymd') . 'RANK' . $nextrankid;
                $cmcountarr = addcmlist($LANG['g_rankbonus'] . ' ' . $nextrankname, 'RANKBON', $rankbonuslist, $usrStr, $dumbtrxstr);
            }
        }

        $nextrankidstr = ($nextrankid > 0) ? $nextrankid : $mprankid;
    }
}

function ranklist($rkid = 0, $listopt = 0, $addopt = '') {
    global $db, $frlmtdcfg;

    $resultarr = [];
    $isdisable = ($addopt != '') ? ' disabled' : ' selected';
    $result = "<option value=''{$isdisable}>-</option>";
    $rankData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_ranks WHERE 1 AND rkname != '' LIMIT 10");
    $doselect = '';
    $rkcount = 0;
    if (count($rankData) > 0) {
        foreach ($rankData as $key => $val) {
            $resultarr[$val['rkid']] = $val;
            if ($listopt != 1) {
                continue;
            }
            if ($val['rkid'] == $rkid) {
                $isselect = " selected";
                $doselect = 1;
            } else {
                $isselect = "";
            }
            $rkstatusstr = ($val['rkstatus'] == '1') ? " &#10003;" : "";
            $result .= "<option value='{$val['rkid']}'{$isselect}>{$val['rkname']}{$rkstatusstr}";
            $rkcount++;
        }

        if ($addopt !== '' && $rkcount < $frlmtdcfg['mxranks']) {
            $isselect = ($doselect == '') ? " selected" : '';
            $result .= "<option value='0'{$isselect}>{$addopt}";
        }
    }
    if ($listopt == 0) {
        $result = ($rkid > 0) ? $resultarr[$rkid] : $resultarr;
    }

    return $result;
}

function show_rankcolorlist($isall = 0) {
    $colorlist = '';
    $rankarr = ranklist();
    if (count($rankarr) > 0) {
        $colorlist = "<style>.ranklegend{margin: 0 2px 0 8px;padding: 2px 11px;border: 1px dotted #555;-webkit-border-radius: 50%;-moz-border-radius: 50%;}</style><strong>Rank:</strong>";
        foreach ($rankarr as $key => $val) {
            if ($isall != 1 && $val['rkstatus'] != 1) {
                continue;
            }
            $rkstatusclor = ($val['rkstatus'] != 1) ? 'text-danger' : 'text-secondary';
            $rkbgcolor = get_optionvals($val['rktoken'], 'rkbgcolor');
            $colorlist .= "<span class='ranklegend' style='background-color: {$rkbgcolor};' data-toggle='tooltip' title='{$val['rkname']}'></span><span class='{$rkstatusclor}'>{$val['rkname']}</span>";
        }
    }
    return $colorlist;
}

function get_codeconfirm($mbrstr) {
    global $db, $cfgrow;

    $mbrtokenarr = get_optionvals($mbrstr['mbrtoken']);
    $regconfirmdate = $mbrtokenarr['regconfirmdate'];
    if ($cfgrow['datestr'] > $regconfirmdate && $mbrstr['email'] != '') {
        $_SESSION['emc64'] = base64_encode($mbrstr['email'] ?? '');

        $confirmrand = mt_rand(1, 9999);
        $confirmkey = md5($confirmrand . '~' . INSTALL_KEYS . '/' . $cfgrow['datestr'] . $mbrstr['id']);
        $confirmhash = md5($confirmrand . '^' . INSTALL_KEYS . '/' . $cfgrow['datestr'] . $mbrstr['email']);
        $mbrtoken = $mbrstr['mbrtoken'];
        $mbrtoken = put_optionvals($mbrtoken, 'regconfirmrand', $confirmrand);
        $mbrtoken = put_optionvals($mbrtoken, 'regconfirmkey', $confirmkey);
        $mbrtoken = put_optionvals($mbrtoken, 'regconfirmhash', $confirmhash);
        $mbrtoken = put_optionvals($mbrtoken, 'regconfirmdate', $cfgrow['datestr']);
        $data = array(
            'mbrtoken' => $mbrtoken,
        );
        $update = $db->update(DB_TBLPREFIX . '_mbrs', $data, array('id' => $mbrstr['id']));

        require_once(INSTALL_PATH . '/common/mailer.do.php');
        $cntaddarr['emailconfirmlink'] = $cfgrow['site_url'] . "/" . MBRFOLDER_NAME . "/regconfirm.php?hashc={$confirmhash}&randc={$confirmrand}";
        $cntaddarr['emailconfirmcode'] = $confirmkey;
        delivermail('mbr_confirmemail', $mbrstr['id'], $cntaddarr);
    }
}

function do_regandnotif($mbrid, $mppid, $refid, $fullname, $password) {
    global $cfgrow, $cfgtoken, $bpparr;

    if ($cfgtoken['isautoregplan'] == 1) {
        // register to membership
        $mbrstr = getmbrinfo($mbrid);
        regmbrplans($mbrstr, $refid, $mppid);
    }

    // send welcome email
    require_once(INSTALL_PATH . '/common/mailer.do.php');
    $cntaddarr['ppname'] = $bpparr[$mppid]['ppname'];
    $cntaddarr['fullname'] = $fullname;
    $cntaddarr['login_url'] = $cfgrow['site_url'] . "/" . MBRFOLDER_NAME;
    $cntaddarr['rawpassword'] = $password;
    delivermail('mbr_reg', $mbrid, $cntaddarr);
}

function get_salesinfo($tbvalue, $tbfield = 'slid') {
    global $db;

    $slRow = [];
    $tbvalue = mystriptag($tbvalue);
    if ($tbvalue != '') {
        $row = $db->getAllRecords(DB_TBLPREFIX . '_sales', '*', " AND {$tbfield} = '{$tbvalue}'");
        $slRow = $row[0];
    }

    return $slRow;
}

function do_parsemsgcnt($mbrstr, $itemstr, $str) {
    $tagsarr = array('[[firstname]]', '[[fullname]]', '[[username]]', '[[email]]', '[[itemname]]');
    $valarr = array($mbrstr['firstname'], $mbrstr['fullname'], $mbrstr['username'], $mbrstr['email'], $itemstr['itname']);
    $returnstr = str_replace($tagsarr, $valarr, $str);
    return $returnstr;
}

function do_eskep($dataarr) {
    $deterarr = array("'", '"', ';', 'or ', 'union ', ' select ', ' table ', '--', 'drop ', 'update ');
    $outarr = str_ireplace($deterarr, '', $dataarr);
    return $outarr;
}

function get_itemlist($mbrstr, $selitid = '', $withadm = 0) {
    global $db;

    $result = '';
    $condition = ($withadm == 1) ? " OR itidmbr = '0'" : '';
    $itemData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_items WHERE 1 AND (itidmbr = '{$mbrstr['id']}' " . $condition . ")");
    if (count($itemData) > 0) {
        foreach ($itemData as $val) {
            $isselect = ($val['itid'] == $selitid) ? " selected" : "";
            $isselect = ($val['itstatus'] != '1') ? " disabled" : $isselect;
            $result .= "<option value='{$val['itid']}'{$isselect}>{$val['itname']}";
        }
    }
    return $result;
}

function do_vendorsl($txid) {
    global $db, $LANG;

    $txstr = get_txinfo($txid);
    $addtxsfee = get_optionvals($txstr['txtoken'], 'addtxsfee');

    $itemarr = get_iteminfo($txstr['txitid']);
    if ($itemarr['itidmbr'] > 0) {
        $txData = $db->getRecFrmQry("SELECT SUM(txamount) as allcm FROM " . DB_TBLPREFIX . "_transactions WHERE 1 AND txfromid = '0'  AND txtoid > '0' AND (txtoken LIKE '%|SRCTXID:{$txstr['txid']}|%' AND txtoken LIKE '%|SRCIDMBR:{$txstr['txfromid']}|%')");
        $totamountout = $txData[0]['allcm'];

        $txamountnet = $txstr['txamount'] - $addtxsfee;
        $vendorearn = $txamountnet - $totamountout;

        $txbatch = $txstr['txbatch'];
        $mbrstr = getmbrinfo($itemarr['itidmbr']);

        $addtxtoken = "|SRCTXID:{$txstr['txid']}|, |SRCTXBATCH:{$txbatch}|, |WLVENDOR:{$mbrstr['id']}|";

        // do vendor earning
        $addtxtokenearn = $addtxtoken . ", |TXVENDOR:EARN|";
        $newewalletfund = $mbrstr['ewallet'] + $vendorearn;
        adjusttrxwallet($mbrstr['ewallet'], $newewalletfund, $mbrstr['id'], $LANG['m_vendorearning'] . " ({$txbatch})", '', '', $addtxtokenearn);

        $data = array(
            'ewallet' => $netewalletfund,
        );
        $db->update(DB_TBLPREFIX . '_mbrs', $data, array('id' => $mbrstr['id']));
    } else {
        $vendorearn = '';
    }

    return $vendorearn;
}

function get_newsletter($emrid = '') {
    global $db, $cfgrow;

    // collect member list to receive the newsletter
    if ($emrid > 0) {
        // get newsletter details
        $condition = " AND emrstatus = '1' AND emrtoken NOT LIKE '%|issent:1|%'";
        $row = $db->getAllRecords(DB_TBLPREFIX . '_emailrec', '*', " AND emrid = '{$emrid}'" . $condition);
        $emRow = $row[0];

        // get recipient based on newsletter program and membership avaibility
        $condition = " AND optinme = '1'";
        // program
        $emrppidarr = str_getcsv($emRow['emrppid'] ?? '');
        $condition .= ($emRow['emrppid'] != '' && is_array($emrppidarr)) ? " AND (mppid = '" . implode("' OR mppid = '", $emrppidarr) . "')" : '';
        // avaibility
        $condition .= ($emRow['emrmpstatus'] > 0) ? " AND mpstatus = '{$emRow['emrmpstatus']}'" : '';

        $totemail = 0;
        $row = $db->getAllRecords(DB_TBLPREFIX . "_mbrs LEFT JOIN " . DB_TBLPREFIX . "_mbrplans ON id = idmbr", '*', $condition . " ORDER BY RAND()");
        foreach ($row as $key => $value) {
            // insert recipient to the emailqueue
            $emqsubject = parsenotify($value, $emRow['emrsubject']);
            $emqbody = parsenotify($value, $emRow['emrbody']);
            $emqhtmlbody = parsenotify($value, $emRow['emrhtmlbody']);
            $emqto = '"' . $value['firstname'] . '","' . $value['email'] . '"';

            $data = array(
                'emqemrid' => $emrid,
                'emqdatetm' => $cfgrow['datetimestr'],
                'emqfrom' => $emRow['emrfrom'],
                'emqto' => $emqto,
                'emqsubject' => $emqsubject,
                'emqbody' => $emqbody,
                'emqhtmlbody' => $emqhtmlbody,
                'emqpriority' => $emRow['emrpriority'],
            );
            $insertid = $db->insert(DB_TBLPREFIX . '_emailqueue', $data);
            if ($insertid) {
                $totemail++;
            }
        }

        // update newsletter has been processed
        $emrtoken = $emRow['emrtoken'];
        $emrtoken = put_optionvals($emrtoken, 'issent', 1);
        $emrtoken = put_optionvals($emrtoken, 'totemail', $totemail);
        $emrtoken = put_optionvals($emrtoken, 'execdate', $cfgrow['datetimestr']);
        $emrtoken = put_optionvals($emrtoken, 'emqueue', $totemail);
        $emrtoken = put_optionvals($emrtoken, 'emsent', 0);
        $data = array(
            'emrstatus' => '1',
            'emrtoken' => $emrtoken,
        );
        $db->update(DB_TBLPREFIX . '_emailrec', $data, array('emrid' => $emrid));
        return $totemail;
    } else {
        return 0;
    }
}

function do_newsletter($limitsend = 99) {
    global $db, $cfgrow;

    if (defined('INSTALL_PATH')) {
        // check scheduled newsletter
        $emrdatesent = $cfgrow['datetimestr'];
        $condition = " AND emrdatesent <= '{$emrdatesent}'";
        $row = $db->getAllRecords(DB_TBLPREFIX . '_emailrec', '*', " AND emrstatus = '2'" . $condition);
        foreach ($row as $key => $value) {
            get_newsletter($value['emrid']);
        }

        // broadcast newsletter
        $hitsent = [];
        require_once(INSTALL_PATH . '/common/mailer.do.php');
        $row = $db->getAllRecords(DB_TBLPREFIX . '_emailqueue', '*', " AND emqsubject != '' LIMIT {$limitsend}");
        foreach ($row as $key => $value) {
            $emqtoarr = str_getcsv($value['emqto'] ?? '');
            $emailtoname = $emqtoarr[0];
            $emailtoaddr = $emqtoarr[1];
            $emqfromarr = str_getcsv($value['emqfrom'] ?? '');
            $emailfromname = $emqfromarr[0];
            $emailfromaddr = $emqfromarr[1];
            $issent = domailer($emailtoname, $emailtoaddr, $value['emqsubject'], $value['emqhtmlbody'], $value['emqbody'], '', '', 0, $emailfromname, $emailfromaddr, $value['emqpriority']);

            if ($issent) {
                $hitsent[$value['emqemrid']] = $hitsent[$value['emqemrid']] + 1;
            } else {
                // if retry again
                $data = array(
                    'emqdatetm' => $cfgrow['datetimestr'],
                    'emqstatus' => 'retry',
                );
            }
            $db->doQueryStr("DELETE FROM " . DB_TBLPREFIX . "_emailqueue WHERE emqid = '{$value['emqid']}'");
        }

        // update newsletter
        foreach ($hitsent as $emrid => $senttot) {
            $row = $db->getAllRecords(DB_TBLPREFIX . '_emailrec', '*', " AND emrid = '{$emrid}'");
            $emrRow = $row[0];
            $emrtokenarr = get_optionvals($emrRow['emrtoken']);
            $emsent = intval($emrtokenarr['emsent']) + intval($senttot);
            $emqueue = abs(intval($emrtokenarr['totemail']) - $emsent);

            $emrtoken = $emrRow['emrtoken'];
            $emrtoken = put_optionvals($emrtoken, 'execdate', $cfgrow['datetimestr']);
            $emrtoken = put_optionvals($emrtoken, 'emsent', $emsent);
            $emrtoken = put_optionvals($emrtoken, 'emqueue', $emqueue);
            $data = array(
                'emrtoken' => $emrtoken,
            );
            $db->update(DB_TBLPREFIX . '_emailrec', $data, array('emrid' => $emrRow['emrid']));
        }
    }
}

function do_planautoregbywallet($limitproc = 5) {
    global $db, $cfgrow, $payrow, $bptoken;

    if ($bptoken['isplanautoregbywallet'] == '1') {
        // default referrer id
        $hitproc = 0;

        // default referrer id
        $refarr = explode(',', str_replace(' ', '', $cfgrow['defaultref'] ?? ''));
        $refstr = getmbrinfo($refarr[0], 'username');
        $defrefid = ($refstr['id'] > 0) ? $refstr['id'] : 1;

        // get list of enable plan in order
        $condition = " AND planstatus > '0' ORDER BY ppid ASC";
        $planData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_payplans WHERE 1 AND ppname != '' " . $condition . "");
        if (count($planData) > 0) {
            foreach ($planData as $value) {
                $regfee = $value['regfee'];
                $feevalue = getamount($payrow['ewalletfee'], $regfee);
                $walletmin = $regfee + $feevalue;
                // search member with enough wallet ballance but that not registered to the plan
                $conditi = " AND ewallet >= '{$walletmin}' AND mbrstatus = '1'";
                $userData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_mbrs WHERE 1" . $conditi . "");
                foreach ($userData as $val) {
                    if ($hitproc > $limitproc) {
                        break 2;
                    }
                    $condi = " AND idmbr = '{$val['id']}' AND mppid = '{$value['ppid']}'";
                    $mbrData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_mbrplans WHERE 1" . $condi . "");
                    if (count($mbrData) < 1) {
                        // register member to the plan
                        $mbrstr = getmbrinfo($val['id']);
                        $idref = ($mbrstr['idref'] > 0) ? $mbrstr['idref'] : $defrefid;
                        do_autoregplan($mbrstr, 0, $idref, $value['ppid'], 'wallet');
                        $hitproc++;
                    }
                }
            }
        }
    }
}

function get_icopercentage($totval, $val, $type = '') {
    $percenval = ($val > 0 && $totval > 0) ? 100 * $val / $totval : 0;

    if ($type == 'ref') {
        $staroff = "<i class='fas fa-star'></i>";
        $staron = "<i class='fas fa-star text-warning'></i>";
        switch (true) {
            case ($val <= 0):
                $icostr = ""
                        . $staroff
                        . $staroff
                        . $staroff
                        . $staroff
                        . $staroff;
                break;
            case ($percenval <= 10):
                $icostr = ""
                        . $staron
                        . $staroff
                        . $staroff
                        . $staroff
                        . $staroff;
                break;
            case ($percenval > 10 && $percenval <= 25):
                $icostr = ""
                        . $staron
                        . $staron
                        . $staroff
                        . $staroff
                        . $staroff;
                break;
            case ($percenval > 25 && $percenval <= 50):
                $icostr = ""
                        . $staron
                        . $staron
                        . $staron
                        . $staroff
                        . $staroff;
                break;
            case ($percenval > 50 && $percenval <= 75):
                $icostr = ""
                        . $staron
                        . $staron
                        . $staron
                        . $staron
                        . $staroff;
                break;
            default:
                $icostr = ""
                        . $staron
                        . $staron
                        . $staron
                        . $staron
                        . $staron;
        }
    } else if ($type == 'hit') {
        $yesterdate = date("Y-m-d H:i:s", strtotime('-24 hours', strtotime($totval)));
        $moreyesterdate = date("Y-m-d H:i:s", strtotime('-72 hours', strtotime($totval)));
        switch (true) {
            case ($val == ''):
                $icostr = ""
                        . "<i class='fas fa-frown'></i>";
                break;
            case ($yesterdate <= $val):
                $icostr = ""
                        . "<i class='fas fa-grin-stars text-success'></i>";
                break;
            case ($moreyesterdate <= $val):
                $icostr = ""
                        . "<i class='fas fa-smile-beam text-info'></i>";
                break;
            default:
                $icostr = ""
                        . "<i class='fas fa-meh text-secondary'></i>";
        }
    } else if ($type == 'amount') {
        switch (true) {
            case ($val <= 0):
                $icostr = ""
                        . "<i class='fas fa-frown'></i>";
                break;
            case ($percenval <= 25):
                $icostr = ""
                        . "<i class='fas fa-smile text-secondary'></i>";
                break;
            case ($percenval > 25 && $percenval <= 75):
                $icostr = ""
                        . "<i class='fas fa-smile-beam text-info'></i>";
                break;
            default:
                $icostr = ""
                        . "<i class='fas fa-grin-stars text-success'></i>";
        }
    }

    return $icostr;
}

function printmoney($number, $decimalpoint = 2) {
    global $LANG;

    // return number_format($number ?? 0, 2);
    // The following code requires the php-intl module or Intl PHP Extensions
    $locale = ($LANG['lang_locale'] != '') ? $LANG['lang_locale'] : 'en_US';
    $formatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);
    $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $decimalpoint);
    return $formatter->format($number ?? 0);
}

function dlcsv_mbrdata($flname = 'mbrsdata', $arrlabel = array(), $arrdata = array(), $condition = '', $arrdataext = array()) {
    global $db;

    $datatime = date("mdHi");
    header('Content-Description: File Transfer');
    header('Content-Type: application/csv');
    header("Content-Transfer-Encoding: UTF-8");
    header("Content-Disposition: attachment; filename={$flname}-{$datatime}.csv");
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

    $handle = fopen('php://output', 'w');
    // clean slate
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    // Set label for data
    fputcsv($handle, $arrlabel);

    // group by based on exported data
    $groupbysql = ($flname == 'mbrsdata') ? " GROUP BY id " : " GROUP BY txid ";

    $arrdataextkeys = array_flip($arrdataext);
    $getdata = implode(',', $arrdata ?? []);
    $userData = $db->getRecFrmQry("SELECT {$getdata} FROM " . DB_TBLPREFIX . "_mbrs AS mbr LEFT JOIN " . DB_TBLPREFIX . "_mbrplans AS plan ON id = idmbr LEFT JOIN " . DB_TBLPREFIX . "_paygates AS pgt ON mbr.id = pgt.pgidmbr LEFT JOIN " . DB_TBLPREFIX . "_transactions AS txn ON mbr.id = txn.txtoid WHERE 1 " . $condition . $groupbysql);
    $countdata = count($userData);
    if ($countdata > 0) {
        // direct to buffered output
        foreach ($userData as $val) {
            $val = array_merge($val, $arrdataextkeys);
            $mbrstr = getmbrinfo($val['id']);

            if (array_key_exists('manualpayipn', $val)) {
                $val['manualpayipn'] = base64_decode($val['manualpayipn'] ?? '');
            }
            $pgdatatoken = $mbrstr['pgdatatoken'];
            $pgmbrtokenarr = get_optionvals($pgdatatoken);
            $mbrbankcfg = get_optarr($pgmbrtokenarr['bankcfg']);

            if (array_key_exists('bankname', $val)) {
                $val['bankname'] = $mbrbankcfg['bankname'];
            }
            if (array_key_exists('bankaccno', $val)) {
                $val['bankaccno'] = $mbrbankcfg['bankaccno'];
            }
            if (array_key_exists('bankaccname', $val)) {
                $val['bankaccname'] = $mbrbankcfg['bankaccname'];
            }

            if (array_key_exists('idspr', $val)) {
                $sprstr = getmbrinfo($val['idspr']);
                $val['idspr'] = $sprstr['username'];
            }

            fputcsv($handle, $val);
        }

        ob_end_flush();
        fclose($handle);
        die();
    }
}

function do_preparetxorderit($mbrstr, $itemstr, $itpricenow, $txtokenrenew = '') {
    global $db, $cfgrow;

    $data = array(
        'txdatetm' => $cfgrow['datetimestr'],
        'txfromid' => $mbrstr['id'],
        'txamount' => (float) $itpricenow,
        'txmemo' => 'Order ' . $itemstr['itname'],
        'txppid' => $mbrstr['mppid'],
        'txitid' => $itemstr['itid'],
        'txtoken' => "|TXTYPE:ORDER|, |STORE:{$itemstr['itsku']}|" . $txtokenrenew,
    );
    $insert = $db->insert(DB_TBLPREFIX . '_transactions', $data);
    $txidstr = $db->lastInsertId();

    return $txidstr;
}

function dodb_point($pofromid, $mbrstr = array(), $point = 0, $memo = '', $potype = '0', $potoken = '', $postatus = 1) {
    global $db, $cfgrow;

    $potoid = $mbrstr['id'];
    $popoint = floatval($point);

    if ($mbrstr['id'] > 0 && $popoint != 0) {
        if ($popoint < 0) {
            $potoid = $pofromid;
            $pofromid = $mbrstr['id'];
        }
        $data = array(
            'podatetm' => $cfgrow['datetimestr'],
            'pofromid' => $pofromid,
            'potoid' => $potoid,
            'popoint' => $popoint,
            'pomemo' => $memo,
            'postatus' => $postatus,
            'potype' => $potype,
            'poppid' => $mbrstr['mppid'],
            'potoken' => $potoken,
        );
        $insert = $db->insert(DB_TBLPREFIX . '_points', $data);

        $epoint = $mbrstr['epoint'] + $popoint;
        $data = array(
            'epoint' => $epoint,
        );
        $update = $db->update(DB_TBLPREFIX . '_mbrs', $data, array('id' => $mbrstr['id']));
    }
}

function sprlistarr($sprlist) {
    $spr_arr = array();
    $sprlist_arr = explode(',', str_replace(' ', '', $sprlist ?? ''));
    foreach ($sprlist_arr as $key => $value) {
        $sprl_arr = explode(':', str_replace('|', '', $value ?? ''));
        $spr_arr[] = $sprl_arr[1];
    }
    return $spr_arr;
}

function dodb_sprpoint($mbrstr, $potype = '1', $txid = '', $pointsrc = '') {
    global $db, $bpparr;

    $sprlistarr = sprlistarr($mbrstr['sprlist']);
    foreach ($sprlistarr as $key => $val) {
        $postier = $key + 1;
        $sprid = $val;

        if ($sprid < 1 || $mbrstr['mpdepth'] < $postier) {
            break;
        }

        $sprstr = getmbrinfo($sprid);

        if ($pointsrc == 'reg') {
            $mpidspr = ($sprstr['mppid'] > $mbrstr['mppid']) ? $mbrstr['mppid'] : $sprstr['mppid'];
            $bppstr = (defined('ISCALC_BYNEWMBR')) ? $bpparr[$mbrstr['mppid']] : $bpparr[$mpidspr];
            $sprpoints = explode(',', str_replace(' ', '', $bppstr['sprpointlist'] ?? ''));
            $memo = "REG{$postier}-{$mbrstr['username']}";
            $potoken = "|DLNIDPOINT:{$txid}|";
        } else if ($pointsrc == 'sales') {
            $txarr = get_txinfo($txid);
            $itarr = get_iteminfo($txarr['txitid']);
            $sprpoints = explode(',', str_replace(' ', '', $itarr['itpointlist'] ?? ''));
            $memo = "PUR{$postier}-{$mbrstr['username']}";
            $potoken = "|SLSIDPOINT:{$txid}|";
        }

        $pontval = $sprpoints[$key];
        if ($pontval > 0 && $sprid > 0) {
            $point = $pontval;
            dodb_point($mbrstr['id'], $sprstr, $point, $memo, $potype, $potoken);
        }
    }
}

function do_decodepinitem($mbrstr, $itemarr, $epinarr) {
    global $db, $cfgrow, $FORM;

    // add new transaction
    $data = array(
        'txdatetm' => $cfgrow['datetimestr'],
        'txfromid' => $mbrstr['id'],
        'txamount' => $itemarr['itprice'],
        'txmemo' => 'Order ' . $itemarr['itname'],
        'txppid' => $mbrstr['mppid'],
        'txitid' => $epinarr['epvalue'],
        'txtoken' => "|TXTYPE:ORDER|, |STORE:{$itemarr['itsku']}|",
    );
    $db->insert(DB_TBLPREFIX . '_transactions', $data);
    $txidstr = $db->lastInsertId();

    // add new sales
    include_once('../common/sendbox.php');
    $FORM['sb_type'] = 'payreg';
    $addtoken = '';
    $txitid = "{$txidstr}-{$epinarr['epvalue']}";
    doipnbox($txitid, $itemarr['itprice'], 'epin', $epinarr['epcode'], '', 'continue', 0, $addtoken);

    // update epin status to already used (activated)
    $data = array(
        'epstatus' => '2',
        'epusedid' => $mbrstr['id'],
        'epusedtm' => $cfgrow['datetimestr'],
    );
    $update = $db->update(DB_TBLPREFIX . '_epins', $data, array('epid' => $epinarr['epid']));
}

function do_decodepinplan($mbrstr, $epinarr) {
    global $db, $cfgrow, $bpparr, $FORM;

    // check if member already registered or not
    $newmppid = $epinarr['epvalue'];

    // if not register yet: register and approve
    $ismbrarr = getmbrinfo($mbrstr['id'], '', '', $newmppid);
    if ($ismbrarr['mpid'] < 1) {
        $resultarr = regmbrplans($mbrstr, $mbrstr['idref'], $newmppid);
        $txid = $resultarr['txid'];
        $mpid = $resultarr['mpid'];
    } else {
        $condition = " AND txfromid = '{$ismbrarr['id']}' AND txppid = '{$newmppid}' AND txstatus = '0' AND (txtoken LIKE '%|REG:{$ismbrarr['mpid']}|%' OR txtoken LIKE '%|RENEW:{$ismbrarr['mpid']}|%')";
        $txRow = $db->getAllRecords(DB_TBLPREFIX . '_transactions', '*', $condition);
        $txid = $txRow[0]['txid'];
        $mpid = $ismbrarr['mpid'];
    }

    $mpidact = '';

    // if registered but not active: approve
    if ($ismbrarr['mpstatus'] < 1) {
        $mpidact = $mpid;
    }

    // if registered and active: renew
    if ($ismbrarr['mpstatus'] == 1 && $ismbrarr['reg_expd'] > $ismbrarr['reg_date ']) {
        $FORM['sb_subs'] = 'renew';
        $mpidact = $mpid;
    }
    // if registered but expire: renew and reactivate
    if ($ismbrarr['mpstatus'] == 2 && $ismbrarr['reg_expd'] > $ismbrarr['reg_date ']) {
        $FORM['sb_subs'] = 'renew';
        $mpidact = $mpid;
    }

    // do!process here...
    if ($mpidact > 0) {
        $initbatch = 'CD';
        $txbatch = $initbatch . $epinarr['epid'] . "-" . $epinarr['epcode'];
        $payamount = $bpparr[$newmppid]['regfee'];

        include_once('sandbox.php');
        $FORM['sb_type'] = 'payreg';
        $FORM['sb_label'] = 'epin';
        $FORM['sb_txtokenarr'] = ['EPID' => $epinarr['epid']];
        $txmpid = $txid . '-' . $mpidact;
        doipnbox($txmpid, $payamount, 'epin', $txbatch, '-HTTPREF-', 'continue', 0, $epinarr['epcode']);

        // check latest member status
        $nowmbrarr = getmbrinfo('', '', $mpidact);
        $mpstatus = $nowmbrarr['mpstatus'];

        // remove unpaid transaction id from registration or renewal
        $condition = " AND (txtoken LIKE '%|REG:%' OR txtoken LIKE '%|RENEW:%')";
        $db->doQueryStr("DELETE FROM " . DB_TBLPREFIX . "_transactions WHERE txfromid = '{$nowmbrarr['idmbr']}' AND txstatus = '0'" . $condition);

        // remove unpaid existing membership
        $condition = " AND idmbr = '{$nowmbrarr['idmbr']}' AND mpstatus = '0'";
        $db->doQueryStr("DELETE FROM " . DB_TBLPREFIX . "_mbrplans WHERE 1" . $condition);

        // update epin status to already used (activated)
        if (($ismbrarr['mpstatus'] != '1' || $FORM['sb_subs'] = 'renew') && $mpstatus == '1') {
            $data = array(
                'epstatus' => '2',
                'epusedid' => $nowmbrarr['id'],
                'epusedtm' => $cfgrow['datetimestr'],
            );
            $update = $db->update(DB_TBLPREFIX . '_epins', $data, array('epid' => $epinarr['epid']));
        }
    }

    if ($update) {
        // webhook
        $datalistarr['status'] = $mpstatus;
        do_mbrwebhook($nowmbrarr, $datalistarr);
    }
}

function do_intvcmpool($limitcheck = 99) {
    global $db, $cfgrow, $LANG;

    $isprocessed = 0;
    $nowDate = $cfgrow['datetimestr'];
    $cmpoolondate = get_optionvals($cfgrow['cfgtoken'], 'cmpoolondate');

    $condition = "AND rkstatus = '1' AND rkiscmpl = '1'";
    $rankData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_ranks WHERE 1 " . $condition . "");

    // process eligible member for commission when the commission pool balance is available
    if (count($rankData) > 0 && $cfgrow['poolwlet'] > 0 && $cfgrow['datestr'] > $cmpoolondate) {
        foreach ($rankData as $rankrow) {

            $rktokenarr = get_optionvals($rankrow['rktoken']);
            $rkcmplmin = $rktokenarr['rkcmplmin'];

            $rkcmplout = $rankrow['rkcmplout'];
            $iscmpoolnow = ($rkcmplout == date("w") || $rkcmplout == date("d")) ? 1 : '';

            if ($iscmpoolnow == '1') {
                $cmpoolpermbr = floatval(get_optionvals($cfgrow['cfgtoken'], 'cmpoolpermbr'));
                $cmpoolhasher = get_optionvals($cfgrow['cfgtoken'], 'cmpoolhasher');
                $cmpoolmath = get_optionvals($cfgrow['cfgtoken'], 'cmpoolmath');

                if ($cmpoolpermbr <= 0) {
                    // get commission pool balance
                    $avalcmpool = $cfgrow['poolwlet'];

                    // get total eligible recipient
                    $condition = "AND mbrstatus = '1' AND mpstatus = '1' AND mprankid = '{$rankrow['rkid']}'";
                    $eligData = $db->getRecFrmQry("SELECT id FROM " . DB_TBLPREFIX . "_mbrs LEFT JOIN " . DB_TBLPREFIX . "_mbrplans ON id = idmbr WHERE 1 " . $condition . " GROUP BY idmbr");
                    $toteligmbr = COUNT($eligData);

                    // get commission interval amount
                    $totcmpool = getamount($rankrow['rkcmplval'], $avalcmpool);
                    $cmpoolperval = $totcmpool / $toteligmbr;
                    $cmpoolpermbr = sprintf('%0.2f', $cmpoolperval);
                    $cmpoolhasher = md5($cfgrow['datestr'] . $rankrow['rkid'] . $cmpoolpermbr);
                    $cmpoolmath = "{$avalcmpool}*{$rankrow['rkcmplval']}/{$toteligmbr}";

                    // save to current cmpoolpayout
                    $cfgtoken = $cfgrow['cfgtoken'];
                    $cfgtoken = put_optionvals($cfgtoken, 'cmpoolpermbr', $cmpoolpermbr);
                    $cfgtoken = put_optionvals($cfgtoken, 'cmpoolhasher', $cmpoolhasher);
                    $cfgtoken = put_optionvals($cfgtoken, 'cmpoolmath', $cmpoolmath);
                    $data = array(
                        'cfgtoken' => $cfgtoken,
                    );
                    $db->update(DB_TBLPREFIX . '_configs', $data, array('cfgid' => '1'));
                }

                // find eligible member that not paid yet this current interval
                $condition = "AND mbrstatus = '1' AND mpstatus = '1' AND txtoken NOT LIKE '%|cmpoolhash:|{$cmpoolhasher}%' AND mprankid = '{$rankrow['rkid']}' GROUP BY idmbr ORDER BY RAND() LIMIT {$limitcheck}";
                $userData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_mbrs LEFT JOIN " . DB_TBLPREFIX . "_mbrplans ON id = idmbr LEFT JOIN " . DB_TBLPREFIX . "_transactions ON id = txtoid WHERE 1 " . $condition . "");

                $didcmpoll = 0;
                // process eligible member for commission
                if (count($userData) > 0 && $rkcmplmin <= $cmpoolpermbr) {
                    foreach ($userData as $val) {
                        // interval commission pool
                        $intcmpoollist = array($val['mpid'] => $cmpoolpermbr);
                        $cmcountarr = addcmlist($LANG['g_poolcommission'], 'INTPOOL', $intcmpoollist, $mbrstr, $trxstr, "|cmpoolhash:{$cmpoolhasher}|, |cmpoolrkid:{$rankrow['rkid']}|, |cmpoolmath:{$cmpoolmath}|");

                        $isprocessed = $isprocessed + $cmcountarr['cmadded'];
                        $didcmpoll = $didcmpoll + $cmcountarr['cmadded'];
                    }
                }

                // deduct commission pool balance
                $cmpoolperdid = $cmpoolpermbr * $didcmpoll;
                $db->getRecFrmQry("UPDATE " . DB_TBLPREFIX . "_configs SET poolwlet = poolwlet - {$cmpoolperdid} WHERE cfgid = '1' ");

                // distribution complete, reset commission pool value and hash
                if ($isprocessed < 1) {
                    $cfgtoken = $cfgrow['cfgtoken'];
                    $cfgtoken = put_optionvals($cfgtoken, 'cmpoolpermbr', '');
                    $cfgtoken = put_optionvals($cfgtoken, 'cmpoolhasher', '');
                    $cfgtoken = put_optionvals($cfgtoken, 'cmpoolondate', $cfgrow['datestr']);
                    $data = array(
                        'cfgtoken' => $cfgtoken,
                    );
                    $db->update(DB_TBLPREFIX . '_configs', $data, array('cfgid' => '1'));
                }
            }
        }
    }
    return $isprocessed;
}

function do_pointrwdexch($mbrid, $prid) {
    global $db, $cfgrow, $prtypeopt_array, $LANG;

    $mbrstr = getmbrinfo($mbrid);
    // if member verified
    if ($mbrstr['mbrkycstatus'] == 1) {
        $prid = intval($prid);
        $pointrwstr = get_pointrwdinfo($prid);

        $mbrpoint = $mbrstr['epoint'];
        $prtype = $pointrwstr['prtype'];
        $prminpoints = $pointrwstr['prminpoints'];

        // if min points meets member point
        if ($prminpoints <= $mbrpoint) {
            $prvalarr = str_getcsv($pointrwstr['prval'] ?? '');
            $prvalstr = $prvalarr[0];
            $postatus = 1;

            if ($prtype == 'file') {
                $pointexctoken = "|exchtype:file|, |exchval:xchfl_{$prvalstr}|";
            } else if ($prtype == 'page') {
                $pointexctoken = "|exchtype:page|, |exchval:xchpg_{$prvalstr}|";
            } else if ($prtype == 'cash') {
                $oldmbrwallet = $mbrstr['ewallet'];

                $ewallet = $oldmbrwallet + $prvalstr;
                $data = array(
                    'ewallet' => $ewallet,
                );
                $update = $db->update(DB_TBLPREFIX . '_mbrs', $data, array('id' => $mbrstr['id']));
                // adjust wallet
                $newtrxid = adjusttrxwallet($oldmbrwallet, $ewallet, $mbrstr['id'], $LANG['g_convertpointcash'] . " (PV:{$prvalstr})");

                $pointexctoken = "|exchtype:cash|, |txidconvert:{$newtrxid}|, |exchval:{$prvalstr}|";
            } else if ($prtype == 'custom') {
                $prvalstr64 = base64_encode($prvalstr);
                $pointexctoken = "|exchtype:custom|, |exchval:{$prvalstr64}|";
                $postatus = 0;
            }
            // insert point history
            dodb_point(0, $mbrstr, -$prminpoints, "{$LANG['g_pointexchangeto']} {$prtypeopt_array[$prtype]}", '50', $pointexctoken, $postatus);
        }
    }
}

function get_rwdmbrlist($mbrstr, $rwdtype = 'file') {
    global $db;

    $listarr = [];
    $condition = " AND potoken LIKE '%|exchtype:{$rwdtype}|%'";
    $rowData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_points WHERE 1 AND pofromid = '{$mbrstr['id']}' AND postatus = '1' " . $condition . "");

    if (count($rowData) > 0) {
        foreach ($rowData as $val) {
            $potokenarr = get_optionvals($val['potoken']);
            if ($rwdtype == 'file') {
                $listarr[] = str_replace('xchfl_', '', $potokenarr['exchval']);
            } else if ($rwdtype == 'page') {
                $listarr[] = str_replace('xchpg_', '', $potokenarr['exchval']);
            }
        }
    }
    return $listarr;
}

function get_leaderboard($ppid = '', $bymonth = '') {
    global $db;

    $dlinertotarr = [];
    $condition = '';
    $condition .= ($bymonth != '') ? " AND reg_date LIKE '%{$bymonth}%' " : '';
    $condition .= ($ppid > 0) ? " AND mppid  = '{$ppid}' " : '';

    $condition .= " AND mpstatus = '1' ";
    $row = $db->getRecFrmQry("SELECT
	id, username, email, mbr_image,
	(SELECT SUM(CASE WHEN sprlist LIKE CONCAT('%:', id, '|%') THEN 1 ELSE 0 END)
FROM " . DB_TBLPREFIX . "_mbrplans WHERE 1 " . $condition . ") AS mytotdl
FROM " . DB_TBLPREFIX . "_mbrs
GROUP BY id ORDER BY mytotdl DESC, id ASC LIMIT 10");

    foreach ($row as $value) {
        $dlinertotarr[$value['id']]['username'] = $value['username'];
        $dlinertotarr[$value['id']]['mbr_image'] = $value['mbr_image'];
        $dlinertotarr[$value['id']]['email'] = $value['email'];
        $dlinertotarr[$value['id']]['dltotal'] = intval($value['mytotdl']);
    }
    return $dlinertotarr;
}

function get_incomeboard($ppid = '', $bymonth = '') {
    global $db;

    $incomertotarr = [];
    $condition = ($bymonth != '') ? " AND txdatetm LIKE '%{$bymonth}%' " : '';
    $condition .= ($ppid > 0) ? " AND txppid  = '{$ppid}' " : '';

    $condition .= " AND txfromid = '0' AND txstatus = '1' AND txstatus = '1' ";
    $row = $db->getRecFrmQry("SELECT id, username, email, mbr_image, (SELECT SUM(CASE WHEN (txtoken LIKE '%|LCM:%' OR txtoken LIKE '%|WALT:IN|%') THEN txamount ELSE 0 END) FROM " . DB_TBLPREFIX . "_transactions WHERE txtoid = id " . $condition . ") as mytoticm FROM " . DB_TBLPREFIX . "_mbrs WHERE 1 GROUP BY id ORDER BY mytoticm DESC, id ASC LIMIT 10");

    foreach ($row as $value) {
        $incomertotarr[$value['id']]['username'] = $value['username'];
        $incomertotarr[$value['id']]['mbr_image'] = $value['mbr_image'];
        $incomertotarr[$value['id']]['email'] = $value['email'];
        $incomertotarr[$value['id']]['earntot'] = floatval($value['mytoticm']);
    }
    return $incomertotarr;
}

function get_toprefnow($idref, $mpid = '') {
    global $db, $bpprow, $cfgtoken;

    $refarr = getmbrinfo($idref, 'id', $mpid);
    $mppid = ($refarr['mppid'] > 0) ? $refarr['mppid'] : 1;

    if ($cfgtoken['isonetopref'] == '1') {
        $condition = " AND mppid = '{$mppid}'";
        $rowData = $db->getRecFrmQry("SELECT mpid FROM " . DB_TBLPREFIX . "_mbrplans WHERE 1 AND mpstatus = '1' " . $condition . " ORDER BY reg_utctime ASC, mpid ASC, recyclingit ASC LIMIT 1");
        $mpid = $rowData[0]['mpid'];
        $refarr = getmbrinfo('', '', $mpid);
    }

    return $refarr;
}
