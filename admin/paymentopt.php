<?php
if (!defined('OK_LOADME')) {
    die('o o p s !');
}

$pgdatatoken = $payrow['pgdatatoken'];
$pgdatatokenarr = get_optionvals($pgdatatoken);
$paytoken = $payrow['paytoken'];
$paytokenarr = get_optionvals($paytoken);

// ---

$manualpayonarr = array(0, 1);
$manualpayon_cek = radiobox_opt($manualpayonarr, $payrow['manualpayon']);
$manualpay4usr_cek = checkbox_opt($payrow['manualpay4usr']);
$manualpay4store_cek = checkbox_opt($payrow['manualpay4store']);

$perfectmoneycfg = get_optarr($pgdatatokenarr['perfectmoneycfg']);

$payfastonarr = array(0, 1);
$payfaston_cek = radiobox_opt($payfastonarr, $pgdatatokenarr['payfaston']);
$payfast4usr_cek = checkbox_opt($pgdatatokenarr['payfast4usr']);
$payfast4store_cek = checkbox_opt($pgdatatokenarr['payfast4store']);
$payfastcfg = get_optarr($pgdatatokenarr['payfastcfg']);

$ispfsandbox = $payfastcfg['payfastsbox'];
$payfastsboxarr = array(0, 1);
$payfastsbox_cek = radiobox_opt($payfastsboxarr, $ispfsandbox);

$razorpayonarr = array(0, 1);
$razorpayon_cek = radiobox_opt($razorpayonarr, $pgdatatokenarr['razorpayon']);
$razorpay4store_cek = checkbox_opt($pgdatatokenarr['razorpay4store']);
$razorpaycfg = get_optarr($pgdatatokenarr['razorpaycfg']);

$isrpsandbox = $razorpaycfg['razorpaysbox'];
$razorpaysboxarr = array(0, 1);
$razorpaysbox_cek = radiobox_opt($razorpaysboxarr, $isrpsandbox);

$paystackonarr = array(0, 1);
$paystackon_cek = radiobox_opt($paystackonarr, $pgdatatokenarr['paystackon']);
$paystack4usr_cek = checkbox_opt($pgdatatokenarr['paystack4usr']);
$paystack4store_cek = checkbox_opt($pgdatatokenarr['paystack4store']);
$paystackcfg = get_optarr($pgdatatokenarr['paystackcfg']);

$coinpaymentsonarr = array(0, 1);
$coinpaymentson_cek = radiobox_opt($coinpaymentsonarr, $pgdatatokenarr['coinpaymentson']);
$coinpayments4usr_cek = checkbox_opt($pgdatatokenarr['coinpayments4usr']);
$coinpayments4store_cek = checkbox_opt($pgdatatokenarr['coinpayments4store']);
$coinpaymentscfg = get_optarr($pgdatatokenarr['coinpaymentscfg']);

$paypalonarr = array(0, 1);
$paypalon_cek = radiobox_opt($paypalonarr, $pgdatatokenarr['paypalon']);
$paypal4usr_cek = checkbox_opt($pgdatatokenarr['paypal4usr']);
$paypal4store_cek = checkbox_opt($pgdatatokenarr['paypal4store']);
$paypalcfg = get_optarr($pgdatatokenarr['paypalcfg']);

$isppsandbox = $paypalcfg['paypalsbox'];
$paypalsboxarr = array(0, 1);
$paypalsbox_cek = radiobox_opt($paypalsboxarr, $isppsandbox);

$stripeonarr = array(0, 1);
$stripeon_cek = radiobox_opt($stripeonarr, $pgdatatokenarr['stripeon']);
$stripe4usr_cek = checkbox_opt($pgdatatokenarr['stripe4usr']);
$stripe4store_cek = checkbox_opt($pgdatatokenarr['stripe4store']);
$stripecfg = get_optarr($pgdatatokenarr['stripecfg']);

$stripeoptcoarr = array(1, 2);
$stripeoptco_cek = radiobox_opt($stripeoptcoarr, $stripecfg['stripeoptco']);

$bankonarr = array(0, 1);
$bankon_cek = radiobox_opt($bankonarr, $pgdatatokenarr['bankon']);
$bank4usr_cek = checkbox_opt($pgdatatokenarr['bank4usr']);
$bank4store_cek = checkbox_opt($pgdatatokenarr['bank4store']);
$bankcfg = get_optarr($pgdatatokenarr['bankcfg']);

$ewalletonarr = array(0, 1);
$ewalleton_cek = radiobox_opt($ewalletonarr, $pgdatatokenarr['ewalleton']);
$ewallet4store_cek = checkbox_opt($pgdatatokenarr['ewallet4store']);
$ewalletcfg = get_optarr($pgdatatokenarr['ewalletcfg']);

