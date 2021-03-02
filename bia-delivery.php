<?php
/**
 * Plugin Name:     Benefits In Action Delivery
 * Description:     Food Delivery Tracking Plugin
 * Author:          Josh Hallinan
 * Text Domain:     bia-delivery
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Bia_Delivery
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
$bia_delivery_db_version = '1.0';

register_activation_hook( __FILE__, "bia_delivery_initialize_database" );
function bia_delivery_initialize_database(){
    global $wpdb;
    global $bia_delivery_db_version;;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE `{$wpdb->prefix}bia_food_requests` (
        id mediumint unsigned NOT NULL auto_increment,
        first_name varchar(50) NOT NULL,
        last_name varchar(50) NOT NULL,
        address varchar(255) NOT NULL,
        zip char(5) NOT NULL,
        pd_district_id tinyint NOT NULL,
        sign_up_date datetime NOT NULL,
        phone varchar(20) NOT NULL,
        email varchar(255),
        is_disabled boolean NOT NULL DEFAULT FALSE,
        is_able_to_text boolean NOT NULL DEFAULT FALSE,
        special_instructions text,
        num_in_house tinyint NOT NULL,
        date_of_birth DATE,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    $sql = "CREATE TABLE `{$wpdb->prefix}bia_food_deliveries` (
        id mediumint unsigned NOT NULL auto_increment,
        food_request_id mediumint unsigned NOT NULL,
        scheduled_time datetime NOT NULL,
        is_complete boolean NOT NULL DEFAULT FALSE,
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta($sql);

    $sql = "CREATE TABLE `{$wpdb->prefix}bia_food_deliveries_out_of_bounds` (
        id mediumint unsigned NOT NULL auto_increment,
        food_request_id mediumint unsigned NOT NULL,
        scheduled_time datetime NOT NULL,
        is_complete boolean NOT NULL DEFAULT FALSE,
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta($sql);


    $sql = "CREATE TABLE `{$wpdb->prefix}bia_food_requests_out_of_bounds` (
        id mediumint unsigned NOT NULL auto_increment,
        first_name varchar(50) NOT NULL,
        last_name varchar(50) NOT NULL,
        address varchar(255) NOT NULL,
        zip char(5) NOT NULL,
        pd_district_id tinyint NOT NULL,
        county_id mediumint NOT NULL DEFAULT 0,
        sign_up_date datetime NOT NULL,
        phone varchar(20) NOT NULL,
        email varchar(255),
        is_disabled boolean NOT NULL DEFAULT FALSE,
        is_able_to_text boolean NOT NULL DEFAULT FALSE,
        special_instructions text,
        num_in_house tinyint NOT NULL,
        date_of_birth DATE,
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta($sql);

    $sql = "CREATE TABLE `{$wpdb->prefix}bia_counties` (
        id mediumint unsigned NOT NULL auto_increment,
        name varchar(255) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta($sql);

    add_option( 'bia_delivery_db_version', $bia_delivery_db_version);
}

function bia_delivery_register_public_scripts(){

    wp_register_script(
        'bia-delivery-jquery-validate',
        'https://cdn.jsdelivr.net/npm/jquery-validation@1.19.1/dist/jquery.validate.min.js',
        array('jquery'),
        false,
        true
    );

    $file_path = 'js/public-form.js';
    wp_register_script(
        'bia-delivery-public-form',
        plugins_url( $file_path, __FILE__),
        array('jquery'),
        date("ymd-Gis", filemtime( plugin_dir_path(__FILE__) . $file_path )),
        true
    );

}
add_action('wp_enqueue_scripts', 'bia_delivery_register_public_scripts');

function bia_delivery_register_admin_scripts(){
}
add_action('admin_enqueue_scripts', 'bia_delivery_register_admin_scripts');

function bia_delivery_enqueue_public_styles(){
    $file_path = 'css/public-form.css';
    wp_enqueue_style(
        'bia-delivery-public-form',
        plugins_url( $file_path, __FILE__),
        array(),
        date("ymd-Gis", filemtime( plugin_dir_path(__FILE__) . $file_path )),
        'all'
    );

    return;
}
add_action('wp_enqueue_scripts', 'bia_delivery_enqueue_public_styles');

function bia_delivery_add_admin_menus(){
    $admin_menu_page = add_menu_page(
        'Benefits In Action Food Delivery',
        'Food Delivery',
        'read',
        'bia_delivery_admin_food_delivery',
        'bia_delivery_render_admin_menu',
        'dashicons-carrot',
        3
    );

    $admin_submenu_page = add_submenu_page(
        'bia_delivery_admin_food_delivery',
        'Schedule Delivery',
        'Schedule Delivery',
        'remove_users',
        'bia_delivery_admin_schedule_delivery',
        'bia_delivery_render_admin_delivery_submenu'
    );

    $admin_out_of_district = add_submenu_page(
        'bia_delivery_admin_food_delivery',
        'Outside Denver Deliveries',
        'Outside Denver Deliveries',
        'read',
        'bia_delivery_admin_outside_denver_deliveries',
        'bia_delivery_render_admin_outside_denver_deliveries'
    );

    add_action( 'load-' . $admin_menu_page, function(){
        add_action('admin_enqueue_scripts', 'bia_delivery_enqueue_admin_menu_scripts');
    });

    add_action( 'load-' . $admin_submenu_page, function(){
        add_action('admin_enqueue_scripts', 'bia_delivery_enqueue_admin_submenu_scripts');
    });

    add_action( 'load-' . $admin_out_of_district, function(){
        add_action('admin_enqueue_scripts', 'bia_delivery_enqueue_admin_deliveries_outside_denver_scripts');
    });
}
add_action('admin_menu', 'bia_delivery_add_admin_menus');

function bia_delivery_enqueue_admin_menu_scripts(){

    $file_path = 'js/admin-main-menu.js';
    wp_enqueue_script('bia_admin_main_menu',
        plugins_url( $file_path, __FILE__),
        array('jquery', 'jquery-ui-core', 'jquery-ui-dialog'),
        date("ymd-Gis", filemtime( plugin_dir_path(__FILE__) . $file_path )),
        true
    );
    wp_localize_script('bia_admin_main_menu', 'settings', array(
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'wpUserId' => get_current_user_id()
    ));

    $file_path = 'css/admin.css';
    wp_enqueue_style(
        'bia-delivery-admin',
        plugins_url( $file_path, __FILE__),
        array('wp-jquery-ui-dialog'),
        date("ymd-Gis", filemtime( plugin_dir_path(__FILE__) . $file_path ))
    );
}

function bia_delivery_enqueue_admin_submenu_scripts(){

    $file_path = 'js/admin-schedule-delivery.js';
    wp_enqueue_script('bia_admin_schedule_delivery_script',
        plugins_url( $file_path, __FILE__),
        array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker', 'jquery-ui-dialog', 'jquery-ui-button'),
        date("ymd-Gis", filemtime( plugin_dir_path(__FILE__) . $file_path )),
        true
    );
    wp_localize_script('bia_admin_schedule_delivery_script', 'settings', array(
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'wpUserId' => get_current_user_id()
    ));

    $file_path = 'css/admin.css';
    wp_enqueue_style(
        'bia-delivery-admin',
        plugins_url( $file_path, __FILE__),
        array('wp-jquery-ui-dialog'),
        date("ymd-Gis", filemtime( plugin_dir_path(__FILE__) . $file_path ))
    );

}

function bia_delivery_enqueue_admin_deliveries_outside_denver_scripts(){
    $file_path = 'js/admin-deliveries-outside-denver.js';
    wp_enqueue_script('bia_admin_deliveries_outside_denver_script',
        plugins_url( $file_path, __FILE__),
        array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker', 'jquery-ui-dialog', 'jquery-ui-button'),
        date("ymd-Gis", filemtime( plugin_dir_path(__FILE__) . $file_path )),
        true
    );
    wp_localize_script('bia_admin_deliveries_outside_denver_script', 'settings', array(
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'wpUserId' => get_current_user_id()
    ));

    $file_path = 'css/admin.css';
    wp_enqueue_style(
        'bia-delivery-admin',
        plugins_url( $file_path, __FILE__),
        array('wp-jquery-ui-dialog'),
        date("ymd-Gis", filemtime( plugin_dir_path(__FILE__) . $file_path ))
    );
}

function bia_delivery_render_admin_menu(){
    include( __DIR__ . '/admin/views/admin_homepage.php' );
}

function bia_delivery_render_admin_delivery_submenu(){
    include( __DIR__ . '/admin/views/admin_homepage.php' );
}

function bia_delivery_render_admin_outside_denver_deliveries(){
    include( __DIR__ . '/admin/views/admin_outside_denver_deliveries.php' );
}

add_shortcode('bia_delivery_food_assistance_form', function(){
    wp_enqueue_script('bia-delivery-jquery-validate');
    wp_enqueue_style('bia-delivery-public-form');
    wp_enqueue_script('bia-delivery-public-form');
    wp_localize_script( 'bia-delivery-public-form', 'settings', array(
        'ajaxurl' => admin_url( 'admin-ajax.php' )
    ));
    ob_start();
    include(__DIR__ . '/public/views/food_assistance_form.php');
    return ob_get_clean();
});

function bia_delivery_ajax_bia_recieve_form(){
    if (!wp_verify_nonce($_POST['bia_delivery_nonce'],'bia_delivery_nonce') ) {
        wp_send_json_error( 'Sorry, this form was submitted incorrectly' );
        wp_die();
    }

    global $wpdb;

    $required_vars = array('first_name', 'last_name', 'phone', 'address', 'zip', 'num_in_house', 'date_of_birth');
    foreach($required_vars as $required_var){
        if (empty($_POST[$required_var] )){
            wp_send_json_error( 'There was a missing value in your form, please try again');
            wp_die();
        }
    }

    $first_name = ucwords(trim($_POST['first_name']));
    $last_name = ucwords(trim($_POST['last_name']));
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $zip = trim($_POST['zip']);
    $special_instructions = trim($_POST['special_instructions']);
    $is_disabled = isset($_POST['is_disabled']) ? (bool)$_POST['is_disabled'] : false;
    $is_able_to_text = isset($_POST['is_able_to_text']) ? (bool)$_POST['is_able_to_text'] : false;
    $num_in_house = (int)$_POST['num_in_house'];
    $date_of_birth = trim($_POST['date_of_birth']);

    $table_name = $wpdb->prefix . 'bia_food_requests';
    $sql = $wpdb->prepare(
                "SELECT COUNT(*) FROM `$table_name`
                WHERE first_name LIKE %s AND last_name LIKE %s AND address LIKE %s",
                $first_name,
                $last_name,
                $address
            );
    $count_unique = (int)$wpdb->get_var($sql);

    if ($count_unique > 0){
        wp_send_json_error( 'Sorry, you have already signed up for food delivery' );
        wp_die();
    }

    $pd_district = bia_delivery_get_police_district($zip);

    $data = array(
        'first_name' => $first_name,
        'last_name' => $last_name,
        'phone' => $phone,
        'email' => $email,
        'address' => $address,
        'zip' => $zip,
        'pd_district_id' => $pd_district,
        'special_instructions' => $special_instructions,
        'is_disabled' => $is_disabled,
        'is_able_to_text' => $is_able_to_text,
        'num_in_house' => $num_in_house,
        'sign_up_date' => date( 'Y-m-d H:i:s' ),
        'date_of_birth' => $date_of_birth
    );

    $format = array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s');


    if ($pd_district < 1){
        $table_name = $wpdb->prefix.'bia_food_requests_out_of_bounds';
    }

    $success = $wpdb->insert($table_name, $data, $format);

    if ($success) {
        wp_send_json_success( 'Thank You for your submission!' );
        wp_die();
    } else {
        wp_send_json_error( 'There was an error in your form, please try again' );
        wp_die();
    }
}
add_action('wp_ajax_nopriv_bia_recieve_form', 'bia_delivery_ajax_bia_recieve_form');
add_action('wp_ajax_bia_recieve_form', 'bia_delivery_ajax_bia_recieve_form');

// Used to update the data on the admin page
function bia_delivery_ajax_bia_complete_delivery(){
    $required_vars = array('food_request_id', 'id_array');

    foreach($required_vars as $required_var){
        if (empty($_POST[$required_var])){
            wp_send_json_error("Empty variable $required_var");
            wp_die();
        }
    }

    $food_request_id = (int)$_POST['food_request_id'];
    $ids = json_decode(stripslashes($_POST['id_array']));

    global $wpdb;

    $table_name = $wpdb->prefix . 'bia_food_deliveries';

    foreach($ids as $id){
        $wpdb->update(
            $table_name,
            array(
                'is_complete' => 1
            ),
            array(
                'food_request_id' => $food_request_id,
                'id' => $id
            ),
            array('%d', '%d')
        );
    }

    wp_send_json_success();
    wp_die();

}
add_action('wp_ajax_bia_complete_delivery', 'bia_delivery_ajax_bia_complete_delivery');


function bia_delivery_ajax_bia_delete_delivery(){
    $required_vars = array('food_request_id', 'id_array');

    foreach($required_vars as $required_var){
        if (empty($_POST[$required_var])){
            wp_send_json_error("Field: $required_var was empty");
            wp_die();
        }
    }

    $food_request_id = (int)$_POST['food_request_id'];


    $ids = json_decode(stripslashes($_POST['id_array']));
    global $wpdb;

    $table_name = $wpdb->prefix . 'bia_food_deliveries';

    foreach($ids as $id){
        $wpdb->delete(
            $table_name,
            array(
                'food_request_id' => $food_request_id,
                'id' => $id
            ),
            array('%d', '%d')
        );
    }

    wp_send_json_success();
    wp_die();

}
add_action('wp_ajax_bia_delete_delivery', 'bia_delivery_ajax_bia_delete_delivery');


function bia_delivery_ajax_bia_schedule_time(){
    $food_request_id = (empty($_POST['food_request_id'])) ? '' : (int)$_POST['food_request_id'];
    $scheduled_time = (empty($_POST['scheduled_time'])) ? '' : $_POST['scheduled_time'];
    $wp_user_id = (empty($_POST['wp_user_id'])) ? '' : (int)$_POST['wp_user_id'];

    if (!user_can($wp_user_id, 'remove_users')){
        wp_send_json_error("You do not have privlidge to schedule");
        wp_die();
    }

    // JQuery sends ISO time
    $timestamp = strtotime($scheduled_time);

    if ($timestamp < time()){
        $requested_date = get_date_from_gmt($timestamp, 'm/d g:i a');
        wp_send_json_error("$requested_date is not in the future. Try again");
        wp_die();
    }

    // store ISO time in database as DATETIME
    $scheduled_datetime = date('Y-m-d H:i:s', $timestamp);

    global $wpdb;

    $table_name = $wpdb->prefix . 'bia_food_deliveries';


    $wpdb->insert(
                $table_name,
                array(
                    'food_request_id' => $food_request_id,
                    'scheduled_time' => $scheduled_datetime
                ),
                array('%d', '%s')
            );

    wp_send_json_success();
    wp_die();
}
add_action('wp_ajax_bia_schedule_time', 'bia_delivery_ajax_bia_schedule_time');


function bia_delivery_ajax_bia_get_people_table(){
    $html_data = '';
    $district_num = empty($_POST["district"]) ? 0 : (int)$_POST["district"];

    global $wpdb;

    $table_name = $wpdb->prefix . 'bia_food_requests';
    $sql = $wpdb->prepare( "SELECT * FROM `$table_name` WHERE pd_district_id = %d", $district_num);
    $entries = $wpdb->get_results($sql);

    foreach ($entries as $person){
        $food_request_id = $person->id;
        $table_name = $wpdb->prefix . 'bia_food_deliveries';
        $sql = $wpdb->prepare("SELECT * FROM `$table_name` WHERE food_request_id = %d ORDER BY scheduled_time DESC", $food_request_id);
        $most_recent = $wpdb->get_row($sql);
        $last_delivery = '------';
        if (isset($most_recent)){
            $last_delivery = get_date_from_gmt($most_recent->scheduled_time, 'm/d g:i a');
        }
        $is_complete_text = (empty($most_recent->is_complete)) ? 'No': 'Yes';

        $html_data .= '<tr>';
        $html_data .= "<td>{$person->first_name}</td>";
        $html_data .= "<td>{$person->last_name}</td>";
        $disabled_text = ($person->is_disabled) ? 'Yes' : 'No';
        $html_data .= "<td>$disabled_text</td>";
        $html_data .= "<td>{$person->num_in_house}</td>";
        $html_data .= "<td>$last_delivery</td>";
        $html_data .= "<td>$is_complete_text</td>";
        $html_data .= "<td><button class='bia-view-btn' value='$food_request_id'>View</button></td>";
        $html_data .= '</tr>';
    }

    wp_send_json_success($html_data);
    wp_die();

}
add_action('wp_ajax_bia_get_people_table', 'bia_delivery_ajax_bia_get_people_table');


function bia_delivery_ajax_bia_get_person_modal(){
    if (empty($_POST['food_request_id'])){
        wp_send_json_error( "No id provided" );
        wp_die();
    }
    $food_request_id = (int)$_POST['food_request_id'];
    global $wpdb;
    $table_name = $wpdb->prefix . 'bia_food_requests';
    $sql = $wpdb->prepare("SELECT * FROM `$table_name` WHERE id = %d", $food_request_id);
    $person_details = $wpdb->get_row($sql);

    $table_name = $wpdb->prefix . 'bia_food_deliveries';
    $sql = $wpdb->prepare("SELECT * FROM `$table_name` WHERE food_request_id = %d", $food_request_id);
    $delivery_details = $wpdb->get_results($sql);

    $is_disabled_text = ($person_details->is_disabled) ? 'Yes' : 'No';
    $date_of_birth = (empty($person_details->date_of_birth)) ? 'N/A' : $person_details->date_of_birth;
    $phone_num_text = bia_delivery_phone_number_format($person_details->phone);
    $html_data = '';

    $html_data .= "<p><strong>Name:</strong> {$person_details->first_name} {$person_details->last_name} </p>";
    $html_data .= "<p><strong>DOB:</strong> {$date_of_birth} </p>";
    $html_data .= "<p><strong>Street Address:</strong> {$person_details->address}</p>";
    $html_data .= "<p><strong>Zip:</strong> {$person_details->zip}</p>";
    $html_data .= "<p><strong>65+ or Disability:</strong> {$is_disabled_text}</p>";
    $html_data .= "<p><strong>Phone:</strong>&nbsp;<a href='tel'>$phone_num_text</a></p>";
    $html_data .= "<p><strong>Email:</strong> {$person_details->email}</p>";
    $html_data .= "<p><strong>Special Insructions:</strong> {$person_details->special_instructions}</p>";
    $html_data .= "<p><strong>Number In House:</strong> {$person_details->num_in_house}</p>";
    $html_data .= "<div class='food_request_id' data-request-id='$food_request_id' style='display:none'></div>";
    $html_data .= "<p><strong>Deliveries:</strong></p>";

    foreach($delivery_details as $delivery){
        $order_time = get_date_from_gmt($delivery->scheduled_time, 'm/d g:i a');
        $html_data .= '<div class="bia-delivery-checkbox">';
        if ($delivery->is_complete){
            $html_data .= "<p class='bia-delivery-checkbox-text'>&emsp;$order_time ---- DELIVERED</p>";

        } else{
            $html_data .= "<input type='checkbox' value='{$delivery->id}'>";
            $html_data .= "<p>&emsp;$order_time</p>";
        }
        $html_data .= '</div>';
    }
    if (empty($delivery_details)){
        $html_data .= "<p>None</p>";
    }

    wp_send_json_success($html_data);
    wp_die();
}
add_action('wp_ajax_bia_get_person_modal', 'bia_delivery_ajax_bia_get_person_modal');


function bia_delivery_ajax_bia_get_person_schedule_modal(){
    if (empty($_POST['food_request_id'])){
        wp_send_json_error( "No id provided" );
        wp_die();
    }
    $food_request_id = (int)$_POST['food_request_id'];

    global $wpdb;

    $table_name = $wpdb->prefix . 'bia_food_requests';
    $sql = $wpdb->prepare("SELECT * FROM `$table_name` WHERE id = %d", $food_request_id);
    $person_details = $wpdb->get_row($sql);

    $table_name = $wpdb->prefix . 'bia_food_deliveries';
    $sql = $wpdb->prepare("SELECT * FROM `$table_name` WHERE food_request_id = %d", $food_request_id);
    $delivery_details = $wpdb->get_results($sql);

    $is_disabled_text = ($person_details->is_disabled) ? 'Yes' : 'No';
    $phone_num_text = bia_delivery_phone_number_format($person_details->phone);
    $date_of_birth = (empty($person_details->date_of_birth)) ? 'N/A' : $person_details->date_of_birth;

    $html_data = '';
    $html_data .= "<p><strong>Name:</strong> {$person_details->first_name} {$person_details->last_name} </p>";
    $html_data .= "<p><strong>DOB:</strong> {$date_of_birth} </p>";
    $html_data .= "<p><strong>Street Address:</strong> {$person_details->address}</p>";
    $html_data .= "<p><strong>Zip:</strong> {$person_details->zip}</p>";
    $html_data .= "<p><strong>65+ or Disability:</strong> {$is_disabled_text}</p>";
    $html_data .= "<p><strong>Phone:</strong>&nbsp;<a href='tel'>$phone_num_text</a></p>";
    $html_data .= "<p><strong>Email:</strong> {$person_details->email}</p>";
    $html_data .= "<p><strong>Special Insructions:</strong> {$person_details->special_instructions}</p>";
    $html_data .= "<p><strong>Number In House:</strong> {$person_details->num_in_house}</p>";
    $html_data .= "<p><strong>Deliveries:</strong></p>";


    foreach($delivery_details as $delivery){
        $order_time = get_date_from_gmt($delivery->scheduled_time, 'm/d g:i a');
        $delivered_text = ($delivery->is_complete) ? '---- DELIVERED' : '';
        $html_data .= '<div class="bia-delivery-checkbox">';
        $html_data .= "<input type='checkbox' value='{$delivery->id}'>";
        $html_data .= "<p>&#8209;&nbsp;$order_time $delivered_text</p>";
        $html_data .= "</div>";
    }

    if (empty($delivery_details)){
        $html_data .= "<p>None</p>";
    }

    $html_data .= "<hr>";

    $html_data .= "<div class='food_request_id' data-request-id='$food_request_id' style='display:none'></div>";
    $html_data .= "<label for='bia-datepicker'><strong>Select Date To Schedule New Pickup:</strong></label>";
    $html_data .= "<input type='datetime-local' id='bia-datepicker' name='bia-datepicker' value=''/>";

    wp_send_json_success($html_data);
    wp_die();
}
add_action('wp_ajax_bia_get_person_schedule_modal', 'bia_delivery_ajax_bia_get_person_schedule_modal');


/**********Out of Denver Deliveries******************************************************************************/

