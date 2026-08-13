<?php
if (!defined('OK_LOADME')) {
    die('o o p s !');
}

if ($stcrow['stcvendoron'] != 1 || $mbrstr['isvendor'] != 1) {
    $hal = 'dashboard';
    redirpageto('index.php?hal=' . $hal);
    die();
}

$FORM = do_eskep($FORM);

if ($FORM['dohal'] == 'clear') {
    redirpageto('index.php?hal=mysaleslist');
    exit;
}

$slstatuscaparr = array(0 => "Unpaid", 1 => "Paid", 2 => "OnHold", 3 => "Expired", 4 => "Cancel");

$condition = " AND itidmbr = '{$mbrstr['id']}'";

if (isset($FORM['slbatch']) && $FORM['slbatch'] != "") {
    $condition .= " AND (slbatch LIKE '%{$FORM['slbatch']}%' OR slorderid LIKE '%{$FORM['slbatch']}%' OR sltoken LIKE '%{$FORM['slbatch']}%') ";
}
if (isset($FORM['slnote']) && $FORM['slnote'] != "") {
    $condition .= " AND slnote LIKE '%{$FORM['slnote']}%' ";
}
if (isset($FORM['sltoken']) && $FORM['sltoken'] != "") {
    $condition .= " AND (sltoken LIKE '%{$FORM['sltoken']}%' OR sladminfo LIKE '%{$FORM['sltoken']}%') ";
}

$tblshort_arr = array("sldatetm", "slbatch", "slitid", "slprice");
$tblshort = dborder_arr($tblshort_arr, $FORM['_stbel'], $FORM['_stype']);
if ($FORM['_stbel'] != '' && (in_array($FORM['_stbel'], $tblshort_arr))) {
    $sqlshort = ($FORM['_stype'] == 'up') ? " ORDER BY {$FORM['_stbel']} DESC " : " ORDER BY {$FORM['_stbel']} ASC ";
} else {
    $sqlshort = " ORDER BY slid DESC ";
}

//Main queries
$sql = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_sales LEFT JOIN " . DB_TBLPREFIX . "_items ON slitid = itid WHERE 1 " . $condition . "");
$pages->items_total = count($sql);
$pages->mid_range = 3;
$pages->paginate();

$salesData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_sales LEFT JOIN " . DB_TBLPREFIX . "_items ON slitid = itid WHERE 1 " . $condition . $sqlshort . $pages->limit . "");
?>

<div class="section-header">
    <h1><i class="fa fa-fw fa-store-alt"></i> <?php echo myvalidate($LANG['m_saleslist']); ?></h1>
</div>

