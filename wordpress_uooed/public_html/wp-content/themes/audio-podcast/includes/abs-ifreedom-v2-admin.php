<?php
// Админка парсера ifreedom v2
add_action('admin_menu', function() {
    add_menu_page('Парсер ifreedom v2', 'Ifreedom v2', 'manage_options', 'abs-ifreedom-v2', 'abs_ifreedom_v2_admin_page', 'dashicons-download', 33);
});

function abs_ifreedom_v2_admin_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'abs_ifreedom_v2_queue';
    
    // Обработка действий
    if (isset($_POST['action']) && $_POST['action'] === 'scan') {
        $page = intval($_POST['page'] ?? 1);
        $books = abs_ifreedom_v2_scan_catalog($page);
        $count = 0;
        foreach ($books as $book) {
            $wpdb->replace($table, [
                'slug' => $book['slug'], 'title' => $book['title'], 'url' => $book['url'], 'status' => 'new'
            ]);
            $count++;
        }
        echo '<div class="notice notice-success"><p>Добавлено книг: ' . $count . ' (страница ' . $page . ')</p></div>';
    }
    
    if (isset($_POST['action']) && $_POST['action'] === 'process_next') {
        $book = $wpdb->get_row("SELECT * FROM $table WHERE status IN('new','error') ORDER BY id ASC LIMIT 1");
        if ($book) {
            $result = abs_ifreedom_v2_process_book($book->slug);
            abs_telegram_log("📥 V2 AJAX: {$book->title} — {$result['loaded']}/{$result['total']} глав");
            echo '<div class="notice notice-success"><p>Обработано: ' . esc_html($book->title) . ' — ' . $result['loaded'] . '/' . $result['total'] . ' глав</p></div>';
        } else {
            echo '<div class="notice notice-warning"><p>Нет книг для обработки</p></div>';
        }
    }
    
    $stats = $wpdb->get_row("SELECT COUNT(*) as total, SUM(CASE WHEN status='done' THEN 1 ELSE 0 END) as done, SUM(CASE WHEN status='error' THEN 1 ELSE 0 END) as errors FROM $table");
    ?>
    <div class="wrap">
        <h1>Парсер ifreedom v2</h1>
        
        <div style="display:flex;gap:20px;margin:20px 0;">
            <div class="card" style="flex:1;text-align:center;padding:20px;">
                <div style="font-size:2rem;font-weight:700;"><?php echo $stats->total; ?></div>
                <div>Всего книг</div>
            </div>
            <div class="card" style="flex:1;text-align:center;padding:20px;">
                <div style="font-size:2rem;font-weight:700;color:green;"><?php echo $stats->done; ?></div>
                <div>Загружено</div>
            </div>
            <div class="card" style="flex:1;text-align:center;padding:20px;">
                <div style="font-size:2rem;font-weight:700;color:red;"><?php echo $stats->errors; ?></div>
                <div>С ошибкой</div>
            </div>
        </div>
        
        <div style="display:flex;gap:10px;margin:20px 0;">
            <form method="post">
                <input type="hidden" name="action" value="scan">
                <button class="button button-primary">🔍 Сканировать</button>
            </form>
            <form method="post">
                <input type="hidden" name="action" value="process_next">
                <button class="button button-primary">📥 Загрузить следующую</button>
            </form>
        </div>
    </div>
    <?php
}

function abs_ifreedom_v2_scan_catalog($page = 1) {
    $url = 'https://ifreedom.su/vse-knigi/' . ($page > 1 ? '?bpage=' . $page : '');
    $html = abs_ifreedom_v2_get_html($url);
    if (is_array($html)) return [];
    
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
        $books[] = ['slug' => $m[1], 'title' => $title_node ? trim($title_node->textContent) : '', 'url' => $book_url];
    }
    return $books;
}