<?php
//about theme info
add_action( 'admin_menu', 'audio_podcast_gettingstarted' );
function audio_podcast_gettingstarted() {
	add_theme_page( esc_html__('About Audio Podcast', 'audio-podcast'), esc_html__('Theme Demo Importer', 'audio-podcast'), 'edit_theme_options', 'audio_podcast_guide', 'audio_podcast_mostrar_guide');
}

/* Add to Dashboard main menu */
function audio_podcast_dashboard_menu() {
    add_menu_page(
        esc_html__( 'Audio Podcast', 'audio-podcast' ), // Page title
        esc_html__( 'Audio Podcast', 'audio-podcast' ), // Menu title
        'manage_options',                            // Capability
        'audio_podcast_guide',                        // Menu slug
        'audio_podcast_mostrar_guide',                // Callback
        get_template_directory_uri() . '/inc/getstart/images/menu-icon.svg', // Image icon
        59                                           // Position
    );
}
add_action( 'admin_menu', 'audio_podcast_dashboard_menu' );

// Add a Custom CSS file to WP Admin Area
function audio_podcast_admin_theme_style() {
	wp_enqueue_style('audio-podcast-custom-admin-style', esc_url(get_template_directory_uri()) . '/inc/getstart/getstart.css');
	wp_enqueue_script('audio-podcast-tabs', esc_url(get_template_directory_uri()) . '/inc/getstart/js/tab.js');

	// Admin notice code START
	wp_register_script('audio-podcast-notice', esc_url(get_template_directory_uri()) . '/inc/getstart/js/notice.js', array('jquery'), time(), true);
	wp_enqueue_script('audio-podcast-notice');
	// Admin notice code END
}
add_action('admin_enqueue_scripts', 'audio_podcast_admin_theme_style');

