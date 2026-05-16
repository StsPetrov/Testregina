<?php
/**
 * Парсер ifreedom.su v2.0
 * Объединённый AJAX + Cron
 * Форматирование HTML, авторы массивом
 * Все настройки, логи, фильтры сохранены
 */

if (!defined('ABSPATH')) exit;

// ============================================================
// 1. ТАБЛИЦА ОЧЕРЕДИ
// ============================================================
function abs_ifreedom_v2_create_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'abs_ifreedom_v2_queue';
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    
    dbDelta("CREATE TABLE $table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(500) NOT NULL UNIQUE,
        title VARCHAR(1000) DEFAULT '',
        url VARCHAR(500) DEFAULT '',
        chapters_count INT DEFAULT 0,
        total_chapters INT DEFAULT 0,
        views INT DEFAULT 0,
        status ENUM('new','parsing','done','error') DEFAULT 'new',
        parsed_chapters INT DEFAULT 0,
        last_parsed_at DATETIME NULL,
        error_msg TEXT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset");
}
add_action('after_switch_theme', 'abs_ifreedom_v2_create_table');

// ============================================================
// 2. НАСТРОЙКИ
// ============================================================
function abs_ifreedom_v2_get_settings() {
    $defaults = [
        'min_delay_ms'      => 1000000,
        'max_delay_ms'      => 3000000,
        'max_per_minute'    => 20,
        'cron_batch_size'   => 15,
        'manual_batch_size' => 30,
        'http_timeout'      => 30,
    ];
    $saved = get_option('abs_ifreedom_v2_settings', []);
    return wp_parse_args($saved, $defaults);
}

function abs_ifreedom_v2_get_user_agents() {
    return [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:125.0) Gecko/20100101 Firefox/125.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_4_1) AppleWebKit/605.1.15 Version/17.4.1 Safari/605.1.15',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/123.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/123.0.0.0 Safari/537.36 Edg/123.0.0.0',
    ];
}

// ============================================================
// 3. HTTP-ЗАПРОСЫ
// ============================================================
function abs_ifreedom_v2_rate_limit() {
    static $requests = 0, $minute_start = 0;
    $now = time();
    if ($now - $minute_start >= 60) { $requests = 0; $minute_start = $now; }
    $requests++;
    $settings = abs_ifreedom_v2_get_settings();
    if ($requests > $settings['max_per_minute']) {
        $wait = 60 - ($now - $minute_start) + 1;
        sleep($wait);
        $requests = 0; $minute_start = time();
    }
    $delay = rand($settings['min_delay_ms'], $settings['max_delay_ms']);
    usleep($delay);
}

function abs_ifreedom_v2_get_html($url, $attempt = 0) {
    abs_ifreedom_v2_rate_limit();
    $ua_list = abs_ifreedom_v2_get_user_agents();
    $settings = abs_ifreedom_v2_get_settings();
    
    $args = [
        'timeout'    => (int)$settings['http_timeout'],
        'user-agent' => $ua_list[array_rand($ua_list)],
        'headers'    => [
            'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'ru-RU,ru;q=0.9,en;q=0.8',
            'Referer'         => 'https://ifreedom.su/',
        ],
    ];
    
    $response = wp_remote_get($url, $args);
    
    if (is_wp_error($response)) {
        if ($attempt < 3) { sleep(5 * ($attempt + 1)); return abs_ifreedom_v2_get_html($url, $attempt + 1); }
        return ['error' => $response->get_error_message()];
    }
    
    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) {
        if (($code === 429 || $code === 403) && $attempt < 3) { sleep(10 * ($attempt + 1)); return abs_ifreedom_v2_get_html($url, $attempt + 1); }
        return ['error' => "HTTP $code"];
    }
    
    return wp_remote_retrieve_body($response);
}

