<?php

// Include WordPress core functions
require_once("../../../wp-load.php");
global $wpdb;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $table_name = $wpdb->prefix . 'ip_hero_changer';
    $primary_key = 'id';

    $user_region = sanitize_text_field($_POST["region"]);
    $user_country = sanitize_text_field($_POST["country"]);
    $user_city = sanitize_text_field($_POST["city_state"]);
    $user_option = "B";
    $user_color = sanitize_text_field($_POST["color"]);
    $btn_id = sanitize_text_field($_POST["btnid"]);

    $existing_data = $wpdb->get_results("SELECT * FROM $table_name");

    if (empty($existing_data)) {
        $wpdb->insert(
            $table_name,
            array(
                'user_region' => $user_region,
                'user_country' => $user_country,
                'user_city' => $user_city,
                'user_option' => $user_option,
                'user_color' => $user_color,
                'btn_id' => $btn_id,
                'submission_date' => current_time('mysql', 1)
            )
        );

        $wpdb->insert(
            $table_name,
            array(
                'user_region' => "user region A",
                'user_country' => "user country A",
                'user_city' => "user city A",
                'user_option' => "A",
                'user_color' => "color A",
                'btn_id' => "btn ID A",
                'submission_date' => current_time('mysql', 1)
            )
        );

        if ($wpdb->last_error) {
            error_log('IHC Error: Failed to save the form options to the database.');
        } else {
            echo "Selected options saved successfully.";
        }
    } else {
        echo "Clear existing data before configuring new options.";
    }
} else {
    http_response_code(405);
    echo "Invalid request method.";
}


?>
