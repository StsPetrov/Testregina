<?php
/**
 * Шаблон читалки главы (single-chapter.php)
 * 
 * Фичи:
 * - Верхняя панель читалки (sticky)
 * - Выпадающий список глав с поиском
 * - Панель настроек чтения (шрифт, междустрочный, ширина)
 * - Ночной/дневной режим (задел)
 * - Навигация Пред/След глава
 * - Текст в узкой колонке (макс. 700px)
 * - Кнопки «Поделиться»
 * - Шорткоды похожих и связанных книг
 * - Комментарии
 * - Нижняя мобильная панель навигации
 * - Сохранение прогресса чтения
 */

get_header();
?>

<div class="breadcrumbs" style="max-width:700px;margin:0 auto 15px;padding:0 20px;">
    <?php if (function_exists('yoast_breadcrumb')) { yoast_breadcrumb('<p id="breadcrumbs">','</p>'); } ?>
</div>

<?php
while (have_posts()) : the_post();
    $ranobe_id = get_post_field('post_parent');
    $ranobe_title = $ranobe_id ? get_the_title($ranobe_id) : '';
    $ranobe_url = $ranobe_id ? get_permalink($ranobe_id) : home_url();
    $volume = get_post_meta(get_the_ID(), '_chapter_volume', true);
    $chapter_num = get_post_meta(get_the_ID(), '_chapter_number', true);

    // Запись просмотра
    if ($ranobe_id) abs_track_ranobe_view($ranobe_id);

    // Сохраняем прогресс чтения
    if (is_user_logged_in() && $ranobe_id && $chapter_num) {
        $user_id = get_current_user_id();
        $all_chapters = get_posts(array(
            'post_type' => 'chapter',
            'post_parent' => $ranobe_id,
            'posts_per_page' => -1,
            'fields' => 'ids',
            'orderby' => 'meta_value_num',
            'meta_key' => '_chapter_number',
            'order' => 'ASC',
        ));
        $total_chapters = count($all_chapters);
        global $wpdb;
        $wpdb->replace($wpdb->prefix . 'abs_reading_progress', array(
            'user_id' => $user_id,
            'ranobe_id' => $ranobe_id,
            'chapter_number' => $chapter_num,
            'total_chapters' => $total_chapters,
        ));
    }

    // Все главы для выпадающего списка
    $all_chapters_data = array();
    if ($ranobe_id) {
        $chapters_query = get_posts(array(
            'post_type' => 'chapter',
            'post_parent' => $ranobe_id,
            'posts_per_page' => -1,
            'orderby' => 'meta_value_num',
            'meta_key' => '_chapter_number',
            'order' => 'ASC',
        ));
        foreach ($chapters_query as $ch) {
            $all_chapters_data[] = array(
                'id' => $ch->ID,
                'number' => get_post_meta($ch->ID, '_chapter_number', true),
                'title' => $ch->post_title,
                'url' => get_permalink($ch->ID),
            );
        }
    }

        // Определяем источник
    $source = get_post_meta($ranobe_id, '_ranobe_source', true);

    if ($source === 'ifreedom') {
        // Для ifreedom — главы в обратном порядке (DESC)
        $prev_chapter = get_posts(array(
            'post_type' => 'chapter', 'post_parent' => $ranobe_id, 'posts_per_page' => 1,
            'orderby' => 'meta_value_num', 'meta_key' => '_chapter_number', 'order' => 'ASC',
            'meta_query' => array(array('key' => '_chapter_number', 'value' => $chapter_num, 'compare' => '>', 'type' => 'NUMERIC'))
        ));
        $next_chapter = get_posts(array(
            'post_type' => 'chapter', 'post_parent' => $ranobe_id, 'posts_per_page' => 1,
            'orderby' => 'meta_value_num', 'meta_key' => '_chapter_number', 'order' => 'DESC',
            'meta_query' => array(array('key' => '_chapter_number', 'value' => $chapter_num, 'compare' => '<', 'type' => 'NUMERIC'))
        ));
    } else {
        // Стандартный порядок (ASC)
        $prev_chapter = get_posts(array(
            'post_type' => 'chapter', 'post_parent' => $ranobe_id, 'posts_per_page' => 1,
            'orderby' => 'meta_value_num', 'meta_key' => '_chapter_number', 'order' => 'DESC',
            'meta_query' => array(array('key' => '_chapter_number', 'value' => $chapter_num, 'compare' => '<', 'type' => 'NUMERIC'))
        ));
        $next_chapter = get_posts(array(
            'post_type' => 'chapter', 'post_parent' => $ranobe_id, 'posts_per_page' => 1,
            'orderby' => 'meta_value_num', 'meta_key' => '_chapter_number', 'order' => 'ASC',
            'meta_query' => array(array('key' => '_chapter_number', 'value' => $chapter_num, 'compare' => '>', 'type' => 'NUMERIC'))
        ));
    }
    ?>

    <!-- ПАНЕЛЬ ЧИТАЛКИ (sticky) -->
    <div class="chapter-reader-toolbar" id="reader-toolbar">
        <div class="toolbar-inner">
            <div class="toolbar-left">
                <a href="<?php echo esc_url($ranobe_url); ?>" class="toolbar-back-btn" title="Вернуться к книге">
                    ← К книге
                </a>
            </div>
            <div class="toolbar-center">
                <span class="toolbar-book-title">📖 <?php echo esc_html($ranobe_title); ?></span>
            </div>
            <div class="toolbar-right">
                <!-- Кнопка темы -->
            <button class="toolbar-icon-btn" id="reader-theme-toggle" title="День/Ночь" aria-label="Переключить тему">
                🌙
            </button>
            <!-- Кнопка настроек -->
            <button class="toolbar-icon-btn" id="reader-settings-toggle" title="Настройки чтения" aria-label="Настройки чтения">
                Aa
            </button>
                <!-- Кнопка списка глав -->
                <button class="toolbar-icon-btn" id="chapter-list-toggle" title="Список глав" aria-label="Список глав">
                    📋 <?php echo $chapter_num; ?> / <?php echo count($all_chapters_data); ?>
                </button>
            </div>
        </div>

        <!-- ПАНЕЛЬ НАСТРОЕК ЧТЕНИЯ -->
        <div class="reader-settings-panel" id="reader-settings-panel" style="display:none;">
            <div class="settings-section">
                <span class="settings-label">Размер шрифта</span>
                <div class="settings-buttons">
                    <button class="setting-btn" data-setting="fontSize" data-value="14">14px</button>
                    <button class="setting-btn active" data-setting="fontSize" data-value="16">16px</button>
                    <button class="setting-btn" data-setting="fontSize" data-value="18">18px</button>
                    <button class="setting-btn" data-setting="fontSize" data-value="20">20px</button>
                    <button class="setting-btn" data-setting="fontSize" data-value="22">22px</button>
                    <button class="setting-btn" data-setting="fontSize" data-value="24">24px</button>
                </div>
            </div>
            <div class="settings-section">
                <span class="settings-label">Междустрочный интервал</span>
                <div class="settings-buttons">
                    <button class="setting-btn" data-setting="lineHeight" data-value="1.4">1.4</button>
                    <button class="setting-btn active" data-setting="lineHeight" data-value="1.6">1.6</button>
                    <button class="setting-btn" data-setting="lineHeight" data-value="1.8">1.8</button>
                    <button class="setting-btn" data-setting="lineHeight" data-value="2.0">2.0</button>
                    <button class="setting-btn" data-setting="lineHeight" data-value="2.4">2.4</button>
                </div>
            </div>
            <div class="settings-section">
                <span class="settings-label">Ширина текста</span>
                <div class="settings-buttons">
                    <button class="setting-btn" data-setting="maxWidth" data-value="550">Узко</button>
                    <button class="setting-btn active" data-setting="maxWidth" data-value="700">Средне</button>
                    <button class="setting-btn" data-setting="maxWidth" data-value="900">Широко</button>
                    <button class="setting-btn" data-setting="maxWidth" data-value="1100">Полная</button>
                </div>
            </div>
            <div class="settings-section">
                <span class="settings-label">Выравнивание</span>
                <div class="settings-buttons">
                    <button class="setting-btn active" data-setting="textAlign" data-value="left">По левому</button>
                    <button class="setting-btn" data-setting="textAlign" data-value="justify">По ширине</button>
                </div>
            </div>
            <div class="settings-section">
                <span class="settings-label">Отступ абзаца</span>
                <div class="settings-buttons">
                    <button class="setting-btn" data-setting="textIndent" data-value="0">Без отступа</button>
                    <button class="setting-btn active" data-setting="textIndent" data-value="1.5">Средний</button>
                    <button class="setting-btn" data-setting="textIndent" data-value="3">Большой</button>
                </div>
            </div>
            <div class="settings-reset">
                <button class="setting-reset-btn" id="reader-settings-reset">↺ Сбросить настройки</button>
            </div>
        </div>

        <!-- ВЫПАДАЮЩИЙ СПИСОК ГЛАВ -->
        <div class="chapter-list-panel" id="chapter-list-panel" style="display:none;">
            <div class="chapter-list-search">
                <input type="text" id="chapter-search-input" placeholder="🔍 Поиск главы..." autocomplete="off">
            </div>
            <div class="chapter-list-scroll" id="chapter-list-scroll">
                <?php foreach ($all_chapters_data as $ch): 
                    $is_current = ($ch['number'] == $chapter_num);
                    $chapter_label = $ch['title'] ?: "Глава {$ch['number']}";
                    ?>
                    <a href="<?php echo esc_url($ch['url']); ?>" 
                       class="chapter-list-item <?php echo $is_current ? 'current' : ''; ?>"
                       data-chapter-number="<?php echo $ch['number']; ?>"
                       data-chapter-title="<?php echo esc_attr(mb_strtolower($ch['title'])); ?>">
                        <span class="chapter-item-num"><?php echo str_pad($ch['number'], 2, '0', STR_PAD_LEFT); ?></span>
                        <span class="chapter-item-title"><?php echo esc_html($chapter_label); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ОСНОВНОЙ КОНТЕНТ -->
    <div class="chapter-reader-wrapper">
        <div class="chapter-reader-container">
            
            <!-- Заголовок книги -->
            <?php if ($ranobe_id): ?>
                <div class="chapter-book-link-row">
                    📖 <a href="<?php echo esc_url($ranobe_url); ?>"><?php echo esc_html($ranobe_title); ?></a>
                </div>
            <?php endif; ?>

            <!-- Заголовок главы -->
            <h1 class="chapter-reader-title">
                <?php 
                if ($volume && $chapter_num) {
                    echo "Том {$volume} • Глава {$chapter_num}";
                } elseif ($chapter_num) {
                    echo "Глава {$chapter_num}";
                }
                $chapter_title = get_the_title();
                if ($chapter_title && $chapter_title != $ranobe_title) {
                    echo ' — ' . esc_html($chapter_title);
                }
                ?>
            </h1>

            <!-- Мета-информация -->
            <div class="chapter-meta-info">
                <?php if ($chapter_num): ?>
                    <span class="chapter-meta-badge"><?php echo $chapter_num; ?> из <?php echo count($all_chapters_data); ?> глав</span>
                <?php endif; ?>
                <span class="chapter-meta-badge"><?php echo count(preg_split('/\s+/', trim(strip_tags(get_the_content())))); ?> слов</span>
            </div>

            <!-- ТЕКСТ ГЛАВЫ -->
            <div class="chapter-reader-content" id="reader-content">
                <?php 
                // Выводим контент, оборачивая каждый параграф в <p>
                $content = get_the_content();
                // Заменяем двойные переносы строк на параграфы
                $content = wpautop($content);
                echo $content;
                ?>
            </div>

            <!-- НАВИГАЦИЯ ПРЕД/СЛЕД -->
            <div class="chapter-nav-bottom">
                <div class="chapter-nav-item">
                    <?php if (!empty($prev_chapter)): ?>
                        <a href="<?php echo get_permalink($prev_chapter[0]->ID); ?>" class="chapter-nav-btn prev-btn">
                            ◀ Предыдущая глава
                        </a>
                    <?php endif; ?>
                </div>
                <div class="chapter-nav-center">
                    <button class="chapter-nav-list-btn" id="chapter-list-toggle-bottom">
                        📋 Список глав
                    </button>
                </div>
                <div class="chapter-nav-item chapter-nav-right">
                    <?php if (!empty($next_chapter)): ?>
                        <a href="<?php echo get_permalink($next_chapter[0]->ID); ?>" class="chapter-nav-btn next-btn">
                            Следующая глава ▶
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- КНОПКИ ПОДЕЛИТЬСЯ -->
            <div class="chapter-share-block">
                <span class="share-label">Поделиться:</span>
                <a href="https://t.me/share/url?url=<?php echo urlencode(get_permalink()); ?>&text=<?php echo urlencode(get_the_title()); ?>" target="_blank" rel="noopener" title="Telegram" class="share-icon-btn tg-btn">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.161c-.18 1.897-.962 6.502-1.359 8.627-.168.9-.5 1.201-.82 1.23-.697.064-1.226-.46-1.901-.903-1.056-.692-1.653-1.123-2.678-1.799-1.185-.781-.417-1.21.258-1.911.177-.184 3.247-2.977 3.307-3.23.007-.032.015-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.139-5.062 3.345-.479.329-.913.489-1.302.481-.428-.009-1.252-.242-1.865-.441-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.831-2.529 6.998-3.015 3.333-1.386 4.025-1.627 4.477-1.635.099-.002.321.023.465.141.145.118.185.276.204.408.019.132.042.433.023.67z"/></svg>
                </a>
                <a href="https://vk.com/share.php?url=<?php echo urlencode(get_permalink()); ?>&title=<?php echo urlencode(get_the_title()); ?>" target="_blank" rel="noopener" title="ВКонтакте" class="share-icon-btn vk-btn">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M22.5 12.2c.1-.4-.2-.8-.6-.8h-2.1c-.3 0-.6.1-.7.4-.9 1.8-2.1 3.4-2.6 3.8-.3.2-.4.2-.5 0-.1-.2-.1-.7-.1-1.1 0-1.2 0-2.7-.2-3.6-.1-.4-.4-.6-.8-.6H12c-.6 0-.9.4-.9.8 0 .9 1.2 1.1 1.3 3.5 0 .6 0 .9-.2 1.1-.3.3-.8-.2-1.8-1.9-1.1-1.9-2-4.2-2.1-4.4-.1-.2-.4-.4-.7-.4H5.6c-.4 0-.7.3-.7.7 0 .5 1.1 3.4 3.8 6.6 2.1 2.5 4.2 3.8 5.7 3.8 1.2 0 1.3-.3 1.3-1 0-1.6-.2-2.7.2-3.1.3-.3.6-.2 1.4.3 1 .8 1.6 1.5 2.2 2.4.2.4.5.6.9.6h2.1c.4 0 .7-.3.6-.7-.4-.9-1.3-2.1-2.3-3.1-.3-.3-.3-.5 0-.8.1 0 1.9-2.1 2.4-3z"/></svg>
                </a>
                <a href="https://wa.me/?text=<?php echo urlencode(get_the_title() . ' ' . get_permalink()); ?>" target="_blank" rel="noopener" title="WhatsApp" class="share-icon-btn wa-btn">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                </a>
                <a href="https://max.ru/share?url=<?php echo urlencode(get_permalink()); ?>" target="_blank" rel="noopener" title="MAX" class="share-icon-btn max-btn">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="10" r="9"/><path d="M4 18l4-3"/></svg>
                </a>
                <a href="javascript:void(0)" onclick="navigator.clipboard.writeText('<?php echo esc_url(get_permalink()); ?>'); this.textContent='✓'; setTimeout(()=>this.textContent='📋',2000)" title="Скопировать ссылку" class="share-icon-btn copy-btn">📋</a>
            </div>

            <!-- ШОРТКОДЫ -->
            <div class="chapter-shortcodes">
                <?php echo do_shortcode('[abs_similar]'); ?>
                <?php echo do_shortcode('[abs_related]'); ?>
            </div>

            <!-- КОММЕНТАРИИ -->
            <?php if (comments_open() || get_comments_number()): ?>
                <div class="chapter-comments">
                    <?php comments_template(); ?>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- МОБИЛЬНАЯ НИЖНЯЯ ПАНЕЛЬ НАВИГАЦИИ -->
    <div class="chapter-mobile-nav" id="mobile-nav">
        <div class="mobile-nav-inner">
            <?php if (!empty($prev_chapter)): ?>
                <a href="<?php echo get_permalink($prev_chapter[0]->ID); ?>" class="mobile-nav-btn prev-mob-btn">
                    ◀
                </a>
            <?php else: ?>
                <span class="mobile-nav-btn disabled">◀</span>
            <?php endif; ?>

            <button class="mobile-nav-btn chapter-select-btn" id="mobile-chapter-toggle">
                📋 Глава <?php echo $chapter_num; ?>
            </button>

            <?php if (!empty($next_chapter)): ?>
                <a href="<?php echo get_permalink($next_chapter[0]->ID); ?>" class="mobile-nav-btn next-mob-btn">
                    ▶
                </a>
            <?php else: ?>
                <span class="mobile-nav-btn disabled">▶</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- МОБИЛЬНЫЙ СПИСОК ГЛАВ -->
    <div class="mobile-chapter-list" id="mobile-chapter-list" style="display:none;">
        <div class="mobile-chapter-list-header">
            <span>Список глав</span>
            <button class="mobile-chapter-list-close" id="mobile-chapter-list-close">✕</button>
        </div>
        <div class="mobile-chapter-search">
            <input type="text" id="mobile-chapter-search-input" placeholder="🔍 Поиск главы..." autocomplete="off">
        </div>
        <div class="mobile-chapter-list-scroll" id="mobile-chapter-list-scroll">
            <?php foreach ($all_chapters_data as $ch): 
                $is_current = ($ch['number'] == $chapter_num);
                $chapter_label = $ch['title'] ?: "Глава {$ch['number']}";
                ?>
                <a href="<?php echo esc_url($ch['url']); ?>" 
                   class="mobile-chapter-item <?php echo $is_current ? 'current' : ''; ?>"
                   data-chapter-number="<?php echo $ch['number']; ?>"
                   data-chapter-title="<?php echo esc_attr(mb_strtolower($ch['title'])); ?>">
                    <span class="mobile-chapter-num"><?php echo str_pad($ch['number'], 2, '0', STR_PAD_LEFT); ?></span>
                    <span class="mobile-chapter-title"><?php echo esc_html($chapter_label); ?></span>
                    <?php if ($is_current): ?>
                        <span class="mobile-chapter-current-badge">←</span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <style>
    /* ===== ОСНОВНЫЕ ПЕРЕМЕННЫЕ ===== */
