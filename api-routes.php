<?php

add_action('rest_api_init', function () {
  register_rest_route('wp/v2', 'users/register', array(
    'methods' => 'POST',
    'callback' => 'register_user',
  ));
  register_rest_route('wp/v2', 'users/info', array(
    'methods' => ['GET', 'POST'],
    'callback' => 'user_info',
    'permission_callback' => function () {
      return is_user_logged_in();
    }
  ));
	register_rest_route('custom/v2', '/offers', [
		'methods' => ['GET'],
		'callback' => 'get_offers',
    'permission_callback' => function () {
      return is_user_logged_in();
    }
	]);
  register_rest_route('custom/v2', '/payment-informations', [
    'methods' => ['GET'],
    'callback' => 'get_payment_informations',
    'permission_callback' => function () {
      return is_user_logged_in();
    }
  ]);
	register_rest_route('custom/v2', '/offers/(?P<id>\d+)/subscribe', [
		'methods' => ['POST'],
		'callback' => 'subscribe_to_offer',
    'permission_callback' => function () {
      return is_user_logged_in();
    }
	]);
	register_rest_route('custom/v2', '/subscriptions', [
		'methods' => ['GET'],
		'callback' => 'get_subscriptions',
    'permission_callback' => function () {
      return is_user_logged_in();
    }
	]);
	register_rest_route('custom/v2', '/subscriptions/(?P<id>\d+)', [
    'methods' => ['GET'],
		'callback' => 'get_subscription',
		'permission_callback' => function () {
      return is_user_logged_in();
		}
	]);
	register_rest_route('custom/v2', '/subscriptions/(?P<id>\d+)/proof', [
    'methods' => ['POST'],
		'callback' => 'upload_subscription_proof',
		'permission_callback' => function () {
      return is_user_logged_in();
		}
	]);
  register_rest_route('custom/v2', '/subscriptions/(?P<id>\d+)/info', [
    'methods' => ['GET'],
    'callback' => 'get_subscription_watch_info',
    'permission_callback' => function () {
      return is_user_logged_in();
    }
  ]);
});

function register_user(WP_REST_Request $request) {
  $username = sanitize_text_field($request->get_param('username'));
  $password = sanitize_text_field($request->get_param('password'));
  $email = sanitize_email($request->get_param('email'));

  if (empty($username) || empty($password) || empty($email)) {
    return new WP_Error('missing_fields', 'Please provide all required fields', array('status' => 400));
  }

  if (username_exists($username) || email_exists($email)) {
    return new WP_Error('user_exists', 'Username or email already exists', array('status' => 400));
  }

  $user_id = wp_create_user($username, $password, $email);

  if (is_wp_error($user_id)) {
    return new WP_Error('registration_failed', 'User registration failed', array('status' => 500));
  }

  return new WP_REST_Response(array(
    'message' => 'User registered successfully',
    'user_id' => $user_id,
  ), 200);
}

function user_info(WP_REST_Request $request) {
  $user = wp_get_current_user();

  switch ($request->get_method()) {
    case 'GET':
      return new WP_REST_Response(array(
        'id'                => $user->ID,
        'username'          => $user->user_login,
        'email'             => $user->user_email,
        'first_name'        => $user->first_name,
        'last_name'         => $user->last_name,
        'description'       => $user->description,
        'profile_photo_url' => spp_get_user_profile_url($user->ID),
      ), 200);
    case 'POST':
      $first_name = sanitize_text_field($request->get_param('first_name'));
      $last_name = sanitize_text_field($request->get_param('last_name'));
      $description = sanitize_textarea_field($request->get_param('description'));
      $profile_photo = $request->get_file_params()['profile_photo'];

      if (empty($first_name) && empty($last_name) && empty($description) && is_null($profile_photo)) {
        return new WP_Error('missing_fields', 'Please provide at least one field to update', array('status' => 400));
      }

      $user_data = array(
        'ID'          => $user->ID,
        'first_name'  => $first_name,
        'last_name'   => $last_name,
        'description' => $description,
      );

      if ($profile_photo) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $profile_photo_uploaded_id = media_handle_upload('profile_photo', 0);
        if (is_wp_error($profile_photo_uploaded_id)) {
          return new WP_Error('profile_photo_upload_failed', 'Profile photo upload failed', array('status' => 500));
        }

        $profile_photo_uploaded_path = get_attached_file($profile_photo_uploaded_id);

        if (!spp_set_user_profile_photo($user->ID, $profile_photo_uploaded_path)) {
          return new WP_Error('profile_photo_set_failed', 'Failed to set profile photo', array('status' => 500));
        }
      }

      wp_update_user($user_data);

      return new WP_REST_Response(array(
        'message' => 'User info updated successfully',
      ), 200);
    default:
      return new WP_Error('invalid_method', 'Method not allowed', array('status' => 405));
  }
}

