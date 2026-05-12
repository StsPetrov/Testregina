<?php
/**
 * abs-importer.php - Импорт аудиокниг из Audiobookshelf
 */

if (!defined('ABSPATH')) {
    exit;
}

// Добавляем отдельный пункт меню
add_action('admin_menu', 'abs_importer_admin_menu');

function abs_importer_admin_menu() {
    add_menu_page(
        'Импорт аудиокниг',
        'Импорт книг',
        'manage_options',
        'abs-importer',
        'abs_importer_admin_page',
        'dashicons-upload',
        30
    );
}

// Страница импорта
function abs_importer_admin_page() {
    ?>
    <div class="wrap">
        <h1>Импорт аудиокниг из Audiobookshelf</h1>
        
        <?php
        $api_key = defined('ABS_API_KEY') ? ABS_API_KEY : '';
        if (empty($api_key)) {
            echo '<div class="notice notice-error"><p>❌ API ключ не найден! Добавьте константу ABS_API_KEY</p></div>';
            return;
        }
        
        $server_url = 'https://94.41.21.24';
        
        if (isset($_POST['abs_import_start'])) {
            check_admin_referer('abs_import_action');
            abs_run_import($server_url, $api_key);
        }
        
        global $wpdb;
        $cache_table = $wpdb->prefix . 'abs_book_cache';
        $total_books = $wpdb->get_var("SELECT COUNT(*) FROM $cache_table");
        
        $existing_posts = get_posts(array(
            'post_type' => 'post',
            'meta_key' => 'abs_book_id',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        ?>
        
        <div class="card">
            <h2>📊 Статистика</h2>
            <p>Книг в кэше БД: <strong><?php echo $total_books; ?></strong></p>
            <p>Импортировано постов: <strong><?php echo count($existing_posts); ?></strong></p>
        </div>
        
        <div class="card">
    <h2>🚀 Запуск импорта</h2>
    <p>Импортирует книги из ABS в WordPress (посты, обложки, жанры, список треков).</p>
    <form method="post">
        <?php wp_nonce_field('abs_import_action'); ?>
        <input type="hidden" name="abs_import_start" value="1">
        <?php submit_button('Запустить импорт', 'primary', 'submit'); ?>
    </form>
</div>

<div class="card">
    <h2>📋 Заполнить метаданные</h2>
    <p>Для всех существующих аудиокниг найти и сохранить авторов, жанры, обложки из текстовых книг.</p>
    <form method="post">
        <?php wp_nonce_field('abs_meta_action'); ?>
        <input type="hidden" name="abs_meta_sync" value="1">
        <?php submit_button('Заполнить метаданные', 'secondary', 'submit'); ?>
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
    </style>
    <?php
}

// Функция импорта
function abs_run_import($server_url, $api_key) {
    echo '<div class="notice notice-info"><p>🔄 Начинаем импорт аудиокниг...</p></div>';
    echo '<div class="log-container" style="background:#f1f1f1;padding:10px;max-height:400px;overflow:auto;font-family:monospace;">';
    
    set_time_limit(0);
    ini_set('memory_limit', '512M');
    
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    
    $response = wp_remote_get("{$server_url}/api/libraries", array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ),
        'timeout' => 30,
        'sslverify' => false
    ));

        $code = wp_remote_retrieve_response_code($response);
    echo '<div class="log-entry log-info">DEBUG: HTTP код ответа: ' . $code . '</div>';
    $body = wp_remote_retrieve_body($response);
    echo '<div class="log-entry log-info">DEBUG: Ответ: ' . substr($body, 0, 500) . '</div>';
    
    if (is_wp_error($response)) {
        echo '<div class="log-entry log-error">❌ Ошибка подключения к ABS: ' . $response->get_error_message() . '</div>';
        echo '</div>';
        return;
    }
    
    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) {
        echo '<div class="log-entry log-error">❌ ABS вернул код ' . $code . '</div>';
        echo '</div>';
        return;
    }
    
    $data = json_decode(wp_remote_retrieve_body($response), true);
    $libraries = isset($data['libraries']) ? $data['libraries'] : array();
    
    echo '<div class="log-entry log-info">📁 Найдено библиотек: ' . count($libraries) . '</div>';
    
    $total = 0;
    $created = 0;
    $updated = 0;
    
    foreach ($libraries as $library) {
        $library_id = $library['id'];
        $library_name = $library['name'];
        
        echo '<div class="log-entry log-info">📚 Библиотека: ' . esc_html($library_name) . '</div>';
        
        $items_response = wp_remote_get("{$server_url}/api/libraries/{$library_id}/items?limit=500", array(
            'headers' => array('Authorization' => 'Bearer ' . $api_key),
            'timeout' => 60,
            'sslverify' => false
        ));
        
        if (is_wp_error($items_response)) {
            echo '<div class="log-entry log-error">❌ Ошибка получения книг</div>';
            continue;
        }
        
        $items_data = json_decode(wp_remote_retrieve_body($items_response), true);
        $items = isset($items_data['results']) ? $items_data['results'] : array();
        
        echo '<div class="log-entry log-info">📚 Найдено элементов: ' . count($items) . '</div>';
        
        foreach ($items as $item) {
            if ($item['mediaType'] !== 'book') continue;
            
            $total++;
            $metadata = $item['media']['metadata'];
            $title = $metadata['title'];
            $book_id = $item['id'];
            $description = isset($metadata['description']) ? $metadata['description'] : '';
            $genres = isset($metadata['genres']) ? $metadata['genres'] : array();
            
            $author = '';
            if (isset($metadata['authorName'])) {
                $author = $metadata['authorName'];
            } elseif (isset($metadata['authors']) && is_array($metadata['authors'])) {
                $author = is_array($metadata['authors'][0]) ? $metadata['authors'][0]['name'] : $metadata['authors'][0];
            }
            
            // ========== ПОЛУЧАЕМ ДЕТАЛЬНУЮ ИНФОРМАЦИЮ О КНИГЕ (включая libraryFiles) ==========
            $item_detail_url = "{$server_url}/api/items/{$book_id}?expanded=1";
            $detail_response = wp_remote_get($item_detail_url, array(
                'headers' => array('Authorization' => 'Bearer ' . $api_key),
                'timeout' => 30,
                'sslverify' => false
            ));
            
            $library_files = array();
            if (!is_wp_error($detail_response)) {
                $detail_data = json_decode(wp_remote_retrieve_body($detail_response), true);
                $library_files = isset($detail_data['libraryFiles']) ? $detail_data['libraryFiles'] : array();
                echo '<div class="log-entry log-info">📖 ' . esc_html($title) . ' - файлов в детальном запросе: ' . count($library_files) . '</div>';
            } else {
                $library_files = $item['libraryFiles'] ?? array();
                echo '<div class="log-entry log-warning">⚠️ ' . esc_html($title) . ' - нет детальной информации, файлов в основном: ' . count($library_files) . '</div>';
            }
            
            // Формируем список треков
            $audio_extensions = ['.mp3', '.m4a', '.flac', '.ogg', '.opus', '.m4b'];
            $tracks = array();
            
            foreach ($library_files as $file) {
                $filename = $file['metadata']['filename'] ?? '';
                $ext = strtolower(substr($filename, strrpos($filename, '.')));
                if (in_array($ext, $audio_extensions)) {
                    $tracks[] = array(
                        'index' => count($tracks),
                        'name' => $filename,
                        'file_id' => $file['ino'],
                    );
                }
            }
            
            // Сортируем треки по имени
            usort($tracks, function($a, $b) {
                return strnatcmp($a['name'], $b['name']);
            });
            
            // Перенумеровываем индексы после сортировки
            foreach ($tracks as $i => $track) {
                $tracks[$i]['index'] = $i;
            }
            
            echo '<div class="log-entry log-info">   🎵 Аудио треков: ' . count($tracks) . '</div>';
            
            // Сохраняем в кэше БД
            global $wpdb;
            $cache_table = $wpdb->prefix . 'abs_book_cache';
            
            // Сохраняем существующую total_duration, если она уже была посчитана
$existing = $wpdb->get_row($wpdb->prepare("SELECT total_duration, total_duration_formatted FROM $cache_table WHERE book_id = %s", $book_id));
$existing_duration = $existing ? $existing->total_duration : 0;
$existing_duration_formatted = $existing ? $existing->total_duration_formatted : '';

// Суммируем длительности треков, если они уже есть в БД
$total_duration = (int)$wpdb->get_var($wpdb->prepare(
    "SELECT COALESCE(SUM(duration), 0) FROM {$wpdb->prefix}abs_track_durations WHERE book_id = %s",
    $book_id
));

// Если длительности ещё не импортированы — оставляем старые значения или 0
if ($total_duration == 0) {
    $total_duration = $existing_duration;
}

$total_formatted = '';
if ($total_duration > 0) {
    $hours = floor($total_duration / 3600);
    $minutes = floor(($total_duration % 3600) / 60);
    $total_formatted = $hours > 0 ? "{$hours} ч {$minutes} мин" : "{$minutes} мин";
} else {
    $total_formatted = $existing_duration_formatted;
}

$wpdb->replace($cache_table, array(
    'book_id' => $book_id,
    'book_data' => json_encode($item),
    'tracks_data' => json_encode($tracks),
    'total_duration' => $total_duration,
    'total_duration_formatted' => $total_formatted,
    'updated_at' => current_time('mysql')
));
            
            // Создаём/обновляем пост
            $existing = get_posts(array(
                'post_type' => 'post',
                'meta_key' => 'abs_book_id',
                'meta_value' => $book_id,
                'posts_per_page' => 1
            ));
            
            $post_data = array(
                'post_title' => $title,
                'post_content' => '[abs_player]',
                'post_excerpt' => wp_trim_words(wp_strip_all_tags($description), 40),
                'post_status' => 'publish',
                'post_type' => 'post',
                'meta_input' => array(
                    'abs_book_id' => $book_id,
                    'book_author' => $author,
                    'book_genres' => implode(', ', $genres)
                )
            );
            
            if (!empty($existing)) {
                $post_data['ID'] = $existing[0]->ID;
                $result = wp_update_post($post_data);
                if ($result) {
                    $updated++;
                    echo '<div class="log-entry log-success">🔄 Обновлено: ' . esc_html($title) . '</div>';
                }
                                    // Обновляем метаданные в кэше
                    $meta = abs_get_book_meta_from_ranobe($book_id);
            } else {
                $result = wp_insert_post($post_data);
                if ($result) {
                    $created++;
                    echo '<div class="log-entry log-success">✅ Создано: ' . esc_html($title) . '</div>';
                    
                    // Сохраняем обложку в медиатеку
                    $cover_url = "{$server_url}/api/items/{$book_id}/cover?token=" . urlencode($api_key);
                    $tmp = download_url($cover_url);
                    if (!is_wp_error($tmp)) {
                        $file_array = array(
                            'name' => 'cover-' . $result . '.jpg',
                            'tmp_name' => $tmp
                        );
                        $attachment_id = media_handle_sideload($file_array, $result);
                        if (!is_wp_error($attachment_id)) {
                            set_post_thumbnail($result, $attachment_id);
                            update_post_meta($result, '_abs_cover_id', $book_id);
                            echo '<div class="log-entry log-info">📸 Обложка добавлена</div>';
                        }
                        @unlink($tmp);
                    }
                    
                    // Добавляем рубрики (жанры)
                    if (!empty($genres)) {
                        $cat_ids = array();
                        foreach ($genres as $genre) {
                            $term = term_exists($genre, 'category');
                            if (!$term) {
                                $term = wp_insert_term($genre, 'category');
                            }
                            if (!is_wp_error($term)) {
                                $cat_ids[] = is_array($term) ? $term['term_id'] : $term;
                            }
                        }
                        if (!empty($cat_ids)) {
                            wp_set_post_categories($result, $cat_ids);
                        }
                    }
                                        
                    // Сохраняем метаданные в кэш
                    $meta = abs_get_book_meta_from_ranobe($book_id);
                    echo '<div class="log-entry log-info">📋 Метаданные: ' . ($meta['author'] ? 'найдены' : 'не найдены') . '</div>';
                }
            }
            
            flush();
            ob_flush();
        }
    }
    
    echo '<div class="log-entry log-info">━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</div>';
    echo '<div class="log-entry log-success">✅ Импорт завершён!</div>';
    echo '<div class="log-entry log-info">📊 Всего книг: ' . $total . '</div>';
    echo '<div class="log-entry log-success">📝 Создано: ' . $created . '</div>';
    echo '<div class="log-entry log-warning">🔄 Обновлено: ' . $updated . '</div>';
    echo '</div>';
}

