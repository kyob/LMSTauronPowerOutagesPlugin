<?php

// REPLACE THIS WITH PATH TO YOUR CONFIG FILE
$CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';

define('CONFIG_FILE', $CONFIG_FILE);

// Ścieżki pluginu/plug-ins (nie polegaj wyłącznie na PLUGINS_DIR z LMS)
$PLUGIN_DIR = realpath(dirname(__DIR__));
$PLUGINS_BASE = dirname($PLUGIN_DIR);

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'lib' : $CONFIG['directories']['lib_dir']);

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);

$composer_autoload_path = SYS_DIR . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($composer_autoload_path)) {
    require_once $composer_autoload_path;
} else {
    die("Composer autoload not found. Run 'composer install' command from LMS directory and try again." . PHP_EOL);
}

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');

// Jeśli LMS nie zainicjalizował PLUGINS_DIR, ustaw go na bazę pluginów.
if (!defined('PLUGINS_DIR')) {
    define('PLUGINS_DIR', $PLUGINS_BASE);
}

// Init database (musi być przed language/ConfigHelper).
$DB = null;
try {
    $DB = LMSDB::getInstance();
} catch (Exception $ex) {
    trigger_error($ex->getMessage(), E_USER_WARNING);
    die("Fatal error: cannot connect to database!" . PHP_EOL);
}

include_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');

// Wczytaj klasę pluginu (stałe/konstanty).
require_once $PLUGIN_DIR . DIRECTORY_SEPARATOR . 'LMSTauronPowerOutagesPlugin.php';

// Load plugin classes
require_once $PLUGIN_DIR . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'TauronPowerOutagesFetcher.php';

$filename = ConfigHelper::getConfig('tauron.filename', 'tauron.json');
$cachePath = TauronPowerOutagesFetcher::resolveCachePath($filename);

$argv = $_SERVER['argv'] ?? [];
$shouldClear = in_array('--clear-cache', $argv, true) || in_array('--clear', $argv, true) || in_array('clear', $argv, true);

if ($shouldClear) {
    if (file_exists($cachePath)) {
        if (@unlink($cachePath)) {
            echo "Cache file removed: {$cachePath}" . PHP_EOL;
            $DB->Destroy();
            exit(0);
        }
        echo "Failed to remove cache file: {$cachePath}" . PHP_EOL;
        $DB->Destroy();
        exit(1);
    }
    echo "No cache file to remove: {$cachePath}" . PHP_EOL;
    $DB->Destroy();
    exit(0);
}

$options = [
    'province' => explode(",", ConfigHelper::getConfig('tauron.province', '24')),
    'district' => explode(",", ConfigHelper::getConfig('tauron.district', '6')),
    'commune' => explode(",", ConfigHelper::getConfig('tauron.commune', '')),
    'forward_days' => intval(ConfigHelper::getConfig('tauron.forward_days', 7)),
    'api_url' => ConfigHelper::getConfig('tauron.api_url', 'https://www.tauron-dystrybucja.pl/waapi'),
    'timeout' => intval(ConfigHelper::getConfig('tauron.timeout', 10)),
    'user_agent' => ConfigHelper::getConfig('tauron.user_agent', 'LMS-Tauron-Power-Outages'),
    'cache_path' => $cachePath,
];

$result = TauronPowerOutagesFetcher::fetchAndCache($options);

$count = isset($result['items']) && is_array($result['items']) ? count($result['items']) : 0;
$updated = isset($result['last_updated']) && $result['last_updated'] ? date('Y-m-d H:i:s', $result['last_updated']) : 'n/d';

echo "Tauron cache refreshed. Items: {$count}, cache: {$cachePath}, last update: {$updated}" . PHP_EOL;

