<?php
  global $wpdb;
  $table_name = $wpdb->prefix . 'spp_offers';
  $hosts_table = $wpdb->prefix . 'spp_hosts';
?>

<h2>Offers List</h2>
<table class="wp-list-table widefat fixed striped">
  <thead>
    <tr>
      <th>ID</th>
      <th>Host ID</th>
      <th>Description</th>
      <th>Prices</th>
      <th>Created At</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php
    $offers = $wpdb->get_results("SELECT `offers`.*, `hosts`.`name` as `host_name` FROM `$table_name` `offers`
                                  LEFT JOIN `$hosts_table` `hosts` ON `offers`.`host_id` = `hosts`.`id`");
    if (empty($offers)) {
      echo '<tr><td colspan="9">No offers found.</td></tr>';
    } else foreach ($offers as $row) {
      echo '<tr>';
      echo '<td>' . esc_html($row->id) . '</td>';
      echo '<td>' . esc_html($row->host_name) . '</td>';
      echo '<td>' . esc_html($row->description) . '</td>';
      echo '<td>';
      $prices = json_decode($row->prices);
      foreach ($prices as $price) {
        echo '- ' . esc_html($price->name) . ' for <span class="success">' . esc_html($price->amount) . 'DZD</span> per ' . esc_html($price->period);
      }
      echo '</td>';
      echo '<td>' . esc_html(['active' => 'Active', 'canceled' => 'Canceled'][$row->status]) . '</td>';
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
