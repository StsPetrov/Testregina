<?php
/**
 * abs-parser-ifreedom-admin.php — Админ-панель парсера ifreedom.su
 */

if (!defined('ABSPATH')) exit;

// ========== МЕНЮ ==========
add_action('admin_menu', 'abs_parser_ifreedom_admin_menu');
function abs_parser_ifreedom_admin_menu() {
    add_menu_page(
        'Парсер Ifreedom',
        'Парсер Ifreedom',
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
    $table = $wpdb->prefix . 'abs_parser_ifreedom_queue';
    
    // Параметры фильтров
    $filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : 'all';
    $sort_by = isset($_GET['sort_by']) ? sanitize_text_field($_GET['sort_by']) : 'id_asc';
    $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
    $chapters_filter = isset($_GET['chapters_filter']) ? sanitize_text_field($_GET['chapters_filter']) : 'all';
    
    // Строим WHERE
    $where = "WHERE 1=1";
    if ($filter_status === 'new') {
        $where .= " AND status = 'new'";
    } elseif ($filter_status === 'parsing') {
        $where .= " AND status = 'parsing'";
    } elseif ($filter_status === 'done') {
        $where .= " AND status = 'done'";
    } elseif ($filter_status === 'error') {
        $where .= " AND status = 'error'";
    } elseif ($filter_status === 'pending') {
        // Новые + ошибки + недокачанные
        $where .= " AND (status IN('new','error') OR (status='done' AND parsed_chapters<chapters_count))";
    }
    
    if (!empty($search)) {
        $where .= $wpdb->prepare(" AND (title LIKE %s OR slug LIKE %s)", '%' . $wpdb->esc_like($search) . '%', '%' . $wpdb->esc_like($search) . '%');
    }
    
    // Фильтр по количеству глав
    if ($chapters_filter === 'small') {
        $where .= " AND chapters_count < 100";
    } elseif ($chapters_filter === 'medium') {
        $where .= " AND chapters_count >= 100 AND chapters_count <= 500";
    } elseif ($chapters_filter === 'large') {
        $where .= " AND chapters_count > 500 AND chapters_count <= 1000";
    } elseif ($chapters_filter === 'xlarge') {
        $where .= " AND chapters_count > 1000";
    }
    
    // Сортировка
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
    $pending_count = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status IN('new','error') OR (status='done' AND parsed_chapters<chapters_count)");
    
    $per_page = 50;
$paged = isset($_GET['paged']) ? max(1, (int)$_GET['paged']) : 1;
$offset = ($paged - 1) * $per_page;
$total_books = $wpdb->get_var("SELECT COUNT(*) FROM $table $where");
$total_pages = ceil($total_books / $per_page);
$books = $wpdb->get_results("SELECT * FROM $table $where $order_by LIMIT $offset, $per_page");
    $settings = abs_parser_ifreedom_get_settings();
    
    // Базовый URL для фильтров
    $base_url = admin_url('admin.php?page=abs-parser-ifreedom');
    ?>
    <div class="wrap abs-parser-ifreedom-wrap">
        <h1>📚 Парсер Ifreedom.su</h1>
        
        <!-- Статистика -->
        <div class="abs-parser-stats">
            <div class="stat-box"><span class="stat-number"><?php echo $total; ?></span><span class="stat-label">Всего</span></div>
            <div class="stat-box stat-new"><span class="stat-number"><?php echo $new_count; ?></span><span class="stat-label">Новых</span></div>
            <div class="stat-box stat-done"><span class="stat-number"><?php echo $done_count; ?></span><span class="stat-label">Загружено</span></div>
            <div class="stat-box stat-error"><span class="stat-number"><?php echo $error_count; ?></span><span class="stat-label">С ошибкой</span></div>
        </div>
        
        <!-- Настройки парсера -->
        <div class="abs-parser-card">
            <div class="card-header" onclick="jQuery(this).next().toggle()">
                <h3>⚙️ Настройки парсера</h3>
                <span class="toggle-icon">▼</span>
            </div>
            <div class="card-body">
                <form id="parser-settings-form" class="settings-grid">
                    <div class="setting-row">
                        <label>Мин. пауза (мс)</label>
                        <input type="number" name="min_delay_ms" value="<?php echo $settings['min_delay_ms']; ?>" step="100000">
                    </div>
                    <div class="setting-row">
                        <label>Макс. пауза (мс)</label>
                        <input type="number" name="max_delay_ms" value="<?php echo $settings['max_delay_ms']; ?>" step="100000">
                    </div>
                    <div class="setting-row">
                        <label>Макс. запросов/мин</label>
                        <input type="number" name="max_per_minute" value="<?php echo $settings['max_per_minute']; ?>">
                    </div>
                    <div class="setting-row">
                        <label>Батч Cron (глав)</label>
                        <input type="number" name="cron_batch_size" value="<?php echo $settings['cron_batch_size']; ?>">
                    </div>
                    <div class="setting-row">
                        <label>Батч ручной (глав)</label>
                        <input type="number" name="manual_batch_size" value="<?php echo $settings['manual_batch_size']; ?>">
                    </div>
                    <div class="setting-row">
                        <label>Таймаут HTTP (сек)</label>
                        <input type="number" name="http_timeout" value="<?php echo $settings['http_timeout']; ?>">
                    </div>
                    <div class="setting-row" style="align-self:flex-end;">
                        <button type="submit" class="button button-primary">💾 Сохранить</button>
                        <span id="settings-message" style="margin-left:10px;"></span>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Кнопки управления -->
        <div class="abs-parser-actions">
            <button id="btn-scan-catalog" class="button button-primary">🔍 Сканировать каталог</button>
            <button id="btn-parse-selected" class="button" disabled>📥 Загрузить выбранные (0)</button>
            <button id="btn-parse-all-filtered" class="button">📥 Загрузить всё (фильтр)</button>
            <button id="btn-clear-queue" class="button button-link-delete">🗑 Очистить очередь</button>
        </div>
        
        <!-- Быстрые действия -->
        <div class="abs-parser-actions" style="margin-top:5px;">
            <button id="btn-select-new" class="button button-small">☐ Выбрать все новые</button>
            <button id="btn-select-error" class="button button-small">☐ Выбрать все с ошибкой</button>
            <button id="btn-select-all" class="button button-small">☑ Выбрать все</button>
            <button id="btn-deselect-all" class="button button-small">☐ Снять выбор</button>
        </div>
        
        <!-- Cron -->
        <div class="abs-parser-actions" style="margin-top:10px;">
            <input type="text" id="cron-book-ids" placeholder="Slug через запятую (пусто = все)" style="width:250px;">
            <button id="btn-cron-start" class="button button-primary">🕒 Фон: СТАРТ</button>
            <button id="btn-cron-stop" class="button">⏹ Фон: СТОП</button>
            <span id="cron-status" style="line-height:30px;margin-left:10px;">⚪ Остановлен</span>
        </div>
        
        <!-- Лог Cron -->
        <div id="abs-cron-status" class="abs-parser-log" style="display:none;">
            <div class="log-header"><strong>🕒 Фоновая загрузка</strong><button id="btn-refresh-cron" class="button button-small">Обновить</button></div>
            <div id="cron-log-content" class="log-content"></div>
        </div>
        
        <!-- Прогресс -->
        <div id="abs-parser-progress" class="abs-parser-progress" style="display:none;">
            <div class="progress-bar-outer"><div class="progress-bar-inner" style="width:0%"></div></div>
            <div class="progress-text"><span id="progress-current">0</span> / <span id="progress-total">0</span> — <span id="progress-status">Ожидание...</span></div>
        </div>
        
        <!-- Лог операций -->
        <div id="abs-parser-log" class="abs-parser-log" style="display:none;">
            <div class="log-header"><strong>📋 Лог операций</strong><button id="btn-clear-log" class="button button-small">Очистить</button></div>
            <div id="log-content" class="log-content"></div>
        </div>
        
        <!-- Фильтры таблицы -->
        <div class="abs-parser-filters" style="margin:15px 0;display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
            <strong>Фильтры:</strong>
            <select id="filter-status" onchange="applyFilters()">
                <option value="all" <?php selected($filter_status, 'all'); ?>>Все статусы</option>
                <option value="new" <?php selected($filter_status, 'new'); ?>>Новые</option>
                <option value="parsing" <?php selected($filter_status, 'parsing'); ?>>В процессе</option>
                <option value="done" <?php selected($filter_status, 'done'); ?>>Загружено</option>
                <option value="error" <?php selected($filter_status, 'error'); ?>>С ошибкой</option>
                <option value="pending" <?php selected($filter_status, 'pending'); ?>>Ожидают (новые+ошибки+недокачка)</option>
            </select>
            
            <select id="chapters-filter" onchange="applyFilters()">
                <option value="all" <?php selected($chapters_filter, 'all'); ?>>Любое кол-во глав</option>
                <option value="small" <?php selected($chapters_filter, 'small'); ?>>Меньше 100</option>
                <option value="medium" <?php selected($chapters_filter, 'medium'); ?>>100-500</option>
                <option value="large" <?php selected($chapters_filter, 'large'); ?>>500-1000</option>
                <option value="xlarge" <?php selected($chapters_filter, 'xlarge'); ?>>Больше 1000</option>
            </select>
            
            <select id="sort-by" onchange="applyFilters()">
                <option value="id_asc" <?php selected($sort_by, 'id_asc'); ?>>По ID (возр.)</option>
                <option value="id_desc" <?php selected($sort_by, 'id_desc'); ?>>По ID (убыв.)</option>
                <option value="title_asc" <?php selected($sort_by, 'title_asc'); ?>>По названию (А-Я)</option>
                <option value="title_desc" <?php selected($sort_by, 'title_desc'); ?>>По названию (Я-А)</option>
                <option value="chapters_asc" <?php selected($sort_by, 'chapters_asc'); ?>>По главам (меньше → больше)</option>
                <option value="chapters_desc" <?php selected($sort_by, 'chapters_desc'); ?>>По главам (больше → меньше)</option>
                <option value="parsed_asc" <?php selected($sort_by, 'parsed_asc'); ?>>По загруженным (меньше → больше)</option>
                <option value="parsed_desc" <?php selected($sort_by, 'parsed_desc'); ?>>По загруженным (больше → меньше)</option>
                <option value="date_desc" <?php selected($sort_by, 'date_desc'); ?>>По дате (новые)</option>
                <option value="date_asc" <?php selected($sort_by, 'date_asc'); ?>>По дате (старые)</option>
                <option value="views_desc" <?php selected($sort_by, 'views_desc'); ?>>По просмотрам (больше → меньше)</option>
<option value="views_asc" <?php selected($sort_by, 'views_asc'); ?>>По просмотрам (меньше → больше)</option>
            </select>
            
            <input type="text" id="search-input" placeholder="Поиск по названию..." value="<?php echo esc_attr($search); ?>" style="width:200px;" onkeydown="if(event.key==='Enter')applyFilters()">
            <button class="button" onclick="applyFilters()">🔍 Применить</button>
            <?php if ($filter_status !== 'all' || !empty($search) || $chapters_filter !== 'all' || $sort_by !== 'id_asc'): ?>
                <a href="<?php echo $base_url; ?>" class="button button-small">✕ Сбросить</a>
            <?php endif; ?>
        </div>
        
        <!-- Таблица очереди -->
        <div class="abs-parser-table-wrap">
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
                        <th>Последняя попытка</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($books)): ?>
                        <tr><td colspan="8">Очередь пуста. Нажмите «Сканировать каталог».</td></tr>
                    <?php else: foreach($books as $book): 
                        $status_labels = [
                            'new'     => ['🐾 Новый', 'status-new'],
                            'parsing' => ['⏳ В процессе', 'status-parsing'],
                            'done'    => ['✅ Загружен', 'status-done'],
                            'error'   => ['❌ Ошибка', 'status-error'],
                        ];
                        $l = $status_labels[$book->status] ?? $status_labels['new'];
                        $progress = ($book->chapters_count > 0) ? round($book->parsed_chapters / $book->chapters_count * 100) : 0;
                    ?>
                    <tr class="queue-row" data-status="<?php echo $book->status; ?>">
                        <td class="check-column"><input type="checkbox" class="book-checkbox" value="<?php echo esc_attr($book->slug); ?>"></td>
                        <td><code><?php echo esc_html($book->slug); ?></code></td>
                        <td>
                            <a href="<?php echo esc_url($book->url); ?>" target="_blank"><?php echo esc_html($book->title); ?></a>
                            <?php if ($book->error_msg): ?>
                                <br><small style="color:#d63638;"><?php echo esc_html($book->error_msg); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $book->chapters_count; ?></td>
                        <td><?php echo $book->total_chapters; ?></td>
                        <td><?php echo number_format($book->views, 0, ',', ' '); ?></td>
                        <td>
                            <?php echo $book->parsed_chapters; ?>
                            <?php if ($book->chapters_count > 0): ?>
                                <div class="mini-progress" style="height:3px;background:#eee;margin-top:3px;border-radius:2px;">
                                    <div style="height:100%;width:<?php echo $progress; ?>%;background:#00a32a;border-radius:2px;"></div>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><span class="status-badge <?php echo $l[1]; ?>"><?php echo $l[0]; ?></span></td>
                        <td><?php echo $book->last_parsed_at ?: '—'; ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
            <?php if ($total_pages > 1): ?>
<div style="margin:20px 0;text-align:center;">
    <?php echo paginate_links(array(
        'base' => add_query_arg('paged', '%#%'),
        'format' => '',
        'current' => $paged,
        'total' => $total_pages,
        'prev_text' => '←',
        'next_text' => '→'
    )); ?>
</div>
<?php endif; ?>
        </div>
    </div>
    
    <style>
        .abs-parser-ifreedom-wrap{margin:20px 0;}.abs-parser-stats{display:flex;gap:20px;margin:20px 0;}.stat-box{background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;text-align:center;min-width:120px;}.stat-number{font-size:32px;font-weight:bold;display:block;}.stat-label{color:#666;font-size:13px;}.stat-new .stat-number{color:#007cba;}.stat-done .stat-number{color:#00a32a;}.stat-error .stat-number{color:#d63638;}.abs-parser-card{background:#fff;border:1px solid #ddd;border-radius:8px;margin:20px 0;}.card-header{cursor:pointer;padding:15px 20px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #eee;}.card-header h3{margin:0;}.card-body{padding:20px;}.settings-grid{display:grid;grid-template-columns:repeat(auto-fill, minmax(200px, 1fr));gap:15px;}.setting-row{display:flex;flex-direction:column;}.setting-row label{font-weight:600;margin-bottom:5px;}.setting-row input{width:100%;}.abs-parser-actions{display:flex;gap:10px;flex-wrap:wrap;}.abs-parser-progress{margin:20px 0;background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;}.progress-bar-outer{height:24px;background:#f0f0f0;border-radius:12px;overflow:hidden;}.progress-bar-inner{height:100%;background:linear-gradient(90deg,#007cba,#00a32a);transition:width 0.3s;}.progress-text{text-align:center;margin-top:10px;font-size:14px;color:#333;}.abs-parser-log{margin:20px 0;background:#1e1e1e;border-radius:8px;padding:15px;}.log-header{display:flex;justify-content:space-between;align-items:center;color:#fff;margin-bottom:10px;}.log-content{max-height:300px;overflow-y:auto;font-family:monospace;font-size:12px;color:#0f0;line-height:1.6;}.status-badge{display:inline-block;padding:3px 10px;border-radius:12px;font-size:12px;}.status-new{background:#e7f3ff;color:#007cba;}.status-parsing{background:#fff3cd;color:#856404;}.status-done{background:#d4edda;color:#155724;}.status-error{background:#f8d7da;color:#721c24;}
    </style>
    
    <script>
    (function($){
        var isRunning=false, logLines=[];
        
        function log(msg, type) {
            type = type || 'info';
            var t = new Date().toLocaleTimeString();
            logLines.push('<div class="log-'+type+'">['+t+'] '+msg+'</div>');
            $('#log-content').html(logLines.join(''));
            $('#abs-parser-log').show();
        }
        
        function updateProgress(c, t, s) {
            $('#abs-parser-progress').show();
            $('#progress-current').text(c);
            $('#progress-total').text(t);
            $('#progress-status').text(s);
            $('.progress-bar-inner').css('width', (t>0?Math.round(c/t*100):0)+'%');
        }
        
        function setButtons(enabled) {
            isRunning = !enabled;
            $('#btn-scan-catalog,#btn-parse-selected,#btn-parse-all-filtered').prop('disabled', !enabled);
        }
        
        function updateParseSelectedBtn() {
            var c = $('.book-checkbox:checked').length;
            $('#btn-parse-selected').prop('disabled', c===0).text('📥 Загрузить выбранные ('+c+')');
        }
        
        // Сохранение настроек
        $('#parser-settings-form').on('submit', function(e) {
            e.preventDefault();
            var data = $(this).serialize();
            data += '&action=abs_parser_ifreedom_save_settings&_ajax_nonce=<?php echo wp_create_nonce("abs_parser_ifreedom_nonce"); ?>';
            $.post(ajaxurl, data, function(r) {
                if (r.success) {
                    $('#settings-message').html('<span style="color:green;">✅ Сохранено!</span>');
                } else {
                    $('#settings-message').html('<span style="color:red;">❌ Ошибка</span>');
                }
            });
        });
        
        // Фильтры
        window.applyFilters = function() {
            var params = [];
            var status = $('#filter-status').val();
            if (status !== 'all') params.push('filter_status=' + status);
            var chapters = $('#chapters-filter').val();
            if (chapters !== 'all') params.push('chapters_filter=' + chapters);
            var sort = $('#sort-by').val();
            if (sort !== 'id_asc') params.push('sort_by=' + sort);
            var search = $('#search-input').val().trim();
            if (search) params.push('search=' + encodeURIComponent(search));
            var url = '<?php echo $base_url; ?>';
            if (params.length) url += '&' + params.join('&');
            window.location.href = url;
        };
        
        // Выбор всех
        $('#select-all-top').on('change', function() {
            $('.book-checkbox').prop('checked', $(this).prop('checked'));
            updateParseSelectedBtn();
        });
        $(document).on('change', '.book-checkbox', updateParseSelectedBtn);
        
        // Быстрые выборы
        $('#btn-select-new').click(function() {
            $('.book-checkbox').each(function() {
                $(this).prop('checked', $(this).closest('tr').data('status') === 'new');
            });
            updateParseSelectedBtn();
        });
        $('#btn-select-error').click(function() {
            $('.book-checkbox').each(function() {
                $(this).prop('checked', $(this).closest('tr').data('status') === 'error');
            });
            updateParseSelectedBtn();
        });
        $('#btn-select-all').click(function() {
            $('.book-checkbox').prop('checked', true);
            updateParseSelectedBtn();
        });
        $('#btn-deselect-all').click(function() {
            $('.book-checkbox').prop('checked', false);
            updateParseSelectedBtn();
        });
        
        // Сканировать каталог
        $('#btn-scan-catalog').on('click', function() {
    if (isRunning) return;
    if (!confirm('Начать сканирование каталога ifreedom.su?')) return;
    setButtons(false);
    log('🔍 Начинаем сканирование...', 'info');
    scanPage(1, 0, 0, 0);
});

function scanPage(page, lastPage, total, errors) {
    $.post(ajaxurl, {
        action: 'abs_parser_ifreedom_scan_catalog_ajax',
        page: page,
        last_page: lastPage,
        total: total,
        errors: errors,
        _ajax_nonce: '<?php echo wp_create_nonce("abs_parser_ifreedom_nonce"); ?>'
    }, function(r) {
        if (r.success) {
            var d = r.data;
            log('📄 ' + d.message, 'info');
            if (d.finished) {
                log('✅ Готово! Книг: ' + d.total + ', ошибок: ' + d.errors, 'success');
                setButtons(true);
                setTimeout(function() { location.reload(); }, 2000);
            } else {
                scanPage(d.page, d.last_page, d.total, d.errors);
            }
        }
    }).fail(function(xhr, status, error) {
        log('❌ Ошибка на стр. ' + page, 'error');
        if (page < lastPage || lastPage === 0) {
            scanPage(page + 1, lastPage, total, errors + 1);
        } else {
            setButtons(true);
        }
    });
}
        
        // Загрузить всё (с учётом фильтра)
        $('#btn-parse-all-filtered').on('click', function() {
            if (isRunning) return;
            // Собираем slugs из видимых строк
            var slugs = [];
            $('.queue-row').each(function() {
                var cb = $(this).find('.book-checkbox');
                if (cb.length) slugs.push(cb.val());
            });
            if (!slugs.length) {
                alert('Нет книг для загрузки.');
                return;
            }
            if (!confirm('Загрузить ' + slugs.length + ' книг из очереди (с учётом фильтра)?')) return;
            startParsingBooks(slugs);
        });
        
        // Загрузить выбранные
        $('#btn-parse-selected').on('click', function() {
            if (isRunning) return;
            var slugs = [];
            $('.book-checkbox:checked').each(function() { slugs.push($(this).val()); });
            if (!slugs.length) return;
            startParsingBooks(slugs);
        });
        
        function startParsingBooks(slugs) {
            setButtons(false);
            log('Начинаем загрузку ' + slugs.length + ' книг...', 'info');
            processNextBook(slugs, 0, 0);
        }
        
        function processNextBook(slugs, index, processed, startChapter) {
            $.post(ajaxurl, {
                action: 'abs_parser_ifreedom_parse_book_ajax',
                book_slugs: slugs,
                current_index: index,
                processed_count: processed,
                start_chapter: startChapter || 0,
                _ajax_nonce: '<?php echo wp_create_nonce("abs_parser_ifreedom_nonce"); ?>'
            }, function(r) {
                if (r.success) {
                    var d = r.data;
                    updateProgress(d.processed, d.total, d.current_book || '...');
                    if (d.log) log(d.log, d.log_type || 'info');
                    if (d.finished) {
                        log('✅ Завершено! Обработано книг: ' + d.processed, 'success');
                        setButtons(true);
                        setTimeout(function() { location.reload(); }, 2000);
                    } else {
                        processNextBook(slugs, d.next_index, d.processed, d.start_chapter || 0);
                    }
                }
            }).fail(function() {
                log('❌ Ошибка AJAX', 'error');
                setButtons(true);
            });
        }
        
        // Очистить очередь
        $('#btn-clear-queue').on('click', function() {
            if (!confirm('Удалить ВСЕ книги из очереди?')) return;
            $.post(ajaxurl, {
                action: 'abs_parser_ifreedom_clear_queue_ajax',
                _ajax_nonce: '<?php echo wp_create_nonce("abs_parser_ifreedom_nonce"); ?>'
            }, function() { location.reload(); });
        });
        
        // Очистить лог
        $('#btn-clear-log').on('click', function() { logLines=[]; $('#log-content').html(''); });
        
        // ===== CRON =====
        function updateCronStatus() {
            $.post(ajaxurl, {
                action: 'abs_parser_ifreedom_cron_toggle',
                cron_action: 'status',
                _ajax_nonce: '<?php echo wp_create_nonce("abs_parser_ifreedom_nonce"); ?>'
            }, function(r) {
                if (r.success && r.data) {
                    var d = r.data;
                    $('#cron-status').text(d.running ? '🟢 Работает' : '⚪ Остановлен');
                    if (d.state && d.state.log) {
                        $('#cron-log-content').html(d.state.log.map(function(l) { return '<div>'+l+'</div>'; }).join(''));
                        $('#abs-cron-status').show();
                    }
                }
            });
        }
        
        $('#btn-cron-start').on('click', function() {
            if (!confirm('Запустить фоновую загрузку?')) return;
            var slugs = $('#cron-book-ids').val().split(',').map(function(s) { return s.trim(); }).filter(Boolean);
            $.post(ajaxurl, {
                action: 'abs_parser_ifreedom_cron_toggle',
                cron_action: 'start',
                book_slugs: slugs,
                _ajax_nonce: '<?php echo wp_create_nonce("abs_parser_ifreedom_nonce"); ?>'
            }, function() { updateCronStatus(); });
        });
        
        $('#btn-cron-stop').on('click', function() {
            if (!confirm('Остановить?')) return;
            $.post(ajaxurl, {
                action: 'abs_parser_ifreedom_cron_toggle',
                cron_action: 'stop',
                _ajax_nonce: '<?php echo wp_create_nonce("abs_parser_ifreedom_nonce"); ?>'
            }, function() { updateCronStatus(); });
        });
        
        $('#btn-refresh-cron').on('click', updateCronStatus);
        updateCronStatus();
        setInterval(updateCronStatus, 5000);
    })(jQuery);
    </script>
    <?php
}

// ========== AJAX ОБРАБОТЧИКИ ==========

// Сохранение настроек
add_action('wp_ajax_abs_parser_ifreedom_save_settings', 'abs_parser_ifreedom_save_settings');
function abs_parser_ifreedom_save_settings() {
    check_ajax_referer('abs_parser_ifreedom_nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Нет прав');
    
    $settings = abs_parser_ifreedom_get_settings();
    $fields = ['min_delay_ms', 'max_delay_ms', 'max_per_minute', 'cron_batch_size', 'manual_batch_size', 'http_timeout'];
    foreach ($fields as $f) {
        if (isset($_POST[$f])) {
            $settings[$f] = absint($_POST[$f]);
        }
    }
    update_option('abs_parser_ifreedom_settings', $settings);
    wp_send_json_success();
}

// Сканирование каталога
add_action('wp_ajax_abs_parser_ifreedom_scan_catalog_ajax', 'abs_parser_ifreedom_scan_catalog_ajax');
function abs_parser_ifreedom_scan_catalog_ajax() {
    check_ajax_referer('abs_parser_ifreedom_nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Нет прав');
    
    require_once get_template_directory() . '/includes/abs-parser-ifreedom.php';
    
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $last_page = isset($_POST['last_page']) ? (int)$_POST['last_page'] : 0;
    $total = isset($_POST['total']) ? (int)$_POST['total'] : 0;
    $errors = isset($_POST['errors']) ? (int)$_POST['errors'] : 0;
    
    if ($last_page == 0) {
        $last_page = abs_parser_ifreedom_get_last_catalog_page();
    }
    
    $books = abs_parser_ifreedom_scan_catalog_page($page);
    if (is_array($books) && isset($books['error'])) {
        $errors++;
    } else {
        foreach ($books as $b) {
            $result = abs_parser_ifreedom_queue_book($b);
            if ($result['status'] === 'queued') $total++;
        }
    }
    
    wp_send_json_success([
        'finished' => ($page >= $last_page),
        'page' => $page + 1,
        'last_page' => $last_page,
        'total' => $total,
        'errors' => $errors,
        'message' => "Страница $page/$last_page, книг: $total",
    ]);
}

// Парсинг книги
add_action('wp_ajax_abs_parser_ifreedom_parse_book_ajax', 'abs_parser_ifreedom_parse_book_ajax');
function abs_parser_ifreedom_parse_book_ajax() {
    set_time_limit(120);
    check_ajax_referer('abs_parser_ifreedom_nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Нет прав');
    
    require_once get_template_directory() . '/includes/abs-parser-ifreedom.php';
    
    global $wpdb;
    $table = $wpdb->prefix . 'abs_parser_ifreedom_queue';
    $slugs = isset($_POST['book_slugs']) ? array_filter((array)$_POST['book_slugs']) : [];
    $ci = (int)($_POST['current_index'] ?? 0);
    $pc = (int)($_POST['processed_count'] ?? 0);
    $settings = abs_parser_ifreedom_get_settings();
    $batch_size = (int)$settings['manual_batch_size'];
    $error_count = (int)($_POST['error_count'] ?? 0);
    
    $saved = get_option('abs_parser_ifreedom_manual_state', null);
    if ($saved && $saved['updated_at'] > time() - 300 && empty($slugs)) {
        $slugs = $saved['slugs'];
        $ci = $saved['current_index'];
        $pc = $saved['processed_count'];
        $_POST['start_chapter'] = $saved['start_chapter'];
    }
    if (empty($slugs)) {
        $queue = $wpdb->get_results("SELECT * FROM $table WHERE status IN('new','error') OR (status='done' AND parsed_chapters<chapters_count) ORDER BY id ASC");
    } else {
        $placeholders = implode(',', array_fill(0, count($slugs), '%s'));
        $queue = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE slug IN($placeholders) ORDER BY id ASC", ...$slugs));
    }
    
    $total = count($queue);
    if ($ci >= $total || $error_count >= 5) {
        $msg = $error_count >= 5 ? "⚠️ Остановлено: {$error_count} ошибок подряд" : '';
        wp_send_json_success(['finished' => true, 'processed' => $pc, 'total' => $total, 'log' => $msg]);
    }
    
    $book = $queue[$ci];
    $wpdb->update($table, ['status' => 'parsing'], ['slug' => $book->slug]);
    
    $book_data = abs_parser_ifreedom_parse_book_page($book->slug);
    if (is_array($book_data) && isset($book_data['error'])) {
        $wpdb->update($table, ['status' => 'error', 'error_msg' => $book_data['error']], ['slug' => $book->slug]);
        wp_send_json_success([
            'finished' => false, 'processed' => $pc, 'total' => $total,
            'next_index' => $ci + 1, 'current_book' => $book->title,
            'error_count' => $error_count + 1,
            'log' => "❌ {$book_data['error']}", 'log_type' => 'error',
        ]);
    }
    
    $book_data['url'] = $book->url;
    if ($book->chapters_count == 0 && isset($book_data['chapters_free_count'])) {
    $wpdb->update($table, [
        'chapters_count' => $book_data['chapters_free_count'],
        'total_chapters' => $book_data['chapters_total_count'] ?? 0,
        'views' => $book_data['views'] ?? 0,
    ], ['slug' => $book->slug]);
    $book->chapters_count = $book_data['chapters_free_count'];
}
    $save = abs_parser_ifreedom_save_ranobe_post($book_data);
    if ($save['status'] === 'error') {
        $wpdb->update($table, ['status' => 'error', 'error_msg' => $save['message']], ['slug' => $book->slug]);
        wp_send_json_success([
            'finished' => false, 'processed' => $pc, 'total' => $total,
            'next_index' => $ci + 1, 'current_book' => $book_data['title'],
            'log' => "❌ {$save['message']}", 'log_type' => 'error',
        ]);
    }
    
    $post_id = $save['post_id'] ?? 0;
    $loaded = 0;
    $sc = (int)($_POST['start_chapter'] ?? 0);
if (!empty($book_data['chapters'])) {
    $tc = count($book_data['chapters']);
    $be = min($sc + $batch_size, $tc);
    
    for ($i = $sc; $i < $be; $i++) {
            $ch = $book_data['chapters'][$i];
            
            $ex = get_posts([
                'post_type' => 'chapter', 'post_parent' => $post_id,
                'meta_key' => '_chapter_number', 'meta_value' => $ch['number'],
                'posts_per_page' => 1, 'post_status' => 'any',
            ]);
            if (!empty($ex)) { $loaded++; continue; }
            
            $cd = abs_parser_ifreedom_parse_chapter_page($ch['url']);
            if (is_array($cd) && isset($cd['error'])) { continue; }
            if (empty($cd['content'])) continue;
            
            abs_parser_ifreedom_save_chapter($post_id, [
                'number' => $ch['number'], 'title' => $cd['chapter_title'],
                'content' => $cd['content'], 'volume' => $cd['volume'] ?? 0,
            ]);
            $loaded++;
        }
        
        if ($be < $tc) {
            $cur = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID=pm.post_id AND pm.meta_key='_chapter_number' WHERE p.post_parent=%d AND p.post_type='chapter'", $post_id));
            $wpdb->update($table, ['parsed_chapters' => $cur], ['slug' => $book->slug]);
            update_option('abs_parser_ifreedom_manual_state', [
                'slugs' => $slugs, 'current_index' => $ci, 'processed_count' => $pc,
                'start_chapter' => $be, 'updated_at' => time(),
            ]);
            wp_send_json_success([
                'finished' => false, 'processed' => $pc, 'total' => $total,
                'next_index' => $ci, 'current_book' => $book_data['title'],
                'start_chapter' => $be,
                'log' => "📖 {$book_data['title']} — $be/$tc", 'log_type' => 'info',
            ]);
        }
    }
    
    $tp = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} p
         JOIN {$wpdb->postmeta} pm ON p.ID=pm.post_id AND pm.meta_key='_chapter_number'
         WHERE p.post_parent=%d AND p.post_type='chapter'", $post_id
    ));
    
    $wpdb->update($table, [
        'status' => ($tp >= $book->chapters_count) ? 'done' : 'new',
        'parsed_chapters' => $tp, 'last_parsed_at' => current_time('mysql'), 'error_msg' => null,
    ], ['slug' => $book->slug]);
    
    abs_parser_ifreedom_sync_chapter_number($post_id, $book->slug);
    delete_option('abs_parser_ifreedom_manual_state');
    $pc++;
    wp_send_json_success([
        'finished' => ($ci + 1 >= $total), 'processed' => $pc, 'total' => $total,
        'next_index' => $ci + 1, 'current_book' => $book_data['title'],
        'log' => "✅ {$book_data['title']} — $tp глав", 'log_type' => 'success',
    ]);
}

