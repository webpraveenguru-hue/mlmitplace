<?php
if (!defined('OK_LOADME')) {
    die('o o p s !');
}

$rkId = ($FORM['rkId'] <= $frlmtdcfg['mxranks']) ? mystriptag($FORM['rkId']) : 1;

if (isset($rkId) && intval($rkId) > 0) {
    $rankrow = ranklist($rkId);
    $old_rklogo = ($rankrow['rklogo']) ? $rankrow['rklogo'] : DEFIMG_FILE;
    $rkbgcolor = get_optionvals($rankrow['rktoken'], 'rkbgcolor');
    $rkcmplmin = get_optionvals($rankrow['rktoken'], 'rkcmplmin');

    $rkstatusarr = array(0, 1);
    $rkstatus_cek = radiobox_opt($rkstatusarr, $rankrow['rkstatus']);

    $rktodolistarr = json_decode($rankrow['rktodolist'] ?? '', 1);
    $rkbonuslistarr = json_decode($rankrow['rkbonuslist'] ?? '', 1);

    $rkiscmpl_cek = checkbox_opt($rankrow['rkiscmpl']);
}

if (isset($FORM['dosubmit']) and $FORM['dosubmit'] == '1') {
    extract($FORM);
    $rkExistId = intval($rkExistId);
    $rkId = intval($rkId);

    $rkcmplval = str_replace('%', '', mystriptag(trim($rkcmplval))) . '%';
    $data = array(
        'rkname' => mystriptag($rkname),
        'rkinfo' => $rkinfo,
        'rktodolist' => json_encode($rktodolist),
        'rkbonuslist' => json_encode($rkbonuslist),
        'rkiscmpl' => intval($rkiscmpl),
        'rkcmplval' => $rkcmplval,
        'rkcmplout' => mystriptag($rkcmplout),
        'rkstatus' => intval($rkstatus),
        'rktoken' => $rktoken,
        'rkadminfo' => mystriptag($rkadminfo),
    );

    $rkcmplmin = floatval($rkcmplmin);
    if ($rkExistId > 0) {
        // update existing
        $rankrow = ranklist($rkExistId);
        $rktoken = $rankrow['rktoken'];
        $rktoken = put_optionvals($rktoken, 'rkbgcolor', $rkbgcolor);
        $rktoken = put_optionvals($rktoken, 'rkcmplmin', $rkcmplmin);
        $data['rktoken'] = $rktoken;
        $update = $db->update(DB_TBLPREFIX . '_ranks', $data, array('rkid' => $rkExistId));
        $rkIdUpdate = $rkExistId;
    } else {
        // create new
        $rktoken = "|rkbgcolor:{$rkbgcolor}|, |rkcmplmin:{$rkcmplmin}|";
        $data['rktoken'] = $rktoken;
        $insert = $db->insert(DB_TBLPREFIX . '_ranks', $data);
        $rkIdUpdate = $db->lastInsertId();
    }

    // update logo image
    $rklogo = imageupload('ranklogo' . $rkIdUpdate, $_FILES['rklogo'], $old_rklogo);
    $data = array(
        'rklogo' => $rklogo,
    );
    $update1 = $db->update(DB_TBLPREFIX . '_ranks', $data, array('rkid' => $rkIdUpdate));

    if ($update || $update1) {
        $_SESSION['dotoaster'] = "toastr.success('Rank updated successfully!', 'Success');";
    } elseif ($insert) {
        $_SESSION['dotoaster'] = "toastr.success('Rank created successfully!', 'Success');";
    }

    //header('location: index.php?hal=' . $hal);
    redirpageto("index.php?hal={$hal}&rkId={$rkIdUpdate}");
    exit;
}

function ruleselectopt($rulename, $defopt = '') {
    $selectstr = "<select name='rktodolist[{$rulename}]' id='rktodolist_{$rulename}' class='custom-select'>";
    $optarr = array('' => 'disable', 'or' => 'OR', 'and' => 'AND');
    foreach ($optarr as $key => $value) {
        $optselected = ($key == $defopt) ? ' selected' : '';
        $selectstr .= "<option value='{$key}'{$optselected}>{$value}";
    }
    $selectstr .= "</select>";
    return $selectstr;
}

function intvcmpoolopt() {
    global $rankrow;
    $rkcmplout = $rankrow['rkcmplout'];
    $selectstr = "<select name='rkcmplout' id='rkcmplout' class='custom-select'>";
    $optarr = array('3' => 'Weekly (on Wednesday)', '4' => 'Weekly (on Thursday)', '5' => 'Weekly (on Friday)', '02' => 'Monthly (on the 2nd)', '15' => 'Monthly (on the 15th)', '28' => 'Monthly (on the 28th)');
    foreach ($optarr as $key => $value) {
        $optselected = ($key == $rkcmplout) ? ' selected' : '';
        $selectstr .= "<option value='{$key}'{$optselected}>{$value}";
    }
    $selectstr .= "</select>";
    return $selectstr;
}
?>

<div class="section-header">
    <h1><i class="fa fa-fw fa-medal"></i> <?php echo myvalidate($LANG['a_rank']); ?></h1>
