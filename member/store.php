<?php
if (!defined('OK_LOADME')) {
    die('o o p s !');
}

if ($mbrstr['mpstatus'] != 1) {
    $hal = 'dashboard';
    redirpageto('index.php?hal=' . $hal);
    die();
}

$FORM = do_eskep($FORM);

$itgrid = intval($FORM['grid']);

$groupcatlist = '<option value="">-</option>';
$row = $db->getAllRecords(DB_TBLPREFIX . '_groups', '*', " AND grtype = 'item'");
$grouprow = array();
foreach ($row as $value) {
    $grouprow = array_merge($grouprow, $value);
    $strsel = ($grouprow['grid'] == $itgrid) ? ' selected' : '';
    $groupcatlist .= "<option value='{$grouprow['grid']}'{$strsel}>{$grouprow['grtitle']}</option>";
}

$sqlitgrid = ($itgrid > 0) ? " AND itgrid = '{$itgrid}'" : '';

$kwrds = mystriptag($FORM['kwrds']);
$condition = ($FORM['kwrds'] != '') ? " AND (itname LIKE '%{$kwrds}%' OR itdescr LIKE '%{$kwrds}%' OR itkeywords LIKE '%{$kwrds}%')" : '';

$itcount = 0;
$itemlist = '';
$row = $db->getAllRecords(DB_TBLPREFIX . '_items', '*', " AND itname != '' AND itstatus = '1'{$sqlitgrid}{$condition} ORDER BY itsort, itdatetm DESC, itid DESC");
$pgcntrow = array();
foreach ($row as $value) {

    if ($value['itid'] == 1) {
        // if item for deposit fund
        continue;
    }

    $ithash = md5($mdlhashy . $value['itid'] . '+' . $value['itstatus'] . $mbrstr['id']);
    $itimagestr = ($value['itimage']) ? $value['itimage'] : DEFIMG_FILE;

    $itdescrnotag = strip_tags($value['itdescr'] ?? '');
    $itdescrstr = substr($itdescrnotag ?? '', 0, 141);
    $itdescrstrmore = (strlen($itdescrnotag ?? '') > 141) ? $itdescrstr . '...' : $itdescrstr;

    $itpricenow = get_itpricebyplan($value, $mbrstr['mppid']);
    $defPrice = ($value['itprice'] > 0 && $value['itprice'] > $itpricenow) ? "<span class='text-small text-danger'><s>{$bpprow['currencysym']}{$value['itprice']}</s></span><br />" : '';
    $payintervalstr = $rangeinterval_array[$value['itexpinval']];

    $itbuynow = "index.php?hal=orderpay&l={$ithash}&itid={$value['itid']}";
    $itembtnmore = "<a href='javascript:;' data-href='storeitem.php?itemId={$value['itid']}&mdlhashy={$mdlhashy}' class='btn btn-sm btn-outline-info openPopup' data-toggle='tooltip' title='Details {$value['itname']}' data-poptitle='Product Descriptions'><i class='far fa-fw fa-question-circle'></i></a>";

    $itemprices = ($value['itid'] > 1) ? "{$defPrice}{$bpprow['currencysym']}{$itpricenow} {$bpprow['currencycode']} <span class='text-small text-muted'>{$payintervalstr}</span>" : '';

    $itembuttons = ($value['itid'] > 1) ? "{$itembtnmore} <a href='javascript:;' onclick=\"location.href = '{$itbuynow}'\" class='btn btn-sm btn-primary' data-toggle='tooltip' title='{$LANG['g_ordernow']}'>{$LANG['g_ordernow']}</a>" : "<a href='javascript:;' data-href='addfund.php?mdlhashy={$mdlhashy}' data-poptitle=\"<i class='fa fa-fw fa-cash-register'></i> {$LANG['m_addfund']}\" class='btn btn-sm btn-primary openPopup'>{$LANG['m_addfund']}</a>";

    $itemlist .= <<<INI_HTML
    <link rel="stylesheet" href="../assets/css/pslightbox.css">
    <div class="col-sm-12 col-md-6">
        <div class="card card-large-icons w-100">
            <div class="card-icon bg-light text-white">
                &nbsp; <a href="#itimg{$ithash}"><img src='{$itimagestr}' alt='{$value['itname']}' class='mr-3 img-fluid img-thumbnail rounded mx-auto d-block' width='{$cfgrow['mbrmax_image_width']}' height='{$cfgrow['mbrmax_image_height']}'></a>
                <a href="#" class="pslightbox" id="itimg{$ithash}">
                    <div style="background-image:url('{$itimagestr}')"></div>
                </a>
            </div>
            <div class="card-body">
                <h4>{$value['itname']}</h4>
                <p>{$itdescrstrmore}</p>
                <div class="mt-2 mb-4">
                    {$itemprices}
                </div>
                <div class="text-right">
                        {$itembuttons}
                </div>
            </div>
        </div>
    </div>
INI_HTML;
    $itcount++;
}

if ($itcount < 1) {
    $itemlist = <<<INI_HTML
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="empty-state">
                        <div class="empty-state-icon bg-info">
                            <i class="fas fa-question"></i>
                        </div>
                        <h2>{$LANG['m_noitem']}</h2>
                        <p class="lead">
                            {$LANG['m_noitemnote']}
                        </p>
                    </div>
                </div>
            </div>
        </div>
INI_HTML;
}
?>

<div class="section-header">
    <h1><i class="fa fa-fw fa-store"></i> <?php echo myvalidate($LANG['m_store']); ?></h1>
</div>


<div class="section-header">
    <div class="row w-100">
        <div class="col-md-6">
            <form method="get" action="index.php" id="store">
                <div class="form-group">
                    <input type="hidden" name="kwrds" value="<?php echo myvalidate($kwrds); ?>">
                    <input type="hidden" name="hal" value="store">
                    <div class="input-group">
                        <select name="grid" class="custom-select">
                            <?php echo myvalidate($groupcatlist); ?>
                        </select>
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit"><i class="fas fa-arrow-right fa-fw"></i></button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="col-md-6">
            <form method="get" action="index.php" id="store">
                <div class="form-group">
                    <input type="hidden" name="grid" value="<?php echo myvalidate($FORM['grid']); ?>">
                    <input type="hidden" name="hal" value="store">
                    <div class="input-group">
                        <input name="kwrds" class="form-control" value="<?php echo myvalidate($FORM['kwrds']); ?>">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit"><i class="fas fa-search fa-fw"></i></button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="section-body">
    <div class="row">
        <?php echo myvalidate($itemlist); ?>
    </div>
</div>