//guidline for about theme
function audio_podcast_mostrar_guide() { 
	//custom function about theme customizer
	$audio_podcast_return = add_query_arg( array()) ;
	$audio_podcast_theme = wp_get_theme( 'audio-podcast' );
?>

<div class="wrapper-info">
	<div class="tab-sec">
    	
    	<div class="tab">
    		<button class="tablinks" onclick="audio_podcast_open_tab(event, 'theme_offer')"><?php esc_html_e( 'Demo Import', 'audio-podcast' ); ?></button>
			<button class="tablinks" onclick="audio_podcast_open_tab(event, 'lite_theme')"><?php esc_html_e( 'Setup With Customizer', 'audio-podcast' ); ?></button>
			<button class="tablinks" onclick="audio_podcast_open_tab(event, 'theme_pro')"><?php esc_html_e( 'Get Premium', 'audio-podcast' ); ?></button>
  			<button class="tablinks" onclick="audio_podcast_open_tab(event, 'free_pro')"><?php esc_html_e( 'Free VS Premium', 'audio-podcast' ); ?></button>
  			<button class="tablinks" onclick="audio_podcast_open_tab(event, 'get_bundle')"><?php esc_html_e( 'WP Theme Bundle', 'audio-podcast' ); ?></button>
		</div>

		<?php 
			$audio_podcast_plugin_custom_css = '';
			if(class_exists('Ibtana_Visual_Editor_Menu_Class')){
				$audio_podcast_plugin_custom_css ='display: block';
			}
		?>

		<div id="theme_offer" class="tabcontent open">
			<div class="demo-content">
				<div class="demo-text">
					<?php 
					/* Get Started. */ 
					require get_parent_theme_file_path( '/inc/getstart/demo-content.php' );
				 	?>
				</div>
				
			 	<img src="<?php echo esc_url(get_template_directory_uri()); ?>/inc/getstart/images/responsive.png" alt="" class="resp-img" />
			</div> 	
		</div>
		<div id="lite_theme" class="tabcontent">
			<div class="lite-theme-tab" style="<?php echo esc_attr($audio_podcast_plugin_custom_css); ?>">
				<h3><?php esc_html_e( 'Audio Podcast', 'audio-podcast' ); ?></h3>
				<hr class="h3hr">
				<p><?php esc_html_e('Audio Podcast is a stunningly crafted theme with a minimal design for representing audio, DJ music, music recording, live podcast, fm music, music player, music streaming, seo Podcasts and video as well as audio streaming platforms such as Spotify. Podcasters, audio player, streaming,Podcasting, Media, Broadcast, Marketing, Interviews, radio players will find this theme extremely useful. This expert-level theme will serve as a multipurpose theme as modifying this theme is a breeze with the help of the personalization options given. To give your website a much-sophisticated look, it uses retina-ready pictures and imagery and its responsive design makes all the elements get perfectly resized according to the device’s screen. Being a free theme, no quality has been compromised as far as this theme’s coding and other things are concerned. The Banner looks wonderful on the screen and thanks to the codes that are optimized and SEO friendly, the website is going to work smoothly and can be spotted in top ranks in the search engines. Secure and clean codes are going to make the design lightweight giving faster page load time. Having an animated layout will result in more user engagement. And with more social media icons, reaching out to a huge audience base gets easier. This modern theme is made translation-ready and is developed using a powerful Bootstrap framework.','audio-podcast'); ?></p>
				<div class="lite-info">
					<div class="col-left-inner">
				  		<h4><?php esc_html_e( 'Theme Documentation', 'audio-podcast' ); ?></h4>
						<p><?php esc_html_e( 'If you need any assistance regarding setting up and configuring the Theme, our documentation is there.', 'audio-podcast' ); ?></p>
						<div class="info-link">
							<a href="<?php echo esc_url( AUDIO_PODCAST_FREE_THEME_DOC ); ?>" target="_blank"> <?php esc_html_e( 'Documentation', 'audio-podcast' ); ?></a>
						</div>
						<hr>
						<h4><?php esc_html_e('Theme Customizer', 'audio-podcast'); ?></h4>
						<p> <?php esc_html_e('To begin customizing your website, start by clicking "Customize".', 'audio-podcast'); ?></p>
						<div class="info-link">
							<a target="_blank" href="<?php echo esc_url( admin_url('customize.php') ); ?>"><?php esc_html_e('Customizing', 'audio-podcast'); ?></a>
						</div>
						<hr>
						<h4><?php esc_html_e('Having Trouble, Need Support?', 'audio-podcast'); ?></h4>
						<p> <?php esc_html_e('Our dedicated team is well prepared to help you out in case of queries and doubts regarding our theme.', 'audio-podcast'); ?></p>
						<div class="info-link">
							<a href="<?php echo esc_url( AUDIO_PODCAST_SUPPORT ); ?>" target="_blank"><?php esc_html_e('Support Forum', 'audio-podcast'); ?></a>
						</div>
						<hr>
						<h4><?php esc_html_e('Reviews & Testimonials', 'audio-podcast'); ?></h4>
						<p> <?php esc_html_e('All the features and aspects of this WordPress Theme are phenomenal. I\'d recommend this theme to all.', 'audio-podcast'); ?></p>
						<div class="info-link">
							<a href="<?php echo esc_url( AUDIO_PODCAST_REVIEW ); ?>" target="_blank"><?php esc_html_e('Reviews', 'audio-podcast'); ?></a>
						</div>

						<div class="link-customizer">
							<h4><?php esc_html_e( 'Link to customizer', 'audio-podcast' ); ?></h4>
							<div class="first-row">
								<div class="row-box">
									<div class="row-box1">
										<span class="dashicons dashicons-buddicons-buddypress-logo"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[control]=custom_logo') ); ?>" target="_blank"><?php esc_html_e('Upload your logo','audio-podcast'); ?></a>
									</div>
									<div class="row-box2">
										<span class="dashicons dashicons-category"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[section]=audio_podcast_top_bar') ); ?>" target="_blank"><?php esc_html_e('Header','audio-podcast'); ?></a>
									</div>
								</div>

								<div class="row-box">
									<div class="row-box1">
										<span class="dashicons dashicons-slides"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[section]=audio_podcast_slider_section') ); ?>" target="_blank"><?php esc_html_e('Slider Settings','audio-podcast'); ?></a>
									</div>
									<div class="row-box2">
										<span class="dashicons dashicons-category"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[section]=audio_podcast_top_charts_section') ); ?>" target="_blank"><?php esc_html_e('Top Charts Section','audio-podcast'); ?></a>
									</div>
								</div>
							
								<div class="row-box">
									<div class="row-box1">
										<span class="dashicons dashicons-category"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[section]=audio_podcast_right_sidebar_section') ); ?>" target="_blank"><?php esc_html_e('Right Sidebar Section','audio-podcast'); ?></a>
									</div>
									<div class="row-box2">
										<span class="dashicons dashicons-menu"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[panel]=nav_menus') ); ?>" target="_blank"><?php esc_html_e('Menus','audio-podcast'); ?></a>
									</div>
								</div>
								
								<div class="row-box">
									<div class="row-box1">
										<span class="dashicons dashicons-admin-generic"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[section]=audio_podcast_left_right') ); ?>" target="_blank"><?php esc_html_e('General Settings','audio-podcast'); ?></a>
									</div>
									<div class="row-box2">
										<span class="dashicons dashicons-format-gallery"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[section]=audio_podcast_post_settings') ); ?>" target="_blank"><?php esc_html_e('Post settings','audio-podcast'); ?></a>
									</div>
								</div>

								<div class="row-box">
									<div class="row-box1">
										<span class="dashicons dashicons-screenoptions"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[panel]=widgets') ); ?>" target="_blank"><?php esc_html_e('Footer Widget','audio-podcast'); ?></a>
									</div>
									<div class="row-box2">
										<span class="dashicons dashicons-text-page"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[section]=audio_podcast_footer') ); ?>" target="_blank"><?php esc_html_e('Footer Text','audio-podcast'); ?></a>
									</div>
								</div>
							</div>
						</div>
				  	</div>
					<div class="col-right-inner">
						<h4 class="page-template"><?php esc_html_e('How to set up Home Page Template','audio-podcast'); ?></h4>
						<p><?php esc_html_e('Follow these instructions to setup Home page.','audio-podcast'); ?></p>
	                  	<p><span class="strong"><?php esc_html_e('1. Create a new page :','audio-podcast'); ?></span><?php esc_html_e(' Go to ','audio-podcast'); ?>
						  	<b><?php esc_html_e(' Dashboard >> Pages >> Add New Page','audio-podcast'); ?></b></p>
	                  	<p><?php esc_html_e('Name it as "Home" then select the template "Custom Home Page".','audio-podcast'); ?></p>
	                  	<img src="<?php echo esc_url(get_template_directory_uri()); ?>/inc/getstart/images/home-page-template.png" alt="" />
	                  	<p><span class="strong"><?php esc_html_e('2. Set the front page:','audio-podcast'); ?></span><?php esc_html_e(' Go to ','audio-podcast'); ?>
						  	<b><?php esc_html_e(' Settings >> Reading ','audio-podcast'); ?></b></p>
					  	<p><?php esc_html_e('Select the option of Static Page, now select the page you created to be the homepage, while another page to be your default page.','audio-podcast'); ?></p>
	                  	<img src="<?php echo esc_url(get_template_directory_uri()); ?>/inc/getstart/images/set-front-page.png" alt="" />
	                  	<p><?php esc_html_e(' Once you are done with setup, then follow the','audio-podcast'); ?> <a class="doc-links" href="<?php echo esc_url( AUDIO_PODCAST_FREE_THEME_DOC ); ?>" target="_blank"><?php esc_html_e('Documentation','audio-podcast'); ?></a></p>
				  	</div>

				</div>
			  	
			</div>
		</div>

		<div id="theme_pro" class="tabcontent">		  	
			<div class="pro-info">
				<div class="col-left-pro">
					<h3><?php esc_html_e( 'Premium Theme Information', 'audio-podcast' ); ?></h3>
					<hr class="h3hr">
			    	<p><?php esc_html_e('Audio Podcast WordPress Theme is an excellent theme crafted especially for podcasters, music brands, audio, and any other kind of multimedia website. An impressive and modern homepage puts your media in front and makes it become the center of attraction for your visitors. It offers a dark and light color scheme and plenty of color options to help you obtain the desired look. The visitors of your website can check your site while on the go and this is possible because of the theme’s mobile-friendly design. It supports multiple media formats and has all the necessary material to get started. WP Audio Podcast WordPress Theme comes with a translatable design supporting WPML and RTL languages making your website ready for an international audience as well. You cannot really miss the professional appeal that this theme brings making your website look as if it is crafted by an experienced web developer.','audio-podcast'); ?></p>
			    	<div class="pro-links">
				    	<a href="<?php echo esc_url( AUDIO_PODCAST_LIVE_DEMO ); ?>" target="_blank" class="demo-btn"><?php esc_html_e('Live Demo', 'audio-podcast'); ?></a>
						<a href="<?php echo esc_url( AUDIO_PODCAST_BUY_NOW ); ?>" target="_blank" class="prem-btn"><?php esc_html_e('Buy Premium', 'audio-podcast'); ?></a>
						<a href="<?php echo esc_url( AUDIO_PODCAST_PRO_DOC ); ?>" target="_blank" class="doc-btn"><?php esc_html_e('Documentation', 'audio-podcast'); ?></a>
					</div>
			    </div>
			    <div class="col-right-pro scroll-image-wrapper">
			    	<img src="<?php echo esc_url(get_template_directory_uri()); ?>/inc/getstart/images/pro-theme.jpg" alt="" class="pro-img" />		    	
			    </div>
			</div>		    
		</div>

		<div id="free_pro" class="tabcontent">
		  	<div class="featurebox">
			    <h3><?php esc_html_e( 'Theme Features', 'audio-podcast' ); ?></h3>
				<hr class="h3hr">
				<div class="table-image">
					<table class="tablebox">
						<thead>
							<tr>
								<th> <?php esc_html_e('Features', 'audio-podcast'); ?></th>
								<th><?php esc_html_e('Free Themes', 'audio-podcast'); ?></th>
								<th><?php esc_html_e('Premium Themes', 'audio-podcast'); ?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><?php esc_html_e('Theme Customization', 'audio-podcast'); ?></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e('Responsive Design', 'audio-podcast'); ?></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Logo Upload', 'audio-podcast'); ?></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e('Social Media Links', 'audio-podcast'); ?></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Banner Settings', 'audio-podcast'); ?></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Template Pages', 'audio-podcast'); ?></td>
								<td class="table-img"><?php esc_html_e('3', 'audio-podcast'); ?></td>
								<td class="table-img"><?php esc_html_e('10', 'audio-podcast'); ?></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e('Home Page Template', 'audio-podcast'); ?></td>
								<td class="table-img"><?php esc_html_e('1', 'audio-podcast'); ?></td>
								<td class="table-img"><?php esc_html_e('1', 'audio-podcast'); ?></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Theme sections', 'audio-podcast'); ?></td>
								<td class="table-img"><?php esc_html_e('2', 'audio-podcast'); ?></td>
								<td class="table-img"><?php esc_html_e('13', 'audio-podcast'); ?></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e('Contact us Page Template / Support Templates', 'audio-podcast'); ?></td>
								<td class="table-img">0</td>
								<td class="table-img"><?php esc_html_e('1', 'audio-podcast'); ?></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Blog Templates & Layout', 'audio-podcast'); ?></td>
								<td class="table-img">0</td>
								<td class="table-img"><?php esc_html_e('3(Full width/Left/Right Sidebar)', 'audio-podcast'); ?></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e('Page Templates & Layout', 'audio-podcast'); ?></td>
								<td class="table-img">0</td>
								<td class="table-img"><?php esc_html_e('3(Left/Right Sidebar)', 'audio-podcast'); ?></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Color Pallete For Particular Sections', 'audio-podcast'); ?></td>
								<td class="table-img"><span class="dashicons dashicons-no"></span></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e('Global Color Option', 'audio-podcast'); ?></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Section Reordering', 'audio-podcast'); ?></td>
								<td class="table-img"><span class="dashicons dashicons-no"></span></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e('Demo Importer', 'audio-podcast'); ?></td>
								<td class="table-img"><span class="dashicons dashicons-no"></span></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Allow To Set Site Title, Tagline, Logo', 'audio-podcast'); ?></td>
								<td class="table-img"><span class="dashicons dashicons-no"></span></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e('Enable Disable Options On All Sections, Logo', 'audio-podcast'); ?></td>
								<td class="table-img"><span class="dashicons dashicons-no"></span></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Full Documentation', 'audio-podcast'); ?></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e('Latest WordPress Compatibility', 'audio-podcast'); ?></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e('Support 3rd Party Plugins', 'audio-podcast'); ?></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Secure and Optimized Code', 'audio-podcast'); ?></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e('Exclusive Functionalities', 'audio-podcast'); ?></td>
								<td class="table-img"><span class="dashicons dashicons-no"></span></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Section Enable / Disable', 'audio-podcast'); ?></td>
								<td class="table-img"><span class="dashicons dashicons-no"></span></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e('Section Google Font Choices', 'audio-podcast'); ?></td>
								<td class="table-img"><span class="dashicons dashicons-no"></span></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Video Gallery', 'audio-podcast'); ?></td>
								<td class="table-img"><span class="dashicons dashicons-no"></span></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e('Simple & Mega Menu Option', 'audio-podcast'); ?></td>
								<td class="table-img"><span class="dashicons dashicons-no"></span></td>
								<td class="table-img"><span class="dashicons dashicons-no"></span></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Support to add custom CSS / JS ', 'audio-podcast'); ?></td>
								<td class="table-img"><span class="dashicons dashicons-no"></span></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e('Shortcodes', 'audio-podcast'); ?></td>
								<td class="table-img"><span class="dashicons dashicons-no"></span></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Custom Background, Colors, Header, Logo & Menu', 'audio-podcast'); ?></td>
								<td class="table-img"><span class="dashicons dashicons-no"></span></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e('Premium Membership', 'audio-podcast'); ?></td>
								<td class="table-img"><span class="dashicons dashicons-no"></span></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Budget Friendly Value', 'audio-podcast'); ?></td>
								<td class="table-img"><span class="dashicons dashicons-no"></span></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e('Priority Error Fixing', 'audio-podcast'); ?></td>
								<td class="table-img"><span class="dashicons dashicons-no"></span></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Custom Feature Addition', 'audio-podcast'); ?></td>
								<td class="table-img"><span class="dashicons dashicons-no"></span></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e('All Access Theme Pass', 'audio-podcast'); ?></td>
								<td class="table-img"><span class="dashicons dashicons-no"></span></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Seamless Customer Support', 'audio-podcast'); ?></td>
								<td class="table-img"><span class="dashicons dashicons-no"></span></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Audio Podcast ', 'audio-podcast'); ?></td>
								<td class="table-img"><span class="dashicons dashicons-no"></span></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Detail Services', 'audio-podcast'); ?></td>
								<td class="table-img"><span class="dashicons dashicons-no"></span></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
							</tr>
							<tr>
								<td><?php esc_html_e('About Business Page', 'audio-podcast'); ?></td>
								<td class="table-img"><span class="dashicons dashicons-no"></span></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Team Member Page', 'audio-podcast'); ?></td>
								<td class="table-img"><span class="dashicons dashicons-no"></span></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Project Description Page', 'audio-podcast'); ?></td>
								<td class="table-img"><span class="dashicons dashicons-no"></span></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Support Page', 'audio-podcast'); ?></td>
								<td class="table-img"><span class="dashicons dashicons-no"></span></td>
								<td class="table-img"><span class="dashicons dashicons-saved"></span></td>
							</tr>
							<tr>
								<td></td>
								<td class="table-img"></td>
								<td class="update-link"><a href="<?php echo esc_url( AUDIO_PODCAST_BUY_NOW ); ?>" target="_blank"><?php esc_html_e('Upgrade to Pro', 'audio-podcast'); ?></a></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>

		<div id="get_bundle" class="tabcontent">	
			<div class="bundle-info">
				<div class="col-left-pro">
			   		<h3><?php esc_html_e( 'WP Theme Bundle', 'audio-podcast' ); ?></h3>
			   		<hr class="h3hr">
			    	<p><?php esc_html_e('Enhance your website effortlessly with our WP Theme Bundle. Get access to 400+ premium WordPress themes and 5+ powerful plugins, all designed to meet diverse business needs. Enjoy seamless integration with any plugins, ultimate customization flexibility, and regular updates to keep your site current and secure. Plus, benefit from our dedicated customer support, ensuring a smooth and professional web experience.','audio-podcast'); ?></p>
			    	<div class="feature">
			    		<h4><?php esc_html_e( 'Features:', 'audio-podcast' ); ?></h4>
			    		<p><img src="<?php echo esc_url(get_template_directory_uri()); ?>/inc/getstart/images/tick.png" alt="" /><?php esc_html_e('400+ Premium Themes & 5+ Plugins.', 'audio-podcast'); ?></p>
			    		<p><img src="<?php echo esc_url(get_template_directory_uri()); ?>/inc/getstart/images/tick.png" alt="" /><?php esc_html_e('Seamless Integration.', 'audio-podcast'); ?></p>
			    		<p><img src="<?php echo esc_url(get_template_directory_uri()); ?>/inc/getstart/images/tick.png" alt="" /><?php esc_html_e('Customization Flexibility.', 'audio-podcast'); ?></p>
			    		<p><img src="<?php echo esc_url(get_template_directory_uri()); ?>/inc/getstart/images/tick.png" alt="" /><?php esc_html_e('Regular Updates.', 'audio-podcast'); ?></p>
			    		<p><img src="<?php echo esc_url(get_template_directory_uri()); ?>/inc/getstart/images/tick.png" alt="" /><?php esc_html_e('Dedicated Support.', 'audio-podcast'); ?></p>
			    	</div>
			    	<p><?php esc_html_e('Upgrade now and give your website the professional edge it deserves, all at an unbeatable price of $99!', 'audio-podcast'); ?></p>
			    	<div class="pro-links">
						<a href="<?php echo esc_url( AUDIO_PODCAST_THEME_BUNDLE_BUY_NOW ); ?>" target="_blank" class="bundle-buy"><?php esc_html_e('Get Bundle', 'audio-podcast'); ?></a>
						<a href="<?php echo esc_url( AUDIO_PODCAST_THEME_BUNDLE_DOC ); ?>" target="_blank" class="bundle-doc"><?php esc_html_e('Documentation', 'audio-podcast'); ?></a>
					</div>
			   	</div>
			   	<div class="col-right-pro scroll-image-wrapper">
			    	<img src="<?php echo esc_url(get_template_directory_uri()); ?>/inc/getstart/images/bundle.jpg" alt="" />
			   	</div>
			</div>	  	
		   			    
		</div>
	</div>
	<div class="coupen-code-section">
		<div class="sshot-section">
			<div class="sshot-inner">
				<h2><?php esc_html_e( 'Welcome To Audio Podcast,', 'audio-podcast' ); ?>  </h2>
				<div class="on-pro">
					<span class="version"><?php esc_html_e( 'Version', 'audio-podcast' ); ?>: <?php echo esc_html($audio_podcast_theme['Version']);?></span>
					<span class="coupon-code"><?php esc_html_e('Get 20% Of On Pro Theme-Use Code: ','audio-podcast'); ?><span class="code-highlight"><?php esc_html_e('VWPRO20','audio-podcast'); ?></span>
				</div>
		    	<p><?php esc_html_e('All Our Wordpress Themes Are Modern, Minimalist, 100% Responsive, Seo-Friendly,Feature-Rich, And Multipurpose That Best Suit Designers, Bloggers And Other Professionals Who Are Working In The Creative Fields.','audio-podcast'); ?></p>
		    	<div class="btn-section">
			    	<div class="proo-links">
				    	<a href="<?php echo esc_url( AUDIO_PODCAST_LIVE_DEMO ); ?>" target="_blank" class="demo-btn"><?php esc_html_e('Live Demo', 'audio-podcast'); ?></a>
						<a href="<?php echo esc_url( AUDIO_PODCAST_BUY_NOW ); ?>" target="_blank" class="prem-btn"><?php esc_html_e('Buy Premium', 'audio-podcast'); ?></a>
						<a href="<?php echo esc_url( AUDIO_PODCAST_PRO_DOC ); ?>" target="_blank" class="doc-btn"><?php esc_html_e('Documentation', 'audio-podcast'); ?></a>
						
					</div>
			    	
			    </div>
			</div>
	    	<div class="bundle-banner">
	    		<div class="bundle-img">
	    			<img src="<?php echo esc_url(get_template_directory_uri()); ?>/inc/getstart/images/bundle-notice.png" alt="" />
	    		</div>
	    		<div class="bundle-text">
		  			<h2><?php esc_html_e('WP THEME BUNDLE','audio-podcast'); ?></h2>
					<h4><?php esc_html_e('Get Access to 400+ Premium WordPress Themes At Just $99','audio-podcast'); ?></h4>
					<div class="bundle-button">
			  			<a href="<?php echo esc_url( 'https://www.vwthemes.com/discount/FREEBREF?redirect=/products/wp-theme-bundle'); ?>" target="_blank"><?php esc_html_e('Get 10% OFF On Bundle', 'audio-podcast'); ?></a>
			  		</div>
		  		</div>
		  		
	    	</div>
	    </div>
	    <div class="coupen-section">
	    	<div class="logo-section">
			  	<img src="<?php echo esc_url(get_template_directory_uri()); ?>/screenshot.png" alt="" />
		  	</div>
		  	<div class="logo-right">	
		  		<div class="logo-text">
		  			<h2><?php esc_html_e('GET PRO','audio-podcast'); ?></h2>
					<h4><?php esc_html_e('20% Off','audio-podcast'); ?></h4>
		  		</div>						
			</div>
	    </div>
	</div>
      
</div>

<?php } ?>