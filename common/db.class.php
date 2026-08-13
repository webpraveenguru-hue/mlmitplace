<?php

if (!defined('OK_LOADME')) {
    die("<title>Error!</title><body>No such file or directory.</body>");
}

function pplandbarr() {
    global $db;

    $result = array();
    $condition = " AND ppname != ''";
    $userData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_payplans WHERE 1" . $condition . " ORDER BY ppid LIMIT 4");
    if (count($userData) > 0) {
        foreach ($userData as $val) {
            foreach ($val as $key => $value) {
                $result[$val['ppid']][$key] = $value;
            }
        }
    }
    return $result;
}

function get_optionvals($options, $var = '') {
    $varsvals = ($var == '') ? array() : '';
    $options = str_replace('|, , |', '|,|', trim($options ?? ''));
    $options = str_replace('|,, |', '|,|', trim($options ?? ''));
    $options = str_replace('|, |', '|,|', $options ?? '');
    $varvals = explode('|,|', $options ?? '');
    $vvcount = count($varvals ?? '');
    $varfound = 0;
    for ($i = 0; $i < $vvcount; $i++) {
        $varsvalsx = str_replace('|,', '|', $varvals[$i]);
        if ($i == 0)
            $varsvalsx = substr($varsvalsx, 1);
        if ($i == $vvcount - 1)
            $varsvalsx = substr($varsvalsx, 0, -1);
        $vals = explode(':', $varsvalsx);
        if ($var != '' && $vals[0] != $var)
            continue;
        if ($var != '' && $vals[0] == $var)
            $varfound = 1;
        $val = str_replace($vals[0] . ':', '', $varsvalsx);
        ($var == '') ? $varsvals[$vals[0]] = $val : $varsvals = $val;
    }
    if ($var != '' && $varfound != 1)
        $varsvals = false;
    return $varsvals;
}

function add_optionvals($options, $var = '', $val = '') {
    if (get_optionvals($options, $var) === false) {
        $options = ($options == '') ? '|' . $var . ':' . $val . '|' : $options . ', |' . $var . ':' . $val . '|';
    } else {
        $existval = get_optionvals($options, $var);
        $options = str_replace('|' . $var . ':' . $existval . '|', '|' . $var . ':' . $val . '|', $options);
    }
    return $options;
}

$creditstr = "LyogLS0tCiAqIElNUE9SVEFOVCEgSWYgeW91IGludGVuZCB0byB1c2" . "UgdGhlIFJlZ3VsYXIgbGl" . 'jZW5zZSBmb3IgY29tbWVyY2lhbCBwdXJwb3NlcywKICogcGxlYXNlIHN1cHBvcnQgdXMg' . 'YnkgbWFpbnRhaW5pbmcgdGhlIHNjcmlwdCBjcm' . "VkaXRzIChwb3dlcmVkIGJ5IHRleHQpCiAqIGluIHRoZWlyIG9yaWdpbmFsIGZvcm0uIEl0IHdpbGwgaGV" . 'scCB1cyB0byBnYXRoZXIgaW1wcmVzc2lvbiBzbyB0aGF0IHdlIGNhbgogKiBjb250aW51ZSB0byBw' . 'cm92aWRlIGZ' . "yZWUgdXBkYXRlcyB0byB5b3UuIE90aGVyd2lzZSwgeW91IGNhbiBzdXBwb3J0IHVzCiAqIGJ5IHVwZ3" . 'JhZGluZyB5b3VyIFJlZ3VsYXIgbGljZW5zZSB0byBhIFBsdXMgKG9yIEV4dGVuZGVkKSBsaWNl' . 'bnNlLiBUaG' . "FuayB5b3UuCiAqIC0tLQogKi8K";

function put_optionvals($options, $var = '', $val = '') {
    if ($var != '') {
        $options = add_optionvals($options, $var, $val);
    }
    return $options;
}

function getimglinks($timeout = 0) {
    global $cfgrow, $ssysout;

    $arrdata['type'] = 'um';
    $arrdata['site'] = base64_encode($cfgrow['site_url'] ?? '');
    $arrdata['lkey'] = $cfgrow['lickey'];
    $arrdata['hash'] = md5($arrdata['site'] . 'x' . $arrdata['lkey'] . '|' . $arrdata['type']);
    $initurl = "{$ssysout('SSYS_URL')}/_sc" . "rmote/getimg" . "links.p" . "hp";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $initurl);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($arrdata, '', '&'));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTREDIR, CURL_REDIR_POST_ALL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
    if ($timeout > 0) {
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    }
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        $arrResponse['err'] = curl_error($ch);
    }
    curl_close($ch);
    $arrResponse['data'] = json_decode($response, true);
    return $arrResponse;
}

function get_isnocredit($cfgtoken) {
    $isnocredit = (($cfgtoken['lictype'] != '2083' && $cfgtoken['licpk'] == '-') ||
            ($cfgtoken['lictype'] == '2083' && $cfgtoken['licpk'] != '')) ? true : false;
    return $isnocredit;
}

