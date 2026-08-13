<?php
if (!defined('OK_LOADME')) {
    die('o o p s !');
}

if ($mbrstr['reflink'] == '') {
    redirpageto('index.php?hal=dashboard');
    exit;
}

$condition = ' AND bnstatus = "1"';

// shorting
$tblshort_arr = array("bndate", "bntitle");
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
                    <th scope="col"><?php echo myvalidate($tblshort['bndate']); ?>#Update</th>
                    <th scope="col" nowrap><?php echo myvalidate($tblshort['bntitle']); ?>Banner</th>
                    <th scope="col" nowrap></th>
                    <th scope="col" align="center" nowrap></th>
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
                        $bnimgtumbstr = "<img alt='{$val['bntitle']}' src='" . BANNER_URL . '/' . $val['bnfile'] . "' class='img-fluid mr-3 rounded img-thumbnail' width='128'>";

                        $bntokenarr = get_optionvals($val['bntoken']);
                        $bannersize = ($bntokenarr['fsize']) ? $bntokenarr['fsize'] : '';
                        $bannersize .= ($bntokenarr['imgsize']) ? " ({$bntokenarr['imgsize']})" : '';
                        ?>
                        <tr>

                            <th scope="row"><?php echo myvalidate($s); ?></th>
                            <td align="left">
                                <a href="javascript:;"
                                   data-html="true"
                                   data-toggle="popover"
                                   data-trigger="hover"
                                   data-placement="left" 
                                   data-content="<div class='mt-2'><?php echo myvalidate($bnimgstr); ?></div>
                                   <div class='mt-2'><?php echo myvalidate($val['bntitle']); ?></div>
                                   ">
                                       <?php echo myvalidate($bnimgtumbstr); ?>
                                </a>
                            </td>
                            <td align="center" nowrap><span class="badge badge-light text-small"><?php echo myvalidate($bannersize); ?></span></td>
                            <td align="center" nowrap>
                                <a href="javascript:;" data-href="mmcode.php?bnID=<?php echo myvalidate($val['bnid']); ?>&type=banner&mdlhashy=<?php echo myvalidate($mdlhashy); ?>" data-poptitle="<i class='fa fa-fw fa-code'></i> Banner Code" class="btn btn-sm btn-info openPopup" data-toggle="tooltip" title="Get Banner Code"><i class="fa fa-fw fa-code"></i></a>
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
