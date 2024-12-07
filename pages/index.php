<?php

function attach_menu() {
  add_menu_page(
		'WP SPP API',           // Page title
		'SPP Hosts',            // Menu title
		'manage_options',       // Capability
		'wp-spp-api',           // Menu slug
		'home_page_content',    // Callback function
		'dashicons-networking', // Dashicon class
		6                       // Position
	);
	add_submenu_page(
		'wp-spp-api',        // Parent slug
		'Manage Hosts',      // Page title
		'Hosts',             // Menu title
		'manage_options',    // Capability
		'manage-hosts',      // Menu slug
		'hosts_page_content' // Callback function
	);
	add_submenu_page(
		'wp-spp-api',         // Parent slug
		'Manage Offers',      // Page title
		'Offers',             // Menu title
		'manage_options',     // Capability
		'manage-offers',      // Menu slug
		'offers_page_content' // Callback function
	);
	add_submenu_page(
		'wp-spp-api',                // Parent slug
		'Manage Subscriptions',      // Page title
		'Subscriptions',             // Menu title
		'manage_options',            // Capability
		'manage-subscriptions',      // Menu slug
		'subscriptions_page_content' // Callback function
	);
}
add_action('admin_menu', 'attach_menu');

function validate_fields($fields, &$errors) {
  $errors = [];
  $valid = true;
  foreach ($fields as $field)
    if ($_POST[$field] == '') {
      $errors[$field] = "Field $field is required";
      $valid = false;
    }
  return $valid;
}

function home_page_content() {
  // Save the payment information
  if (isset($_POST['ccp_rip']))
    update_option('ccp_rip', sanitize_text_field($_POST['ccp_rip']));
  if (isset($_POST['paypal_email']))
    update_option('paypal_email', sanitize_text_field($_POST['paypal_email']));
  if (isset($_POST['paysera_email']))
    update_option('paysera_email', sanitize_text_field($_POST['paysera_email']));

  // Display the home page
  include 'home-page.php';
}

function hosts_page_content() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'spp_hosts';

	// Handle form submission for adding/editing/deleting hosts
	if (isset($_POST['action'])) {
    $errors = [];
    switch ($_POST['action']) {
			case 'add':
        if (!validate_fields(['name', 'host', 'description'], $errors)) break;
				$name              = sanitize_text_field($_POST['name']);
				$host              = sanitize_text_field($_POST['host']);
				$preview_image_url = sanitize_textarea_field($_POST['preview_image_url']);
				$cookie            = $_POST['cookie'];
        $cookie            = empty($cookie) ? null : $cookie;
        $blocked_urls      = $_POST['blocked_urls'];
        $blocked_urls      = empty($blocked_urls) ? null : $blocked_urls;
				$description       = sanitize_textarea_field($_POST['description']);

        $wpdb->insert($table_name, compact('name', 'host', 'preview_image_url', 'cookie', 'blocked_urls', 'description'));

        break;

			case 'edit':
        if (!validate_fields(['name', 'host', 'description'], $errors)) break;
				$id                = intval($_POST['id']);
				$name              = sanitize_text_field($_POST['name']);
				$host              = sanitize_text_field($_POST['host']);
				$preview_image_url = sanitize_textarea_field($_POST['preview_image_url']);
				$cookie            = $_POST['cookie'];
        $cookie            = empty($cookie) ? null : $cookie;
        $blocked_urls      = $_POST['blocked_urls'];
        $blocked_urls      = empty($blocked_urls) ? null : $blocked_urls;
				$description       = sanitize_textarea_field($_POST['description']);

				$wpdb->update($table_name, compact('name', 'host', 'preview_image_url', 'cookie', 'blocked_urls', 'description'), ['id' => $id]);

        break;

			case 'delete':
				$id = intval($_POST['id']);
				$wpdb->delete($table_name, ['id' => $id]);
				break;
		}
	}

	// Display the form and the list of hosts
	include 'hosts-page.php';
}

