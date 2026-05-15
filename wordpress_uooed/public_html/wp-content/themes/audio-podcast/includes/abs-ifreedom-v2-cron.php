<?php
// Запускается только через CLI (системный cron)
if (php_sapi_name() !== 'cli') die('CLI only');

require_once dirname(__FILE__) . '/../../../../../wp-load.php';
require_once dirname(__FILE__) . '/abs-ifreedom-v2.php';

// Защита от повторного запуска
$lock_file = dirname(__FILE__) . '/cron-v2.lock';
if (file_exists($lock_file)) {
    $lock_time = filemtime($lock_file);
    if (time() - $lock_time < 300) die('Already running'); // 5 минут
    unlink($lock_file); // Старый лок, удаляем
}
file_put_contents($lock_file, time());

// Логирование
function cron_log($msg) {
    $log_file = dirname(__FILE__) . '/parser-v2.log';
    file_put_contents($log_file, date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);
    echo $msg . "\n";
}

global $wpdb;
$table = $wpdb->prefix . 'abs_ifreedom_v2_queue';

// Берём следующую книгу
$book = $wpdb->get_row("SELECT * FROM $table WHERE status IN('new','error') ORDER BY id ASC LIMIT 1");

if (!$book) {
    cron_log('No books to process. Exiting.');
    unlink($lock_file);
    exit;
}

cron_log("Processing: {$book->title} ({$book->slug})");

// Отправка в Telegram
abs_telegram_log("🔄 Крон: {$book->title}");

$result = abs_ifreedom_v2_process_book($book->slug);

if ($result['status'] === 'ok') {
    cron_log("Done: {$book->title} — {$result['loaded']}/{$result['total']} chapters");
    abs_telegram_log("✅ Крон: {$book->title} — {$result['loaded']}/{$result['total']} глав");
} else {
    cron_log("Error: {$book->title} — {$result['message']}");
    abs_telegram_log("❌ Крон: {$book->title} — {$result['message']}");
}

unlink($lock_file);