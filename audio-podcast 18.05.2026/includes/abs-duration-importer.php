<?php
/**
 * abs-duration-importer.php - Импорт длительностей треков
 */

if (!defined('ABSPATH')) {
    exit;
}

// Добавляем отдельный пункт меню
add_action('admin_menu', 'abs_duration_importer_menu');

function abs_duration_importer_menu() {
    add_menu_page(
        'Импорт длительностей',
        'Импорт длит',
        'manage_options',
        'abs-duration-importer',
        'abs_duration_importer_page',
        'dashicons-clock',
        31
    );
}

// Страница импорта
function abs_duration_importer_page() {
    ?>
    <div class="wrap">
        <h1>Импорт длительностей треков</h1>
        
        <?php
        $api_key = defined('ABS_API_KEY') ? ABS_API_KEY : '';
        if (empty($api_key)) {
            echo '<div class="notice notice-error"><p>❌ API ключ не найден!</p></div>';
            return;
        }
        
        // Обработка импорта
        if (isset($_POST['abs_duration_import_start']) && check_admin_referer('abs_duration_import_action')) {
            abs_run_duration_import();
        }
        
        // Обработка очистки
        if (isset($_POST['abs_duration_clear']) && check_admin_referer('abs_duration_import_action')) {
            global $wpdb;
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}abs_track_durations");
            echo '<div class="notice notice-success"><p>🗑️ Все длительности удалены! Запустите импорт заново.</p></div>';
        }
        
        global $wpdb;
        $cache_table = $wpdb->prefix . 'abs_book_cache';
        $durations_table = $wpdb->prefix . 'abs_track_durations';
        
        $total_books = $wpdb->get_var("SELECT COUNT(*) FROM $cache_table");
        $books_with_durations = $wpdb->get_var("SELECT COUNT(DISTINCT book_id) FROM $durations_table");
        $books_without = $total_books - $books_with_durations;
        $total_durations = $wpdb->get_var("SELECT COUNT(*) FROM $durations_table");
        ?>
        
        <div class="card">
            <h2>📊 Статистика</h2>
            <p>Всего книг в кэше: <strong><?php echo $total_books; ?></strong></p>
            <p>Книг с длительностями: <strong><?php echo $books_with_durations; ?></strong></p>
            <p>Книг без длительностей: <strong><?php echo $books_without; ?></strong></p>
            <p>Всего треков с длительностями: <strong><?php echo $total_durations; ?></strong></p>
        </div>
        
        <div class="card">
            <h2>🎵 Импорт длительностей</h2>
            <p>Загружает длительности треков из ABS и сохраняет в БД.</p>
            <form method="post">
                <?php wp_nonce_field('abs_duration_import_action'); ?>
                <input type="hidden" name="abs_duration_import_start" value="1">
                <?php submit_button('Запустить импорт', 'primary', 'submit'); ?>
            </form>
        </div>
        
        <div class="card">
            <h2>🗑️ Очистка</h2>
            <p>Удаляет ВСЕ сохранённые длительности. После этого нужно будет запустить импорт заново.</p>
            <form method="post" onsubmit="return confirm('Вы уверены? Все длительности будут удалены!');">
                <?php wp_nonce_field('abs_duration_import_action'); ?>
                <input type="hidden" name="abs_duration_clear" value="1">
                <?php submit_button('Очистить все длительности', 'delete', 'submit'); ?>
            </form>
        </div>
    </div>
    
    <style>
        .card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .notice {
            margin: 15px 0;
        }
    </style>
    <?php
}

