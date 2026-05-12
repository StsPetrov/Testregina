<div class="theme-offer">
	<?php 
        // Check if the demo import has been completed
        $audio_podcast_demo_import_completed = get_option('audio_podcast_demo_import_completed', false);

        // If the demo import is completed, display the "View Site" button
        if ($audio_podcast_demo_import_completed) {
        echo '<p class="notice-text">' . esc_html__('Your demo import has been completed successfully.', 'audio-podcast') . '</p>';
        echo '<span><a href="' . esc_url(home_url()) . '" class="button button-primary site-btn" target="_blank">' . esc_html__('View Site', 'audio-podcast') . '</a></span>';echo '<span><a href="'. esc_url(admin_url('customize.php') ) .'" class="button button-primary demo-btn" target=_blank>'. esc_html__( 'Customize Your Site', 'audio-podcast' ) .'</a></span>';
        echo '<span><a href="'. esc_url( 'https://preview.vwthemesdemo.com/docs/free-audio-podcast/' ) .'" class="button button-primary doc-btn" target=_blank>'. esc_html__( 'Free Theme Documentation', 'audio-podcast' ) .'</a></span>';    
    }

		//POST and update the customizer and other related data
        if (isset($_POST['submit'])) {
            // Check if ibtana visual editor is installed and activated
            if (!is_plugin_active('ibtana-visual-editor/plugin.php')) {
              // Install the plugin if it doesn't exist
              $audio_podcast_plugin_slug = 'ibtana-visual-editor';
              $audio_podcast_plugin_file = 'ibtana-visual-editor/plugin.php';

              // Check if plugin is installed
              $audio_podcast_installed_plugins = get_plugins();
              if (!isset($audio_podcast_installed_plugins[$audio_podcast_plugin_file])) {
                  include_once(ABSPATH . 'wp-admin/includes/plugin-install.php');
                  include_once(ABSPATH . 'wp-admin/includes/file.php');
                  include_once(ABSPATH . 'wp-admin/includes/misc.php');
                  include_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');

                  // Install the plugin
                  $audio_podcast_upgrader = new Plugin_Upgrader();
                  $audio_podcast_upgrader->install('https://downloads.wordpress.org/plugin/ibtana-visual-editor.latest-stable.zip');
              }
              // Activate the plugin
              activate_plugin($audio_podcast_plugin_file);
            } 

            if (!is_plugin_active('audioigniter/audioigniter.php')) {
                // Plugin details
                $audio_podcast_audioigniter_plugin_slug = 'audioigniter';
                $audio_podcast_audioigniter_plugin_file = 'audioigniter/audioigniter.php';
            
                // Check if plugin is installed
                $audio_podcast_installed_plugins = get_plugins();
                if (!isset($audio_podcast_installed_plugins[$audio_podcast_audioigniter_plugin_file])) {
                    // Include necessary WordPress files for plugin installation
                    include_once(ABSPATH . 'wp-admin/includes/plugin-install.php');
                    include_once(ABSPATH . 'wp-admin/includes/file.php');
                    include_once(ABSPATH . 'wp-admin/includes/misc.php');
                    include_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
            
                    // Install the plugin
                    $audio_podcast_plugin_upgrader = new Plugin_Upgrader();
                    $audio_podcast_plugin_upgrader->install('https://downloads.wordpress.org/plugin/audioigniter.latest-stable.zip');
                }
            
                // Activate the plugin
                activate_plugin($audio_podcast_audioigniter_plugin_file);
            } 

            // ------- Create Nav Menu --------
            $audio_podcast_menuname = 'Main Menus';
            $audio_podcast_bpmenulocation = 'primary';
            $audio_podcast_menu_exists = wp_get_nav_menu_object($audio_podcast_menuname);

            if (!$audio_podcast_menu_exists) {
                $audio_podcast_menu_id = wp_create_nav_menu($audio_podcast_menuname);

                // Create Home Page
                $audio_podcast_home_title = 'Home';
                $audio_podcast_home = array(
                    'post_type' => 'page',
                    'post_title' => $audio_podcast_home_title,
                    'post_content' => '',
                    'post_status' => 'publish',
                    'post_author' => 1,
                    'post_slug' => 'home'
                );
                $audio_podcast_home_id = wp_insert_post($audio_podcast_home);
                // Assign Home Page Template
                add_post_meta($audio_podcast_home_id, '_wp_page_template', 'page-template/custom-home-page.php');
                // Update options to set Home Page as the front page
                update_option('page_on_front', $audio_podcast_home_id);
                update_option('show_on_front', 'page');
                // Add Home Page to Menu
                wp_update_nav_menu_item($audio_podcast_menu_id, 0, array(
                    'menu-item-title' => __('Home', 'audio-podcast'),
                    'menu-item-classes' => 'home',
                    'menu-item-url' => home_url('/'),
                    'menu-item-status' => 'publish',
                    'menu-item-object-id' => $audio_podcast_home_id,
                    'menu-item-object' => 'page',
                    'menu-item-type' => 'post_type'
                ));

                // Create Pages Page with Dummy Content
                $audio_podcast_pages_title = 'Pages';
                $audio_podcast_pages_content = '
                <p>Explore all the pages we have on our website. Find information about our services, company, and more.</p>

                 Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry standard dummy text ever since the 1500, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960 with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.<br> 

                  All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary, making this the first true generator on the Internet. It uses a dictionary of over 200 Latin words, combined with a handful of model sentence structures, to generate Lorem Ipsum which looks reasonable. The generated Lorem Ipsum is therefore always free from repetition, injected humour, or non-characteristic words etc.';
                $audio_podcast_pages = array(
                    'post_type' => 'page',
                    'post_title' => $audio_podcast_pages_title,
                    'post_content' => $audio_podcast_pages_content,
                    'post_status' => 'publish',
                    'post_author' => 1,
                    'post_slug' => 'pages'
                );
                $audio_podcast_pages_id = wp_insert_post($audio_podcast_pages);
                // Add Pages Page to Menu
                wp_update_nav_menu_item($audio_podcast_menu_id, 0, array(
                    'menu-item-title' => __('Pages', 'audio-podcast'),
                    'menu-item-classes' => 'pages',
                    'menu-item-url' => home_url('/pages/'),
                    'menu-item-status' => 'publish',
                    'menu-item-object-id' => $audio_podcast_pages_id,
                    'menu-item-object' => 'page',
                    'menu-item-type' => 'post_type'
                ));

                // Create Trending  Page with Dummy Content
                $audio_podcast_about_title = 'Trending ';
                $audio_podcast_about_content = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam...<br>

                         Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry standard dummy text ever since the 1500, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960 with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.<br> 

                            There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which dont look even slightly believable. If you are going to use a passage of Lorem Ipsum, you need to be sure there isnt anything embarrassing hidden in the middle of text.<br> 

                            All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary, making this the first true generator on the Internet. It uses a dictionary of over 200 Latin words, combined with a handful of model sentence structures, to generate Lorem Ipsum which looks reasonable. The generated Lorem Ipsum is therefore always free from repetition, injected humour, or non-characteristic words etc.';
                $audio_podcast_about = array(
                    'post_type' => 'page',
                    'post_title' => $audio_podcast_about_title,
                    'post_content' => $audio_podcast_about_content,
                    'post_status' => 'publish',
                    'post_author' => 1,
                    'post_slug' => 'about-us'
                );
                $audio_podcast_about_id = wp_insert_post($audio_podcast_about);
                // Add Trending  Page to Menu
                wp_update_nav_menu_item($audio_podcast_menu_id, 0, array(
                    'menu-item-title' => __('Trending ', 'audio-podcast'),
                    'menu-item-classes' => 'about-us',
                    'menu-item-url' => home_url('/about-us/'),
                    'menu-item-status' => 'publish',
                    'menu-item-object-id' => $audio_podcast_about_id,
                    'menu-item-object' => 'page',
                    'menu-item-type' => 'post_type'
                ));

                 // Create Upcoming  Page with Dummy Content
                $audio_podcast_about_title = 'Upcoming ';
                $audio_podcast_about_content = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam...<br>

                         Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry standard dummy text ever since the 1500, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960 with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.<br> 

                            There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which dont look even slightly believable. If you are going to use a passage of Lorem Ipsum, you need to be sure there isnt anything embarrassing hidden in the middle of text.<br> 

                            All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary, making this the first true generator on the Internet. It uses a dictionary of over 200 Latin words, combined with a handful of model sentence structures, to generate Lorem Ipsum which looks reasonable. The generated Lorem Ipsum is therefore always free from repetition, injected humour, or non-characteristic words etc.';
                $audio_podcast_about = array(
                    'post_type' => 'page',
                    'post_title' => $audio_podcast_about_title,
                    'post_content' => $audio_podcast_about_content,
                    'post_status' => 'publish',
                    'post_author' => 1,
                    'post_slug' => 'about-us'
                );
                $audio_podcast_about_id = wp_insert_post($audio_podcast_about);
                // Add Upcoming  Page to Menu
                wp_update_nav_menu_item($audio_podcast_menu_id, 0, array(
                    'menu-item-title' => __('Upcoming ', 'audio-podcast'),
                    'menu-item-classes' => 'about-us',
                    'menu-item-url' => home_url('/about-us/'),
                    'menu-item-status' => 'publish',
                    'menu-item-object-id' => $audio_podcast_about_id,
                    'menu-item-object' => 'page',
                    'menu-item-type' => 'post_type'
                ));

                 // Create Contact  Page with Dummy Content
                $audio_podcast_about_title = 'Contact ';
                $audio_podcast_about_content = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam...<br>

                         Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry standard dummy text ever since the 1500, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960 with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.<br> 

                            There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which dont look even slightly believable. If you are going to use a passage of Lorem Ipsum, you need to be sure there isnt anything embarrassing hidden in the middle of text.<br> 

                            All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary, making this the first true generator on the Internet. It uses a dictionary of over 200 Latin words, combined with a handful of model sentence structures, to generate Lorem Ipsum which looks reasonable. The generated Lorem Ipsum is therefore always free from repetition, injected humour, or non-characteristic words etc.';
                $audio_podcast_about = array(
                    'post_type' => 'page',
                    'post_title' => $audio_podcast_about_title,
                    'post_content' => $audio_podcast_about_content,
                    'post_status' => 'publish',
                    'post_author' => 1,
                    'post_slug' => 'about-us'
                );
                $audio_podcast_about_id = wp_insert_post($audio_podcast_about);
                // Add Contact  Page to Menu
                wp_update_nav_menu_item($audio_podcast_menu_id, 0, array(
                    'menu-item-title' => __('Contact ', 'audio-podcast'),
                    'menu-item-classes' => 'about-us',
                    'menu-item-url' => home_url('/about-us/'),
                    'menu-item-status' => 'publish',
                    'menu-item-object-id' => $audio_podcast_about_id,
                    'menu-item-object' => 'page',
                    'menu-item-type' => 'post_type'
                ));

                // Set the menu location if it's not already set
                if (!has_nav_menu($audio_podcast_bpmenulocation)) {
                    $locations = get_theme_mod('nav_menu_locations'); // Use 'nav_menu_locations' to get locations array
                    if (empty($locations)) {
                        $locations = array();
                    }
                    $locations[$audio_podcast_bpmenulocation] = $audio_podcast_menu_id;
                    set_theme_mod('nav_menu_locations', $locations);
                }               
        }

         
            // Set the demo import completion flag
    		update_option('audio_podcast_demo_import_completed', true);
    		// Display success message and "View Site" button
    		echo '<p class="notice-text">' . esc_html__('Your demo import has been completed successfully.', 'audio-podcast') . '</p>';
    		echo '<span><a href="' . esc_url(home_url()) . '" class="button button-primary site-btn" target="_blank">' . esc_html__('View Site', 'audio-podcast') . '</a></span>';echo '<span><a href="'. esc_url(admin_url('customize.php') ) .'" class="button button-primary demo-btn" target=_blank>'. esc_html__( 'Customize Your Site', 'audio-podcast' ) .'</a></span>';
            echo '<span><a href="'. esc_url( 'https://preview.vwthemesdemo.com/docs/free-audio-podcast/' ) .'" class="button button-primary doc-btn" target=_blank>'. esc_html__( 'Free Theme Documentation', 'audio-podcast' ) .'</a></span>';
            
            //end 


            // Top Bar //
            set_theme_mod( 'audio_podcast_topbar_text', 'Lorem ipsum dolor sit amet, consectetur' );  
            set_theme_mod( 'audio_podcast_topbar_support_link', '#' );
            set_theme_mod( 'audio_podcast_topbar_wishlist_link', '#' );
            set_theme_mod( 'audio_podcast_topbar_myaccount_link', '#' );

            // slider section start //      
            set_theme_mod( 'audio_podcast_slider_button_text', 'EXPLORE ALL' );
            set_theme_mod( 'audio_podcast_topbar_btn_link', '#' );

            for($audio_podcast_i=1;$audio_podcast_i<=3;$audio_podcast_i++){
               $audio_podcast_slider_title = 'THE INSPIRE PODCAST';
               $audio_podcast_slider_content = 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry standard dummy text ever since the 1500';
                  // Create post object
               $my_post = array(
               'post_title'    => wp_strip_all_tags( $audio_podcast_slider_title ),
               'post_content'  => $audio_podcast_slider_content,
               'post_status'   => 'publish',
               'post_type'     => 'page',
               );

               // Insert the post into the database
               $audio_podcast_post_id = wp_insert_post( $my_post );

               if ($audio_podcast_post_id) {
                 // Set the theme mod for the slider page
                 set_theme_mod('audio_podcast_slider_page' . $audio_podcast_i, $audio_podcast_post_id);

                  $audio_podcast_image_url = get_template_directory_uri().'/assets/images/slider'.$audio_podcast_i.'.png';

                $audio_podcast_image_id = media_sideload_image($audio_podcast_image_url, $audio_podcast_post_id, null, 'id');

                    if (!is_wp_error($audio_podcast_image_id)) {
                        // Set the downloaded image as the post's featured image
                        set_post_thumbnail($audio_podcast_post_id, $audio_podcast_image_id);
                    }
                }
            }  

            // Track Player Section
            for ($audio_podcast_i = 1; $audio_podcast_i <= 2; $audio_podcast_i++) {
                $audio_podcast_featured_album = array();
                $audio_podcast_content = "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.";

                // Create post object
                $audio_podcast_my_post = array(
                    'post_title'   => 'Featured-album-' . $audio_podcast_i,
                    'post_content' => $audio_podcast_content,
                    'post_status'  => 'publish',
                    'post_type'    => 'ai_playlist',
                );

                // Insert the post into the database
                $audio_podcast_bwt_post_id = wp_insert_post($audio_podcast_my_post);

                if (is_wp_error($audio_podcast_bwt_post_id)) {
                    add_action('admin_notices', function () {
                        echo '<div class="notice notice-error is-dismissible">
                            <p>Failed to create playlist post.</p>
                        </div>';
                    });
                    continue;
                }

                $audio_podcast_image_url = get_template_directory_uri() . '/assets/images/track.png';
                $audio_podcast_image_name = 'track_image_' . $audio_podcast_i . '.png';
                $audio_podcast_upload_dir = wp_upload_dir();

                // Get image data
                $audio_podcast_image_data = @file_get_contents($audio_podcast_image_url);
                if ($audio_podcast_image_data === false) {
                    add_action('admin_notices', function () use ($audio_podcast_image_url) {
                        echo '<div class="notice notice-error is-dismissible">
                            <p>Failed to fetch image: ' . esc_html($audio_podcast_image_url) . '</p>
                        </div>';
                    });
                    continue;
                }

                $audio_podcast_unique_file_name = wp_unique_filename($audio_podcast_upload_dir['path'], $audio_podcast_image_name);
                $audio_podcast_filename = basename($audio_podcast_unique_file_name);

                // Define file location
                $audio_podcast_file = wp_mkdir_p($audio_podcast_upload_dir['path']) ? $audio_podcast_upload_dir['path'] . '/' . $audio_podcast_filename : $audio_podcast_upload_dir['basedir'] . '/' . $audio_podcast_filename;

                // Create the image file on the server
                if ( ! function_exists( 'WP_Filesystem' ) ) {
                    require_once ABSPATH . 'wp-admin/includes/file.php';
                }

                WP_Filesystem();
                global $wp_filesystem;

                if ( $wp_filesystem ) {
                    $wp_filesystem->put_contents(
                        $audio_podcast_file,
                        $audio_podcast_image_data,
                        FS_CHMOD_FILE
                    );
                }

                // Check image file type
                $audio_podcast_wp_filetype = wp_check_filetype($audio_podcast_filename, null);
                $audio_podcast_attachment = array(
                    'post_mime_type' => $audio_podcast_wp_filetype['type'],
                    'post_title'     => sanitize_file_name($audio_podcast_filename),
                    'post_content'   => '',
                    'post_status'    => 'inherit',
                );

                // Create the attachment
                $audio_podcast_attach_id = wp_insert_attachment($audio_podcast_attachment, $audio_podcast_file, $audio_podcast_bwt_post_id);
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $audio_podcast_attach_data = wp_generate_attachment_metadata($audio_podcast_attach_id, $audio_podcast_file);
                wp_update_attachment_metadata($audio_podcast_attach_id, $audio_podcast_attach_data);

                $audio_podcast_player_url = get_template_directory_uri() . '/assets/images/audio/sample.mp3';

                // Add tracks
                $audio_podcast_featured_album_tracks = array();
                
                    $audio_podcast_featured_album_tracks[] = array(
                        'cover_id'               => $audio_podcast_attach_id,
                        'title'                  => 'Dream Your Moment',
                        'artist'                 => 'Ava Cornish',
                        'track_url'              => $audio_podcast_player_url,
                        'buy_link'               => '#',
                        'download_url'           => $audio_podcast_player_url,
                        'download_uses_track_url' => '0',
                    );
                

                // Save track data as metadata
                update_post_meta($audio_podcast_bwt_post_id, '_audioigniter_tracks', $audio_podcast_featured_album_tracks);

               
            }   

            // Create the 'Products' page if it doesn't exist
            $audio_podcast_page_query = new WP_Query(array(
                'post_type'      => 'page',
                'title'          => 'Page1',
                'post_status'    => 'publish',
                'posts_per_page' => 1
            ));

            if (!$audio_podcast_page_query->have_posts()) {
                $audio_podcast_page_title = 'Audio Track Page';
                $productpage = '[ai_playlist id="' . $audio_podcast_bwt_post_id . '"]';

                // Append the WooCommerce products shortcode to the content
                $audio_podcast_content = '';
                $audio_podcast_content .= do_shortcode($productpage);

                // Create the new page
                $audio_podcast_page = array(
                    'post_type'    => 'page',
                    'post_title'   => $audio_podcast_page_title,
                    'post_content' => $audio_podcast_content,
                    'post_status'  => 'publish',
                    'post_author'  => 1,
                    'post_slug'    => 'products'
                );

                // Insert the page and get its ID
                $audio_podcast_page_id = wp_insert_post($audio_podcast_page);

                // Store the page ID in theme mod
                if (!is_wp_error($audio_podcast_page_id)) {
                    set_theme_mod('audio_podcast_player_page', $audio_podcast_page_id);
                }
            }
            
        }
        
    ?>

    <form action="<?php echo esc_url(home_url()); ?>/wp-admin/themes.php?page=audio_podcast_guide" method="POST" onsubmit="return validate(this);">
        <?php if (!get_option('audio_podcast_demo_import_completed')) : ?>
             <form method="post">   
            <p class="run-import-text"><?php esc_html_e('Click On The Below Run Importer Button To Import Demo Content Of audio podcast', 'audio-podcast'); ?></p>
                <p><?php esc_html_e('Please back up your website if it’s already live with data. This importer will overwrite your existing settings with the new customizer values for audio podcast', 'audio-podcast'); ?></p>
                <input class="run-import" type="submit" name="submit" value="<?php esc_attr_e('Run Importer', 'audio-podcast'); ?>" class="button button-primary button-large">
        </form>
        <?php endif; ?>
        <div id="spinner" style="display:none;">         
            <img src="<?php echo esc_url(get_template_directory_uri()); ?>/assets/images/spinner.png" alt="" />
        </div>
    </form>
    <script type="text/javascript">
        function validate(form) {
            if (confirm("Do you really want to import the theme demo content?")) {
                // Show the spinner
                document.getElementById('spinner').style.display = 'block';
                // Allow the form to be submitted
                return true;
            } 
            else {
                return false;
            }
        }
    </script>
</div>

