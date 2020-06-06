<?php

function powerOutages($url)
{
    $commune = ConfigHelper::getConfig('tauron.commune');
    $commune = explode(",", (int) $commune);

    $district = ConfigHelper::getConfig('tauron.district');
    $district = explode(",", (int) $district);

    $api_url = ConfigHelper::getConfig('tauron.api_url', 'https://www.tauron-dystrybucja.pl/iapi');
    $array = array();

    $CURLConnection = curl_init();

    foreach ($commune as $gaid) {
        curl_setopt($CURLConnection, CURLOPT_URL, $api_url . "/outage/GetOutages?gaid=" . $gaid . "&type=commune");
        curl_setopt($CURLConnection, CURLOPT_RETURNTRANSFER, true);
        $json = curl_exec($CURLConnection);
        $array = array_merge_recursive($array, json_decode($json, true));
    }

    foreach ($district as $gaid) {
        curl_setopt($CURLConnection, CURLOPT_URL, $api_url . "/outage/GetOutages?gaid=" . $gaid . "&type=district");
        curl_setopt($CURLConnection, CURLOPT_RETURNTRANSFER, true);
        $json = curl_exec($CURLConnection);
        $json = curl_exec($CURLConnection);
        $array = array_merge_recursive($array, json_decode($json, true));
    }

    curl_close($CURLConnection);
    return $array;
}

$SMARTY->assign('power_outages', powerOutages($api_url . "/outage/GetOutages?gaid=" . $gaid . "&type=" . $type));
$SMARTY->display('tauronpoweroutages.html');
