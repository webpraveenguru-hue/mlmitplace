<?php
include_once('../common/init.loader.php');

$seskey = verifylog_sess('member');
if ($seskey == '') {
    die('o o p s !');
}
if ($mdlhashy != $FORM['mdlhashy']) {
    echo myvalidate($LANG['a_loadingmdlcnt']);
    redirpageto('index.php', 1);
    exit;
}

$sesRow = getlog_sess($seskey);
$mbrusername = get_optionvals($sesRow['sesdata'], 'un');

// Get referrer/member details
$mbrstr = getmbrinfo($mbrusername, 'username');

if (isset($FORM['bnID']) && $FORM['bnID'] > 0) {
    $condition = ' AND bnid LIKE "' . $FORM['bnID'] . '" ';
    $sql = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_banners WHERE 1 " . $condition . "");
    $bannerStr = $sql[0];
}
?>

<div class="row">
    <div class="col-md-12">

        <?php
        if ($bannerStr['bnstatus'] == '1') {
            $bnimgstr = "<img alt='{$bannerStr['bntitle']}' src='" . BANNER_URL . '/' . $bannerStr['bnfile'] . "' class='img-fluid'>";
            $myreflink = ($mbrstr['peppylinkplurl']) ? $mbrstr['peppylinkplurl'] : $mbrstr['reflinkseo'];
            // target="_blank|_self|_parent|_top|framename"
            $mybannercode = "<a href='{$myreflink}' target='_top'>{$bnimgstr}</a>";
            ?>
            <form method="post" action="addbanner.php" enctype="multipart/form-data">
                <div class="form-group">
                    <?php echo myvalidate($bnimgstr); ?>
                    <textarea class="form-control rowsize-sm inputformcode" name="mybannercode" placeholder="Banner code here" id="mybannercode"><?php echo isset($mybannercode) ? $mybannercode : ''; ?></textarea>
                    <div class="alert alert-light text-small text-info mt-1"><?php echo myvalidate($LANG['m_mmbannernote']); ?></div>
                    <div class="text-md-center mt-4">
                        <a href="javascript:;" class="btn btn-warning" data-dismiss="modal"><i class="fa fa-fw fa-times"></i> Close</a>
                        <a href="javascript:;" class="btn btn-primary" type="button" onclick="copyInputText('mybannercode')"><i class="fa fa-fw fa-copy"></i> Copy</a>
                    </div>
                </div>
            </form>
            <?php
        } else {
            ?>
            <div class="empty-state" data-height="200">
                <div class="empty-state-icon bg-info">
                    <i class="fas fa-question"></i>
                </div>
                <p class="lead">
                    <?php echo myvalidate($LANG['g_norecordgen']); ?>
                </p>
            </div>
            <?php
        }
        ?>

    </div>

</div>