// Used to update the data on the admin page
function bia_delivery_ajax_bia_complete_delivery_outside_denver(){
    $required_vars = array('food_request_id', 'id_array');

    foreach($required_vars as $required_var){
        if (empty($_POST[$required_var])){
            wp_send_json_error("Empty variable $required_var");
            wp_die();
        }
    }

    $food_request_id = (int)$_POST['food_request_id'];
    $ids = json_decode(stripslashes($_POST['id_array']));

    global $wpdb;

    $table_name = $wpdb->prefix . 'bia_food_deliveries_out_of_bounds';

    foreach($ids as $id){
        $wpdb->update(
            $table_name,
            array(
                'is_complete' => 1
            ),
            array(
                'food_request_id' => $food_request_id,
                'id' => $id
            ),
            array('%d', '%d')
        );
    }

    wp_send_json_success();
    wp_die();

}
add_action('wp_ajax_bia_complete_delivery_outside_denver', 'bia_delivery_ajax_bia_complete_delivery_outside_denver');


function bia_delivery_ajax_bia_delete_delivery_outside_denver(){
    $required_vars = array('food_request_id', 'id_array');

    foreach($required_vars as $required_var){
        if (empty($_POST[$required_var])){
            wp_send_json_error("Field: $required_var was empty");
            wp_die();
        }
    }

    $food_request_id = (int)$_POST['food_request_id'];


    $ids = json_decode(stripslashes($_POST['id_array']));
    global $wpdb;

    $table_name = $wpdb->prefix . 'bia_food_deliveries_out_of_bounds';

    foreach($ids as $id){
        $wpdb->delete(
            $table_name,
            array(
                'food_request_id' => $food_request_id,
                'id' => $id
            ),
            array('%d', '%d')
        );
    }

    wp_send_json_success();
    wp_die();

}
add_action('wp_ajax_bia_delete_delivery_outside_denver', 'bia_delivery_ajax_bia_delete_delivery_outside_denver');


