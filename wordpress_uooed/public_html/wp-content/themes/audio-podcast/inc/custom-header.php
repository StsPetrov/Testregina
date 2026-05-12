<?php
/**
 * @package Audio Podcast
 * Setup the WordPress core custom header feature.
 *
 * @uses audio_podcast_header_style()
*/
function audio_podcast_custom_header_setup() {
	add_theme_support( 'custom-header', apply_filters( 'audio_podcast_custom_header_args', array(
		'header-text' 			 =>	false,
		'width'                  => 1600,
		'height'                 => 85,
		'flex-width'    		 => true,
		'flex-height'    		 => true,
		'wp-head-callback'       => 'audio_podcast_header_style',
	) ) );
}
add_action( 'after_setup_theme', 'audio_podcast_custom_header_setup' );

if ( ! function_exists( 'audio_podcast_header_style' ) ) :
/**
 * Styles the header image and text displayed on the blog
 *
 * @see audio_podcast_custom_header_setup().
 */
add_action( 'wp_enqueue_scripts', 'audio_podcast_header_style' );

function audio_podcast_header_style() {
	if ( get_header_image() ) :
	$custom_css = "
        .main-header{
			background-image:url('".esc_url(get_header_image())."');
			background-position: center top;
		    background-size: cover;
		}";
	   	wp_add_inline_style( 'audio-podcast-basic-style', $custom_css );
	endif;
}
endif;