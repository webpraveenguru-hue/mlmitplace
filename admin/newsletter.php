<?php
if (!defined('OK_LOADME')) {
    die('o o p s !');
}

$FORM = do_eskep($FORM);
$mdlhashnow = hash('sha256', $mdlhasher . "@doemailrec.php");

$condition = '';

if (isset($FORM['emrsubject']) && $FORM['emrsubject'] != "") {
    $condition .= ' AND (emrsubject LIKE "%' . $FORM['emrsubject'] . '%") ';
}
if (isset($FORM['emrbody']) && $FORM['emrbody'] != "") {
    $condition .= ' AND (emrbody LIKE "%' . $FORM['emrbody'] . '%" OR emrhtmlbody LIKE "%' . $FORM['emrbody'] . '%") ';
}
if (isset($FORM['emradminfo']) && $FORM['emradminfo'] != "") {
    $condition .= ' AND emradminfo LIKE "%' . $FORM['emradminfo'] . '%" ';
}

// shorting
$tblshort_arr = array("emrid", "emrupdate", "emrsubject", "emrstatus");
$tblshort = dborder_arr($tblshort_arr, $FORM['_stbel'], $FORM['_stype']);
if ($FORM['_stbel'] != '' && (in_array($FORM['_stbel'], $tblshort_arr))) {
    $sqlshort = ($FORM['_stype'] == 'up') ? " ORDER BY {$FORM['_stbel']} DESC " : " ORDER BY {$FORM['_stbel']} ASC ";
} else {
    $sqlshort = " ORDER BY emrupdate DESC, emrid DESC ";
}

$emrstatusarr = array(0 => "Draft", 1 => "Sent");

//Main queries
$sql = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_emailrec WHERE 1 " . $condition . "");
$pages->items_total = count($sql);
$pages->mid_range = 3;
$pages->paginate();

$newsData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_emailrec WHERE 1 " . $condition . ' ' . $sqlshort . $pages->limit . "");
?>

<div class="section-header">
    <h1><i class="fa fa-fw fa-paper-plane"></i> <?php echo myvalidate($LANG['a_newsletter']); ?></h1>
</div>

