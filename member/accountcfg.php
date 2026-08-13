<?php
if (!defined('OK_LOADME')) {
    die('o o p s !');
}

$pgdatatokenarr = get_optionvals($payrow['pgdatatoken']);
$pgdatatoken = $mbrstr['pgdatatoken'];
$pgmbrtokenarr = get_optionvals($pgdatatoken);

$mbrbankcfg = get_optarr($pgmbrtokenarr['bankcfg']);
$strmbr_intro = base64_decode($mbrstr['mbr_intro'] ?? '');
$mbr_sosmed = get_optionvals($mbrstr['mbr_sosmed']);

$mbrtokenarr = get_optionvals($mbrstr['mbrtoken']);
$peppymbrapi = base64_decode($mbrtokenarr['peppymbrapi'] ?? '');
$goglacode = base64_decode($mbrtokenarr['goglacode'] ?? '');

$mbrstr['mbr_biopage'] = $mbr_sosmed['mbr_biopage'];
$mbrstr['mbr_twitter'] = $mbr_sosmed['mbr_twitter'];
$mbrstr['mbr_facebook'] = $mbr_sosmed['mbr_facebook'];

$country_array = array_map('strtolower', $country_array);
$country_array = array_map('ucwords', $country_array);
$country_menu = select_opt($country_array, $mbrstr['country']);

$mbrsite_cat_menu = select_opt($webcategory_array, $mbrstr['mbrsite_cat']);

$optinmearr = array(0, 1);
$optinme_cek = radiobox_opt($optinmearr, $mbrstr['optinme']);
$showsitearr = array(0, 1);
$showsite_cek = radiobox_opt($showsitearr, $mbrstr['showsite']);

$mbrimgstr = ($mbrstr['mbr_image']) ? $mbrstr['mbr_image'] : $cfgrow['mbr_defaultimage'];

if (isset($_FILES['mbr_image']) && $_FILES['mbr_image']["tmp_name"] != '') {
    // process images
    if (!defined('ISDEMOMODE')) {
        $mbr_image = do_imgresize('mbr_image_' . $mbrstr['id'], $_FILES["mbr_image"]["tmp_name"], $cfgrow['mbrmax_image_width'], $cfgrow['mbrmax_image_height'], 'jpeg');
        $data = array(
            'mbr_image' => $mbr_image,
        );

        $update = $db->update(DB_TBLPREFIX . '_mbrs', $data, array('id' => $mbrstr['id']));
        if ($update) {
            $_SESSION['dotoaster'] = "toastr.success('Profile picture updated successfully!', 'Success');";
        }
    } else {
        $_SESSION['dotoaster'] = "toastr.warning('Profile picture is not updated!', 'Demo Account - Update disabled!');";
    }
    redirpageto('index.php?hal=' . $hal);
    exit;
}

