<?php
if (!defined('OK_LOADME')) {
    die('o o p s !');
}

$FORM = do_eskep($FORM);

$condition = '';
if (isset($FORM['prname']) and $FORM['prname'] != "") {
    $condition .= ' AND prname LIKE "%' . $FORM['prname'] . '%" ';
}
if (isset($FORM['prinfo']) and $FORM['prinfo'] != "") {
    $condition .= ' AND prinfo LIKE "%' . $FORM['prinfo'] . '%" ';
}
if (isset($FORM['prtoken']) and $FORM['prtoken'] != "") {
    $condition .= ' AND (pradminfo LIKE "%' . $FORM['prtoken'] . '%" OR prtoken LIKE "%' . $FORM['prtoken'] . '%") ';
}

$condition = str_replace(array("'"), '', $condition ?? '');

$tblshort_arr = array("prdatetm", "prname", "prminpoints");
$tblshort = dborder_arr($tblshort_arr, $FORM['_stbel'], $FORM['_stype']);
if ($FORM['_stbel'] != '' && (in_array($FORM['_stbel'], $tblshort_arr))) {
    $sqlshort = ($FORM['_stype'] == 'up') ? " ORDER BY {$FORM['_stbel']} DESC " : " ORDER BY {$FORM['_stbel']} ASC ";
} else {
    $sqlshort = " ORDER BY prid DESC ";
}

//Main queries
$sql = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_pointrwds WHERE 1 " . $condition . "");
$pages->items_total = count($sql);
$pages->mid_range = 3;
$pages->paginate();

$pointrwdData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_pointrwds WHERE 1 " . $condition . $sqlshort . $pages->limit . "");
?>

<div class="section-header">
    <h1><i class="fa fa-fw fa-gift"></i> Manage Point Rewards</h1>
</div>

