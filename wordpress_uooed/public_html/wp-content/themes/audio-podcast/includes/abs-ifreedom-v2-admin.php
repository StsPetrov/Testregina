<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', 'abs_ifreedom_v2_admin_menu');
function abs_ifreedom_v2_admin_menu() {
    add_menu_page('Ifreedom v2', 'Ifreedom v2', 'manage_options', 'abs-ifreedom-v2', 'abs_ifreedom_v2_admin_page', 'dashicons-download', 33);
}

// ========== AJAX: Сканирование ==========
add_action('wp_ajax_abs_ifreedom_v2_scan', 'abs_ifreedom_v2_ajax_scan');
function abs_ifreedom_v2_ajax_scan() {
    check_ajax_referer('abs_ifreedom_v2_nonce');
    if (!current_user_can('manage_options')) wp_send_json_error();
    require_once get_template_directory() . '/includes/abs-ifreedom-v2.php';
    $page = (int)($_POST['page'] ?? 1);
    $last = (int)($_POST['last_page'] ?? 0);
    $total = (int)($_POST['total'] ?? 0);
    $errors = (int)($_POST['errors'] ?? 0);
    if (!$last) $last = abs_ifreedom_v2_get_last_catalog_page();
    $books = abs_ifreedom_v2_scan_catalog_page($page);
    if (isset($books['error'])) { $errors++; }
    else { foreach ((array)$books as $b) { $r = abs_ifreedom_v2_queue_book($b); if ($r['status']==='queued') $total++; } }
    wp_send_json_success(['finished'=>($page>=$last),'page'=>$page+1,'last_page'=>$last,'total'=>$total,'errors'=>$errors,'message'=>"Стр. $page/$last, книг: $total"]);
}

