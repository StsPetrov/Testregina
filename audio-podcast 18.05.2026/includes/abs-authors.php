<?php
// Шорткод для списка авторов (аудиокниги + текстовые книги)

function abs_decline_audio($n) {
    $w = abs_decline($n, array('аудиокнига', 'аудиокниги', 'аудиокниг'));
    return $n . ' ' . $w;
}
function abs_decline_text($n) {
    $w = abs_decline($n, array('текстовая книга', 'текстовые книги', 'текстовых книг'));
    return $n . ' ' . $w;
}

add_shortcode('abs_authors', 'abs_authors_shortcode');
function abs_authors_shortcode() {
    global $wpdb;
    $authors_map = array();
    
    // 1. Авторы из аудиокниг (только с привязкой к текстовым)
    $cache_table = $wpdb->prefix . 'abs_book_cache';
    $books = $wpdb->get_results("SELECT book_data FROM $cache_table");
    foreach ($books as $book) {
        $data = json_decode($book->book_data, true);
        $book_id = $book->book_id;
        
        // Ищем привязку к текстовой книге
        $ranobe = get_posts(array(
            'post_type' => 'ranobe',
            'meta_key' => '_ranobe_abs_book_id',
            'meta_value' => $book_id,
            'posts_per_page' => 1,
        ));
        
        if (empty($ranobe)) {
            // Ищем по названию
            $title = $data['media']['metadata']['title'] ?? '';
            if ($title) {
                $ranobe = get_posts(array(
                    'post_type' => 'ranobe',
                    's' => $title,
                    'posts_per_page' => 1,
                ));
            }
        }
        
        if (!empty($ranobe)) {
            $author = get_post_meta($ranobe[0]->ID, '_ranobe_author', true);
            if ($author) {
                $name = trim($author);
                $key = mb_strtolower($name);
                if (!isset($authors_map[$key])) $authors_map[$key] = array('name' => $name, 'audio' => 0, 'text' => 0);
                $authors_map[$key]['audio']++;
            }
        }
    }
    
    // 2. Авторы из текстовых книг
    $ranobe_posts = get_posts(array('post_type' => 'ranobe', 'posts_per_page' => -1));
    foreach ($ranobe_posts as $rp) {
        $author = get_post_meta($rp->ID, '_ranobe_author', true);
        if ($author) {
            $key = mb_strtolower(trim($author));
            if (!isset($authors_map[$key])) $authors_map[$key] = array('name' => trim($author), 'audio' => 0, 'text' => 0);
            $authors_map[$key]['text']++;
        }
    }
    
    ksort($authors_map);
    
    ob_start();
    ?>
    <div class="abs-authors-list">
        <div class="authors-grid">
            <?php foreach ($authors_map as $author): ?>
                <div class="author-card">
                    <a href="/catalog?author=<?php echo urlencode($author['name']); ?>" class="author-name">
                        👤 <?php echo esc_html($author['name']); ?>
                    </a>
                    <div class="author-book-count">🎧 <?php echo abs_decline_audio($author['audio']); ?></div>
                    <div class="author-book-count">📖 <?php echo abs_decline_text($author['text']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <style>
        .abs-authors-list { padding: 20px; }
        .authors-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; margin-top: 20px; }
        .author-card { background: rgba(255,255,255,0.05); border-radius: 16px; padding: 20px; transition: all 0.2s; }
        .author-card:hover { background: rgba(13,202,240,0.1); transform: translateY(-4px); }
        .author-name { color: #0dcaf0; font-size: 1.1rem; text-decoration: none; display: block; margin-bottom: 8px; }
        .author-name:hover { text-decoration: underline; }
        .author-book-count { color: rgba(255,255,255,0.7); font-size: 0.85rem; }
    </style>
    <?php
    return ob_get_clean();
}