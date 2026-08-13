<?php
include_once('../common/init.loader.php');

if (verifylog_sess('admin') == '') {
    die('o o p s !');
}
$mdlhashnow = hash('sha256', $mdlhasher . "@doemailrec.php");
if ($mdlhashnow != $FORM['mdlhashnow']) {
    echo myvalidate($LANG['a_loadingmdlcnt']);
    redirpageto('index.php', 1);
    exit;
}

$_SESSION['redirto'] = redir_to($FORM['redir']);

if (isset($FORM['delId']) && $FORM['delId'] != "") {
    $hasdel = md5($FORM['delId'] . date("dH") ?? '');
    if ($FORM['hash'] == $hasdel) {
        $db->doQueryStr("DELETE FROM " . DB_TBLPREFIX . "_emailrec WHERE emrid = '{$FORM['delId']}'");
        $db->doQueryStr("DELETE FROM " . DB_TBLPREFIX . "_emailqueue WHERE emqemrid = '{$FORM['delId']}'");
        $_SESSION['dotoaster'] = "toastr.success('Record deleted successfully!', 'Success');";
    } else {
        $_SESSION['dotoaster'] = "toastr.error('Record deleted failed!', 'Error');";
    }

    header('location: ' . $_SESSION['redirto']);
    $_SESSION['redirto'] = '';
    exit;
}

$editId = intval($FORM['editId']);
$dupId = intval($FORM['dupId']);

$emrmpstatus_array = array(0 => "All Registered Member", 1 => "Registered Member with Membership Active", 2 => "Registered Member with Membership Inactive");
$emrmpstatus_menu = select_opt($emrmpstatus_array);

if ($editId > 0 || $dupId > 0) {
    $thisId = ($editId > 0) ? $editId : $dupId;

    $row = $db->getAllRecords(DB_TBLPREFIX . '_emailrec', '*', ' AND emrid="' . $thisId . '"');
    $rowstr = array();
    foreach ($row as $value) {
        $rowstr = array_merge($rowstr, $value);
    }

    if ($dupId > 0) {
        $emrsubjectstr = '[duplicate] ' . $rowstr['emrsubject'];
        $rowstr['emrdatesent'] = $cfgrow['datetimestr'];
        $rowstr['emrstatus'] = '';
    } else {
        $emrsubjectstr = $rowstr['emrsubject'];
    }

    $_SESSION['redirto'] = redir_to($FORM['redir']);

    $emrfromarr = str_getcsv($rowstr['emrfrom'] ?? '');
    $emrfromname = $emrfromarr[0];
    $emrfromemail = $emrfromarr[1];

    // 5 = low, 3 = Normal, 1 = High
    $emrpriorityarr = array(5, 3, 1);
    $emrpriority_cek = radiobox_opt($emrpriorityarr, $rowstr['emrpriority']);
    $emrstatusarr = array(0, 1);
    $emrstatus_cek = radiobox_opt($emrstatusarr, $rowstr['emrstatus']);

    $emrmpstatus_menu = select_opt($emrmpstatus_array, $rowstr['emrmpstatus']);
}

$emrppidarr = str_getcsv($rowstr['emrppid'] ?? '');
$emrppid_menu = ppdblist($emrppidarr);

