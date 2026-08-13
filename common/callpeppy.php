<?php

include_once('../common/init.loader.php');

if ($FORM['do'] == 'get_peppyacc') {
    $peppyaccarr = get_peppyacc($FORM['apik']);

    // check api avaibility and verify it if exist or not
    if ($FORM['valin'] == 'check') {
        if ($peppyaccarr['error'] == 0 && $peppyaccarr['data']['id'] > 0 && $peppyaccarr['data']['username'] != '') {
            $peppyapicheck = '<i class="fas fa-check"></i>';
            if ($peppyaccarr['data']['status'] == 'free') {
                $peppyaccexpiry = strtoupper($peppyaccarr['data']['status']);
                $peppyaccstatus = '<span class="badge badge-secondary">' . $peppyaccexpiry . '</span>';
                $peppyaccnum = 1;
            } else {
                $peppyaccexparr = explode(' ', $peppyaccarr['data']['expires']);
                $peppyaccexpiry = $peppyaccexparr[0];
                $peppyaccstatus = '<span class="badge badge-success"><i class="fas fa-check-circle fa-fw"></i> ' . $peppyaccexpiry . '</span>';
                $peppyaccnum = 2;
            }
        } else {
            $peppyapicheck = '<i class="fas fa-times text-light"></i>';
            $peppyaccstatus = '<span class="badge badge-danger">Invalid Key</span>';
            $peppyaccnum = 0;
        }
        $peppyaccstatus .= '<input type="hidden" name="peppyaccstatus" value="' . $peppyaccexpiry . '">';
        $result['result1'] = $peppyapicheck;
        $result['result2'] = $peppyaccstatus;
        $result['result3'] = $peppyaccnum;
        $response = json_encode($result);
        if ($FORM['nodie'] != 1) {
            die($response);
        }
    }
}