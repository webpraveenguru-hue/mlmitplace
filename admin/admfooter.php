<?php

if (!defined('OK_LOADME')) {
    die('o o p s !');
}
$nowdatetm = date('Y-m-d H:i:s', time() + (3600 * $cfgrow['time_offset']));
$lastdatetm = date('Y-m-d H:i:s', time() + (3600 * $cfgrow['time_offset']) - 60);
$dothisok = INSTALL_KEYS;
include_once('../common/cron.do.php');

$dotoaster = $_SESSION['dotoaster'];
$_SESSION['dotoaster'] = '';

$site_subname = ($cfgtoken['site_subname'] != '') ? "<a href='{$cfgrow['site_url']}'>{$cfgtoken['site_subname']}</a>" : "<a href='{$ssysout('SSYS_URL')}/id/{$cfgrow['envacc']}' target='_blank'>{$cfgrow['site_name']}</a>";
$admnotifytoast = ($cfgtoken['istoastactvty'] == '1') ? '<script src="../assets/js/notifytoast.js"></script>' : '';

if ($cfgtoken['isdarkthemeopt'] > 0) {
    $turnonofflink = "index.php?hal={$FORM['hal']}&turnto=";
    $turnonofficon = ($cfgtoken['isdarktheme'] != 1) ? "<a href='{$turnonofflink}on' data-toggle='tooltip' title='{$LANG['g_turntodark']}'><i class='fas fa-fw fa-lightbulb'></i></a>" : "<a href='{$turnonofflink}off' data-toggle='tooltip' title='{$LANG['g_turntolight']}'><i class='far fa-fw fa-lightbulb'></i></a>";
} else {
    
}
$admin_content = <<<INI_HTML
<footer class="main-footer">
    <!--
    Powered by UniMatrix Membership System - MLMScript.net
    This credit link must remain visible and accessible to the public. Any attempt to conceal or remove it is strictly prohibited.
    -->
    <div class="footer-left">
        <span class="d-none d-sm-inline">{$LANG['g_builtwith']} {$crftpowbyicoyear} {$site_subname}</span><span>{$cfgrow['_isnocreditstr']}</span>
    </div>
    <div class="footer-right text-sm-left">
        {$turnonofficon}
    </div>
</footer>
</div>
</div>

<!-- Template JS File -->
<script src="../assets/js/scripts.js"></script>
<script src="../assets/js/custom.js"></script>
<script src="../assets/js/admcustom.js"></script>
{$admnotifytoast}

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
        const sidebar = $('#sidebar-admmenu');
        const storageKey = 'sidebarAdmScrollPosition';
        if (typeof(Storage) !== "undefined") {
            const storedPosition = sessionStorage.getItem(storageKey);
            if (storedPosition !== null) {
                setTimeout(function() {
                    sidebar.scrollTop(storedPosition);
                }, 50);
            }
        }
        let timeout;
        sidebar.on('scroll', function() {
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                sessionStorage.setItem(storageKey, sidebar.scrollTop());
            }, 300);
        });
        $(window).on('beforeunload', function() {
            sessionStorage.setItem(storageKey, sidebar.scrollTop());
        });
    });
</script>

</body>
</html>
INI_HTML;
echo myvalidate($admin_content);
