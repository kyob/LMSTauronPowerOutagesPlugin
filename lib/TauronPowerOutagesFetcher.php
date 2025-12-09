<?php

class TauronPowerOutagesFetcher
{
    public static function resolveCachePath(string $configuredFilename): string
    {
        if (strpos($configuredFilename, DIRECTORY_SEPARATOR) === 0) {
            return $configuredFilename;
        }

        $pluginBase = PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSTauronPowerOutagesPlugin::PLUGIN_DIRECTORY_NAME;
        $cacheDir = $pluginBase . DIRECTORY_SEPARATOR . 'var';

        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0770, true);
        }

        return $cacheDir . DIRECTORY_SEPARATOR . $configuredFilename;
    }

    public static function loadCache(string $path): array
    {
        if (!file_exists($path) || !is_readable($path)) {
            return ['items' => [], 'raw' => []];
        }

        $decoded = json_decode(file_get_contents($path), true);
        if (!is_array($decoded)) {
            return ['items' => [], 'raw' => []];
        }

        $items = $decoded['OutageItems'] ?? [];
        return [
            'items' => is_array($items) ? $items : [],
            'raw' => $decoded,
        ];
    }

    public static function fetchAndCache(array $options): array
    {
        $province = self::sanitizeIds($options['province'] ?? []);
        $district = self::sanitizeIds($options['district'] ?? []);
        $commune = self::sanitizeIds($options['commune'] ?? []);

        $forwardDays = (int) ($options['forward_days'] ?? 7);
        $forwardSeconds = $forwardDays * 24 * 60 * 60;

        $apiUrl = rtrim($options['api_url'] ?? '', '/');
        $timeout = (int) ($options['timeout'] ?? 5);
        $userAgent = $options['user_agent'] ?? 'LMS-Tauron-Power-Outages/1.0';

        $queryDateTimeStart = date("Y-m-d") . "T" . date("H:i:s") . ".000Z";
        $queryDateTimeStop = date("Y-m-d", time() + $forwardSeconds) . "T" . date("H:i:s") . ".000Z";

        $outageItems = [];

        $curl = curl_init();
        foreach ($province as $provinceGAID) {
            foreach ($district as $districtGAID) {
                foreach ($commune as $communeGAID) {
                    $url = $apiUrl . "/outages/area?provinceGAID=" . $provinceGAID . "&districtGAID=" . $districtGAID . "&fromDate=" . $queryDateTimeStart . "&toDate=" . $queryDateTimeStop;
                    if ($communeGAID !== null) {
                        $url .= "&communeGAID=" . $communeGAID;
                    }

                    curl_setopt_array($curl, [
                        CURLOPT_URL => $url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_TIMEOUT => $timeout,
                        CURLOPT_CONNECTTIMEOUT => $timeout,
                        CURLOPT_USERAGENT => $userAgent,
                    ]);

                    $json = curl_exec($curl);
                    if ($json === false) {
                        continue;
                    }

                    $httpCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
                    if ($httpCode !== 200) {
                        continue;
                    }

                    $decoded = json_decode($json, true);
                    if (isset($decoded['OutageItems']) && is_array($decoded['OutageItems'])) {
                        foreach ($decoded['OutageItems'] as $item) {
                            $id = $item['OutageId'] ?? null;
                            if ($id === null) {
                                $outageItems[] = $item;
                                continue;
                            }
                            $outageItems[$id] = $item;
                        }
                    }
                }
            }
        }
        curl_close($curl);

        // Normalize indexed array and sort by start date (ascending)
        $outageItems = array_values($outageItems);
        usort($outageItems, function ($a, $b) {
            $aStart = isset($a['StartDate']) ? strtotime($a['StartDate']) : 0;
            $bStart = isset($b['StartDate']) ? strtotime($b['StartDate']) : 0;
            return $aStart <=> $bStart;
        });

        $payload = ['OutageItems' => $outageItems];
        $cachePath = $options['cache_path'];

        if ((!empty($outageItems)) || !file_exists($cachePath)) {
            file_put_contents($cachePath, json_encode($payload), LOCK_EX);
        }

        return [
            'items' => $outageItems,
            'raw' => $payload,
            'cache_path' => $cachePath,
            'last_updated' => file_exists($cachePath) ? filemtime($cachePath) : null,
        ];
    }

    private static function sanitizeIds(array $ids): array
    {
        $filtered = array_filter(array_map('trim', $ids), function ($value) {
            return $value !== '' && $value !== null;
        });
        return array_values($filtered);
    }
}

