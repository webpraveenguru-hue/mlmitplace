<?php
include_once('../common/init.loader.php');

$seskey = verifylog_sess('member');
if ($seskey == '') {
    die('o o p s !');
}
if ($mdlhashy != $FORM['mdlhashy']) {
    echo myvalidate($LANG['a_loadingmdlcnt']);
    redirpageto('index.php', 1);
    exit;
}

$sesRow = getlog_sess($seskey);
$mbrusername = get_optionvals($sesRow['sesdata'], 'un');

// Get referrer/member details
$mbrstr = getmbrinfo($mbrusername, 'username');

$slidhash = md5($FORM['slid'] . '/' . $mbrstr['id'] . $mdlhashy);
if ($slidhash != $FORM['slidhash']) {
    echo myvalidate($LANG['a_loadingmdlcnt']);
    redirpageto('index.php', 1);
    exit;
}

$_SESSION['redirto'] = redir_to($FORM['redir']);

$slid = intval($FORM['slid']);
$salesstr = get_salesinfo($slid);
$itemstr = get_iteminfo($salesstr['slitid'], $mbrstr);
$txstr = get_txinfo($salesstr['slbatch'], 'txbatch');
$gatefee = get_optionvals($txstr['txtoken'], 'addtxsfee');
$itemprice = $salesstr['slprice'] - $gatefee;
?>

<div class="row">
    <div class="col-md-12">

        <h5 class="text-muted"><?php echo myvalidate($itemstr['itname']); ?></h5>
        <div class="row">
            <div class="col-md-12">
                <div>Date: <strong><?php echo formatdate($salesstr['sldatetm']); ?></strong></div>
                <div>Transaction ID: <strong><?php echo myvalidate($salesstr['slbatch']); ?></strong></div>
                <div>Amount: <strong><?php echo myvalidate($bpprow['currencysym'] . $itemprice); ?></strong><code> + Payment service fee <?php echo myvalidate($bpprow['currencysym'] . $gatefee); ?></code></div>
            </div>
        </div>

        <div class=" mt-4 table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th scope="col">Transaction</th>
                        <th scope="col" nowrap>Description</th>
                        <th scope="col" class="text-right" nowrap>Amount (<?php echo myvalidate($bpprow['currencysym']); ?>)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $amounttot = 0;
                    $condition = " AND txtoken LIKE '%|SRCTXID:{$txstr['txid']}|%' ";
                    $txitmData = $db->getRecFrmQry("SELECT *, CASE WHEN txtoken LIKE '%|TXVENDOR:FEE|%' THEN 'Vendor Fee' ELSE '' END as txfeenote, CASE WHEN txtoken LIKE '%|TXVENDOR:EARN|%' THEN 'Vendor Earning' ELSE '' END as txearnnote FROM " . DB_TBLPREFIX . "_transactions LEFT JOIN " . DB_TBLPREFIX . "_items ON txitid = itid WHERE 1 " . $condition . " ORDER BY txid ASC" . "");
                    foreach ($txitmData as $val) {
                        $amounttot = $amounttot + $val['txamount'];
                        $txamountstr = ($val['txtoid'] == $mbrstr['id']) ? "<span class='text-success'>{$val['txamount']}</span>" : "<span class='text-danger'>{$val['txamount']}</span>";
                        ?>
                        <tr>
                            <th scope="row"><?php echo myvalidate($val['txbatch']); ?></th>
                            <td>
                                <?php echo ($val['txfeenote'] || $val['txearnnote']) ? "{$val['txfeenote']}{$val['txearnnote']}" : $val['txmemo']; ?>
                            </td>
                            <td class="text-right"><?php echo myvalidate($txamountstr); ?></td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="clearfix"></div>

        <div class="text-right">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        </div>
    </div>
</div>
