<?php
include_once('../common/init.loader.php');

if ($cfgrow['site_status'] != 1) {
    redirpageto($cfgrow['site_url'] . '/' . MBRFOLDER_NAME . '/offline.php');
    exit;
}

$seskey = verifylog_sess('member');
if ($seskey != '') {
    $sesRow = getlog_sess($seskey);
    $username = get_optionvals($sesRow['sesdata'], 'un');

    // Get member details
    $mbrstr = getmbrinfo($username, 'username');
}

$goglacode = base64_decode($cfgtoken['goglacode'] ?? '');

$sortbylist = '<option value="">- Sort by (Default)</option>';
$sortbyarr = array('entrynew' => 'Latest', 'entryold' => 'Oldest', 'view' => 'Popularity');
foreach ($sortbyarr as $key => $value) {
    $strsel = ($key == ($FORM['sortby'] ?? null)) ? ' selected' : '';
    $sortbylist .= "<option value='{$key}'{$strsel}>{$value}</option>";
}

$itgrid = intval($FORM['grid'] ?? null);
$groupcatlist = '<option value="">- Category (All)</option>';
$row = $db->getAllRecords(DB_TBLPREFIX . '_groups', '*', " AND grtype = 'item'");
$grouprow = array();
foreach ($row as $value) {
    $grouprow = array_merge($grouprow, $value);
    $strsel = ($grouprow['grid'] == $itgrid) ? ' selected' : '';
    $groupcatlist .= "<option value='{$grouprow['grid']}'{$strsel}>{$grouprow['grtitle']}</option>";
}