// Очистка
add_action('admin_init', function() {
    if (isset($_POST['abs_clean_start']) && check_admin_referer('abs_clean_action')) {
        $books = get_posts(array(
            'post_type' => 'post',
            'meta_key' => 'abs_book_id',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        
        foreach ($books as $book_id) {
            $thumbnail_id = get_post_thumbnail_id($book_id);
            if ($thumbnail_id) {
                wp_delete_attachment($thumbnail_id, true);
            }
            wp_delete_post($book_id, true);
        }
        
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}abs_book_cache");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}abs_track_durations");
        
        echo '<div class="notice notice-success"><p>🗑️ Удалено книг: ' . count($books) . '</p></div>';
    }
});
// Обработка заполнения метаданных
add_action('admin_init', function() {
    if (!isset($_POST['abs_meta_sync']) || !check_admin_referer('abs_meta_action')) return;
    
    global $wpdb;
    $cache_table = $wpdb->prefix . 'abs_book_cache';
    $meta_table = $wpdb->prefix . 'abs_audio_meta';
    
    $books = $wpdb->get_results("SELECT book_id FROM $cache_table WHERE book_id NOT IN (SELECT book_id FROM $meta_table WHERE author != '')");
    
    $total = count($books);
    $found = 0;
    
    echo '<div class="notice notice-info"><p>🔄 Обрабатываю ' . $total . ' книг...</p></div>';
    
    foreach ($books as $book) {
        $meta = abs_get_book_meta_from_ranobe($book->book_id);
        if ($meta['author']) $found++;
    }
    
    echo '<div class="notice notice-success"><p>✅ Готово! Найдены метаданные для ' . $found . ' из ' . $total . ' книг.</p></div>';
});