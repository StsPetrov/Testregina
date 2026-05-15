<?php
/**
 * Парсер Ранобэ (FB2) — ядро
 * Источник: ranobe.me
 * Метод загрузки глав: только FB2
 */

if (!defined('ABSPATH')) exit;

// ============================================================
// 1. HTTP-ЗАПРОСЫ С ЗАЩИТОЙ ОТ БАНА
// ============================================================

function abs_fb2_get_user_agents() {
    return [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:125.0) Gecko/20100101 Firefox/125.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_4_1) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4.1 Safari/605.1.15',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36 Edg/123.0.0.0',
    ];
}

function abs_fb2_rate_limit() {
    static $requests_this_minute = 0;
    static $minute_start = 0;

    $now = time();
    if ($now - $minute_start >= 60) {
        $requests_this_minute = 0;
        $minute_start = $now;
    }

    $requests_this_minute++;
    if ($requests_this_minute > 20) {
        $wait = 60 - ($now - $minute_start) + 1;
        if ($wait > 0) sleep($wait);
        $requests_this_minute = 0;
        $minute_start = time();
    }

    usleep(rand(500000, 1000000));
}

function abs_fb2_get_html($url, $attempt = 0) {
    abs_fb2_rate_limit();

    $user_agents = abs_fb2_get_user_agents();
    $ua = $user_agents[array_rand($user_agents)];

    $args = [
        'timeout'     => 30,
        'user-agent'  => $ua,
        'headers'     => [
            'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'ru-RU,ru;q=0.9,en;q=0.8',
            'Referer'         => 'https://ranobe.me/',
        ],
    ];

    $response = wp_remote_get($url, $args);

    if (is_wp_error($response)) {
        if ($attempt < 3) {
            sleep(5 * ($attempt + 1));
            return abs_fb2_get_html($url, $attempt + 1);
        }
        return ['error' => $response->get_error_message()];
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) {
        if (($code === 429 || $code === 403) && $attempt < 3) {
            sleep(10 * ($attempt + 1));
            return abs_fb2_get_html($url, $attempt + 1);
        }
        return ['error' => "HTTP $code"];
    }

    return wp_remote_retrieve_body($response);
}

// ============================================================
// 2. СКАНИРОВАНИЕ КАТАЛОГА
// ============================================================

function abs_fb2_get_last_catalog_page() {
    $html = abs_fb2_get_html('https://ranobe.me/catalog');
    if (is_array($html) && isset($html['error'])) return 1;

    $dom = new DOMDocument();
    $dom->loadXML($fb2_content);
    $xpath = new DOMXPath($dom);

    $max_page = 1;
    foreach ($xpath->query("//div[contains(@class, 'paginator')]//a") as $link) {
        if (preg_match('/page=(\d+)/', $link->getAttribute('href'), $m)) {
            $max_page = max($max_page, (int)$m[1]);
        }
    }
    return $max_page;
}

function abs_fb2_scan_catalog_page($page = 1) {
    $url = 'https://ranobe.me/catalog' . ($page > 1 ? '?page=' . $page : '');
    $html = abs_fb2_get_html($url);
    if (is_array($html) && isset($html['error'])) return $html;

    $dom = new DOMDocument();
    @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    $xpath = new DOMXPath($dom);

    $books = [];
    foreach ($xpath->query("//div[contains(@class, 'FicTable')]") as $node) {
        $ranobe_id = (int) str_replace('fic_', '', $node->getAttribute('id'));
        if (!$ranobe_id) continue;

        $title_node = $xpath->query(".//div[contains(@class, 'FicTable_Title')]/a", $node)->item(0);
        $chapters_node = $xpath->query(".//span[contains(@class, 'ChaptersCount')]", $node)->item(0);

        $books[] = [
            'ranobe_id'      => $ranobe_id,
            'title'          => $title_node ? trim($title_node->textContent) : '',
            'url'            => $title_node ? 'https://ranobe.me' . $title_node->getAttribute('href') : '',
            'chapters_count' => $chapters_node ? (int) trim($chapters_node->textContent) : 0,
        ];
    }
    return $books;
}

// ============================================================
// 3. ОЧЕРЕДЬ
// ============================================================

