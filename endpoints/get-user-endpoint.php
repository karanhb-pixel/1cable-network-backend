<?php
// Register user-plans endpoint
register_rest_route('wp/v2/iws/v1', 'users', [
    'methods' => 'GET',
    'callback' => 'get_user_plan',
    'permission_callback' => 'check_user_permission',
    'args' => [
        'plan_id' => [
            'description' => 'Plan ID to filter results.',
            'type' => 'integer',
            'required' => false,
            'validate_callback' => function ($value) {
                return is_numeric($value) && $value > 0;
            },
        ],
    ]
]);

// Callback function
function get_user_plan($request)
{
    // (Paste the get_user_plans function code here)
    $current_user = wp_get_current_user();
    $user_roles = $current_user->roles;
    $user_id = $current_user->ID;
    $user_email = $current_user->user_email;
    $plan_id = $request->get_param('plan_id');

    global $wpdb;
    $user_plans_table = 'user_plans';
    $wifi_plans_table = $wpdb->prefix . 'wifi_plans';
    $ott_plans_table = $wpdb->prefix . 'ott_plans';
    
    if (!$user_id) {
        return new WP_REST_Response(data: ['error' => 'Authentication failed'], status: 401);
    }

    $results = [];

    if (in_array('administrator', $user_roles)) {
        if ($plan_id) {
            $sql_query = $wpdb->prepare(
                "SELECT up.plan_id, up.username, up.start_date, up.end_date, up.email, wp.speed AS wifi_speed, ot.duration AS ott_duration 
                 FROM {$user_plans_table} AS up 
                 LEFT JOIN {$wifi_plans_table} AS wp ON up.wifi_plan = wp.plan_id 
                 LEFT JOIN {$ott_plans_table} AS ot ON up.ott_plan = ot.plan_id 
                 WHERE up.plan_id = %d",
                $plan_id
            );



        } else {
            // Case 2: Admin requests all user details (no email parameter).
            $sql_query = "SELECT up.plan_id, up.username, up.start_date, up.end_date, up.email, wp.speed AS wifi_speed, ot.duration AS ott_duration 
                          FROM {$user_plans_table} AS up 
                          LEFT JOIN {$wifi_plans_table} AS wp ON up.wifi_plan = wp.plan_id 
                          LEFT JOIN {$ott_plans_table} AS ot ON up.ott_plan = ot.plan_id";
        }

    } else {
        // Case 3: Subscriber requests only their own details.
        // We ignore any 'user_email' parameter for security.
        $sql_query = $wpdb->prepare(
            "SELECT up.plan_id, up.username, up.start_date, up.end_date, up.email, wp.speed AS wifi_speed, ot.duration AS ott_duration 
             FROM {$user_plans_table} AS up 
             LEFT JOIN {$wifi_plans_table} AS wp ON up.wifi_plan = wp.plan_id 
             LEFT JOIN {$ott_plans_table} AS ot ON up.ott_plan = ot.plan_id 
             WHERE up.email = %s",
            $user_email
        );
    }

    // Execute the appropriate query.
    $results = $wpdb->get_results($sql_query, ARRAY_A);

    if (empty($results)) {
        return new WP_REST_Response(['message' => 'No plans found for this user'], 404);
    }

    if ($plan_id && in_array('administrator', $user_roles)) {
        $results_user_email = $results[0]['email'];
        //getting user details from wp_users table
        $user = get_user_by('email', $results_user_email);



        if ($user) {
            $user_details = [
                'user_id' => $user->ID,
                'name' => $user->display_name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'nicename' => $user->user_nicename,
                'display_name' => $user->display_name,
                'roles' => $user->roles[0],
            ];
            // Merge the user details into the first (and only) result.
            $results[0] = array_merge($results[0], $user_details);

        } else {
            return new WP_REST_Response(['error' => 'User details not found in wp'], 404);
        }

    }

    return new WP_REST_Response($results, 200);
}