function bia_delivery_ajax_bia_schedule_time_outside_denver(){
    $food_request_id = (empty($_POST['food_request_id'])) ? '' : (int)$_POST['food_request_id'];
    $scheduled_time = (empty($_POST['scheduled_time'])) ? '' : $_POST['scheduled_time'];
    $wp_user_id = (empty($_POST['wp_user_id'])) ? '' : (int)$_POST['wp_user_id'];

    if (!user_can($wp_user_id, 'remove_users')){
        wp_send_json_error("You do not have privlidge to schedule");
        wp_die();
    }

    // JQuery sends ISO time
    $timestamp = strtotime($scheduled_time);

    if ($timestamp < time()){
        $requested_date = get_date_from_gmt($timestamp, 'm/d g:i a');
        wp_send_json_error("$requested_date is not in the future. Try again");
        wp_die();
    }

    // store ISO time in database as DATETIME
    $scheduled_datetime = date('Y-m-d H:i:s', $timestamp);

    global $wpdb;

    $table_name = $wpdb->prefix . 'bia_food_deliveries_out_of_bounds';


    $wpdb->insert(
                $table_name,
                array(
                    'food_request_id' => $food_request_id,
                    'scheduled_time' => $scheduled_datetime
                ),
                array('%d', '%s')
            );

    wp_send_json_success();
    wp_die();
}
add_action('wp_ajax_bia_schedule_time_outside_denver', 'bia_delivery_ajax_bia_schedule_time_outside_denver');