if (isset($FORM['dosubmit']) && $FORM['dosubmit'] == '1') {
    extract($FORM);
    $editId = intval($editId);

    $emrfrom = '"' . $emrfromname . '","' . $emrfromemail . '"';
    $emrppids = (is_array($emrppid)) ? '"' . implode('","', $emrppid) . '"' : '';
    $emrdatesentstr = ($emrstatus == 1) ? $cfgrow['datetimestr'] : $emrdatesent;
    $emrtoken = '|admid:0|';

    $data = array(
        'emrupdate' => $cfgrow['datetimestr'],
        'emrfrom' => $emrfrom,
        'emrsubject' => $emrsubject,
        'emrbody' => $emrbody,
        'emrhtmlbody' => $emrhtmlbody,
        'emrpriority' => $emrpriority,
        'emrppid' => $emrppids,
        'emrmpstatus' => intval($emrmpstatus),
        'emrstatus' => intval($emrstatus),
        'emrdatesent' => $emrdatesentstr,
        'emrtoken' => $emrtoken,
        'emradminfo' => mystriptag($emradminfo),
    );

    $redirto = $_SESSION['redirto'];
    $_SESSION['redirto'] = '';

    if (isset($editId) && $editId > 0) {
        // if update newsletter history
        $condition = ' AND emrid = "' . $editId . '" ';
    } else {
        // if new newsletter history exist, keep using old emrsubject
        $condition = ' AND emrsubject LIKE "' . $emrsubject . '" AND emrsubject != "" ';
    }
    $sql = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_emailrec WHERE 1 " . $condition . "");
    if (count($sql) > 0 && $dupId < 1) {
        if ($editId > 0) {
            $update = $db->update(DB_TBLPREFIX . '_emailrec', $data, array('emrid' => $editId));
            if ($update) {
                $_SESSION['dotoaster'] = "toastr.success('Record updated successfully!', 'Success');";
            } else {
                $_SESSION['dotoaster'] = "toastr.warning('{$LANG['g_nomajorchanges']}', 'Info');";
            }
            $getemrid = $editId;
        } else {
            // do nothing
            $_SESSION['dotoaster'] = "toastr.warning('Record not added <strong>Newsletter exist!</strong>', 'Warning');";
        }
    } else {
        $data['emrdatetm'] = $cfgrow['datetimestr'];
        $insertid = $db->insert(DB_TBLPREFIX . '_emailrec', $data);
        if ($insertid) {
            $_SESSION['dotoaster'] = "toastr.success('Record added successfully!', 'Success');";
            $getemrid = $db->lastInsertId();
        } else {
            $_SESSION['dotoaster'] = "toastr.error('Record not added <strong>Please try again!</strong>', 'Warning');";
        }
    }

    // process sending email now
    if (defined('ISDEMOMODE')) {
        $_SESSION['dotoaster'] = "toastr.error('Demo Mode - Sending newsletter disable!', 'Error');";
    } else if ($emrstatus == 1 && $getemrid > 0) {
        $totinqueue = get_newsletter($getemrid);
        $_SESSION['dotoaster'] .= "toastr.warning('Recipient in the queue is {$totinqueue}', 'Info');";
    }

    header('location: ' . $redirto);
    exit;
}
?>

