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
?>

<div class="row">
    <div class="col-md-12">

        <?php
        $ithash = '';
        $itid = intval($FORM['itemId']);
        if ($itid > 0) {
            $sesRow = getlog_sess($seskey);
            $username = get_optionvals($sesRow['sesdata'], 'un');
            $mbrstr = getmbrinfo($username, 'username');

            $itemStr = get_iteminfo($itid);
            $itimagestr = ($itemStr['itimage']) ? $itemStr['itimage'] : DEFIMG_FILE;

            if ($itemStr['itstatus'] == '1') {
                $ithash = md5($mdlhashy . $itemStr['itid'] . '+' . $itemStr['itstatus'] . $mbrstr['id']);
                $itbuynow = "index.php?hal=orderpay&l={$ithash}&itid={$itemStr['itid']}";

                // get price based on plan
                $itpricenow = get_itpricebyplan($itemStr, $mbrstr['mppid']);
                $priceDef = "<div class='mt-2 lead'>{$LANG['g_price']}: {$bpprow['currencysym']}{$itpricenow} {$bpprow['currencycode']}</div>";
                ?>
                <div class="card">
                    <div class="card-header">
                        <h4><?php echo myvalidate($itemStr['itname']); ?></h4>
                        <div class="card-header-action">
                            <?php echo myvalidate($catstr); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <img class='mr-3 rounded-circle img-responsive' width='<?php echo myvalidate($cfgrow['mbrmax_image_width']); ?>' height='<?php echo myvalidate($cfgrow['mbrmax_image_height']); ?>' style='max-width:160px;height:auto;' src='<?php echo myvalidate($itimagestr); ?>' alt='<?php echo myvalidate($itemStr['itname']); ?>'>
                            </div>
                            <div class="col-md-9">
                                <?php echo myvalidate($priceDef); ?>
                                <hr />
                                <div class='mt-2'>
                                    <?php echo myvalidate("<p><b>{$LANG['g_description']}</b></p>" . $itemStr['itdescr']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-whitesmoke text-md-right">
                        <a href="javascript:;" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-fw fa-times"></i> Close</a>
                        <a href="javascript:;" onclick="location.href = '<?php echo myvalidate($itbuynow); ?>'" class="btn btn-primary"><i class="fa fa-fw fa-shopping-bag"></i> <?php echo myvalidate($LANG['g_ordernow']); ?></a>
                    </div>
                </div>
                <?php
            } else {
                
            }
        } else {
            
        }
        ?>
    </div>
</div>
