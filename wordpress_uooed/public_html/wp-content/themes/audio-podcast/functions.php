<?php


/**
 * Audio Podcast functions and definitions
 *
 * @package Audio Podcast
 */

/* Breadcrumb Begin */
function audio_podcast_the_breadcrumb() {
	if (!is_home()) {
		echo '<a href="';
			echo esc_url( home_url() );
		echo '">';
			bloginfo('name');
		echo "</a> ";
		if (is_category() || is_single()) {
			the_category(',');
			if (is_single()) {
				echo "<span> ";
					the_title();
				echo "</span> ";
			}
		} elseif (is_page()) {
			echo "<span> ";
				the_title();
		}
	}
}

/* Theme Setup */
if ( ! function_exists( 'audio_podcast_setup' ) ) :
 
function audio_podcast_setup() {

	$GLOBALS['content_width'] = apply_filters( 'audio_podcast_content_width', 640 );
	
	load_theme_textdomain( 'audio-podcast', get_template_directory() . '/languages' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-slider' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'comment-list', 'search-form', 'comment-form', ) );
	add_theme_support( 'custom-logo', array(
		'height'      => 240,
		'width'       => 240,
		'flex-height' => true,
	) );
	add_image_size('audio-podcast-homepage-thumb',240,145,true);
	
    register_nav_menus( array(
		'primary' => __( 'Primary Menu', 'audio-podcast' ),
		'left-menu' => __( 'Left Side Menu', 'audio-podcast' ),
	) );

	add_theme_support( 'custom-background', array(
		'default-color' => 'ffffff'
	) );

	//selective refresh for sidebar and widgets
	add_theme_support( 'customize-selective-refresh-widgets' );

	/*
	 * Enable support for Post Formats.
	 *
	 * See: https://codex.wordpress.org/Post_Formats
	 */
	add_theme_support( 'post-formats', array('image','video','gallery','audio',) );

	/*
	 * This theme styles the visual editor to resemble the theme style,
	 * specifically font, colors, icons, and column width.
	 */
	add_editor_style( array( 'css/editor-style.css', audio_podcast_font_url() ) );

	// Theme Activation Notice
	global $pagenow;

	if (
		is_admin()
		&&
		('themes.php' == $pagenow)
		// &&
		// isset( $_GET['activated'] )
	) {
		add_action('admin_notices', 'audio_podcast_activation_notice');
	}
}
endif;

add_action( 'after_setup_theme', 'audio_podcast_setup' );

// Notice after Theme Activation

function audio_podcast_activation_notice() {

	$audio_podcast_meta = get_option( 'audio_podcast_admin_notice' );

	if (!$audio_podcast_meta) {
			echo '<div id="audio-podcast-welcome-notice" class="notice notice-success is-dismissible welcome-notice">';
			echo '<div class="notice-row">';
				echo '<div class="notice-text">';
					echo '<p class="welcome-text1">'. esc_html__( '🎉 Welcome to VW Themes,', 'audio-podcast' ) .'</p>';
					echo '<p class="welcome-text2">'. esc_html__( 'You are now using the Audio Podcast, a beautifully designed theme to kickstart your website.', 'audio-podcast' ) .'</p>';
					echo '<p class="welcome-text3">'. esc_html__( 'To help you get started quickly, use the options below:', 'audio-podcast' ) .'</p>';
					echo '<span class="import-btn"><a href="'. esc_url( admin_url( 'admin.php?page=audio_podcast_guide' ) ) .'" class="button button-primary">'. esc_html__( 'DEMO IMPORT', 'audio-podcast' ) .'</a></span>';
					echo '<span class="demo-btn"><a href="'. esc_url( 'https://www.vwthemes.net/vw-audio-podcast/' ) .'" class="button button-primary" target=_blank>'. esc_html__( 'VIEW DEMO', 'audio-podcast' ) .'</a></span>';
					echo '<span class="upgrade-btn"><a href="'. esc_url( 'https://www.vwthemes.com/products/audio-podcast-wordpress-theme' ) .'" class="button button-primary" target=_blank>'. esc_html__( 'UPGRADE TO PRO', 'audio-podcast' ) .'</a></span>';
					echo '<span class="bundle-btn"><a href="'. esc_url( 'https://www.vwthemes.com/products/wp-theme-bundle' ) .'" class="button button-primary" target=_blank>'. esc_html__( 'BUNDLE OF 400+ THEMES', 'audio-podcast' ) .'</a></span>';
				echo '</div>';
				echo '<div class="notice-img1">';
					echo '<img src="' . esc_url( get_template_directory_uri() . '/inc/getstart/images/arrow-notice.png' ) . '" width="180" alt="' . esc_attr__( 'Audio Podcast', 'audio-podcast' ) . '" />';
				echo '</div>';
				echo '<div class="notice-img2">';
					echo '<img src="' . esc_url( get_template_directory_uri() . '/inc/getstart/images/bundle-notice.png' ) . '" width="180" alt="' . esc_attr__( 'Audio Podcast', 'audio-podcast' ) . '" />';
				echo '</div>';	
			echo '</div>';	
		echo '</div>';
	}
}


/* Theme Widgets Setup */
function audio_podcast_widgets_init() {
	register_sidebar( array(
		'name'          => __( 'Blog Sidebar', 'audio-podcast' ),
		'description'   => __( 'Appears on blog page sidebar', 'audio-podcast' ),
		'id'            => 'sidebar-1',
		'before_widget' => '<aside id="%1$s" class="widget mb-5 p-3 %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h3 class="widget-title py-3 px-4">',
		'after_title'   => '</h3>',
	) );
	
	register_sidebar( array(
		'name'          => __( 'Page Sidebar', 'audio-podcast' ),
		'description'   => __( 'Appears on page sidebar', 'audio-podcast' ),
		'id'            => 'sidebar-2',
		'before_widget' => '<aside id="%1$s" class="widget mb-5 p-3 %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h3 class="widget-title py-3 px-4">',
		'after_title'   => '</h3>',
	) );

	register_sidebar(array(
		'name'          => __('Sidebar 3', 'audio-podcast'),
		'description'   => __('Appears on Blog Page sidebar', 'audio-podcast'),
		'id'            => 'sidebar-3',
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	));

	register_sidebar( array(
		'name'          => __( 'Footer Navigation 1', 'audio-podcast' ),
		'description'   => __( 'Appears on footer 1', 'audio-podcast' ),
		'id'            => 'footer-1',
		'before_widget' => '<aside id="%1$s" class="widget py-3 %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h3 class="widget-title px-0 py-2">',
		'after_title'   => '</h3>',
	) );

	register_sidebar( array(
		'name'          => __( 'Footer Navigation 2', 'audio-podcast' ),
		'description'   => __( 'Appears on footer 2', 'audio-podcast' ),
		'id'            => 'footer-2',
		'before_widget' => '<aside id="%1$s" class="widget py-3 %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h3 class="widget-title px-0 py-2">',
		'after_title'   => '</h3>',
	) );

	register_sidebar( array(
		'name'          => __( 'Footer Navigation 3', 'audio-podcast' ),
		'description'   => __( 'Appears on footer 3', 'audio-podcast' ),
		'id'            => 'footer-3',
		'before_widget' => '<aside id="%1$s" class="widget py-3 %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h3 class="widget-title px-0 py-2">',
		'after_title'   => '</h3>',
	) );

	register_sidebar( array(
		'name'          => __( 'Footer Navigation 4', 'audio-podcast' ),
		'description'   => __( 'Appears on footer 4', 'audio-podcast' ),
		'id'            => 'footer-4',
		'before_widget' => '<aside id="%1$s" class="widget py-3 %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h3 class="widget-title px-0 py-2">',
		'after_title'   => '</h3>',
	) );

	register_sidebar( array(
		'name'          => __( 'Shop Page Sidebar', 'audio-podcast' ),
		'description'   => __( 'Appears on shop page', 'audio-podcast' ),
		'id'            => 'woocommerce-shop-sidebar',
		'before_widget' => '<aside id="%1$s" class="widget mb-5 p-3 %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h3 class="widget-title px-3 py-2">',
		'after_title'   => '</h3>',
	) );

	register_sidebar( array(
		'name'          => __( 'Single Product Sidebar', 'audio-podcast' ),
		'description'   => __( 'Appears on single product page', 'audio-podcast' ),
		'id'            => 'woocommerce-single-sidebar',
		'before_widget' => '<aside id="%1$s" class="widget mb-5 p-3 %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h3 class="widget-title px-3 py-2">',
		'after_title'   => '</h3>',
	) );

	register_sidebar( array(
		'name'          => __( 'Slider Social Media', 'audio-podcast' ),
		'description'   => __( 'Appears on slider', 'audio-podcast' ),
		'id'            => 'slider-social',
		'before_widget' => '<aside id="%1$s" class="widget mb-5 p-3 %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h3 class="widget-title px-3 py-2">',
		'after_title'   => '</h3>',
	) );

	register_sidebar( array(
		'name'          => __( 'Footer Social Icon', 'audio-podcast' ),
		'description'   => __( 'Appears on right side footer', 'audio-podcast' ),
		'id'            => 'footer-icon',
		'before_widget' => '<aside id="%1$s" class="widget mb-5 p-3 %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h3 class="widget-title px-3 py-2">',
		'after_title'   => '</h3>',
	) );  
}
add_action( 'widgets_init', 'audio_podcast_widgets_init' );

/* Theme Font URL */
function audio_podcast_font_url() {
	$font_family   = array(
		'ABeeZee:ital@0;1',
	 	'Abril Fatface',
	 	'Acme',
	 	'Alfa Slab One',
	 	'Allura',
	 	'Anton',
	 	'Architects Daughter',
	 	'Archivo:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900',
	 	'Arimo:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700',
	 	'Arsenal:ital,wght@0,400;0,700;1,400;1,700',
	 	'Arvo:ital,wght@0,400;0,700;1,400;1,700',
	 	'Alegreya Sans:ital,wght@0,100;0,300;0,400;0,500;0,700;0,800;0,900;1,100;1,300;1,400;1,500;1,700;1,800;1,900',
	 	'Asap:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900',
	 	'Assistant:wght@200;300;400;500;600;700;800',
	 	'Averia Serif Libre:ital,wght@0,300;0,400;0,700;1,300;1,400;1,700',
	 	'Bangers',
	 	'Boogaloo',
	 	'Bad Script',
	 	'Barlow Condensed:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900',
	 	'Bitter:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900',
	 	'Bree Serif',
	 	'BenchNine:wght@300;400;700',
	 	'Cabin:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700',
	 	'Cardo:ital,wght@0,400;0,700;1,400',
	 	'Courgette',
	 	'Caveat Brush',
	 	'Cherry Swash:wght@400;700',
	 	'Cormorant Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700',
	 	'Crimson Text:ital,wght@0,400;0,600;0,700;1,400;1,600;1,700',
	 	'Cuprum:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700',
	 	'Cookie',
	 	'Coming Soon',
	 	'Charm:wght@400;700',
	 	'Chewy',
	 	'Days One',
	 	'DM Serif Display:ital@0;1',
	 	'Dosis:wght@200;300;400;500;600;700;800',
	 	'EB Garamond:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,500;1,600;1,700;1,800',
	 	'Economica:ital,wght@0,400;0,700;1,400;1,700',
	 	'Exo 2:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,400;1,500;1,600;1,700;1,800;1,900',
	 	'Fira Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900',
	 	'Fredoka One',
	 	'Fjalla One',
	 	'Frank Ruhl Libre:wght@300;400;500;700;900',
	 	'Gabriela',
	 	'Gloria Hallelujah',
	 	'Great Vibes',
	 	'Handlee',
	 	'Hammersmith One',
	 	'Heebo:wght@100;200;300;400;500;600;700;800;900',
	 	'Hind:wght@300;400;500;600;700',
	 	'Inconsolata:wght@200;300;400;500;600;700;800;900',
	 	'Indie Flower',
	 	'IM Fell English SC',
	 	'Julius Sans One',
	 	'Jomhuria',
	 	'Josefin Slab:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700',
	 	'Josefin Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700',
	 	'Jost:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900',
	 	'Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900',
	 	'Kaushan Script',
	 	'Krub:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;1,200;1,300;1,400;1,500;1,600;1,700',
	 	'Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900',
	 	'Lora:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700',
	 	'Libre Baskerville:ital,wght@0,400;0,700;1,400',
	 	'Literata:ital,opsz,wght@0,7..72,200;0,7..72,300;0,7..72,400;0,7..72,500;0,7..72,600;0,7..72,700;0,7..72,800;0,7..72,900;1,7..72,200;1,7..72,300;1,7..72,400;1,7..72,500;1,7..72,600;1,7..72,700;1,7..72,800;1,7..72,900',
	 	'Lobster',
	 	'Lobster Two:ital,wght@0,400;0,700;1,400;1,700',
	 	'Merriweather:ital,wght@0,300;0,400;0,700;0,900;1,300;1,400;1,700;1,900',
	 	'Marck Script',
	 	'Marcellus',
	 	'Merienda One',
	 	'Monda:wght@400;700',
	 	'Montserrat:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900',
	 	'Mulish:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;0,1000;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900;1,1000',
	 	'Noto Serif:ital,wght@0,400;0,700;1,400;1,700',
	 	'Nunito Sans:ital,wght@0,200;0,300;0,400;0,600;0,700;0,800;0,900;1,200;1,300;1,400;1,600;1,700;1,800;1,900',
	 	'Open Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800',
	 	'Overpass:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900',
	 	'Overpass Mono:wght@300;400;500;600;700',
	 	'Oxygen:wght@300;400;700',
	 	'Oswald:wght@200;300;400;500;600;700',
	 	'Orbitron:wght@400;500;600;700;800;900',
	 	'Patua One',
	 	'Pacifico',
	 	'Padauk:wght@400;700',
	 	'Playball',
	 	'Playfair Display:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,500;1,600;1,700;1,800;1,900',
	 	'Prompt:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900',
	 	'PT Sans:ital,wght@0,400;0,700;1,400;1,700',
	 	'PT Serif:ital,wght@0,400;0,700;1,400;1,700',
	 	'Philosopher:ital,wght@0,400;0,700;1,400;1,700',
	 	'Permanent Marker',
	 	'Poiret One',
	 	'Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900',
	 	'Prata',
	 	'Quicksand:wght@300;400;500;600;700',
	 	'Quattrocento Sans:ital,wght@0,400;0,700;1,400;1,700',
	 	'Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900',
	 	'Rubik:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,300;1,400;1,500;1,600;1,700;1,800;1,900',
	 	'Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900',
	 	'Roboto Condensed:ital,wght@0,300;0,400;0,700;1,300;1,400;1,700',
	 	'Rokkitt:wght@100;200;300;400;500;600;700;800;900',
	 	'Ropa Sans:ital@0;1',
	 	'Russo One',
	 	'Righteous',
	 	'Saira:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900',
	 	'Satisfy',
	 	'Sen:wght@400;700;800',
	 	'Source Sans Pro:ital,wght@0,200;0,300;0,400;0,600;0,700;0,900;1,200;1,300;1,400;1,600;1,700;1,900',
	 	'Shadows Into Light Two',
	 	'Shadows Into Light',
	 	'Sacramento',
	 	'Sail',
	 	'Shrikhand',
	 	'Staatliches',
	 	'Stylish',
	 	'Tangerine:wght@400;700',
	 	'Titillium Web:ital,wght@0,200;0,300;0,400;0,600;0,700;0,900;1,200;1,300;1,400;1,600;1,700',
	 	'Trirong:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900',
	 	'Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700',
	 	'Unica One',
	 	'VT323',
	 	'Varela Round',
	 	'Vampiro One',
	 	'Vollkorn:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,500;1,600;1,700;1,800;1,900',
	 	'Volkhov:ital,wght@0,400;0,700;1,400;1,700',
	 	'Work Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900',
	 	'Yanone Kaffeesatz:wght@200;300;400;500;600;700',
	 	'ZCOOL XiaoWei'
	);
	
	$query_args = array(
		'family'	=> rawurlencode(implode('|',$font_family)),
	);
	$font_url = add_query_arg($query_args,'//fonts.googleapis.com/css');
	return $font_url;
	$contents = audio_podcast_wptt_get_webfont_url( esc_url_raw( $fonts_url ) );
}

/* Theme enqueue scripts */
function audio_podcast_scripts() {
	wp_enqueue_style( 'audio-podcast-font', audio_podcast_font_url(), array() );
	wp_enqueue_style( 'audio-podcast-block-style', get_theme_file_uri('/assets/css/blocks.css') );
	wp_enqueue_style( 'audio-podcast-block-patterns-style-frontend', get_theme_file_uri('/inc/block-patterns/css/block-frontend.css') );
	wp_enqueue_style( 'bootstrap-style', get_template_directory_uri().'/assets/css/bootstrap.css' );
	wp_enqueue_style( 'audio-podcast-basic-style', get_stylesheet_uri() );
	wp_style_add_data('audio-podcast-basic-style', 'rtl', 'replace');
	/* Inline style sheet */
	require get_parent_theme_file_path( '/custom-style.php' );
	wp_add_inline_style( 'audio-podcast-basic-style',$audio_podcast_custom_css );
	wp_enqueue_style( 'font-awesome-css', get_template_directory_uri().'/assets/css/fontawesome-all.css' );
	wp_enqueue_script( 'jquery-superfish', get_theme_file_uri( '/assets/js/jquery.superfish.js' ), array( 'jquery' ), '2.1.2', true );
	wp_enqueue_script( 'bootstrap-js', get_template_directory_uri(). '/assets/js/bootstrap.js', array('jquery') ,'',true);
	wp_enqueue_script( 'audio-podcast-custom-scripts', get_template_directory_uri() . '/assets/js/custom.js', array('jquery'),'' ,true );

	if (get_theme_mod('audio_podcast_animation', true) == true){
		wp_enqueue_script( 'wow-jquery', get_template_directory_uri() . '/assets/js/wow.js', array('jquery'),'' ,true );
		wp_enqueue_style( 'animate-style', get_template_directory_uri().'/assets/css/animate.css' );
	}

	
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}

	/* Enqueue the Dashicons script */
	wp_enqueue_style( 'dashicons' );
}
add_action( 'wp_enqueue_scripts', 'audio_podcast_scripts' );


/**
 * Enqueue block editor style
 */
function audio_podcast_block_editor_styles() {
	wp_enqueue_style( 'audio-podcast-font', audio_podcast_font_url(), array() );
    wp_enqueue_style( 'audio-podcast-block-patterns-style-editor', get_theme_file_uri( '/inc/block-patterns/css/block-editor.css' ), false, '1.0', 'all' );
    wp_enqueue_style( 'bootstrap-style', get_template_directory_uri().'/assets/css/bootstrap.css' );
    wp_enqueue_style( 'font-awesome-css', get_template_directory_uri().'/assets/css/fontawesome-all.css' );
}
add_action( 'enqueue_block_editor_assets', 'audio_podcast_block_editor_styles' );

function audio_podcast_sanitize_dropdown_pages( $page_id, $setting ) {
  	// Ensure $input is an absolute integer.
  	$page_id = absint( $page_id );
  	// If $page_id is an ID of a published page, return it; otherwise, return the default.
  	return ( 'publish' == get_post_status( $page_id ) ? $page_id : $setting->default );
}

function audio_podcast_sanitize_choices( $input, $setting ) {
    global $wp_customize; 
    $control = $wp_customize->get_control( $setting->id ); 
    if ( array_key_exists( $input, $control->choices ) ) {
        return $input;
    } else {
        return $setting->default;
    }
}

function audio_podcast_sanitize_number_range( $number, $setting ) {
	
	// Ensure input is an absolute integer.
	$number = absint( $number );
	
	// Get the input attributes associated with the setting.
	$atts = $setting->manager->get_control( $setting->id )->input_attrs;
	
	// Get minimum number in the range.
	$min = ( isset( $atts['min'] ) ? $atts['min'] : $number );
	
	// Get maximum number in the range.
	$max = ( isset( $atts['max'] ) ? $atts['max'] : $number );
	
	// Get step.
	$step = ( isset( $atts['step'] ) ? $atts['step'] : 1 );
	
	// If the number is within the valid range, return it; otherwise, return the default
	return ( $min <= $number && $number <= $max && is_int( $number / $step ) ? $number : $setting->default );
}

function audio_podcast_sanitize_number_absint( $number, $setting ) {
	// Ensure $number is an absolute integer (whole number, zero or greater).
	$number = absint( $number );
	
	// If the input is an absolute integer, return it; otherwise, return the default
	return ( $number ? $number : $setting->default );
}

/* Excerpt Limit Begin */
function audio_podcast_string_limit_words($string, $word_limit) {
	$words = explode(' ', $string, ($word_limit + 1));
	if(count($words) > $word_limit)
	array_pop($words);
	return implode(' ', $words);
}

if ( ! function_exists( 'audio_podcast_switch_sanitization' ) ) {
	function audio_podcast_switch_sanitization( $input ) {
		if ( true === $input ) {
			return 1;
		} else {
			return 0;
		}
	}
}

// Change number or products per row to 3
add_filter('loop_shop_columns', 'audio_podcast_loop_columns');
	if (!function_exists('audio_podcast_loop_columns')) {
		function audio_podcast_loop_columns() {
		return 3; // 3 products per row
	}
}

function audio_podcast_sanitize_phone_number( $phone ) {
	return preg_replace( '/[^\d+]/', '', $phone );
}

function audio_podcast_logo_title_hide_show(){
	if(get_theme_mod('audio_podcast_logo_title_hide_show') == '1' ) {
		return true;
	}
	return false;
}

function audio_podcast_tagline_hide_show(){
	if(get_theme_mod('audio_podcast_tagline_hide_show',0) == '1' ) {
		return true;
	}
	return false;
}

//Active Callback
function audio_podcast_default_slider(){
	if(get_theme_mod('audio_podcast_slider_type', 'Default slider') == 'Default slider' ) {
		return true;
	}
	return false;
}

function audio_podcast_advance_slider(){
	if(get_theme_mod('audio_podcast_slider_type', 'Default slider') == 'Advance slider' ) {
		return true;
	}
	return false;
}

