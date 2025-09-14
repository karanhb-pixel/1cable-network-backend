<?php
// Register add-user endpoint
register_rest_route('wp/v2/iws/v1', 'users', [
    'methods' => 'POST',
    'callback' => 'handle_add_user',
    'permission_callback' => 'check_create_user_permission',
    'args' => [
            'username' => [
                'description' => 'Login name for the user.',
                'type' => 'string',
                'required' => true,
                'sanitize_callback' => 'sanitize_user',
            ],
            'name' => [
                'description' => 'Display name for the user.',
                'type' => 'string',
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'first_name' => [
                'description' => 'First name for the user.',
                'type' => 'string',
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'last_name' => [
                'description' => 'Last name for the user.',
                'type' => 'string',
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'email' => [
                'description' => 'The email address for the user.',
                'type' => 'string',
                'format' => 'email',
                'required' => true,
                'sanitize_callback' => 'sanitize_email',
                'validate_callback' => 'is_email',
            ],
            'nicename' => [
                'description' => 'The nicename for the user.',
                'type' => 'string',
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'roles' => [
                'description' => 'Role assigned to the user.',
                'type' => 'string',
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'password' => [
                'description' => 'Password for the user (never included).',
                'type' => 'string',
                'required' => true,
            ],
            'wifi_plan' => [
                'description' => 'Wifi plan for the user.',
                'type' => 'string',
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'ott_plan' => [
                'description' => 'OTT plan for the user.',
                'type' => 'string',
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'start_date' => [
                'description' => 'Start date for the user plan.',
                'type' => 'string',
                'required' => false,
                'sanitize_callback' => function($date) {
                    // Accepts empty or valid date string (YYYY-MM-DD)
                    $date = sanitize_text_field($date);
                    if (empty($date)) return '';
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return $date;
                    return '';
                },
            ],
            'end_date' => [
                'description' => 'End date for the user plan.',
                'type' => 'string',
                'required' => false,
                'sanitize_callback' => function($date) {
                    $date = sanitize_text_field($date);
                    if (empty($date)) return '';
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return $date;
                    return '';
                },
            ],
        ],
]);
//check if the user has permission to create user like Administator
function check_create_user_permission(){
    return current_user_can('create_users');
}
// Callback function
function handle_add_user($request) {
    // (Paste the handle_add_user function code here)
    $params = $request->get_json_params();
    global $wpdb;
    $table_name =  'user_plans';

    
    $username    = isset($params['username'])    ? $params['username']    : '';
    $name        = isset($params['name'])        ? $params['name']        : '';
    $first_name  = isset($params['first_name'])  ? $params['first_name']  : '';
    $last_name   = isset($params['last_name'])   ? $params['last_name']   : '';
    $email       = isset($params['email'])       ? $params['email']       : '';
    $nicename    = isset($params['nicename'])    ? $params['nicename']    : '';
    $roles       = isset($params['roles'])       ? $params['roles']       : '';
    $password    = isset($params['password'])    ? $params['password']    : '';
    $wifi_plan 	= (empty($params['wifi_plan']) || trim($params['wifi_plan']) === '0') ? 0 : $params['wifi_plan'];
    $ott_plan 	= (empty($params['ott_plan']) || trim($params['ott_plan']) === '0') ? 0 : $params['ott_plan'];
    $start_date = !empty($params['start_date']) ? $params['start_date'] : null;
    $end_date 	= !empty($params['end_date']) ? $params['end_date'] : null;


     // Create the user
    $user_id = wp_insert_user( userdata: [
        'user_login'    => $username,
        'user_pass'     => $password,
        'user_email'    => $email,
        'role'          => $roles,
        'display_name'  => $name,
        'first_name'    => $first_name,
        'last_name'     => $last_name,
        'nicename'      => $nicename,
    ] );

    if (is_wp_error($user_id)) {
        return new WP_REST_Response(['error' => $user_id->get_error_message()], 400);
    }

     // Now, insert the plan data into the custom user_plans table.
    $insert_data = [
        'username'      => $username,
        'email'         => $email,
        'wifi_plan'     => $wifi_plan,
        'ott_plan'      => $ott_plan,
        'start_date'    => $start_date,
        'end_date'      => $end_date,
    ];
    
    // We must provide a format specifier for every piece of data.
    $insert_formats = [
        '%s', // username
        '%s', // email
        '%d', // wifi_plan (always an integer)
        '%d', // ott_plan (always an integer)
        '%s', // start_date
        '%s', // end_date
    ];


    $insert_result = $wpdb->insert(
        $table_name,
        $insert_data,
        $insert_formats
    );

    // If the plan insertion fails, we must manually roll back and delete the user.
    if (!$insert_result) {
        // Log the MySQL error for debugging.
        error_log('MySQL Insert Error (user_plans): ' . $wpdb->last_error);

        // First, check if the function exists before calling it.
        // Also, log the user ID to confirm what is being passed to the function.
        error_log("Attempting to delete user with ID: " . $user_id);
        if (function_exists('wp_delete_user')) {
            $delete_result = wp_delete_user($user_id);

            if (is_wp_error($delete_result)) {
                // Log if the user deletion also fails.
                error_log('Failed to delete user on rollback: ' . $delete_result->get_error_message());
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Failed to add plan data and rollback user creation.',
                    'db_error' => $wpdb->last_error,
                    'rollback_error' => $delete_result->get_error_message()
                ], 500);
            }
        } else {
             error_log('wp_delete_user function does not exist.');
        }

        // Return a response indicating the plan insertion failure.
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Failed to add plan data.',
            'db_error' => $wpdb->last_error
        ], 500);
    }

    // If both operations succeeded, return a success response.
    return new WP_REST_Response([
        'success' => true,
        'user_id' => $user_id,
        'username' => $username,
        'message' => 'User and Plan Successfully Created.'
    ], 201);
}