<?php
/**
 * The template part for Top Header
 *
 * @package Audio Podcast
 * @subpackage audio-podcast
 * @since audio-podcast 1.0
 */
?>

<?php if(get_theme_mod('audio_podcast_topbar_hide_show', true) == 1 || get_theme_mod( 'audio_podcast_resp_topbar_hide_show', true) == 1) {?>
    <div class="top-header">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 col-md-6 text-md-start text-center">
                    <?php if(get_theme_mod('audio_podcast_topbar_text') != ''){ ?>
                        <p class="topbar-text mb-md-0"><?php echo esc_html(get_theme_mod('audio_podcast_topbar_text')); ?></p>
                    <?php }?>
                </div>
                <div class="col-lg-6 col-md-6 text-md-end text-center">
                    <div class="topbar-links">
                        <?php if(get_theme_mod('audio_podcast_topbar_support_link') != ''){ ?>
                            <a href="<?php echo esc_url(get_theme_mod('audio_podcast_topbar_support_link')); ?>" class="support"><?php echo esc_html('Support','audio-podcast'); ?><span class="screen-reader-text"><?php echo esc_html('Support','audio-podcast'); ?></span></a>
                        <?php }?>
                        <?php if(get_theme_mod('audio_podcast_topbar_wishlist_link') != ''){ ?>
                            <a href="<?php echo esc_url(get_theme_mod('audio_podcast_topbar_wishlist_link')); ?>" class="support"><?php echo esc_html('Wishlist','audio-podcast'); ?><span class="screen-reader-text"><?php echo esc_html('Wishlist','audio-podcast'); ?></span></a>
                        <?php }?>
                        <?php if(get_theme_mod('audio_podcast_topbar_myaccount_link') != ''){ ?>
                            <a href="<?php echo esc_url(get_theme_mod('audio_podcast_topbar_myaccount_link')); ?>" class="support"><?php echo esc_html('My Account','audio-podcast'); ?><span class="screen-reader-text"><?php echo esc_html('My Account','audio-podcast'); ?></span></a>
                        <?php }?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php }?>