function audio_podcast_blog_post_featured_image_dimension(){
	if(get_theme_mod('audio_podcast_blog_post_featured_image_dimension') == 'custom' ) {
		return true;
	}
	return false;
}


// edit

if (!function_exists('audio_podcast_edit_link')) :

    function audio_podcast_edit_link($view = 'default')
    {
        global $post;
            edit_post_link(
                sprintf(
                    wp_kses(
                    /* translators: %s: Name of current post. Only visible to screen readers */
                        __('Edit <span class="screen-reader-text">%s</span>', 'audio-podcast'),
                        array(
                            'span' => array(
                                'class' => array(),
                            ),
                        )
                    ),
                    get_the_title()
                ),
                '<span class="edit-link"><i class="fas fa-edit"></i>',
                '</span>'
            );

    }
endif;

/* Implement the Custom Header feature. */
require get_template_directory() . '/inc/custom-header.php';

function audio_podcast_init_setup() {
	/* Custom template tags for this theme. */
	require get_template_directory() . '/inc/template-tags.php';

	/* Customizer additions. */
	require get_template_directory() . '/inc/customizer.php';

	/* Typography */
	require get_template_directory() . '/inc/typography/ctypo.php';

	/* Plugin Activation */
	require get_template_directory() . '/inc/getstart/plugin-activation.php';

	/* Implement the About theme page */
	require get_template_directory() . '/inc/getstart/getstart.php';

	/* Block Pattern */
	require get_template_directory() . '/inc/block-patterns/block-patterns.php';

	/* TGM Plugin Activation */
	require get_template_directory() . '/inc/tgm/tgm.php';

	/* Social Icons */
	require get_template_directory() . '/inc/themes-widgets/social-icon.php';

	/* Webfonts */
	require get_template_directory() . '/inc/wptt-webfont-loader.php';

	/* Customizer additions. */
	require get_template_directory() . '/inc/themes-widgets/about-us-widget.php';

	/* Customizer additions. */
	require get_template_directory() . '/inc/themes-widgets/contact-us-widget.php';

	define('AUDIO_PODCAST_FREE_THEME_DOC',__('https://preview.vwthemesdemo.com/docs/free-audio-podcast/','audio-podcast'));
	define('AUDIO_PODCAST_SUPPORT',__('https://wordpress.org/support/theme/audio-podcast/','audio-podcast'));
	define('AUDIO_PODCAST_REVIEW',__('https://wordpress.org/support/theme/audio-podcast/reviews','audio-podcast'));
	define('AUDIO_PODCAST_BUY_NOW',__('https://www.vwthemes.com/products/audio-podcast-wordpress-theme','audio-podcast'));
	define('AUDIO_PODCAST_LIVE_DEMO',__('https://www.vwthemes.net/vw-audio-podcast/','audio-podcast'));
	define('AUDIO_PODCAST_PRO_DOC',__('https://preview.vwthemesdemo.com/docs/vw-audio-podcast-pro/','audio-podcast'));
	define('AUDIO_PODCAST_FAQ',__('https://www.vwthemes.com/faqs/','audio-podcast'));
	define('AUDIO_PODCAST_CHILD_THEME',__('https://developer.wordpress.org/themes/advanced-topics/child-themes/','audio-podcast'));
	define('AUDIO_PODCAST_CONTACT',__('https://www.vwthemes.com/contact/','audio-podcast'));
	define('AUDIO_PODCAST_CREDIT',__('https://www.vwthemes.com/products/free-podcast-wordpress-theme','audio-podcast'));
	define('AUDIO_PODCAST_THEME_BUNDLE_BUY_NOW',__('https://www.vwthemes.com/products/wp-theme-bundle','audio-podcast'));
	define('AUDIO_PODCAST_THEME_BUNDLE_DOC',__('https://preview.vwthemesdemo.com/docs/theme-bundle/','audio-podcast'));

	if ( ! function_exists( 'audio_podcast_credit' ) ) {
		function audio_podcast_credit(){
			echo "<a href=".esc_url(AUDIO_PODCAST_CREDIT)." target='_blank'>".esc_html__('Audio Podcast WordPress Theme','audio-podcast')."</a>";
		}
	}
}
add_action( 'after_setup_theme', 'audio_podcast_init_setup' );	

// Admin notice code START
function audio_podcast_dismissed_notice() {
	update_option( 'audio_podcast_admin_notice', true );
}
add_action( 'wp_ajax_audio_podcast_dismissed_notice', 'audio_podcast_dismissed_notice' );

//After Switch theme function
add_action('after_switch_theme', 'audio_podcast_getstart_setup_options');
function audio_podcast_getstart_setup_options () {
    update_option('audio_podcast_admin_notice', false );
}
// Admin notice code END












 




// ========== ABS PLAYER ==========

// API ключ (хранится только на сервере)
if (!defined('ABS_API_KEY')) {
    define('ABS_API_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJrZXlJZCI6ImM5NWI3YTI2LTBhNGEtNGZhZC04NGVlLTFjYTdjMjIyMTBlZCIsIm5hbWUiOiJ3cCIsInR5cGUiOiJhcGkiLCJpYXQiOjE3NzY1OTA4MTR9.JTWM94qCJ7CFIHcP6pjr5ilcSAntJJIRuC4STOovGZ8');
}

require_once get_template_directory() . '/includes/abs-functions.php';

// Новый парсер FB2
require_once get_template_directory() . '/includes/abs-parser-ranobe-fb2.php';
require_once get_template_directory() . '/includes/abs-parser-ranobe-fb2-admin.php';


// Подключаем каталог книг
require_once get_template_directory() . '/includes/abs-catalog.php';

require_once get_template_directory() . '/includes/abs-genres-widget.php';

// Подключаем авторов
require_once get_template_directory() . '/includes/abs-authors.php';

// Подключаем AJAX обработчики
$abs_ajax_file = get_template_directory() . '/includes/abs-ajax.php';
if (file_exists($abs_ajax_file)) {
    require_once $abs_ajax_file;
}

// Подключаем личный кабинет
$abs_dashboard_file = get_template_directory() . '/includes/abs-user-dashboard.php';
if (file_exists($abs_dashboard_file)) {
    require_once $abs_dashboard_file;
}

// Подключаем импорт книг
$abs_importer_file = get_template_directory() . '/includes/abs-importer.php';
if (file_exists($abs_importer_file)) {
    require_once $abs_importer_file;
}

// Подключаем импорт длительностей
$abs_duration_file = get_template_directory() . '/includes/abs-duration-importer.php';
if (file_exists($abs_duration_file)) {
    require_once $abs_duration_file;
}

// CSS для плеера
function abs_player_styles() {
    wp_enqueue_style('abs-player-style', get_stylesheet_directory_uri() . '/css/player-style.css', array(), '1.0');
}
add_action('wp_enqueue_scripts', 'abs_player_styles');

// Шорткод плеера
function abs_player_shortcode() {
    global $post;
    $book_id = get_post_meta($post->ID, 'abs_book_id', true);
    
    if ($book_id) {
        abs_track_book_view($book_id);
    }
    
    // Текстовая версия
    $ranobe_permalink = '';
    if ($book_id) {
        $meta = abs_get_book_meta_from_ranobe($book_id);
        if ($meta['ranobe_id']) {
            $ranobe_permalink = get_permalink($meta['ranobe_id']);
        }
    }
    
    $html = '<div style="text-align:center;margin:10px 0;"><a href="https://pay.cloudtips.ru/p/db763c18" target="_blank" style="display:inline-block;background:linear-gradient(135deg,#ff9800,#ff5722);color:#fff;padding:12px 28px;border-radius:30px;text-decoration:none;font-weight:700;font-size:1rem;margin-bottom: 10px;">💰 Поддержать проект</a></div>';
$html .= '<div class="audiobookshelf-player-container">';
    
    // Вкладки
    if ($ranobe_permalink) {
        $html .= '<div class="ranobe-tabs" style="display:flex; gap:0; margin:0 0 0 0; border-bottom:1px solid rgba(255,255,255,0.1);">';
        $html .= '<span class="ranobe-tab active" style="padding:14px 28px; color:#0dcaf0; border-bottom:3px solid #0dcaf0; font-size:1.1rem; font-weight:600;">🎧 Слушать</span>';
        $html .= '<a href="' . esc_url($ranobe_permalink) . '" style="padding:14px 28px; color:rgba(255,255,255,0.7); text-decoration:none; font-size:1.1rem; font-weight:600;">📖 Читать</a>';
        $html .= '</div>';
    }
    
    // Скрытые элементы для JS
    $html .= '<div style="display:none;">';
    $html .= '<div id="book-cover"></div>';
    $html .= '<h1 id="book-title"></h1>';
    $html .= '<div id="book-author"></div>';
    $html .= '<div id="book-description"></div>';
    $html .= '<div id="book-tags"></div>';
    $html .= '<div id="book-meta"></div>';
    $html .= '</div>';
    
    $html .= '
        <div class="player-container">
            <button id="abs-prev" class="play-prev-btn">⏮</button>
            <button id="abs-play-pause-big" class="play-pause-btn-big">▶</button>
            <button id="abs-next" class="play-next-btn">⏭</button>
            <div class="progress-wrapper">
                <div class="track-title-display" id="current-track-title">--</div>
                <input type="range" id="abs-seek-slider" value="0" min="0" step="1">
                <div class="time-info">
                    <span id="current-time">0:00</span>
                    <span id="duration-time">0:00</span>
                </div>
            </div>
            <div class="volume-control">
                <button id="abs-volume-btn" class="volume-btn">🔊</button>
                <input type="range" id="abs-volume-slider" class="volume-slider" min="0" max="100" value="80" style="display: none;">
            </div>
            <div class="speed-control">
                <button id="abs-speed-btn" class="speed-btn">1x</button>
                <div class="speed-menu" id="abs-speed-menu" style="display: none;">
                    <button data-speed="0.5">0.5x</button>
                    <button data-speed="0.75">0.75x</button>
                    <button data-speed="1">1x</button>
                    <button data-speed="1.25">1.25x</button>
                    <button data-speed="1.5">1.5x</button>
                    <button data-speed="2">2x</button>
                </div>
            </div>
        </div>
        <div class="chapters-list">
            <h4>Содержание</h4>
            <ul id="abs-track-list"><li>Загрузка...</li></ul>
        </div>
    </div>';
    
    return $html;
}
add_shortcode('abs_player', 'abs_player_shortcode');

function abs_continue_listening_shortcode() {
    if (!is_user_logged_in()) {
        return '<div class="continue-listening"><h2>📖 Продолжить</h2><div class="catalog-books-grid"><div class="catalog-book-card"><div style="padding:20px;text-align:center;">🔒 Авторизуйтесь</div><a href="' . home_url('/login') . '" class="catalog-listen-btn" style="display:block;text-align:center;margin:10px auto;">Войти</a></div></div></div>';
    }
    
    global $wpdb;
    $user_id = get_current_user_id();
    $progress_table = $wpdb->prefix . 'abs_progress';
    $reading_table = $wpdb->prefix . 'abs_reading_progress';
    $cache_table = $wpdb->prefix . 'abs_book_cache';
    $meta_table = $wpdb->prefix . 'abs_audio_meta';
    
    $all_books = array();
    
    // Аудиокниги в прогрессе
    $audio_books = $wpdb->get_results($wpdb->prepare(
        "SELECT p.book_id, p.total_progress_seconds, c.book_data, c.total_duration,
                m.author, m.genres, m.description, m.cover_url
         FROM $progress_table p 
         LEFT JOIN $cache_table c ON p.book_id = c.book_id 
         LEFT JOIN $meta_table m ON p.book_id = m.book_id
         WHERE p.user_id = %d AND p.total_progress_seconds > 0
         ORDER BY p.updated_at DESC LIMIT 10",
        $user_id
    ));
    
    foreach ($audio_books as $ab) {
        $book_data = json_decode($ab->book_data, true);
        $title = $book_data['media']['metadata']['title'] ?? 'Без названия';
        $perm = abs_get_book_permalink($ab->book_id);
        $total_dur = (int)$ab->total_duration;
        $progress = (int)$ab->total_progress_seconds;
        $pct = ($total_dur > 0) ? round(($progress / $total_dur) * 100, 1) : 0;
        if ($pct > 100) $pct = 100;
        
        $all_books[] = array(
            'type' => 'audio', 'title' => $title, 'permalink' => $perm,
            'book_id' => $ab->book_id, 'cover_url' => $ab->cover_url ?: '',
            'author' => $ab->author ?: '', 
            'genres' => $ab->genres ? explode(', ', $ab->genres) : array(),
            'description' => $ab->description ?: ($book_data['media']['metadata']['description'] ?? ''),
            'progress' => $pct, 'updated' => $ab->updated_at,
        );
    }
    
    // Текстовые книги в прогрессе
    $text_books = $wpdb->get_results($wpdb->prepare(
        "SELECT rp.ranobe_id, rp.chapter_number, rp.total_chapters, rp.updated_at
         FROM $reading_table rp
         WHERE rp.user_id = %d
         ORDER BY rp.updated_at DESC LIMIT 10",
        $user_id
    ));
    
    foreach ($text_books as $tb) {
        $post = get_post($tb->ranobe_id);
        if (!$post) continue;
        $pct = ($tb->total_chapters > 0) ? round(($tb->chapter_number / $tb->total_chapters) * 100, 1) : 0;
        $author = get_post_meta($post->ID, '_ranobe_author', true);
        $cats = wp_get_post_categories($post->ID);
        $genres = array();
        foreach ($cats as $cid) { $cat = get_category($cid); if ($cat) $genres[] = $cat->name; }
        $desc = $post->post_excerpt ?: wp_trim_words(strip_tags($post->post_content), 20);
        
        $all_books[] = array(
            'type' => 'text', 'title' => $post->post_title, 'permalink' => get_permalink($post->ID),
            'book_id' => $tb->ranobe_id,
            'cover_url' => has_post_thumbnail($post->ID) ? get_the_post_thumbnail_url($post->ID, 'medium') : '',
            'author' => $author ?: '', 'genres' => $genres, 'description' => $desc,
            'progress' => $pct, 'updated' => $tb->updated_at,
        );
    }
    
    // Сортируем по дате обновления
    usort($all_books, function($a, $b) { return strcmp($b['updated'], $a['updated']); });
    $all_books = array_slice($all_books, 0, 10);
    
    if (empty($all_books)) {
        return '<div class="continue-listening"><h2>📖 Продолжить</h2><div class="catalog-books-grid"><div class="catalog-book-card"><div style="padding:20px;text-align:center;">📚 Нет книг в процессе</div></div></div></div>';
    }
    
    $html = '<div class="continue-listening"><h2>📖 Продолжить</h2><div class="catalog-books-grid">';
    
    foreach ($all_books as $book) {
        $type_badge = $book['type'] === 'audio' ? '🎧' : '📖';
        $btn_text = $book['type'] === 'audio' ? '▶ Продолжить' : '📖 Читать';
        
        $html .= '<div class="catalog-book-card" style="position:relative;">';
        $html .= '<button class="remove-progress-btn" data-book-id="' . esc_attr($book['book_id']) . '" data-type="' . $book['type'] . '" title="Удалить" style="position:absolute;top:8px;right:8px;z-index:10;background:rgba(0,0,0,0.5);border:none;color:#fff;width:24px;height:24px;border-radius:50%;cursor:pointer;font-size:14px;">✕</button>';
        
        $html .= '<div class="catalog-book-cover">';
        if ($book['cover_url']) {
            $html .= '<img src="' . esc_url($book['cover_url']) . '" alt="" onerror="this.style.display=\'none\'; this.parentElement.querySelector(\'.no-cover\').style.display=\'flex\';">';
            $html .= '<div class="no-cover" style="display:none;">' . $type_badge . '</div>';
        } else {
            $html .= '<div class="no-cover">' . $type_badge . '</div>';
        }
        $html .= '</div>';
        
        $html .= '<div class="catalog-book-info">';
        $html .= '<h3 class="catalog-book-title"><a href="' . esc_url($book['permalink']) . '">' . esc_html($book['title']) . ' <span style="font-size:0.7rem;color:#0dcaf0;">' . $type_badge . '</span></a></h3>';
        if ($book['author']) {
            $html .= '<div class="catalog-book-author"><a href="/catalog?author=' . urlencode($book['author']) . '" class="author-link">' . esc_html($book['author']) . '</a></div>';
        }
        if (!empty($book['genres'])) {
            $html .= '<div class="catalog-book-genres">';
            foreach ($book['genres'] as $g) {
                $html .= '<a href="/catalog?genre=' . urlencode($g) . '" class="book-genre-tag">' . esc_html($g) . '</a>';
            }
            $html .= '</div>';
        }
        if ($book['description']) {
            $html .= '<div class="catalog-book-description">' . esc_html(wp_trim_words(wp_strip_all_tags($book['description']), 15, '...')) . '</div>';
        }
        $html .= '</div>';
        
        $html .= '<div class="catalog-book-actions" style="flex-direction:column;align-items:center;justify-content:center;gap:8px;">';
        $html .= '<a href="' . esc_url($book['permalink']) . '" class="catalog-listen-btn">' . $btn_text . '</a>';
        $html .= '<div class="progress-bar-container" style="width:100%;height:4px;background:rgba(255,255,255,0.2);border-radius:2px;overflow:hidden;"><div class="progress-bar-fill" style="width:' . $book['progress'] . '%;height:100%;background:#0dcaf0;"></div></div>';
        $html .= '<span style="font-size:0.85rem;color:#0dcaf0;font-weight:600;">' . $book['progress'] . '%</span>';
        $html .= '</div>';
        
        $html .= '</div>';
    }
    
    $html .= '</div></div>';
    return $html;
}
add_shortcode('abs_continue', 'abs_continue_listening_shortcode');
// Шорткод "Избранное" (ИСПРАВЛЕН)
function abs_favorites_shortcode() {
    if (!is_user_logged_in()) {
        return '<div class="favorites-list"><h2>⭐ Избранное</h2><div class="catalog-books-grid"><div class="catalog-book-card"><div style="padding:20px;text-align:center;">🔒 Чтобы увидеть избранное, авторизуйтесь</div><a href="' . home_url('/login?redirect_to=' . urlencode(get_permalink())) . '" class="catalog-listen-btn" style="display:block;text-align:center;margin:10px auto;">Войти в аккаунт</a></div></div></div>';
    }
    
    global $wpdb;
    $user_id = get_current_user_id();
    $fav_table = $wpdb->prefix . 'abs_favorites';
    $cache_table = $wpdb->prefix . 'abs_book_cache';
    $meta_table = $wpdb->prefix . 'abs_audio_meta';
    
    $all_books = array();
    
    // Аудиокниги из избранного
    $audio_favs = $wpdb->get_results($wpdb->prepare(
        "SELECT f.book_id, c.book_data, m.author, m.genres, m.description, m.cover_url
         FROM $fav_table f
         LEFT JOIN $cache_table c ON f.book_id = c.book_id
         LEFT JOIN $meta_table m ON f.book_id = m.book_id
         WHERE f.user_id = %d AND f.type = 'audio'
         ORDER BY f.added_at DESC",
        $user_id
    ));
    
    foreach ($audio_favs as $fav) {
        $book_data = json_decode($fav->book_data, true);
        $title = $book_data['media']['metadata']['title'] ?? 'Без названия';
        $perm = abs_get_book_permalink($fav->book_id);
        $all_books[] = array(
            'type'      => 'audio',
            'title'     => $title,
            'permalink' => $perm,
            'book_id'   => $fav->book_id,
            'cover_url' => $fav->cover_url ?: '',
            'author'    => $fav->author ?: '',
            'genres'    => $fav->genres ? explode(', ', $fav->genres) : array(),
            'description' => $fav->description ?: ($book_data['media']['metadata']['description'] ?? ''),
        );
    }
    
    // Текстовые книги из избранного
    $text_favs = $wpdb->get_results($wpdb->prepare(
        "SELECT f.ranobe_id, f.book_id
         FROM $fav_table f
         WHERE f.user_id = %d AND f.type = 'text'
         ORDER BY f.added_at DESC",
        $user_id
    ));
    
    foreach ($text_favs as $fav) {
        $tb = get_post($fav->ranobe_id);
        if (!$tb) continue;
        $author = get_post_meta($tb->ID, '_ranobe_author', true);
        $cats = wp_get_post_categories($tb->ID);
        $genres = array();
        foreach ($cats as $cid) {
            $cat = get_category($cid);
            if ($cat) $genres[] = $cat->name;
        }
        $desc = $tb->post_excerpt ?: wp_trim_words(strip_tags($tb->post_content), 20);
        $all_books[] = array(
            'type'      => 'text',
            'title'     => $tb->post_title,
            'permalink' => get_permalink($tb->ID),
            'book_id'   => $fav->book_id,
            'cover_url' => has_post_thumbnail($tb->ID) ? get_the_post_thumbnail_url($tb->ID, 'medium') : '',
            'author'    => $author ?: '',
            'genres'    => $genres,
            'description' => $desc,
        );
    }
    
    if (empty($all_books)) {
        return '<div class="favorites-list"><h2>⭐ Избранное</h2><div class="catalog-books-grid"><div class="catalog-book-card"><div style="padding:20px;text-align:center;">⭐ Нет книг в избранном</div></div></div></div>';
    }
    
    $html = '<div class="favorites-list"><h2>⭐ Избранное</h2><div class="catalog-books-grid">';
    
    foreach ($all_books as $book) {
        $type_badge = $book['type'] === 'audio' ? '🎧' : '📖';
        $btn_text = $book['type'] === 'audio' ? '▶ Слушать' : '📖 Читать';
        
        $html .= '<div class="catalog-book-card">';
        $html .= '<div class="catalog-book-cover">';
        if ($book['cover_url']) {
            $html .= '<img src="' . esc_url($book['cover_url']) . '" alt="" onerror="this.style.display=\'none\'; this.parentElement.querySelector(\'.no-cover\').style.display=\'flex\';">';
            $html .= '<div class="no-cover" style="display:none;">' . $type_badge . '</div>';
        } else {
            $html .= '<div class="no-cover">' . $type_badge . '</div>';
        }
        $html .= '</div>';
        
        $html .= '<div class="catalog-book-info">';
        $html .= '<h3 class="catalog-book-title"><a href="' . esc_url($book['permalink']) . '">' . esc_html($book['title']) . ' <span style="font-size:0.7rem;color:#0dcaf0;">' . $type_badge . '</span></a></h3>';
        if ($book['author']) {
            $html .= '<div class="catalog-book-author"><a href="/catalog?author=' . urlencode($book['author']) . '" class="author-link">' . esc_html($book['author']) . '</a></div>';
        }
        if (!empty($book['genres'])) {
            $html .= '<div class="catalog-book-genres">';
            foreach ($book['genres'] as $g) {
                $html .= '<a href="/catalog?genre=' . urlencode($g) . '" class="book-genre-tag">' . esc_html($g) . '</a>';
            }
            $html .= '</div>';
        }
        if ($book['description']) {
            $html .= '<div class="catalog-book-description">' . esc_html(wp_trim_words(wp_strip_all_tags($book['description']), 15, '...')) . '</div>';
        }
        $html .= '</div>';
        
        $html .= '<div class="catalog-book-actions"><a href="' . esc_url($book['permalink']) . '" class="catalog-listen-btn">' . $btn_text . '</a></div>';
        $html .= '</div>';
    }
    
    $html .= '</div></div>';
    return $html;
}
add_shortcode('abs_favorites', 'abs_favorites_shortcode');

