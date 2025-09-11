<?php
// Register edit-user endpoint
register_rest_route('wp/v2/iws/v1', 'edit-user/(?P<id>\d+)', [
    'methods' => 'PUT',
    'callback' => 'handle_edit_user',
    'permission_callback' => 'check_edit_user_permission',
    'args' => [
        'id' => [
            'description' => 'User ID to edit.',
            'type' => 'integer',
            'required' => true,
            'validate_callback' => function($value) {
                return is_numeric($value) && $value > 0;
            },
        ],
        'username' => [
            'description' => 'Login name for the user.',
            'type' => 'string',
            'required' => false,
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
            'required' => false,
            'sanitize_callback' => 'sanitize_email',
            'validate_callback' => 'is_email',
        ],
        'nickname' => [
            'description' => 'The nickname for the user.',
            'type' => 'string',
            'required' => false,
            'sanitize_callback' => 'sanitize_text_field',
        ],
        'roles' => [
            'description' => 'Role assigned to the user.',
            'type' => 'string',
            'required' => false,
            'sanitize_callback' => 'sanitize_text_field',
        ],
        'password' => [
            'description' => 'Password for the user (never included).',
            'type' => 'string',
            'required' => false,
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

// Check if the user has permission to edit user like Administrator
function check_edit_user_permission() {
    return current_user_can('edit_users');
}

// Callback function
function handle_edit_user($request) {
    $params = $request->get_json_params();
    $user_id = (int) $request->get_url_params()['id'];
    global $wpdb;
    $table_name = 'user_plans';

    // Validate user exists
    $user = get_user_by('ID', $user_id);
    if (!$user) {
        return new WP_REST_Response(['error' => 'User not found.'], 404);
    }

    // Prepare update data for user
    $user_update_data = [];
    if (isset($params['username']) && !empty($params['username'])) {
        $user_update_data['user_login'] = $params['username'];
    }
    if (isset($params['name']) && !empty($params['name'])) {
        $user_update_data['display_name'] = $params['name'];
    }
    if (isset($params['first_name'])) {
        $user_update_data['first_name'] = $params['first_name'];
    }
    if (isset($params['last_name'])) {
        $user_update_data['last_name'] = $params['last_name'];
    }
    if (isset($params['email']) && !empty($params['email'])) {
        $user_update_data['user_email'] = $params['email'];
    }
    if (isset($params['nickname'])) {
        $user_update_data['nickname'] = $params['nickname'];
    }
    if (isset($params['roles']) && !empty($params['roles'])) {
        $user_update_data['role'] = $params['roles'];
    }
    if (isset($params['password']) && !empty($params['password'])) {
        $user_update_data['user_pass'] = $params['password'];
    }
    $user_update_data['ID'] = $user_id;

    // Update the user
    if (!empty($user_update_data)) {
        $update_result = wp_update_user($user_update_data);
        if (is_wp_error($update_result)) {
            return new WP_REST_Response(['error' => $update_result->get_error_message()], 400);
        }
    }

    // Prepare update data for user_plans
    $plan_update_data = [];
    $plan_update_formats = [];
    if (isset($params['wifi_plan'])) {
        $plan_update_data['wifi_plan'] = (empty($params['wifi_plan']) || trim($params['wifi_plan']) === '0') ? 0 : $params['wifi_plan'];
        $plan_update_formats[] = '%d';
    }
    if (isset($params['ott_plan'])) {
        $plan_update_data['ott_plan'] = (empty($params['ott_plan']) || trim($params['ott_plan']) === '0') ? 0 : $params['ott_plan'];
        $plan_update_formats[] = '%d';
    }
    if (isset($params['start_date'])) {
        $plan_update_data['start_date'] = !empty($params['start_date']) ? $params['start_date'] : null;
        $plan_update_formats[] = '%s';
    }
    if (isset($params['end_date'])) {
        $plan_update_data['end_date'] = !empty($params['end_date']) ? $params['end_date'] : null;
        $plan_update_formats[] = '%s';
    }
    if (isset($params['username']) && !empty($params['username'])) {
        $plan_update_data['username'] = $params['username'];
        $plan_update_formats[] = '%s';
    }
    if (isset($params['email']) && !empty($params['email'])) {
        $plan_update_data['email'] = $params['email'];
        $plan_update_formats[] = '%s';
    }

    // Update user_plans table
    if (!empty($plan_update_data)) {
        $where = ['email' => $user->user_email]; // Assuming username is unique identifier in user_plans
        $update_result = $wpdb->update($table_name, $plan_update_data, $where, $plan_update_formats, ['%s']);
        if ($update_result === false) {
            error_log('MySQL Update Error (user_plans): ' . $wpdb->last_error);
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Failed to update plan data.',
                'db_error' => $wpdb->last_error
            ], 500);
        }
    }

    // Return success response
    return new WP_REST_Response([
        'success' => true,
        'user_id' => $user_id,
        'message' => 'User updated successfully.'
    ], 200);
}