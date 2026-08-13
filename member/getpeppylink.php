<?php
include("../common/init.loader.php");

$seskey = verifylog_sess('member');
if ($seskey == '') {
    die($LANG['g_invalidlogin']);
}
if ($mdlhashy != $FORM['mdlhashy']) {
    echo myvalidate($LANG['a_loadingmdlcnt']);
    redirpageto('index.php', 1);
    exit;
}

$sesRow = getlog_sess($seskey);
$username = get_optionvals($sesRow['sesdata'], 'un');

// Get member details
$mbrstr = getmbrinfo($username, 'username');
$reflinkseo = $mbrstr['reflinkseo'];

$act = $FORM['act'];

// force download
if ($act == 'dl') {
    $file_path = $mbrstr['peppylinkqrurl'];

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $username . '_qrcode.svg"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');

    // Clear output buffer
    ob_clean();
    flush();
    readfile($file_path);
    die();
}

$mbrtokenarr = get_optionvals($mbrstr['mbrtoken']);

$srcshortarr = array();
$srcshortarr['peppyapi'] = base64_decode($mbrtokenarr['peppymbrapi'] ?? '');
$srcshortarr['pltype'] = 'link';
$srcshortarr['plmbrid'] = $mbrstr['id'];
$srcshortarr['plsrcid'] = $mbrstr['mpid'];

if ($act == 'add' && $mbrstr['peppylinkqrurl'] == '') {
    $result = do_shortener($srcshortarr, $reflinkseo, "{$cfgtoken['site_subname']} ref {$mbrstr['username']}", "{$cfgrow['site_name']} referral link for {$mbrstr['username']}");
    if ($result == 1) {
        $mbrstr = getmbrinfo($username, 'username');
        $reflinkseo = $mbrstr['reflinkseo'];
    } else {
        die('<span class="badge badge-danger mb-2">' . $result['message'] . '</span><span class="badge badge-info mb-2">' . $LANG['g_contactadminfo'] . '</span>');
    }
}

// refresh qr image cache
$peplstr = get_peppyinfo($mbrstr['peppylinkpllid'], 'pllid');
$qrfile = get_optionvals($peplstr['pltoken'], 'QRFILE');

// update link if url source is not the same
if ($mbrstr['peppylinkpllid'] && $reflinkseo != $mbrstr['peppylinkplsrc']) {
    $result = do_shortener($srcshortarr, $reflinkseo, "{$cfgtoken['site_subname']} ref {$mbrstr['username']}", "{$cfgrow['site_name']} referral link for {$mbrstr['username']}", $mbrstr['peppylinkpllid']);
    if ($result == 1) {
        $mbrstr = getmbrinfo($username, 'username');
        $reflinkseo = $mbrstr['reflinkseo'];
    } else {
        die('<span class="badge badge-danger mb-2">' . $result['message'] . '</span><span class="badge badge-info mb-2">' . $LANG['g_contactadminfo'] . '</span>');
    }
}

if ($mbrstr['peppylinkpllid'] && $qrfile == '' && filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) {
    // download the image, save on local server for further use
    $imgurl = $mbrstr['peppylinkplurl'] . '/qr';
    $newqrfile = "mbr{$mbrstr['peppylinkpllid']}.svg";
    $newqrpathfile = "../assets/imagextra/qr/{$newqrfile}";

    $iscopied = copy($imgurl, $newqrpathfile);
    if ($iscopied) {
        // update local qr file
        $pltoken = put_optionvals($peplstr['pltoken'], 'QRFILE', $newqrfile);
        $data = array(
            'plupdate' => $cfgrow['datetimestr'],
            'plmbrid' => intval($mbrstr['id']),
            'plsrc' => $reflinkseo,
            'pltoken' => $pltoken,
        );
        $db->update(DB_TBLPREFIX . '_peppylink', $data, array('pllid' => $mbrstr['peppylinkpllid']));
    }
}
?>

<div class="row">
    <div class="col-md-6" id='printTable'>
        <img class='mr-3 img-fluid mx-auto d-block' src='<?php echo myvalidate($mbrstr['peppylinkqrurl']); ?>' alt=''>
    </div>
    <div class="col-md-6">
        <h2 class="mt-2"><?php echo myvalidate($mbrstr['username']); ?></h2>
        <div><?php echo myvalidate($mbrstr['fullname']); ?></div>
        <div class="mb-4"><a href="<?php echo myvalidate($mbrstr['mbrbiolink']); ?>" target="_blank"><?php echo myvalidate($mbrstr['mbrbiolink']); ?></a></div>

        <button class="btn btn-info btn-sm" type="button" onclick="printContent('printTable')">
            <i class="fa fa-print fa-fw"></i> Print
        </button>
        <button class="btn btn-info btn-sm" type="button" onclick="location.href = 'getpeppylink.php?act=dl&mdlhashy=<?php echo myvalidate($mdlhashy); ?>'">
            <i class="fa fa-download fa-fw"></i> Download SVG
        </button>
    </div>
</div>
<hr />
<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label><?php echo myvalidate($LANG['g_shorturl']); ?></label>
            <div class="input-group">
                <input type="text" class="form-control form-control-sm" value="<?php echo myvalidate($mbrstr['peppylinkplurl']); ?>" id="myrefqrurl" readonly>
                <div class="input-group-append">
                    <button class="btn btn-primary btn-sm" type="button" onclick="copyInputText('myrefqrurl')">
                        <i class="fas fa-copy fa-fw"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label><?php echo myvalidate($LANG['g_default'] . ' ' . $LANG['g_refurl']); ?></label>
            <div class="input-group">
                <input type="text" class="form-control form-control-sm" value="<?php echo myvalidate($reflinkseo); ?>" id="myrefurl" readonly>
                <div class="input-group-append">
                    <button class="btn btn-primary btn-sm" type="button" onclick="copyInputText('myrefurl')">
                        <i class="fas fa-copy fa-fw"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
<!--
    function printContent(id) {
        str = document.getElementById(id).innerHTML;
        newwin = window.open('', 'printwin', 'left=100,top=100,width=400,height=400');
        newwin.document.write('<html>\n<head>\n');
        newwin.document.write('<title>QR Code: <?php echo myvalidate("{$username} - {$mbrstr['peppylinkplurl']}"); ?></title>\n');
        newwin.document.write('<script>\n');
        newwin.document.write('function chkstate(){\n');
        newwin.document.write('if(document.readyState=="complete"){\n');
        newwin.document.write('window.close()\n');
        newwin.document.write('}\n');
        newwin.document.write('else{\n');
        newwin.document.write('setTimeout("chkstate()",2000)\n');
        newwin.document.write('}\n');
        newwin.document.write('}\n');
        newwin.document.write('function print_win(){\n');
        newwin.document.write('window.print();\n');
        newwin.document.write('chkstate();\n');
        newwin.document.write('}\n');
        newwin.document.write('<\/script>\n');
        newwin.document.write('</head>\n');
        newwin.document.write('<body onload="print_win()">\n');
        newwin.document.write(str);
        newwin.document.write('</body>\n');
        newwin.document.write('</html>\n');
        newwin.document.close();
    }
//-->
</script>