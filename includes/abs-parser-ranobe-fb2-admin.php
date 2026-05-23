<?php
/**
 * Парсер Ранобэ (FB2) — админ-панель
 */

if (!defined('ABSPATH')) exit;

// ============================================================
// 1. МЕНЮ
// ============================================================
add_action('admin_menu', 'abs_fb2_admin_menu');
function abs_fb2_admin_menu() {
    add_menu_page(
        'Парсер Ранобэ (FB2)',
        'Парсер FB2',
        'manage_options',
        'abs-parser-fb2',
        'abs_fb2_admin_page',
        'dashicons-book-alt',
        33
    );
}

// ============================================================
// 2. ГЛАВНАЯ СТРАНИЦА
// ============================================================
function abs_fb2_admin_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'abs_parser_queue_fb2';

    // Фильтры
    $filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : 'all';
    $sort_by       = isset($_GET['sort_by']) ? sanitize_text_field($_GET['sort_by']) : 'id_asc';
    $search        = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

    // WHERE
    $where = "WHERE 1=1";
    if ($filter_status === 'new') {
        $where .= " AND status = 'new'";
    } elseif ($filter_status === 'parsing') {
        $where .= " AND status = 'parsing'";
    } elseif ($filter_status === 'done') {
        $where .= " AND status = 'done'";
    } elseif ($filter_status === 'error') {
        $where .= " AND status = 'error'";
    } elseif ($filter_status === 'has_updates') {
        $where .= " AND status = 'has_updates'";
    } elseif ($filter_status === 'pending') {
        $where .= " AND status IN('new','error','has_updates')";
    }

    if (!empty($search)) {
        $where .= $wpdb->prepare(" AND title LIKE %s", '%' . $wpdb->esc_like($search) . '%');
    }

    // ORDER BY
    $order_by = "ORDER BY id ASC";
    if ($sort_by === 'id_desc') $order_by = "ORDER BY id DESC";
    elseif ($sort_by === 'title_asc') $order_by = "ORDER BY title ASC";
    elseif ($sort_by === 'title_desc') $order_by = "ORDER BY title DESC";
    elseif ($sort_by === 'chapters_asc') $order_by = "ORDER BY chapters_count ASC";
    elseif ($sort_by === 'chapters_desc') $order_by = "ORDER BY chapters_count DESC";

    // Статистика
    $total    = $wpdb->get_var("SELECT COUNT(*) FROM $table");
    $new      = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'new'");
    $parsing  = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'parsing'");
    $done     = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'done'");
    $error    = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'error'");
    $updates  = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'has_updates'");
    $pending  = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status IN('new','error','has_updates')");

    // Книги
    $books = $wpdb->get_results("SELECT * FROM $table $where $order_by");
    $base_url = admin_url('admin.php?page=abs-parser-fb2');
    ?>
    <div class="wrap abs-fb2-wrap">
        <h1>📚 Парсер Ранобэ (FB2)</h1>

        <!-- Статистика -->
        <div class="abs-fb2-stats">
            <div class="stat-box"><span class="stat-number"><?php echo $total; ?></span><span>Всего</span></div>
            <div class="stat-box stat-new"><span class="stat-number"><?php echo $new; ?></span><span>Новых</span></div>
            <div class="stat-box stat-parsing"><span class="stat-number"><?php echo $parsing; ?></span><span>В процессе</span></div>
            <div class="stat-box stat-updates"><span class="stat-number"><?php echo $updates; ?></span><span>Обновления</span></div>
            <div class="stat-box stat-done"><span class="stat-number"><?php echo $done; ?></span><span>Готово</span></div>
            <div class="stat-box stat-error"><span class="stat-number"><?php echo $error; ?></span><span>Ошибок</span></div>
        </div>

        <!-- Кнопки -->
        <div class="abs-fb2-actions">
            <button id="btn-scan" class="button button-primary">🔍 Сканировать каталог</button>
            <button id="btn-parse-selected" class="button" disabled>📥 Загрузить выбранные (0)</button>
            <button id="btn-parse-all" class="button">📥 Загрузить всё (<?php echo $pending; ?>)</button>
            <button id="btn-check-updates" class="button">🔄 Проверить обновления</button>
            <button id="btn-clear" class="button button-link-delete">🗑 Очистить</button>
        </div>

        <!-- Быстрый выбор -->
        <div class="abs-fb2-actions" style="margin-top:5px;">
            <button id="btn-select-new" class="button button-small">☐ Новые</button>
            <button id="btn-select-error" class="button button-small">☐ С ошибкой</button>
            <button id="btn-select-updates" class="button button-small">☐ Обновления</button>
            <button id="btn-select-all" class="button button-small">☑ Все</button>
            <button id="btn-deselect-all" class="button button-small">☐ Снять</button>
        </div>

        <!-- Прогресс -->
        <div id="abs-fb2-progress" class="abs-fb2-progress" style="display:none;">
            <div class="progress-bar-outer"><div class="progress-bar-inner" style="width:0%"></div></div>
            <div class="progress-text"><span id="progress-current">0</span> / <span id="progress-total">0</span> — <span id="progress-status">...</span></div>
        </div>

        <!-- Лог -->
        <div id="abs-fb2-log" class="abs-fb2-log" style="display:none;">
            <div class="log-header"><strong>📋 Лог</strong><button id="btn-clear-log" class="button button-small">Очистить</button></div>
            <div id="log-content" class="log-content"></div>
        </div>

        <!-- Фильтры -->
        <div class="abs-fb2-filters" style="margin:15px 0;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <strong>Фильтры:</strong>
            <select id="filter-status" onchange="applyFilters()">
                <option value="all" <?php selected($filter_status, 'all'); ?>>Все</option>
                <option value="new" <?php selected($filter_status, 'new'); ?>>Новые</option>
                <option value="parsing" <?php selected($filter_status, 'parsing'); ?>>В процессе</option>
                <option value="has_updates" <?php selected($filter_status, 'has_updates'); ?>>Обновления</option>
                <option value="done" <?php selected($filter_status, 'done'); ?>>Готово</option>
                <option value="error" <?php selected($filter_status, 'error'); ?>>Ошибки</option>
                <option value="pending" <?php selected($filter_status, 'pending'); ?>>Ожидают</option>
            </select>
            <select id="sort-by" onchange="applyFilters()">
                <option value="id_asc" <?php selected($sort_by, 'id_asc'); ?>>ID ↑</option>
                <option value="id_desc" <?php selected($sort_by, 'id_desc'); ?>>ID ↓</option>
                <option value="title_asc" <?php selected($sort_by, 'title_asc'); ?>>Название А-Я</option>
                <option value="title_desc" <?php selected($sort_by, 'title_desc'); ?>>Название Я-А</option>
                <option value="chapters_asc" <?php selected($sort_by, 'chapters_asc'); ?>>Глав ↑</option>
                <option value="chapters_desc" <?php selected($sort_by, 'chapters_desc'); ?>>Глав ↓</option>
            </select>
            <input type="text" id="search-input" placeholder="Поиск..." value="<?php echo esc_attr($search); ?>" style="width:200px;" onkeydown="if(event.key==='Enter')applyFilters()">
            <button class="button" onclick="applyFilters()">🔍</button>
            <?php if ($filter_status !== 'all' || $search || $sort_by !== 'id_asc'): ?>
                <a href="<?php echo $base_url; ?>" class="button button-small">✕ Сбросить</a>
            <?php endif; ?>
        </div>

        <!-- Таблица -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <td class="check-column"><input type="checkbox" id="select-all-top"></td>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Глав на сайте</th>
                    <th>Загружено</th>
                    <th>Статус</th>
                    <th>Дата</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($books)): ?>
                    <tr><td colspan="7">Очередь пуста. Нажмите «Сканировать каталог».</td></tr>
                <?php else: foreach ($books as $book):
                    $status_labels = [
                        'new'         => ['🐾 Новый', 'status-new'],
                        'parsing'     => ['⏳ В процессе', 'status-parsing'],
                        'done'        => ['✅ Готово', 'status-done'],
                        'error'       => ['❌ Ошибка', 'status-error'],
                        'has_updates' => ['🔄 Обновление', 'status-updates'],
                    ];
                    $l = $status_labels[$book->status] ?? $status_labels['new'];
                ?>
                    <tr class="queue-row" data-status="<?php echo $book->status; ?>">
                        <td class="check-column"><input type="checkbox" class="book-checkbox" value="<?php echo $book->ranobe_id; ?>"></td>
                        <td><?php echo $book->ranobe_id; ?></td>
                        <td>
                            <a href="<?php echo esc_url($book->url); ?>" target="_blank"><?php echo esc_html($book->title); ?></a>
                            <?php if ($book->error_msg): ?>
                                <br><small style="color:#d63638;"><?php echo esc_html($book->error_msg); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $book->chapters_count; ?></td>
                        <td><?php echo $book->parsed_chapters; ?></td>
                        <td><span class="status-badge <?php echo $l[1]; ?>"><?php echo $l[0]; ?></span></td>
                        <td><?php echo $book->last_parsed_at ?: '—'; ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <style>
        .stat-parsing .stat-number { color: #f0a030; }
        .abs-fb2-wrap { margin: 20px 0; }
        .abs-fb2-stats { display: flex; gap: 20px; margin: 20px 0; }
        .stat-box { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; text-align: center; min-width: 100px; }
        .stat-number { font-size: 32px; font-weight: bold; display: block; }
        .stat-new .stat-number { color: #007cba; }
        .stat-done .stat-number { color: #00a32a; }
        .stat-error .stat-number { color: #d63638; }
        .stat-updates .stat-number { color: #f0a030; }
        .abs-fb2-actions { display: flex; gap: 10px; margin: 20px 0; flex-wrap: wrap; }
        .abs-fb2-progress { margin: 20px 0; background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; }
        .progress-bar-outer { height: 24px; background: #f0f0f0; border-radius: 12px; overflow: hidden; }
        .progress-bar-inner { height: 100%; background: linear-gradient(90deg, #007cba, #00a32a); transition: width 0.3s; }
        .progress-text { text-align: center; margin-top: 10px; font-size: 14px; }
        .abs-fb2-log { margin: 20px 0; background: #1e1e1e; border-radius: 8px; padding: 15px; }
        .log-header { display: flex; justify-content: space-between; align-items: center; color: #fff; margin-bottom: 10px; }
        .log-content { max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 12px; color: #0f0; line-height: 1.6; }
        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 12px; }
        .status-new { background: #e7f3ff; color: #007cba; }
        .status-parsing { background: #fff3cd; color: #856404; }
        .status-done { background: #d4edda; color: #155724; }
        .status-error { background: #f8d7da; color: #721c24; }
        .status-updates { background: #fff3e0; color: #e65100; }
    </style>

    <script>
    (function($) {
        var isRunning = false;
        var logLines = [];

        function log(msg, type) {
            type = type || 'info';
            var t = new Date().toLocaleTimeString();
            logLines.push('<div class="log-' + type + '">[' + t + '] ' + msg + '</div>');
            $('#log-content').html(logLines.join(''));
            $('#abs-fb2-log').show();
        }

        function updateProgress(c, t, s) {
            $('#abs-fb2-progress').show();
            $('#progress-current').text(c);
            $('#progress-total').text(t);
            $('#progress-status').text(s);
            $('.progress-bar-inner').css('width', (t > 0 ? Math.round(c / t * 100) : 0) + '%');
        }

        function setButtons(enabled) {
            isRunning = !enabled;
            $('#btn-scan, #btn-parse-selected, #btn-parse-all, #btn-check-updates').prop('disabled', !enabled);
        }

        function updateSelectedBtn() {
            var c = $('.book-checkbox:checked').length;
            $('#btn-parse-selected').prop('disabled', c === 0).text('📥 Загрузить выбранные (' + c + ')');
        }

        // Фильтры
        window.applyFilters = function() {
            var params = [];
            var s = $('#filter-status').val();
            if (s !== 'all') params.push('filter_status=' + s);
            var sort = $('#sort-by').val();
            if (sort !== 'id_asc') params.push('sort_by=' + sort);
            var q = $('#search-input').val().trim();
            if (q) params.push('search=' + encodeURIComponent(q));
            var url = '<?php echo $base_url; ?>';
            if (params.length) url += '&' + params.join('&');
            window.location.href = url;
        };

        // Выбор всех
        $('#select-all-top').on('change', function() {
            $('.book-checkbox').prop('checked', $(this).prop('checked'));
            updateSelectedBtn();
        });
        $(document).on('change', '.book-checkbox', updateSelectedBtn);

        // Быстрые выборы
        $('#btn-select-new').click(function() {
            $('.queue-row[data-status="new"] .book-checkbox').prop('checked', true);
            updateSelectedBtn();
        });
        $('#btn-select-error').click(function() {
            $('.queue-row[data-status="error"] .book-checkbox').prop('checked', true);
            updateSelectedBtn();
        });
        $('#btn-select-updates').click(function() {
            $('.queue-row[data-status="has_updates"] .book-checkbox').prop('checked', true);
            updateSelectedBtn();
        });
        $('#btn-select-all').click(function() {
            $('.book-checkbox').prop('checked', true);
            updateSelectedBtn();
        });
        $('#btn-deselect-all').click(function() {
            $('.book-checkbox').prop('checked', false);
            updateSelectedBtn();
        });

        // Сканировать каталог
        $('#btn-scan').on('click', function() {
            if (isRunning) return;
            if (!confirm('Сканировать каталог ranobe.me?')) return;
            setButtons(false);
            log('🔍 Сканирование...', 'info');
            scanPage(1, 0, 0, 0);
        });

        function scanPage(page, lastPage, total, errors) {
            $.post(ajaxurl, {
                action: 'abs_fb2_scan_ajax',
                page: page,
                last_page: lastPage,
                total: total,
                errors: errors,
                _ajax_nonce: '<?php echo wp_create_nonce("abs_fb2_nonce"); ?>'
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
            }).fail(function() {
                log('❌ Ошибка AJAX', 'error');
                if (page < lastPage || lastPage === 0) {
                    scanPage(page + 1, lastPage, total, errors + 1);
                } else {
                    setButtons(true);
                }
            });
        }

        // Загрузить всё
        $('#btn-parse-all').on('click', function() {
            if (isRunning) return;
            var slugs = [];
            $('.queue-row').each(function() {
                var cb = $(this).find('.book-checkbox');
                if (cb.length) slugs.push(cb.val());
            });
            if (!slugs.length) { alert('Нет книг.'); return; }
            if (!confirm('Загрузить ' + slugs.length + ' книг?')) return;
            startParsing(slugs);
        });

        // Загрузить выбранные
        $('#btn-parse-selected').on('click', function() {
            if (isRunning) return;
            var slugs = [];
            $('.book-checkbox:checked').each(function() { slugs.push($(this).val()); });
            if (!slugs.length) return;
            startParsing(slugs);
        });

        function startParsing(ids) {
            setButtons(false);
            log('📥 Загрузка ' + ids.length + ' книг...', 'info');
            parseNext(ids, 0, 0);
        }

        function parseNext(ids, index, processed) {
            $.post(ajaxurl, {
                action: 'abs_fb2_parse_book_ajax',
                book_ids: ids,
                current_index: index,
                processed_count: processed,
                _ajax_nonce: '<?php echo wp_create_nonce("abs_fb2_nonce"); ?>'
            }, function(r) {
                if (r.success) {
                    var d = r.data;
                    updateProgress(d.processed, d.total, d.current_book || '...');
                    if (d.log) log(d.log, d.log_type || 'info');
                    if (d.finished) {
                        log('✅ Готово!', 'success');
                        setButtons(true);
                        setTimeout(function() { location.reload(); }, 2000);
                    } else {
                        parseNext(ids, d.next_index, d.processed);
                    }
                }
            }).fail(function() {
                log('❌ Ошибка AJAX', 'error');
                setButtons(true);
            });
        }

        // Проверить обновления
        $('#btn-check-updates').on('click', function() {
            if (isRunning) return;
            if (!confirm('Проверить обновления для ВСЕХ книг в очереди?')) return;
            setButtons(false);
            log('🔄 Проверка обновлений...', 'info');
            checkUpdates(0);
        });

        function checkUpdates(offset) {
            $.post(ajaxurl, {
                action: 'abs_fb2_check_updates_ajax',
                offset: offset,
                _ajax_nonce: '<?php echo wp_create_nonce("abs_fb2_nonce"); ?>'
            }, function(r) {
                if (r.success) {
                    log('📄 ' + r.data.message, 'info');
                    if (r.data.finished) {
                        log('✅ Проверка завершена!', 'success');
                        setButtons(true);
                        setTimeout(function() { location.reload(); }, 2000);
                    } else {
                        checkUpdates(r.data.offset);
                    }
                }
            }).fail(function() {
                log('❌ Ошибка', 'error');
                setButtons(true);
            });
        }

        // Очистить
        $('#btn-clear').on('click', function() {
            if (!confirm('Удалить ВСЕ книги из очереди?')) return;
            $.post(ajaxurl, {
                action: 'abs_fb2_clear_ajax',
                _ajax_nonce: '<?php echo wp_create_nonce("abs_fb2_nonce"); ?>'
            }, function() { location.reload(); });
        });

        // Очистить лог
        $('#btn-clear-log').on('click', function() {
            logLines = [];
            $('#log-content').html('');
        });

        updateSelectedBtn();
    })(jQuery);
    </script>
    <?php
}

// ============================================================
// 3. AJAX: СКАНИРОВАНИЕ
// ============================================================
add_action('wp_ajax_abs_fb2_scan_ajax', 'abs_fb2_scan_ajax');
function abs_fb2_scan_ajax() {
    check_ajax_referer('abs_fb2_nonce');
    if (!current_user_can('manage_options')) wp_send_json_error();

    require_once get_template_directory() . '/includes/abs-parser-ranobe-fb2.php';

    $page      = (int) ($_POST['page'] ?? 1);
    $last_page = (int) ($_POST['last_page'] ?? 0);
    $total     = (int) ($_POST['total'] ?? 0);
    $errors    = (int) ($_POST['errors'] ?? 0);

    if ($last_page == 0) {
        $last_page = abs_fb2_get_last_catalog_page();
    }

    $books = abs_fb2_scan_catalog_page($page);

    if (is_array($books) && isset($books['error'])) {
        $errors++;
    } else {
        foreach ($books as $b) {
            $result = abs_fb2_queue_book($b);
            if ($result['status'] === 'queued') $total++;
        }
    }

    wp_send_json_success([
        'finished'  => ($page >= $last_page),
        'page'      => $page + 1,
        'last_page' => $last_page,
        'total'     => $total,
        'errors'    => $errors,
        'message'   => "Страница $page/$last_page, книг: $total",
    ]);
}

// ============================================================
// 4. AJAX: ПАРСИНГ КНИГИ (ТОЛЬКО FB2)
// ============================================================
add_action('wp_ajax_abs_fb2_parse_book_ajax', 'abs_fb2_parse_book_ajax');
function abs_fb2_parse_book_ajax() {
    check_ajax_referer('abs_fb2_nonce');
    if (!current_user_can('manage_options')) wp_send_json_error();

    require_once get_template_directory() . '/includes/abs-parser-ranobe-fb2.php';

    global $wpdb;
    $table = $wpdb->prefix . 'abs_parser_queue_fb2';

    $ids = isset($_POST['book_ids']) ? array_filter(array_map('absint', (array) $_POST['book_ids'])) : [];
    $ci  = (int) ($_POST['current_index'] ?? 0);
    $pc  = (int) ($_POST['processed_count'] ?? 0);

    if (empty($ids)) {
        $queue = $wpdb->get_results("SELECT * FROM $table WHERE status IN('new','error','has_updates') ORDER BY id ASC");
    } else {
        $ph = implode(',', array_fill(0, count($ids), '%d'));
        $queue = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE ranobe_id IN($ph) ORDER BY id ASC", ...$ids));
    }

    $total = count($queue);
    if ($ci >= $total) {
        wp_send_json_success(['finished' => true, 'processed' => $pc, 'total' => $total]);
    }

    $book = $queue[$ci];
    $wpdb->update($table, ['status' => 'parsing'], ['ranobe_id' => $book->ranobe_id]);

    // 1. Парсим страницу книги
    $book_data = abs_fb2_parse_book_page($book->ranobe_id);
    if (isset($book_data['error'])) {
        $wpdb->update($table, ['status' => 'error', 'error_msg' => $book_data['error']], ['ranobe_id' => $book->ranobe_id]);
        $pc++;
        wp_send_json_success([
            'finished'     => ($ci + 1 >= $total),
            'processed'    => $pc,
            'total'        => $total,
            'next_index'   => $ci + 1,
            'current_book' => "ID:{$book->ranobe_id}",
            'log'          => "❌ {$book_data['error']}",
            'log_type'     => 'error',
        ]);
    }

    $book_data['url'] = $book->url;

    // 2. Сохраняем пост
    $save = abs_fb2_save_ranobe_post($book_data);
    if ($save['status'] === 'error') {
        $wpdb->update($table, ['status' => 'error', 'error_msg' => $save['message']], ['ranobe_id' => $book->ranobe_id]);
        $pc++;
        wp_send_json_success([
            'finished'     => ($ci + 1 >= $total),
            'processed'    => $pc,
            'total'        => $total,
            'next_index'   => $ci + 1,
            'current_book' => $book_data['title'],
            'log'          => "❌ {$save['message']}",
            'log_type'     => 'error',
        ]);
    }

    $post_id = $save['post_id'];

    // 3. Загружаем FB2 (все части)
    $fb2_result = abs_fb2_import_all($book->ranobe_id, $post_id);

    if (!empty($fb2_result['error'])) {
        $wpdb->update($table, [
            'status'    => 'error',
            'error_msg' => $fb2_result['error'],
        ], ['ranobe_id' => $book->ranobe_id]);
        $pc++;
        wp_send_json_success([
            'finished'     => ($ci + 1 >= $total),
            'processed'    => $pc,
            'total'        => $total,
            'next_index'   => $ci + 1,
            'current_book' => $book_data['title'],
            'log'          => "❌ {$fb2_result['error']}",
            'log_type'     => 'error',
        ]);
    }

    // 4. Успех
    $chapter_count = count($fb2_result['chapters']);
    $wpdb->update($table, [
        'status'          => 'done',
        'parsed_chapters' => $chapter_count,
        'last_parsed_at'  => current_time('mysql'),
        'error_msg'       => null,
    ], ['ranobe_id' => $book->ranobe_id]);

    update_post_meta($post_id, '_ranobe_fb2_failed', 0);

    $pc++;
    wp_send_json_success([
        'finished'     => ($ci + 1 >= $total),
        'processed'    => $pc,
        'total'        => $total,
        'next_index'   => $ci + 1,
        'current_book' => $book_data['title'],
        'log'          => "✅ {$book_data['title']} — $chapter_count глав",
        'log_type'     => 'success',
    ]);
}

// ============================================================
// 5. AJAX: ПРОВЕРКА ОБНОВЛЕНИЙ
// ============================================================
add_action('wp_ajax_abs_fb2_check_updates_ajax', 'abs_fb2_check_updates_ajax');
function abs_fb2_check_updates_ajax() {
    check_ajax_referer('abs_fb2_nonce');
    if (!current_user_can('manage_options')) wp_send_json_error();

    require_once get_template_directory() . '/includes/abs-parser-ranobe-fb2.php';

    global $wpdb;
    $table = $wpdb->prefix . 'abs_parser_queue_fb2';

    $offset = (int) ($_POST['offset'] ?? 0);
    $batch  = 10;

    $books = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table ORDER BY id ASC LIMIT %d, %d", $offset, $batch));

    if (empty($books)) {
        wp_send_json_success(['finished' => true, 'message' => 'Готово']);
    }

    $checked = 0;
    $found_updates = 0;

    foreach ($books as $book) {
        $site_data = abs_fb2_get_html("https://ranobe.me/ranobe{$book->ranobe_id}");
        if (is_array($site_data) && isset($site_data['error'])) continue;

        // Считаем главы на сайте
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($site_data, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);
        $site_chapters = 0;
        foreach ($xpath->query("//ul[contains(@class, 'FicContents')]") as $list) {
            if ($list->getAttribute('OnCLick') || $list->getAttribute('onclick')) continue;
            $site_chapters += $xpath->query(".//li[contains(@class, 't-b-dotted')]", $list)->length;
        }

        if ($site_chapters > $book->chapters_count) {
            $wpdb->update($table, [
                'chapters_count' => $site_chapters,
                'status'         => 'has_updates',
            ], ['ranobe_id' => $book->ranobe_id]);
            $found_updates++;
        }

        $checked++;
    }

    wp_send_json_success([
        'finished' => ($offset + $batch >= $wpdb->get_var("SELECT COUNT(*) FROM $table")),
        'offset'   => $offset + $batch,
        'message'  => "Проверено: $checked, обновлений: $found_updates",
    ]);
}

// ============================================================
// 6. AJAX: ОЧИСТКА
// ============================================================
add_action('wp_ajax_abs_fb2_clear_ajax', 'abs_fb2_clear_ajax');
function abs_fb2_clear_ajax() {
    check_ajax_referer('abs_fb2_nonce');
    if (!current_user_can('manage_options')) wp_send_json_error();

    global $wpdb;
    $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}abs_parser_queue_fb2");
    wp_send_json_success();
}