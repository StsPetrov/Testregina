<?php
/**
 * importer.php - Исправленный импортёр аудиокниг
 */

// Включаем отладку
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Загружаем WordPress
require_once('../../../wp-load.php');

// Подключаем необходимые файлы для работы с медиафайлами
require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');

echo '<h1>Импорт аудиокниг из Audiobookshelf</h1>';

// Проверка прав
if (!current_user_can('manage_options')) {
    echo '<p style="color:red;">❌ Нет прав доступа. Войдите как администратор.</p>';
    echo '<a href="/wp-admin">Войти в админку</a>';
    exit;
}

echo '<p>✅ Пользователь авторизован</p>';

// Проверка API ключа
if (!defined('ABS_API_KEY')) {
    echo '<p style="color:red;">❌ API ключ не найден! Создайте файл wp-config-abs.php в корне WordPress</p>';
    exit;
}

echo '<p>✅ API ключ найден</p>';

$api_key = ABS_API_KEY;
$server_url = 'http://1001ranobe.ru:13378';

// Проверка подключения к ABS
echo '<h2>Проверка подключения...</h2>';

$test_response = wp_remote_get("{$server_url}/api/libraries", array(
    'headers' => array('Authorization' => 'Bearer ' . $api_key),
    'timeout' => 30
));

if (is_wp_error($test_response)) {
    echo '<p style="color:red;">❌ Ошибка подключения к ABS: ' . $test_response->get_error_message() . '</p>';
    exit;
}

$http_code = wp_remote_retrieve_response_code($test_response);
echo '<p>✅ Статус ответа ABS: ' . $http_code . '</p>';

if ($http_code !== 200) {
    echo '<p style="color:red;">❌ Ошибка авторизации. Проверьте API ключ.</p>';
    exit;
}

$libraries_data = json_decode(wp_remote_retrieve_body($test_response), true);

// Получаем библиотеки из ключа 'libraries'
$libraries = array();

if (isset($libraries_data['libraries']) && is_array($libraries_data['libraries'])) {
    $libraries = $libraries_data['libraries'];
    echo '<p>📁 Найдено библиотек: ' . count($libraries) . '</p>';
} else {
    echo '<p style="color:red;">❌ Не удалось получить список библиотек</p>';
    exit;
}

$total_books = 0;
$created = 0;
$updated = 0;

