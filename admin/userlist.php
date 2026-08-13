<?php
if (!defined('OK_LOADME')) {
    die('o o p s !');
}

$FORM = do_eskep($FORM);

if ($FORM['dohal'] == 'clear') {
    $_SESSION['filterid'] = $_SESSION['filtermpid'] = '';
    redirpageto('index.php?hal=userlist');
    exit;
} else if ($FORM['dohal'] == 'sesclear') {
    $db->delete(DB_TBLPREFIX . '_sessions', array('sestype' => 'member'));
    redirpageto('index.php?hal=dashboard');
    exit;
}

if ($FORM['dohal'] == 'filter' && $FORM['dompid']) {
    $_SESSION['filterid'] = $FORM['doval'];
    $_SESSION['filtermpid'] = $FORM['dompid'];
}

$condition = '';
$ppidfltr = intval($FORM['ppidfltr']);

if (isset($FORM['ppidfltr']) && $ppidfltr > 0) {
    $condition .= ' AND mppid = "' . $ppidfltr . '" ';
}
if (isset($FORM['name']) && $FORM['name'] != "") {
    $condition .= ' AND (firstname LIKE "%' . $FORM['name'] . '%" OR lastname LIKE "%' . $FORM['name'] . '%") ';
}
if (isset($FORM['username']) && $FORM['username'] != "") {
    $condition .= ' AND username LIKE "%' . $FORM['username'] . '%" ';
}
if (isset($FORM['email']) && $FORM['email'] != "") {
    $condition .= ' AND email LIKE "%' . $FORM['email'] . '%" ';
}

$filterusrstr = array();
if ($_SESSION['filtermpid']) {
    $filtermpid = intval($_SESSION['filtermpid']);
    $condition .= " AND sprlist LIKE '%:$filtermpid|%' ";
    $btnclorclear = 'btn-danger';
    $filterusrstr = getmbrinfo('', '', $filtermpid);
    $clearfilterusrstr = " filter for member ({$filterusrstr['username']})";
    $filtermbrlink = "index.php?hal=getuser&getId={$filterusrstr['id']}";
    $filterusrnow = "<div class='text-right ml-2'><a href='javascript:;' onclick=\"location.href = '{$filtermbrlink}'\" class='btn btn-sm btn-outline-warning'>" . strtoupper($filterusrstr['username']) . "</a></div>";
} else {
    $btnclorclear = 'btn-warning';
    $clearfilterusrstr = $filterusrnow = "";
}

$clistnow = ($FORM['cyclist'] > 0) ? $FORM['cyclist'] : '';
$icyc = 0;
$optviewcycle = '';
if ($filterusrstr['id'] > 0) {
    $userData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_mbrplans WHERE 1 AND idmbr = '{$filterusrstr['id']}' ORDER BY mpid");
    if (count($userData) > 0) {
        $pagestr = ($FORM['page']) ? $FORM['page'] : 1;
        $ippstr = ($FORM['ipp']) ? $FORM['ipp'] : 'All';
        foreach ($userData as $val) {
            $icyc++;
            $ismarked = ($FORM['cyclist'] == $icyc) ? ' &#10003;' : '';
            $isusercyc = ($val['cyclingbyid'] > 0) ? ' &#x269F;' : '';
            $plancycname = ($frlmtdcfg['isxplans'] == 1) ? $bpparr[$val['mppid']]['ppname'] . $isusercyc : $LANG['g_cycle'] . ' ' . $icyc;
            $optviewcycle .= "<a class='dropdown-item' href='index.php?page={$pagestr}&ipp={$ippstr}&hal=userlist&dohal=filter&doval={$filterusrstr['id']}&dompid={$val['mpid']}&cyclist={$icyc}'>" . strtoupper($filterusrstr['username']) . " - {$plancycname}{$ismarked}</a>";
        }
    }
}

if ($frlmtdcfg['isxplans'] == 1) {
    $plancycname = $bpparr[$filterusrstr['mppid']]['ppname'];
    $plancycstr = $LANG['g_plan'];
} else {
    $plancycname = $clistnow;
    $plancycstr = $LANG['g_cycle'];
}

$tblshort_arr = array("reg_utctime", "username", "email", "ewallet");
$tblshort = dborder_arr($tblshort_arr, $FORM['_stbel'], $FORM['_stype']);
if ($FORM['_stbel'] != '' && (in_array($FORM['_stbel'], $tblshort_arr))) {
    $sqlshort = ($FORM['_stype'] == 'up') ? " ORDER BY {$FORM['_stbel']} DESC, in_date DESC " : " ORDER BY {$FORM['_stbel']} ASC, in_date ASC ";
} else {
    $sqlshort = " ORDER BY reg_utctime DESC, in_date DESC, mpid DESC ";
}

