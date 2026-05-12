<?php
// Простейший тест AJAX
add_action('wp_ajax_test', function() {
    echo 'OK';
    exit;
});
add_action('wp_ajax_nopriv_test', function() {
    echo 'OK';
    exit;
});

// Симулируем запрос
if (isset($_GET['test'])) {
    do_action('wp_ajax_test');
    exit;
}