if (isset($FORM['dosubmit']) && $FORM['dosubmit'] == '1') {

    if ($mbrstr['mbrstatus'] > 1) {
        $_SESSION['dotoaster'] = "toastr.error('{$LANG['g_nomajorchanges']}', 'Account limited');";
        redirpageto('index.php?hal=' . $hal);
        exit;
    }

    extract($FORM);

    $mbr_sosmed = put_optionvals($mbr_sosmed, 'mbr_biopage', mystriptag($mbr_biopage));
    $mbr_sosmed = put_optionvals($mbr_sosmed, 'mbr_twitter', mystriptag($mbr_twitter));
    $mbr_sosmed = put_optionvals($mbr_sosmed, 'mbr_facebook', mystriptag($mbr_facebook));

    // if password change
    if ($password1 == $password2 && $ischangeok == 1) {
        $data = array(
            'password' => getpasshash($password1),
        );
        $update0 = $db->update(DB_TBLPREFIX . '_mbrs', $data, array('id' => $mbrstr['id']));
    }

    $mbr_intro = mystriptag($mbr_intro);
    $mbrtoken = $mbrstr['mbrtoken'];

    if ($mbrsite_url != $mbrsite_url_old) {
        $mbrtoken = put_optionvals($mbrtoken, 'isdowebscreenshot', '1');
    }

    $mbrtoken = put_optionvals($mbrtoken, 'goglacode', base64_encode($goglacode ?? ''));

    // start check and save member peppy.link api key
    if ($peppymbrapi != '') {
        $FORM['do'] = 'get_peppyacc';
        $FORM['valin'] = 'check';
        $FORM['nodie'] = 1;
        $FORM['apik'] = $peppymbrapi;
        ob_start();
        include_once('../common/callpeppy.php');
        ob_end_clean();
        $respeppyaccarr = json_decode($response, true);
        $peppymbrapinow = ($respeppyaccarr['result3'] > 0) ? base64_encode($peppymbrapi ?? '') : '';
    } else {
        $peppymbrapinow = '';
    }
    $birthday = "{$bodyear}-{$bodmonth}-{$bodday}";
    $mbrtoken = put_optionvals($mbrtoken, 'peppymbrapi', $peppymbrapinow);
    // end check

    $data = array(
        'birthday' => $birthday,
        'optinme' => $optinme,
        'mbr_intro' => base64_encode($mbr_intro ?? ''),
        'address' => mystriptag($address),
        'state' => mystriptag($state),
        'country' => $country,
        'phone' => mystriptag($phone),
        'mbr_sosmed' => mystriptag($mbr_sosmed),
        'mbrsite_url' => mystriptag($mbrsite_url, 'url'),
        'mbrsite_title' => substr(mystriptag($mbrsite_title), 0, $cfgrow['mbrmax_title_char']),
        'mbrsite_desc' => base64_encode(mystriptag(substr($mbrsite_desc ?? '', 0, $cfgrow['mbrmax_descr_char'])) ?? ''),
        'mbrsite_cat' => $mbrsite_cat,
        'showsite' => $showsite,
        'mbrtoken' => $mbrtoken,
    );

    if ($cfgtoken['isnoeditname'] != 1) {
        $data['firstname'] = mystriptag($firstname);
        $data['lastname'] = mystriptag($lastname);
    }
    if ($cfgtoken['isnoeditemail'] != 1) {
        $data['email'] = mystriptag($email, 'email');
    }

    $update = $db->update(DB_TBLPREFIX . '_mbrs', $data, array('id' => $mbrstr['id']));

    // ---

    $bankarr = array('bankname' => $bankname, 'bankaccno' => $bankaccno, 'bankaccname' => $bankaccname);
    $bankcfg = put_optarr($pgmbrtokenarr['bankcfg'], $bankarr);
    $pgdatatoken = put_optionvals($pgdatatoken, 'bankcfg', $bankcfg);

    $manualpayipn_untags = mystriptag($manualpayipn);

    $data = array(
        'pgdatatoken' => $pgdatatoken,
        'manualpayipn' => base64_encode($manualpayipn ?? ''),
    );
    $condition = ' AND pgidmbr = "' . $mbrstr['id'] . '" ';
    $sql = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_paygates WHERE 1 " . $condition . "");
    if (count($sql) > 0) {
        $update1 = $db->update(DB_TBLPREFIX . '_paygates', $data, array('pgidmbr' => $mbrstr['id']));
    } else {
        $data_add = array(
            'pgidmbr' => $mbrstr['id'],
        );
        $data = array_merge($data, $data_add);
        $insert = $db->insert(DB_TBLPREFIX . '_paygates', $data);
    }
    // ---

    if ($update0 || $update || $update1 || $insert) {
        $_SESSION['dotoaster'] = "toastr.success('Record updated successfully!', 'Success');";
    } else {
        $_SESSION['dotoaster'] = "toastr.warning('{$LANG['g_nomajorchanges']}', 'Info');";
    }

    redirpageto('index.php?hal=' . $hal);
    exit;
}

