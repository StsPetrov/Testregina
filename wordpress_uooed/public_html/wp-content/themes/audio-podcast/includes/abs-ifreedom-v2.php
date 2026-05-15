<?php
/**
 * Парсер ifreedom.su v2.0
 * Единая функция для AJAX и Cron
 * Авторы через запятую, форматирование HTML, пропуск ошибок
 */

if (!defined('ABSPATH')) exit;

// ============================================================
// 1. ОЧЕРЕДЬ ПАРСИНГА
// ============================================================
function abs_ifreedom_v2_create_queue_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'abs_ifreedom_v2_queue';
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    
    dbDelta("CREATE TABLE $table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(500) NOT NULL UNIQUE,
        title VARCHAR(1000) DEFAULT '',
        url VARCHAR(500) DEFAULT '',
        chapters_total INT DEFAULT 0,
        chapters_loaded INT DEFAULT 0,
        status ENUM('new','parsing','done','error','skip') DEFAULT 'new',
        error_msg TEXT NULL,
        last_parsed_at DATETIME NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset");
}
add_action('after_switch_theme', 'abs_ifreedom_v2_create_queue_table');

// ============================================================
// 2. HTTP-ЗАПРОСЫ
// ============================================================
function abs_ifreedom_v2_get_html($url, $attempt = 0) {
    $ua_list = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:125.0) Gecko/20100101 Firefox/125.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_4_1) AppleWebKit/605.1.15 Version/17.4.1 Safari/605.1.15',
    ];
    
    $args = [
        'timeout'    => 15,
        'user-agent' => $ua_list[array_rand($ua_list)],
        'headers'    => [
            'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'ru-RU,ru;q=0.9,en;q=0.8',
            'Referer'         => 'https://ifreedom.su/',
        ],
    ];
    
    $response = wp_remote_get($url, $args);
    
    if (is_wp_error($response)) {
        if ($attempt < 3) {
            sleep(5 * ($attempt + 1));
            return abs_ifreedom_v2_get_html($url, $attempt + 1);
        }
        return ['error' => $response->get_error_message()];
    }
    
    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) {
        if (($code === 429 || $code === 403) && $attempt < 3) {
            sleep(10 * ($attempt + 1));
            return abs_ifreedom_v2_get_html($url, $attempt + 1);
        }
        return ['error' => "HTTP $code"];
    }
    
    return wp_remote_retrieve_body($response);
}

// ============================================================
// 3. ПАРСИНГ СТРАНИЦЫ КНИГИ
// ============================================================
function abs_ifreedom_v2_parse_book_page($slug) {
    $url = "https://ifreedom.su/ranobe/{$slug}/";
    $html = abs_ifreedom_v2_get_html($url);
    if (is_array($html) && isset($html['error'])) return $html;
    
    $dom = new DOMDocument();
    @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    $xpath = new DOMXPath($dom);
    
    $data = [
        'slug'        => $slug,
        'url'         => $url,
        'title'       => '',
        'cover_url'   => '',
        'authors'     => [],
        'genres'      => [],
        'language'    => '',
        'status'      => 'ongoing',
        'description' => '',
        'chapters'    => [],
    ];
    
    // Название
    $title_node = $xpath->query("//h1")->item(0);
    if ($title_node) $data['title'] = trim($title_node->textContent);
    
    // Обложка
    $cover_node = $xpath->query("//div[contains(@class, 'book-img')]//img")->item(0);
    if ($cover_node) $data['cover_url'] = $cover_node->getAttribute('src');
    
    // Авторы
    $author_nodes = $xpath->query("//div[contains(@class, 'book-info-list')]//a[contains(@href, 'authorid=')]");
    foreach ($author_nodes as $a) {
        $name = trim($a->textContent);
        if ($name && $name !== 'Ifreedom' && $name !== 'ifreedom') {
            $data['authors'][] = $name;
        }
    }
    
    // Жанры
    $genre_nodes = $xpath->query("//div[contains(@class, 'genreslist')]//a");
    foreach ($genre_nodes as $g) {
        $genre = trim($g->textContent);
        if ($genre) $data['genres'][] = $genre;
    }
    
    // Язык
    $lang_nodes = $xpath->query("//div[contains(@class, 'book-info-list')]//a[contains(@href, 'lang[]=')]");
    if ($lang_nodes->length > 0) $data['language'] = trim($lang_nodes->item(0)->textContent);
    
    // Статус
    $info_lists = $xpath->query("//div[contains(@class, 'book-info-list')]");
    foreach ($info_lists as $list) {
        $text = trim($list->textContent);
        if (stripos($text, 'завершен') !== false) { $data['status'] = 'completed'; break; }
        if (stripos($text, 'заморожен') !== false || stripos($text, 'приостановлен') !== false) { $data['status'] = 'frozen'; break; }
    }
    
    // Описание
    $desc_node = $xpath->query("//div[contains(@class, 'tab-content')]//div[@data-name='Описание']")->item(0);
    if ($desc_node) $data['description'] = trim($desc_node->textContent);
    
    // Главы
    $chapter_nodes = $xpath->query("//div[contains(@class, 'chapterlinks')]//div[contains(@class, 'chapterinfo')]");
    foreach ($chapter_nodes as $ch) {
        $link = $xpath->query(".//a", $ch)->item(0);
        if (!$link) continue;
        
        $vip_node = $xpath->query(".//span[contains(@class, 'chapico')]", $ch)->item(0);
        $is_vip = $vip_node && stripos(trim($vip_node->textContent), 'VIP') !== false;
        if ($is_vip) continue;
        
        $chapter_url = $link->getAttribute('href');
        if (strpos($chapter_url, 'http') !== 0) $chapter_url = 'https://ifreedom.su' . $chapter_url;
        
        $data['chapters'][] = [
            'title' => trim($link->textContent),
            'url'   => $chapter_url,
        ];
    }
    
    // Разворачиваем (на источнике новые сверху)
    $data['chapters'] = array_reverse($data['chapters']);
    
    return $data;
}