</div>

<div class="section-body">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Rank Level</h4>
                </div>
                <form method="get">
                    <div class="card-body">
                        <div class="form-group">
                            <select name="rkId" class="form-control select2">
                                <?php
                                echo ranklist($rkId, 1, "+");
                                ?>
                            </select>
                        </div>
                        <div class="text-right">
                            <?php
                            if ($payrow['testpayon'] == 1) {
                                $uptopluscnt = file_get_contents("../common/plus.html");
                                ?>
                                <div class="float-left">
                                    <a href="javascript:;" data-href="<?php echo myvalidate($ssysout('SSYS_URL')); ?>/docs/<?php echo myvalidate(strtolower($ssysout('SSYS_NAME'))); ?>/index.php?todo=upgrade" class="btn btn-danger bootboxconfirm mb-4" data-poptitle="Add Rank" data-popmsg="<?php echo myvalidate($uptopluscnt); ?>" data-toggle="tooltip" title="Add more member rank"><i class="fas fa-fw fa-question-circle"></i> Need more?</a>
                                </div>
                                <?php
                            }
                            ?>

                            <button type="button" class="btn btn-primary" onclick="location.href = 'index.php?rkId=0&hal=ranklist'">
                                Create New
                            </button>
                            <button type="submit" value="Load" id="load" class="btn btn-info">
                                <i class="fa fa-fw fa-redo"></i> Load
                            </button>
                            <input type="hidden" name="hal" value="ranklist">
                        </div>
                    </div>
                </form>
            </div>
            <?php
            if ($rkId > 0 && $rankrow['rklogo'] != '') {
                ?>
                <div class="card">
                    <div class="card-body">
                        <div class="chocolat-parent">
                            <div class='text-center'>
                                <img alt="image" src="<?php echo myvalidate($rankrow['rklogo']); ?>" class="img-fluid rounded author-box-picture">
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
            ?>

            <div class="card">
                <form method="post" action="index.php" enctype="multipart/form-data" id="msgtplform">
                    <input type="hidden" name="hal" value="ranklist">

                    <div class="card-header">
                        <h4>Options</h4>
                    </div>

                    <div class="card-body">
                        <?php
                        if (isset($rkId) && $rkId >= 0) {
                            ?>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="rkname">Rank Title</label>
                                    <input type="text" name="rkname" id="rkname" class="form-control" value="<?php echo isset($rankrow['rkname']) ? $rankrow['rkname'] : ''; ?>" placeholder="Rank Title" required>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="rklogo">Rank Logo</label>
                                    <input type="file" accept="image/*" name="rklogo" id="rklogo" class="form-control">
                                    <input type="hidden" name="old_rklogo" value="<?php echo myvalidate($rankrow['rklogo']); ?>">
                                    <div class="form-text text-muted">The image must have a maximum size of 256Kb</div>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="rkbgcolor">Genealogy Node Color</label>
                                    <div class="input-group">
                                        <input type="color" name="rkbgcolor" id="rkbgcolor" class="form-control" value="<?php echo ($rkbgcolor != '') ? $rkbgcolor : '#EEEEEE'; ?>" placeholder="#EEE">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="summernote"><?php echo myvalidate($LANG['g_description']); ?></label>
                                <textarea class="form-control" name="rkinfo" id="summernotemini" placeholder="Rank description"><?php echo isset($rankrow['rkinfo']) ? $rankrow['rkinfo'] : ''; ?></textarea>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="rktodolist_minmydl">Min. Personal Referrals</label>
                                    <div class="input-group">
                                        <?php
                                        $rulemydl = ruleselectopt('rulemydl', $rktodolistarr['rulemydl']);
                                        echo myvalidate($rulemydl);
                                        ?>
                                        <input type="number" name="rktodolist[minmydl]" id="rktodolist_minmydl" class="form-control" value="<?php echo isset($rktodolistarr['minmydl']) ? $rktodolistarr['minmydl'] : '0'; ?>" placeholder="Minimum personal referrals for this rank" required>
                                    </div>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="rktodolist_mintotdl">Min. Total Referrals</label>
                                    <div class="input-group">
                                        <?php
                                        $ruletotdl = ruleselectopt('ruletotdl', $rktodolistarr['ruletotdl']);
                                        echo myvalidate($ruletotdl);
                                        ?>
                                        <input type="number" name="rktodolist[mintotdl]" id="rktodolist_mintotdl" class="form-control" value="<?php echo isset($rktodolistarr['mintotdl']) ? $rktodolistarr['mintotdl'] : '0'; ?>" placeholder="Minimum total referrals for this rank" required>
                                    </div>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="rktodolist_minmydl">Referrals in Program</label>
                                    <?php
                                    $ppid_menu = ppdblist(array($rktodolistarr['myppid']), 0, 1);
                                    ?>
                                    <select name="rktodolist[myppid]" id="rktodolist_myppid" class="form-control">
                                        <?php echo myvalidate($ppid_menu); ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="rkbonuslist_rankcmlist">Rank Reward</label>
                                <textarea class="form-control" name="rkbonuslist[rankcmlist]" id="rkbonuslist_rankcmlist" placeholder="One time bonus or reward when achieve this rank"><?php echo isset($rkbonuslistarr['rankcmlist']) ? $rkbonuslistarr['rankcmlist'] : ''; ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="rkbonuslist_adjcmdrlist">Adjustment - Personal Referral Commission</label>
                                <textarea class="form-control" name="rkbonuslist[adjcmdrlist]" id="rkbonuslist_adjcmdrlist" placeholder="Modified personal referral commission list based on this rank"><?php echo isset($rkbonuslistarr['adjcmdrlist']) ? $rkbonuslistarr['adjcmdrlist'] : ''; ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="rkbonuslist_adjcmlist">Adjustment - Initial Level Commission</label>
                                <textarea class="form-control" name="rkbonuslist[adjcmlist]" id="rkbonuslist_adjcmlist" placeholder="Modified initial referrals commission list based on this rank"><?php echo isset($rkbonuslistarr['adjcmlist']) ? $rkbonuslistarr['adjcmlist'] : ''; ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="rkbonuslist_cmlistrnew">Adjustment - Renewal Level Commission</label>
                                <textarea class="form-control" name="rkbonuslist[cmlistrnew]" id="rkbonuslist_cmlistrnew" placeholder="Modified renewal referral commission list based on this rank"><?php echo isset($rkbonuslistarr['cmlistrnew']) ? $rkbonuslistarr['cmlistrnew'] : ''; ?></textarea>
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" name="rkiscmpl" value="1" class="custom-control-input" id="rkiscmpl"<?php echo myvalidate($rkiscmpl_cek); ?>>
                                    <label class="custom-control-label" for="rkiscmpl">Enable Commission Pool</label>
                                </div>
                            </div>
                            <ul>
                                <li>

                                    <div class="form-row">
                                        <div class="form-group col-md-4">
                                            <label for="rkcmplval">Commission percentage</label>
                                            <input type="text" name="rkcmplval" id="rkcmplval" class="form-control" value="<?php echo isset($rankrow['rkcmplval']) ? $rankrow['rkcmplval'] : ''; ?>" placeholder="Calculated from the commission pool by the eligible member">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="rkcmplmin">Minimum amount to process</label>
                                            <input type="text" name="rkcmplmin" id="rkcmplmin" class="form-control" value="<?php echo myvalidate($rkcmplmin > 0) ? $rkcmplmin : ''; ?>" placeholder="Min commission eligible to process">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="rktodolist_mintotdl">Interval</label>
                                            <div class="input-group">
                                                <?php
                                                $intvcmpool = intvcmpoolopt();
                                                echo myvalidate($intvcmpool);
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </ul>

                            <div class="form-group">
                                <label for="rkadminfo">Note (displayed by Administrator only)</label>
                                <textarea class="form-control rowsize-sm" name="rkadminfo" id="rkadminfo" placeholder="Administration note about this rank"><?php echo isset($rankrow['rkadminfo']) ? $rankrow['rkadminfo'] : ''; ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="selectgroup-pills">Rank Status</label>
                                <div class="selectgroup selectgroup-pills">
                                    <label class="selectgroup-item">
                                        <input type="radio" name="rkstatus" value="0" class="selectgroup-input"<?php echo myvalidate($rkstatus_cek[0]); ?>>
                                        <span class="selectgroup-button selectgroup-button-icon"><i class="fa fa-fw fa-times"></i> Disable</span>
                                    </label>
                                    <label class="selectgroup-item">
                                        <input type="radio" name="rkstatus" value="1" class="selectgroup-input"<?php echo myvalidate($rkstatus_cek[1]); ?>>
                                        <span class="selectgroup-button selectgroup-button-icon"><i class="fa fa-fw fa-check"></i> Enable</span>
                                    </label>
                                </div>
                            </div>

                            <?php
                        } else {
                            ?>
                            <p class="text-muted"><?php echo ($rankrow['rkname'] != '') ? '' : '<i class="fa fa-fw fa-long-arrow-alt-up"></i> Please select the level rank from the list to update or click + (when it is available) to create a new rank!'; ?></p>
                            <?php
                        }
                        ?>

                    </div>

                    <?php
                    if (isset($rkId) && $rkId >= 0) {
                        ?>
                        <div class="card-footer bg-whitesmoke text-md-right">
                            <button type="reset" name="reset" value="reset" id="reset" class="btn btn-warning">
                                <i class="fa fa-fw fa-undo"></i> Reset
                            </button>
                            <button type="submit" name="submit" value="submit" id="submit" class="btn btn-primary" onclick="return VerifyUploadFileSize('rklogo', 'The image size must not exceed', 262144);">
                                <i class="fa fa-fw fa-check"></i> Save Changes
                            </button>
                            <input type="hidden" name="rkId" value="<?php echo myvalidate($rkId); ?>">
                            <input type="hidden" name="rkExistId" value="<?php echo myvalidate($rankrow['rkid']); ?>">
                            <input type="hidden" name="dosubmit" value="1">
                        </div>
                        <?php
                    }
                    ?>

                </form>

            </div>
        </div>
    </div>
</div>
