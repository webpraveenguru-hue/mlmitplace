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

$_SESSION['redirto'] = redir_to($FORM['redir']);

$rowstr = [];
if (isset($mbrstr['id']) && $mbrstr['id'] > 0) {
    $condition = " AND kyidmbr = '" . intval($mbrstr['id']) . "' ";
    $rowstr = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_mbrkycs WHERE 1 " . $condition . "");

    $kymbrnote = $rowstr[0]['kymbrnote'];
    $kymbrnote = str_replace('&lt;p&gt;', '<p>', $kymbrnote ?? '');
    $kymbrnote = str_replace('&lt;/p&gt;', '</p>', $kymbrnote ?? '');

    $kytoken = $rowstr[0]['kytoken'];
    $kyctokenarr = get_optionvals($kytoken);
    $editId = $rowstr[0]['kyid'];
}

if (isset($FORM['dosubmit']) && $FORM['dosubmit'] == '1') {
    extract($FORM);

    $redirto = $_SESSION['redirto'];
    $_SESSION['redirto'] = '';

    // upload image 1
    if ($_FILES['kyimgid'] && $_FILES["kyimgid"]["size"] < 1100000) {
        // valid extensions
        $valid_extensions = array('png', 'jpg', 'jpeg');
        $fnameid = $_FILES['kyimgid']['name'];
        // get uploaded file's extension
        $ext = strtolower(pathinfo($fnameid ?? '', PATHINFO_EXTENSION));
        // check's valid format
        if (in_array($ext, $valid_extensions)) {
            $kyimgid = file_get_contents($_FILES['kyimgid']['tmp_name']);
            $kyimgidType = $_FILES['kyimgid']['type'];
        } else {
            $_SESSION['dotoaster'] = "toastr.error('File {$LANG['m_kycimgphotoid']} not supported <strong>Please try again!</strong>', 'Warning');";
            header('location: ' . $redirto);
            exit;
        }
    }

    // upload image 2
    if ($_FILES['kyimgbill'] && $_FILES["kyimgbill"]["size"] < 1100000) {
        // valid extensions
        $valid_extensions = array('png', 'jpg', 'jpeg');
        $fnamebill = $_FILES['kyimgbill']['name'];
        // get uploaded file's extension
        $ext = strtolower(pathinfo($fnamebill ?? '', PATHINFO_EXTENSION));
        // check's valid format
        if (in_array($ext, $valid_extensions)) {
            $kyimgbill = file_get_contents($_FILES['kyimgbill']['tmp_name']);
            $kyimgbillType = $_FILES['kyimgbill']['type'];
        } else {
            $_SESSION['dotoaster'] = "toastr.error('File {$LANG['m_kycimgbill']} not supported <strong>Please try again!</strong>', 'Warning');";
            header('location: ' . $redirto);
            exit;
        }
    }

    $kymbrnote = ($kymbrnote != '') ? "<p>{$kymbrnote}</p>" . $rowstr[0]['kymbrnote'] : $rowstr[0]['kymbrnote'];

    $kyorder = ($editId > 0) ? 1 : 0;

    $kytoken = put_optionvals($kytoken, 'kyimgidtype', $kyimgidType);
    $kytoken = put_optionvals($kytoken, 'kyimgbilltype', $kyimgbillType);
    $kytoken = $data = array(
        'kyimgid' => base64_encode($kyimgid),
        'kyimgbill' => base64_encode($kyimgbill),
        'kymbrnote' => mystriptag($kymbrnote),
        'kystatus' => '0',
        'kyorder' => intval($kyorder),
        'kytoken' => $kytoken,
    );

    if (count($rowstr) > 0) {
        if ($editId > 0) {
            $data_add = array(
                'kyupdate' => $cfgrow['datetimestr'],
            );
            $data = array_merge($data, $data_add);

            $update = $db->update(DB_TBLPREFIX . '_mbrkycs', $data, array('kyid' => $editId));
            if ($update) {
                $_SESSION['dotoaster'] = "toastr.success('File images updated successfully!', 'Success');";
            } else {
                $_SESSION['dotoaster'] = "toastr.warning('{$LANG['g_nomajorchanges']}', 'Info');";
            }
        } else {
            // do nothing
            $_SESSION['dotoaster'] = "toastr.warning('File not added <strong>File exist!</strong>', 'Warning');";
        }
    } else {
        $data_add = array(
            'kyidmbr' => intval($mbrstr['id']),
            'kydate' => $cfgrow['datetimestr'],
            'kyupdate' => $cfgrow['datetimestr'],
        );
        $data = array_merge($data, $data_add);

        $insert = $db->insert(DB_TBLPREFIX . '_mbrkycs', $data);
        if ($insert) {
            $_SESSION['dotoaster'] = "toastr.success('File images added successfully!', 'Success');";
        } else {
            $_SESSION['dotoaster'] = "toastr.error('File not added <strong>Please try again!</strong>', 'Warning');";
        }
    }
    header('location: ' . $redirto);
    exit;
}
?>

