<?php
if (!defined('OK_LOADME')) {
    die('o o p s !');
}

$FORM = do_eskep($FORM);

if ($FORM['dohal'] == 'clear') {
    $_SESSION['filterid'] = '';
    redirpageto('index.php?hal=epinlist');
    exit;
}
if ($FORM['dohal'] == 'filter' && $FORM['doval']) {
    $_SESSION['filterid'] = $FORM['doval'];
}

$condition = "";

if (isset($FORM['epcode']) && $FORM['epcode'] != "") {
    $condition .= ' AND epcode LIKE "%' . $FORM['epcode'] . '%" ';
}
if (isset($FORM['epadminfo']) && $FORM['epadminfo'] != "") {
    $condition .= ' AND (eptoken LIKE "%' . $FORM['epadminfo'] . '%" OR epadminfo LIKE "%' . $FORM['epadminfo'] . '%") ';
}

$tblshort_arr = array("epdate", "epcode", "epvalue");
$tblshort = dborder_arr($tblshort_arr, $FORM['_stbel'], $FORM['_stype']);
if ($FORM['_stbel'] != '' && (in_array($FORM['_stbel'], $tblshort_arr))) {
    $sqlshort = ($FORM['_stype'] == 'up') ? " ORDER BY {$FORM['_stbel']} DESC " : " ORDER BY {$FORM['_stbel']} ASC ";
} else {
    $sqlshort = " ORDER BY epid DESC ";
}

//Main queries
$sql = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_epins WHERE 1 " . $condition . "");
$pages->items_total = count($sql);
$pages->mid_range = 3;
$pages->paginate();

$epinData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_epins LEFT JOIN " . DB_TBLPREFIX . "_mbrs ON (eprefid = id OR epusedid = id) WHERE 1 " . $condition . $sqlshort . $pages->limit . "");
?>

<div class="section-header">
    <h1><i class="fa fa-fw fa-tags"></i> <?php echo myvalidate($LANG['g_epinlist']); ?></h1>
</div>

