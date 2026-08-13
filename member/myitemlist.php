<?php
if (!defined('OK_LOADME')) {
    die('o o p s !');
}

if ($stcrow['stcvendoron'] != 1 || $mbrstr['isvendor'] != 1) {
    $hal = 'dashboard';
    redirpageto('index.php?hal=' . $hal);
    die();
}

$FORM = do_eskep($FORM);

if ($FORM['dohal'] == 'clear') {
    $_SESSION['filterid'] = '';
    redirpageto('index.php?hal=myitemlist');
    exit;
}
if ($FORM['dohal'] == 'filter' && $FORM['doval']) {
    $_SESSION['filterid'] = $FORM['doval'];
}

if (isset($FORM['dosId']) && isset($FORM['act']) && isset($FORM['shash'])) {
    if ($FORM['shash'] == md5($FORM['dosId'] . date("d") . $cfgrow['lichash'])) {
        $itid = intval($FORM['dosId']);
        $itstatus = intval($FORM['act']);
        $update = $db->update(DB_TBLPREFIX . '_items', array('itstatus' => $itstatus), array('itid' => $itid));
    }
    redirpageto('index.php?hal=myitemlist');
    exit;
}

$condition = " AND itidmbr = '{$mbrstr['id']}'";

if (isset($FORM['itname']) and $FORM['itname'] != "") {
    $condition .= ' AND itname LIKE "%' . $FORM['itname'] . '%" ';
}
if (isset($FORM['itdescr']) and $FORM['itdescr'] != "") {
    $condition .= ' AND itdescr LIKE "%' . $FORM['itdescr'] . '%" ';
}
if (isset($FORM['itkeywords']) and $FORM['itkeywords'] != "") {
    $condition .= ' AND (ittoken LIKE "%' . $FORM['itkeywords'] . '%" OR itkeywords LIKE "%' . $FORM['itkeywords'] . '%") ';
}

$tblshort_arr = array("itdatetm", "itname", "itprice");
$tblshort = dborder_arr($tblshort_arr, $FORM['_stbel'], $FORM['_stype']);
if ($FORM['_stbel'] != '' && (in_array($FORM['_stbel'], $tblshort_arr))) {
    $sqlshort = ($FORM['_stype'] == 'up') ? " ORDER BY {$FORM['_stbel']} DESC " : " ORDER BY {$FORM['_stbel']} ASC ";
} else {
    $sqlshort = " ORDER BY itid DESC ";
}

//Main queries
$sql = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_items WHERE 1 " . $condition . "");
$pages->items_total = count($sql);
$pages->mid_range = 3;
$pages->paginate();

$totmyitem = $pages->items_total;
$isnewitem = ($totmyitem < $stcrow['stcvendormaxitem']) ? 1 : 0;

$userData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_items WHERE 1 " . $condition . $sqlshort . $pages->limit . "");
?>

<div class="section-header">
    <h1><i class="fa fa-fw fa-box"></i> <?php echo myvalidate($LANG['m_itemlist']); ?></h1>
</div>

