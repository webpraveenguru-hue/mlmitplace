<?php

if (!defined('OK_LOADME')) {
    die('o o p s !');
}
$nowdatetm = date('Y-m-d H:i:s', time() + (3600 * $cfgrow['time_offset']));
$lastdatetm = date('Y-m-d H:i:s', time() + (3600 * $cfgrow['time_offset']) - 60);
$dothisok = INSTALL_KEYS;
include_once('cron.do.php');

$site_subname = ($cfgtoken['site_subname'] != '') ? "<a href='{$cfgrow['site_url']}'>{$cfgtoken['site_subname']}</a>" : "<a href='{$ssysout('SSYS_URL')}/id/{$cfgrow['envacc']}' target='_blank'>{$cfgrow['site_name']}</a>";

if ($cfgtoken['iscookieconsent'] == '1') {
    $cookieconsentmsg = base64_decode($cfgtoken['cookieconsentmsg'] ?? '');
    $cookieconsentstr = ($cookieconsentmsg != '') ? $cookieconsentmsg : $LANG['g_cookieconsent'];
    $iscookieconsentstr = <<<INI_HTML
        <div id="cookieAlertBar" class="cookieAlertBar">
            {$cookieconsentstr}<br /><br /> <button id="cookieAlertBarConfirm" class="btn btn-sm btn-warning">{$LANG['g_yesgetit']}</button>
        </div>
INI_HTML;
} else {
    $iscookieconsentstr = '';
}

/*
 * ---
  WARNING! If you want to use the regular license for commercial use, please do not alter, hide, or remove the script credits. If you want to do so, please support us by upgrading your script to Plus (or Extended) license. Thank you.
 * ---
 */

$page_content = <<<INI_HTML
<!---->
                <div style="text-align: center; margin-top: 8px; margin-bottom: 16px;">
                    <!--

                    Powered by UniMatrix MLM & Membership - MLMScript.net

                    This credit link must remain visible and accessible to the public. Any attempt to conceal or remove it is strictly prohibited.

                    -->
                    <div style="font-size: 12px; line-height: 20px;">{$LANG['g_builtwith']} {$crftpowbyicoyear} {$site_subname}{$cfgrow['_isnocreditstr']}
                </div>

        </div>
        {$pub_footerstr}

        <!-- Demo Color Changer -->
        {$iscookieconsentstr}
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
        <script src="../assets/js/custom.js"></script>

        <!-- Page Specific JS File -->
        <!-- include summernote css/js -->
        <script src="../assets/js/summernote-bs4.min.js"></script>

   </body>
</html>
INI_HTML;
echo myvalidate($page_content);