function abs_fb2_queue_book($book_data) {
    global $wpdb;
    $table = $wpdb->prefix . 'abs_parser_queue_fb2';

    $ranobe_id    = $book_data['ranobe_id'];
    $original_url = $book_data['url'];

    // Проверяем существующие посты
    $existing_posts = get_posts([
        'post_type'      => 'ranobe',
        'meta_key'       => '_ranobe_ranobe_id',
        'meta_value'     => $ranobe_id,
        'posts_per_page' => 1,
        'post_status'    => 'any',
    ]);

    if (!empty($existing_posts)) {
        $post_id = $existing_posts[0]->ID;
        $existing_chapters = get_posts([
            'post_type'      => 'chapter',
            'post_parent'    => $post_id,
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]);
        $existing_count = count($existing_chapters);

        if ($book_data['chapters_count'] <= $existing_count) {
            $wpdb->replace($table, [
    'ranobe_id'       => $ranobe_id,
    'title'           => $book_data['title'],
    'url'             => $original_url,
    'chapters_count'  => $book_data['chapters_count'],
    'status'          => 'done',
    'parsed_chapters' => $existing_count,
    'source'          => 'ranobe.me_fb2',
]);
            return ['status' => 'skip', 'reason' => 'no_new_chapters'];
        }

        $wpdb->replace($table, [
    'ranobe_id'       => $ranobe_id,
    'title'           => $book_data['title'],
    'url'             => $original_url,
    'chapters_count'  => $book_data['chapters_count'],
    'status'          => 'has_updates',
    'parsed_chapters' => $existing_count,
    'source'          => 'ranobe.me_fb2',
]);
        return ['status' => 'queued', 'reason' => 'new_chapters_available'];
    }

    $wpdb->replace($table, [
    'ranobe_id'      => $ranobe_id,
    'title'          => $book_data['title'],
    'url'            => $original_url,
    'chapters_count' => $book_data['chapters_count'],
    'status'         => 'new',
    'source'         => 'ranobe.me_fb2',
]);
    return ['status' => 'queued', 'reason' => 'new_book'];
}

// ============================================================
// 4. ПАРСИНГ СТРАНИЦЫ КНИГИ
// ============================================================

function abs_fb2_parse_book_page($ranobe_id) {
    $html = abs_fb2_get_html("https://ranobe.me/ranobe{$ranobe_id}");
    if (is_array($html) && isset($html['error'])) return $html;

    $dom = new DOMDocument();
    @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    $xpath = new DOMXPath($dom);

    $data = [
        'ranobe_id'   => $ranobe_id,
        'title'       => '',
        'author'      => '',
        'genres'      => [],
        'language'    => '',
        'year'        => '',
        'description' => '',
        'cover_url'   => '',
        'chapters'    => [],
    ];

    // Название
    $title_node = $xpath->query("//div[contains(@class, 'FicHead')]//h1")->item(0);
    if ($title_node) $data['title'] = trim($title_node->textContent);

    // Обложка
    $cover_node = $xpath->query("//div[contains(@class, 'FicCover')]//img")->item(0);
    if ($cover_node) {
        $src = $cover_node->getAttribute('data-src') ?: $cover_node->getAttribute('src');
        if ($src) $data['cover_url'] = (strpos($src, 'http') === 0) ? $src : 'https://ranobe.me' . $src;
    }

    // Автор, жанры, язык, год
    foreach ($xpath->query("//div[contains(@class, 'FicHeadRight')]//div[contains(@class, 'tr')]") as $row) {
        $title_div   = $xpath->query(".//div[contains(@class, 'title')]", $row)->item(0);
        $content_div = $xpath->query(".//div[contains(@class, 'content')]", $row)->item(0);
        if (!$title_div || !$content_div) continue;

        $label = trim(mb_strtolower(strip_tags($title_div->textContent)));

        if (mb_strpos($label, 'автор') !== false) {
            $authors = [];
            foreach ($xpath->query(".//a", $content_div) as $a) {
                $n = trim($a->textContent);
                if ($n) $authors[] = $n;
            }
            $data['author'] = $authors ? implode(', ', $authors) : trim(strip_tags($content_div->textContent));
        }

        if (mb_strpos($label, 'жанр') !== false) {
            foreach ($xpath->query(".//a", $content_div) as $a) {
                $g = trim($a->textContent);
                if ($g) $data['genres'][] = $g;
            }
        }

        if (mb_strpos($label, 'язык') !== false) {
            $lang_link = $xpath->query(".//a", $content_div)->item(0);
            $data['language'] = $lang_link ? trim($lang_link->textContent) : trim(strip_tags($content_div->textContent));
        }

        if (mb_strpos($label, 'год') !== false) {
            $data['year'] = trim(strip_tags($content_div->textContent));
        }
    }

    // Описание
    $desc_node = $xpath->query("//div[contains(@class, 'summary_text_fic3')]")->item(0);
    if ($desc_node) $data['description'] = trim($desc_node->textContent);

    // Список глав с сайта (только для подсчёта количества)
    foreach ($xpath->query("//ul[contains(@class, 'FicContents')]") as $list) {
        if ($list->getAttribute('OnCLick') || $list->getAttribute('onclick')) continue;
        foreach ($xpath->query(".//li[contains(@class, 't-b-dotted')]", $list) as $ch) {
            $link = $xpath->query(".//div[contains(@class, 'FicContentsChapterName')]//a", $ch)->item(0);
            if ($link && preg_match('#/ranobe\d+/(\d+)#', $link->getAttribute('href'), $m)) {
                $data['chapters'][] = [
                    'number' => (int) $m[1],
                    'title'  => trim($link->textContent),
                ];
            }
        }
    }

    return $data;
}

