<?php
/**
 * Админка парсера ifreedom v2
 * Все фильтры, сортировки, настройки, логи
 */

if (!defined('ABSPATH')) exit;

// ========== МЕНЮ ==========
add_action('admin_menu', 'abs_ifreedom_v2_admin_menu');
function abs_ifreedom_v2_admin_menu() {
    add_menu_page(
        'Парсер Ifreedom v2',
        'Ifreedom v2',
        'manage_options',
        'abs-ifreedom-v2',
        'abs_ifreedom_v2_admin_page',
        'dashicons-download',
        33
    );
}

// ========== AJAX ОБРАБОТЧИКИ ==========
add_action('wp_ajax_abs_ifreedom_v2_save_settings', 'abs_ifreedom_v2_save_settings');
function abs_ifreedom_v2_save_settings() {
    check_ajax_referer('abs_ifreedom_v2_nonce');
    if (!current_user_can('manage_options')) wp_send_json_error();
    
    $settings = abs_ifreedom_v2_get_settings();
    $fields = ['min_delay_ms', 'max_delay_ms', 'max_per_minute', 'cron_batch_size', 'manual_batch_size', 'http_timeout'];
    foreach ($fields as $f) {
        if (isset($_POST[$f])) $settings[$f] = absint($_POST[$f]);
    }
    update_option('abs_ifreedom_v2_settings', $settings);
    wp_send_json_success();
}

add_action('wp_ajax_abs_ifreedom_v2_scan', 'abs_ifreedom_v2_scan_ajax');
function abs_ifreedom_v2_scan_ajax() {
    check_ajax_referer('abs_ifreedom_v2_nonce');
    if (!current_user_can('manage_options')) wp_send_json_error();
    
    require_once get_template_directory() . '/includes/abs-ifreedom-v2.php';
    
    $page = (int)($_POST['page'] ?? 1);
    $last_page = (int)($_POST['last_page'] ?? 0);
    $total = (int)($_POST['total'] ?? 0);
    $errors = (int)($_POST['errors'] ?? 0);
    
    if ($last_page == 0) $last_page = abs_ifreedom_v2_get_last_catalog_page();
    
    $books = abs_ifreedom_v2_scan_catalog_page($page);
    if (is_array($books) && isset($books['error'])) {
        $errors++;
    } else {
        foreach ($books as $b) {
            $result = abs_ifreedom_v2_queue_book($b);
            if ($result['status'] === 'queued') $total++;
        }
    }
    
    wp_send_json_success([
        'finished' => ($page >= $last_page),
        'page' => $page + 1, 'last_page' => $last_page,
        'total' => $total, 'errors' => $errors,
        'message' => "Страница $page/$last_page, книг: $total",
    ]);
}

add_action('wp_ajax_abs_ifreedom_v2_process', 'abs_ifreedom_v2_process_ajax');
function abs_ifreedom_v2_process_ajax() {
    ini_set('memory_limit', '256M');
    set_time_limit(300);
    check_ajax_referer('abs_ifreedom_v2_nonce');
    if (!current_user_can('manage_options')) wp_send_json_error();
    
    require_once get_template_directory() . '/includes/abs-ifreedom-v2.php';
    
    global $wpdb;
    $table = $wpdb->prefix . 'abs_ifreedom_v2_queue';
    $slugs = isset($_POST['slugs']) ? array_filter((array)$_POST['slugs']) : [];
    $index = (int)($_POST['index'] ?? 0);
    $processed = (int)($_POST['processed'] ?? 0);
    
    if (empty($slugs)) {
        $queue = $wpdb->get_col("SELECT slug FROM $table WHERE status IN('new','error') ORDER BY id ASC");
    } else {
        $queue = $slugs;
    }
    
    $total = count($queue);
    if ($index >= $total) {
        wp_send_json_success(['finished' => true, 'processed' => $processed, 'total' => $total]);
    }
    
    $slug = $queue[$index];
    $start_chapter = (int)($_POST['start_chapter'] ?? 0);
    
    $result = abs_ifreedom_v2_process_book($slug, $start_chapter);
    $book = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE slug = %s", $slug));
    
    $book_title = $wpdb->get_var($wpdb->prepare("SELECT title FROM $table WHERE slug = %s", $slug));
    
    if ($result['status'] === 'ok' && $result['finished']) {
        $processed++;
        $index++;
        if (function_exists('abs_telegram_log')) {
            abs_telegram_log("📥 V2: {$book_title} — {$result['loaded']}/{$result['total']} глав");
        }
    }
    
    $log_msg = $result['finished'] 
        ? "✅ {$book_title} — {$result['loaded']}/{$result['total']}" 
        : "📖 {$book_title} — {$result['next_chapter']}/{$result['total']}";
    
    wp_send_json_success([
        'finished' => ($index >= $total),
        'processed' => $processed, 'total' => $total,
        'next_index' => $index,
        'start_chapter' => $result['finished'] ? 0 : $result['next_chapter'],
        'log' => $log_msg,
    ]);
}

