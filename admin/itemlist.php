<?php
if (!defined('OK_LOADME')) {
    die('o o p s !');
}

$FORM = do_eskep($FORM);

if ($FORM['dohal'] == 'clear') {
    $_SESSION['filterid'] = '';
    redirpageto('index.php?hal=itemlist');
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
    redirpageto('index.php?hal=itemlist');
    exit;
}

$condition = "";

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

// filter by item owner
if ($FORM['itmfltrby'] != 'all') {
    $_SESSION['itmfltrby'] = $FORM['itmfltrby'];
} else {
    $_SESSION['itmfltrby'] = '';
}

if ($_SESSION['itmfltrby'] != '') {
    if ($_SESSION['itmfltrby'] == 'vendor') {
        $condition .= " AND itidmbr > '0'";
        $itmfltrbystr = 'Vendor Only';
        $itmfltrbyinfo = 'Display product by vendor';
    } else {
        $condition .= " AND itidmbr < '1'";
        $itmfltrbystr = 'Company Only';
        $itmfltrbyinfo = 'Display product by company';
    }
} else {
    $itmfltrbystr = 'All Products';
    $itmfltrbyinfo = 'Display all products';
}

//Main queries
$sql = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_items WHERE 1 " . $condition . "");
$pages->items_total = count($sql);
$pages->mid_range = 3;
$pages->paginate();

$itemData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_items WHERE 1 " . $condition . $sqlshort . $pages->limit . "");
?>

