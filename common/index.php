<?php

include_once('init.loader.php');

// Reset Member
if ($FORM['prkey'] != '') {
    $seskey = base64_decode($FORM['prkey'] ?? '');
    $_SESSION['pr_key'] = $seskey;
    redirpageto('reset-password.php?f=' . $FORM['f']);
    exit;
}

// Ajs Get Value
if ($FORM['agv'] != '') {

    $agvarr = explode('-', $FORM['agv'], 2);
    $key = $agvarr[0];
    $value = mystriptag($agvarr[1]);

    // reserved username
    $isunexist = is_unamereserved($value);

    if ($key == 'un2i' || $key == 'unex' || $key == 'unexrev') {
        // username to member info
        $condition = ' AND username = "' . $value . '"';
    } elseif ($key == 'id2i') {
        // id to member info
        $condition = ' AND id = "' . $value . '"';
    } elseif ($key == 'em2i') {
        // email to member info
        $condition = ' AND email = "' . $value . '"';
    } elseif ($key == 'id2itname') {
        // item details
        $itemStr = get_iteminfo($value);
        if (($FORM['nodatatitle'] != '' || $FORM['nodatainfo'] != '') && $itemStr['itname'] == '') {
            echo "<div class='text-primary nogapline'><strong>{$FORM['nodatatitle']}</strong><br /><span class='text-danger text-small'>{$FORM['nodatainfo']}</span></div>";
        } else {
            echo "<div class='text-primary nogapline'><strong>{$itemStr['itname']}</strong><br /><span class='text-info text-small'>{$itemStr['itsalesnote']}</span></div>";
        }
    } elseif ($key == 'id2progname') {
        // payplan details
        $planStr = $bpparr[$value];
        if (($FORM['nodatatitle'] != '' || $FORM['nodatainfo'] != '') && $planStr['ppname'] == '') {
            $FORM['nodatainfo'] = ($value == $FORM['defval'] && $FORM['devnote'] != '') ? $FORM['devnote'] : $FORM['nodatainfo'];
            echo "<div class='text-primary nogapline'><strong>{$FORM['nodatatitle']}</strong><br /><span class='text-danger text-small'>{$FORM['nodatainfo']}</span></div>";
        } else {
            echo "<div class='text-primary nogapline'><strong>{$planStr['ppname']}</strong> ({$bpprow['currencysym']}{$planStr['regfee']})<br /><span class='text-info text-small'>{$planStr['planinfo']}</span></div>";
        }
    } else {
        
    }

    if ($condition != '') {
        // username is exist?
        $row = $db->getAllRecords(DB_TBLPREFIX . '_mbrs', '*', $condition . " LIMIT 1");
        $mbrRow = array();
        foreach ($row as $value) {
            $mbrRow = array_merge($mbrRow, $value);
        }
        $row = $db->getAllRecords(DB_TBLPREFIX . '_mbrplans', '*', ' AND idmbr = "' . $mbrRow['id'] . '"');
        foreach ($row as $value) {
            $mbrRow = array_merge($mbrRow, $value);
        }

        if ($key == 'unex') {
            if ($mbrRow['id'] > 0 || $value == '' || $isunexist) {
                // if username NOT available
                echo "<i class='far fa-times-circle fa-fw text-danger'></i>";
            } else {
                // otherwise
                echo "<i class='far fa-check-circle fa-fw text-success'></i>";
            }
        } else if ($key == 'unexrev') {
            if ($mbrRow['id'] > 0 && $mbrRow['mpstatus'] == 1 && $value != '') {
                // if username exist
                echo "<i class='far fa-check-circle fa-fw text-success'></i>";
            } else {
                // otherwise
                echo "<i class='far fa-times-circle fa-fw text-danger'></i>";
            }
        } else {
            if ($mbrRow['id'] > 0) {
                // member status
                if ($mbrRow['mbrstatus'] != '1') {
                    $arrstatus = array(0 => 'Inactive', 1 => 'Active', 2 => 'Limited', 3 => 'Pending');
                    $arrstr = $arrstatus[$mbrRow['mbrstatus']];
                    $icostatus = " <span class='badge badge-danger text-small float-right'>{$arrstr}</span>";
                } else if ($mbrRow['mpstatus'] != '1') {
                    $arrstatus = array(0 => 'Inactive', 1 => 'Active', 2 => 'Expired', 3 => 'Pending');
                    $arrstr = $arrstatus[$mbrRow['mpstatus']];
                    $icostatus = " <span class='badge badge-warning text-small float-right'>{$arrstr}</span>";
                } else {
                    $icostatus = '';
                }
                echo myvalidate($icostatus) . "<div class='text-primary nogapline'><strong>{$mbrRow['username']}</strong><br /><span class='text-info text-small'>{$mbrRow['firstname']} {$mbrRow['lastname']} ({$mbrRow['email']})</span></div>";
            } else {
                if (($FORM['nodatatitle'] != '' || $FORM['nodatainfo'] != '') && $mbrRow['username'] == '') {
                    $deftitle = $FORM['nodatatitle'];
                    $defnote = ($FORM['defval'] == $value) ? "<span class='text-success text-small'>{$FORM['devnote']}</span>" : "<span class='text-danger text-small'>{$FORM['nodatainfo']}</span>";
                    echo "<div class='text-primary nogapline'><strong>{$deftitle}</strong><br />{$defnote}</div>";
                } else {
                    echo "<div class='text-primary nogapline'><strong>{$cfgtoken['admin_subname']}</strong><br /><span class='text-info text-small'>- not a member</span></div>";
                }
            }
        }
    }

    exit;
}

// Ajs Get Area Value
if ($FORM['cca'] != '') {
    $value = mystriptag($FORM['cca']);
    echo '+' . $countryarea_array[$value]['code'];
    exit;
}
