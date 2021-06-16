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
        'bia-delivery-jquery-phone-number-mask',
        'https://unpkg.com/jquery-input-mask-phone-number@1.0.14/dist/jquery-input-mask-phone-number.js',
        array('jquery'),
        false,
        true
    );

    wp_register_script(
        'bia-delivery-jquery-validate',
        'https://cdn.jsdelivr.net/npm/jquery-validation@1.19.1/dist/jquery.validate.min.js',
        array('jquery'),
        false,
        true
    );

    wp_register_script(
        'bia-delivery-jquery-validate-additional-methods',
        'https://cdn.jsdelivr.net/npm/jquery-validation@1.19.1/dist/additional-methods.min.js',
        array('jquery'),
        false,
        true
    );

    wp_register_script(
        'bia-delivery-autonumeric',
        'https://cdn.jsdelivr.net/npm/autonumeric@4.1.0',
        array(),
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

add_shortcode('bia_delivery_food_assistance_form', function(){
    wp_enqueue_script('bia-delivery-jquery-phone-number-mask');
    wp_enqueue_script('bia-delivery-jquery-validate');
    wp_enqueue_script('bia-delivery-jquery-validate-additional-methods');
    wp_enqueue_script('bia-delivery-autonumeric');

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
