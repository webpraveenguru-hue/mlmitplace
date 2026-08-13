<?php
include_once('../common/init.loader.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$page_header = $LANG['m_contactus'];

include('../common/sys.captch.php');
$ishcapcok = do_hcaptcha('ishcaptcha', $_POST['h-captcha-response']);
$isrecapv3 = do_recaptcha('isrecaptcha', $_POST['g-recaptcha-response']);

if (isset($FORM['dosubmit']) and $FORM['dosubmit'] == '1') {

    extract($FORM);

    if (!defined('ISDEMOMODE') && !defined('ISNOMAILER') && $cfname && $cfemail && $cfsubject && $cfmessage) {
        if (!dumbtoken($dumbtoken)) {
            $_SESSION['show_msg'] = showalert('danger', $LANG['g_error'], $LANG['g_invalidtoken']);
            $redirval = "?res=errtoken";
            redirpageto($redirval);
            exit;
        }


        if (!$ishcapcok && $_POST['h-captcha-response'] != '') {
            $_SESSION['show_msg'] = showalert('warning', $LANG['g_error'], $LANG['g_errorhcaptcha']);
            $redirval = "?res=hcapt";
        } elseif (!$isrecapv3 && $_POST['g-recaptcha-response'] != '') {
            $_SESSION['show_msg'] = showalert('warning', $LANG['g_error'], $LANG['g_errorrecaptcha']);
            $redirval = "?res=rcapt";
        } else {

            require_once('../common/mailer.do.php');

            //Set the subject line
            $msgsubject = mystriptag($cfsubject);

            $cfmessage = mystriptag($cfmessage);
            $cfmessageadd = "Contact Form:
            Name: {$cfname} ({$cfemail})

            ";
            // Plain text body (for mail clients that cannot read HTML)
            $cfmessage = $cfmessageadd . $cfmessage;

            // HTML body
            $cfmessagehtml = nl2br($cfmessage);

            //send the message, check for errors
            $isdomailer = domailer($bpprow['pay_emailname'], $bpprow['pay_emailaddr'], $msgsubject, $cfmessagehtml, $cfmessage, '', '', 0, $cfname, $cfemail);

            if ($isdomailer) {
                $res = '1';
                $_SESSION['show_msg'] = showalert('success', $LANG['g_success'], $LANG['g_messagesent']);
            } else {
                $_SESSION['show_msg'] = showalert('danger', $LANG['g_error'], 'Mailer result: Please check the error log in your server!');
            }
        }
    }

    //header('location: contact.php?res=' . $res);
    redirpageto('contact.php?res=' . $res);
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
                ?>

                <div class="card card-primary">
                    <div class="card-header"><h4><?php echo myvalidate($page_header); ?></h4></div>

                    <div class="card-body">
                        <form method="POST" class="needs-validation" novalidate="" id="contactform">
                            <div class="form-group">
                                <label for="cfname"><?php echo myvalidate($LANG['g_name']); ?></label>
                                <input id="cfname" type="text" class="form-control" name="cfname" tabindex="1" placeholder="<?php echo myvalidate($LANG['m_descfname']); ?>" required autofocus>
                                <div class="invalid-feedback">
                                    <?php echo myvalidate($LANG['m_descfname']); ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="cfemail"><?php echo myvalidate($LANG['g_email']); ?></label>
                                <input id="cfemail" type="email" class="form-control" name="cfemail" tabindex="2" placeholder="<?php echo myvalidate($LANG['m_descfemail']); ?>" required autofocus>
                                <div class="invalid-feedback">
                                    <?php echo myvalidate($LANG['m_descfemail']); ?>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="cfsubject"><?php echo myvalidate($LANG['m_feedbacksubject']); ?></label>
                                <input type="text" name="cfsubject" id="cfsubject" class="form-control" value="<?php echo isset($cfsubject) ? $cfsubject : ''; ?>" placeholder="<?php echo myvalidate($LANG['m_descfsubject']); ?>" required>
                                <div class="invalid-feedback">
                                    <?php echo myvalidate($LANG['m_descfsubject']); ?>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="cfmessage"><?php echo myvalidate($LANG['m_feedbackmessage']); ?></label>
                                <textarea class="form-control rowsize-lg" name="cfmessage" id="cfmessage" placeholder="<?php echo myvalidate($LANG['m_descfmessage']); ?>" required><?php echo isset($cfmessage) ? $cfmessage : ''; ?></textarea>
                                <div class="invalid-feedback">
                                    <?php echo myvalidate($LANG['m_descfmessage']); ?>
                                </div>
                            </div>

                            <div class="form-group">
                                <?php echo myvalidate($ishcapcok); ?>
                                <?php echo myvalidate($isrecapv3); ?>

                                <button type='submit' class="btn btn-primary btn-lg btn-block">
                                    <?php echo myvalidate($LANG['g_submit']); ?>
                                </button>
                                <input type="hidden" name="dosubmit" value="1">
                                <input type="hidden" name="dumbtoken" value="<?php echo myvalidate($_SESSION['dumbtoken']); ?>">
                            </div>
                        </form>

                        <div class="mt-4 text-muted text-center">
                            <?php echo myvalidate($LANG['g_haveacc']); ?> <a href="login.php"><?php echo myvalidate($LANG['g_loginhere']); ?></a>
                        </div>
                        <div class="mt-2 text-muted text-center">
                            <?php echo myvalidate($LANG['g_donothaveacc']); ?> <a href="register.php"><?php echo myvalidate($LANG['g_createacc']); ?></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php
include('../common/pub.footer.php');
