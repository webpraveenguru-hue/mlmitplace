<?php
include_once('../common/init.loader.php');

if (verifylog_sess('admin') == '') {
    die('o o p s !');
}
if ($mdlhasher != $FORM['mdlhasher']) {
    echo myvalidate($LANG['a_loadingmdlcnt']);
    redirpageto('index.php', 1);
    exit;
}

$_SESSION['redirto'] = redir_to($FORM['redir']);

$redirto = $_SESSION['redirto'];
$_SESSION['redirto'] = '';

if (isset($FORM['doupbnfile']) && $FORM['doupbnfile'] == '1') {

    $newbanner = 0;
    // Count total files
    $countfiles = count($_FILES['bnfiles']['name']);
    // Upload directory
    $upload_location = "../" . BANNER_FOLDER . '/';
    // Loop all files
    for ($i = 0; $i < $countfiles; $i++) {
        if (isset($_FILES['bnfiles']['name'][$i]) && $_FILES['bnfiles']['name'][$i] != '') {
            // File name
            $filename = $_FILES['bnfiles']['name'][$i];
            // Get extension
            $ext = strtolower(pathinfo($filename ?? '', PATHINFO_EXTENSION));
            // Valid image extension
            $valid_ext = array("png", "jpeg", "jpg");
            // Check extension
            if (in_array($ext, $valid_ext)) {
                // File path
                $newfilename = $filename;
                $path = $upload_location . $newfilename;
                // Upload file
                if (move_uploaded_file($_FILES['bnfiles']['tmp_name'][$i], $path)) {

                    $bnfilestr = "../" . BANNER_FOLDER . '/' . $newfilename;
                    $getfilesize = filesize($bnfilestr);
                    $fsizestr = read_file_size($getfilesize);
                    list($imgwidth, $imgheight, $imgtype, $imgattr) = getimagesize($bnfilestr);

                    $bntoken = "|fsize:{$fsizestr}|, |imgsize:{$imgwidth}x{$imgheight}|, |imgattr:{$imgattr}|";
                    // add to database
                    $data = array(
                        'bndate' => $cfgrow['datestr'],
                        'bnfile' => $filename,
                        'bntoken' => $bntoken,
                    );
                    $insert = $db->insert(DB_TBLPREFIX . '_banners', $data);
                    $newbanner++;
                }
            }
        }
    }

    if ($newbanner > 0) {
        $_SESSION['dotoaster'] = "toastr.success('New banner added successfully!', 'Success');";
    } else {
        $_SESSION['dotoaster'] = "toastr.error('Banner failed to upload. Please try again and make sure file format and size are supported.', 'Warning');";
    }

    header('location: ' . $redirto);
    exit;
}

if (isset($FORM['bnID']) && $FORM['bnID'] > 0) {
    $condition = ' AND bnid LIKE "' . $FORM['bnID'] . '" ';
    $sql = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_banners WHERE 1 " . $condition . "");
    $bannerStr = $sql[0];

    $bnstatus_arr = array('0' => $LANG['g_inactive'], '1' => $LANG['g_active']);
    $bnstatus_menu = select_opt($bnstatus_arr, $bannerStr['bnstatus']);
}

if (isset($FORM['dosubmit']) && $FORM['dosubmit'] == '1') {
    extract($FORM);
    $editId = intval($bnID);

    $bnfilestr = "../" . BANNER_FOLDER . '/' . $bannerStr['bnfile'];
    $getfilesize = filesize($bnfilestr);
    $fsizestr = read_file_size($getfilesize);
    list($imgwidth, $imgheight, $imgtype, $imgattr) = getimagesize($bnfilestr);

    $bntoken = $bannerStr['bntoken'];
    $bntoken = put_optionvals($bntoken, 'fsize', $fsizestr);
    $bntoken = put_optionvals($bntoken, 'imgsize', "{$imgwidth}x{$imgheight}");
    $bntoken = put_optionvals($bntoken, 'imgattr', $imgattr);

    $data = array(
        'bndate' => $cfgrow['datestr'],
        'bntitle' => mystriptag($bntitle),
        'bnstatus' => intval($bnstatus),
        'bntoken' => $bntoken,
        'bnadminfo' => mystriptag($bnadminfo),
    );
    $update = $db->update(DB_TBLPREFIX . '_banners', $data, array('bnid' => $editId));

    if ($update) {
        $_SESSION['dotoaster'] = "toastr.success('Record updated successfully!', 'Success');";
    } else {
        $_SESSION['dotoaster'] = "toastr.error('Record not updated <strong>Please try again!</strong>', 'Warning');";
    }

    header('location: ' . $redirto);
    exit;
}

