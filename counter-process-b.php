<?php

require_once("../../../wp-load.php");

function ihc_user_clicked_b() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ip_hero_changer';
    $id = 1;
    $data = array('user_clicked' => $wpdb->prepare('%d', 1));
    $where = array('id' => $id);

    $sql = sprintf("UPDATE %s SET user_clicked = user_clicked + %d WHERE id = %d", $table_name, $data['user_clicked'], $id);
    $wpdb->query($sql);

    if ($wpdb->last_error) {
        error_log('IHC Error: Failed to increment user_clicked B by +1 in the database.');
    } else {
        // Write a loging function for the plugin logs;
    }
};

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    ihc_user_clicked_b();
} else {
    http_response_code(405);
    echo "Invalid request method.";
};


 ?>
