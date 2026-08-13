<?php


function do_hcaptcha($captchafor, $captchastr = '') {
    global $cfgtoken;

    if ($captchastr == '') {
        $showcapcal = '';
        if ($cfgtoken['ishcaptcha'] == 1 && ($cfgtoken[$captchafor] == 1 || $captchafor == 'ishcaptcha')) {
            $showcapcal .= "<script src='https://www.hCaptcha.com/1/api.js' async defer></script>";
            $showcapcal .= <<<INI_HTML
                            <script type="text/javascript">
                                $("form").on('submit', function(event) {
                                   var hcaptchaVal = $('[name=h-captcha-response]').value;
                                   if (hcaptchaVal === "") {
                                      event.preventDefault();
                                      alert("Please resolve the hCaptcha");
                                   }
                                });
                            </script>
INI_HTML;
            $showcapcal .= '<div class="h-captcha" data-sitekey="' . $cfgtoken['hc_sitekey'] . '"></div>';
        }
        return $showcapcal;
    } else {
        $ishcapcok = true;
        if ($cfgtoken['ishcaptcha'] == 1 && $cfgtoken['ishcapcregin'] == 1 && isset($captchastr)) {
            $secret = $cfgtoken['hc_secretkey'];
            $data = array(
                'secret' => $secret,
                'response' => $captchastr
            );
            $verify = curl_init();
            curl_setopt($verify, CURLOPT_URL, "https://hcaptcha.com/siteverify");
            curl_setopt($verify, CURLOPT_POST, true);
            curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($verify);

            $responseData = json_decode($response);
            if ($responseData->success) {
                // your success code goes here
            } else {
                $ishcapcok = false;
            }
        }
        return $ishcapcok;
    }
}

function do_recaptcha($captchafor, $captchastr = '') {
    global $cfgrow, $cfgtoken;

    if ($captchastr == '') {
        $showcapcal = '';
        if ($cfgrow['isrecaptcha'] == 1 && ($cfgtoken[$captchafor] == 1 || $captchafor == 'isrecaptcha')) {
            $showcapcal .= '<script src="https://www.google.com/recaptcha/api.js?render=' . $cfgrow['rc_sitekey'] . '"></script>';
            $showcapcal .= <<<INI_HTML
                            <script>
                                grecaptcha.ready(function() {
                                    grecaptcha.execute('{$cfgrow['rc_sitekey']}', {action:'validate_captcha'}).then(function(token) {
                                        document.getElementById('g-recaptcha-response').value = token;
                                    });
                                });
                            </script>
INI_HTML;
            $showcapcal .= '<input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response"><input type="hidden" name="action" value="validate_captcha">';
        }
        return $showcapcal;
    } else {
        $isrecapv3 = true;
        if ($cfgrow['isrecaptcha'] == 1 && $cfgtoken['isrcapcregin'] == 1 && isset($captchastr)) {
            $secret = $cfgrow['rc_securekey'];
            $remoteIp = $_SERVER['REMOTE_ADDR'];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('secret' => $secret, 'response' => $captchastr, 'remoteip' => $remoteIp), '', '&'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);
            $arrResponse = json_decode($response, true);

            $rcscore = floatval($arrResponse["score"] ?? '');
            if ($arrResponse["success"] == '1' && $rcscore >= 0.5) {
                // valid submission
            } else {
                $isrecapv3 = false;
            }
        }
        return $isrecapv3;
    }
}
