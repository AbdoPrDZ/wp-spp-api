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

  <h2>Payment Information</h2>

  <form method="post">
    <table class="form-table">
      <tr>
        <th><label for="ccp-rip">CCP Algeria RIP</label></th>
        <td>
          <input type="number" name="ccp_rip" id="ccp-rip" value="<?php echo esc_attr(get_option('ccp_rip')); ?>" class="regular-text">
        </td>
      </tr>
      <tr>
        <th><label for="paypal_email">Paypal Email</label></th>
        <td>
          <input type="email" name="paypal_email" id="paypal_email" value="<?php echo esc_attr(get_option('paypal_email')); ?>" class="regular-text">
        </td>
      </tr>
      <tr>
        <th><label for="paysera_email">Paysera Email</label></th>
        <td>
          <input type="email" name="paysera_email" id="paysera_email" value="<?php echo esc_attr(get_option('paysera_email')); ?>" class="regular-text">
        </td>
      </tr>
    </table>
    <input type="submit" value="Save" class="button button-primary">
  </form>

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