function get_offers(WP_REST_Request $request) {
  global $wpdb;

  $page         = $request->get_param('page') ?? 1;
  $itemsPerPage = $request->get_param('itemsPerPage') ?? 10;
  $sortBy       = $request->get_param('sortBy') ?? 'created_at';
  $sortType     = $request->get_param('sortType') ?? 'asc';
  $dateCompare  = $request->get_param('dateCompare'); // at, before, after
  $date         = $request->get_param('date');

	$table_name = $wpdb->prefix . 'spp_offers';
	$hosts_table = $wpdb->prefix . 'spp_hosts';

  $condition = "WHERE `offers`.`status` = 'activate'";

  $itemsCount = $wpdb->get_var("SELECT COUNT(*) FROM `$table_name` as `offers` $condition");

  if ($dateCompare) {
    if ($dateCompare == 'at') {
      $condition .= " AND `offers`.`created_at` = '$date";
    } else {
      $date = $date ?? date('Y-m-d H:i:s');
      $dateCompare = $dateCompare == 'before' ? '<' : '>';
      $condition .= " AND `offers`.`created_at` $dateCompare '$date'";
    }
  }

  $totalCount = $wpdb->get_var("SELECT COUNT(*) FROM `$table_name` as `offers` $condition");

  $skip = $itemsPerPage * ($page - 1);

  $offset = $skip < $itemsCount ? $skip : 0;

  $take = min($itemsPerPage, $totalCount);

	$offers = $wpdb->get_results("SELECT `offers`.*,
																			 `hosts`.`name` as `host_name`,
																			 `hosts`.`preview_image_url` as `host_preview_image_url`
																		FROM `$table_name` `offers`
																LEFT JOIN `$hosts_table` `hosts` ON `offers`.`host_id` = `hosts`.`id`
                                $condition
                                ORDER BY `offers`.`$sortBy` $sortType
                                LIMIT $take
                                OFFSET $offset");

  for ($i = 0; $i < count($offers); $i++) {
    $prices = $wpdb->get_results($wpdb->prepare("SELECT * FROM `{$wpdb->prefix}spp_prices` WHERE `offer_id` = %d", $offers[$i]->id));
    $offers[$i]->prices = $prices;
  }

	return rest_ensure_response([
		'message'       => 'Offers fetched successfully',
    'total_count'   => $totalCount,
    'items_count'   => count($offers),
    'page'          => $skip < $itemsCount ? $page * 1 : 1,
    'pages_count'   => ceil($totalCount / max($itemsPerPage, 1)),
		'offers'        => $offers,
	]);
}

function subscribe_to_offer(WP_REST_Request $request) {
  global $wpdb;

  $table_name = $wpdb->prefix . 'spp_subscriptions';

  $offer_id = sanitize_text_field($request->get_param('id'));
  $price_id = sanitize_text_field($request->get_param('price_id'));

  if (empty($offer_id) || empty($price_id)) {
    return new WP_Error('missing_fields', 'Please provide all required fields', ['status' => 400]);
  }

  $offer = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$wpdb->prefix}spp_offers` WHERE `id` = %d", $offer_id));

  if (!$offer) {
    return new WP_Error('offer_not_found', 'Offer not found', ['status' => 404]);
  }

  $price = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$wpdb->prefix}spp_prices` WHERE `id` = %d", $price_id));

  if (!$price) {
    return new WP_Error('price_not_found', 'Price not found', ['status' => 404]);
  }

  $user_id = get_current_user_id();
  $price_id = $price->id;

  $wpdb->insert($table_name, compact('user_id', 'offer_id', 'price_id'));

  return rest_ensure_response([
    'message' => 'Subscribed successfully',
  ]);
}

function get_payment_informations() {
  $ccp_rip = get_option('ccp_rip');
  $paypal_email = !empty(get_option('paypal_email')) ? get_option('paypal_email') : null;
  $paysera_email = !empty(get_option('paysera_email')) ? get_option('paysera_email') : null;

  return rest_ensure_response([
    'message'      => 'Payment informations fetched successfully',
    'informations' => [
      'CCP-RIP' => $ccp_rip,
      'PayPal'  => $paypal_email,
      'Paysera' => $paysera_email,
    ]
  ]);
}

