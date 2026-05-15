<?php
/**
 * The template part for Middle Header
 *
 * @package Audio Podcast
 * @subpackage audio-podcast
 * @since audio-podcast 1.0
 */
?>

<div class="main-header <?php if( get_theme_mod( 'audio_podcast_sticky_header', false) == 1 || get_theme_mod( 'audio_podcast_stickyheader_hide_show', false) == 1) { ?> header-sticky"<?php } else { ?>close-sticky <?php } ?>">
  <div class="container">
    <div class="row">
      <div class="col-lg-3 col-md-5 col-9 align-self-center logo-bg-bx">
        <div class="logo text-start pb-3 pb-md-0 d-flex align-items-center flex-wrap">
          <?php if ( has_custom_logo() ) : ?>
            <div class="site-logo"><?php the_custom_logo(); ?></div>
            
          <?php endif; ?>
          <?php $blog_info = get_bloginfo( 'name' ); ?>
            <?php if ( ! empty( $blog_info ) ) : ?>
              <?php if ( is_front_page() && is_home() ) : ?>
                <?php if( get_theme_mod('audio_podcast_logo_title_hide_show',true) == 1){ ?>
                  <p class="site-title mb-0"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></p>
                <?php } ?>
              <?php else : ?>
                <?php if( get_theme_mod('audio_podcast_logo_title_hide_show',true) == 1){ ?>
                  <p class="site-title mb-0"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></p>
                <?php } ?>
              <?php endif; ?>
            <?php endif; ?>
            <?php
              $description = get_bloginfo( 'description', 'display' );
              if ( $description || is_customize_preview() ) :
            ?>
            <?php if( get_theme_mod('audio_podcast_tagline_hide_show',false) == 1){ ?>
              <p class="site-description mb-0">
                <?php echo esc_html($description); ?>
              </p>
            <?php } ?>
          <?php endif; ?>
                  <?php if (is_user_logged_in()): 
            global $wpdb;
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}abs_notifications WHERE user_id = %d AND is_read = 0",
                get_current_user_id()
            ));
        ?>
            <a href="/notifications" style="font-size:1.4rem;text-decoration:none;display:inline-block;margin-top:5px;margin-left:5px;position:relative;">
                🔔
                <?php if ($count > 0): ?>
                    <span style="position:absolute;top:-5px;right:-8px;background:#ff4444;color:#fff;border-radius:50%;width:18px;height:18px;display:inline-flex;align-items:center;justify-content:center;font-size:0.6rem;font-weight:700;"><?php echo $count; ?></span>
                <?php endif; ?>
            </a>
        <?php endif; ?>
        </div>
      </div>
      <div class="col-lg-6 col-md-2 col-3 align-self-center">
        <?php get_template_part('template-parts/header/navigation'); ?>
      </div>
      <div class="col-lg-3 col-md-5 align-self-center">
        <div class="menu-search text-md-end text-center">
          <?php get_search_form(); ?>
        </div>
      </div>
    </div>
  </div>
</div>