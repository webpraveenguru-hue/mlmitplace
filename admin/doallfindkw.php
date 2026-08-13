<?php
include_once('../common/init.loader.php');

if (verifylog_sess('admin') == '') {
    die('o o p s !');
}

function getInitial($namestr) {
    $namearr = explode(" ", $namestr);
    $nameinit = "";
    $nameinit .= strtoupper(substr($namearr[0], 0, 1));
    if (count($namearr) > 1) {
        $nameinit .= strtoupper(substr($namearr[1], 0, 1));
    }
    return $nameinit;
}

function getBackgroundColor($namestr) {
    $colors = ['#5681C6', '#6356C6', '#C656B9', '#C66356', '#C69B56', '#56B9C6', '#56C69B', '#56C663', '#81C656'];
    $hash = crc32($namestr);
    $index = abs($hash) % count($colors);
    return $colors[$index];
}

$keyword = $FORM["keyword"];
if (isset($keyword) && $keyword != '') {
    $condition = " AND (username LIKE '%{$keyword}%' OR email LIKE '%{$keyword}%' OR firstname LIKE '%{$keyword}%' OR lastname LIKE '%{$keyword}|%') ORDER BY RAND() LIMIT 9";
    $userData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_mbrs LEFT JOIN " . DB_TBLPREFIX . "_mbrplans ON id = idmbr WHERE 1 " . $condition . "");

    if (count($userData) > 0) {
        ?>
        <div class="search-header">
            <?php echo myvalidate($LANG['g_result']); ?>
        </div>
        <?php
        foreach ($userData as $val) {
            $fullname = "{$val['firstname']} {$val['lastname']} ";
            $initname = getInitial($fullname);
            $clbgname = getBackgroundColor($fullname);
            ?>
            <div class="search-item">
                <a href="index.php?username=<?php echo myvalidate($val['username']); ?>&hal=userlist">
                    <div class="search-icon text-white mr-3" style="background-color: <?php echo myvalidate($clbgname); ?>;">
                        <?php echo myvalidate($initname); ?>
                    </div>
                    <span><?php echo myvalidate("{$val['username']} <span class='text-small text-muted'>({$val['email']})</span>"); ?></span>
                </a>
            </div>
            <?php
        }
    } else {
        ?>
        <div class="search-item">
            <a class="text-small text-muted"><?php echo myvalidate($LANG['g_norecordinfo']); ?></a>
            <a href="#" class="search-close"><i class="fas fa-times"></i></a>
        </div>
        <?php
    }
} else {
    ?>
    <div class="search-item">
        <a class="text-small text-muted"><?php echo myvalidate($LANG['g_findbyusername']); ?></a>
        <a href="#" class="search-close"><i class="fas fa-times"></i></a>
    </div>
    <?php
}