// ========== AJAX: Загрузка книг (пакетная) ==========
add_action('wp_ajax_abs_ifreedom_v2_process', 'abs_ifreedom_v2_ajax_process');
function abs_ifreedom_v2_ajax_process() {
    // check_ajax_referer('abs_ifreedom_v2_nonce');
    if (!current_user_can('manage_options')) wp_send_json_error();
    require_once get_template_directory() . '/includes/abs-ifreedom-v2.php';
    global $wpdb;
    $t = $wpdb->prefix . 'abs_ifreedom_v2_queue';
    $slugs = isset($_POST['book_slugs']) ? array_filter((array)$_POST['book_slugs']) : [];
    $ci = max(0, (int)($_POST['current_index'] ?? 0));
    $pc = max(0, (int)($_POST['processed_count'] ?? 0));
    $sc = max(1, (int)($_POST['start_chapter'] ?? 1));
    $batch = (int)abs_ifreedom_v2_get_settings()['manual_batch_size'];

    if (empty($slugs)) {
        $queue = $wpdb->get_results("SELECT * FROM $t WHERE status IN('new','error') OR (status='done' AND parsed_chapters<chapters_count) ORDER BY id ASC");
    } else {
        $ph = implode(',', array_fill(0, count($slugs), '%s'));
        $queue = $wpdb->get_results($wpdb->prepare("SELECT * FROM $t WHERE slug IN($ph) ORDER BY id ASC", ...$slugs));
    }

    $total = count($queue);
    if ($ci >= $total) { wp_send_json_success(['finished'=>true,'processed'=>$pc,'total'=>$total]); }

    $book = $queue[$ci];
    $wpdb->update($t, ['status'=>'parsing'], ['slug'=>$book->slug]);

    // Лог: начало книги
    abs_ifreedom_v2_log("📥 Старт: {$book->title}");
    if (function_exists('abs_telegram_log')) abs_telegram_log("📥 V2 старт: {$book->title}");

    $bd = abs_ifreedom_v2_parse_book_page($book->slug);
    if (isset($bd['error'])) {
        $wpdb->update($t, ['status'=>'error','error_msg'=>$bd['error']], ['slug'=>$book->slug]);
        wp_send_json_success(['finished'=>false,'processed'=>$pc,'total'=>$total,'next_index'=>$ci+1,'log'=>"❌ {$bd['error']}"]);
    }

    if ($book->chapters_count == 0 && isset($bd['chapters_free_count'])) {
        $wpdb->update($t, ['chapters_count'=>$bd['chapters_free_count'],'total_chapters'=>$bd['chapters_total_count']??0,'views'=>$bd['views']??0], ['slug'=>$book->slug]);
        $book->chapters_count = $bd['chapters_free_count'];
    }

    $sv = abs_ifreedom_v2_save_book($bd);
    if ($sv['status']==='error') {
        $wpdb->update($t, ['status'=>'error','error_msg'=>$sv['message']], ['slug'=>$book->slug]);
        wp_send_json_success(['finished'=>false,'processed'=>$pc,'total'=>$total,'next_index'=>$ci+1,'log'=>"❌ {$sv['message']}"]);
    }

    $pid = $sv['post_id'];
    $chapters = $bd['chapters'] ?? [];
    $tc = count($chapters);
    if ($tc == 0) {
        $wpdb->update($t, ['status'=>'done','last_parsed_at'=>current_time('mysql')], ['slug'=>$book->slug]);
        $pc++;
        if (function_exists('abs_telegram_log')) abs_telegram_log("✅ V2: {$book->title} — 0 глав");
        wp_send_json_success(['finished'=>($ci+1>=$total),'processed'=>$pc,'total'=>$total,'next_index'=>$ci+1,'log'=>"✅ {$book->title} — 0 глав"]);
    }

    $be = min($sc + $batch, $tc);
    $loaded = 0;
    for ($i = $sc - 1; $i < $be; $i++) {
        $ch = $chapters[$i]; $num = $i + 1;
        $ex = get_posts(['post_type'=>'chapter','post_parent'=>$pid,'meta_key'=>'_chapter_number','meta_value'=>$num,'posts_per_page'=>1,'post_status'=>'any']);
        if (!empty($ex)) { $loaded++; continue; }
        $cd = abs_ifreedom_v2_parse_chapter($ch['url']);
        if (isset($cd['error']) || empty($cd['content'])) continue;
        abs_ifreedom_v2_save_chapter($pid, $num, $cd['title'], $cd['content'], $cd['volume']??0);
        $loaded++;
    }

    $cur = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID=pm.post_id AND pm.meta_key='_chapter_number' WHERE p.post_parent=%d AND p.post_type='chapter'", $pid));
    $wpdb->update($t, ['parsed_chapters'=>$cur], ['slug'=>$book->slug]);

    if ($be < $tc) {
        wp_send_json_success(['finished'=>false,'processed'=>$pc,'total'=>$total,'next_index'=>$ci,'start_chapter'=>$be+1,'log'=>"📖 {$bd['title']} — $be/$tc"]);
    }

    $wpdb->update($t, ['status'=>'done','last_parsed_at'=>current_time('mysql')], ['slug'=>$book->slug]);
    $pc++;
    abs_ifreedom_v2_log("✅ Завершено: {$book->title} — {$cur} глав");
    if (function_exists('abs_telegram_log')) abs_telegram_log("✅ V2: {$book->title} — {$cur} глав");
    wp_send_json_success(['finished'=>($ci+1>=$total),'processed'=>$pc,'total'=>$total,'next_index'=>$ci+1,'log'=>"✅ {$book->title} — {$cur} глав"]);
}

// ========== AJAX: Очистка ==========
add_action('wp_ajax_abs_ifreedom_v2_clear', 'abs_ifreedom_v2_ajax_clear');
function abs_ifreedom_v2_ajax_clear() {
    check_ajax_referer('abs_ifreedom_v2_nonce');
    if (!current_user_can('manage_options')) wp_send_json_error();
    global $wpdb; $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}abs_ifreedom_v2_queue"); wp_send_json_success();
}

