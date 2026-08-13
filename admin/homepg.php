<?php
if (!defined('OK_LOADME')) {
    die('o o p s !');
}

$homepgarr = array('', 'test', 'redir');
$homepgopt_cek = radiobox_opt($homepgarr, $cfgtoken['homepg']);

$homepgtestclrarr = array('', 'v1', 'o1', 'green1');
$homepgtestclr_cek = radiobox_opt($homepgtestclrarr, $cfgtoken['homepgtestclr']);

$fileindexhomepg = INSTALL_PATH . "/index.php";
$filehomepg = INSTALL_PATH . "/webpage/starter.html";
$filehomepgtest = INSTALL_PATH . "/webpage/evolve2/home.php";

$filedefhomepg = INSTALL_PATH . "/webpage/_starter.html";
$filedefhomepgtest = INSTALL_PATH . "/webpage/evolve2/_home.php";

$filehomepgcnt = (file_exists($filehomepg)) ? file_get_contents($filehomepg) : ((file_exists($filedefhomepg)) ? file_get_contents($filedefhomepg) : '');
$filehomepgtestcnt = (file_exists($filehomepgtest)) ? file_get_contents($filehomepgtest) : ((file_exists($filedefhomepgtest)) ? file_get_contents($filedefhomepgtest) : '');

if (isset($FORM['dosubmit']) && $FORM['dosubmit'] == '1' && !defined('ISDEMOMODE')) {
    extract($FORM);

    $newcfgtoken = $cfgrow['cfgtoken'];
    if ($homepg == 'test') {
        if ($homepgbaktest == '1') {
            file_put_contents($filehomepgtest . '_bak' . date("YmdHis") . '.php', $filehomepgtestcnt);
        }
        if (file_put_contents($filehomepgtest, $homepgtest)) {
            $_SESSION['dotoaster'] = "toastr.success('Example page updated successfully!', 'Success');";
        } else {
            $_SESSION['dotoaster'] = "toastr.error('Example page failed to update <strong>Please try again!</strong>', 'Warning');";
        }
    } else if ($homepg == 'redir') {
        $newcfgtoken = put_optionvals($newcfgtoken, 'homepgredir', base64_encode($homepgredir ?? ''));
        if ($homepgredir != '') {
            $_SESSION['dotoaster'] = "toastr.success('Redirection configured!', 'Success');";
        } else {
            $_SESSION['dotoaster'] = "toastr.error('Redirection empty!', 'Warning');";
        }
    } else {
        if ($homepgbakstarter == '1') {
            file_put_contents($filehomepg . '_bak' . date("YmdHis") . '.html', $filehomepgcnt);
        }
        if (file_put_contents($filehomepg, $homepgstarter)) {
            $_SESSION['dotoaster'] = "toastr.success('Starter page updated!', 'Success');";
        } else {
            $_SESSION['dotoaster'] = "toastr.error('Starter page failed to update <strong>Please try again!</strong>', 'Warning');";
        }
    }

    $newcfgtoken = put_optionvals($newcfgtoken, 'homepg', $homepg);
    $newcfgtoken = put_optionvals($newcfgtoken, 'homepgtestclr', $homepgtestclr);

    $data = array(
        'cfgtoken' => $newcfgtoken,
    );
    $update = $db->update(DB_TBLPREFIX . '_configs', $data, array('cfgid' => '1'));
    if ($update) {
        $_SESSION['dotoaster'] = "toastr.success('Configuration updated successfully!', 'Success');";
    }

    //header('location: index.php?hal=' . $hal);
    redirpageto('index.php?hal=' . $hal);
    exit;
}

$fileindexhomepgcnt = (file_exists($fileindexhomepg)) ? file_get_contents($fileindexhomepg) : '';
?>

<div class="section-header">
    <h1><i class="fa fa-fw fa-satellite-dish"></i> <?php echo myvalidate($LANG['a_homepg']); ?></h1>
</div>

