<?php
// Include WordPress core functions
require_once("../../../wp-load.php");

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ip_hero_changer';

    $user_country = sanitize_text_field($_POST["country"]);
    $user_state = sanitize_text_field($_POST["state"]);
    $user_city = sanitize_text_field($_POST["city"]);
    $user_option = sanitize_text_field($_POST["option"]);

    $wpdb->insert(
        $table_name,
        array(
            'user_country' => $user_country,
            'user_state' => $user_state,
            'user_city' => $user_city,
            'user_option' => $user_option,
            'submission_date' => current_time('mysql', 1)
        )
    );

    if ($wpdb->last_error) {
        echo "An error occurred while saving the form data.";
    } else {
        echo "Form data saved successfully.";
    }
} else {
    // Invalid request method
    http_response_code(405);
    echo "Invalid request method.";
}
?>
