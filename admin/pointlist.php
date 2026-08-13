<?php
if (!defined('OK_LOADME')) {
    die('o o p s !');
}

if ($FORM['dopId'] > 0 && $FORM['pohash'] == md5($FORM['dopId'] . date("d") . $cfgrow['lichash'])) {
    $poid = intval($FORM['dopId']);
    $pointarr = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_points WHERE poid = '{$poid}' ");
    $potoken = $pointarr[0]['potoken'];
    $potoken = put_optionvals($potoken, 'exchdatetm', $cfgrow['datetimestr']);
    $data = array(
        'postatus' => 1,
        'potoken' => $potoken,
    );
    $db->update(DB_TBLPREFIX . '_points', $data, array('poid' => $poid));
    redirpageto('index.php?hal=pointlist');
    exit;
}

$condition = '';

$tblshort_arr = array("podatetm", "pomemo", "popoint");
$tblshort = dborder_arr($tblshort_arr, $FORM['_stbel'], $FORM['_stype']);
if ($FORM['_stbel'] != '' && (in_array($FORM['_stbel'], $tblshort_arr))) {
    $sqlshort = ($FORM['_stype'] == 'up') ? " ORDER BY {$FORM['_stbel']} DESC " : " ORDER BY {$FORM['_stbel']} ASC ";
} else {
    $sqlshort = " ORDER BY poid DESC ";
}

//Main queries
$sql = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_points WHERE 1 " . $condition . "");
$pages->items_total = count($sql);
$pages->mid_range = 3;
$pages->paginate();

$pointData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_points WHERE 1 " . $condition . $sqlshort . $pages->limit . "");
?>

<div class="section-header">
    <h1><i class="fa fa-fw fa-coins"></i> <?php echo myvalidate($LANG['g_pointlist']); ?></h1>
</div>

<div class="section-body">

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
                    <th scope="col" nowrap><?php echo myvalidate($tblshort['podatetm']); ?>Date</th>
                    <th scope="col" nowrap><?php echo myvalidate($tblshort['pomemo']); ?>Description</th>
                    <th scope="col" class="text-right" nowrap><?php echo myvalidate($tblshort['popoint']); ?>Point</th>
                    <th scope="col" class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (count($pointData) > 0) {
                    $s = $pages->get_disponpage($FORM);
                    foreach ($pointData as $val) {
                        $s++;

                        $payfrom = getusernameid($val['pofromid'], 'username');
                        $payto = getusernameid($val['potoid'], 'username');

                        $potokenarr = get_optionvals($val['potoken']);

                        switch ($val['postatus']) {
                            // 0=waiting, 1=complete
                            case "1":
                                $btnstatustext = "Complete";
                                $btnstatuscolor = "success";
                                $btnstatusicon = "<i class='fas fa-fw fa-check'></i>";
                                $btnstatusstate = " disabled";
                                break;
                            default:
                                $btnstatustext = "In process...";
                                $btnstatuscolor = "warning";
                                $btnstatusicon = "<i class = 'fas fa-fw fa-stopwatch'></i>";
                                $btnstatusstate = "";
                        }

                        if ($potokenarr['exchtype'] == 'file') {
                            $exchval = str_replace('xchfl_', '', $potokenarr['exchval']);
                            $condition = " AND flid = '{$exchval}'";
                            $rowData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_files WHERE 1 " . $condition . "");
                            $prvalstr = $rowData[0]['flname'];
                            $pointoexchval = "<div class='text-small text-muted'>{$prvalstr}</div>";
                        } else if ($potokenarr['exchtype'] == 'page') {
                            $exchval = str_replace('xchpg_', '', $potokenarr['exchval']);
                            $condition = " AND pgid = '{$exchval}'";
                            $rowData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_pages WHERE 1 " . $condition . "");
                            $prvalstr = $rowData[0]['pgtitle'];
                            $pointoexchval = "<div class='text-small text-muted'>{$prvalstr}</div>";
                        } else if ($potokenarr['exchtype'] == 'cash') {
                            $pointoexchval = $bpprow['currencysym'] . $potokenarr['exchval'];
                        } else if ($potokenarr['exchtype'] == 'custom') {
                            $prvalstr = base64_decode($potokenarr['exchval']);
                            $pointoexchval = "<div class='text-small text-muted'>{$prvalstr}</div>";
                        }
                        $pointoexchval = ($pointoexchval != '') ? ' ' . $pointoexchval : '';

                        $pohash = md5($val['poid'] . date("d") . $cfgrow['lichash']);

                        $completeon = ($potokenarr['exchdatetm'] != '') ? "Complete on " . formatdate($potokenarr['exchdatetm']) : '';

                        $overview = "<label>Info</label><div>" . $val['adminfo'] . "</div>";
                        ?>
                        <tr>

                            <th scope="row"><?php echo myvalidate($s); ?></th>
                            <td data-toggle="tooltip" title="<?php echo myvalidate($val['podatetm']); ?>"><?php echo formatdate($val['podatetm']); ?></td>
                            <td><?php echo myvalidate($val['pomemo'] . $pointoexchval); ?></td>
                            <td class="text-right"><?php echo myvalidate($val['popoint']); ?></td>

                            <td align="center" nowrap>
                                <a href="javascript:;"
                                   class="btn btn-sm btn-secondary"
                                   data-html="true"
                                   data-toggle="popover"
                                   data-trigger="hover"
                                   data-placement="left" 
                                   title="<?php echo myvalidate($val['poid'] . '. ' . $bpparr[$val['poppid']]['ppname']); ?>"
                                   data-content="<h6><?php echo myvalidate($val['txtmstamp']); ?></h6>
                                   <div>From: <?php echo myvalidate($payfrom); ?></div>
                                   <div>To: <?php echo myvalidate($payto); ?></div>
                                   <div class='text-small text-muted'><?php echo myvalidate($completeon); ?></div>
                                   <div class='text-small mt-2'><?php echo myvalidate($val['txadminfo']); ?></div>
                                   ">
                                    <i class="far fa-fw fa-question-circle"></i>
                                </a>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-<?php echo myvalidate($btnstatuscolor); ?> btn-sm" data-toggle="tooltip" title="<?php echo myvalidate($btnstatustext); ?>"><?php echo myvalidate($btnstatusicon); ?></button>
                                    <button type="button" class="btn btn-<?php echo myvalidate($btnstatuscolor); ?> btn-sm dropdown-toggle dropdown-toggle-split" data-toggle="dropdown"<?php echo myvalidate($btnstatusstate); ?>>
                                        <span class="sr-only">Status</span>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item bootboxconfirm" href="javascript:;" data-href="index.php?hal=pointlist&dopId=<?php echo myvalidate($val['poid']); ?>&pohash=<?php echo myvalidate($pohash); ?>" data-poptitle="Change Reward Status" data-popmsg="<div class='text-info text-small mb-2'>Make sure you have got the member reward processed and delivered before change the status to Complete.</div><div class='text-danger'>Are you sure you want to change the status to Complete?</div>"><i class="fa fa-check fa-fw"></i> Mark as Complete</a>
                                    </div>
                                </div>
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
