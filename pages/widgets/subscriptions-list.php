<?php
  global $wpdb;
  $table_name = $wpdb->prefix . 'spp_subscriptions';
  $offers_table = $wpdb->prefix . 'spp_offers';
  $hosts_table = $wpdb->prefix . 'spp_hosts';
  $users_table = $wpdb->prefix . 'users';
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
      echo '<td>' . esc_html(['pending' => 'Pending', 'active' => 'Active', 'expired' => 'Expired', 'canceled' => 'Canceled'][$row->status]) . '</td>';
      echo '<td>' . esc_html($row->expired_at ?? 'None') . '</td>';
      echo '<td>' . esc_html($row->created_at) . '</td>';
      echo '<td>' .
        ($row->status == 'pending' ? '<button class="button" onclick="activate(' . $row->id . ')">Activate</button> |' : '') .
        '<a href="' . admin_url('admin.php?page=manage-subscriptions&edit=' . $row->id) . '" class="button">Edit</a> |
        <form method="post" style="display:inline;">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="' . esc_attr($row->id) . '">
          <input type="submit" value="Delete" onclick="return confirm(\'Are you sure?\');" class="button">
        </form>
      </td>';
      echo '</tr>';
    }
    ?>
  </tbody>
  <script>
    function activate(id) {
      if (confirm('Are you sure?')) {
        window.location.href = '<?php echo admin_url('admin.php?page=manage-subscriptions&activate='); ?>' + id;
      }
    }
  </script>
</table>