// ============================================================
// 4. СКАНИРОВАНИЕ КАТАЛОГА
// ============================================================
function abs_ifreedom_v2_get_last_catalog_page() {
    $html = abs_ifreedom_v2_get_html('https://ifreedom.su/vse-knigi/');
    if (is_array($html) && isset($html['error'])) return 1;
    
    $dom = new DOMDocument();
    @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    $xpath = new DOMXPath($dom);
    
    $max_page = 1;
    $links = $xpath->query("//div[contains(@class, 'numpagenav')]//a");
    foreach ($links as $link) {
        $href = $link->getAttribute('href');
        if (preg_match('/bpage=(\d+)/', $href, $m)) $max_page = max($max_page, (int)$m[1]);
    }
    return $max_page;
}

function abs_ifreedom_v2_scan_catalog_page($page = 1, $filters = []) {
    $url = 'https://ifreedom.su/vse-knigi/';
    if ($page > 1) $url .= '?bpage=' . $page;
    
    if (!empty($filters)) {
        $sep = ($page > 1) ? '&' : '?';
        foreach ($filters as $key => $values) {
            foreach ((array)$values as $v) {
                $url .= $sep . $key . '[]=' . urlencode($v);
                $sep = '&';
            }
        }
    }
    
    $html = abs_ifreedom_v2_get_html($url);
    if (is_array($html) && isset($html['error'])) return $html;
    
    $dom = new DOMDocument();
    @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    $xpath = new DOMXPath($dom);
    
    $books = [];
    $nodes = $xpath->query("//div[contains(@class, 'item-book-slide')]");
    foreach ($nodes as $node) {
        $link = $xpath->query(".//a[contains(@class, 'link-book-slide')]", $node)->item(0);
        if (!$link) continue;
        
        $book_url = $link->getAttribute('href');
        if (!preg_match('#/ranobe/([^/]+)/#', $book_url, $m)) continue;
        
        $title_node = $xpath->query(".//div[contains(@class, 'block-book-slide-title')]", $node)->item(0);
        
        $views = 0;
        $views_node = $xpath->query(".//div[contains(@class, 'rating-home')]//div", $node)->item(0);
        if ($views_node) {
            $views_text = trim($views_node->textContent);
            if (preg_match('/(\d+[\d\s]*[KkМ]?)/', $views_text, $vm)) {
                $v = str_replace([' ', 'K', 'k', 'М', 'м'], ['', '000', '000', '000000', '000000'], $vm[1]);
                $views = (int)$v;
            }
        }
        
        $books[] = [
            'slug'  => $m[1],
            'title' => $title_node ? trim($title_node->textContent) : '',
            'url'   => $book_url,
            'views' => $views,
        ];
    }
    return $books;
}

// ============================================================
// 5. СОХРАНЕНИЕ В ОЧЕРЕДЬ
// ============================================================
function abs_ifreedom_v2_queue_book($book_data) {
    global $wpdb;
    $table = $wpdb->prefix . 'abs_ifreedom_v2_queue';
    
    $existing = get_posts([
        'post_type'      => 'ranobe',
        'meta_key'       => '_ifreedom_slug',
        'meta_value'     => $book_data['slug'],
        'posts_per_page' => 1,
        'post_status'    => 'any',
    ]);
    
    if (!empty($existing)) {
        $post_id = $existing[0]->ID;
        $existing_chapters = get_posts(['post_type'=>'chapter','post_parent'=>$post_id,'posts_per_page'=>-1,'fields'=>'ids']);
        $existing_count = count($existing_chapters);
        
        $wpdb->replace($table, [
            'slug' => $book_data['slug'], 'title' => $book_data['title'], 'url' => $book_data['url'],
            'chapters_count' => $book_data['chapters_count'] ?? 0, 'views' => $book_data['views'] ?? 0,
            'status' => ($book_data['chapters_count'] <= $existing_count) ? 'done' : 'has_updates',
            'parsed_chapters' => $existing_count,
        ]);
        return ['status' => 'queued', 'reason' => 'updated'];
    }
    
    $wpdb->replace($table, [
        'slug' => $book_data['slug'], 'title' => $book_data['title'], 'url' => $book_data['url'],
        'views' => $book_data['views'] ?? 0, 'status' => 'new',
    ]);
    return ['status' => 'queued', 'reason' => 'new'];
}