function bia_delivery_ajax_bia_get_people_table_outside_denver(){
    $html_data = '';
    $county_id = empty($_POST["county_id"]) ? 0 : (int)$_POST["county_id"];

    global $wpdb;

    $table_name = $wpdb->prefix . 'bia_food_requests_out_of_bounds';
    $sql = $wpdb->prepare( "SELECT * FROM `$table_name` WHERE county_id = %d ORDER BY zip ASC", $county_id);
    $entries = $wpdb->get_results($sql);

    foreach ($entries as $person){
        $food_request_id = $person->id;
        $table_name = $wpdb->prefix . 'bia_food_deliveries_out_of_bounds';
        $sql = $wpdb->prepare("SELECT * FROM `$table_name` WHERE food_request_id = %d ORDER BY scheduled_time DESC", $food_request_id);
        $most_recent = $wpdb->get_row($sql);
        $last_delivery = '------';
        if (isset($most_recent)){
            $last_delivery = get_date_from_gmt($most_recent->scheduled_time, 'm/d g:i a');
        }
        $is_complete_text = (empty($most_recent->is_complete)) ? 'No': 'Yes';

        $html_data .= '<tr>';
        $html_data .= "<td>{$person->zip}</td>";
        $html_data .= "<td>{$person->first_name}</td>";
        $html_data .= "<td>{$person->last_name}</td>";
        $disabled_text = ($person->is_disabled) ? 'Yes' : 'No';
        $html_data .= "<td>$disabled_text</td>";
        $html_data .= "<td>{$person->num_in_house}</td>";
        $html_data .= "<td>$last_delivery</td>";
        $html_data .= "<td>$is_complete_text</td>";
        $html_data .= "<td><button class='bia-view-btn' value='$food_request_id'>View</button></td>";
        $html_data .= '</tr>';
    }

    wp_send_json_success($html_data);
    wp_die();

}
add_action('wp_ajax_bia_get_people_table_outside_denver', 'bia_delivery_ajax_bia_get_people_table_outside_denver');

