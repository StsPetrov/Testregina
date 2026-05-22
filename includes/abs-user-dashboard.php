<?php

/**
 * abs-user-dashboard.php - Личный кабинет пользователя
 */

if (!defined('ABSPATH')) {
    exit;
}

// ========== ШОРТКОД ДЛЯ ВЫВОДА КНИГ ПО СТАТУСУ ==========
add_shortcode('abs_user_books', 'abs_user_books_shortcode');
function abs_user_books_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<p>🔒 <a href="' . home_url('/login?redirect_to=' . urlencode(get_permalink())) . '">Войдите</a>, чтобы видеть свои книги.</p>';
    }
    
    $atts = shortcode_atts(array('status' => 'listening'), $atts);
    $status = sanitize_text_field($atts['status']);
    
    $user_id = get_current_user_id();
    $books = abs_get_user_books_by_status($user_id, $status);
    
    if (empty($books)) {
        return '<p>📚 Нет книг в статусе "' . esc_html($status) . '".</p>';
    }
    
    ob_start();
    ?>
    <div class="abs-user-books">
        <h3>📖 <?php echo esc_html(ucfirst($status)); ?></h3>
        <div class="books-grid">
            <?php foreach ($books as $book): ?>
                <div class="book-card">
                    <a href="<?php echo esc_url($book['permalink']); ?>" class="book-title-link">
                        <?php if ($book['cover']): ?>
                            <img src="<?php echo esc_url($book['cover']); ?>" alt="<?php echo esc_attr($book['title']); ?>">
                        <?php endif; ?>
                        <h4><?php echo esc_html($book['title']); ?></h4>
                    </a>
                    <?php if ($status == 'listening' && $book['progress_percent'] > 0): ?>
                        <div class="progress-bar"><div style="width: <?php echo $book['progress_percent']; ?>%"></div></div>
                    <?php endif; ?>
                    <button class="play-btn" data-book-id="<?php echo esc_attr($book['book_id']); ?>">▶ Слушать</button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// ========== ФУНКЦИИ ДЛЯ РАБОТЫ С ДАННЫМИ ==========

function abs_get_user_library($user_id) {
    global $wpdb;
    
    $result = [
        'listening' => [],
        'completed' => [],
        'favorites' => [],
        'postponed' => [],
        'abandoned' => []
    ];
    
    $progress = $wpdb->get_results($wpdb->prepare("
        SELECT p.book_id, p.total_progress_seconds, c.book_data, c.total_duration,
               COALESCE(s.status, 'listening') as status
        FROM {$wpdb->prefix}abs_progress p
        LEFT JOIN {$wpdb->prefix}abs_book_status s ON p.book_id = s.book_id AND s.user_id = %d
        LEFT JOIN {$wpdb->prefix}abs_book_cache c ON p.book_id = c.book_id
        WHERE p.user_id = %d
        GROUP BY p.book_id
    ", $user_id, $user_id));
    
    $favorites = $wpdb->get_results($wpdb->prepare("
        SELECT f.book_id, c.book_data
        FROM {$wpdb->prefix}abs_favorites f
        LEFT JOIN {$wpdb->prefix}abs_book_cache c ON f.book_id = c.book_id
        WHERE f.user_id = %d
    ", $user_id));
    
    foreach ($progress as $book) {
        $book_data = json_decode($book->book_data, true);
        $title = $book_data['media']['metadata']['title'] ?? 'Без названия';
        $cover_path = $book_data['media']['coverPath'] ?? '';
        $cover = $cover_path ? 'http://1001ranobe.ru:13378' . $cover_path : '';
        
        $progress_percent = 0;
        if ($book->total_duration > 0 && $book->total_progress_seconds > 0) {
            $progress_percent = round(($book->total_progress_seconds / $book->total_duration) * 100);
            if ($progress_percent > 100) $progress_percent = 100;
        }
        
        $item = [
            'book_id' => $book->book_id,
            'title' => $title,
            'cover' => $cover,
            'progress_percent' => $progress_percent
        ];
        
        $status = $book->status;
        if (isset($result[$status])) {
            $result[$status][] = $item;
        } else {
            $result['listening'][] = $item;
        }
    }
    
    foreach ($favorites as $book) {
        $book_data = json_decode($book->book_data, true);
        $title = $book_data['media']['metadata']['title'] ?? 'Без названия';
        $cover_path = $book_data['media']['coverPath'] ?? '';
        $cover = $cover_path ? 'http://1001ranobe.ru:13378' . $cover_path : '';
        
        $result['favorites'][] = [
            'book_id' => $book->book_id,
            'title' => $title,
            'cover' => $cover,
            'progress_percent' => 0
        ];
    }
    
    return $result;
}

function abs_get_user_books_by_status($user_id, $status) {
    global $wpdb;
    
    if ($status == 'favorites') {
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT f.book_id, c.book_data 
             FROM {$wpdb->prefix}abs_favorites f
             LEFT JOIN {$wpdb->prefix}abs_book_cache c ON f.book_id = c.book_id
             WHERE f.user_id = %d
             ORDER BY f.added_at DESC",
            $user_id
        ));
    } else {
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT p.book_id, p.total_progress_seconds, c.book_data, c.total_duration
             FROM {$wpdb->prefix}abs_progress p
             LEFT JOIN {$wpdb->prefix}abs_book_cache c ON p.book_id = c.book_id
             LEFT JOIN {$wpdb->prefix}abs_book_status s ON p.book_id = s.book_id AND s.user_id = %d
             WHERE p.user_id = %d
               AND (s.status = %s OR (%s = 'listening' AND (s.status IS NULL OR s.status = 'listening')))
             ORDER BY p.updated_at DESC",
            $user_id, $user_id, $status, $status
        ));
    }
    
    $books = [];
    foreach ($results as $row) {
        $data = json_decode($row->book_data, true);
        $metadata = $data['media']['metadata'] ?? [];
        $title = $metadata['title'] ?? 'Без названия';
        $cover_path = $data['media']['coverPath'] ?? '';
        $cover = $cover_path ? 'http://1001ranobe.ru:13378' . $cover_path : '';
        
        $progress_percent = 0;
        if (isset($row->total_duration) && $row->total_duration > 0 && isset($row->total_progress_seconds)) {
            $progress_percent = round(($row->total_progress_seconds / $row->total_duration) * 100);
            if ($progress_percent > 100) $progress_percent = 100;
        }
        
        $permalink = abs_get_book_permalink($row->book_id);
        
        $books[] = [
            'book_id' => $row->book_id,
            'title' => $title,
            'cover' => $cover,
            'permalink' => $permalink,
            'progress_percent' => $progress_percent
        ];
    }
    
    return $books;
}

