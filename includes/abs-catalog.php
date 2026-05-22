<?php
/**
 * abs-catalog.php - Каталог книг
 */

// Добавляем rewrite rules
add_action('init', 'abs_catalog_rewrite_rules');
function abs_catalog_rewrite_rules() {
    add_rewrite_rule('^catalog/page/([0-9]+)/?$', 'index.php?pagename=catalog&paged=$matches[1]', 'top');
    add_rewrite_rule('^catalog/?$', 'index.php?pagename=catalog', 'top');
    add_rewrite_rule('^author/([^/]+)/page/([0-9]+)/?$', 'index.php?author_name=$matches[1]&paged=$matches[2]', 'top');
    add_rewrite_rule('^author/([^/]+)/?$', 'index.php?author_name=$matches[1]', 'top');
    add_rewrite_rule('^authors/?$', 'index.php?authors_page=1', 'top');
}

add_filter('query_vars', 'abs_catalog_query_vars');
function abs_catalog_query_vars($vars) {
    $vars[] = 'paged';
    $vars[] = 'sort';
    $vars[] = 'genre';
    $vars[] = 'search';
    $vars[] = 'author';
    $vars[] = 'author_name';
    $vars[] = 'authors_page';
    $vars[] = 'type';
    return $vars;
}

// Создаём страницу каталога
add_action('init', 'abs_create_catalog_page');
function abs_create_catalog_page() {
    if (!get_page_by_path('catalog')) {
        wp_insert_post(array(
            'post_title' => 'Каталог книг',
            'post_name' => 'catalog',
            'post_content' => '[abs_catalog]',
            'post_status' => 'publish',
            'post_type' => 'page'
        ));
    }
}

add_shortcode('abs_catalog', 'abs_catalog_shortcode');
function abs_catalog_shortcode() {
    ob_start();
    abs_catalog_render();
    return ob_get_clean();
}



function abs_get_all_genres() {
    global $wpdb;
    $cache_table = $wpdb->prefix . 'abs_book_cache';
    $books = $wpdb->get_col("SELECT book_data FROM $cache_table");
    $genres = array();
    foreach ($books as $book_data) {
        $data = json_decode($book_data, true);
        $book_genres = $data['media']['metadata']['genres'] ?? array();
        foreach ($book_genres as $genre) {
            if (!in_array($genre, $genres) && !empty($genre)) {
                $genres[] = $genre;
            }
        }
    }
    sort($genres);
    return $genres;
}