function bia_delivery_ajax_bia_get_person_modal_outside_denver(){
    if (empty($_POST['food_request_id'])){
        wp_send_json_error( "No id provided" );
        wp_die();
    }
    $food_request_id = (int)$_POST['food_request_id'];
    global $wpdb;
    $table_name = $wpdb->prefix . 'bia_food_requests_out_of_bounds';
    $sql = $wpdb->prepare("SELECT * FROM `$table_name` WHERE id = %d", $food_request_id);
    $person_details = $wpdb->get_row($sql);

    $table_name = $wpdb->prefix . 'bia_food_deliveries_out_of_bounds';
    $sql = $wpdb->prepare("SELECT * FROM `$table_name` WHERE food_request_id = %d", $food_request_id);
    $delivery_details = $wpdb->get_results($sql);

    $is_disabled_text = ($person_details->is_disabled) ? 'Yes' : 'No';
    $date_of_birth = (empty($person_details->date_of_birth)) ? 'N/A' : $person_details->date_of_birth;
    $phone_num_text = bia_delivery_phone_number_format($person_details->phone);
    $html_data = '';

    $html_data .= "<p><strong>Name:</strong> {$person_details->first_name} {$person_details->last_name} </p>";
    $html_data .= "<p><strong>DOB:</strong> {$date_of_birth} </p>";
    $html_data .= "<p><strong>Street Address:</strong> {$person_details->address}</p>";
    $html_data .= "<p><strong>Zip:</strong> {$person_details->zip}</p>";
    $html_data .= "<p><strong>65+ or Disability:</strong> {$is_disabled_text}</p>";
    $html_data .= "<p><strong>Phone:</strong>&nbsp;<a href='tel'>$phone_num_text</a></p>";
    $html_data .= "<p><strong>Email:</strong> {$person_details->email}</p>";
    $html_data .= "<p><strong>Special Insructions:</strong> {$person_details->special_instructions}</p>";
    $html_data .= "<p><strong>Number In House:</strong> {$person_details->num_in_house}</p>";
    $html_data .= "<div class='food_request_id' data-request-id='$food_request_id' style='display:none'></div>";
    $html_data .= "<p><strong>Deliveries:</strong></p>";

    foreach($delivery_details as $delivery){
        $order_time = get_date_from_gmt($delivery->scheduled_time, 'm/d g:i a');
        $html_data .= '<div class="bia-delivery-checkbox">';
        if ($delivery->is_complete){
            $html_data .= "<p class='bia-delivery-checkbox-text'>&emsp;$order_time ---- DELIVERED</p>";

        } else{
            $html_data .= "<input type='checkbox' value='{$delivery->id}'>";
            $html_data .= "<p>&emsp;$order_time</p>";
        }
        $html_data .= '</div>';
    }
    if (empty($delivery_details)){
        $html_data .= "<p>None</p>";
    }

    wp_send_json_success($html_data);
    wp_die();
}
add_action('wp_ajax_bia_get_person_modal_outside_denver', 'bia_delivery_ajax_bia_get_person_modal_outside_denver');