// Шорткод "Новинки" (заголовок H2)
function abs_new_releases_shortcode($atts) {
    $atts = shortcode_atts(array('limit' => 12), $atts);
    $limit = intval($atts['limit']);
    
    global $wpdb;
    $cache_table = $wpdb->prefix . 'abs_book_cache';
    $meta_table = $wpdb->prefix . 'abs_audio_meta';
    
    // Аудиокниги
    $audio_books = $wpdb->get_results($wpdb->prepare(
        "SELECT c.book_id, c.book_data, m.author, m.genres, m.description, m.cover_url, m.ranobe_id
         FROM $cache_table c
         LEFT JOIN $meta_table m ON c.book_id = m.book_id
         ORDER BY c.updated_at DESC
         LIMIT %d",
        $limit
    ));
    
    // Текстовые книги
    $text_books = get_posts(array(
        'post_type'      => 'ranobe',
        'posts_per_page' => $limit,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ));
    
    // Объединяем в общий массив
    $all_books = array();
    
    foreach ($audio_books as $ab) {
        $book_data = json_decode($ab->book_data, true);
        $title = $book_data['media']['metadata']['title'] ?? 'Без названия';
        $perm = abs_get_book_permalink($ab->book_id);
        $all_books[] = array(
            'type'        => 'audio',
            'title'       => $title,
            'permalink'   => $perm,
            'book_id'     => $ab->book_id,
            'cover_url'   => $ab->cover_url ?: '',
            'author'      => $ab->author ?: '',
            'genres'      => $ab->genres ? explode(', ', $ab->genres) : array(),
            'description' => $ab->description ?: ($book_data['media']['metadata']['description'] ?? ''),
            'date'        => $ab->updated_at,
        );
    }
    
    foreach ($text_books as $tb) {
        $author = get_post_meta($tb->ID, '_ranobe_author', true);
        $cats = wp_get_post_categories($tb->ID);
        $genres = array();
        foreach ($cats as $cid) {
            $cat = get_category($cid);
            if ($cat) $genres[] = $cat->name;
        }
        $desc = $tb->post_excerpt ?: wp_trim_words(strip_tags($tb->post_content), 20);
        $all_books[] = array(
            'type'        => 'text',
            'title'       => $tb->post_title,
            'permalink'   => get_permalink($tb->ID),
            'book_id'     => $tb->ID,
            'cover_url'   => has_post_thumbnail($tb->ID) ? get_the_post_thumbnail_url($tb->ID, 'medium') : '',
            'author'      => $author ?: '',
            'genres'      => $genres,
            'description' => $desc,
            'date'        => $tb->post_date,
        );
    }
    
    // Сортируем по дате
    usort($all_books, function($a, $b) { return strcmp($b['date'], $a['date']); });
    $all_books = array_slice($all_books, 0, $limit);
    
    if (empty($all_books)) {
        return '<div class="new-releases-section"><h2>🆕 Новинки</h2><div class="catalog-books-grid"><div class="catalog-book-card"><div style="padding:20px;text-align:center;">📚 Новинки не найдены</div></div></div></div>';
    }
    
    $html = '<div class="new-releases-section"><h2>🆕 Новинки</h2><div class="catalog-books-grid">';
    
    foreach ($all_books as $book) {
        $type_badge = $book['type'] === 'audio' ? '🎧' : '📖';
        $btn_text = $book['type'] === 'audio' ? '▶ Слушать' : '📖 Читать';
        
        $html .= '<div class="catalog-book-card">';
        $html .= '<div class="catalog-book-cover">';
        if ($book['cover_url']) {
            $html .= '<img src="' . esc_url($book['cover_url']) . '" alt="" onerror="this.style.display=\'none\'; this.parentElement.querySelector(\'.no-cover\').style.display=\'flex\';">';
            $html .= '<div class="no-cover" style="display:none;">' . $type_badge . '</div>';
        } else {
            $html .= '<div class="no-cover">' . $type_badge . '</div>';
        }
        $html .= '</div>';
        
        $html .= '<div class="catalog-book-info">';
        $html .= '<h3 class="catalog-book-title"><a href="' . esc_url($book['permalink']) . '">' . esc_html($book['title']) . ' <span style="font-size:0.7rem;color:#0dcaf0;">' . $type_badge . '</span></a></h3>';
        if ($book['author']) {
            $html .= '<div class="catalog-book-author"><a href="/catalog?author=' . urlencode($book['author']) . '" class="author-link">' . esc_html($book['author']) . '</a></div>';
        }
        if (!empty($book['genres'])) {
            $html .= '<div class="catalog-book-genres">';
            foreach ($book['genres'] as $g) {
                $html .= '<a href="/catalog?genre=' . urlencode($g) . '" class="book-genre-tag">' . esc_html($g) . '</a>';
            }
            $html .= '</div>';
        }
        if ($book['description']) {
            $html .= '<div class="catalog-book-description">' . esc_html(wp_trim_words(wp_strip_all_tags($book['description']), 15, '...')) . '</div>';
        }
        $html .= '</div>';
        
        $html .= '<div class="catalog-book-actions"><a href="' . esc_url($book['permalink']) . '" class="catalog-listen-btn">' . $btn_text . '</a></div>';
        $html .= '</div>';
    }
    
    $html .= '</div></div>';
    return $html;
}
add_shortcode('abs_new_releases', 'abs_new_releases_shortcode');

// Шорткод "Популярное" (заголовок H2)
function abs_popular_shortcode($atts) {
    $atts = shortcode_atts(array('limit' => 12), $atts);
    $limit = intval($atts['limit']);
    
    global $wpdb;
    $cache_table = $wpdb->prefix . 'abs_book_cache';
    $meta_table = $wpdb->prefix . 'abs_audio_meta';
    $progress_table = $wpdb->prefix . 'abs_progress';
    $durations_table = $wpdb->prefix . 'abs_track_durations';
    
    $all_books = array();
    
    // Аудиокниги — процент завершения
    $audio_books = $wpdb->get_results("
        SELECT c.book_id, c.book_data, c.total_duration,
               m.author, m.genres, m.description, m.cover_url,
               COALESCE(AVG(p.total_progress_seconds / NULLIF(c.total_duration, 0) * 100), 0) as avg_progress,
               COUNT(DISTINCT p.user_id) as listeners
        FROM $cache_table c
        LEFT JOIN $meta_table m ON c.book_id = m.book_id
        LEFT JOIN $progress_table p ON c.book_id = p.book_id
        GROUP BY c.book_id
        HAVING avg_progress > 0
        ORDER BY avg_progress DESC
        LIMIT $limit
    ");
    
    foreach ($audio_books as $ab) {
        $book_data = json_decode($ab->book_data, true);
        $title = $book_data['media']['metadata']['title'] ?? 'Без названия';
        $perm = abs_get_book_permalink($ab->book_id);
        $all_books[] = array(
            'type'      => 'audio',
            'title'     => $title,
            'permalink' => $perm,
            'cover_url' => $ab->cover_url ?: '',
            'author'    => $ab->author ?: '',
            'genres'    => $ab->genres ? explode(', ', $ab->genres) : array(),
            'description' => $ab->description ?: ($book_data['media']['metadata']['description'] ?? ''),
            'score'     => round($ab->avg_progress, 1),
        );
    }
    
    // Текстовые книги — процент прочитанных глав
    $text_books = get_posts(array(
        'post_type'      => 'ranobe',
        'posts_per_page' => $limit,
    ));
    
    foreach ($text_books as $tb) {
        $chapters = get_posts(array(
            'post_type'      => 'chapter',
            'post_parent'    => $tb->ID,
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ));
        $total_chapters = count($chapters);
        if ($total_chapters == 0) continue;
        
        // Считаем средний прогресс по пользователям (из abs_reading_progress пока нет, ставим 0)
        $avg_progress = 0; // TODO: когда будет таблица чтения
        
        $author = get_post_meta($tb->ID, '_ranobe_author', true);
        $cats = wp_get_post_categories($tb->ID);
        $genres = array();
        foreach ($cats as $cid) {
            $cat = get_category($cid);
            if ($cat) $genres[] = $cat->name;
        }
        
        $all_books[] = array(
            'type'      => 'text',
            'title'     => $tb->post_title,
            'permalink' => get_permalink($tb->ID),
            'cover_url' => has_post_thumbnail($tb->ID) ? get_the_post_thumbnail_url($tb->ID, 'medium') : '',
            'author'    => $author ?: '',
            'genres'    => $genres,
            'description' => $tb->post_excerpt ?: wp_trim_words(strip_tags($tb->post_content), 20),
            'score'     => $avg_progress,
        );
    }
    
    // Сортируем по score
    usort($all_books, function($a, $b) { return $b['score'] - $a['score']; });
    $all_books = array_slice($all_books, 0, $limit);
    
    if (empty($all_books)) {
        return '<div class="popular-section"><h2>🔥 Популярное</h2><div class="catalog-books-grid"><div class="catalog-book-card"><div style="padding:20px;text-align:center;">📚 Нет данных</div></div></div></div>';
    }
    
    $html = '<div class="popular-section"><h2>🔥 Популярное</h2><div class="catalog-books-grid">';
    
    foreach ($all_books as $book) {
        $type_badge = $book['type'] === 'audio' ? '🎧' : '📖';
        $btn_text = $book['type'] === 'audio' ? '▶ Слушать' : '📖 Читать';
        
        $html .= '<div class="catalog-book-card">';
        $html .= '<div class="catalog-book-cover">';
        if ($book['cover_url']) {
            $html .= '<img src="' . esc_url($book['cover_url']) . '" alt="" onerror="this.style.display=\'none\'; this.parentElement.querySelector(\'.no-cover\').style.display=\'flex\';">';
            $html .= '<div class="no-cover" style="display:none;">' . $type_badge . '</div>';
        } else {
            $html .= '<div class="no-cover">' . $type_badge . '</div>';
        }
        $html .= '</div>';
        
        $html .= '<div class="catalog-book-info">';
        $html .= '<h3 class="catalog-book-title"><a href="' . esc_url($book['permalink']) . '">' . esc_html($book['title']) . ' <span style="font-size:0.7rem;color:#0dcaf0;">' . $type_badge . '</span></a></h3>';
        if ($book['author']) {
            $html .= '<div class="catalog-book-author"><a href="/catalog?author=' . urlencode($book['author']) . '" class="author-link">' . esc_html($book['author']) . '</a></div>';
        }
        if (!empty($book['genres'])) {
            $html .= '<div class="catalog-book-genres">';
            foreach ($book['genres'] as $g) {
                $html .= '<a href="/catalog?genre=' . urlencode($g) . '" class="book-genre-tag">' . esc_html($g) . '</a>';
            }
            $html .= '</div>';
        }
        if ($book['description']) {
            $html .= '<div class="catalog-book-description">' . esc_html(wp_trim_words(wp_strip_all_tags($book['description']), 15, '...')) . '</div>';
        }
        $html .= '</div>';
        
        $html .= '<div class="catalog-book-actions" style="flex-direction:column;align-items:center;gap:4px;">';
        $html .= '<a href="' . esc_url($book['permalink']) . '" class="catalog-listen-btn">' . $btn_text . '</a>';
        if ($book['score'] > 0) {
            $html .= '<span style="font-size:0.8rem;color:#0dcaf0;">' . $book['score'] . '% завершают</span>';
        }
        $html .= '</div>';
        
        $html .= '</div>';
    }
    
    $html .= '</div></div>';
    return $html;
}
add_shortcode('abs_popular', 'abs_popular_shortcode');

// Скрипты плеера
if (!function_exists('abs_player_enqueue_scripts')) {
    function abs_player_enqueue_scripts() {
        global $post;
        
        $has_player = false;
        if ($post && has_shortcode($post->post_content, 'abs_player')) {
            $has_player = true;
        }
        
        if (!$has_player) {
            return;
        }
        
        $howler_js = get_stylesheet_directory() . '/js/howler.min.js';
        if (file_exists($howler_js)) {
            wp_enqueue_script('howler-js', get_stylesheet_directory_uri() . '/js/howler.min.js', array(), '2.2.4', true);
        } else {
            wp_enqueue_script('howler-js', 'https://cdnjs.cloudflare.com/ajax/libs/howler/2.2.4/howler.min.js', array(), '2.2.4', true);
        }
        
        $player_js = get_stylesheet_directory() . '/js/abs-player.js';
        if (file_exists($player_js)) {
            wp_enqueue_script('abs-player', get_stylesheet_directory_uri() . '/js/abs-player.js', array('howler-js', 'jquery'), '1.0', true);
        }
        
        $book_id = '';
        if ($post) {
            $book_id = get_post_meta($post->ID, 'abs_book_id', true);
        }
        
        wp_localize_script('abs-player', 'absPlayerData', array(
            'apiKey'    => defined('ABS_API_KEY') ? ABS_API_KEY : '',
            'serverUrl' => 'https://audiobook.1001ranobe.ru',
            'itemId'    => $book_id,
            'postId'    => $post ? $post->ID : 0,
            'ajaxUrl'   => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce('abs_player_nonce'),
        ));
    }
    add_action('wp_enqueue_scripts', 'abs_player_enqueue_scripts');
}

// Прямой обработчик аудио
if (!function_exists('direct_audio_handler')) {
    add_action('wp_ajax_get_abs_audio', 'direct_audio_handler');
    add_action('wp_ajax_nopriv_get_abs_audio', 'direct_audio_handler');
    
    function direct_audio_handler() {
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'abs_player_nonce')) {
            status_header(403);
            echo 'Invalid nonce';
            exit;
        }
        
        $book_id = isset($_GET['book_id']) ? sanitize_text_field($_GET['book_id']) : '';
        $file_id = isset($_GET['file_id']) ? sanitize_text_field($_GET['file_id']) : '';
        
        if (!$book_id || !$file_id) {
            status_header(400);
            echo 'Missing book_id or file_id';
            exit;
        }
        
        $api_key = defined('ABS_API_KEY') ? ABS_API_KEY : '';
        $url = 'https://audiobook.1001ranobe.ru/api/items/' . $book_id . '/file/' . $file_id;
        
        $response = wp_remote_get($url, array(
            'headers' => array('Authorization' => 'Bearer ' . $api_key),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            status_header(500);
            echo 'Error: ' . $response->get_error_message();
            exit;
        }
        
        $status = wp_remote_retrieve_response_code($response);
        if ($status !== 200) {
            status_header($status);
            echo "ABS returned $status";
            exit;
        }
        
        $type = wp_remote_retrieve_header($response, 'content-type');
        $body = wp_remote_retrieve_body($response);
        
        header('Content-Type: ' . ($type ?: 'audio/mpeg'));
        header('Content-Length: ' . strlen($body));
        echo $body;
        exit;
    }
}

// Функция для получения ID страницы по book_id
function abs_get_page_by_book_id($book_id) {
    global $wpdb;
    $page_id = $wpdb->get_var($wpdb->prepare(
        "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'abs_book_id' AND meta_value = %s",
        $book_id
    ));
    return $page_id ? get_permalink($page_id) : '#';
}

// Удаление прогресса книги
add_action('wp_ajax_remove_abs_progress', 'abs_ajax_remove_progress');

function abs_ajax_remove_progress() {
    $user_id = get_current_user_id();
    $book_id = sanitize_text_field($_POST['book_id']);
    
    if (!$user_id || !$book_id) {
        wp_send_json_error('Неверные данные');
        return;
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'abs_progress';
    
    $result = $wpdb->delete($table, array(
        'user_id' => $user_id,
        'book_id' => $book_id
    ));
    
    if ($result === false) {
        wp_send_json_error('Ошибка БД');
    } else {
        wp_send_json_success();
    }
}


add_action('wp_ajax_get_page_by_book_id', 'abs_ajax_get_page_by_book_id');
add_action('wp_ajax_nopriv_get_page_by_book_id', 'abs_ajax_get_page_by_book_id');

function abs_ajax_get_page_by_book_id() {
    $book_id = sanitize_text_field($_GET['book_id']);
    
    global $wpdb;
    $page_id = $wpdb->get_var($wpdb->prepare(
        "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'abs_book_id' AND meta_value = %s",
        $book_id
    ));
    
    if ($page_id) {
        wp_send_json_success(array('url' => get_permalink($page_id)));
    } else {
        wp_send_json_error();
    }
}

// Отключить админ-бар для всех, кроме администратора
add_action('after_setup_theme', 'abs_remove_admin_bar');

function abs_remove_admin_bar() {
    if (!current_user_can('administrator')) {
        show_admin_bar(false);
    }
}
add_action('init', 'abs_custom_search_redirect');
function abs_custom_search_redirect() {
    if (is_admin()) return;
    
    $query = '';
    if (isset($_GET['s']) && !empty($_GET['s'])) {
        $query = $_GET['s'];
    }
    
    if (!empty($query)) {
        wp_redirect(home_url('/catalog?search=' . urlencode($query)), 302);
        exit;
    }
}

add_action('init', function() {
    if (get_query_var('author_name')) {
        error_log('author_name: ' . get_query_var('author_name'));
    }
});

// Редирект с плюсами на подчёркивания в ссылках авторов
add_action('template_redirect', 'abs_fix_author_plus_redirect');
function abs_fix_author_plus_redirect() {
    $request_uri = $_SERVER['REQUEST_URI'];
    if (strpos($request_uri, '/author/') !== false && strpos($request_uri, '+') !== false) {
        $new_uri = str_replace('+', '_', $request_uri);
        wp_redirect(home_url($new_uri), 301);
        exit;
    }
}

// Редирект на страницу author-page
add_action('template_redirect', 'abs_redirect_author');
function abs_redirect_author() {
    if (strpos($_SERVER['REQUEST_URI'], '/author/') !== false) {
        $author_slug = basename($_SERVER['REQUEST_URI']);
        $author_slug = str_replace(['/author/', '/'], '', $author_slug);
        $author_slug = str_replace(['+', '_'], ' ', $author_slug);
        if (!empty($author_slug)) {
            wp_redirect(home_url('/catalog?author=' . urlencode($author_slug)), 301);
            exit;
        }
    }
}

require_once get_template_directory() . '/includes/abs-authors.php';



// Шорткод для вывода личного кабинета на сайте
add_shortcode('abs_my_library', 'abs_my_library_shortcode');
function abs_my_library_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>🔒 <a href="' . home_url('/login?redirect_to=' . urlencode(get_permalink())) . '">Войдите</a>, чтобы увидеть свою библиотеку.</p>';
    }
    
    ob_start();
    abs_my_library_page_content();
    return ob_get_clean();
}

