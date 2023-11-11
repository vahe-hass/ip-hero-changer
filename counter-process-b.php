<?php

function ihc_user_clicked_b() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ip_hero_changer';
    $user_id = 1;
    $data = array('user_clicked' => $wpdb->prepare('%d', 1));
    $where = array('id' => $user_id);

    $sql = sprintf("UPDATE %s SET user_clicked = user_clicked + %d WHERE id = %d", $table_name, $data['user_clicked'], $user_id);
    $wpdb->query($sql);

    if ($wpdb->last_error) {
        error_log('IHC Error: Failed to increment user_clicked B by +1 in the database.');
    } else {
        // Write a loging function for the plugin logs;
    }

};


 ?>