function offers_page_content() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'spp_offers';
	$prices_table = $wpdb->prefix . 'spp_prices';

	// Handle form submission for adding/editing/deleting offers
	if (isset($_POST['action'])) {
    $errors = [];
		switch ($_POST['action']) {
			case 'add':
        if (!validate_fields(['host_id', 'description', 'prices', 'status'], $errors)) break;

				$host_id     = intval($_POST['host_id']);
				$description = sanitize_textarea_field($_POST['description']);
				$pricesText  = wp_unslash($_POST['prices']); // Remove slashes from input if magic quotes are enabled
        $pricesText  = htmlspecialchars_decode($pricesText); // Decode any HTML entities
				$prices      = (array) json_decode($pricesText);
				$status      = sanitize_text_field($_POST['status']);

        if (json_last_error() !== JSON_ERROR_NONE) {
					$errors['prices'] = 'Invalid JSON format in prices field "' . $pricesText .'"';
					break;
				}

        $id = $wpdb->insert($table_name, compact('host_id', 'description', 'status'));

        foreach ($prices as $price) {
          $price = (array) $price;
          $price['offer_id'] = $id;
          $wpdb->insert($prices_table, $price);
        }

				break;

			case 'edit':
        if (!validate_fields(['id', 'host_id', 'description', 'prices', 'status'], $errors)) break;

				$id          = intval($_POST['id']);
				$host_id     = intval($_POST['host_id']);
				$description = sanitize_textarea_field($_POST['description']);
				$pricesText  = wp_unslash($_POST['prices']); // Remove slashes from input if magic quotes are enabled
        $pricesText  = htmlspecialchars_decode($pricesText); // Decode any HTML entities
				$prices      = (array) json_decode($pricesText);
				$status      = sanitize_text_field($_POST['status']);

        if (json_last_error() !== JSON_ERROR_NONE) {
					$errors['prices'] = 'Invalid JSON format in prices field "' . $pricesText .'"';
					break;
				}

				$wpdb->update($table_name, compact('host_id', 'description', 'status'), ['id' => $id]);

        $wpdb->delete($prices_table, ['offer_id' => $id]);
        foreach ($prices as $price) {
          $price = (array) $price;
          $price['offer_id'] = $id;
          $wpdb->insert($prices_table, $price);
        }

				break;

			case 'delete':
				$id = intval($_POST['id']);
				$wpdb->delete($table_name, ['id' => $id]);
				break;
		}
	}

	// Display the form and the list of offers
	include 'offers-page.php';
}

function subscriptions_page_content() {
	// global $wpdb;
	// $table_name = $wpdb->prefix . 'spp_subscriptions';

	// Handle form submission for adding/editing/deleting subscriptions
	// if (isset($_POST['action'])) {
  //   $errors = [];
	// 	switch ($_POST['action']) {
	// 		case 'add':
  //       if (!validate_fields(['host_id', 'user_id', 'status', 'expired_at'], $errors)) break;
	// 			$host_id = intval($_POST['host_id']);
	// 			$user_id = intval($_POST['user_id']);
	// 			$status = sanitize_textarea_field($_POST['status']);
	// 			$expired_at = sanitize_text_field($_POST['expired_at']);
	// 			$wpdb->insert($table_name, compact('host_id', 'user_id', 'expired_at', 'status'));
	// 			break;

	// 		case 'edit':
  //       if (!validate_fields(['id', 'host_id', 'user_id', 'status', 'expired_at'], $errors)) break;
	// 			$id = intval($_POST['id']);
	// 			$host_id = intval($_POST['host_id']);
	// 			$user_id = intval($_POST['user_id']);
	// 			$status = sanitize_textarea_field($_POST['status']);
	// 			$expired_at = sanitize_text_field($_POST['expired_at']);
	// 			$wpdb->update($table_name, compact('host_id', 'user_id', 'expired_at', 'status'), ['id' => $id]);
	// 			break;

	// 		case 'delete':
	// 			$id = intval($_POST['id']);
	// 			$wpdb->delete($table_name, ['id' => $id]);
	// 			break;
	// 	}
	// }

	// Display the form and the list of subscriptions
	include 'subscriptions-page.php';
}