// ========== AJAX: Cron ==========
add_action('wp_ajax_abs_ifreedom_v2_cron_toggle', 'abs_ifreedom_v2_ajax_cron');
function abs_ifreedom_v2_ajax_cron() {
    check_ajax_referer('abs_ifreedom_v2_nonce');
    $action = $_POST['cron_action'] ?? 'status';
    if ($action === 'start') {
        global $wpdb; $wpdb->query("UPDATE {$wpdb->prefix}abs_ifreedom_v2_queue SET status='new' WHERE status='parsing'");
        if (!wp_next_scheduled('abs_ifreedom_v2_cron_hook')) wp_schedule_event(time(), 'every_minute', 'abs_ifreedom_v2_cron_hook');
        $st = ['running'=>true,'log'=>[date('H:i:s').' Cron запущен']];
        $slugs = isset($_POST['book_slugs']) ? array_filter((array)$_POST['book_slugs']) : [];
        if ($slugs) { $st['only_slugs']=$slugs; $st['log'][]=date('H:i:s').' Фильтр: '.implode(', ',$slugs); }
        update_option('abs_ifreedom_v2_cron_state', $st);
        wp_send_json_success(['running'=>true]);
    } elseif ($action === 'stop') {
        wp_clear_scheduled_hook('abs_ifreedom_v2_cron_hook');
        update_option('abs_ifreedom_v2_cron_state', ['running'=>false,'log'=>[date('H:i:s').' Cron остановлен']]);
        wp_send_json_success(['running'=>false]);
    }
    $st = get_option('abs_ifreedom_v2_cron_state', ['running'=>false,'log'=>[]]);
    wp_send_json_success(['running'=>$st['running'], 'state'=>$st]);
}

