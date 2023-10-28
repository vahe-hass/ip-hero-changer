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

 use MaxMind\Db\Reader;

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


function get_user_location($ip) {
    // Replace 'YOUR_API_KEY' with your MaxMind API key
    $apiKey = 'YOUR_API_KEY';

    // Path to the MaxMind GeoIP2 database file
    $dbPath = 'path-to-maxmind-database.mmdb';

    $reader = new Reader($dbPath);

    try {
        $record = $reader->city($ip);

        // Extract location information
        $location = array(
            'city' => $record->city->name,
            'region' => $record->mostSpecificSubdivision->name,
            'country' => $record->country->name,
        );

        return $location;
    } catch (Exception $e) {
        // Handle errors
        return array(
            'city' => 'Unknown',
            'region' => 'Unknown',
            'country' => 'Unknown',
        );
    }
}
