<?php
include_once('../common/init.loader.php');

$page_header = $LANG['g_confirmation'];

$codelinkc = '';

// by link
if (isset($FORM['randc']) && $FORM['randc'] != '') {
    extract($FORM);

    $randc = intval($randc);
    $hashc = mystriptag($hashc);

    $row = $db->getAllRecords(DB_TBLPREFIX . '_mbrs', '*', " AND mbrtoken LIKE '%|regconfirmrand:{$randc}|%' AND mbrtoken LIKE '%|regconfirmhash:{$hashc}|%'");
    $mbrstr = getmbrinfo($row[0]['id']);
    $mbrtokenarr = get_optionvals($mbrstr['mbrtoken']);

    $iscodelinkc = 1;
}

// by form
if (isset($FORM['dosubmit']) && $FORM['dosubmit'] == '1') {
    extract($FORM);

    if (!defined('ISDEMOMODE') && $emailconfirmcode) {
        if (!dumbtoken($dumbtoken)) {
            $_SESSION['show_msg'] = showalert('danger', 'Error!', $LANG['g_invalidtoken']);
            $redirval = "?res=errtoken";
            redirpageto($redirval);
            exit;
        }

        $emailmbr = base64_decode($_SESSION['emc64'] ?? '');
        $emailconfirmcode = mystriptag($emailconfirmcode);

        $row = $db->getAllRecords(DB_TBLPREFIX . '_mbrs', '*', " AND email LIKE '{$emailmbr}' AND mbrtoken LIKE '%|regconfirmkey:{$emailconfirmcode}|%'");
        $mbrstr = getmbrinfo($row[0]['id']);
        $mbrtokenarr = get_optionvals($mbrstr['mbrtoken']);

        $iscodelinkc = 1;
    } else {
        $_SESSION['show_msg'] = showalert('danger', 'Error!', $LANG['g_confirmationfail']);
        redirpageto('regconfirm.php?res=' . $emailconfirmcode);
        exit;
    }
}

if ($iscodelinkc == 1) {
    if ($cfgrow['datestr'] == $mbrtokenarr['regconfirmdate']) {

        // send registration email if not send
        if ($mbrtokenarr['tempupw'] != '') {
            $rawpassword = base64_decode($mbrtokenarr['tempupw'] ?? '');
            do_regandnotif($mbrstr['id'], $mbrtokenarr['stgId'], $mbrtokenarr['refbyidmbr'], $mbrstr['fullname'], $rawpassword);
        }

        // update member token and mark as confirmed
        $mbrtoken = '';
        $mbrtoken = put_optionvals($mbrtoken, 'regconfirmhash', '-');
        $mbrtoken = put_optionvals($mbrtoken, 'regconfirmdate', $cfgrow['datestr']);

        $data = array(
            'isconfirm' => '1',
            'mbrtoken' => $mbrtoken,
        );
        $update = $db->update(DB_TBLPREFIX . '_mbrs', $data, array('id' => $mbrstr['id']));

        $_SESSION['show_msg'] = showalert('success', $LANG['g_success'], $LANG['g_confirmationok']);
        $res = 1;
    } else {
        if ($mbrstr['id'] < 1) {
            $_SESSION['show_msg'] = showalert('danger', $LANG['g_error'], $LANG['g_confirmationfail']);
            $res = 'nouser';
        } else {
            $_SESSION['show_msg'] = showalert('danger', $LANG['g_error'], $LANG['g_confirmationexp']);
            get_codeconfirm($mbrstr);
            $res = 'relink';
        }
    }
    redirpageto('regconfirm.php?res=' . $res);
    exit;
}

$show_msg = $_SESSION['show_msg'];
$_SESSION['show_msg'] = '';

include('../common/pub.header.php');
?>
<section class="section">
    <div class="container mt-4">
        <div class="row">
            <div class="col-12 col-sm-10 offset-sm-1 col-md-8 offset-md-2 col-lg-8 offset-lg-2 col-xl-6 offset-xl-3">
                <div class="login-brand">
                    <img src="<?php echo myvalidate($site_logo); ?>" alt="logo" width="100" class="shadow-light<?php echo myvalidate($weblogo_style); ?>">
                    <div><?php echo myvalidate($cfgrow['site_name']); ?></div>
                </div>

                <?php
                echo myvalidate($show_msg);

                if ($FORM['res'] != 1) {
                    if ($cfgrow['isrecaptcha'] == 1) {
                        echo '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
                    }
                    ?>

                    <div class="card card-danger">
                        <div class="card-header"><h4><?php echo myvalidate($page_header); ?></h4></div>

                        <div class="card-body">

                            <div class="alert alert-light">
                                <div class="text-danger text-lg-center"><strong><?php echo myvalidate($LANG['g_confirmationtitle']); ?></strong></div>
                                <div class="mt-4 text-muted"><?php echo myvalidate($LANG['g_confirmationnote']); ?></div>
                                <div class="mt-3 text-lg-center text-small text-info"><?php echo myvalidate($LANG['g_confirmationtime']); ?></div>
                            </div>
                            <form method="POST" class="needs-validation" novalidate="" id="confirmform">
                                <div class="form-group">
                                    <label for="emailconfirmcode"><?php echo myvalidate($LANG['g_confirmationcode']); ?></label>
                                    <input id="emailconfirmcode" type="text" class="form-control" name="emailconfirmcode" tabindex="1" autocomplete="off" autofocus>
                                </div>
                                <div class="form-group">
                                    <button type='submit' class="btn btn-primary btn-lg btn-block">
                                        <?php echo myvalidate($LANG['g_submit']); ?>
                                    </button>
                                    <input type="hidden" name="dosubmit" value="1">
                                    <input type="hidden" name="dumbtoken" value="<?php echo myvalidate($_SESSION['dumbtoken']); ?>">
                                </div>
                            </form>

                            <div class="mt-4 text-muted text-center">
                                <?php echo myvalidate($LANG['g_havequestion']); ?> <a href="contact.php"><?php echo myvalidate($LANG['g_contactus']); ?></a>
                            </div>
                        </div>
                    </div>
                    <?php
                } else {
                    echo "<div class='text-center'><a href='index.php?hal=dashboard' class='btn btn-primary'>{$LANG['g_continue']} <i class='fas fa-long-arrow-alt-right'></i></a></div>";
                }
                ?>
            </div>
        </div>
    </div>
</section>
<?php
include('../common/pub.footer.php');
