<?php
/**
 * abs-ajax.php - AJAX обработчики для плеера
 */

// ВРЕМЕННО: логирование всех AJAX-запросов
function _abs_debug_log($action, $data = []) {
    $log_file = get_template_directory() . '/backups/debug-ajax.log';
    $entry = date('[Y-m-d H:i:s]') . " ACTION: {$action} | USER: " . get_current_user_id() . " | DATA: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
    file_put_contents($log_file, $entry, FILE_APPEND);
}

// ========== ДАННЫЕ КНИГИ ИЗ БД ==========
add_action('wp_ajax_get_abs_book_data', 'abs_ajax_get_book_data');
add_action('wp_ajax_nopriv_get_abs_book_data', 'abs_ajax_get_book_data');

function abs_ajax_get_book_data() {
    _abs_debug_log('get_abs_book_data', ['GET' => $_GET]);
    
    $book_id = sanitize_text_field($_GET['book_id']);
    
    if (!$book_id) {
        wp_send_json_error('Нет ID книги');
    }
    
    global $wpdb;
    $cache_table = $wpdb->prefix . 'abs_book_cache';
    
    $cached = $wpdb->get_row($wpdb->prepare(
        "SELECT book_data, tracks_data, total_duration, total_duration_formatted FROM $cache_table WHERE book_id = %s",
        $book_id
    ));
    
    if ($cached && $cached->book_data) {
    $book_data = json_decode($cached->book_data, true);
    $tracks = json_decode($cached->tracks_data, true) ?: [];
    
    // Добавляем пути к файлам в MinIO
    $book_title = $book_data['media']['metadata']['title'] ?? '';
    if ($book_title && $tracks) {
        foreach ($tracks as &$track) {
            $track['book_folder'] = $book_title;
            $track['file_name'] = $track['name'] ?? ($track['file_id'] . '.mp3');
            // Генерируем подписанный URL (временный)
 if (function_exists('s3_get_presigned_url')) {
     $key = $book_title . '/' . $track['file_name'];
     $track['minio_url'] = s3_get_presigned_url('1001ranobe-audio', $key);
 }
        }
    }
    
    wp_send_json_success(array(
        'from_cache' => true,
        'book_data' => $book_data,
        'tracks' => $tracks,
        'total_duration' => $cached->total_duration,
        'total_duration_formatted' => $cached->total_duration_formatted
    ));
    return;
}
    
    wp_send_json_error('Книга не найдена в кэше. Запустите импорт книг.');
}

// ========== ДЛИТЕЛЬНОСТИ ТРЕКОВ ИЗ БД ==========
add_action('wp_ajax_get_abs_durations', 'abs_ajax_get_durations');
add_action('wp_ajax_nopriv_get_abs_durations', 'abs_ajax_get_durations');

function abs_ajax_get_durations() {
    _abs_debug_log('get_abs_durations', ['GET' => $_GET]);
    
    $book_id = sanitize_text_field($_GET['book_id']);
    
    if (!$book_id) {
        wp_send_json_error('Нет ID книги');
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'abs_track_durations';
    
    $tracks = $wpdb->get_results($wpdb->prepare(
        "SELECT track_index, duration_formatted FROM $table WHERE book_id = %s ORDER BY track_index ASC",
        $book_id
    ));
    
    if ($tracks) {
        wp_send_json_success(array('tracks' => $tracks));
    } else {
        wp_send_json_error('Нет данных. Запустите импорт длительностей.');
    }
}

// ========== ОБЛОЖКА ==========
add_action('wp_ajax_get_abs_cover', 'abs_ajax_get_cover');
add_action('wp_ajax_nopriv_get_abs_cover', 'abs_ajax_get_cover');

function abs_ajax_get_cover() {
    $book_id = sanitize_text_field($_GET['book_id']);
    
    if (!$book_id) {
        status_header(400);
        echo 'Missing book_id';
        exit;
    }
    
    // Ищем обложку в медиатеке
    $attachment_id = abs_get_cover_attachment_id($book_id);
    if ($attachment_id) {
        $image_path = get_attached_file($attachment_id);
        if ($image_path && file_exists($image_path)) {
            header('Content-Type: ' . wp_get_image_mime($image_path));
            header('Cache-Control: public, max-age=86400');
            readfile($image_path);
            exit;
        }
    }
    
    // Если нет в медиатеке — проксируем из ABS
    $api_key = defined('ABS_API_KEY') ? ABS_API_KEY : '';
    $server_url = 'https://audiobook.1001ranobe.ru';
    
    $response = wp_remote_get("{$server_url}/api/items/{$book_id}/cover", array(
        'headers' => array('Authorization' => 'Bearer ' . $api_key),
        'timeout' => 30,
        'sslverify' => false,
    ));
    
    if (is_wp_error($response)) {
        status_header(404);
        exit;
    }
    
    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) {
        status_header($code);
        exit;
    }
    
    $content_type = wp_remote_retrieve_header($response, 'content-type');
    $body = wp_remote_retrieve_body($response);
    
    header('Content-Type: ' . $content_type);
    header('Cache-Control: public, max-age=86400');
    echo $body;
    exit;
}

