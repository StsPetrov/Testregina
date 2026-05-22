<?php
get_header();
?>

<div class="container">
    <main class="middle-align">
    <div class="breadcrumbs" style="margin-bottom:15px;">
    <?php if (function_exists('yoast_breadcrumb')) { yoast_breadcrumb('<p id="breadcrumbs">','</p>'); } ?>
</div>
        <div class="row">
            <div class="col-lg-8 col-md-8">
                <?php while (have_posts()) : the_post();
                    $ranobe_id = get_the_ID();
                    $author = get_post_meta($ranobe_id, '_ranobe_author', true);
                    $status = get_post_meta($ranobe_id, '_ranobe_status', true);
                    $year = get_post_meta($ranobe_id, '_ranobe_year', true);
                    $language = get_post_meta($ranobe_id, '_ranobe_language', true);
                    $abs_book_id = get_post_meta($ranobe_id, '_ranobe_abs_book_id', true);
                    
                    $status_labels = ['ongoing' => '🔄 Онгоинг', 'completed' => '✅ Завершено', 'frozen' => '❄️ Заморожено'];
                    $lang_labels = ['jp' => '🇯🇵 Японский', 'cn' => '🇨🇳 Китайский', 'kr' => '🇰🇷 Корейский', 'en' => '🇬🇧 Английский', 'ru' => '🇷🇺 Русский'];
                    
                    $order = 'ASC';

$chapters = get_posts(array(
    'post_type' => 'chapter',
    'post_parent' => $ranobe_id,
    'posts_per_page' => -1,
    'orderby' => 'meta_value_num',
    'meta_key' => '_chapter_number',
    'order' => $order
));
                    
                    global $wpdb;
                    $audio_page_url = '';
                    if ($abs_book_id) {
                        $audio_page_id = $wpdb->get_var($wpdb->prepare(
                            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'abs_book_id' AND meta_value = %s",
                            $abs_book_id
                        ));
                        $audio_page_url = $audio_page_id ? get_permalink($audio_page_id) : '';
                    }
                    
                    $view_count = (int)$wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}abs_book_views WHERE ranobe_id = %d",
                        $ranobe_id
                    ));
                    
                    $avg_rating = $wpdb->get_var($wpdb->prepare(
                        "SELECT AVG(rating) FROM {$wpdb->prefix}abs_ratings WHERE book_id = %s",
                        $ranobe_id
                    ));
                    $rating_count = (int)$wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}abs_ratings WHERE book_id = %s",
                        $ranobe_id
                    ));
                    $avg_rating = $avg_rating ? round($avg_rating, 1) : 0;
                ?>
                
                <article class="ranobe-book">
                    <div class="ranobe-header" style="display:flex; gap:20px; margin-bottom:20px;">
                        <div class="ranobe-cover" style="flex-shrink:0; width:200px;">
                            <?php if (has_post_thumbnail()): ?>
                                <?php the_post_thumbnail('medium', ['style' => 'border-radius:12px; width:100%;']); ?>
                            <?php else: ?>
                                <div style="width:200px; height:280px; background:rgba(255,255,255,0.05); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:3rem;">📖</div>
                            <?php endif; ?>
                        </div>
                        <div class="ranobe-info" style="flex:1;">
                            <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:5px;">
                                <h1 style="color:#fff; margin:0; font-size:1.6rem;"><?php the_title(); ?></h1>
                                <?php 
$first_chapter = get_posts(array(
    'post_type' => 'chapter', 'post_parent' => $ranobe_id,
    'orderby' => 'meta_value_num', 'meta_key' => '_chapter_number', 'order' => 'ASC',
    'posts_per_page' => 1,
));
if (!empty($first_chapter)):
    $label = '▶ Начать читать';
    $continue_url = get_permalink($first_chapter[0]->ID);
    if (is_user_logged_in()) {
        global $wpdb;
        $last_chapter = $wpdb->get_var($wpdb->prepare(
            "SELECT chapter_number FROM {$wpdb->prefix}abs_reading_progress WHERE user_id = %d AND ranobe_id = %d",
            get_current_user_id(), $ranobe_id
        ));
        if ($last_chapter) {
            $chapter_posts = get_posts(array(
                'post_type' => 'chapter', 'post_parent' => $ranobe_id,
                'meta_key' => '_chapter_number', 'meta_value' => $last_chapter,
                'posts_per_page' => 1,
            ));
            if (!empty($chapter_posts)) {
                $continue_url = get_permalink($chapter_posts[0]->ID);
                $label = "▶ Продолжить чтение (глава {$last_chapter})";
            }
        }
    }
