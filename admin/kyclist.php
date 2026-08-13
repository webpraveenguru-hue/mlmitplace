<?php
if (!defined('OK_LOADME')) {
    die('o o p s !');
}

$FORM = do_eskep($FORM);

$findcondition = '';
if (isset($FORM['username']) && $FORM['username'] != "") {
    $findcondition .= " AND (username LIKE '%{$FORM['username']}%' OR email LIKE '%{$FORM['username']}%') ";
}
if (isset($FORM['name']) && $FORM['name'] != "") {
    $findcondition .= " AND (firstname LIKE '%{$FORM['name']}%' OR lastname LIKE '%{$FORM['name']}%') ";
}

if ($findcondition != '') {
    $rowstr = $db->getRecFrmQry("SELECT id FROM " . DB_TBLPREFIX . "_mbrs WHERE 1 " . $findcondition . "");
    $findmbrid = intval($rowstr[0]['id']);
}

$condition = '';
if ($findcondition != '') {
    $condition .= " AND kyidmbr = '{$findmbrid}' ";
}
if (isset($FORM['kyadminfo']) && $FORM['kyadminfo'] != "") {
    $condition .= " AND kyadminfo LIKE '%{$FORM['kyadminfo']}%' ";
}

// shorting
$tblshort_arr = array("kyid", "kyupdate", "kyorder");
$tblshort = dborder_arr($tblshort_arr, $FORM['_stbel'], $FORM['_stype']);
if ($FORM['_stbel'] != '' && (in_array($FORM['_stbel'], $tblshort_arr))) {
    $sqlshort = ($FORM['_stype'] == 'up') ? " ORDER BY {$FORM['_stbel']} DESC " : " ORDER BY {$FORM['_stbel']} ASC ";
} else {
    $sqlshort = " ORDER BY kyorder ASC, kyid DESC ";
}

//Main queries
$sql = $db->getRecFrmQry("SELECT kyid FROM " . DB_TBLPREFIX . "_mbrkycs LEFT JOIN " . DB_TBLPREFIX . "_mbrs ON kyidmbr = id WHERE 1 " . $condition . "");
$pages->items_total = count($sql);
$pages->mid_range = 3;
$pages->paginate();

$kycData = $db->getRecFrmQry("SELECT kyid, kyidmbr, kyupdate, kystatus, kyorder, kytoken, kyadminfo, username, firstname, lastname, email FROM " . DB_TBLPREFIX . "_mbrkycs LEFT JOIN " . DB_TBLPREFIX . "_mbrs ON kyidmbr = id WHERE 1 " . $condition . ' ' . $sqlshort . $pages->limit . "");
?>

<div class="section-header">
    <h1><i class="fa fa-fw fa-user-check"></i> <?php echo myvalidate($LANG['a_managekyc']); ?></h1>
</div>