function abs_catalog_render() {
    global $wpdb;
    
    $paged = max(1, get_query_var('paged') ?: 1);
    $sort = sanitize_text_field(get_query_var('sort') ?: 'popularity_desc');
    $genre = sanitize_text_field(get_query_var('genre'));
    $search = sanitize_text_field($_GET['search'] ?? get_query_var('search') ?? '');
    $author_filter = sanitize_text_field($_GET['author'] ?? '');
    $type = sanitize_text_field(get_query_var('type') ?: 'all');
    $per_page = 50;
    $offset = ($paged - 1) * $per_page;
    
    $filtered_books = array();
    
    // 1. Аудиокниги
    if ($type === 'all' || $type === 'audio') {
        $cache_table = $wpdb->prefix . 'abs_book_cache';
        $all_audio = $wpdb->get_results("SELECT book_id, book_data FROM $cache_table");
        foreach ($all_audio as $book) {
            $book_data = json_decode($book->book_data, true);
            $metadata = $book_data['media']['metadata'] ?? array();
            $title = $metadata['title'] ?? '';
            $authors = abs_get_book_authors($metadata);
            $author_str = implode(', ', $authors);
            $genres_list = $metadata['genres'] ?? array();
            
            $total_listen_time = (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(total_seconds, 0) FROM {$wpdb->prefix}abs_book_stats WHERE book_id = %s",
                $book->book_id
            ));
            
            $pass_search = true;
            if (!empty($search)) {
                $tl = mb_strtolower($title); $al = mb_strtolower($author_str); $sl = mb_strtolower($search);
                $pass_search = (strpos($tl, $sl) !== false || strpos($al, $sl) !== false);
            }
            if ($pass_search && !empty($author_filter)) {
                $al = mb_strtolower($author_str);
                $af = mb_strtolower($author_filter);
                $pass_search = ($al === $af || strpos($al, $af) !== false);
            }
            $pass_genre = empty($genre) || in_array($genre, $genres_list);
            
            if ($pass_search && $pass_genre) {
                $page_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'abs_book_id' AND meta_value = %s", $book->book_id
                ));
                // Подтягиваем метаданные из текстовой книги
$ranobe_meta = abs_get_book_meta_from_ranobe($book->book_id);
if (!empty($ranobe_meta['author'])) {
    $author_str = $ranobe_meta['author'];
    $authors = array($ranobe_meta['author']);
}
if (!empty($ranobe_meta['genres'])) {
    $genres_list = $ranobe_meta['genres'];
}
$desc = !empty($ranobe_meta['description']) ? $ranobe_meta['description'] : ($metadata['description'] ?? '');

$filtered_books[] = array(
    'type' => 'audio', 'book_id' => $book->book_id, 'title' => $title,
    'authors' => $authors, 'author_str' => $author_str, 'genres_list' => $genres_list,
    'description' => $desc, 'total_listen_time' => $total_listen_time,
    'permalink' => $page_id ? get_permalink($page_id) : '#',
);
            }
        }
    }
    
    // 2. Текстовые книги
    if ($type === 'all' || $type === 'text') {
        $all_ranobe = get_posts(array('post_type' => 'ranobe', 'posts_per_page' => -1));
        foreach ($all_ranobe as $ranobe) {
            $title = $ranobe->post_title;
            $author = get_post_meta($ranobe->ID, '_ranobe_author', true);
            $authors = $author ? array($author) : array();
            $author_str = $author;
            $cats = wp_get_post_categories($ranobe->ID);
            $genres_list = array();
            foreach ($cats as $cid) { $c = get_category($cid); if ($c) $genres_list[] = $c->name; }
            $description = $ranobe->post_excerpt ?: wp_trim_words(strip_tags($ranobe->post_content), 20);
            
            $pass_search = true;
            if (!empty($search)) {
                $tl = mb_strtolower($title); $al = mb_strtolower($author_str); $sl = mb_strtolower($search);
                $pass_search = (strpos($tl, $sl) !== false || ($author_str && strpos($al, $sl) !== false));
            }
            if ($pass_search && !empty($author_filter)) {
                $al = mb_strtolower($author_str);
                $af = mb_strtolower($author_filter);
                $pass_search = ($al === $af || strpos($al, $af) !== false);
            }
            $pass_genre = empty($genre) || in_array($genre, $genres_list);
            
            if ($pass_search && $pass_genre) {
                $filtered_books[] = array(
                    'type' => 'text', 'book_id' => $ranobe->ID, 'title' => $title,
                    'authors' => $authors, 'author_str' => $author_str, 'genres_list' => $genres_list,
                    'description' => $description, 'total_listen_time' => 0,
                    'permalink' => get_permalink($ranobe->ID),
                );
            }
        }
    }
    
    // Сортировка
    if ($sort == 'title_asc') usort($filtered_books, function($a, $b) { return strcmp($a['title'], $b['title']); });
    elseif ($sort == 'title_desc') usort($filtered_books, function($a, $b) { return strcmp($b['title'], $a['title']); });
    elseif ($sort == 'popularity_asc') usort($filtered_books, function($a, $b) { return $a['total_listen_time'] - $b['total_listen_time']; });
    else usort($filtered_books, function($a, $b) { return $b['total_listen_time'] - $a['total_listen_time']; });
    
    $total_books = count($filtered_books);
    $total_pages = ceil($total_books / $per_page);
    $books = array_slice($filtered_books, $offset, $per_page);
    // Жанры с подсчётом книг
$genre_counts = [];
$all_audio = $wpdb->get_results("SELECT book_id, book_data FROM {$wpdb->prefix}abs_book_cache");
foreach ($all_audio as $book) {
    $data = json_decode($book->book_data, true);
    $book_genres = $data['media']['metadata']['genres'] ?? [];
    foreach ($book_genres as $g) {
        $g = trim($g);
        if ($g) {
            $genre_counts[$g] = ($genre_counts[$g] ?? 0) + 1;
        }
    }
}
$all_text = get_posts(['post_type' => 'ranobe', 'posts_per_page' => -1]);
foreach ($all_text as $text) {
    $cats = wp_get_post_categories($text->ID);
    foreach ($cats as $cid) {
        $cat = get_category($cid);
        if ($cat) {
            $genre_counts[$cat->name] = ($genre_counts[$cat->name] ?? 0) + 1;
        }
    }
}
ksort($genre_counts);

