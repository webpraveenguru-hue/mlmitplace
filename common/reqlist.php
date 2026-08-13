<?php

// file execute by page load
if (!defined('OK_LOADME')) {
    die("^-^ REGL");
}

function is_valid_domain($url) {

    $validation = FALSE;
    /* Parse URL */
    $urlparts = parse_url(filter_var($url, FILTER_SANITIZE_URL));
    /* Check host exist else path assign to host */
    if (!isset($urlparts['host'])) {
        $urlparts['host'] = $urlparts['path'];
    }

    if ($urlparts['host'] != '') {
        /* Add scheme if not found */
        if (!isset($urlparts['scheme'])) {
            $urlparts['scheme'] = 'http';
        }
        /* Validation */
        if (checkdnsrr($urlparts['host'], 'A') && in_array($urlparts['scheme'], array('http', 'https')) && ip2long($urlparts['host']) === FALSE) {
            $urlparts['host'] = preg_replace('/^www\./', '', $urlparts['host'] ?? '');
            $url = $urlparts['scheme'] . '://' . $urlparts['host'] . "/";

            if (filter_var($url, FILTER_VALIDATE_URL) !== false && @get_headers($url)) {
                $validation = TRUE;
            }
        }
    }

    return $validation;
    /* //
      if (!$validation) {
      echo "Invalid Domain";
      } else {
      echo "Valid Domain";
      }
      // */
}

$phpver = phpversion();
if ($phpver >= 7.4 && $phpver < 8.3) {
    $recomendverstr = ($phpver < 8.2) ? " <span class='text-muted'>(recommended to use PHP 8.2)</span>" : '';
    $phpversionstr = "<i class='fa fa-fw fa-check text-success'></i> PHP {$phpver}{$recomendverstr}</li>";
} else {
    $phpversionstr = "<i class='fa fa-fw fa-times text-danger'></i> PHP {$phpver}</li>";
    $doregsbtnsubmit = '';
}

$sqlver = " <span class='text-muted'>(do a manual check)</span>";
$sqlversionstr = "<i class='fa fa-fw fa-question text-info'></i> MySQL {$sqlver}</li>";

if (function_exists('curl_init')) {
    $curlstr = "<i class='fa fa-fw fa-check text-success'></i> cUrl function</li>";
} else {
    $curlstr = "<i class='fa fa-fw fa-times text-danger'></i> cUrl function</li>";
    $doregsbtnsubmit = '';
}

if (filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) {
    $urlfopen = "<i class='fa fa-fw fa-check text-success'></i> Option: allow_url_fopen</li>";
} else {
    $urlfopen = "<i class='fa fa-fw fa-times text-danger'></i> Option: allow_url_fopen</li>";
}

if (function_exists('fsockopen')) {
    $fsockopenstr = "<i class='fa fa-fw fa-check text-success'></i> fSockOpen function</li>";
} else {
    $fsockopenstr = "<i class='fa fa-fw fa-times text-warning'></i> fSockOpen function</li>";
}

if (get_extension_funcs('json')) {
    $jsonstr = "<i class='fa fa-fw fa-check text-success'></i> JSON extension</li>";
} else {
    $jsonstr = "<i class='fa fa-fw fa-times text-danger'></i> JSON extension</li>";
    $doregsbtnsubmit = '';
}

if (get_extension_funcs('intl')) {
    $intlstr = "<i class='fa fa-fw fa-check text-success'></i> Intl extension</li>";
} else {
    $intlstr = "<i class='fa fa-fw fa-times text-warning'></i> Intl extension</li>";
}

if (get_extension_funcs('zlib')) {
    $zlibstr = "<i class='fa fa-fw fa-check text-success'></i> zLib extension</li>";
} else {
    $zlibstr = "<i class='fa fa-fw fa-times text-danger'></i> zLib extension</li>";
    $doregsbtnsubmit = '';
}

if (function_exists('gzencode')) {
    $gzencodestr = "<i class='fa fa-fw fa-check text-success'></i> gzEncode function</li>";
} else {
    $gzencodestr = "<i class='fa fa-fw fa-times text-warning'></i> gzEncode function</li>";
    $doregsbtnsubmit = '';
}

if (get_extension_funcs('pdo')) {
    $classpdostr = "<i class='fa fa-fw fa-check text-success'></i> PDO extension</li>";
} else {
    $classpdostr = "<i class='fa fa-fw fa-times text-danger'></i> PDO extension</li>";
}

if (get_extension_funcs('mysqli')) {
    $classpdomysqlstr = "<i class='fa fa-fw fa-check text-success'></i> MySQLi extension</li>";
} else {
    $classpdomysqlstr = "<i class='fa fa-fw fa-times text-danger'></i> MySQLi extension</li>";
}

if (function_exists('imagecreatefromstring')) {
    $imagecreatefromstringstr = "<i class='fa fa-fw fa-check text-success'></i> ImageCreateFromString function</li>";
} else {
    $imagecreatefromstringstr = "<i class='fa fa-fw fa-times text-warning'></i> ImageCreateFromString function</li>";
}

$cfgfile = "../common/config.php";
if (file_exists($cfgfile) && is_writable($cfgfile)) {
    $cfgfilestr = "<i class='fa fa-fw fa-check text-success'></i> Configuration file</li>";
} else {
    $cfgfilestr = "<i class='fa fa-fw fa-check text-danger'></i> Configuration file <span class='text-muted'>(must be rewritable)</span></li>";
}

$isdomainname = (in_array($_SERVER['REM' . 'OTE_A' . 'DDR'], array('12' . '7.0.' . '0.1', "::1")) || !is_valid_domain($_SERVER['HTT' . 'P_HO' . 'ST'])) ? "<i class='fa fa-fw fa-unlink text-warning'></i> Domain or Online Server</li>" : "<i class='fa fa-fw fa-globe text-success'></i> Domain or Online Server</li>";

$servsoftver = strtolower($_SERVER['SERVER_SOFTWARE'] ?? '');
$servsoftverstr = (strpos($servsoftver ?? '', 'apache') !== false && strpos($servsoftver ?? '', 'php') !== false) ? "<i class='fa fa-fw fa-layer-group text-info'></i> {$servsoftver}</li>" : "<i class='fa fa-fw fa-question text-warning'></i> {$servsoftver}(do a manual check)</li>";

$showreg_server = <<<INI_HTML
        <ul class="list-unstyled">
            <li>{$isdomainname}</li>
            <li>{$servsoftverstr}</li>
            <li>{$phpversionstr}</li>
            <li>{$sqlversionstr}</li>
            <li>{$classpdostr}</li>
            <li>{$classpdomysqlstr}</li>
            <li>{$curlstr}</li>
            <li>{$urlfopen}</li>
            <li>{$fsockopenstr}</li>
            <li>{$jsonstr}</li>
            <li>{$intlstr}</li>
            <li>{$zlibstr}</li>
            <li>{$gzencodestr}</li>
            <li>{$imagecreatefromstringstr}</li>
            <li>{$cfgfilestr}</li>
        </ul>
INI_HTML;