function abs_get_cover_attachment_id($book_id) {
    global $wpdb;
    return $wpdb->get_var($wpdb->prepare(
        "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_abs_cover_id' AND meta_value = %s",
        $book_id
    ));
}

// ========== РЕЙТИНГ ==========
add_action('wp_ajax_save_abs_rating', 'abs_ajax_save_rating');
add_action('wp_ajax_nopriv_save_abs_rating', 'abs_ajax_save_rating');

function abs_ajax_save_rating() {
    _abs_debug_log('save_rating', ['POST' => $_POST]);
    
    $book_id = sanitize_text_field($_POST['book_id']);
    $rating = intval($_POST['rating']);
    $user_id = get_current_user_id();
    
    if (!$book_id || $rating < 1 || $rating > 5) {
        wp_send_json_error('Неверные данные');
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'abs_ratings';
    $user_ip = $_SERVER['REMOTE_ADDR'];
    
    if ($user_id) {
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT rating FROM $table WHERE book_id = %s AND user_id = %d",
            $book_id, $user_id
        ));
    } else {
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT rating FROM $table WHERE book_id = %s AND user_ip = %s AND user_id = 0",
            $book_id, $user_ip
        ));
    }
    
    if ($existing) {
        wp_send_json_error('Вы уже голосовали');
        return;
    }
    
    $wpdb->insert($table, array(
        'book_id' => $book_id,
        'user_id' => $user_id ?: 0,
        'rating' => $rating,
        'user_ip' => $user_ip,
        'created_at' => current_time('mysql')
    ));
    
    $avg = $wpdb->get_var($wpdb->prepare(
        "SELECT AVG(rating) FROM $table WHERE book_id = %s",
        $book_id
    ));
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE book_id = %s",
        $book_id
    ));
    
    wp_send_json_success(array(
        'average' => round($avg, 1),
        'count' => intval($count)
    ));
}

add_action('wp_ajax_get_abs_rating', 'abs_ajax_get_rating');
add_action('wp_ajax_nopriv_get_abs_rating', 'abs_ajax_get_rating');

function abs_ajax_get_rating() {
    $book_id = sanitize_text_field($_GET['book_id']);
    
    if (!$book_id) {
        wp_send_json_error('Нет ID книги');
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'abs_ratings';
    
    $avg = $wpdb->get_var($wpdb->prepare(
        "SELECT AVG(rating) FROM $table WHERE book_id = %s",
        $book_id
    ));
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE book_id = %s",
        $book_id
    ));
    
    $user_id = get_current_user_id();
    $user_rating = 0;
    if ($user_id) {
        $user_rating = $wpdb->get_var($wpdb->prepare(
            "SELECT rating FROM $table WHERE book_id = %s AND user_id = %d",
            $book_id, $user_id
        ));
    } else {
        $user_ip = $_SERVER['REMOTE_ADDR'];
        $user_rating = $wpdb->get_var($wpdb->prepare(
            "SELECT rating FROM $table WHERE book_id = %s AND user_ip = %s AND user_id = 0",
            $book_id, $user_ip
        ));
    }
    
    wp_send_json_success(array(
        'average' => $avg ? round($avg, 1) : 0,
        'count' => intval($count),
        'user_rating' => intval($user_rating)
    ));
}

// ========== СТАТУС КНИГИ ==========
add_action('wp_ajax_save_book_status', 'abs_ajax_save_status');
add_action('wp_ajax_nopriv_save_book_status', 'abs_ajax_save_status');

