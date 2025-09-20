<?php
// Register user-plans endpoint
register_rest_route('wp/v2/iws/v1', 'users', [
    'methods' => 'GET',
    'callback' => 'get_user_plan',
    'permission_callback' => 'check_user_permission',
    'args' => [
        'user_id' => [ // Changed from plan_id to user_id for better logic
            'description' => 'User ID to filter results.',
            'type' => 'integer',
            'required' => false,
            'validate_callback' => function ($value) {
                return is_numeric($value) && $value > 0;
            },
        ],
    ]
]);



/**
 * Callback function to get user plan data from user meta.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
function get_user_plan($request)
{
    global $wpdb;
    $current_user = wp_get_current_user();
    $wifi_plans_table = $wpdb->prefix . 'wifi_plans';
    $ott_plans_table = $wpdb->prefix . 'ott_plans';
    $results = [];

    if (!$current_user->ID) {
        return new WP_REST_Response(['error' => 'Authentication failed'], 401);
    }

    $user_id_from_request = $request->get_param('user_id');
    $args = [];

    if (in_array('administrator', $current_user->roles)) {
        // Administrator can request a specific user or all users
        if ($user_id_from_request) {
            $args['include'] = [$user_id_from_request];
        }
        $args['role__not_in'] = ['administrator']; // Exclude administrators from the list
    } else {
        // Subscriber can only request their own details
        $args['include'] = [$current_user->ID];
    }

    // Fetch users based on the arguments
    $users = get_users($args);

    if (empty($users)) {
        return new WP_REST_Response(['message' => 'No users found.'], 404);
    }

    // Process each user to get their plan details
    foreach ($users as $user) {
        // Get user meta fields
        $wifi_plan_id = get_user_meta($user->ID, 'wifi_plan', true);
        $ott_plan_id = get_user_meta($user->ID, 'ott_plan', true);

        $plan_details = [
            'user_id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
            'nicename' => $user->nicename,
            'roles' => $user->roles[0],
            'first_name' => get_user_meta($user->ID, 'first_name', true),
            'last_name' => get_user_meta($user->ID, 'last_name', true),
            'start_date' => get_user_meta($user->ID, 'start_date', true),
            'end_date' => get_user_meta($user->ID, 'end_date', true),
        ];

        // Join with wifi_plans table
        if ($wifi_plan_id) {
            $wifi_plan = $wpdb->get_row($wpdb->prepare("SELECT speed FROM {$wifi_plans_table} WHERE plan_id = %d", $wifi_plan_id), ARRAY_A);
            $plan_details['wifi_speed'] = $wifi_plan['speed'] ?? null;
        }

        // Join with ott_plans table
        if ($ott_plan_id) {
            $ott_plan = $wpdb->get_row($wpdb->prepare("SELECT duration FROM {$ott_plans_table} WHERE plan_id = %d", $ott_plan_id), ARRAY_A);
            $plan_details['ott_duration'] = $ott_plan['duration'] ?? null;
        }

        $results[] = $plan_details;
    }

    if (empty($results)) {
        return new WP_REST_Response(['message' => 'No plans found for this user.'], 404);
    }
    // error_log('result in get_user : '. json_encode($results));
    foreach ($results as $result) {

    }

    return new WP_REST_Response($results, 200);
}
