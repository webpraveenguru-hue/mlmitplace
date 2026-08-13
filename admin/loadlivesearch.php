<?php

include_once('../common/init.loader.php');

if (verifylog_sess('admin') == '') {
    die('o o p s !');
}

$findkey = mystriptag($FORM['findkey']);
$findontable = mystriptag($FORM['findontable']);

// member
if ($FORM['findwhat'] == 'mbr') {
    $condition = ($findontable != '') ? "AND {$findontable} LIKE '%{$findkey}%'" : " AND (firstname LIKE '%{$findkey}%' OR lastname LIKE '%{$findkey}%' OR username LIKE '%{$findkey}%' OR email LIKE '%{$findkey}%')";
    $userData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_mbrs WHERE 1 " . $condition . " ORDER BY RAND() LIMIT 9");

    echo "<option value='all'>Available for ALL";

    $isfound = count($userData);
    if ($isfound > 0) {
        foreach ($userData as $userarr) {
            $mbrimgval = ($userarr['mbr_image']) ? $userarr['mbr_image'] : $cfgrow['mbr_defaultimage'];
            $valuestr = ($findontable != '') ? $userarr[$findontable] : $userarr['id'];
            echo "<option value='" . $valuestr . "'>{$userarr['username']} ({$userarr['email']})";
        }
    } else {
        echo "<option value=''>No matches found";
    }
}

// plan
if ($FORM['findwhat'] == 'plan') {
    $condition = ($findontable != '') ? "AND {$findontable} LIKE '%{$findkey}%'" : " AND (ppid LIKE '%{$findkey}%' OR ppname LIKE '%{$findkey}%' OR planinfo LIKE '%{$findkey}%' OR plantoken LIKE '%{$findkey}%')";
    $planData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_payplans LEFT JOIN " . DB_TBLPREFIX . "_baseplan ON ppbpid = bpid WHERE 1 " . $condition . " ORDER BY RAND() LIMIT 9");

    $isfound = count($planData);
    if ($isfound > 0) {
        foreach ($planData as $planarr) {
            $planamount = ($planarr['regfee'] > 0) ? $bpprow['currencysym'] . $planarr['regfee'] : $LANG['g_free'];
            $valuestr = ($findontable != '') ? $planarr[$findontable] : $planarr['pid'];
            echo "<option value='" . $valuestr . "'>{$planarr['ppname']} ({$planamount})";
        }
    } else {
        echo "<option value=''>No matches found";
    }
}

// product
if ($FORM['findwhat'] == 'item') {
    $condition = ($findontable != '') ? "AND {$findontable} LIKE '%{$findkey}%'" : " AND (itid LIKE '%{$findkey}%' OR itsku LIKE '%{$findkey}%' OR itname LIKE '%{$findkey}%' OR itsalesnote LIKE '%{$findkey}%' OR itkeywords LIKE '%{$findkey}%')";
    $itemData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_items LEFT JOIN " . DB_TBLPREFIX . "_groups ON itgrid = grid WHERE 1 " . $condition . " ORDER BY RAND() LIMIT 9");

    $isfound = count($itemData);
    if ($isfound > 0) {
        foreach ($itemData as $itemarr) {
            $itemamount = ($itemarr['itprice'] > 0) ? $bpprow['currencysym'] . $itemarr['itprice'] : $LANG['g_free'];
            $amountstr = ($itemarr['itid'] == 1) ? $LANG['g_default'] . ' ' . $itemamount : $itemamount;
            $itemsku = ($itemarr['itsku'] != '') ? ' - ' . $itemarr['itsku'] : '';
            $itemgrup = ($itemarr['grtitle'] != '') ? ' - ' . $itemarr['grtitle'] : '';
            $valuestr = ($findontable != '') ? $itemarr[$findontable] : $itemarr['itid'];
            echo "<option value='" . $valuestr . "'>{$itemarr['itname']} ({$amountstr}){$itemsku}{$itemgrup}";
        }
    } else {
        echo "<option value=''>No matches found";
    }
}