function do_postarrdata($arrdata, $timeout = 0) {
    global $ssysout;

    $initurl = "{$ssysout('SSYS_URL')}/_ver" . 'ifypass/a' . "pi.php";
    if (filter_var($initurl, FILTER_VALIDATE_URL) !== false) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $initurl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        if ($timeout > 0) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($arrdata, '', '&'));
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $arrResponse['err'] = curl_error($ch);
        }
        curl_close($ch);
        $arrResponse = json_decode($response, true);
        return $arrResponse;
    } else {
        return 'Invalid URL';
    }
}

function getpasshash($str) {
    $strmd = md5($str);
    return password_hash($strmd, PASSWORD_DEFAULT);
}

function get_inarrmind() {
    global $cfgtoken;
    $result = (strpos(base64_decode($cfgtoken['licstr'] ?? ''), 'Regular') !== false || $cfgtoken['lictype'] !== '8852' || md5('8852' . '#' . $cfgtoken['licstr'] . '-' . base64_encode(str_replace('www.', '', strtolower($_SERVER['HTT' . 'P_HO' . 'ST'] ?? '')))) != $cfgtoken['licvh']) ? false : true;
    return $result;
}

function do_isvalises() {
    global $cfgtoken;
    $lc_dbuser = (defined('DB_USER')) ? DB_USER : '';
    $lc_dbpass = (defined('DB_PASSWORD')) ? DB_PASSWORD : '';
    $lc_dbhash = (defined('DB_HASHPASSWORD')) ? DB_HASHPASSWORD : '';
    if (md5($lc_dbuser . '*' . $lc_dbpass) != $lc_dbhash && md5($cfgtoken['lictype'] . '#' . $cfgtoken['licstr'] . '-' . base64_encode(str_replace('ww' . 'w.', '', strtolower($_SERVER['HT' . '' . 'TP_H' . 'OST'] ?? '')))) != $cfgtoken['licvh']) {
        die('Insta' . "llati" . 'on ha' . "sh mis" . 'mat' . "ch. Please tr" . 'y to reins' . "tall th" . 'e system.');
    }
}

function dumbtoken($readtoken = '', $expt = 8) {
    if (time() > $_SESSION['dumbtokenexp']) {
        $_SESSION['dumbtokenexp'] = $_SESSION['dumbtoken'] = '';
    }
    if ($readtoken == '') {
        if ($_SESSION['dumbtoken'] != '') {
            return $_SESSION['dumbtoken'];
        } else {
            $_SESSION['dumbtokenexp'] = time() + (60 * $expt);
            $_SESSION['dumbtoken'] = bin2hex(openssl_random_pseudo_bytes(24));
            return $_SESSION['dumbtoken'];
        }
    } else {
        if ($_SESSION['dumbtoken'] == $readtoken && time() <= $_SESSION['dumbtokenexp']) {
            return true;
        } else {
            return false;
        }
    }
}

function gobackup($cmp, $isdownload = 0) {
    global $db, $cfgrow, $umbasever, $ssysout;

    $dbcnt_dumb = '';
    $tables = $db->getRecFrmQry("SHOW TABLE STATUS FROM " . DB_NAME . " LIKE '%'");
    if ($tables != 0) {
        $dbcnt_dumb .= "# {$ssysout('SSYS_NAME')} v{$umbasever} - Database Backup\n" .
                "# {$cfgrow['site_name']}\n" .
                "# Creation date: " . date("Y-m-d H:i:s", time()) . " - " . base64_decode('YnkgVW5pTWF0cml4IFNjcmlwdA==') . "\n" .
                "# Database: " . DB_NAME . "\n\n";

        $ssysname = strtoupper($ssysout('SSYS_NAME') ?? '');
        foreach ($tables as $key => $table) {
            $tb_name = $table['Name'];

            $realtable = explode("_", $tb_name);
            if (DB_TBLPREFIX != $realtable[0]) {
                continue;
            }

            $tbx_name = str_replace(DB_TBLPREFIX, "#{$ssysname}#", $tb_name);
            $dbcnt_dumb .= "\n\nDROP TABLE IF EXISTS $tbx_name;\n";
            $q = $db->getRecFrmQry("SHOW CREATE TABLE $tb_name");
            $createx_sql = str_replace(DB_TBLPREFIX, "#{$ssysname}#", $q[0]['Create Table']);
            $dbcnt_dumb .= "$createx_sql;\n";
            $q = $db->getRecFrmQry("SELECT * FROM $tb_name");
            foreach ($q as $key => $a) {
                $fields = join(',', array_keys($a));
                $value = array_map('strvalescape', array_values($a));
                $values = join(',', $value);
                $tbx_name = str_replace(DB_TBLPREFIX, "#{$ssysname}#", $tb_name);
                $dbcnt_dumb .= "INSERT INTO $tbx_name ($fields) VALUES ($values);\n";
            }
        }
        $dbcnt_dumb .= "\n\n" .
                "# Valid end of backup from " . DB_NAME . " database.\n" .
                "# ---\n" .
                "# Powered by {$ssysout('SSYS_TITLE')}\n" .
                "# Available at {$ssysout('SSYS_URL')}\n" .
                "# ---\n";

        if ($cmp == 'gz') {
            ob_start();
            print $dbcnt_dumb;
            $out = gzencode(ob_get_contents());
            ob_end_clean();
            if ($isdownload != 0) {
                echo myvalidate($out);
            } else {
                return $out;
            }
        } else {
            if ($isdownload != 0) {
                print $dbcnt_dumb;
            } else {
                return $dbcnt_dumb;
            }
        }
    }
}

