<?php

if (!defined('OK_LOADME')) {
    die('o o p s !');
}

$admin_content = <<<INI_HTML
<div class="section-header">
    <h1><i class="fa fa-fw fa-question text-warning"></i> {$LANG['a_unavailablepage']}</h1>
</div>

<div class="section-body">
    <a href="index.php?hal=dashboard" class="btn btn-secondary"><i class="fas fa-long-arrow-alt-left"></i> {$LANG['g_back']}</a>
</div>
INI_HTML;
echo myvalidate($admin_content);