// Авторы с подсчётом книг (только из текстовых книг)
$author_counts = [];
foreach ($all_text as $text) {
    $author = get_post_meta($text->ID, '_ranobe_author', true);
    if ($author) {
        $author = trim($author);
        $author_counts[$author] = ($author_counts[$author] ?? 0) + 1;
    }
}
// Добавляем аудиокниги, у которых есть привязка к текстовой
foreach ($all_audio as $book) {
    $ranobe_meta = abs_get_book_meta_from_ranobe($book->book_id);
    if (!empty($ranobe_meta['author'])) {
        $a = trim($ranobe_meta['author']);
        $author_counts[$a] = ($author_counts[$a] ?? 0) + 1;
    }
}
ksort($author_counts);
    // Подсчёт количества аудиокниг и текстовых книг
$audio_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}abs_book_cache");
$text_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'ranobe' AND post_status = 'publish'");
    ?>
    <div class="abs-catalog">
        <div class="catalog-filters">
            <form method="get" action="<?php echo esc_url(home_url('/catalog')); ?>">
                <div class="filter-type-buttons">
                    <a href="/catalog?type=all<?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $genre ? '&genre=' . urlencode($genre) : ''; ?>" class="type-btn <?php echo $type === 'all' ? 'active' : ''; ?>">Все (<?php echo $audio_count + $text_count; ?>)</a>
                    <a href="/catalog?type=audio<?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $genre ? '&genre=' . urlencode($genre) : ''; ?>" class="type-btn <?php echo $type === 'audio' ? 'active' : ''; ?>">🎧 Аудиокниги (<?php echo $audio_count; ?>)</a>
                    <a href="/catalog?type=text<?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $genre ? '&genre=' . urlencode($genre) : ''; ?>" class="type-btn <?php echo $type === 'text' ? 'active' : ''; ?>">📖 Книги (<?php echo $text_count; ?>)</a>
                </div>

                <div class="filter-search">
                    <div class="search-wrapper">
                        <input type="text" name="search" placeholder="Поиск по названию или автору..." value="<?php echo esc_attr($search); ?>" autocomplete="off">
                        <button type="button" id="search-submit" class="search-icon-btn">🔍</button>
                    </div>
                </div>

                <div class="filter-row">
                    <div class="filter-genre">
                        <select name="genre" id="genre-select">
                            <option value="">Все жанры</option>
                            <?php foreach ($genre_counts as $g => $count): ?>
                                <option value="<?php echo esc_attr($g); ?>" <?php selected($genre, $g); ?>><?php echo esc_html($g); ?> (<?php echo $count; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-author">
                        <select name="author" id="author-select">
                            <option value="">Все авторы</option>
                            <?php foreach ($author_counts as $a => $count): ?>
    <option value="<?php echo esc_attr($a); ?>" <?php selected($author_filter, $a); ?>><?php echo esc_html($a); ?> (<?php echo $count; ?>)</option>
<?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-sort">
                        <select name="sort" id="sort-select">
                            <option value="popularity_desc" <?php selected($sort, 'popularity_desc'); ?>>Популярные (сначала)</option>
                            <option value="popularity_asc" <?php selected($sort, 'popularity_asc'); ?>>Непопулярные (сначала)</option>
                            <option value="title_asc" <?php selected($sort, 'title_asc'); ?>>Название (А-Я)</option>
                            <option value="title_desc" <?php selected($sort, 'title_desc'); ?>>Название (Я-А)</option>
                        </select>
                    </div>
                </div>
                <?php if (!empty($search) || !empty($genre) || $type !== 'all'): ?>
                    <div class="filter-reset"><a href="/catalog">Сбросить фильтры</a></div>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="catalog-books-grid">
            <?php if (empty($books)): ?>
                <p>Книги не найдены.</p>
            <?php else: ?>
                                <?php foreach ($books as $book):
                    $title = $book['title'];
                    
                    if ($book['type'] === 'audio') {
                        $ranobe_meta = abs_get_book_meta_from_ranobe($book['book_id']);
                        $cover_url = $ranobe_meta['cover_url'];
                        $authors = !empty($ranobe_meta['author']) ? array($ranobe_meta['author']) : $book['authors'];
                        $genres_list = !empty($ranobe_meta['genres']) ? $ranobe_meta['genres'] : $book['genres_list'];
                        $description = !empty($ranobe_meta['description']) 
                            ? wp_trim_words(wp_strip_all_tags($ranobe_meta['description']), 20, '...')
                            : wp_trim_words(wp_strip_all_tags($book['description']), 20, '...');
                    } else {
                        $cover_url = has_post_thumbnail($book['book_id']) ? get_the_post_thumbnail_url($book['book_id'], 'medium') : '';
                        $authors = $book['authors'];
                        $genres_list = $book['genres_list'];
                        $description = wp_trim_words(wp_strip_all_tags($book['description']), 20, '...');
                    }
                    
                    $btn_text = $book['type'] === 'audio' ? '▶ Слушать' : '📖 Читать';
                    $type_badge = $book['type'] === 'audio' ? '🎧' : '📖';
                ?>
                    <div class="catalog-book-card">
                        <div class="catalog-book-cover">
                            <?php if ($cover_url): ?>
                                <img src="<?php echo esc_url($cover_url); ?>" alt="<?php echo esc_attr($title); ?>" onerror="this.style.display='none'; this.parentElement.querySelector('.no-cover').style.display='flex';">
<div class="no-cover" style="display:none;"><?php echo $type_badge; ?></div>
                            <?php else: ?>
                                <div class="no-cover"><?php echo $type_badge; ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="catalog-book-info">
                            <h3 class="catalog-book-title">
                                <a href="<?php echo esc_url($book['permalink']); ?>"><?php echo esc_html($title); ?></a>
                                <span style="font-size:0.7rem;color:#0dcaf0;margin-left:5px;"><?php echo $type_badge; ?></span>
                            </h3>
                            <div class="catalog-book-author">
                                <?php foreach ($authors as $author): ?>
                                    <a href="/catalog?author=<?php echo urlencode($author); ?>" class="author-link"><?php echo esc_html($author); ?></a>
                                <?php endforeach; ?>
                            </div>
                            <?php if (!empty($genres_list)): ?>
                                <div class="catalog-book-genres">
                                    <?php foreach ($genres_list as $g): ?>
                                        <a href="/catalog?genre=<?php echo urlencode($g); ?>" class="book-genre-tag"><?php echo esc_html($g); ?></a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <div class="catalog-book-description"><?php echo esc_html($description); ?></div>
                        </div>
                        <div class="catalog-book-actions">
                            <a href="<?php echo esc_url($book['permalink']); ?>" class="catalog-listen-btn"><?php echo $btn_text; ?></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if ($total_pages > 1): ?>
        <div class="catalog-pagination">
            <?php echo paginate_links(array(
                'base' => home_url('/catalog/page/%#%'), 'format' => '', 'current' => $paged, 'total' => $total_pages,
                'prev_text' => '←', 'next_text' => '→',
                'add_args' => array_filter(array('sort' => $sort, 'genre' => $genre, 'search' => $search, 'author' => $author_filter, 'type' => $type))
            )); ?>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var form = document.querySelector('.abs-catalog .catalog-filters form');
        if (!form) return;
        form.querySelectorAll('select').forEach(function(s) { s.addEventListener('change', function() { form.submit(); }); });
        var sb = document.getElementById('search-submit'); if (sb) sb.addEventListener('click', function() { form.submit(); });
        var si = document.getElementById('search-input'); if (si) si.addEventListener('keypress', function(e) { if (e.key === 'Enter') { e.preventDefault(); form.submit(); } });
    });
    </script>
    
    <style>
        .filter-author select {
        background-color: rgba(26,26,46,0.8) !important;
        border: 1px solid rgba(13,202,240,0.3) !important;
        border-radius: 30px !important;
        color: #fff !important;
        padding: 10px 16px !important;
        height: 42px;
        cursor: pointer;
        min-width: 180px;
    }
    .filter-author select option {
        background-color: #1a1a2e !important;
        color: #fff !important;
    }
    .search-wrapper { position:relative; width:100%; }
    .search-wrapper input { width:100%; padding-right:40px !important; box-sizing:border-box; margin-bottom:0 !important; }
    .search-icon-btn { position:absolute; right:12px; top:50%; transform:translateY(-50%); background:transparent; border:none; cursor:pointer; font-size:18px; color:rgba(255,255,255,0.6); }
    .search-icon-btn:hover { color:#0dcaf0; }
        .filter-type-buttons {
        display: flex;
        gap: 0;
        margin-bottom: 20px;
        border-radius: 14px;
        overflow: hidden;
        width: 100%;
    }
    .type-btn {
        flex: 1;
        text-align: center;
        padding: 14px 10px;
        font-size: 1rem;
        font-weight: 600;
        text-decoration: none;
        color: rgba(255,255,255,0.7);
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        transition: all 0.2s;
    }
    .type-btn:first-child {
        border-radius: 14px 0 0 14px;
    }
    .type-btn:last-child {
        border-radius: 0 14px 14px 0;
    }
    .type-btn.active {
        background: #0dcaf0;
        color: #1b2039;
        border-color: #0dcaf0;
    }
    .type-btn:hover:not(.active) {
        background: rgba(13,202,240,0.15);
        color: #0dcaf0;
    }
    @media (max-width: 600px) {
        .type-btn {
            font-size: 0.85rem;
            padding: 12px 6px;
        }
    }
    .filter-type select, .filter-genre select, .filter-sort select { background-color:rgba(26,26,46,0.8) !important; border:1px solid rgba(13,202,240,0.3) !important; border-radius:30px !important; color:#fff !important; padding:10px 16px !important; height:42px; cursor:pointer; }
    .filter-type select option, .filter-genre select option, .filter-sort select option { background-color:#1a1a2e !important; color:#fff !important; }
    .filter-row {
        margin-top: 15px;
        display: flex;
        gap: 8px;
        flex-wrap: nowrap;
        align-items: center;
    }
    .filter-genre, .filter-author, .filter-sort {
        flex: 1;
        min-width: 0;
    }
    .filter-genre select, .filter-author select, .filter-sort select {
        width: 100%;
    }
    @media (max-width: 768px) {
        .filter-row {
            flex-wrap: wrap;
        }
        .filter-genre, .filter-author, .filter-sort {
            flex: 1 1 auto;
            min-width: 120px;
        }
    }
    .filter-search {
        margin-bottom: 20px;
    }
    .filter-type-buttons {
        display: flex;
        gap: 0;
        margin-bottom: 15px;
        border-radius: 14px;
        overflow: hidden;
        width: 100%;
    }
    </style>
    <?php
}




function abs_get_book_meta_from_ranobe($book_id) {
    global $wpdb;
    $meta_table = $wpdb->prefix . 'abs_audio_meta';
    
    // 1. Проверяем кэш
    $cached = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $meta_table WHERE book_id = %s",
        $book_id
    ));
    
    if ($cached && !empty($cached->author)) {
        return array(
        'cover_url' => $cached->cover_url ?: '',
        'author'    => $cached->author,
        'genres'    => !empty($cached->genres) ? explode(', ', $cached->genres) : array(),
        'description' => $cached->description ?: '',
        'ranobe_id' => (int)$cached->ranobe_id,
    );
    }
    
    // 2. Ищем в текстовых книгах
    $result = array('cover_url' => '', 'author' => '', 'genres' => array(), 'description' => '');
    
    // По прямой привязке
    $ranobe = get_posts(array(
        'post_type'      => 'ranobe',
        'meta_key'       => '_ranobe_abs_book_id',
        'meta_value'     => $book_id,
        'posts_per_page' => 1,
    ));
    
    // По названию
    if (empty($ranobe)) {
        $cache_table = $wpdb->prefix . 'abs_book_cache';
        $cached_book = $wpdb->get_var($wpdb->prepare(
            "SELECT book_data FROM $cache_table WHERE book_id = %s", $book_id
        ));
        if ($cached_book) {
            $data = json_decode($cached_book, true);
            $title = $data['media']['metadata']['title'] ?? '';
            if ($title) {
                $ranobe = get_posts(array(
                    'post_type'      => 'ranobe',
                    's'              => $title,
                    'posts_per_page' => 1,
                ));
            }
        }
    }
    
    if (!empty($ranobe)) {
        $ranobe_id = $ranobe[0]->ID;
        if (has_post_thumbnail($ranobe_id)) {
            $result['cover_url'] = get_the_post_thumbnail_url($ranobe_id, 'medium');
        }
        $result['author'] = get_post_meta($ranobe_id, '_ranobe_author', true);
        $cats = wp_get_post_categories($ranobe_id);
        foreach ($cats as $cat_id) {
            $cat = get_category($cat_id);
            if ($cat) $result['genres'][] = $cat->name;
        }
        $post = get_post($ranobe_id);
        $result['description'] = $post->post_excerpt ?: strip_tags($post->post_content);
        
        // Сохраняем в кэш
        $wpdb->replace($meta_table, array(
            'book_id'     => $book_id,
            'ranobe_id'   => $ranobe_id,
            'author'      => $result['author'],
            'genres'      => implode(', ', $result['genres']),
            'description' => $result['description'],
            'cover_url'   => $result['cover_url'],
        ));
                // Обновляем привязку у текстовой книги
        if ($ranobe_id && !get_post_meta($ranobe_id, '_ranobe_abs_book_id', true)) {
            update_post_meta($ranobe_id, '_ranobe_abs_book_id', $book_id);
        }
    } elseif (!$cached) {
        // Сохраняем пустую запись чтобы не искать повторно
        $wpdb->replace($meta_table, array(
            'book_id'   => $book_id,
            'ranobe_id' => 0,
            'author'    => '',
        ));
    }
    
    return $result;
}
?>