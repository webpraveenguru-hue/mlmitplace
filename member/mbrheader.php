<?php

if (!defined('OK_LOADME')) {
    die('o o p s !');
}

if ($cfgtoken['isgetstart'] != 1) {
    $menuactive['getstarted'] = " style='display: none;'";
}

if ($bpprow['maxwidth'] == 0 && $bptoken['isgenview'] == '1') {
    $menuactive['genealogyview'] = " style='display:none;'";
    if ($FORM['hal'] == 'genealogyview') {
        $pagefile = 'dashboard.php';
    }
}

$mbrimgfile = ($mbrstr['mbr_image']) ? $mbrstr['mbr_image'] : $cfgrow['mbr_defaultimage'];

// withdrawal
$mbrwithdraw_menu = '';
if ($cfgtoken['diswithdraw'] != '1') {
    $mbrwithdraw_menu = <<<INI_HTML
                            <li{$menuactive['withdrawreq']}><a class="nav-link" href="index.php?hal=withdrawreq"><i class="fas fa-hand-holding-usd"></i> <span>{$LANG['m_withdrawreq']}</span></a></li>
INI_HTML;
}

if ($mbrstr['mpid'] > 0) {
    $mbractive_menu = <<<INI_HTML
                            <li class="menu-header">{$LANG['g_menu_account']}</li>
                            <li{$menuactive['userlist']}><a class="nav-link" href="index.php?hal=userlist"><i class="fas fa-users"></i><span>{$LANG['m_userlist']}</span></a></li>
                            <li{$menuactive['historylist']}><a class="nav-link" href="index.php?hal=historylist"><i class="fas fa-cash-register"></i> <span>{$LANG['m_historylist']}</span></a></li>
                            {$mbrwithdraw_menu}
                            <li{$menuactive['genealogyview']}><a class="nav-link" href="index.php?hal=genealogyview"><i class="fas fa-sitemap"></i> <span>{$LANG['m_genealogyview']}</span></a></li>
INI_HTML;
}

// store menu/page
if (defined('ISLOADSTORE') && $mbrstr['mpstatus'] == 1) {
    if ($stctoken['stcismbrhide'] != '1') {
        $mbrstore_menu_items = <<<INI_HTML
                            <li{$menuactive['store']}><a class="nav-link" href="index.php?hal=store"><i class="fas fa-store"></i><span>{$LANG['m_store']}</span></a></li>
INI_HTML;
    } else {
        $mbrstore_menu_items = '';
        if ($FORM['hal'] == 'store') {
            $pagefile = 'dashboard.php';
        }
    }
    $mbrstore_menu = <<<INI_HTML
                            {$mbrstore_menu_items}
                            <li{$menuactive['orderlist']}><a class="nav-link" href="index.php?hal=orderlist"><i class="fas fa-shopping-basket"></i> <span>{$LANG['m_orderlist']}</span></a></li>
INI_HTML;
} else {
    $mbrstore_menu = '';
}

// vendor menu/page
if (defined('ISLOADSTORE') && $stcrow['stcvendoron'] == 1 && $mbrstr['isvendor'] == 1) {
    $mbrvendor_menu = <<<INI_HTML
                            <li class="menu-header">{$LANG['g_menu_vendor']}</li>
                            <li{$menuactive['mysaleslist']}><a class="nav-link" href="index.php?hal=mysaleslist"><i class="fas fa-fw fa-store-alt"></i> <span>{$LANG['m_saleslist']}</span></a></li>
                            <li{$menuactive['mydigicontent']}><a class="nav-link" href="index.php?hal=mydigicontent"><i class="fas fa-magic"></i><span>{$LANG['m_digicontent']}</span></a></li>
                            <li{$menuactive['myitemlist']}><a class="nav-link" href="index.php?hal=myitemlist"><i class="fas fa-box"></i><span>{$LANG['m_itemlist']}</span></a></li>
INI_HTML;
} else {
    $mbrvendor_menu = '';
}

// banner
$mbrbanner_menu = '';
if ($mbrstr['reflink'] != '') {
    $mbrbanner_menu = <<<INI_HTML
                            <li{$menuactive['mmbanner']}><a class="nav-link" href="index.php?hal=mmbanner"><i class="fas fa-puzzle-piece"></i><span>{$LANG['m_mmbanner']}</span></a></li>
INI_HTML;
}