// ============================================================
// 4. ПАРСИНГ ГЛАВЫ (СОХРАНЯЕМ HTML)
// ============================================================
function abs_ifreedom_v2_parse_chapter($chapter_url) {
    $html = abs_ifreedom_v2_get_html($chapter_url);
    if (is_array($html) && isset($html['error'])) return $html;
    
    $dom = new DOMDocument();
    @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    $xpath = new DOMXPath($dom);
    
    // Заголовок
    $title_node = $xpath->query("//h1")->item(0);
    $title = $title_node ? trim($title_node->textContent) : '';
    
    // Контент (сохраняем HTML-теги)
    $content_parts = [];
    $paragraphs = $xpath->query("//div[contains(@class, 'chapter-content')]//p");
    foreach ($paragraphs as $p) {
        $html_content = trim($dom->saveHTML($p));
        if ($html_content && $html_content !== '<p>.</p>' && !preg_match('/^<p>[.\s\-—]+<\/p>$/', $html_content)) {
            $content_parts[] = $html_content;
        }
    }
    
    return [
        'title'   => $title,
        'content' => implode("\n", $content_parts),
    ];
}

// ============================================================
// 5. СОХРАНЕНИЕ КНИГИ
// ============================================================
function abs_ifreedom_v2_save_book($book_data) {
    $existing = get_posts([
        'post_type'      => 'ranobe',
        'meta_key'       => '_ifreedom_slug',
        'meta_value'     => $book_data['slug'],
        'posts_per_page' => 1,
        'post_status'    => 'any',
    ]);
    
    if (!empty($existing)) {
        $post_id = $existing[0]->ID;
        wp_update_post(['ID' => $post_id, 'post_title' => $book_data['title'], 'post_content' => $book_data['description']]);
    } else {
        $post_id = wp_insert_post([
            'post_type'    => 'ranobe',
            'post_title'   => mb_substr($book_data['title'], 0, 200),
            'post_content' => $book_data['description'],
            'post_status'  => 'publish',
        ]);
    }
    
    if (!$post_id || is_wp_error($post_id)) return ['status' => 'error', 'message' => 'Не удалось создать пост'];
    
    update_post_meta($post_id, '_ranobe_author', implode(', ', $book_data['authors']));
    update_post_meta($post_id, '_ranobe_original_url', $book_data['url']);
    update_post_meta($post_id, '_ifreedom_slug', $book_data['slug']);
    update_post_meta($post_id, '_ranobe_status', $book_data['status']);
    update_post_meta($post_id, '_ranobe_language', $book_data['language']);
    update_post_meta($post_id, '_ranobe_source', 'ifreedom');
    
    // Жанры → категории
    if (!empty($book_data['genres'])) {
        $cat_ids = [];
        foreach ($book_data['genres'] as $genre) {
            $term = term_exists($genre, 'category');
            if (!$term) $term = wp_insert_term($genre, 'category');
            if (!is_wp_error($term)) $cat_ids[] = is_array($term) ? $term['term_id'] : $term;
        }
        if (!empty($cat_ids)) wp_set_post_terms($post_id, $cat_ids, 'category');
    }
    
    // Обложка
    if (!empty($book_data['cover_url']) && !has_post_thumbnail($post_id)) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        
        $tmp = download_url($book_data['cover_url']);
        if (!is_wp_error($tmp)) {
            $file_array = ['name' => 'cover-' . $post_id . '.jpg', 'tmp_name' => $tmp];
            $attachment_id = media_handle_sideload($file_array, $post_id);
            if (!is_wp_error($attachment_id)) set_post_thumbnail($post_id, $attachment_id);
            @unlink($tmp);
        }
    }
    
    return ['status' => 'ok', 'post_id' => $post_id];
}