// ============================================================
// 6. ПАРСИНГ СТРАНИЦЫ КНИГИ
// ============================================================
function abs_ifreedom_v2_parse_book_page($slug) {
    $url = "https://ifreedom.su/ranobe/{$slug}/";
    $html = abs_ifreedom_v2_get_html($url);
    if (is_array($html) && isset($html['error'])) return $html;
    
    $dom = new DOMDocument();
    @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    $xpath = new DOMXPath($dom);
    
    $data = ['slug' => $slug, 'url' => $url];
    
    // Название
    $title_node = $xpath->query("//h1")->item(0);
    $data['title'] = $title_node ? trim($title_node->textContent) : '';
    
    // Обложка
    $cover_node = $xpath->query("//div[contains(@class, 'book-img')]//img")->item(0);
    $data['cover_url'] = $cover_node ? $cover_node->getAttribute('src') : '';
    
    // Авторы (массивом)
    $data['authors'] = [];
    $author_nodes = $xpath->query("//div[contains(@class, 'book-info-list')]//a[contains(@href, 'authorid=')]");
    foreach ($author_nodes as $a) {
        $name = trim($a->textContent);
        if ($name && $name !== 'Ifreedom' && $name !== 'ifreedom') $data['authors'][] = $name;
    }
    
    // Жанры
    $data['genres'] = [];
    $genre_nodes = $xpath->query("//div[contains(@class, 'genreslist')]//a");
    foreach ($genre_nodes as $g) {
        $genre = trim($g->textContent);
        if ($genre) $data['genres'][] = $genre;
    }
    
    // Язык
    $data['language'] = '';
    $lang_nodes = $xpath->query("//div[contains(@class, 'book-info-list')]//a[contains(@href, 'lang[]=')]");
    if ($lang_nodes->length > 0) $data['language'] = trim($lang_nodes->item(0)->textContent);
    
    // Статус
    $data['status'] = 'ongoing';
    $info_lists = $xpath->query("//div[contains(@class, 'book-info-list')]");
    foreach ($info_lists as $list) {
        $text = trim($list->textContent);
        if (stripos($text, 'завершен') !== false) { $data['status'] = 'completed'; break; }
        if (stripos($text, 'заморожен') !== false || stripos($text, 'приостановлен') !== false) { $data['status'] = 'frozen'; break; }
    }
    
    // Описание
    $data['description'] = '';
    $desc_node = $xpath->query("//div[contains(@class, 'tab-content')]//div[@data-name='Описание']")->item(0);
    if ($desc_node) $data['description'] = trim($desc_node->textContent);
    
    // Главы
    $data['chapters'] = [];
    $chapters_total = 0;
    $chapter_nodes = $xpath->query("//div[contains(@class, 'chapterlinks')]//div[contains(@class, 'chapterinfo')]");
    foreach ($chapter_nodes as $ch) {
        $chapters_total++;
        $link = $xpath->query(".//a", $ch)->item(0);
        if (!$link) continue;
        
        $vip_node = $xpath->query(".//span[contains(@class, 'chapico')]", $ch)->item(0);
        if ($vip_node && stripos(trim($vip_node->textContent), 'VIP') !== false) continue;
        
        $chapter_url = $link->getAttribute('href');
        if (strpos($chapter_url, 'http') !== 0) $chapter_url = 'https://ifreedom.su' . $chapter_url;
        
        $data['chapters'][] = ['title' => trim($link->textContent), 'url' => $chapter_url];
    }
    
    $data['chapters'] = array_reverse($data['chapters']);
    $data['chapters_free_count'] = count($data['chapters']);
    $data['chapters_total_count'] = $chapters_total;
    
    return $data;
}

