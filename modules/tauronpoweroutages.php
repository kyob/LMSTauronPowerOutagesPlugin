<?php

function powerOutages($url)
{
    $commune = explode(",", ConfigHelper::getConfig('tauron.commune'));
    $district = explode(",", ConfigHelper::getConfig('tauron.district'));
    $api_url = ConfigHelper::getConfig('tauron.api_url', 'https://www.tauron-dystrybucja.pl/iapi');
    $result = array();

    $ch = curl_init(); // CURL handle

    if (empty($district)) {
        foreach ($commune as $gaid) {
            curl_setopt($ch, CURLOPT_URL, $api_url . "/outage/GetOutages?gaid=" . $gaid . "&type=commune");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            if (curl_exec($ch) === false) {
                echo 'Curl error: ' . curl_error($ch);
            } else {
                $json = curl_exec($ch);
                $result = array_merge_recursive($result, json_decode($json, true));
            }
        }
    } else {
        foreach ($district as $gaid) {
            curl_setopt($ch, CURLOPT_URL, $api_url . "/outage/GetOutages?gaid=" . $gaid . "&type=district");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            if (curl_exec($ch) === false) {
                echo 'Curl error: ' . curl_error($ch);
            } else {
                $json = curl_exec($ch);
                $result = array_merge_recursive($result, json_decode($json, true));
            }
        }
    }
    curl_close($ch);
    return $result;
}

$SMARTY->assign('power_outages', powerOutages($api_url . "/outage/GetOutages?gaid=" . $gaid . "&type=" . $type));
$SMARTY->display('tauronpoweroutages.html');
