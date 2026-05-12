<?php
/**
 * abs-db.php - Создание и управление таблицами БД
 */

function abs_create_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Таблица кэша данных книг
    $cache_table = $wpdb->prefix . 'abs_book_cache';
    $sql_cache = "CREATE TABLE IF NOT EXISTS $cache_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        book_id varchar(100) NOT NULL,
        book_data longtext NOT NULL,
        file_hash varchar(64) DEFAULT '',
        tracks_data longtext,
        total_duration int DEFAULT 0,
        total_duration_formatted varchar(50) DEFAULT '',
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY book_id (book_id)
    ) $charset_collate;";
    
    // Таблица длительностей треков
    $durations_table = $wpdb->prefix . 'abs_track_durations';
    $sql_durations = "CREATE TABLE IF NOT EXISTS $durations_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        book_id varchar(100) NOT NULL,
        track_index int NOT NULL DEFAULT 0,
        track_name varchar(255) DEFAULT '',
        duration int NOT NULL DEFAULT 0,
        duration_formatted varchar(20) DEFAULT '',
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY book_track (book_id, track_index)
    ) $charset_collate;";
    
    // Таблица прогресса
    $progress_table = $wpdb->prefix . 'abs_progress';
    $sql_progress = "CREATE TABLE IF NOT EXISTS $progress_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL DEFAULT 0,
        book_id varchar(100) NOT NULL,
        track_index int NOT NULL DEFAULT 0,
        current_time int NOT NULL DEFAULT 0,
        duration int NOT NULL DEFAULT 0,
        is_finished boolean DEFAULT false,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY user_book (user_id, book_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_cache);
    dbDelta($sql_durations);
    dbDelta($sql_progress);

        // Таблица общей статистики пользователя (не сбрасывается при удалении из прогресса)
    $user_stats_table = $wpdb->prefix . 'abs_user_stats';
    $sql_user_stats = "CREATE TABLE IF NOT EXISTS $user_stats_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        total_seconds int NOT NULL DEFAULT 0,
        completed_books int NOT NULL DEFAULT 0,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY user_id (user_id)
    ) $charset_collate;";
    
    // Таблица статистики по книгам (не сбрасывается, для popular)
    $book_stats_table = $wpdb->prefix . 'abs_book_stats';
    $sql_book_stats = "CREATE TABLE IF NOT EXISTS $book_stats_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        book_id varchar(100) NOT NULL,
        total_seconds int NOT NULL DEFAULT 0,
        total_listeners int NOT NULL DEFAULT 0,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY book_id (book_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_cache);
    dbDelta($sql_durations);
    dbDelta($sql_progress);
    dbDelta($sql_user_stats);
    dbDelta($sql_book_stats);
}
add_action('after_switch_theme', 'abs_create_tables');

// Вспомогательная функция для вычисления хеша
function abs_calculate_file_hash($book_data) {
    $files = $book_data['libraryFiles'] ?? [];
    $hash_data = [];
    foreach ($files as $file) {
        $hash_data[] = [
            'name' => $file['metadata']['filename'] ?? '',
            'modified' => $file['mtimeMs'] ?? 0,
            'size' => $file['size'] ?? 0
        ];
    }
    return md5(json_encode($hash_data));
}
?>