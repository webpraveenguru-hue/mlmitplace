<?php
if (!defined('OK_LOADME')) {
    die('o o p s !');
}

$FORM = do_eskep($FORM);

if ($FORM['dohal'] == 'clear') {
    $_SESSION['filterid'] = $_SESSION['filtermpid'] = '';
    redirpageto('index.php?hal=historylist');
    exit;
}
if ($FORM['dohal'] == 'filter' && $FORM['doval']) {
    $_SESSION['filterid'] = $FORM['doval'];
    $_SESSION['filtermpid'] = $FORM['dompid'];
}

if ($FORM['dohal'] == 'proof') {
    $_SESSION['payproof'] = ($_SESSION['payproof'] == '') ? '1' : '';
    redirpageto('index.php?hal=historylist');
    exit;
}

$condition = '';

if ($_SESSION['payproof'] == '1') {
    $condition .= "AND txstatus != '1' AND txtoken LIKE '%|fbacktype:9|%' ";
    $filterproofonly = 'Display All';
    $btnclorproof = 'btn-danger';
} else {
    $filterproofonly = 'Payment Confirmation';
    $btnclorproof = 'btn-info';
}

if (isset($FORM['txbatch']) && $FORM['txbatch'] != "") {
    $condition .= " AND txbatch LIKE '%{$FORM['txbatch']}%' ";
}
if (isset($FORM['txmemo']) && $FORM['txmemo'] != "") {
    $condition .= " AND txmemo LIKE '%{$FORM['txmemo']}%' ";
}
if (isset($FORM['txadminfo']) && $FORM['txadminfo'] != "") {
    $condition .= " AND (txtoken LIKE '%{$FORM['txadminfo']}%' OR txadminfo LIKE '%{$FORM['txadminfo']}%') ";
}

if ($_SESSION['filterid']) {
    $filterid = intval($_SESSION['filterid']);
    $condition .= " AND (txfromid = '$filterid' OR txtoid = '$filterid') ";
    $btnclorclear = 'btn-danger';
    $filterusrstr = getmbrinfo($filterid);
    $clearfilterusrstr = " filter for member ({$filterusrstr['username']})";
    $filtermbrlink = "index.php?hal=getuser&getId={$filterusrstr['id']}";
    $filterusrnow = "<div class='text-right ml-2'><a href='javascript:;' onclick=\"location.href = '{$filtermbrlink}'\" class='btn btn-sm btn-outline-warning'>" . strtoupper($filterusrstr['username']) . "</a></div>";
} else {
    $btnclorclear = 'btn-warning';
    $clearfilterusrstr = $filterusrnow = "";
}


$tblshort_arr = array("txdatetm", "txbatch", "txmemo", "txamount", "txtmstamp");
$tblshort = dborder_arr($tblshort_arr, $FORM['_stbel'], $FORM['_stype']);
if ($FORM['_stbel'] != '' && (in_array($FORM['_stbel'], $tblshort_arr))) {
    $sqlshort = ($FORM['_stype'] == 'up') ? " ORDER BY {$FORM['_stbel']} DESC, txid DESC " : " ORDER BY {$FORM['_stbel']} ASC, txid DESC ";
} else {
    $sqlshort = " ORDER BY txdatetm DESC, txid DESC ";
}

//Main queries
$sql = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_transactions WHERE 1 " . $condition . "");
$pages->items_total = count($sql);
$pages->mid_range = 3;
$pages->paginate();

$transacData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_transactions WHERE 1 " . $condition . $sqlshort . $pages->limit . "");
?>

<div class="section-header">
    <h1><i class="fa fa-fw fa-cash-register"></i> <?php echo myvalidate($LANG['g_historylist']); ?></h1>
    <?php echo myvalidate($filterusrnow); ?>
</div>

