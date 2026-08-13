<?php

/**
 * Peppy.link functions.
 * v1.2
 *
 * @refer     https://peppy.link/developers
 * @refer     Software or Script System
 *
 * @author    TheWebDev
 * @copyright 2022, 2023, 2024
 * @note      This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE.
 */
function get_peppyacc($apikey = '') {
    global $cfgtoken;

    $peppyapi = ($apikey == '') ? base64_decode($cfgtoken['peppyapi'] ?? '') : $apikey;
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $cfgtoken['shortenby'] . "/account",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 2,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer {$peppyapi}",
            "Content-Type: application/json",
        ),
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
    ));

    $response = curl_exec($ch);
    curl_close($ch);

    /* response:
      {
      "error": 0,
      "data": {
      "id": 1,
      "email": "sample@domain.com",
      "username": "sampleuser",
      "avatar": "https:\/\/domain.com\/content\/avatar.png",
      "status": "pro",
      "expires": "2022-11-15 15:00:00",
      "registered": "2020-11-10 18:01:43"
      }
      }
     *
     */
    $return = json_decode($response ?? '', true);
    return $return;
}

function get_peppydomainlist($apikey = '') {
    global $cfgtoken;

    $peppyapi = ($apikey == '') ? base64_decode($cfgtoken['peppyapi'] ?? '') : $apikey;
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $cfgtoken['shortenby'] . "/domains",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 2,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer {$peppyapi}",
            "Content-Type: application/json",
        ),
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
    ));

    $response = curl_exec($ch);
    curl_close($ch);

    /* response:
      {
      "error": "0",
      "data": {
      "result": 2,
      "perpage": 2,
      "currentpage": 1,
      "nextpage": 1,
      "maxpage": 1,
      "domains": [
      {
      "id": 1,
      "domain": "https:\/\/domain1.com",
      "redirectroot": "https:\/\/rootdomain1.com",
      "redirect404": "https:\/\/rootdomain1.com\/404"
      },
      {
      "id": 2,
      "domain": "https:\/\/domain2.com",
      "redirectroot": "https:\/\/rootdomain2.com",
      "redirect404": "https:\/\/rootdomain2.com\/404"
      }
      ]
      },
      "message": "error message (if any)"
      }
     *
     */
    $return = json_decode($response, true);
    return $return;
}

function get_peppylink($apikey, $url, $metatitle, $metadesc = '', $plinkid = '') {
    global $cfgtoken;

    $linkid = intval($plinkid);
    if ($linkid < 1) {
        $posturl = $cfgtoken['shortenby'] . "/url/add";
        $acturl = "POST";
    } else {
        $posturl = $cfgtoken['shortenby'] . "/url/{$linkid}/update";
        $acturl = "PUT";
    }

    $peppyapi = ($apikey == '') ? base64_decode($cfgtoken['peppyapi'] ?? '') : $apikey;
    $peppydomain = ($cfgtoken['peppydomain'] != '') ? base64_decode($cfgtoken['peppydomain'] ?? '') : '';
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $posturl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 2,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CUSTOMREQUEST => $acturl,
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer {$peppyapi}",
            "Content-Type: application/json",
        ),
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_POSTFIELDS => json_encode(array(
            'url' => $url,
            'domain' => $peppydomain,
            'type' => 'direct',
            'metatitle' => $metatitle,
            'metadescription' => $metadesc)),
    ));

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $iferrorstr = curl_error($ch);
    curl_close($ch);
    /* response create:
      {
      "error": [error_code, 0 = no error],
      "id": [short_url_id],
      "shorturl": "https:\/\/peppy.link\/[random_alias]"
      }
     *
     */
    /* response update:
      {
      "error": 0,
      "id": 3,
      "shorturl": "https:\/\/peppy.link\/[random_alias]"
      }
     *
     */

    $return = json_decode($response ?? '', true);
    return $return;
}

function get_peppylink_info($apikey = '', $plinkid = '') {
    global $cfgtoken;

    $linkid = intval($plinkid);
    $peppyapi = ($apikey == '') ? base64_decode($cfgtoken['peppyapi'] ?? '') : $apikey;
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $cfgtoken['shortenby'] . "/url/{$linkid}",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 2,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer {$peppyapi}",
            "Content-Type: application/json",
        ),
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
    ));

    $response = curl_exec($ch);
    curl_close($ch);

    /* response:
      {
      "error": 0,
      "details": {
      "id": 1,
      "shorturl": "https:\/\/peppy.link\/googlecanada",
      "longurl": "https:\/\/google.com",
      "title": "Google",
      "description": "",
      "location": {
      "canada": "https:\/\/google.ca",
      "united states": "https:\/\/google.us"
      },
      "device": {
      "iphone": "https:\/\/google.com",
      "android": "https:\/\/google.com"
      },
      "expiry": null,
      "date": "2022-12-18 18:01:43"
      },
      "data": {
      "clicks": 0,
      "uniqueClicks": 0,
      "topCountries": 0,
      "topReferrers": 0,
      "topBrowsers": 0,
      "topOs": 0,
      "socialCount": {
      "facebook": 0,
      "twitter": 0,
      "google": 0
      }
      }
      }
     *
     */
    $return = json_decode($response, true);
    return $return;
}

function del_peppylink($apikey = '', $plinkid = '') {
    global $cfgtoken;

    $linkid = intval($plinkid);
    $peppyapi = ($apikey == '') ? base64_decode($cfgtoken['peppyapi'] ?? '') : $apikey;
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $cfgtoken['shortenby'] . "/url/{$linkid}/delete",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 2,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CUSTOMREQUEST => "DELETE",
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer {$peppyapi}",
            "Content-Type: application/json",
        ),
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
    ));

    $response = curl_exec($ch);
    curl_close($ch);

    /* response:
      {
      "error": 0,
      "message": "Link has been deleted successfully"
      }     *
     */
    $return = json_decode($response, true);
    return $return;
}

function get_peppyqrcode($apikey, $url, $metatitle, $metadesc = '', $qrid = '') {
    global $cfgtoken;

    $peppyapi = ($apikey == '') ? base64_decode($cfgtoken['peppyapi'] ?? '') : $apikey;
    $urlenc = urlencode($url ?? '');

    $datapost = array(array(
            'type' => 'link',
            'data' => $urlenc,
            'background' => 'rgb(255,255,255)',
            'foreground' => 'rgb(0,0,0)',
            'metatitle' => $metatitle,
            'metadescription' => $metadesc,
    ));
    $peppyqrlogo = base64_decode($cfgtoken['peppyqrlogo'] ?? '');
    if ($peppyqrlogo != '') {
        $datapost['logo'] = $peppyqrlogo;
    }

    if ($qrid < 1) {
        $posturl = $cfgtoken['shortenby'] . "/qr/add";
        $acturl = "POST";
    } else {
        $qrid = intval($qrid);
        $posturl = $cfgtoken['shortenby'] . "/qr/{$qrid}/update";
        $acturl = "PUT";
    }

    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $posturl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 2,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CUSTOMREQUEST => $acturl,
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer {$peppyapi}",
            "Content-Type: application/json",
        ),
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_POSTFIELDS => json_encode($datapost),
    ));

    $response = curl_exec($ch);

    curl_close($ch);

    $return = json_decode($response, true);
    return $return;
}