function bia_delivery_ajax_bia_get_person_schedule_modal_outside_denver(){
    if (empty($_POST['food_request_id'])){
        wp_send_json_error( "No id provided" );
        wp_die();
    }
    $food_request_id = (int)$_POST['food_request_id'];

    global $wpdb;

    $table_name = $wpdb->prefix . 'bia_food_requests_out_of_bounds';
    $sql = $wpdb->prepare("SELECT * FROM `$table_name` WHERE id = %d", $food_request_id);
    $person_details = $wpdb->get_row($sql);

    $table_name = $wpdb->prefix . 'bia_food_deliveries_out_of_bounds';
    $sql = $wpdb->prepare("SELECT * FROM `$table_name` WHERE food_request_id = %d", $food_request_id);
    $delivery_details = $wpdb->get_results($sql);

    $is_disabled_text = ($person_details->is_disabled) ? 'Yes' : 'No';
    $phone_num_text = bia_delivery_phone_number_format($person_details->phone);
    $date_of_birth = (empty($person_details->date_of_birth)) ? 'N/A' : $person_details->date_of_birth;

    $html_data = '';
    $html_data .= "<p><strong>Name:</strong> {$person_details->first_name} {$person_details->last_name} </p>";
    $html_data .= "<p><strong>DOB:</strong> {$date_of_birth} </p>";
    $html_data .= "<p><strong>Street Address:</strong> {$person_details->address}</p>";
    $html_data .= "<p><strong>Zip:</strong> {$person_details->zip}</p>";
    $html_data .= "<p><strong>65+ or Disability:</strong> {$is_disabled_text}</p>";
    $html_data .= "<p><strong>Phone:</strong>&nbsp;<a href='tel'>$phone_num_text</a></p>";
    $html_data .= "<p><strong>Email:</strong> {$person_details->email}</p>";
    $html_data .= "<p><strong>Special Insructions:</strong> {$person_details->special_instructions}</p>";
    $html_data .= "<p><strong>Number In House:</strong> {$person_details->num_in_house}</p>";
    $html_data .= "<p><strong>Deliveries:</strong></p>";


    foreach($delivery_details as $delivery){
        $order_time = get_date_from_gmt($delivery->scheduled_time, 'm/d g:i a');
        $delivered_text = ($delivery->is_complete) ? '---- DELIVERED' : '';
        $html_data .= '<div class="bia-delivery-checkbox">';
        $html_data .= "<input type='checkbox' value='{$delivery->id}'>";
        $html_data .= "<p>&#8209;&nbsp;$order_time $delivered_text</p>";
        $html_data .= "</div>";
    }

    if (empty($delivery_details)){
        $html_data .= "<p>None</p>";
    }

    $html_data .= "<hr>";

    $html_data .= "<div class='food_request_id' data-request-id='$food_request_id' style='display:none'></div>";
    $html_data .= "<label for='bia-datepicker'><strong>Select Date To Schedule New Pickup:</strong></label>";
    $html_data .= "<input type='datetime-local' id='bia-datepicker' name='bia-datepicker' value=''/>";

    wp_send_json_success($html_data);
    wp_die();
}
add_action('wp_ajax_bia_get_person_schedule_modal_outside_denver', 'bia_delivery_ajax_bia_get_person_schedule_modal_outside_denver');

