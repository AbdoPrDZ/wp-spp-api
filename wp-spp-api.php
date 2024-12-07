<?php
/*
Plugin Name:  WP SPP API
Plugin URI:   https://github.com/AbdoPrDZ/wp-spp-api
Description:  A simple plugin to manage hosts and subscriptions for WordPress Smart proxy pass.
Version:      1.0.0
Author:       AbdoPrDZ
Author URI:   https://github.com/AbdoPrDZ
License:      MIT
License URI:  https://opensource.org/licenses/MIT
Text Domain:  wp-spp-api
Domain Path:  /languages
*/

/* Installation */

// define the plugin directory
if (!defined('WP_SPP_API_DIR')) {
  define('WP_SPP_API_DIR', __DIR__ . '/');
}

// define the plugin file
if (!defined('WP_SPP_API_FILE')) {
  define('WP_SPP_API_FILE', __FILE__);
}

// define the plugin version
if (!defined('WP_SPP_API_VERSION')) {
  define('WP_SPP_API_VERSION', '1.0.0');
}

// define the plugin upload directory
if (!defined('WP_SPP_API_UPLOAD_DIR')) {
  define('WP_SPP_API_UPLOAD_DIR', ABSPATH . 'wp-content/uploads/spp-api');
}

// define the plugin upload URL
if (!defined('WP_SPP_API_UPLOAD_URL')) {
  define('WP_SPP_API_UPLOAD_URL', 'wp-content/uploads/spp-api');
}

if (!function_exists('spp_load_uploads')) {
  /**
   * Load the plugin uploads
   *
   * @return void
   */
  function spp_load_uploads() {
    $default_uploads = WP_SPP_API_DIR . 'assets/uploads';
    $uploads_dir = WP_SPP_API_UPLOAD_DIR;

    if (!file_exists($uploads_dir) && !wp_mkdir_p($uploads_dir))
      return;

    $files = scandir($default_uploads);
    foreach ($files as $file) {
      if ($file == '.' || $file == '..') {
        continue;
      }

      $file_path = $default_uploads . '/' . $file;
      $new_file_path = $uploads_dir . '/' . $file;

      if (!file_exists($new_file_path)) {
        copy($file_path, $new_file_path);
      }
    }
  }
}

if (!function_exists('spp_get_uploaded_file_url')) {
  /**
   * Get the uploaded file URL
   *
   * @param string $file_name
   * @return string
   */
  function spp_get_uploaded_file_url($file_name) {
    spp_load_uploads();

    return WP_SPP_API_UPLOAD_URL . '/' . $file_name;
  }
}

if (!function_exists('spp_get_user_file_url')) {
  /**
   * Get the user file URL
   *
   * @param int $user_id
   * @param string $file_name
   * @return string
   */
  function spp_get_user_file_url($user_id, $file_name) {
    spp_load_uploads();

    return spp_get_uploaded_file_url($user_id . '/' . $file_name);
  }
}

if (!function_exists('spp_set_user_file')) {
  /**
   * Set the user file
   *
   * @param int $user_id
   * @param string $file_name
   * @param string $file_path
   * @return bool
   */
  function spp_set_user_file($user_id, $file_name, $file_path) {
    spp_load_uploads();

    $user_dir = WP_SPP_API_UPLOAD_DIR . '/' . $user_id;

    // check if the directory exists
    if (!file_exists($user_dir)) {
      // create the directory
      wp_mkdir_p($user_dir);
    }

    // move the file to the user directory
    // return move_uploaded_file($file_path, $user_dir . '/' . $file_name);
    return rename($file_path, $user_dir . '/' . $file_name);
  }
}

if (!function_exists('spp_delete_user_file')) {
  /**
   * Delete the user file
   *
   * @param int $user_id
   * @param string $file_name
   * @return bool
   */
  function spp_delete_user_file($user_id, $file_name) {
    spp_load_uploads();

    $file_path = WP_SPP_API_UPLOAD_DIR . '/' . $user_id . '/' . $file_name;

    if (file_exists($file_path)) {
      // return unlink($file_path);
      return wp_delete_file($file_path);
    }

    return false;
  }
}

if (!function_exists('spp_set_user_profile_photo')) {
  /**
   * Set the user profile photo
   *
   * @param int $user_id
   * @param string $file_path
   * @return string|null
   */
  function spp_set_user_profile_photo($user_id, $file_path) {
    $old_file = get_user_meta($user_id, 'profile_photo', true);

    if ($old_file) {
      spp_delete_user_file($user_id, $old_file);
    }

    $file_name = 'profile-photo-' . time() . '.' . pathinfo($file_path, PATHINFO_EXTENSION);

    if (spp_set_user_file($user_id, $file_name, $file_path)) {
      update_user_meta($user_id, 'profile_photo', $file_name);
      return $file_name;
    }
  }
}

if (!function_exists('spp_get_user_profile_url')) {
  /**
   * Get the user profile URL
   *
   * @param int $user_id
   * @return string
   */
  function spp_get_user_profile_url($user_id) {
    $image_path = get_user_meta($user_id, 'profile_photo', true);

    if (!$image_path)
      return spp_get_uploaded_file_url('default-profile.png');

    return spp_get_user_file_url($user_id, $image_path);
  }
}

if (!function_exists('spp_get_subscription_proof_url')) {
  /**
   * Get the subscription proof URL
   *
   * @param int $user_id
   * @param int $subscription_id
   * @return string|null
   */
  function spp_get_subscription_proof_url($user_id, $subscription_id) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'spp_subscriptions';

    $proof = $wpdb->get_var($wpdb->prepare("SELECT proof_path FROM $table_name
                                            WHERE `user_id` = $user_id AND `id` = %d", $subscription_id));

    if (!$proof)
      return null;

    return spp_get_user_file_url($user_id, "subscriptions/$subscription_id/$proof");
  }
}

if (!function_exists('spp_set_subscription_proof')) {
  /**
   * Get the subscription proof URL
   *
   * @param int $user_id
   * @param int $subscription_id
   * @return string|null
   */
  function spp_set_subscription_proof($user_id, $subscription_id, $file_path) {
    $file_name = 'proof-' . time() . '.' . pathinfo($file_path, PATHINFO_EXTENSION);

    // check if the subscriptions directory exists
    $subscriptions_dir = WP_SPP_API_UPLOAD_DIR . "/$user_id/subscriptions";

    if (!file_exists($subscriptions_dir)) {
      // create the directory
      wp_mkdir_p($subscriptions_dir);
    }

    // check if the subscription directory exists
    $subscription_dir = "$subscriptions_dir/$subscription_id";
    if (!file_exists($subscription_dir)) {
      // create the directory
      wp_mkdir_p($subscription_dir);
    }

    if (spp_set_user_file($user_id, "subscriptions/$subscription_id/$file_name", $file_path)) {
      return $file_name;
    }
  }
}

// Register plugin
include 'register.php';

// Unregister plugin
include 'unregister.php';

// Menu & Pages
include 'pages/index.php';

// API routes
include 'api-routes.php';