function abs_get_user_stats($user_id) {
    global $wpdb;
    
    $progress = $wpdb->get_results($wpdb->prepare(
        "SELECT total_progress_seconds FROM {$wpdb->prefix}abs_progress WHERE user_id = %d",
        $user_id
    ));
    
    $statuses = $wpdb->get_results($wpdb->prepare(
        "SELECT status, COUNT(*) as count 
         FROM {$wpdb->prefix}abs_book_status 
         WHERE user_id = %d 
         GROUP BY status",
        $user_id
    ));
    
    $favorites_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}abs_favorites WHERE user_id = %d",
        $user_id
    ));
    
    $avg_rating = $wpdb->get_var($wpdb->prepare(
        "SELECT AVG(rating) FROM {$wpdb->prefix}abs_ratings WHERE user_id = %d",
        $user_id
    ));
    
    $total_books = count($progress);
    $total_hours = 0;
    foreach ($progress as $track) {
        $total_hours += $track->total_progress_seconds / 3600;
    }
    
    $status_counts = ['listening' => 0, 'completed' => 0, 'postponed' => 0, 'abandoned' => 0];
    foreach ($statuses as $s) {
        if (isset($status_counts[$s->status])) {
            $status_counts[$s->status] = $s->count;
        }
    }
    
    $completed_books = $status_counts['completed'];
    
    return [
        'total_books' => $total_books,
        'completed_books' => $completed_books,
        'total_hours' => round($total_hours, 1),
        'favorites_count' => (int)$favorites_count,
        'avg_rating' => round($avg_rating ?: 0, 1),
        'status_counts' => $status_counts
    ];
}

function abs_render_book_grid($books, $title) {
    if (empty($books)) {
        echo '<div class="empty-message">📚 Нет книг в разделе "' . $title . '"</div>';
        return;
    }
    
    echo '<div class="books-grid">';
    foreach ($books as $book) {
        $permalink = abs_get_book_permalink($book['book_id']);
        ?>
        <div class="book-card" data-book-id="<?php echo esc_attr($book['book_id']); ?>">
            <a href="<?php echo esc_url($permalink); ?>" class="book-card-title">
                <?php echo esc_html($book['title']); ?>
            </a>
           
            <div class="book-actions">
                <a href="<?php echo esc_url($permalink); ?>" class="listen-btn">▶ Слушать</a>
                <?php if ($title == 'Избранное' || $title == 'favorites'): ?>
                    <a href="/delete-book.php?book_id=<?php echo esc_attr($book['book_id']); ?>&type=fav&nonce=<?php echo wp_create_nonce('abs_delete_book'); ?>" class="remove-btn" onclick="return confirm('Удалить из избранного?')">✕</a>
                <?php else: ?>
                    <a href="/delete-book.php?book_id=<?php echo esc_attr($book['book_id']); ?>&type=progress&nonce=<?php echo wp_create_nonce('abs_delete_book'); ?>" class="remove-btn" onclick="return confirm('Удалить из списка?')">✕</a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    echo '</div>';
}