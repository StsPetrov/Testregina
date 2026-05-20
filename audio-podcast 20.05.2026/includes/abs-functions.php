<?php
/**
 * abs-functions.php - Общие вспомогательные функции
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Получить список авторов из метаданных книги
 */
function abs_get_book_authors($metadata) {
    $authors = array();
    
    if (!empty($metadata['authorName'])) {
        $names = explode(', ', $metadata['authorName']);
        foreach ($names as $name) {
            $name = trim($name);
            if (!empty($name)) {
                $authors[] = $name;
            }
        }
    }
    
    if (!empty($metadata['authors'])) {
        foreach ($metadata['authors'] as $a) {
            $name = $a['name'] ?? $a;
            $name = trim($name);
            if (!empty($name) && !in_array($name, $authors)) {
                $authors[] = $name;
            }
        }
    }
    
    return array_unique($authors);
}

/**
 * Получить URL страницы книги по book_id (с кэшированием)
 */
function abs_get_book_permalink($book_id) {
    global $wpdb;
    
    static $cache = array();
    
    if (isset($cache[$book_id])) {
        return $cache[$book_id];
    }
    
    $page_id = $wpdb->get_var($wpdb->prepare(
        "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'abs_book_id' AND meta_value = %s",
        $book_id
    ));
    
    $permalink = $page_id ? get_permalink($page_id) : '#';
    $cache[$book_id] = $permalink;
    
    return $permalink;
}