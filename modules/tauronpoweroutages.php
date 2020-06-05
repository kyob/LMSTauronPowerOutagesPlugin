<?php
$gaid = ConfigHelper::getConfig('tauron.gaid', 502);
$type = ConfigHelper::getConfig('tauron.type', 'commune');
$api_url = ConfigHelper::getConfig('tauron.api_url', 'https://www.tauron-dystrybucja.pl/iapi');

function powerOutages($url)
{
    $CURLConnection = curl_init();
    curl_setopt($CURLConnection, CURLOPT_URL, $url);
    curl_setopt($CURLConnection, CURLOPT_RETURNTRANSFER, true);
    $json = curl_exec($CURLConnection);
    curl_close($CURLConnection);
    return json_decode($json, true);
}

$SMARTY->assign('power_outages', powerOutages($api_url . "/outage/GetOutages?gaid=" . $gaid . "&type=" . $type));
$SMARTY->display('tauronpoweroutages.html');
