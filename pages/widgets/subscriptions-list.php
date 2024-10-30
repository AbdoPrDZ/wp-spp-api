<?php
  global $wpdb;
  $table_name = $wpdb->prefix . 'spp_subscriptions';
  $offers_table = $wpdb->prefix . 'spp_offers';
  $hosts_table = $wpdb->prefix . 'spp_hosts';
  $users_table = $wpdb->prefix . 'users';

  // Handle form submission for adding/editing/deleting subscriptions
  if (isset($_POST['action']) && $_POST['action'] == 'status' && current_user_can('manage_options')) {
    $id = intval($_POST['id']);
    $status = sanitize_text_field($_POST['status']);
    if (!in_array($status, ['activate', 'deactivate'])) {
      echo '<div class="notice notice-error is-dismissible"><p>Invalid status.</p></div>';
      return;
    }
    $subscription = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
    if (empty($subscription)) {
      echo '<div class="notice notice-error is-dismissible"><p>Subscription #' . $id . ' not found.</p></div>';
      return;
    }
    $wpdb->update($table_name, ['status' => $status], ['id' => $id]);
    echo '<div class="notice notice-success is-dismissible"><p>Subscription #' . $id . ' ' . ($status == 'activate' ? 'activated' : 'deactivated') . ' successfully.</p></div>';
  }
?>

<h2>Subscriptions List</h2>
<table class="wp-list-table widefat fixed striped">
  <thead>
    <tr>
      <th>ID</th>
      <th>Host</th>
      <th>User</th>
      <th>Price</th>
      <th>Status</th>
      <th>Expiration Date</th>
      <th>Created At</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php
    $subscriptions = $wpdb->get_results("SELECT `subscriptions`.*, `hosts`.`name` as `host_name`, `users`.`display_name` as `user_name` FROM `$table_name` `subscriptions`
                                  LEFT JOIN `$offers_table` `offers` ON `subscriptions`.`offer_id` = `offers`.`id`
                                  LEFT JOIN `$hosts_table` `hosts` ON `offers`.`host_id` = `hosts`.`id`
                                  LEFT JOIN `$users_table` `users` ON `subscriptions`.`user_id` = `users`.`ID`");
    if (empty($subscriptions)) {
      echo '<tr><td colspan="9">No subscriptions found.</td></tr>';
    } else foreach ($subscriptions as $row) {
      echo '<tr>';
      echo '<td>' . esc_html($row->id) . '</td>';
      echo '<td>' . esc_html($row->host_name) . '</td>';
      echo '<td>' . esc_html($row->user_name) . '</td>';
      echo '<td>' . esc_html($row->price) . '</td>';
      echo '<td>' . esc_html(['pending' => 'Pending', 'activate' => 'Activate', 'expired' => 'Expired', 'deactivated' => 'Deactivated'][$row->status]) . '</td>';
      echo '<td>' . esc_html($row->expired_at ?? 'None') . '</td>';
      echo '<td>' . esc_html($row->created_at) . '</td>';
      echo '<td class="actions">';
      if ($row->status != 'expired') {
        echo '<form method="post" style="display:inline;">
                <input type="hidden" name="action" value="status">
                <input type="hidden" name="id" value="' . esc_attr($row->id) . '">
                <input type="hidden" name="status" value="' . esc_attr($row->status == 'activate' ? 'deactivate' : 'activate') . '">
                <input type="submit" value="' . ($row->status == 'activate' ? 'Deactivate' : 'Activate') . '" onclick="return confirm(\'Are you sure you want to ' . ($row->status == 'activate' ? 'deactivate' : 'activate') . ' this subscription?\');" class="button">
              </form>';
      }
      echo '<a href="' . admin_url('admin.php?page=manage-subscriptions&edit=' . $row->id) . '" class="button">Edit</a>
            <form method="post" style="display:inline;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="' . esc_attr($row->id) . '">
                <input type="submit" value="Delete" onclick="return confirm(\'Are you sure you want to delete this subscription?\');" class="button">
              </form>
            </td>';
      echo '</tr>';
    }
    ?>
  </tbody>
  <style>
    td.actions {
      display: flex;
      justify-content: space-between;
      gap: 2px;
    }
  </style>
</table>
