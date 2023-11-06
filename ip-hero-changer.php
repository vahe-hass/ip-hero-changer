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
            user_region varchar(255) NOT NULL,
            user_country varchar(255) NOT NULL,
            user_state varchar(255),
            user_option varchar(1) NOT NULL,
            user_color varchar(7) NOT NULL,
            submission_date datetime NOT NULL,
            user_viewed INT DEFAULT 0,
            conversion_rate INT DEFAULT 0,
            engagement_metrics INT DEFAULT 0,
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

// Add an admin menu admin page
function ihc_admin_page() {
    $form_template_path = plugin_dir_path(__FILE__) . 'templates/ihc_form.html';
        if (file_exists($form_template_path)) {
            include($form_template_path);
        } else {
            echo 'IHC Form template not found.';
        }
}

function ihc_add_admin_menu() {
    add_menu_page('IP Hero Changer', 'IP Hero Changer', 'manage_options', 'ip-hero-changer-admin', 'ihc_admin_page');
}
add_action('admin_menu', 'ihc_add_admin_menu');

// Add resources to the plugin admin page header
function enqueue_ihc_resources() {

    if (isset($_GET['page']) && $_GET['page'] === 'ip-hero-changer-admin') {

        wp_enqueue_style('bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css');

        wp_enqueue_style( 'ihc-plugin', plugins_url( 'ip-hero-changer/assets/ihc.css' ) );

        wp_enqueue_script('bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js', array('jquery'), null, true);

        wp_enqueue_script( 'ihc-plugin', plugins_url( 'ip-hero-changer/assets/ihc.js' ) );

    }
}

add_action('admin_enqueue_scripts', 'enqueue_ihc_resources');
