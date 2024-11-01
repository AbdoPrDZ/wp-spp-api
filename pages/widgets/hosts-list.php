<?php
  global $wpdb;
  $table_name = $wpdb->prefix . 'spp_hosts';
?>

<h2>Hosts List</h2>
<table class="wp-list-table widefat fixed striped">
  <thead>
    <tr>
      <th>ID</th>
      <th>Name</th>
      <th>Host Target</th>
      <th>Host Preview Image</th>
      <th>Host Cookie</th>
      <th>Blocked routes</th>
      <th>Description</th>
      <th>Created At</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php
    $hosts = $wpdb->get_results("SELECT * FROM `$table_name`");
    if (empty($hosts)) {
      echo '<tr><td colspan="9">No hosts found.</td></tr>';
    } else foreach ($hosts as $row) {
      echo '<tr>';
      echo '<td>' . esc_html($row->id) . '</td>';
      echo '<td>' . esc_html($row->name) . '</td>';
      echo '<td>' . esc_html($row->host) . '</td>';
      echo '<td>' . esc_html(empty($row->preview_image_url) ? 'None' : $row->preview_image_url) . '</td>';
      echo '<td>' . esc_html(empty($row->cookie) ? 'None' : substr($row->cookie, 0, 50) . "...") . '</td>';
      echo '<td>' . esc_html(empty($row->blocked_routes) ? 'None' : $row->blocked_routes) . '</td>';
      echo '<td>' . esc_html($row->description) . '</td>';
      echo '<td>' . esc_html($row->created_at) . '</td>';
      echo '<td>
        <a href="' . admin_url('admin.php?page=manage-hosts&edit=' . $row->id) . '" class="button">Edit</a> |
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