// filter by member account
if ($FORM['mbrfltrby'] == 'reg') {
    $_SESSION['mbrfltrby'] = $FORM['mbrfltrby'];
} else {
    $_SESSION['mbrfltrby'] = '';
}

if ($_SESSION['mbrfltrby'] != '') {
    $joinersql = "LEFT JOIN";
    $mbrfltrbystr = 'Registered';
    $mbrfltrbyinfo = 'Display all the registered members';
} else {
    $joinersql = "JOIN";
    $mbrfltrbystr = 'Member Only';
    $mbrfltrbyinfo = 'Registered members who have joined to the payplan';
}

if ($FORM['dodisp'] == 'online') {
    $useronlinesql = "JOIN " . DB_TBLPREFIX . "_sessions ON id = sesidmbr";
    $condition .= " GROUP BY idmbr";
    $userlistmsg = 'Recent online member';
    $clearonsess = ' <a href="javascript:;" data-href="index.php?hal=userlist&dohal=sesclear" class="badge badge-sm badge-danger float-right bootboxconfirm" data-poptitle="Clear Session" data-popmsg="<div class=text-danger>Are you sure you want to clear the current member login session?</div>"><i class="fa fa-fw fa-trash"></i> Clear Session</a>';
} else {
    $useronlinesql = $userlistmsg = $clearonsess = '';
}

$condition64 = base64_encode($condition ?? '');

//Main queries
$sql = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_mbrs {$joinersql} " . DB_TBLPREFIX . "_mbrplans ON id = idmbr {$useronlinesql} WHERE 1 " . $condition . "");
$pages->items_total = count($sql);
$pages->mid_range = 3;
$pages->paginate();

$userData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_mbrs AS mbr {$joinersql} " . DB_TBLPREFIX . "_mbrplans AS plan ON id = idmbr {$useronlinesql} WHERE 1 " . $condition . $sqlshort . $pages->limit . "");
?>

<div class="section-header">
    <h1><i class="fa fa-fw fa-users"></i> <?php echo myvalidate($LANG['a_managemember']); ?></h1>
    <?php echo myvalidate($filterusrnow); ?>
</div>

