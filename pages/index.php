<?php

function attach_menu() {
  add_menu_page(
		'WP SPP API',          // Page title
		'SPP Hosts',             // Menu title
		'manage_options',        // Capability
		'wp-spp-api',          // Menu slug
		'home_page_content',    // Callback function
		'dashicons-networking',  // Dashicon class
		6                        // Position
	);
	add_submenu_page(
		'wp-spp-api',           // Parent slug
		'Manage Hosts',           // Page title
		'Hosts',                  // Menu title
		'manage_options',         // Capability
		'manage-hosts',          // Menu slug
		'hosts_page_content'     // Callback function
	);
	add_submenu_page(
		'wp-spp-api',           // Parent slug
		'Manage Offers',           // Page title
		'Offers',                  // Menu title
		'manage_options',         // Capability
		'manage-offers',          // Menu slug
		'offers_page_content'     // Callback function
	);
	add_submenu_page(
		'wp-spp-api',           // Parent slug
		'Manage Subscriptions',           // Page title
		'Subscriptions',                  // Menu title
		'manage_options',         // Capability
		'manage-subscriptions',          // Menu slug
		'subscriptions_page_content'     // Callback function
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
				$name = sanitize_text_field($_POST['name']);
				$host = sanitize_text_field($_POST['host']);
				$preview_image_url = sanitize_textarea_field($_POST['preview_image_url']);
				// $cookie = sanitize_text_field($_POST['cookie']);
				$cookie = $_POST['cookie'];
        $blocked_routes = sanitize_textarea_field($_POST['blocked_routes']);
				$description = sanitize_textarea_field($_POST['description']);
				$wpdb->insert($table_name, compact('name', 'host', 'preview_image_url', 'cookie', 'blocked_routes', 'description'));
				break;

			case 'edit':
        if (!validate_fields(['name', 'host', 'description'], $errors)) break;
				$id = intval($_POST['id']);
				$name = sanitize_text_field($_POST['name']);
				$host = sanitize_text_field($_POST['host']);
				$preview_image_url = sanitize_textarea_field($_POST['preview_image_url']);
				// $cookie = sanitize_textarea_field($_POST['cookie']);
				$cookie = $_POST['cookie'];
        $blocked_routes = sanitize_textarea_field($_POST['blocked_routes']);
				$description = sanitize_textarea_field($_POST['description']);
				$wpdb->update($table_name, compact('name', 'host', 'preview_image_url', 'cookie', 'blocked_routes', 'description'), ['id' => $id]);
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

	// Handle form submission for adding/editing/deleting offers
	if (isset($_POST['action'])) {
    $errors = [];
		switch ($_POST['action']) {
			case 'add':
        if (!validate_fields(['host_id', 'description', 'prices', 'status'], $errors)) break;
				$host_id = intval($_POST['host_id']);
				$description = sanitize_textarea_field($_POST['description']);
				$pricesText = wp_unslash($_POST['prices']); // Remove slashes from input if magic quotes are enabled
        $pricesText = htmlspecialchars_decode($pricesText); // Decode any HTML entities
				$prices = json_decode($pricesText);
				if (json_last_error() !== JSON_ERROR_NONE) {
					$errors['prices'] = 'Invalid JSON format in prices field "' . $pricesText .'"';
					break;
				}
        $prices = json_encode($prices);
				$status = sanitize_text_field($_POST['status']);
				$wpdb->insert($table_name, compact('host_id', 'description', 'prices', 'status'));
				break;

			case 'edit':
        if (!validate_fields(['id', 'host_id', 'description', 'prices', 'status'], $errors)) break;
				$id = intval($_POST['id']);
				$host_id = intval($_POST['host_id']);
				$description = sanitize_textarea_field($_POST['description']);
				$pricesText = wp_unslash($_POST['prices']); // Remove slashes from input if magic quotes are enabled
        $pricesText = htmlspecialchars_decode($pricesText); // Decode any HTML entities
				$prices = json_decode($pricesText);
				if (json_last_error() !== JSON_ERROR_NONE) {
					$errors['prices'] = 'Invalid JSON format in prices field "' . $pricesText .'"';
					break;
				}
        $prices = json_encode($prices);
				$status = sanitize_text_field($_POST['status']);
				$wpdb->update($table_name, compact('host_id', 'description', 'prices', 'status'), ['id' => $id]);
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
	global $wpdb;
	$table_name = $wpdb->prefix . 'spp_subscriptions';

	// Handle form submission for adding/editing/deleting subscriptions
	if (isset($_POST['action'])) {
    $errors = [];
		switch ($_POST['action']) {
			case 'add':
        if (!validate_fields(['host_id', 'user_id', 'status', 'expired_at'], $errors)) break;
				$host_id = intval($_POST['host_id']);
				$user_id = intval($_POST['user_id']);
				$status = sanitize_textarea_field($_POST['status']);
				$expired_at = sanitize_text_field($_POST['expired_at']);
				$wpdb->insert($table_name, compact('host_id', 'user_id', 'expired_at', 'status'));
				break;

			case 'edit':
        if (!validate_fields(['id', 'host_id', 'user_id', 'status', 'expired_at'], $errors)) break;
				$id = intval($_POST['id']);
				$host_id = intval($_POST['host_id']);
				$user_id = intval($_POST['user_id']);
				$status = sanitize_textarea_field($_POST['status']);
				$expired_at = sanitize_text_field($_POST['expired_at']);
				$wpdb->update($table_name, compact('host_id', 'user_id', 'expired_at', 'status'), ['id' => $id]);
				break;

			case 'delete':
				$id = intval($_POST['id']);
				$wpdb->delete($table_name, ['id' => $id]);
				break;
		}
	}

	// Display the form and the list of subscriptions
	include 'subscriptions-page.php';
}