<div class="section-body">

    <form method="get">
        <div class="card card-primary">
            <div class="card-header">
                <h4>
                    <i class="fa fa-fw fa-search"></i> Find Rewards
                </h4>
            </div>
            <div class="card-body">
                <div class="row">

                    <div class="col-sm-4">
                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="prname" id="prname" class="form-control" value="<?php echo isset($FORM['prname']) ? $FORM['prname'] : '' ?>" placeholder="Reward title">
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <div class="form-group">
                            <label><?php echo myvalidate($LANG['g_description']); ?></label>
                            <input type="text" name="prinfo" id="prinfo" class="form-control" value="<?php echo isset($FORM['prinfo']) ? $FORM['prinfo'] : '' ?>" placeholder="Reward description">
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <div class="form-group">
                            <label><?php echo myvalidate($LANG['g_keyword']); ?></label>
                            <input type="prtoken" name="prtoken" id="prtoken" class="form-control" value="<?php echo isset($FORM['prtoken']) ? $FORM['prtoken'] : '' ?>" placeholder="Enter keyword">
                        </div>
                    </div>

                </div>
            </div>
            <div class="card-footer bg-whitesmoke">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="float-md-right">
                            <a href="index.php?hal=pointrwd" class="btn btn-danger"><i class="fa fa-fw fa-redo"></i> Clear</a>
                            <button type="submit" name="submit" value="search" id="submit" class="btn btn-primary"><i class="fa fa-fw fa-search"></i> Search</button>
                        </div>
                        <div class="d-block d-sm-none">
                            &nbsp;
                        </div>
                        <div>
                            <a href="javascript:;" data-href="dopointrwd.php?redir=pointrwd&mdlhasher=<?php echo myvalidate($mdlhasher); ?>" data-poptitle="<i class='fa fa-fw fa-plus'></i> New Reward" class="openPopup btn btn-secondary"><i class="fa fa-fw fa-plus"></i> Add Reward</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="clearfix"></div>
        </div>
        <input type="hidden" name="hal" value="pointrwd">
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
                    <th scope="col" nowrap><?php echo myvalidate($tblshort['prdatetm']); ?>Date</th>
                    <th scope="col" nowrap><?php echo myvalidate($tblshort['prname']); ?>Title</th>
                    <th scope="col" class="text-right" nowrap><?php echo myvalidate($tblshort['prminpoints']); ?>Points</th>
                    <th scope="col" class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (count($pointrwdData) > 0) {
                    $s = $pages->get_disponpage($FORM);

                    foreach ($pointrwdData as $val) {
                        $s++;
                        $hasdel = md5($val['prid'] . date("dH"));

                        if ($val['prtype'] == 'file') {
                            $prtypestr = "<i class='fas fa-fw fa-file'></i>";
                        } else if ($val['prtype'] == 'page') {
                            $prtypestr = "<i class='fas fa-fw fa-bookmark'></i>";
                        } else if ($val['prtype'] == 'cash') {
                            $prtypestr = "<i class='fas fa-fw fa-coins'></i>";
                        } else if ($val['prtype'] == 'custom') {
                            $prtypestr = "<i class='fas fa-fw fa-gifts'></i>";
                        }


                        $overview = "<label>Info</label><div>" . $val['pradminfo'] . "</div>";
                        $primage = ($val['primage']) ? $val['primage'] : DEFIMG_FILE;
                        ?>
                        <tr>

                            <th scope="row"><?php echo myvalidate($s); ?></th>
                            <td>
                                <span data-toggle="tooltip" title="<?php echo myvalidate($val['prdatetm']); ?>">
                                    <?php echo formatdate($val['prdatetm']); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo myvalidate(substr($val['prname'], 0, 24)); ?>
                            </td>
                            <td class="text-right"><?php echo intval($val['prminpoints']); ?></td>
                            <td align="center" nowrap>
                                <span class='badge badge-light text-primary' data-toggle='tooltip' title='<?php echo myvalidate($prtypeopt_array[$val['prtype']]); ?>'><?php echo myvalidate($prtypestr); ?></span>
                                <a href="javascript:;"
                                   class="btn btn-sm btn-secondary"
                                   data-html="true"
                                   data-toggle="popover"
                                   data-trigger="hover"
                                   data-placement="left" 
                                   title="<?php echo myvalidate($val['prid'] . '. ' . $val['prname']); ?>"
                                   data-content="<div><img src='<?php echo myvalidate($primage); ?>' class='mr-3 rounded' width='128'></div>
                                   <div class='text-small mt-2'><?php echo myvalidate($val['prinfo']); ?></div>
                                   <div class='text-muted text-small mt-2'><?php echo myvalidate($val['pradminfo']); ?></div>
                                   ">
                                    <i class="far fa-fw fa-question-circle"></i>
                                </a>
                                <a href="javascript:;" data-href="dopointrwd.php?editId=<?php echo myvalidate($val['prid']); ?>&redir=pointrwd&mdlhasher=<?php echo myvalidate($mdlhasher); ?>" data-poptitle="<i class='fa fa-fw fa-edit'></i> Update Reward Details" class="btn btn-sm btn-info openPopup" data-toggle="tooltip" title="Update <?php echo myvalidate($val['prname']); ?>"><i class="fa fa-fw fa-edit"></i></a>
                                <a href="javascript:;" data-href="dopointrwd.php?hash=<?php echo myvalidate($hasdel); ?>&delId=<?php echo myvalidate($val['prid']); ?>&redir=pointrwd&mdlhasher=<?php echo myvalidate($mdlhasher); ?>" class="btn btn-sm btn-danger bootboxconfirm" data-poptitle="File: <?php echo myvalidate($val['prid'] . '. ' . $val['prname']); ?>" data-popmsg="Are you sure want to delete this reward?" data-toggle="tooltip" title="Delete <?php echo myvalidate($val['prname']); ?>"><i class="far fa-fw fa-trash-alt"></i></a>
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

