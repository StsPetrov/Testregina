<?php
/**
 * abs-parser-ifreedom.php — Ядро парсера ifreedom.su
 */

if (!defined('ABSPATH')) exit;

// ============================================================
// 1. ТАБЛИЦА ОЧЕРЕДИ ПАРСИНГА
// ============================================================
function abs_parser_ifreedom_create_queue_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'abs_parser_ifreedom_queue';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(500) NOT NULL,
        title VARCHAR(1000) DEFAULT '',
        url VARCHAR(500) DEFAULT '',
        chapters_count INT DEFAULT 0,
        total_chapters INT DEFAULT 0,
        status ENUM('new','parsing','done','error') DEFAULT 'new',
        parsed_chapters INT DEFAULT 0,
        last_parsed_at DATETIME NULL,
        error_msg TEXT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY slug (slug)
    ) $charset";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
add_action('after_switch_theme', 'abs_parser_ifreedom_create_queue_table');

// ============================================================
// 2. НАСТРОЙКИ ПАРСЕРА
// ============================================================
function abs_parser_ifreedom_get_settings() {
    $defaults = [
        'min_delay_ms'      => 1000000,
        'max_delay_ms'      => 3000000,
        'max_per_minute'    => 20,
        'cron_batch_size'   => 15,
        'manual_batch_size' => 30,
        'http_timeout'      => 30,
        'fb2_timeout'       => 300,
    ];
    $saved = get_option('abs_parser_ifreedom_settings', []);
    return wp_parse_args($saved, $defaults);
}

function abs_parser_ifreedom_get_user_agents() {
    return [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:125.0) Gecko/20100101 Firefox/125.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_4_1) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4.1 Safari/605.1.1',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36 Edg/123.0.0.0',
    ];
}

// ============================================================
// 3. HTTP-ЗАПРОСЫ
// ============================================================
function abs_parser_ifreedom_rate_limit() {
    static $requests_this_minute = 0;
    static $minute_start = 0;

    $now = time();
    if ($now - $minute_start >= 60) {
        $requests_this_minute = 0;
        $minute_start = $now;
    }

    $requests_this_minute++;
    $settings = abs_parser_ifreedom_get_settings();
    $max_per_minute = (int) $settings['max_per_minute'];

    if ($requests_this_minute > $max_per_minute) {
        $wait = 60 - ($now - $minute_start) + 1;
        sleep($wait);
        $requests_this_minute = 0;
        $minute_start = time();
    }

    $min_delay = (int) $settings['min_delay_ms'];
    $max_delay = (int) $settings['max_delay_ms'];
    if ($min_delay > 0 && $max_delay >= $min_delay) {
        $delay = rand($min_delay, $max_delay);
        usleep($delay);
    }
}

function abs_parser_ifreedom_get_html($url, $attempt = 0) {
    abs_parser_ifreedom_rate_limit();

    $user_agents = abs_parser_ifreedom_get_user_agents();
    $ua = $user_agents[array_rand($user_agents)];
    $settings = abs_parser_ifreedom_get_settings();

    $args = [
        'timeout'    => (int) $settings['http_timeout'],
        'user-agent' => $ua,
        'headers'    => [
            'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'ru-RU,ru;q=0.9,en;q=0.8',
            'Referer'         => 'https://ifreedom.su/',
        ],
    ];

    $args = apply_filters('abs_parser_ifreedom_curl_args', $args, $url);
    $response = wp_remote_get($url, $args);

    if (is_wp_error($response)) {
        if ($attempt < 3) {
            sleep(5 * ($attempt + 1));
            return abs_parser_ifreedom_get_html($url, $attempt + 1);
        }
        return ['error' => $response->get_error_message()];
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) {
        if (($code === 429 || $code === 403) && $attempt < 3) {
            sleep(10 * ($attempt + 1));
            return abs_parser_ifreedom_get_html($url, $attempt + 1);
        }
        return ['error' => "HTTP $code"];
    }

    return wp_remote_retrieve_body($response);
}

// ============================================================
// 4. ПАРСИНГ КАТАЛОГА
// ============================================================
function abs_parser_ifreedom_get_last_catalog_page() {
    $html = abs_parser_ifreedom_get_html('https://ifreedom.su/vse-knigi/');
    if (is_array($html) && isset($html['error'])) return 1;

    $dom = new DOMDocument();
    @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    $xpath = new DOMXPath($dom);

    $max_page = 1;
    $links = $xpath->query("//div[contains(@class, 'numpagenav')]//a");
    foreach ($links as $link) {
        $href = $link->getAttribute('href');
        if (preg_match('/bpage=(\d+)/', $href, $m)) {
            $max_page = max($max_page, (int) $m[1]);
        }
    }
    return $max_page;
}

