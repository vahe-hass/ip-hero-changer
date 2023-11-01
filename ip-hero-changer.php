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
            id int(11) NOT NULL AUTO_INCREMENT,
            user_country varchar(255) NOT NULL,
            user_state varchar(255),
            user_city varchar(255),
            user_option varchar(1) NOT NULL,
            submission_date datetime NOT NULL,
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
    ?>
    <div class="wrap">
        <h2>Enter Your Location</h2>
        <form id="location-form">
            <label for="country">Country:</label>
            <select id="country" name="country" required>
                <option value="USA" selected>United States</option>
                <!-- You can add more countries here if needed -->
            </select><br>

            <label for="region">State:</label>
            <select id="region" name="state" required>
                <option value="" disabled selected>Select a state</option>
                <option value="AL">Alabama</option>
                <option value="AK">Alaska</option>
                <option value="AZ">Arizona</option>
                <option value="AR">Arkansas</option>
                <option value="CA">California</option>
                <option value="CO">Colorado</option>
                <!-- Add cities for all 50 states -->
                <option value="WY">Wyoming</option>
            </select><br>

            <label for="city">City:</label>
            <select id="city" name="city" required>
                <option value="" disabled selected>Select a city</option>
            </select><br>

            <label>Choose Option:</label>
            <input type="radio" id="optionA" name="option" value="A" required>
            <label for="optionA">A</label>
            <input type="radio" id="optionB" name="option" value="B" required>
            <label for="optionB">B</label><br>

            <input type="submit" value="Submit">
            <div id="response-message"></div>
        </form>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // JavaScript to populate city options based on the selected state
        var stateSelect = document.getElementById("region");
        var citySelect = document.getElementById("city");

        // Sample data for cities in each state
        var citiesByState = {
            "AL": ["Birmingham", "Montgomery", "Mobile"],
            "AK": ["Anchorage", "Fairbanks", "Juneau"],
            "AZ": ["Phoenix", "Tucson", "Mesa"],
            "AR": ["Little Rock", "Fort Smith", "Fayetteville"],
            "CA": ["Los Angeles", "San Francisco", "San Diego"],
            "CO": ['Denver', 'Colorado Springs','Aurora','Fort Collins'],
            // Add cities for all 50 states
            "WY": ["Cheyenne", "Casper", "Laramie"]
        };

        stateSelect.addEventListener("change", function() {
            var selectedState = stateSelect.value;

            // Clear city options
            citySelect.innerHTML = '<option value="" disabled selected>Select a city</option>';

            if (citiesByState[selectedState]) {
                // Populate cities for the selected state
                citiesByState[selectedState].forEach(function(city) {
                    var option = document.createElement("option");
                    option.value = city;
                    option.textContent = city;
                    citySelect.appendChild(option);
                });
            }
        });

        // JavaScript to handle form submission via AJAX
        $(document).ready(function() {
            $("#location-form").submit(function(event) {
                event.preventDefault();

                var formData = {
                    country: $("#country").val(),
                    state: $("#region").val(),
                    city: $("#city").val(),
                    option: $("input[name='option']:checked").val()
                };

                $.ajax({
                    type: "POST",
                    url: "/wp-content/plugins/ip-hero-changer/ihc_process_form.php", // The URL of the server-side script
                    data: formData,
                    success: function(response) {
                        // Display the server's response in the response-message div
                        $("#response-message").html(response);
                    },
                    error: function() {
                        $("#response-message").html("An error occurred while processing your request.");
                    }
                });
            });
        });
    </script>
    <?php
}

// Add an admin menu item linking to your admin page callback function
function ihc_add_admin_menu() {
    add_menu_page('IP Hero Changer', 'IP Hero Changer', 'manage_options', 'ip-hero-changer-admin', 'ihc_admin_page');
}
add_action('admin_menu', 'ihc_add_admin_menu');


function customize_container_before_render($element) {
    // Check if this is the container you want to customize
    if ( is_front_page() ) {
        $container_id = $element->get_id();
        echo $container_id;
    }
}

add_action('elementor/frontend/container/before_render', 'customize_container_before_render');