<div class="section-body">

    <form method="get">
        <div class="card card-primary">
            <div class="card-header">
                <h4>
                    <i class="fa fa-fw fa-search"></i> Find Codes
                </h4>
            </div>
            <div class="card-body">
                <div class="row">

                    <div class="col-sm-6">
                        <div class="form-group">
                            <label><?php echo myvalidate($LANG['g_epinstr']); ?></label>
                            <input type="text" name="epcode" id="epcode" class="form-control" value="<?php echo isset($FORM['epcode']) ? $FORM['epcode'] : '' ?>" placeholder="<?php echo myvalidate($LANG['g_epinlist']); ?>">
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <div class="form-group">
                            <label><?php echo myvalidate($LANG['g_keyword']); ?></label>
                            <input type="epadminfo" name="epadminfo" id="epadminfo" class="form-control" value="<?php echo isset($FORM['epadminfo']) ? $FORM['epadminfo'] : '' ?>" placeholder="Enter keyword">
                        </div>
                    </div>

                </div>
            </div>
            <div class="card-footer bg-whitesmoke">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="float-md-right">
                            <a href="index.php?hal=epinlist&dohal=clear" class="btn btn-warning"><i class="fa fa-fw fa-redo"></i> Clear</a>
                            <button type="submit" name="submit" value="search" id="submit" class="btn btn-primary"><i class="fa fa-fw fa-search"></i> Search</button>
                        </div>
                        <div class="d-block d-sm-none">
                            &nbsp;
                        </div>
                        <div>
                            <a href="javascript:;" data-href="doepin.php?redir=epinlist&mdlhasher=<?php echo myvalidate($mdlhasher); ?>" data-poptitle="<i class='fa fa-fw fa-check'></i> Add <?php echo myvalidate($LANG['g_epinlist']); ?>" class="openPopup btn btn-secondary"><i class="fa fa-fw fa-plus"></i> New Codes</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="clearfix"></div>
        </div>
        <input type="hidden" name="hal" value="epinlist">
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
                    <th scope="col" nowrap><?php echo myvalidate($tblshort['epdate']); ?>Date</th>
                    <th scope="col" nowrap><?php echo myvalidate($tblshort['epcode'] . $LANG['g_epinlist']); ?></th>
                    <th scope="col" nowrap></th>
                    <th scope="col" nowrap><?php echo myvalidate($tblshort['epvalue'] . $LANG['g_epinvalue']); ?></th>
                    <th scope="col" class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (count($epinData) > 0) {
                    $pgdataarr = array('perfectmoneyacc' => 'perfectmoneycfg', 'paystackpub' => 'paystackcfg', 'payfastmercid' => 'payfastcfg', 'stripeacc' => 'stripecfg');
                    $s = $pages->get_disponpage($FORM);
                    foreach ($epinData as $val) {
                        $s++;
                        $hasdel = md5($val['epid'] . date("dH"));

                        $epusedidstr = ($val['epusedid'] > 0) ? $val['username'] : $LANG['g_all'];
                        $epicomark = '';
                        switch ($val['epstatus']) {
                            case "1":
                                $statusbadge = "<span class='badge badge-success'>{$LANG['g_active']}</span>";
                                $epicomark = ($val['epusedid'] > 0) ? ' text-warning' : ' text-success';
                                break;
                            case "2":
                                $statusbadge = "<span class='badge badge-secondary'>{$LANG['g_used']}</span>";
                                break;
                            case "3":
                                $statusbadge = "<span class='badge badge-warning'>{$LANG['g_suspend']}</span>";
                                break;
                            default:
                                $statusbadge = "<span class='badge badge-light'>{$LANG['g_inactive']}</span>";
                        }

                        if ($val['eptype'] == 'item') {
                            $codforwhat = '<i class="fas fa-fw fa-shopping-bag' . $epicomark . '"></i> ' . get_iteminfo($val['epvalue'])['itname'];
                        } else if ($val['eptype'] == 'plan') {
                            $codforwhat = '<i class="fas fa-fw fa-user-tag' . $epicomark . '"></i> ' . $bpparr[$val['epvalue']]['ppname'];
                        } else {
                            $codforwhat = '?';
                        }

                        $overview = "<strong>Info</strong><div class='text-info'>" . $val['epadminfo'] . "</div>";
                        ?>
                        <tr>

                            <th scope="row"><?php echo myvalidate($s); ?></th>
                            <td data-toggle="tooltip" title="<?php echo myvalidate($val['epdate']); ?>"><?php echo formatdate($val['epdate']); ?></td>
                            <td><?php echo ($val['epcode']) ? myvalidate($val['epcode']) : '-'; ?></td>
                            <td class="text-right">
                                <?php
                                if ($val['epstatus'] == '1') {
                                    ?>
                                    <a href="javascript:;" data-href="doepin.php?redir=epinlist&epdate=<?php echo base64_encode($val['epdate']); ?>&mdlhasher=<?php echo myvalidate($mdlhasher); ?>" data-poptitle="<i class='fa fa-fw fa-tags'></i> <?php echo myvalidate($LANG['g_epinlist']); ?>" class="openPopup btn btn-info btn-sm"><i class="fa fa-fw fa-search"></i></a>
                                    <?php
                                }
                                ?>
                            </td>
                            <td><span class='text-muted text-small'><?php echo myvalidate($codforwhat); ?></span></td>
                            <td align="center" nowrap>
                                <a href="javascript:;"
                                   class="btn btn-sm btn-secondary"
                                   data-html="true"
                                   data-toggle="popover"
                                   data-trigger="hover"
                                   data-placement="left" 
                                   title="<?php echo myvalidate($val['epdate']); ?>"
                                   data-content="<strong><?php echo myvalidate($val['epid']); ?>.<?php echo myvalidate($val['epcode'] . '</strong><br />' . $statusbadge); ?><div>Beneficiary: <?php echo myvalidate($epusedidstr); ?></div><div class='mt-2 text-small'><?php echo myvalidate($overview); ?></div>">
                                    <i class="far fa-fw fa-question-circle"></i>
                                </a>
                                <?php
                                if ($val['epstatus'] != '2') {
                                    ?>
                                    <a href="javascript:;" data-href="doepin.php?hash=<?php echo myvalidate($hasdel); ?>&delId=<?php echo myvalidate($val['epid']); ?>&redir=epinlist&mdlhasher=<?php echo myvalidate($mdlhasher); ?>" class="btn btn-sm btn-danger bootboxconfirm" data-poptitle="<?php echo myvalidate($LANG['g_epinlist'] . ': ' . $val['epid']) . '. ' . myvalidate($val['epcode']); ?>" data-popmsg="Are you sure want to delete this <?php echo myvalidate($LANG['g_epinlist']); ?>?" data-toggle="tooltip" title="Delete <?php echo myvalidate($val['epcode']); ?>"><i class="far fa-fw fa-trash-alt"></i></a>
                                    <?php
                                } else {
                                    ?>
                                    <a class="btn btn-sm btn-light"><i class="far fa-fw fa-trash-alt"></i></a>
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
