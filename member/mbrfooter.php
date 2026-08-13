<?php

if (!defined('OK_LOADME')) {
    die('o o p s !');
}
$nowdatetm = date('Y-m-d H:i:s', time() + (3600 * $cfgrow['time_offset']));
$lastdatetm = date('Y-m-d H:i:s', time() + (3600 * $cfgrow['time_offset']) - 60);
$dothisok = INSTALL_KEYS;
include_once('../common/cron.do.php');

$VW5pTWF0cml4 = base64_decode('VW5pTWF0cml4');
$dotoaster = $_SESSION['dotoaster'];
$_SESSION['dotoaster'] = '';

$site_subname = ($cfgtoken['site_subname'] != '') ? "<a href='{$cfgrow['site_url']}'>{$cfgtoken['site_subname']}</a>" : "<a href='{$ssysout('SSYS_URL')}/id/{$cfgrow['envacc']}' target='_blank'>{$cfgrow['site_name']}</a>";

$isnocredit = get_isnocredit($cfgtoken);
$cfgrow['_isnocreditstr'] = (!$isnocredit) ? base64_decode('IHwgUG93ZXJlZCBieQ==') . " <a href='{$ssysout('SSYS_URL')}/um' target='_blank'>{$VW5pTWF0cml4}</a>" : '';

/*
 * ---
  WARNING! If you want to use the regular license for commercial use, please do not alter, hide, or remove the script credits. If you want to do so, please support us by upgrading your script to Plus (or Extended) license. Thank you.
 * ---
 */

$member_content = <<<INI_HTML
<footer class="main-footer">
    <!--
    Powered by UniMatrix Membership & MLM eStore - MLMScript.net
    This credit link must remain visible and accessible to the public. Any attempt to conceal or remove it is strictly prohibited.
    -->
    <div class="footer-left">
        {$LANG['g_builtwith']} {$crftpowbyicoyear} {$site_subname}{$cfgrow['_isnocreditstr']}
    </div>
    <div class="footer-right">
        <div class="d-none d-sm-block text-small">
        <a href="javascript:;" data-href="../common/terms.html" data-poptitle="Terms and Conditions" class="openPopup text-info">{$LANG['g_termscon']}</a>
        </div>
    </div>
</footer>
</div>

</div>

<script src="../assets/js/toastr.min.js"></script>
<script src="../assets/js/bootbox.min.js"></script>

<!-- JS Libraies -->
<script src="../assets/js/stisla.js"></script>

<!-- Template JS File -->
<script src="../assets/js/scripts.js"></script>
<script src="../assets/js/custom.js"></script>

<!-- Page Specific JS File -->
<script type="text/javascript">
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "preventDuplicates": true,
        "onclick": null
    }
    {$dotoaster}

    $(function() {
        const sidebar = $('#sidebar-mbrmenu');
        const storageKey = 'sidebarMbrScrollPosition';

        if (typeof(Storage) !== "undefined") {
            const storedPosition = sessionStorage.getItem(storageKey);
            if (storedPosition !== null) {
                setTimeout(function() {
                    sidebar.scrollTop(storedPosition);
                }, 0);
            }
        }

        let timeout;
        sidebar.on('scroll', function() {
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                sessionStorage.setItem(storageKey, sidebar.scrollTop());
            }, 100);
        });

        $(window).on('beforeunload', function() {
            sessionStorage.setItem(storageKey, sidebar.scrollTop());
        });
    });
</script>

</body>
</html>
INI_HTML;
echo myvalidate($member_content);