// ============================================================
// 5. СОХРАНЕНИЕ КНИГИ (пост ranobe)
// ============================================================

function abs_fb2_save_ranobe_post($book_data) {
    $existing = get_posts([
        'post_type'      => 'ranobe',
        'meta_key'       => '_ranobe_ranobe_id',
        'meta_value'     => $book_data['ranobe_id'],
        'posts_per_page' => 1,
        'post_status'    => 'any',
    ]);

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
    update_post_meta($post_id, '_ranobe_original_url', $book_data['url']);
    update_post_meta($post_id, '_ranobe_ranobe_id', $book_data['ranobe_id']);
    update_post_meta($post_id, '_ranobe_status', 'ongoing');
    update_post_meta($post_id, '_ranobe_language', $book_data['language'] ?? '');
    update_post_meta($post_id, '_ranobe_source', 'ranobe.me_fb2');

    // Жанры → категории
    if (!empty($book_data['genres'])) {
        $cat_ids = [];
        foreach ($book_data['genres'] as $genre) {
            $term = term_exists($genre, 'category');
            if (!$term) $term = wp_insert_term($genre, 'category');
            if (!is_wp_error($term)) {
                $cat_ids[] = is_array($term) ? $term['term_id'] : $term;
            }
        }
        if (!empty($cat_ids)) wp_set_post_terms($post_id, $cat_ids, 'category');
    }

    // Обложка
    if (!empty($book_data['cover_url']) && !has_post_thumbnail($post_id)) {
        abs_fb2_download_cover($post_id, $book_data['cover_url']);
    }

    return ['status' => 'ok', 'post_id' => $post_id];
}

// ============================================================
// 6. ЗАГРУЗКА ОБЛОЖКИ
// ============================================================

function abs_fb2_download_cover($post_id, $cover_url) {
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
// 7. FB2: СКАЧИВАНИЕ
// ============================================================

function abs_fb2_download($ranobe_id, $part = 1) {
    $url = "https://ranobe.me/section_fictofile_download.php?id={$ranobe_id}&format=fb2&part={$part}";

    $response = wp_remote_get($url, [
        'timeout'     => 300,
        'user-agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'headers'     => ['Referer' => 'https://ranobe.me/'],
    ]);

    if (is_wp_error($response)) return false;
    if (wp_remote_retrieve_response_code($response) !== 200) return false;

    $body = wp_remote_retrieve_body($response);

    // Если ZIP — извлекаем .fb2
    if (substr($body, 0, 2) === 'PK') {
        return abs_fb2_extract_from_zip($body);
    }

    return $body;
}

function abs_fb2_extract_from_zip($zip_data) {
    $tmp_file = wp_tempnam('ranobe_fb2_zip_');
    file_put_contents($tmp_file, $zip_data);

    $zip = new ZipArchive();
    if ($zip->open($tmp_file) !== true) {
        @unlink($tmp_file);
        return false;
    }

    $fb2_content = false;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (preg_match('/\.fb2$/i', $name)) {
            $fb2_content = $zip->getFromIndex($i);
            break;
        }
    }

    $zip->close();
    @unlink($tmp_file);
    return $fb2_content;
}

// ============================================================
// 8. FB2: ПАРСИНГ
// ============================================================

