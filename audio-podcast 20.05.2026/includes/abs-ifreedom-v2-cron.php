<?php
/**
 * Системный Cron для парсера ifreedom v2
 * Запуск: curl -s "https://1001ranobe.ru/wp-content/themes/audio-podcast/includes/abs-ifreedom-v2-cron.php"
 */

// CLI or web

// Подключаем WordPress
$wp_load = '/home/m/magsport/wordpress_uooed/public_html/wp-load.php';
if (!file_exists($wp_load)) die('wp-load.php not found');
require_once $wp_load;

// Подключаем ядро парсера
require_once dirname(__FILE__) . '/abs-ifreedom-v2.php';

// Защита от повторного запуска
$lock_file = dirname(__FILE__) . '/parser-v2.lock';
if (file_exists($lock_file)) {
    $lock_time = filemtime($lock_file);
    if (time() - $lock_time < 300) die('Already running');
    unlink($lock_file);
}
file_put_contents($lock_file, time());

// Логирование в файл
function v2_cron_log($msg) {
    $log_file = dirname(__FILE__) . '/cron-v2.log';
    $line = date('Y-m-d H:i:s') . ' ' . $msg;
    file_put_contents($log_file, $line . "\n", FILE_APPEND);
    echo $line . "\n";
}

global $wpdb;
$table = $wpdb->prefix . 'abs_ifreedom_v2_queue';

// Обрабатываем до 5 книг за запуск
$max_books = 5;
$processed = 0;

while ($processed < $max_books) {
    $book = $wpdb->get_row("SELECT * FROM $table WHERE status IN('new','error') ORDER BY id ASC LIMIT 1");
    if (!$book) break;

    // Защита от зацикливания
    $attempt_key = 'cron_attempt_' . $book->slug;
    $attempts = get_transient($attempt_key) ?: 0;
    if ($attempts >= 3) {
        v2_cron_log("Skip (stuck): {$book->title}");
        $wpdb->update($table, ['status' => 'error', 'error_msg' => 'Зацикливание: 3 попытки'], ['slug' => $book->slug]);
        if (function_exists('abs_telegram_log')) abs_telegram_log("⚠️ Крон V2: {$book->title} — пропущена (зацикливание)");
        continue;
    }
    set_transient($attempt_key, $attempts + 1, 600); // 10 минут

    // Старт
    v2_cron_log("Start: {$book->title}");
    if (function_exists('abs_telegram_log')) abs_telegram_log("🔄 Крон V2: {$book->title}");

    // Загружаем
    $result = abs_ifreedom_v2_process_book($book->slug);

    // Финал
    if ($result['status'] === 'ok') {
        delete_transient($attempt_key);
        v2_cron_log("Done: {$book->title} — {$result['loaded']}/{$result['total']}");
        if (function_exists('abs_telegram_log')) abs_telegram_log("✅ Крон V2: {$book->title} — {$result['loaded']}/{$result['total']} глав");
    } else {
        v2_cron_log("Error: {$book->title} — {$result['message']}");
        if (function_exists('abs_telegram_log')) abs_telegram_log("❌ Крон V2: {$book->title} — {$result['message']}");
    }

    $processed++;
}

v2_cron_log("Processed $processed books this run");

// Снимаем лок
unlink($lock_file);