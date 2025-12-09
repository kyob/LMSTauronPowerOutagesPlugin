<?php

// REPLACE THIS WITH PATH TO YOUR CONFIG FILE
$CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';

// PLEASE DO NOT MODIFY ANYTHING BELOW THIS LINE UNLESS YOU KNOW
// *EXACTLY* WHAT ARE YOU DOING!!!
// *******************************************************************

define('CONFIG_FILE', $CONFIG_FILE);

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'lib' : $CONFIG['directories']['lib_dir']);

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);

// Load autoloader
$composer_autoload_path = SYS_DIR . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($composer_autoload_path)) {
    require_once $composer_autoload_path;
} else {
    die("Composer autoload not found. Run 'composer install' command from LMS directory and try again. More informations at https://getcomposer.org/" . PHP_EOL);
}

// Init database
$DB = null;

try {
    $DB = LMSDB::getInstance();
} catch (Exception $ex) {
    trigger_error($ex->getMessage(), E_USER_WARNING);
    // can't working without database
    die("Fatal error: cannot connect to database!" . PHP_EOL);
}

// Include required files (including sequence is important)
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
include_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');

$SYSLOG = SYSLOG::getInstance();

/**
 * Wysyła wiadomość na Telegram przez Bot API
 * 
 * @param string $botToken Token bota Telegram
 * @param string|array $chatIds Chat ID (może być string z przecinkami lub array)
 * @param string $message Treść wiadomości
 * @return bool
 * @throws Exception
 */
function sendTelegram($botToken, $chatIds, $message)
{
    if (empty($botToken)) {
        throw new Exception('Telegram bot token is required');
    }

    if (empty($chatIds)) {
        throw new Exception('Telegram chat ID is required');
    }

    // Obsługa wielu chat ID (przecinki lub array)
    if (is_string($chatIds)) {
        $chatIds = array_map('trim', explode(',', $chatIds));
    }

    $apiUrl = 'https://api.telegram.org/bot' . $botToken . '/sendMessage';
    $success = true;

    foreach ($chatIds as $chatId) {
        if (empty($chatId)) {
            continue;
        }

        $curl = curl_init();
        $postData = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
        ];

        curl_setopt_array($curl, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $result = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($curlError) {
            error_log("Telegram send error (chat_id={$chatId}): " . $curlError);
            $success = false;
            continue;
        }

        if ($httpCode !== 200) {
            error_log("Telegram HTTP error (chat_id={$chatId}): HTTP {$httpCode}, response: " . substr($result, 0, 200));
            $success = false;
            continue;
        }

        $response = json_decode($result, true);
        if (isset($response['ok']) && $response['ok'] === true) {
            // Sukces
        } else {
            $errorDesc = isset($response['description']) ? $response['description'] : 'Unknown error';
            error_log("Telegram API error (chat_id={$chatId}): " . $errorDesc);
            $success = false;
        }
    }

    return $success;
}

// Konfiguracja Telegram
$telegramBotToken = ConfigHelper::getConfig('tauron.telegram_bot_token', '');
$telegramChatIds = ConfigHelper::getConfig('tauron.telegram_chat_ids', '');

if (empty($telegramBotToken) || empty($telegramChatIds)) {
    die("Telegram notifications disabled: missing bot_token or chat_ids in config. Set tauron.telegram_bot_token and tauron.telegram_chat_ids" . PHP_EOL);
}

$argv = $_SERVER['argv'] ?? [];
$isTest = in_array('--test', $argv, true) || in_array('test', $argv, true);
if ($isTest) {
    // optional custom message after flag
    $testIdx = array_search('--test', $argv, true);
    if ($testIdx === false) {
        $testIdx = array_search('test', $argv, true);
    }
    $customMessage = null;
    if ($testIdx !== false && isset($argv[$testIdx + 1])) {
        $customMessage = $argv[$testIdx + 1];
    }

    $message = $customMessage ?: "<b>⚡ Test powiadomień Tauron</b>\n\n"
        . "<b>Od:</b> " . date('Y-m-d H:i') . "\n"
        . "<b>Do:</b> " . date('Y-m-d H:i', strtotime('+1 hour')) . "\n"
        . "<b>Obszar:</b> Testowa wiadomość";

    $ok = sendTelegram($telegramBotToken, $telegramChatIds, $message);
    echo $ok ? "Test Telegram: OK\n" : "Test Telegram: FAILED\n";
    $DB->Destroy();
    exit($ok ? 0 : 1);
}

// Pobierz awarie do wysłania (tylko dzisiejsze, jeszcze nie wysłane)
$current_date = date('Y-m-d');
$current_outages_to_send = $DB->GetAll(
    "SELECT id, start_date, end_date, message FROM alfa_plugin_tauron WHERE telegram_notify=false AND DATE(start_date) = ? ORDER BY id ASC",
    [$current_date]
);

if (empty($current_outages_to_send)) {
    echo "No new outages to notify via Telegram." . PHP_EOL;
    $DB->Destroy();
    exit(0);
}

$sentCount = 0;
$errorCount = 0;

foreach ($current_outages_to_send as $item) {
    $start_date = date('Y-m-d H:i', strtotime($item['start_date']));
    $end_date = date('Y-m-d H:i', strtotime($item['end_date']));

    // Formatuj wiadomość HTML dla Telegram
    $message = "<b>⚡ Tauron - Wyłączenie prądu</b>\n\n";
    $message .= "<b>Od:</b> " . htmlspecialchars($start_date) . "\n";
    $message .= "<b>Do:</b> " . htmlspecialchars($end_date) . "\n";
    $message .= "<b>Obszar:</b> " . htmlspecialchars($item['message']) . "\n";

    try {
        $success = sendTelegram($telegramBotToken, $telegramChatIds, $message);
        
        if ($success) {
            $DB->Execute("UPDATE alfa_plugin_tauron SET telegram_notify = true WHERE id = ?", [$item['id']]);
            $sentCount++;
        } else {
            $errorCount++;
        }
    } catch (Exception $e) {
        error_log('Telegram notification failed for outage ID ' . $item['id'] . ': ' . $e->getMessage());
        $errorCount++;
    }
}

echo "Telegram notifications sent: {$sentCount}, errors: {$errorCount}" . PHP_EOL;

$DB->Destroy();