// Функция для вывода содержимого библиотеки (без обёртки админки)
function abs_my_library_page_content() {
    $user_id = get_current_user_id();
    $books = abs_get_user_library($user_id);
    ?>
    <div class="abs-user-library-front">
        <h1>📚 Моя библиотека</h1>


                <div class="abs-stats-cards">
            <div class="stat-card">
                <span class="stat-value"><?php echo count($books['listening']); ?></span>
                <span class="stat-label">В процессе</span>
            </div>
            <div class="stat-card">
                <span class="stat-value"><?php echo count($books['completed']); ?></span>
                <span class="stat-label">Прослушано</span>
            </div>
            <div class="stat-card">
                <span class="stat-value"><?php echo count($books['favorites']); ?></span>
                <span class="stat-label">В избранном</span>
            </div>
        </div>
        
        <div class="abs-tabs">
    <button class="tab-btn active" data-tab="listening">🎧 В процессе (<?php echo count($books['listening']); ?>)</button>
    <button class="tab-btn" data-tab="completed">✅ Прослушано (<?php echo count($books['completed']); ?>)</button>
    <button class="tab-btn" data-tab="favorites">⭐ Избранное (<?php echo count($books['favorites']); ?>)</button>
    <button class="tab-btn" data-tab="postponed">⏸ Отложено (<?php echo count($books['postponed']); ?>)</button>
    <button class="tab-btn" data-tab="abandoned">❌ Брошено (<?php echo count($books['abandoned']); ?>)</button>
</div>
        
        <div id="tab-listening" class="tab-content active">
            <?php abs_render_book_grid($books['listening'] ?? [], 'В процессе'); ?>
        </div>
        <div id="tab-completed" class="tab-content">
            <?php abs_render_book_grid($books['completed'] ?? [], 'Прослушано'); ?>
        </div>
        <div id="tab-favorites" class="tab-content">
            <?php abs_render_book_grid($books['favorites'] ?? [], 'Избранное'); ?>
        </div>
        <div id="tab-postponed" class="tab-content">
            <?php abs_render_book_grid($books['postponed'] ?? [], 'Отложено'); ?>
        </div>
        <div id="tab-abandoned" class="tab-content">
            <?php abs_render_book_grid($books['abandoned'] ?? [], 'Брошено'); ?>
        </div>
    </div>
    
    <style>        .abs-user-library-front { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .abs-stats-cards { display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap; }
        .stat-card { background: rgba(255,255,255,0.05); border-radius: 16px; padding: 20px; text-align: center; flex: 1; min-width: 120px; }
        .stat-value { font-size: 32px; font-weight: bold; color: #0dcaf0; display: block; }
        .stat-label { font-size: 14px; color: rgba(255,255,255,0.7); margin-top: 8px; }
        .abs-tabs { display: flex; gap: 10px; margin-bottom: 30px; flex-wrap: wrap; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; }
        .tab-btn { background: transparent; border: none; padding: 10px 20px; cursor: pointer; font-size: 16px; color: rgba(255,255,255,0.7); border-radius: 30px; transition: all 0.3s; }
        .tab-btn:hover { background: rgba(13,202,240,0.2); color: #0dcaf0; }
        .tab-btn.active { background: #0dcaf0; color: #1b2039; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .books-grid { display: flex; flex-direction: column; gap: 8px; margin-top: 20px; }
        .book-card { display: flex !important; flex-direction: row !important; flex-wrap: nowrap !important; align-items: center !important; justify-content: space-between; padding: 12px 16px; background: rgba(255,255,255,0.03); border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); gap: 12px; }
        .book-card:hover { background: rgba(13,202,240,0.08); transform: none; }
        .book-card img { display: none; }
                .book-card-title { flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 0.9rem; color: #fff; text-decoration: none; }
        .book-card-title:hover { color: #0dcaf0; }
        .book-actions { flex: 0 0 auto; }
        .book-actions { display: flex; gap: 6px; flex-shrink: 0; }
        .listen-btn { padding: 8px 20px; font-size: 0.85rem; border-radius: 25px; white-space: nowrap; background: linear-gradient(90deg,#0dcaf0,#5bc0de); color: #1b2039; font-weight: 600; text-decoration: none; display: inline-block; }
        .listen-btn:hover { transform: scale(1.02); box-shadow: 0 4px 12px rgba(13,202,240,0.3); color: #1b2039; }
        .remove-btn { width: 32px; height: 32px; border-radius: 50%; background: rgba(255,255,255,0.1); color: #fff; display: flex; align-items: center; justify-content: center; text-decoration: none; flex-shrink: 0; }
        .remove-btn:hover { background: #ff4444; }
        .progress-bar { height: 4px; background: rgba(255,255,255,0.2); border-radius: 2px; margin: 8px 0; overflow: hidden; }
        .progress-bar div { height: 100%; background: #0dcaf0; }
        .empty-message { text-align: center; padding: 40px; color: rgba(255,255,255,0.5); }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        $('.tab-btn').on('click', function() {
            $('.tab-btn').removeClass('active');
            $(this).addClass('active');
            $('.tab-content').removeClass('active');
            $('#tab-' + $(this).data('tab')).addClass('active');
        });
    });
    </script>
    <?php
}




// Функция для расчёта звания пользователя
function abs_get_user_rank($user_id) {
    global $wpdb;
    
    $total_seconds = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(total_progress_seconds) FROM {$wpdb->prefix}abs_progress WHERE user_id = %d", $user_id
    ));
    $total_hours = round($total_seconds / 3600, 1);
    
    $total_audio_books = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}abs_progress WHERE user_id = %d", $user_id
    ));
    $total_text_books = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}abs_reading_progress WHERE user_id = %d", $user_id
    ));
    $total_books = $total_audio_books + $total_text_books;
    
    $total_chapters = $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(chapter_number), 0) FROM {$wpdb->prefix}abs_reading_progress WHERE user_id = %d", $user_id
    ));
    
    $comments_count = get_comments(array('user_id' => $user_id, 'count' => true, 'status' => 'approve'));
    
    $ranks = array(
        // level => [name, icon, books, hours, chapters, comments]
        1 => ['Попаданец', '🌀', 1, 10, 30, 0],
        2 => ['Авантюрист', '⭐', 3, 50, 150, 1],
        3 => ['Мастер', '⚜️', 5, 200, 600, 3],
        4 => ['Герой', '🛡️', 10, 400, 1200, 8],
        5 => ['Драконий хранитель', '🐉', 30, 1000, 3000, 15],
        6 => ['Всевидящий', '👁️', 50, 2500, 7500, 30],
        7 => ['Бессмертный', '✨', 75, 5000, 15000, 50],
    );
    
    $level = 0;
    $name = 'Новичок';
    $icon = '📖';
    
    foreach ($ranks as $lvl => $r) {
        $books_ok = $total_books >= $r[2];
        $audio_ok = $total_hours >= $r[3];
        $text_ok = $total_chapters >= $r[4];
        $comments_ok = $comments_count >= $r[5];
        
        if ($books_ok && ($audio_ok || $text_ok) && $comments_ok) {
            $level = $lvl;
            $name = $r[0];
            $icon = $r[1];
        }
    }
    
    return array('name' => $name, 'icon' => $icon, 'level' => $level);
}

function abs_get_rank_progress($user_id) {
    global $wpdb;
    
    $total_seconds = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(total_progress_seconds) FROM {$wpdb->prefix}abs_progress WHERE user_id = %d", $user_id
    ));
    $total_hours = round($total_seconds / 3600, 1);
    
    $total_audio_books = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}abs_progress WHERE user_id = %d", $user_id
    ));
    $total_text_books = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}abs_reading_progress WHERE user_id = %d", $user_id
    ));
    $total_books = $total_audio_books + $total_text_books;
    
    $total_chapters = $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(chapter_number), 0) FROM {$wpdb->prefix}abs_reading_progress WHERE user_id = %d", $user_id
    ));
    
    $comments_count = get_comments(array('user_id' => $user_id, 'count' => true, 'status' => 'approve'));
    
    $ranks = array(
        1 => ['Попаданец', 1, 10, 30, 0],
        2 => ['Авантюрист', 3, 50, 150, 1],
        3 => ['Мастер', 5, 200, 600, 3],
        4 => ['Герой', 10, 400, 1200, 8],
        5 => ['Драконий хранитель', 30, 1000, 3000, 15],
        6 => ['Всевидящий', 50, 2500, 7500, 30],
        7 => ['Бессмертный', 75, 5000, 15000, 50],
    );
    
    $current = abs_get_user_rank($user_id);
    if ($current['level'] >= 7) return array('progress' => 100, 'next_name' => null);
    
    $next = $current['level'] + 1;
    $r = $ranks[$next];
    
    $books_pct = $r[1] > 0 ? min(100, ($total_books / $r[1]) * 100) : 100;
    $audio_pct = $r[2] > 0 ? min(100, ($total_hours / $r[2]) * 100) : 100;
    $text_pct = $r[3] > 0 ? min(100, ($total_chapters / $r[3]) * 100) : 100;
    $comments_pct = $r[4] > 0 ? min(100, ($comments_count / $r[4]) * 100) : 100;
    
    $progress = round(($books_pct + min($audio_pct, $text_pct) + $comments_pct) / 3);
    
    return array('progress' => $progress, 'next_name' => $r[0]);
}


