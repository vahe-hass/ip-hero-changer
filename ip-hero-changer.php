<?php
/**
 * Plugin Name: IP Hero Changer
 * Plugin URI: https://vahegrikorihassratian.com/plugins/ip-hero-changer/
 * Description: A WordPress plugin to change the hero section button colors based on IP.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.2
 * Author: Vahe Grikorihassratian
 * Author URI: https://vahegrikorihassratian.com/
 * License: GPL v3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html/
 */


 /*
 IP Hero Changer is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 2 of the License, or
 any later version.

 IP Hero Changer is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with IP Hero Changer. If not, see https://www.gnu.org/licenses/gpl-3.0.html/
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
            plugins_url('/assets/js/ihc-frontend.js', __FILE__),
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
    $id = intval($id);
    $column_name = esc_sql($column_name);
    $result = $wpdb->get_var($wpdb->prepare(
        "SELECT $column_name FROM $table_name WHERE id = %d",
        $id
    ));

    return $result;
}

function ihc_db_row_query($row_id) {
    global $wpdb;

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
    $admin_template_path = plugin_dir_path(__FILE__) . 'templates/ihc-admin.html';


    $option_b_row = ihc_db_row_query(1);
    $option_a_row = ihc_db_row_query(2);

    if (empty($option_b_row)) {
        $empty_database = "Don't forget to save your configuration so that the IP Hero Changer plugin can begin its tasks.";
        $optionBhex = 'Not Set';
        $optionState = 'Not Set';
        $ctrB = '';
        $ctrA = '';
        $impressionB = 0;
        $clickedB = 0;
        $impressionA = 0;
        $clickedA = 0;
    } else {
        $empty_database = "";
        $optionBhex = $option_b_row[3];
        $optionState = $option_b_row[1];
        $impressionB = intval($option_b_row[5]);
        $clickedB = intval($option_b_row[6]);
        $impressionA = intval($option_a_row[5]);
        $clickedA = intval($option_a_row[6]);
        $ctrB_condition = ($impressionB !== 0) ? $clickedB / $impressionB : 0;
        $ctrA_condition =($impressionA !== 0) ? $clickedA / $impressionA : 0;
        $ctrB = round($ctrB_condition * 100, 1);
        $ctrA = round($ctrA_condition * 100, 1);
    }


    if (file_exists($admin_template_path)) {
        include($admin_template_path);
    } else {
        $ihc_line = __LINE__ - 3;
        ihc_log_error('IHC admin template not found check the templates folder.', $ihc_line);
    }
}

function ihc_add_admin_menu() {
    add_menu_page(
        'IP Hero Changer',
        'IP Hero Changer',
        'manage_options',
        'ip-hero-changer-admin',
        'ihc_admin_page');
}

add_action('admin_menu', 'ihc_add_admin_menu');

function ihc_render_documentation_page() {
    $doc_template_path = plugin_dir_path(__FILE__) . 'templates/ihc-docs.html';
    $ihc_img1 = plugins_url('assets/images/ihc-elem-docs.jpg', __FILE__);
    $ihc_img2 = plugins_url('assets/images/ihc-wp-docs.jpg', __FILE__);
    $ihc_img3 = plugins_url('assets/images/ihc-form-docs.jpg', __FILE__);


    if (file_exists($doc_template_path)) {
        include($doc_template_path);
    } else {
        $ihc_line = __LINE__ - 3;
        ihc_log_error('IHC documentation template not found check the templates folder.', $ihc_line);
    }
}

function ihc_add_documentation_page() {
    add_submenu_page(
        'ip-hero-changer-admin',
        'Documentation',
        'Documentation',
        'manage_options',
        'ihc-documentation',
        'ihc_render_documentation_page'
    );
}

add_action('admin_menu', 'ihc_add_documentation_page');


function enqueue_ihc_resources() {

    if (isset($_GET['page']) && ($_GET['page'] === 'ip-hero-changer-admin' || $_GET['page'] === 'ihc-documentation')) {

        wp_enqueue_style('bootstrap-head', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css');
        wp_enqueue_style('fontawesome-head', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css');
        wp_enqueue_style('ihc-fonts', 'https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i');
        wp_enqueue_style('ihc-plugin', plugins_url( 'ip-hero-changer/assets/css/ihc.css'));
        wp_enqueue_style('ihc-plugin-bootstrap', plugins_url( 'ip-hero-changer/assets/css/bootstrap.min.css'));

        wp_enqueue_script('ihc-plugin-city-state', plugins_url( 'ip-hero-changer/assets/js/ihc.js'));
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
        $ihc_line = __LINE__ - 3;
        ihc_log_error('ihc_user_viewed_counter_b function could not increment the user_viewed by 1.', $ihc_line);
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
        $ihc_line = __LINE__ - 3;
        ihc_log_error('ihc_user_viewed_counter_a function could not increment the user_viewed by 1.', $ihc_line);
    }

};

function ihc_main_procees() {

    if (!is_home() && !is_front_page()) {
        return;
    }

    if (current_user_can( 'edit_posts' )) {
        return;
    }

    $ihc_sql = ihc_db_row_query(1);
    if (empty($ihc_sql)) {
        return;
    }

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Check if it's a new session
    if (!isset($_SESSION['visited_homepage'])) {
        $session_ip_address = ihc_get_visitor_ip();
        $response = wp_remote_get("https://ipapi.co/" . $session_ip_address . "/json/");
        $state = '';

        if (is_array($response) && !is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $obj = json_decode($body);

            if (isset($obj->error) && $obj->error === true && isset($obj->reason) && $obj->reason === 'RateLimited') {
                $ihc_line = __LINE__ ;
                ihc_log_error('Ip Hero Changer utilizes ipapi for IP detection, and the free version of ipapi comes with a limit of 30,000 requests per month, with a daily cap of up to 1,000. Unfortunately, it seems you have exceeded these limits.', $ihc_line);
                // Add exceeded limit option in DB to show in admin page
            } else {
                $state = isset($obj->region) ? $obj->region : '';

            }
        } else {
            $ipapi_error_message = $response->get_error_message();
            $ihc_line = __LINE__ ;
            ihc_log_error($ipapi_error_message, $ihc_line);
        }

        $ipapi_respoonse_array = array($state);
        $commonValues = array_intersect($ihc_sql, $ipapi_respoonse_array);

        if (!empty($commonValues)) {
            $ihc_sql_color = $ihc_sql[3];
            $ihc_sql_btn_id = $ihc_sql[4];
            add_action('wp_head', function () use ($ihc_sql_color, $ihc_sql_btn_id) {
                ihc_styles_generator_b($ihc_sql_color, $ihc_sql_btn_id);
            });
            ihc_user_viewed_counter_b();

        } else {
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
                    var ajaxSent = false;
                    anchorElementA.addEventListener('click', function() {
                        if (ajaxSent) {
                            return;
                        }
                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', '/wp-content/plugins/ip-hero-changer/counter-process-a.php', true);
                        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                        xhr.onload = function() {
                            if (xhr.status >= 200 && xhr.status < 300) {
                                var response = xhr.responseText;
                                console.log(response);
                                ajaxSent = true;
                            } else {
                                console.error('AJAX error: ' + xhr.status, xhr.statusText);
                            }
                        };
                        xhr.onerror = function() {
                            console.error('Network error occurred');
                        };
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
                    var ajaxSent = false;
                    anchorElementA.addEventListener('click', function() {
                        if (ajaxSent) {
                            return;
                        }
                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', '/wp-content/plugins/ip-hero-changer/counter-process-a.php', true);
                        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                        xhr.onload = function() {
                            if (xhr.status >= 200 && xhr.status < 300) {
                                var response = xhr.responseText;
                                console.log(response);
                                ajaxSent = true;
                            } else {
                                console.error('AJAX error: ' + xhr.status, xhr.statusText);
                            }
                        };
                        xhr.onerror = function() {
                            console.error('Network error occurred');
                        };
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