function abs_ajax_save_status() {
    _abs_debug_log('save_status', ['POST' => $_POST]);
    
    $book_id = sanitize_text_field($_POST['book_id']);
    $status = sanitize_text_field($_POST['status']);
    $user_id = get_current_user_id();
    
    if (!$user_id || !$book_id || !$status) {
        wp_send_json_error('Нет данных');
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'abs_book_status';
    $wpdb->replace($table, array(
        'user_id' => $user_id,
        'book_id' => $book_id,
        'status' => $status,
        'updated_at' => current_time('mysql')
    ));
    
    wp_send_json_success();
}

add_action('wp_ajax_get_book_status', 'abs_ajax_get_status');
add_action('wp_ajax_nopriv_get_book_status', 'abs_ajax_get_status');

function abs_ajax_get_status() {
    $book_id = sanitize_text_field($_GET['book_id']);
    $user_id = get_current_user_id();
    
    if (!$user_id) {
        wp_send_json_success(array('status' => 'listening'));
        return;
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'abs_book_status';
    $status = $wpdb->get_var($wpdb->prepare(
        "SELECT status FROM $table WHERE user_id = %d AND book_id = %s",
        $user_id, $book_id
    ));
    
    wp_send_json_success(array('status' => $status ?: 'listening'));
}

// ========== ИЗБРАННОЕ ==========
add_action('wp_ajax_toggle_favorite', 'abs_ajax_toggle_favorite');
add_action('wp_ajax_nopriv_toggle_favorite', 'abs_ajax_toggle_favorite');

function abs_ajax_toggle_favorite() {
    _abs_debug_log('toggle_favorite', ['POST' => $_POST]);
    
    $book_id = sanitize_text_field($_POST['book_id']);
    $type = sanitize_text_field($_POST['type'] ?? 'audio');
    $user_id = get_current_user_id();
    
    if (!$user_id) {
        wp_send_json_error('Не авторизован');
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'abs_favorites';
    
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table WHERE user_id = %d AND book_id = %s AND type = %s",
        $user_id, $book_id, $type
    ));
    
    if ($exists) {
        $wpdb->delete($table, array('user_id' => $user_id, 'book_id' => $book_id, 'type' => $type));
        wp_send_json_success(array('favorite' => false));
    } else {
        $wpdb->insert($table, array(
            'user_id'  => $user_id,
            'book_id'  => $book_id,
            'type'     => $type,
            'ranobe_id' => ($type === 'text') ? intval($book_id) : 0,
            'added_at' => current_time('mysql')
        ));
        wp_send_json_success(array('favorite' => true));
    }
}

add_action('wp_ajax_is_favorite', 'abs_ajax_is_favorite');
add_action('wp_ajax_nopriv_is_favorite', 'abs_ajax_is_favorite');

function abs_ajax_is_favorite() {
    $book_id = sanitize_text_field($_GET['book_id']);
    $type = sanitize_text_field($_GET['type'] ?? 'audio');
    $user_id = get_current_user_id();
    
    if (!$user_id) {
        wp_send_json_success(array('favorite' => false));
        return;
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'abs_favorites';
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table WHERE user_id = %d AND book_id = %s AND type = %s",
        $user_id, $book_id, $type
    ));
    
    wp_send_json_success(array('favorite' => (bool)$exists));
}

// ========== ПРОГРЕСС ==========
add_action('wp_ajax_save_abs_progress', 'abs_ajax_save_progress');
add_action('wp_ajax_nopriv_save_abs_progress', 'abs_ajax_save_progress_nopriv');

function abs_ajax_save_progress() {
    _abs_debug_log('save_progress', ['POST' => $_POST]);
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('Не авторизован');
        return;
    }
    
    $book_id = sanitize_text_field($_POST['book_id']);
    $track_index = intval($_POST['track_index']);
    $progress_seconds = intval($_POST['progress_seconds']);
    
    global $wpdb;
    $table = $wpdb->prefix . 'abs_progress';
    $durations_table = $wpdb->prefix . 'abs_track_durations';
    
    // Получаем сумму длительности предыдущих треков
    $prev_tracks_duration = (int)$wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(duration), 0) FROM $durations_table 
         WHERE book_id = %s AND track_index < %d",
        $book_id, $track_index
    ));
    
    // Общее прослушанное время = длительность предыдущих треков + текущая позиция
    $total_progress = $prev_tracks_duration + $progress_seconds;
    
    // Получаем предыдущее значение прогресса
    $old_progress = (int)$wpdb->get_var($wpdb->prepare(
        "SELECT total_progress_seconds FROM $table WHERE user_id = %d AND book_id = %s",
        $user_id, $book_id
    ));
    
    // Считаем разницу (только вперёд)
    $new_seconds = 0;
    if ($total_progress > $old_progress) {
        $new_seconds = $total_progress - $old_progress;
    }
    
    $wpdb->replace($table, array(
        'user_id' => $user_id,
        'book_id' => $book_id,
        'track_index' => $track_index,
        'progress_seconds' => $progress_seconds,
        'total_progress_seconds' => $total_progress,
        'updated_at' => current_time('mysql')
    ));
    
    // Обновляем общую статистику пользователя (только разницу)
    if ($new_seconds > 0) {
        $user_stats_table = $wpdb->prefix . 'abs_user_stats';
        $existing_user = $wpdb->get_var($wpdb->prepare(
            "SELECT total_seconds FROM $user_stats_table WHERE user_id = %d",
            $user_id
        ));
        if ($existing_user !== null) {
            $wpdb->query($wpdb->prepare(
                "UPDATE $user_stats_table SET total_seconds = total_seconds + %d WHERE user_id = %d",
                $new_seconds, $user_id
            ));
        } else {
            $wpdb->insert($user_stats_table, array(
                'user_id' => $user_id,
                'total_seconds' => $new_seconds
            ));
        }
        
        // Обновляем статистику книги (только разницу)
        $book_stats_table = $wpdb->prefix . 'abs_book_stats';
        $existing_book = $wpdb->get_var($wpdb->prepare(
            "SELECT total_seconds FROM $book_stats_table WHERE book_id = %s",
            $book_id
        ));
        if ($existing_book !== null) {
            $wpdb->query($wpdb->prepare(
                "UPDATE $book_stats_table SET total_seconds = total_seconds + %d WHERE book_id = %s",
                $new_seconds, $book_id
            ));
        } else {
            $wpdb->insert($book_stats_table, array(
                'book_id' => $book_id,
                'total_seconds' => $new_seconds,
                'total_listeners' => 1
            ));
        }
    }
    
    wp_send_json_success();
}
// ===== ОБРАБОТЧИК ДЛЯ ГОСТЕЙ (БЕЗ device_id) =====
function abs_ajax_save_progress_nopriv() {
    $book_id = sanitize_text_field($_POST['book_id']);
    $track_index = intval($_POST['track_index']);
    $progress_seconds = intval($_POST['progress_seconds']);
    
    if (!$book_id) {
        wp_send_json_error('Нет данных');
    }
    
    // Используем IP + браузер как идентификатор гостя
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $guest_id = md5($ip . $user_agent);
    
    $key = 'abs_progress_guest_' . $guest_id . '_' . $book_id;
    
    set_transient($key, array(
        'track_index' => $track_index,
        'progress_seconds' => $progress_seconds,
        'updated_at' => time()
    ), 30 * DAY_IN_SECONDS);
    
    wp_send_json_success();
}

