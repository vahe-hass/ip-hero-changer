<?php

require_once("../../../wp-load.php");

if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'delete_all_data') {
    delete_all_data();
} else {
    http_response_code(403);
    echo "Invalid request method.";
}

// Function to delete all data from the database
function delete_all_data() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'ip_hero_changer';
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

    if ($count > 0) {
        $wpdb->query("DELETE FROM $table_name");
        if ($wpdb->last_error) {
            error_log('Error deleting data: ' . $wpdb->last_error);
            echo 'Error deleting data.';
        } else {
            echo 'Data deleted successfully.';
            $wpdb->query("ALTER TABLE $table_name AUTO_INCREMENT = 1");
        }
    } else {
        echo 'No data to delete.';
    }

    exit();
}

?>
