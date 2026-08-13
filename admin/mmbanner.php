<?php
if (!defined('OK_LOADME')) {
    die('o o p s !');
}

$FORM = do_eskep($FORM);

$condition = '';

if (isset($FORM['bntitle']) && $FORM['bntitle'] != "") {
    $condition .= ' AND (bntitle LIKE "%' . $FORM['bntitle'] . '%") ';
}
if (isset($FORM['bnfile']) && $FORM['bnfile'] != "") {
    $condition .= ' AND bnfile LIKE "%' . $FORM['bnfile'] . '%" ';
}
if (isset($FORM['bnadminfo']) && $FORM['bnadminfo'] != "") {
    $condition .= ' AND bnadminfo LIKE "%' . $FORM['bnadminfo'] . '%" ';
}

// shorting
$tblshort_arr = array("bnid", "bndate", "bntitle", "bnstatus");
$tblshort = dborder_arr($tblshort_arr, $FORM['_stbel'], $FORM['_stype']);
if ($FORM['_stbel'] != '' && (in_array($FORM['_stbel'], $tblshort_arr))) {
    $sqlshort = ($FORM['_stype'] == 'up') ? " ORDER BY {$FORM['_stbel']} DESC " : " ORDER BY {$FORM['_stbel']} ASC ";
} else {
    $sqlshort = " ORDER BY bndate DESC, bnid DESC ";
}

//Main queries
$sql = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_banners WHERE 1 " . $condition . "");
$pages->items_total = count($sql);
$pages->mid_range = 3;
$pages->paginate();

$bannerData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_banners WHERE 1 " . $condition . ' ' . $sqlshort . $pages->limit . "");
?>

<div class="section-header">
    <h1><i class="fa fa-fw fa-puzzle-piece"></i> <?php echo myvalidate($LANG['g_mmbanner']); ?></h1>
</div>

<div class="section-body">

    <form method="get">
        <div class="card card-primary">
            <div class="card-header">
                <h4>
                    <i class="fa fa-fw fa-search"></i> Find Banner
                </h4>
            </div>
            <div class="card-body">
                <div class="row">

                    <div class="col-sm-4">
                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="bntitle" id="bntitle" class="form-control" value="<?php echo isset($FORM['bntitle']) ? $FORM['bntitle'] : '' ?>" placeholder="Enter banner title">
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <div class="form-group">
                            <label>File Name</label>
                            <input type="text" name="bnfile" id="bnfile" class="form-control" value="<?php echo isset($FORM['bnfile']) ? $FORM['bnfile'] : '' ?>" placeholder="Enter banner file name">
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <div class="form-group">
                            <label>Note</label>
                            <input type="text" name="bnadminfo" id="bnadminfo" class="form-control" value="<?php echo isset($FORM['bnadminfo']) ? $FORM['bnadminfo'] : '' ?>" placeholder="Enter banner note">
                        </div>
                    </div>

                </div>
            </div>
            <div class="card-footer bg-whitesmoke">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="float-md-right">
                            <a href="index.php?hal=mmbanner&dohal=clear" class="btn btn-warning"><i class="fa fa-fw fa-redo"></i> Clear<?php echo myvalidate($clearfilterusrstr); ?></a>
                            <button type="submit" name="submit" value="search" id="submit" class="btn btn-primary"><i class="fa fa-fw fa-search"></i> Search</button>
                        </div>
                        <div class="d-block d-sm-none">
                            &nbsp;
                        </div>
                        <div>
                            <a href="javascript:;" data-href="dobanner.php?redir=mmbanner&mdlhasher=<?php echo myvalidate($mdlhasher); ?>" data-poptitle="<i class='fa fa-fw fa-check'></i> Upload Banner" class="openPopup btn btn-secondary"><i class="fa fa-fw fa-plus"></i> New Banner</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="clearfix"></div>
        </div>
        <input type="hidden" name="hal" value="mmbanner">
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
                    <th scope="col"><?php echo myvalidate($tblshort['bnid']); ?>#</th>
                    <th scope="col" nowrap><?php echo myvalidate($tblshort['bndate']); ?>Update</th>
                    <th scope="col" nowrap><?php echo myvalidate($tblshort['bntitle']); ?>Banner</th>
                    <th scope="col" nowrap><?php echo myvalidate($tblshort['bnstatus']); ?>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (count($bannerData) > 0) {
                    $s = $pages->get_disponpage($FORM);

                    foreach ($bannerData as $val) {
                        $s++;
                        $hasdel = md5($val['bnid'] . date("dH"));

                        $bnimgstr = "<img alt='{$val['bntitle']}' src='" . BANNER_URL . '/' . $val['bnfile'] . "' class='img-fluid mr-3'>";
                        $bnimgtumbstr = "<img alt='{$val['bntitle']}' src='" . BANNER_URL . '/' . $val['bnfile'] . "' class='img-fluid mr-3 rounded img-thumbnail' width='80'>";
                        $bntokenarr = get_optionvals($val['bntoken']);

                        $bannersize = ($bntokenarr['fsize']) ? $bntokenarr['fsize'] : '';
                        $bannersize .= ($bntokenarr['imgsize']) ? " ({$bntokenarr['imgsize']})" : '';
                        ?>
                        <tr>

                            <th scope="row"><?php echo myvalidate($s); ?></th>
                            <td data-toggle="tooltip" title="<?php echo myvalidate($val['bndate']); ?>" nowrap><?php echo formatdate($val['bndate']); ?></td>
                            <td data-toggle='tooltip' title='<?php echo myvalidate($val['bntitle']); ?>'><?php echo myvalidate($bnimgtumbstr); ?></td>
                            <td align="center" nowrap>
                                <a href="javascript:;"
                                   class="btn btn-sm btn-secondary"
                                   data-html="true"
                                   data-toggle="popover"
                                   data-trigger="hover"
                                   data-placement="left" 
                                   title="<?php echo strtoupper($val['bnid'] . ' / ' . $val['bndate']); ?>"
                                   data-content="<div class='mt-2'><?php echo myvalidate($bnimgstr); ?></div>
                                   <div class='mt-1'><?php echo myvalidate($bannersize); ?></div>
                                   <div class='mt-2'><?php echo myvalidate($val['bntitle']); ?></div>
                                   <div class='text-muted'><?php echo myvalidate($val['bnadminfo']); ?></div>
                                   ">
                                    <i class="far fa-fw fa-question-circle"></i>
                                </a>
                                <a href="javascript:;" data-href="dobanner.php?bnID=<?php echo myvalidate($val['bnid']); ?>&redir=mmbanner&mdlhasher=<?php echo myvalidate($mdlhasher); ?>" data-poptitle="<i class='fa fa-fw fa-edit'></i> Update Banner #<?php echo myvalidate($val['bnid'] . ' / Last Update: ' . $val['bndate']); ?>" class="btn btn-sm btn-success openPopup" data-toggle="tooltip" title="Update Banner <?php echo myvalidate($val['bnid']); ?>"><i class="fa fa-fw fa-edit"></i></a>
                                <a href="javascript:;" data-href="dobanner.php?hash=<?php echo myvalidate($hasdel); ?>&delId=<?php echo myvalidate($val['bnid']); ?>&redir=mmbanner&mdlhasher=<?php echo myvalidate($mdlhasher); ?>" class="btn btn-sm btn-danger bootboxconfirm" data-poptitle="Banner: <?php echo myvalidate($val['bnfile']); ?>" data-popmsg="Are you sure want to delete this banner?" data-toggle="tooltip" title="Delete <?php echo myvalidate($val['bntitle']); ?>"><i class="far fa-fw fa-trash-alt"></i></a>
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