add_action('wp_ajax_abs_ifreedom_v2_clear', 'abs_ifreedom_v2_clear_ajax');
function abs_ifreedom_v2_clear_ajax() {
    check_ajax_referer('abs_ifreedom_v2_nonce');
    if (!current_user_can('manage_options')) wp_send_json_error();
    global $wpdb;
    $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}abs_ifreedom_v2_queue");
    wp_send_json_success();
}

// ========== ГЛАВНАЯ СТРАНИЦА ==========
function abs_ifreedom_v2_admin_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'abs_ifreedom_v2_queue';
    $settings = abs_ifreedom_v2_get_settings();
    
    $filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : 'all';
    $sort_by = isset($_GET['sort_by']) ? sanitize_text_field($_GET['sort_by']) : 'id_asc';
    $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
    
    $where = "WHERE 1=1";
    if ($filter_status !== 'all') $where .= $wpdb->prepare(" AND status = %s", $filter_status);
    if ($search) $where .= $wpdb->prepare(" AND title LIKE %s", '%' . $wpdb->esc_like($search) . '%');
    
    $order_by = "ORDER BY id ASC";
    switch ($sort_by) {
        case 'id_desc': $order_by = "ORDER BY id DESC"; break;
        case 'title_asc': $order_by = "ORDER BY title ASC"; break;
        case 'title_desc': $order_by = "ORDER BY title DESC"; break;
        case 'chapters_asc': $order_by = "ORDER BY chapters_count ASC"; break;
        case 'chapters_desc': $order_by = "ORDER BY chapters_count DESC"; break;
        case 'views_desc': $order_by = "ORDER BY views DESC"; break;
case 'views_asc': $order_by = "ORDER BY views ASC"; break;
    }
    
    $total = $wpdb->get_var("SELECT COUNT(*) FROM $table");
    $new = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status='new'");
    $parsing = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status='parsing'");
    $done = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status='done'");
    $error = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status='error'");
    
    $per_page = 50;
    $paged = isset($_GET['paged']) ? max(1, (int)$_GET['paged']) : 1;
    $offset = ($paged - 1) * $per_page;
    $books = $wpdb->get_results("SELECT * FROM $table $where $order_by LIMIT $offset, $per_page");
    $total_pages = ceil($total / $per_page);
    
    $base_url = admin_url('admin.php?page=abs-ifreedom-v2');
    ?>
    <div class="wrap">
        <h1>📚 Парсер Ifreedom v2</h1>
        
        <!-- Статистика -->
<div class="abs-stats" style="display:flex;gap:20px;margin:20px 0;">
    <div class="stat-box" style="flex:1;text-align:center;padding:20px;background:#fff;border-radius:8px;border:1px solid #ccd0d4;">
        <span style="font-size:2rem;font-weight:700;"><?php echo $total; ?></span><br>Всего
    </div>
    <div class="stat-box" style="flex:1;text-align:center;padding:20px;background:#fff;border-radius:8px;border:1px solid #ccd0d4;">
        <span style="font-size:2rem;font-weight:700;color:#007cba;"><?php echo $new; ?></span><br>Новых
    </div>
    <div class="stat-box" style="flex:1;text-align:center;padding:20px;background:#fff;border-radius:8px;border:1px solid #ccd0d4;">
    <span style="font-size:2rem;font-weight:700;color:#f0a030;"><?php echo $parsing; ?></span><br>В процессе
