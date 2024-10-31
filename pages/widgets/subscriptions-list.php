<?php
  global $wpdb;

  $table_name = $wpdb->prefix . 'spp_subscriptions';
  $users_table = $wpdb->prefix . 'users';
  $hosts_table = $wpdb->prefix . 'spp_hosts';
  $offers_table = $wpdb->prefix . 'spp_offers';
  $prices_table = $wpdb->prefix . 'spp_prices';

  // Handle form submission for adding/editing/deleting subscriptions
  if (isset($_POST['action']) && $_POST['action'] == 'status' && current_user_can('manage_options')) {
    switch ($_POST['action']) {
      case 'status':
        $id = intval($_POST['id']);
        $status = sanitize_text_field($_POST['status']);
        if (!in_array($status, ['activate', 'deactivate'])) {
          echo '<div class="notice notice-error is-dismissible"><p>Invalid status.</p></div>';
          break;
        }

        $subscription = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));

        if (empty($subscription)) {
          echo '<div class="notice notice-error is-dismissible"><p>Subscription #' . $id . ' not found.</p></div>';
          break;
        } elseif ($status == 'activate' && $subscription->status == 'expired') {
          echo '<div class="notice notice-error is-dismissible"><p>Subscription #' . $id . ' is expired.</p></div>';
          break;
        } elseif ($status == 'deactivate' && $subscription->status == 'deactivated') {
          echo '<div class="notice notice-error is-dismissible"><p>Subscription #' . $id . ' is already deactivated.</p></div>';
          break;
        }

        $price = $wpdb->get_row($wpdb->prepare("SELECT * FROM $prices_table WHERE id = %d", $subscription->price_id));

        $expired_at = $subscription->expired_at;
        if ($status == 'activate' && $subscription->status == 'pending') {
          $expired_at = date('Y-m-d H:i:s', strtotime("+1$price->period"));
        } elseif ($status == 'deactivate') {
          $expired_at = date('Y-m-d H:i:s');
        }

        $wpdb->update(
          $table_name,
          [
          'status' => $status,
          'expired_at' => $expired_at
          ],
          ['id' => $id]
        );

        echo '<div class="notice notice-success is-dismissible"><p>Subscription #' . $id . ' ' . ($status == 'activate' ? 'activated' : 'deactivated') . ' successfully.</p></div>';

        break;
      case 'delete':
        $id = intval($_POST['id']);
        $wpdb->delete($table_name, ['id' => $id]);
        echo '<div class="notice notice-success is-dismissible"><p>Subscription #' . $id . ' deleted successfully.</p></div>';

        break;
      default:
        echo '<div class="notice notice-error is-dismissible"><p>Invalid action.</p></div>';
        break;
    }
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
      $price = $wpdb->get_row($wpdb->prepare("SELECT * FROM $prices_table WHERE id = %d", $row->price_id));
      echo '<tr>';
      echo '<td>' . esc_html($row->id) . '</td>';
      echo '<td>' . esc_html($row->host_name) . '</td>';
      echo '<td>' . esc_html($row->user_name) . '</td>';
      echo '<td>' . esc_html($price->amount) . '</td>';
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
      // echo '  <a href="' . admin_url('admin.php?page=manage-subscriptions&edit=' . $row->id) . '" class="button">Edit</a>';
      echo '  <form method="post" style="display:inline;">
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
      justify-content: space-evenly;
      gap: 2px;
    }
  </style>
</table>
