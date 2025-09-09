<?php
// Register user-plan endpoint
register_rest_route('wp/v2', 'user-plan/(?P<id>\d+)', [
    'methods' => ['GET', 'PUT'],
    'callback' => 'handle_user_plans_endpoint',
    'permission_callback' => 'check_user_permission',
    'args' => [
        'id' => [
            'validate_callback' => function($param, $request, $key) {
                return is_numeric($param);
            }
        ]
    ]
]);

// A helper function to split a full name into first and last name
function split_name($full_name) {
    $parts = explode(' ', trim($full_name));
    $first_name = array_shift($parts);
    $last_name = implode(' ', $parts);
    return [
        'first_name' => $first_name,
        'last_name' => $last_name
    ];
}

/**
 * Helper function to retrieve and format user details for the response.
 * @param array $results The results from the user plans database query.
 * @return array The formatted user data.
 */
function get_user_details_for_response($results,$method) {
        error_log(print_r("result in get_user_function: ",true));
        error_log(print_r($results, true));
    if (function_exists('get_user_by') && isset($results[0]["username"])) {
        $user = get_user_by('login', $results[0]["username"]);
        error_log(print_r("User: ",true));
        error_log(print_r($user, true));
        if ($user) {
            $names = split_name($user->display_name);
            
            // Safely get the first role from the user's roles array.
            $roles_string = isset($user->roles[0]) ? $user->roles[0] : null;

            $filter_user = [
                'user_id' => $user->ID,
                'nicename' => $user->user_nicename,
                'display_name' => $user->display_name,
                'roles' => $roles_string,
                'first_name' => $names['first_name'],
                'last_name' => $names['last_name']
            ];

            error_log(print_r($filter_user." filter_user", true));
            // Combine the results with the new name and filtered user fields
            $response_data = array_merge($results[0], $filter_user);
            return $response_data;
        }
    }
    return $results[0]; // Fallback to original data if user lookup fails
}

// Function to handle the REST API endpoint logic
function handle_user_plans_endpoint($request) {
    global $wpdb;

    // Get the request method (e.g., GET, POST, PUT, DELETE)
    $method = $request->get_method();

    // Get the user ID from the request
    $id = intval($request->get_param('id'));

    // Get the current user's roles and email for security checks
    $user_roles = wp_get_current_user()->roles;
    $user_email = wp_get_current_user()->user_email;

    $current_user = wp_get_current_user();
    if (!$current_user->ID) {
        return new WP_REST_Response(['error' => 'Authentication failed'], 401);
    }

    if ($method === 'GET') {
        // Build the base query. Only select the fields you need.
        $query = "SELECT up.plan_id, up.username, up.email, up.start_date, up.end_date, up.wifi_plan, up.ott_plan FROM user_plans AS up WHERE up.plan_id = %d";
        
        $params = [$id];

        // If the user is not an administrator, add a security check for their email
        if (!in_array('administrator', $user_roles)) {
            $query .= " AND up.email = %s";
            $params[] = $user_email;
        }

        // Prepare and execute the query
        $results = $wpdb->get_results($wpdb->prepare($query, ...$params), ARRAY_A);
        
        if (empty($results)) {
            return new WP_REST_Response(['message' => 'Plan not found with id ' . $id], 404);
        }

        // Use the new helper function to get formatted user details
        $response_data = get_user_details_for_response($results,$method);
        
        return new WP_REST_Response($response_data, 200);
    } elseif ($method === 'PUT') {
        if (!in_array('administrator', $user_roles)) {
            return new WP_REST_Response(['error' => 'Insufficient permissions'], 403);
        }

        $table_name = 'user_plans';
        $params = $request->get_json_params();

        // Get the plan_id from the request
        $plan_id = $id;
        
        // Fetch the current user data from the database
        $current_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE plan_id = %d", $plan_id), ARRAY_A);

        if (!$current_data) {
            return new WP_REST_Response(['message' => 'Plan not found.'], 404);
        }
        $response_data = get_user_details_for_response([$current_data],$method);
        error_log(print_r("current_data: ", true));
        error_log(print_r($current_data, true));
        error_log(print_r("response_data: ", true));
        error_log(print_r($response_data, true));
        // Extract the relevant submitted data for comparison
        $submitted_data = [
            'wifi_plan' => isset($params['wifi_plan']) ? sanitize_text_field($params['wifi_plan']) : $response_data['wifi_plan'],
            'ott_plan' => isset($params['ott_plan']) ? sanitize_text_field($params['ott_plan']) : $response_data['ott_plan'],
            'start_date' => isset($params['start_date']) ? sanitize_text_field($params['start_date']) : $response_data['start_date'],
            'end_date' => isset($params['end_date']) ? sanitize_text_field($params['end_date']) : $response_data['end_date'],
            'username' => isset($params['username']) ? sanitize_text_field($params['username']) : $response_data['username'],
            'email' => isset($params['email']) ? sanitize_email($params['email']) : $response_data['email'],
            
            'nicename' => isset($params['nicename']) ? sanitize_text_field($params['nicename']) : $response_data['nicename'],
            'display_name' => isset($params['display_name']) ? sanitize_text_field($params['display_name']) : $response_data['display_name'],
            'roles' => isset($params['roles']) ? sanitize_text_field($params['roles']) : $response_data['roles'],
            'first_name' => isset($params['first_name']) ? sanitize_text_field($params['first_name']) : $response_data['first_name'],
            'last_name' => isset($params['last_name']) ? sanitize_text_field($params['last_name']) : $response_data['last_name']
            
        ];
        error_log(print_r("submitted_data", true));
        error_log(print_r($submitted_data, true));
        // Compare submitted data with current data
        $is_data_changed = false;
        foreach ($submitted_data as $key => $value) {
            if ($value !== $response_data[$key]) {
                $is_data_changed = true;
                break;
            }
        }

        if (!$is_data_changed) {
            return new WP_REST_Response(['message' => 'No changes detected. Update not performed.'], 200);
        }

        // Prepare data to be updated
        $data_to_update = $submitted_data;
        error_log(print_r("data_to_update", true));
        error_log(print_r($data_to_update, true));
        
        $user_plans_keys = ['wifi_plan','ott_plan','start_date','end_date','username','email'];
        $wp_user_keys = ['username','email','nicename','display_name','roles'];

        $user_plans_data = array_intersect_key($data_to_update,array_flip($user_plans_keys));
        $wp_user_data = array_intersect_key($data_to_update,array_flip($wp_user_keys));

        $wp_user_data['ID'] = $response_data['user_id'];
        // // Perform the update
        $update_user_plan_result = $wpdb->update($table_name, $user_plans_data, ['plan_id' => $plan_id]);

        if ($update_user_plan_result === false) {
            return new WP_REST_Response(['message' => 'Failed to update user plans.', 'db_error' => $wpdb->last_error], 500);
        }
        // Always perform a check to ensure the function exists, especially if used in a plugin or theme file
        if ( ! function_exists('wp_update_user') ) {
            return;
        }

        $update_wp_user_result = wp_update_user($wp_user_data);

        if ( is_wp_error($update_wp_user_result)  ) {
            // There was an error during the update
            return new WP_REST_Response(['message' => 'Failed to update wp_user.', $update_wp_user_result->get_error_message()], 500);
            
        } else {
            // The update was successful
            // echo "User data updated successfully.";
            return new WP_REST_Response(['message' => 'User plans updated successfully.'], 200);
        }

    }
    
    // Fallback response for unsupported methods
    return new WP_REST_Response(['message' => 'Method not supported.'], 405);
}