function abs_parser_ifreedom_scan_catalog_page($page = 1, $filters = []) {
    $url = 'https://ifreedom.su/vse-knigi/';
    if ($page > 1) {
        $url .= '?bpage=' . $page;
    }
    
    if (!empty($filters)) {
        $sep = ($page > 1) ? '&' : '?';
        if (!empty($filters['genre'])) {
            foreach ((array)$filters['genre'] as $g) {
                $url .= $sep . 'genre[]=' . urlencode($g);
                $sep = '&';
            }
        }
        if (!empty($filters['status'])) {
            foreach ((array)$filters['status'] as $s) {
                $url .= $sep . 'status[]=' . urlencode($s);
                $sep = '&';
            }
        }
        if (!empty($filters['lang'])) {
            foreach ((array)$filters['lang'] as $l) {
                $url .= $sep . 'lang[]=' . urlencode($l);
                $sep = '&';
            }
        }
        if (!empty($filters['sortfolder'])) {
            $url .= $sep . 'sortfolder=' . urlencode($filters['sortfolder']);
            $sep = '&';
        }
        if (!empty($filters['iibook'])) {
            $url .= $sep . 'iibook=' . urlencode($filters['iibook']);
            $sep = '&';
        }
    }

    $html = abs_parser_ifreedom_get_html($url);
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
        
        $slug = $m[1];
        $title_node = $xpath->query(".//div[contains(@class, 'block-book-slide-title')]", $node)->item(0);
        $title = $title_node ? trim($title_node->textContent) : '';

        $views_node = $xpath->query(".//div[contains(@class, 'rating-home')]//div", $node)->item(0);
        $views = 0;
        if ($views_node) {
            $views_text = trim($views_node->textContent);
            if (preg_match('/(\d+[\d\s]*[KkМ]?)/', $views_text, $vm)) {
                $v = str_replace([' ', 'K', 'k', 'М', 'м'], ['', '000', '000', '000000', '000000'], $vm[1]);
                $views = (int)$v;
            }
        }

        $books[] = [
            'slug'  => $slug,
            'title' => $title,
            'url'   => $book_url,
            'views' => $views,
        ];
    }

    return $books;
}

// ============================================================
// 5. СОХРАНЕНИЕ В ОЧЕРЕДЬ
// ============================================================
function abs_parser_ifreedom_queue_book($book_data) {
    global $wpdb;
    $table = $wpdb->prefix . 'abs_parser_ifreedom_queue';

    $original_url = $book_data['url'];
    $slug = $book_data['slug'];
    
    $existing_posts = get_posts([
        'post_type'      => 'ranobe',
        'meta_key'       => '_ifreedom_slug',
        'meta_value'     => $slug,
        'posts_per_page' => 1,
        'post_status'    => 'any',
    ]);
    
    if (!empty($existing_posts)) {
        $post_id = $existing_posts[0]->ID;
        $existing_chapters = get_posts([
            'post_type' => 'chapter', 'post_parent' => $post_id,
            'posts_per_page' => -1, 'fields' => 'ids',
        ]);
        $existing_count = count($existing_chapters);
        if ($book_data['chapters_count'] <= $existing_count) {
            $wpdb->replace($table, [
                'slug' => $slug, 'title' => $book_data['title'], 'url' => $original_url,
                'chapters_count' => $book_data['chapters_count'], 'status' => 'done',
                'parsed_chapters' => $existing_count,
            ]);
            return ['status' => 'skip', 'reason' => 'no_new_chapters'];
        }
        $wpdb->replace($table, [
            'slug' => $slug, 'title' => $book_data['title'], 'url' => $original_url,
            'chapters_count' => $book_data['chapters_count'], 'status' => 'has_updates',
            'parsed_chapters' => $existing_count,
        ]);
        return ['status' => 'queued', 'reason' => 'new_chapters_available'];
    }
    
    $wpdb->replace($table, [
        'slug'           => $book_data['slug'],
        'title'          => $book_data['title'],
        'url'            => $original_url,
        'chapters_count' => 0,
        'total_chapters' => 0,
        'views'          => $book_data['views'] ?? 0,
        'status'         => 'new',
    ]);

    return ['status' => 'queued', 'reason' => 'new_book'];
}