<div class="section-body">

    <form method="get">
        <div class="card card-primary">
            <div class="card-header">
                <h4>
                    <i class="fa fa-fw fa-search"></i> Find Newsletter
                </h4>
            </div>
            <div class="card-body">
                <div class="row">

                    <div class="col-sm-4">
                        <div class="form-group">
                            <label>Subject</label>
                            <input type="text" name="emrsubject" id="emrsubject" class="form-control" value="<?php echo isset($FORM['emrsubject']) ? $FORM['emrsubject'] : '' ?>" placeholder="Enter subject title">
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <div class="form-group">
                            <label>Message</label>
                            <input type="text" name="emrbody" id="emrbody" class="form-control" value="<?php echo isset($FORM['emrbody']) ? $FORM['emrbody'] : '' ?>" placeholder="Enter email message">
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <div class="form-group">
                            <label>Keywords</label>
                            <input type="text" name="emradminfo" id="emradminfo" class="form-control" value="<?php echo isset($FORM['emradminfo']) ? $FORM['emradminfo'] : '' ?>" placeholder="Enter newsletter keywords">
                        </div>
                    </div>

                </div>
            </div>
            <div class="card-footer bg-whitesmoke">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="float-md-right">
                            <a href="index.php?hal=newsletter&dohal=clear" class="btn btn-warning"><i class="fa fa-fw fa-redo"></i> Clear<?php echo myvalidate($clearfilterusrstr); ?></a>
                            <button type="submit" name="submit" value="search" id="submit" class="btn btn-primary"><i class="fa fa-fw fa-search"></i> Search</button>
                        </div>
                        <div class="d-block d-sm-none">
                            &nbsp;
                        </div>
                        <div>
                            <a href="javascript:;" data-href="doemailrec.php?redir=newsletter&mdlhashnow=<?php echo myvalidate($mdlhashnow); ?>" data-poptitle="<i class='fa fa-fw fa-check'></i> Create Newsletter" class="openPopup btn btn-secondary"><i class="fa fa-fw fa-plus"></i> Create Newsletter</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="clearfix"></div>
        </div>
        <input type="hidden" name="hal" value="newsletter">
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
                    <th scope="col"><?php echo myvalidate($tblshort['emrid']); ?>#</th>
                    <th scope="col" nowrap><?php echo myvalidate($tblshort['emrupdate']); ?>Update</th>
                    <th scope="col" nowrap><?php echo myvalidate($tblshort['emrsubject']); ?>Subject</th>
                    <th scope="col" class="text-center" nowrap><?php echo myvalidate($tblshort['emrstatus']); ?>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (count($newsData) > 0) {
                    $s = $pages->get_disponpage($FORM);

                    foreach ($newsData as $val) {
                        $s++;
                        $hasdel = md5($val['emrid'] . date("dH") ?? '');

                        $btndisablestr = ' openPopup';
                        $datesent = ($val['emrstatus'] > 0) ? formatdate($val['emrdatesent'], 1) : '';
                        $statustipstr = "data-toggle='tooltip' title='" . $emrstatusarr[$val['emrstatus']] . " {$datesent}'";
                        if ($val['emrstatus'] == 1) {
                            $bletmark = "<span class='bullet text-success' {$statustipstr}></span>";
                            $btndisablestr = ' disabled';
                        } else {
                            $bletmark = "<span class='bullet text-secondary' {$statustipstr}></span>";
                        }

                        $emrtokenarr = get_optionvals($val['emrtoken']);
                        ?>
                        <tr>

                            <th scope="row"><?php echo myvalidate($s); ?></th>
                            <td data-toggle="tooltip" title="<?php echo myvalidate($val['emrupdate']); ?>" nowrap><?php echo formatdate($val['emrupdate']); ?></td>
                            <td data-toggle='tooltip' title='<?php echo myvalidate($val['emrsubject']); ?>'><?php echo myvalidate($val['emrsubject']); ?></td>
                            <td align="center" nowrap>
                                <?php echo myvalidate($bletmark); ?>
                                <a href="javascript:;"
                                   data-href="doemailrec.php?editId=<?php echo myvalidate($val['emrid']); ?>&viewId=<?php echo myvalidate($val['emrid']); ?>&mdlhashnow=<?php echo myvalidate($mdlhashnow); ?>" data-poptitle="<i class='fa fa-fw fa-search'></i> Message Preview"
                                   class="btn btn-sm btn-secondary openPopup"
                                   data-html="true"
                                   data-toggle="popover"
                                   data-trigger="hover"
                                   data-placement="left" 
                                   title="<?php echo strtoupper($val['emrid'] . ' / ' . $val['emrdatetm']); ?>"
                                   data-content="<div class='mb-1 text-small text-muted'>-- <?php echo myvalidate($emrstatusarr[$val['emrstatus']]); ?> --</div>
                                   <div class='text-small'>Last Sent: <?php echo myvalidate($emrtokenarr['execdate']); ?></div>
                                   <div class='text-small'>Queue: <?php echo intval($emrtokenarr['emqueue']); ?></div>
                                   <div class='text-small'>Processed: <?php echo intval($emrtokenarr['emsent']); ?></div>
                                   <div class='mt-2 text-small text-muted'><?php echo myvalidate($val['emradminfo']); ?></div>
                                   ">
                                    <i class="fa fa-fw fa-search"></i>
                                </a>
                                <a href="javascript:;" data-href="doemailrec.php?editId=<?php echo myvalidate($val['emrid']); ?>&redir=newsletter&mdlhashnow=<?php echo myvalidate($mdlhashnow); ?>" data-poptitle="<i class='fa fa-fw fa-edit'></i> Update Newsletter: <?php echo myvalidate($val['emrsubject']); ?>" class="btn btn-sm btn-success<?php echo myvalidate($btndisablestr); ?>" data-toggle="tooltip" title="Update Newsletter: <?php echo myvalidate($val['emrsubject']); ?>"><i class="fa fa-fw fa-edit"></i></a>
                                <a href="javascript:;" data-href="doemailrec.php?dupId=<?php echo myvalidate($val['emrid']); ?>&redir=newsletter&mdlhashnow=<?php echo myvalidate($mdlhashnow); ?>" data-poptitle="<i class='far fa-fw fa-copy'></i> Duplicate Newsletter: <?php echo myvalidate($val['emrsubject']); ?>" class="btn btn-sm btn-info openPopup" data-toggle="tooltip" title="Duplicate Newsletter: <?php echo myvalidate($val['emrsubject']); ?>"><i class="far fa-fw fa-copy"></i></a>
                                <a href="javascript:;" data-href="doemailrec.php?hash=<?php echo myvalidate($hasdel); ?>&delId=<?php echo myvalidate($val['emrid']); ?>&redir=newsletter&mdlhashnow=<?php echo myvalidate($mdlhashnow); ?>" class="btn btn-sm btn-danger bootboxconfirm" data-poptitle="Remove Newsletter" data-popmsg="<div class='mb-2'><strong><?php echo myvalidate($val['emrsubject']); ?></strong></div><span class='text-danger'>Are you sure want to delete this newsletter?</span>" data-toggle="tooltip" title="Delete <?php echo myvalidate($val['emrsubject']); ?>"><i class="far fa-fw fa-trash-alt"></i></a>
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
