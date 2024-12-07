<?php
global $wpdb;
$table_name = $wpdb->prefix . 'spp_hosts';

// Handle editing a specific host
if (isset($_GET['edit'])) {
  $id = intval($_GET['edit']);
  $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
}

$errors = isset($errors) ? $errors : [];

function error($errors, $target) {
  return isset($errors[$target]) ? $errors[$target] : null;
}

?>

<div class="wrap">
  <h1 class="wp-heading-inline">Manage Hosts</h1>

  <?php if (isset($_GET['edit'])) : ?>
    <a href="<?php echo admin_url('admin.php?page=wp-spp-api'); ?>" class="page-title action">Add New</a>
  <?php endif; ?>

  <!-- Form for adding or editing a host -->
  <form method="post">
    <input type="hidden" name="action" value="<?php echo isset($item) ? 'edit' : 'add'; ?>">
    <?php if (isset($item)) : ?>
      <input type="hidden" name="id" value="<?php echo esc_attr($item->id); ?>">
    <?php endif; ?>
    <table class="form-table">
      <tr>
        <th><label for="name">Name</label></th>
        <td>
          <input type="text" name="name" id="name" value="<?php echo isset($item) ? esc_attr($item->name) : ''; ?>" class="regular-text">
          <?php if ($error = error($errors, 'name')) : ?>
            <p class="description error"><?php echo $error; ?></p>
          <?php endif; ?>
        </td>
      </tr>
      <tr>
        <th><label for="host">Host Target</label></th>
        <td>
          <input type="text" name="host" id="host" value="<?php echo isset($item) ? esc_attr($item->host) : ''; ?>" class="regular-text">
          <?php if ($error = error($errors, 'host')) : ?>
            <p class="description error"><?php echo $error; ?></p>
          <?php endif; ?>
        </td>
      </tr>
      <tr>
        <th><label for="preview-image">Host Preview Image</label></th>
        <td>
          <input type="text" name="preview_image_url" id="preview-imag" value="<?php echo isset($item) ? esc_attr($item->preview_image_url) : ''; ?>" class="regular-text">
          <?php if ($error = error($errors, 'preview_image_url')) : ?>
            <p class="description error"><?php echo $error; ?></p>
          <?php endif; ?>
        </td>
      </tr>
      <tr>
        <th><label for="cookie">Host Cookie</label></th>
        <td>
          <textarea name="cookie" id="cookie" class="large-text"><?php echo isset($item) ? esc_textarea($item->cookie) : ''; ?></textarea>
          <?php if ($error = error($errors, 'cookie')) : ?>
            <p class="description error"><?php echo $error; ?></p>
          <?php endif; ?>
        </td>
      </tr>
      <tr>
        <th><label for="blocked-urls">Blocked urls</label></th>
        <td>
          <textarea name="blocked_urls" id="blocked-urls" class="large-text"><?php echo isset($item) ? esc_textarea($item->blocked_urls) : ''; ?></textarea>
          <?php if ($error = error($errors, 'blocked_urls')) : ?>
            <p class="description error"><?php echo $error; ?></p>
          <?php endif; ?>
        </td>
      </tr>
      <tr>
        <th><label for="description">Description</label></th>
        <td>
          <textarea name="description" id="description" class="large-text"><?php echo isset($item) ? esc_textarea($item->description) : ''; ?></textarea>
          <?php if ($error = error($errors, 'description')) : ?>
            <p class="description error"><?php echo $error; ?></p>
          <?php endif; ?>
        </td>
      </tr>
    </table>
    <p class="submit">
      <input type="submit" class="button button-primary" value="<?php echo isset($item) ? 'Update Host' : 'Add Host'; ?>">
    </p>
  </form>

  <!-- Display the list of hosts -->
  <?php
    include 'widgets/hosts-list.php';
  ?>
</div>
