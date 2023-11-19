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
            btn_id varchar(255),
            submission_date datetime NOT NULL,
            user_viewed INT DEFAULT 0,
            user_clicked INT DEFAULT 0,
            engagement_metrics INT DEFAULT 0,
            PRIMARY KEY (id)
        ) $charset_collate;";

         require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
         dbDelta($sql);
     }
}

function ihc_create_log_file() {
    $log_file_path = WP_CONTENT_DIR . '/plugins/ip-hero-changer/ihc-error-log.txt';

    if (!file_exists($log_file_path)) {
        file_put_contents($log_file_path, '');
    }
}

function ihc_activate() {
    ip_hero_changer_table();
    ihc_create_log_file();
}

register_activation_hook(__FILE__, 'ihc_activate');

// delete the database table during plugin uninstallation
function delete_ip_hero_changer_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ip_hero_changer';
    $sql = "DROP TABLE IF EXISTS $table_name;";
    $wpdb->query($sql);
}

// Function to delete the log file
function ihc_delete_log_file() {
    $log_file_path = WP_CONTENT_DIR . '/plugins/ip-hero-changer/ihc-error-log.txt';

    if (file_exists($log_file_path)) {
        unlink($log_file_path);
    }
}

function ihc_uninstall() {
    delete_ip_hero_changer_table();
    ihc_delete_log_file();
}

register_uninstall_hook(__FILE__, 'ihc_uninstall');

function ihc_log_error($error_message, $ihc_line) {
    $log_file_path = WP_CONTENT_DIR . '/plugins/ip-hero-changer/ihc-error-log.txt';
    $line_number = $ihc_line;
    $log_entry = date('Y-m-d H:i:s') . ' - Line ' . $line_number . ': ' . $error_message . PHP_EOL;
    file_put_contents($log_file_path, $log_entry, FILE_APPEND);
};

function enqueue_ihc_frontend_script() {
    if (is_front_page()) {
        wp_enqueue_script(
            'ihc-frontend-script',
            plugins_url('/assets/ihc-frontend.js', __FILE__),
            array('jquery'),
            '1.0',
            true
        );
    }
}

add_action('wp_enqueue_scripts', 'enqueue_ihc_frontend_script');

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

function ihc_get_column_value($id, $column_name) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ip_hero_changer';

    // Sanitize input to prevent SQL injection
    $id = intval($id);
    $column_name = esc_sql($column_name);

    // Query the database
    $result = $wpdb->get_var($wpdb->prepare(
        "SELECT $column_name FROM $table_name WHERE id = %d",
        $id
    ));

    return $result;
}

function ihc_db_row_query($row_id) {
    global $wpdb;

    // Define your custom SQL query
    $sql = "SELECT * FROM {$wpdb->prefix}ip_hero_changer WHERE id = $row_id";

    $results = $wpdb->get_results($sql);

    if (!empty($results)) {
        foreach ($results as $row) {
            $user_country = $row->user_country;
            $user_state = $row->user_state;
            $user_option = $row->user_option;
            $user_color = $row->user_color;
            $button_id = $row->btn_id;
            $user_viewed = $row->user_viewed;
            $user_clicked = $row->user_clicked;
        }

        return array($user_country, $user_state, $user_option, $user_color, $button_id, $user_viewed, $user_clicked);   } else {
        $ihc_line = __LINE__ - 15;
        ihc_log_error('The sql query for ihc_proceess_db_query function returned an empty array', $ihc_line);
        return array();
    }

};