<div class="section-body">
    <div class="row">
        <div class="col-md-12">
            <div class="card">

                <form method="post" action="index.php" id="homepgform">
                    <input type="hidden" name="hal" value="homepg">

                    <div class="card-header">
                        <h4>Page Content</h4>
                    </div>

                    <div class="card-body">
                        <p class="text-muted">Installation folder: <strong><?php echo INSTALL_PATH; ?></strong></p>

                        <?php
                        $isnosave = '';
                        if (strpos($fileindexhomepgcnt, 'INDEX_UNIMATRIX') !== false) {
                            ?>
                            <p class="text-muted"><?php echo myvalidate($LANG['a_homepginfo']); ?></p>

                            <div class="form-group">
                                <div class="selectgroup selectgroup-pills">
                                    <label class="selectgroup-item">
                                        <input type="radio" name="homepg" value="" class="selectgroup-input"<?php echo myvalidate($homepgopt_cek[0]); ?> onchange="doHideShow(document.getElementById('homepg'), '', true, 'dHS_homepg');doHideShow(document.getElementById('homepg'), '', false, 'dHS_homepgtest');doHideShow(document.getElementById('homepg'), '', false, 'dHS_homepgredir');" id="homepg">
                                        <span class="selectgroup-button selectgroup-button-icon"><i class="fa fa-fw fa-cogs"></i> Starter Installation</span>
                                    </label>
                                    <label class="selectgroup-item">
                                        <input type="radio" name="homepg" value="test" class="selectgroup-input"<?php echo myvalidate($homepgopt_cek[1]); ?> onchange="doHideShow(document.getElementById('homepgtest'), 'test', false, 'dHS_homepg');doHideShow(document.getElementById('homepgtest'), 'test', true, 'dHS_homepgtest');doHideShow(document.getElementById('homepgtest'), 'test', false, 'dHS_homepgredir');" id="homepgtest">
                                        <span class="selectgroup-button selectgroup-button-icon"><i class="fa fa-fw fa-laptop-code"></i> Example page</span>
                                    </label>
                                    <label class="selectgroup-item">
                                        <input type="radio" name="homepg" value="redir" class="selectgroup-input"<?php echo myvalidate($homepgopt_cek[2]); ?> onchange="doHideShow(document.getElementById('homepgredir'), 'redir', false, 'dHS_homepg');doHideShow(document.getElementById('homepgredir'), 'redir', false, 'dHS_homepgtest');doHideShow(document.getElementById('homepgredir'), 'redir', true, 'dHS_homepgredir');" id="homepgredir">
                                        <span class="selectgroup-button selectgroup-button-icon"><i class="fa fa-fw fa-location-arrow"></i> Redirection</span>
                                    </label>
                                </div>
                            </div>

                            <div class="subcfg-option" id="dHS_homepg">
                                <div class="form-group">
                                    <p class="text-muted">File location: <?php echo myvalidate($filehomepg); ?>
                                        <?php
                                        if (!is_writable(INSTALL_PATH . "/webpage/")) {
                                            echo "<span class='badge badge-danger'><i class='fas fa-exclamation-triangle fa-fw'></i> The file is not writable!</span>";
                                        }
                                        ?>
                                    </p>
                                    <div>
                                        <label for="homepgstarter" class="text-danger">Basic webmaster or HTML knowledge required to update the page content.</label>
                                    </div>
                                    <textarea class="form-control rowsize-lg text-monospace" name="homepgstarter" id="summernotemaxi"><?php echo isset($filehomepgcnt) ? $filehomepgcnt : ''; ?></textarea>
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" name="homepgbakstarter" value="1" class="custom-control-input" id="homepgbakstarter" checked>
                                        <label class="custom-control-label" for="homepgbakstarter">If possible: save existing file as backup.</label>
                                    </div>
                                </div>
                            </div>
                            <div class="subcfg-option" id="dHS_homepgtest">
                                <script src="../assets/fellow/codemirror-5.65.20/codemirror.js" type="text/javascript"></script>
                                <script src="../assets/fellow/codemirror-5.65.20/htmlmixed.js" type="text/javascript"></script>
                                <script src="../assets/fellow/codemirror-5.65.20/xml.js" type="text/javascript"></script>
                                <script src="../assets/fellow/codemirror-5.65.20/javascript.js" type="text/javascript"></script>

                                <script src="../assets/fellow/codemirror-5.65.20/css.js" type="text/javascript"></script>
                                <link rel="stylesheet" type="text/css" href="../assets/fellow/codemirror-5.65.20/codemirror.css"/>

                                <style>
                                    .preview-wrap {
                                        width:100%;
                                        height:50vh;
                                        border:1px solid #ddd;
                                        border-radius:4px;
                                        overflow-x: auto;
                                        overflow-y: hidden;
                                        position:relative;
                                    }
                                    iframe#previewFrame {
                                        width:100%;
                                        min-width:1028px;
                                        height:100%;
                                        border:0;
                                        transition: opacity 300ms ease;
                                        opacity:1;
                                        display:block;
                                        background:white;
                                    }
                                    /* placeholder overlay saat loading (opsional) */
                                    .loading-overlay {
                                        position:absolute;
                                        inset:0;
                                        display:flex;
                                        align-items:center;
                                        justify-content:center;
                                        pointer-events:none;
                                        font-size:14px;
                                        color:#666;
                                        opacity:0;
                                        transition:opacity 200ms;
                                    }
                                    .loading-overlay.show {
                                        opacity:1;
                                    }
                                </style>

                                <div class="form-group">
                                    <p class="text-muted">File location: <?php echo myvalidate($filehomepgtest); ?>
                                        <?php
                                        if (!is_writable(INSTALL_PATH . "/webpage/evolve2/")) {
                                            echo "<span class='badge badge-danger'><i class='fas fa-exclamation-triangle fa-fw'></i> The file is not writable!</span>";
                                        }
                                        ?>
                                    </p>

                                    <div class="form-group">
                                        <label for="selectgroup-pills">Color Theme Option</label>
                                        <div class="selectgroup selectgroup-pills">
                                            <label class="selectgroup-item">
                                                <input type="radio" name="homepgtestclr" value="" class="selectgroup-input"<?php echo myvalidate($homepgtestclr_cek[0]); ?>>
                                                <span class="selectgroup-button selectgroup-button-icon">Default</span>
                                            </label>
                                            <label class="selectgroup-item">
                                                <input type="radio" name="homepgtestclr" value="v1" class="selectgroup-input"<?php echo myvalidate($homepgtestclr_cek[1]); ?>>
                                                <span class="selectgroup-button selectgroup-button-icon">Violet</span>
                                            </label>
                                            <label class="selectgroup-item">
                                                <input type="radio" name="homepgtestclr" value="o1" class="selectgroup-input"<?php echo myvalidate($homepgtestclr_cek[2]); ?>>
                                                <span class="selectgroup-button selectgroup-button-icon">Orange</span>
                                            </label>
                                            <label class="selectgroup-item">
                                                <input type="radio" name="homepgtestclr" value="green1" class="selectgroup-input"<?php echo myvalidate($homepgtestclr_cek[3]); ?>>
                                                <span class="selectgroup-button selectgroup-button-icon">Green</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div for="homepgpreview" class="alert alert-light text-info mt-4"><strong>Live Preview</strong></div>

                                    <div class="preview-wrap">
                                        <div id="loadingFrame" class="loading-overlay">Loading preview…</div>
                                        <iframe id="previewFrame" sandbox="allow-same-origin allow-forms allow-scripts"></iframe>
                                    </div>

                                    <div for="homepgtest" class="alert alert-light text-danger mt-4"><strong>Programming knowledge required!</strong><br />Please do not remove or update any of the php codes, unless you know what you are doing.</div>
                                    <textarea class="form-control rowsize-lg text-monospace" name="homepgtest" id="ccmircode"><?php echo isset($filehomepgtestcnt) ? $filehomepgtestcnt : ''; ?></textarea>
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" name="homepgbaktest" value="1" class="custom-control-input" id="homepgbaktest">
                                        <label class="custom-control-label" for="homepgbaktest">If possible: save existing file as backup.</label>
                                    </div>
                                </div>
                            </div>
                            <div class="subcfg-option" id="dHS_homepgredir">
                                <div class="form-group">
                                    <label for="homepgredir">Relative file path or URL for the homepage redirection.</label>
                                    <input type="text" name="homepgredir" id="homepgredir" class="form-control" value="<?php echo ($cfgtoken['homepgredir'] != '') ? base64_decode($cfgtoken['homepgredir'] ?? '') : ''; ?>" placeholder="Destination file or URL">
                                </div>
                            </div>
                            <?php
                        } else {
                            $isnosave = 1;
                            ?>
                            <div class="badge badge-danger">Default index.php file no longer available!</div>
                            <?php
                        }
                        ?>

                    </div>

                    <div class="card-footer bg-whitesmoke text-md-right">
                        <?php
                        if ($isnosave != 1) {
                            ?>
                            <button type="reset" name="reset" value="reset" id="reset" class="btn btn-warning">
                                <i class="fa fa-fw fa-undo"></i> Reset
                            </button>
                            <button class="btn btn-primary bootboxformconfirm" type="submit" data-form="homepgform" data-poptitle="Manage Homepage" data-popmsg="Are you sure want to process?<br /><span class='text-small text-danger'>If necessary, back up your existing file by clicking the checkbox.</span>">
                                <i class="fa fa-fw fa-check"></i> Save Changes
                            </button>
                            <input type="hidden" name="dosubmit" value="1">
                            <?php
                        }
                        ?>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
                                            $(document).ready(function () {
<?php
if ($cfgtoken['homepg'] == 'test') {
    echo '$("#dHS_homepg").hide();';
    echo '$("#dHS_homepgredir").hide();';
} else if ($cfgtoken['homepg'] == 'redir') {
    echo '$("#dHS_homepg").hide();';
    echo '$("#dHS_homepgtest").hide();';
} else {
    echo '$("#dHS_homepgtest").hide();';
    echo '$("#dHS_homepgredir").hide();';
}

$testpgcolor = ($cfgtoken['homepgtestclr']) ? '_' . $cfgtoken['homepgtestclr'] : '_default';
?>
                                            });


                                            (function () {
                                                const editor = CodeMirror.fromTextArea(document.getElementById("ccmircode"), {
                                                    mode: "htmlmixed",
                                                    lineNumbers: true,
                                                    tabSize: 4,
                                                    lineWrapping: true,
                                                });

                                                const iframe = document.getElementById('previewFrame');
                                                const loading = document.getElementById('loadingFrame');
                                                let lastScroll = 0;

                                                // Debounce helper
                                                function debounce(fn, wait) {
                                                    let t;
                                                    return function (...args) {
                                                        clearTimeout(t);
                                                        t = setTimeout(() => fn.apply(this, args), wait);
                                                    };
                                                }

                                                function updatePreview(content) {
                                                    var newcontent = content.replaceAll('[[websrcbaseurl]]', "..");
                                                    var newcontent = newcontent.replaceAll('[[websrcbasepath]]', "../assets");
                                                    var newcontent = newcontent.replaceAll('[[websrcpagepath]]', "../webpage/evolve2");
                                                    var newcontent = newcontent.replaceAll('[[lpassetscolorstyle]]', "lpassets/lpstylegdbg<?php echo myvalidate($testpgcolor); ?>.css");
                                                    var newcontent = newcontent.replaceAll('[[site_name]]', "<?php echo myvalidate($cfgrow['site_name']); ?>");

                                                    try {
                                                        lastScroll = iframe.contentWindow.scrollY || 0;
                                                    } catch (e) {
                                                        lastScroll = 0;
                                                    }

                                                    loading.classList.add('show');
                                                    iframe.style.opacity = '0';

                                                    requestAnimationFrame(() => {
                                                        iframe.srcdoc = newcontent;
                                                    });
                                                }

                                                iframe.addEventListener('load', () => {
                                                    setTimeout(() => {
                                                        iframe.style.opacity = '1';
                                                        loading.classList.remove('show');

                                                        try {
                                                            iframe.contentWindow.scrollTo(0, lastScroll);
                                                        } catch (e) {
                                                        }
                                                    }, 100);
                                                });

                                                // Debounced listener
                                                const debounced = debounce(() => {
                                                    updatePreview(editor.getValue());
                                                }, 600);

                                                updatePreview(editor.getValue());

                                                editor.on("change", debounced);
                                            })
                                                    ();

</script>