<div class="section-body">

    <form method="get">
        <div class="card card-primary">
            <div class="card-header">
                <h4>
                    <i class="fa fa-fw fa-search"></i> <?php echo myvalidate($LANG['a_findsales']); ?>
                </h4>
            </div>
            <div class="card-body">
                <div class="row">

                    <div class="col-sm-4">
                        <div class="form-group">
                            <label><?php echo myvalidate($LANG['g_transactionid']); ?></label>
                            <input type="text" name="slbatch" id="slbatch" class="form-control" value="<?php echo isset($FORM['slbatch']) ? $FORM['slbatch'] : '' ?>" placeholder="Transaction ID">
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <div class="form-group">
                            <label><?php echo myvalidate($LANG['g_description']); ?></label>
                            <input type="text" name="slnote" id="slnote" class="form-control" value="<?php echo isset($FORM['slnote']) ? $FORM['slnote'] : '' ?>" placeholder="Sales note">
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <div class="form-group">
                            <label><?php echo myvalidate($LANG['g_keyword']); ?></label>
                            <input type="sltoken" name="sltoken" id="sltoken" class="form-control" value="<?php echo isset($FORM['sltoken']) ? $FORM['sltoken'] : '' ?>" placeholder="Sales keyword">
                        </div>
                    </div>

                </div>
            </div>
            <div class="card-footer bg-whitesmoke">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="float-md-right">
                            <a href="index.php?hal=mysaleslist&dohal=clear" class="btn btn-warning"><i class="fa fa-fw fa-redo"></i> Clear</a>
                            <button type="submit" name="submit" value="search" id="submit" class="btn btn-primary"><i class="fa fa-fw fa-search"></i> Search</button>
                        </div>
                        <div class="d-block d-sm-none">
                            &nbsp;
                        </div>
                        <div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="clearfix"></div>
        </div>
        <input type="hidden" name="hal" value="mysaleslist">
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
                    <th scope="col" nowrap><?php echo myvalidate($tblshort['sldatetm']); ?>Date</th>
                    <th scope="col" nowrap><?php echo myvalidate($tblshort['slbatch']); ?>Transaction ID</th>
                    <th scope="col" nowrap><?php echo myvalidate($LANG['g_product']); ?></th>
                    <th scope="col" nowrap><?php echo myvalidate($tblshort['slprice']); ?>Amount</th>
                    <th scope="col" class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (count($salesData) > 0) {
                    $s = $pages->get_disponpage($FORM);
                    foreach ($salesData as $val) {
                        $s++;

                        $statustipstr = "data-toggle='tooltip' title='" . $slstatuscaparr[$val['slstatus']] . "'";
                        if ($val['slstatus'] == 1) {
                            $bletmark = "<span class='bullet text-success' {$statustipstr}></span>";
                        } elseif ($val['slstatus'] == 2) {
                            $bletmark = "<span class='bullet text-secondary' {$statustipstr}></span>";
                        } elseif ($val['slstatus'] == 3) {
                            $bletmark = "<span class='bullet text-warning' {$statustipstr}></span>";
                        } elseif ($val['slstatus'] == 4) {
                            $bletmark = "<span class='bullet text-danger' {$statustipstr}></span>";
                        } else {
                            $bletmark = "<span class='bullet text-light' {$statustipstr}></span>";
                        }

                        $slexpdatetmdisp = formatdate($val['slexpdatetm']);
                        $expiryinstr = $LANG['g_expirywithin'] . ' ' . time_expiry($val['slexpdatetm']);
                        if ($val['slexpdatetm'] > $val['sldatetm']) {
                            if ($val['slexpdatetm'] < $cfgrow['datetimestr']) {
                                $expdicon = "<i class='fas fa-business-time fa-fw text-danger' data-toggle='tooltip' title='{$LANG['g_expirationdate']}: {$slexpdatetmdisp}'></i>";
                                $slexpdatetmstr = "<span class='text-danger'>{$slexpdatetmdisp}</span>";
                            } else {
                                $expdicon = "<i class='fas fa-business-time fa-fw text-success' data-toggle='tooltip' title='{$LANG['g_expirationdate']}: {$slexpdatetmdisp}'></i>";
                                $slexpdatetmstr = "<span class='text-success'>{$slexpdatetmdisp}</span>";
                            }
                        } else {
                            $expdicon = '';
                        }

                        $slfieldstr = '';
                        $slfieldarr = unserialize($val['slfield'] ?? '');
                        foreach ((array) $slfieldarr as $key => $value) {
                            if ($key == 'b:0;') {
                                continue;
                            }
                            $slfieldstr .= "<strong>&bull;</strong> <span class='text-small text-muted'>" . trim($key ?? '') . ":</span> " . strip_tags($value ?? '') . "<br />";
                        }

                        $itemnamestr = $val['itname'];
                        $buyerstr = $val['slmbrun'];

                        $sltoken = get_optionvals($val['sltoken']);

                        $overview = "<label>Info</label><div>" . $val['adminfo'] . "</div>";

                        $slidhash = md5($val['slid'] . '/' . $mbrstr['id'] . $mdlhashy);
                        ?>
                        <tr>

                            <th scope="row"><?php echo myvalidate($s); ?></th>
                            <td data-toggle="tooltip" title="<?php echo myvalidate($val['sldatetm']); ?>"><?php echo formatdate($val['sldatetm']); ?></td>
                            <td><?php echo myvalidate($val['slbatch']) ? $expdicon . ' ' . $val['slbatch'] : '-'; ?></td>
                            <td><?php echo myvalidate($itemnamestr); ?></td>
                            <td class="text-right"><?php echo myvalidate($val['slprice'] . $bletmark); ?></td>
                            <td align="center" nowrap>
                                <a href="javascript:;"
                                   data-href="mygetsales.php?redir=mysaleslist&slidhash=<?php echo myvalidate($slidhash); ?>&slid=<?php echo myvalidate($val['slid']); ?>&mdlhashy=<?php echo myvalidate($mdlhashy); ?>"
                                   data-poptitle="<i class='fa fa-fw fa-receipt'></i> <?php echo myvalidate($LANG['m_overview']); ?>"
                                   class="openPopup btn btn-sm btn-secondary"
                                   data-html="true"
                                   data-toggle="popover"
                                   data-trigger="hover"
                                   data-placement="left" 
                                   title="<?php echo myvalidate($val['slbatch']); ?>"
                                   data-content="<h6><?php echo myvalidate($val['txtmstamp']); ?></h6>
                                   <?php
                                   if ($expdicon != '') {
                                       ?>
                                       <div class='text-small mt-2'><?php echo myvalidate($LANG['g_expirationdate']); ?>:</div>
                                       <div><?php echo myvalidate($slexpdatetmstr); ?></div>
                                       <div class='text-small text-muted'><?php echo myvalidate($expiryinstr); ?></div>
                                       <?php
                                   }
                                   ?>
                                   <div class='text-small mt-2'><?php echo myvalidate($LANG['g_description']); ?>:</div>
                                   <div><?php echo myvalidate($val['slnote']); ?></div>
                                   <div class='text-small mt-2'><?php echo myvalidate($LANG['g_buyer']); ?>:</div>
                                   <div><strong><?php echo myvalidate($buyerstr); ?></strong></div>
                                   <div class='text-small mt-2'>Delivery Option:</div>
                                   <div class='text-small text-muted'>Content Access: <strong><?php echo intval($sltoken['didcnt']); ?></strong>x</div>
                                   <div class='text-small text-muted'>URL Visit: <strong><?php echo intval($sltoken['didvisit']); ?></strong>x</div>
                                   <div class='mt-2'><?php echo myvalidate($val['sladminfo']); ?></div>">
                                    <i class="far fa-fw fa-question-circle"></i>
                                </a>
                                <a href="javascript:;" data-href="mydosales.php?editId=<?php echo myvalidate($val['slid']); ?>&redir=mysaleslist&mdlhashy=<?php echo myvalidate($mdlhashy); ?>" data-poptitle="<i class='fa fa-fw fa-edit'></i> Update Sales History #<?php echo myvalidate($val['slbatch']); ?>" class="btn btn-sm btn-success openPopup" data-toggle="tooltip" title="Update <?php echo myvalidate($val['slbatch']); ?>"><i class="fa fa-fw fa-edit"></i></a>
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
