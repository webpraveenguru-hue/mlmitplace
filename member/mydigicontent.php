<?php
if (!defined('OK_LOADME')) {
    die('o o p s !');
}

if ($stcrow['stcvendoron'] != 1 || $mbrstr['isvendor'] != 1) {
    $hal = 'dashboard';
    redirpageto('index.php?hal=' . $hal);
    die();
}

$pgid = mystriptag($FORM['pgid']);
$condition = " AND pgbyidmbr = '{$mbrstr['id']}'";

if (isset($pgid)) {
    $row = $db->getAllRecords(DB_TBLPREFIX . '_pages', '*', " AND pgid = '{$pgid}'" . $condition);
    $pgcntrow = array();
    foreach ($row as $value) {
        $pgcntrow = array_merge($pgcntrow, $value);
    }

    if (md5($pgid . '+' . $mbrstr['id']) == $FORM['del']) {
        $db->delete(DB_TBLPREFIX . '_pages', array('pgid' => $pgid));
        $_SESSION['dotoaster'] = "toastr.success('Custom page deleted successfully!', 'Success');";
        redirpageto('index.php?hal=mydigicontent');
        exit;
    }

    $pgcntrow['pgsubtitle'] = base64_decode($pgcntrow['pgsubtitle'] ?? '');
    $pgcntrow['pgcontent'] = base64_decode($pgcntrow['pgcontent'] ?? '');

    $pgavalon = $pgcntrow['pgavalon'];

    $cntordhash = hash('md5', $pgid . $mdlhashy . date('d_H'));
    $cntviewlink = "index.php?hal=digiview&pgid={$pgid}&pghashid={$cntordhash}";
}

$msgListData = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_pages WHERE 1 {$condition} ORDER BY pgorder");

if (isset($FORM['dosubmit']) and $FORM['dosubmit'] == '1') {

    extract($FORM);
    $pgid = mystriptag($pgid);

    $pgidnew = ($pgidnew != '' && $pgidnew != $pgid) ? $pgidnew . mt_rand(99, 9999) : $pgid;
    $pgidnew = preg_replace('/(\W)+/', '', $pgidnew);

    $pgavalon = put_optionvals($pgavalon, 'mbr', 0);
    $pgavalon = put_optionvals($pgavalon, 'mbpp0', 1);
    $pgavalon = put_optionvals($pgavalon, 'mbpp1', 1);

    $data = array(
        'pgid' => $pgidnew,
        'pgbyidmbr' => $mbrstr['id'],
        'pglang' => mystriptag($pglang),
        'pgmenu' => mystriptag($pgmenu),
        'pgtitle' => mystriptag($pgtitle),
        'pgsubtitle' => base64_encode(mystriptag($pgsubtitle)),
        'pgcontent' => base64_encode($pgcontent),
        'pgavalon' => $pgavalon,
        'pgppids' => '',
        'pgstatus' => '2',
        'pgorder' => intval($pgorder),
    );

    $condition = ' AND pgid LIKE "' . $pgid . '" ' . $condition;
    $sql = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_pages WHERE 1 " . $condition . "");
    if (count($sql) > 0) {
        $update = $db->update(DB_TBLPREFIX . '_pages', $data, array('pgid' => $pgid));
        if ($update) {
            $_SESSION['dotoaster'] = "toastr.success('Custom content updated successfully!', 'Success');";
        } else {
            $_SESSION['dotoaster'] = "toastr.warning('{$LANG['g_nomajorchanges']}', 'Info');";
        }
    } else {
        $insert = $db->insert(DB_TBLPREFIX . '_pages', $data);
        if ($insert) {
            $_SESSION['dotoaster'] = "toastr.success('Custom content added successfully!', 'Success');";
        } else {
            $_SESSION['dotoaster'] = "toastr.error('Custom content not added <strong>Please try again!</strong>', 'Warning');";
        }
    }

    //header('location: index.php?hal=' . $hal);
    redirpageto("index.php?hal={$hal}&pgid={$pgid}");
    exit;
}
?>

<div class="section-header">
    <h1><i class="fa fa-fw fa-magic"></i> <?php echo myvalidate($LANG['m_digicontent']); ?></h1>
</div>

