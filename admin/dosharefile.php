<?php
include("../common/init.loader.php");

if (verifylog_sess('admin') == '') {
    die('o o p s !');
}
if ($mdlhasher != $FORM['mdlhasher']) {
    echo myvalidate($LANG['a_loadingmdlcnt']);
    redirpageto('index.php', 1);
    exit;
}

$flId = $FORM['fileId'];
$act = $FORM['act'];

$fileStr = get_fileinfo($flId);

$extfile = pathinfo($fileStr['flpath'] ?? '', PATHINFO_EXTENSION);
$dlfilestr = preg_replace('/[\W]/', '_', strtolower($fileStr['flname'] ?? ''));
$dlfilename = $dlfilestr . '.' . $extfile;

// force download
if ($act == 'dl') {
    $file_path = "../assets/imagextra/qr/fdl{$flId}.svg";

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $dlfilestr . '_qrcode.svg"');
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

$flhashshort = md5($fileStr['flid'] . $dlfilename . '*');
$dldlinkshort = "{$cfgrow['site_url']}/" . MBRFOLDER_NAME . "/index.php?dlfn={$dlfilename}&dlid={$fileStr['flid']}&i={$flhashshort}";

$peplstr = get_peppyinfo($dldlinkshort, 'plsrc');

if ($cfgtoken['ispeppy']) {
    if ($peplstr['plurl'] == '') {
        $srcshortarr = array();
        $srcshortarr['peppyapi'] = '';
        $srcshortarr['pltype'] = 'file';
        $srcshortarr['plmbrid'] = 0;
        $srcshortarr['plsrcid'] = $fileStr['flid'];
        $result = do_shortener($srcshortarr, $dldlinkshort, "Download {$fileStr['flname']}", "File download {$dlfilename}");
        if ($result == 1) {
            $peplstr = get_peppyinfo($dldlinkshort, 'plsrc');
        } else {
            die("<span class='badge badge-danger mb-2'>{$result['message']}</span><div class='alert alert-light'>Please make sure the URL Shortener feature has been configured properly on the <a href='index.php?hal=generalcfg'>{$LANG['a_settings']}</a> page.</div>");
        }
    }
} else {
    die("<span class='badge badge-danger mb-2'>The URL Shortener feature is disabled!</span><div class='alert alert-light'>In order to use this Share File feature, please enable this feature in the <a href='index.php?hal=generalcfg'>{$LANG['a_settings']}</a> page.</div>");
}

// refresh qr image cache
$qrfile = get_optionvals($peplstr['pltoken'], 'QRFILE');
$qrfilepath = "../assets/imagextra/qr/";
if ($peplstr['plurl'] && $qrfile == '' && filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) {
    // download the image, save on local server for further use
    $imgurl = $peplstr['plurl'] . '/qr';
    $newqrfile = "file{$peplstr['pllid']}.svg";
    $iscopied = copy($imgurl, "{$qrfilepath}{$newqrfile}");

    if ($iscopied) {// update local qr file
        $pltoken = put_optionvals($peplstr['pltoken'], 'QRFILE', $newqrfile);
        $data = array(
            'plupdate' => $cfgrow['datetimestr'],
            'pltoken' => $pltoken,
        );
        $db->update(DB_TBLPREFIX . '_peppylink', $data, array('plid' => $peplstr['plid']));
    }
}

$dlurlshort = $peplstr['plurl'];
$dlurlshortqr = ($qrfile) ? "{$qrfilepath}{$qrfile}" : $dlurlshort . '/qr';

$flhash = md5($cfgrow['dldir'] . $fileStr['flid'] . date("md"));
$dldlink = "{$cfgrow['site_url']}/" . MBRFOLDER_NAME . "/index.php?dlfn={$dlfilename}&dlid={$fileStr['flid']}&l={$flhash}";
?>

<div class="row">
    <div class="col-md-6" id='printTable'>
        <img class='mr-3 img-fluid mx-auto d-block' src='<?php echo myvalidate($dlurlshortqr); ?>' alt=''>
    </div>
    <div class="col-md-6">
        <h5 class="mt-2"><?php echo myvalidate($fileStr['flname']); ?></h5>
        <div class="mb-4"><?php echo myvalidate($dlfilename); ?></div>

        <button class="btn btn-info btn-sm" type="button" onclick="printContent('printTable')">
            <i class="fa fa-print fa-fw"></i> Print
        </button>
        <button class="btn btn-info btn-sm" type="button" onclick="location.href = 'dosharefile.php?act=dl&fileId=<?php echo myvalidate($flId); ?>&mdlhasher=<?php echo myvalidate($mdlhasher); ?>'">
            <i class="fa fa-download fa-fw"></i> Download SVG
        </button>
    </div>
</div>
<hr />
<div class="row">
    <div class="col-md-12">
        <div class="form-group">
            <label>Secure Download Link</label>
            <div class="input-group">
                <input type="text" class="form-control form-control-sm" value="<?php echo myvalidate($dlurlshort); ?>" id="shortdlfile" readonly>
                <div class="input-group-append">
                    <button class="btn btn-primary btn-sm" type="button" onclick="copyInputText('shortdlfile')">
                        <i class="fas fa-copy fa-fw"></i>
                    </button>
                </div>
            </div>
            <div class='alert alert-light text-small text-danger'>Copy and share the above short and secure download URL. Login will be required if the file is protected.</div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="form-group">
            <label>Default Download Link</label>
            <div class="input-group">
                <input type="text" class="form-control form-control-sm" value="<?php echo myvalidate($dldlink); ?>" id="defdlfile" readonly>
                <div class="input-group-append">
                    <button class="btn btn-primary btn-sm" type="button" onclick="copyInputText('defdlfile')">
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
        newwin.document.write('<title>QR Code: <?php echo myvalidate($fileStr['flname']); ?></title>\n');
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