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
      <th>Blocked urls</th>
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
      echo "<td><a href=\"$row->host\">$row->host</a></td>";
      echo '<td>' . (empty($row->preview_image_url) ? esc_html('None') : "<img src=\"$row->preview_image_url\">") . '</td>';
      echo '<td>' . esc_html(empty($row->blocked_urls) ? 'None' : (count(explode("\n", $row->blocked_urls)) . " blocked url")) . '</td>';
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