$testpayonarr = array(0, 1);
$testpayon_cek = radiobox_opt($testpayonarr, $payrow['testpayon']);
$testpay4usr_cek = checkbox_opt($payrow['testpay4usr']);

if (isset($FORM['dosubmit']) and $FORM['dosubmit'] == '1') {
    extract($FORM);

    $bankarr = array('bankguide' => base64_encode($bankguide), 'bankname' => $bankname, 'bankaccno' => $bankaccno, 'bankaccname' => $bankaccname, 'bankfee' => $bankfee);
    $bankcfg = put_optarr($pgdatatokenarr['bankcfg'], $bankarr);
    $pgdatatoken = put_optionvals($pgdatatoken, 'bankon', intval($bankon));
    $pgdatatoken = put_optionvals($pgdatatoken, 'bank4usr', intval($bank4usr));
    $pgdatatoken = put_optionvals($pgdatatoken, 'bank4store', intval($bank4store));
    $pgdatatoken = put_optionvals($pgdatatoken, 'bankcfg', $bankcfg);

    $ewalletarr = array('ewalletlabel' => $ewalletlabel, 'ewalletfee' => $ewalletfee);
    $ewalletcfg = put_optarr($pgdatatokenarr['ewalletcfg'], $ewalletarr);
    $pgdatatoken = put_optionvals($pgdatatoken, 'ewalleton', intval($ewalleton));
    $pgdatatoken = put_optionvals($pgdatatoken, 'ewallet4store', intval($ewallet4store));
    $pgdatatoken = put_optionvals($pgdatatoken, 'ewalletcfg', $ewalletcfg);

    $paytoken = put_optionvals($paytoken, 'manualpayguide', base64_encode($manualpayguide));

    $data = array(
        'pgdatatoken' => $pgdatatoken,
        'manualpayon' => intval($manualpayon),
        'manualpaybtn' => $manualpaybtn,
        'manualpayfee' => $manualpayfee,
        'manualpayname' => mystriptag($manualpayname),
        'manualpayipn' => base64_encode($manualpayipn),
        'manualpay4usr' => intval($manualpay4usr),
        'manualpay4store' => intval($manualpay4store),
        'testpayon' => intval($testpayon),
        'testpayfee' => $testpayfee,
        'testpaylabel' => $testpaylabel,
        'testpay4usr' => intval($testpay4usr),
        'paytoken' => $paytoken,
    );

    $condition = " AND paygid = '{$didId}' ";
    $sql = $db->getRecFrmQry("SELECT * FROM " . DB_TBLPREFIX . "_paygates WHERE 1 " . $condition . "");
    if (count($sql) > 0) {
        if (!defined('ISDEMOMODE')) {
            $update = $db->update(DB_TBLPREFIX . '_paygates', $data, array('paygid' => $didId));
            if ($update) {
                $_SESSION['dotoaster'] = "toastr.success('Payment options updated successfully!', 'Success');";
            } else {
                $_SESSION['dotoaster'] = "toastr.warning('{$LANG['g_nomajorchanges']}', 'Info');";
            }
        } else {
            $_SESSION['dotoaster'] = "toastr.warning('{$LANG['g_nomajorchanges']}', 'Demo Mode');";
        }
    } else {
        $insert = $db->insert(DB_TBLPREFIX . '_paygates', $data);
        if ($insert) {
            $_SESSION['dotoaster'] = "toastr.success('Payment options added successfully!', 'Success');";
        } else {
            $_SESSION['dotoaster'] = "toastr.error('Payment options not added <strong>Please try again!</strong>', 'Warning');";
        }
    }
    //header('location: index.php?hal=' . $hal);
    redirpageto('index.php?hal=' . $hal);
    exit;
}

$ispfsandboxstr = ($ispfsandbox == 1) ? "<span class='badge badge-transparent float-right text-small text-warning'><i class='fa fa-fw fa-exclamation'></i></span>" : '';
$isppsandboxstr = ($isppsandbox == 1) ? "<span class='badge badge-transparent float-right text-small text-warning'><i class='fa fa-fw fa-exclamation'></i></span>" : '';
$iconstatuspaystr = ($pgdatatokenarr['payfaston'] == 1 || $pgdatatokenarr['razorpayon'] == 1 || $pgdatatokenarr['stripeon'] == 1 || $pgdatatokenarr['perfectmoneyon'] == 1 || $pgdatatokenarr['paystackon'] == 1 || $pgdatatokenarr['ewalleton'] == 1 || $pgdatatokenarr['paypalon'] == 1 || $pgdatatokenarr['coinpaymentson'] == 1 || $pgdatatokenarr['bankon'] == 1 || $payrow['manualpayon'] == 1) ? "<i class='fa fa-check text-success' data-toggle='tooltip' title='Payment Option is Available'></i>" : "<i class='fa fa-times text-danger' data-toggle='tooltip' title='Payment Option is Unavailable'></i>";
?>