$readonlyname = ($cfgtoken['isnoeditname'] != 1) ? '' : ' readonly';
$readonlyemail = ($cfgtoken['isnoeditemail'] != 1) ? '' : ' readonly';

$faiconcolor = ($mbrstr['mbrstatus'] == 2) ? '<div class="section-header-breadcrumb"><i class="fa fa-2x fa-fw fa-lock text-danger"></i></div>' : '';
?>

<div class="section-header">
    <h1><i class="fa fa-fw fa-user-cog"></i> <?php echo myvalidate($LANG['m_profilecfg']); ?></h1>
    <?php echo myvalidate($faiconcolor); ?>
</div>

<div class="section-body">
    <div class="row">
        <div class="col-md-4">	
            <div class="card">
                <div class="card-header text-center">
                    <h4><?php echo myvalidate($mbrstr['firstname'] . ' ' . $mbrstr['lastname']); ?></h4>
                </div>
                <div class="card-body text-center">
                    <form enctype="multipart/form-data" method="post" role="form" id="update_mbr_image">
                        <input type="hidden" name="hal" value="accountcfg">
                        <img id="mbr_image_btn" width='<?php echo myvalidate($cfgrow['mbrmax_image_width']); ?>' height='<?php echo myvalidate($cfgrow['mbrmax_image_height']); ?>' style='max-width:160px;height:auto;cursor: pointer;' alt="<?php echo myvalidate($mbrstr['username']); ?>" src="<?php echo myvalidate($mbrimgstr); ?>" class="img-fluid rounded-circle img-thumbnail img-shadow author-box-picture mb-2">
                        <input type="file" id="my_file" name="mbr_image" style="display: none;" />
                    </form>
                    <span class="text-small"><i class="fas fa-user-tag fa-fw"></i> <?php echo myvalidate($mbrstr['username'] . ' ' . $mbrstr['kyverifiedimg']); ?></span>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h4><?php echo myvalidate($LANG['g_settings']); ?></h4>
                </div>
                <div class="card-body">
                    <ul class="nav nav-pills flex-column" id="myTab4" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="config-tab1" data-toggle="tab" href="#cfgtab1" role="tab" aria-controls="profile" aria-selected="true"><?php echo myvalidate($LANG['g_profile']); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="config-tab2" data-toggle="tab" href="#cfgtab2" role="tab" aria-controls="account" aria-selected="false"><?php echo myvalidate($LANG['g_account']); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="config-tab3" data-toggle="tab" href="#cfgtab3" role="tab" aria-controls="website" aria-selected="false"><?php echo myvalidate($LANG['g_website']); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="config-tab4" data-toggle="tab" href="#cfgtab4" role="tab" aria-controls="password" aria-selected="false"><?php echo myvalidate($LANG['g_password']); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="config-tab5" data-toggle="tab" href="#cfgtab5" role="tab" aria-controls="extension" aria-selected="false"><?php echo myvalidate($LANG['g_extension']); ?></a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-8">	

            <?php
            if ($cfgtoken['ismbrkyc'] > 0 && $mbrstr['kystatus'] != 1) {
                ?>
                <div class="alert alert-light alert-has-icon text-danger mb-2">
                    <div class="alert-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="alert-body">
                        <div class="alert-title">
                            <?php echo myvalidate($LANG['m_notice']); ?>
                        </div>
                        <div class="row">
                            <div class="col-8">
                                <?php echo myvalidate($LANG['m_kycverifynote']); ?>
                            </div>
                            <div class="col-4 text-center">
                                <?php
                                if ($mbrstr['kyid'] < 1) {
                                    ?>
                                    <a href="javascript:;" data-href="doupkyc.php?redir=dashboard&mdlhashy=<?php echo myvalidate($mdlhashy); ?>" data-poptitle="<i class='fa fa-fw fa-plus'></i> Upload Files" class="openPopup btn btn-secondary"><i class="fa fa-fw fa-upload"></i> <?php echo myvalidate($LANG['g_kycneedupload']); ?></a>
                                    <?php
                                } else if ($mbrstr['kystatus'] == '2') {
                                    ?>
                                    <a href="javascript:;" data-href="doupkyc.php?redir=dashboard&mdlhashy=<?php echo myvalidate($mdlhashy); ?>" data-poptitle="<i class='fa fa-fw fa-plus'></i> Upload Files" class="openPopup btn btn-secondary"><i class="fa fa-fw fa-upload"></i> <?php echo myvalidate($LANG['g_kycneedreupload']); ?></a>
                                    <?php
                                } else if ($mbrstr['kystatus'] = '1') {
                                    echo "<span class='text-info'>" . myvalidate($LANG['g_kycwaitreview']) . "</span>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
            ?>

            <div class="card">
                <form method="post" action="index.php" id="cfgform" oninput='password1.setCustomValidity(password2.value != password1.value ? "Passwords do not match." : "")'>
                    <input type="hidden" name="hal" value="accountcfg">

                    <div class="card-header">
                        <h4><?php echo myvalidate($LANG['g_options']); ?></h4>
                    </div>

                    <div class="card-body">
                        <div class="tab-content no-padding" id="myTab2Content">

                            <div class="tab-pane fade show active" id="cfgtab1" role="tabpanel" aria-labelledby="config-tab1">
                                <p class="text-muted"><?php echo myvalidate($LANG['m_profileaccnote']); ?></p>

                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label><?php echo myvalidate($LANG['g_firstname']); ?> <span class="text-danger">*</span></label>
                                        <input type="text" name="firstname" id="firstname" class="form-control" value="<?php echo isset($mbrstr['firstname']) ? $mbrstr['firstname'] : ''; ?>" placeholder="<?php echo myvalidate($LANG['m_userfirstname']); ?>"<?php echo myvalidate($readonlyname); ?> required>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label><?php echo myvalidate($LANG['g_lastname']); ?> <span class="text-danger">*</span></label>
                                        <input type="text" name="lastname" id="lastname" class="form-control" value="<?php echo isset($mbrstr['lastname']) ? $mbrstr['lastname'] : ''; ?>" placeholder="<?php echo myvalidate($LANG['m_userlastname']); ?>"<?php echo myvalidate($readonlyname); ?> required>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>Email <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <div class="input-group-text"><i class="fa fa-fw fa-envelope"></i></div>
                                            </div>
                                            <input type="email" name="email" id="email" class="form-control" value="<?php echo isset($mbrstr['email']) ? $mbrstr['email'] : ''; ?>" placeholder="<?php echo myvalidate($LANG['m_useremail']); ?>"<?php echo myvalidate($readonlyemail); ?> required>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <?php
                                        $birthdayarr = explode('-', $mbrstr['birthday'] ?? '');

                                        $bodday = "<option value='' disabled>...</option>";
                                        for ($i = 1; $i <= 31; $i++) {
                                            $oi = sprintf("%02d", $i);
                                            $isselectstr = ($birthdayarr[2] == $oi) ? ' selected' : '';
                                            $bodday .= "<option value='{$oi}'{$isselectstr}>{$i}</option>";
                                        }

                                        $month_array = explode(',', str_replace(' ', '', $LANG['g_monthstrlist']) ?? '');
                                        $bodmonth = "<option value='' disabled>...</option>";
                                        foreach ($month_array as $key => $month) {
                                            $keyadj = $key + 1;
                                            $isselectstr = ($birthdayarr[1] == $keyadj) ? ' selected' : '';
                                            $bodmonth .= "<option value='{$keyadj}'{$isselectstr}>{$month}</option>";
                                        }

                                        $yearnow = date("Y");
                                        $bodyear = "<option value='' disabled>...</option>";
                                        for ($i = $yearnow; $i >= $yearnow - 99; $i--) {
                                            $isselectstr = ($birthdayarr[0] == $i) ? ' selected' : '';
                                            $bodyear .= "<option value='{$i}'{$isselectstr}>{$i}</option>";
                                        }
                                        ?>
                                        <label for="birthday"><?php echo myvalidate($LANG['g_birthday']); ?></label>
                                        <div class="input-group">
                                            <select name="bodday" class="custom-select" required>
                                                <?php echo myvalidate($bodday); ?>
                                            </select>
                                            <select name="bodmonth" class="custom-select" required>
                                                <?php echo myvalidate($bodmonth); ?>
                                            </select>
                                            <select name="bodyear" class="input-group-append custom-select" required>
                                                <?php echo myvalidate($bodyear); ?>
                                            </select>
                                        </div>
                                        <div class="invalid-feedback">
                                            <?php echo myvalidate($LANG['g_regbirthday']); ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-12">
                                        <label for="selectgroup-pills"><?php echo myvalidate($LANG['m_notifoptin']); ?></label>
                                        <div class="selectgroup selectgroup-pills">
                                            <label class="selectgroup-item">
                                                <input type="radio" name="optinme" value="0" class="selectgroup-input"<?php echo myvalidate($optinme_cek[0]); ?>>
                                                <span class="selectgroup-button selectgroup-button-icon"><i class="fa fa-fw fa-times"></i> <?php echo myvalidate($LANG['g_no']); ?></span>
                                            </label>
                                            <label class="selectgroup-item">
                                                <input type="radio" name="optinme" value="1" class="selectgroup-input"<?php echo myvalidate($optinme_cek[1]); ?>>
                                                <span class="selectgroup-button selectgroup-button-icon"><i class="fa fa-fw fa-check"></i> <?php echo myvalidate($LANG['g_yes']); ?></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label><?php echo myvalidate($LANG['m_aboutme']); ?></label>
                                    <textarea name="mbr_intro" class="form-control rowsize-md" id="mbr_intro" placeholder="<?php echo myvalidate($LANG['m_useraboutme']); ?>"><?php echo isset($strmbr_intro) ? $strmbr_intro : ''; ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label><?php echo myvalidate($LANG['m_address']); ?></label>
                                    <textarea name="address" class="form-control rowsize-sm" id="address" rows="16" placeholder="<?php echo myvalidate($LANG['m_useraddress']); ?>"><?php echo isset($mbrstr['address']) ? $mbrstr['address'] : ''; ?></textarea>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label><?php echo myvalidate($LANG['m_state']); ?></label>
                                        <input type="text" name="state" id="state" class="form-control" value="<?php echo isset($mbrstr['state']) ? $mbrstr['state'] : ''; ?>" placeholder="<?php echo myvalidate($LANG['m_userstate']); ?>">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label><?php echo myvalidate($LANG['m_country']); ?></label>
                                        <select name="country" id="country" class="form-control">
                                            <?php echo myvalidate($country_menu); ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label><?php echo myvalidate($LANG['m_phone']); ?></label>
                                        <input type="tel" class="tel form-control" name="phone" id="phone" x-autocompletetype="tel" value="<?php echo isset($mbrstr['phone']) ? $mbrstr['phone'] : ''; ?>" placeholder="<?php echo myvalidate($LANG['m_userphone']); ?>">
                                    </div>
                                </div>

                                <div class="form-row">
                                    <input type="hidden" name="mbr_sosmed" value="<?php echo isset($mbrstr['mbr_sosmed']) ? $mbrstr['mbr_sosmed'] : ''; ?>">
                                    <div class="form-group col-md-12">
                                        <label>Bio Profile Page URL (recommended). If you do not have one, <a href="https://peppy.link/bio-profiles" target="_blank" data-toggle="tooltip" title="https://peppy.link/bio-profiles">register</a> for free.</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <div class="input-group-text"><i class="fas fa-fw fa-link"></i></div>
                                            </div>
                                            <input type="text" name="mbr_biopage" id="mbr_biopage" class="form-control" value="<?php echo isset($mbrstr['mbr_biopage']) ? $mbrstr['mbr_biopage'] : ''; ?>" placeholder="<?php echo myvalidate($LANG['m_biopagelink']); ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>Twitter</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <div class="input-group-text"><i class="fab fa-fw fa-twitter"></i></div>
                                            </div>
                                            <input type="text" name="mbr_twitter" id="mbr_twitter" class="form-control" value="<?php echo isset($mbrstr['mbr_twitter']) ? $mbrstr['mbr_twitter'] : ''; ?>" placeholder="<?php echo myvalidate($LANG['m_twitteraccount']); ?>">
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Facebook</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <div class="input-group-text"><i class="fab fa-fw fa-facebook-f"></i></div>
                                            </div>
                                            <input type="text" name="mbr_facebook" id="mbr_facebook" class="form-control" value="<?php echo isset($mbrstr['mbr_facebook']) ? $mbrstr['mbr_facebook'] : ''; ?>" placeholder="<?php echo myvalidate($LANG['m_facebookaccount']); ?>">
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div class="tab-pane fade" id="cfgtab2" role="tabpanel" aria-labelledby="config-tab2">
                                <p class="text-muted"><?php echo myvalidate($LANG['m_profilepaynote']); ?></p>

                                <?php
                                if ($pgdatatokenarr['bank4usr'] == 1) {
                                    ?>
                                    <div class="form-group">
                                        <label><?php echo myvalidate($LANG['g_banktitle']); ?></label>
                                        <div class="input-group mb-2">
                                            <div class="input-group-prepend w-25">
                                                <div class="input-group-text text-small text-muted"><?php echo myvalidate($LANG['g_bankname']); ?></div>
                                            </div>
                                            <input type="text" name="bankname" id="bankname" class="form-control" value="<?php echo isset($mbrbankcfg['bankname']) ? $mbrbankcfg['bankname'] : ''; ?>" placeholder="Your bank name">
                                        </div>
                                        <div class="input-group mb-2">
                                            <div class="input-group-prepend w-25">
                                                <div class="input-group-text text-small text-muted"><?php echo myvalidate($LANG['g_bankaccnumber']); ?></div>
                                            </div>
                                            <input type="text" name="bankaccno" id="bankaccno" class="form-control" value="<?php echo isset($mbrbankcfg['bankaccno']) ? $mbrbankcfg['bankaccno'] : ''; ?>" placeholder="Your bank account number">
                                        </div>
                                        <div class="input-group mb-2">
                                            <div class="input-group-prepend w-25">
                                                <div class="input-group-text text-small text-muted"><?php echo myvalidate($LANG['g_bankaccname']); ?></div>
                                            </div>
                                            <input type="text" name="bankaccname" id="bankaccname" class="form-control" value="<?php echo isset($mbrbankcfg['bankaccname']) ? $mbrbankcfg['bankaccname'] : ''; ?>" placeholder="Your bank account name">
                                        </div>

                                    </div>
                                    <?php
                                }
                                if ($payrow['manualpay4usr'] == 1) {
                                    ?>
                                    <div class="form-group">
                                        <label><?php echo myvalidate($payrow['manualpayname']); ?></label>
                                        <textarea name="manualpayipn" class="form-control rowsize-sm" id="manualpayipn" rows="16" placeholder="<?php echo myvalidate($payrow['manualpayname']); ?>"><?php echo isset($mbrstr['manualpayipn']) ? base64_decode($mbrstr['manualpayipn'] ?? '') : ''; ?></textarea>
                                    </div>
                                    <?php
                                }
                                ?>

                            </div>

                            <div class="tab-pane fade" id="cfgtab3" role="tabpanel" aria-labelledby="config-tab3">
                                <p class="text-muted"><?php echo myvalidate($LANG['m_profilewebnote']); ?></p>
                                <?php
                                $loaderquery = "idmbr={$mbrstr['id']}&do=webscreenshot";
                                $loaderhash = md5($mdlhashy . $loaderquery);
                                $wsimgstr = ($mbrstr['mbrsite_img']) ? $mbrstr['mbrsite_img'] : DEFIMG_SITE;
                                ?>
                                <div class="text-small text-muted">
                                    <img src='<?php echo myvalidate($wsimgstr . '?' . date("mdhis")); ?>' alt='[^-^]' class='mr-3 mb-3 img-fluid rounded' width=''<?php echo myvalidate($cfgrow['mbrmax_image_width']); ?>' height=''<?php echo myvalidate($cfgrow['mbrmax_image_height']); ?>' style='max-width:128px;height:auto;'>
                                    <?php
                                    if ($mbrtokenarr['isdowebscreenshot'] == '1') {
                                        ?>
                                        <span><i class="fa fa-arrow-left mr-1"></i><a href="javascript:;" data-href="../common/callajaxer.php?<?php echo myvalidate($loaderquery); ?>&hash=<?php echo myvalidate($loaderhash); ?>" data-poptitle="<i class='fa fa-fw fa-image'></i> Website Screenshot" class="openPopup">Reload</a></span>
                                        <?php
                                    }
                                    ?>
                                </div>
                                <div class="form-group">
                                    <label><?php echo myvalidate($LANG['m_siteurl']); ?></label>
                                    <input type="text" name="mbrsite_url" id="mbrsite_url" class="form-control" value="<?php echo isset($mbrstr['mbrsite_url']) ? $mbrstr['mbrsite_url'] : ''; ?>" placeholder="<?php echo myvalidate($LANG['m_usersiteurl']); ?>">
                                    <input type="hidden" name="mbrsite_url_old" value="<?php echo isset($mbrstr['mbrsite_url']) ? $mbrstr['mbrsite_url'] : ''; ?>">
                                    <input type="hidden" name="mbrsite_img_old" value="<?php echo isset($mbrstr['mbrsite_img']) ? $mbrstr['mbrsite_img'] : DEFIMG_SITE; ?>">
                                </div>

                                <div class="form-group">
                                    <label><?php echo myvalidate($LANG['m_sitetitle']); ?></label>
                                    <input type="text" name="mbrsite_title" id="mbrsite_title" class="form-control" value="<?php echo isset($mbrstr['mbrsite_title']) ? $mbrstr['mbrsite_title'] : ''; ?>" maxlength="<?php echo myvalidate($cfgrow['mbrmax_title_char']); ?>" placeholder="<?php echo myvalidate($LANG['m_usersitetitle']); ?>">
                                </div>

                                <div class="form-group">
                                    <label><?php echo myvalidate($LANG['m_siteinfo']); ?></label>
                                    <textarea name="mbrsite_desc" class="form-control rowsize-sm" id="mbrsite_desc" rows="16" maxlength="<?php echo myvalidate($cfgrow['mbrmax_descr_char']); ?>" placeholder="<?php echo myvalidate($LANG['m_usersiteinfo']); ?>"><?php echo isset($mbrstr['mbrsite_desc']) ? base64_decode($mbrstr['mbrsite_desc'] ?? '') : ''; ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label><?php echo myvalidate($LANG['m_sitecategory']); ?></label>
                                    <select name="mbrsite_cat" id="mbrsite_cat" class="form-control">
                                        <?php echo myvalidate($mbrsite_cat_menu); ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="selectgroup-pills"><?php echo myvalidate($LANG['m_sitepublish']); ?></label>
                                    <div class="selectgroup selectgroup-pills">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="showsite" value="0" class="selectgroup-input"<?php echo myvalidate($showsite_cek[0]); ?>>
                                            <span class="selectgroup-button selectgroup-button-icon"><i class="fa fa-fw fa-times"></i> <?php echo myvalidate($LANG['g_no']); ?></span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="radio" name="showsite" value="1" class="selectgroup-input"<?php echo myvalidate($showsite_cek[1]); ?>>
                                            <span class="selectgroup-button selectgroup-button-icon"><i class="fa fa-fw fa-check"></i> <?php echo myvalidate($LANG['g_yes']); ?></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="cfgtab4" role="tabpanel" aria-labelledby="config-tab4">
                                <p class="text-muted"><?php echo myvalidate($LANG['m_profilepassnote']); ?></p>

                                <div class="form-row">
                                    <input type="hidden" name="password" value="<?php echo isset($mbrstr['password']) ? $mbrstr['password'] : ''; ?>">
                                    <div class="form-group col-md-6">
                                        <label><?php echo myvalidate($LANG['m_accpass']); ?></label>
                                        <input type="password" class="form-control" name="password2" id="password2" value="" placeholder="<?php echo myvalidate($LANG['m_useraccpass']); ?>">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label><?php echo myvalidate($LANG['m_accpassconfirm']); ?></label>
                                        <input type="password" class="form-control" name="password1" id="password1" value="" placeholder="<?php echo myvalidate($LANG['m_useraccpassconfirm']); ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input name="ischangeok" value="1" type="checkbox" class="custom-control-input" id="ischangeok">
                                        <label class="custom-control-label" for="ischangeok"><?php echo myvalidate($LANG['m_confirmpass']); ?></label>
                                    </div>
                                </div>

                            </div>

                            <div class="tab-pane fade" id="cfgtab5" role="tabpanel" aria-labelledby="config-tab5">
                                <p class="text-muted"><?php echo myvalidate($LANG['m_profilemorenote']); ?></p>

                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label><?php echo myvalidate($LANG['m_urlshortqrcode']); ?></label>
                                        <div class="text-small"><a href="https://peppy.link" target="_blank"><strong>Peppy.link</strong></a> <?php echo myvalidate($LANG['m_urlshortqrcodeinfo']); ?></div>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Peppy API Key <a href="https://peppy.link/user/register" target="_blank" data-toggle="tooltip" title="https://peppy.link/user/register"><i class="fas fa-external-link-alt"></i></a></label>
                                        <input type="text" class="form-control" name="peppymbrapi" id="peppymbrapi" value="<?php echo myvalidate($peppymbrapi); ?>" placeholder="<?php echo myvalidate($LANG['m_peppymbrapiform']); ?>">
                                    </div>
                                </div>

                            </div>

                        </div>
                    </div>

                    <div class="card-footer bg-whitesmoke text-md-right">
                        <button type="reset" name="reset" value="reset" id="reset" class="btn btn-warning">
                            <i class="fa fa-fw fa-undo"></i> <?php echo myvalidate($LANG['g_reset']); ?>
                        </button>
                        <button type="submit" name="submit" value="submit" id="submit" class="btn btn-primary">
                            <i class="fa fa-fw fa-check"></i> <?php echo myvalidate($LANG['g_savechanges']); ?>
                        </button>
                        <input type="hidden" name="dosubmit" value="1">
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

<script language="JavaScript" type="text/javascript">
<!--
    $(document).ready(function () {
        $("#mbr_image_btn").on("click", function () {
            $("#my_file").click();
        });
        $("#my_file").on("change", function () {
            $("#update_mbr_image")[0].submit();
        });

        $('#submit').click(function () {
            var invalidInputs = $('input:invalid, select:invalid');
            if (invalidInputs.length > 0) {
                alert("<?php echo myvalidate($LANG['g_reqfieldalert']); ?>");
                var invalidInput = $(invalidInputs[0]);
                var $closest = invalidInput.closest('.tab-pane');
                var id = $closest.attr('id');
                $('.nav a[href="#' + id + '"]').tab('show');
            }
        });
    });

-->
</script>