function abs_fb2_parse($fb2_content, $start_number = 1) {
    // Удаляем ВСЕ xmlns-атрибуты
    $fb2_content = preg_replace('/\s+xmlns(:\w+)?="[^"]*"/', '', $fb2_content);
    // Убираем префиксы неймспейсов из тегов
    $fb2_content = preg_replace('/<(\/?)(\w+):(\w+)([\s>])/', '<$1$3$4', $fb2_content);

    // Используем DOMDocument с либеральным парсингом
    $dom = new DOMDocument();
    $dom->recover = true; // пытается исправить кривой XML
    $dom->strictErrorChecking = false;
    libxml_use_internal_errors(true);
    $dom->loadHTML($fb2_content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    $chapters = [];
    $chapter_num = $start_number;

    $sections = $xpath->query('//body//section');
    if ($sections->length === 0) {
        return ['chapters' => [], 'error' => 'No sections found'];
    }

    foreach ($sections as $section) {
        // Заголовок
        $title = '';
        $titleNodes = $xpath->query('.//title', $section);
        if ($titleNodes->length > 0) {
            $title = trim($dom->saveHTML($titleNodes->item(0)));
        }

        // Параграфы
        $paragraphs = [];
        $pNodes = $xpath->query('.//p', $section);
        foreach ($pNodes as $p) {
            $t = trim($dom->saveHTML($p));
            if ($t) $paragraphs[] = $t;
        }

        if ($paragraphs) {
            $chapters[] = [
                'number'  => $chapter_num,
                'title'   => $title ?: "Глава {$chapter_num}",
                'content' => implode("\n\n", $paragraphs),
                'volume'  => 0,
            ];
            $chapter_num++;
        }
    }

    return ['chapters' => $chapters, 'error' => null];
}

// ============================================================
// 9. FB2: ЗАГРУЗКА ВСЕХ ЧАСТЕЙ И СОХРАНЕНИЕ ГЛАВ
// ============================================================

function abs_fb2_import_all($ranobe_id, $post_id) {
    $all_chapters = [];
    $max_parts    = 10;
    $start_number = 1; // сквозная нумерация

    for ($part = 1; $part <= $max_parts; $part++) {
        $fb2_content = abs_fb2_download($ranobe_id, $part);

        if (!$fb2_content) {
            if ($part === 1) {
                update_post_meta($post_id, '_ranobe_fb2_failed', 1);
                return ['error' => 'FB2 часть 1 не загрузилась'];
            }
            break;
        }

        $result = abs_fb2_parse($fb2_content, $start_number);
// Если часть не дала новых глав или дала меньше 2 глав — конец
if (empty($result['chapters']) || count($result['chapters']) < 2) {
    break;
}

// Проверяем, не дублирует ли эта часть предыдущую
// Сравниваем название первой главы этой части с уже загруженными
if (!empty($all_chapters)) {
    $first_new_title = mb_strtolower(trim($result['chapters'][0]['title']));
    $last_loaded_title = mb_strtolower(trim(end($all_chapters)['title']));
    
    // Если название первой главы совпадает с последней загруженной — это дубль
    if ($first_new_title === $last_loaded_title) {
        abs_fb2_log("FB2: Часть $part — дубль части " . ($part - 1) . ", остановка");
        break;
    }
    
    // Дополнительно: проверяем первые 3 главы на дубли
    $dup_count = 0;
    $check_count = min(3, count($result['chapters']));
    for ($i = 0; $i < $check_count; $i++) {
        $new_title = mb_strtolower(trim($result['chapters'][$i]['title']));
        foreach ($all_chapters as $existing) {
            if (mb_strtolower(trim($existing['title'])) === $new_title) {
                $dup_count++;
                break;
            }
        }
    }
    // Если 2 из 3 первых глав — дубликаты, это та же часть
    if ($dup_count >= 2) {
        abs_fb2_log("FB2: Часть $part — дубликат (совпало $dup_count из $check_count глав), остановка");
        break;
    }
}
        if (!empty($result['error'])) {
            if ($part === 1) {
                update_post_meta($post_id, '_ranobe_fb2_failed', 1);
                return ['error' => 'FB2 часть 1: ' . $result['error']];
            }
            break;
        }

        $all_chapters = array_merge($all_chapters, $result['chapters']);
        $start_number += count($result['chapters']);

        if ($part < $max_parts) {
            sleep(2);
        }
    }

    if (empty($all_chapters)) {
        return ['error' => 'FB2 не содержит глав'];
    }

    $loaded = abs_fb2_save_chapters($post_id, $all_chapters);

    return [
        'success'  => true,
        'loaded'   => $loaded,
        'total'    => count($all_chapters),
        'chapters' => $all_chapters,
    ];
}

// ============================================================
// 10. СОХРАНЕНИЕ ГЛАВ
// ============================================================

function abs_fb2_save_chapter($post_parent, $chapter_data) {
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

function abs_fb2_save_chapters($post_parent, $chapters) {
    $loaded = 0;
    foreach ($chapters as $ch) {
        $result = abs_fb2_save_chapter($post_parent, $ch);
        if ($result && !is_wp_error($result)) {
            $loaded++;
        }
    }
    return $loaded;
}

// ============================================================
// 11. ОБНОВЛЕНИЕ: ПРОВЕРКА НОВЫХ ГЛАВ
// ============================================================

function abs_fb2_check_updates($ranobe_id) {
    // Парсим страницу книги — получаем ТЕКУЩЕЕ количество глав на сайте
    $book_data = abs_fb2_parse_book_page($ranobe_id);
    if (isset($book_data['error'])) return $book_data;

    return [
        'chapters_on_site' => count($book_data['chapters']),
    ];
}

// ============================================================
// 12. ЛОГИРОВАНИЕ
// ============================================================

function abs_fb2_log($msg) {
    $log_file = get_template_directory() . '/parser-fb2-v2.log';
    file_put_contents($log_file, date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);
}