foreach ($libraries as $library) {
    $library_id = $library['id'];
    $library_name = $library['name'];
    
    echo '<h3>📚 Библиотека: ' . esc_html($library_name) . ' (ID: ' . $library_id . ')</h3>';
    
    // Получаем книги из библиотеки
    $items_response = wp_remote_get("{$server_url}/api/libraries/{$library_id}/items?limit=500", array(
        'headers' => array('Authorization' => 'Bearer ' . $api_key),
        'timeout' => 60
    ));
    
    if (is_wp_error($items_response)) {
        echo '<p style="color:red;">❌ Ошибка: ' . $items_response->get_error_message() . '</p>';
        continue;
    }
    
    $items_data = json_decode(wp_remote_retrieve_body($items_response), true);
    
    // Получаем элементы из 'results'
    $items = array();
    
    if (isset($items_data['results']) && is_array($items_data['results'])) {
        $items = $items_data['results'];
        echo '<p>📚 Найдено элементов: ' . count($items) . '</p>';
    } else {
        echo '<p>⚠️ В библиотеке нет элементов</p>';
        continue;
    }
    
    foreach ($items as $item) {
        // Проверяем тип элемента
        $media_type = isset($item['mediaType']) ? $item['mediaType'] : '';
        
        if ($media_type !== 'book') {
            continue;
        }
        
        $total_books++;
        
        // Извлекаем метаданные
        $metadata = isset($item['media']['metadata']) ? $item['media']['metadata'] : array();
        
        $title = isset($metadata['title']) ? $metadata['title'] : 'Без названия';
        $book_id = $item['id'];
        $description = isset($metadata['description']) ? $metadata['description'] : '';
        
        // Жанры
        $genres = array();
        if (isset($metadata['genres']) && is_array($metadata['genres'])) {
            $genres = $metadata['genres'];
        }
        
        // Автор
        $author = '';
        if (isset($metadata['authorName'])) {
            $author = $metadata['authorName'];
        } elseif (isset($metadata['author'])) {
            $author = $metadata['author'];
        } elseif (isset($metadata['authors']) && is_array($metadata['authors']) && !empty($metadata['authors'])) {
            $author = is_array($metadata['authors'][0]) ? $metadata['authors'][0]['name'] : $metadata['authors'][0];
        }
        
        echo '<p><strong>' . $total_books . '.</strong> ' . esc_html($title) . '</p>';
        echo '<small>ID: ' . $book_id . '</small><br>';
        if ($author) {
            echo '<small>Автор: ' . esc_html($author) . '</small><br>';
        }
        if (!empty($genres)) {
            echo '<small>Жанры: ' . esc_html(implode(', ', $genres)) . '</small><br>';
        }
        
        // Проверяем существование записи
        $existing_posts = get_posts(array(
            'post_type' => 'post',
            'meta_key' => 'abs_book_id',
            'meta_value' => $book_id,
            'posts_per_page' => 1,
            'post_status' => 'any'
        ));
        
        // Формируем контент записи
        $post_content = '[abs_player]';
        
        $post_data = array(
            'post_title' => wp_strip_all_tags($title),
            'post_content' => $post_content,
            'post_excerpt' => wp_trim_words($description, 40),
            'post_status' => 'publish',
            'post_type' => 'post',
            'meta_input' => array(
                'abs_book_id' => $book_id,
                'book_author' => $author,
                'book_genres' => implode(', ', $genres)
            )
        );
        
        if (!empty($existing_posts)) {
            $post_data['ID'] = $existing_posts[0]->ID;
            $result = wp_update_post($post_data);
            if ($result) {
                $updated++;
                echo '<span style="color:orange;">🔄 Обновлено (ID: ' . $result . ')</span><br>';
            } else {
                echo '<span style="color:red;">❌ Ошибка обновления</span><br>';
            }
        } else {
            $result = wp_insert_post($post_data);
            if ($result) {
                $created++;
                echo '<span style="color:green;">✅ Создано (ID: ' . $result . ')</span><br>';
                
                // Добавляем обложку
                $cover_url = "{$server_url}/api/items/{$book_id}/cover?token=" . urlencode($api_key);
                
                // Скачиваем изображение
                $tmp = download_url($cover_url);
                if (!is_wp_error($tmp)) {
                    $file_array = array(
                        'name' => 'cover-' . $result . '.jpg',
                        'tmp_name' => $tmp
                    );
                    $attachment_id = media_handle_sideload($file_array, $result);
                    if (!is_wp_error($attachment_id)) {
                        set_post_thumbnail($result, $attachment_id);
                        update_post_meta($result, '_abs_cover_id', $attachment_id);
                        echo '<span style="color:blue;">📸 Обложка добавлена</span><br>';
                    } else {
                        echo '<span style="color:red;">❌ Ошибка добавления обложки: ' . $attachment_id->get_error_message() . '</span><br>';
                    }
                    @unlink($tmp);
                } else {
                    echo '<span style="color:red;">❌ Ошибка скачивания обложки: ' . $tmp->get_error_message() . '</span><br>';
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
                        echo '<span style="color:blue;">🏷️ Рубрики добавлены: ' . esc_html(implode(', ', $genres)) . '</span><br>';
                    }
                }
            } else {
                echo '<span style="color:red;">❌ Ошибка создания</span><br>';
            }
        }
        
        echo '<hr style="margin: 5px 0;">';
        flush();
        ob_flush();
    }
}

echo '<hr>';
echo '<h2>✅ Импорт завершён!</h2>';
echo '<p>📊 Всего книг: ' . $total_books . '</p>';
echo '<p>📝 Создано: ' . $created . '</p>';
echo '<p>🔄 Обновлено: ' . $updated . '</p>';
echo '<p><a href="/wp-admin/edit.php">🔗 Перейти к списку записей</a></p>';
?>