<div class="section-body">

    <form method="get">
        <div class="card card-primary">
            <div class="card-header">
                <h4>
                    <i class="fa fa-fw fa-search"></i> Find Member
                </h4>
                <div class="card-header-action">
                    <?php
                    if ($icyc > 1) {
                        ?>
                        <div class="dropdown">
                            <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown">
                                <?php echo myvalidate($plancycstr); ?> <span class="badge badge-light"><?php echo myvalidate($plancycname); ?></span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right">
                                <?php echo myvalidate($optviewcycle); ?>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
            <div class="card-body">
                <div class="row">

                    <div class="col-sm-3">
                        <div class="form-group">
                            <label><?php echo myvalidate($LANG['g_membership']); ?></label>
                            <select name="ppidfltr" id="ppidfltr" class="form-control select1" onchange="showInfo(this)">
                                <option value='' selected><?php echo myvalidate($LANG['g_all']); ?>
                                    <?php
                                    $condition = " AND planstatus = '1' ORDER BY ppid ASC";
                                    $planData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_payplans WHERE 1 AND ppname != '' " . $condition . "");
                                    if (count($planData) > 0) {
                                        foreach ($planData as $value) {
                                            $doselected = ($value['ppid'] == $ppidfltr) ? ' selected' : '';
                                            ?>
                                        <option value='<?php echo myvalidate($value['ppid']); ?>'<?php echo myvalidate($doselected); ?>><?php echo myvalidate($value['ppname']); ?>
                                            <?php
                                        }
                                    }
                                    ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-sm-3">
                        <div class="form-group">
                            <label><?php echo myvalidate($LANG['g_firstname']); ?></label>
                            <input type="text" name="name" id="name" class="form-control" value="<?php echo isset($FORM['name']) ? $FORM['name'] : '' ?>" placeholder="Enter member name">
                        </div>
                    </div>

                    <div class="col-sm-3">
                        <div class="form-group">
                            <label><?php echo myvalidate($LANG['g_username']); ?></label>
                            <input type="text" name="username" id="username" class="form-control" value="<?php echo isset($FORM['username']) ? $FORM['username'] : '' ?>" placeholder="Enter member username">
                        </div>
                    </div>

                    <div class="col-sm-3">
                        <div class="form-group">
                            <label><?php echo myvalidate($LANG['g_email']); ?></label>
                            <input type="text" name="email" id="useremail" class="form-control" value="<?php echo isset($FORM['email']) ? $FORM['email'] : '' ?>" placeholder="Enter member email">
                        </div>
                    </div>

                </div>
            </div>
            <div class="card-footer bg-whitesmoke">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="float-md-right">
                            <a href="javascript:;" data-href="adduser.php?doact=excsv&mdlhasher=<?php echo myvalidate($mdlhasher); ?>&sqlflter=<?php echo myvalidate($condition64); ?>" class="btn btn-secondary bootboxconfirm" data-poptitle="Export Data" data-popmsg="<div class=text-info>Are you sure you want to export the current member data?</div>"><i class="fas fa-fw fa-file-csv"></i> Export Member Data</a>

                            <div class="btn-group">
                                <button type="button" class="btn btn-info" data-toggle="tooltip" title="<?php echo myvalidate($mbrfltrbyinfo); ?>"><i class="fa fa-fw fa-check"></i> <?php echo myvalidate($mbrfltrbystr); ?></button>
                                <button type="button" class="btn btn-info dropdown-toggle dropdown-toggle-split" data-toggle="dropdown">
                                    <span class="sr-only">Status</span>
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="index.php?hal=userlist&mbrfltrby=mbr"><i class="fa fa-user-tie fa-fw"></i> Member Only</a>
                                    <a class="dropdown-item" href="index.php?hal=userlist&mbrfltrby=reg"><i class="fa fa-user fa-fw"></i> Registered</a>
                                </div>
                            </div>

                            <a href="index.php?hal=userlist&dohal=clear" class="btn <?php echo myvalidate($btnclorclear); ?>"><i class="fa fa-fw fa-redo"></i> Clear<?php echo myvalidate($clearfilterusrstr); ?></a>
                            <button type="submit" name="submit" value="search" id="submit" class="btn btn-primary"><i class="fa fa-fw fa-search"></i> Search</button>
                        </div>
                        <div class="d-block d-sm-none">
                            &nbsp;
                        </div>
                        <div>
                            <a href="javascript:;" data-href="adduser.php?redir=userlist&mdlhasher=<?php echo myvalidate($mdlhasher); ?>" data-poptitle="<i class='fa fa-fw fa-check'></i> Add Member" class="openPopup btn btn-secondary"><i class="fa fa-fw fa-plus"></i> New Member</a>
                            <a href="javascript:;" data-href="addusers.php?redir=userlist&mdlhasher=<?php echo myvalidate($mdlhasher); ?>" data-poptitle="<i class='fa fa-fw fa-check'></i> Import Members" class="openPopup btn btn-secondary"><i class="fa fa-fw fa-plus"></i> Import Members</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="clearfix"></div>
        </div>
        <input type="hidden" name="hal" value="userlist">
    </form>

    <hr />
    <?php
    if ($userlistmsg != '') {
        echo "<div class='alert alert-info'><i class='fa fa-fw fa-lightbulb'></i> {$userlistmsg}{$clearonsess}</div><hr />";
    }
    ?>

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
                    <th scope="col" nowrap><?php echo myvalidate($tblshort['in_date']); ?>Date</th>
                    <th scope="col" nowrap><?php echo myvalidate($tblshort['username']); ?>Username</th>
                    <th scope="col" nowrap><?php echo myvalidate($tblshort['email']); ?>Email</th>
                    <th scope="col" class="text-center"></th>
                    <th scope="col" class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (count($userData) > 0) {
                    $s = $pages->get_disponpage($FORM);

                    $lastses = time() - (9 * 3600 * ($cfgrow['time_offset'] + 1));
                    foreach ($userData as $val) {
                        $s++;
                        $hasdel = md5($val['id'] . date("dH"));

                        $regmbrdate = ($val['mpid'] > 0) ? $val['reg_utctime'] : $val['in_date'];

                        $usridstr = ($val['id'] != $val['mpid']) ? $val['id'] . '.' . $val['mpid'] : $val['id'];
                        $valmail = maskmail($val['email']);
                        $stremail = (strlen($valmail ?? '') > 21) ? substr($valmail ?? '', 0, 18) . '...' : $valmail;

                        $userOnline = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_sessions WHERE sesidmbr = '{$val['id']}'");
                        $valsesarr = $userOnline[0];
                        $isuseron = ($valsesarr['sesid'] > 0 && $valsesarr['sestime'] > $lastses) ? "<span class='beep'></span>" : '';

                        $isusercyc = ($val['cyclingbyid'] > 0) ? ' &#x269F;' : '';

                        $overview = "<label>Info</label><div>" . $val['adminfo'] . "</div>";
                        $mbrimgval = ($val['mbr_image']) ? $val['mbr_image'] : $cfgrow['mbr_defaultimage'];
                        $mbrimgvalstr = "<img alt='?' src='{$mbrimgval}'class='img-fluid mr-3 rounded-circle img-thumbnail' width='96'>";

                        $usernamestr = $val['username'];
                        $usernamestr = ($cfgtoken['ismbrneedconfirm'] == 1 && $val['isconfirm'] < 1) ? "<span class='text-danger'>{$usernamestr}</span>" : $usernamestr;
                        $asvendorico = ($val['isvendor'] == 1) ? "<i class='fas fa-fw fa-store-alt' data-toggle='tooltip' title='{$LANG['g_menu_vendor']}'></i>" : '';
                        ?>
                        <tr>

                            <th scope="row"><?php echo myvalidate($s); ?></th>
                            <td data-toggle="tooltip" title="<?php echo myvalidate($regmbrdate); ?>" nowrap><?php echo formatdate($regmbrdate); ?></td>
                            <td data-toggle='tooltip' title='<?php echo myvalidate($val['firstname'] . ' ' . $val['lastname']); ?>'><?php echo myvalidate($usernamestr . $isusercyc); ?></td>
                            <td data-toggle='tooltip' title='<?php echo myvalidate($valmail); ?>'><?php echo myvalidate($stremail); ?></td>
                            <td nowrap>
                                <?php echo myvalidate($isuseron); ?>
                                <?php echo badgembrplanstatus($val['mbrstatus'], $val['mpstatus'], $bpparr[$val['mppid']]['ppname'], $mbrimgval); ?>
                                <?php echo myvalidate($asvendorico); ?>
                            </td>
                            <td align="center" nowrap>
                                <a href="javascript:;"
                                   class="btn btn-sm btn-secondary"
                                   data-html="true"
                                   data-toggle="popover"
                                   data-trigger="hover"
                                   data-placement="left" 
                                   title="<?php echo strtoupper($val['username'] . ' / ' . $usridstr); ?>"
                                   data-content="<div class='mt-2'><?php echo myvalidate($mbrimgvalstr); ?></div>
                                   <div class='mt-2'><?php echo myvalidate($val['firstname'] . ' ' . $val['lastname']); ?></div>
                                   <div class='text-small mt-2'><em><?php echo myvalidate($val['adminfo']); ?></em></div>
                                   ">
                                    <i class="far fa-fw fa-question-circle"></i>
                                </a>
                                <a href="index.php?hal=getuser&getId=<?php echo myvalidate($val['id']); ?>&getMpid=<?php echo myvalidate($val['mpid']); ?>" class="btn btn-sm btn-info" data-toggle="tooltip" title="View <?php echo myvalidate($val['username']); ?>"><i class="far fa-fw fa-id-badge"></i></a>
                                <a href="javascript:;" data-href="edituser.php?editId=<?php echo myvalidate($val['id']); ?>&editMpid=<?php echo myvalidate($val['mpid']); ?>&redir=userlist&mdlhasher=<?php echo myvalidate($mdlhasher); ?>" data-poptitle="<i class='fa fa-fw fa-edit'></i> Update Member #<?php echo myvalidate($val['id'] . ' / ' . $val['username']); ?>" class="btn btn-sm btn-success openPopup" data-toggle="tooltip" title="Update <?php echo myvalidate($val['username']); ?>"><i class="fa fa-fw fa-edit"></i></a>
                                <a href="javascript:;" data-href="deluser.php?hash=<?php echo myvalidate($hasdel); ?>&delId=<?php echo myvalidate($val['id']); ?>&redir=userlist&mdlhasher=<?php echo myvalidate($mdlhasher); ?>" class="btn btn-sm btn-danger bootboxconfirm" data-poptitle="Username: <?php echo myvalidate($val['username']); ?>" data-popmsg="<div class='text-danger'>Are you sure want to delete this member?</div>" data-toggle="tooltip" title="Delete <?php echo myvalidate($val['username']); ?>"><i class="far fa-fw fa-trash-alt"></i></a>
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
