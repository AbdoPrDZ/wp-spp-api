<?php

// Plugin tables
function create_hosts_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'spp_hosts'; // Table name with WordPress prefix

	// SQL to create the table
	$charset_collate = $wpdb->get_charset_collate();
	$sql = "CREATE TABLE `$table_name` (
		`id` BIGINT NOT NULL AUTO_INCREMENT,
		`name` VARCHAR(255) NOT NULL,
		`host` VARCHAR(255) NOT NULL,
		`preview_image_url` VARCHAR(255) DEFAULT NULL,
		`cookie` TEXT DEFAULT NULL,
		`blocked_routes` TEXT DEFAULT NULL,
		`description` TEXT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (`id`)
	) $charset_collate;";

	// Include the WordPress upgrade file
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta($sql);
}

function create_offers_table() {
  global $wpdb;
  $table_name = $wpdb->prefix . 'spp_offers'; // Table name with WordPress prefix
  $hosts_table = $wpdb->prefix . 'spp_hosts'; // Assuming the hosts table is named spp_hosts

  // SQL to create the table
  $charset_collate = $wpdb->get_charset_collate();
  $sql = "CREATE TABLE `$table_name` (
    `id` BIGINT NOT NULL AUTO_INCREMENT,
    `host_id` BIGINT NOT NULL,
    `description` TEXT NOT NULL,
    `prices` TEXT NOT NULL,
		`status` ENUM('activate', 'deactivated') NOT NULL DEFAULT 'activate',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`host_id`) REFERENCES `$hosts_table`(`id`) ON DELETE CASCADE
  ) $charset_collate;";

  // Include the WordPress upgrade file
  require_once ABSPATH . 'wp-admin/includes/upgrade.php';
  dbDelta($sql);
}

function create_subscriptions_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'spp_subscriptions'; // Table name with WordPress prefix
  $offers_table = $wpdb->prefix . 'spp_offers'; // Assuming the offers table is named spp_hosts
  $users_table = $wpdb->prefix . 'users'; // Assuming the users table is named wp_users

	// SQL to create the table
	$charset_collate = $wpdb->get_charset_collate();
	$sql = "CREATE TABLE `$table_name` (
		`id` BIGINT NOT NULL AUTO_INCREMENT,
		`user_id` BIGINT NOT NULL,
		`offer_id` BIGINT NOT NULL,
		`expired_at` DATETIME DEFAULT NULL,
    `price` DECIMAL(10, 2) NOT NULL,
		`status` ENUM('pending', 'activate', 'expired', 'deactivated') NOT NULL DEFAULT 'pending',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (`id`),
    -- FOREIGN KEY (`user_id`) REFERENCES `$users_table`(`ID`) ON DELETE CASCADE,
    FOREIGN KEY (`offer_id`) REFERENCES `$offers_table`(`id`) ON DELETE CASCADE
	) $charset_collate;";

	// Include the WordPress upgrade file
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta($sql);
}

function register_plugin() {
  // create the plugin tables
  create_hosts_table();
  create_offers_table();
  create_subscriptions_table();
}
register_activation_hook(WP_SPP_API_FILE, 'register_plugin');