function get_subscriptions(WP_REST_Request $request) {
	global $wpdb;

  $user_id = get_current_user_id();

  $page         = $request->get_param('page') ?? 1;
  $itemsPerPage = $request->get_param('itemsPerPage') ?? 10;
  $sortBy       = $request->get_param('sortBy') ?? 'created_at';
  $sortType     = $request->get_param('sortType') ?? 'asc';
  $dateCompare  = $request->get_param('dateCompare'); // at, before, after
  $date         = $request->get_param('date');

	$table_name   = $wpdb->prefix . 'spp_subscriptions';
	$hosts_table  = $wpdb->prefix . 'spp_hosts';
	$offers_table = $wpdb->prefix . 'spp_offers';
	$users_table  = $wpdb->prefix . 'users';

  $condition = "WHERE `subscriptions`.`user_id` = $user_id";

  $itemsCount = $wpdb->get_var("SELECT COUNT(*) FROM `$table_name` as `subscriptions` $condition");

  if ($dateCompare) {
    if ($dateCompare == 'at') {
      $condition .= " AND `subscriptions`.`created_at` = '$date";
    } else {
      $date = $date ?? date('Y-m-d H:i:s');
      $dateCompare = $dateCompare == 'before' ? '<' : '>';
      $condition .= " AND `subscriptions`.`created_at` $dateCompare '$date'";
    }
  }

  $totalCount = $wpdb->get_var("SELECT COUNT(*) FROM `$table_name` as `subscriptions` $condition");

  $skip = $itemsPerPage * ($page - 1);

  $offset = $skip < $itemsCount ? $skip : 0;

  $take = min($itemsPerPage, $totalCount);

  $subscriptions = $wpdb->get_results("SELECT `subscriptions`.*,
                                              `hosts`.`name` as `host_name`,
                                              `hosts`.`description` as `host_description`,
                                              `hosts`.`preview_image_url` as `host_preview_image_url`
                                          FROM `$table_name` `subscriptions`
                                        LEFT JOIN `$offers_table` `offers` ON `subscriptions`.`offer_id` = `offers`.`id`
                                        LEFT JOIN `$hosts_table` `hosts` ON `offers`.`host_id` = `hosts`.`id`
                                        LEFT JOIN `$users_table` `users` ON `subscriptions`.`user_id` = `users`.`ID`
                                        $condition
                                        ORDER BY `subscriptions`.`$sortBy` $sortType
                                        LIMIT $take
                                        OFFSET $offset");

  foreach ($subscriptions as &$subscription) {
    $subscription->proof_url = spp_get_subscription_proof_url($user_id, $subscription->id);
    unset($subscription->proof_path);
  }

	return rest_ensure_response([
		'message'       => 'Subscriptions fetched successfully',
    'total_count'   => $totalCount,
    'items_count'   => count($subscriptions),
    'page'          => $skip < $itemsCount ? $page * 1 : 1,
    'pages_count'   => ceil($totalCount / max($itemsPerPage, 1)),
		'subscriptions' => $subscriptions,
	]);
}

function get_subscription(WP_REST_Request $request) {
  global $wpdb;

  $table_name = $wpdb->prefix . 'spp_subscriptions';
  $hosts_table = $wpdb->prefix . 'spp_hosts';
  $offers_table = $wpdb->prefix . 'spp_offers';
  $users_table = $wpdb->prefix . 'users';

  $user_id = get_current_user_id();

  $subscription_id = intval($request->get_param('id'));
  $subscription = $wpdb->get_row($wpdb->prepare(
    "SELECT `subscriptions`.*,
            `hosts`.`name` as `host_name`,
            `hosts`.`description` as `host_description`,
            `hosts`.`preview_image_url` as `host_preview_image_url`
        FROM `$table_name` `subscriptions`
    LEFT JOIN `$offers_table` `offers` ON `subscriptions`.`offer_id` = `offers`.`id`
    LEFT JOIN `$hosts_table` `hosts` ON `offers`.`host_id` = `hosts`.`id`
    LEFT JOIN `$users_table` `users` ON `subscriptions`.`user_id` = `users`.`ID`
    WHERE `subscriptions`.`id` = %d
      AND `subscriptions`.`user_id` = %d",
    $subscription_id, $user_id
  ));

  if (!$subscription)
    return new WP_Error('no_subscription', 'Subscription not found', ['status' => 404]);

  $subscription->proof_url = spp_get_subscription_proof_url($user_id, $subscription->id);
  unset($subscription->proof_path);

  return rest_ensure_response([
    'message'      => 'Subscription fetched successfully',
    'subscription' => $subscription,
  ]);
}

function upload_subscription_proof(WP_REST_Request $request) {
  global $wpdb;

  $table_name = $wpdb->prefix . 'spp_subscriptions';

  $subscription_id = intval($request->get_param('id'));
  $proof = $request->get_file_params()['proof'];

  if (empty($proof)) {
    return new WP_Error('missing_proof', 'Please provide a proof', ['status' => 400]);
  }

  $user_id = get_current_user_id();

  $subscription = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM `$table_name` WHERE `id` = %d AND `user_id` = %d",
    $subscription_id, $user_id
  ));

  if (!$subscription) {
    return new WP_Error('subscription_not_found', 'Subscription not found', ['status' => 404]);
  }

  require_once(ABSPATH . 'wp-admin/includes/image.php');
  require_once(ABSPATH . 'wp-admin/includes/file.php');
  require_once(ABSPATH . 'wp-admin/includes/media.php');

  $proof_uploaded_id = media_handle_upload('proof', 0);
  if (is_wp_error($proof_uploaded_id)) {
    return new WP_Error('proof_upload_failed', 'Proof upload failed', ['status' => 500, 'error' => $proof_uploaded_id]);
  }

  $proof_uploaded_path = get_attached_file($proof_uploaded_id);

  $proof_path = spp_set_subscription_proof($user_id, $subscription_id, $proof_uploaded_path);

  $wpdb->update($table_name, ['proof_path' => $proof_path, 'status' => 'verifing_proof'], ['id' => $subscription_id]);

  return rest_ensure_response([
    'message'    => 'Proof uploaded successfully',
    'proof_url'  => $proof_url,
    'proof_path' => $proof_path,
  ]);
}

