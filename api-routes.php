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
	register_rest_route('custom/v2', '/subscriptions/(?P<id>\d+)/cookies', [
		'methods' => ['GET'],
		'callback' => 'get_subscription_cookies',
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
        'id' => $user->ID,
        'username' => $user->user_login,
        'email' => $user->user_email,
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'description' => $user->description,
        'avatar_url' => get_avatar_url($user->ID),
      ), 200);
    case 'POST':
      $first_name = sanitize_text_field($request->get_param('first_name'));
      $last_name = sanitize_text_field($request->get_param('last_name'));
      $description = sanitize_textarea_field($request->get_param('description'));
      $avatar = $request->get_file_params()['avatar'];

      if (empty($first_name) && empty($last_name) && empty($description) && empty($avatar)) {
        return new WP_Error('no_fields', 'No fields to update', array('status' => 400));
      }

      $user_data = array(
        'ID' => $user->ID,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'description' => $description,
      );

      if ($avatar) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $avatar_id = media_handle_upload('avatar', 0);
        if (is_wp_error($avatar_id)) {
          return new WP_Error('avatar_upload_failed', 'Avatar upload failed', array('status' => 500));
        }

        // get the avatar URL
        $avatar_url = wp_get_attachment_url($avatar_id);
        update_user_meta($user->ID, 'avatar_url', $avatar_url);
      }

      wp_update_user($user_data);

      return new WP_REST_Response(array(
        'message' => 'User info updated successfully',
      ), 200);
    default:
      return new WP_Error('invalid_method', 'Method not allowed', array('status' => 405));
  }
}

function get_offers() {
  global $wpdb;

	$table_name = $wpdb->prefix . 'spp_offers';
	$hosts_table = $wpdb->prefix . 'spp_hosts';


	$offers = $wpdb->get_results("SELECT `offers`.*,
																			 `hosts`.`name` as `host_name`,
																			 `hosts`.`preview_image_url` as `host_preview_image_url`
																		FROM `$table_name` `offers`
																LEFT JOIN `$hosts_table` `hosts` ON `offers`.`host_id` = `hosts`.`id`");

	return rest_ensure_response([
		'message' => 'Offers fetched successfully',
		'offers' => $offers,
	]);
}

function subscribe_to_offer(WP_REST_Request $request) {
  global $wpdb;

  $table_name = $wpdb->prefix . 'spp_subscriptions';

  $offer_id = intval($request->get_param('id'));

  $offer = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$wpdb->prefix}spp_offers` WHERE `id` = %d", $offer_id));

  if (!$offer) {
    return new WP_Error('offer_not_found', 'Offer not found', ['status' => 404]);
  }

  $user_id = get_current_user_id();
  $price = floatval($request->get_param('price'));

  $wpdb->insert($table_name, compact('user_id', 'offer_id', 'price'));

  return rest_ensure_response([
    'message' => 'Subscribed successfully',
  ]);
}

function get_subscriptions() {
	global $wpdb;

	$table_name = $wpdb->prefix . 'spp_subscriptions';
	$hosts_table = $wpdb->prefix . 'spp_hosts';
	$users_table = $wpdb->prefix . 'users';

	$subscriptions = $wpdb->get_results("SELECT `subscriptions`.*,
																			 `hosts`.`name` as `host_name`,
																			 `hosts`.`description` as `host_description`,
																			 `hosts`.`preview_image_url` as `host_preview_image_url`,
																			 `hosts`.`host` as `host_url`,
																			 `hosts`.`blocked_routes` as `host_blocked_routes`,
																			 `users`.`display_name` as `user_name`
																		FROM `$table_name` `subscriptions`
																LEFT JOIN `$hosts_table` `hosts` ON `subscriptions`.`host_id` = `hosts`.`id`
																LEFT JOIN `$users_table` `users` ON `subscriptions`.`user_id` = `users`.`ID`");

	return rest_ensure_response([
		'message' => 'Subscriptions fetched successfully',
		'subscriptions' => $subscriptions,
	]);
}

function get_subscription_cookies(WP_REST_Request $request) {
	global $wpdb;

	$table_name = $wpdb->prefix . 'spp_subscriptions';
	$hosts_table = $wpdb->prefix . 'spp_hosts';

	$subscription_id = intval($request->get_param('id'));
	$subscription = $wpdb->get_row($wpdb->prepare(
		"SELECT `hosts`.`cookie`
		 FROM `$table_name` `subscriptions`
		 LEFT JOIN `$hosts_table` `hosts` ON `subscriptions`.`host_id` = `hosts`.`id`
		 WHERE `subscriptions`.`id` = %d",
		$subscription_id
	));

	if ($subscription) {
		return rest_ensure_response([
			'message' => 'Subscription cookies fetched successfully',
			'cookie' => $subscription->cookie,
		]);
	} else {
		return new WP_Error('no_subscription', 'Subscription not found', ['status' => 404]);
	}
}