</div>
    <div class="stat-box" style="flex:1;text-align:center;padding:20px;background:#fff;border-radius:8px;border:1px solid #ccd0d4;">
        <span style="font-size:2rem;font-weight:700;color:#00a32a;"><?php echo $done; ?></span><br>Готово
    </div>
    <div class="stat-box" style="flex:1;text-align:center;padding:20px;background:#fff;border-radius:8px;border:1px solid #ccd0d4;">
        <span style="font-size:2rem;font-weight:700;color:#d63638;"><?php echo $error; ?></span><br>Ошибок
    </div>
</div>
        
        <!-- Настройки -->
        <div class="card" style="margin:20px 0;">
    <div class="card-header" onclick="jQuery('#v2-settings-body').toggle()" style="cursor:pointer;padding:15px;display:flex;justify-content:space-between;align-items:center;">
        <h3 style="margin:0;">⚙️ Настройки парсера</h3>
        <span>▼</span>
    </div>
    <div id="v2-settings-body" style="display:none;padding:15px;">
        <form id="v2-settings-form" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:15px;">
            <div><label>Мин. пауза (мс)</label><br><input type="number" name="min_delay_ms" value="<?php echo $settings['min_delay_ms']; ?>" style="width:100%;"></div>
            <div><label>Макс. пауза (мс)</label><br><input type="number" name="max_delay_ms" value="<?php echo $settings['max_delay_ms']; ?>" style="width:100%;"></div>
            <div><label>Запросов/мин</label><br><input type="number" name="max_per_minute" value="<?php echo $settings['max_per_minute']; ?>" style="width:100%;"></div>
            <div><label>Батч ручной</label><br><input type="number" name="manual_batch_size" value="<?php echo $settings['manual_batch_size']; ?>" style="width:100%;"></div>
            <div><label>Батч Cron</label><br><input type="number" name="cron_batch_size" value="<?php echo $settings['cron_batch_size']; ?>" style="width:100%;"></div>
            <div><label>Таймаут HTTP</label><br><input type="number" name="http_timeout" value="<?php echo $settings['http_timeout']; ?>" style="width:100%;"></div>
            <div style="align-self:end;"><button type="submit" class="button button-primary">💾 Сохранить</button></div>
        </form>
    </div>
</div>
        
        <!-- Кнопки -->
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin:20px 0;">
            <button id="btn-scan" class="button button-primary">🔍 Сканировать каталог</button>
            <button id="btn-process-selected" class="button" disabled>📥 Загрузить выбранные (0)</button>
            <button id="btn-process-all" class="button">📥 Загрузить всё (<?php echo $new + $error; ?>)</button>
            <button id="btn-clear" class="button button-link-delete">🗑 Очистить</button>
        </div>
        
        <div style="display:flex;gap:10px;margin:10px 0;">
            <button id="btn-select-new" class="button button-small">Новые</button>
            <button id="btn-select-error" class="button button-small">С ошибкой</button>
            <button id="btn-select-all" class="button button-small">Все</button>
            <button id="btn-deselect" class="button button-small">Снять</button>
        </div>
        
        <!-- Прогресс -->
        <div id="v2-progress" style="display:none;margin:20px 0;">
            <div style="height:20px;background:#f0f0f0;border-radius:10px;overflow:hidden;">
                <div id="v2-progress-bar" style="height:100%;width:0%;background:#007cba;transition:width 0.3s;"></div>
            </div>
            <div style="text-align:center;margin-top:10px;"><span id="v2-progress-current">0</span> / <span id="v2-progress-total">0</span></div>
        </div>
        
        <!-- Лог -->
        <div id="v2-log" style="display:none;background:#1e1e1e;border-radius:8px;padding:15px;max-height:300px;overflow:auto;font-family:monospace;color:#0f0;margin:20px 0;"></div>
        
        <!-- Фильтры -->
        <div style="display:flex;gap:10px;margin:15px 0;flex-wrap:wrap;align-items:center;">
            <strong>Фильтры:</strong>
            <select id="filter-status" onchange="applyFilters()">
                <option value="all" <?php selected($filter_status, 'all'); ?>>Все</option>
                <option value="new" <?php selected($filter_status, 'new'); ?>>Новые</option>
                <option value="parsing" <?php selected($filter_status, 'parsing'); ?>>В процессе</option>
                <option value="done" <?php selected($filter_status, 'done'); ?>>Готово</option>
                <option value="error" <?php selected($filter_status, 'error'); ?>>Ошибки</option>
            </select>
            <select id="sort-by" onchange="applyFilters()">
                <option value="id_asc" <?php selected($sort_by, 'id_asc'); ?>>ID ↑</option>
                <option value="id_desc" <?php selected($sort_by, 'id_desc'); ?>>ID ↓</option>
                <option value="title_asc" <?php selected($sort_by, 'title_asc'); ?>>Название А-Я</option>
                <option value="title_desc" <?php selected($sort_by, 'title_desc'); ?>>Название Я-А</option>
                <option value="chapters_asc" <?php selected($sort_by, 'chapters_asc'); ?>>Глав ↑</option>
                <option value="chapters_desc" <?php selected($sort_by, 'chapters_desc'); ?>>Глав ↓</option>
                <option value="views_desc" <?php selected($sort_by, 'views_desc'); ?>>Просмотры ↓</option>
