// Страница заказов в админке
add_action('admin_menu', function() {
    add_menu_page('Заказы на озвучку', '🎙️ Заказы', 'manage_options', 'abs-voice-orders', 'abs_voice_orders_page', 'dashicons-microphone', 31);
});

function abs_voice_orders_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'abs_voice_orders';
    
    // Создаём таблицу если нет
    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
        $wpdb->query("CREATE TABLE $table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id VARCHAR(100) NOT NULL,
            ranobe_id BIGINT NOT NULL,
            book_title VARCHAR(255),
            chapters_count INT DEFAULT 0,
            amount DECIMAL(10,2),
            customer VARCHAR(100),
            status ENUM('pending','paid','voicing','done') DEFAULT 'pending',
            payment_id VARCHAR(100),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    }
    
    // Сохранение статуса
    if (isset($_POST['update_status']) && isset($_POST['order_id'])) {
        $wpdb->update($table, ['status' => sanitize_text_field($_POST['status'])], ['id' => intval($_POST['order_id'])]);
        echo '<div class="notice notice-success"><p>Статус обновлён</p></div>';
    }
    
    $orders = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT 50");
    
    ?>
    <div class="wrap">
        <h1>🎙️ Заказы на озвучку</h1>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Книга</th>
                    <th>Глав</th>
                    <th>Сумма</th>
                    <th>Клиент</th>
                    <th>Статус</th>
                    <th>Дата</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="7">Заказов пока нет</td></tr>
                <?php else: foreach ($orders as $order): ?>
                    <tr>
                        <td><?php echo $order->id; ?></td>
                        <td><a href="<?php echo get_permalink($order->ranobe_id); ?>" target="_blank"><?php echo esc_html($order->book_title); ?></a></td>
                        <td><?php echo $order->chapters_count; ?></td>
                        <td><?php echo $order->amount; ?> ₽</td>
                        <td><?php echo esc_html($order->customer); ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="order_id" value="<?php echo $order->id; ?>">
                                <input type="hidden" name="update_status" value="1">
                                <select name="status" onchange="this.form.submit()">
                                    <option value="pending" <?php selected($order->status, 'pending'); ?>>Ожидает</option>
                                    <option value="paid" <?php selected($order->status, 'paid'); ?>>Оплачен</option>
                                    <option value="voicing" <?php selected($order->status, 'voicing'); ?>>Озвучивается</option>
                                    <option value="done" <?php selected($order->status, 'done'); ?>>Готово</option>
                                </select>
                            </form>
                        </td>
                        <td><?php echo $order->created_at; ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}