// ============================================================
// 7. ПАРСИНГ ГЛАВЫ (С HTML-ФОРМАТИРОВАНИЕМ)
// ============================================================
function abs_ifreedom_v2_parse_chapter($chapter_url) {
    $html = abs_ifreedom_v2_get_html($chapter_url);
    if (is_array($html) && isset($html['error'])) return $html;
    
    $dom = new DOMDocument();
    @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    $xpath = new DOMXPath($dom);
    
    $title_node = $xpath->query("//h1")->item(0);
    $title = $title_node ? trim($title_node->textContent) : '';
    
    // Сохраняем HTML-теги
    $content_parts = [];
    $paragraphs = $xpath->query("//div[contains(@class, 'chapter-content')]//p");
    foreach ($paragraphs as $p) {
        $html_content = trim($dom->saveHTML($p));
        if ($html_content && !preg_match('/^<p>[.\s\-—]*<\/p>$/', $html_content)) {
            $content_parts[] = $html_content;
        }
    }
    
    $content = implode("\n", $content_parts);
    // Замена ссылок
    $content = str_replace('ifreedom.su', '1001ranobe.ru', $content);
    $content = preg_replace('#href="/([^"]*)"#', 'href="https://1001ranobe.ru/$1"', $content);
    
    $volume = 0;
    if (preg_match('/[Тт]ом\s*(\d+)/', $title, $vm)) $volume = (int)$vm[1];
    
    return ['title' => $title, 'content' => $content, 'volume' => $volume];
}

// ============================================================
// 8. СОХРАНЕНИЕ КНИГИ
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
    
    if (!empty($book_data['genres'])) {
        $cat_ids = [];
        foreach ($book_data['genres'] as $genre) {
            $term = term_exists($genre, 'category');
            if (!$term) $term = wp_insert_term($genre, 'category');
            if (!is_wp_error($term)) $cat_ids[] = is_array($term) ? $term['term_id'] : $term;
        }
        if (!empty($cat_ids)) wp_set_post_terms($post_id, $cat_ids, 'category');
    }
    
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
// 9. СОХРАНЕНИЕ ГЛАВЫ
// ============================================================
function abs_ifreedom_v2_save_chapter($post_parent, $chapter_num, $chapter_title, $chapter_content, $volume = 0) {
    $existing = get_posts([
        'post_type'      => 'chapter',
        'post_parent'    => $post_parent,
        'meta_key'       => '_chapter_number',
        'meta_value'     => $chapter_num,
        'posts_per_page' => 1,
        'post_status'    => 'any',
    ]);
    
    if (!empty($existing)) return $existing[0]->ID;
    
    $chapter_id = wp_insert_post([
        'post_type'    => 'chapter',
        'post_title'   => $chapter_title,
        'post_content' => $chapter_content,
        'post_parent'  => $post_parent,
        'post_status'  => 'publish',
    ]);
    
    if ($chapter_id && !is_wp_error($chapter_id)) {
        update_post_meta($chapter_id, '_chapter_number', $chapter_num);
        if ($volume) update_post_meta($chapter_id, '_chapter_volume', $volume);
    }
    
    return $chapter_id;
}

