<?php
/**
 * abs-parser-ifreedom-admin.php — Админ-панель парсера ifreedom.su v2
 * Работает с abs-ifreedom-v2.php
 */

if (!defined('ABSPATH')) exit;

// ========== МЕНЮ ==========
add_action('admin_menu', 'abs_parser_ifreedom_admin_menu');
function abs_parser_ifreedom_admin_menu() {
    add_menu_page(
        'Парсер Ifreedom v2',
        'Ifreedom v2',
        'manage_options',
        'abs-parser-ifreedom',
        'abs_parser_ifreedom_admin_page',
        'dashicons-download',
        33
    );
}

// ========== ГЛАВНАЯ СТРАНИЦА ==========
function abs_parser_ifreedom_admin_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'abs_ifreedom_v2_queue';
    
    $filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : 'all';
    $sort_by = isset($_GET['sort_by']) ? sanitize_text_field($_GET['sort_by']) : 'id_asc';
    $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
    $chapters_filter = isset($_GET['chapters_filter']) ? sanitize_text_field($_GET['chapters_filter']) : 'all';
    
    $where = "WHERE 1=1";
    if ($filter_status === 'new') $where .= " AND status = 'new'";
    elseif ($filter_status === 'parsing') $where .= " AND status = 'parsing'";
    elseif ($filter_status === 'done') $where .= " AND status = 'done'";
    elseif ($filter_status === 'error') $where .= " AND status = 'error'";
    elseif ($filter_status === 'pending') $where .= " AND (status IN('new','error') OR (status='done' AND parsed_chapters<chapters_count))";
    
    if (!empty($search)) $where .= $wpdb->prepare(" AND (title LIKE %s OR slug LIKE %s)", '%' . $wpdb->esc_like($search) . '%', '%' . $wpdb->esc_like($search) . '%');
    
    if ($chapters_filter === 'small') $where .= " AND chapters_count < 100";
    elseif ($chapters_filter === 'medium') $where .= " AND chapters_count >= 100 AND chapters_count <= 500";
    elseif ($chapters_filter === 'large') $where .= " AND chapters_count > 500 AND chapters_count <= 1000";
    elseif ($chapters_filter === 'xlarge') $where .= " AND chapters_count > 1000";
    
    $order_by = "ORDER BY id ASC";
    if ($sort_by === 'id_desc') $order_by = "ORDER BY id DESC";
    elseif ($sort_by === 'title_asc') $order_by = "ORDER BY title ASC";
    elseif ($sort_by === 'title_desc') $order_by = "ORDER BY title DESC";
    elseif ($sort_by === 'chapters_asc') $order_by = "ORDER BY chapters_count ASC";
    elseif ($sort_by === 'chapters_desc') $order_by = "ORDER BY chapters_count DESC";
    elseif ($sort_by === 'parsed_asc') $order_by = "ORDER BY parsed_chapters ASC";
    elseif ($sort_by === 'parsed_desc') $order_by = "ORDER BY parsed_chapters DESC";
    elseif ($sort_by === 'date_asc') $order_by = "ORDER BY last_parsed_at ASC";
    elseif ($sort_by === 'date_desc') $order_by = "ORDER BY last_parsed_at DESC";
    elseif ($sort_by === 'views_desc') $order_by = "ORDER BY views DESC";
    elseif ($sort_by === 'views_asc') $order_by = "ORDER BY views ASC";
    
    $total = $wpdb->get_var("SELECT COUNT(*) FROM $table");
    $new_count = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'new'");
    $done_count = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'done'");
    $error_count = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'error'");
    $parsing_count = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'parsing'");
    
    $per_page = 50;
    $paged = isset($_GET['paged']) ? max(1, (int)$_GET['paged']) : 1;
    $offset = ($paged - 1) * $per_page;
    $total_books = $wpdb->get_var("SELECT COUNT(*) FROM $table $where");
    $total_pages = ceil($total_books / $per_page);
    $books = $wpdb->get_results("SELECT * FROM $table $where $order_by LIMIT $offset, $per_page");
    $settings = abs_ifreedom_v2_get_settings();
    
    $base_url = admin_url('admin.php?page=abs-parser-ifreedom');
    ?>
    <div class="wrap abs-parser-ifreedom-wrap">
        <h1>📚 Парсер Ifreedom v2</h1>
        
        <!-- Статистика -->
        <div class="abs-parser-stats" style="display:flex;gap:20px;margin:20px 0;">
            <div class="stat-box" style="flex:1;text-align:center;padding:20px;background:#fff;border-radius:8px;border:1px solid #ccd0d4;">
                <span style="font-size:2rem;font-weight:700;"><?php echo $total; ?></span><br>Всего
            </div>
            <div class="stat-box" style="flex:1;text-align:center;padding:20px;background:#fff;border-radius:8px;border:1px solid #ccd0d4;">
                <span style="font-size:2rem;font-weight:700;color:#007cba;"><?php echo $new_count; ?></span><br>Новых
            </div>
            <div class="stat-box" style="flex:1;text-align:center;padding:20px;background:#fff;border-radius:8px;border:1px solid #ccd0d4;">
                <span style="font-size:2rem;font-weight:700;color:#f0a030;"><?php echo $parsing_count; ?></span><br>В процессе
            </div>
            <div class="stat-box" style="flex:1;text-align:center;padding:20px;background:#fff;border-radius:8px;border:1px solid #ccd0d4;">
                <span style="font-size:2rem;font-weight:700;color:#00a32a;"><?php echo $done_count; ?></span><br>Готово
            </div>
            <div class="stat-box" style="flex:1;text-align:center;padding:20px;background:#fff;border-radius:8px;border:1px solid #ccd0d4;">
                <span style="font-size:2rem;font-weight:700;color:#d63638;"><?php echo $error_count; ?></span><br>Ошибок
            </div>
        </div>
        
        <!-- Настройки -->
        <div class="card" style="margin:20px 0;">
            <div class="card-header" onclick="jQuery('#v2-settings-body').toggle()" style="cursor:pointer;padding:15px;display:flex;justify-content:space-between;align-items:center;background:#fff;border-bottom:1px solid #ccd0d4;">
                <h3 style="margin:0;">⚙️ Настройки парсера</h3><span>▼</span>
            </div>
            <div id="v2-settings-body" style="display:none;padding:15px;">
                <form id="parser-settings-form" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:15px;">
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
            <button id="btn-scan-catalog" class="button button-primary">🔍 Сканировать каталог</button>
            <button id="btn-parse-selected" class="button" disabled>📥 Загрузить выбранные (0)</button>
            <button id="btn-parse-all-filtered" class="button">📥 Загрузить всё (фильтр)</button>
            <button id="btn-clear-queue" class="button button-link-delete">🗑 Очистить очередь</button>
        </div>
        
        <div style="display:flex;gap:10px;margin:10px 0;">
            <button id="btn-select-new" class="button button-small">Новые</button>
            <button id="btn-select-error" class="button button-small">С ошибкой</button>
            <button id="btn-select-all" class="button button-small">Все</button>
            <button id="btn-deselect" class="button button-small">Снять</button>
        </div>
        
        <!-- Прогресс -->
        <div id="abs-parser-progress" style="display:none;margin:20px 0;background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;">
            <div style="height:24px;background:#f0f0f0;border-radius:12px;overflow:hidden;">
                <div id="progress-bar-inner" style="height:100%;width:0%;background:linear-gradient(90deg,#007cba,#00a32a);transition:width 0.3s;"></div>
            </div>
            <div style="text-align:center;margin-top:10px;"><span id="progress-current">0</span> / <span id="progress-total">0</span> — <span id="progress-status">Ожидание...</span></div>
        </div>
        
        <!-- Лог -->
        <div id="abs-parser-log" style="display:none;background:#1e1e1e;border-radius:8px;padding:15px;max-height:300px;overflow:auto;font-family:monospace;color:#0f0;margin:20px 0;">
            <div id="log-content"></div>
        </div>
        
        <!-- Фильтры -->
        <div style="display:flex;gap:10px;margin:15px 0;flex-wrap:wrap;align-items:center;">
            <strong>Фильтры:</strong>
            <select id="filter-status" onchange="applyFilters()">
                <option value="all" <?php selected($filter_status, 'all'); ?>>Все</option>
                <option value="new" <?php selected($filter_status, 'new'); ?>>Новые</option>
                <option value="parsing" <?php selected($filter_status, 'parsing'); ?>>В процессе</option>
                <option value="done" <?php selected($filter_status, 'done'); ?>>Готово</option>
                <option value="error" <?php selected($filter_status, 'error'); ?>>Ошибки</option>
                <option value="pending" <?php selected($filter_status, 'pending'); ?>>Ожидают</option>
            </select>
            <select id="chapters-filter" onchange="applyFilters()">
                <option value="all" <?php selected($chapters_filter, 'all'); ?>>Любое кол-во глав</option>
                <option value="small" <?php selected($chapters_filter, 'small'); ?>>Меньше 100</option>
                <option value="medium" <?php selected($chapters_filter, 'medium'); ?>>100-500</option>
                <option value="large" <?php selected($chapters_filter, 'large'); ?>>500-1000</option>
                <option value="xlarge" <?php selected($chapters_filter, 'xlarge'); ?>>Больше 1000</option>
            </select>
            <select id="sort-by" onchange="applyFilters()">
                <option value="id_asc" <?php selected($sort_by, 'id_asc'); ?>>ID ↑</option>
                <option value="id_desc" <?php selected($sort_by, 'id_desc'); ?>>ID ↓</option>
                <option value="title_asc" <?php selected($sort_by, 'title_asc'); ?>>Название А-Я</option>
                <option value="title_desc" <?php selected($sort_by, 'title_desc'); ?>>Название Я-А</option>
                <option value="chapters_asc" <?php selected($sort_by, 'chapters_asc'); ?>>Глав ↑</option>
                <option value="chapters_desc" <?php selected($sort_by, 'chapters_desc'); ?>>Глав ↓</option>
                <option value="parsed_asc" <?php selected($sort_by, 'parsed_asc'); ?>>Загружено ↑</option>
                <option value="parsed_desc" <?php selected($sort_by, 'parsed_desc'); ?>>Загружено ↓</option>
                <option value="date_desc" <?php selected($sort_by, 'date_desc'); ?>>Дата ↓</option>
                <option value="date_asc" <?php selected($sort_by, 'date_asc'); ?>>Дата ↑</option>
                <option value="views_desc" <?php selected($sort_by, 'views_desc'); ?>>Просмотры ↓</option>
                <option value="views_asc" <?php selected($sort_by, 'views_asc'); ?>>Просмотры ↑</option>
            </select>
            <input type="text" id="search-input" placeholder="Поиск..." value="<?php echo esc_attr($search); ?>" style="width:200px;" onkeydown="if(event.key==='Enter')applyFilters()">
            <button class="button" onclick="applyFilters()">🔍</button>
            <?php if ($filter_status !== 'all' || !empty($search) || $chapters_filter !== 'all' || $sort_by !== 'id_asc'): ?>
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
                    <th>Глав (беспл.)</th>
                    <th>Всего глав</th>
                    <th>Просмотры</th>
                    <th>Загружено</th>
                    <th>Статус</th>
                    <th>Дата</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($books)): ?>
                    <tr><td colspan="9">Очередь пуста</td></tr>
                <?php else: foreach ($books as $book): 
                    $status_labels = [
                        'new' => ['🐾 Новый', 'color:#007cba'],
                        'parsing' => ['⏳ В процессе', 'color:#f0a030'],
                        'done' => ['✅ Готово', 'color:#00a32a'],
                        'error' => ['❌ Ошибка', 'color:#d63638'],
                    ];
                    $l = $status_labels[$book->status] ?? $status_labels['new'];
                    $progress = ($book->chapters_count > 0) ? round($book->parsed_chapters / $book->chapters_count * 100) : 0;
                ?>
                    <tr class="queue-row" data-status="<?php echo $book->status; ?>">
                        <td><input type="checkbox" class="book-checkbox" value="<?php echo esc_attr($book->slug); ?>"></td>
                        <td><code><?php echo esc_html($book->slug); ?></code></td>
                        <td><a href="<?php echo esc_url($book->url); ?>" target="_blank"><?php echo esc_html($book->title); ?></a></td>
                        <td><?php echo $book->chapters_count; ?></td>
                        <td><?php echo $book->total_chapters; ?></td>
                        <td><?php echo number_format($book->views, 0, ',', ' '); ?></td>
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
        
        function log(msg, type) {
            type = type || 'info';
            var t = new Date().toLocaleTimeString();
            $('#log-content').append('<div class="log-'+type+'">['+t+'] '+msg+'</div>');
            $('#abs-parser-log').show();
        }
        
        function updateProgress(c, t, s) {
            $('#abs-parser-progress').show();
            $('#progress-current').text(c);
            $('#progress-total').text(t);
            $('#progress-status').text(s);
            $('#progress-bar-inner').css('width', (t>0?Math.round(c/t*100):0)+'%');
        }
        
        function updateSelectedBtn() {
            var c = $('.book-checkbox:checked').length;
            $('#btn-parse-selected').prop('disabled', c===0).text('📥 Загрузить выбранные ('+c+')');
        }
        
        window.applyFilters = function() {
            var params = [];
            var s = $('#filter-status').val(); if (s !== 'all') params.push('filter_status='+s);
            s = $('#chapters-filter').val(); if (s !== 'all') params.push('chapters_filter='+s);
            s = $('#sort-by').val(); if (s !== 'id_asc') params.push('sort_by='+s);
            var q = $('#search-input').val().trim(); if (q) params.push('search='+encodeURIComponent(q));
            var url = '<?php echo $base_url; ?>';
            if (params.length) url += '&' + params.join('&');
            window.location.href = url;
        };
        
        $('#select-all-top').on('change', function() { $('.book-checkbox').prop('checked',$(this).prop('checked')); updateSelectedBtn(); });
        $(document).on('change','.book-checkbox', updateSelectedBtn);
        $('#btn-select-new').click(function(){ $('.book-checkbox').prop('checked',false); $('.queue-row[data-status="new"] .book-checkbox').prop('checked',true); updateSelectedBtn(); });
        $('#btn-select-error').click(function(){ $('.book-checkbox').prop('checked',false); $('.queue-row[data-status="error"] .book-checkbox').prop('checked',true); updateSelectedBtn(); });
        $('#btn-select-all').click(function(){ $('.book-checkbox').prop('checked',true); updateSelectedBtn(); });
        $('#btn-deselect').click(function(){ $('.book-checkbox').prop('checked',false); updateSelectedBtn(); });
        
        $('#btn-scan-catalog').click(function() {
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
                    if (r.data.finished) { log('✅ Готово!'); isRunning = false; setTimeout(function(){ location.reload(); }, 2000); }
                    else scanPage(r.data.page, r.data.last_page, r.data.total, r.data.errors);
                }
            }).fail(function() { log('❌ Ошибка'); isRunning = false; });
        }
        
        $('#btn-parse-all-filtered').click(function() {
            if (isRunning) return;
            var slugs = $('.queue-row .book-checkbox').map(function(){ return this.value; }).get();
            if (!slugs.length) { alert('Нет книг.'); return; }
            if (!confirm('Загрузить ' + slugs.length + ' книг?')) return;
            startParsingBooks(slugs);
        });
        
        $('#btn-parse-selected').click(function() {
            if (isRunning) return;
            var slugs = $('.book-checkbox:checked').map(function(){ return this.value; }).get();
            if (!slugs.length) return;
            startParsingBooks(slugs);
        });
        
        function startParsingBooks(slugs) {
            isRunning = true;
            log('📥 Загрузка ' + slugs.length + ' книг...');
            processNextBook(slugs, 0, 0, 0);
        }
        
        function processNextBook(slugs, index, processed, startChapter) {
            $.post(ajaxurl, {
                action: 'abs_ifreedom_v2_process',
                slugs: slugs,
                index: index,
                processed: processed,
                start_chapter: startChapter || 0,
                _ajax_nonce: '<?php echo wp_create_nonce("abs_ifreedom_v2_nonce"); ?>'
            }, function(r) {
                if (r.success) {
                    var d = r.data;
                    updateProgress(d.processed, d.total, d.current_book || '...');
                    if (d.log) log(d.log, d.log_type || 'info');
                    if (d.finished) { log('✅ Готово!'); isRunning = false; setTimeout(function(){ location.reload(); }, 2000); }
                    else processNextBook(slugs, d.next_index, d.processed, d.start_chapter || 0);
                }
            }).fail(function() { log('❌ Ошибка AJAX'); isRunning = false; });
        }
        
        $('#btn-clear-queue').click(function() {
            if (!confirm('Удалить всю очередь?')) return;
            $.post(ajaxurl, { action: 'abs_ifreedom_v2_clear', _ajax_nonce: '<?php echo wp_create_nonce("abs_ifreedom_v2_nonce"); ?>' }, function() { location.reload(); });
        });
        
        $('#parser-settings-form').on('submit', function(e) {
            e.preventDefault();
            var data = $(this).serialize() + '&action=abs_ifreedom_v2_save_settings&_ajax_nonce=<?php echo wp_create_nonce("abs_ifreedom_v2_nonce"); ?>';
            $.post(ajaxurl, data, function() { alert('Сохранено!'); });
        });
    });
    </script>
    <?php
}