<div class="row">
    <div class="col-md-12">

        <p class="text-primary">Fields with <span class="text-danger">*</span> are mandatory!</p>

        <form method="post" action="doupkyc.php" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label><?php echo myvalidate($LANG['m_kycimgphotoid']); ?> <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="file" name="kyimgid" id="kyimgid" class="form-control" value="" accept=".jpg,.jpeg,.png">
                    </div>
                    <div class="form-text text-muted">The image must have a maximum size of 1Mb</div>
                    <?php
                    if ($rowstr[0]['kyimgid'] != '') {
                        ?>
                        <div class="input-group mt-2">
                            <img src="data:<?php echo myvalidate($kyctokenarr['kyimgidtype']); ?>;base64,<?php echo myvalidate($rowstr[0]['kyimgid']); ?>" class='img-fluid' width='128'>
                        </div>
                        <?php
                    } else {
                        echo "<img src='" . DEFIMG_FILE . "' class='img-fluid' width='128'>";
                    }
                    ?>
                </div>
                <div class="form-group col-md-6">
                    <label><?php echo myvalidate($LANG['m_kycimgbill']); ?> <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="file" name="kyimgbill" id="kyimgbill" class="form-control" value="" accept=".jpg,.jpeg,.png">
                    </div>
                    <div class="form-text text-muted">The image must have a maximum size of 1Mb</div>
                    <?php
                    if ($rowstr[0]['kyimgbill'] != '') {
                        ?>
                        <div class="input-group mt-2">
                            <img src="data:<?php echo myvalidate($kyctokenarr['kyimgbilltype']); ?>;base64,<?php echo myvalidate($rowstr[0]['kyimgbill']); ?>" class='img-fluid' width='128'>
                        </div>
                        <?php
                    } else {
                        echo "<img src='" . DEFIMG_FILE . "' class='img-fluid' width='128'>";
                    }
                    ?>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-12">
                    <div class='alert alert-light text-small'><?php echo isset($kymbrnote) ? $kymbrnote : ''; ?></div>
                    <label><?php echo myvalidate($LANG['m_kycnoteimge']); ?></label>
                    <textarea class="form-control rowsize-sm" name="kymbrnote" id="kymbrnote"></textarea>
                </div>
            </div>

            <div class="text-md-right">
                <a href="javascript:;" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-fw fa-times"></i> Cancel</a>
                <button type="submit" name="submit" value="submit" id="submit" class="btn btn-primary">
                    <i class="fa fa-fw fa-check"></i> Submit
                </button>
                <input type="hidden" name="dosubmit" value="1">
                <input type="hidden" name="mdlhashy" value="<?php echo myvalidate($mdlhashy); ?>">
            </div>

        </form>

    </div>

</div>