?>
    <a href="<?php echo esc_url($continue_url); ?>" style="display:inline-block; margin-top:8px; padding:8px 20px; background:linear-gradient(90deg,#0dcaf0,#5bc0de); color:#1b2039; border-radius:25px; text-decoration:none; font-weight:600; font-size:0.9rem;">
        <?php echo $label; ?>
    </a>
<?php endif; ?>
                                <button id="abs-favorite-btn-text" data-book-id="<?php echo $ranobe_id; ?>" data-type="text" style="background:transparent; border:none; font-size:2.5rem; cursor:pointer; color:rgba(255,255,255,0.5); flex-shrink:0;">♡</button>
                            </div>
                            
                            <!-- Рейтинг -->
                            <div class="book-rating" id="book-rating" style="display:flex; align-items:center; gap:10px; margin-bottom:10px; flex-wrap:wrap;">
                                <span style="color:rgba(255,255,255,0.7);">Рейтинг:</span>
                                <div class="stars" id="rating-stars-text" style="display:flex; gap:3px;">
                                    <span class="star" data-value="1">☆</span>
                                    <span class="star" data-value="2">☆</span>
                                    <span class="star" data-value="3">☆</span>
                                    <span class="star" data-value="4">☆</span>
                                    <span class="star" data-value="5">☆</span>
                                </div>
                                <span class="rating-value" id="rating-value-text" style="color:#ffc107; font-weight:bold;"><?php echo $avg_rating; ?></span>
                                <span class="rating-count" id="rating-count-text" style="color:rgba(255,255,255,0.5); font-size:0.8rem;">(<?php echo $rating_count; ?>)</span>
                            </div>
                            
                            <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:10px;">
                                <?php if ($status): ?><span style="background:rgba(255,255,255,0.1); padding:4px 12px; border-radius:20px; font-size:0.9rem;"><?php echo $status_labels[$status] ?? $status; ?></span><?php endif; ?>
                                <?php if ($year): ?><span style="background:rgba(255,255,255,0.1); padding:4px 12px; border-radius:20px; font-size:0.9rem;">📅 <?php echo $year; ?></span><?php endif; ?>
                                <?php if ($language): ?><span style="background:rgba(255,255,255,0.1); padding:4px 12px; border-radius:20px; font-size:0.9rem;"><?php echo $lang_labels[$language] ?? $language; ?></span><?php endif; ?>
                            </div>
                            
                            <!-- Кнопки поделиться -->
                            <div style="margin:10px 0; display:flex; flex-wrap:wrap; align-items:center; gap:6px;">
                                <span style="color:rgba(255,255,255,0.5); font-size:0.85rem;">Поделиться:</span>
                                <a href="https://t.me/share/url?url=<?php echo urlencode(get_permalink()); ?>&text=<?php echo urlencode(get_the_title()); ?>" target="_blank" rel="noopener" title="Telegram" style="display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; background:rgba(255,255,255,0.08); border-radius:50%; color:rgba(255,255,255,0.7); text-decoration:none;" onmouseover="this.style.background='#229ED9'; this.style.color='#fff'" onmouseout="this.style.background='rgba(255,255,255,0.08)'; this.style.color='rgba(255,255,255,0.7)'"><svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.161c-.18 1.897-.962 6.502-1.359 8.627-.168.9-.5 1.201-.82 1.23-.697.064-1.226-.46-1.901-.903-1.056-.692-1.653-1.123-2.678-1.799-1.185-.781-.417-1.21.258-1.911.177-.184 3.247-2.977 3.307-3.23.007-.032.015-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.139-5.062 3.345-.479.329-.913.489-1.302.481-.428-.009-1.252-.242-1.865-.441-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.831-2.529 6.998-3.015 3.333-1.386 4.025-1.627 4.477-1.635.099-.002.321.023.465.141.145.118.185.276.204.408.019.132.042.433.023.67z"/></svg></a>
                                <a href="https://vk.com/share.php?url=<?php echo urlencode(get_permalink()); ?>&title=<?php echo urlencode(get_the_title()); ?>" target="_blank" rel="noopener" title="ВКонтакте" style="display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; background:rgba(255,255,255,0.08); border-radius:50%; color:rgba(255,255,255,0.7); text-decoration:none;" onmouseover="this.style.background='#0077FF'; this.style.color='#fff'" onmouseout="this.style.background='rgba(255,255,255,0.08)'; this.style.color='rgba(255,255,255,0.7)'"><svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M22.5 12.2c.1-.4-.2-.8-.6-.8h-2.1c-.3 0-.6.1-.7.4-.9 1.8-2.1 3.4-2.6 3.8-.3.2-.4.2-.5 0-.1-.2-.1-.7-.1-1.1 0-1.2 0-2.7-.2-3.6-.1-.4-.4-.6-.8-.6H12c-.6 0-.9.4-.9.8 0 .9 1.2 1.1 1.3 3.5 0 .6 0 .9-.2 1.1-.3.3-.8-.2-1.8-1.9-1.1-1.9-2-4.2-2.1-4.4-.1-.2-.4-.4-.7-.4H5.6c-.4 0-.7.3-.7.7 0 .5 1.1 3.4 3.8 6.6 2.1 2.5 4.2 3.8 5.7 3.8 1.2 0 1.3-.3 1.3-1 0-1.6-.2-2.7.2-3.1.3-.3.6-.2 1.4.3 1 .8 1.6 1.5 2.2 2.4.2.4.5.6.9.6h2.1c.4 0 .7-.3.6-.7-.4-.9-1.3-2.1-2.3-3.1-.3-.3-.3-.5 0-.8.1 0 1.9-2.1 2.4-3z"/></svg></a>
                                <a href="https://wa.me/?text=<?php echo urlencode(get_the_title() . ' ' . get_permalink()); ?>" target="_blank" rel="noopener" title="WhatsApp" style="display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; background:rgba(255,255,255,0.08); border-radius:50%; color:rgba(255,255,255,0.7); text-decoration:none;" onmouseover="this.style.background='#25D366'; this.style.color='#fff'" onmouseout="this.style.background='rgba(255,255,255,0.08)'; this.style.color='rgba(255,255,255,0.7)'"><svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg></a>
                                <a href="https://max.ru/share?url=<?php echo urlencode(get_permalink()); ?>" target="_blank" rel="noopener" title="MAX" style="display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; background:rgba(255,255,255,0.08); border-radius:50%; color:rgba(255,255,255,0.7); text-decoration:none;" onmouseover="this.style.background='linear-gradient(135deg,#E94E35,#E91E8C)'; this.style.color='#fff'" onmouseout="this.style.background='rgba(255,255,255,0.08)'; this.style.color='rgba(255,255,255,0.7)'"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="10" r="9"/><path d="M4 18l4-3"/></svg></a>
                                <a href="javascript:void(0)" onclick="navigator.clipboard.writeText('<?php echo esc_url(get_permalink()); ?>'); this.textContent='✓'; setTimeout(()=>this.textContent='📋',2000)" title="Скопировать ссылку" style="display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; background:rgba(255,255,255,0.08); border-radius:50%; color:rgba(255,255,255,0.7); text-decoration:none;" onmouseover="this.style.background='rgba(255,255,255,0.2)'; this.style.color='#fff'" onmouseout="this.style.background='rgba(255,255,255,0.08)'; this.style.color='rgba(255,255,255,0.7)'">📋</a>
                            </div>
                            
                            <?php if ($author): ?>
                                <p style="color:#0dcaf0; font-size:1.1rem; margin:0 0 10px;">
                                    ✍️ <a href="/catalog?author=<?php echo urlencode($author); ?>" style="color:#0dcaf0; text-decoration:none;"><?php echo esc_html($author); ?></a>
                                </p>
                            <?php endif; ?>
                            
                            <div style="color:rgba(255,255,255,0.8); line-height:1.6; margin-bottom:15px;">
                                <?php the_content(); ?>
                            </div>
                            
                            <div style="margin-bottom:10px;">
                                <?php
                                $genres = wp_get_post_categories($ranobe_id);
                                foreach ($genres as $cat_id): 
                                    $cat = get_category($cat_id); ?>
                                    <a href="/catalog?genre=<?php echo urlencode($cat->name); ?>" style="display:inline-block; padding:4px 12px; background:rgba(13,202,240,0.2); color:#0dcaf0; border-radius:20px; font-size:0.8rem; text-decoration:none; margin:2px;"><?php echo esc_html($cat->name); ?></a>
                                <?php endforeach; ?>
                            </div>
                            
                            <div style="display:flex; gap:15px; flex-wrap:wrap; margin-bottom:15px;">
                                <?php if ($view_count > 0): ?>
                                    <span style="background:rgba(13,202,240,0.15); padding:6px 14px; border-radius:20px; font-size:0.9rem;">👁️ Открывали: <strong><?php echo $view_count; ?> раз</strong></span>
                                <?php endif; ?>
                                <?php if ($chapters): ?>
                                    <span style="background:rgba(13,202,240,0.15); padding:6px 14px; border-radius:20px; font-size:0.9rem;">📄 Всего глав: <strong><?php echo count($chapters); ?></strong></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Общая рамка как в аудиоплеере -->
                    <div class="audiobookshelf-player-container" style="margin-bottom:20px;">
                        <!-- Вкладки -->
                        <div style="display:flex; border-bottom:1px solid rgba(255,255,255,0.1);">
                            <span style="padding:14px 28px; color:#0dcaf0; border-bottom:3px solid #0dcaf0; font-size:1.1rem; font-weight:600;">📖 Читать</span>
                            <?php if ($audio_page_url): ?>
                                <a href="<?php echo esc_url($audio_page_url); ?>" style="padding:14px 28px; color:rgba(255,255,255,0.7); text-decoration:none; font-size:1.1rem; font-weight:600;">🎧 Слушать</a>
                            <?php else: ?>
                               
                            <?php endif; ?>
                        </div>
                        <?php echo do_shortcode('[abs_order_voice]'); ?>
                        <!-- Список глав -->
                        <div style="text-align:center;margin:10px 0;">
    <a href="https://pay.cloudtips.ru/p/db763c18" target="_blank" style="display:inline-block;background:linear-gradient(135deg,#ff9800,#ff5722);color:#fff;padding:12px 28px;border-radius:30px;text-decoration:none;font-weight:700;font-size:1rem;margin-top: 10px;">💰 Поддержать проект</a>
