<?php
/**
 * The template for displaying home page.
 *
 * @package Audio Podcast
 */

get_header(); ?>

<div class="container">
  <main id="content" role="main" class="middle-align">
    
    <?php
    // Определяем настройки темы
    $audio_podcast_theme_lay = get_theme_mod( 'audio_podcast_theme_options','Right Sidebar');
    
    // Определяем класс для левой колонки
    $left_column_class = '';
    if($audio_podcast_theme_lay == 'Left Sidebar' || $audio_podcast_theme_lay == 'Right Sidebar'){
        $left_column_class = 'col-lg-8 col-md-8';
    } elseif($audio_podcast_theme_lay == 'One Column') {
        $left_column_class = 'services col-lg-12';
    } elseif($audio_podcast_theme_lay == 'Three Columns') {
        $left_column_class = 'col-lg-6 col-md-6';
    } elseif($audio_podcast_theme_lay == 'Four Columns') {
        $left_column_class = 'col-lg-3 col-md-3';
    } elseif($audio_podcast_theme_lay == 'Grid Layout') {
        $left_column_class = 'col-lg-9 col-md-9';
    } else {
        $left_column_class = 'col-lg-8 col-md-8';
    }
    
    // ОДИН row для всей левой колонки и сайдбара
    echo '<div class="row">';
    
    // Левая колонка (шорткоды + посты)
    echo '<div id="our-services" class="services ' . esc_attr($left_column_class) . '">';
    
    // ========== БЛОК ШОРТКОДОВ (только на первой странице) ==========
    $paged_main = get_query_var('paged') ? get_query_var('paged') : 1;
    if ((is_home() || is_front_page()) && $paged_main == 1) {
        
        // 1. Достижения
        echo do_shortcode('[user_stats]');
    
        // 2. Продолжить прослушивание
        echo do_shortcode('[abs_continue]');
        
        // 3. Избранное
        if (is_user_logged_in()) {
            echo do_shortcode('[abs_favorites]');
        } else {
            echo '<div class="favorites-list"><h2>⭐ Избранное</h2><div class="books-grid"><div class="book-card login-card"><div class="login-message-text">🔒 Чтобы увидеть избранное, авторизуйтесь</div><a href="' . home_url('/login?redirect_to=' . urlencode(get_permalink())) . '" class="login-style-btn">Войти в аккаунт</a></div></div></div>';
        }
        
        // 4. Новинки
        echo do_shortcode('[abs_new_releases limit="12"]');
        
        // 5. Популярное
        echo do_shortcode('[abs_popular limit="12"]');
    }
    // ========== КОНЕЦ ШОРТКОДОВ ==========
    
    // ========== КАТАЛОГ КНИГ ==========
    echo do_shortcode('[abs_catalog]');
    
    echo '</div>'; // закрываем левую колонку
    
    // ========== САЙДБАР ==========
    if($audio_podcast_theme_lay == 'Left Sidebar'){ ?>
        <div class="col-lg-4 col-md-4" id="sidebar"><?php get_sidebar();?></div>
    <?php }elseif($audio_podcast_theme_lay == 'Right Sidebar'){ ?>
        <div class="col-lg-4 col-md-4" id="sidebar"><?php get_sidebar();?></div>
    <?php }elseif($audio_podcast_theme_lay == 'Three Columns'){ ?>
        <div class="col-lg-3 col-md-3" id="sidebar"><?php dynamic_sidebar('sidebar-2');?></div>
    <?php }elseif($audio_podcast_theme_lay == 'Four Columns'){ ?>
        <div class="col-lg-3 col-md-3" id="sidebar"><?php dynamic_sidebar('sidebar-2');?></div>
        <div class="col-lg-3 col-md-3" id="sidebar"><?php dynamic_sidebar('sidebar-3');?></div>
    <?php }elseif($audio_podcast_theme_lay == 'Grid Layout'){ ?>
        <div class="col-lg-3 col-md-3" id="sidebar"><?php dynamic_sidebar('sidebar-1');?></div>
    <?php }else{ ?>
        <div class="col-lg-4 col-md-4" id="sidebar"><?php dynamic_sidebar('sidebar-1');?></div>
    <?php } ?>
    
    <?php echo '</div>'; // закрываем row ?>
    
    <div class="clearfix"></div>
  </main>
</div>

<?php get_footer(); ?>