<?php
/**
 * Plugin Name: IP Hero Changer
 * Description: A WordPress plugin to change the hero section based on IP.
 * Version: 1.0.0
 * Author: Vahe Grikorihassratian
 */

 // Exit if accessed directly.
 if ( ! defined( 'ABSPATH' ) ) {
 	exit;
 }

 //function to create the database table during plugin activation
 function ip_hero_changer_table() {
     global $wpdb;
     $table_name = $wpdb->prefix . 'ip_hero_changer';

     if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
         $charset_collate = $wpdb->get_charset_collate();

         // Define the table structure
         $sql = "CREATE TABLE $table_name (
             id mediumint(9) NOT NULL AUTO_INCREMENT,
             variation_id mediumint(9) NOT NULL,
             user_ip varchar(45) NOT NULL,
             action varchar(20) NOT NULL,
             timestamp timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
             PRIMARY KEY (id)
         ) $charset_collate;";

         require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
         dbDelta($sql);
     }
 }

 register_activation_hook(__FILE__, 'ip_hero_changer_table');

 // delete the database table during plugin uninstallation
function delete_ip_hero_changer_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ip_hero_changer';
    $sql = "DROP TABLE IF EXISTS $table_name;";
    $wpdb->query($sql);
}

register_uninstall_hook(__FILE__, 'delete_ip_hero_changer_table');

function ihc_admin_page() {
    // Render the admin page content, including the form to input the IP range
    echo '<div class="wrap">';
    echo '<h2>Your Plugin Admin Page</h2><br>';
    echo '<form method="post" action="">';
    echo '<label for="ip_range">Enter IP Range:</label>';
    echo '<input type="text" id="ip_range" name="ip_range" />';
    echo '<input type="submit" name="check_ip_range" value="Check" class="button-primary" />';
    echo '</form>';
    echo '</div>';

    // Handle form submissions here
    if (isset($_POST['check_ip_range'])) {
        $ip_range = sanitize_text_field($_POST['ip_range']);
        // Process the IP range and perform the necessary actions
        // ...
    }
}

// Add an admin menu item linking to your admin page callback function
function ihc_add_admin_menu() {
    add_menu_page('IP Hero Changer', 'IP Hero Changer', 'manage_options', 'ip-hero-changer-admin', 'ihc_admin_page');
}
add_action('admin_menu', 'ihc_add_admin_menu');


// gets visitors IP address using the server
function wp_get_visitor_ip() {
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

function ihc_log_user_ip() {
    global $wpdb;

    if (is_front_page()) {
        if (!session_id()) {
            session_start();
        }

        if (isset($_SESSION['ip_logged'])) {
            return;
        }

        $user_ip = wp_get_visitor_ip();
        $table_name = $wpdb->prefix . 'ip_hero_changer';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
            $wpdb->insert(
                $table_name,
                array(
                    'variation_id' => 0,
                    'user_ip' => $user_ip,
                    'action' => 'visit',
                )
            );
        }

        $_SESSION['ip_logged'] = true;
    }
}

// Hook the function to run upon webpage request (e.g., when a page is loaded)
add_action('template_redirect', 'ihc_log_user_ip');

// function that checks the given ip range from admin and then callback another JS function