</div>
                        <div class="chapters-list">
                            <h4>Содержание</h4>
                            <ul id="chapter-list" style="list-style:none; padding:0; max-height:500px; overflow-y:auto; border-radius:12px; background:rgba(0,0,0,0.2);">
                                <?php if ($chapters): ?>
                                    <?php 
$current_volume = -1;
$display_num = 1;
foreach ($chapters as $ch):
    $num = get_post_meta($ch->ID, '_chapter_number', true);
    $vol = get_post_meta($ch->ID, '_chapter_volume', true);
    $words = count(preg_split('/\s+/', trim(strip_tags($ch->post_content))));
    if ($vol != $current_volume && $vol > 0):
        $current_volume = $vol;
        echo '<li style="color:#0dcaf0; padding:8px 15px; font-weight:600; font-size:0.85rem; border-bottom:1px solid rgba(255,255,255,0.05);">📚 Том ' . $current_volume . '</li>';
    endif;
?>
                                        <li style="display:flex; align-items:center; gap:12px; padding:12px 15px; border-bottom:1px solid rgba(255,255,255,0.05); cursor:pointer; color:rgba(255,255,255,0.8); transition:all 0.2s;" onmouseover="this.style.background='rgba(13,202,240,0.1)'; this.style.paddingLeft='20px'" onmouseout="this.style.background='transparent'; this.style.paddingLeft='15px'" onclick="window.location.href='<?php echo get_permalink($ch->ID); ?>'">
                                            <span class="track-num"><?php echo str_pad($display_num, 2, '0', STR_PAD_LEFT); $display_num++; ?></span>
                                            <span class="track-title"><?php echo $ch->post_title ? esc_html($ch->post_title) : 'Глава ' . $num; ?></span>
                                            <span class="track-duration loaded"></span>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li style="padding:30px; text-align:center; color:rgba(255,255,255,0.5);">Глав пока нет.</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                    
                    <?php if ($audio_page_url): ?>
                        <div style="background:linear-gradient(135deg,rgba(13,202,240,0.1),rgba(91,192,222,0.1)); border:1px solid rgba(13,202,240,0.25); border-radius:12px; padding:16px 20px; margin:0 0 20px 0; display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                            <span style="font-size:2rem;">🎧</span>
                            <div style="flex:1;">
                                <div style="color:#fff; font-weight:600; font-size:1rem; margin-bottom:4px;">Доступна аудиоверсия</div>
                                <div style="color:rgba(255,255,255,0.7); font-size:0.85rem; line-height:1.4;">Слушайте эту книгу в профессиональной озвучке — дома, в дороге или на прогулке.</div>
                            </div>
                            <a href="<?php echo esc_url($audio_page_url); ?>" style="background:linear-gradient(90deg,#0dcaf0,#5bc0de); color:#1b2039; padding:10px 20px; border-radius:25px; text-decoration:none; font-weight:600; font-size:0.9rem; white-space:nowrap;">🎧 Слушать аудиокнигу</a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!$audio_page_url): ?>
                        <div style="text-align:center; margin:0 0 20px 0;">
    <a href="https://pay.cloudtips.ru/p/db763c18" target="_blank" style="display:inline-block;background:linear-gradient(135deg,#ff9800,#ff5722);color:#fff;padding:12px 28px;border-radius:30px;text-decoration:none;font-weight:700;font-size:1rem;margin-top: 10px;">💰 Поддержать проект</a>
                        </div>
                    <?php endif; ?>
                    
                    <?php echo do_shortcode('[abs_related]'); ?>
                    <?php echo do_shortcode('[abs_similar]'); ?>
                    
                    <?php if (comments_open() || get_comments_number()): ?>
                        <div style="margin-top:40px;">
                            <?php comments_template(); ?>
                        </div>
                    <?php endif; ?>
                </article>