$thisyear = date("Y");
$site_subname = ($cfgtoken['site_subname'] != '') ? "<a href='{$cfgrow['site_url']}'>{$cfgtoken['site_subname']}</a>" : "<a href='{$ssysout('SSYS_URL')}/id/{$cfgrow['envacc']}' target='_blank'>{$cfgrow['site_name']}</a>";
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="<?php echo myvalidate($LANG['lang_charset']); ?>">
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
        <title><?php echo myvalidate($cfgrow['site_name']); ?></title>

        <meta name="description" content="<?php echo myvalidate($cfgrow['site_descr']); ?>">
        <meta name="keywords" content="<?php echo myvalidate($cfgrow['site_keywrd']); ?>">
        <meta name="author" content="<?php echo myvalidate($ssysout('SSYS_AUTHOR')); ?>">

        <!-- General CSS Files -->
        <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
        <link rel="stylesheet" href="../assets/fellow/fontawesome5121/css/all.min.css">

        <link rel="shortcut icon" type="image/png" href="../assets/image/favicon.png"/>

        <!-- CSS Libraries -->
        <link rel="stylesheet" href="../assets/css/pace-theme-minimal.css">
        <link rel="stylesheet" href="../assets/css/pslightbox.css">

        <!-- Template CSS -->
        <link rel="stylesheet" href="../assets/css/fontmuli.css">

        <?php echo myvalidate($stylecolor_css); ?>

        <?php echo myvalidate($goglacode); ?>

    </head>

    <body>
        <div id="app">
            <div class="main-wrapper">
                <div class="navbar-bg"></div>

                <!-- Main Content -->
                <div class="container">
                    <section class="section">
                        <div class="section-header">
                            <h1><?php echo myvalidate($cfgrow['site_name']); ?></h1>
                            <div class="section-header-breadcrumb">
                                <div class="breadcrumb-item active"><a href="<?php echo myvalidate($cfgrow['site_url']); ?>">Home</a></div>
                                <div class="breadcrumb-item active"><a href="<?php echo myvalidate($cfgrow['site_url'] . '/' . MBRFOLDER_NAME . '/register.php'); ?>">Register</a></div>
                                <div class="breadcrumb-item"><a href="<?php echo myvalidate($cfgrow['site_url'] . '/' . MBRFOLDER_NAME . '/'); ?>">Login</a></div>
                            </div>
                        </div>

                        <div class="section-body">
                            <div class="row">

                                <div class="col-12">
                                    <form method="get" action="index.php" id="catalog">
                                        <div class="form-group">
                                            <div class="input-group">
                                                <select name="sortby" class="custom-select">
                                                    <?php echo myvalidate($sortbylist); ?>
                                                </select>
                                                <select name="grid" class="custom-select">
                                                    <?php echo myvalidate($groupcatlist); ?>
                                                </select>
                                                <input type="text" name="find" class="form-control" value="<?php echo myvalidate($FORM['find'] ?? null); ?>">
                                                <button class="btn btn-primary" type="submit"><i class="fas fa-search fa-fw"></i> <?php echo myvalidate($LANG['g_search']); ?></button>
                                                <div class="input-group-append">
                                                    <button class="btn btn-danger" type="button" onclick="location.href = 'index.php'" data-toggle="tooltip" title="<?php echo myvalidate($LANG['g_reset']); ?>"><i class="fas fa-times fa-fw"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <?php
                                $itscount = 0;
                                $condition = $itsuserlist = '';

                                if ($sesref['mpid'] > 0) {
                                    $itmpidarr = ["mpid  = '" . intval($sesref['mpid']) . "'"];
                                    $sprlist = str_replace(array(' ', '|'), '', $sesref['sprlist']);
                                    $sprlistarr = explode(',', $sprlist);
                                    foreach ($sprlistarr as $key => $value) {
                                        $valarr = explode(':', $value);
                                        $itmpidarr[] = "mpid  = '" . intval($valarr[1]) . "'";
                                    }
                                    $itmpidarr = array_unique($itmpidarr);
                                    $itmpidsql = (count($itmpidarr) > 0) ? "(" . implode(' OR ', $itmpidarr) . ")" : '1';
                                    $condition1 = " AND {$itmpidsql}";
                                    $mbrData = $db->getRecFrmQry("SELECT idmbr FROM " . DB_TBLPREFIX . "_mbrplans WHERE 1 " . $condition1);
                                    $itidmbrarr = [];
                                    foreach ($mbrData as $key => $value) {
                                        $itidmbrarr[] = "itidmbr = '" . $value['idmbr'] . "'";
                                    }
                                    $itidmbrsql = (count($itidmbrarr) > 0) ? "(" . implode(' OR ', $itidmbrarr) . ")" : '1';
                                    $condition = " AND {$itidmbrsql}";
                                }

                                if ($cfgtoken['isincladmitem'] == '1') {
                                    $condition .= " OR (itidmbr = '0')";
                                }

                                $condition .= " AND itid > '1' AND itprice > '0' AND itstock >= '0' AND itimage != ''";
                                $find = mystriptag($FORM['find'] ?? '');
                                $condition .= ($find != '') ? " AND (itname LIKE '%{$find}%' OR itdescr LIKE '%{$find}%' OR itkeywords LIKE '%{$find}%')" : " AND itname != ''";
                                $condition .= ($itgrid > 0) ? " AND itgrid = '{$itgrid}'" : '';

                                $sortbyrule = array('entrynew' => 'itdatetm DESC', 'entryold' => 'itdatetm ASC', 'view' => 'itview DESC');
                                $orderbysql = ($FORM['sortby'] != '' && $sortbyrule[$FORM['sortby']] != '') ? $sortbyrule[$FORM['sortby']] : "RAND()";
                                $condition .= " ORDER BY {$orderbysql} LIMIT 50";
                                $row = $db->getAllRecords(DB_TBLPREFIX . '_items LEFT JOIN ' . DB_TBLPREFIX . '_groups ON itgrid = grid', '*', $condition);
                                $pgcntrow = array();
                                foreach ($row as $value) {

                                    if ($value['itstatus'] != 1) {
                                        continue;
                                    }

                                    $itshash = md5($value['id'] . $value['itid'] . date("dh"));
                                    $itsimgstr = ($value['itimage']) ? $value['itimage'] : DEFIMG_FILE;

                                    $itsdesc = (strlen($value['itdescr'] ?? '') > 196) ? substr($value['itdescr'] ?? '', 0, 196) . '...' : $value['itdescr'];

                                    $ithash = md5($mdlhashy . $value['itid'] . '+' . $value['itstatus'] . $mbrstr['id']);
                                    $itsbuynow = "../" . MBRFOLDER_NAME . "/index.php?hal=orderpay&l={$ithash}&itid={$value['itid']}";

                                    $itpricenow = get_itpricebyplan($value, $mbrstr['mppid']);
                                    $itshits = $value['itview'];

                                    $itsuserlist .= <<<INI_HTML
    <div class="col-sm-12 col-md-6">
        <div class="card card-large-icons w-100">
            <div class="card-icon bg-light text-white mx-auto text-center">
                <a href="#itimg{$itshash}">
                    <img src='{$itsimgstr}' alt='[^-^]' class='mr-3 img-fluid rounded mx-auto d-block' width='{$cfgrow['mbrmax_image_width']}' height='{$cfgrow['mbrmax_image_height']}'>
                </a>
                <a href="#" class="pslightbox" id="itimg{$itshash}">
                    <div style="background-image:url('{$itsimgstr}')"></div>
                </a>
            </div>
            <div class="card-body">
                <h4>{$value['itname']}</h4>
                <h6><span class="badge badge-info">{$value['grtitle']}</span></h6>
                <div class='mb-2'>
                <p>{$itsdesc}</p>
                </div>
                <div class='text-right'>
                <hr />
                <span class="text-secondary">{$bpprow['currencysym']}{$itpricenow} {$bpprow['currencycode']}</span>&nbsp;
                <a href="javascript:;" onclick="location.href = '{$itsbuynow}'" class="btn btn-sm btn-primary"><i class="fas fa-shopping-bag"></i> {$LANG['g_ordernow']}</a>
                </div>
            </div>
        </div>
    </div>
INI_HTML;
                                    $itscount++;
                                }

                                if ($itscount < 1) {
                                    $itsuserlist = <<<INI_HTML
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="empty-state">
                        <div class="empty-state-icon bg-info">
                            <i class="fas fa-unlink"></i>
                        </div>
                        <h2>{$LANG['g_norecordinfo']}</h2>
                        <p class="lead">
                            {$LANG['g_norecordgen']}
                        </p>
                    </div>
                </div>
            </div>
        </div>
INI_HTML;
                                }

                                echo myvalidate($itsuserlist);
                                ?>

                            </div>
                        </div>
                    </section>
                </div>
                <div class="simple-footer">
                    <!--
                    You are not allowed to remove this credit link unless you have right to do so
                    -->
                    <div class="text-small"><?php echo myvalidate($LANG['g_builtwith']); ?> <i class="fa fa-fw fa-heart"></i> <?php echo "{$thisyear} {$site_subname}{$cfgrow['_isnocreditstr']}"; ?>
                    </div>

                </div>      
            </div>
        </div>

        <!-- General JS Scripts -->
        <script src="../assets/js/jquery-3.4.1.min.js"></script>
        <script src="../assets/js/popper.min.js"></script>
        <script src="../assets/js/bootstrap.min.js"></script>
        <script src="../assets/js/jquery.nicescroll.min.js"></script>
        <script src="../assets/js/moment.min.js"></script>
        <script src="../assets/js/pace.min.js"></script>

        <!-- JS Libraies -->
        <script src="../assets/js/stisla.js"></script>


        <!-- Template JS File -->
        <script src="../assets/js/scripts.js"></script>
        <!--script src="../assets/js/custom.js"></script-->

        <!-- Page Specific JS File -->
    </body>
</html>
