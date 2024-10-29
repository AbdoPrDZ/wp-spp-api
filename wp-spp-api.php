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

if (!defined('WP_SPP_API_DIR')) {
  define('WP_SPP_API_DIR', __DIR__ . '/');
}

if (!defined('WP_SPP_API_FILE')) {
  define('WP_SPP_API_FILE', __FILE__);
}

// Register plugin
include 'register.php';

// Unregister plugin
include 'unregister.php';

// Menu & Pages
include 'pages/index.php';

// API routes
include 'api-routes.php';