<?php echo abs_generate_book_seo_text(get_the_ID(), 'text'); ?>

                <?php endwhile; ?>
            </div>
            <div class="col-lg-4 col-md-4" id="sidebar">
                <?php get_sidebar(); ?>
            </div>
        </div>
    </main>
</div>

<script>
jQuery(document).ready(function($) {
    // Промотка списка глав к последней читаемой
    var lastChapter = <?php 
        $lc = 0;
        if (is_user_logged_in()) {
            global $wpdb;
            $lc = $wpdb->get_var($wpdb->prepare(
                "SELECT chapter_number FROM {$wpdb->prefix}abs_reading_progress WHERE user_id = %d AND ranobe_id = %d",
                get_current_user_id(), $ranobe_id
            ));
        }
        echo $lc ?: 0;
    ?>;
    if (lastChapter > 0) {
        setTimeout(function() {
            var list = document.querySelector('#chapter-list');
            if (!list) return;
            var items = list.querySelectorAll('li');
            for (var i = 0; i < items.length; i++) {
                var numEl = items[i].querySelector('.track-num');
                if (numEl) {
                    var num = parseInt(numEl.textContent);
                    if (num === lastChapter) {
                        list.scrollTop = items[i].offsetTop - list.offsetTop - 50;
                        items[i].style.background = 'rgba(13,202,240,0.15)';
                        break;
                    }
                }
            }
        }, 500);
    }

    // Избранное
    var favBtn = $('#abs-favorite-btn-text');
    if (favBtn.length) {
        $.get('<?php echo admin_url("admin-ajax.php"); ?>', {
            action: 'is_favorite', book_id: '<?php echo $ranobe_id; ?>', type: 'text'
        }, function(r) {
            if (r.success) { favBtn.html(r.data.favorite ? '❤️' : '♡'); favBtn.toggleClass('active', r.data.favorite); }
        });
        favBtn.on('click', function() {
            $.post('<?php echo admin_url("admin-ajax.php"); ?>', {
                action: 'toggle_favorite', book_id: '<?php echo $ranobe_id; ?>', type: 'text'
            }, function(r) {
                if (r.success) { favBtn.html(r.data.favorite ? '❤️' : '♡'); favBtn.toggleClass('active', r.data.favorite); }
            });
        });
    }
    
    // Рейтинг
    $('#rating-stars-text .star').on('click', function() {
        var rating = parseInt($(this).data('value'));
        $('#rating-stars-text .star').each(function(i) {
            $(this).toggleClass('active', i < rating);
            $(this).text(i < rating ? '★' : '☆');
        });
        $.post('<?php echo admin_url("admin-ajax.php"); ?>', {
            action: 'save_abs_rating', book_id: '<?php echo $ranobe_id; ?>', rating: rating
        }, function(r) {
            if (r.success) {
                $('#rating-value-text').text(Number(r.data.average).toFixed(1));
                $('#rating-count-text').text('(' + r.data.count + ')');
            }
        });
    });
    
    // Загрузка рейтинга
    if (<?php echo $avg_rating; ?> > 0) {
        var r = Math.round(<?php echo $avg_rating; ?>);
        $('#rating-stars-text .star').each(function(i) {
            $(this).toggleClass('active', i < r);
            $(this).text(i < r ? '★' : '☆');
        });
    }
});
</script>

<?php get_footer(); ?>