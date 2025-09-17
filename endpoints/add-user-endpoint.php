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
            'type' => 'integer',
            'required' => false,
            'sanitize_callback' => 'sanitize_text_field',
        ],
        'ott_plan' => [
            'description' => 'OTT plan for the user.',
            'type' => 'integer',
            'required' => false,
            'sanitize_callback' => 'sanitize_text_field',
        ],
        'start_date' => [
            'description' => 'Start date for the user plan.',
            'type' => 'string',
            'required' => false,
            'sanitize_callback' => function ($date) {
                // Accepts empty or valid date string (YYYY-MM-DD)
                $date = sanitize_text_field($date);
                if (empty($date))
                    return '';
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date))
                    return $date;
                return '';
            },
        ],
        'end_date' => [
            'description' => 'End date for the user plan.',
            'type' => 'string',
            'required' => false,
            'sanitize_callback' => function ($date) {
                $date = sanitize_text_field($date);
                if (empty($date))
                    return '';
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date))
                    return $date;
                return '';
            },
        ],
    ],
]);
//check if the user has permission to create user like Administator
function check_create_user_permission()
{
    return current_user_can('create_users');
}
// Callback function
function handle_add_user($request)
{
    // (Paste the handle_add_user function code here)
    $params = $request->get_json_params();
    global $wpdb;
    // Collect user data for wp_insert_user
    $user_data = [
        'user_login' => $params['username'] ?? '',
        'user_pass' => $params['password'] ?? '',
        'user_email' => $params['email'] ?? '',
        'role' => $params['roles'] ?? '',
        'display_name' => $params['name'] ?? '',
        'first_name' => $params['first_name'] ?? '',
        'last_name' => $params['last_name'] ?? '',
        'nicename' => $params['nicename'] ?? '',
        'meta_input' => [
            'wifi_plan' => $params['wifi_plan'] ?? 0,
            'ott_plan' => $params['ott_plan'] ?? 0,
            'start_date' => $params['start_date'] ?? null,
            'end_date' => $params['end_date'] ?? null,
        ]
    ];

    // Insert the user
    $user_id = wp_insert_user($user_data);

    if (is_wp_error($user_id)) {
        return new WP_REST_Response(['error' => $user_id->get_error_message()], 400);
    }
   // If the user insertion is successful, the meta fields are automatically added.
    return new WP_REST_Response([
        'success' => true,
        'user_id' => $user_id,
        'username' => $user_data['user_login'],
        'message' => 'User and Plan Meta Successfully Created.'
    ], 201);
}