function put_optarr($token64, $valarr = array()) {
    $token = base64_decode($token64 ?? '');
    foreach ($valarr as $key => $value) {
        $token = put_optionvals($token, $key, $value);
    }
    return base64_encode($token);
}

function get_optarr($token64) {
    $token = base64_decode($token64 ?? '');
    return get_optionvals($token);
}

function loadimglink($timeout = 0) {
    $keephour = rand(1, 6);
    $now = date("Y-m-d H:m:s");
    $new_time = date("Y-m-d H:i:s", strtotime("+{$keephour} hours", strtotime($now)));
    if (!is_array($_SESSION['loadimglink']) || $_SESSION['loadimglinkdt'] < $now) {
        $responarr = getimglinks($timeout = 0);
        $result = [];
        foreach ($responarr['data'] as $key => $value) {
            if ($value['status'] == 1) {
                $result[] = "<a href='{$value['url']}' target='_blank' data-toggle='tooltip' title='{$value['tip']}'><img alt='{$value['alt']}' src='{$value['img']}' class='img-fluid'></a>";
            }
        }
        $_SESSION['loadimglink'] = $result;
        $_SESSION['loadimglinkdt'] = $new_time;
    } else {
        $result = $_SESSION['loadimglink'];
    }
    return $result;
}

function read_file_size($size) {
    if (intval($size) <= 0) {
        return("0 Bytes");
    }
    $filesizename = array(" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
    return round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . $filesizename[$i];
}

function dborder_arr($tblarr, $tblsel, $tblsrt) {
    $curqryurl = $_SERVER['REQUEST_URI'];
    if ((strpos($curqryurl ?? '', "_stbel=") !== false)) {
        $rtblsrt = ($tblsrt == 'up') ? "down" : "up";
        $curqryurl = str_replace("_stbel={$tblsel}", "_stbel=^", $curqryurl ?? '');
    } else {
        $curqryx = (strpos($_SERVER['REQUEST_URI'] ?? '', '?') !== false) ? "&" : "?";
        $curqryurl .= $curqryx . "_stbel=^&_stype=down";
    }

    $tblarrlink = [];
    foreach ($tblarr as $key => $value) {
        if ($tblsel == $value) {
            $curqryurlgo = str_replace("_stype={$tblsrt}", "_stype={$rtblsrt}", $curqryurl ?? '');
            $curqryurlgo = str_replace("_stbel=^", "_stbel={$value}", $curqryurlgo ?? '');
            $curfontaw = ($tblsrt != 'up') ? "fa fa-fw fa-long-arrow-alt-down" : "fa fa-fw fa-long-arrow-alt-up";
        } else {
            $curqryurlgo = str_replace("_stbel=^", "_stbel={$value}", $curqryurl ?? '');
            $curfontaw = "fa fa-fw fa-arrows-alt-v";
        }
        $tblarrlink[$value] = "<a href='{$curqryurlgo}'><i class='{$curfontaw}'></i></a>";
    }
    return $tblarrlink;
}

function select_opt($valarr, $valsel = '', $tostr = 0) {
    if ($tostr != 0) {
        $selopt = $valarr[$valsel];
    } else {
        $selopt = ($valsel == '') ? "<option selected>-</option>" : "<option disabled>-</option>";
        foreach ($valarr as $key => $value) {
            if ($value == '') {
                continue;
            }
            $selopt .= ($key == $valsel) ? "<option value='{$key}' selected>{$value}</option>" : "<option value='{$key}'>{$value}</option>";
        }
    }
    return $selopt;
}

function checkbox_opt($value, $targetval = 1, $tostr = 0) {
    global $LANG;

    if ($tostr != 0) {
        $cekopt = ($value == $targetval) ? $LANG['g_yes'] : $LANG['g_no'];
    } else {
        $cekopt = ($value == $targetval) ? " checked" : "";
    }
    return $cekopt;
}

function radiobox_opt($valuearr, $targetval = 1, $keybyval = '') {
    $cekopt = [];
    foreach ($valuearr as $key => $value) {
        if ($keybyval != '') {
            $cekopt[$value] = ($key == $targetval) ? ' checked="checked"' : '';
        } else {
            $cekopt[$key] = ($value == $targetval) ? ' checked="checked"' : '';
        }
    }
    return $cekopt;
}

function redir_to($redir = '') {
    $refredir = $_SERVER["HTTP_REFERER"];
    $redirto = ($redir == '') ? $refredir : "index.php?hal=" . $redir;
    return $redirto;
}

function mystriptag($mysdata, $filter = 'string') {
    global $cfgtoken;

    $mysdata = trim($mysdata ?? '');
    if ($filter == 'email') {
        $mysdata = filter_var($mysdata, FILTER_SANITIZE_EMAIL);
        $mysdata = strtolower(trim($mysdata ?? ''));
    } elseif ($filter == 'url') {
        $mysdata = filter_var($mysdata, FILTER_SANITIZE_URL);
        $mysdata = rtrim($mysdata ?? '', "/");
    } else {
        $mysdata = filter_var($mysdata, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    }
    if ($filter == 'user') {
        $mysdata = preg_replace("/[^A-Za-z0-9]/", '', $mysdata ?? '');
        $mysdata = ($cfgtoken['unlowercs'] == '1') ? strtolower($mysdata ?? '') : $mysdata;
    }
    return strip_tags($mysdata ?? '');
}

function imageupload($outfname, $fileimg, $oldimg = '') {
    $valid_extensions = array('jpeg', 'jpg', 'png', 'gif');

    $newimg = $oldimg;
    $path = '../assets/imagextra/';
    if ($fileimg) {
        $img = $fileimg['name'];
        $tmp = $fileimg['tmp_name'];
        $ext = strtolower(pathinfo($img ?? '', PATHINFO_EXTENSION));
        $final_image = $outfname . '.' . $ext;
        // check's valid format
        if (in_array($ext, $valid_extensions)) {
            if ($oldimg != '' && file_exists($oldimg) && strpos($oldimg ?? '', '/imagextra/') !== false) {
                unlink($oldimg);
            }
            $path = $path . strtolower($final_image ?? '');
            if (move_uploaded_file($tmp, $path)) {
                $newimg = $path;
            }
        }
    }
    return $newimg;
}

function readfile_chunked($filename, $retbytes = true) {
    $chunksize = 2 * (1024 * 1024);
    $buffer = '';
    $cnt = 0;

    $handle = fopen($filename, 'rb');
    if ($handle === false) {
        return false;
    }
    while (!feof($handle)) {
        $buffer = fread($handle, $chunksize);
        echo myvalidate($buffer);
        ob_flush();
        flush();
        if ($retbytes) {
            $cnt += strlen($buffer ?? '');
        }
    }
    $status = fclose($handle);
    if ($retbytes && $status) {
        return $cnt;
    }
    return $status;
}

function dodlfile($file_path, $file_name, $mtype) {
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header("Content-Type: $mtype");
    header("Content-Disposition: attachment; filename=\"$file_name\"");
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: " . filesize($file_path));

    readfile_chunked($file_path);
}

function do_definer() {
    global $ssysout;

    define('SSYS_NAME', 'UniMa' . 'trix');
    define('SSYS_TITLE', 'UniMa' . "trix Memb" . 'ership');
    define('SSYS_TAGLINE', 'Uni' . 'level and Mat' . 'rix Mem' . "bership - ML" . 'M Script');
    define('SSYS_DOMAIN', 'mlms' . 'cript.net');
    define('SSYS_URL', 'htt' . "ps:/" . '/ww' . 'w.ml' . 'msc' . 'ript.' . 'net');
    define('SSYS_AUTHOR', 'ML' . 'MSc' . 'ript.n' . 'et');
    define('SSYS_ALIAS', 'm l ' . 'm s c r i ' . 'p t . n e' . ' t');
    define('SSYS_MARKER', '9afde277466bf0c590' . "a798f48761756fa159" . 'd6291253289698e41f6f6aff169e');
    $ssysout = 'constant';
}

function badgembrplanstatus($statusid, $mpstatus = 0, $mpnamestr = '', $imgstr = '') {
    global $LANG;

    $statusbadge = '';
    switch ($statusid) {
        case "1":
            $statustr = $LANG['g_active'];
            $statuclr = 'success';
            $statumrk = 'online';
            break;
        case "2":
            $statustr = $LANG['g_limited'];
            $statuclr = 'warning';
            $statumrk = 'away';
            break;
        case "3":
            $statustr = $LANG['g_pending'];
            $statuclr = 'danger';
            $statumrk = 'busy';
            break;
        default:
            $statustr = $LANG['g_inactive'];
            $statuclr = 'light';
            $statumrk = 'offline';
    }
    if ($imgstr == '') {
        $statusbadge .= "<span class='badge badge-{$statuclr}'>{$statustr}</span>";
    } else {
        $statusbadge .= '
                    <figure class="avatar mr-2 avatar-sm">
                      <img src="' . $imgstr . '" alt="...">
                      <i class="fa fa-id-badge text-' . $statuclr . ' avatar-icon" data-toggle="tooltip" title="' . $LANG['g_account'] . ' - ' . $statustr . '"></i>
                    </figure>
        ';
    }
    $mpnamestr = ($mpnamestr == '') ? $LANG['g_membership'] : $mpnamestr;
    $mpnamestr .= ' - ';
    switch ($mpstatus) {
        case "0":
            $statusbadge .= "<span class='badge badge-light' data-toggle='tooltip' title='{$mpnamestr}{$LANG['g_registeredonly']}'><i class='fa fa-fw fa-user'></i></span>";
            break;
        case "1":
            $statusbadge .= "<span class='badge badge-success' data-toggle='tooltip' title='{$mpnamestr}{$LANG['g_active']}'><i class='fa fa-fw fa-check'></i></span>";
            break;
        case "2":
            $statusbadge .= "<span class='badge badge-warning' data-toggle='tooltip' title='{$mpnamestr}{$LANG['g_expire']}'><i class='fa fa-fw fa-exclamation'></i></span>";
            break;
        case "3":
            $statusbadge .= "<span class='badge badge-danger' data-toggle='tooltip' title='{$mpnamestr}{$LANG['g_pending']}'><i class='fa fa-fw fa-times'></i></span>";
            break;
        default:
            $statusbadge .= "<span class='badge badge-light' data-toggle='tooltip' title='{$LANG['g_unregistered']}'><i class='fa fa-fw fa-question'></i></span>";
    }
    return $statusbadge;
}

// function to get ip address
function get_userip() {
    $ip = false;
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    return ($ip ? $ip : $_SERVER['RE' . 'MOT' . 'E_AD' . 'DR']);
}

function redirpageto($destinationurl, $delay = 0) {
    $delay = intval($delay);
    echo "<meta http-equiv='refresh' content='{$delay};url={$destinationurl}'>";
    exit;
}

function formatdate($datetimestr, $type = 'd') {
    global $cfgrow, $LANG;

    $dtformat = ($type == 'd') ? $cfgrow['sodatef'] : $cfgrow['lodatef'];
    $datestr = date($dtformat, strtotime($datetimestr ?? ''));

    if ($LANG['lang_iso'] != 'en') {
        $daystrlist = str_replace(' ', '', $LANG['g_daystrlist'] ?? '');
        $daystrlistarr = explode(',', $daystrlist ?? '');

        $monthstrlist = str_replace(' ', '', $LANG['g_monthstrlist'] ?? '');
        $monthstrlistarr = explode(',', $monthstrlist ?? '');

        $daylongstrlist = str_replace(' ', '', $LANG['g_daylongstrlist'] ?? '');
        $daylongstrlistarr = explode(',', $daylongstrlist ?? '');

        $monthlongstrlist = str_replace(' ', '', $LANG['g_monthlongstrlist'] ?? '');
        $monthlongstrlistarr = explode(',', $daystrlist ?? '');

        $g_daystrlist = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');
        $g_monthstrlist = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
        $g_daylongstrlist = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
        $g_monthlongstrlist = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');

        $datestr = str_replace($g_monthlongstrlist ?? '', $monthlongstrlistarr ?? '', $datestr ?? '');
        $datestr = str_replace($g_daylongstrlist ?? '', $daylongstrlistarr ?? '', $datestr ?? '');
        $datestr = str_replace($g_monthstrlist ?? '', $monthstrlistarr ?? '', $datestr ?? '');
        $datestr = str_replace($g_daystrlist ?? '', $daystrlistarr ?? '', $datestr ?? '');
    }

    return $datestr;
}

function addlog_sess($username, $type = 'system', $rememberme = '') {
    global $db, $cfgrow;

    dellog_sess('member');
    $_SESSION['logmeremember'] = ($_SESSION['logmeremember'] == '') ? $rememberme : $_SESSION['logmeremember'];

    $userip = get_userip();
    $mbrstr = getmbrinfo($username, 'username');
    $sesdata = put_optionvals('', 'un', $username);
    $sesdata = put_optionvals($sesdata, 'ip', $userip);

    $sestime = time() + (3600 * $cfgrow['time_offset']);
    $logkeysesid = ($_SESSION['logmeremember'] != '') ? date("Ym") : $userip;
    $seskey = getpasshash($username . '|' . $logkeysesid . INSTALL_KEYS);

    $data = array(
        'sestype' => $type,
        'sesidmbr' => intval($mbrstr['id']),
        'sesdata' => $sesdata,
        'sestime' => intval($sestime),
        'seskey' => $seskey,
    );

    $sesRow = getlog_sess($seskey);
    if ($sesRow['sesid'] < 1) {
        $db->insert(DB_TBLPREFIX . '_sessions', $data);
    } else {
        $db->update(DB_TBLPREFIX . '_sessions', $data, array('sesid' => $sesRow['sesid']));
    }

    $_SESSION[$cfgrow['md5sess'] . $type] = $seskey;
    if ($rememberme == 1) {
        setcookie($cfgrow['md5sess'] . $type, $seskey ?? '', time() + (3600 * 72) + (3600 * $cfgrow['time_offset']), "/");
    } else {
        setcookie($cfgrow['md5sess'] . $type, $seskey ?? '', time() + (3600 * 1) + (3600 * $cfgrow['time_offset']), "/");
    }
    return $seskey;
}

function getlog_sess($seskey, $isupdate = '') {
    global $db, $cfgrow;

    $condition = ' AND seskey = "' . $seskey . '" ';
    $row = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_sessions WHERE 1 " . $condition . "");
    $sesRow = [];
    foreach ($row as $value) {
        $sesRow = array_merge($sesRow, $value);
    }

    // update time
    if ($sesRow['sesid'] > 0 && $isupdate == 1) {
        $sestime = time() + (3600 * $cfgrow['time_offset']);
        $data = array(
            'sestime' => intval($sestime),
        );
        $db->update(DB_TBLPREFIX . '_sessions', $data, array('sesid' => $sesRow['sesid']));
    }
    return $sesRow;
}

function dellog_sess($type = '') {
    global $db, $cfgrow;

    if ($type != '') {
        // delete type session
        $_SESSION['filteruid'] = $_SESSION['clisti'] = $_SESSION['clistview'] = $_SESSION['dotoaster'] = $_SESSION['show_msg'] = '';
        $seskey = ($_SESSION[$cfgrow['md5sess'] . $type] ? $_SESSION[$cfgrow['md5sess'] . $type] : $_COOKIE[$cfgrow['md5sess'] . $type]);
        if ($seskey != '') {
            $db->delete(DB_TBLPREFIX . '_sessions', array('seskey' => $seskey));

            $_SESSION[$cfgrow['md5sess'] . $type] = $_SESSION['logmeremember'] = '';
            setcookie($cfgrow['md5sess'] . $type, '', time() - (3600 * $cfgrow['time_offset']), "/");
        }
    } else {
        // delete old sessions
        $sqlarr = [];
        $tmintvarr = array("system" => (3600 * 6), "admin" => (3600 * 12), "member" => (3600 * 72));
        foreach ($tmintvarr as $key => $value) {
            $sestime = time() - $value;
            $sqlarr[] = "(sestype = '{$key}' AND sestime < {$sestime})";
        }
        $sqladd = implode(' OR ', $sqlarr);
        $condition = "AND ({$sqladd})";
        $db->doQueryStr("DELETE FROM " . DB_TBLPREFIX . "_sessions WHERE 1 " . $condition);
    }
}

function verifylog_sess($type = 'system', $isupdate = '') {
    global $cfgrow;

    $hasil = '';
    $seskey = ($_SESSION[$cfgrow['md5sess'] . $type]) ? $_SESSION[$cfgrow['md5sess'] . $type] : $_COOKIE[$cfgrow['md5sess'] . $type];

    $userip = get_userip();
    $sesRow = getlog_sess($seskey, $isupdate);
    $username = get_optionvals($sesRow['sesdata'] ?? '', 'un');

    $logkeysesid = ($_SESSION['logmeremember'] != '') ? date("Ym") : $userip;
    if (password_verify(md5($username . '|' . $logkeysesid . INSTALL_KEYS), $seskey ?? '')) {
        $hasil = $seskey;
    } else {
        dellog_sess($seskey);
    }
    return $hasil;
}

function time_since($sestime) {
    global $cfgrow, $LANG;

    $timearr = explode(',', str_replace(' ', '', $LANG['g_timelist'] ?? ''));

    $since = time() + (3600 * $cfgrow['time_offset']) - $sestime;
    $chunks = array(
        array(60 * 60 * 24 * 365, $timearr[0]),
        array(60 * 60 * 24 * 30, $timearr[1]),
        array(60 * 60 * 24 * 7, $timearr[2]),
        array(60 * 60 * 24, $timearr[3]),
        array(60 * 60, $timearr[4]),
        array(60, $timearr[5]),
        array(1, $timearr[6])
    );

    for ($i = 0, $j = count($chunks); $i < $j; $i++) {
        $seconds = $chunks[$i][0];
        $name = $chunks[$i][1];
        if (($count = floor($since / $seconds)) != 0) {
            break;
        }
    }

    $print = ($count == 1) ? '1 ' . $name : "$count {$name}{$timearr[7]}";
    return $print;
}

function time_expiry($sestime, $isminsec = 0) {
    global $cfgrow, $LANG;

    // "year, month, week, day, hour, minute, second"
    $timearr = explode(',', str_replace(' ', '', $LANG['g_timelist'] ?? ''));

    $result = '';
    if ($sestime > $cfgrow['datetimestr']) {
        $expire = \DateTime::createFromFormat('Y-m-d H:i:s', $sestime);
        $now = new \DateTime();

        $diff = $expire->diff(($now));

        if ($diff->y) {
            $result .= $diff->y . ($diff->y > 1 ? " {$timearr[0]}{$timearr[7]} " : " {$timearr[0]} ");
        }
        if ($diff->m) {
            $result .= $diff->m . ($diff->m > 1 ? " {$timearr[1]}{$timearr[7]} " : " {$timearr[1]} ");
        }
        if ($diff->d) {
            $result .= $diff->d . ($diff->d > 1 ? " {$timearr[3]}{$timearr[7]} " : " {$timearr[3]} ");
        }
        if ($diff->h) {
            $result .= ' and ' . $diff->h . ($diff->h > 1 ? " {$timearr[4]}{$timearr[7]} " : " {$timearr[4]} ");
        }
        if ($diff->i && $isminsec == 1) {
            $result .= $diff->i . ($diff->i > 1 ? " {$timearr[5]}{$timearr[7]} " : " {$timearr[5]} ");
        }
        if ($diff->s && $isminsec == 1) {
            $result .= $diff->s . ($diff->s > 1 ? " {$timearr[6]}{$timearr[7]} " : " {$timearr[6]} ");
        }
    }
    return $result;
}

function showalert($type, $title, $message) {

    $faiconarr = array("info" => "lightbulb", "success" => "check-circle", "warning" => "question-circle", "danger" => "times-circle", "secondary" => "bell", "light" => "bell", "dark" => "bell", "primary" => "bell");
    $faicon = $faiconarr[$type];

    $alert_content = <<<INI_HTML
                <div class="alert alert-{$type} alert-dismissible alert-has-icon show fade">
                    <div class="alert-icon"><i class="far fa-{$faicon} fa-fw"></i></div>
                    <div class="alert-body">
                        <button class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                        <div class="alert-title">{$title}</div>
                        {$message}
                    </div>
                </div>
INI_HTML;

    return $alert_content;
}

function getmbrinfo($id, $bfield = '', $mpid = 0, $ppid = 0) {
    global $db, $cfgrow, $cfgtoken, $bpparr, $LANG;

    $userRow = $mbrpparrall = $mbrpparract = [];
    $userRow['pparr_all'] = $userRow['pparr_act'] = $mbrpparrall;
    $bfield = ($bfield == '') ? 'id' : $bfield;

    if ($id != '') {
        $row = $db->getAllRecords(DB_TBLPREFIX . '_mbrs', '*', " AND {$bfield} = '{$id}'");
        foreach ($row as $value) {
            $userRow = array_merge($userRow, $value);
        }

        $ppid = ($userRow['mppid'] > 0) ? $userRow['mppid'] : $ppid;
        $mpid = ($userRow['mpid'] > 0) ? $userRow['mpid'] : $mpid;

        $condition = ($ppid > 0) ? " AND mppid = '{$ppid}'" : " ORDER BY cyclingbyid ASC, mpid DESC LIMIT 1";
        $row = $db->getAllRecords(DB_TBLPREFIX . '_mbrplans', '*', " AND idmbr = '{$userRow['id']}'" . $condition . "");
        foreach ($row as $value) {
            $userRow = array_merge($userRow, $value);
        }
    }

    // plan member
    if ($mpid > 0) {
        $row = $db->getAllRecords(DB_TBLPREFIX . '_mbrplans', '*', " AND mpid = '{$mpid}'");
        foreach ($row as $value) {
            $userRow = array_merge($userRow, $value);
        }
        if ($id == '') {
            $row = $db->getAllRecords(DB_TBLPREFIX . '_mbrs', '*', " AND id = '{$userRow['idmbr']}'");
            foreach ($row as $value) {
                $userRow = array_merge($userRow, $value);
            }
        }
    }

    $plantoken = $bpparr[$userRow['mppid']]['plantoken'];
    $plantokenarr = get_optionvals($plantoken);
    $isfreedoact = $plantokenarr['isfreedoact'];

    // get all registered plans in array
    $condition = " ORDER BY cyclingbyid ASC, mpid DESC";
    $row = $db->getAllRecords(DB_TBLPREFIX . '_mbrplans', '*', " AND idmbr = '{$userRow['id']}'" . $condition . "");
    foreach ($row as $value) {
        if ($value['mpstatus'] == 1) {
            $mbrpparract[] = $value['mppid'];
        }
        $mbrpparrall[] = $value['mppid'];
    }
    $userRow['pparr_all'] = $mbrpparrall;
    $userRow['pparr_act'] = $mbrpparract;

    // payment options
    if ($userRow['id'] > 0) {
        $row = $db->getAllRecords(DB_TBLPREFIX . '_paygates', '*', " AND pgidmbr = '{$userRow['id']}'");
        foreach ($row as $value) {
            $userRow = array_merge($userRow, $value);
        }
    }

    // Bio Page
    $mbr_sosmedarr = get_optionvals($userRow['mbr_sosmed']);
    $userRow['mbrbiolink'] = $mbr_sosmedarr['mbr_biopage'];

    // peppylink
    $plsrc = '/' . UIDFOLDER_NAME . '/' . $userRow['username'];
    $addmpidqry = ($mpid > 0) ? " AND plsrcid = '{$mpid}'" : '';
    $row = $db->getAllRecords(DB_TBLPREFIX . '_peppylink', '*', $addmpidqry . " AND plmbrid = '{$userRow['id']}' AND plsrc LIKE '%" . $plsrc . "' AND (pltype = 'link' OR pltype = 'qr') AND plstatus = '1'");
    foreach ($row as $value) {
        if ($value['pltype'] == 'link') {
            $userRow['peppylinkplid'] = $value['plid'];
            $userRow['peppylinkplsrc'] = $value['plsrc'];
            $userRow['peppylinkplurl'] = $value['plurl'];
            $userRow['peppylinkpllid'] = $value['pllid'];

            $peppymbrstr = get_peppyinfo($cfgrow['site_url'] . $plsrc, 'plsrc');
            $qrfile = get_optionvals($peppymbrstr['pltoken'], 'QRFILE');
            $userRow['peppylinkqrurl'] = ($qrfile) ? $cfgrow['site_url'] . "/assets/imagextra/qr/{$qrfile}" : $userRow['peppylinkplurl'] . '/qr';
        }
        if ($value['pltype'] == 'qr') {
            $userRow['peppylinkqrid'] = $value['plid'];
            $userRow['peppylinkqrurl'] = $value['plurl'];
            $userRow['peppylinkqrlid'] = $value['pllid'];
        }
    }

    // kyc status
    $condition = "";
    $row = $db->getAllRecords(DB_TBLPREFIX . '_mbrkycs', 'kyid, kystatus', " AND kyidmbr = '{$userRow['id']}'" . $condition . "");
    $userRow['kyid'] = $row[0]['kyid'];
    $userRow['kystatus'] = $row[0]['kystatus'];
    $userRow['kyverifiedimg'] = ($row[0]['kystatus'] == 1) ? "<i class='fas fa-fw text-success fa-check-circle' data-toggle='tooltip' title='{$LANG['g_kycverified']}'></i>" : '';

    // check kyc is mandatory and member kyc status is verified
    $userRow['mbrkycstatus'] = ($cfgtoken['ismbrkyc'] == '1' && $userRow['kystatus'] != '1') ? 0 : 1;

    if ($userRow['mbrstatus'] == '1' && ($userRow['mpstatus'] == '1' || $isfreedoact == 1) && $cfgtoken['disreflink'] != 1) {
        $userRow['reflinkseo'] = $cfgrow['site_url'] . '/' . UIDFOLDER_NAME . '/' . $userRow['username'];
        $userRow['reflinkreg'] = $cfgrow['site_url'] . '/' . UIDFOLDER_NAME . '/?ref=' . $userRow['username'];
        $userRow['reflink'] = ($cfgtoken['isreflinkreg'] == 1) ? $userRow['reflinkreg'] : $userRow['reflinkseo'];
    }
    $statusaccarr = array(0 => $LANG['g_inactive'], 1 => $LANG['g_active'], 2 => $LANG['g_limited'], 3 => $LANG['g_pending']);
    $userRow['straccstatus'] = $statusaccarr[$userRow['mbrstatus'] ?? ''];
    $statusmbrarr = array(0 => $LANG['g_inactive'], 1 => $LANG['g_active'], 2 => $LANG['g_expire'], 3 => $LANG['g_pending']);
    $userRow['strmbrstatus'] = $statusmbrarr[$userRow['mpstatus'] ?? ''];

    $userRow['username'] = ($userRow['username'] == '') ? $cfgtoken['admin_subname'] : $userRow['username'];
    $userRow['firstname'] = ($userRow['username'] == $cfgtoken['admin_subname']) ? 'ADMIN' : $userRow['firstname'];
    $userRow['lastname'] = ($userRow['username'] == $cfgtoken['admin_subname']) ? 'Administrator' : $userRow['lastname'];
    $userRow['fullname'] = $userRow['firstname'] . ' ' . $userRow['lastname'];

    return $userRow;
}

do_definer();

function getusernameid($srcval, $targetstr = 'id') {
    global $db, $cfgtoken;

    if ($srcval < 1) {
        $userRow[$targetstr] = $cfgtoken['admin_subname'];
    } else {
        if ($targetstr == 'id') {
            $sqlwhere = "username LIKE '{$srcval}'";
        } else {
            $sqlwhere = "id = '{$srcval}'";
        }

        $userRow = [];
        $row = $db->getAllRecords(DB_TBLPREFIX . '_mbrs', '*', ' AND ' . $sqlwhere);
        foreach ($row as $value) {
            $userRow = array_merge($userRow, $value);
        }
    }

    return $userRow[$targetstr];
}

function parsenotify($cntarr, $msg) {
    foreach ((array) $cntarr as $key => $value) {
        if (is_array($value)) {
            continue;
        }
        $msg = str_replace("[[{$key}]]", $value ?? '', $msg ?? '');
    }

    // add custom parse
    $msg = str_replace("[[fullname]]", $cntarr['firstname'] . ' ' . $cntarr['lastname'], $msg ?? '');

    return $msg;
}

function printlog($idstr = '', $err = '') {
    global $cfgrow;

    if (defined('ISPRINTLOG')) {
        $datetm = $cfgrow['datetimestr'];
        $myfile = file_put_contents('printlog.log', "[{$datetm}][{$idstr}] {$err}" . PHP_EOL, FILE_APPEND | LOCK_EX);
        return $myfile;
    }
}