// Очистка очереди
add_action('wp_ajax_abs_parser_ifreedom_clear_queue_ajax', 'abs_parser_ifreedom_clear_queue_ajax');
function abs_parser_ifreedom_clear_queue_ajax() {
    check_ajax_referer('abs_parser_ifreedom_nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Нет прав');
    global $wpdb;
    $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}abs_parser_ifreedom_queue");
    wp_send_json_success();
}

// ========== CRON ==========
add_filter('cron_schedules', 'abs_parser_ifreedom_cron_interval');
function abs_parser_ifreedom_cron_interval($s) {
    $s['every_minute_ifreedom'] = ['interval' => 60, 'display' => 'Каждую минуту (Ifreedom)'];
    return $s;
}

add_action('abs_parser_ifreedom_cron_hook', 'abs_parser_ifreedom_cron_process');
function abs_parser_ifreedom_cron_process() {
    require_once get_template_directory() . '/includes/abs-parser-ifreedom.php';
    
    global $wpdb;
    $table = $wpdb->prefix . 'abs_parser_ifreedom_queue';
    $ok = 'abs_parser_ifreedom_cron_state';
    $settings = abs_parser_ifreedom_get_settings();
    $batch_size = (int)$settings['cron_batch_size'];
    
    $state = get_option($ok, [
        'slug' => null, 'post_id' => null, 'start_chapter' => 0,
        'total_chapters' => 0, 'batch_size' => $batch_size,
        'running' => false, 'log' => [], 'chapters' => [],
    ]);
    
    if (!$state['running'] || !$state['slug']) {
        $only_slugs = isset($state['only_slugs']) ? $state['only_slugs'] : [];
        if (!empty($only_slugs)) {
            $ph = implode(',', array_fill(0, count($only_slugs), '%s'));
            $next = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table WHERE (status IN('new','error','parsing') OR (status='done' AND parsed_chapters<chapters_count)) AND slug IN($ph) ORDER BY id ASC LIMIT 1",
                ...$only_slugs
            ));
        } else {
            $next = $wpdb->get_row("SELECT * FROM $table WHERE status IN('new','error','parsing') OR (status='done' AND parsed_chapters<chapters_count) ORDER BY id ASC LIMIT 1");
        }
        
        if (!$next) {
            wp_clear_scheduled_hook('abs_parser_ifreedom_cron_hook');
            $state['running'] = false;
            $state['log'][] = date('H:i:s') . ' Все книги загружены, Cron остановлен';
            update_option($ok, $state);
            return;
        }
        
        $bd = abs_parser_ifreedom_parse_book_page($next->slug);
        // Обновляем количество глав в очереди
