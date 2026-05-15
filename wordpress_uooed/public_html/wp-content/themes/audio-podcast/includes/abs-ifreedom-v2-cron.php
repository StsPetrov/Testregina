<?php
/**
 * Системный Cron для парсера ifreedom v2
 * Запуск: /usr/bin/php abs-ifreedom-v2-cron.php
 */

// Только CLI
if (php_sapi_name() !== 'cli') die('CLI only');

// Подключаем WordPress
$wp_load = dirname(__FILE__) . '/../../../../../wp-load.php';
if (!file_exists($wp_load)) die('wp-load.php not found');
require_once $wp_load;

// Подключаем ядро парсера
require_once dirname(__FILE__) . '/abs-ifreedom-v2.php';

// Защита от повторного запуска
$lock_file = dirname(__FILE__) . '/parser-v2.lock';
if (file_exists($lock_file)) {
    $lock_time = filemtime($lock_file);
    if (time() - $lock_time < 300) die('Already running'); // 5 минут
    unlink($lock_file);
}
file_put_contents($lock_file, time());

// Логирование в файл
function v2_cron_log($msg) {
    $log_file = dirname(__FILE__) . '/parser-v2.log';
    $line = date('Y-m-d H:i:s') . ' ' . $msg;
    file_put_contents($log_file, $line . "\n", FILE_APPEND);
    echo $line . "\n";
}

global $wpdb;
$table = $wpdb->prefix . 'abs_ifreedom_v2_queue';

// Берём следующую книгу
$book = $wpdb->get_row("SELECT * FROM $table WHERE status IN('new','error') ORDER BY id ASC LIMIT 1");

if (!$book) {
    v2_cron_log('No books to process');
    unlink($lock_file);
    exit;
}

// Старт
v2_cron_log("Start: {$book->title}");
abs_telegram_log("🔄 Крон V2: {$book->title}");

// Загружаем
$result = abs_ifreedom_v2_process_book($book->slug);

// Финал
if ($result['status'] === 'ok') {
    v2_cron_log("Done: {$book->title} — {$result['loaded']}/{$result['total']}");
    abs_telegram_log("✅ Крон V2: {$book->title} — {$result['loaded']}/{$result['total']} глав");
} else {
    v2_cron_log("Error: {$book->title} — {$result['message']}");
    abs_telegram_log("❌ Крон V2: {$book->title} — {$result['message']}");
}

// Снимаем лок
unlink($lock_file);