// ============================================================
// 6. ПАРСИНГ СТРАНИЦЫ КНИГИ (БЕЗ ПЕРЕВОРОТА ГЛАВ)
// ============================================================
function abs_parser_ifreedom_parse_book_page($slug) {
    $url = "https://ifreedom.su/ranobe/{$slug}/";
    $html = abs_parser_ifreedom_get_html($url);

    if (is_array($html) && isset($html['error'])) return $html;

    $dom = new DOMDocument();
    @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    $xpath = new DOMXPath($dom);

    $data = [];
    $data['slug'] = $slug;
    $data['url'] = $url;

    // Название
    $title_node = $xpath->query("//h1")->item(0);
    $data['title'] = $title_node ? trim($title_node->textContent) : '';

    // Обложка
    $cover_node = $xpath->query("//div[contains(@class, 'book-img')]//img")->item(0);
    $data['cover_url'] = '';
    if ($cover_node) {
        $data['cover_url'] = $cover_node->getAttribute('src');
    }

    // Автор
    $data['author'] = '';
    $author_nodes = $xpath->query("//div[contains(@class, 'book-info-list')]//a[contains(@href, 'authorid=')]");
    if ($author_nodes->length > 0) {
        $author_name = trim($author_nodes->item(0)->textContent);
        $data['author'] = ($author_name === 'Ifreedom' || $author_name === 'ifreedom') ? '' : $author_name;
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
    if ($lang_nodes->length > 0) {
        $data['language'] = trim($lang_nodes->item(0)->textContent);
    }

    // Статус
    $data['status'] = 'ongoing';
    $info_lists = $xpath->query("//div[contains(@class, 'book-info-list')]");
    foreach ($info_lists as $list) {
        $text = trim($list->textContent);
        if (stripos($text, 'завершен') !== false) {
            $data['status'] = 'completed';
            break;
        } elseif (stripos($text, 'заморожен') !== false || stripos($text, 'приостановлен') !== false) {
            $data['status'] = 'frozen';
            break;
        } elseif (stripos($text, 'активен') !== false || stripos($text, 'продолжается') !== false) {
            $data['status'] = 'ongoing';
            break;
        }
    }

    // Описание
    $data['description'] = '';
    $desc_node = $xpath->query("//div[contains(@class, 'tab-content')]//div[@data-name='Описание']")->item(0);
    if ($desc_node) {
        $data['description'] = trim($desc_node->textContent);
    }

    // Список глав (в порядке как на источнике, без переворота)
    $data['chapters'] = [];
    $chapters_free_count = 0;
    $chapters_total_count = 0;
    
    $chapter_nodes = $xpath->query("//div[contains(@class, 'chapterlinks')]//div[contains(@class, 'chapterinfo')]");
    foreach ($chapter_nodes as $ch) {
        $chapters_total_count++;
        $link = $xpath->query(".//a", $ch)->item(0);
        if (!$link) continue;

        $chapter_url = $link->getAttribute('href');
        $chapter_title = trim($link->textContent);
        $data_id = $link->getAttribute('data-id');

        // Проверяем VIP
        $vip_node = $xpath->query(".//span[contains(@class, 'chapico')]", $ch)->item(0);
        $is_vip = $vip_node && stripos(trim($vip_node->textContent), 'VIP') !== false;

        if (!$is_vip) {
            $chapters_free_count++;
            $data['chapters'][] = [
                'number'   => $chapters_free_count,
                'title'    => $chapter_title,
                'url'      => (strpos($chapter_url, 'http') === 0) ? $chapter_url : 'https://ifreedom.su' . $chapter_url,
                'data_id'  => $data_id,
                'is_free'  => true,
            ];
        }
    }
    $data['chapters_free_count'] = $chapters_free_count;
    $data['chapters_total_count'] = $chapters_total_count;
    return $data;
}

// ============================================================
// 7. ПАРСИНГ СТРАНИЦЫ ГЛАВЫ
// ============================================================
function abs_parser_ifreedom_parse_chapter_page($chapter_url) {
    $html = abs_parser_ifreedom_get_html($chapter_url);

    if (is_array($html) && isset($html['error'])) return $html;

    $dom = new DOMDocument();
    @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    $xpath = new DOMXPath($dom);

    $data = [];

    // Заголовок главы
    $title_node = $xpath->query("//h1")->item(0);
    $data['chapter_title'] = $title_node ? trim($title_node->textContent) : '';

    // Текст главы
    $content_parts = [];
    $paragraphs = $xpath->query("//div[contains(@class, 'chapter-content')]//p");
    foreach ($paragraphs as $p) {
        $text = trim($p->textContent);
        if ($text && $text !== '.' && !preg_match('/^[.\s\-—]+$/', $text)) {
            $content_parts[] = $text;
        }
    }

    $data['content'] = implode("\n\n", $content_parts);

    // Номер главы из URL
    $data['volume'] = 0;
    if (preg_match('/glava-(\d+)/', $chapter_url, $m)) {
        $data['chapter_number'] = (int)$m[1];
    }

    // Том из заголовка
    if (preg_match('/[Тт]ом\s*(\d+)/', $data['chapter_title'], $vm)) {
        $data['volume'] = (int)$vm[1];
    }

    return $data;
}

// ============================================================
// 8. СОХРАНЕНИЕ КНИГИ В RANOBE ПОСТ
// ============================================================
function abs_parser_ifreedom_save_ranobe_post($book_data) {
    $original_url = $book_data['url'];
    $existing = get_posts([
        'post_type'      => 'ranobe',
        'meta_key'       => '_ifreedom_slug',
        'meta_value'     => $book_data['slug'],
        'posts_per_page' => 1,
        'post_status'    => 'any',
    ]);

    $post_id = null;

    if (!empty($existing)) {
        $post_id = $existing[0]->ID;
        wp_update_post([
            'ID'           => $post_id,
            'post_title'   => $book_data['title'],
            'post_content' => $book_data['description'] ?? '',
        ]);
    } else {
        $post_id = wp_insert_post([
            'post_type'    => 'ranobe',
            'post_title'   => mb_substr($book_data['title'], 0, 200),
            'post_content' => $book_data['description'] ?? '',
            'post_status'  => 'publish',
        ]);
    }

    if (!$post_id || is_wp_error($post_id)) {
        return ['status' => 'error', 'message' => 'Не удалось создать пост'];
    }

    update_post_meta($post_id, '_ranobe_author', $book_data['author'] ?? '');
    update_post_meta($post_id, '_ranobe_original_url', $original_url);
    update_post_meta($post_id, '_ifreedom_slug', $book_data['slug']);
    update_post_meta($post_id, '_ranobe_status', $book_data['status'] ?? 'ongoing');
    update_post_meta($post_id, '_ranobe_language', $book_data['language'] ?? '');
    update_post_meta($post_id, '_ranobe_source', 'ifreedom');

    if (!empty($book_data['genres'])) {
        $cat_ids = [];
        foreach ($book_data['genres'] as $genre) {
            $term = term_exists($genre, 'category');
            if (!$term) {
                $term = wp_insert_term($genre, 'category');
            }
            if (!is_wp_error($term)) {
                $cat_ids[] = is_array($term) ? $term['term_id'] : $term;
            }
        }
        if (!empty($cat_ids)) {
            wp_set_post_terms($post_id, $cat_ids, 'category');
        }
    }

    if (!empty($book_data['cover_url']) && !has_post_thumbnail($post_id)) {
        abs_parser_ifreedom_download_cover($post_id, $book_data['cover_url']);
    }

    return ['status' => 'ok', 'post_id' => $post_id];
}

// ============================================================
// 9. СОХРАНЕНИЕ ГЛАВЫ
// ============================================================
function abs_parser_ifreedom_save_chapter($post_parent, $chapter_data) {
    $existing = get_posts([
        'post_type'      => 'chapter',
        'post_parent'    => $post_parent,
        'meta_key'       => '_chapter_number',
        'meta_value'     => $chapter_data['number'],
        'posts_per_page' => 1,
        'post_status'    => 'any',
    ]);

    if (!empty($existing)) {
        $chapter_id = $existing[0]->ID;
        wp_update_post([
            'ID'           => $chapter_id,
            'post_title'   => $chapter_data['title'],
            'post_content' => $chapter_data['content'],
        ]);
    } else {
        $chapter_id = wp_insert_post([
            'post_type'    => 'chapter',
            'post_title'   => $chapter_data['title'],
            'post_content' => $chapter_data['content'],
            'post_parent'  => $post_parent,
            'post_status'  => 'publish',
        ]);
    }

    if ($chapter_id && !is_wp_error($chapter_id)) {
        update_post_meta($chapter_id, '_chapter_number', $chapter_data['number']);
        update_post_meta($chapter_id, '_chapter_volume', $chapter_data['volume'] ?? 0);
    }

    return $chapter_id;
}

// ============================================================
// 10. ЗАГРУЗКА ОБЛОЖКИ
// ============================================================
function abs_parser_ifreedom_download_cover($post_id, $cover_url) {
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $tmp = download_url($cover_url);
    if (is_wp_error($tmp)) return;

    $file_array = [
        'name'     => 'cover-' . $post_id . '.jpg',
        'tmp_name' => $tmp,
    ];

    $attachment_id = media_handle_sideload($file_array, $post_id);
    if (!is_wp_error($attachment_id)) {
        set_post_thumbnail($post_id, $attachment_id);
    }

    @unlink($tmp);
}

// ============================================================
// 11. ЛОГИРОВАНИЕ
// ============================================================


function abs_parser_ifreedom_log($msg) {
    $log_file = get_template_directory() . '/parser-ifreedom.log';
    file_put_contents($log_file, date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);
}