// Add an admin menu admin page
function ihc_admin_page() {
    $form_template_path = plugin_dir_path(__FILE__) . 'templates/ihc-admin.html';


    $option_b_row = ihc_db_row_query(1);
    $option_a_row = ihc_db_row_query(2);

    if (empty($option_b_row)) {
        $empty_database = "Don't forget to save your configuration so that the IP Hero Changer plugin can begin its tasks.";
        $ctrB = '';
        $ctrA = '';
        $impressionB = 0;
        $clickedB = 0;
        $impressionA = 0;
        $clickedA = 0;
    } else {
        $empty_database = "";
        $impressionB = intval($option_b_row[5]);
        $clickedB = intval($option_b_row[6]);
        $impressionA = intval($option_a_row[5]);
        $clickedA = intval($option_a_row[6]);
        $ctrB_condition = ($impressionB !== 0) ? $clickedB / $impressionB : 0;
        $ctrA_condition =($impressionA !== 0) ? $clickedA / $impressionA : 0;
        $ctrB = round($ctrB_condition * 100, 1);
        $ctrA = round($ctrA_condition * 100, 1);
    }


    if (file_exists($form_template_path)) {
        include($form_template_path);
    } else {
        // log error in this part
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

        wp_enqueue_style('bootstrap-head', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css');
        wp_enqueue_style('fontawesome-head', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css');
        wp_enqueue_style('ihc-fonts', 'https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i');
        wp_enqueue_style('ihc-plugin', plugins_url( 'ip-hero-changer/assets/ihc.css'));
        wp_enqueue_style('ihc-plugin-bootstrap', plugins_url( 'ip-hero-changer/assets/bootstrap.min.css'));

        wp_enqueue_script('ihc-plugin-city-state', plugins_url( 'ip-hero-changer/assets/ihc.js'));
        wp_enqueue_script('bootstrap-footer', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js', array(), '5.3.2', true);

    }
}

add_action('admin_enqueue_scripts', 'enqueue_ihc_resources');

function ihc_elementor_check() {
    $elementor_page = get_post_meta( get_the_ID(), '_elementor_edit_mode', true );
    if ( ! ! $elementor_page ) {
        return true;
    }
};

function ihc_user_viewed_counter_b() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ip_hero_changer';
    $user_id = 1;
    $data = array('user_viewed' => $wpdb->prepare('%d', 1));
    $where = array('id' => $user_id);

    $sql = sprintf("UPDATE %s SET user_viewed = user_viewed + %d WHERE id = %d", $table_name, $data['user_viewed'], $user_id);
    $wpdb->query($sql);

    if ($wpdb->last_error) {
        error_log('IC Error: Failed to increment user_viewed B by +1 in the database.');
    } else {
        // Write a loging function for the plugin logs;
    }

};

function ihc_user_viewed_counter_a() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ip_hero_changer';
    $user_id = 2;
    $data = array('user_viewed' => $wpdb->prepare('%d', 1));
    $where = array('id' => $user_id);

    $sql = sprintf("UPDATE %s SET user_viewed = user_viewed + %d WHERE id = %d", $table_name, $data['user_viewed'], $user_id);
    $wpdb->query($sql);

    if ($wpdb->last_error) {
        error_log('IHC Error: Failed to increment user_viewed A by +1 in the database.');
    } else {
        // Write a loging function for the plugin logs;
    }

};

function ihc_main_procees() {
    // Check if it's the homepage
    if (is_home() || is_front_page()) {
        // Your code to execute on the homepage for every session
        // This code runs ok
        $ihc_sql = ihc_db_row_query(1);
        // $ihc_sql = array("Afghanistan", "Kabul", "#c00b0b", "#first-btn");

        if (empty($ihc_sql)) {
            return;
        }
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

        $ipapi_respoonse_array = array("Algeria");
        // $ipapi_respoonse_array = array("Iran", "Tehran");

        $commonValues = array_intersect($ihc_sql, $ipapi_respoonse_array);

        if (!empty($commonValues)) {
            $ihc_sql_color = $ihc_sql[3];
            $ihc_sql_btn_id = $ihc_sql[4];
            add_action('wp_head', function () use ($ihc_sql_color, $ihc_sql_btn_id) {
                ihc_styles_generator_b($ihc_sql_color, $ihc_sql_btn_id);
            });
            ihc_user_viewed_counter_b();

        } else {
            echo "There are !!NO!! common values in your array.";
            $ihc_sql_btn_id = $ihc_sql[4];
            add_action('wp_head', function () use ($ihc_sql_btn_id) {
                ihc_styles_generator_a($ihc_sql_btn_id);
            });
            ihc_user_viewed_counter_a();
        }

        $_SESSION['visited_homepage'] = true;
    }
}

function ihc_styles_generator_b($ihc_sql_color, $ihc_sql_btn_id) {
    $elementor_check = ihc_elementor_check();
    $cleaned_btn_id = str_replace('#', '', $ihc_sql_btn_id);
    if ( $elementor_check ) {
        echo "<style type='text/css'>" .
            $ihc_sql_btn_id . " .elementor-button {
                background-color: " . $ihc_sql_color . " !important;
            }
        </style>";
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                var ihcfirstBtn = document.getElementById('$cleaned_btn_id');
                if (ihcfirstBtn) {
                    ihcfirstBtn.classList.add('ihc-changed-to-b');
                }
            });
            </script>";
    } else {
        echo "<style type='text/css'>" .
            $ihc_sql_btn_id .  " .wp-block-button a {
                background-color: " . $ihc_sql_color . " !important;
            }
        </style>";
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                var ihcfirstBtn = document.getElementById('$cleaned_btn_id');
                if (ihcfirstBtn) {
                    ihcfirstBtn.classList.add('ihc-changed-to-b');
                }
            });
            </script>";

    }

}