<div class="row">
    <div class="col-md-12">
        <?php
        if ($FORM['viewId'] > 0 && $editId == intval($FORM['viewId'])) {

            // ----- start get random recipient as preview only
            $condition = '';
            // program
            $emrppidarr = str_getcsv($rowstr['emrppid'] ?? '');
            $condition .= ($rowstr['emrppid'] != '' && is_array($emrppidarr)) ? " AND (mppid = '" . implode("' OR mppid = '", $emrppidarr) . "')" : '';
            // avaibility
            $condition .= ($rowstr['emrmpstatus'] > 0) ? " AND mpstatus = '{$rowstr['emrmpstatus']}'" : '';

            $testemrow = $db->getAllRecords(DB_TBLPREFIX . "_mbrs LEFT JOIN " . DB_TBLPREFIX . "_mbrplans ON id = idmbr", '*', $condition . " ORDER BY RAND() LIMIT 1");
            $exmplrow = $testemrow[0];
            // ----- end get random recipient as preview only

            $emqsubject = parsenotify($exmplrow, $rowstr['emrsubject']);
            $emqbody = parsenotify($exmplrow, $rowstr['emrbody']);
            $emqhtmlbody = parsenotify($exmplrow, $rowstr['emrhtmlbody']);
            $clearfixer = "<div class='clearfix'></div>";
            ?>
            <div class="text-small text-muted">Subject:</div>
            <h6 class="mb-4 ml-4">
                <?php echo myvalidate($emqsubject); ?>
            </h6>
            <div class="text-small text-muted">Message:</div>
            <div class="mb-4 ml-4">
                <?php echo myvalidate($emqhtmlbody . $clearfixer); ?>
            </div>
            <hr />
            <div class="text-md-right">
                <a href="javascript:;" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-fw fa-times"></i> Close</a>
            </div>
            <?php
        } else {
            ?>
            <p class="text-primary">Fields with <span class="text-danger">*</span> are mandatory!</p>
            <form method="post" action="doemailrec.php">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Sender Name<span class="text-danger">*</span></label>
                        <input type="text" name="emrfromname" id="emrfromname" class="form-control" value="<?php echo isset($emrfromname) ? $emrfromname : "{$cfgrow['site_emailname']}"; ?>" placeholder="Sender name" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Sender Email<span class="text-danger">*</span></label>
                        <input type="text" name="emrfromemail" id="emrfromemail" class="form-control" value="<?php echo isset($emrfromemail) ? $emrfromemail : "{$cfgrow['site_emailaddr']}"; ?>" placeholder="Sender email" required readonly="">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-10">
                        <label>Subject <span class="text-danger">*</span></label>
                        <input type="text" name="emrsubject" id="emrsubject" class="form-control" value="<?php echo isset($emrsubjectstr) ? $emrsubjectstr : ''; ?>" placeholder="Newsletter subject" required>
                    </div>
                    <div class="form-group col-md-2">
                        <label>Available Tags</label>
                        <div class="text-monospace text-small">[[firstname]]<br />[[fullname]]</div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-10">
                        <label>Message (HTML) <span class="text-danger">*</span></label>
                        <textarea name="emrhtmlbody" id="summernotemodal" class="form-control" placeholder="Newsletter content in HTML format" required><?php echo isset($rowstr['emrhtmlbody']) ? $rowstr['emrhtmlbody'] : ''; ?></textarea>
                    </div>
                    <div class="form-group col-md-2">
                        <label>Available Tags</label>
                        <div class="text-monospace text-small">
                            [[fullname]]<br />
                            [[firstname]]<br />
                            [[lastname]]<br />
                            [[username]]<br />
                            [[email]]<br />
                            [[phone]]<br />
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-10">
                        <label>Message (Plain Text) <span class="text-danger">*</span></label>
                        <textarea name="emrbody" id="emrbody" class="form-control rowsize-md" placeholder="Newsletter content in plain text" required><?php echo isset($rowstr['emrbody']) ? $rowstr['emrbody'] : ''; ?></textarea>
                    </div>
                    <div class="form-group col-md-2">
                        <label>Available Tags</label>
                        <div class="text-monospace text-small">
                            [[fullname]]<br />
                            [[firstname]]<br />
                            [[lastname]]<br />
                            [[username]]<br />
                            [[email]]<br />
                            [[phone]]<br />
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Recipient Program</label>
                        <select name="emrppid[]" id="emrppid" class="form-control" multiple="multiple" data-height="100%">
                            <?php echo myvalidate($emrppid_menu); ?>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Recipient Availability</label>
                        <select name="emrmpstatus" id="emrmpstatus" class="form-control select1">
                            <?php echo myvalidate($emrmpstatus_menu); ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="selectgroup-pills">Priority</label>
                        <div class="selectgroup selectgroup-pills">
                            <label class="selectgroup-item">
                                <input type="radio" name="emrpriority" value="5" class="selectgroup-input"<?php echo myvalidate($emrpriority_cek[0]); ?>>
                                <span class="selectgroup-button selectgroup-button-icon"><i class="fas fa-fw fa-thermometer-empty"></i> Low</span>
                            </label>
                            <label class="selectgroup-item">
                                <input type="radio" name="emrpriority" value="3" class="selectgroup-input"<?php echo myvalidate($emrpriority_cek[1]); ?>>
                                <span class="selectgroup-button selectgroup-button-icon"><i class="fas fa-fw fa-thermometer-half"></i> Normal</span>
                            </label>
                            <label class="selectgroup-item">
                                <input type="radio" name="emrpriority" value="1" class="selectgroup-input"<?php echo myvalidate($emrpriority_cek[2]); ?>>
                                <span class="selectgroup-button selectgroup-button-icon"><i class="fas fa-fw fa-thermometer-full"></i> High</span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="selectgroup-pills">Status</label>
                        <div class="selectgroup selectgroup-pills">
                            <label class="selectgroup-item">
                                <input type="radio" name="emrstatus" value="0" class="selectgroup-input"<?php echo myvalidate($emrstatus_cek[0]); ?> required>
                                <span class="selectgroup-button selectgroup-button-icon"><i class="fas fa-fw fa-clipboard"></i> Draft</span>
                            </label>
                            <label class="selectgroup-item">
                                <input type="radio" name="emrstatus" value="1" class="selectgroup-input"<?php echo myvalidate($emrstatus_cek[1]); ?>>
                                <span class="selectgroup-button selectgroup-button-icon"><i class="fas fa-fw fa-paper-plane"></i> Send Now</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label>Note (Admin Only)</label>
                        <textarea class="form-control" name="emradminfo" id="emradminfo" placeholder="Newsletter description or keywords, available for Admin only"><?php echo isset($rowstr['emradminfo']) ? $rowstr['emradminfo'] : ''; ?></textarea>
                    </div>
                </div>

                <div class="text-md-right">
                    <a href="javascript:;" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-fw fa-times"></i> Cancel</a>
                    <button type="submit" name="submit" value="submit" id="submit" class="btn btn-primary">
                        <i class="fa fa-fw fa-check"></i> Submit
                    </button>
                    <input type="hidden" name="editId" value="<?php echo myvalidate($editId); ?>">
                    <input type="hidden" name="dosubmit" value="1">
                    <input type="hidden" name="mdlhashnow" value="<?php echo myvalidate($mdlhashnow); ?>">
                </div>
            </form>
            <?php
        }
        ?>

    </div>
</div>