<div class="section-header">
    <h1><i class="fa fa-fw fa-boxes"></i> <?php echo myvalidate($LANG['a_itemlist']); ?></h1>
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
                            <label><?php echo myvalidate($LANG['g_product']); ?></label>
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
                            <div class="btn-group">
                                <button type="button" class="btn btn-info" data-toggle="tooltip" title="Update <?php echo myvalidate($itmfltrbyinfo); ?>"><i class="fa fa-fw fa-check"></i> <?php echo myvalidate($itmfltrbystr); ?></button>
                                <button type="button" class="btn btn-info dropdown-toggle dropdown-toggle-split" data-toggle="dropdown">
                                    <span class="sr-only">Status</span>
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="index.php?hal=itemlist&itmfltrby=all"><i class="fa fa-boxes fa-fw"></i> All</a>
                                    <a class="dropdown-item" href="index.php?hal=itemlist&itmfltrby=admin"><i class="fa fa-box-open fa-fw"></i> by Company Only</a>
                                    <a class="dropdown-item" href="index.php?hal=itemlist&itmfltrby=vendor"><i class="fa fa-box fa-fw"></i> by Vendor Only</a>
                                </div>
                            </div>

                            <a href="index.php?hal=itemlist&dohal=clear" class="btn btn-warning"><i class="fa fa-fw fa-redo"></i> Clear</a>
                            <button type="submit" name="submit" value="search" id="submit" class="btn btn-primary"><i class="fa fa-fw fa-search"></i> Search</button>
                        </div>
                        <div class="d-block d-sm-none">
                            &nbsp;
                        </div>
                        <div>
                            <a href="javascript:;" data-href="doitem.php?redir=itemlist&mdlhasher=<?php echo myvalidate($mdlhasher); ?>" data-poptitle="<i class='fa fa-fw fa-check'></i> New Product" class="openPopup btn btn-secondary"><i class="fa fa-fw fa-plus"></i> New Item</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="clearfix"></div>
        </div>
        <input type="hidden" name="hal" value="itemlist">
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
                if (count($itemData) > 0) {
                    $s = $pages->get_disponpage($FORM);
                    foreach ($itemData as $val) {
                        $s++;
                        $hasdel = md5($val['itid'] . date("dH"));

                        $itdatetmstr = ($val['itid'] > 1) ? $val['itdatetm'] : $cfgrow['datetimestr'];

                        switch ($val['itstatus']) {
                            // 0=disable, 1=enable for all, 2=enable for member only, 7=hidden, 8=archive, 9=waiting approval, 13=suspend
                            case "1":
                                $btnstatustext = "Enable";
                                $btnstatuscolor = "success";
                                break;
                            case "2":
                                $btnstatustext = "Private";
                                $btnstatuscolor = "success";
                                break;
                            case "7":
                                $btnstatustext = "Hidden";
                                $btnstatuscolor = "secondary";
                                break;
                            case "8":
                                $btnstatustext = "Archived";
                                $btnstatuscolor = "light";
                                break;
                            case "9":
                                $btnstatustext = "Waiting";
                                $btnstatuscolor = "info";
                                break;
                            case "13":
                                $btnstatustext = "Suspended";
                                $btnstatuscolor = "danger";
                                break;
                            default:
                                $btnstatustext = "Disable";
                                $btnstatuscolor = "secondary";
                        }

                        $itpricestr = ($val['itid'] <= 1) ? '-' : $val['itprice'];
                        $isfreestr = ($val['itprice'] == 0 && $val['itid'] > 1) ? "<sup><span class='text-info mr-1'>{$LANG['g_free']}</span></sup>" : '';

                        $payintervalstr = $rangeinterval_array[$val['itexpinval']];

                        $itdescrnotag = strip_tags($val['itdescr'] ?? '');
                        $itdescrstr = substr($itdescrnotag, 0, 141);
                        $itdescrstrmore = (strlen($itdescrnotag ?? '') > 141) ? $itdescrstr . '...' : $itdescrstr;

                        $shash = md5($val['itid'] . date("d") . $cfgrow['lichash']);
                        $overview = "<label>Info</label><div>" . $val['adminfo'] . "</div>";
                        $itimage = ($val['itimage']) ? $val['itimage'] : DEFIMG_FILE;

                        $vendorarr = getmbrinfo($val['itidmbr']);
                        $itidmbrusername = $vendorarr['username'];
                        ?>
                        <tr>

                            <th scope="row"><?php echo myvalidate($s); ?></th>
                            <td data-toggle="tooltip" title="<?php echo myvalidate($itdatetmstr); ?>"><?php echo formatdate($itdatetmstr); ?></td>
                            <td><?php echo ($val['itname']) ? myvalidate($val['itname']) : '-'; ?></td>
                            <td class="text-right"><?php echo myvalidate($isfreestr . $itpricestr); ?></td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-<?php echo myvalidate($btnstatuscolor); ?> btn-sm openPopup" data-href="doitem.php?editId=<?php echo myvalidate($val['itid']); ?>&redir=itemlist&mdlhasher=<?php echo myvalidate($mdlhasher); ?>" data-poptitle="<i class='fa fa-fw fa-edit'></i> Update Product" data-toggle="tooltip" title="Update <?php echo myvalidate($val['itname']); ?>"><i class="fa fa-fw fa-edit"></i> <?php echo myvalidate($btnstatustext); ?></button>
                                    <button type="button" class="btn btn-<?php echo myvalidate($btnstatuscolor); ?> btn-sm dropdown-toggle dropdown-toggle-split" data-toggle="dropdown">
                                        <span class="sr-only">Status</span>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="index.php?hal=itemlist&dosId=<?php echo myvalidate($val['itid']); ?>&act=0&shash=<?php echo myvalidate($shash); ?>"><i class="fa fa-times fa-fw"></i> Disable</a>
                                        <a class="dropdown-item" href="index.php?hal=itemlist&dosId=<?php echo myvalidate($val['itid']); ?>&act=1&shash=<?php echo myvalidate($shash); ?>"><i class="fa fa-check fa-fw"></i> Enable</a>
                                    </div>
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
                                   data-content="
                                   <h6><?php echo myvalidate($val['itid']); ?>.<?php echo myvalidate($val['itname']); ?></h6>
                                   <div class='text-small text-primary'>by <?php echo myvalidate($itidmbrusername); ?></div>
                                   <div><img src='<?php echo myvalidate($itimage); ?>' class='mr-3 rounded' width='128'></div>
                                   <div class='mt-2'><?php echo myvalidate($itdescrstrmore); ?></div>
                                   <div class='mt-2 text-info'><?php echo myvalidate($val['itkeywords']); ?></div>
                                   ">
                                    <i class="far fa-fw fa-question-circle"></i>
                                </a>
                                <?php
                                if ($val['itid'] > 1) {
                                    ?>
                                    <a href="javascript:;" data-href="doitem.php?dupId=<?php echo myvalidate($val['itid']); ?>&redir=itemlist&mdlhasher=<?php echo myvalidate($mdlhasher); ?>" class="btn btn-sm btn-info openPopup" data-poptitle="Duplicate Product: <?php echo myvalidate($val['itname']); ?>" data-toggle="tooltip" title="Duplicate <?php echo myvalidate($val['itname']); ?>"><i class="far fa-fw fa-copy"></i></a>
                                    <a href="javascript:;" data-href="doitem.php?hash=<?php echo myvalidate($hasdel); ?>&delId=<?php echo myvalidate($val['itid']); ?>&redir=itemlist&mdlhasher=<?php echo myvalidate($mdlhasher); ?>" class="btn btn-sm btn-danger bootboxconfirm" data-poptitle="Product ID: <?php echo myvalidate($val['itid']) . ' ' . myvalidate($val['itname']); ?>" data-popmsg="Are you sure want to delete this product?" data-toggle="tooltip" title="Delete <?php echo myvalidate($val['itname']); ?>"><i class="far fa-fw fa-trash-alt"></i></a>
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