:root, [data-reader-theme="dark"] {
    --reader-bg: #1b2039;
    --reader-block-bg: rgba(26, 26, 46, 0.8);
    --reader-text: #e0e0e0;
    --reader-heading: #ffffff;
    --reader-accent: #0dcaf0;
    --reader-accent-light: #5bc0de;
    --reader-border: rgba(255, 255, 255, 0.1);
    --reader-border-light: rgba(255, 255, 255, 0.05);
    --reader-font-size: 16px;
    --reader-line-height: 1.6;
    --reader-max-width: 700px;
    --reader-text-align: left;
    --reader-text-indent: 1.5em;
    --reader-paragraph-spacing: 1em;
}

[data-reader-theme="light"] {
    --reader-bg: #f5f5f5;
    --reader-block-bg: #ffffff;
    --reader-text: #333333;
    --reader-heading: #1a1a1a;
    --reader-accent: #0077aa;
    --reader-accent-light: #0099cc;
    --reader-border: rgba(0, 0, 0, 0.1);
    --reader-border-light: rgba(0, 0, 0, 0.05);
}

    /* ===== КОНТЕЙНЕР СТРАНИЦЫ ===== */
    .chapter-reader-wrapper {
    background: var(--reader-bg);
    padding: 0 20px 0;
    overflow: auto;
}

    .chapter-reader-container {
        max-width: 100%;
        margin: 0 auto;
        padding: 0px 0 40px; /* отступ сверху для sticky-панели */
    }

    /* ===== ПАНЕЛЬ ЧИТАЛКИ (STICKY) ===== */
    .chapter-reader-toolbar {
position: fixed;
    top: 60px; /* ← замени на точную высоту шапки */
    left: 0;
    right: 0;
    z-index: 999;
    background: rgba(26, 26, 46, 0.95);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-bottom: 1px solid var(--reader-border);
    padding: 10px 0;
    }

    .toolbar-inner {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
    }

    .toolbar-left {
        flex-shrink: 0;
    }

    .toolbar-back-btn {
        color: var(--reader-accent);
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
        padding: 6px 14px;
        border-radius: 20px;
        background: rgba(13, 202, 240, 0.1);
        transition: all 0.2s;
        white-space: nowrap;
    }
    .toolbar-back-btn:hover {
        background: rgba(13, 202, 240, 0.2);
        color: #fff;
    }

    .toolbar-center {
        flex: 1;
        text-align: center;
        overflow: hidden;
    }

    .toolbar-book-title {
        color: var(--reader-heading);
        font-size: 0.95rem;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: block;
    }

    .toolbar-right {
        display: flex;
        gap: 8px;
        flex-shrink: 0;
    }

    .toolbar-icon-btn {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.15);
        color: #fff;
        padding: 6px 14px;
        border-radius: 20px;
        cursor: pointer;
        font-size: 0.85rem;
        font-weight: 600;
        transition: all 0.2s;
        white-space: nowrap;
    }
    .toolbar-icon-btn:hover {
        background: var(--reader-accent);
        border-color: var(--reader-accent);
        color: #1b2039;
    }

    /* ===== ПАНЕЛЬ НАСТРОЕК ===== */
    .reader-settings-panel {
        max-width: 500px;
        margin: 10px auto 0;
        background: rgba(26, 26, 46, 0.98);
        border: 1px solid var(--reader-border);
        border-radius: 16px;
        padding: 20px;
        display: none;
    }

    .settings-section {
        margin-bottom: 16px;
    }
    .settings-section:last-of-type {
        margin-bottom: 10px;
    }

    .settings-label {
        display: block;
        color: rgba(255, 255, 255, 0.6);
        font-size: 0.75rem;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .settings-buttons {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }

    .setting-btn {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.15);
        color: #fff;
        padding: 6px 14px;
        border-radius: 20px;
        cursor: pointer;
        font-size: 0.8rem;
        transition: all 0.2s;
    }
    .setting-btn:hover {
        background: rgba(13, 202, 240, 0.2);
        border-color: var(--reader-accent);
    }
    .setting-btn.active {
        background: var(--reader-accent);
        border-color: var(--reader-accent);
        color: #1b2039;
        font-weight: 700;
    }

    .setting-reset-btn {
        background: transparent;
        border: 1px solid rgba(255, 255, 255, 0.15);
        color: rgba(255, 255, 255, 0.6);
        padding: 8px 20px;
        border-radius: 20px;
        cursor: pointer;
        font-size: 0.8rem;
        transition: all 0.2s;
        width: 100%;
    }
    .setting-reset-btn:hover {
        background: rgba(255, 68, 68, 0.2);
        border-color: #ff4444;
        color: #ff6666;
    }

    /* ===== ВЫПАДАЮЩИЙ СПИСОК ГЛАВ (ДЕСКТОП) ===== */
    .chapter-list-panel {
        max-width: 500px;
        margin: 10px auto 0;
        background: rgba(26, 26, 46, 0.98);
        border: 1px solid var(--reader-border);
        border-radius: 16px;
        overflow: hidden;
        display: none;
    }

    .chapter-list-search {
        padding: 12px;
        border-bottom: 1px solid var(--reader-border);
        position: sticky;
        top: 0;
        background: rgba(26, 26, 46, 0.98);
        z-index: 2;
    }

    .chapter-list-search input {
        width: 100%;
        padding: 10px 16px;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 25px;
        color: #fff;
        font-size: 0.9rem;
        box-sizing: border-box;
    }
    .chapter-list-search input:focus {
        outline: none;
        border-color: var(--reader-accent);
    }

    .chapter-list-scroll {
        max-height: 350px;
        overflow-y: auto;
    }
    .chapter-list-scroll::-webkit-scrollbar {
        width: 4px;
    }
    .chapter-list-scroll::-webkit-scrollbar-thumb {
        background: var(--reader-accent);
        border-radius: 2px;
    }

    .chapter-list-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 16px;
        color: #fff;
        text-decoration: none;
        border-bottom: 1px solid var(--reader-border-light);
        transition: all 0.2s;
    }
    .chapter-list-item:hover {
        background: rgba(13, 202, 240, 0.1);
    }
    .chapter-list-item.current {
        background: rgba(13, 202, 240, 0.15);
        border-left: 3px solid var(--reader-accent);
        color: var(--reader-accent);
        font-weight: 600;
    }
    .chapter-list-item.hidden {
        display: none;
    }

    .chapter-item-num {
        color: var(--reader-accent);
        font-size: 0.8rem;
        font-weight: 600;
        min-width: 28px;
    }

    .chapter-item-title {
        flex: 1;
        font-size: 0.9rem;
    }

    /* ===== ЗАГОЛОВОК И МЕТА ===== */
    .chapter-book-link-row {
        text-align: center;
        margin-bottom: 20px;
    }
    .chapter-book-link-row a {
        color: var(--reader-accent);
        text-decoration: none;
        font-size: 1rem;
        font-weight: 600;
        padding: 6px 16px;
        background: rgba(13, 202, 240, 0.1);
        border-radius: 20px;
        display: inline-block;
        transition: all 0.2s;
    }
    .chapter-book-link-row a:hover {
        background: rgba(13, 202, 240, 0.2);
    }

    .chapter-reader-title {
        color: var(--reader-heading);
        font-size: 1.5rem;
        text-align: center;
        margin: 0 auto 12px;
        max-width: var(--reader-max-width);
        font-weight: 700;
    }

    .chapter-meta-info {
        text-align: center;
        margin-bottom: 30px;
        display: flex;
        justify-content: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .chapter-meta-badge {
        background: rgba(255, 255, 255, 0.08);
        color: rgba(255, 255, 255, 0.6);
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
    }

    /* ===== ТЕКСТ ГЛАВЫ ===== */
    .chapter-reader-content {
        max-width: var(--reader-max-width);
        margin: 0 auto 40px;
        font-size: var(--reader-font-size);
        line-height: var(--reader-line-height);
        text-align: var(--reader-text-align);
        color: var(--reader-text);
        transition: all 0.3s ease;
    }

    .chapter-reader-content p {
        margin-bottom: var(--reader-paragraph-spacing);
        text-indent: var(--reader-text-indent);
    }

    /* ===== НАВИГАЦИЯ ПРЕД/СЛЕД ===== */
    .chapter-nav-bottom {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        max-width: var(--reader-max-width);
        margin: 0 auto 30px;
        padding-top: 20px;
        border-top: 1px solid var(--reader-border);
    }

    .chapter-nav-item {
        flex: 1;
    }
    .chapter-nav-right {
        text-align: right;
    }
    .chapter-nav-center {
        flex-shrink: 0;
    }

    .chapter-nav-btn {
        display: inline-block;
        padding: 10px 20px;
        border-radius: 25px;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 600;
        transition: all 0.2s;
        white-space: nowrap;
    }
    .prev-btn {
        background: rgba(255, 255, 255, 0.08);
        color: #fff;
    }
    .prev-btn:hover {
        background: rgba(255, 255, 255, 0.15);
    }
    .next-btn {
        background: linear-gradient(90deg, var(--reader-accent), var(--reader-accent-light));
        color: #1b2039;
    }
    .next-btn:hover {
        transform: scale(1.02);
        box-shadow: 0 4px 12px rgba(13, 202, 240, 0.3);
    }

    .chapter-nav-list-btn {
        background: transparent;
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #fff;
        padding: 10px 20px;
        border-radius: 25px;
        cursor: pointer;
        font-size: 0.85rem;
        transition: all 0.2s;
    }
    .chapter-nav-list-btn:hover {
        background: rgba(255, 255, 255, 0.1);
    }

    /* ===== КНОПКИ ПОДЕЛИТЬСЯ ===== */
    .chapter-share-block {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        flex-wrap: wrap;
        max-width: var(--reader-max-width);
        margin: 0 auto 30px;
        padding: 16px 0;
        border-top: 1px solid var(--reader-border);
    }

    .share-label {
        color: rgba(255, 255, 255, 0.5);
        font-size: 0.8rem;
        margin-right: 4px;
    }

    .share-icon-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        background: rgba(255, 255, 255, 0.08);
        border-radius: 50%;
        color: rgba(255, 255, 255, 0.7);
        text-decoration: none;
        transition: all 0.2s;
    }
    .share-icon-btn:hover {
        transform: scale(1.1);
    }
    .tg-btn:hover { background: #229ED9; color: #fff; }
    .vk-btn:hover { background: #0077FF; color: #fff; }
    .wa-btn:hover { background: #25D366; color: #fff; }
    .max-btn:hover { background: linear-gradient(135deg, #E94E35, #E91E8C); color: #fff; }
    .copy-btn:hover { background: rgba(255, 255, 255, 0.2); color: #fff; }

    /* ===== ШОРТКОДЫ ===== */
    .chapter-shortcodes {
        max-width: 1100px;
        margin: 0 auto;
    }

    /* ===== КОММЕНТАРИИ ===== */
    .chapter-comments {
        max-width: var(--reader-max-width);
        margin: 40px auto 0;
    }

    /* ===== МОБИЛЬНАЯ ПАНЕЛЬ ===== */
    .chapter-mobile-nav {
        display: none;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        background: rgba(26, 26, 46, 0.95);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border-top: 1px solid var(--reader-border);
        padding: 8px 0 12px;
    }

    .mobile-nav-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 16px;
        gap: 10px;
    }

    .mobile-nav-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
        text-decoration: none;
        font-size: 1.2rem;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
    }
    .mobile-nav-btn:hover {
        background: var(--reader-accent);
    }
    .mobile-nav-btn.disabled {
        opacity: 0.3;
        cursor: default;
        pointer-events: none;
    }

    .chapter-select-btn {
        width: auto;
        padding: 0 20px;
        border-radius: 25px;
        font-size: 0.9rem;
        font-weight: 600;
    }

    /* Мобильный список глав */
    .mobile-chapter-list {
        position: fixed;
        bottom: 70px;
        left: 0;
        right: 0;
        z-index: 999;
        background: rgba(26, 26, 46, 0.98);
        border-radius: 16px 16px 0 0;
        max-height: 60vh;
        display: flex;
        flex-direction: column;
    }

    .mobile-chapter-list-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 16px;
        border-bottom: 1px solid var(--reader-border);
        color: #fff;
        font-weight: 600;
    }

    .mobile-chapter-list-close {
        background: none;
        border: none;
        color: #fff;
        font-size: 1.2rem;
        cursor: pointer;
        padding: 4px 8px;
    }

    .mobile-chapter-search {
        padding: 10px 16px;
        border-bottom: 1px solid var(--reader-border);
    }

    .mobile-chapter-search input {
        width: 100%;
        padding: 10px 16px;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 25px;
        color: #fff;
        font-size: 0.9rem;
        box-sizing: border-box;
    }

    .mobile-chapter-list-scroll {
        overflow-y: auto;
        flex: 1;
        padding: 8px 0;
    }

    .mobile-chapter-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        color: #fff;
        text-decoration: none;
        border-bottom: 1px solid var(--reader-border-light);
    }
    .mobile-chapter-item.current {
        background: rgba(13, 202, 240, 0.15);
        color: var(--reader-accent);
        font-weight: 600;
    }
    .mobile-chapter-item.hidden {
        display: none;
    }

    .mobile-chapter-num {
        color: var(--reader-accent);
        font-size: 0.8rem;
        font-weight: 600;
        min-width: 28px;
    }

    .mobile-chapter-title {
        flex: 1;
        font-size: 0.9rem;
    }

    .mobile-chapter-current-badge {
        color: var(--reader-accent);
        font-weight: 700;
    }

    /* ===== АДАПТИВ ===== */
    @media (max-width: 768px) {
        .chapter-reader-wrapper {
            padding: 0 12px 80px;
        }
        .chapter-reader-container {
            padding-top: 80px;
        }
        .toolbar-center {
            display: none;
        }
        .chapter-mobile-nav {
            display: block;
        }
        .chapter-nav-bottom .chapter-nav-center {
            display: none;
        }
    }

    @media (min-width: 769px) {
        .chapter-mobile-nav,
        .mobile-chapter-list {
            display: none !important;
        }
    }
    </style>

    <script>
    (function() {
        // ===== ТЕМА (ДЕНЬ/НОЧЬ) =====
        var themeToggle = document.getElementById('reader-theme-toggle');
        var html = document.documentElement;
        
        // Загружаем сохранённую тему
        var savedTheme = localStorage.getItem('readerTheme') || 'dark';
        html.setAttribute('data-reader-theme', savedTheme);
        updateThemeButton(savedTheme);
        
        themeToggle.addEventListener('click', function() {
            var current = html.getAttribute('data-reader-theme');
            var next = current === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-reader-theme', next);
            localStorage.setItem('readerTheme', next);
            updateThemeButton(next);
        });
        
        function updateThemeButton(theme) {
            themeToggle.textContent = theme === 'dark' ? '🌙' : '☀️';
        }
        // ===== НАСТРОЙКИ ЧТЕНИЯ =====
        var settings = {
            fontSize: 16,
            lineHeight: 1.6,
            maxWidth: 700,
            textAlign: 'left',
            textIndent: 1.5
        };

        // Загружаем сохранённые настройки
        try {
            var saved = JSON.parse(localStorage.getItem('chapterReaderSettings'));
            if (saved) {
                for (var key in saved) {
                    if (settings.hasOwnProperty(key)) {
                        settings[key] = saved[key];
                    }
                }
            }
        } catch(e) {}

        function applySettings() {
            var root = document.documentElement;
            root.style.setProperty('--reader-font-size', settings.fontSize + 'px');
            root.style.setProperty('--reader-line-height', settings.lineHeight);
            root.style.setProperty('--reader-max-width', settings.maxWidth + 'px');
            root.style.setProperty('--reader-text-align', settings.textAlign);
            root.style.setProperty('--reader-text-indent', settings.textIndent + 'em');
        }

        function saveSettings() {
            try {
                localStorage.setItem('chapterReaderSettings', JSON.stringify(settings));
            } catch(e) {}
        }

        function updateSettingsUI() {
            document.querySelectorAll('.setting-btn').forEach(function(btn) {
                var s = btn.dataset.setting;
                var v = btn.dataset.value;
                var isActive = false;

                if (s === 'fontSize' && parseInt(v) === settings.fontSize) isActive = true;
                if (s === 'lineHeight' && parseFloat(v) === settings.lineHeight) isActive = true;
                if (s === 'maxWidth' && parseInt(v) === settings.maxWidth) isActive = true;
                if (s === 'textAlign' && v === settings.textAlign) isActive = true;
                if (s === 'textIndent' && parseFloat(v) === settings.textIndent) isActive = true;

                btn.classList.toggle('active', isActive);
            });
        }

        // Применяем при загрузке
        applySettings();
        updateSettingsUI();

        // Клики по кнопкам настроек
        document.querySelectorAll('.setting-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var s = this.dataset.setting;
                var v = this.dataset.value;

                if (s === 'fontSize') settings.fontSize = parseInt(v);
                if (s === 'lineHeight') settings.lineHeight = parseFloat(v);
                if (s === 'maxWidth') settings.maxWidth = parseInt(v);
                if (s === 'textAlign') settings.textAlign = v;
                if (s === 'textIndent') settings.textIndent = parseFloat(v);

                applySettings();
                saveSettings();
                updateSettingsUI();
            });
        });

        // Сброс настроек
        document.getElementById('reader-settings-reset').addEventListener('click', function() {
            settings = {
                fontSize: 16,
                lineHeight: 1.6,
                maxWidth: 700,
                textAlign: 'left',
                textIndent: 1.5
            };
            applySettings();
            saveSettings();
            updateSettingsUI();
        });

        // ===== ПЕРЕКЛЮЧЕНИЕ ПАНЕЛЕЙ =====
        var settingsPanel = document.getElementById('reader-settings-panel');
        var chapterPanel = document.getElementById('chapter-list-panel');
        var settingsToggle = document.getElementById('reader-settings-toggle');
        var chapterToggle = document.getElementById('chapter-list-toggle');

        function closeAllPanels() {
            settingsPanel.style.display = 'none';
            chapterPanel.style.display = 'none';
        }

        settingsToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            if (settingsPanel.style.display === 'block') {
                settingsPanel.style.display = 'none';
            } else {
                closeAllPanels();
                settingsPanel.style.display = 'block';
            }
        });

        chapterToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            if (chapterPanel.style.display === 'block') {
                chapterPanel.style.display = 'none';
            } else {
                closeAllPanels();
                chapterPanel.style.display = 'block';
                // Скролл к текущей главе
                setTimeout(function() {
                    var current = chapterPanel.querySelector('.chapter-list-item.current');
                    if (current) {
                        current.scrollIntoView({ block: 'center' });
                    }
                }, 100);
            }
        });

        document.addEventListener('click', function(e) {
            if (!settingsPanel.contains(e.target) && e.target !== settingsToggle) {
                settingsPanel.style.display = 'none';
            }
            if (!chapterPanel.contains(e.target) && e.target !== chapterToggle) {
                chapterPanel.style.display = 'none';
            }
        });

        // Поиск по главам (десктоп)
        document.getElementById('chapter-search-input').addEventListener('input', function() {
            var query = this.value.toLowerCase();
            document.querySelectorAll('#chapter-list-scroll .chapter-list-item').forEach(function(item) {
                var num = item.dataset.chapterNumber;
                var title = item.dataset.chapterTitle || '';
                if (query === '' || num.indexOf(query) !== -1 || title.indexOf(query) !== -1) {
                    item.classList.remove('hidden');
                } else {
                    item.classList.add('hidden');
                }
            });
        });

        // ===== МОБИЛЬНЫЙ СПИСОК ГЛАВ =====
        var mobileChapterList = document.getElementById('mobile-chapter-list');
        var mobileToggle = document.getElementById('mobile-chapter-toggle');
        var mobileClose = document.getElementById('mobile-chapter-list-close');

        mobileToggle.addEventListener('click', function() {
            mobileChapterList.style.display = 'flex';
            setTimeout(function() {
                var current = mobileChapterList.querySelector('.mobile-chapter-item.current');
                if (current) {
                    current.scrollIntoView({ block: 'center' });
                }
            }, 100);
        });

        mobileClose.addEventListener('click', function() {
            mobileChapterList.style.display = 'none';
        });

        document.addEventListener('click', function(e) {
            if (!mobileChapterList.contains(e.target) && e.target !== mobileToggle) {
                mobileChapterList.style.display = 'none';
            }
        });

        // Поиск по главам (мобильный)
        document.getElementById('mobile-chapter-search-input').addEventListener('input', function() {
            var query = this.value.toLowerCase();
            document.querySelectorAll('#mobile-chapter-list-scroll .mobile-chapter-item').forEach(function(item) {
                var num = item.dataset.chapterNumber;
                var title = item.dataset.chapterTitle || '';
                if (query === '' || num.indexOf(query) !== -1 || title.indexOf(query) !== -1) {
                    item.classList.remove('hidden');
                } else {
                    item.classList.add('hidden');
                }
            });
        });

        // Кнопка списка глав в нижней навигации (десктоп)
        var bottomListToggle = document.getElementById('chapter-list-toggle-bottom');
        if (bottomListToggle) {
            bottomListToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                if (chapterPanel.style.display === 'block') {
                    chapterPanel.style.display = 'none';
                } else {
                    closeAllPanels();
                    chapterPanel.style.display = 'block';
                    setTimeout(function() {
                        var current = chapterPanel.querySelector('.chapter-list-item.current');
                        if (current) {
                            current.scrollIntoView({ block: 'center' });
                        }
                    }, 100);
                }
            });
        }
    })();
    </script>

<?php endwhile; ?>

<?php get_footer(); ?>