// ============================================================
// 6. СОХРАНЕНИЕ ГЛАВЫ
// ============================================================
function abs_ifreedom_v2_save_chapter($post_parent, $chapter_num, $chapter_title, $chapter_content) {
    $existing = get_posts([
        'post_type'      => 'chapter',
        'post_parent'    => $post_parent,
        'meta_key'       => '_chapter_number',
        'meta_value'     => $chapter_num,
        'posts_per_page' => 1,
        'post_status'    => 'any',
    ]);
    
    if (!empty($existing)) return $existing[0]->ID; // Уже существует
    
    $chapter_id = wp_insert_post([
        'post_type'    => 'chapter',
        'post_title'   => $chapter_title,
        'post_content' => $chapter_content,
        'post_parent'  => $post_parent,
        'post_status'  => 'publish',
    ]);
    
    if ($chapter_id && !is_wp_error($chapter_id)) {
        update_post_meta($chapter_id, '_chapter_number', $chapter_num);
    }
    
    return $chapter_id;
}

// ============================================================
// 7. ЕДИНАЯ ФУНКЦИЯ ОБРАБОТКИ КНИГИ (AJAX + CRON)
// ============================================================
function abs_ifreedom_v2_process_book($slug) {
    global $wpdb;
    $table = $wpdb->prefix . 'abs_ifreedom_v2_queue';
    
    $book_data = abs_ifreedom_v2_parse_book_page($slug);
    if (isset($book_data['error'])) {
        $wpdb->update($table, ['status' => 'error', 'error_msg' => $book_data['error']], ['slug' => $slug]);
        abs_telegram_log("❌ V2: {$slug} — {$book_data['error']}");
return ['status' => 'error', 'message' => $book_data['error']];
    }
    
    $total_chapters = count($book_data['chapters']);
    $wpdb->update($table, ['chapters_total' => $total_chapters, 'status' => 'parsing'], ['slug' => $slug]);
    
    // Сохраняем книгу
    $save = abs_ifreedom_v2_save_book($book_data);
    if ($save['status'] === 'error') {
        $wpdb->update($table, ['status' => 'error', 'error_msg' => $save['message']], ['slug' => $slug]);
        return $save;
    }
    
    $post_id = $save['post_id'];
    $loaded = 0;
    $errors = 0;
    
    foreach ($book_data['chapters'] as $i => $ch) {
        $chapter_num = $i + 1;
        
        // Пропускаем существующие
        $exists = get_posts([
            'post_type'      => 'chapter',
            'post_parent'    => $post_id,
            'meta_key'       => '_chapter_number',
            'meta_value'     => $chapter_num,
            'posts_per_page' => 1,
            'fields'         => 'ids',
        ]);
        if (!empty($exists)) { $loaded++; continue; }
        
        // Пауза между запросами
        if ($i > 0 && $i % 5 == 0) sleep(1);
        
        $chapter_data = abs_ifreedom_v2_parse_chapter($ch['url']);
        if (isset($chapter_data['error'])) {
            $errors++;
            if ($errors > 10) break; // Слишком много ошибок — останавливаем
            continue;
        }
        
        abs_ifreedom_v2_save_chapter($post_id, $chapter_num, $chapter_data['title'], $chapter_data['content']);
        $loaded++;
        $errors = 0; // Сброс счётчика ошибок
    }
    
    // Обновляем статус
    $wpdb->update($table, [
        'chapters_loaded' => $loaded,
        'status'          => ($loaded >= $total_chapters) ? 'done' : (($errors > 0) ? 'error' : 'new'),
        'last_parsed_at'  => current_time('mysql'),
    ], ['slug' => $slug]);
    
    // Telegram-уведомление
abs_telegram_log("✅ V2: {$book_data['title']} — {$loaded}/{$total_chapters} глав");

return ['status' => 'ok', 'loaded' => $loaded, 'total' => $total_chapters];
}