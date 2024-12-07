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
      if (!in_array($status, ['activate', 'proof_rejected', 'deactivate'])) {
        echo '<div class="notice notice-error is-dismissible"><p>Invalid status.</p></div>';
        break;
      }

      $subscription = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));

      if (empty($subscription)) {
        echo '<div class="notice notice-error is-dismissible"><p>Subscription #' . $id . ' not found.</p></div>';
        break;
      } elseif ($status == 'deactivate' && $subscription->status == 'deactivated') {
        echo '<div class="notice notice-error is-dismissible"><p>Subscription #' . $id . ' is already deactivated.</p></div>';
        break;
      } elseif ($status == 'activate' && $subscription->status == 'activate') {
        echo '<div class="notice notice-error is-dismissible"><p>Subscription #' . $id . ' is already activated.</p></div>';
        break;
      } elseif (($status == 'activate' || $status == 'proof_rejected') && $subscription->status == 'waiting_proof') {
        echo '<div class="notice notice-error is-dismissible"><p>Cannot ' . ['activate' => 'activate', 'proof_rejected' => 'reject the proof of'][$status] . ' subscription #' . $id . ' without the payment proof.</p></div>';
        break;
      } elseif (($status == 'activate' || $status == 'proof_rejected') && $subscription->status == 'expired') {
        echo '<div class="notice notice-error is-dismissible"><p>Cannot ' . ['activate' => 'activate', 'proof_rejected' => 'reject the proof of'][$status] . ' subscription #' . $id . ' because it has expired.</p></div>';
        break;
      }

      $price = $wpdb->get_row($wpdb->prepare("SELECT * FROM $prices_table WHERE id = %d", $subscription->price_id));

      $expired_at = $subscription->expired_at;
      if ($status == 'activate') {
        $expired_at = date('Y-m-d H:i:s', strtotime("+1 $price->period"));
      } elseif ($status == 'deactivate') {
        $expired_at = date('Y-m-d H:i:s');
      }

      $proof_path = $subscription->proof_path;

      if ($status == 'proof_rejected') {
        // remove the proof image from the server
        if ($proof_path) {
          spp_delete_user_file($subscription->user_id, $subscription->id . '/' . $proof_path);
        }

        $proof_path = null;
      }

      $wpdb->update(
        $table_name,
        [
          'status'     => $status,
          'expired_at' => $expired_at,
          'proof_path' => $proof_path,
        ],
        ['id' => $id]
      );

      echo '<div class="notice notice-success is-dismissible"><p>Subscription #' . $id . ' ' .
            (['activate' => 'activated', 'proof_rejected' => 'proof rejected', 'deaactivate' => 'deactivated'][$status]) .
            ' successfully.</p></div>';

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
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<h2>Subscriptions List</h2>
<table class="wp-list-table widefat fixed striped">
  <thead>
    <tr>
      <th>ID</th>
      <th>Host</th>
      <th>User</th>
      <th>Price</th>
      <th>Proof</th>
      <th>Status</th>
      <th>Expiration Date</th>
      <th>Created At</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php
    $subscriptions = $wpdb->get_results("SELECT `subscriptions`.*,
                                                `hosts`.`name` as `host_name`,
                                                `hosts`.`host` as `host_url`,
                                                `users`.`display_name` as `user_name`
                                          FROM `$table_name` `subscriptions`
                                  LEFT JOIN `$offers_table` `offers` ON `subscriptions`.`offer_id` = `offers`.`id`
                                  LEFT JOIN `$hosts_table` `hosts` ON `offers`.`host_id` = `hosts`.`id`
                                  LEFT JOIN `$users_table` `users` ON `subscriptions`.`user_id` = `users`.`ID`");
    if (empty($subscriptions)) {
      echo '<tr><td colspan="9">No subscriptions found.</td></tr>';
    } else foreach ($subscriptions as $row) {
      $row->proof_url = spp_get_subscription_proof_url($row->user_id, $row->id);
      $price = $wpdb->get_row($wpdb->prepare("SELECT * FROM $prices_table WHERE id = %d", $row->price_id));
      echo '
      <tr>
        <td>' . esc_html($row->id) . '</td>
        <td><a href="' . admin_url('admin.php?page=manage-hosts&edit=' . $row->host_url) . '">' . $row->host_name . '</a></td>
        <td>' . esc_html($row->user_name) . '</td>
        <td>' . esc_html($price->amount) . '</td>
        <td>' . ($row->proof_url ? '<a href="' . $row->proof_url . '" target="_blank">View</a>' : 'None') . '</td>
        <td>' . esc_html([
                  'waiting_proof'  => 'Waiting the payment proof',
                  'verifing_proof' => 'Verifing the payment proof',
                  'proof_rejected' => 'Payment proof rejected',
                  'activate'       => 'Activate',
                  'expired'        => 'Expired',
                  'deactivated'    => 'Deactivated',
                ][$row->status]) . '</td>
        <td>' . esc_html($row->expired_at ?? 'None') . '</td>
        <td>' . esc_html($row->created_at) . '</td>
        <td class="actions">
          <button class="button" onclick=\'showModal(' . json_encode($row) . ')\'>Edit</button>
          <a href="' . admin_url('admin.php?page=widgets/subscriptions-list.php&action=delete&id=' . $row->id) . '" class="button">Delete</a>
        </td>
      </tr>';
    }
    ?>
  </tbody>
</table>

<div class="modal fade" id="subscription-modal" tabindex="-1" aria-labelledby="subscription-modal-lable" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post">
      <div class="modal-content">
        <div class="modal-header">
          <h1 class="modal-title fs-5" id="subscription-modal-lable">
            Edit Subscription
          </h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <table class="form-table">
          </table>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Save changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  function showModal(subscription) {
    const modelEl = document.getElementById('subscription-modal');

    let optionsHtml = '';
    if (subscription.status == 'waiting_proof')
      optionsHtml = `Waiting for the payment proof image to be uploaded from the user.`;
    else if (subscription.status == 'expired')
      optionsHtml = `This subscription has expired so you can't change its status.`;
    else {
      let options = [];
      if (subscription.status == 'verifing_proof')
        options = ['activate', 'proof_rejected'];
      else if (subscription.status == 'activate')
        options = ['deactivate'];
      else if (subscription.status == 'proof_rejected' || subscription.status == 'deactivated')
        options = ['activate'];

      optionsHtml = `
        <select class="form-select" id="status" name="status">
          ${options.map(option => `<option value="${option}">${{
            'activate': 'Activate',
            'proof_rejected': 'Reject Proof',
            'deactivate': 'Deactivate'
          }[option]}</option>`).join('')}
        </select>
      `;
    }

    let imgHtml = '';
    if (subscription.proof_url) {
      imgHtml = `
        <th><label for="proof-img" class="form-label">Payment Proof</label></th>
        <td>
          <img id="proof-img" src="${subscription.proof_url}" alt="Payment Proof" style="max-width: 100%;">
          <a href="${subscription.proof_url}" target="_blank">Preview full size</a>
        </td>
      `;
    }

    modelEl.querySelector('.form-table').innerHTML =  `
      <input type="hidden" name="action" value="status">
      <input type="hidden" name="id" value="${subscription.id}">
      ${imgHtml}
      <tr>
        <th><label for="status" class="form-label">Status</label></th>
        <td>${optionsHtml}</td>
      </tr>
    `;

    new bootstrap.Modal(modelEl).show();
  }
</script>

<style>
  td.actions {
    display: flex;
    justify-content: space-evenly;
    gap: 2px;
  }
</style>