if ($next->chapters_count == 0 && isset($bd['chapters_free_count'])) {
    $wpdb->update($table, [
        'chapters_count' => $bd['chapters_free_count'],
        'total_chapters' => $bd['chapters_total_count'] ?? 0,
        'views' => $bd['views'] ?? 0,
    ], ['slug' => $next->slug]);
    $next->chapters_count = $bd['chapters_free_count'];
}
        if (is_array($bd) && isset($bd['error'])) {
            $wpdb->update($table, ['status' => 'error', 'error_msg' => $bd['error']], ['slug' => $next->slug]);
            $state['log'][] = date('H:i:s') . ' ❌ ' . $next->title;
            update_option($ok, $state);
            return;
        }
        
        $bd['url'] = $next->url;
        $sv = abs_parser_ifreedom_save_ranobe_post($bd);
        if ($sv['status'] === 'error') {
            $wpdb->update($table, ['status' => 'error', 'error_msg' => $sv['message']], ['slug' => $next->slug]);
            return;
        }
        
        $wpdb->update($table, ['status' => 'parsing'], ['slug' => $next->slug]);
        
        $existing_chapters = $wpdb->get_col($wpdb->prepare(
            "SELECT pm.meta_value FROM {$wpdb->posts} p
             JOIN {$wpdb->postmeta} pm ON p.ID=pm.post_id AND pm.meta_key='_chapter_number'
             WHERE p.post_parent=%d AND p.post_type='chapter'
             ORDER BY CAST(pm.meta_value AS UNSIGNED) ASC", $sv['post_id']
        ));
        $start_from = 1;
        
        $state = [
            'slug' => $next->slug, 'post_id' => $sv['post_id'], 'title' => $bd['title'],
            'start_chapter' => $start_from, 'total_chapters' => count($bd['chapters'] ?? []),
            'chapters' => $bd['chapters'] ?? [], 'running' => true,
            'batch_size' => $batch_size, 'stuck_cycles' => 0,
            'log' => [date('H:i:s') . ' 🚀 ' . $bd['title'] . ' (' . count($bd['chapters'] ?? []) . ' глав, старт с ' . $start_from . ')'],
        ];
        if (!empty($only_slugs)) $state['only_slugs'] = $only_slugs;
        update_option($ok, $state);
    }
    
    if ($state['running'] && $state['slug'] && !empty($state['chapters'])) {
        $be = min($state['start_chapter'] + $batch_size, $state['total_chapters']);
        $loaded = 0;
        
        for ($i = $state['start_chapter']; $i < $be; $i++) {
            $ch = $state['chapters'][$i];
            
            $ex = get_posts([
                'post_type' => 'chapter', 'post_parent' => $state['post_id'],
                'meta_key' => '_chapter_number', 'meta_value' => $ch['number'],
                'posts_per_page' => 1, 'post_status' => 'any',
            ]);
            if (!empty($ex)) { $loaded++; continue; }
            
            $cd = abs_parser_ifreedom_parse_chapter_page($ch['url']);
            if (is_array($cd) && isset($cd['error'])) {
                $state['log'][] = date('H:i:s') . ' ❌ Гл.' . $ch['number'] . ': ' . $cd['error'];
                continue;
            }
            if (empty($cd['content'])) {
                $state['log'][] = date('H:i:s') . ' ❌ Гл.' . $ch['number'] . ': пусто';
                continue;
            }
            
            $sv = abs_parser_ifreedom_save_chapter($state['post_id'], [
                'number' => $ch['number'], 'title' => $cd['chapter_title'],
                'content' => $cd['content'], 'volume' => $cd['volume'] ?? 0,
            ]);
            if (!$sv || is_wp_error($sv)) {
                $state['log'][] = date('H:i:s') . ' ❌ Сохранение гл.' . $ch['number'] . ' ошибка';
                continue;
            }
            $loaded++;
        }
        
        $state['start_chapter'] = $be;
        $stuck = $loaded > 0 ? 0 : (($state['stuck_cycles'] ?? 0) + 1);
        $state['stuck_cycles'] = $stuck;
        $state['log'][] = date('H:i:s') . ' 📖 ' . $state['title'] . ' — ' . $be . '/' . $state['total_chapters'] . ' (' . $loaded . ')' . ($stuck > 0 ? ' [зависание: ' . $stuck . ']' : '');
        
        if ($stuck >= 10) {
            $tp = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID=pm.post_id AND pm.meta_key='_chapter_number' WHERE p.post_parent=%d AND p.post_type='chapter'",
                $state['post_id']
            ));
            $wpdb->update($table, [
                'status' => 'error', 'error_msg' => 'Недоступные главы на источнике',
                'parsed_chapters' => $tp, 'last_parsed_at' => current_time('mysql'),
            ], ['slug' => $state['slug']]);
            $state['log'][] = date('H:i:s') . ' ⚠️ ' . $state['title'] . ' — пропущена (' . $stuck . ' циклов без загрузки)';
            $state['running'] = false; $state['slug'] = null;
            update_option($ok, $state);
            return;
        }
        
        if (count($state['log']) > 50) $state['log'] = array_slice($state['log'], -50);
        
        if ($be >= $state['total_chapters']) {
            abs_parser_ifreedom_sync_chapter_number($state['post_id'], $state['slug']);
            $tp = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID=pm.post_id AND pm.meta_key='_chapter_number' WHERE p.post_parent=%d AND p.post_type='chapter'",
                $state['post_id']
            ));
            $bi = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE slug=%s", $state['slug']));
            $wpdb->update($table, [
                'status' => ($tp >= ($bi->chapters_count ?? 0)) ? 'done' : 'new',
                'parsed_chapters' => $tp, 'last_parsed_at' => current_time('mysql'),
            ], ['slug' => $state['slug']]);
            $state['log'][] = date('H:i:s') . ' ✅ ' . $state['title'] . ' — ' . $tp . ' глав';
            $state['running'] = false; $state['slug'] = null;
        }
        update_option($ok, $state);
    }
}

