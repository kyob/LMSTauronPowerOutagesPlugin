<?php

function powerOutages($url)
{
    $commune = explode(",", ConfigHelper::getConfig('tauron.commune'));
    $district = explode(",", ConfigHelper::getConfig('tauron.district'));
    $api_url = ConfigHelper::getConfig('tauron.api_url', 'https://www.tauron-dystrybucja.pl/iapi');
    $array = array();

    $CURLConnection = curl_init();

    if (empty($district)) {
        foreach ($commune as $gaid) {
            curl_setopt($CURLConnection, CURLOPT_URL, $api_url . "/outage/GetOutages?gaid=" . $gaid . "&type=commune");
            curl_setopt($CURLConnection, CURLOPT_RETURNTRANSFER, true);
            $json = curl_exec($CURLConnection);
            $array = array_merge_recursive($array, json_decode($json, true));
        }
    } else {
        foreach ($district as $gaid) {
            curl_setopt($CURLConnection, CURLOPT_URL, $api_url . "/outage/GetOutages?gaid=" . $gaid . "&type=district");
            curl_setopt($CURLConnection, CURLOPT_RETURNTRANSFER, true);
            $json = curl_exec($CURLConnection);
            $array = array_merge_recursive($array, json_decode($json, true));
        }
    }
    curl_close($CURLConnection);
    return $array;
}

$SMARTY->assign('power_outages', powerOutages($api_url . "/outage/GetOutages?gaid=" . $gaid . "&type=" . $type));
$SMARTY->display('tauronpoweroutages.html');
