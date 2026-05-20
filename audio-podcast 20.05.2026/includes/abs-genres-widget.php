<?php
/**
 * abs-genres-widget.php - Виджет жанров книг
 */

class ABS_Genres_Widget extends WP_Widget {
    
    function __construct() {
        parent::__construct(
            'abs_genres_widget',
            'Жанры книг',
            array('description' => 'Список жанров из аудиокниг')
        );
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        echo $args['before_title'] . '📚 Жанры книг' . $args['after_title'];
        
        global $wpdb;
        $cache_table = $wpdb->prefix . 'abs_book_cache';
        $books = $wpdb->get_col("SELECT book_data FROM $cache_table");
        
        $genres = array();
        foreach ($books as $book_data) {
            $data = json_decode($book_data, true);
            $book_genres = $data['media']['metadata']['genres'] ?? array();
            foreach ($book_genres as $genre) {
                if (!in_array($genre, $genres) && !empty($genre)) {
                    $genres[] = $genre;
                }
            }
        }
        sort($genres);
        
        echo '<ul class="abs-genres-list">';
        foreach ($genres as $genre) {
            echo '<li><a href="/catalog?genre=' . urlencode($genre) . '">' . esc_html($genre) . '</a></li>';
        }
        echo '</ul>';
        
        echo $args['after_widget'];
    }
}

// Регистрируем виджет
add_action('widgets_init', function() {
    register_widget('ABS_Genres_Widget');
});