<div class="section-body">

    <form method="get">
        <div class="card card-primary">
            <div class="card-header">
                <h4>
                    <i class="fa fa-fw fa-search"></i> Find Product
                </h4>
            </div>
            <div class="card-body">
                <div class="row">

                    <div class="col-sm-4">
                        <div class="form-group">
                            <label><?php echo myvalidate($LANG['g_transactionid']); ?></label>
                            <input type="text" name="itname" id="itname" class="form-control" value="<?php echo isset($FORM['itname']) ? $FORM['itname'] : '' ?>" placeholder="Product name">
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <div class="form-group">
                            <label><?php echo myvalidate($LANG['g_description']); ?></label>
                            <input type="text" name="itdescr" id="itdescr" class="form-control" value="<?php echo isset($FORM['itdescr']) ? $FORM['itdescr'] : '' ?>" placeholder="Product description">
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <div class="form-group">
                            <label><?php echo myvalidate($LANG['g_keyword']); ?></label>
                            <input type="itkeywords" name="itkeywords" id="itkeywords" class="form-control" value="<?php echo isset($FORM['itkeywords']) ? $FORM['itkeywords'] : '' ?>" placeholder="Enter product keyword">
                        </div>
                    </div>

                </div>
            </div>
            <div class="card-footer bg-whitesmoke">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="float-md-right">
                            <a href="index.php?hal=myitemlist&dohal=clear" class="btn btn-warning"><i class="fa fa-fw fa-redo"></i> Clear</a>
                            <button type="submit" name="submit" value="search" id="submit" class="btn btn-primary"><i class="fa fa-fw fa-search"></i> Search</button>
                        </div>
                        <div class="d-block d-sm-none">
                            &nbsp;
                        </div>
                        <?php
                        if ($isnewitem == 1) {
                            ?>
                            <div>
                                <a href="javascript:;" data-href="mydoitem.php?redir=myitemlist&mdlhashy=<?php echo myvalidate($mdlhashy); ?>" data-poptitle="<i class='fa fa-fw fa-check'></i> New Product" class="openPopup btn btn-secondary"><i class="fa fa-fw fa-plus"></i> New Item</a>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
            <div class="clearfix"></div>
        </div>
        <input type="hidden" name="hal" value="myitemlist">
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
                    <th scope="col" nowrap><?php echo myvalidate($tblshort['itdatetm']); ?>Date</th>
                    <th scope="col" nowrap><?php echo myvalidate($tblshort['itname']); ?>Name</th>
                    <th scope="col" nowrap><?php echo myvalidate($tblshort['itprice']); ?>Amount</th>
                    <th scope="col" nowrap></th>
                    <th scope="col" class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (count($userData) > 0) {
                    $s = $pages->get_disponpage($FORM);
                    foreach ($userData as $val) {
                        $s++;

                        switch ($val['itstatus']) {
                            case "1":
                                $btnstatustext = "Enable";
                                $btnstatuscolor = "success";
                                break;
                            default:
                                $btnstatustext = "Disable";
                                $btnstatuscolor = "secondary";
                        }

                        $payintervalstr = $rangeinterval_array[$val['itexpinval']];

                        $itdescrnotag = strip_tags($val['itdescr'] ?? '');
                        $itdescrstr = substr($itdescrnotag, 0, 141);
                        $itdescrstrmore = (strlen($itdescrnotag ?? '') > 141) ? $itdescrstr . '...' : $itdescrstr;

                        $shash = md5($val['itid'] . date("d") . $cfgrow['lichash']);
                        $overview = "<label>Info</label><div>" . $val['adminfo'] . "</div>";
                        $itimage = ($val['itimage']) ? $val['itimage'] : DEFIMG_FILE;
                        ?>
                        <tr>

                            <th scope="row"><?php echo myvalidate($s); ?></th>
                            <td data-toggle="tooltip" title="<?php echo myvalidate($val['itdatetm']); ?>"><?php echo formatdate($val['itdatetm']); ?></td>
                            <td><?php echo ($val['itname']) ? myvalidate($val['itname']) : '-'; ?></td>
                            <td data-toggle="tooltip" title="<?php echo myvalidate($payintervalstr); ?>" class="text-right"><?php echo myvalidate($val['itprice']); ?></td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <?php
                                    if ($val['itstatus'] == 13) {
                                        ?>
                                        <span class="badge badge-light"><i class="fa fa-exclamation-circle text-danger fa-fw"></i> Suspended</span>
                                        <?php
                                    } else {
                                        ?>
                                        <button type="button" class="btn btn-<?php echo myvalidate($btnstatuscolor); ?> btn-sm openPopup" data-href="mydoitem.php?editId=<?php echo myvalidate($val['itid']); ?>&redir=myitemlist&mdlhashy=<?php echo myvalidate($mdlhashy); ?>" data-poptitle="<i class='fa fa-fw fa-edit'></i> Update Product" data-toggle="tooltip" title="Update <?php echo myvalidate($val['itname']); ?>"><i class="fa fa-fw fa-edit"></i> <?php echo myvalidate($btnstatustext); ?></button>
                                        <button type="button" class="btn btn-<?php echo myvalidate($btnstatuscolor); ?> btn-sm dropdown-toggle dropdown-toggle-split" data-toggle="dropdown">
                                            <span class="sr-only">Status</span>
                                        </button>
                                        <div class="dropdown-menu">
                                            <?php
                                            if ($val['itstatus'] <= 2 || ($stcrow['stcvendordoact'] == 1 && $val['itstatus'] == 9)) {
                                                ?>
                                                <a class="dropdown-item" href="index.php?hal=myitemlist&dosId=<?php echo myvalidate($val['itid']); ?>&act=0&shash=<?php echo myvalidate($shash); ?>"><i class="fa fa-times fa-fw"></i> Disable</a>
                                                <a class="dropdown-item" href="index.php?hal=myitemlist&dosId=<?php echo myvalidate($val['itid']); ?>&act=1&shash=<?php echo myvalidate($shash); ?>"><i class="fa fa-check fa-fw"></i> Enable</a>
                                                <?php
                                            } else if ($val['itstatus'] == 9 || $val['itstatus'] <= 2) {
                                                ?>
                                                <span class="dropdown-item"><i class="fa fa-info-circle fa-fw"></i> Waiting approval</span>
                                                <?php
                                            } else {
                                                ?>
                                                <span class="dropdown-item"><i class="fa fa-info-circle fa-fw"></i> <?php echo myvalidate($itemstatus_array[$val['itstatus']]); ?></span>
                                                <?php
                                            }
                                            ?>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </td>
                            <td align="center" nowrap>
                                <a href="javascript:;"
                                   class="btn btn-sm btn-secondary"
                                   data-html="true"
                                   data-toggle="popover"
                                   data-trigger="hover"
                                   data-placement="left" 
                                   title="<?php echo myvalidate($val['txtmstamp']); ?>"
                                   data-content="<h6><?php echo myvalidate($val['itname']); ?></h6><div><img src='<?php echo myvalidate($itimage); ?>' class='mr-3 rounded' width='128'></div><div class='mt-2'><?php echo myvalidate($itdescrstrmore); ?></div><div class='mt-2 text-info'><?php echo myvalidate($val['itkeywords']); ?></div>">
                                    <i class="far fa-fw fa-question-circle"></i>
                                </a>
                                <?php
                                if ($isnewitem == 1) {
                                    ?>
                                    <a href="javascript:;" data-href="mydoitem.php?dupId=<?php echo myvalidate($val['itid']); ?>&redir=myitemlist&mdlhashy=<?php echo myvalidate($mdlhashy); ?>" class="btn btn-sm btn-info openPopup" data-poptitle="Duplicate Product: <?php echo myvalidate($val['itname']); ?>" data-toggle="tooltip" title="Duplicate <?php echo myvalidate($val['itname']); ?>"><i class="far fa-fw fa-copy"></i></a>
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