function get_subscription_watch_info(WP_REST_Request $request) {
	global $wpdb;

	$table_name = $wpdb->prefix . 'spp_subscriptions';
	$hosts_table = $wpdb->prefix . 'spp_hosts';
	$offers_table = $wpdb->prefix . 'spp_offers';

	$subscription_id = intval($request->get_param('id'));
	$subscription = $wpdb->get_row($wpdb->prepare(
	  "SELECT
       `subscriptions`.*,
       `hosts`.`cookie`,
       `hosts`.`host` as `host_url`,
       `hosts`.`blocked_urls`
     FROM `$table_name` `subscriptions`
     LEFT JOIN `$offers_table` `offers` ON `subscriptions`.`offer_id` = `offers`.`id`
     LEFT JOIN `$hosts_table` `hosts` ON `offers`.`host_id` = `hosts`.`id`
     WHERE `subscriptions`.`id` = %d
       AND `subscriptions`.`user_id` = %d",
		$subscription_id, get_current_user_id()
	));

	if ($subscription) {
    if ($subscription->status == 'expired' || ($subscription->expired_at && $subscription->expired_at < date('Y-m-d H:i:s'))) {
      if ($subscription->status != 'expired') {
        $wpdb->update($table_name, ['status' => 'expired'], ['id' => $subscription_id]);
      }
      return new WP_Error('subscription_expired', 'Sorry, your subscription has expired', ['status' => 400]);
    } elseif ($subscription->status == 'waiting_proof') {
      return new WP_Error('subscription_waiting_proof', 'Please upload a payment proof to activate your subscription', ['status' => 400]);
    } elseif ($subscription->status == 'verifing_proof') {
      return new WP_Error('subscription_verifing_proof', 'Please wait while we verify your payment proof', ['status' => 400]);
    } elseif ($subscription->status == 'deactivate') {
      return new WP_Error('subscription_deactivated', 'This subscription has been deactivated', ['status' => 400]);
    }
		return rest_ensure_response([
			'message'      => 'Subscription cookies fetched successfully',
      'host_url'     => $subscription->host_url,
      'blocked_urls' => $subscription->blocked_urls,
			'cookies'      => $subscription->cookie,
		]);
	} else {
		return new WP_Error('no_subscription', 'Subscription not found', ['status' => 404]);
	}
}