<div class="section-header">
    <h1><i class="fa fa-fw fa-money-bill-wave"></i> <?php echo myvalidate($LANG['a_payment']); ?></h1>
</div>

<div class="section-body">
    <div class="row">
        <div class="col-md-4">	
            <div class="card">
                <div class="card-header">
                    <h4>Gateway</h4>
                    <div class="card-header-action">
                        <?php echo myvalidate($iconstatuspaystr); ?>
                    </div>
                </div>
                <div class="card-body">
                    <ul class="nav nav-pills flex-column" id="myTab4" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="config-cash" data-toggle="tab" href="#paycash" role="tab" aria-controls="cash" aria-selected="true"><?php echo isset($payrow['manualpayname']) ? $payrow['manualpayname'] : 'Manual Payment'; ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="config-ewallet" data-toggle="tab" href="#payewallet" role="tab" aria-controls="ewallet" aria-selected="false">E-Wallet</a>
                        </li>

                        <li class="nav-item">
                            <a href="javascript:;" data-href="../common/plusoffer.php" data-poptitle="Additional Payment Options" class="nav-link text-danger openPopup">More Payment Options</a>
                        </li>
                        <li class="nav-item">
                            <hr />
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="config-test" data-toggle="tab" href="#paytest" role="tab" aria-controls="test" aria-selected="false">System Test</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-8">	
            <div class="card">

                <form method="post" action="index.php" enctype="multipart/form-data" id="payform">
                    <input type="hidden" name="hal" value="paymentopt">

                    <div class="card-header">
                        <h4>Options</h4>
                    </div>

                    <div class="card-body">
                        <div class="tab-content no-padding" id="myTab2Content">

                            <div class="tab-pane fade show active" id="paycash" role="tabpanel" aria-labelledby="config-cash">
                                <p class="text-muted">Use this gateway option to accept manual payment (cash, wire transfer, crypto or coin payments, and other offline or manual payment methods).</p>
                                <p class="text-muted">The following tags available to display dynamic contents:</p>
                                <ul>
                                    <li><strong>[[currencysym]]</strong> = Currency symbol (<?php echo myvalidate($bpprow['currencysym']); ?>).</li>
                                    <li><strong>[[currencycode]]</strong> = Currency code (<?php echo myvalidate($bpprow['currencycode']); ?>).</li>
                                    <li><strong>[[feeamount]]</strong> = Payment processing fee.</li>
                                    <li><strong>[[amount]]</strong> = Registration amount.</li>
                                    <li><strong>[[totamount]]</strong> = Total amount need to pay.</li>
                                    <li><strong>[[payplan]]</strong> = Membership name.</li>
                                </ul>

                                <div class="form-group">
                                    <label for="manualpayname">Payment Name</label>
                                    <input type="text" name="manualpayname" id="manualpayname" class="form-control" value="<?php echo isset($payrow['manualpayname']) ? $payrow['manualpayname'] : 'Manual Payment'; ?>" placeholder="Manual payment name">
                                </div>
                                <div class="form-group">
                                    <label for="manualpayguide">Payment Instructions</label>
                                    <textarea class="form-control rowsize-md summernoteclass" name="manualpayguide" id="manualpayguide" placeholder="Enter the payment instructions here."><?php echo isset($paytokenarr['manualpayguide']) ? base64_decode($paytokenarr['manualpayguide']) : ''; ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="manualpayipn">Payment Account</label>
                                    <textarea class="form-control rowsize-sm summernoteclass" name="manualpayipn" id="manualpayipn" placeholder="Enter the payment account here."><?php echo isset($payrow['manualpayipn']) ? base64_decode($payrow['manualpayipn']) : ''; ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="manualpayfee">Processing Fee</label>
                                    <input type="text" name="manualpayfee" id="manualpayfee" class="form-control" value="<?php echo isset($payrow['manualpayfee']) ? $payrow['manualpayfee'] : '0'; ?>" placeholder="Additional fee">
                                </div>

                                <div class="form-group">
                                    <label for="selectgroup-pills">Gateway Status</label>
                                    <div class="selectgroup selectgroup-pills">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="manualpayon" value="0" class="selectgroup-input"<?php echo myvalidate($manualpayon_cek[0]); ?>>
                                            <span class="selectgroup-button selectgroup-button-icon"><i class="fa fa-fw fa-times"></i> Disable</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="radio" name="manualpayon" value="1" class="selectgroup-input"<?php echo myvalidate($manualpayon_cek[1]); ?>>
                                            <span class="selectgroup-button selectgroup-button-icon"><i class="fa fa-fw fa-check"></i> Enable</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="control-label">Availability</div>
                                    <label class="custom-switch mt-2">
                                        <input type="checkbox" name="manualpay4usr" value="1" class="custom-switch-input"<?php echo myvalidate($manualpay4usr_cek); ?>>
                                        <span class="custom-switch-indicator"></span>
                                        <span class="custom-switch-description">As payout option in the withdrawal</span>
                                    </label>
                                    <label class="custom-switch mt-2">
                                        <input type="checkbox" name="manualpay4store" value="1" class="custom-switch-input"<?php echo myvalidate($manualpay4store_cek); ?>>
                                        <span class="custom-switch-indicator"></span>
                                        <span class="custom-switch-description">As payment option in the Store</span>
                                    </label>
                                </div>

                            </div>

                            <div class="tab-pane fade" id="payewallet" role="tabpanel" aria-labelledby="config-ewallet">
                                <p class="text-muted">Use this gateway option to accept payment using E-Wallet.</p>
                                <p class="text-muted text-small">This payment option will use a member E-Wallet fund and will be processed internally.</p>

                                <div class="form-group">
                                    <label for="ewalletlabel">E-Wallet Label</label>
                                    <input type="text" name="ewalletlabel" id="ewalletlabel" class="form-control" value="<?php echo myvalidate($ewalletcfg['ewalletlabel'] != '') ? $ewalletcfg['ewalletlabel'] : 'E-Wallet'; ?>" placeholder="E-Wallet Label">
                                </div>

                                <div class="form-group">
                                    <label for="ewalletfee">Gateway Fee</label>
                                    <input type="text" name="ewalletfee" id="ewalletfee" class="form-control" value="<?php echo myvalidate($ewalletcfg['ewalletfee'] > 0) ? $ewalletcfg['ewalletfee'] : '0'; ?>" placeholder="Additional fee">
                                </div>

                                <div class="form-group">
                                    <label for="selectgroup-pills">Gateway Status</label>
                                    <div class="selectgroup selectgroup-pills">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="ewalleton" value="0" class="selectgroup-input"<?php echo myvalidate($ewalleton_cek[0]); ?>>
                                            <span class="selectgroup-button selectgroup-button-icon"><i class="fa fa-fw fa-times"></i> Disable</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="radio" name="ewalleton" value="1" class="selectgroup-input"<?php echo myvalidate($ewalleton_cek[1]); ?>>
                                            <span class="selectgroup-button selectgroup-button-icon"><i class="fa fa-fw fa-check"></i> Enable</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="control-label">Availability</div>
                                    <label class="custom-switch mt-2">
                                        <input type="checkbox" name="ewallet4store" value="1" class="custom-switch-input"<?php echo myvalidate($ewallet4store_cek); ?>>
                                        <span class="custom-switch-indicator"></span>
                                        <span class="custom-switch-description">As payment option in the Store</span>
                                    </label>
                                </div>

                            </div>

                            <div class="tab-pane fade" id="paybank" role="tabpanel" aria-labelledby="config-bank">
                                <p class="text-muted">Use this gateway option to accept manual payment using bank account.</p>
                                <p class="text-muted">The following tags available to display dynamic contents:</p>
                                <ul>
                                    <li><strong>[[currencysym]]</strong> = Currency symbol (<?php echo myvalidate($bpprow['currencysym']); ?>).</li>
                                    <li><strong>[[currencycode]]</strong> = Currency code (<?php echo myvalidate($bpprow['currencycode']); ?>).</li>
                                    <li><strong>[[feeamount]]</strong> = Payment processing fee.</li>
                                    <li><strong>[[amount]]</strong> = Registration amount.</li>
                                    <li><strong>[[totamount]]</strong> = Total amount need to pay.</li>
                                    <li><strong>[[payplan]]</strong> = Membership name.</li>
                                </ul>

                                <div class="form-group">
                                    <label for="bankguide">Payment Instructions</label>
                                    <textarea class="form-control rowsize-sm summernoteclass" name="bankguide" id="bankguide" placeholder="Enter the payment instructions here."><?php echo isset($bankcfg['bankguide']) ? base64_decode($bankcfg['bankguide'] ?? '') : ''; ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="bankname">Bank Name</label>
                                    <input type="text" name="bankname" id="bankname" class="form-control" value="<?php echo isset($bankcfg['bankname']) ? $bankcfg['bankname'] : ''; ?>" placeholder="Bank Name">
                                </div>

                                <div class="form-group">
                                    <label for="bankaccno">Bank Account Number</label>
                                    <input type="text" name="bankaccno" id="bankaccno" class="form-control" value="<?php echo isset($bankcfg['bankaccno']) ? $bankcfg['bankaccno'] : ''; ?>" placeholder="Bank Account Number">
                                </div>
                                <div class="form-group">
                                    <label for="bankaccname">Bank Account Name</label>
                                    <input type="text" name="bankaccname" id="bankaccname" class="form-control" value="<?php echo isset($bankcfg['bankaccname']) ? $bankcfg['bankaccname'] : ''; ?>" placeholder="Bank Account Name">
                                </div>

                                <div class="form-group">
                                    <label for="bankfee">Gateway Fee</label>
                                    <input type="text" name="bankfee" id="bankfee" class="form-control" value="<?php echo isset($bankcfg['bankfee']) ? $bankcfg['bankfee'] : '0'; ?>" placeholder="Additional fee">
                                </div>

                                <div class="form-group">
                                    <label for="selectgroup-pills">Gateway Status</label>
                                    <div class="selectgroup selectgroup-pills">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="bankon" value="0" class="selectgroup-input"<?php echo myvalidate($bankon_cek[0]); ?>>
                                            <span class="selectgroup-button selectgroup-button-icon"><i class="fas fa-fw fa-times-circle"></i> Disable</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="radio" name="bankon" value="1" class="selectgroup-input"<?php echo myvalidate($bankon_cek[1]); ?>>
                                            <span class="selectgroup-button selectgroup-button-icon"><i class="fas fa-fw fa-check-circle"></i> Enable</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="control-label">Member Gateway Status</div>
                                    <label class="custom-switch mt-2">
                                        <input type="checkbox" name="bank4usr" value="1" class="custom-switch-input"<?php echo myvalidate($bank4usr_cek); ?>>
                                        <span class="custom-switch-indicator"></span>
                                        <span class="custom-switch-description">As payout option in the withdrawal</span>
                                    </label>
                                    <label class="custom-switch mt-2">
                                        <input type="checkbox" name="bank4store" value="1" class="custom-switch-input"<?php echo myvalidate($bank4store_cek); ?>>
                                        <span class="custom-switch-indicator"></span>
                                        <span class="custom-switch-description">As payment option in the Store</span>
                                    </label>
                                </div>

                            </div>

                            <div class="tab-pane fade" id="paytest" role="tabpanel" aria-labelledby="config-test">
                                <p class="text-muted">Use this gateway option for testing and to simulate member payment.</p>

                                <div class="form-group">
                                    <label for="testpaylabel">Payment Name</label>
                                    <input type="text" name="testpaylabel" id="testpaylabel" class="form-control" value="<?php echo isset($payrow['testpaylabel']) ? $payrow['testpaylabel'] : 'Test Payment'; ?>" placeholder="Gateway Name">
                                </div>

                                <div class="form-group">
                                    <label for="testpayfee">Gateway Fee</label>
                                    <input type="text" name="testpayfee" id="testpayfee" class="form-control" value="<?php echo isset($payrow['testpayfee']) ? $payrow['testpayfee'] : '0'; ?>" placeholder="Additional fee">
                                </div>

                                <div class="form-group">
                                    <label for="selectgroup-pills">Gateway Status (Debug Mode)</label>
                                    <div class="selectgroup selectgroup-pills">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="testpayon" value="0" class="selectgroup-input"<?php echo myvalidate($testpayon_cek[0]); ?>>
                                            <span class="selectgroup-button selectgroup-button-icon"><i class="fa fa-fw fa-times"></i> Disable</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="radio" name="testpayon" value="1" class="selectgroup-input"<?php echo myvalidate($testpayon_cek[1]); ?>>
                                            <span class="selectgroup-button selectgroup-button-icon"><i class="fa fa-fw fa-check"></i> Enable</span>
                                        </label>
                                    </div>
                                </div>

                            </div>

                        </div>
                    </div>

                    <div class="card-footer bg-whitesmoke text-md-right">
                        <button type="reset" name="reset" value="reset" id="reset" class="btn btn-warning">
                            <i class="fa fa-fw fa-undo"></i> Reset
                        </button>
                        <button type="submit" name="submit" value="submit" id="submit" class="btn btn-primary">
                            <i class="fa fa-fw fa-check"></i> Save Changes
                        </button>
                        <input type="hidden" name="dosubmit" value="1">
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>
