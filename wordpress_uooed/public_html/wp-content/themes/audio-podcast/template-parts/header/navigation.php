<?php
/**
 * The template part for header
 *
 * @package Audio Podcast 
 * @subpackage audio-podcast
 * @since audio-podcast 1.0
 */
?>

<div id="header" class="text-end">
  <?php ?>
    <div class="toggle-nav mobile-menu">
      <button role="tab" onclick="audio_podcast_menu_open_nav()" class="responsivetoggle"><i class="py-2 px-3 <?php echo esc_attr(get_theme_mod('audio_podcast_res_open_menu_icon','fas fa-bars')); ?>"></i><span class="screen-reader-text"><?php esc_html_e('Open Button','audio-podcast'); ?></span></button>
    </div>
  <?php  ?>
  <div id="mySidenav" class="nav sidenav">
    <nav id="site-navigation" class="main-navigation" role="navigation" aria-label="<?php esc_attr_e( 'Top Menu', 'audio-podcast' ); ?>">
      <?php
        wp_nav_menu( array( 
          'theme_location' => 'primary',
          'container_class' => 'main-menu clearfix' ,
          'menu_class' => 'clearfix',
          'items_wrap' => '<ul id="%1$s" class="%2$s mobile_nav">%3$s</ul>',
          'fallback_cb' => 'wp_page_menu',
        ) );
      ?>
      <a href="javascript:void(0)" class="closebtn mobile-menu" onclick="audio_podcast_menu_close_nav()"><i class="<?php echo esc_attr(get_theme_mod('audio_podcast_res_menu_close_icon','fas fa-times')); ?>"></i><span class="screen-reader-text"><?php esc_html_e('Close Button','audio-podcast'); ?></span></a>
    </nav>
  </div>
</div>