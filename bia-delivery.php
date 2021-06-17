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
$bia_delivery_db_version = '2.0';

register_activation_hook( __FILE__, "bia_delivery_initialize_database" );
function bia_delivery_initialize_database(){
    global $wpdb;
    global $bia_delivery_db_version;;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE `{$wpdb->prefix}bia_food_requests` (
        id mediumint unsigned NOT NULL auto_increment,
        first_name varchar(50) NOT NULL,
        last_name varchar(50) NOT NULL,
        date_of_birth DATE,
        phone varchar(20) NOT NULL,
        email varchar(255),
        address varchar(255) NOT NULL,
        apartment_num varchar(50),
        city varchar(50) NOT NULL,
        zip char(5) NOT NULL,
        county varchar(50),
        pd_district_id tinyint NOT NULL,
        sign_up_date datetime NOT NULL,
        num_in_house tinyint NOT NULL,
        is_disabled boolean NOT NULL DEFAULT FALSE,
        is_over_sixty boolean,
        is_able_to_text boolean NOT NULL DEFAULT FALSE,
        monthly_income mediumint unsigned NOT NULL DEFAULT 0,
        special_instructions text,
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

    $sql = "CREATE TABLE `{$wpdb->prefix}bia_food_requests_race` (
        id mediumint unsigned NOT NULL auto_increment,
        food_request_id mediumint unsigned NOT NULL,
        american_indian boolean NOT NULL DEFAULT FALSE,
        black boolean NOT NULL DEFAULT FALSE,
        hispanic boolean NOT NULL DEFAULT FALSE,
        middle_eastern boolean NOT NULL DEFAULT FALSE,
        pacific_islander boolean NOT NULL DEFAULT FALSE,
        asian boolean NOT NULL DEFAULT FALSE,
        white boolean NOT NULL DEFAULT FALSE,
        white_hispanic boolean NOT NULL DEFAULT FALSE,
        not_listed boolean NOT NULL DEFAULT FALSE,
        unknown boolean NOT NULL DEFAULT FALSE,
        PRIMARY KEY (id),
        FOREIGN KEY (food_request_id) REFERENCES `{$wpdb->prefix}bia_food_requests`(id)
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

    $required_vars = array('first_name', 'last_name', 'date_of_birth', 'phone', 'address', 'city', 'zip', 'county', 'race', 'num_in_house', 'is_disabled', 'is_over_sixty', 'monthly_income', 'is_able_to_text');
    foreach($required_vars as $required_var){
        if (!isset($_POST[$required_var] )){
            wp_send_json_error( "There was a missing value for `$required_var` in your form, please try again");
            wp_die();
        }
    }

    $first_name = filter_var(strtoupper(trim($_POST['first_name'])), FILTER_SANITIZE_STRING);
    $last_name = filter_var(strtoupper(trim($_POST['last_name'])), FILTER_SANITIZE_STRING);
    $date_of_birth = filter_var(trim($_POST['date_of_birth']), FILTER_SANITIZE_STRING);
    $phone = filter_var(trim($_POST['phone']), FILTER_SANITIZE_NUMBER_INT);
    $email = isset($_POST['email']) ? filter_var(strtolower(trim($_POST['email'])), FILTER_SANITIZE_EMAIL) : "";
    $address = filter_var(trim($_POST['address']), FILTER_SANITIZE_STRING);
    $apartment_num = filter_var(isset($_POST['address']) ? trim($_POST['apartment_num']) : "", FILTER_SANITIZE_STRING);
    $city = filter_var(strtoupper(trim($_POST['city'])), FILTER_SANITIZE_STRING);
    $zip = (int)filter_var(trim($_POST['zip']), FILTER_SANITIZE_NUMBER_INT);
    $county = filter_var(strtoupper(trim($_POST['county'])), FILTER_SANITIZE_STRING);
    $race = $_POST['race'];
    $num_in_house = (int)filter_var($_POST['num_in_house'], FILTER_SANITIZE_NUMBER_INT);
    $is_disabled = isset($_POST['is_disabled']) ? (bool)$_POST['is_disabled'] : false;
    $is_over_sixty = isset($_POST['is_over_sixty']) ? (bool)$_POST['is_over_sixty'] : false;
    $is_able_to_text = isset($_POST['is_able_to_text']) ? (bool)$_POST['is_able_to_text'] : false;
    $monthly_income = filter_var(bia_currency_to_int(trim($_POST['monthly_income'])), FILTER_SANITIZE_NUMBER_INT);
    $special_instructions = filter_var(trim($_POST['special_instructions']), FILTER_SANITIZE_STRING);

    // format address to combine elements
    $address = "$address, $apartment_num, $city, CO $zip";

    $table_name = $wpdb->prefix . 'bia_food_requests';
    $sql = $wpdb->prepare(
                "SELECT COUNT(*) FROM `$table_name`
                WHERE first_name LIKE %s AND last_name LIKE %s AND phone LIKE %s",
                $first_name,
                $last_name,
                $phone
            );
    $count_unique = (int)$wpdb->get_var($sql);

    if ($count_unique > 0){
        wp_send_json_error( 'Sorry, you have already signed up for food delivery. Please contact Benefits In Action directly to change information' );
        wp_die();
    }

    $pd_district = bia_delivery_get_police_district($zip);

    $data = array(
        'first_name' => $first_name,
        'last_name' => $last_name,
        'date_of_birth' => $date_of_birth,
        'phone' => $phone,
        'email' => $email,
        'address' => $address,
        'apartment_num' => $apartment_num,
        'city' => $city,
        'zip' => $zip,
        'county' => $county,
        'pd_district_id' => $pd_district,
        'sign_up_date' => date( 'Y-m-d H:i:s' ),
        'num_in_house' => $num_in_house,
        'is_disabled' => $is_disabled,
        'is_over_sixty' => $is_over_sixty,
        'is_able_to_text' => $is_able_to_text,
        'monthly_income' => $monthly_income,
        'special_instructions' => $special_instructions
    );

    $format = array(
      '%s',
      '%s',
      '%s',
      '%s',
      '%s',
      '%s',
      '%s',
      '%s',
      '%d',
      '%s',
      '%d',
      '%s',
      '%d',
      '%d',
      '%d',
      '%d',
      '%d',
      '%s'
    );

    $success = $wpdb->insert($table_name, $data, $format);
    $request_id = $wpdb->insert_id;

    $race_table = array(
      "food_request_id" => $request_id,
      "american_indian" => false,
      "black" => false,
      "hispanic" => false,
      "middle_eastern" => false,
      "pacific_islander" => false,
      "asian" => false,
      "white" => false,
      "white_hispanic" => false,
      "not_listed" => false,
      "unknown" => false
    );

    foreach($race as $race_item){
      if (array_key_exists($race_item, $race_table)){
        $race_table[$race_item] = true;
      }
    }

    $format = array('%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d');
    $table_name = "{$wpdb->prefix}bia_food_requests_race";
    $success = $success && $wpdb->insert($table_name, $race_table, $format);

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

function bia_currency_to_int($currency): int {
  return (int)preg_replace("/[^0-9]/", "", $currency);
}
