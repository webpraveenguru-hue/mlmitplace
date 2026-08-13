<?php
include_once('../common/init.loader.php');

$page_header = $LANG['m_ipnthanks'];
include('../common/pub.header.php');

$redirto = ($FORM['hal'] != '') ? "index.php?hal={$FORM['hal']}" : '.';
?>
<section class="section">
    <div class="container mt-4">
        <div class="row">
            <div class="col-12 col-sm-10 offset-sm-1 col-md-8 offset-md-2 col-lg-8 offset-lg-2 col-xl-8 offset-xl-2">
                <div class="login-brand">
                    <img src="<?php echo myvalidate($site_logo); ?>" alt="logo" width="100" class="shadow-light<?php echo myvalidate($weblogo_style); ?>">
                    <div><?php echo myvalidate($cfgrow['site_name']); ?></div>
                </div>

                <div class="card card-primary">
                    <div class="card-header"><h4><i class="fas fa-handshake fa-fw"></i> <?php echo myvalidate($LANG['g_info']); ?></h4></div>

                    <div class="card-body">
                        <p class="text-muted"><?php echo ($_SESSION['payment_msg'] != '') ? $_SESSION['payment_msg'] : myvalidate($LANG['m_ipnthanksverify']); ?></p>
                        <form method="POST">
                            <div class="form-group">
                                <button type="button" class="btn btn-primary mt-4" tabindex="4" onclick="location.href = '<?php echo myvalidate($redirto); ?>'">
                                    <?php echo myvalidate($LANG['m_ipnnextbtn']); ?> <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </form>
                    </div>

                </div>

            </div>
        </div>
    </div>
</section>
<?php
$_SESSION['payment_msg'] = '';
include('../common/pub.footer.php');
redirpageto($redirto, 5);
