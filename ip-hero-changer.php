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
function ihc_get_visitor_ip() {
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
};

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


function ihc_proceess_db_query() {
    global $wpdb;

    // Define your custom SQL query
    $sql = "SELECT * FROM {$wpdb->prefix}ip_hero_changer WHERE user_option = 'B'";

    $results = $wpdb->get_results($sql);

    if (!empty($results)) {
        foreach ($results as $row) {
            $user_country = $row->user_country;
            $user_state = $row->user_state;
            $user_option = $row->user_option;
            $user_color = $row->user_color;
        }

        return array($user_country, $user_state, $user_option, $user_color);

    } else {
        // !add error loging here!!!!!!!!!!!!!!!!!!!
        echo "No results found";
        return array();
    }

};

function ihc_elementor_check() {
    $elementor_page = get_post_meta( get_the_ID(), '_elementor_edit_mode', true );
    if ( ! ! $elementor_page ) {
        return true;
    }
};


function ihc_main_procees() {
    // Check if it's the homepage
    if (is_home() || is_front_page()) {
        // Your code to execute on the homepage for every session
        // This code runs ok
        // $ihc_sql = ihc_proceess_db_query();
        $ihc_sql = array("Afghanistan", "Kabul", "#c00b0b");


    }

    if (session_status() == PHP_SESSION_NONE) {
        session_start(); // Start the session if it's not already started
    }

    // Check if it's a new session
    if (!isset($_SESSION['visited_homepage'])) {
        // Your code to execute on the homepage for a new session
        // This code runs only on the first visit to the homepage in a new session
        // $session_ip_address = ihc_get_visitor_ip();
        $session_ip_address = '164.130.107.24';
        // $response = wp_remote_get("https://ipapi.co/" . $session_ip_address . "/json/");
        // if (is_array($response) && !is_wp_error($response)) {
        //     $ipapi_loc = wp_remote_retrieve_body($response);
        //     $obj = json_decode($ipapi_loc);
        //     $country_name = $obj->{'country_name'};
        //     $state = $obj->{'region'};
        //
        // } else {
        //     $error_message = $response->get_error_message();
        //     error_log("IHC Error: $error_message");
        // }
        //
        // $ipapi_respoonse_array = array($country_name, $state);
        // Note! a good function to compute the intersection of !!strings!!

        $ipapi_respoonse_array = array("Afghanistan", "Kabul");

        $commonValues = array_intersect($ihc_sql, $ipapi_respoonse_array);

        if (!empty($commonValues)) {
            $ihc_sql_color = $ihc_sql[2];
            add_action('wp_head', function () use ($ihc_sql_color) {
                ihc_styles_generator($ihc_sql_color);
            });

        } else {
            echo "There are !!NO!! common values in your array.";
        }

        $_SESSION['visited_homepage'] = true;
        // Add counter for homepage visits
    }
}

function ihc_styles_generator($ihc_sql_color) {
    $elementor_check = ihc_elementor_check();
    if ( $elementor_check ) {
        echo "<style type='text/css'>
            #first-btn .elementor-button {
                background-color: " . $ihc_sql_color . " !important;
            }
        </style>";
    } else {
        echo "<style type='text/css'>
            #firstbtn .wp-block-button a {
                background-color: " . $ihc_sql_color . " !important;
            }
        </style>";

    }

}

add_action('template_redirect', 'ihc_main_procees');