function ihc_styles_generator_a($ihc_sql_btn_id) {
    $elementor_check = ihc_elementor_check();
    $cleaned_btn_id = str_replace('#', '', $ihc_sql_btn_id);
    if ( $elementor_check ) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                var ihcfirstBtnA = document.getElementById('$cleaned_btn_id');
                if (ihcfirstBtnA !== null) {
                    var anchorElementA = ihcfirstBtnA.querySelector('a');

                    anchorElementA.addEventListener('click', function() {
                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', '/wp-content/plugins/ip-hero-changer/counter-process-a.php', true);

                        // Set the appropriate headers for a form POST request
                        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

                        xhr.onload = function() {
                            if (xhr.status >= 200 && xhr.status < 300) {
                                // Request was successful, handle the response if needed
                                var response = xhr.responseText;
                                console.log(response);
                            } else {
                                // Request failed
                                console.error('AJAX error: ' + xhr.status, xhr.statusText);
                            }
                        };

                        // Handle network errors
                        xhr.onerror = function() {
                            console.error('Network error occurred');
                        };

                        // Send the request (you may need to adjust the data parameter based on your needs)
                        xhr.send();
                    });
                } else {
                    console.log('IHC button ID is not set');
                }
            });
        </script>";
    } else {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                var ihcfirstBtnA = document.getElementById('$cleaned_btn_id');
                if (ihcfirstBtnA !== null) {
                    var anchorElementA = ihcfirstBtnA.querySelector('a');

                    anchorElementA.addEventListener('click', function() {
                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', '/wp-content/plugins/ip-hero-changer/counter-process-a.php', true);

                        // Set the appropriate headers for a form POST request
                        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

                        xhr.onload = function() {
                            if (xhr.status >= 200 && xhr.status < 300) {
                                // Request was successful, handle the response if needed
                                var response = xhr.responseText;
                                console.log(response);
                            } else {
                                // Request failed
                                console.error('AJAX error: ' + xhr.status, xhr.statusText);
                            }
                        };

                        // Handle network errors
                        xhr.onerror = function() {
                            console.error('Network error occurred');
                        };

                        // Send the request (you may need to adjust the data parameter based on your needs)
                        xhr.send();
                    });
                } else {
                    console.log('IHC button ID is not set');
                }
            });
        </script>";
    }
}

add_action('template_redirect', 'ihc_main_procees');
