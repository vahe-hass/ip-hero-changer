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
            id INT NOT NULL AUTO_INCREMENT,
            user_country varchar(255) NOT NULL,
            user_state varchar(255),
            user_city varchar(255),
            user_option varchar(1) NOT NULL,
            user_color varchar(7) NOT NULL,
            submission_date datetime NOT NULL,
            user_viewed INT,
            conversion_rate INT,
            engagement_metrics INT,
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

function ihc_admin_page() {
    $form_template_path = plugin_dir_path(__FILE__) . 'templates/ihc_form.html';
        if (file_exists($form_template_path)) {
            include($form_template_path);
        } else {
            echo 'IHC Form template not found.';
        }
}

// Add an admin menu item linking to your admin page callback function
function ihc_add_admin_menu() {
    add_menu_page('IP Hero Changer', 'IP Hero Changer', 'manage_options', 'ip-hero-changer-admin', 'ihc_admin_page');
}
add_action('admin_menu', 'ihc_add_admin_menu');


function enqueue_ihc_resources() {

    if (isset($_GET['page']) && $_GET['page'] === 'ip-hero-changer-admin') {
        // Enqueue the external CSS.
        wp_enqueue_style('bootstrap-css', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css');


        // Enqueue Bootstrap JavaScript.
        wp_enqueue_script('bootstrap-js', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js', array('jquery'), null, true);

    }
}

// Hook the function to the admin_enqueue_scripts action.
add_action('admin_enqueue_scripts', 'enqueue_ihc_resources');