if (isset($FORM['delId']) && $FORM['delId'] > 0) {

    $delId = intval($FORM['delId']);
    $hasdel = md5($delId . date("dH"));
    if ($FORM['hash'] == $hasdel) {
        if (defined('ISDEMOMODE') && $delId <= 1) {
            $_SESSION['dotoaster'] = "toastr.error('Demo Mode - Delete banner failed!', 'Error');";
        } else {
            $condition = ' AND bnid LIKE "' . $delId . '" ';
            $sql = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_banners WHERE 1 " . $condition . "");
            $bannerStr = $sql[0];
            $bnfile = $bannerStr['bnfile'];
            unlink("../" . BANNER_FOLDER . '/' . $bnfile);

            $db->delete(DB_TBLPREFIX . '_banners', array('bnid' => $delId));
            $_SESSION['dotoaster'] = "toastr.success('Banner removed successfully!', 'Success');";
        }
    } else {
        $_SESSION['dotoaster'] = "toastr.error('Banner failed to remove!', 'Error');";
    }

    $redirto = redir_to($FORM['redir']);
    header('location: ' . $redirto);
    exit;
}
?>

<div class="row">
    <div class="col-md-12">

        <?php
        if (isset($FORM['bnID']) && $FORM['bnID'] > 0) {
            ?>
            <p class="text-primary">Fields with <span class="text-danger">*</span> are mandatory!</p>

            <form method="post" action="dobanner.php">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>File</label>
                        <div id="resultGetMbr"><?php echo myvalidate($bannerStr['bnfile']); ?></div>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Title <span class="text-danger">*</span></label>
                        <input type="text" name="bntitle" id="bntitle" class="form-control" value="<?php echo myvalidate($bannerStr['bntitle']); ?>" placeholder="Enter banner title" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Status <span class="text-danger">*</span></label>
                        <select name="bnstatus" id="bnstatus" class="form-control">
                            <?php echo myvalidate($bnstatus_menu); ?>
                        </select>

                    </div>
                    <div class="form-group col-md-6">
                        <label>Note</label>
                        <input type="text" name="bnadminfo" id="bnadminfo" class="form-control" value="<?php echo myvalidate($bannerStr['bnadminfo']); ?>" minlength="3" placeholder="Enter banner description (optional)">
                    </div>
                </div>

                <div class="text-md-right">
                    <a href="javascript:;" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-fw fa-times"></i> Cancel</a>
                    <button type="submit" name="submit" value="submit" id="submit" class="btn btn-primary">
                        <i class="fa fa-fw fa-check"></i> Submit
                    </button>
                    <input type="hidden" name="bnID" value="<?php echo myvalidate($FORM['bnID']); ?>">
                    <input type="hidden" name="dosubmit" value="1">
                    <input type="hidden" name="redir" value="mmbanner">
                    <input type="hidden" name="mdlhasher" value="<?php echo myvalidate($mdlhasher); ?>">
                </div>
            </form>
            <?php
        } else {
            ?>
            <form method="post" action="dobanner.php" enctype="multipart/form-data">
                <div class="form-group">
                    <div class="custom-file">
                        <input type="file" id="file" name="bnfiles[]" multiple class="custom-file-input" id="customFile" accept=".jpg,.jpeg,.png">
                        <label class="custom-file-label" for="customFile">Choose file</label>
                    </div>
                    <div class="alert alert-light text-small text-info mt-1">Select multiple image files (.jpg, .jpeg, and .png) with reasonable file sizes to upload multiple banner at once, make sure your server support it.</div>
                    <div class="text-md-center mt-4">
                        <a href="javascript:;" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-fw fa-times"></i> Close</a>
                        <button type='submit' name='submit' id="doflupload" class="btn btn-primary"><i class="fas fa-fw fa-upload"></i> Upload</button>
                    </div>
                </div>
                <input type="hidden" name="doupbnfile" value="1">
                <input type="hidden" name="redir" value="mmbanner">
                <input type="hidden" name="mdlhasher" value="<?php echo myvalidate($mdlhasher); ?>">
            </form>
            <?php
        }
        ?>

    </div>

</div>
