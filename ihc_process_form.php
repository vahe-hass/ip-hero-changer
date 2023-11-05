<?php

// Include WordPress core functions
require_once("../../../wp-load.php");
global $wpdb;

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $table_name = $wpdb->prefix . 'ip_hero_changer';
    $primary_key = 'id';

    $user_country = sanitize_text_field($_POST["country"]);
    $user_state = sanitize_text_field($_POST["state"]);
    $user_city = sanitize_text_field($_POST["city"]);
    // $user_option = sanitize_text_field($_POST["option"]);
    $user_option = "B";
    $user_color = sanitize_text_field($_POST["color"]);

    $wpdb->insert(
        $table_name,
        array(
            'user_country' => $user_country,
            'user_state' => $user_state,
            'user_city' => $user_city,
            'user_option' => $user_option,
            'user_color' => $user_color,
            'submission_date' => current_time('mysql', 1)
        )
    );

    if ($wpdb->last_error) {
        echo "An error occurred while saving the form data.";
    } else {
        echo "Form data saved successfully.";

        $wpdb->insert(
            $table_name,
            array(
                'user_country' => "user country A",
                'user_state' => "user state A",
                'user_city' => "user city A",
                'user_option' => "A",
                'user_color' => "color A",
                'submission_date' => current_time('mysql', 1)
            )
        );
    }
} else {
    // Invalid request method
    http_response_code(405);
    echo "Invalid request method.";
}

?>