<div class="section-body">
    <div class="row">
        <div class="col-md-4">	
            <div class="card">
                <div class="card-header">
                    <h4>Title</h4>
                </div>
                <form method="get">
                    <div class="card-body">
                        <div class="form-group">
                            <select name="pgid" class="form-control select1">
                                <option value="">-</option>
                                <?php
                                if (count($msgListData) > 0) {
                                    foreach ($msgListData as $val) {
                                        $strsel = ($FORM['pgid'] == $val['pgid']) ? ' selected' : '';
                                        echo "<option value='{$val['pgid']}'{$strsel}>" . $val['pgmenu'] . "</option>";
                                    }
                                } else {
                                    echo "<option disabled>No Record(s) Found!</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="text-right">
                            <button type="button" class="btn btn-primary" onclick="location.href = 'index.php?hal=mydigicontent&pgid=0'">
                                Create New
                            </button>
                            <button type="submit" value="Load" id="load" class="btn btn-info">
                                <i class="fa fa-fw fa-redo"></i> Load
                            </button>
                            <input type="hidden" name="hal" value="mydigicontent">
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-md-8">	
            <div class="card">

                <form method="post" action="index.php" id="msgtplform">
                    <input type="hidden" name="hal" value="mydigicontent">

                    <div class="card-header">
                        <h4>Contents</h4>
                        <?php
                        if ($pgcntrow['pgtitle'] != '') {
                            ?>
                            <div class="card-header-action text-small text-light">
                                <button type="button" class="btn btn-info" onclick="location.href = '<?php echo myvalidate($cntviewlink); ?>'" data-toggle="tooltip" title="Preview: <?php echo myvalidate($pgcntrow['pgtitle']); ?>">
                                    <i class="fa fa-fw fa-binoculars"></i> Preview
                                </button>
                            </div>
                            <?php
                        }
                        ?>
                    </div>

                    <div class="card-body">
                        <p class="text-muted"><?php echo ($FORM['pgid'] != '') ? '' : '<i class="fa fa-fw fa-long-arrow-alt-left"></i> Please select an  item from the drop down list on the left to update or click the <strong>Create New</strong> button to add a new digital product or content!'; ?></p>

                        <?php
                        if ($FORM['pgid'] != '') {
                            if (strlen($FORM['pgid'] ?? '') < 4) {
                                ?>
                                <div class="form-group">
                                    <label for="pgidnew">Page ID</label>
                                    <input type="text" pattern=".{4,64}" name="pgidnew" id="pgidnew" class="form-control" value="<?php echo isset($pgcntrow['pgid']) ? $pgcntrow['pgid'] : ''; ?>" required>
                                    <div class="form-text text-muted"><em>Use only alphanumeric (a-z, A-Z, 0-9) and minimum 4 characters.</em></div>
                                </div>
                                <?php
                            }
                            ?>
                            <input type="hidden" name="pgid" value="<?php echo myvalidate($pgid); ?>">

                            <div class="form-group">
                                <label for="pgmenu">Menu Name</label>
                                <input type="text" name="pgmenu" id="pgmenu" class="form-control" value="<?php echo isset($pgcntrow['pgmenu']) ? $pgcntrow['pgmenu'] : ''; ?>" placeholder="Menu Name" required>
                                <?php echo isset($pgcntrow['pgid']) ? "<div class='form-text text-muted'><em>Page ID: {$pgcntrow['pgid']}</em></div>" : ''; ?>
                            </div>

                            <div class="form-group">
                                <label for="pgtitle">Title</label>
                                <input type="text" name="pgtitle" id="pgtitle" class="form-control" value="<?php echo isset($pgcntrow['pgtitle']) ? $pgcntrow['pgtitle'] : ''; ?>" placeholder="Title" required>
                            </div>
                            <div class="form-group">
                                <label for="pgsubtitle">Subtitle</label>
                                <textarea class="form-control rowsize-sm" name="pgsubtitle" id="pgsubtitle" placeholder="Subtitle or brief description" required><?php echo isset($pgcntrow['pgsubtitle']) ? $pgcntrow['pgsubtitle'] : ''; ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="summernote">Content</label>
                                <textarea class="form-control" name="pgcontent" id="summernotemaxi" placeholder="Page content" required><?php echo isset($pgcntrow['pgcontent']) ? $pgcntrow['pgcontent'] : ''; ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="selectgroup-pills">Language</label>
                                <select name="pglang" class="form-control select1">
                                    <option value="">Default</option>
                                    <?php
                                    $TEMPLANG = $LANG;
                                    $langdir = INSTALL_PATH . "/common/lang";
                                    $langfiles = scandir($langdir);
                                    foreach ($langfiles as $key => $value) {
                                        if (strpos($value, '.lang.php') !== false) {
                                            include($langdir . '/' . $value);
                                            $isdeflang_sel = ($LANG['lang_iso'] == $pgcntrow['pglang']) ? ' selected' : '';
                                            if ($langlistarr[$LANG['lang_iso']] == '') {
                                                continue;
                                            }
                                            echo "<option value='{$LANG['lang_iso']}'{$isdeflang_sel}>" . $translation_str . "</option>";
                                        }
                                    }
                                    $LANG = $TEMPLANG;
                                    $TEMPLANG = '';
                                    ?>
                                </select>
                                </label>
                            </div>

                            <?php
                        }
                        ?>

                    </div>

                    <?php
                    if ($FORM['pgid'] != '') {
                        ?>
                        <div class="card-footer text-md-right">
                            <?php
                            if ($pgcntrow['pgid'] != '0') {
                                ?>
                                <div class="form-group float-left text-left">
                                    <a href="javascript:;" data-href="index.php?pgid=<?php echo myvalidate($pgcntrow['pgid']); ?>&hal=mydigicontent&del=<?php echo md5($pgcntrow['pgid'] . '+' . $mbrstr['id']); ?>" class="btn btn-danger bootboxconfirm" data-poptitle="Page ID: <?php echo myvalidate($pgcntrow['pgid']); ?>" data-popmsg="Are you sure want to delete this custom page?" data-toggle="tooltip" title="Delete: <?php echo myvalidate($pgcntrow['pgtitle']); ?>"><i class="far fa-fw fa-trash-alt"></i> Remove</a>
                                </div>
                                <?php
                            }
                            ?>
                            <button type="reset" name="reset" value="reset" id="reset" class="btn btn-warning">
                                <i class="fa fa-fw fa-undo"></i> Reset
                            </button>
                            <button type="submit" name="submit" value="submit" id="submit" class="btn btn-primary">
                                <i class="fa fa-fw fa-check"></i> Save Changes
                            </button>
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
