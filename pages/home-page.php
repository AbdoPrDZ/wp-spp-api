<?php
  global $wpdb;
  $hosts_table = $wpdb->prefix . 'spp_hosts';
  $offers_table = $wpdb->prefix . 'spp_offers';
  $subscriptions_table = $wpdb->prefix . 'spp_subscriptions';
?>

<div class="wrap">
  <h1 class="wp-heading-inline">SPP API Plugin</h1>
  <p>Welcome to the SPP API Plugin. This plugin allows you to manage hosts and subscriptions for WordPress Smart proxy pass.</p>
  <p>Use the menu on the left to navigate through the plugin's features.</p>

  <hr>

  <h2>Statistics</h2>

  <table class="wp-list-table widefat striped">
    <thead>
      <tr>
        <th>Hosts</th>
        <th>Offers</th>
        <th>Subscriptions</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><?php echo $wpdb->get_var("SELECT COUNT(*) FROM $hosts_table"); ?></td>
        <td><?php echo $wpdb->get_var("SELECT COUNT(*) FROM $offers_table"); ?></td>
        <td><?php echo $wpdb->get_var("SELECT COUNT(*) FROM $subscriptions_table"); ?></td>
      </tr>
    </tbody>
  </table>

  <hr>

  <?php include 'widgets/hosts-list.php'; ?>
  <?php include 'widgets/offers-list.php'; ?>
  <?php include 'widgets/subscriptions-list.php'; ?>

</div>