add_action('wp_ajax_abs_parser_ifreedom_cron_toggle', 'abs_parser_ifreedom_cron_toggle');
function abs_parser_ifreedom_cron_toggle() {
    check_ajax_referer('abs_parser_ifreedom_nonce');
    $action = $_POST['cron_action'] ?? 'status';
    
    if ($action === 'start') {
        global $wpdb;
        $table = $wpdb->prefix . 'abs_parser_ifreedom_queue';
        $wpdb->query("UPDATE $table SET status = 'new' WHERE status = 'parsing'");
        
        $slugs = isset($_POST['book_slugs']) ? array_filter((array)$_POST['book_slugs']) : [];
        if (!wp_next_scheduled('abs_parser_ifreedom_cron_hook')) {
            wp_schedule_event(time(), 'every_minute_ifreedom', 'abs_parser_ifreedom_cron_hook');
        }
        $s = ['running' => false, 'log' => [date('H:i:s') . ' Cron запущен']];
        if (!empty($slugs)) {
            $s['only_slugs'] = $slugs;
            $s['log'][] = date('H:i:s') . ' 🔍 Фильтр: ' . implode(', ', $s['only_slugs']);
        }
        update_option('abs_parser_ifreedom_cron_state', $s);
        wp_send_json_success(['running' => true]);
    }
    
    if ($action === 'stop') {
        wp_clear_scheduled_hook('abs_parser_ifreedom_cron_hook');
        update_option('abs_parser_ifreedom_cron_state', ['running' => false, 'log' => [date('H:i:s') . ' Cron остановлен']]);
        wp_send_json_success(['running' => false]);
    }
    
    $s = get_option('abs_parser_ifreedom_cron_state', ['running' => false, 'log' => []]);
    $sc = wp_next_scheduled('abs_parser_ifreedom_cron_hook');
    wp_send_json_success(['running' => $s['running'] || $sc, 'state' => $s, 'next_run' => $sc ? date('H:i:s', $sc) : null]);
}

add_action('admin_footer', function() {
    if (get_current_screen()->id !== 'toplevel_page_abs-parser-ifreedom') return;
    echo '<div style="background:#fff;padding:10px;margin:10px;">';
    echo 'Settings: ' . (function_exists('abs_parser_ifreedom_get_settings') ? 'OK' : 'MISSING') . '<br>';
    echo 'Scan page: ' . (function_exists('abs_parser_ifreedom_scan_catalog_page') ? 'OK' : 'MISSING') . '<br>';
    echo '</div>';
});

function abs_parser_ifreedom_test() {
    require_once get_template_directory() . '/includes/abs-parser-ifreedom.php';
    $s = abs_parser_ifreedom_get_settings();
    echo '<pre>Settings OK: '; print_r($s); echo '</pre>';
}
add_action('admin_footer', 'abs_parser_ifreedom_test');