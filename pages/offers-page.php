<?php

global $wpdb;

$table_name = $wpdb->prefix . 'spp_offers';
$hosts_table = $wpdb->prefix . 'spp_hosts';
$prices_table = $wpdb->prefix . 'spp_prices';
$capabilities_table = $wpdb->prefix . 'capabilities';

// Fetch the list of hosts
$hosts = $wpdb->get_results("SELECT id, name FROM $hosts_table");

// Handle editing a specific offer
if (isset($_GET['edit'])) {
  $id = intval($_GET['edit']);
  $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));

  if ($item) {
    $prices = $wpdb->get_results($wpdb->prepare("SELECT * FROM $prices_table WHERE offer_id = %d", $item->id));
    $item->prices = json_encode($prices);
  } else
    echo '<div class="notice notice-error is-dismissible"><p>Offer #' . $id . ' not found.</p></div>';
}

$errors = isset($errors) ? $errors : [];

function error($errors, $target) {
  return isset($errors[$target]) ? $errors[$target] : null;
}

?>

<div class="wrap">
  <h1 class="wp-heading-inline">Manage Offers</h1>

  <!-- Form for adding or editing a offer -->
  <form method="post">
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
        <th><label for="description">Description</label></th>
        <td>
          <textarea name="description" id="description" class="large-text"><?php echo isset($item) ? esc_textarea($item->description) : ''; ?></textarea>
          <?php if ($error = error($errors, 'description')) : ?>
            <p class="description error"><?php echo $error; ?></p>
          <?php endif; ?>
        </td>
      </tr>
      <tr>
        <th><label for="prices">Prices</label></th>
        <td>
          <input type="hidden" name="prices" id="prices" value="<?php echo isset($item) ? esc_attr($item->prices) : '[]'; ?>"/>
          <div id="prices-input">
            <table class="form-table">
              <tr>
                <th><label for="price-name">Name</label></th>
                <td><input type="text" id="price-name" name="price_name"></td>
              </tr>
              <tr>
                <th><label for="price-description">Description</label></th>
                <td><textarea id="price-description" name="price_description"></textarea></td>
              </tr>
              <tr>
                <th><label for="price-amount">Amount</label></th>
                <td><input type="number" id="price-amount" name="price_amount" min="1"></td>
              </tr>
              <tr>
                <th><label for="price-features">Features</label></th>
                <td>
                  <textarea name="price_features" id="price-features"></textarea>
                </td>
              </tr>
              <tr>
                <th><label for="price-period">Period</label></th>
                <td>
                  <select name="price_period" id="price-period">
                    <option value="hour">Hour</option>
                    <option value="day">Day</option>
                    <option value="month" selected>Month</option>
                    <option value="year">Year</option>
                  </select>
                </td>
              </tr>
            </table>
            <a id="add-price" class="button" onclick="addPrice()">Add Price</a>
            <br>
            <ul id="prices-list">
              <?php if (isset($item)) : ?>
                <?php foreach (json_decode($item->prices) as $price) : ?>
                  <li class="price-item">
                    <span><?php echo $price->name; ?></span>
                    <span><?php echo $price->description; ?></span>
                    <span><?php echo $price->amount; ?></span>
                    <span><?php echo $price->period; ?></span>
                    <span><?php echo $price->features; ?></span>
                    <button type="button" onclick="removePrice(this)">
                      <i class="fas fa-trash-alt""></i>
                    </button>
                  </li>
                <?php endforeach; ?>
              <?php endif; ?>
            </ul>
            <script>
              function addPrice() {
                const nameEl = document.getElementById('price-name');
                const descriptionEl = document.getElementById('price-description');
                const amountEl = document.getElementById('price-amount');
                const featuresEl = document.getElementById('price-features');
                const periodEl = document.getElementById('price-period');

                const name = nameEl.value;
                const description = descriptionEl.value;
                const amount = amountEl.value;
                const features = featuresEl.value;
                const period = periodEl.value;

                if (!name || !amount || !description || !features || !period) {
                  alert('Please fill all fields');
                  return;
                }

                const prices = JSON.parse(document.getElementById('prices').value);
                prices.push({ name: name, description: description, amount: amount, features: features, period: period });
                document.getElementById('prices').value = JSON.stringify(prices);
                document.getElementById('prices-list').innerHTML += `
                  <li class="price-item">
                    <span>${name}</span>
                    <span>${description}</span>
                    <span>${amount}</span>
                    <span>${period}</span>
                    <span>${features.split('\n').map(f => f.trim()).filter(f => f).join(', ')}</span>
                    <button type="button" onclick="removePrice(this)">
                      <i class="fas fa-trash-alt"></i>
                    </button>
                  </li>
                `;

                nameEl.value = '';
                descriptionEl.value = '';
                amountEl.value = '';
                featuresEl.value = '';
                periodEl.value = 'month';
              }

              function removePrice(button) {
                const prices = JSON.parse(document.getElementById('prices').value);
                const index = Array.from(button.parentElement.parentElement.children).indexOf(button.parentElement);

                prices.splice(index, 1);
                document.getElementById('prices').value = JSON.stringify(prices);

                button.parentElement.remove();
              }
            </script>
            <style>
              #prices-list {
                list-style-type: none;
                padding: 0;
                border: 1px solid #ddd;
                border-radius: 5px;
                min-height: 100px;
                background: #f9f9f9;
              }

              #prices-list .price-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                background: #f9f9f9;
                margin: 5px 10px;
                padding: 5px 10px;
              }

              #prices-list .price-item:hover {
                background: #f1f1f1;
              }

              #prices-list .price-item button {
                background: none;
                border: none;
                cursor: pointer;
                color: red;
              }
            </style>
          </div>
          <?php if ($error = error($errors, 'prices')) : ?>
            <p class="description error"><?php echo $error; ?></p>
          <?php endif; ?>
        </td>
      </tr>
      <tr>
        <th><label for="status">Status</label></th>
        <td>
          <select name="status" id="status">
            <option value="activate" <?php selected(isset($item) && $item->status == "activate"); ?>>
              Activate
            </option>
            <option value="deactivated" <?php selected(isset($item) && $item->status == "deactivated"); ?>>
              Deactivated
            </option>
          </select>
        </td>
      </tr>
    </table>
    <p class="submit">
      <input type="submit" class="button button-primary" value="<?php echo isset($item) ? 'Update Offer' : 'Add Offer'; ?>">
    </p>
  </form>

  <!-- Display the list of offers -->
  <?php
    include 'widgets/offers-list.php';
  ?>
</div>