<div class="section-body">

    <form method="get">
        <div class="card card-primary">
            <div class="card-header">
                <h4>
                    <i class="fa fa-fw fa-search"></i> <?php echo myvalidate($LANG['g_findhistory']); ?>
                </h4>
            </div>
            <div class="card-body">
                <div class="row">

                    <div class="col-sm-4">
                        <div class="form-group">
                            <label><?php echo myvalidate($LANG['g_transactionid']); ?></label>
                            <input type="text" name="txbatch" id="txbatch" class="form-control" value="<?php echo isset($FORM['txbatch']) ? $FORM['txbatch'] : '' ?>" placeholder="Transaction ID">
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <div class="form-group">
                            <label><?php echo myvalidate($LANG['g_description']); ?></label>
                            <input type="text" name="txmemo" id="txmemo" class="form-control" value="<?php echo isset($FORM['txmemo']) ? $FORM['txmemo'] : '' ?>" placeholder="Transaction description">
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <div class="form-group">
                            <label><?php echo myvalidate($LANG['g_keyword']); ?></label>
                            <input type="txadminfo" name="txadminfo" id="txadminfo" class="form-control" value="<?php echo isset($FORM['txadminfo']) ? $FORM['txadminfo'] : '' ?>" placeholder="Enter transaction keyword">
                        </div>
                    </div>

                </div>
            </div>
            <div class="card-footer bg-whitesmoke">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="float-md-right">
                            <?php
                            if ($payrow['manualpayon'] == '1') {
                                ?>
                                <a href="index.php?hal=historylist&dohal=proof" class="btn <?php echo myvalidate($btnclorproof); ?>"><i class="fa fa-fw fa-receipt"></i> <?php echo myvalidate($filterproofonly); ?></a>
                                <?php
                            }
                            ?>
                            <a href="index.php?hal=historylist&dohal=clear" class="btn <?php echo myvalidate($btnclorclear); ?>"><i class="fa fa-fw fa-redo"></i> Clear<?php echo myvalidate($clearfilterusrstr); ?></a>
                            <button type="submit" name="submit" value="search" id="submit" class="btn btn-primary"><i class="fa fa-fw fa-search"></i> Search</button>
                        </div>
                        <div class="d-block d-sm-none">
                            &nbsp;
                        </div>

                    </div>
                </div>
            </div>
            <div class="clearfix"></div>
        </div>
        <input type="hidden" name="hal" value="historylist">
    </form>

    <hr>

    <div class="clearfix"></div>

    <div class="row marginTop">
        <div class="col-sm-12 paddingLeft pagerfwt">
            <?php if ($pages->items_total > 0) { ?>
                <div class="row">
                    <div class="col-md-7">
                        <?php echo myvalidate($pages->display_pages()); ?>
                    </div>
                    <div class="col-md-5 text-right">
                        <span class="d-none d-md-block">
                            <?php echo myvalidate($pages->display_items_per_page()); ?>
                            <?php echo myvalidate($pages->display_jump_menu()); ?>
                            <?php echo myvalidate($pages->items_total()); ?>
                        </span>
                    </div>
                <?php } ?>
            </div>
            <div class="clearfix"></div>
        </div>
        <div class="clearfix"></div>
    </div>

    <div class="clearfix"></div>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col" nowrap><?php echo myvalidate($tblshort['txdatetm']); ?>Date</th>
                    <th scope="col" nowrap><?php echo myvalidate($tblshort['txbatch']); ?>Transaction ID</th>
                    <th scope="col" nowrap><?php echo myvalidate($tblshort['txmemo']); ?>Description</th>
                    <th scope="col" nowrap><?php echo myvalidate($tblshort['txamount']); ?>Amount</th>
                    <th scope="col" class="text-center"><?php echo myvalidate($tblshort['txtmstamp']); ?>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (count($transacData) > 0) {
                    $s = $pages->get_disponpage($FORM);
                    foreach ($transacData as $val) {
                        $s++;
                        $hasdel = md5($val['txid'] . date("dH"));
                        $mbrfromstr = getmbrinfo($val['txfromid']);

                        if ($val['txstatus'] == 2) {
                            $bletmark = '<span class="bullet text-warning" data-toggle="tooltip" title="Onhold"></span>';
                        } elseif ($val['txstatus'] == 3) {
                            $bletmark = '<span class="bullet text-light" data-toggle="tooltip" title="Cancel"></span>';
                        } else {
                            $statusttip = ($val['txstatus'] == 1) ? 'Paid' : 'Unpaid';
                            if ($val['txfromid'] == 0) {
                                $statusclor = ($val['txstatus'] == 1) ? 'text-danger' : 'text-secondary';
                                $bletmark = '<i class="fa fa-caret-down fa-fw ' . $statusclor . '" data-toggle="tooltip" title="' . $statusttip . '"></i>';
                            } elseif ($val['txtoid'] == 0) {
                                $statusclor = ($val['txstatus'] == 1) ? 'text-success' : 'text-secondary';
                                $bletmark = '<i class="fa fa-caret-up fa-fw ' . $statusclor . '" data-toggle="tooltip" title="' . $statusttip . '"></i>';
                            } else {
                                $statusclor = ($val['txstatus'] == 1) ? 'text-info' : 'text-muted';
                                $bletmark = '<span class="bullet ' . $statusclor . '" data-toggle="tooltip" title="' . $statusttip . '"></span>';
                            }
                        }

                        $payfrom = getusernameid($val['txfromid'], 'username');
                        $payto = getusernameid($val['txtoid'], 'username');

                        $txtoken = get_optionvals($val['txtoken']);
                        $txrefid = intval($txtoken['SRCIDMBR']);
                        $txreftxbatch = trim($txtoken['SRCTXID'] ?? '');
                        $refuname = ($txrefid > 0 && $txrefid != $val['txfromid']) ? "<div class='text-small'>" . $LANG['g_reference'] . ': <strong>' . getusernameid($txrefid, 'username') . "</strong> #{$txreftxbatch}</div>" : '';
                        $txbatchstr = (strlen($val['txbatch']) > 24) ? substr($val['txbatch'], 0, 22) . '...' : $val['txbatch'];

                        $sb_label = $avalpaymentopt_array[$txtoken['sb_label']];
                        $refuname .= ($sb_label != '') ? "<div class='text-small text-muted mt-2'>{$sb_label}</div>" : '';
                        $manpaytxid = base64_decode($txtoken['manpaytxid'] ?? '');
                        $refuname .= ($txtoken['manpaytxid'] != '') ? "<div class='text-info'>{$manpaytxid}</div>" : '';

                        if (strpos($val['txtoken'] ?? '', '|NOTE:') !== false) {
                            $notestr = base64_decode($txtoken['NOTE'] ?? '');
                            $txmemostr = "<span class='text-info'>{$notestr}</span>";
                        } elseif (strpos($val['txtoken'] ?? '', '|WALT:IN|') !== false) {
                            $txmemostr = "<span class='text-danger'>{$LANG['g_walletcredit']}</span>";
                        } elseif (strpos($val['txtoken'] ?? '', '|WALT:OUT|') !== false) {
                            $txmemostr = "<span class='text-success'>{$LANG['g_walletdebit']}</span>";
                        } elseif (strpos($val['txtoken'] ?? '', '|WIDR:') !== false) {
                            $txmemostr = "<span class='text-warning'>{$LANG['g_withdrawreq']}</span>";
                        } else {
                            $txmemostr = '';
                        }

                        if ($val['txitid'] > 0 && $txtoken['TXTYPE'] == 'ORDER') {
                            $datastore = $val['txid'] . '-' . $val['txitid'];
                            $dataid = $val['txamount'];
                            $datampid = strtoupper(date("DmdH-is-")) . $datastore;
                            $datalink = '';
                        } else {
                            $datastore = '';
                            $dataid = $val['txfromid'];
                            $datampid = $mbrfromstr['mpid'];
                            $datalink = 'getuser';
                        }

                        $proofimgstr = ($txtoken['proofimg'] != '' || ($txtoken['fbacktype'] == '9' && $val['txstatus'] != 1)) ? '<a href="javascript:;" data-img="' . $txtoken['proofimg'] . '" data-link="' . $datalink . '" data-store="' . $datastore . '" data-id="' . $dataid . '" data-mpid="' . $datampid . '" data-poptitle="Proof of Payment: ' . $payfrom . '" class="openPopup text-info" data-toggle="tooltip" title="Proof of Payment: ' . $payfrom . '"><i class="fa fa-receipt fa-fw"></i></a>' : '';

                        $overview = "<label>Info</label><div>" . $val['adminfo'] . "</div>";
                        ?>
                        <tr>

                            <th scope="row"><?php echo myvalidate($s); ?></th>
                            <td data-toggle="tooltip" title="<?php echo myvalidate($val['txdatetm']); ?>"><?php echo formatdate($val['txdatetm']); ?></td>
                            <td><?php echo ($val['txbatch']) ? myvalidate($txbatchstr) : '-'; ?></td>
                            <td><?php echo myvalidate($val['txmemo'] . ' ' . $proofimgstr); ?></td>
                            <td class="text-right" nowrap><?php echo myvalidate($val['txamount'] . $bletmark); ?></td>
                            <td align="center" nowrap>
                                <a href="javascript:;"
                                   class="btn btn-sm btn-secondary"
                                   data-html="true"
                                   data-toggle="popover"
                                   data-trigger="hover"
                                   data-placement="left" 
                                   title="#<?php echo myvalidate($val['txid'] . '. ' . $val['txbatch']); ?>"
                                   data-content="<div class='text-small text-muted'><?php echo myvalidate($bpparr[$val['txppid']]['ppname']); ?></div><h6><?php echo myvalidate($val['txtmstamp']); ?></h6><div>From: <?php echo myvalidate($payfrom); ?></div><div>To: <?php echo myvalidate($payto); ?></div><?php echo myvalidate($refuname); ?><div class='mt-2 text-small'><?php echo myvalidate($txmemostr); ?></div><div class='mt-2'><?php echo myvalidate($val['txadminfo']); ?></div>">
                                    <i class="far fa-fw fa-question-circle"></i>
                                </a>
                                <a href="javascript:;" data-href="dohistory.php?editId=<?php echo myvalidate($val['txid']); ?>&redir=historylist&mdlhasher=<?php echo myvalidate($mdlhasher); ?>" data-poptitle="<i class='fa fa-fw fa-edit'></i> Update Transaction History #<?php echo myvalidate($val['txid']); ?>" class="btn btn-sm btn-success openPopup" data-toggle="tooltip" title="Update <?php echo myvalidate($val['txbatch']); ?>"><i class="fa fa-fw fa-edit"></i></a>

                                <?php
                                if (strpos($val['txtoken'] ?? '', '|WDRTX') !== false) {
                                    ?>
                                    <a class="btn btn-sm btn-light"><i class="far fa-fw fa-trash-alt"></i></a>
                                    <?php
                                } else {
                                    ?>
                                    <a href="javascript:;" data-href="dohistory.php?hash=<?php echo myvalidate($hasdel); ?>&delId=<?php echo myvalidate($val['txid']); ?>&redir=historylist&mdlhasher=<?php echo myvalidate($mdlhasher); ?>" class="btn btn-sm btn-danger bootboxconfirm" data-poptitle="Transaction ID: <?php echo myvalidate($val['txid']) . '-' . myvalidate($val['txbatch']); ?>" data-popmsg="Are you sure want to delete this transaction history?" data-toggle="tooltip" title="Delete <?php echo myvalidate($val['txbatch']); ?>"><i class="far fa-fw fa-trash-alt"></i></a>
                                    <?php
                                }
                                ?>
                            </td>

                        </tr>
                        <?php
                    }
                } else {
                    ?>
                    <tr>
                        <td colspan="6">
                            <div class="text-center mt-4 text-muted">
                                <div>
                                    <i class="fa fa-3x fa-question-circle"></i>
                                </div>
                                <div><?php echo myvalidate($LANG['g_norecordinfo']); ?></div>
                            </div>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="clearfix"></div>

    <div class="row marginTop">
        <div class="col-sm-12 paddingLeft pagerfwt">
            <?php if ($pages->items_total > 0) { ?>
                <div class="row">
                    <div class="col-md-7">
                        <?php echo myvalidate($pages->display_pages()); ?>
                    </div>
                    <div class="col-md-5 text-right">
                        <span class="d-none d-md-block">
                            <?php echo myvalidate($pages->display_items_per_page()); ?>
                            <?php echo myvalidate($pages->display_jump_menu()); ?>
                            <?php echo myvalidate($pages->items_total()); ?>
                        </span>
                    </div>
                <?php } ?>
            </div>
            <div class="clearfix"></div>
        </div>
        <div class="clearfix"></div>
    </div>

</div>