// ============================================================
// 10. ЕДИНАЯ ФУНКЦИЯ ОБРАБОТКИ (AJAX + CRON)
// ============================================================
function abs_ifreedom_v2_process_book($slug) {
    set_time_limit(300); // 5 минут на книгу
    global $wpdb;
    $settings = abs_ifreedom_v2_get_settings();
    $batch_size = $settings['manual_batch_size'];
    $table = $wpdb->prefix . 'abs_ifreedom_v2_queue';
    
    // Логируем старт
    $book_info = $wpdb->get_row($wpdb->prepare("SELECT title FROM $table WHERE slug = %s", $slug));
    $book_title = $book_info ? $book_info->title : $slug;
    abs_ifreedom_v2_log("Start: {$book_title}");
    
    $book_data = abs_ifreedom_v2_parse_book_page($slug);
    if (isset($book_data['error'])) {
        $wpdb->update($table, ['status' => 'error', 'error_msg' => $book_data['error']], ['slug' => $slug]);
        abs_telegram_log("❌ V2: {$book_title} — {$book_data['error']}");
        abs_ifreedom_v2_log("Error: {$book_title} — {$book_data['error']}");
        return ['status' => 'error', 'message' => $book_data['error']];
    }
    
    $total = count($book_data['chapters']);
    $wpdb->update($table, [
        'chapters_count' => $total,
        'total_chapters' => $book_data['chapters_total_count'],
        'views' => $book_data['views'] ?? 0,
        'status' => 'parsing'
    ], ['slug' => $slug]);
    
    $save = abs_ifreedom_v2_save_book($book_data);
    if ($save['status'] === 'error') {
        $wpdb->update($table, ['status' => 'error', 'error_msg' => $save['message']], ['slug' => $slug]);
        abs_ifreedom_v2_log("Error: {$book_title} — {$save['message']}");
        return $save;
    }
    
    $post_id = $save['post_id'];
    $loaded = 0;
    $errors = 0;
    $vip_skipped = 0;
    
    foreach ($book_data['chapters'] as $i => $ch) {
        $num = $i + 1;
        
        // Проверяем существующую главу
        $exists = get_posts([
            'post_type' => 'chapter', 'post_parent' => $post_id,
            'meta_key' => '_chapter_number', 'meta_value' => $num,
            'posts_per_page' => 1, 'fields' => 'ids'
        ]);
        if (!empty($exists)) { 
            $loaded++; 
            continue; 
        }
        
        // Пауза каждые 5 запросов
        if ($i > 0 && $i % 5 == 0) {
            abs_ifreedom_v2_log("Progress: {$book_title} — {$loaded}/{$total}");
            sleep(1);
        }
        
        $cd = abs_ifreedom_v2_parse_chapter($ch['url']);
        
        // Пропускаем платные/недоступные главы
        if (isset($cd['error'])) {
    $errors++;
    abs_ifreedom_v2_log("Error ch.{$num}: {$cd['error']}");
    if ($errors > 20) {
        abs_ifreedom_v2_log("Too many errors, stopping: {$book_title}");
        break;
    }
    continue;
}
        
        if (empty($cd['content'])) {
            $vip_skipped++;
            abs_ifreedom_v2_log("Empty chapter {$num}, likely VIP");
            continue; // Пропускаем платную главу
        }
        
        abs_ifreedom_v2_save_chapter($post_id, $num, $cd['title'], $cd['content'], $cd['volume']);
        $loaded++;
        $batch_size = abs_ifreedom_v2_get_settings()['manual_batch_size'];
if ($loaded > 0 && $loaded % $batch_size == 0) {
    abs_ifreedom_v2_log("Progress: {$book_title} — {$loaded}/{$total}");
}
        $errors = 0; // Сбрасываем счётчик ошибок при успехе
    }
    
    // Финальный статус
    $final_status = ($loaded >= $total) ? 'done' : (($loaded > 0) ? 'new' : 'error');
    $wpdb->update($table, [
        'parsed_chapters' => $loaded,
        'status' => $final_status,
        'last_parsed_at' => current_time('mysql'),
        'error_msg' => ($vip_skipped > 0) ? "Пропущено VIP: {$vip_skipped}" : null,
    ], ['slug' => $slug]);
    
    $msg = "✅ V2: {$book_title} — {$loaded}/{$total} глав";
    if ($vip_skipped > 0) $msg .= " (VIP: {$vip_skipped})";
    abs_telegram_log($msg);
    abs_ifreedom_v2_log("Done: {$book_title} — {$loaded}/{$total}");
    
    return ['status' => 'ok', 'loaded' => $loaded, 'total' => $total, 'vip_skipped' => $vip_skipped];
}

// ============================================================
// 11. ЛОГИРОВАНИЕ
// ============================================================
function abs_ifreedom_v2_log($msg) {
    $log_file = dirname(__FILE__) . '/parser-v2.log';
    file_put_contents($log_file, date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);
}