add_action('wp_ajax_get_abs_progress', 'abs_ajax_get_progress');
add_action('wp_ajax_nopriv_get_abs_progress', 'abs_ajax_get_progress_nopriv');

function abs_ajax_get_progress() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Не авторизован');
    }
    
    $user_id = get_current_user_id();
    $book_id = sanitize_text_field($_GET['book_id']);
    
    global $wpdb;
    $table = $wpdb->prefix . 'abs_progress';
    $progress = $wpdb->get_row($wpdb->prepare(
        "SELECT track_index, progress_seconds FROM $table WHERE user_id = %d AND book_id = %s",
        $user_id, $book_id
    ));
    
    if ($progress) {
        wp_send_json_success(array(
            'track_index' => (int)$progress->track_index,
            'progress_seconds' => (int)$progress->progress_seconds
        ));
    } else {
        wp_send_json_error('Нет прогресса');
    }
}

// ===== ПОЛУЧЕНИЕ ПРОГРЕССА ДЛЯ ГОСТЕЙ =====
function abs_ajax_get_progress_nopriv() {
    $book_id = sanitize_text_field($_GET['book_id']);
    
    if (!$book_id) {
        wp_send_json_error('Нет ID книги');
    }
    
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $guest_id = md5($ip . $user_agent);
    
    $key = 'abs_progress_guest_' . $guest_id . '_' . $book_id;
    $progress = get_transient($key);
    
    if ($progress && isset($progress['track_index'])) {
        wp_send_json_success(array(
            'track_index' => $progress['track_index'],
            'progress_seconds' => $progress['progress_seconds']
        ));
    } else {
        wp_send_json_error('Нет прогресса');
    }
}


// Удаление любого прогресса (аудио + текст)
add_action('wp_ajax_remove_book_progress', 'abs_ajax_remove_book_progress');

function abs_ajax_remove_book_progress() {
    _abs_debug_log('remove_book_progress', ['POST' => $_POST]);
    
    $user_id = get_current_user_id();
    $book_id = sanitize_text_field($_POST['book_id']);
    $type = sanitize_text_field($_POST['type'] ?? 'audio');
    
    if (!$user_id || !$book_id) {
        wp_send_json_error('Неверные данные');
    }
    
    global $wpdb;
    
    if ($type === 'audio') {
        // Если book_id не число (UUID), ищем post_id
        $clean_id = $book_id;
        if (!is_numeric($book_id)) {
            $post_id = $wpdb->get_var($wpdb->prepare(
                "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'abs_book_id' AND meta_value = %s",
                $book_id
            ));
            $clean_id = $post_id ?: $book_id;
        }
        $wpdb->delete($wpdb->prefix . 'abs_progress', array('user_id' => $user_id, 'book_id' => $clean_id));
    } else {
        $wpdb->delete($wpdb->prefix . 'abs_reading_progress', array('user_id' => $user_id, 'ranobe_id' => intval($book_id)));
    }
    
    wp_send_json_success();
}


?>