<div class="section-body">

    <form method="get">
        <div class="card card-primary">
            <div class="card-header">
                <h4>
                    <i class="fa fa-fw fa-search"></i> Find KYC
                </h4>
            </div>
            <div class="card-body">
                <div class="row">

                    <div class="col-sm-4">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" id="username" class="form-control" value="<?php echo isset($FORM['username']) ? $FORM['username'] : '' ?>" placeholder="Enter member username">
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" name="name" id="name" class="form-control" value="<?php echo isset($FORM['name']) ? $FORM['name'] : '' ?>" placeholder="Enter member name">
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <div class="form-group">
                            <label>Note</label>
                            <input type="text" name="kyadminfo" id="kyadminfo" class="form-control" value="<?php echo isset($FORM['kyadminfo']) ? $FORM['kyadminfo'] : '' ?>" placeholder="Enter KYC keyword">
                        </div>
                    </div>

                </div>
            </div>
            <div class="card-footer bg-whitesmoke">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="float-md-right">
                            <a href="index.php?hal=kyclist&dohal=clear" class="btn btn-warning"><i class="fa fa-fw fa-redo"></i> Clear<?php echo myvalidate($clearfilterusrstr); ?></a>
                            <button type="submit" name="submit" value="search" id="submit" class="btn btn-primary"><i class="fa fa-fw fa-search"></i> Search</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="clearfix"></div>
        </div>
        <input type="hidden" name="hal" value="kyclist">
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
                    <th scope="col"><?php echo myvalidate($tblshort['kyid']); ?>#</th>
                    <th scope="col" nowrap><?php echo myvalidate($tblshort['kyupdate']); ?>Update</th>
                    <th scope="col" nowrap>Member</th>
                    <th scope="col" class="text-center" nowrap><?php echo myvalidate($tblshort['kyorder']); ?>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (count($kycData) > 0) {
                    $s = $pages->get_disponpage($FORM);

                    foreach ($kycData as $val) {
                        $s++;
                        $hasdel = md5($val['kyid'] . date("dH"));

                        if ($val['kystatus'] == '1') {
                            $kystatusbadge = "<span class='badge badge-success'>{$LANG['g_kycverified']}</span>";
                        } else if ($val['kystatus'] == '2') {
                            $kystatusbadge = "<span class='badge badge-warning'>{$LANG['g_kycincomplete']}</span>";
                        } else {
                            $kystatusbadge = "<span class='badge badge-danger'>{$LANG['g_kycunverified']}</span>";
                        }
                        ?>

                        <tr>

                            <th scope="row"><?php echo myvalidate($s); ?></th>
                            <td data-toggle="tooltip" title="<?php echo myvalidate($val['kyupdate']); ?>" nowrap><?php echo formatdate($val['kyupdate']); ?></td>
                            <td data-toggle='tooltip' title='<?php echo myvalidate($val['email']); ?>'><?php echo myvalidate($val['username']); ?></td>
                            <td align="center" nowrap>
                                <?php echo myvalidate($kystatusbadge); ?>
                                <a href="javascript:;"
                                   class="btn btn-sm btn-secondary"
                                   data-html="true"
                                   data-toggle="popover"
                                   data-trigger="hover"
                                   data-placement="left" 
                                   title="<?php echo strtoupper($val['kyid'] . ' / ' . $val['kyupdate']); ?>"
                                   data-content="<div class='mt-2'></div>
                                   <div class='mt-2'>
                                   <span class='text-small text-muted'>Username</span><br />
                                   <?php echo myvalidate($val['username']); ?>
                                   </div>
                                   <div class='mt-2'>
                                   <span class='text-small text-muted'>Email</span><br />
                                   <?php echo myvalidate($val['email']); ?>
                                   </div>
                                   <div class='mt-2'>
                                   <span class='text-small text-muted'>Name</span><br />
                                   <?php echo myvalidate($val['firstname'] . ' ' . $val['lastname']); ?>
                                   </div>
                                   <div class='text-muted'>
                                   <?php echo myvalidate($val['kyadminfo']); ?>
                                   </div>
                                   ">
                                    <i class="far fa-fw fa-question-circle"></i>
                                </a>
                                <a href="javascript:;" data-href="dokyc.php?kyID=<?php echo myvalidate($val['kyid']); ?>&redir=kyclist&mdlhasher=<?php echo myvalidate($mdlhasher); ?>" data-poptitle="<i class='fa fa-fw fa-edit'></i> Update KYC #<?php echo myvalidate($val['kyid'] . ' / Last Update: ' . $val['kyupdate']); ?>" class="btn btn-sm btn-success openPopup" data-toggle="tooltip" title="Review and Update KYC <?php echo myvalidate($val['username']); ?>"><i class="fa fa-fw fa-street-view"></i></a>
                                <a href="javascript:;" data-href="dokyc.php?hash=<?php echo myvalidate($hasdel); ?>&delId=<?php echo myvalidate($val['kyid']); ?>&redir=kyclist&mdlhasher=<?php echo myvalidate($mdlhasher); ?>" class="btn btn-sm btn-danger bootboxconfirm" data-poptitle="KYC: <?php echo myvalidate($val['username']); ?>" data-popmsg="Are you sure want to delete this KYC?" data-toggle="tooltip" title="Delete KYC <?php echo myvalidate($val['username']); ?>"><i class="far fa-fw fa-trash-alt"></i></a>
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