// Шорткод для страницы достижений
add_shortcode('user_achievements', 'abs_achievements_page');
function abs_achievements_page() {
    if (!is_user_logged_in()) {
        return '<p>🔒 <a href="' . home_url('/login?redirect_to=' . urlencode(get_permalink())) . '">Войдите</a>, чтобы видеть свои достижения.</p>';
    }
    
    $user_id = get_current_user_id();
    $rank = abs_get_user_rank($user_id);
    $rank_progress = abs_get_rank_progress($user_id);
    
    global $wpdb;
    $total_seconds = $wpdb->get_var($wpdb->prepare("SELECT SUM(total_progress_seconds) FROM {$wpdb->prefix}abs_progress WHERE user_id = %d", $user_id));
    $total_hours = round($total_seconds / 3600, 1);
    $total_audio_books = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}abs_progress WHERE user_id = %d", $user_id));
    $total_text_books = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}abs_reading_progress WHERE user_id = %d", $user_id));
    $total_books = $total_audio_books + $total_text_books;
    $total_chapters = $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(chapter_number), 0) FROM {$wpdb->prefix}abs_reading_progress WHERE user_id = %d", $user_id));
    $comments_count = get_comments(array('user_id' => $user_id, 'count' => true, 'status' => 'approve'));
    
    $all_ranks = array(
        1 => ['🌀', 'Попаданец', 1, 10, 30, 0, '1+ книга / 10+ часов или 30+ глав'],
        2 => ['⭐', 'Авантюрист', 3, 50, 150, 1, '3+ книги / 50+ часов или 150+ глав / 1+ комментарий'],
        3 => ['⚜️', 'Мастер', 5, 200, 600, 3, '5+ книг / 200+ часов или 600+ глав / 3+ комментария'],
        4 => ['🛡️', 'Герой', 10, 400, 1200, 8, '10+ книг / 400+ часов или 1200+ глав / 8+ комментариев'],
        5 => ['🐉', 'Драконий хранитель', 30, 1000, 3000, 15, '30+ книг / 1000+ часов или 3000+ глав / 15+ комментариев'],
        6 => ['👁️', 'Всевидящий', 50, 2500, 7500, 30, '50+ книг / 2500+ часов или 7500+ глав / 30+ комментариев'],
        7 => ['✨', 'Бессмертный', 75, 5000, 15000, 50, '75+ книг / 5000+ часов или 15000+ глав / 50+ комментариев'],
    );
    
    ob_start();
    ?>
    <div class="abs-achievements">
        <div class="current-rank-card">
            <h2>🏆 Моё звание</h2>
            <div class="rank-display">
                <span class="rank-icon"><?php echo $rank['icon']; ?></span>
                <span class="rank-name"><?php echo $rank['name']; ?></span>
            </div>
            <div class="rank-progress">
                <div class="progress-bar"><div class="progress-fill" style="width: <?php echo $rank_progress['progress']; ?>%"></div></div>
                <?php if ($rank_progress['next_name']): ?>
                    <div class="progress-text">До звания "<?php echo $rank_progress['next_name']; ?>": <?php echo $rank_progress['progress']; ?>%</div>
                <?php else: ?>
                    <div class="progress-text">🏆 Вы достигли высшего звания!</div>
                <?php endif; ?>
            </div>
            <div class="rank-stats">
                <span>📚 Книг: <?php echo $total_books; ?></span>
                <span>🎧 Часов: <?php echo $total_hours; ?></span>
                <span>📖 Глав: <?php echo $total_chapters; ?></span>
                <span>💬 Комментариев: <?php echo $comments_count; ?></span>
            </div>
        </div>
        
        <div class="all-ranks">
            <h2>📜 Все звания</h2>
            <div class="ranks-grid">
                <div class="rank-card <?php echo $rank['level'] >= 0 ? 'achieved' : 'locked'; ?>">
                    <div class="rank-icon">📖</div>
                    <div class="rank-name">Новичок</div>
                    <div class="rank-desc">Начните слушать или читать книги</div>
                    <div class="rank-status"><?php echo $rank['level'] >= 0 ? '✅ Получено' : '🔒 Не получено'; ?></div>
                </div>
                <?php foreach ($all_ranks as $lvl => $r): 
                    $achieved = ($total_books >= $r[2] && ($total_hours >= $r[3] || $total_chapters >= $r[4]) && $comments_count >= $r[5]);
                ?>
                    <div class="rank-card <?php echo $achieved ? 'achieved' : 'locked'; ?>">
                        <div class="rank-icon"><?php echo $r[0]; ?></div>
                        <div class="rank-name"><?php echo $r[1]; ?></div>
                        <div class="rank-desc"><?php echo $r[6]; ?></div>
                        <div class="rank-status"><?php echo $achieved ? '✅ Получено' : '🔒 Не получено'; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <style>
        .abs-achievements { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .current-rank-card { background: rgba(13,202,240,0.1); border-radius: 24px; padding: 30px; text-align: center; margin-bottom: 40px; }
        .rank-display { display: flex; align-items: center; justify-content: center; gap: 20px; margin-bottom: 20px; }
        .rank-display .rank-icon { font-size: 48px; }
        .rank-display .rank-name { font-size: 28px; font-weight: bold; color: #0dcaf0; }
        .rank-stats { display: flex; justify-content: center; gap: 15px; flex-wrap: wrap; margin-top: 15px; }
        .rank-stats span { background: rgba(255,255,255,0.1); padding: 5px 15px; border-radius: 20px; }
        .progress-bar { height: 10px; background: rgba(255,255,255,0.1); border-radius: 5px; overflow: hidden; margin: 15px 0; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #0dcaf0, #5bc0de); }
        .progress-text { font-size: 14px; color: rgba(255,255,255,0.7); }
        .all-ranks h2 { text-align: center; margin-bottom: 30px; color: #0dcaf0; }
        .ranks-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px; }
        .rank-card { background: rgba(255,255,255,0.05); border-radius: 20px; padding: 20px; text-align: center; }
        .rank-card.achieved { background: rgba(13,202,240,0.15); border: 1px solid #0dcaf0; }
        .rank-card.locked { opacity: 0.6; }
        .rank-card .rank-icon { font-size: 40px; }
        .rank-card .rank-name { font-size: 18px; font-weight: bold; margin: 10px 0; }
        .rank-card .rank-desc { font-size: 11px; color: rgba(255,255,255,0.6); margin-bottom: 10px; }
        .rank-card .rank-status { font-size: 12px; font-weight: bold; }
        .rank-card.achieved .rank-status { color: #0dcaf0; }
        @media (max-width: 600px) { .ranks-grid { grid-template-columns: 1fr; } }
    </style>
    <?php
    return ob_get_clean();
}



function abs_decline($number, $forms) {
    $number = abs($number);
    if ($number % 10 == 1 && $number % 100 != 11) {
        return $forms[0];
    } elseif ($number % 10 >= 2 && $number % 10 <= 4 && ($number % 100 < 10 || $number % 100 >= 20)) {
        return $forms[1];
    } else {
        return $forms[2];
    }
}



// Шорткод для мини-статистики на главной
add_shortcode('user_stats', 'abs_user_stats_shortcode');
function abs_user_stats_shortcode() {
    if (!is_user_logged_in()) return '';
    
    $user_id = get_current_user_id();
    $rank = abs_get_user_rank($user_id);
    $rank_progress = abs_get_rank_progress($user_id);
    
    global $wpdb;
    $total_seconds = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(total_progress_seconds) FROM {$wpdb->prefix}abs_progress WHERE user_id = %d", $user_id
    ));
    $total_hours = round($total_seconds / 3600, 1);
    $total_books = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}abs_progress WHERE user_id = %d", $user_id
    ));
    $total_reading = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}abs_reading_progress WHERE user_id = %d", $user_id
    ));
    $total_chapters = $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(chapter_number), 0) FROM {$wpdb->prefix}abs_reading_progress WHERE user_id = %d", $user_id
    ));
    $total_books_all = $total_books + $total_reading;
    $comments_count = get_comments(array('user_id' => $user_id, 'count' => true, 'status' => 'approve'));
    
    $books_word = abs_decline($total_books_all, array('книга', 'книги', 'книг'));
    $hours_word = abs_decline($total_hours, array('час', 'часа', 'часов'));
    $chapters_word = abs_decline($total_chapters, array('глава', 'главы', 'глав'));
    
    ob_start();
    ?>
    <div class="user-stats-mini">
        <div class="stats-header">
            <span class="rank-icon"><?php echo $rank['icon']; ?></span>
            <span class="rank-name"><?php echo $rank['name']; ?></span>
        </div>
        <div class="stats-details">
            <div class="stat-item"><span class="stat-value"><?php echo $total_books_all; ?></span><span class="stat-label"><?php echo $books_word; ?></span></div>
            <div class="stat-item"><span class="stat-value"><?php echo $total_hours; ?></span><span class="stat-label"><?php echo $hours_word; ?></span></div>
            <div class="stat-item"><span class="stat-value"><?php echo $total_chapters; ?></span><span class="stat-label"><?php echo $chapters_word; ?></span></div>
        </div>
        <div class="progress-section">
            <div class="progress-bar"><div class="progress-fill" style="width: <?php echo $rank_progress['progress']; ?>%"></div></div>
            <?php if (!empty($rank_progress['next_name'])): ?>
                <div class="progress-text">До "<?php echo $rank_progress['next_name']; ?>": <?php echo $rank_progress['progress']; ?>%</div>
            <?php else: ?>
                <div class="progress-text">🏆 Высшее звание!</div>
            <?php endif; ?>
        </div>
        <div class="stats-button">
            <a href="/moi-dostizheniya" class="achievements-link">🏆 Мои достижения</a>
        </div>
    </div>
    <style>
        .user-stats-mini { background: rgba(255,255,255,0.05); border-radius: 20px; padding: 20px; margin-bottom: 25px; }
        .stats-header { text-align: center; margin-bottom: 15px; }
        .rank-icon { font-size: 40px; display: block; }
        .rank-name { font-size: 24px; font-weight: bold; color: #0dcaf0; display: block; margin-top: 5px; }
        .stats-details { display: flex; justify-content: space-around; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
        .stat-item { text-align: center; flex: 1; min-width: 80px; }
        .stat-value { font-size: 28px; font-weight: bold; color: #fff; display: block; }
        .stat-label { font-size: 12px; color: rgba(255,255,255,0.6); }
        .progress-section { margin-bottom: 20px; }
        .progress-bar { height: 8px; background: rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden; margin-bottom: 8px; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #0dcaf0, #5bc0de); }
        .progress-text { font-size: 13px; color: rgba(255,255,255,0.7); text-align: center; }
        .stats-button { text-align: center; }
        .achievements-link { display: inline-block; padding: 10px 25px; background: rgba(13,202,240,0.2); border-radius: 40px; color: #0dcaf0; text-decoration: none; font-weight: 600; }
        .achievements-link:hover { background: #0dcaf0; color: #1b2039; }
    </style>
    <?php
    return ob_get_clean();
}

// Шорткод для истории просмотров
add_shortcode('user_history', 'abs_user_history_shortcode');
function abs_user_history_shortcode() {
    if (!is_user_logged_in()) {
        return do_shortcode('[abs_login]');
    }
    
    $user_id = get_current_user_id();
    global $wpdb;
    
    $history = $wpdb->get_results($wpdb->prepare(
        "SELECT v.book_id, v.viewed_at, c.book_data 
         FROM {$wpdb->prefix}abs_book_views v
         LEFT JOIN {$wpdb->prefix}abs_book_cache c ON v.book_id = c.book_id 
         WHERE v.user_id = %d 
         ORDER BY v.viewed_at DESC 
         LIMIT 10",
        $user_id
    ));
    
    if (!$history) {
        return '<p>📚 История просмотров пуста</p>';
    }
    
    ob_start();
    ?>
    <div class="user-history">
        <h3>📜 История просмотров</h3>
        <div class="history-list">
            <?php foreach ($history as $item):
                $data = json_decode($item->book_data, true);
                $title = $data['media']['metadata']['title'] ?? 'Без названия';
                $page_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'abs_book_id' AND meta_value = %s",
                    $item->book_id
                ));
                $url = $page_id ? get_permalink($page_id) : '#';
                $date = date('d.m.Y H:i', strtotime($item->viewed_at));
            ?>
                <div class="history-item">
                    <a href="<?php echo esc_url($url); ?>"><?php echo esc_html($title); ?></a>
                    <span><?php echo $date; ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <style>
        .user-history { background: rgba(255,255,255,0.05); border-radius: 16px; padding: 20px; margin-top: 20px; }
        .history-list { max-height: 300px; overflow-y: auto; }
        .history-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: rgba(255,255,255,0.03); border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); margin-bottom: 8px; }
        .history-item a { flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-right: 12px; }
        .history-item span { flex-shrink: 0; }
        .history-item a { color: #e0e0e0; text-decoration: none; }
        .history-item a:hover { color: #0dcaf0; }
        .history-item span { font-size: 12px; color: rgba(255,255,255,0.5); }
    </style>
    <?php
    return ob_get_clean();
}


// Запись просмотра книги
function abs_track_book_view($book_id) {
    if (empty($book_id)) return;
    
    $user_id = get_current_user_id();
    if (!$user_id) return;
    
    $user_ip = $_SERVER['REMOTE_ADDR'];
    
    global $wpdb;
    $table = $wpdb->prefix . 'abs_book_views';
    
    $recent = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table WHERE book_id = %s AND user_id = %d AND viewed_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)",
        $book_id, $user_id
    ));
    
    if (!$recent) {
        $wpdb->insert($table, array(
            'user_id' => $user_id,
            'book_id' => $book_id,
            'user_ip' => $user_ip,
            'viewed_at' => current_time('mysql')
        ));
    }
}
// Запись просмотра текстовой книги
function abs_track_ranobe_view($ranobe_id) {
    if (empty($ranobe_id)) return;
    
    $user_id = get_current_user_id();
    if (!$user_id) return;
    
    $user_ip = $_SERVER['REMOTE_ADDR'];
    
    global $wpdb;
    $table = $wpdb->prefix . 'abs_book_views';
    
    $recent = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table WHERE ranobe_id = %d AND user_id = %d AND viewed_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)",
        $ranobe_id, $user_id
    ));
    
    if (!$recent) {
        $wpdb->insert($table, array(
            'user_id' => $user_id,
            'book_id' => $ranobe_id,
            'ranobe_id' => $ranobe_id,
            'type' => 'text',
            'user_ip' => $user_ip,
            'viewed_at' => current_time('mysql')
        ));
    }
}
// Шорткод для редактирования профиля
add_shortcode('abs_profile_edit', 'abs_profile_edit_shortcode');
function abs_profile_edit_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>🔒 <a href="' . home_url('/login?redirect_to=' . urlencode(get_permalink())) . '">Войдите</a>, чтобы редактировать профиль.</p>';
    }
    
    $user_id = get_current_user_id();
    $current_user = wp_get_current_user();
    $avatar_url = get_user_meta($user_id, 'abs_avatar_url', true);
    if (!$avatar_url) {
        $avatar_url = get_avatar_url($user_id);
    }
    
    ob_start();
    ?>
    <div class="abs-profile-edit">
        <div class="profile-header">
            <?php if ($avatar_url): ?>
                <img src="<?php echo esc_url($avatar_url); ?>" class="profile-avatar">
            <?php else: ?>
                <?php echo get_avatar($user_id, 100); ?>
            <?php endif; ?>
            <h2><?php echo esc_html($current_user->display_name); ?></h2>
        </div>
        
        <form id="abs-profile-form">
            <div class="form-group">
                <label>👤 Имя пользователя</label>
                <input type="text" name="display_name" value="<?php echo esc_attr($current_user->display_name); ?>" required>
            </div>
            <div class="form-group">
                <label>📧 Email</label>
                <input type="email" name="email" value="<?php echo esc_attr($current_user->user_email); ?>" required>
            </div>
            <div class="form-group">
                <label>🔒 Новый пароль (оставьте пустым, если не хотите менять)</label>
                <input type="password" name="password" autocomplete="off">
            </div>
            <div class="form-group">
                <label>🖼️ Аватар</label>
                <div class="avatar-upload-row">
                    <?php if ($avatar_url): ?>
                        <img src="<?php echo esc_url($avatar_url); ?>" class="avatar-preview" id="avatar-preview">
                    <?php else: ?>
                        <div class="avatar-preview avatar-placeholder" id="avatar-preview">👤</div>
                    <?php endif; ?>
                    <label for="avatar-file-input" class="avatar-upload-btn">Изменить аватар 📎</label>
                    <input type="file" id="avatar-file-input" name="avatar_file" accept="image/*" style="display: none;">
                </div>
            </div>
            <button type="submit" class="profile-save-btn">💾 Сохранить изменения</button>
            <div id="profile-edit-message"></div>
        </form>
    </div>
    
    <style>
        .abs-profile-edit { background: rgba(255,255,255,0.05); border-radius: 20px; padding: 25px; max-width: 500px; margin: 0 auto; }
        .profile-header { text-align: center; margin-bottom: 25px; }
        .profile-avatar { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin-bottom: 10px; }
        .profile-header h2 { color: #0dcaf0; margin: 0; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 8px; color: rgba(255,255,255,0.8); font-size: 14px; }
        .form-group input { width: 100%; padding: 12px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 30px; color: #fff; box-sizing: border-box; }
                .avatar-upload-row { display: flex; align-items: center; gap: 15px; }
        .avatar-preview { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; }
        .avatar-placeholder { background: rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; font-size: 32px; }
        .avatar-upload-btn { padding: 8px 16px; border-radius: 30px; background: rgba(255,255,255,0.15); color: #fff; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; font-size: 13px; transition: all 0.2s; white-space: nowrap; }
        .avatar-upload-btn:hover { background: #0dcaf0; }
        .profile-save-btn { width: 100%; background: linear-gradient(90deg,#0dcaf0,#5bc0de); border: none; border-radius: 30px; padding: 12px; color: #1b2039; font-weight: 600; cursor: pointer; font-size: 16px; }
        .profile-save-btn:hover { transform: scale(1.02); }
        #profile-edit-message { margin-top: 15px; text-align: center; }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // Превью аватарки при выборе файла
        $('#avatar-file-input').on('change', function() {
            var file = this.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#avatar-preview').attr('src', e.target.result).show();
                };
                reader.readAsDataURL(file);
            }
        });
        
        $('#abs-profile-form').on('submit', function(e) {
            e.preventDefault();
            var btn = $(this).find('button');
            btn.text('Сохранение...').prop('disabled', true);
            
            var formData = new FormData();
            formData.append('action', 'abs_update_profile');
            formData.append('display_name', $('input[name="display_name"]').val());
            formData.append('email', $('input[name="email"]').val());
            formData.append('password', $('input[name="password"]').val());
            
            var fileInput = $('input[name="avatar_file"]')[0];
            if (fileInput && fileInput.files[0]) {
                formData.append('avatar_file', fileInput.files[0]);
            }
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(r) {
                    if (r.success) {
                        $('#profile-edit-message').html('<p style="color:#0dcaf0;">✅ Профиль обновлён! Страница обновится...</p>');
                        setTimeout(function() { location.reload(); }, 1500);
                    } else {
                        $('#profile-edit-message').html('<p style="color:#ff5555;">❌ ' + r.data + '</p>');
                        btn.text('💾 Сохранить изменения').prop('disabled', false);
                    }
                },
                error: function() {
                    $('#profile-edit-message').html('<p style="color:#ff5555;">❌ Ошибка сервера</p>');
                    btn.text('💾 Сохранить изменения').prop('disabled', false);
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}


// Шорткод карточки профиля для личного кабинета
add_shortcode('abs_profile_card', 'abs_profile_card_shortcode');
function abs_profile_card_shortcode() {
    if (!is_user_logged_in()) {
        return '';
    }
    
    $user_id = get_current_user_id();
    $current_user = wp_get_current_user();
    $avatar_url = get_user_meta($user_id, 'abs_avatar_url', true);
    if (!$avatar_url) {
        $avatar_url = get_avatar_url($user_id);
    }
    $rank = abs_get_user_rank($user_id);
    
    ob_start();
    ?>
    <div class="abs-profile-card">
        <div class="profile-card-avatar">
            <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($current_user->display_name); ?>">
        </div>
        <div class="profile-card-info">
            <div class="profile-card-name"><?php echo esc_html($current_user->display_name); ?></div>
            <div class="profile-card-rank"><span class="rank-icon"><?php echo $rank['icon']; ?></span> <?php echo $rank['name']; ?></div>
        </div>
        <a href="/profile_edit" class="profile-card-edit-btn">✏️ Редактировать</a>
    </div>
    
    <style>
        .abs-profile-card {
            display: flex;
            align-items: center;
            gap: 16px;
            background: rgba(255,255,255,0.05);
            border-radius: 16px;
            padding: 16px 20px;
            margin-bottom: 20px;
        }
        .profile-card-avatar img {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            object-fit: cover;
        }
        .profile-card-info {
            flex: 1;
        }
        .profile-card-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #fff;
        }
        .profile-card-rank {
            font-size: 0.85rem;
            color: #0dcaf0;
            margin-top: 4px;
        }
        .profile-card-rank .rank-icon {
            font-size: 1rem;
        }
        .profile-card-edit-btn {
            background: rgba(13,202,240,0.2);
            color: #0dcaf0;
            padding: 8px 16px;
            border-radius: 30px;
            text-decoration: none;
            font-size: 0.9rem;
            white-space: nowrap;
        }
        .profile-card-edit-btn:hover {
            background: #0dcaf0;
            color: #1b2039;
        }
        @media (max-width: 480px) {
            .abs-profile-card {
                flex-wrap: wrap;
                text-align: center;
                justify-content: center;
            }
            .profile-card-info {
                flex: 100%;
            }
        }
    </style>
    <?php
    return ob_get_clean();
}

// AJAX обработчик
add_action('wp_ajax_abs_update_profile', 'abs_ajax_update_profile');

function abs_ajax_update_profile() {
    $user_id = get_current_user_id();
    if (!$user_id) wp_send_json_error('Не авторизован');
    
    $display_name = sanitize_text_field($_POST['display_name']);
    $email = sanitize_email($_POST['email']);
    $password = $_POST['password'];
    
    $update_data = array('ID' => $user_id, 'display_name' => $display_name, 'user_email' => $email);
    if (!empty($password)) $update_data['user_pass'] = $password;
    
    $result = wp_update_user($update_data);
    if (is_wp_error($result)) wp_send_json_error($result->get_error_message());
    
    // Загрузка аватарки
    if (!empty($_FILES['avatar_file'])) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $attachment_id = media_handle_upload('avatar_file', 0);
        if (!is_wp_error($attachment_id)) {
            $avatar_url = wp_get_attachment_url($attachment_id);
            update_user_meta($user_id, 'abs_avatar_url', $avatar_url);
        }
    }
    
    wp_send_json_success();
}

// Редирект после входа/регистрации на главную
add_action('wp_footer', 'abs_tml_redirect_script');
function abs_tml_redirect_script() {
    if (is_page('login') || is_page('log-in') || is_page('register') || is_page('registration')) {
        if (is_user_logged_in()) {
            echo '<script>window.location.href = "' . home_url() . '";</script>';
        }
    }
}
// Серверный редирект после входа (для всех кроме админов)
add_filter('login_redirect', 'abs_login_redirect', 10, 3);
function abs_login_redirect($redirect_to, $request, $user) {
    if (isset($user->roles) && !in_array('administrator', $user->roles)) {
        return home_url();
    }
    return $redirect_to;
}

// Редирект после регистрации
add_filter('registration_redirect', 'abs_registration_redirect');
function abs_registration_redirect($redirect_to) {
    return home_url();
}

// Шорткод облака жанров
add_shortcode('abs_genres', 'abs_genres_shortcode');
function abs_genres_shortcode() {
    global $wpdb;
    $cache_table = $wpdb->prefix . 'abs_book_cache';
    $books = $wpdb->get_col("SELECT book_data FROM $cache_table");
    
    $genres = array();
    foreach ($books as $book_data) {
        $data = json_decode($book_data, true);
        $book_genres = $data['media']['metadata']['genres'] ?? array();
        foreach ($book_genres as $genre) {
            if (!empty($genre)) {
                $genre = trim($genre);
                if (!isset($genres[$genre])) {
                    $genres[$genre] = 0;
                }
                $genres[$genre]++;
            }
        }
    }
    
    if (empty($genres)) {
        return '<p>📚 Жанры не найдены</p>';
    }
    
    // Перемешиваем
    $keys = array_keys($genres);
    shuffle($keys);
    $shuffled = array();
    foreach ($keys as $key) {
        $shuffled[$key] = $genres[$key];
    }
    
    $max = max($genres);
    $min = min($genres);
    
    ob_start();
    ?>
    <div class="abs-genres-cloud">
        <?php foreach ($shuffled as $genre => $count): 
            // Размер от 0.7rem до 2.5rem в зависимости от количества
            if ($max == $min) {
                $size = 1.2;
            } else {
                $ratio = ($count - $min) / ($max - $min);
                $size = 0.8 + $ratio * 1.6; // от 0.8 до 2.4 rem
            }
            $opacity = 0.5 + ($size - 0.8) / 1.6 * 0.5; // от 0.5 до 1
        ?>
            <a href="/catalog?genre=<?php echo urlencode($genre); ?>" class="genre-tag" style="font-size: <?php echo round($size, 2); ?>rem; opacity: <?php echo round($opacity, 2); ?>;">
                <?php echo esc_html($genre); ?>
            </a>
        <?php endforeach; ?>
    </div>
    
    <style>
        .abs-genres-cloud {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 12px 20px;
            padding: 30px;
            line-height: 1.4;
        }
        .genre-tag {
            display: inline-block;
            padding: 4px 14px;
            border: 1px solid;
            border-radius: 30px;
            text-decoration: none;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .genre-tag:hover {
            transform: scale(1.1);
            color: #fff !important;
            background: #0dcaf0;
        }
    </style>
    <?php
    return ob_get_clean();
}

// Полностью скрываем копирайт-полосу футера
add_action('wp_footer', function() {
    
}, 99);




// Нижнее меню для мобильных + стили
add_action('wp_head', function() {
    echo '<style>
    .mobile-bottom-nav { display: none; position: fixed; bottom: 0; left: 0; width: 100%; background: #1a1a2e; border-top: 1px solid rgba(255,255,255,0.1); z-index: 9999; justify-content: space-around; align-items: center; padding: 6px 0 10px 0; }
    .mobile-nav-item { display: flex; flex-direction: column; align-items: center; text-decoration: none; color: rgba(255,255,255,0.5); font-size: 10px; gap: 4px; padding: 4px 8px; }
    .mobile-nav-item:hover { color: #0dcaf0; }
    .mobile-nav-icon { font-size: 20px; }
    @media (max-width: 768px) {
        .mobile-bottom-nav { display: flex !important; }
        body { padding-bottom: 70px; }
        .main-navigation, #sidebar, .left-menu, .toggle-nav, #mySidenav { display: none !important; }
        .main-header { padding: 6px 10px !important; }
        .logo h1, p.site-title { font-size: 22px !important; margin: 0 !important; }
        p.site-description { font-size: 11px !important; margin: 0 !important; }
               .menu-search { display: flex !important; justify-content: center !important; }
        .menu-search form { margin: 0 auto !important; }
        .menu-search input[type="search"] { flex: 1 !important; min-width: 0 !important; padding: 10px 14px !important; font-size: 14px !important; border-radius: 25px 0 0 25px !important; }
        .menu-search input[type="submit"] { width: 42px !important; height: 42px !important; padding: 0 !important; border-radius: 0 25px 25px 0 !important; flex-shrink: 0 !important; }
        .middle-align { padding: 1em 0 !important; }
        .audiobookshelf-player-container { padding: 12px !important; margin: 10px 0 !important; }
        .book-header { flex-direction: column !important; gap: 12px !important; }
        .book-cover { width: 100px !important; height: 140px !important; margin: 0 auto !important; }
        .book-title { font-size: 1.2rem !important; text-align: center !important; word-break: break-word !important; }
        .book-author { text-align: center !important; font-size: 0.9rem !important; word-break: break-word !important; }
        .book-description { font-size: 0.8rem !important; max-height: 80px !important; overflow-y: auto !important; word-break: break-word !important; max-width: 100% !important; }
        .book-info { max-width: 100% !important; overflow: hidden !important; }
        .player-container { gap: 8px !important; margin: 15px 0 !important; }
        .play-pause-btn-big { width: 50px !important; height: 50px !important; font-size: 1.2rem !important; }
        .play-prev-btn, .play-next-btn { width: 36px !important; height: 36px !important; }
        #abs-track-list { max-height: 200px !important; }
        #abs-track-list li { padding: 10px !important; font-size: 0.85rem !important; }
        .book-tags { gap: 6px !important; }
        .book-tag { font-size: 0.7rem !important; padding: 4px 10px !important; }
        .book-meta { gap: 8px !important; }
        .book-meta-item { font-size: 0.75rem !important; padding: 4px 10px !important; }
        .title-row { flex-wrap: nowrap !important; gap: 8px !important; }
        .play-pause-btn-small { width: 40px !important; height: 40px !important; font-size: 1rem !important; }
        .books-grid { gap: 10px !important; padding: 0 8px !important; }
        .book-card { padding: 14px !important; flex-direction: row !important; gap: 12px !important; align-items: center !important; flex-wrap: wrap !important; }
        .book-card .book-title-link { font-size: 0.95rem !important; flex: 1 1 100% !important; }
        .book-card .play-btn { padding: 10px 18px !important; font-size: 0.85rem !important; min-width: 100px !important; border-radius: 25px !important; }
        .continue-listening h2, .favorites-list h2, .new-releases-section h2, .popular-section h2 { font-size: 1.2rem !important; padding: 0 8px !important; margin-bottom: 12px !important; }
                .continue-btn { display: inline-block !important; text-decoration: none !important; cursor: pointer !important; z-index: 99 !important; position: relative !important; pointer-events: auto !important; }
                        .continue-btn { display: inline-block !important; text-decoration: none !important; cursor: pointer !important; z-index: 999 !important; position: relative !important; pointer-events: auto !important; }
        .continue-btn * { pointer-events: none !important; }
        .catalog-book-cover { width: 120px !important; height: 170px !important; }
        .catalog-book-cover img { width: 100% !important; height: 100% !important; object-fit: cover !important; }
        .catalog-book-title { font-size: 1rem !important; }
        .catalog-book-author { font-size: 0.85rem !important; }
        .catalog-book-description { font-size: 0.8rem !important; display: block !important; }
        .catalog-listen-btn { padding: 10px 18px !important; font-size: 0.85rem !important; }
        .custom-pagination { gap: 6px !important; }
        .custom-pagination a, .custom-pagination span { padding: 8px 14px !important; font-size: 0.9rem !important; }
        .abs-stats-cards { gap: 8px !important; }
        .stat-card { padding: 12px !important; min-width: 70px !important; }
        .stat-value { font-size: 22px !important; }
        .stat-label { font-size: 10px !important; }
        .abs-tabs { gap: 4px !important; flex-wrap: nowrap !important; overflow-x: auto !important; }
        .tab-btn { padding: 8px 12px !important; font-size: 12px !important; white-space: nowrap !important; }
        .book-card button, .listen-btn, .continue-btn { padding: 6px 10px !important; font-size: 0.7rem !important; white-space: nowrap !important; min-width: auto !important; width: auto !important; }
        .book-actions { flex-direction: row !important; gap: 6px !important; }
        .book-card h4 { font-size: 0.75rem !important; }
        .history-item { font-size: 0.8rem !important; padding: 8px 0 !important; }
        .abs-profile-card { padding: 12px !important; gap: 10px !important; }
        .profile-card-avatar img { width: 40px !important; height: 40px !important; }
        .profile-card-name { font-size: 0.9rem !important; }
        .profile-card-rank { font-size: 0.75rem !important; }
        .profile-card-edit-btn { font-size: 0.75rem !important; padding: 6px 12px !important; }
    }
    </style>';
}, 99);

add_action('wp_footer', 'abs_mobile_bottom_menu');
function abs_mobile_bottom_menu() {
    ?>
    <nav class="mobile-bottom-nav">
        <a href="/" class="mobile-nav-item"><span class="mobile-nav-icon">🏠</span><span class="mobile-nav-label">Главная</span></a>
        <a href="#" class="mobile-nav-item" id="mobile-menu-toggle"><span class="mobile-nav-icon">📚</span><span class="mobile-nav-label">Меню</span></a>
        <a href="#" class="mobile-nav-item" id="mobile-search-toggle"><span class="mobile-nav-icon">🔍</span><span class="mobile-nav-label">Поиск</span></a>
        <a href="/catalog" class="mobile-nav-item"><span class="mobile-nav-icon">📖</span><span class="mobile-nav-label">Книги</span></a>
        <a href="/lk" class="mobile-nav-item"><span class="mobile-nav-icon">👤</span><span class="mobile-nav-label">Профиль</span></a>
    </nav>
    <div id="mobile-menu-panel" style="display:none; position:fixed; bottom:60px; left:0; width:100%; background:#1a1a2e; z-index:9998; border-radius:16px 16px 0 0; padding:20px; max-height:60vh; overflow-y:auto;">
        <?php wp_nav_menu(array('theme_location' => 'primary', 'menu_class' => 'mobile-menu-list', 'container' => false, 'fallback_cb' => false)); ?>
    </div>
    <div id="mobile-search-panel" style="display:none; position:fixed; bottom:60px; left:0; width:100%; background:#1a1a2e; z-index:9998; border-radius:16px 16px 0 0; padding:20px;">
        <form role="search" method="get" action="/catalog" style="display:flex;gap:8px;">
            <input type="text" name="search" placeholder="Поиск книг..." style="flex:1;padding:12px 16px;border-radius:30px;border:none;background:rgba(255,255,255,0.1);color:#fff;font-size:16px;">
            <button type="submit" style="width:46px;height:46px;border-radius:50%;border:none;background:#0dcaf0;color:#1b2039;font-size:18px;cursor:pointer;flex-shrink:0;">🔍</button>
        </form>
    </div>
    <style>.mobile-menu-list { list-style:none; padding:0; margin:0; } .mobile-menu-list li { margin-bottom:8px; } .mobile-menu-list a { color:#fff; text-decoration:none; font-size:16px; display:block; padding:8px 0; } .mobile-menu-list a:hover { color:#0dcaf0; }</style>
    <script>
    document.getElementById('mobile-menu-toggle').addEventListener('click', function(e) {
        e.preventDefault();
        var p = document.getElementById('mobile-menu-panel');
        document.getElementById('mobile-search-panel').style.display = 'none';
        p.style.display = p.style.display === 'none' ? 'block' : 'none';
    });
    document.getElementById('mobile-search-toggle').addEventListener('click', function(e) {
        e.preventDefault();
        var p = document.getElementById('mobile-search-panel');
        document.getElementById('mobile-menu-panel').style.display = 'none';
        p.style.display = p.style.display === 'none' ? 'block' : 'none';
    });
   
    </script>
    <?php
}

// ========== ШОРТКОДЫ АВТОРИЗАЦИИ ==========

// Шорткод формы входа
add_shortcode('abs_login', 'abs_login_shortcode');
function abs_login_shortcode() {
    if (is_user_logged_in()) {
        return '<p>✅ Вы уже вошли. <a href="' . home_url() . '">На главную</a> | <a href="' . wp_logout_url(home_url()) . '">Выйти</a></p>';
    }
    $redirect = isset($_GET['redirect_to']) ? $_GET['redirect_to'] : home_url();
    ob_start();
    ?>
    <div class="abs-login-form-wrapper">
        <div class="abs-auth-form">
            <h2>🔐 Войти</h2>
            <?php if (isset($_GET['login']) && $_GET['login'] === 'failed'): ?>
                <div class="abs-auth-error">❌ Неверный логин или пароль</div>
            <?php endif; ?>
            <form method="post" action="<?php echo site_url('wp-login.php', 'login_post'); ?>">
                <input type="hidden" name="redirect_to" value="<?php echo esc_url($redirect); ?>">
                <div class="form-group">
                    <label>👤 Имя пользователя или Email</label>
                    <input type="text" name="log" required>
                </div>
                <div class="form-group">
                    <label>🔒 Пароль</label>
                    <input type="password" name="pwd" required>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" name="rememberme" id="rememberme" value="forever">
                    <label for="rememberme">Запомнить меня</label>
                </div>
                <button type="submit" class="auth-submit-btn">Войти</button>
                <div class="auth-links">
                    <a href="/register">📝 Регистрация</a>
                    <a href="/lostpassword">🔑 Забыли пароль?</a>
                </div>
            </form>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Шорткод формы регистрации
add_shortcode('abs_register', 'abs_register_shortcode');
function abs_register_shortcode() {
    if (is_user_logged_in()) {
        return '<p>✅ Вы уже зарегистрированы и вошли. <a href="' . home_url() . '">На главную</a></p>';
    }
    if (!get_option('users_can_register')) {
        return '<p>📝 Регистрация временно закрыта.</p>';
    }
    ob_start();
    ?>
    <div class="abs-auth-form">
        <h2>📝 Регистрация</h2>
        <?php if (isset($_GET['registration']) && $_GET['registration'] === 'empty'): ?>
            <div class="abs-auth-error">❌ Заполните все поля</div>
        <?php endif; ?>
        <?php if (isset($_GET['registration']) && $_GET['registration'] === 'email_exists'): ?>
            <div class="abs-auth-error">❌ Этот email уже используется</div>
        <?php endif; ?>
        <?php if (isset($_GET['registration']) && $_GET['registration'] === 'username_exists'): ?>
            <div class="abs-auth-error">❌ Это имя пользователя уже занято</div>
        <?php endif; ?>
                        <form method="post">
            <input type="hidden" name="action" value="abs_register_user">
            <div class="form-group">
                <label>👤 Имя пользователя</label>
                <input type="text" name="user_login" required>
            </div>
            <div class="form-group">
                <label>📧 Email</label>
                <input type="email" name="user_email" required>
            </div>
            <div class="form-group">
                <label>🔒 Пароль (минимум 6 символов)</label>
                <input type="password" name="user_pass" required minlength="6">
            </div>
            <button type="submit" class="auth-submit-btn">Зарегистрироваться</button>
            <div class="auth-links">
                <a href="/login">🔐 Уже есть аккаунт? Войти</a>
            </div>
        </form>
        <div id="register-message"></div>
        </div>
    <script>
    jQuery(document).ready(function($) {
        $('.abs-auth-form form').on('submit', function(e) {
            e.preventDefault();
            var btn = $(this).find('button');
            btn.text('Регистрация...').prop('disabled', true);
            $.post('/wp-admin/admin-ajax.php', {
                action: 'abs_register_user',
                user_login: $('input[name="user_login"]').val(),
                user_email: $('input[name="user_email"]').val(),
                user_pass: $('input[name="user_pass"]').val()
            }, function(r) {
                if (r.success) {
                    $('.abs-auth-form').html('<div style="text-align:center;padding:40px 20px;"><div style="font-size:60px;margin-bottom:20px;">🎉</div><h2 style="color:#0dcaf0;margin-bottom:15px;">Регистрация успешна!</h2><p style="color:#fff;font-size:16px;margin-bottom:25px;">Добро пожаловать, ' + r.data.name + '!</p><a href="/" style="display:inline-block;padding:12px 30px;background:linear-gradient(90deg,#0dcaf0,#5bc0de);border-radius:40px;color:#1b2039;font-weight:700;text-decoration:none;font-size:16px;">🏠 На главную</a></div>');
                } else {
                    $('#register-message').html('<div class="abs-auth-error">❌ ' + r.data + '</div>');
                    btn.text('Зарегистрироваться').prop('disabled', false);
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

// Шорткод восстановления пароля
add_shortcode('abs_lostpassword', 'abs_lostpassword_shortcode');
function abs_lostpassword_shortcode() {
    if (is_user_logged_in()) {
        return '<p>✅ Вы уже вошли. <a href="' . home_url() . '">На главную</a></p>';
    }
    ob_start();
    ?>
    <div class="abs-auth-form">
        <h2>🔑 Восстановление пароля</h2>
        <?php if (isset($_GET['checkemail']) && $_GET['checkemail'] === 'confirm'): ?>
            <div class="abs-auth-success">✅ Проверьте email для сброса пароля</div>
        <?php endif; ?>
        <?php if (isset($_GET['login']) && $_GET['login'] === 'invalidkey'): ?>
            <div class="abs-auth-error">❌ Неверная ссылка для сброса</div>
        <?php endif; ?>
        <form method="post" id="lostpassword-form">
            <input type="hidden" name="action" value="abs_lostpassword">
            <div class="form-group">
                <label>📧 Email или имя пользователя</label>
                <input type="text" name="user_login" required>
            </div>
            <button type="submit" class="auth-submit-btn">Сбросить пароль</button>
            <div class="auth-links">
                <a href="/login">🔐 Вернуться ко входу</a>
            </div>
        </form>
        <div id="lostpassword-message"></div>
    </div>
    <script>
    jQuery(document).ready(function($) {
        $('#lostpassword-form').on('submit', function(e) {
            e.preventDefault();
            var btn = $(this).find('button');
            btn.text('Отправка...').prop('disabled', true);
            $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                action: 'abs_lostpassword',
                user_login: $('input[name="user_login"]').val()
            }, function(r) {
                if (r.success) {
                    $('#lostpassword-message').html('<div class="abs-auth-success">✅ Ссылка для сброса пароля отправлена на ваш email. Проверьте почту!</div>');
                    $('#lostpassword-form').hide();
                } else {
                    $('#lostpassword-message').html('<div class="abs-auth-error">❌ ' + r.data + '</div>');
                    btn.text('Сбросить пароль').prop('disabled', false);
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

// Ошибка входа — всегда на свою страницу
add_action('wp_login_failed', function($username) {
    wp_redirect(home_url('/login?login=failed'));
    exit;
});

// Пустые поля — тоже на свою страницу
add_action('wp_login_errors', function($errors, $redirect_to) {
    if (isset($_POST['log'])) {
        wp_redirect(home_url('/login?login=failed'));
        exit;
    }
    return $errors;
}, 10, 2);

// Ошибка регистрации
add_action('register_post', function($login, $email, $errors) {
    $ref = wp_get_referer();
    if ($ref && !strpos($ref, 'wp-login') && $errors->get_error_code()) {
        $code = $errors->get_error_code();
        $map = ['empty_username' => 'empty', 'empty_email' => 'empty', 'email_exists' => 'email_exists', 'username_exists' => 'username_exists'];
        $param = isset($map[$code]) ? $map[$code] : 'empty';
        wp_redirect(add_query_arg('registration', $param, $ref));
        exit;
    }
}, 10, 3);

// Жёсткий редирект после входа
add_action('wp_login', function($user_login, $user) {
    if (!in_array('administrator', $user->roles)) {
        wp_redirect(home_url());
        exit;
    }
}, 10, 2);

// Редирект после регистрации
add_filter('registration_redirect', function() {
    return home_url('/login?registered=1');
}, 999);

// Редирект после сброса пароля
add_action('after_password_reset', function($user) {
    wp_redirect(home_url('/login?password_reset=1'));
    exit;
});



add_action('login_form_register', function() {
    if (!current_user_can('administrator')) {
        wp_redirect(home_url('/register'));
        exit;
    }
});

add_action('login_form_lostpassword', function() {
    if (!current_user_can('administrator')) {
        wp_redirect(home_url('/lostpassword'));
        exit;
    }
});

// Стили для всех форм
add_action('wp_head', function() {
    if (is_page('login') || is_page('register') || is_page('lostpassword') || is_page('lk') || is_page('resetpassword')) {        echo '<style>
        .abs-auth-form { max-width:400px; margin:30px auto; background:rgba(26,26,46,0.9); border-radius:20px; padding:30px; border:1px solid rgba(13,202,240,0.2); }
        .abs-auth-form h2 { text-align:center; color:#0dcaf0; margin-bottom:25px; font-size:1.5rem; }
        .abs-auth-form .form-group { margin-bottom:15px; }
        .abs-auth-form .form-group label { display:block; margin-bottom:6px; color:rgba(255,255,255,0.8); font-size:14px; }
        .abs-auth-form .form-group input { width:100%; padding:12px; background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.15); border-radius:30px; color:#fff; font-size:14px; box-sizing:border-box; }
        .abs-auth-form .checkbox-group { display:flex; align-items:center; gap:8px; margin-bottom:20px; color:rgba(255,255,255,0.7); font-size:13px; }
        .auth-submit-btn { width:100%; padding:12px; background:linear-gradient(90deg,#0dcaf0,#5bc0de); border:none; border-radius:40px; color:#1b2039; font-weight:700; font-size:16px; cursor:pointer; }
        .auth-submit-btn:hover { transform:scale(1.02); box-shadow:0 4px 12px rgba(13,202,240,0.3); }
        .auth-links { text-align:center; margin-top:15px; padding-top:15px; border-top:1px solid rgba(255,255,255,0.1); display:flex; justify-content:center; gap:20px; font-size:13px; }
        .auth-links a { color:#0dcaf0; text-decoration:none; }
        .auth-links a:hover { text-decoration:underline; }
        .abs-auth-error { background:rgba(255,68,68,0.15); border-left:3px solid #ff4444; padding:10px 15px; border-radius:8px; margin-bottom:20px; color:#ff6666; font-size:14px; }
        .abs-auth-success { background:rgba(13,202,240,0.15); border-left:3px solid #0dcaf0; padding:10px 15px; border-radius:8px; margin-bottom:20px; color:#0dcaf0; font-size:14px; }
        </style>';
    }
});

// Кнопка Вход/Выход в основное меню
add_filter('wp_nav_menu_items', 'abs_add_auth_link', 10, 2);
function abs_add_auth_link($items, $args) {
    if ($args->theme_location == 'primary') {
        if (is_user_logged_in()) {
            
            $items .= '<li class="menu-item"><a href="' . wp_logout_url(home_url()) . '">🚪 Выйти</a></li>';
        } else {
            $items .= '<li class="menu-item"><a href="/login">🔐 Войти</a></li>';
        }
    }
    return $items;
}

// Редирект после выхода на главную
add_action('wp_logout', function() {
    wp_redirect(home_url());
    exit;
});

// AJAX обработчик регистрации
add_action('wp_ajax_nopriv_abs_register_user', 'abs_ajax_register_user');
function abs_ajax_register_user() {
    $login = sanitize_user($_POST['user_login']);
    $email = sanitize_email($_POST['user_email']);
    $pass = $_POST['user_pass'];
    
    if (empty($login) || empty($email) || empty($pass)) {
        wp_send_json_error('Заполните все поля');
    }
    if (strlen($pass) < 6) {
        wp_send_json_error('Пароль должен быть минимум 6 символов');
    }
    if (username_exists($login)) {
        wp_send_json_error('Это имя пользователя уже занято');
    }
    if (email_exists($email)) {
        wp_send_json_error('Этот email уже используется');
    }
    
    $user_id = wp_insert_user(array(
        'user_login' => $login,
        'user_email' => $email,
        'user_pass' => $pass,
        'role' => 'subscriber'
    ));
    
    if (is_wp_error($user_id)) {
        wp_send_json_error($user_id->get_error_message());
    }
    
    // Автоматически логиним
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);
    
        wp_send_json_success(array('name' => $login));
}

// AJAX обработчик восстановления пароля
add_action('wp_ajax_nopriv_abs_lostpassword', 'abs_ajax_lostpassword');
function abs_ajax_lostpassword() {
    $login = sanitize_text_field($_POST['user_login']);
    
    if (empty($login)) {
        wp_send_json_error('Введите email или имя пользователя');
    }
    
    $user = get_user_by('email', $login) ?: get_user_by('login', $login);
    
    if (!$user) {
        wp_send_json_error('Пользователь с таким email или именем не найден');
    }
    
    // Генерируем ключ сброса
    $key = get_password_reset_key($user);
    if (is_wp_error($key)) {
        wp_send_json_error('Ошибка генерации ключа сброса');
    }
    
    $reset_url = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user->user_login));
    
    $message = "Для сброса пароля перейдите по ссылке:\n\n$reset_url\n\nЕсли вы не запрашивали сброс пароля, проигнорируйте это письмо.";
    wp_mail($user->user_email, 'Сброс пароля на сайте ' . get_bloginfo('name'), $message);
    
    wp_send_json_success();
}

// Перехват страницы сброса пароля (убираем админку)
add_action('login_form_rp', function() {
    $key = isset($_GET['key']) ? $_GET['key'] : '';
    $login = isset($_GET['login']) ? $_GET['login'] : '';
    if ($key && $login) {
        wp_redirect(home_url('/resetpassword?key=' . urlencode($key) . '&login=' . urlencode($login)));
        exit;
    }
});

add_action('login_form_resetpass', function() {
    $key = isset($_GET['key']) ? $_GET['key'] : '';
    $login = isset($_GET['login']) ? $_GET['login'] : '';
    if ($key && $login) {
        wp_redirect(home_url('/resetpassword?key=' . urlencode($key) . '&login=' . urlencode($login)));
        exit;
    }
});

// Шорткод страницы установки нового пароля
add_shortcode('abs_resetpassword', 'abs_resetpassword_shortcode');
function abs_resetpassword_shortcode() {
    if (is_user_logged_in()) {
        return '<p>✅ Вы уже вошли. <a href="' . home_url() . '">На главную</a></p>';
    }
    
    $key = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';
    $login = isset($_GET['login']) ? sanitize_text_field($_GET['login']) : '';
    
    if (!$key || !$login) {
        return '<div class="abs-auth-form"><h2>🔑 Ошибка</h2><p>Неверная ссылка для сброса пароля.</p><a href="/lostpassword" class="auth-submit-btn" style="display:block;text-align:center;">Запросить новую ссылку</a></div>';
    }
    
    $user = check_password_reset_key($key, $login);
    if (is_wp_error($user)) {
        return '<div class="abs-auth-form"><h2>🔑 Ошибка</h2><p>Ссылка устарела или недействительна.</p><a href="/lostpassword" class="auth-submit-btn" style="display:block;text-align:center;">Запросить новую ссылку</a></div>';
    }
    
    ob_start();
    ?>
    <div class="abs-auth-form">
        <h2>🔑 Новый пароль</h2>
        <form id="resetpassword-form">
            <input type="hidden" name="key" value="<?php echo esc_attr($key); ?>">
            <input type="hidden" name="login" value="<?php echo esc_attr($login); ?>">
            <div class="form-group">
                <label>🔒 Новый пароль (минимум 6 символов)</label>
                <input type="password" name="pass1" required minlength="6">
            </div>
            <div class="form-group">
                <label>🔒 Повторите пароль</label>
                <input type="password" name="pass2" required minlength="6">
            </div>
            <button type="submit" class="auth-submit-btn">Установить пароль</button>
        </form>
        <div id="resetpassword-message"></div>
    </div>
    <script>
    jQuery(document).ready(function($) {
        $('#resetpassword-form').on('submit', function(e) {
            e.preventDefault();
            if ($('input[name="pass1"]').val() !== $('input[name="pass2"]').val()) {
                $('#resetpassword-message').html('<div class="abs-auth-error">❌ Пароли не совпадают</div>');
                return;
            }
            var btn = $(this).find('button');
            btn.text('Сохранение...').prop('disabled', true);
            var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
            $.post(ajaxurl, {
                action: 'abs_resetpassword',
                key: $('input[name="key"]').val(),
                login: $('input[name="login"]').val(),
                pass: $('input[name="pass1"]').val()
            }, function(r) {
                if (r.success) {
                    $('.abs-auth-form').html('<div style="text-align:center;padding:40px 20px;"><div style="font-size:60px;margin-bottom:20px;">🔐</div><h2 style="color:#0dcaf0;margin-bottom:15px;">Пароль изменён!</h2><p style="color:#fff;font-size:16px;margin-bottom:25px;">Теперь вы можете войти с новым паролем.</p><a href="/login" class="auth-submit-btn" style="display:inline-block;padding:12px 30px;background:linear-gradient(90deg,#0dcaf0,#5bc0de);border-radius:40px;color:#1b2039;font-weight:700;text-decoration:none;font-size:16px;">🔐 Войти</a></div>');
                } else {
                    $('#resetpassword-message').html('<div class="abs-auth-error">❌ ' + r.data + '</div>');
                    btn.text('Установить пароль').prop('disabled', false);
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

// AJAX обработчик установки нового пароля
add_action('wp_ajax_nopriv_abs_resetpassword', 'abs_ajax_resetpassword');
function abs_ajax_resetpassword() {
    $key = sanitize_text_field($_POST['key']);
    $login = sanitize_text_field($_POST['login']);
    $pass = $_POST['pass'];
    
    if (strlen($pass) < 6) {
        wp_send_json_error('Пароль должен быть минимум 6 символов');
    }
    
        $user = check_password_reset_key($key, $login);
    if (is_wp_error($user)) {
        wp_send_json_error('Ссылка устарела');
    }
    
    wp_set_password($pass, $user->ID);
    wp_send_json_success(array('ok' => true));
}

// Скрываем wp-admin от не-админов
add_action('init', function() {
    if (is_admin() && !current_user_can('administrator') && !wp_doing_ajax()) {
        wp_redirect(home_url());
        exit;
    }
});

// Редирект с wp-login.php на главную (только GET-запросы, не ломаем вход)
add_action('login_init', function() {
    if (!current_user_can('administrator') && $_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['action']) && !isset($_GET['key'])) {
        wp_redirect(home_url());
        exit;
    }
});
// Ссылки на аудиокниги и книги с подсчётом
add_filter('wp_nav_menu_items', 'abs_add_book_links', 5, 2);
function abs_add_book_links($items, $args) {
    if ($args->theme_location == 'primary') {
        global $wpdb;
        $audio_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}abs_book_cache");
        $text_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'ranobe' AND post_status = 'publish'");
        
        $links = '<li class="menu-item"><a href="/">🏠 Главная</a></li>';
        $links .= '<li class="menu-item"><a href="/catalog?type=audio">🎧 Аудиокниги (' . $audio_count . ')</a></li>';
        $links .= '<li class="menu-item"><a href="/catalog?type=text">📖 Книги (' . $text_count . ')</a></li>';
        $links .= '<li class="menu-item"><a href="https://pay.cloudtips.ru/p/db763c18" target="_blank" style="background:rgba(255,152,0,0.15);color:#ff9800!important;border-radius:20px;padding:6px 16px!important;font-weight:600;">💰 Поддержать</a></li>';
        
        $items = $links . $items;
    }
    return $items;
}
// Кнопка Войти в админку для админа в меню
add_filter('wp_nav_menu_items', 'abs_add_admin_link', 10, 2);
function abs_add_admin_link($items, $args) {
    if ($args->theme_location == 'primary' && current_user_can('administrator')) {
        $items .= '<li class="menu-item"><a href="' . admin_url() . '">⚙️ Админка</a></li>';
    }
    return $items;
}

// ========== ТЕКСТОВЫЕ КНИГИ (РАНОБЭ) ==========

// 1. Регистрация типов записей
add_action('init', function() {
    register_post_type('ranobe', array(
        'labels' => array(
            'name' => 'Ранобэ',
            'singular_name' => 'Ранобэ',
            'add_new' => 'Добавить книгу',
            'edit_item' => 'Редактировать книгу',
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'comments'),
        'taxonomies' => array('category'),
        'menu_icon' => 'dashicons-book-alt',
        'rewrite' => array('slug' => 'ranobe'),
    ));
    
    register_post_type('chapter', array(
        'labels' => array(
            'name' => 'Главы',
            'singular_name' => 'Глава',
            'add_new' => 'Добавить главу',
            'edit_item' => 'Редактировать главу',
        ),
        'public' => true,
        'has_archive' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => 'edit.php?post_type=ranobe',
        'supports' => array('title', 'editor', 'comments', 'page-attributes'),
        'hierarchical' => false,
        'rewrite' => array('slug' => 'chapter'),
    ));
});

// 2. Создание таблиц
add_action('after_switch_theme', function() {
    global $wpdb;
    $summaries_table = $wpdb->prefix . 'abs_summaries';
    $charset = $wpdb->get_charset_collate();
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    dbDelta("CREATE TABLE $summaries_table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ranobe_id BIGINT NOT NULL,
        title VARCHAR(255) DEFAULT '',
        content LONGTEXT,
        audio_file_id VARCHAR(100) DEFAULT '',
        duration INT DEFAULT 0,
        order_num INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset");
});

// 3. Мета-боксы и сохранение
add_action('add_meta_boxes', function() {
    add_meta_box('ranobe_details', 'Детали книги', function($post) {
        $author = get_post_meta($post->ID, '_ranobe_author', true);
        $status = get_post_meta($post->ID, '_ranobe_status', true);
        $year = get_post_meta($post->ID, '_ranobe_year', true);
        $language = get_post_meta($post->ID, '_ranobe_language', true);
        $original_url = get_post_meta($post->ID, '_ranobe_original_url', true);
        $abs_book_id = get_post_meta($post->ID, '_ranobe_abs_book_id', true);
        ?>
        <p><label>Автор: <input type="text" name="ranobe_author" value="<?php echo esc_attr($author); ?>" style="width:100%"></label></p>
        <p><label>Статус:
            <select name="ranobe_status">
                <option value="ongoing" <?php selected($status, 'ongoing'); ?>>Онгоинг</option>
                <option value="completed" <?php selected($status, 'completed'); ?>>Завершено</option>
                <option value="frozen" <?php selected($status, 'frozen'); ?>>Заморожено</option>
            </select>
        </label></p>
        <p><label>Год выпуска: <input type="number" name="ranobe_year" value="<?php echo esc_attr($year); ?>" style="width:100%"></label></p>
        <p><label>Язык оригинала:
            <select name="ranobe_language">
                <option value="jp" <?php selected($language, 'jp'); ?>>Японский</option>
                <option value="cn" <?php selected($language, 'cn'); ?>>Китайский</option>
                <option value="kr" <?php selected($language, 'kr'); ?>>Корейский</option>
                <option value="en" <?php selected($language, 'en'); ?>>Английский</option>
                <option value="ru" <?php selected($language, 'ru'); ?>>Русский</option>
            </select>
        </label></p>
        <p><label>Оригинальный URL: <input type="url" name="ranobe_original_url" value="<?php echo esc_url($original_url); ?>" style="width:100%"></label></p>
        <p><label>Аудиокнига:
            <select name="ranobe_abs_book_id" style="width:100%">
                <option value="">— Не выбрана —</option>
                <?php
                $audio_posts = get_posts(array('post_type' => 'post', 'meta_key' => 'abs_book_id', 'posts_per_page' => 100, 'orderby' => 'title', 'order' => 'ASC'));
                foreach ($audio_posts as $ap):
                    $ap_book_id = get_post_meta($ap->ID, 'abs_book_id', true);
                    echo '<option value="' . esc_attr($ap_book_id) . '" ' . selected($abs_book_id, $ap_book_id, false) . '>' . esc_html($ap->post_title) . '</option>';
                endforeach;
                ?>
            </select>
        </label></p>
        <?php
    }, 'ranobe', 'side');

    add_meta_box('ranobe_chapters_new', 'Главы', function($post) {
        $chapters = get_posts(array('post_type' => 'chapter', 'post_parent' => $post->ID, 'posts_per_page' => -1, 'orderby' => 'meta_value_num', 'meta_key' => '_chapter_number', 'order' => 'ASC'));
        echo '<table class="widefat"><thead><tr><th>№</th><th>Название</th><th>Действия</th></tr></thead><tbody>';
        foreach ($chapters as $ch) {
            $num = get_post_meta($ch->ID, '_chapter_number', true);
            $edit_url = get_edit_post_link($ch->ID);
            echo '<tr><td>' . $num . '</td><td><a href="' . $edit_url . '">' . esc_html($ch->post_title) . '</a></td><td><a href="' . get_permalink($ch->ID) . '" target="_blank">📖</a></td></tr>';
        }
        echo '</tbody></table>';
        echo '<a href="' . admin_url('post-new.php?post_type=chapter&ranobe_parent=' . $post->ID) . '" class="button">+ Добавить главу</a>';
    }, 'ranobe', 'normal', 'high');

    add_meta_box('chapter_details', 'Параметры главы', function($post) {
        $num = get_post_meta($post->ID, '_chapter_number', true);
        $vol = get_post_meta($post->ID, '_chapter_volume', true);
        $parent_id = $post->post_parent;
        if ($parent_id) {
            $parent_title = get_the_title($parent_id);
            $parent_edit_url = get_edit_post_link($parent_id);
            echo '<p><strong>📖 Книга:</strong> <a href="' . $parent_edit_url . '">' . esc_html($parent_title) . '</a></p>';
        }
        echo '<hr>';
        echo '<p><label>Номер главы: <input type="number" name="chapter_number" value="' . esc_attr($num) . '" style="width:100%"></label></p>';
        echo '<p><label>Том: <input type="number" name="chapter_volume" value="' . esc_attr($vol) . '" style="width:100%"></label></p>';
    }, 'chapter', 'side');
});

// Сохранение
add_action('save_post', function($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (get_post_type($post_id) === 'ranobe') {
        if (isset($_POST['ranobe_author'])) update_post_meta($post_id, '_ranobe_author', sanitize_text_field($_POST['ranobe_author']));
        if (isset($_POST['ranobe_status'])) update_post_meta($post_id, '_ranobe_status', sanitize_text_field($_POST['ranobe_status']));
        if (isset($_POST['ranobe_year'])) update_post_meta($post_id, '_ranobe_year', intval($_POST['ranobe_year']));
        if (isset($_POST['ranobe_language'])) update_post_meta($post_id, '_ranobe_language', sanitize_text_field($_POST['ranobe_language']));
        if (isset($_POST['ranobe_original_url'])) update_post_meta($post_id, '_ranobe_original_url', esc_url_raw($_POST['ranobe_original_url']));
        if (isset($_POST['ranobe_abs_book_id'])) update_post_meta($post_id, '_ranobe_abs_book_id', sanitize_text_field($_POST['ranobe_abs_book_id']));
    }
    if (get_post_type($post_id) === 'chapter') {
        if (isset($_POST['chapter_number'])) update_post_meta($post_id, '_chapter_number', intval($_POST['chapter_number']));
        if (isset($_POST['chapter_volume'])) update_post_meta($post_id, '_chapter_volume', intval($_POST['chapter_volume']));
    }
});



// Разрешаем главам иметь родителем ranobe (в админке)
add_filter('page_attributes_dropdown_pages_args', function($args, $post) {
    if ($post->post_type === 'chapter') {
        $args['post_type'] = 'ranobe';
    }
    return $args;
}, 10, 2);

// Авто-включение комментариев для ranobe
add_action('save_post', function($post_id, $post, $update) {
    if ($post->post_type === 'ranobe' && $post->post_status === 'publish' && $post->comment_status !== 'open') {
        global $wpdb;
        $wpdb->update($wpdb->posts, ['comment_status' => 'open'], ['ID' => $post_id]);
    }
}, 20, 3);

// Включаем ranobe в вывод категорий
add_action('pre_get_posts', function($query) {
    if ($query->is_category() && $query->is_main_query()) {
        $query->set('post_type', array('post', 'ranobe'));
    }
});



// Отключаем проверку SSL для запросов к ABS
add_action('http_api_curl', function($handle, $r, $url) {
    if (strpos($url, '94.41.21.24') !== false || strpos($url, 'audiobook.1001ranobe.ru') !== false) {
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
    }
}, 10, 3);

// Шорткод "Похожие книги" по жанрам
add_shortcode('abs_similar', 'abs_similar_shortcode');
function abs_similar_shortcode($atts) {
    global $post;
    if (!$post) return '';
    
    $current_id = $post->ID;
    $limit = isset($atts['limit']) ? intval($atts['limit']) : 4;
    
    // Получаем жанры текущей книги
    $current_genres = array();
    
    if ($post->post_type === 'ranobe') {
        $cats = wp_get_post_categories($current_id);
        foreach ($cats as $cid) {
            $cat = get_category($cid);
            if ($cat) $current_genres[] = $cat->name;
        }
    } elseif ($post->post_type === 'post') {
        $book_id = get_post_meta($current_id, 'abs_book_id', true);
        if ($book_id) {
            $meta = abs_get_book_meta_from_ranobe($book_id);
            $current_genres = $meta['genres'] ?: array();
        }
    } elseif ($post->post_type === 'chapter') {
        $parent_id = $post->post_parent;
        $cats = wp_get_post_categories($parent_id);
        foreach ($cats as $cid) {
            $cat = get_category($cid);
            if ($cat) $current_genres[] = $cat->name;
        }
    }
    
    if (empty($current_genres)) return '<!-- DEBUG: no genres for post_type=' . $post->post_type . ' id=' . $current_id . ' -->';
    
    global $wpdb;
    $meta_table = $wpdb->prefix . 'abs_audio_meta';
    $all_books = array();
    
    // Ищем текстовые книги с похожими жанрами
    $genre_placeholders = implode("','", array_map('esc_sql', $current_genres));
    
    $text_books = get_posts(array(
        'post_type' => 'ranobe',
        'posts_per_page' => $limit * 2,
        'exclude' => array($current_id, ($post->post_type === 'chapter' ? $post->post_parent : 0)),
        'category__in' => wp_get_post_categories($current_id),
        'orderby' => 'rand',
    ));
    
    foreach ($text_books as $tb) {
        $cats = wp_get_post_categories($tb->ID);
        $genres = array();
        foreach ($cats as $cid) {
            $cat = get_category($cid);
            if ($cat) $genres[] = $cat->name;
        }
        $common = count(array_intersect($current_genres, $genres));
        
        $author = get_post_meta($tb->ID, '_ranobe_author', true);
        $desc = $tb->post_excerpt ?: wp_trim_words(strip_tags($tb->post_content), 15);
        
        $all_books[] = array(
            'type' => 'text', 'title' => $tb->post_title, 'permalink' => get_permalink($tb->ID),
            'cover_url' => has_post_thumbnail($tb->ID) ? get_the_post_thumbnail_url($tb->ID, 'medium') : '',
            'author' => $author, 'genres' => $genres, 'description' => $desc,
            'common' => $common,
        );
    }
        // Аудиокниги с похожими жанрами
    if (!empty($current_genres)) {
        $genre_likes = [];
        foreach ($current_genres as $g) {
            $genre_likes[] = "genres LIKE '%" . esc_sql($g) . "%'";
        }
        $where = implode(' OR ', $genre_likes);
        
        $similar_audio = $wpdb->get_results("
            SELECT am.book_id, am.author, am.genres, am.description, am.cover_url,
                   c.book_data
            FROM {$wpdb->prefix}abs_audio_meta am
            JOIN {$wpdb->prefix}abs_book_cache c ON am.book_id = c.book_id
            WHERE ($where) AND am.author != ''
            LIMIT $limit
        ");
        
        foreach ($similar_audio as $ab) {
            $book_data = json_decode($ab->book_data, true);
            $title = $book_data['media']['metadata']['title'] ?? 'Без названия';
            $perm = abs_get_book_permalink($ab->book_id);
            $genres = $ab->genres ? explode(', ', $ab->genres) : array();
            $common = count(array_intersect($current_genres, $genres));
            if ($common == 0) continue;
            
            $all_books[] = array(
                'type' => 'audio', 'title' => $title, 'permalink' => $perm,
                'cover_url' => $ab->cover_url ?: '',
                'author' => $ab->author, 'genres' => $genres,
                'description' => $ab->description ?: ($book_data['media']['metadata']['description'] ?? ''),
                'common' => $common,
            );
        }
    }
    // Сортируем по количеству общих жанров
    usort($all_books, function($a, $b) { return $b['common'] - $a['common']; });
    $all_books = array_slice($all_books, 0, $limit);
    
    if (empty($all_books)) return '<!-- DEBUG: no similar books for genres: ' . implode(',', $current_genres) . ' -->';
    
    $html = '<div class="similar-section"><h2>📚 Похожие книги</h2><div class="catalog-books-grid">';
    
    foreach ($all_books as $book) {
        $html .= '<div class="catalog-book-card">';
        $html .= '<div class="catalog-book-cover">';
        if ($book['cover_url']) {
            $html .= '<img src="' . esc_url($book['cover_url']) . '" alt="" onerror="this.style.display=\'none\'; this.parentElement.querySelector(\'.no-cover\').style.display=\'flex\';">';
            $html .= '<div class="no-cover" style="display:none;">📖</div>';
        } else {
            $html .= '<div class="no-cover">📖</div>';
        }
        $html .= '</div>';
        $html .= '<div class="catalog-book-info">';
        $html .= '<h3 class="catalog-book-title"><a href="' . esc_url($book['permalink']) . '">' . esc_html($book['title']) . '</a></h3>';
        if ($book['author']) {
            $html .= '<div class="catalog-book-author"><a href="/catalog?author=' . urlencode($book['author']) . '" class="author-link">' . esc_html($book['author']) . '</a></div>';
        }
        if (!empty($book['genres'])) {
            $html .= '<div class="catalog-book-genres">';
            foreach (array_slice($book['genres'], 0, 3) as $g) {
                $html .= '<a href="/catalog?genre=' . urlencode($g) . '" class="book-genre-tag">' . esc_html($g) . '</a>';
            }
            $html .= '</div>';
        }
        if ($book['description']) {
            $html .= '<div class="catalog-book-description">' . esc_html(wp_trim_words(wp_strip_all_tags($book['description']), 15, '...')) . '</div>';
        }

        
        $html .= '</div>';
        $html .= '<div class="catalog-book-actions"><a href="' . esc_url($book['permalink']) . '" class="catalog-listen-btn">📖 Читать</a></div>';
        $html .= '</div>';
    }
    
    $html .= '</div></div>';
    return $html;
}

add_shortcode('abs_related', 'abs_related_shortcode');
function abs_related_shortcode($atts) {
    global $post;
    if (!$post) return '';
    
    $current_id = $post->ID;
    $limit = isset($atts['limit']) ? intval($atts['limit']) : 4;
    
    global $wpdb;
    $views_table = $wpdb->prefix . 'abs_book_views';
    $meta_table = $wpdb->prefix . 'abs_audio_meta';
    $cache_table = $wpdb->prefix . 'abs_book_cache';
    
    // Определяем book_id текущей страницы
    if ($post->post_type === 'post') {
        $current_book_id = get_post_meta($current_id, 'abs_book_id', true);
        $current_ranobe_id = 0;
    } elseif ($post->post_type === 'ranobe') {
        $current_book_id = '';
        $current_ranobe_id = $current_id;
    } elseif ($post->post_type === 'chapter') {
        $current_book_id = '';
        $current_ranobe_id = $post->post_parent;
    }
    
    if (!$current_book_id && !$current_ranobe_id) return '';
    
    // Находим пользователей которые смотрели эту книгу
    if ($current_book_id) {
        $users = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT user_id FROM $views_table WHERE book_id = %s",
            $current_book_id
        ));
    } else {
        $users = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT user_id FROM $views_table WHERE ranobe_id = %d",
            $current_ranobe_id
        ));
    }
    
    if (empty($users)) return '';
    
    $user_ids = implode(',', array_map('intval', $users));
    
    // Находим книги которые смотрели те же пользователи
    $related = $wpdb->get_results("
        SELECT v.book_id, v.ranobe_id, v.type, COUNT(*) as views,
               am.author, am.genres, am.description, am.cover_url,
               c.book_data
        FROM $views_table v
        LEFT JOIN $meta_table am ON v.book_id = am.book_id
        LEFT JOIN $cache_table c ON v.book_id = c.book_id
        WHERE v.user_id IN ($user_ids)
          AND (v.book_id != '" . esc_sql($current_book_id) . "' OR v.book_id IS NULL)
          AND (v.ranobe_id != " . intval($current_ranobe_id) . " OR v.ranobe_id = 0)
        GROUP BY COALESCE(v.book_id, v.ranobe_id), v.type
        ORDER BY views DESC
        LIMIT $limit
    ");
    
    $all_books = array();
    
    foreach ($related as $r) {
        if ($r->type === 'audio' && $r->book_id) {
            $book_data = json_decode($r->book_data, true);
            $title = $book_data['media']['metadata']['title'] ?? 'Без названия';
            $perm = abs_get_book_permalink($r->book_id);
            $all_books[] = array(
                'type' => 'audio', 'title' => $title, 'permalink' => $perm,
                'cover_url' => $r->cover_url ?: '',
                'author' => $r->author ?: '',
                'genres' => $r->genres ? explode(', ', $r->genres) : array(),
                'description' => $r->description ?: '',
            );
        } elseif ($r->ranobe_id) {
            $tb = get_post($r->ranobe_id);
            if (!$tb) continue;
            $author = get_post_meta($tb->ID, '_ranobe_author', true);
            $cats = wp_get_post_categories($tb->ID);
            $genres = array();
            foreach ($cats as $cid) { $cat = get_category($cid); if ($cat) $genres[] = $cat->name; }
            $desc = $tb->post_excerpt ?: wp_trim_words(strip_tags($tb->post_content), 15);
            $all_books[] = array(
                'type' => 'text', 'title' => $tb->post_title, 'permalink' => get_permalink($tb->ID),
                'cover_url' => has_post_thumbnail($tb->ID) ? get_the_post_thumbnail_url($tb->ID, 'medium') : '',
                'author' => $author ?: '', 'genres' => $genres, 'description' => $desc,
            );
        }
    }
    
    if (empty($all_books)) return '';
    
    $html = '<div class="related-section"><h2>👥 Читают также</h2><div class="catalog-books-grid">';
    
    foreach ($all_books as $book) {
        $type_badge = $book['type'] === 'audio' ? '🎧' : '📖';
        $btn_text = $book['type'] === 'audio' ? '▶ Слушать' : '📖 Читать';
        
        $html .= '<div class="catalog-book-card">';
        $html .= '<div class="catalog-book-cover">';
        if ($book['cover_url']) {
            $html .= '<img src="' . esc_url($book['cover_url']) . '" alt="" onerror="this.style.display=\'none\'; this.parentElement.querySelector(\'.no-cover\').style.display=\'flex\';">';
            $html .= '<div class="no-cover" style="display:none;">' . $type_badge . '</div>';
        } else {
            $html .= '<div class="no-cover">' . $type_badge . '</div>';
        }
        $html .= '</div>';
        $html .= '<div class="catalog-book-info">';
        $html .= '<h3 class="catalog-book-title"><a href="' . esc_url($book['permalink']) . '">' . esc_html($book['title']) . '</a></h3>';
        if ($book['author']) {
            $html .= '<div class="catalog-book-author"><a href="/catalog?author=' . urlencode($book['author']) . '" class="author-link">' . esc_html($book['author']) . '</a></div>';
        }
        if (!empty($book['genres'])) {
            $html .= '<div class="catalog-book-genres">';
            foreach (array_slice($book['genres'], 0, 3) as $g) {
                $html .= '<a href="/catalog?genre=' . urlencode($g) . '" class="book-genre-tag">' . esc_html($g) . '</a>';
            }
            $html .= '</div>';
        }
                if ($book['description']) {
            $html .= '<div class="catalog-book-description">' . esc_html(wp_trim_words(wp_strip_all_tags($book['description']), 15, '...')) . '</div>';
        }
            $html .= '</div>';
        $html .= '<div class="catalog-book-actions"><a href="' . esc_url($book['permalink']) . '" class="catalog-listen-btn">' . $btn_text . '</a></div>';
        $html .= '</div>';
    }
    
    $html .= '</div></div>';
    return $html;
}

// Обработчик удаления из "Продолжить" (работает без плеера)
add_action('wp_footer', function() {
    if (!is_user_logged_in()) return;
    ?>
    <script>
    jQuery(document).ready(function($) {
        $(document).on('click', '.remove-progress-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var btn = $(this);
            var card = btn.closest('.catalog-book-card, .book-card');
            if (confirm('Удалить книгу из списка?')) {
$.post('<?php echo admin_url("admin-ajax.php"); ?>', {
    action: 'remove_book_progress',
    book_id: btn.data('book-id'),
    type: btn.data('type') || 'audio'
}, function(r) {
    if (r.success) {
        card.fadeOut(300, function() { card.remove(); });
    } else {
        alert('Ошибка: ' + (r.data || 'не удалось удалить'));
    }
}).fail(function() {
    alert('Ошибка соединения');
});
            }
            return false;
        });
    });
    </script>
    <?php
});


// ========== SEO-ТЕКСТ ДЛЯ КНИГ ==========

function abs_generate_book_seo_text($book_id, $type = 'audio') {
    if ($type === 'audio') {
    global $wpdb;
    $cache = $wpdb->get_row($wpdb->prepare(
        "SELECT book_data, total_duration_formatted FROM {$wpdb->prefix}abs_book_cache WHERE book_id = %s",
        $book_id
    ));
    if (!$cache) return '';
    
    $data = json_decode($cache->book_data, true);
    $meta = $data['media']['metadata'] ?? [];
    $title = $meta['title'] ?? '';
    $description = $meta['description'] ?? '';
    $duration = $cache->total_duration_formatted ?: 'не указана';
    
    // Берём автора и жанры из связанной текстовой книги
    $ranobe_meta = abs_get_book_meta_from_ranobe($book_id);
    $author = $ranobe_meta['author'] ?: ($meta['authorName'] ?? $meta['author'] ?? '');
    $genres = !empty($ranobe_meta['genres']) ? $ranobe_meta['genres'] : ($meta['genres'] ?? []);
    
    $tracks_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}abs_track_durations WHERE book_id = %s",
        $book_id
    ));
} else {
        $post = get_post($book_id);
        if (!$post) return '';
        
        $title = $post->post_title;
        $description = $post->post_excerpt ?: wp_trim_words(strip_tags($post->post_content), 30);
        
        $author = get_post_meta($book_id, '_ranobe_author', true) ?: '';
        
        $cats = wp_get_post_categories($book_id);
        $genres = [];
        foreach ($cats as $cid) {
            $cat = get_category($cid);
            if ($cat) $genres[] = $cat->name;
        }
        
        $chapters = get_posts([
            'post_type' => 'chapter',
            'post_parent' => $book_id,
            'posts_per_page' => -1,
            'fields' => 'ids',
        ]);
        $tracks_count = count($chapters);
        $duration = ''; // для текста не указываем
    }
    
    if (empty($title)) return '';
    
    $genre_list = !empty($genres) ? implode(', ', $genres) : '';
    
    $text = '';
    
    if ($type === 'audio') {
        $text .= "Слушать аудиокнигу «{$title}» онлайн бесплатно на 1001ranobe.ru. ";
        if ($author) $text .= "Автор: {$author}. ";
        if ($genre_list) $text .= "Жанры: {$genre_list}. ";
        if ($duration !== 'не указана') $text .= "Общая длительность: {$duration}. ";
        if ($tracks_count) $text .= "Плейлист включает {$tracks_count} треков. ";
        if ($description) $text .= "{$description} ";
        $text .= "Начните слушать «{$title}» прямо сейчас или добавьте аудиокнигу в избранное.";
    } else {
        $text .= "Читать ранобэ «{$title}» онлайн бесплатно на 1001ranobe.ru. ";
        if ($author) $text .= "Автор: {$author}. ";
        if ($genre_list) $text .= "Жанры: {$genre_list}. ";
        if ($tracks_count) $text .= "Всего глав: {$tracks_count}. ";
        if ($description) $text .= "{$description} ";
        $text .= "Читайте «{$title}» в удобной читалке с ночным режимом и сохранением прогресса.";
    }
    
    return '<div class="book-seo-text" style="margin-top:30px;padding:20px;background:rgba(255,255,255,0.03);border-radius:12px;font-size:0.9rem;line-height:1.6;color:rgba(255,255,255,0.7);">' . esc_html($text) . '</div>';
}

// ========== SCHEMA.ORG РАЗМЕТКА ДЛЯ КНИГ ==========

add_action('wp_head', 'abs_schema_book_markup');
function abs_schema_book_markup() {
    if (!is_singular(['post', 'ranobe'])) return;
    
    global $post, $wpdb;
    $schema = [];
    $book_id_for_rating = ''; // для запроса рейтинга
    
    if ($post->post_type === 'post') {
        $book_id = get_post_meta($post->ID, 'abs_book_id', true);
        if (!$book_id) return;
        
        $book_id_for_rating = $book_id;
        
        $cache = $wpdb->get_row($wpdb->prepare(
            "SELECT book_data, total_duration_formatted FROM {$wpdb->prefix}abs_book_cache WHERE book_id = %s", $book_id
        ));
        if (!$cache) return;
        
        $data = json_decode($cache->book_data, true);
        $meta = $data['media']['metadata'] ?? [];
        $title = $meta['title'] ?? $post->post_title;
        $description = $meta['description'] ?? '';
        $duration = $cache->total_duration_formatted ?? '';
        
        $ranobe_meta = abs_get_book_meta_from_ranobe($book_id);
        $author = $ranobe_meta['author'] ?: ($meta['authorName'] ?? '');
        $cover = $ranobe_meta['cover_url'] ?: '';
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Audiobook',
            'name' => $title,
            'description' => $description,
            'url' => get_permalink(),
        ];
        if ($author) $schema['author'] = ['@type' => 'Person', 'name' => $author];
        if ($cover) $schema['image'] = $cover;
        if ($duration) $schema['duration'] = $duration;
        
    } elseif ($post->post_type === 'ranobe') {
        $book_id_for_rating = $post->ID;
        
        $title = $post->post_title;
        $description = $post->post_excerpt ?: wp_trim_words(strip_tags($post->post_content), 30);
        $author = get_post_meta($post->ID, '_ranobe_author', true);
        $cover = has_post_thumbnail($post->ID) ? get_the_post_thumbnail_url($post->ID, 'medium') : '';
        
        $cats = wp_get_post_categories($post->ID);
        $genres = [];
        foreach ($cats as $cid) {
            $cat = get_category($cid);
            if ($cat) $genres[] = $cat->name;
        }
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Book',
            'name' => $title,
            'description' => $description,
            'url' => get_permalink(),
        ];
        if ($author) $schema['author'] = ['@type' => 'Person', 'name' => $author];
        if ($cover) $schema['image'] = $cover;
        if ($genres) $schema['genre'] = $genres;
    }
    
    // Рейтинг
    if ($book_id_for_rating) {
        $rating_data = $wpdb->get_row($wpdb->prepare(
            "SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM {$wpdb->prefix}abs_ratings WHERE book_id = %s",
            $book_id_for_rating
        ));
        if ($rating_data && $rating_data->count > 0) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => round($rating_data->avg_rating, 1),
                'reviewCount' => (int)$rating_data->count,
                'bestRating' => 5,
                'worstRating' => 1,
            ];
        }
    }
    
    if (!empty($schema)) {
        echo "\n<script type=\"application/ld+json\">\n" . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n</script>\n";
    }
}

// Авто-генерация title для аудиокниг
add_filter('wpseo_title', 'abs_auto_title', 10, 2);
function abs_auto_title($title, $presentation) {
    global $post;
    if (!$post) return $title;
    
    if ($post->post_type === 'ranobe') {
        $author = get_post_meta($post->ID, '_ranobe_author', true);
        $new_title = $post->post_title;
        if ($author) $new_title .= " — {$author}";
        return $new_title . ' читать онлайн бесплатно | 1001 Ранобэ';
    }
    
    if ($post->post_type === 'post') {
        $book_id = get_post_meta($post->ID, 'abs_book_id', true);
        $meta = $book_id ? abs_get_book_meta_from_ranobe($book_id) : [];
        $author = $meta['author'] ?? '';
        $new_title = $post->post_title;
        if ($author) $new_title .= " — {$author}";
        return $new_title . ' слушать онлайн аудиокнигу бесплатно | 1001 Ранобэ';
    }
    
    return $title;
}

// Авто-генерация meta description
add_filter('wpseo_metadesc', 'abs_auto_meta_description', 10, 2);
function abs_auto_meta_description($description, $presentation) {
    if (!empty($description)) return $description;
    
    global $post;
    if (!$post) return $description;
    
    if ($post->post_type === 'ranobe') {
        $author = get_post_meta($post->ID, '_ranobe_author', true);
        $cats = wp_get_post_categories($post->ID);
        $genres = [];
        foreach ($cats as $cid) { $cat = get_category($cid); if ($cat) $genres[] = $cat->name; }
        $chapters = get_posts(['post_type'=>'chapter','post_parent'=>$post->ID,'posts_per_page'=>-1,'fields'=>'ids']);
        $excerpt = $post->post_excerpt ?: wp_trim_words(strip_tags($post->post_content), 25);
        
        $desc = "Читать ранобэ «{$post->post_title}» онлайн бесплатно на 1001ranobe.ru.";
        if ($author) $desc .= " Автор: {$author}.";
        if ($genres) $desc .= " Жанры: " . implode(', ', array_slice($genres, 0, 5)) . ".";
        if ($chapters) $desc .= " Всего глав: " . count($chapters) . ".";
        if ($excerpt) $desc .= " {$excerpt}";
        $desc .= " Удобная читалка с ночным режимом и сохранением прогресса.";
        return $desc;
    }
    
    if ($post->post_type === 'post') {
        $book_id = get_post_meta($post->ID, 'abs_book_id', true);
        if (!$book_id) return $description;
        
        $meta = abs_get_book_meta_from_ranobe($book_id);
        global $wpdb;
        $duration = $wpdb->get_var($wpdb->prepare(
            "SELECT total_duration_formatted FROM {$wpdb->prefix}abs_book_cache WHERE book_id = %s", $book_id
        ));
        $tracks = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}abs_track_durations WHERE book_id = %s", $book_id
        ));
        
        $desc = "Слушать аудиокнигу «{$post->post_title}» онлайн бесплатно на 1001ranobe.ru.";
        if ($meta['author']) $desc .= " Автор: {$meta['author']}.";
        if ($meta['genres']) $desc .= " Жанры: " . implode(', ', array_slice($meta['genres'], 0, 5)) . ".";
        if ($duration) $desc .= " Длительность: {$duration}.";
        if ($tracks) $desc .= " Плейлист: {$tracks} треков.";
        if ($meta['description']) $desc .= " " . wp_trim_words($meta['description'], 20) . ".";
        $desc .= " Профессиональная озвучка, регулировка скорости, сохранение прогресса.";
        return $desc;
    }
    
    return $description;
}

add_filter('wpseo_breadcrumb_links', 'abs_fix_breadcrumbs');
function abs_fix_breadcrumbs($links) {
    if (is_singular('ranobe')) {
        $links = [
            ['url' => home_url('/'), 'text' => 'Главная'],
            ['url' => home_url('/catalog?type=text'), 'text' => 'Каталог книг', 'id' => 0],
            ['text' => get_the_title()],
        ];
    }
    elseif (is_singular('post')) {
        $links = [
            ['url' => home_url('/'), 'text' => 'Главная'],
            ['url' => home_url('/catalog?type=audio'), 'text' => 'Каталог аудиокниг', 'id' => 0],
            ['text' => get_the_title()],
        ];
    }
    elseif (is_singular('chapter')) {
        $parent_id = wp_get_post_parent_id(get_the_ID());
        $parent_title = $parent_id ? get_the_title($parent_id) : 'Книга';
        $parent_url = $parent_id ? get_permalink($parent_id) : '#';
        $links = [
            ['url' => home_url('/'), 'text' => 'Главная'],
            ['url' => home_url('/catalog?type=text'), 'text' => 'Каталог книг', 'id' => 0],
            ['url' => $parent_url, 'text' => $parent_title],
            ['text' => get_the_title()],
        ];
    }
    return $links;
}

// Авто-alt для обложек книг
add_filter('wp_get_attachment_image_attributes', 'abs_auto_alt_cover', 10, 3);
function abs_auto_alt_cover($attr, $attachment, $size) {
    if (is_singular(['ranobe', 'post']) && in_the_loop()) {
        if (empty($attr['alt'])) {
            $post_type = get_post_type();
            $prefix = ($post_type === 'post') ? 'Аудиокнига' : 'Ранобэ';
            $attr['alt'] = $prefix . ' «' . get_the_title() . '» — обложка';
        }
    }
    return $attr;
}

function abs_player_enqueue_scripts() {
    global $post;
    $has_player = $post && has_shortcode($post->post_content, 'abs_player');
    if (!$has_player) return;
    
    $howler_js = get_stylesheet_directory() . '/js/howler.min.js';
    if (file_exists($howler_js)) {
        wp_enqueue_script('howler-js', get_stylesheet_directory_uri() . '/js/howler.min.js', array(), '2.2.4', true);
    } else {
        wp_enqueue_script('howler-js', 'https://cdnjs.cloudflare.com/ajax/libs/howler/2.2.4/howler.min.js', array(), '2.2.4', true);
    }
    
    $player_js = get_stylesheet_directory() . '/js/abs-player.js';
    if (file_exists($player_js)) {
        wp_enqueue_script('abs-player', get_stylesheet_directory_uri() . '/js/abs-player.js', array('howler-js', 'jquery'), '1.0', true);
    }
    
    $book_id = $post ? get_post_meta($post->ID, 'abs_book_id', true) : '';
    wp_localize_script('abs-player', 'absPlayerData', array(
        'apiKey'    => defined('ABS_API_KEY') ? ABS_API_KEY : '',
        'serverUrl' => 'https://audiobook.1001ranobe.ru',
        'itemId'    => $book_id,
        'postId'    => $post ? $post->ID : 0,
        'ajaxUrl'   => admin_url('admin-ajax.php'),
        'nonce'     => wp_create_nonce('abs_player_nonce'),
    ));
}
add_action('wp_enqueue_scripts', 'abs_player_enqueue_scripts');

add_action('wp_head', 'abs_noindex_service_pages', 1);
function abs_noindex_service_pages() {
    $noindex_pages = ['lk', 'login', 'register', 'lostpassword', 'resetpassword', 'profile_edit', 'test-payment'];
    foreach ($noindex_pages as $slug) {
        if (is_page($slug)) {
            echo '<meta name="robots" content="noindex, follow">' . "\n";
            return;
        }
    }
}


add_action('wp_footer', function() {
    ?>
    <a href="https://pay.cloudtips.ru/p/db763c18" target="_blank" class="donate-float-btn" title="Поддержать проект">💰</a>
   <style>
.donate-float-btn {
    position:fixed;
    width:56px;height:56px;
    background:linear-gradient(135deg,#ff9800,#ff5722);border-radius:50%;
    display:flex;align-items:center;justify-content:center;font-size:28px;
    text-decoration:none;z-index:999;box-shadow:0 4px 15px rgba(255,87,34,0.4);
    animation:donate-pulse 2s infinite;
}
@keyframes donate-pulse {
    0%,100%{transform:scale(1)}50%{transform:scale(1.1)}
}
@media(max-width:768px){
    .donate-float-btn{width:48px;height:48px;font-size:24px}
}
</style>
<script>
(function(){
    var btn = document.querySelector('.donate-float-btn');
    if(!btn) return;
    var positions = [
        {bottom:'80px',right:'20px'},
        {bottom:'80px',left:'20px'},
        {top:'120px',right:'20px'},
        {top:'120px',left:'20px'},
        {bottom:'140px',right:'20px'},
        {bottom:'140px',left:'20px'},
    ];
    var pos = positions[Math.floor(Math.random() * positions.length)];
    for(var k in pos) btn.style[k] = pos[k];
})();
</script>
    <?php
});


add_action('wp_ajax_tbank_init_payment', 'abs_tbank_init_payment');
add_action('wp_ajax_nopriv_tbank_init_payment', 'abs_tbank_init_payment');

function abs_tbank_init_payment() {
    $terminal_key = '1778777475774';
    $password = '*$&xe&4M671pfCEy';
    
    $amount = intval($_POST['amount'] ?? 100);
    $order_id = sanitize_text_field($_POST['order_id'] ?? 'test_' . time());
    $description = sanitize_text_field($_POST['description'] ?? 'Тестовый платёж');
    
$token_string = $amount . $description . $order_id . $password . $terminal_key;
$token = hash('sha256', $token_string);

// Сохраняем заказ
$ranobe_id = 0;
$book_title = '';
if (preg_match('/voice_(\d+)_/', $order_id, $m)) {
    $ranobe_id = $m[1];
    $post = get_post($ranobe_id);
    $book_title = $post ? $post->post_title : '';
    
    global $wpdb;
    $table = $wpdb->prefix . 'abs_voice_orders';
    $wpdb->insert($table, [
        'order_id' => $order_id,
        'ranobe_id' => $ranobe_id,
        'book_title' => $book_title,
        'chapters_count' => intval(preg_replace('/[^0-9]/', '', sanitize_text_field($_POST['description'] ?? '0'))),
        'amount' => $amount / 100,
        'customer' => is_user_logged_in() ? wp_get_current_user()->user_login : 'Гость',
        'status' => 'paid',
    ]);
    
    // Отправка в Telegram
    $amount_rub = $amount / 100;
    $message = "🎙️ Новый заказ на озвучку!\n📖 {$book_title}\n💰 {$amount_rub} ₽\n👤 " . (is_user_logged_in() ? wp_get_current_user()->user_login : 'Гость');
    abs_telegram_log($message);
}
   $request_data = [
        'TerminalKey' => $terminal_key,
        'Amount' => $amount,
        'OrderId' => $order_id,
        'Description' => $description,
        'Token' => $token,
    ];
    
    $response = wp_remote_post('https://securepay.tinkoff.ru/v2/Init', [
        'headers' => ['Content-Type' => 'application/json'],
        'body' => json_encode($request_data),
        'timeout' => 30,
    ]);
    
    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    }
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    if (!empty($body['PaymentURL'])) {
        wp_send_json_success(['payment_url' => $body['PaymentURL']]);
    } else {
        wp_send_json_error($body['Details'] ?? $body['Message'] ?? 'Неизвестная ошибка');
    }
}

// Шорткод заказа озвучки
add_shortcode('abs_order_voice', function() {
    global $post;
    // Если уже есть аудиоверсия — не показываем
    $abs_book_id = get_post_meta($post->ID, '_ranobe_abs_book_id', true);
    if ($abs_book_id) return '';

    $chapters_count = 0;
    
    if ($post->post_type === 'ranobe') {
        $chapters = get_posts(['post_type'=>'chapter','post_parent'=>$post->ID,'posts_per_page'=>-1,'fields'=>'ids']);
        $chapters_count = count($chapters);
    }
    
    // Расчёт суммы: 0.1₽ за главу, минимум 50₽, округление вверх до 50₽
    $price = max(50, ceil($chapters_count * 0.1 / 50) * 50);
    
    ob_start();
    ?>
    <div style="background:linear-gradient(135deg,rgba(255,152,0,0.1),rgba(255,87,34,0.1));border:1px solid rgba(255,152,0,0.3);border-radius:16px;padding:20px;margin:15px 0;text-align:center;">
        <h3 style="color:#ff9800;margin:0 0 10px;">🎙️ Заказать озвучку книги</h3>
                
        <div style="display:flex;justify-content:center;gap:30px;margin-bottom:15px;">
            <div style="text-align:center;">
                <div style="color:rgba(255,255,255,0.5);font-size:0.8rem;">Глав в книге</div>
                <div style="color:#fff;font-size:1.5rem;font-weight:700;"><?php echo $chapters_count; ?></div>
            </div>
            <div style="text-align:center;">
                <div style="color:rgba(255,255,255,0.5);font-size:0.8rem;">Стоимость</div>
                <div style="color:#ff9800;font-size:1.5rem;font-weight:700;"><?php echo $price; ?> ₽</div>
            </div>
        </div>
        
        <button id="voice-order-btn" style="background:linear-gradient(135deg,#ff9800,#ff5722);border:none;border-radius:40px;padding:12px 30px;color:#fff;font-weight:700;font-size:1rem;cursor:pointer;">
            🎙️ Заказать озвучку за <?php echo $price; ?> ₽
        </button>
        <div id="voice-msg" style="margin-top:10px;"></div>
    </div>

    <script>
    document.getElementById('voice-order-btn').addEventListener('click', function() {
        this.textContent = 'Загрузка...';
        this.disabled = true;
        
        var amount = <?php echo $price * 100; ?>; // в копейках
        var formData = new FormData();
        formData.append('action', 'tbank_init_payment');
        formData.append('amount', amount);
        formData.append('order_id', 'voice_<?php echo $post->ID; ?>_' + Date.now());
        formData.append('description', 'Озвучка: <?php echo esc_js($post->post_title); ?> — <?php echo $chapters_count; ?> глав');
        
        fetch('<?php echo admin_url("admin-ajax.php"); ?>', {method:'POST', body: formData})
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success && d.data && d.data.payment_url) {
                window.location.href = d.data.payment_url;
            } else {
                document.getElementById('voice-msg').innerHTML = '<p style="color:#ff5555;">❌ ' + (d.data || 'Ошибка') + '</p>';
                this.textContent = '🎙️ Заказать озвучку за <?php echo $price; ?> ₽';
                this.disabled = false;
            }
        }.bind(this))
        .catch(function() {
            document.getElementById('voice-msg').innerHTML = '<p style="color:#ff5555;">❌ Ошибка</p>';
            this.textContent = '🎙️ Заказать озвучку за <?php echo $price; ?> ₽';
            this.disabled = false;
        }.bind(this));
    });
    </script>
    <?php
    return ob_get_clean();
});

require_once get_template_directory() . '/includes/abs-voice-orders.php';

add_action('wp_ajax_generate_fb2', 'abs_generate_fb2_for_order');
function abs_generate_fb2_for_order() {
    $ranobe_id = intval($_GET['ranobe_id']);
    $post = get_post($ranobe_id);
    $source = get_post_meta($ranobe_id, '_ranobe_source', true);
$order = ($source === 'ifreedom') ? 'DESC' : 'ASC';
    $chapters = get_posts(['post_type'=>'chapter','post_parent'=>$ranobe_id,'posts_per_page'=>-1,'orderby'=>'meta_value_num','meta_key'=>'_chapter_number','order'=>$order]);
    
    header('Content-Type: application/xml');
    header('Content-Disposition: attachment; filename="' . sanitize_title($post->post_title) . '.fb2"');
    
    echo '<?xml version="1.0" encoding="UTF-8"?><FictionBook xmlns="http://www.gribuser.ru/xml/fictionbook/2.0"><description><title-info><book-title>' . esc_html($post->post_title) . '</book-title></title-info></description><body>';
    foreach ($chapters as $ch) {
        echo '<section><title>' . esc_html($ch->post_title) . '</title>' . wpautop($ch->post_content) . '</section>';
    }
    echo '</body></FictionBook>';
    exit;
}

// Функция создания уведомления
function abs_create_notification($user_id, $type, $message, $link = '') {
    global $wpdb;
    $wpdb->insert($wpdb->prefix . 'abs_notifications', [
        'user_id' => $user_id,
        'type' => $type,
        'message' => $message,
        'link' => $link,
    ]);
}

// Новая глава → уведомить читателей
add_action('save_post', function($post_id, $post) {
    if ($post->post_type !== 'chapter' || $post->post_status !== 'publish') return;
    if (get_post_meta($post_id, '_notified', true)) return;
    
    $ranobe_id = $post->post_parent;
    $ranobe_title = get_the_title($ranobe_id);
    
    global $wpdb;
    $readers = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT user_id FROM {$wpdb->prefix}abs_reading_progress WHERE ranobe_id = %d",
        $ranobe_id
    ));
    
    foreach ($readers as $uid) {
        abs_create_notification($uid, 'new_chapter', 
            "📖 Новая глава: «{$ranobe_title}» — {$post->post_title}",
            get_permalink($post_id)
        );
    }
    
    update_post_meta($post_id, '_notified', 1);
}, 10, 2);

// Аудиоверсия добавлена → уведомить избранное
add_action('updated_post_meta', function($meta_id, $post_id, $meta_key) {
    if ($meta_key !== '_ranobe_abs_book_id') return;
    
    $abs_book_id = get_post_meta($post_id, '_ranobe_abs_book_id', true);
    if (!$abs_book_id) return;
    
    $ranobe_title = get_the_title($post_id);
    
    global $wpdb;
    $users = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT user_id FROM {$wpdb->prefix}abs_favorites WHERE ranobe_id = %d",
        $post_id
    ));
    
    foreach ($users as $uid) {
        abs_create_notification($uid, 'audio_added',
            "🎧 Аудиоверсия: «{$ranobe_title}»",
            get_permalink($post_id)
        );
    }
}, 10, 3);



add_shortcode('abs_notifications', function() {
    if (!is_user_logged_in()) return '<p>🔒 Нужно войти</p>';
    
    global $wpdb;
    $user_id = get_current_user_id();
    
    // Отмечаем как прочитанные
    if (isset($_GET['mark_read'])) {
        $wpdb->update($wpdb->prefix . 'abs_notifications', ['is_read' => 1], ['user_id' => $user_id]);
    }
    
    $notifications = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}abs_notifications WHERE user_id = %d ORDER BY created_at DESC LIMIT 50",
        $user_id
    ));
    
    ob_start();
    ?>
    <div style="max-width:600px;margin:30px auto;">
        <h2>🔔 Уведомления</h2>
        <a href="?mark_read=1" style="color:#0dcaf0;font-size:0.85rem;">Отметить все прочитанными</a>
        
        <?php if (empty($notifications)): ?>
            <p>Нет уведомлений</p>
        <?php else: foreach ($notifications as $n): ?>
            <div style="background:rgba(255,255,255,0.05);padding:12px;border-radius:10px;margin:8px 0;<?php echo $n->is_read ? 'opacity:0.5;' : 'border-left:3px solid #0dcaf0;'; ?>">
                <?php if ($n->link): ?>
                    <a href="<?php echo esc_url($n->link); ?>" style="text-decoration:none;color:inherit;">
                <?php endif; ?>
                <?php echo esc_html($n->message); ?>
                <div style="font-size:0.75rem;color:rgba(255,255,255,0.4);"><?php echo $n->created_at; ?></div>
                <?php if ($n->link): ?></a><?php endif; ?>
            </div>
        <?php endforeach; endif; ?>
    </div>
    <?php
    return ob_get_clean();
});


require_once get_template_directory() . '/includes/abs-ifreedom-v2.php';
require_once get_template_directory() . '/includes/abs-ifreedom-v2-admin.php';

add_filter('nonce_life', function() { return 3600; }); // 1 час