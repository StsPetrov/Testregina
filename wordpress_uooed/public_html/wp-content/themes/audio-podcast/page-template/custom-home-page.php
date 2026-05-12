<?php
/**
 * Template Name: Custom Home Page
 */

get_header(); ?>

<main id="maincontent" role="main">
  <?php do_action( 'audio_podcast_before_slider' ); ?>

    <?php if( get_theme_mod( 'audio_podcast_slider_hide_show', true) == 1 || get_theme_mod( 'audio_podcast_resp_slider_hide_show', true) == 1) { ?>
      <section id="slider"> 
      <?php if(get_theme_mod('audio_podcast_slider_type', 'Default slider') == 'Default slider' ){ ?>       
        <div id="carouselExampleCaptions" class="carousel slide" data-bs-ride="carousel" data-bs-interval="<?php echo esc_attr(get_theme_mod( 'audio_podcast_slider_speed',4000)) ?>">
          <div class="row">
            <?php if(has_nav_menu('left-menu')){ ?>
              <div class="col-lg-1 col-md-2 left-bg pe-md-0">
                <?php if(has_nav_menu('primary')){ ?>
                  <div class="toggle-nav mobile-menu text-center py-3">
                    <button role="tab" onclick="audio_podcast_leftmenu_open_nav()" class="responsive-lefttoggle"><i class="py-2 px-3 <?php echo esc_attr(get_theme_mod('audio_podcast_res_open_menu_icon','fas fa-bars')); ?>"></i><span class="screen-reader-text"><?php esc_html_e('Open Button','audio-podcast'); ?></span></button>
                  </div>
                <?php } ?>
                <div class="left-menu">
                  <?php 
                    wp_nav_menu( array( 
                      'theme_location' => 'left-menu',
                      'container_class' => 'main-menu clearfix' ,
                      'menu_class' => 'clearfix',
                      'items_wrap' => '<ul id="%1$s" class="%2$s leftside-menu p-0 m-0">%3$s</ul>',
                      'fallback_cb' => 'wp_page_menu',
                    ) );
                  ?>
                  <a href="javascript:void(0)" class="closebtn mobile-leftmenu" onclick="audio_podcast_leftmenu_close_nav()"><i class="<?php echo esc_attr(get_theme_mod('audio_podcast_res_menu_close_icon','fas fa-times')); ?>"></i><span class="screen-reader-text"><?php esc_html_e('Close Button','audio-podcast'); ?></span></a>
                </div>
              </div>
            <?php }?>
            <div class="<?php if(has_nav_menu('left-menu')){ ?>col-lg-11 col-md-10 ps-md-0 <?php } else {?> col-lg-12 col-md-12 <?php }?>slider-section">
              <?php $audio_podcast_pages = array();
                for ( $count = 1; $count <= 3; $count++ ) {
                  $mod = intval( get_theme_mod( 'audio_podcast_slider_page' . $count ));
                  if ( 'page-none-selected' != $mod ) {
                    $audio_podcast_pages[] = $mod;
                  }
                }
                if( !empty($audio_podcast_pages) ) :
                  $args = array(
                    'post_type' => 'page',
                    'post__in' => $audio_podcast_pages,
                    'orderby' => 'post__in'
                  );
                  $query = new WP_Query( $args );
                  if ( $query->have_posts() ) :
                    $i = 1;
              ?>
              <div class="carousel-inner" role="listbox">
                <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                  <div <?php if($i == 1){echo 'class="carousel-item active"';} else{ echo 'class="carousel-item"';}?>>
                    <?php if(has_post_thumbnail()){
                      the_post_thumbnail();
                    } else{?>
                      <img src="<?php echo esc_url(get_template_directory_uri()); ?>/inc/block-patterns/images/banner.png" alt="" />
                    <?php } ?>
                    <div class="carousel-caption">
                      <div class="inner_carousel">
                        <?php if( get_theme_mod('audio_podcast_slider_title_hide_show',true) == 1){ ?>
                           <h1 class="wow rollIn delay-1000" data-wow-duration="2s"><a href="<?php echo esc_url( get_permalink() ); ?>" title="<?php echo the_title_attribute(); ?>"><?php the_title(); ?></a></h1>
                        <?php } ?>
                        <?php if( get_theme_mod('audio_podcast_slider_content_hide_show',true) == 1){ ?>
                          <p class="wow rollIn delay-1000" data-wow-duration="2s"><?php $audio_podcast_excerpt = get_the_excerpt(); echo esc_html( audio_podcast_string_limit_words( $audio_podcast_excerpt, esc_attr(get_theme_mod('audio_podcast_slider_excerpt_number','30')))); ?></p>
                        <?php } ?>
                        <?php
                          $audio_podcast_button_text = get_theme_mod('audio_podcast_slider_button_text', 'EXPLORE ALL');
                          $audio_podcast_button_link = get_theme_mod('audio_podcast_topbar_btn_link', '');
                          if (empty($audio_podcast_button_link)) {
                            $audio_podcast_button_link = get_permalink();
                          }
                          if ($audio_podcast_button_text || !empty($audio_podcast_button_link)) { ?>
                            <div class="slider-btn wow rollIn delay-1000" data-wow-duration="2s">
                              <?php if( get_theme_mod('audio_podcast_slider_button_text', 'EXPLORE ALL') != ''){ ?>
                                <a href="<?php echo esc_url($audio_podcast_button_link); ?>" class="button redmor">
                                <?php echo esc_html($audio_podcast_button_text); ?>
                                  <span class="screen-reader-text"><?php echo esc_html($audio_podcast_button_text); ?></span>
                                </a>
                              <?php } ?>
                            </div>
                        <?php } ?>
                      </div>
                    </div>
                  </div>
                <?php $i++; endwhile; 
                wp_reset_postdata();?>
              </div>
              <?php else : ?>
                <div class="no-postfound"></div>
              <?php endif;
              endif;?>
              <?php if(get_theme_mod('audio_podcast_slider_arrow_hide_show', true)){?>
                <a class="carousel-control-prev" data-bs-target="#carouselExampleCaptions" data-bs-slide="prev" role="button">
                  <span class="carousel-control-prev-icon" aria-hidden="true"><i class="<?php echo esc_attr(get_theme_mod('audio_podcast_slider_prev_icon','fas fa-arrow-left')); ?>"></i></span>
                  <span class="screen-reader-text"><?php esc_html_e( 'Previous','audio-podcast' );?></span>
                </a>
                <a class="carousel-control-next" data-bs-target="#carouselExampleCaptions" data-bs-slide="next" role="button">
                  <span class="carousel-control-next-icon" aria-hidden="true"><i class="<?php echo esc_attr(get_theme_mod('audio_podcast_slider_next_icon','fas fa-arrow-right')); ?>"></i></span>
                  <span class="screen-reader-text"><?php esc_html_e( 'Next','audio-podcast' );?></span>
                </a>
              <?php }?>
            </div>
          </div>
        </div>  
        <?php } else if(get_theme_mod('audio_podcast_slider_type', 'Advance slider') == 'Advance slider'){?>
          <?php echo do_shortcode(get_theme_mod('audio_podcast_advance_slider_shortcode')); ?>
        <?php } ?>
      </section>
    <?php }?>

  <?php do_action( 'audio_podcast_after_slider' ); ?>

  <section id="player-sec" class="wow zoomIn delay-1000 delay-1000" data-wow-duration="2s">     
    <?php $audio_podcast_player_page = array();
      $mod = intval( get_theme_mod( 'audio_podcast_player_page','audio-podcast'));
      if ( 'page-none-selected' != $mod ) {
        $audio_podcast_player_page[] = $mod;
      }
      if( !empty($audio_podcast_player_page) ) :
        $args = array(
          'post_type' => 'page',
          'post__in' => $audio_podcast_player_page,
          'orderby' => 'post__in'
        );
        $query = new WP_Query( $args );
        if ( $query->have_posts() ) :
          $count = 0;
          while ( $query->have_posts() ) : $query->the_post(); ?>
            <?php the_content(); ?>
          <?php $count++; endwhile; ?>
        <?php else : ?>
          <div class="no-postfound"></div>
        <?php endif;
      endif;
      wp_reset_postdata()
    ?>
  </section>

  <?php do_action( 'audio_podcast_after_service' ); ?>

  <div id="content-vw" class="py-3">
    <div class="container">
      <?php while ( have_posts() ) : the_post(); ?>
        <?php the_content(); ?>
      <?php endwhile; // end of the loop. ?>
    </div>
  </div>
</main>

<?php get_footer(); ?>