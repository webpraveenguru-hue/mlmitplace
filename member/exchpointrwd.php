<?php
include_once('../common/init.loader.php');

$seskey = verifylog_sess('member');
if ($seskey == '') {
    die('o o p s !');
}
if ($mdlhashy != $FORM['mdlhashy']) {
    echo myvalidate($LANG['a_loadingmdlcnt']);
    redirpageto('index.php', 1);
    exit;
}

$sesRow = getlog_sess($seskey);
$mbrusername = get_optionvals($sesRow['sesdata'], 'un');

// Get referrer/member details
$mbrstr = getmbrinfo($mbrusername, 'username');

$_SESSION['redirto'] = redir_to($FORM['redir']);
if ($FORM['exchId'] > 0) {
    extract($FORM);

    $redirto = $_SESSION['redirto'];
    $_SESSION['redirto'] = '';

    do_pointrwdexch($mbrstr['id'], $FORM['exchId']);

    redirpageto($redirto);
    exit;
}

$condition = " AND prstatus = '1' AND prname != '' AND prminpoints > '0' AND prval != ''";
$pointrwdData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_pointrwds WHERE 1 " . $condition . "");
?>

<div class="row">
    <div class="col-md-12">

        <?php
        if ($cfgtoken['ismbrpointrwd'] != '1') {
            echo 'The administrator has disabled this feature.';
        } else {
            ?>
            <div class="form-group">
                <?php
                if ($cfgtoken['ismbrkyc'] == 1 && $mbrstr['kystatus'] != 1) {
                    echo myvalidate($LANG['m_kycverifynote']);
                    echo "<div class='mt-4'><a href='index.php?hal=accountcfg' class='btn btn-warning'>{$LANG['g_continue']} <i class='fas fa-fw fa-arrow-right'></i></a></div>";
                } else {
                    // get member qualified for any reward or not
                    $condition = " ORDER BY prminpoints ASC ";
                    $minpointData = $db->getRecFrmQry("SELECT prminpoints FROM " . DB_TBLPREFIX . "_pointrwds WHERE 1 " . $condition . "");
                    if ($minpointData[0]['prminpoints'] == '') {
                        // no reward found
                    } else if ($minpointData[0]['prminpoints'] > $mbrstr['epoint']) {
                        ?>
                        <div class="empty-state" data-height="100">
                            <div class="empty-state-icon bg-danger">
                                <i class="fas fa-exclamation"></i>
                            </div>
                            <p class="lead">
                                <?php echo myvalidate($LANG['g_pointexchangerwderror']); ?>
                            </p>
                        </div>
                        <?php
                    } else {
                        ?>
                        <div class="alert alert-light lead text-success mb-2"><?php echo myvalidate($LANG['g_pointexchangerwdinfo']); ?></div>
                        <div class="alert alert-light text-small text-danger mb-2"><?php echo myvalidate($LANG['g_pointexchangerwdprocess']); ?></div>
                        <?php
                    }
                    ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <tbody>
                                <?php
                                if (count($pointrwdData) > 0) {
                                    foreach ($pointrwdData as $val) {
                                        $prvalarr = str_getcsv($val['prval'] ?? '');
                                        $prvalstr = $prvalarr[0];

                                        if ($val['prtype'] == 'file') {
                                            $condition = " AND flid = '{$prvalstr}'";
                                            $rowData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_files WHERE 1 " . $condition . "");
                                            $prvalstr = $rowData[0]['flname'];
                                        } else if ($val['prtype'] == 'page') {
                                            $condition = " AND pgid = '{$prvalstr}'";
                                            $rowData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_pages WHERE 1 " . $condition . "");
                                            $prvalstr = $rowData[0]['pgtitle'];
                                        } else if ($val['prtype'] == 'cash') {
                                            $prvalstr = $bpprow['currencysym'] . $prvalstr;
                                        } else if ($val['prtype'] == 'custom') {
                                            $prvalstr = $prvalstr;
                                        }

                                        $primage = ($val['primage']) ? $val['primage'] : DEFIMG_FILE;
                                        if ($val['prminpoints'] > $mbrstr['epoint']) {
                                            $pointclr = 'text-muted';
                                            $pointbtn = ' disabled';
                                            $pointicn = 'times text-warning';
                                        } else {
                                            $pointclr = 'text-success';
                                            $pointbtn = '';
                                            $pointicn = 'arrow-right';
                                        }
                                        ?>
                                        <tr>

                                            <th class='text-center' scope="row">
                                                <img src='<?php echo myvalidate($primage); ?>' alt="<?php echo myvalidate($val['prname']); ?>" width="128" class="shadow-light<?php echo myvalidate($weblogo_style); ?>">
                                            </th>
                                            <td class='text-small'>
                                                <div class="<?php echo myvalidate($pointclr); ?>"><i class="fa fa-gem"></i> <span class='lead'><?php echo intval($val['prminpoints']); ?></span></div>                                                <?php echo myvalidate($LANG['g_pointexchangeto'] . ' ' . $prtypeopt_array[$val['prtype']]); ?>
                                                <div class='text-info'><?php echo myvalidate($prvalstr); ?></div>
                                            </td>
                                            <td class='text-small'>
                                                <div class='font-weight-bold'><?php echo myvalidate($val['prname']); ?></div>
                                                <div><?php echo myvalidate($val['prinfo']); ?></div>
                                            </td>
                                            <td align="center" nowrap>
                                                <a href="exchpointrwd.php?exchId=<?php echo myvalidate($val['prid']); ?>&redir=dashboard&mdlhashy=<?php echo myvalidate($mdlhashy); ?>" class="btn btn-sm btn-info<?php echo myvalidate($pointbtn); ?>"><i class="fa fa-fw fa-<?php echo myvalidate($pointicn); ?>"></i> <?php echo myvalidate($LANG['g_select']); ?></a>
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
                                                    <i class="fa fa-3x text-danger fa-exclamation"></i>
                                                </div>
                                                <div class="mb-4"><?php echo myvalidate($LANG['g_pointexchangerwdnone']); ?></div>
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
                    <?php
                }
                ?>
                <div class="text-md-center mt-4">
                    <a href="javascript:;" class="btn btn-warning" data-dismiss="modal"><i class="fa fa-fw fa-times"></i> Cancel</a>
                </div>
            </div>
            <?php
        }
        ?>

    </div>

</div>
