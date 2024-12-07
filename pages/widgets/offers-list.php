<?php
  global $wpdb;
  $table_name = $wpdb->prefix . 'spp_offers';
  $hosts_table = $wpdb->prefix . 'spp_hosts';
  $prices_table = $wpdb->prefix . 'spp_prices';
?>

<h2>Offers List</h2>
<table class="wp-list-table widefat fixed striped">
  <thead>
    <tr>
      <th>ID</th>
      <th>Host</th>
      <th>Description</th>
      <th>Prices</th>
      <th>Status</th>
      <th>Created At</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php
    $offers = $wpdb->get_results("SELECT `offers`.*,
                                         `hosts`.`name` as `host_name`,
                                          `hosts`.`host` as `host_url`
                                    FROM `$table_name` `offers`
                                  LEFT JOIN `$hosts_table` `hosts` ON `offers`.`host_id` = `hosts`.`id`");
    if (empty($offers)) {
      echo '<tr><td colspan="9">No offers found.</td></tr>';
    } else foreach ($offers as $row) {
      echo '<tr>';
      echo '<td>' . esc_html($row->id) . '</td>';
      echo '<td><a href="' . admin_url('admin.php?page=manage-hosts&edit=' . $row->host_url) . '">' . $row->host_name . '</a></td>';
      echo '<td>' . esc_html($row->description) . '</td>';
      echo '<td>';
      $prices = $wpdb->get_results($wpdb->prepare("SELECT * FROM $prices_table WHERE offer_id = %d", $row->id));
      foreach ($prices as $price) {
        echo '<p>- ' . esc_html($price->name) . ' for <span class="success">' . esc_html($price->amount) . 'DZD</span> per ' . esc_html($price->period) . '</li>';
      }
      echo '</td>';
      echo '<td>' . esc_html(['activate' => 'Activate', 'deactivated' => 'Deactivated'][$row->status]) . '</td>';
      echo '<td>' . esc_html($row->created_at) . '</td>';
      echo '<td>
        <a href="' . admin_url('admin.php?page=manage-offers&edit=' . $row->id) . '" class="button">Edit</a> |
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
</table>

<style>
  .success {
    color: green;
  }
</style>