<option value="views_asc" <?php selected($sort_by, 'views_asc'); ?>>Просмотры ↑</option>
            </select>
            <input type="text" id="search-input" placeholder="Поиск..." value="<?php echo esc_attr($search); ?>" style="width:200px;" onkeydown="if(event.key==='Enter')applyFilters()">
            <button class="button" onclick="applyFilters()">🔍</button>
            <?php if ($filter_status !== 'all' || $search): ?>
                <a href="<?php echo $base_url; ?>" class="button button-small">✕ Сбросить</a>
            <?php endif; ?>
        </div>
        
        <!-- Таблица -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <td class="check-column"><input type="checkbox" id="select-all-top"></td>
                    <th>Slug</th>
                    <th>Название</th>
<th>Просмотров</th>
<th>Бесплатных глав</th>
<th>Всего глав</th>
<th>Загружено</th>
                    <th>Статус</th>
                    <th>Дата</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($books)): ?>
                    <tr><td colspan="7">Очередь пуста</td></tr>
                <?php else: foreach ($books as $book): 
                    $status_labels = [
                        'new' => ['🐾 Новый', 'color:#007cba'],
                        'parsing' => ['⏳ В процессе', 'color:#f0a030'],
                        'done' => ['✅ Готово', 'color:#00a32a'],
                        'error' => ['❌ Ошибка', 'color:#d63638'],
                    ];
                    $l = $status_labels[$book->status] ?? $status_labels['new'];
                ?>
                    <tr>
                        <td><input type="checkbox" class="book-checkbox" value="<?php echo esc_attr($book->slug); ?>"></td>
                        <td><code><?php echo esc_html($book->slug); ?></code></td>
                        <td><a href="<?php echo esc_url($book->url); ?>" target="_blank"><?php echo esc_html($book->title); ?></a></td>
