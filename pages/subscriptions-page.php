<?php

// global $wpdb;

// $table_name = $wpdb->prefix . 'spp_subscriptions';
// $hosts_table = $wpdb->prefix . 'spp_hosts';
// $users_table = $wpdb->prefix . 'users';
// $capabilities_table = $wpdb->prefix . 'capabilities';

// // Fetch the list of hosts and users
// $hosts = $wpdb->get_results("SELECT id, name FROM $hosts_table");
// $users = $wpdb->get_results(
//   "SELECT `users`.ID, `users`.`display_name`
//    FROM `$users_table` `users`
//    INNER JOIN `$wpdb->usermeta` `usermeta` ON `users`.`ID` = `usermeta`.`user_id`
//    WHERE `usermeta`.`meta_key` = '$capabilities_table'
//      AND `usermeta`.`meta_value` LIKE '%\"subscriber\"%'"
// );

// // Handle editing a specific token
// if (isset($_GET['edit'])) {
//   $id = intval($_GET['edit']);
//   $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
// }

?>

<div class="wrap">
  <h1 class="wp-heading-inline">Manage Subscriptions</h1>

  <!-- Form for adding or editing a token -->
  <!-- <form method="post">
    <input type="hidden" name="action" value="<?php echo isset($item) ? 'edit' : 'add'; ?>">
    <?php if (isset($item)) : ?>
      <input type="hidden" name="id" value="<?php echo esc_attr($item->id); ?>">
    <?php endif; ?>
    <table class="form-table">
      <tr>
        <th><label for="host_id">Host</label></th>
        <td>
          <select name="host_id" id="host_id">
            <?php foreach ($hosts as $host) : ?>
              <option value="<?php echo esc_attr($host->id); ?>" <?php selected(isset($item) && $item->host_id == $host->id); ?>>
                <?php echo esc_html($host->name); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </td>
      </tr>
      <tr>
        <th><label for="user_id">User</label></th>
        <td>
          <select name="user_id" id="user_id">
            <?php foreach ($users as $user) : ?>
              <option value="<?php echo esc_attr($user->ID); ?>" <?php selected(isset($item) && $item->user_id == $user->ID); ?>>
                <?php echo esc_html($user->display_name); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </td>
      </tr>
      <tr>
        <th><label for="status">Status</label></th>
        <td>
          <select name="status" id="status">
            <option value="active" <?php selected(isset($item) && $item->status == "active"); ?>>
              Active
            </option>
            <option value="expired" <?php selected(isset($item) && $item->status == "expired"); ?>>
              Expired
            </option>
            <option value="canceled" <?php selected(isset($item) && $item->status == "canceled"); ?>>
              Canceled
            </option>
          </select>
        </td>
      </tr>
      <tr>
        <th><label for="expired_at">Expiration Date</label></th>
        <td><input type="datetime-local" name="expired_at" id="expired_at" value="<?php echo isset($item) ? esc_attr($item->expired_at) : ''; ?>" class="regular-text"></td>
      </tr>
    </table>
    <p class="submit">
      <input type="submit" class="button button-primary" value="<?php echo isset($item) ? 'Update Token' : 'Add Token'; ?>">
    </p>
  </form> -->

  <!-- Display the list of subscriptions -->
  <?php
    include 'widgets/subscriptions-list.php';
  ?>
</div>
