<?php

/* INDEX_UNIMATRIX */
error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));
$cfgtoken = [];

include_once('common/config.php');
if (defined('INSTALL_PATH')) {
    include_once("common/init.loader.php");
}

$webpagefolder = "webpage";

if ($cfgtoken['homepg'] == 'redir' && $cfgtoken['homepgredir'] != '') {
    header("Location: " . base64_decode($cfgtoken['homepgredir'] ?? ''));
} else if ($cfgtoken['homepg'] == 'test') {
    $iswebbaseurl = 1;
    $websrcbasepath = "assets";
    $websrcpagepath = $webpagefolder . "/evolve2";
    $testpgcolor = ($cfgtoken['homepgtestclr']) ? '_' . $cfgtoken['homepgtestclr'] : '_default';
    $lpassetscolorstyle = "lpassets/lpstylegdbg{$testpgcolor}.css";
    $subpage = (file_exists("{$websrcpagepath}/home.php")) ? 'home' : ((file_exists("{$websrcpagepath}/_home.php")) ? '_home' : '');
    $websrcbaseurl = (isset($iswebbaseurl) && $iswebbaseurl == 1) ? "" : "../..";
    $websrcbasepath = (isset($websrcbasepath) && $websrcbasepath) ? $websrcbasepath : "../../assets";
    $websrcpagepath = (isset($websrcpagepath) && $websrcpagepath) ? $websrcpagepath : "";
    if ($FORM['pg'] != '') {
        $subpage = (file_exists($websrcpagepath . '/' . $FORM['pg'] . '.php')) ? $FORM['pg'] : $subpage;
    }
    if ($subpage != '') {
        $ref_sess_un = (isset($_SESSION['ref_sess_un']) && $_SESSION['ref_sess_un'] != '') ? $_SESSION['ref_sess_un'] : '[username]';
        $hpagecntsrc = file_get_contents("{$websrcpagepath}/{$subpage}.php");
        $arrfind = ['[[ref_sess_un]]', '[[site_name]]', '[[websrcbaseurl]]', '[[websrcbasepath]]', '[[websrcpagepath]]', '[[lpassetscolorstyle]]'];
        $arrrepl = [$ref_sess_un, $cfgrow['site_name'], $websrcbaseurl, $websrcbasepath, $websrcpagepath, $lpassetscolorstyle];
        $hpagecnt = str_replace($arrfind, $arrrepl, $hpagecntsrc);
        print $hpagecnt;
    } else {
        die("<pre>Default website file not found</pre>");
    }
} else {
    error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));

    $file_starter_header = "{$webpagefolder}/starter_header.html";
    $file_starter_footer = "{$webpagefolder}/starter_footer.html";
    $starter_header = (file_exists($file_starter_header)) ? file_get_contents($file_starter_header) : '';
    $starter_footer = (file_exists($file_starter_footer)) ? file_get_contents($file_starter_footer) : '';

    $file_starter_main = "{$webpagefolder}/starter.html";
    $starter_main = (file_exists($file_starter_main)) ? file_get_contents($file_starter_main) : (($webpagefolder . "/_starter.html") ? file_get_contents($webpagefolder . "/_starter.html") : '');

    if ($starter_main == '') {
        die("<pre>Default homepage file not found</pre>");
    }

    $starter_main = str_replace('<!--[[starter-header]]-->', $starter_header, $starter_main);
    $starter_main = str_replace('<!--[[starter-footer]]-->', $starter_footer, $starter_main);
    $starter_main = str_replace('<!--[[starter-heroimage]]-->', 'webpage/starter-bg-image.jpg', $starter_main);
    echo($starter_main);
}