<td><?php echo number_format($book->views, 0, ',', ' '); ?></td>
<td><?php echo $book->chapters_count; ?></td>
<td><?php echo $book->total_chapters; ?></td>
<td><?php echo $book->parsed_chapters; ?></td>
                        <td><span style="<?php echo $l[1]; ?>"><?php echo $l[0]; ?></span></td>
                        <td><?php echo $book->last_parsed_at ?: '—'; ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
        
        <?php if ($total_pages > 1): ?>
            <div style="text-align:center;margin:20px 0;">
                <?php echo paginate_links(['base'=>add_query_arg('paged','%#%'),'format'=>'','current'=>$paged,'total'=>$total_pages,'prev_text'=>'←','next_text'=>'→']); ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
    jQuery(function($) {
        var isRunning = false;
        
        function log(msg) {
            $('#v2-log').show().append('<div>' + msg + '</div>');
            $('#v2-log').scrollTop($('#v2-log')[0].scrollHeight);
        }
        
        function updateProgress(c, t) {
            $('#v2-progress').show();
            $('#v2-progress-current').text(c);
            $('#v2-progress-total').text(t);
            $('#v2-progress-bar').css('width', (t>0 ? Math.round(c/t*100) : 0) + '%');
        }
        
        function updateSelectedBtn() {
            var c = $('.book-checkbox:checked').length;
            $('#btn-process-selected').prop('disabled', c===0).text('📥 Загрузить выбранные (' + c + ')');
        }
        
        window.applyFilters = function() {
            var params = [];
            var s = $('#filter-status').val(); if (s !== 'all') params.push('filter_status='+s);
            s = $('#sort-by').val(); if (s !== 'id_asc') params.push('sort_by='+s);
            var q = $('#search-input').val().trim(); if (q) params.push('search='+encodeURIComponent(q));
            var url = '<?php echo $base_url; ?>';
            if (params.length) url += '&' + params.join('&');
            window.location.href = url;
        };
        
        $('#select-all-top').on('change', function() { $('.book-checkbox').prop('checked',$(this).prop('checked')); updateSelectedBtn(); });
        $(document).on('change','.book-checkbox', updateSelectedBtn);
        $('#btn-select-new').click(function(){ $('.book-checkbox').prop('checked',false); $('tr').each(function(){ if($(this).find('span').text().includes('Новый')) $(this).find('.book-checkbox').prop('checked',true); }); updateSelectedBtn(); });
        $('#btn-select-error').click(function(){ $('.book-checkbox').prop('checked',false); $('tr').each(function(){ if($(this).find('span').text().includes('Ошибка')) $(this).find('.book-checkbox').prop('checked',true); }); updateSelectedBtn(); });
        $('#btn-select-all').click(function(){ $('.book-checkbox').prop('checked',true); updateSelectedBtn(); });
        $('#btn-deselect').click(function(){ $('.book-checkbox').prop('checked',false); updateSelectedBtn(); });
        
        $('#btn-scan').click(function() {
            if (isRunning) return;
            if (!confirm('Сканировать каталог?')) return;
            isRunning = true;
            log('🔍 Сканирование...');
            scanPage(1, 0, 0, 0);
        });
        
        function scanPage(page, lastPage, total, errors) {
            $.post(ajaxurl, {
                action: 'abs_ifreedom_v2_scan',
                page: page, last_page: lastPage, total: total, errors: errors,
                _ajax_nonce: '<?php echo wp_create_nonce("abs_ifreedom_v2_nonce"); ?>'
            }, function(r) {
                if (r.success) {
                    log(r.data.message);
                    if (r.data.finished) {
                        log('✅ Готово! Книг: ' + r.data.total);
                        isRunning = false;
                        setTimeout(function(){ location.reload(); }, 2000);
                    } else {
                        scanPage(r.data.page, r.data.last_page, r.data.total, r.data.errors);
                    }
                }
            }).fail(function() { log('❌ Ошибка'); isRunning = false; });
        }
        
        $('#btn-process-all').click(function() {
            if (isRunning) return;
            if (!confirm('Загрузить все книги со статусом new/error?')) return;
            isRunning = true;
            log('📥 Загрузка...');
            processBooks([], 0, 0, 0);
        });
        
        $('#btn-process-selected').click(function() {
            if (isRunning) return;
            var slugs = $('.book-checkbox:checked').map(function(){ return this.value; }).get();
            if (!slugs.length) return;
            isRunning = true;
            log('📥 Загрузка выбранных (' + slugs.length + ')...');
            processBooks(slugs, 0, 0, 0);
        });
        
        function processBooks(slugs, index, processed, startChapter) {
    $.post(ajaxurl, {
        action: 'abs_ifreedom_v2_process',
        slugs: slugs,
        index: index,
        processed: processed,
        start_chapter: startChapter || 0,
        _ajax_nonce: '...'
    }, function(r) {
        if (r.success) {
            updateProgress(r.data.processed, r.data.total);
            log(r.data.log);
            if (r.data.finished) {
                log('✅ Готово!');
                isRunning = false;
                setTimeout(function(){ location.reload(); }, 2000);
            } else {
                processBooks(slugs, r.data.next_index, r.data.processed, r.data.start_chapter);
            }
        }
    });
}
        
        $('#btn-clear').click(function() {
            if (!confirm('Удалить всю очередь?')) return;
            $.post(ajaxurl, { action: 'abs_ifreedom_v2_clear', _ajax_nonce: '<?php echo wp_create_nonce("abs_ifreedom_v2_nonce"); ?>' }, function() { location.reload(); });
        });
        
        $('#v2-settings-form').on('submit', function(e) {
            e.preventDefault();
            var data = $(this).serialize() + '&action=abs_ifreedom_v2_save_settings&_ajax_nonce=<?php echo wp_create_nonce("abs_ifreedom_v2_nonce"); ?>';
            $.post(ajaxurl, data, function() { alert('Сохранено!'); });
        });
    });
    </script>
    <?php
}