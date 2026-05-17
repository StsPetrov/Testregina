<?php
/**
 * Related posts based on categories and tags.
 * 
 */
$audio_podcast_archive_year  = get_the_time('Y'); 
$audio_podcast_archive_month = get_the_time('m'); 
$audio_podcast_archive_day   = get_the_time('d'); 

$audio_podcast_related_posts_taxonomy = get_theme_mod( 'audio_podcast_related_posts_taxonomy', 'category' );

$audio_podcast_post_args = array(
    'posts_per_page'    => absint( get_theme_mod( 'audio_podcast_related_posts_count', '3' ) ),
    'orderby'           => 'rand',
    'post__not_in'      => array( get_the_ID() ),
);

$audio_podcast_tax_terms = wp_get_post_terms( get_the_ID(), 'category' );
$audio_podcast_terms_ids = array();
foreach( $audio_podcast_tax_terms as $tax_term ) {
	$audio_podcast_terms_ids[] = $tax_term->term_id;
}

$audio_podcast_post_args['category__in'] = $audio_podcast_terms_ids; 

if(get_theme_mod('audio_podcast_related_post',true)==1){

$audio_podcast_related_posts = new WP_Query( $audio_podcast_post_args );

if ( $audio_podcast_related_posts->have_posts() ) : ?>
    <div class="related-post wow zoomInUp delay-1000" data-wow-duration="2s">
        <h3 class="py-3"><?php echo esc_html(get_theme_mod('audio_podcast_related_post_title','Related Post'));?></h3>
        <div class="row">
            <?php while ( $audio_podcast_related_posts->have_posts() ) : $audio_podcast_related_posts->the_post(); ?>
                <div class="col-xl-4 col-lg-6 col-md-6 col-12">
                    <article id="post-<?php the_ID(); ?>" <?php post_class('inner-service'); ?>>
                        <div class="post-main-box">
                            <?php if( get_theme_mod( 'audio_podcast_related_image_hide_show',true) == 1) { ?>
                                <div class="box-image">
                                    <?php 
                                        if(has_post_thumbnail()) { 
                                          the_post_thumbnail(); 
                                        }
                                    ?>
                                </div>
                            <?php } ?>
                            <h2 class="section-title"><a href="<?php echo esc_url(get_permalink()); ?>"><?php the_title();?><span class="screen-reader-text"><?php the_title(); ?></span></a></h2>
                            <?php if( get_theme_mod( 'audio_podcast_related_toggle_postdate',true) == 1 || get_theme_mod( 'audio_podcast_related_toggle_author',true) == 1 || get_theme_mod( 'audio_podcast_related_toggle_comments',true) == 1 || get_theme_mod( 'audio_podcast_related_toggle_time',true) == 1) { ?>
                                <div class="post-info p-2 my-3">
                                  <?php if(get_theme_mod('audio_podcast_related_toggle_postdate',true)==1){ ?>
                                    <i class="<?php echo esc_attr(get_theme_mod('audio_podcast_related_postdate_icon','fas fa-calendar-alt')); ?> me-2"></i><span class="entry-date"><a href="<?php echo esc_url( get_day_link( $audio_podcast_archive_year, $audio_podcast_archive_month, $audio_podcast_archive_day)); ?>"><?php echo esc_html( get_the_date() ); ?><span class="screen-reader-text"><?php echo esc_html( get_the_date() ); ?></span></a></span><span><?php echo esc_html(get_theme_mod('audio_podcast_related_post_meta_field_separator', '|'));?></span>
                                  <?php } ?>

                                  <?php if(get_theme_mod('audio_podcast_related_toggle_author',true)==1){ ?>
                                    <i class="<?php echo esc_attr(get_theme_mod('audio_podcast_related_author_icon','fas fa-user')); ?> me-2"></i><span class="entry-author"><a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' )) ); ?>"><?php the_author(); ?><span class="screen-reader-text"><?php the_author(); ?></span></a></span><span><?php echo esc_html(get_theme_mod('audio_podcast_related_post_meta_field_separator', '|'));?></span>
                                  <?php } ?>

                                  <?php if(get_theme_mod('audio_podcast_related_toggle_comments',true)==1){ ?>
                                    <i class="<?php echo esc_attr(get_theme_mod('audio_podcast_related_comments_icon','fa fa-comments')); ?> me-2" aria-hidden="true"></i><span class="entry-comments"><?php comments_number( __('0 Comment', 'audio-podcast'), __('0 Comments', 'audio-podcast'), __('% Comments', 'audio-podcast') ); ?></span><span><?php echo esc_html(get_theme_mod('audio_podcast_related_post_meta_field_separator', '|'));?></span>
                                  <?php } ?>

                                  <?php if(get_theme_mod('audio_podcast_related_toggle_time',true)==1){ ?>
                                    <i class="<?php echo esc_attr(get_theme_mod('audio_podcast_related_time_icon','fas fa-clock')); ?> me-2"></i><span class="entry-time"><?php echo esc_html( get_the_time() ); ?></span>
                                  <?php } ?>
                                  <?php echo esc_html (audio_podcast_edit_link()); ?>
                                </div>
                            <?php } ?>
                            <div class="new-text">
                                <div class="entry-content">
                                    <?php $audio_podcast_theme_lay = get_theme_mod( 'audio_podcast_excerpt_settings','Excerpt');
                                        if($audio_podcast_theme_lay == 'Content'){ ?>
                                          <?php the_content(); ?>
                                        <?php }
                                        if($audio_podcast_theme_lay == 'Excerpt'){ ?>
                                          <?php if(get_the_excerpt()) { ?>
                                            <p><?php $audio_podcast_excerpt = get_the_excerpt(); echo esc_html( audio_podcast_string_limit_words( $audio_podcast_excerpt, esc_attr(get_theme_mod('audio_podcast_related_posts_excerpt_number','30')))); ?></p>
                                          <?php }?>
                                        <?php }?>
                                </div>
                            </div>
                            <?php if( get_theme_mod('audio_podcast_related_button_text','Read More') != ''){ ?>
                                <div class="more-btn">
                                    <a href="<?php echo esc_url(get_permalink()); ?>"><?php echo esc_html(get_theme_mod('audio_podcast_related_button_text',__('Read More','audio-podcast')));?><span class="screen-reader-text"><?php echo esc_html(get_theme_mod('audio_podcast_related_button_text',__('Read More','audio-podcast')));?></span><span class="top-icon"></span></a>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="clearfix"></div>
                    </article>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
<?php endif;
wp_reset_postdata();

}