<?php

function unregister_plugin() {
  global $wpdb;

  // drop the hosts table
	$hosts_table         = $wpdb->prefix . 'spp_hosts';
	$offers_table        = $wpdb->prefix . 'spp_offers';
	$subscriptions_table = $wpdb->prefix . 'spp_subscriptions';
	$prices_table        = $wpdb->prefix . 'spp_prices';

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta("DROP TABLE IF EXISTS $hosts_table");
	dbDelta("DROP TABLE IF EXISTS $offers_table");
	dbDelta("DROP TABLE IF EXISTS $subscriptions_table");
	dbDelta("DROP TABLE IF EXISTS $prices_table");

  // delete the options
  delete_option('ccp_rip');
  delete_option('paypal_email');
  delete_option('paysera_email');

  // remove uploads directory
  if (file_exists(WP_SPP_API_UPLOAD_DIR))
    rmdir(WP_SPP_API_UPLOAD_DIR);
}
register_deactivation_hook(WP_SPP_API_FILE, 'unregister_plugin');