$member_content = <<<INI_HTML
<!DOCTYPE html>
<html lang="{$LANG['lang_iso']}">
    <head>
        <meta charset="{$LANG['lang_charset']}">
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
        <title>{$cfgrow['site_name']} {$LANG['g_membercp']}</title>

        <meta name="description" content="{$cfgrow['site_descr']}">
        <meta name="keywords" content="{$cfgrow['site_keywrd']}">
        <meta name="author" content="{$ssysout('SSYS_AUTHOR')}">

        <link rel="shortcut icon" type="image/png" href="../assets/image/favicon.png"/>
        <link rel="icon" type="image/png" sizes="32x32" href="../assets/image/favicon.png"/>
        <link rel="icon" type="image/png" sizes="16x16" href="../assets/image/favicon.png"/>
        
        <!-- General CSS Files -->
        <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
        <link rel="stylesheet" href="../assets/fellow/fontawesome5121/css/all.min.css">

        <!-- CSS Libraries -->
        <link rel="stylesheet" href="../assets/css/pace-theme-minimal.css">
        <link rel="stylesheet" href="../assets/css/toastr.min.css">

        <!-- Template CSS -->
        <link rel="stylesheet" href="../assets/css/fontmuli.css">
        {$stylecolor_css}
        <link rel="stylesheet" href="../assets/css/custom.css">
	{$customdark_css}

        <!-- General JS Scripts -->
        <script src="../assets/js/jquery-3.4.1.min.js"></script>
        <script src="../assets/js/popper.min.js"></script>
        <script src="../assets/js/bootstrap.min.js"></script>
        <script src="../assets/js/jquery.nicescroll.min.js"></script>
        <script src="../assets/js/moment.min.js"></script>
        <script src="../assets/js/pace.min.js"></script>

        <!-- include summernote css/js -->
        <link href="../assets/css/summernote-bs4.css" rel="stylesheet">
        <script src="../assets/js/summernote-bs4.min.js"></script>

    </head>

    <body>
        <div id="app">
            <div class="main-wrapper">
                <div class="navbar-bg"></div>
                <nav class="navbar navbar-expand-lg main-navbar">
                    <div class="mr-auto">
                        <ul class="navbar-nav mr-3">
                            <li><a href="#" data-toggle="sidebar" class="nav-link nav-link-lg"><i class="fas fa-bars"></i></a></li>
                        </ul>
                    </div>
                    {$tplstr['demo_mode_warn']}{$tplstr['debug_mode_warn']}
                    <ul class="navbar-nav navbar-right">
                        <li class="dropdown dropdown-list-toggle">
                            <a href="#" data-toggle="dropdown" class="nav-link notification-toggle nav-link-lg">
                                <div class="d-block d-md-none badge badge-light"><span class="text-uppercase">{$LANG['lang_iso']}</span></div>
                                <div class="d-none d-md-block badge badge-light">{$translation_str}</div>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right">
                                <div class="dropdown-title">{$LANG['g_language']}</div>
                                {$langliststr}
                            </div>
                        </li>
                        
                        <li class="dropdown"><a href="#" data-toggle="dropdown" class="nav-link dropdown-toggle nav-link-lg nav-link-user">
                                <img alt="image" src="{$mbrimgfile}" class="rounded-circle mr-1">
                                <div class="d-sm-none d-lg-inline-block"><span class="text-capitalize">{$mbrstr['username']}</span></div></a>
                            <div class="dropdown-menu dropdown-menu-right">
                                <div class="dropdown-title">{$LANG['m_loggedin']} {$logtimeago}</div>
                                <a href="index.php?hal=accountcfg#cfgtab3" class="dropdown-item has-icon">
                                    <i class="far fa-user-circle"></i> {$LANG['g_profile']}
                                </a>
                                <div class="dropdown-divider"></div>
                                <a href="logout.php?un={$mbrstr['username']}" class="dropdown-item has-icon text-danger">
                                    <i class="fas fa-door-open"></i> {$LANG['g_menu_logout']}
                                </a>
                            </div>
                        </li>
                    </ul>
                </nav>
                <div id="sidebar-mbrmenu" class="main-sidebar">
                    <aside id="sidebar-wrapper">
                        <div class="sidebar-brand">
                            <a href="index.php">{$LANG['g_membercp']}</a>
                        </div>
                        <div class="sidebar-brand sidebar-brand-sm">
                            <a href="index.php">{$LANG['g_membercpinit']}</a>
                        </div>
                        <ul class="sidebar-menu">
                            <li class="menu-header">{$LANG['g_menu_main']}</li>
                            <li{$menuactive['dashboard']}><a class="nav-link" href="index.php?hal=dashboard"><i class="fas fa-chart-line"></i><span>{$LANG['g_dashboard']}</span></a></li>
                            <li{$menuactive['getstarted']}><a class="nav-link" href="index.php?hal=getstarted"><i class="fas fa-flag-checkered"></i><span>{$LANG['m_getstarted']}</span></a></li>
                            
                            {$mbrstore_menu}
                            {$mbractive_menu}

                            <li class="menu-header">{$LANG['g_menu_item']}</li>
                            <li{$menuactive['digiload']}><a class="nav-link" href="index.php?hal=digiload"><i class="fas fa-cloud-download-alt"></i> <span>{$LANG['m_digiload']}</span></a></li>
                            <li{$menuactive['digiview']}><a class="nav-link" href="index.php?hal=digiview"><i class="fas fa-window-restore"></i><span>{$LANG['m_digiview']}</span></a></li>
                            {$mbrbanner_menu}

                            {$mbrvendor_menu}

                            <li class="menu-header">{$LANG['g_menu_setting']}</li>
                            <li{$menuactive['accountcfg']}><a class="nav-link" href="index.php?hal=accountcfg"><i class="fas fa-user-cog"></i> <span>{$LANG['m_profilecfg']}</span></a></li>
                            <li{$menuactive['feedback']}><a class="nav-link" href="index.php?hal=feedback"><i class="fas fa-life-ring"></i><span>{$LANG['m_feedback']}</span></a></li>
                        </ul>

                        <div class="mt-4 mb-4 p-3 hide-sidebar-mini">
                            <a href="logout.php?un={$mbrstr['username']}" class="btn btn-danger btn-lg btn-block btn-icon-split">
                                <i class="fas fa-door-open"></i> {$LANG['g_menu_logout']}
                            </a>
                        </div>
                    </aside>
                </div>
INI_HTML;
echo myvalidate($member_content);