// ========== ГЛАВНАЯ СТРАНИЦА ==========
function abs_ifreedom_v2_admin_page() {
    global $wpdb; $t = $wpdb->prefix . 'abs_ifreedom_v2_queue';
    $settings = abs_ifreedom_v2_get_settings();
    $filter = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : 'all';
    $sort = isset($_GET['sort_by']) ? sanitize_text_field($_GET['sort_by']) : 'id_asc';
    $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
    $cf = isset($_GET['chapters_filter']) ? sanitize_text_field($_GET['chapters_filter']) : 'all';
    $where = "WHERE 1=1";
    if ($filter==='new') $where.=" AND status='new'";
    elseif ($filter==='parsing') $where.=" AND status='parsing'";
    elseif ($filter==='done') $where.=" AND status='done'";
    elseif ($filter==='error') $where.=" AND status='error'";
    elseif ($filter==='pending') $where.=" AND (status IN('new','error') OR (status='done' AND parsed_chapters<chapters_count))";
    if ($search) $where.=$wpdb->prepare(" AND (title LIKE %s OR slug LIKE %s)",'%'.$wpdb->esc_like($search).'%','%'.$wpdb->esc_like($search).'%');
    if ($cf==='small') $where.=" AND chapters_count<100";
    elseif ($cf==='medium') $where.=" AND chapters_count BETWEEN 100 AND 500";
    elseif ($cf==='large') $where.=" AND chapters_count BETWEEN 501 AND 1000";
    elseif ($cf==='xlarge') $where.=" AND chapters_count>1000";
    $order = "ORDER BY id ASC";
    if ($sort==='id_desc') $order="ORDER BY id DESC";
    elseif ($sort==='title_asc') $order="ORDER BY title ASC";
    elseif ($sort==='title_desc') $order="ORDER BY title DESC";
    elseif ($sort==='chapters_asc') $order="ORDER BY chapters_count ASC";
    elseif ($sort==='chapters_desc') $order="ORDER BY chapters_count DESC";
    elseif ($sort==='views_asc') $order="ORDER BY views ASC";
    elseif ($sort==='views_desc') $order="ORDER BY views DESC";
    $total = $wpdb->get_var("SELECT COUNT(*) FROM $t");
    $new = $wpdb->get_var("SELECT COUNT(*) FROM $t WHERE status='new'");
    $done = $wpdb->get_var("SELECT COUNT(*) FROM $t WHERE status='done'");
    $error = $wpdb->get_var("SELECT COUNT(*) FROM $t WHERE status='error'");
    $paged = isset($_GET['paged']) ? max(1,(int)$_GET['paged']) : 1;
    $pp = 50; $offset = ($paged-1)*$pp;
    $books = $wpdb->get_results("SELECT * FROM $t $where $order LIMIT $offset,$pp");
    $total_books = $wpdb->get_var("SELECT COUNT(*) FROM $t $where");
    $total_pages = ceil($total_books/$pp);
    $all_slugs = $wpdb->get_col("SELECT slug FROM $t WHERE status IN('new','error') OR (status='done' AND parsed_chapters<chapters_count) $order");
    $base_url = admin_url('admin.php?page=abs-ifreedom-v2');
    ?>
    <div class="wrap"><h1>📚 Парсер Ifreedom v2</h1>
    <div style="display:flex;gap:20px;margin:20px 0;"><?php
        foreach ([['Всего',$total,''],['Новых',$new,'#007cba'],['Загружено',$done,'#00a32a'],['Ошибок',$error,'#d63638']] as $st)
            echo '<div style="flex:1;text-align:center;padding:20px;background:#fff;border-radius:8px;border:1px solid #ccd0d4;"><span style="font-size:2rem;font-weight:700;color:'.$st[2].'">'.$st[1].'</span><br>'.$st[0].'</div>';
    ?></div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin:20px 0;">
        <button id="btn-scan" class="button button-primary">🔍 Сканировать</button>
        <button id="btn-parse-selected" class="button" disabled>📥 Загрузить выбранные (0)</button>
        <button id="btn-parse-filtered" class="button">📥 Загрузить всё (фильтр)</button>
        <button id="btn-clear" class="button button-link-delete">🗑 Очистить</button>
    </div>
    <div style="display:flex;gap:10px;margin:10px 0;">
        <input type="text" id="cron-slugs" placeholder="Slug через запятую" style="width:250px;">
        <button id="btn-cron-start" class="button button-primary">🕒 Фон: СТАРТ</button>
        <button id="btn-cron-stop" class="button">⏹ СТОП</button>
        <span id="cron-status">⚪ Остановлен</span>
    </div>
    <div id="v2-progress" style="display:none;margin:20px 0;background:#fff;padding:20px;border-radius:8px;border:1px solid #ddd;">
        <div style="height:20px;background:#f0f0f0;border-radius:10px;"><div id="v2-bar" style="height:100%;width:0%;background:linear-gradient(90deg,#007cba,#00a32a);border-radius:10px;"></div></div>
        <div style="text-align:center;margin-top:10px;"><span id="v2-cur">0</span> / <span id="v2-tot">0</span> — <span id="v2-status"></span></div>
    </div>
    <div id="v2-log" style="display:none;background:#1e1e1e;border-radius:8px;padding:15px;max-height:300px;overflow:auto;font-family:monospace;color:#0f0;margin:20px 0;"></div>
    <div style="display:flex;gap:10px;margin:15px 0;flex-wrap:wrap;align-items:center;">
        <strong>Фильтры:</strong>
        <select id="filter-status" onchange="applyFilters()"><?php foreach(['all'=>'Все','new'=>'Новые','parsing'=>'В процессе','done'=>'Загружено','error'=>'С ошибкой','pending'=>'Ожидают'] as $v=>$n) echo '<option value="'.$v.'"'.selected($filter,$v,false).'>'.$n.'</option>'; ?></select>
        <select id="chapters-filter" onchange="applyFilters()"><?php foreach(['all'=>'Любое','small'=>'<100','medium'=>'100-500','large'=>'500-1000','xlarge'=>'>1000'] as $v=>$n) echo '<option value="'.$v.'"'.selected($cf,$v,false).'>'.$n.'</option>'; ?></select>
        <select id="sort-by" onchange="applyFilters()"><?php foreach(['id_asc'=>'ID ↑','id_desc'=>'ID ↓','title_asc'=>'А-Я','title_desc'=>'Я-А','chapters_asc'=>'Глав ↑','chapters_desc'=>'Глав ↓','views_asc'=>'Просм. ↑','views_desc'=>'Просм. ↓'] as $v=>$n) echo '<option value="'.$v.'"'.selected($sort,$v,false).'>'.$n.'</option>'; ?></select>
        <input type="text" id="search-input" value="<?php echo esc_attr($search); ?>" placeholder="Поиск..." style="width:200px;" onkeydown="if(event.key==='Enter')applyFilters()">
        <button class="button" onclick="applyFilters()">🔍</button>
        <?php if($filter!=='all'||$search||$cf!=='all'||$sort!=='id_asc') echo '<a href="'.$base_url.'" class="button button-small">✕ Сбросить</a>'; ?>
    </div>
    <table class="wp-list-table widefat fixed striped"><thead><tr><td class="check-column"><input type="checkbox" id="select-all"></td><th>Slug</th><th>Название</th><th>Глав</th><th>Всего</th><th>Просм.</th><th>Загр.</th><th>Статус</th><th>Дата</th></tr></thead><tbody><?php
    if(empty($books)) echo '<tr><td colspan="9">Пусто</td></tr>';
    else foreach($books as $b):
        $lb=['new'=>['🐾 Новый','color:#007cba'],'parsing'=>['⏳ В процессе','color:#f0a030'],'done'=>['✅ Готово','color:#00a32a'],'error'=>['❌ Ошибка','color:#d63638']];
        $l=$lb[$b->status]??$lb['new'];
        echo '<tr><td class="check-column"><input type="checkbox" class="book-checkbox" value="'.esc_attr($b->slug).'"></td><td><code>'.esc_html($b->slug).'</code></td><td><a href="'.esc_url($b->url).'" target="_blank">'.esc_html($b->title).'</a></td><td>'.$b->chapters_count.'</td><td>'.$b->total_chapters.'</td><td>'.number_format($b->views,0,',',' ').'</td><td>'.$b->parsed_chapters.'</td><td><span style="'.$l[1].'">'.$l[0].'</span></td><td>'.($b->last_parsed_at?:'—').'</td></tr>';
    endforeach;
    ?></tbody></table>
    <?php if($total_pages>1) echo '<div style="text-align:center;margin:20px 0;">'.paginate_links(['base'=>add_query_arg('paged','%#%'),'format'=>'','current'=>$paged,'total'=>$total_pages,'prev_text'=>'←','next_text'=>'→']).'</div>'; ?>
    </div>
    <script>(function($){
        var running=false, slugs=<?php echo json_encode($all_slugs); ?>;
        function log(m){ $('#v2-log').show().append('<div>'+m+'</div>'); $('#v2-log').scrollTop($('#v2-log')[0].scrollHeight); }
        function prog(c,t,s){ $('#v2-progress').show(); $('#v2-cur').text(c); $('#v2-tot').text(t); $('#v2-status').text(s||''); $('#v2-bar').css('width',(t>0?Math.round(c/t*100):0)+'%'); }
        window.applyFilters=function(){ var p=[]; var s=$('#filter-status').val(); if(s!=='all') p.push('filter_status='+s); s=$('#chapters-filter').val(); if(s!=='all') p.push('chapters_filter='+s); s=$('#sort-by').val(); if(s!=='id_asc') p.push('sort_by='+s); var q=$('#search-input').val().trim(); if(q) p.push('search='+encodeURIComponent(q)); var url='<?php echo $base_url; ?>'; if(p.length) url+='&'+p.join('&'); window.location.href=url; };
        $('#select-all').on('change',function(){ $('.book-checkbox').prop('checked',$(this).prop('checked')); updSel(); });
        $(document).on('change','.book-checkbox',updSel);
        function updSel(){ var c=$('.book-checkbox:checked').length; $('#btn-parse-selected').prop('disabled',c===0).text('📥 Загрузить выбранные ('+c+')'); }
        $('#btn-scan').click(function(){ if(running)return; if(!confirm('Сканировать?'))return; running=true; log('🔍 Сканирование...'); scan(1,0,0,0); });
        function scan(page,lp,total,err){ $.post(ajaxurl,{action:'abs_ifreedom_v2_scan',page:page,last_page:lp,total:total,errors:err,_ajax_nonce:'<?php echo wp_create_nonce("abs_ifreedom_v2_nonce"); ?>'},function(r){ if(r.success){ log(r.data.message); if(r.data.finished){ log('✅ Готово! Книг: '+r.data.total); running=false; setTimeout(function(){location.reload()},2000); } else scan(r.data.page,r.data.last_page,r.data.total,r.data.errors); } }).fail(function(){log('❌ Ошибка');running=false;}); }
        $('#btn-parse-filtered').click(function(){ if(running)return; if(!slugs.length){alert('Нет книг для загрузки.');return;} if(!confirm('Загрузить '+slugs.length+' книг?'))return; running=true; log('📥 Загрузка...'); parse(slugs,0,0,1); });
        $('#btn-parse-selected').click(function(){ if(running)return; var s=$('.book-checkbox:checked').map(function(){return this.value;}).get(); if(!s.length)return; if(!confirm('Загрузить '+s.length+' книг?'))return; running=true; log('📥 Загрузка выбранных...'); parse(s,0,0,1); });
        function parse(sl,i,pc,sc){ $.post(ajaxurl,{action:'abs_ifreedom_v2_process',book_slugs:sl,current_index:i,processed_count:pc,start_chapter:sc||1,_ajax_nonce:'<?php echo wp_create_nonce("abs_ifreedom_v2_nonce"); ?>'},function(r){ if(r.success){ var d=r.data; prog(d.processed,d.total); log(d.log); if(d.finished){ log('✅ Готово!'); running=false; setTimeout(function(){location.reload()},2000); } else parse(sl,d.next_index,d.processed,d.start_chapter||1); } }).fail(function(){log('❌ Ошибка');running=false;}); }
        $('#btn-clear').click(function(){ if(!confirm('Удалить всё?'))return; $.post(ajaxurl,{action:'abs_ifreedom_v2_clear',_ajax_nonce:'<?php echo wp_create_nonce("abs_ifreedom_v2_nonce"); ?>'},function(){location.reload();}); });
        function updCron(){ $.post(ajaxurl,{action:'abs_ifreedom_v2_cron_toggle',cron_action:'status',_ajax_nonce:'<?php echo wp_create_nonce("abs_ifreedom_v2_nonce"); ?>'},function(r){ if(r.success&&r.data){ $('#cron-status').text(r.data.running?'🟢 Работает':'⚪ Остановлен'); } }); }
        $('#btn-cron-start').click(function(){ if(!confirm('Запустить фон?'))return; var s=$('#cron-slugs').val().split(',').map(function(x){return x.trim();}).filter(Boolean); $.post(ajaxurl,{action:'abs_ifreedom_v2_cron_toggle',cron_action:'start',book_slugs:s,_ajax_nonce:'<?php echo wp_create_nonce("abs_ifreedom_v2_nonce"); ?>'},function(){updCron();}); });
        $('#btn-cron-stop').click(function(){ if(!confirm('Остановить?'))return; $.post(ajaxurl,{action:'abs_ifreedom_v2_cron_toggle',cron_action:'stop',_ajax_nonce:'<?php echo wp_create_nonce("abs_ifreedom_v2_nonce"); ?>'},function(){updCron();}); });
        updCron(); setInterval(updCron,5000);
    })(jQuery);</script>
    <?php
}