<?php
if (!defined('OK_LOADME')) {
    die('o o p s !');
}

$FORM = do_eskep($FORM);

if ($FORM['dohal'] == 'clear') {
    redirpageto('index.php?hal=orderlist');
    exit;
}

$condition = " AND slmbrid = '{$mbrstr['id']}'";

if (isset($FORM['slbatch']) && $FORM['slbatch'] != "") {
    $condition .= " AND slbatch LIKE '%{$FORM['slbatch']}%'";
}
if (isset($FORM['slnote']) && $FORM['slnote'] != "") {
    $condition .= " AND (itname LIKE '%{$FORM['slnote']}%' OR slnote LIKE '%{$FORM['slnote']}%')";
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
    <h1><i class="fa fa-fw fa-shopping-basket"></i> <?php echo myvalidate($LANG['m_myorder']); ?></h1>
</div>

<div class="section-body">

    <form method="get">
        <div class="card card-primary">
            <div class="card-header">
                <h4>
                    <i class="fa fa-fw fa-search"></i> <?php echo myvalidate($LANG['m_findorder']); ?>
                </h4>
            </div>
            <div class="card-body">
                <div class="row">

                    <div class="col-sm-6">
                        <div class="form-group">
                            <label><?php echo myvalidate($LANG['g_transactionid']); ?></label>
                            <input type="text" name="slbatch" id="slbatch" class="form-control" value="<?php echo isset($FORM['slbatch']) ? $FORM['slbatch'] : '' ?>" placeholder="<?php echo myvalidate($LANG['g_transactionid']); ?>">
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <div class="form-group">
                            <label><?php echo myvalidate($LANG['g_description']); ?></label>
                            <input type="text" name="slnote" id="slnote" class="form-control" value="<?php echo isset($FORM['slnote']) ? $FORM['slnote'] : '' ?>" placeholder="<?php echo myvalidate($LANG['g_description']); ?>">
                        </div>
                    </div>

                </div>
            </div>
            <div class="card-footer bg-whitesmoke">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="float-md-right">
                            <a href="index.php?hal=orderlist&dohal=clear" class="btn btn-warning"><i class="fa fa-fw fa-redo"></i> <?php echo myvalidate($LANG['g_clear']); ?></a>
                            <button type="submit" name="submit" value="search" id="submit" class="btn btn-primary"><i class="fa fa-fw fa-search"></i> <?php echo myvalidate($LANG['g_search']); ?></button>
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
        <input type="hidden" name="hal" value="orderlist">
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
                    <th scope="col" nowrap><?php echo myvalidate($tblshort['sldatetm']); ?><?php echo myvalidate($LANG['g_date']); ?></th>
                    <th scope="col" nowrap><?php echo myvalidate($tblshort['slbatch']); ?><?php echo myvalidate($LANG['g_transactionid']); ?></th>
                    <th scope="col" nowrap><?php echo myvalidate($LANG['g_product']); ?></th>
                    <th scope="col" nowrap><?php echo myvalidate($tblshort['slprice']); ?><?php echo myvalidate($LANG['g_amount']); ?></th>
                    <th scope="col" class="text-center"><?php echo myvalidate($LANG['g_action']); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (count($salesData) > 0) {
                    $s = $pages->get_disponpage($FORM);
                    foreach ($salesData as $val) {
                        $s++;

                        $isitexp = '';
                        if ($val['slstatus'] == 1) {
                            $bletmark = '<span class="bullet text-success"></span>';
                        } elseif ($val['slstatus'] == 2) {
                            $bletmark = '<span class="bullet text-light"></span>';
                        } else {
                            $bletmark = '<span class="bullet text-warning"></span>';
                        }

                        $slexpdatetmdisp = formatdate($val['slexpdatetm']);
                        $expiryinstr = $LANG['g_expirywithin'] . ' ' . time_expiry($val['slexpdatetm']);
                        if ($val['slexpdatetm'] > $val['sldatetm']) {
                            if ($val['slexpdatetm'] < $cfgrow['datetimestr'] || $val['slstatus'] == 3) {
                                $expdicon = "<i class='fas fa-business-time fa-fw text-danger' data-toggle='tooltip' title='{$LANG['g_expirationdate']}: {$slexpdatetmdisp}'></i>";
                                $slexpdatetmstr = "<span class='text-danger'>{$slexpdatetmdisp}</span>";
                                $isitexp = 1;
                            } else {
                                $expdicon = "<i class='fas fa-business-time fa-fw text-success' data-toggle='tooltip' title='{$LANG['g_expirationdate']}: {$slexpdatetmdisp}'></i>";
                                $slexpdatetmstr = "<span class='text-success'>{$slexpdatetmdisp}</span><div class='text-small text-muted'>{$expiryinstr}</div>";
                            }
                        } else {
                            $expdicon = '';
                        }

                        $slfieldvalarr = array();
                        $slfieldarr = unserialize($val['slfield'] ?? '');
                        foreach ((array) $slfieldarr as $key => $value) {
                            if ($key == 'b:0;') {
                                continue;
                            }
                            $slfieldvalarr[] = strip_tags($value ?? '');
                        }
                        $slfieldstr = implode(', ', $slfieldvalarr);

                        $itemnamestr = $val['itname'];
                        $itdeliverarr = unserialize(base64_decode($val['itdeliver'] ?? ''));

                        $overview = "<label>Info</label><div>" . $val['adminfo'] . "</div>";
                        ?>
                        <tr>

                            <th scope="row"><?php echo myvalidate($s); ?></th>
                            <td data-toggle="tooltip" title="<?php echo myvalidate($val['sldatetm']); ?>"><?php echo formatdate($val['sldatetm']); ?></td>
                            <td><?php echo myvalidate($val['slbatch']) ? $expdicon . ' ' . $val['slbatch'] : '-'; ?></td>
                            <td><?php echo myvalidate($itemnamestr); ?></td>
                            <td class="text-right"><?php echo myvalidate($val['slprice'] . $bletmark); ?></td>
                            <td align="center" nowrap>
                                <a href="javascript:;"
                                   class="btn btn-sm btn-secondary"
                                   data-html="true"
                                   data-toggle="popover"
                                   data-trigger="hover"
                                   data-placement="left" 
                                   title="<?php echo myvalidate($val['slid'] . '.' . $val['slbatch']); ?>"
                                   data-content="<h6><?php echo myvalidate($val['txtmstamp']); ?></h6>
                                   <div class='text-small text-muted'>hash:<?php echo md5($val['slbatch'] . $mbrstr['id']); ?></div>
                                   <?php
                                   if ($expdicon != '') {
                                       ?>
                                       <div class='text-small mt-2'><?php echo myvalidate($LANG['g_expirationdate']); ?>:</div>
                                       <div><?php echo myvalidate($slexpdatetmstr); ?></div>
                                       <?php
                                   }
                                   ?>
                                   <div class='text-small mt-2'><?php echo myvalidate($LANG['g_description']); ?>:</div>
                                   <div><?php echo myvalidate($val['slnote']); ?></div>
                                   ">
                                    <i class="far fa-fw fa-question-circle"></i>
                                </a>
                                <?php
                                $dldlinkstr = '';

                                if ($val['slstatus'] == 1 && $isitexp != 1) {
                                    if ($itdeliverarr['itgetcnt'] != '') {
                                        $cntordhash = hash('md5', $itdeliverarr['itgetcnt'] . $mdlhashy . date('d_H'));
                                        $cntlink = base64_encode("index.php?hal=digiview&pgid={$itdeliverarr['itgetcnt']}&pghashid={$cntordhash}");

                                        $didhash = hash('sha256', 'didcnt' . $val['slid'] . $mdlhashy);
                                        $dldlinkstr = "index.php?dodeliver=didcnt&slid={$val['slid']}&doredir={$cntlink}&didhash={$didhash}";
                                        ?>
                                        <a href="javascript:;" onclick="location.href = '<?php echo myvalidate($dldlinkstr); ?>'" class="btn btn-sm btn-info" data-toggle="tooltip" title="View Content"><i class="fas fa-unlock-alt fa-fw"></i></a>
                                        <?php
                                    }
                                    $initurl = trim($itdeliverarr['iturlredir'] ?? '');
                                    if (filter_var($initurl, FILTER_VALIDATE_URL) !== false) {
                                        $didhash = hash('sha256', 'didvisit' . $val['slid'] . $mdlhashy);
                                        $gotourl = base64_encode($initurl);
                                        $dldlinkstr = "index.php?dodeliver=didvisit&slid={$val['slid']}&doredir={$gotourl}&didhash={$didhash}";
                                        ?>
                                        <a href="javascript:;" onclick="openInNewTab('<?php echo myvalidate($dldlinkstr); ?>');" class="btn btn-sm btn-info" data-toggle="tooltip" title="Visit URL"><i class="fas fa-external-link-alt fa-fw"></i></a>
                                        <?php
                                    }
                                }

                                $dldlinkstr = '';
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

<script>
    function openInNewTab(href) {
        var redirectWindow = window.open(href, '_blank');
        redirectWindow.location;
    }
</script>