// Функция импорта длительностей
function abs_run_duration_import() {
    echo '<div class="notice notice-info"><p>🔄 Начинаем импорт длительностей...</p></div>';
    echo '<div class="log-container" style="background:#f1f1f1;padding:10px;max-height:400px;overflow:auto;font-family:monospace;margin-top:20px;">';
    
    set_time_limit(0);
    
    global $wpdb;
    $cache_table = $wpdb->prefix . 'abs_book_cache';
    $durations_table = $wpdb->prefix . 'abs_track_durations';
    
    // Находим книги без длительностей
    $books = $wpdb->get_results("
        SELECT c.book_id, c.tracks_data
        FROM $cache_table c
        WHERE c.book_id NOT IN (SELECT DISTINCT book_id FROM $durations_table)
        LIMIT 50
    ");
    
    if (empty($books)) {
        echo '<div class="log-entry log-success">✅ Все книги уже имеют длительности!</div>';
        echo '</div>';
        return;
    }
    
    $api_key = defined('ABS_API_KEY') ? ABS_API_KEY : '';
    $server_url = 'https://94.41.21.24';
    $processed = 0;
    $total_durations = 0;
    
    foreach ($books as $book) {
        $book_id = $book->book_id;
        $tracks_data = json_decode($book->tracks_data, true);
        
        if (empty($tracks_data)) {
            echo '<div class="log-entry log-warning">⚠️ Нет треков для книги: ' . esc_html($book_id) . '</div>';
            continue;
        }
        
        echo '<div class="log-entry log-info">📚 Обработка книги: ' . esc_html($book_id) . ' (' . count($tracks_data) . ' треков)</div>';
        
        $total_seconds = 0;
        $track_index = 0;
        $book_durations = 0;
        
        foreach ($tracks_data as $track) {
            $file_id = $track['file_id'];
            $url = $server_url . '/api/items/' . $book_id . '/file/' . $file_id;
            
            $duration = abs_get_track_duration($url, $api_key);
            
            if ($duration > 0) {
                $mins = floor($duration / 60);
                $secs = $duration % 60;
                $formatted = $mins . ':' . ($secs < 10 ? '0' . $secs : $secs);
                $total_seconds += $duration;
                
                $wpdb->replace($durations_table, [
                    'book_id' => $book_id,
                    'track_index' => $track_index,
                    'track_name' => $track['name'],
                    'duration' => $duration,
                    'duration_formatted' => $formatted,
                ]);
                $book_durations++;
                $total_durations++;
                echo '<div class="log-entry log-info">   🎵 Трек ' . ($track_index + 1) . ': ' . $formatted . '</div>';
            } else {
                echo '<div class="log-entry log-warning">   ⚠️ Трек ' . ($track_index + 1) . ': не удалось получить длительность</div>';
            }
            $track_index++;
            
            usleep(50000);
        }
        
        // Обновляем общую длительность в кэше
        $hours = floor($total_seconds / 3600);
        $minutes = floor(($total_seconds % 3600) / 60);
        $total_formatted = $hours > 0 ? "{$hours} ч {$minutes} мин" : "{$minutes} мин";
        
        $wpdb->update(
            $cache_table,
            [
                'total_duration' => $total_seconds,
                'total_duration_formatted' => $total_formatted
            ],
            ['book_id' => $book_id]
        );
        
        echo '<div class="log-entry log-success">   ✅ Итого: ' . $book_durations . ' треков, общая длительность: ' . $total_formatted . '</div>';
        $processed++;
        
        sleep(1);
    }
    
    echo '<div class="log-entry log-info">━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</div>';
    echo '<div class="log-entry log-success">✅ Импорт завершён!</div>';
    echo '<div class="log-entry log-info">📊 Обработано книг: ' . $processed . '</div>';
    echo '<div class="log-entry log-success">🎵 Загружено длительностей: ' . $total_durations . '</div>';
    
    if ($processed == 50) {
        echo '<div class="log-entry log-warning">⚠️ Обработано 50 книг. Запустите импорт ещё раз.</div>';
    }
    
    echo '</div>';
}

// Функция получения длительности (исправлена для HTTPS)
function abs_get_track_duration($url, $api_key) {
    $args = [
        'headers' => ['Authorization' => 'Bearer ' . $api_key],
        'timeout' => 30,
        'sslverify' => false,
        'httpversion' => '1.0' // отключает chunked encoding
    ];
    
    // Пробуем HEAD
    $response = wp_remote_head($url, $args);
    
    if (is_wp_error($response)) {
        return 0;
    }
    
    // Пробуем получить через Range (1 байт)
    $range_args = $args;
    $range_args['headers']['Range'] = 'bytes=0-0';
    
    $range_response = wp_remote_get($url, $range_args);
    
    if (!is_wp_error($range_response)) {
        $content_range = wp_remote_retrieve_header($range_response, 'content-range');
        if ($content_range && preg_match('/(\d+)$/', $content_range, $matches)) {
            $duration = intval($matches[1]) + 1;
            if ($duration > 0 && $duration < 7200) {
                return $duration;
            }
        }
    }
    
    // Через content-length и битрейт 64 kbps
    $content_length = wp_remote_retrieve_header($response, 'content-length');
    if ($content_length) {
        $duration = round($content_length / 8192);
        if ($duration > 0 && $duration < 7200) {
            return $duration;
        }
    }
    
    return 0;
}
?>