/***************************************************************************************************************/

function bia_delivery_get_police_district($zip): int {
    $pd_dictionary = array(
        '80204' => 1,
        '80211' => 1,
        '80212' => 1,
        '80221' => 1,

        '80205' => 2,
        '80206' => 2,
        '80207' => 2,
        '80216' => 2,
        '80220' => 2,

        '80209' => 3,
        '80210' => 3,
        '80222' => 3,
        '80224' => 3,
        '80230' => 3,
        '80231' => 3,
        '80237' => 3,
        '80246' => 3,
        '80247' => 3,

        '80110' => 4,
        '80123' => 4,
        '80219' => 4,
        '80223' => 4,
        '80235' => 4,
        '80236' => 4,

        '80238' => 5,
        '80239' => 5,
        '80249' => 5,

        '80202' => 6,
        '80203' => 6,
        '80218' => 6
    );

    if(array_key_exists($zip, $pd_dictionary)){
        return (int)$pd_dictionary[$zip];
    }
    else {
        return -1;
    }

}

function bia_delivery_phone_number_format($number): string {
    // Allow only Digits, remove all other characters.
    $number = preg_replace("/[^\d]/","",$number);

    // get number length.
    $length = strlen($number);

   // if number = 10
   if($length == 10) {
    $number = preg_replace("/^1?(\d{3})(\d{3})(\d{4})$/", "$1-$2-$3", $number);
   }

    return $number;

}
