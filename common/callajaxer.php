<?php
include_once('../common/init.loader.php');
$payload = array();

//reload members' website screenhot
if ($FORM['do'] == 'webscreenshot') {
    $ajaxerhash = md5($mdlhashy . "idmbr={$FORM['idmbr']}&do=webscreenshot");
    if ($FORM['idmbr'] > 0 && $FORM['hash'] == $ajaxerhash) {
        $mbrstr = getmbrinfo($FORM['idmbr']);
        $imgtofile = getwebssdata($mbrstr, $mbrstr['mbrsite_url']);
        if ($mbrstr['mbrsite_url'] != '' && $imgtofile != '') {
            $mbrsite_img = ".." . $imgtofile;
            $mbrtoken = put_optionvals($mbrstr['mbrtoken'], 'isdowebscreenshot', '');
            $data = array(
                'mbrsite_img' => $mbrsite_img,
                'mbrtoken' => $mbrtoken,
            );
            $update = $db->update(DB_TBLPREFIX . '_mbrs', $data, array('id' => $mbrstr['id']));
        }
        $mbrsite_loadimg = ($update) ? $mbrsite_img : $mbrstr['mbrsite_img'];
        ?>
        <div class="row">
            <div class="col-md-12">
                <div class="text-center">
                    <img src='<?php echo myvalidate($mbrsite_loadimg . '?' . date("mdhis")); ?>' alt='[^-^]' class='mr-3 mb-3 img-fluid rounded img-shadow'>
                    <div class="text-small text-muted"><?php echo myvalidate($mbrstr['mbrsite_url']); ?></div>
                </div>
                <div class="text-md-right">
                    <a href="index.php?hal=accountcfg" class="btn btn-primary"><i class="fa fa-fw fa-arrow-right"></i> Continue</a>
                </div>
            </div>
        </div>
        <?php
        die();
    } else {
        
    }
}

$payload['result'] = $FORM['err'];
$response = json_encode($payload);
die($response);
